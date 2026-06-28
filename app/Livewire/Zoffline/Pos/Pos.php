<?php

namespace App\Livewire\Zoffline\Pos;

use App\Mail\SalesReceiptMail;
use App\Models\Employe;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodRate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promo;
use App\Models\SecondProduct;
use App\Models\SecondProductVariant;
use App\Models\User;
use App\Services\AccurateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

#[Layout('layouts.z', ['title' => 'Point of Sale'])]
class Pos extends Component
{
    use Traits\WithCart;
    use Traits\WithCustomerAndSales;
    use Traits\WithPaymentAndPromo;
    use Traits\WithCheckoutAndReceipt;

    #[Computed]
    public function activeShift()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        return \App\Models\CashierShift::where('business_unit_id', $buId)
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('status', 'open')
            ->first();
    }


    public $order_date;

    // ─── Wizard State ──────────────────────────────────────────
    public $currentStep = 1; // 1: Customer, 2: Cart, 3: Upsell, 4: Payment
    public $isPiutangSettlement = false;

    public function nextStep()
    {
        if ($this->isPiutangSettlement) {
            return; // Cannot move from step 4
        }
        if ($this->currentStep == 1) {
            // Validasi Step 1: Customer dan Sales
            if (!$this->selectedCustomerId) {
                // Auto set new customer if they filled the search and phone
                if (strlen($this->searchCustomer) >= 2 && !empty($this->customerPhone)) {
                    $this->isNewCustomer = true;
                    $this->customerName = $this->searchCustomer;
                }

                if ($this->isNewCustomer) {
                    $existingProfile = \App\Models\UserProfile::with('user')->where('phone_number', $this->customerPhone)->first();
                    if ($existingProfile) {
                        $this->existingCustomerToUpdate = $existingProfile->user;
                        $this->showConfirmUpdateCustomerModal = true;
                        return;
                    }
                }

                if (!$this->isNewCustomer) {
                    $this->dispatch('toast', title: 'Customer Belum Lengkap', message: 'Pilih customer dari daftar, atau lengkapi Nama & Nomor HP untuk membuat pelanggan baru.', type: 'warning');
                    return;
                }
            }
            if (empty($this->selectedSales)) {
                $this->dispatch('toast', title: 'Sales Belum Dipilih', message: 'Pilih minimal 1 tenaga penjual.', type: 'warning');
                return;
            }
        } elseif ($this->currentStep == 2) {
            // Validasi Step 2: Cart
            if (empty($this->cart)) {
                $this->dispatch('toast', title: 'Keranjang Kosong', message: 'Tambahkan produk ke keranjang terlebih dahulu.', type: 'warning');
                return;
            }
            foreach ($this->cart as $item) {
                if (!isset($item['has_sn']) || $item['has_sn']) {
                    $sns = $item['serial_numbers'] ?? [];
                    $validSns = array_filter($sns, fn($value) => trim($value) !== '');
                    if (empty($validSns) || count($validSns) < $item['qty']) {
                        $this->dispatch('toast', title: 'SN Belum Lengkap', message: 'Pastikan semua item sudah diisi Serial Number sesuai jumlah barang.', type: 'warning');
                        return;
                    }
                }
            }
        }

        if ($this->currentStep == 2 || $this->currentStep == 3) {
            // Validasi Harga 0
            if ($this->hasZeroPriceItem) {
                $this->dispatch('toast', title: 'Harga Tidak Valid', message: 'Terdapat item dengan harga Rp 0. Silakan atur harga terlebih dahulu.', type: 'warning');
                return;
            }
        }

        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }

    public function prevStep()
    {
        if ($this->isPiutangSettlement) {
            return; // Cannot move back from step 4
        }
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($this->isPiutangSettlement && $step != 4) {
            return; // Cannot move to other steps
        }
        $this->currentStep = $step;
    }

    // ─── Modals ────────────────────────────────────────────────
    public $showCheckoutModal = false;
    public $showReceiptModal = false;

    public $completedOrder = null;
    public $showConfirmUpdateCustomerModal = false;
    public $existingCustomerToUpdate = null;

    public function confirmUpdateCustomer()
    {
        // 1. Update nama di tabel users
        $user = $this->existingCustomerToUpdate;
        $user->name = $this->customerName;
        $user->save();

        // 2. Update nama di user_profiles jika ada fieldnya, misal full_name
        if ($user->profile) {
            $user->profile->full_name = $this->customerName;
            $user->profile->save();
        }

        // 3. Update di Accurate (Background Job)
        \App\Jobs\SyncAccurateCustomerJob::dispatch($user, $this->databaseSource);

        // 4. Pilih customer ini untuk transaksi saat ini
        $this->selectedCustomerId = $user->id;
        $this->searchCustomer = $user->name;
        $this->isNewCustomer = false;

        $this->showConfirmUpdateCustomerModal = false;
        $this->dispatch('toast', title: 'Customer Diperbarui', message: 'Data pelanggan berhasil diperbarui.', type: 'success');

        // Lanjut ke pengecekan sales
        if (empty($this->selectedSales)) {
            $this->dispatch('toast', title: 'Sales Belum Dipilih', message: 'Pilih minimal 1 tenaga penjual.', type: 'warning');
            return;
        }

        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }

    public function cancelUpdateCustomer()
    {
        $user = $this->existingCustomerToUpdate;

        $this->selectedCustomerId = $user->id;
        $this->searchCustomer = $user->name;
        $this->isNewCustomer = false;

        $this->showConfirmUpdateCustomerModal = false;

        // Lanjut ke pengecekan sales
        if (empty($this->selectedSales)) {
            $this->dispatch('toast', title: 'Sales Belum Dipilih', message: 'Pilih minimal 1 tenaga penjual.', type: 'warning');
            return;
        }

        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }

    // ─── Draft Sales Properties ──────────────────────────────
    public $showDraftModal = false;
    public $draftOrders = [];
    public $loadedDraftId = null;

    public function openDraft()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $userBranchId = $user->branch_id ?? null;

        $this->draftOrders = Order::with(['user'])
            ->where('order_channel', 'POS')
            ->where('order_status', 'DRAFT')
            ->where('business_unit_id', $user->getActiveBusinessUnitId())
            ->where('branch_id', $userBranchId)
            ->latest()
            ->take(20)
            ->get();

        $this->showDraftModal = true;
    }

    // ─── Piutang Settlement ──────────────────────────────
    public $showPiutangModal = false;
    public $piutangOrders = [];

    public function openPiutang()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $userBranchId = $user->branch_id ?? null;

        $this->piutangOrders = Order::with(['user'])
            ->where('order_channel', 'POS')
            ->where('order_status', 'PIUTANG')
            ->where('business_unit_id', $user->getActiveBusinessUnitId())
            ->where('branch_id', $userBranchId)
            ->latest()
            ->take(20)
            ->get();

        $this->showPiutangModal = true;
    }

    public function loadPiutang($orderId)
    {
        $order = Order::with(['items.variant', 'items.promos', 'user.profile', 'promos'])->find($orderId);

        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Faktur Piutang tidak ditemukan.', type: 'error');
            return;
        }

        // Restore customer
        $this->selectedCustomerId = $order->user_id;
        $this->isNewCustomer = false;
        if ($order->user) {
            $this->customerName = $order->user->name;
            $this->customerPhone = $order->user->profile->phone_number ?? '';
            $this->customerEmail = $order->user->email ?? '';
        }

        // Restore sales (jika ada)
        if ($order->sales_id) {
            $sales = \App\Models\Employe::find($order->sales_id);
            if ($sales) {
                $this->selectedSales = [[
                    'id' => $sales->id,
                    'name' => $sales->name,
                    'employee_no' => $sales->employee_no
                ]];
            }
        }

        // Restore manual discount
        $this->discount_amount = (int) $order->discount_amount;
        $this->notes = $order->notes;

        $this->order_date = $order->order_date;
        // Restore promos
        $this->selectedPromos = $order->promos->pluck('id')->toArray();

        // Restore cart
        $this->cart = [];
        foreach ($order->items as $item) {
            $snArray = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));

            $variant = $item->variant;

            $promoDiscounts = [];
            foreach ($item->promos as $promo) {
                $promoDiscounts[$promo->id] = ($promoDiscounts[$promo->id] ?? 0) + $promo->pivot->discount_amount;
            }

            $this->cart[] = [
                'variant_id' => $item->product_variant_id,
                'variant_type' => $item->product_variant_type,
                'name' => $variant->name ?? 'Unknown',
                'sku' => $variant->item_no ?? ($variant->sku ?? ''),
                'ram' => '-',
                'storage' => '-',
                'color' => '-',
                'price' => (int) $item->price_at_checkout,
                'qty' => $item->qty,
                'discount_amount' => (int) ($item->discount_amount / max(1, $item->qty)),
                'promo_discount' => (int) $item->promo_discount_amount,
                'applied_promo_id' => $item->applied_promo_id,
                'serial_numbers' => $snArray,
                'has_sn' => (bool) ($variant->has_sn ?? true),
                'database_source' => $variant->database_source ?? 'syihab',
                'promo_discounts' => $promoDiscounts,
            ];
        }

        $this->loadedDraftId = $order->id;
        $this->isPiutangSettlement = true;
        $this->syncSinglePaymentAmount();
        $this->showPiutangModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Faktur Piutang berhasil dimuat.', type: 'success');
        $this->goToStep(4);
    }

    public function loadDraft($orderId)
    {
        $order = Order::with(['items.variant', 'items.promos', 'user.profile', 'promos'])->find($orderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Draft tidak ditemukan.', type: 'error');
            return;
        }

        // Restore customer
        $this->selectedCustomerId = $order->user_id;
        $this->isNewCustomer = false;
        if ($order->user) {
            $this->customerName = $order->user->name;
            $this->customerPhone = $order->user->profile->phone_number ?? '';
            $this->customerEmail = $order->user->email ?? '';
        }

        // Restore sales (jika ada)
        if ($order->sales_id) {
            $sales = \App\Models\Employe::find($order->sales_id);
            if ($sales) {
                $this->selectedSales = [[
                    'id' => $sales->id,
                    'name' => $sales->name,
                    'employee_no' => $sales->employee_no
                ]];
            }
        }

        // Restore manual discount
        $this->discount_amount = (int) $order->discount_amount;
        $this->notes = $order->notes;

        $this->order_date = $order->order_date;

        // Restore promos
        $this->selectedPromos = $order->promos->pluck('id')->toArray();

        // Restore cart
        $this->cart = [];
        foreach ($order->items as $item) {
            $snArray = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));

            $variant = $item->variant; // Instance dari ProductAccurate

            $promoDiscounts = [];
            foreach ($item->promos as $promo) {
                $promoDiscounts[$promo->id] = ($promoDiscounts[$promo->id] ?? 0) + $promo->pivot->discount_amount;
            }

            $this->cart[] = [
                'variant_id' => $item->product_variant_id,
                'variant_type' => $item->product_variant_type,
                'name' => $variant->name ?? 'Unknown',
                'sku' => $variant->item_no ?? ($variant->sku ?? ''),
                'ram' => '-',
                'storage' => '-',
                'color' => '-',
                'price' => (int) $item->price_at_checkout,
                'qty' => $item->qty,
                'discount_amount' => (int) ($item->discount_amount / max(1, $item->qty)),
                'promo_discount' => (int) $item->promo_discount_amount,
                'applied_promo_id' => $item->applied_promo_id,
                'serial_numbers' => $snArray,
                'has_sn' => (bool) ($variant->has_sn ?? true),
                'database_source' => $variant->database_source ?? 'syihab',
                'promo_discounts' => $promoDiscounts,
            ];
        }

        // Set the loaded draft ID so we can update it later
        $this->loadedDraftId = $order->id;
        $this->syncSinglePaymentAmount();
        $this->showDraftModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Draft berhasil dimuat.', type: 'success');
    }

    public function deleteDraft($orderId)
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Draft tidak ditemukan.', type: 'error');
            return;
        }

        // Return stock
        foreach ($order->items as $item) {
            $warehouseStock = \App\Models\WarehouseStock::where([
                'warehouse_id' => Auth::user()->warehouse_id,
                'variant_id' => $item->product_variant_id,
                'variant_type' => $item->product_variant_type,
            ])->first();

            if ($warehouseStock) {
                $warehouseStock->update([
                    'stock' => $warehouseStock->stock + (int)$item->qty
                ]);
            }

            // Return SN
            if (!empty($item->serial_number)) {
                $sns = explode(',', $item->serial_number);
                $cleanSns = array_values(array_filter(array_map('trim', $sns)));
                if (!empty($cleanSns)) {
                    \App\Models\ProductSerialNumber::whereIn('serial_number', $cleanSns)
                        ->update(['status' => 'Available']);
                }
            }
        }

        $order->items()->delete();
        $order->promos()->detach();
        $order->delete();

        // Refresh list draft
        $this->openDraft();
        $this->dispatch('toast', title: 'Berhasil', message: 'Draft berhasil dihapus dan stok dikembalikan.', type: 'success');
    }

    // ─── History Sales Properties ──────────────────────────────
    public $showHistoryModal = false;
    public $historyOrders = [];
    public $searchHistory = '';
    public $searchHistoryDate = '';
    public $databaseSource = 'syihab';


    // Method untuk cetak ulang (reprint) dari riwayat
    public function reprintOrder($orderId)
    {
        $this->completedOrder = Order::with(['items', 'user', 'paymentMethod', 'handledBy', 'salesBy'])->find($orderId);
        // dd($this->completedOrder);
        if ($this->completedOrder) {
            $this->showHistoryModal = false; // tutup modal history
            $this->showReceiptModal = true;  // buka modal struk bawaan kamu
        }
    }

    /**
     * Helper terpusat untuk bikin PDF
     */
    private function generateReceiptPdf($order)
    {
        // Menggunakan kertas thermal POS 80mm
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', compact('order'))
            ->setPaper([0, 0, 226, 600], 'portrait');
    }

    public function mount()
    {
        $unit = \Illuminate\Support\Facades\Auth::user()->businessUnit?->code ?? 'all';
        if ($unit === 'second') {
            $this->productType = 'second';
            $this->databaseSource = 'second';
        } elseif ($unit === 'syihab') {
            $this->productType = 'new';
            $this->databaseSource = 'syihab';
        } else {
            $this->productType = 'all';
            $this->databaseSource = 'syihab'; // Default for all
        }
    }

    public function updatedPayments($value, $key)
    {
        // Reset rate when method changes
        if (str_contains($key, '.payment_method_id')) {
            $parts = explode('.', $key);
            $index = $parts[0];
            $this->payments[$index]['payment_method_rate_id'] = '';
        }
        $this->syncSinglePaymentAmount();
    }

    public function updatedDiscountAmount()
    {
        $this->syncSinglePaymentAmount();
    }

    public function updated($property, $value = null)
    {
        if (str_starts_with($property, 'cart.') && str_ends_with($property, '.qty')) {
            $parts = explode('.', $property);
            if (count($parts) === 3) {
                $index = $parts[1];
                $this->validateCartItemQty($index, $value);
            }
        }

        if (str_starts_with($property, 'cart.')) {
            $this->syncSinglePaymentAmount();
        }
    }




    #[Computed]
    public function customerResults()
    {
        if (strlen($this->searchCustomer) < 2) return [];

        return User::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })->where(function ($q) {
            $q->where('name', 'like', '%' . $this->searchCustomer . '%')
                ->orWhere('email', 'like', '%' . $this->searchCustomer . '%')
                ->orWhereHas('profile', function ($q2) {
                    $q2->where('phone_number', 'like', '%' . $this->searchCustomer . '%');
                });
        })->with('profile')->take(5)->get();
    }
    #[Computed]
    public function salesResults()
    {
        if (strlen($this->searchSales) < 2) return [];

        $user = Auth::user();
        $businessUnitId = $user->getActiveBusinessUnitId() ?? 1;

        return Employe::active()
            ->where('business_unit_id', $businessUnitId)
            ->with('branch')
            ->where(function ($q) {
                // Filter 1: Jika cabangnya sama
                $q->where('branch_id', Auth::user()->branch_id)
                    // Filter 2: Karyawan yang cabangnya kosong (null) karena tidak di set di Accurate
                    ->orWhereNull('branch_id');
            })
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchSales . '%');
            })->take(10)->get();
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->search) < 2) return collect();

        $results = collect();
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        // 1. Exact Match Scan Barcode di tabel ProductSerialNumber (Untuk HP / Barang ber-IMEI)
        $snItems = \App\Models\ProductSerialNumber::where('serial_number', $this->search)
            ->whereHas('productAccurate', function ($q) use ($buId) {
                $q->where(function ($q2) use ($buId) {
                    $q2->where('business_unit_id', $buId)->orWhereNull('business_unit_id');
                });
            })
            ->get();

        foreach ($snItems as $snItem) {
            $productAccurate = \App\Models\ProductAccurate::where('item_no', $snItem->item_no)->first();
            if ($productAccurate) {
                // Lacak Riwayat QC dari tabel sell_phones jika ini adalah HP Tukar Tambah
                $qcData = \App\Models\SellPhone::where('imei', $snItem->serial_number)->first();
                $condition = $qcData ? $qcData->minus_desc : 'Bagus / Mulus';
                $buyPrice = $qcData ? $qcData->appraised_value : $snItem->hpp;

                $productAccurate->is_second_catalog = ($qcData !== null);
                $productAccurate->matched_sn = $snItem->serial_number;
                $productAccurate->qc_condition = $condition;
                $productAccurate->buy_price = $buyPrice;
                $results->push($productAccurate);
            }
        }

        // 2. Exact Match Scan SKU di tabel ProductAccurate (Untuk Aksesoris / Non-IMEI)
        $skuItems = \App\Models\ProductAccurate::where('item_no', $this->search)
            ->where(function ($q) use ($buId) {
                $q->where('business_unit_id', $buId)->orWhereNull('business_unit_id');
            })
            ->get();

        foreach ($skuItems as $skuItem) {
            if (!$results->contains('id', $skuItem->id)) {
                $skuItem->is_second_catalog = false;
                $results->push($skuItem);
            }
        }

        return $results;
    }

    public $searchAddons = '';

    #[Computed]
    public function addonsResults()
    {
        $unit = \Illuminate\Support\Facades\Auth::user()->businessUnit?->code ?? 'all';
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        $query = \App\Models\ProductAccurate::where(function ($q) use ($buId) {
            $q->where('business_unit_id', $buId)->orWhereNull('business_unit_id');
        });

        if (strlen($this->searchAddons) >= 2) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchAddons . '%')
                    ->orWhere('item_no', 'like', '%' . $this->searchAddons . '%');
            });
        } else {
            $query->where('categoryName', 'like', '%ADD ON%');
        }

        $newProducts = $query->take(20)->get();

        return $newProducts;
    }

    // ─── Cart Subtotals ────────────────────────────────────────

    #[Computed]
    public function subtotal()
    {
        return collect($this->cart)->sum(fn($item) => ((int)$item['price'] * (int)$item['qty']));
    }

    #[Computed]
    public function hasZeroPriceItem()
    {
        return collect($this->cart)->contains(fn($item) => (float)($item['price'] ?? 0) <= 0);
    }

    #[Computed]
    public function mdrAmount()
    {
        $totalMdr = 0;
        foreach ($this->payments as $payment) {
            $pct = $this->getMdrPercentage($payment);
            if ($pct > 0) {
                $totalMdr += round((float)$payment['amount'] * $pct / 100, 0);
            }
        }
        return $totalMdr;
    }

    #[Computed]
    public function grandTotal()
    {
        return max(0, $this->subtotal() - (int)$this->totalDiscount());
    }

    #[Computed]
    public function activePromos()
    {
        $service = app(\App\Services\PromoCalculatorService::class);
        $userBranchId = \Illuminate\Support\Facades\Auth::user()->branch_id;
        $businessUnitId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        $eligiblePromos = $service->getEligiblePromos($this->cart, $userBranchId, $businessUnitId);

        // Check if previously selected promos are still eligible
        $eligibleIds = $eligiblePromos->pluck('id')->toArray();
        $needsUpdate = false;
        
        // Pastikan format awalnya adalah array
        if (!is_array($this->selectedPromos)) {
            $this->selectedPromos = $this->selectedPromos ? [$this->selectedPromos] : [];
        }

        foreach ($this->selectedPromos as $id) {
            if (!in_array($id, $eligibleIds)) {
                $this->selectedPromos = array_diff($this->selectedPromos, [$id]);
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            // SANGAT PENTING: array_diff merusak urutan index (menjadi assosiatif).
            // Kita harus mengembalikannya menjadi array berurutan (sequential)
            // agar Livewire (JavaScript) membacanya sebagai Array [] bukan Object {},
            // karena Object akan membuat checkbox multi-select nge-bug (uncheck all).
            $this->selectedPromos = array_values($this->selectedPromos);
            $this->applyPromosToCart();
        }

        return $eligiblePromos;
    }

    #[Computed]
    public function potentialPromos()
    {
        $cartSkus = array_column($this->cart, 'sku');
        if (empty($cartSkus)) return collect();

        $userBranchId = \Illuminate\Support\Facades\Auth::user()->branch_id;
        $businessUnitId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        return \App\Models\Promo::with(['skus', 'bundleSkus'])
            ->where('is_active', true)
            ->where('is_bundle', true)
            ->where(function ($q) use ($businessUnitId) {
                $q->whereNull('business_unit_id');
                if ($businessUnitId) {
                    $q->orWhere('business_unit_id', $businessUnitId);
                }
            })
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('quota')->orWhereColumn('quota', '>', 'used_quota');
            })
            ->where(function ($q) use ($userBranchId) {
                $q->whereDoesntHave('branches')
                    ->orWhereHas('branches', function ($bq) use ($userBranchId) {
                        $bq->where('branches.id', $userBranchId);
                    });
            })
            ->whereHas('bundleSkus', function ($q) use ($cartSkus) {
                $q->whereIn('sku', $cartSkus);
            })
            ->get();
    }

    #[Computed]
    public function itemDiscountTotal()
    {
        // Menghitung total diskon manual per-unit dikali qty
        return collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1));
    }

    #[Computed]
    public function promoDiscountTotal()
    {
        // Menghitung total diskon promo
        return collect($this->cart)->sum(fn($item) => (int)($item['promo_discount'] ?? 0));
    }

    #[Computed]
    public function totalDiscount()
    {
        return $this->itemDiscountTotal + $this->promoDiscountTotal;
    }

    // discount

    public function applyPromosToCart()
    {
        // Pastikan variabel selalu berupa array untuk mencegah TypeError dari Livewire binding
        if (!is_array($this->selectedPromos)) {
            $this->selectedPromos = $this->selectedPromos ? [$this->selectedPromos] : [];
        }

        $service = app(\App\Services\PromoCalculatorService::class);
        $success = $service->applyPromosToCart($this->cart, $this->selectedPromos);

        if (!$success) {
            $this->dispatch('toast', title: 'Gagal', message: 'Ada promo yang tidak dapat digabungkan dengan promo lain.', type: 'error');
            // Revert ke 1 promo saja (yg pertama)
            if (count($this->selectedPromos) > 0) {
                $this->selectedPromos = [$this->selectedPromos[0]];
                $service->applyPromosToCart($this->cart, $this->selectedPromos);
            }
        }
    }

    public function updatedSelectedPromos()
    {
        $this->applyPromosToCart();
        $this->syncSinglePaymentAmount();
    }


    // baru


}
