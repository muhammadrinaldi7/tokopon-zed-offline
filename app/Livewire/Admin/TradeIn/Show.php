<?php

namespace App\Livewire\Admin\TradeIn;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\SecondProductVariant;
use App\Models\TradeIn;
use App\Models\TradeInUnitOption;
use App\Services\XenditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Show extends Component
{
    public TradeIn $tradeIn;

    // Appraisal Form
    public $appraisedValue = 0;
    public array $selectedVariants = [];
    public $searchVariant = '';

    // Accurate Confirm Payment SNs
    public $showConfirmModal = false;
    public $targetSN = '';
    public $oldPhoneSN = '';

    // Physical Inspection Form
    public $shippingCost = 0;

    // Convert to Second Product
    public $convertModal = false;
    public $sellPrice = 0;
    public $secondCondition = 'Bekas';
    public $existingProductId = null; // Opsional: gabung ke parent "iPhone 13 Pro Max - Second" yang sudah ada

    // Payment Method
    public $payment_method_id;
    public $available_payment_methods = [];
    public function mount(TradeIn $tradeIn)
    {
        $this->tradeIn = $tradeIn->load(['user', 'targetProduct', 'media', 'unitOptions.variant', 'buybackDevice.tier']);
        $this->appraisedValue = $this->tradeIn->appraised_value ?? 0;

        $this->selectedVariants = $this->tradeIn->product_variant_id ? [$this->tradeIn->product_variant_id] : [];
    }

    // submitAppraisal dihapus karena harga sudah fix dan admin langsung assign unit saat inspeksi

    public function toggleVariant($variantId)
    {
        $this->selectedVariants = [$variantId];
    }

    public function updateAppraisedValue()
    {
        if (!in_array($this->tradeIn->status, ['WAITING_FOR_DEVICE', 'INSPECTING'])) return;

        $this->validate([
            'appraisedValue' => 'required|numeric|min:0'
        ]);

        $this->tradeIn->update([
            'appraised_value' => $this->appraisedValue
        ]);

        $this->dispatch('toast', title: 'Berhasil', message: 'Harga disepakati berhasil diperbarui.', type: 'success');
    }

    public function markAsPhysicallyVerified()
    {
        if (!in_array($this->tradeIn->status, ['WAITING_FOR_DEVICE', 'INSPECTING'])) return;

        $this->validate([
            'appraisedValue' => 'required|numeric|min:0'
        ]);

        if (empty($this->selectedVariants)) {
            $this->dispatch('toast', title: 'Gagal', message: 'Silakan pilih 1 unit produk untuk diberikan ke pengguna.', type: 'error');
            return;
        }

        // Update nilai beli HP lama jika diubah oleh admin saat inspeksi
        $this->tradeIn->update([
            'appraised_value' => $this->appraisedValue
        ]);

        DB::transaction(function () {
            $variantId = $this->selectedVariants[0];
            $isNew = $this->tradeIn->target_product_type === \App\Models\Product::class;

            if ($isNew) {
                $variant = \App\Models\ProductVariant::with('product')->find($variantId);
            } else {
                $variant = \App\Models\SecondProductVariant::with('secondProduct')->find($variantId);
            }

            $topupAmount = max(0, $variant->price - (float) $this->tradeIn->appraised_value);

            // Assign unit ke trade in & ubah status ke OFFERED
            $this->tradeIn->update([
                'product_variant_id' => $variant->id,
                'topup_amount' => $topupAmount,
                'status' => 'OFFERED'
            ]);
        });

        $this->tradeIn->refresh();
        $this->dispatch('toast', title: 'Berhasil Diajukan', message: 'Silakan konfirmasi nominal Top-Up ke Klien.', type: 'success');
    }

    public function cancelTradeIn()
    {
        if (!in_array($this->tradeIn->status, ['WAITING_FOR_DEVICE', 'INSPECTING', 'WAITING_PAYMENT'])) return;

        DB::transaction(function () {
            // Restore stock if it was already locked (WAITING_PAYMENT)
            if ($this->tradeIn->status === 'WAITING_PAYMENT' && $this->tradeIn->productVariant) {
                $handler = $this->tradeIn->handledBy ?? Auth::user();
                $warehouseId = $handler->warehouse_id ?? \App\Models\Warehouse::first()?->id;

                if ($warehouseId) {
                    \App\Models\WarehouseStock::updateOrCreate(
                        [
                            'warehouse_id' => $warehouseId,
                            'variant_id' => $this->tradeIn->product_variant_id,
                            'variant_type' => $this->tradeIn->product_variant_type,
                        ],
                        [
                            'stock' => \Illuminate\Support\Facades\DB::raw("stock + 1")
                        ]
                    );
                }
            }

            if ($this->tradeIn->order) {
                $this->tradeIn->order->update(['order_status' => 'CANCELLED']);
            }

            $this->tradeIn->update(['status' => 'CANCELLED']);
        });

        $this->dispatch('toast', title: 'Dibatalkan', message: 'Transaksi Trade-In berhasil dibatalkan secara sepihak.', type: 'info');
    }

    public function promptConfirmPayment()
    {
        if ($this->tradeIn->status !== 'WAITING_PAYMENT') return;

        $this->oldPhoneSN = 'TRD-SN-' . str_pad($this->tradeIn->id, 4, '0', STR_PAD_LEFT);
        $this->targetSN = '';

        $this->available_payment_methods = \App\Models\PaymentMethod::where('is_active', true)->get();
        if ($this->available_payment_methods->count() > 0) {
            $this->payment_method_id = $this->available_payment_methods->first()->id;
        }
        // dd($this->available_payment_methods);

        $this->showConfirmModal = true;
    }

    public function confirmPayment()
    {
        if ($this->tradeIn->status !== 'WAITING_PAYMENT') return;

        $this->validate([
            'oldPhoneSN' => 'required|string',
            'targetSN' => 'required|string',
            'payment_method_id' => 'required|exists:payment_methods,id'
        ]);

        $handler = $this->tradeIn->handledBy ?? Auth::user();
        $branchName = $handler && $handler->branch ? $handler->branch->name : 'Banjarbaru';
        $warehouseName = $handler && $handler->warehouse ? $handler->warehouse->name : 'Head Office';

        try {
            // Update Order and Payment locally (Aman dari rollback)
            $order = $this->tradeIn->order;
            if ($order && $order->order_status !== 'COMPLETED') {
                $order->update(['order_status' => 'COMPLETED']);
                $payment = $order->payments()->where('status', 'PENDING')->first();
                if ($payment) {
                    $payment->update([
                        'status' => 'PAID',
                        'payment_method_id' => $this->payment_method_id
                    ]);
                }
            }

            // Accurate hits (PI, SI, SR)
            $isNew = $this->tradeIn->target_product_type === \App\Models\Product::class;
            $dbSource = $isNew ? 'syihab' : 'second';
            $variant = $this->tradeIn->productVariant;
            $productName = $isNew ? $variant->product->name : $variant->secondProduct->name;

            // 0. Sync Vendor & Customer ke Accurate (agar vendorNo dan customerNo pasti terisi)
            $accurateService = app(\App\Services\AccurateService::class);
            $customerUser = $this->tradeIn->user;

            $accurateService->syncVendor($customerUser, $dbSource);
            $accurateService->syncCustomer($customerUser, $dbSource);

            // Refresh user agar mendapat vendor_no & customer_no terbaru dari database
            $customerUser->refresh();
            $billNumber = 'TRD-' . date('dmY') . str_pad($this->tradeIn->id, 4, '0', STR_PAD_LEFT);
            $vendorNo = str_replace('"', '', $customerUser->accurate_vendor_no) ?? 'V-CASH';

            // 1. Hit Accurate Purchase Invoice (Pembelian HP Lama) JIKA BELUM ADA
            if (!$this->tradeIn->purchase_invoice_number) {
                $purchaseInvoiceData = [
                    'vendorNo' => $vendorNo,
                    'billNumber' => $billNumber,
                    'branchName' => $branchName,
                    'detailItem' => [
                        [
                            'itemNo' => $this->tradeIn->productVariant->sku,
                            'warehouseName' => $warehouseName,
                            'unitPrice' => (float) $this->tradeIn->appraised_value,
                            'quantity' => 1,
                            'useTax1' => false,
                            'detailName' => $this->tradeIn->old_phone_brand . ' ' . $this->tradeIn->old_phone_model,
                            'detailSerialNumber' => [
                                [
                                    'serialNumberNo' => $this->oldPhoneSN,
                                    'quantity' => 1
                                ]
                            ]
                        ]
                    ]
                ];

                $piResult = $accurateService->postPurchaseInvoice($purchaseInvoiceData, $dbSource);
                if (isset($piResult['r']['number'])) {
                    // Simpan state secara iteratif
                    $this->tradeIn->update(['purchase_invoice_number' => $piResult['r']['number']]);
                }
            }

            // 2. Hit Accurate Sales Invoice (Penjualan Unit Baru) JIKA BELUM ADA
            if (!$this->tradeIn->sales_invoice_number) {
                $salesInvoiceData = [
                    'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                    'branchName' => $branchName,
                    'detailItem' => [
                        [
                            'itemNo' => $variant->sku ?? 'HP-BARU-DUMMY',
                            'warehouseName' => $warehouseName,
                            'unitPrice' => (float) $this->tradeIn->topup_amount,
                            'quantity' => 1,
                            'useTax1' => false,
                            'detailName' => $productName . ' - ' . $variant->storage,
                            'detailSerialNumber' => [
                                [
                                    'serialNumberNo' => $this->targetSN,
                                    'quantity' => 1
                                ]
                            ]
                        ]
                    ]
                ];

                $siResult = $accurateService->postSalesInvoice($salesInvoiceData, $dbSource);
                if (isset($siResult['r']['number'])) {
                    $this->tradeIn->update(['sales_invoice_number' => $siResult['r']['number']]);
                }
            }

            // 3. Hit Accurate Sales Receipt (Pelunasan Sisa) JIKA BELUM ADA dan ada tagihan
            if ($this->tradeIn->topup_amount > 0 && !$this->tradeIn->sales_receipt_number) {
                // Pastikan Sales Invoice Number sudah terisi dari step sebelumnya
                if ($this->tradeIn->sales_invoice_number) {
                    $orderPayment = $this->tradeIn->order ? $this->tradeIn->order->payments()->first() : null;
                    $accurateBankNo = $orderPayment && $orderPayment->paymentMethod ? $orderPayment->paymentMethod->accurate_bank_no : 'KAS-CASH';

                    $salesReceiptData = [
                        'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                        'branchName' => $branchName,
                        'bankNo' => $accurateBankNo, // Dinamis dari Database
                        'receiptAmount' => (float) $this->tradeIn->topup_amount,
                        'chequeAmount' => $this->tradeIn->topup_amount,
                        'detailInvoice' => [
                            [
                                'invoiceNo' => $this->tradeIn->sales_invoice_number,
                                'paymentAmount' => (float) $this->tradeIn->topup_amount
                            ]
                        ]
                    ];

                    $srResult = $accurateService->postSalesReceipt($salesReceiptData, $dbSource);
                    if (isset($srResult['r']['number'])) {
                        $this->tradeIn->update(['sales_receipt_number' => $srResult['r']['number']]);
                    }
                }
            }

            // Mark TradeIn as Completed jika semua berhasil
            $this->tradeIn->update(['status' => 'COMPLETED']);

            $this->showConfirmModal = false;
            $this->dispatch('toast', title: 'Pembayaran Dikonfirmasi', message: 'Pembayaran selesai dan data telah diupdate.', type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Accurate Integration Failed during confirmPayment: ' . $e->getMessage());

            // Dispatch a toast error to the UI so the user knows it failed and rolled back
            $this->dispatch('toast', title: 'Gagal Sinkronisasi Accurate', message: $e->getMessage(), type: 'error');
        }
    }

    public function reject()
    {
        $this->tradeIn->update(['status' => 'CANCELLED']);
        $this->dispatch('toast', title: 'Ditolak', message: 'Tukar tambah dibatalkan secara sepihak.', type: 'info');
    }

    public function convertToProduct()
    {
        if ($this->tradeIn->status !== 'COMPLETED') return;

        $this->validate([
            'sellPrice' => 'required|numeric|min:1000',
            'secondCondition' => 'required|string',
        ]);

        DB::transaction(function () {
            // Cek apakah produk parent sudah pernah dibikin untuk merek/tipe ini (yang second)
            $productName = $this->tradeIn->old_phone_brand . ' ' . $this->tradeIn->old_phone_model;

            $product = null;
            if ($this->existingProductId) {
                $product = \App\Models\SecondProduct::find($this->existingProductId);
            } else {
                $product = \App\Models\SecondProduct::firstOrCreate(
                    ['name' => $productName],
                    [
                        'slug' => Str::slug($productName . ' Second ' . rand(100, 999)),
                        'brand_id' => null, // Opsional jika punya relasi tabel brands
                        'category_id' => \App\Models\Category::first()?->id, // Default ke kategori pertama
                        'description' => 'Produk unit seken / bekas pakai.',
                        'is_active' => true,
                        'starting_price' => $this->sellPrice,
                    ]
                );
            }

            // Buat variant fisiknya
            $variant = SecondProductVariant::create([
                'second_product_id' => $product->id,
                'trade_in_id' => $this->tradeIn->id,
                'storage' => $this->tradeIn->old_phone_storage ?? '-',
                'color' => '-',
                'condition_desc' => $this->secondCondition,
                'weight' => 500, // asumsikan
                'price' => $this->sellPrice,
                'stock' => 1,
            ]);

            $warehouseId = Auth::user()->warehouse_id ?? \App\Models\Warehouse::first()?->id;
            if ($warehouseId) {
                \App\Models\WarehouseStock::create([
                    'warehouse_id' => $warehouseId,
                    'variant_id' => $variant->id,
                    'variant_type' => get_class($variant),
                    'stock' => 1,
                ]);
            }

            // Tandai Trade In sudah memiliki produk / ter-convert (opsional, bisa dilacak dari trade_in_id di variants)
        });

        $this->convertModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Unit HP lama masuk ke Katalog Second.', type: 'success');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $isNew = $this->tradeIn->target_product_type === \App\Models\Product::class;

        $warehouseId = Auth::user()->warehouse_id;

        if ($isNew) {
            $availableVariants = \App\Models\ProductVariant::with(['product', 'warehouseStocks' => function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])
                ->where('product_id', $this->tradeIn->target_product_id)
                ->whereHas('warehouseStocks', function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId)->where('stock', '>', 0);
                })
                ->when($this->searchVariant, function ($q) {
                    $q->where('storage', 'like', "%{$this->searchVariant}%")
                        ->orWhere('color', 'like', "%{$this->searchVariant}%");
                })
                ->get();
        } else {
            $availableVariants = SecondProductVariant::with(['secondProduct', 'warehouseStocks' => function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])
                ->where('second_product_id', $this->tradeIn->target_product_id)
                ->whereHas('warehouseStocks', function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId)->where('stock', '>', 0);
                })
                ->when($this->searchVariant, function ($q) {
                    $q->where('storage', 'like', "%{$this->searchVariant}%")
                        ->orWhere('color', 'like', "%{$this->searchVariant}%")
                        ->orWhere('condition_desc', 'like', "%{$this->searchVariant}%");
                })
                ->get();
        }

        return view('livewire.admin.trade-in.show', [
            'availableVariants' => $availableVariants
        ]);
    }
}
