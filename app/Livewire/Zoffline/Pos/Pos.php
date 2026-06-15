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


    public $order_date;

    // ─── Wizard State ──────────────────────────────────────────
    public $currentStep = 1; // 1: Customer, 2: Cart, 3: Upsell, 4: Payment

    public function nextStep()
    {
        if ($this->currentStep == 1) {
            // Validasi Step 1: Customer dan Sales
            if (!$this->selectedCustomerId) {
                // Auto set new customer if they filled the search and phone
                if (strlen($this->searchCustomer) >= 2 && !empty($this->customerPhone)) {
                    $this->isNewCustomer = true;
                    $this->customerName = $this->searchCustomer;
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
        
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        $this->currentStep = $step;
    }

    // ─── Modals ────────────────────────────────────────────────
    public $showCheckoutModal = false;
    public $showReceiptModal = false;
    public $completedOrder = null;

    // ─── History Sales Properties ──────────────────────────────
    public $showHistoryModal = false;
    public $historyOrders = [];
    public $searchHistory = '';
    public $searchHistoryDate = '';
    public $databaseSource = 'syihab';

    // ─── Draft Sales Properties ──────────────────────────────
    public $showDraftModal = false;
    public $draftOrders = [];
    public $loadedAccurateSoId = null;
    public $loadedAccurateSoNumber = null;
    public $loadedDraftId = null;

    public function openDraft()
    {
        $this->draftOrders = Order::with(['user'])
            ->where('order_channel', 'POS')
            ->where('order_status', 'DRAFT')
            ->latest()
            ->take(20)
            ->get();

        $this->showDraftModal = true;
    }

    public function loadDraft($orderId)
    {
        $order = Order::with(['items.variant.product', 'user', 'promos'])->find($orderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Draft tidak ditemukan.', type: 'error');
            return;
        }

        // Restore customer
        $this->selectedCustomerId = $order->user_id;
        $this->isNewCustomer = false;

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

        // Restore SO Accurate info
        $this->loadedAccurateSoId = $order->accurate_so_id;
        $this->loadedAccurateSoNumber = $order->accurate_so_number;
        // dd($order);
        // Restore cart
        $this->cart = [];
        foreach ($order->items as $item) {
            // Karena orderItem punya serial_number (string dipisah koma), kita split lagi
            $snArray = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));

            $product = $item->variant->product ?? $item->variant->secondProduct;
            $this->cart[] = [
                'variant_id' => $item->product_variant_id,
                'variant_type' => $item->product_variant_type,
                'is_second' => $item->product_variant_type === \App\Models\SecondProductVariant::class,
                'name' => $product->name ?? 'Unknown',
                'sku' => $item->variant->sku ?? '',
                'storage' => $item->variant->storage ?? '',
                'color' => $item->variant->color ?? '',
                'price' => (int) $item->price_at_checkout,
                'qty' => $item->qty,
                'discount_amount' => (int) $item->discount_amount,
                'serial_numbers' => $snArray,
                'has_sn' => (bool) ($item->variant->has_sn ?? true),
            ];
        }

        // Set the loaded draft ID so we can update it later
        $this->loadedDraftId = $order->id;
        $this->syncSinglePaymentAmount();
        $this->showDraftModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Draft berhasil dimuat.', type: 'success');
    }

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

    public function updated($property)
    {
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

        $newProducts = collect();
        $secondProducts = collect();
        $unit = \Illuminate\Support\Facades\Auth::user()->businessUnit?->code ?? 'all';

        if ($this->productType !== 'second' && $unit !== 'second') {
            $newProducts = Product::with(['variants', 'brand', 'media'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('variants', function ($q2) {
                            $q2->where('sku', 'like', '%' . $this->search . '%');
                        });
                })
                ->take(10)->get()
                ->map(function ($p) {
                    $p->is_second_catalog = false;
                    return $p;
                });
        }

        if ($this->productType !== 'new' && $unit !== 'syihab') {
            $secondProducts = SecondProduct::with(['variants', 'brand', 'media'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('variants', function ($q2) {
                            $q2->where('sku', 'like', '%' . $this->search . '%');
                        });
                })
                ->take(10)->get()
                ->map(function ($p) {
                    $p->is_second_catalog = true;
                    return $p;
                });
        }

        return $newProducts->concat($secondProducts);
    }

    public $searchAddons = '';

    #[Computed]
    public function addonsResults()
    {
        if (strlen($this->searchAddons) < 2) return collect();

        $newProducts = collect();
        $unit = \Illuminate\Support\Facades\Auth::user()->businessUnit?->code ?? 'all';

        if ($this->productType !== 'second' && $unit !== 'second') {
            $newProducts = Product::with(['variants', 'brand', 'media'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchAddons . '%')
                        ->orWhereHas('variants', function ($q2) {
                            $q2->where('sku', 'like', '%' . $this->searchAddons . '%');
                        });
                })
                ->take(10)->get()
                ->map(function ($p) {
                    $p->is_second_catalog = false;
                    return $p;
                });
        }

        return $newProducts;
    }

    // ─── Cart Subtotals ────────────────────────────────────────

    #[Computed]
    public function subtotal()
    {
        return collect($this->cart)->sum(fn($item) => (int)$item['price'] * (int)$item['qty']);
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
        $userBranchId = \Illuminate\Support\Facades\Auth::user()->branch_id;
        $promos = \App\Models\Promo::with(['skus', 'bundleSkus.variant.product', 'branches'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) use ($userBranchId) {
                $q->whereDoesntHave('branches')
                  ->orWhereHas('branches', function ($bq) use ($userBranchId) {
                      $bq->where('branches.id', $userBranchId);
                  });
            })
            ->get();

        $eligiblePromos = [];
        foreach ($promos as $promo) {
            if ($this->isPromoEligible($promo)) {
                $eligiblePromos[] = $promo;
            } else {
                if (in_array($promo->id, $this->selectedPromos)) {
                    $this->selectedPromos = array_diff($this->selectedPromos, [$promo->id]);
                    $this->applyPromosToCart();
                }
            }
        }
        return collect($eligiblePromos);
    }

    #[Computed]
    public function itemDiscountTotal()
    {
        // Menghitung total diskon manual DAN diskon promo dari semua item di keranjang
        return collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0) + (int)($item['promo_discount'] ?? 0));
    }

    #[Computed]
    public function totalDiscount()
    {
        return $this->itemDiscountTotal;
    }

    // discount

    public function isPromoEligible($promo)
    {
        // Mengecek kelayakan HANYA berdasarkan Produk Utama (Main Product)
        $eligible = $this->calculateEligibleCart($promo, false);

        if ($promo->min_qty && $eligible['qty'] < $promo->min_qty) return false;
        if ($promo->min_transaction_amount && $eligible['amount'] < $promo->min_transaction_amount) return false;

        // Jika tidak apply ke semua produk dan qty produk utama 0 = tidak valid
        if (!$promo->apply_to_all_items && $eligible['qty'] == 0) return false;
        return true;
    }

    public function calculateEligibleCart($promo, $forBundle = false, $multiplier = 1)
    {
        $eligibleQty = 0;
        $eligibleAmount = 0;
        if ($promo->apply_to_all_items && !$forBundle) {
            $eligibleAmount = $this->subtotal();
            $eligibleQty = array_sum(array_column($this->cart, 'qty'));
        } else {
            // Jika untuk bundle, cari dari bundleSkus. Jika utama, dari skus.
            $promoSkus = $forBundle ? $promo->bundleSkus->pluck('sku')->toArray() : $promo->skus->pluck('sku')->toArray();

            foreach ($this->cart as $item) {
                if (in_array($item['sku'], $promoSkus)) {
                    $eligibleQty += (int)$item['qty'];
                    $eligibleAmount += ((int)$item['qty'] * (float)$item['price']);
                }
            }
        }
        // Cap reward qty untuk produk bundle (jika diset max qty-nya) - perhatikan multiplier
        if ($forBundle && $promo->bundle_max_qty && $eligibleQty > ($promo->bundle_max_qty * $multiplier)) {
            $avgPrice = $eligibleQty > 0 ? ($eligibleAmount / $eligibleQty) : 0;
            $eligibleQty = $promo->bundle_max_qty * $multiplier;
            $eligibleAmount = $eligibleQty * $avgPrice;
        }
        return ['qty' => $eligibleQty, 'amount' => $eligibleAmount];
    }

    public function applyPromosToCart()
    {
        // 1. Reset all promo discounts in cart
        foreach ($this->cart as $key => $item) {
            $this->cart[$key]['promo_discount'] = 0;
            $this->cart[$key]['applied_promo_id'] = null;
        }

        if (empty($this->selectedPromos)) return;

        $promos = \App\Models\Promo::with(['skus', 'bundleSkus'])->whereIn('id', $this->selectedPromos)->get();

        // Cek kombinasi promo
        $hasNonCombinable = $promos->where('is_combinable', false)->count() > 0;
        if ($hasNonCombinable && count($this->selectedPromos) > 1) {
            $this->dispatch('toast', title: 'Gagal', message: 'Ada promo yang tidak dapat digabungkan dengan promo lain.', type: 'error');
            // Revert ke 1 promo saja (yg pertama)
            $this->selectedPromos = [$this->selectedPromos[0]];
            $promos = \App\Models\Promo::with(['skus', 'bundleSkus'])->whereIn('id', $this->selectedPromos)->get();
        }

        foreach ($promos as $promo) {
            // 2. Kalkulasi Diskon Utama
            $eligibleMain = $this->calculateEligibleCart($promo, false);
            if ($eligibleMain['qty'] > 0) {
                $multiplier = 1;
                if ($promo->is_multiply && $promo->min_qty > 0) {
                    $multiplier = floor($eligibleMain['qty'] / $promo->min_qty);
                }

                $mainDiscountValue = $promo->discount_type === 'fixed' 
                    ? ($promo->discount_value * $multiplier)
                    : ($eligibleMain['amount'] * ($promo->discount_value / 100));
                
                if ($promo->max_discount) {
                    $mainDiscountValue = min($mainDiscountValue, $promo->max_discount * $multiplier);
                }

                // Distribusikan $mainDiscountValue ke item yang eligible
                $mainSkus = $promo->apply_to_all_items ? array_column($this->cart, 'sku') : $promo->skus->pluck('sku')->toArray();
                foreach ($this->cart as $key => $item) {
                    if (in_array($item['sku'], $mainSkus)) {
                        $itemAmount = $item['qty'] * $item['price'];
                        $proportion = $itemAmount / $eligibleMain['amount'];
                        $itemDiscount = round($mainDiscountValue * $proportion);
                        $this->cart[$key]['promo_discount'] += $itemDiscount;
                        $this->cart[$key]['applied_promo_id'] = $promo->id;
                    }
                }
            }

            // 3. Kalkulasi Diskon Bundling
            if ($promo->is_bundle) {
                $multiplier = 1;
                if ($promo->is_multiply && $promo->min_qty > 0) {
                    $multiplier = floor($eligibleMain['qty'] / $promo->min_qty);
                }

                $eligibleBundle = $this->calculateEligibleCart($promo, true, $multiplier);
                
                if ($eligibleBundle['qty'] > 0) {
                    $applicableQty = $eligibleBundle['qty'];
                    $totalQtyApplied = 0;
                    
                    $bundleSkusInfo = $promo->bundleSkus->keyBy('sku');

                    foreach ($this->cart as $key => $item) {
                        if ($bundleSkusInfo->has($item['sku']) && $totalQtyApplied < $applicableQty) {
                            $bSku = $bundleSkusInfo->get($item['sku']);
                            
                            // Ambil prioritas: diskon per item (jika diisi) ATAU fallback ke global
                            $type = $bSku->discount_value > 0 ? $bSku->discount_type : $promo->bundle_discount_type;
                            $val = $bSku->discount_value > 0 ? $bSku->discount_value : $promo->bundle_discount_value;
                            $max = $bSku->discount_value > 0 ? $bSku->max_discount : $promo->bundle_max_discount;
                            
                            if (!$val) continue;

                            // Berapa banyak qty item ini yang boleh didiskon
                            $qtyToDiscount = min($item['qty'], $applicableQty - $totalQtyApplied);

                            // Hitung diskon dasar
                            $itemDiscount = $type === 'fixed' 
                                ? ($val * $qtyToDiscount) 
                                : (($item['price'] * $qtyToDiscount) * ($val / 100));

                            // Terapkan batasan max discount jika ada (dikalikan multiplier karena berlaku per kelipatan bundle)
                            if ($max && $max > 0) {
                                $itemDiscount = min($itemDiscount, $max * $multiplier);
                            }

                            $this->cart[$key]['promo_discount'] += $itemDiscount;
                            $this->cart[$key]['applied_promo_id'] = $promo->id;
                            
                            $totalQtyApplied += $qtyToDiscount;
                        }
                    }
                }
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
