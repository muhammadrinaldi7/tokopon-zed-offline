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

    public $order_date;
    // ─── Search & Filter ───────────────────────────────────────
    public $search = '';
    public $productType = 'new'; // all, new, second
    public $scanned_sn = '';
    // ─── Cart (in-memory) ──────────────────────────────────────
    public $cart = []; // [{variant_id, variant_type, name, storage, color, price, qty, serial_number, sku}]

    // ─── Customer ──────────────────────────────────────────────
    public $isNewCustomer = false;
    public $searchCustomer = '';
    public $selectedCustomerId = null;
    public $customerName = '';
    public $customerPhone = '';
    public $customerEmail = '';

    // SALES
    public $selectedSales = []; // Array untuk menampung lebih dari 1 sales
    public $searchSales = '';

    // ─── Payment ───────────────────────────────────────────────
    public $payments = [];
    public $discount_amount = 0;
    public $notes = '';
    public $selectedPromos = []; // Menyimpan ID promo yang dipilih

    // ─── Modals ────────────────────────────────────────────────
    public $showCheckoutModal = false;
    public $showReceiptModal = false;
    public $completedOrder = null;

    // ─── Variant Selection ─────────────────────────────────────
    public $showVariantModal = false;
    public $variantModalProduct = null;
    public $variantModalVariants = [];
    public $variantModalIsSecond = false;

    // ─── History Sales Properties ──────────────────────────────
    public $showHistoryModal = false;
    public $historyOrders = [];
    public $searchHistory = '';
    public $searchHistoryDate = '';
    public $databaseSource = 'syihab';

    // ─── QC Serah Terima ───────────────────────────────────────
    public $showQcModal = false;
    public $targetSnId = null;
    public $targetImei = '';

    protected $listeners = ['qc-inspection-saved' => 'onInspectionSaved'];

    public function openQcSerahTerima($sn)
    {
        $snRecord = \App\Models\ProductSerialNumber::where('serial_number', $sn)->first();
        if ($snRecord) {
            $this->targetSnId = $snRecord->id;
            $this->targetImei = $sn;
            $this->showQcModal = true;
        } else {
            $this->dispatch('toast', title: 'Error', message: 'Serial Number tidak valid.', type: 'error');
        }
    }

    public function onInspectionSaved($verdict)
    {
        $this->showQcModal = false;
        // Pengecekan status sudah dilakukan saat openCheckout
    }

    public $showCustomerQcModal = false;
    public $customerQcData = null;

    public function openCustomerQcModal($sn)
    {
        $inspection = \App\Models\DeviceInspection::with(['inspector', 'media'])
            ->where('imei', $sn)
            ->where('label', '!=', 'QC Serah Terima')
            ->orderBy('inspected_at', 'desc')
            ->first();

        if (!$inspection) {
            $this->dispatch('toast', title: 'Info', message: "Belum ada riwayat Sertifikat QC untuk IMEI/SN: {$sn}", type: 'warning');
            return;
        }

        $this->customerQcData = $inspection;
        $this->showCustomerQcModal = true;
    }

    public function processScan(AccurateService $accurateService)
    {
        $sn = trim($this->scanned_sn);

        if (empty($sn)) {
            return;
        }

        // 1. Hit ke Accurate via Service untuk mendapatkan No SKU
        $skuFromAccurate = $accurateService->findSkuBySerialNumber($sn, $this->databaseSource);

        $unit = \Illuminate\Support\Facades\Auth::user()->businessUnit?->code ?? 'all';

        // Jika user adalah 'all' dan SN tidak ditemukan di syihab, coba cari di second
        if ((!$skuFromAccurate || $skuFromAccurate === 'error') && $unit === 'all' && $this->databaseSource === 'syihab') {
            $skuFromAccurateSecond = $accurateService->findSkuBySerialNumber($sn, 'second');
            if ($skuFromAccurateSecond && $skuFromAccurateSecond !== 'error' && $skuFromAccurateSecond !== 'invalid_type') {
                $skuFromAccurate = $skuFromAccurateSecond;
                $this->databaseSource = 'second'; // Switch source temporarily for this transaction
            }
        }

        if ($skuFromAccurate === 'error') {
            $this->dispatch('toast', title: 'Error', message: 'Terjadi gangguan koneksi ke Accurate.', type: 'error');
            return;
        }

        // KONDISI B (BARU): Data ada di Accurate, tapi yang di-scan BUKAN Serial Number
        if ($skuFromAccurate === 'invalid_type') {
            $this->dispatch('toast', title: 'Peringatan', message: "Kode '{$sn}' terdeteksi sebagai Barcode/SKU barang, mohon scan Serial Number produk.", type: 'warning');
            $this->scanned_sn = '';
            return;
        }

        if (!$skuFromAccurate) {
            $this->dispatch('toast', title: 'Error', message: "Serial Number '{$sn}' tidak ditemukan di Accurate.", type: 'error');
            $this->scanned_sn = '';
            return;
        }

        // 2. Cek apakah SN ini sudah discan dan ada di cart (Mencegah scan ganda)
        if ($this->isSnAlreadyInCart($sn)) {
            $this->dispatch('toast', title: 'Peringatan', message: "Serial Number '{$sn}' sudah ada di dalam keranjang.", type: 'warning');
            $this->scanned_sn = '';
            return;
        }

        // 3. Cari SKU di Database Lokal (Cek Baru, lalu Bekas)
        $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;

        // =========================================================================
        // 3. CEK KESESUAIAN WAREHOUSE DI TABEL product_serial_numbers
        // =========================================================================
        // Catatan: Pastikan nama kolom 'sn' atau 'serial_number' sesuai dengan yang ada di database-mu
        $localSnRecord = \Illuminate\Support\Facades\DB::table('product_serial_numbers')
            ->where('serial_number', $sn)
            ->first();

        if (!$localSnRecord) {
            $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$sn}' tidak ditemukan di ZPOS.", type: 'error');
            $this->scanned_sn = '';
            return;
        }

        if ($localSnRecord->warehouse_id != $warehouseId) {
            // Ambil nama gudang yang memiliki SN tersebut
            $actualWarehouseName = \Illuminate\Support\Facades\DB::table('warehouses')
                ->where('id', $localSnRecord->warehouse_id)
                ->value('name'); // Ganti 'name' dengan nama kolom gudang di databasemu (misal: 'nama_gudang')

            // Antisipasi jika data gudangnya ternyata tidak ketemu di DB
            $warehouseTarget = $actualWarehouseName ?? 'Gudang Lain';

            $this->dispatch(
                'toast',
                title: 'Gagal',
                message: "Serial Number '{$sn}' ada di gudang {$warehouseTarget} Silahkan lakukan pemindahan barang di accurate.",
                type: 'error'
            );

            $this->scanned_sn = '';
            return;
        }

        $isSecond = false;
        $variantType = \App\Models\ProductVariant::class;

        // Cek di Varian Produk Baru
        $variant = \App\Models\ProductVariant::with(['product', 'warehouseStocks' => function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }])->where('sku', $skuFromAccurate)->first();

        // Jika tidak ada di Baru, Cek di Varian Produk Bekas
        if (!$variant) {
            $variant = \App\Models\SecondProductVariant::with(['secondProduct', 'warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->where('sku', $skuFromAccurate)->first();

            if ($variant) {
                $isSecond = true;
                $variantType = \App\Models\SecondProductVariant::class;
            }
        }

        if (!$variant) {
            $this->dispatch('toast', title: 'Peringatan', message: "Produk (SKU: {$skuFromAccurate}) belum terdaftar di sistem lokal.", type: 'warning');
            $this->scanned_sn = '';
            return;
        }

        // 4. Masukkan ke cart dengan SN yang berhasil discan
        $this->addScannedVariantToCart($variant, $isSecond, $variantType, $sn);

        // Kosongkan kembali input scanner
        $this->scanned_sn = '';
    }

    /**
     * Memasukkan varian hasil scan ke cart
     */
    private function addScannedVariantToCart($variant, $isSecond, $variantType, $sn)
    {
        $stock = $variant->warehouseStocks->first()?->stock ?? 0;

        if ($stock <= 0) {
            $this->dispatch('toast', title: 'Stok Habis', message: 'Varian ini kosong di gudang Anda.', type: 'warning');
            return;
        }

        // Cek apakah produk varian ini sudah ada di keranjang
        $existingIndex = collect($this->cart)->search(
            fn($item) => $item['variant_id'] == $variant->id && $item['variant_type'] == $variantType
        );

        if ($existingIndex !== false) {
            $currentQty = $this->cart[$existingIndex]['qty'];

            if ($currentQty < $stock) {
                $this->cart[$existingIndex]['qty']++;

                if (!isset($this->cart[$existingIndex]['serial_numbers'])) {
                    $this->cart[$existingIndex]['serial_numbers'] = [];
                }

                // PERBEDAAN: Push nilai $sn, BUKAN string kosong ''
                $this->cart[$existingIndex]['serial_numbers'][] = $sn;

                $this->dispatch('toast', title: 'Sukses', message: 'Kuantitas ditambah & SN tercatat.', type: 'success');
            } else {
                $this->dispatch('toast', title: 'Stok Tidak Cukup', message: 'Sudah mencapai batas stok.', type: 'warning');
            }
        } else {
            // Jika produk belum ada di keranjang, buat item baru
            $this->cart[] = [
                'variant_id' => $variant->id,
                'variant_type' => $variantType,
                'name' => $variant->product->name, // Ambil nama dari relasi 'product'
                'ram' => $variant->ram ?? '-',
                'storage' => $variant->storage ?? '-',
                'color' => $variant->color ?? '-',
                'price' => (int) $variant->price,
                'discount_amount' => 0,
                'qty' => 1,
                'serial_numbers' => [$sn], // PERBEDAAN: Langsung inisiasi array dengan $sn
                'sku' => $variant->sku ?? '',
                'has_sn' => (bool) $variant->has_sn,
                'is_second' => $isSecond,
            ];

            $this->dispatch('toast', title: 'Sukses', message: "Berhasil menambahkan {$variant->product->name} ke keranjang.", type: 'success');
        }

        $this->syncSinglePaymentAmount();
    }

    /**
     * Helper untuk mencegah scan SN yang sama berkali-kali
     */
    private function isSnAlreadyInCart($sn)
    {
        foreach ($this->cart as $item) {
            if (isset($item['serial_numbers']) && in_array($sn, $item['serial_numbers'])) {
                return true;
            }
        }
        return false;
    }

    public function loadHistory()
    {
        $userWarehouseName = Auth::user()->warehouse->name ?? null;

        $query = Order::with(['items', 'user', 'paymentMethod', 'handledBy'])
            ->where('order_channel', 'POS')
            ->where('shipping_address_snapshot->store', $userWarehouseName)
            ->whereNotIn('order_status', ['DRAFT', 'DRAFT_LOADED', 'CANCELLED', 'RETURNED']);

        if (!empty($this->searchHistory)) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->searchHistory . '%')
                    ->orWhere('accurate_invoice_no', 'like', '%' . $this->searchHistory . '%')
                    ->orWhereHas('user', function ($q2) {
                        $q2->where('name', 'like', '%' . $this->searchHistory . '%')
                            ->orWhereHas('profile', function ($q3) {
                                $q3->where('phone_number', 'like', '%' . $this->searchHistory . '%');
                            });
                    });
            });
        }

        if (!empty($this->searchHistoryDate)) {
            $query->whereDate('created_at', $this->searchHistoryDate);
        }

        $this->historyOrders = $query->latest()->take(50)->get();
    }

    public function updatedSearchHistory()
    {
        $this->loadHistory();
    }

    public function updatedSearchHistoryDate()
    {
        $this->loadHistory();
    }

    // Method untuk membuka modal dan memuat data transaksi POS terbaru
    public function openHistory()
    {
        $this->searchHistory = '';
        $this->searchHistoryDate = '';
        $this->loadHistory();
        $this->showHistoryModal = true;
    }

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

        $this->payments = [
            [
                'payment_method_id' => '',
                'payment_method_rate_id' => '',
                'no_kontrak' => '',
                'amount' => 0,
            ]
        ];
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

    public function addPaymentRow()
    {
        $remaining = max(0, ($this->subtotal - (int)$this->totalDiscount) - $this->paymentsTotalBase);
        $this->payments[] = [
            'payment_method_id' => '',
            'payment_method_rate_id' => '',
            'no_kontrak' => '',
            'amount' => $remaining,
        ];
    }

    public function removePaymentRow($index)
    {
        if (count($this->payments) > 1) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
            $this->syncSinglePaymentAmount();
        }
    }

    public function autofillRemaining($index)
    {
        $totalOther = 0;
        foreach ($this->payments as $i => $p) {
            if ($i !== $index) {
                $totalOther += (int)$p['amount'];
            }
        }
        $target = max(0, $this->subtotal - (int)$this->totalDiscount);
        $this->payments[$index]['amount'] = max(0, $target - $totalOther);
    }

    public function syncSinglePaymentAmount()
    {
        if (count($this->payments) === 1) {
            $this->payments[0]['amount'] = max(0, $this->subtotal - (int)$this->totalDiscount);
        }
    }

    public function getMdrPercentage($payment)
    {
        $pmId = $payment['payment_method_id'] ?? null;
        $rateId = $payment['payment_method_rate_id'] ?? null;

        if (!$pmId) return 0;

        if ($rateId) {
            $rate = \App\Models\PaymentMethodRate::find($rateId);
            return $rate ? (float) $rate->mdr_percentage : 0;
        }

        $pm = \App\Models\PaymentMethod::find($pmId);
        return $pm ? (float) $pm->mdr_percentage : 0;
    }

    #[Computed]
    public function paymentsTotalBase()
    {
        return collect($this->payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
    }

    #[Computed]
    public function isPaymentsValid()
    {
        foreach ($this->payments as $p) {
            if (empty($p['payment_method_id'])) return false;

            $pm = \App\Models\PaymentMethod::find($p['payment_method_id']);
            if ($pm && $pm->rates()->where('is_active', true)->count() > 0 && empty($p['payment_method_rate_id'])) {
                return false;
            }
        }

        $target = max(0, $this->subtotal - $this->totalDiscount);

        return (int) $this->paymentsTotalBase === (int) $target;
    }

    // #[Computed]
    // public function paymentMethods()
    // {
    //     return PaymentMethod::where('is_active', true)->get();

    // }
    #[Computed]
    public function paymentMethods()
    {
        // 1. Ambil nama warehouse atau branch dari user yang sedang login
        // Sesuaikan bagian ini dengan relasi database kamu (misal $user->branch->name)
        $user = Auth::user();
        $locationName = $user->warehouse->name ?? '';

        // 2. Ambil semua metode pembayaran yang aktif sesuai business_unit
        $methods = PaymentMethod::where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('business_unit_id', $user->business_unit_id)
                    ->orWhereNull('business_unit_id');
            })
            ->get();

        // 3. Filter datanya
        return $methods->filter(function ($method) use ($locationName) {
            // Ubah ke huruf kecil semua agar pencarian tidak sensitif huruf besar/kecil
            $methodName = strtolower($method->name);
            $location = strtolower($locationName);

            // Jika nama metode pembayarannya mengandung kata 'tunai'
            if (str_contains($methodName, 'tunai')) {
                // Hanya tampilkan jika nama metode juga mengandung nama lokasi user (misal: 'banjarbaru')
                // Jika $location kosong, kita asumsikan tidak lolos filter untuk keamanan
                return $location !== '' && str_contains($methodName, $location);
            }

            // Jika BUKAN tunai (misal: EDC BCA, Transfer Mandiri), biarkan tetap muncul
            return true;
        })->values(); // values() berguna untuk me-reset nomor urut/index array
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

        return Employe::active()->with('branch')
            ->where(function ($q) {
                // Filter 1: Jika cabangnya sama
                $q->where('branch_id', Auth::user()->branch_id)
                    // Filter 2: ATAU jika namanya mengandung 'Cc_'
                    ->orWhere('branch_id', 6);
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
        return max(0, $this->subtotal() - (int)$this->totalDiscount);
    }

    #[Computed]
    public function activePromos()
    {
        $promos = Promo::with(['skus', 'bundleSkus.variant.product'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->get();

        $eligiblePromos = [];
        foreach ($promos as $promo) {
            if ($this->isPromoEligible($promo)) {
                $eligiblePromos[] = $promo;
            } else {
                if (in_array($promo->id, $this->selectedPromos)) {
                    $this->selectedPromos = array_diff($this->selectedPromos, [$promo->id]);
                }
            }
        }
        return collect($eligiblePromos);
    }

    #[Computed]
    public function itemDiscountTotal()
    {
        // Menghitung total diskon manual dari semua item di keranjang
        return collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0));
    }

    #[Computed]
    public function totalDiscount()
    {
        // $itemDiscounts = collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0));
        return $this->itemDiscountTotal + $this->totalPromoDiscount;
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
    public function calculateEligibleCart($promo, $forBundle = false)
    {
        $eligibleQty = 0;
        $eligibleAmount = 0;
        if ($promo->apply_to_all_items && !$forBundle) {
            $eligibleAmount = $this->subtotal;
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
        // Cap reward qty untuk produk bundle (jika diset max qty-nya)
        if ($forBundle && $promo->bundle_max_qty && $eligibleQty > $promo->bundle_max_qty) {
            $avgPrice = $eligibleQty > 0 ? ($eligibleAmount / $eligibleQty) : 0;
            $eligibleQty = $promo->bundle_max_qty;
            $eligibleAmount = $eligibleQty * $avgPrice;
        }
        return ['qty' => $eligibleQty, 'amount' => $eligibleAmount];
    }

    #[Computed]
    public function totalPromoDiscount()
    {
        if (empty($this->selectedPromos)) return 0;

        $promos = Promo::with(['skus', 'bundleSkus'])->whereIn('id', $this->selectedPromos)->get();
        $total = 0;
        foreach ($promos as $promo) {
            // 1. Kalkulasi Diskon Utama
            $eligibleMain = $this->calculateEligibleCart($promo, false);
            if ($promo->discount_type === 'fixed') {
                $total += $promo->discount_value; // Fixed diskon utama (dihitung 1x per transaksi)
            } else {
                $calc = $eligibleMain['amount'] * ($promo->discount_value / 100);
                if ($promo->max_discount) $calc = min($calc, $promo->max_discount);
                $total += $calc;
            }
            // 2. Kalkulasi Diskon Tambahan (Bundle)
            if ($promo->is_bundle) {
                $eligibleBundle = $this->calculateEligibleCart($promo, true);

                // Jika ada barang bundle-nya di keranjang
                if ($eligibleBundle['qty'] > 0) {
                    if ($promo->bundle_discount_type === 'fixed') {
                        // Fixed diskon bundle dikalikan dengan jumlah qty barang bundle yg valid
                        $total += $promo->bundle_discount_value * $eligibleBundle['qty'];
                    } else {
                        $calc = $eligibleBundle['amount'] * ($promo->bundle_discount_value / 100);
                        if ($promo->bundle_max_discount) $calc = min($calc, $promo->bundle_max_discount);
                        $total += $calc;
                    }
                }
            }
        }
        return $total;
    }
    public function updatedSelectedPromos()
    {
        $this->syncSinglePaymentAmount();
    }


    // baru

    // ─── Cart Actions ──────────────────────────────────────────

    public function openVariantPicker($productId, $isSecond = false)
    {
        $warehouseId = Auth::user()->warehouse_id;

        if ($isSecond) {
            $product = SecondProduct::with([
                'variants' => function ($q) use ($warehouseId) {
                    $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
                        $q2->where('warehouse_id', $warehouseId);
                    }]);
                },
                'brand'
            ])->find($productId);

            $this->variantModalVariants = $product->variants->map(fn($v) => [
                'id' => $v->id,
                // PERBAIKAN DI SINI
                'label' => trim(($v->ram ? $v->ram . ' / ' : '') . $v->storage . ' ' . $v->color),
                'condition' => $v->condition ?? '',
                'price' => $v->price,
                'stock' => $v->warehouseStocks->first()?->stock ?? 0,
                'sku' => $v->sku ?? '',
            ])->toArray();
        } else {
            $product = Product::with([
                'variants' => function ($q) use ($warehouseId) {
                    $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
                        $q2->where('warehouse_id', $warehouseId);
                    }]);
                },
                'brand'
            ])->find($productId);

            $this->variantModalVariants = $product->variants->map(fn($v) => [
                'id' => $v->id,
                // PERBAIKAN DI SINI
                'label' => trim(($v->ram ? $v->ram . ' / ' : '') . $v->storage . ' ' . $v->color),
                'condition' => '',
                'price' => $v->price,
                'stock' => $v->warehouseStocks->first()?->stock ?? 0,
                'sku' => $v->sku ?? '',
            ])->toArray();
        }
        $this->variantModalProduct = $product;
        $this->variantModalIsSecond = $isSecond;
        $this->showVariantModal = true;
    }

    public function addVariantToCart($variantId)
    {
        $isSecond = $this->variantModalIsSecond;
        $product = $this->variantModalProduct;
        $warehouseId = Auth::user()->warehouse_id;

        if ($isSecond) {
            $variant = SecondProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->find($variantId);
            $variantType = SecondProductVariant::class;
        } else {
            $variant = ProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->find($variantId);
            $variantType = ProductVariant::class;
        }

        $stock = $variant ? ($variant->warehouseStocks->first()?->stock ?? 0) : 0;

        if (!$variant || $stock <= 0) {
            $this->dispatch('toast', title: 'Stok Habis', message: 'Varian ini tidak tersedia.', type: 'warning');
            return;
        }

        // Check if already in cart
        $existingIndex = collect($this->cart)->search(
            fn($item) =>
            $item['variant_id'] == $variantId && $item['variant_type'] == $variantType
        );

        if ($existingIndex !== false) {
            $currentQty = $this->cart[$existingIndex]['qty'];
            if ($currentQty < $stock) {
                $this->cart[$existingIndex]['qty']++;

                // PERBAIKAN: Langsung push slot kosong ke array serial_numbers tanpa mengecek legacy
                if (!isset($this->cart[$existingIndex]['serial_numbers'])) {
                    $this->cart[$existingIndex]['serial_numbers'] = [];
                }
                $this->cart[$existingIndex]['serial_numbers'][] = '';
            } else {
                $this->dispatch('toast', title: 'Stok Tidak Cukup', message: 'Sudah mencapai batas stok.', type: 'warning');
            }
        } else {
            $this->cart[] = [
                'variant_id' => $variant->id,
                'variant_type' => $variantType,
                'name' => $product->name,
                'ram' => $variant->ram ?? '-',
                'storage' => $variant->storage ?? '-',
                'color' => $variant->color ?? '-',
                'price' => (int) $variant->price,
                'discount_amount' => 0,
                'qty' => 1,
                'serial_numbers' => [''], // array of SNs based on qty
                'sku' => $variant->sku ?? '',
                'has_sn' => (bool) $variant->has_sn,
                'is_second' => $isSecond,
            ];
        }
        $this->showVariantModal = false;
        $this->variantModalProduct = null;
        $this->variantModalVariants = [];
        $this->syncSinglePaymentAmount();
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // re-index
        $this->syncSinglePaymentAmount();
    }

    public function incrementCartItem($index)
    {
        if (isset($this->cart[$index])) {
            // 1. Naikkan jumlah kuantitas barang
            $this->cart[$index]['qty']++;

            // JANGAN lakukan push string kosong ('') lagi di sini.
            // Biarkan array serial_numbers tetap apa adanya sampai user melakukan scan.

            $this->syncSinglePaymentAmount();
        }
    }

    public function decrementCartItem($index)
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['qty'] > 1) {
            // 1. Turunkan jumlah kuantitas barang
            $this->cart[$index]['qty']--;

            // 2. Jika jumlah array SN melebihi qty yang baru,
            // kita hapus elemen/slot paling terakhir agar sinkron.
            if (isset($this->cart[$index]['serial_numbers'])) {
                while (count($this->cart[$index]['serial_numbers']) > $this->cart[$index]['qty']) {
                    array_pop($this->cart[$index]['serial_numbers']);
                }

                // Catatan: Sinkronisasi legacy di sini sudah dihapus!
            }

            $this->syncSinglePaymentAmount();
        }
    }


    public function updateSerialNumber($index, $snIndex, $value)
    {
        $value = trim($value);

        if (isset($this->cart[$index]) && !empty($value)) {

            $expectedSku = $this->cart[$index]['sku'] ?? null;

            if (empty($expectedSku)) {
                $this->dispatch('toast', title: 'Error Data', message: 'SKU untuk produk ini tidak ditemukan di keranjang.', type: 'error');
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");
                return;
            }

            // =================================================================
            // PROSES VALIDASI SN KE ACCURATE ONLINE
            // =================================================================
            $accurateService = app(\App\Services\AccurateService::class);
            $dbSource = $this->databaseSource ?? 'syihab';

            // Menampung string status ('valid', 'not_found', 'mismatch', 'error')
            $status = $accurateService->checkSerialNumberExistance($value, $expectedSku, $dbSource);

            if ($status !== 'valid') {
                $title = 'Gagal Validasi';
                $message = 'Terjadi kesalahan saat memvalidasi SN.';

                // Pilah pesan error sesuai kondisi riil dari Accurate
                if ($status === 'not_found') {
                    $title = 'SN Tidak Ditemukan';
                    $message = "Serial Number '{$value}' tidak terdaftar di Accurate ({$dbSource}).";
                } elseif ($status === 'mismatch') {
                    $title = 'SN Tidak Sesuai';
                    $message = "SN '{$value}' ada di Accurate, TAPI milik produk/barang lain.";
                } elseif ($status === 'invalid_type') {
                    // KONDISI BARU: Menangkap input yang bukan Serial Number
                    $title = 'Input Salah';
                    $message = "Kode '{$value}' terdeteksi sebagai Barcode/SKU, harap masukkan Serial Number.";
                } elseif ($status === 'error') {
                    $title = 'Gangguan Sistem';
                    $message = "Gagal menghubungi Accurate. Silakan coba beberapa saat lagi.";
                }

                // Kirim toast spesifik sesuai error-nya
                $this->dispatch(
                    'toast',
                    title: $title,
                    message: $message,
                    type: 'error',
                    duration: 4000
                );

                // Kosongkan input text pencarian di browser
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");

                return; // Gagalkan pengisian SN ke cart
            }
            // =================================================================

            // =================================================================
            // TAMBAHAN: CEK KESESUAIAN WAREHOUSE DI TABEL product_serial_numbers
            // =================================================================
            $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;

            // Cari record SN di database lokal
            $localSnRecord = \Illuminate\Support\Facades\DB::table('product_serial_numbers')
                ->where('serial_number', $value) // sesuaikan kolom jika namanya 'sn'
                ->first();

            if (!$localSnRecord) {
                $this->dispatch('toast', title: 'Error', message: "Serial Number '{$value}' tidak ditemukan di database lokal.", type: 'error');
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");
                return;
            }

            // Cek jika warehouse tidak cocok
            if ($localSnRecord->warehouse_id != $warehouseId) {
                $actualWarehouseName = \Illuminate\Support\Facades\DB::table('warehouses')
                    ->where('id', $localSnRecord->warehouse_id)
                    ->value('name'); // sesuaikan kolom nama gudang Anda

                $warehouseTarget = $actualWarehouseName ?? 'Gudang Lain';

                $this->dispatch(
                    'toast',
                    title: 'Error',
                    message: "Serial Number '{$value}' ada di gudang {$warehouseTarget}. Silahkan lakukan pemindahan barang di accurate.",
                    type: 'error'
                );

                // Kosongkan kembali input di browser jika salah gudang
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");
                return;
            }
            // =================================================================

            // 2. Pastikan array serial_numbers sudah ada (buat jaga-jaga saja)
            if (!isset($this->cart[$index]['serial_numbers'])) {
                $this->cart[$index]['serial_numbers'] = [];
            }

            // 3. Langsung masukkan nilai SN baru ke index yang dituju
            $this->cart[$index]['serial_numbers'][$snIndex] = $value;

            // Catatan: Baris kode step ke-4 (Legacy) sudah dihapus total!
        }
    }
    // ─── Stock Modal Properties ────────────────────────────────
    public $showStockModal = false;
    public $stockModalData = [];
    public $stockModalItemTitle = '';

    public function checkStock($index)
    {
        // 1. Pastikan item ada di keranjang
        if (!isset($this->cart[$index])) {
            $this->dispatch('toast', title: 'Error', message: 'Item tidak ditemukan di keranjang.', type: 'error');
            return;
        }

        $item = $this->cart[$index];
        $userWarehouseId = Auth::user()->warehouse_id;

        // 2. Ambil data varian beserta SEMUA stok gudang. 
        // Pastikan relasi 'warehouse' ada di model WarehouseStock kamu.
        if (isset($item['is_second']) && $item['is_second']) {
            $variant = SecondProductVariant::with(['warehouseStocks.warehouse'])->find($item['variant_id']);
        } else {
            $variant = ProductVariant::with(['warehouseStocks.warehouse'])->find($item['variant_id']);
        }

        // 3. Mapping data untuk ditampilkan di modal
        if ($variant) {
            $this->stockModalItemTitle = "{$item['name']} ({$item['color']} - {$item['storage']})";

            $this->stockModalData = $variant->warehouseStocks->map(function ($ws) use ($userWarehouseId) {
                return [
                    // Sesuaikan 'name' jika field nama gudang di tabelmu beda (misal: nama_gudang)
                    'warehouse_name' => $ws->warehouse->name ?? 'Gudang Tidak Diketahui',
                    'stock' => $ws->stock,
                    'is_current_user_warehouse' => $ws->warehouse_id === $userWarehouseId,
                ];
            })->toArray();

            // Tampilkan Modal
            $this->showStockModal = true;
        } else {
            $this->dispatch('toast', title: 'Gagal', message: 'Data varian tidak ditemukan di database.', type: 'error');
        }
    }

    public function closeStockModal()
    {
        $this->showStockModal = false;
        $this->stockModalData = [];
        $this->stockModalItemTitle = '';
    }

    public function removeSerialNumber($index, $snIndex)
    {
        // Fungsi untuk menghapus badge SN saat tombol X diklik
        if (isset($this->cart[$index]['serial_numbers'][$snIndex])) {

            // 1. Hapus SN berdasarkan index-nya
            unset($this->cart[$index]['serial_numbers'][$snIndex]);

            // 2. WAJIB: Reset urutan key array agar kembali berurutan (0, 1, 2...)
            $this->cart[$index]['serial_numbers'] = array_values($this->cart[$index]['serial_numbers']);

            // Catatan: Bagian "Sinkronisasi ulang data legacy backward compatibility" sudah dihapus total!
        }
    }

    // ─── Customer Actions ──────────────────────────────────────

    public function selectCustomer($id)
    {
        $this->selectedCustomerId = $id;
        $this->searchCustomer = '';
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomerId = null;
        $this->isNewCustomer = false;
    }

    // ─── Sales Actions ──────────────────────────────────────

    public function selectSales($id)
    {
        $sales = \App\Models\Employe::find($id);
        if ($sales && !collect($this->selectedSales)->contains('id', $id)) {
            $this->selectedSales[] = [
                'id' => $sales->id,
                'name' => $sales->name,
                'employee_no' => $sales->employee_no
            ];
        }
        $this->searchSales = '';
    }

    public function removeSales($id)
    {
        $this->selectedSales = array_values(array_filter($this->selectedSales, function ($s) use ($id) {
            return $s['id'] != $id;
        }));
    }

    // ─── Checkout ──────────────────────────────────────────────

    public function openCheckout()
    {
        if (empty($this->cart)) {
            $this->dispatch('toast', title: 'Keranjang Kosong', message: 'Tambahkan produk ke keranjang terlebih dahulu.', type: 'warning');
            return;
        }
        // Validate all items have SN
        foreach ($this->cart as $item) {
            // Jika produk tidak membutuhkan SN, lewati validasi
            if (!isset($item['has_sn']) || $item['has_sn']) {
                // Ambil array serial_numbers, default ke array kosong jika tidak ada
                $sns = $item['serial_numbers'] ?? [];

                // array_filter akan membuang elemen yang isinya string kosong '' atau null
                $validSns = array_filter($sns, fn($value) => trim($value) !== '');

                // Jika setelah difilter ternyata kosong, atau jumlah SN yang diisi kurang dari QTY
                if (empty($validSns) || count($validSns) < $item['qty']) {
                    $this->dispatch('toast', title: 'SN Belum Lengkap', message: 'Pastikan semua item sudah diisi Serial Number / IMEI sesuai jumlah barang.', type: 'warning');
                    return;
                }
            }
        }

        // Validate QC Serah Terima for Second items with SN
        foreach ($this->cart as $item) {
            if (($item['variant_type'] ?? '') === \App\Models\SecondProductVariant::class && (!isset($item['has_sn']) || $item['has_sn'])) {
                $sns = array_filter($item['serial_numbers'] ?? [], fn($value) => trim($value) !== '');
                foreach ($sns as $sn) {
                    $hasPassedQc = \App\Models\DeviceInspection::where('imei', $sn)
                        ->where('label', 'QC Serah Terima')
                        ->where('verdict', 'pass')
                        ->exists();

                    if (!$hasPassedQc) {
                        $this->dispatch('toast', title: 'QC Serah Terima Belum Lulus', message: "Serial Number {$sn} belum lulus QC Serah Terima. Silakan lakukan QC dari keranjang belanja.", type: 'warning');
                        return;
                    }
                }
            }
        }

        if (!$this->selectedCustomerId && !$this->isNewCustomer) {
            $this->dispatch('toast', title: 'Customer Belum Dipilih', message: 'Pilih atau buat data customer terlebih dahulu.', type: 'warning');
            return;
        }

        if (empty($this->selectedSales)) {
            $this->dispatch('toast', title: 'Sales Belum Dipilih', message: 'Pilih minimal 1 tenaga penjual.', type: 'warning');
            return;
        }

        if (!$this->isPaymentsValid) {
            $this->dispatch('toast', title: 'Pembayaran Belum Sesuai', message: 'Pastikan total pembayaran cocok dengan tagihan dan semua metode pembayaran sudah dipilih.', type: 'warning');
            return;
        }

        $this->showCheckoutModal = true;
    }

    public function processPayment()
    {

        try {
            $customerId = $this->selectedCustomerId;

            // Jika customer baru, buat user terlebih dahulu
            if ($this->isNewCustomer && !$customerId) {

                // 1. Cek jika input HANYA berisi angka 0
                if (preg_match('/^0+$/', (string) $this->customerPhone)) {
                    $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: 'Nomor HP tidak boleh hanya berisi angka 0.', type: 'error');
                    return; // Hentikan proses di sini
                }

                // Tentukan email yang akan digunakan
                $emailToValidate = $this->customerEmail ?: ($this->customerPhone . rand(1000, 9999) . '@zpos.com');

                // 2. Terapkan Validasi menggunakan Validator Facade
                $validator = \Illuminate\Support\Facades\Validator::make(
                    [
                        'customerName'  => $this->customerName,
                        'customerPhone' => $this->customerPhone,
                        'customerEmail' => $emailToValidate,
                    ],
                    [
                        'customerName'  => 'required|string|max:255',
                        // Tambahkan rule unique langsung di sini
                        'customerPhone' => 'required|string|max:20|unique:user_profiles,phone_number',
                        'customerEmail' => 'nullable|email|unique:users,email',
                    ],
                    [
                        'customerName.required'  => 'Nama customer wajib diisi.',
                        'customerPhone.required' => 'Nomor HP customer wajib diisi.',
                        'customerPhone.unique'   => 'Nomor HP ini sudah terdaftar. Silakan pilih customer dari daftar pencarian.',
                        'customerEmail.email'    => 'Format email tidak valid.',
                        'customerEmail.unique'   => 'Email ini sudah terdaftar. Silakan pilih customer dari daftar pencarian.',
                    ]
                );

                // JIKA VALIDASI GAGAL
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $failedRules = $validator->failed();
                    $firstErrorMessage = $errors->first();

                    // Cek spesifik jika validasi gagal karena nomor HP sudah terdaftar (Rule 'Unique')
                    if (isset($failedRules['customerPhone']['Unique'])) {
                        // Lakukan query ke database untuk mengambil nama dari user_profiles
                        $existingProfile = \Illuminate\Support\Facades\DB::table('user_profiles')
                            ->where('phone_number', $this->customerPhone)
                            ->first();

                        if ($existingProfile) {
                            $namaCustomer = $existingProfile->full_name ?? 'Customer Lain';
                            $firstErrorMessage = "Nomor HP sudah terdaftar atas nama: {$namaCustomer}. Silakan pilih customer dari daftar pencarian.";
                        }
                    }

                    $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: $firstErrorMessage, type: 'error');
                    return; // Hentikan proses pembayaran di sini
                }

                // 3. Jika validasi aman, barulah proses ke database
                $newUser = User::create([
                    'name'     => $this->customerName,
                    'email'    => $emailToValidate,
                    'password' => bcrypt('tokopun' . rand(1000, 9999)),
                ]);
                $newUser->assignRole('user');

                if ($this->customerPhone) {
                    $newUser->profile()->create([
                        'full_name'    => $this->customerName,
                        'phone_number' => $this->customerPhone,
                    ]);
                }

                $customerId = $newUser->id;
            }

            if (!$customerId) {
                $this->dispatch('toast', title: 'Error', message: 'Customer belum dipilih.', type: 'error');
                return;
            }

            if (!$this->isPaymentsValid) {
                $this->dispatch('toast', title: 'Error', message: 'Pembayaran belum valid.', type: 'error');
                return;
            }

            $subtotal = $this->subtotal();
            $manualDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0));
            $promoDiscountAmount = $this->totalPromoDiscount;
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;

            $mdrAmt = $this->mdrAmount;
            $grandTotal = max(0, $subtotal - $totalDiscountAmount);

            \Illuminate\Support\Facades\DB::beginTransaction();

            $order = null;
            if ($this->loadedDraftId) {
                $order = Order::find($this->loadedDraftId);
                if ($order) {
                    // Kembalikan stock dari item draft yang lama
                    foreach ($order->items as $oldItem) {
                        $warehouseStock = \App\Models\WarehouseStock::where([
                            'warehouse_id' => Auth::user()->warehouse_id,
                            'variant_id' => $oldItem->product_variant_id,
                            'variant_type' => $oldItem->product_variant_type,
                        ])->first();
                        if ($warehouseStock) {
                            $warehouseStock->update([
                                'stock' => $warehouseStock->stock + (int)$oldItem->qty
                            ]);
                        }
                    }
                    $order->items()->delete();
                    $order->promos()->detach();

                    $order->update([
                        'user_id' => $customerId,
                        'total_amount' => $subtotal,
                        'shipping_cost' => 0,
                        'discount_amount' => $totalDiscountAmount,
                        'mdr_percentage' => ($subtotal - $totalDiscountAmount) > 0 ? round(($mdrAmt / ($subtotal - $totalDiscountAmount)) * 100, 2) : 0,
                        'mdr_amount' => $mdrAmt,
                        'grand_total' => $grandTotal,
                        'order_status' => 'COMPLETED',
                        'order_channel' => 'POS',
                        'handled_by' => Auth::id(),
                        'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                        'payment_method_id' => $this->payments[0]['payment_method_id'] ?: null,
                        'payment_method_rate_id' => $this->payments[0]['payment_method_rate_id'] ?: null,
                        'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                        'notes' => $this->notes,
                        'accurate_so_id' => $this->loadedAccurateSoId,
                        'accurate_so_number' => $this->loadedAccurateSoNumber,
                    ]);
                    $orderNumber = $order->order_number;
                }
            }
            if (!$order) {
                // 1. Tentukan tanggal yang digunakan (dari input form atau hari ini)
                $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();

                // 2. Generate order number berdasarkan order_date
                $orderNumber = 'POS-SYB-' . $dateToUse->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
                    Order::whereDate('order_date', $dateToUse->format('Y-m-d')) // <- Menggunakan order_date
                        ->where('order_channel', 'POS')
                        ->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // Create Order
                $order = Order::create([
                    'business_unit_id' => Auth::user()->business_unit_id ?? 1,
                    'user_id' => $customerId,
                    'order_number' => $orderNumber,
                    'order_date' => $dateToUse->format('Y-m-d'),
                    'total_amount' => $subtotal,
                    'shipping_cost' => 0,
                    'discount_amount' => $totalDiscountAmount,
                    'mdr_percentage' => ($subtotal - $totalDiscountAmount) > 0 ? round(($mdrAmt / ($subtotal - $totalDiscountAmount)) * 100, 2) : 0,
                    'mdr_amount' => $mdrAmt,
                    'grand_total' => $grandTotal,
                    'order_status' => 'COMPLETED',
                    'order_channel' => 'POS',
                    'handled_by' => Auth::id(),
                    'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                    'payment_method_id' => $this->payments[0]['payment_method_id'] ?: null,
                    'payment_method_rate_id' => $this->payments[0]['payment_method_rate_id'] ?: null,
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                    'notes' => $this->notes,
                    'accurate_so_id' => $this->loadedAccurateSoId,
                    'accurate_so_number' => $this->loadedAccurateSoNumber,
                ]);
            }

            // Save promos to pivot table
            if (!empty($this->selectedPromos)) {
                $promos = \App\Models\Promo::with(['skus', 'bundleSkus'])->whereIn('id', $this->selectedPromos)->get();
                foreach ($promos as $promo) {
                    $applied = 0;

                    // 1. Kalkulasi Diskon Utama
                    $eligibleMain = $this->calculateEligibleCart($promo, false);
                    if ($promo->discount_type === 'fixed') {
                        $applied += $promo->discount_value;
                    } else {
                        $calc = $eligibleMain['amount'] * ($promo->discount_value / 100);
                        if ($promo->max_discount) $calc = min($calc, $promo->max_discount);
                        $applied += $calc;
                    }

                    // 2. Kalkulasi Diskon Tambahan (Bundle)
                    if ($promo->is_bundle) {
                        $eligibleBundle = $this->calculateEligibleCart($promo, true);
                        if ($eligibleBundle['qty'] > 0) {
                            if ($promo->bundle_discount_type === 'fixed') {
                                $applied += $promo->bundle_discount_value * $eligibleBundle['qty'];
                            } else {
                                $calc = $eligibleBundle['amount'] * ($promo->bundle_discount_value / 100);
                                if ($promo->bundle_max_discount) $calc = min($calc, $promo->bundle_max_discount);
                                $applied += $calc;
                            }
                        }
                    }

                    $order->promos()->attach($promo->id, ['discount_applied' => $applied]);
                }
            }


            // Create Order Items + reduce stock
            foreach ($this->cart as $item) {
                // 1. Murni hanya mengambil dari array 'serial_numbers'
                $rawSns = $item['serial_numbers'] ?? [];

                // 2. Bersihkan spasi berlebih dan buang array yang kosong
                $cleanSns = array_values(array_filter(array_map('trim', $rawSns)));
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_variant_type' => $item['variant_type'],
                    'qty' => $item['qty'],
                    'price_at_checkout' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'discount_amount' => (int) ($item['discount_amount'] ?? 0),
                    // 3. Simpan ke database. Jika ada 2 SN, jadinya: "SN001, SN002"
                    'serial_number' => !empty($cleanSns) ? implode(', ', $cleanSns) : '',
                ]);

                // Reduce stock
                $warehouseStock = \App\Models\WarehouseStock::firstOrCreate(
                    [
                        'warehouse_id' => Auth::user()->warehouse_id,
                        'variant_id' => $item['variant_id'],
                        'variant_type' => $item['variant_type'],
                    ],
                    [
                        'stock' => 0
                    ]
                );
                $warehouseStock->update([
                    'stock' => max(0, $warehouseStock->stock - (int)$item['qty'])
                ]);
            }

            // Create OrderPayments (for each split payment row)
            foreach ($this->payments as $payment) {
                $rowTotal = (float)$payment['amount'];

                OrderPayment::create([
                    'order_id' => $order->id,
                    'xendit_external_id' => 'ORD-BUY-' . date('YmdHis') . rand(1000, 9999),
                    'amount' => $rowTotal,
                    'status' => 'PAID',
                    'payment_method_id' => $payment['payment_method_id'],
                    'payment_method_rate_id' => $payment['payment_method_rate_id'] ?: null,
                    'no_kontrak' => $payment['no_kontrak'] ?? null,
                ]);
            }

            // KUNCI TRANSAKSI: Jika sampai sini aman, simpan permanen ke DB Lokal
            \Illuminate\Support\Facades\DB::commit();


            // ─── INTEGRASI ACCURATE (Di luar DB Transaction agar POS tidak macet jika API lambat) ───
            try {
                $accurateService = app(AccurateService::class);
                $customerUser = User::find($customerId);
                $handler = Auth::user();
                $branchName = $handler->branch->name ?? 'Banjarbaru';
                $warehouseName = $handler->warehouse->name ?? 'Head Office';

                $hasSecond = collect($this->cart)->contains('is_second', true);
                $dbSource = $hasSecond ? 'second' : 'syihab';

                // Sesuaikan penamaan Cabang & Gudang untuk Accurate GSK
                $accurateBranchName = $dbSource === 'second' ? 'GSK ' . $branchName : $branchName;
                $accurateWarehouseName = $dbSource === 'second' ? 'GSK ' . $warehouseName : $warehouseName;

                // Sync Customer to Accurate
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();

                // Sales Invoice
                if (!$order->accurate_invoice_no) {
                    $detailItems = [];
                    foreach ($this->cart as $item) {

                        // PERBAIKAN SN ACCURATE: Filter bersih data SN terlebih dahulu
                        $rawSns = $item['serial_numbers'] ?? [];
                        $cleanSns = array_values(array_filter(array_map('trim', $rawSns)));

                        $detailSN = [];
                        if (!empty($cleanSns)) {
                            foreach ($cleanSns as $sn) {
                                $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                            }
                        }

                        $detailSalesman = [];
                        foreach ($this->selectedSales as $sales) {
                            if (!empty($sales['employee_no'])) {
                                $detailSalesman[] = (string) $sales['employee_no'];
                            }
                        }

                        $itemData = [
                            'itemNo' => $item['sku'] ?: 'ITEM-UNKNOWN',
                            'warehouseName' => $accurateWarehouseName,
                            'unitPrice' => $item['price'],
                            'quantity' => $item['qty'],
                            'itemCashDiscount' => $item['discount_amount'] ?? 0,
                            // 'detailName' => $item['name'] . ' ' . $item['color'] . ' ' . $item['storage'],
                            // 'detailSerialNumber' => $detailSN,
                            'salesmanListNumber' => $detailSalesman,

                        ];
                        if (!empty($detailSN)) {
                            $itemData['detailSerialNumber'] = $detailSN;
                        }
                        if ($this->loadedAccurateSoId) {
                            $itemData['salesOrderId'] = $this->loadedAccurateSoId;
                        }

                        $detailItems[] = $itemData;
                    }

                    $siData = [
                        'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                        'branchName' => $accurateBranchName,
                        'detailItem' => $detailItems,
                        // 'cashDiscount' => $manualDiscountAmount,
                        'inclusiveTax' => true,
                        'transDate'    => $this->order_date
                            ? Carbon::parse($this->order_date)->format('d/m/Y')
                            : now()->format('d/m/Y'),
                        'taxable' => true,
                        'description' => $this->notes
                    ];

                    $siResult = $accurateService->postSalesInvoice($siData, $dbSource);
                    if (isset($siResult['r']['number'])) {
                        $order->update(['accurate_invoice_no' => $siResult['r']['number']]);
                    }
                }

                // Sales Receipts (jika belum ada dan ada invoice)
                if (!$order->accurate_receipt_no && $order->accurate_invoice_no) {
                    $srNumbers = [];
                    $promosAppliedToSR = false; // Flag agar promo hanya diaplikasikan 1x di SR pertama

                    foreach ($this->payments as $index => $payment) {
                        $pm = \App\Models\PaymentMethod::findOrFail($payment['payment_method_id']);
                        $rate = $payment['payment_method_rate_id'] ? \App\Models\PaymentMethodRate::find($payment['payment_method_rate_id']) : null;

                        $pct = $this->getMdrPercentage($payment);
                        $rowMdr = $pct > 0 ? round((float)$payment['amount'] * $pct / 100, 0) : 0;
                        $rowBaseAmount = (float)$payment['amount'];
                        $netReceiptAmount = $rowBaseAmount - $rowMdr;

                        $detailDiscounts = [];

                        // 1. Masukkan MDR sebagai potongan SR
                        if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                            $detailDiscounts[] = [
                                'accountNo' => $rate->accurate_account_no,
                                'amount' => (float) $rowMdr,
                                'departmentName' => $accurateBranchName,
                                'discountNotes' => 'MDR'
                            ];
                        }

                        // 2. Masukkan Semua Promo sebagai potongan SR (hanya di pembayaran pertama)
                        $promoDiscountsTotal = 0;
                        if (!$promosAppliedToSR) {
                            foreach ($order->promos as $promo) {
                                if ($promo->accurate_account_no && $promo->pivot->discount_applied > 0) {
                                    $detailDiscounts[] = [
                                        'accountNo' => $promo->accurate_account_no,
                                        'amount' => (float) $promo->pivot->discount_applied,
                                        'departmentName' => $accurateBranchName,
                                        'discountNotes' => 'Promo: ' . $promo->name
                                    ];
                                    $promoDiscountsTotal += (float) $promo->pivot->discount_applied;
                                }
                            }
                            $promosAppliedToSR = true;
                        }

                        $detailInvoiceItem = [
                            'invoiceNo' => $order->accurate_invoice_no,
                            'paymentAmount' => $rowBaseAmount + $promoDiscountsTotal, // Bayar sisa tagihan invoice = Cash + Promo
                        ];

                        if (!empty($detailDiscounts)) {
                            $detailInvoiceItem['detailDiscount'] = $detailDiscounts;
                        }

                        $srData = [
                            'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                            'branchName' => $accurateBranchName,
                            'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                            'receiptAmount' => (float) $netReceiptAmount, // Net cash ke bank
                            'chequeAmount' => (float) $netReceiptAmount,
                            'transDate'    => $this->order_date
                                ? Carbon::parse($this->order_date)->format('d/m/Y')
                                : now()->format('d/m/Y'),
                            'detailInvoice' => [
                                $detailInvoiceItem
                            ],
                            'description' => $this->notes
                        ];
                        Log::info('POS Accurate Integration SR Data: ' . json_encode($srData));
                        $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                        if (isset($srResult['r']['number'])) {
                            $srNumbers[] = $srResult['r']['number'];
                        }
                    }

                    if (!empty($srNumbers)) {
                        $order->update(['accurate_receipt_no' => implode(', ', $srNumbers)]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('POS Accurate Integration Error: ' . $e->getMessage());
                // Sengaja tidak me-rethrow exception agar transaksi POS lokal tetap dianggap berhasil
            }

            // Success! Show receipt
            $this->completedOrder = $order->load(['items', 'user', 'payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy']);
            $this->showCheckoutModal = false;
            $this->showReceiptModal = true;

            $this->resetCheckout();
            $this->dispatch('toast', title: 'Transaksi Berhasil', message: 'Order ' . $orderNumber . ' berhasil diproses.', type: 'success');
        } catch (\Exception $e) {
            // BATALKAN semua penulisan DB lokal jika terjadi kegagalan sebelum commit
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('POS Payment Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: $e->getMessage(), type: 'error');
        }
    }

    public function resetCheckout()
    {
        $this->loadedAccurateSoId = null;
        $this->loadedAccurateSoNumber = null;
        $this->loadedDraftId = null;
        $this->cart = [];
        $this->selectedSales = [];
        $this->discount_amount = 0;
        $this->notes = '';
        $this->selectedCustomerId = null;
        $this->isNewCustomer = false;
        $this->searchCustomer = '';
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerEmail = '';
        $this->search = '';
        $this->payments = [
            [
                'payment_method_id' => '',
                'payment_method_rate_id' => '',
                'no_kontrak' => '',
                'amount' => 0,
            ]
        ];
        $this->order_date = null;
    }

    public function saveAsDraft()
    {
        if (empty($this->cart)) {
            $this->dispatch('toast', title: 'Keranjang Kosong', message: 'Tambahkan produk ke keranjang terlebih dahulu.', type: 'warning');
            return;
        }

        // Validate all items have SN
        foreach ($this->cart as $item) {
            $sn = $item['serial_number'] ?? null;
            $sns = $item['serial_numbers'] ?? [];
            if (empty(trim($item['sku'] ?? ''))) {
                $this->dispatch('toast', title: 'Data Produk Tidak Valid', message: 'Produk ' . ($item['name'] ?? 'Unknown') . ' tidak memiliki SKU/Kode Barang.', type: 'error');
                return;
            }
            if (empty($sn) && empty($sns)) {
                $this->dispatch('toast', title: 'SN Belum Lengkap', message: 'Pastikan semua item sudah diisi Serial Number / IMEI.', type: 'warning');
                return;
            }
        }

        if (!$this->selectedCustomerId && !$this->isNewCustomer) {
            $this->dispatch('toast', title: 'Customer Belum Dipilih', message: 'Pilih atau buat data customer terlebih dahulu.', type: 'warning');
            return;
        }

        if (empty($this->selectedSales)) {
            $this->dispatch('toast', title: 'Sales Belum Dipilih', message: 'Pilih minimal 1 tenaga penjual.', type: 'warning');
            return;
        }

        try {
            $customerId = $this->selectedCustomerId;
            $hasSecond = collect($this->cart)->contains('is_second', true);
            $dbSource = $hasSecond ? 'second' : 'syihab';
            $accurateService = app(\App\Services\AccurateService::class);

            // Jika customer baru, buat user terlebih dahulu
            if ($this->isNewCustomer && !$customerId) {
                // 1. Tentukan email yang akan divalidasi
                // Cek jika input HANYA berisi angka 0 (satu atau lebih 0 tanpa ada angka/karakter lain)
                if (preg_match('/^0+$/', (string) $this->customerPhone)) {
                    $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: 'Nomor HP tidak boleh hanya berisi angka 0.', type: 'error');
                    return; // Hentikan proses di sini
                }
                $emailToValidate = $this->customerEmail ?: ($this->customerPhone . rand(1000, 9999) . '@zpos.com');
                // 2. Terapkan Validasi Ketat di Livewire
                try {
                    $this->validate(
                        [
                            'customerName'  => 'required|string|max:255',
                            'customerPhone' => 'required|string|max:20',
                            'customerEmail' => 'nullable|email',
                        ],
                        [
                            'customerName.required'  => 'Nama customer wajib diisi.',
                            'customerPhone.required' => 'Nomor HP customer wajib diisi.',
                            'customerEmail.email'    => 'Format email tidak valid.',
                        ]
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $firstErrorMessage = collect($e->errors())->flatten()->first();
                    $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: $firstErrorMessage, type: 'error');
                    return;
                }

                // Cek unik manual karena customerEmail bisa nullable
                if (\App\Models\User::where('email', $emailToValidate)->exists()) {
                    $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: 'Email ini sudah terdaftar. Silakan pilih customer dari daftar pencarian.', type: 'error');
                    return;
                }

                // Cek unik manual untuk nomor HP di tabel user_profiles
                if (\App\Models\UserProfile::where('phone_number', $this->customerPhone)->exists()) {
                    $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: 'Nomor HP ini sudah terdaftar. Silakan pilih customer dari daftar pencarian.', type: 'error');
                    return;
                }

                // 3. Jika validasi aman, barulah proses ke database
                $newUser = User::create([
                    'name' => $this->customerName,
                    'email' => $emailToValidate,
                    'password' => bcrypt('tokopun' . rand(1000, 9999)),
                ]);
                $newUser->assignRole('user');

                if ($this->customerPhone) {

                    $newUser->profile()->create([
                        'full_name' => $this->customerName,
                        'phone_number' => $this->customerPhone,
                    ]);
                }

                $customerId = $newUser->id;
            }

            if (!$customerId) {
                $this->dispatch('toast', title: 'Error', message: 'Customer belum dipilih.', type: 'error');
                return;
            }

            $subtotal = $this->subtotal();
            $manualDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0));
            $promoDiscountAmount = $this->totalPromoDiscount;
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;

            $mdrAmt = $this->mdrAmount;
            $grandTotal = max(0, $subtotal - $totalDiscountAmount);
            $branchName = Auth::user()->branch->name ?? 'Toko';

            \Illuminate\Support\Facades\DB::beginTransaction();

            $order = null;
            if ($this->loadedDraftId) {
                $order = Order::find($this->loadedDraftId);
                if ($order) {
                    // Kembalikan stock dari item draft yang lama
                    foreach ($order->items as $oldItem) {
                        $warehouseStock = \App\Models\WarehouseStock::where([
                            'warehouse_id' => Auth::user()->warehouse_id,
                            'variant_id' => $oldItem->product_variant_id,
                            'variant_type' => $oldItem->product_variant_type,
                        ])->first();
                        if ($warehouseStock) {
                            $warehouseStock->update([
                                'stock' => $warehouseStock->stock + (int)$oldItem->qty
                            ]);
                        }
                    }
                    $order->items()->delete();
                    $order->promos()->detach();

                    $order->update([
                        'user_id' => $customerId,
                        'total_amount' => $subtotal,
                        'shipping_cost' => 0,
                        'discount_amount' => $totalDiscountAmount,
                        'mdr_percentage' => ($subtotal - $totalDiscountAmount) > 0 ? round(($mdrAmt / ($subtotal - $totalDiscountAmount)) * 100, 2) : 0,
                        'mdr_amount' => $mdrAmt,
                        'grand_total' => $grandTotal,
                        'order_status' => 'DRAFT',
                        'order_channel' => 'POS',
                        'handled_by' => Auth::id(),
                        'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                        'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branchName],
                        'notes' => $this->notes,
                    ]);
                    $orderNumber = $order->order_number;
                }
            }

            if (!$order) {
                // Generate order number
                $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();

                // 2. Generate order number berdasarkan order_date
                $orderNumber = 'POS-SYB-' . $dateToUse->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
                    Order::whereDate('order_date', $dateToUse->format('Y-m-d')) // <- Menggunakan order_date
                        ->where('order_channel', 'POS')
                        ->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // Create Order
                $order = Order::create([
                    'business_unit_id' => Auth::user()->business_unit_id ?? 1,
                    'user_id' => $customerId,
                    'order_number' => $orderNumber,
                    'order_date'                => $dateToUse->format('Y-m-d'),
                    'total_amount' => $subtotal,
                    'shipping_cost' => 0,
                    'discount_amount' => $totalDiscountAmount,
                    'mdr_percentage' => ($subtotal - $totalDiscountAmount) > 0 ? round(($mdrAmt / ($subtotal - $totalDiscountAmount)) * 100, 2) : 0,
                    'mdr_amount' => $mdrAmt,
                    'grand_total' => $grandTotal,
                    'order_status' => 'DRAFT',
                    'order_channel' => 'POS',
                    'handled_by' => Auth::id(),
                    'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branchName],
                    'notes' => $this->notes,
                ]);
            }

            // Save promos to pivot table
            if (!empty($this->selectedPromos)) {
                $promos = \App\Models\Promo::with(['skus', 'bundleSkus'])->whereIn('id', $this->selectedPromos)->get();
                foreach ($promos as $promo) {
                    $applied = 0;
                    $eligibleMain = $this->calculateEligibleCart($promo, false);
                    if ($promo->discount_type === 'fixed') {
                        $applied += $promo->discount_value;
                    } else {
                        $calc = $eligibleMain['amount'] * ($promo->discount_value / 100);
                        if ($promo->max_discount) $calc = min($calc, $promo->max_discount);
                        $applied += $calc;
                    }

                    if ($promo->is_bundle) {
                        $eligibleBundle = $this->calculateEligibleCart($promo, true);
                        if ($eligibleBundle['qty'] > 0) {
                            if ($promo->bundle_discount_type === 'fixed') {
                                $applied += $promo->bundle_discount_value * $eligibleBundle['qty'];
                            } else {
                                $calc = $eligibleBundle['amount'] * ($promo->bundle_discount_value / 100);
                                if ($promo->bundle_max_discount) $calc = min($calc, $promo->bundle_max_discount);
                                $applied += $calc;
                            }
                        }
                    }

                    $order->promos()->attach($promo->id, ['discount_applied' => $applied]);
                }
            }

            // Create Order Items + reduce stock
            foreach ($this->cart as $item) {
                $rawSns = $item['serial_numbers'] ?? (!empty(trim($item['serial_number'] ?? '')) ? [$item['serial_number']] : []);
                $cleanSns = array_values(array_filter(array_map('trim', $rawSns)));

                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_variant_type' => $item['variant_type'],
                    'qty' => $item['qty'],
                    'price_at_checkout' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'discount_amount' => (int) ($item['discount_amount'] ?? 0),
                    'serial_number' => !empty($cleanSns) ? implode(', ', $cleanSns) : '',
                ]);

                // Reduce stock
                $warehouseStock = \App\Models\WarehouseStock::firstOrCreate(
                    [
                        'warehouse_id' => Auth::user()->warehouse_id,
                        'variant_id' => $item['variant_id'],
                        'variant_type' => $item['variant_type'],
                    ],
                    [
                        'stock' => 0
                    ]
                );
                $warehouseStock->update([
                    'stock' => max(0, $warehouseStock->stock - (int)$item['qty'])
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            // Hit Accurate Sales Order
            try {
                $customerUser = User::find($customerId);
                $accurateService->syncCustomer($customerUser, $dbSource);

                $detailItems = [];
                $warehouseName = Auth::user()->warehouse->name ?? 'Head Office';

                foreach ($this->cart as $item) {
                    $rawSns = $item['serial_numbers'] ?? (!empty(trim($item['serial_number'] ?? '')) ? [$item['serial_number']] : []);
                    $cleanSns = array_values(array_filter(array_map('trim', $rawSns)));

                    $detailSN = [];
                    if (!empty($cleanSns)) {
                        foreach ($cleanSns as $sn) {
                            $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                        }
                    } else {
                        $detailSN[] = ['serialNumberNo' => '-', 'quantity' => 1];
                    }

                    $detailSalesman = [];
                    foreach ($this->selectedSales as $sales) {
                        if (!empty($sales['employee_no'])) {
                            $detailSalesman[] = (string) $sales['employee_no'];
                        }
                    }

                    $detailItems[] = [
                        'itemNo' => $item['sku'] ?: 'ITEM-UNKNOWN',
                        'warehouseName' => $warehouseName,
                        'unitPrice' => $item['price'],
                        'quantity' => $item['qty'],
                        'itemCashDiscount' => $item['discount_amount'] ?? 0,
                        // 'detailName' => $item['name'] . ' ' . $item['color'] . ' ' . $item['storage'],
                        // 'detailSerialNumber' => $detailSN, // Di Sales Order belum motong stok fisik beneran, tapi jika butuh serial number bisa ditambahkan
                        'salesmanListNumber' => $detailSalesman
                    ];
                }

                $soData = [
                    'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                    'branchName' => $branchName,
                    'detailItem' => $detailItems,
                    // 'cashDiscount' => $manualDiscountAmount,
                    'transDate'    => $this->order_date
                        ? Carbon::parse($this->order_date)->format('d/m/Y')
                        : now()->format('d/m/Y'),
                    'inclusiveTax' => true,
                    'taxable' => true,
                    'description' => 'DRAFT ' . $this->notes
                ];

                $soResult = $accurateService->postSalesOrder($soData, $dbSource);
                if (isset($soResult['r']['id']) && isset($soResult['r']['number'])) {
                    $order->update([
                        'accurate_so_id' => $soResult['r']['id'],
                        'accurate_so_number' => $soResult['r']['number'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('POS Accurate SO Draft Error: ' . $e->getMessage());
                // Tetap berhasil nyimpan draft lokal
            }

            // Reset state keranjang
            $this->resetCheckout();

            $this->dispatch('toast', title: 'Draft Berhasil', message: 'Order ' . $orderNumber . ' berhasil disimpan sebagai Draft.', type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('POS Save Draft Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: $e->getMessage(), type: 'error');
        }
    }

    public function closeReceipt()
    {
        $this->showReceiptModal = false;
        $this->completedOrder = null;
    }

    public function newTransaction()
    {
        $this->cart = [];
        $this->discount_amount = 0;
        $this->notes = '';
        $this->payments = [
            [
                'payment_method_id' => '',
                'payment_method_rate_id' => '',
                'no_kontrak' => '',
                'amount' => 0,
            ]
        ];
        $this->selectedCustomerId = null;
        $this->isNewCustomer = false;
        $this->searchCustomer = '';
        $this->search = '';
        $this->showReceiptModal = false;
        $this->completedOrder = null;
    }

    // ─── Kirim Struk via Email (SMTP) ─────────────────────────
    public function sendReceiptToEmail()
    {
        if (!$this->completedOrder) return;

        // Ambil ID dari state, lalu tarik data paling FRESH langsung dari database
        $orderId = $this->completedOrder->id;
        $order = Order::with('user')->find($orderId);
        $email = $order->user->email ?? null;

        // ─── VALIDASI PEMBATASAN AKSES UTK FRONT-LINER (FL) ───
        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $order->is_email_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk email hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        // Validasi jika email kosong atau merupakan email dummy sistem POS
        if (!$email || str_contains($email, '@pos.tokopun.com')) {
            $this->dispatch('toast', title: 'Gagal Kirim', message: 'Email customer tidak valid atau kosong.', type: 'warning');
            return;
        }

        try {
            // Generate file PDF
            $pdf = $this->generateReceiptPdf($order);
            $pdfContent = $pdf->output();
            $filename = 'Struk_' . $order->order_number . '.pdf';

            // Mengirim email menggunakan Mailable yang sudah dibuat
            Mail::mailer('pos_sales')
                ->to($email)
                ->send(new SalesReceiptMail($order, $pdfContent, $filename));

            // 1. Update ke database menggunakan instance fresh
            $order->update(['is_email_sent' => true]);

            // 2. PAKSA REFRESH STATE LIVEWIRE UTAMA
            $this->completedOrder->refresh();

            $this->dispatch('toast', title: 'Berhasil', message: 'Struk digital telah dikirim ke ' . $email, type: 'success');
        } catch (\Exception $e) {
            Log::error('POS Email Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Koneksi SMTP bermasalah: ' . $e->getMessage(), type: 'error');
        }
    }

    // ─── Kirim Struk via WA + Simpan di Storage (Qontak) ─────────────────────────
    public function sendReceiptToQontak()
    {
        // 1. Validasi Awal data order
        if (!$this->completedOrder) return;

        // Ambil ID dari state, lalu tarik data paling FRESH langsung dari database
        $orderId = $this->completedOrder->id;
        $order = Order::with('user.profile')->find($orderId);
        $phone = $order->user->profile->phone_number ?? null;

        // ─── VALIDASI PEMBATASAN AKSES UTK FRONT-LINER (FL) ───
        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $order->is_wa_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk WhatsApp hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$phone) {
            $this->dispatch('toast', title: 'Gagal', message: 'Nomor HP customer tidak ditemukan.', type: 'warning');
            return;
        }

        // Standardisasi nomor HP (08xx -> 628xx)
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // 2. Tarik variabel dari env untuk Qontak
        $fullUrl = env('QONTAK_API_URL');
        $method = 'POST';

        $parsedUrl = parse_url($fullUrl);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $endpoint = $parsedUrl['path'];

        $clientId = env('QONTAK_CLIENT_ID');
        $clientSecret = env('QONTAK_CLIENT_SECRET');

        // ─── 3. PROSES GENERATE PDF & SIMPAN KE STORAGE PUBLIK ────
        try {
            // Panggil helper terpusat untuk generate instance PDF
            $pdf = $this->generateReceiptPdf($order);

            // Buat nama file unik berdasarkan nomor invoice
            $filename = 'Struk_' . $order->order_number . '.pdf';

            // Tentukan path folder di dalam storage/app/public/
            $folderPath = 'receipts';
            $path = $folderPath . '/' . $filename;

            // Simpan output binary PDF ke disk 'public'
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());

            // Ambil URL Publik asset (Menggunakan konfigurasi APP_URL di .env)
            $pdfPublicUrl = asset('storage/' . $path);
        } catch (\Exception $e) {
            Log::error('Qontak PDF Storage Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal menyimpan file PDF struk ke server.', type: 'error');
            return;
        }

        // ─── 4. PROSES GENERATE HMAC SIGNATURE ────
        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";

        $stringToSign = "date: {$dateString}\n{$requestLine}";

        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);

        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // ─── 5. STRUKTUR PAYLOAD BODY JSON (DENGAN HEADER ATTACHMENT) ────
        $payload = [
            'to_name' => $order->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' => env('QONTAK_CHANNEL_INTEGRATION_ID'),
            'message_template_id' => env('QONTAK_TEMPLATE_ID'),
            'language' => [
                'code' => 'id'
            ],
            'parameters' => [
                // Disuntikkan object header khusus DOCUMENT/PDF sesuai Postman kamu
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        [
                            'key' => 'url',
                            'value' => $pdfPublicUrl
                        ],
                        [
                            'key' => 'filename',
                            'value' => $filename
                        ]
                    ]
                ],
                'body' => [
                    [
                        'key' => '1',
                        'value' => 'nama',
                        'value_text' => $order->user->name ?? 'Customer'
                    ],
                    [
                        'key' => '2',
                        'value' => 'no_invoice',
                        'value_text' => $order->order_number
                    ],
                    [
                        'key' => '3',
                        'value' => 'total_tagihan',
                        'value_text' => 'Rp ' . number_format($order->subtotal, 0, ',', '.')
                    ]
                ]
            ]
        ];

        // ─── 6. EXECUTE API CALL KE QONTAK VIA HTTP CLIENT ────────────────
        try {
            $response = Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($fullUrl, $payload);

            if ($response->successful()) {
                // Update status di database menggunakan instance fresh
                $order->update(['is_wa_sent' => true]);

                // REFRESH STATE LIVEWIRE UTAMA
                $this->completedOrder->refresh();

                $this->dispatch('toast', title: 'Berhasil', message: 'Struk WA dengan PDF berhasil dikirim!', type: 'success');
            } else {
                Log::error('=== DEBUG MEKARI QONTAK ERROR ===');
                Log::error('Status Code: ' . $response->status());
                Log::error('Response Body: ' . $response->body());
                Log::error('Generated URL PDF: ' . $pdfPublicUrl);
                Log::error('=================================');

                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari: Code ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            Log::error('Qontak HMAC Integration Crash: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }

    public function printEscpos()
    {
        if (!$this->completedOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Tidak ada transaksi aktif untuk dicetak.', type: 'error');
            return;
        }

        try {
            $connector = new WindowsPrintConnector("PrinterKasir");
            $printer = new Printer($connector);
            $printer->initialize();

            $this->generateEscposContent($printer);


            // Mengubah kata 'thermal' menjadi 'dot matrix' atau 'kasir'
            $this->dispatch('toast', title: 'Sukses', message: 'Perintah cetak kasir terkirim ke ' . "PrinterKasir", type: 'success');
        } catch (\Exception $e) {
            Log::error('ESCPOS Print Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Cetak Gagal', message: 'Tidak dapat mencetak ke "PrinterKasir" : ' . $e->getMessage(), type: 'error');
        }
    }

    private function generateEscposContent($printer)
    {

        // Ubah ke 33 karena printer menggunakan Font besar/Font B agar tidak meluber
        $maxColumns = 40;
        $separator = str_repeat("-", $maxColumns) . "\n"; // Otomatis membuat 33 karakter '-'

        // Store Title (Center, Large)
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);

        // PERBAIKAN 1: Tambahkan MODE_FONT_B di sini agar ukuran double-nya berbasis Font B
        $printer->selectPrintMode(
            \Mike42\Escpos\Printer::MODE_FONT_B |
                \Mike42\Escpos\Printer::MODE_DOUBLE_WIDTH |
                \Mike42\Escpos\Printer::MODE_DOUBLE_HEIGHT
        );
        $printer->text("SYIHAB STORE\n");

        // PERBAIKAN 2: Kembalikan ke MODE_FONT_B standar (jangan dikosongkan)
        $printer->selectPrintMode(\Mike42\Escpos\Printer::MODE_FONT_B);

        $storeName = $this->completedOrder->shipping_address_snapshot['store'] ?? 'Toko';
        $printer->text($storeName . "\n");
        $printer->text($this->completedOrder->created_at->format('d/m/Y H:i') . "\n");
        $printer->text($separator);

        // Info (Left)
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
        $printer->text($this->formatLine("No. Transaksi", $this->completedOrder->order_number, $maxColumns) . "\n");
        $printer->text($this->formatLine("Kasir", $this->completedOrder->handledBy->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Sales", $this->completedOrder->salesBy->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Customer", $this->completedOrder->user->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Customer No", $this->completedOrder->user->profile->phone_number ?? '-', $maxColumns) . "\n");
        $printer->text($separator);

        // Items List
        foreach ($this->completedOrder->items as $item) {
            $v = $item->variant;
            $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
            // SINKRONISASI LOGIKA DARI BLADE
            if ($v) {
                $ram = $v->ram ?? '';
                $storage = $v->storage ?? '';
                $color = $v->color ?? '';

                // Buat penampung string varian
                $variantDetails = "";

                if ($ram != null && $ram !== '') {
                    $variantDetails .= $ram . "/";
                }

                $variantDetails .= $storage;

                if ($color != null && $color !== '') {
                    $variantDetails .= " " . $color;
                }

                // Gabungkan ke nama item (Mengikuti gaya Blade tanpa tanda kurung)
                // Contoh hasil: "iPhone 13 8GB/256GB Black"
                if (trim($variantDetails) !== '') {
                    $itemName .= " " . trim($variantDetails);
                }
            }

            $printer->text($itemName . "\n");

            $qtyAndPrice = $item->qty . "x Rp " . number_format($item->price_at_checkout, 0, ',', '.');
            $subtotal = "Rp " . number_format($item->subtotal, 0, ',', '.');

            // Mengurangi space di depan menjadi 1 spasi saja agar menghemat karakter yang makin sempit
            $printer->text($this->formatLine(" " . $qtyAndPrice, $subtotal, $maxColumns) . "\n");

            if ($item->serial_number) {
                $printer->text(" SN: " . $item->serial_number . "\n");
            }
        }
        $printer->text($separator);

        // Subtotal
        $printer->text($this->formatLine("Total", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), $maxColumns) . "\n");
        $printer->text($separator);

        // // Grand Total (Bold)
        // $printer->setEmphasis(true);
        // $printer->text($this->formatLine("TOTAL", "Rp " . number_format($this->completedOrder->grand_total, 0, ',', '.'), $maxColumns) . "\n");
        // $printer->setEmphasis(false);
        // $printer->text($separator);

        // // Payments (Split Payments Support)
        // foreach ($this->completedOrder->payments as $payment) {
        //     $label = "Bayar (" . ($payment->paymentMethod->name ?? 'Cash') . ")";
        //     $amount = "Rp " . number_format($payment->amount, 0, ',', '.');

        //     // Jika label terlalu panjang untuk 33 kolom, otomatis akan terformat rapi oleh formatLine
        //     $printer->text($this->formatLine($label, $amount, $maxColumns) . "\n");
        // }

        if ($this->completedOrder->accurate_invoice_no) {
            $printer->text($this->formatLine("No. SI", $this->completedOrder->accurate_invoice_no, $maxColumns) . "\n");
        }
        $printer->text($separator);

        // Footer (Center)
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->text("\nTerima kasih telah berbelanja!\n");
        $printer->text("Call Center : 0811-5600-6464\n");

        // Spasi kosong untuk dorong kertas keluar (karena TM-U220D sobek manual)
        $printer->text("\n\n\n\n\n");
    }

    public function getEscposBase64()
    {
        if (!$this->completedOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Tidak ada transaksi aktif untuk dicetak.', type: 'error');
            return;
        }

        try {
            $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->initialize();

            $this->generateEscposContent($printer);
            // ==========================================
            // TAMBAHKAN PERINTAH POTONG DI SINI
            // ==========================================
            // 2. Gulung kertas beberapa baris agar teks terakhir tidak ikut terpotong pisau
            $printer->feed(1);

            // 3. Perintahkan printer untuk memotong kertas (Partial Cut)
            $printer->cut();
            // ==========================================
            $data = $connector->getData();
            $base64 = base64_encode($data);

            $printer->close();

            // Cukup dispatch SATU event saja, kirim juga orderNumber jika ada
            $orderNumber = $this->completedOrder->order_number ?? 'terbaru';
            $this->dispatch('print-receipt', base64Data: $base64, orderNumber: $orderNumber);
        } catch (\Exception $e) {
            Log::error('ESCPOS Base64 Generation Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal memproses cetakan: ' . $e->getMessage(), type: 'error');
        }
    }



    private function formatLine($left, $right, $width = 58)
    {
        $leftWidth = strlen($left);
        $rightWidth = strlen($right);
        $spaces = $width - $leftWidth - $rightWidth;
        if ($spaces < 1) {
            $spaces = 1;
        }
        return $left . str_repeat(' ', $spaces) . $right;
    }
    public function render()
    {
        return view('livewire.zoffline.pos.pos');
    }
}
