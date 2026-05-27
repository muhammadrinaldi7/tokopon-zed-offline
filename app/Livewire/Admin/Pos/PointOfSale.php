<?php

namespace App\Livewire\Admin\Pos;

use App\Mail\SalesReceiptMail;
use App\Models\Employe;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodRate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SecondProduct;
use App\Models\SecondProductVariant;
use App\Models\User;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PointOfSale extends Component
{
    // ─── Search & Filter ───────────────────────────────────────
    public $search = '';
    public $productType = 'new'; // all, new, second

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

    // Method untuk membuka modal dan memuat data transaksi POS terbaru
    public function openHistory()
    {
        // Mengambil transaksi khusus channel POS handled oleh user aktif / bebas tergantung kebutuhan bisnis
        $this->historyOrders = Order::with(['items', 'user', 'paymentMethod'])
            ->where('order_channel', 'POS')
            ->latest()
            ->take(20) // Ambil 20 transaksi terakhir
            ->get();

        $this->showHistoryModal = true;
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
        $this->payments = [
            [
                'payment_method_id' => '',
                'payment_method_rate_id' => '',
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

    public function addPaymentRow()
    {
        $remaining = max(0, ($this->subtotal - $this->discount_amount) - $this->paymentsTotalBase);
        $this->payments[] = [
            'payment_method_id' => '',
            'payment_method_rate_id' => '',
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
        $target = max(0, $this->subtotal - $this->discount_amount);
        $this->payments[$index]['amount'] = max(0, $target - $totalOther);
    }

    public function syncSinglePaymentAmount()
    {
        if (count($this->payments) === 1) {
            $this->payments[0]['amount'] = max(0, $this->subtotal - $this->discount_amount);
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

        $target = max(0, $this->subtotal - $this->discount_amount);
        return (int) $this->paymentsTotalBase === (int) $target;
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::where('is_active', true)->get();
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

        return Employe::where(function ($q) {
            $q->where('name', 'like', '%' . $this->searchSales . '%');
        })->take(5)->get();
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->search) < 2) return collect();

        $newProducts = collect();
        $secondProducts = collect();

        if ($this->productType !== 'second') {
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

        if ($this->productType !== 'new') {
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
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
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
        return max(0, $this->subtotal() - $this->discount_amount);
    }

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
                'label' => $v->color . ' - ' . $v->storage,
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
                'label' => $v->color . ' - ' . $v->storage,
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
                if (!isset($this->cart[$existingIndex]['serial_numbers'])) {
                    $this->cart[$existingIndex]['serial_numbers'] = [$this->cart[$existingIndex]['serial_number'] ?? ''];
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
                'storage' => $variant->storage ?? '-',
                'color' => $variant->color ?? '-',
                'price' => (int) $variant->price,
                'qty' => 1,
                'serial_number' => '', // legacy
                'serial_numbers' => [''], // array of SNs based on qty
                'sku' => $variant->sku ?? '',
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
            $this->cart[$index]['qty']++;
            if (!isset($this->cart[$index]['serial_numbers'])) {
                $this->cart[$index]['serial_numbers'] = [$this->cart[$index]['serial_number'] ?? ''];
            }
            $this->cart[$index]['serial_numbers'][] = '';
            $this->syncSinglePaymentAmount();
        }
    }

    public function decrementCartItem($index)
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['qty'] > 1) {
            $this->cart[$index]['qty']--;
            if (isset($this->cart[$index]['serial_numbers'])) {
                array_pop($this->cart[$index]['serial_numbers']);
            }
            $this->syncSinglePaymentAmount();
        }
    }

    public function updateSerialNumber($index, $snIndex, $value)
    {
        if (isset($this->cart[$index])) {
            if (!isset($this->cart[$index]['serial_numbers'])) {
                $this->cart[$index]['serial_numbers'] = [$this->cart[$index]['serial_number'] ?? ''];
            }
            $this->cart[$index]['serial_numbers'][$snIndex] = $value;
            // Also update legacy for backward compatibility
            if ($snIndex === 0) {
                $this->cart[$index]['serial_number'] = $value;
            }
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
            if (empty($item['serial_number'])) {
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

            // If new customer, create user first
            if ($this->isNewCustomer && !$customerId) {
                $this->validate([
                    'customerName' => 'required|string|max:255',
                    'customerPhone' => 'required|string|max:20',
                ]);

                $newUser = User::create([
                    'name' => $this->customerName,
                    'email' => $this->customerEmail ?: ($this->customerPhone . '@pos.tokopun.com'),
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

            if (!$this->isPaymentsValid) {
                $this->dispatch('toast', title: 'Error', message: 'Pembayaran belum valid.', type: 'error');
                return;
            }

            // Generate order number
            $orderNumber = 'POS-' . now()->format('Ymd') . '-' . str_pad(
                Order::whereDate('created_at', today())->where('order_channel', 'POS')->count() + 1,
                3,
                '0',
                STR_PAD_LEFT
            );

            $subtotal = $this->subtotal();
            $discountAmount = (int) $this->discount_amount;
            $mdrAmt = $this->mdrAmount;
            $grandTotal = max(0, $subtotal - $discountAmount);

            // Create Order
            $order = Order::create([
                'user_id' => $customerId,
                'order_number' => $orderNumber,
                'total_amount' => $subtotal,
                'shipping_cost' => 0,
                'discount_amount' => $discountAmount,
                'mdr_percentage' => ($subtotal - $discountAmount) > 0 ? round(($mdrAmt / ($subtotal - $discountAmount)) * 100, 2) : 0,
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
            ]);

            // Create Order Items + reduce stock
            foreach ($this->cart as $item) {
                $sns = $item['serial_numbers'] ?? [$item['serial_number'] ?? ''];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_variant_type' => $item['variant_type'],
                    'qty' => $item['qty'],
                    'price_at_checkout' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'serial_number' => implode(', ', $sns),
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
                ]);
            }

            // ─── Accurate Integration (Iterative State Saving) ─────

            try {
                $accurateService = app(AccurateService::class);
                $customerUser = User::find($customerId);
                $handler = Auth::user();
                $branchName = $handler->branch->name ?? 'Banjarbaru';
                $warehouseName = $handler->warehouse->name ?? 'Head Office';

                // Determine dbSource from items (if any is second → 'second', otherwise 'syihab')
                $hasSecond = collect($this->cart)->contains('is_second', true);
                $dbSource = $hasSecond ? 'second' : 'syihab';

                // Sync Customer to Accurate
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();

                // Sales Invoice (jika belum ada)
                if (!$order->accurate_invoice_no) {
                    $detailItems = [];
                    foreach ($this->cart as $item) {
                        $detailSN = array_map(function ($sn) {
                            return ['serialNumberNo' => $sn ?: '-', 'quantity' => 1];
                        }, $item['serial_numbers'] ?? [$item['serial_number'] ?? '-']);
                        $detailSalesman = [];
                        foreach ($this->selectedSales as $sales) {
                            if (!empty($sales['employee_no'])) {
                                // Langsung push nilai string-nya ke dalam array
                                $detailSalesman[] = (string) $sales['employee_no'];
                            }
                        }

                        $detailItems[] = [
                            'itemNo' => $item['sku'] ?: 'ITEM-UNKNOWN',
                            'warehouseName' => $warehouseName,
                            'unitPrice' => $item['price'],
                            'itemCashDiscount' => $discountAmount,
                            'quantity' => $item['qty'],
                            'detailName' => $item['name'] . ' ' . $item['color'] . ' ' . $item['storage'],
                            'detailSerialNumber' => $detailSN,
                            'salesmanListNumber' => $detailSalesman,
                        ];
                    }


                    $siData = [
                        'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                        'branchName' => $branchName,
                        'detailItem' => $detailItems,
                        'inclusiveTax' => true,
                        'taxable' => true
                    ];

                    $siResult = $accurateService->postSalesInvoice($siData, $dbSource);
                    if (isset($siResult['r']['number'])) {
                        $order->update(['accurate_invoice_no' => $siResult['r']['number']]);
                    }
                }

                // Sales Receipts (jika belum ada dan ada invoice)
                if (!$order->accurate_receipt_no && $order->accurate_invoice_no) {
                    $srNumbers = [];
                    foreach ($this->payments as $payment) {
                        $pm = \App\Models\PaymentMethod::findOrFail($payment['payment_method_id']);
                        $rate = $payment['payment_method_rate_id'] ? \App\Models\PaymentMethodRate::find($payment['payment_method_rate_id']) : null;

                        $pct = $this->getMdrPercentage($payment);
                        $rowMdr = $pct > 0 ? round((float)$payment['amount'] * $pct / 100, 0) : 0;
                        $rowBaseAmount = (float)$payment['amount'];

                        $netReceiptAmount = $rowBaseAmount - $rowMdr;
                        $mdrAccountNo = $rate ? $rate->accurate_account_no : null;

                        $detailInvoiceItem = [
                            'invoiceNo' => $order->accurate_invoice_no,
                            'paymentAmount' => $rowBaseAmount,
                        ];

                        if ($rowMdr > 0 && $mdrAccountNo) {
                            $detailInvoiceItem['detailDiscount'] = [
                                [
                                    'accountNo' => $mdrAccountNo,
                                    'amount' => (float) $rowMdr,
                                    'departmentName' => $branchName,
                                    'discountNotes' => 'MDR'
                                ]
                            ];
                        } else {
                            $netReceiptAmount = $rowBaseAmount;
                        }

                        $srData = [
                            'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                            'branchName' => $branchName,
                            'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                            'receiptAmount' => (float) $netReceiptAmount,
                            'chequeAmount' => (float) $netReceiptAmount,
                            'detailInvoice' => [
                                $detailInvoiceItem
                            ]
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
                // Don't block the POS sale; Accurate can be retried
            }

            // Success! Show receipt
            $this->completedOrder = $order->load(['items', 'user', 'payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy']);
            $this->showCheckoutModal = false;
            $this->showReceiptModal = true;

            // Reset cart
            $this->cart = [];
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
                    'amount' => 0,
                ]
            ];

            $this->dispatch('toast', title: 'Transaksi Berhasil', message: 'Order ' . $orderNumber . ' berhasil diproses.', type: 'success');
        } catch (\Exception $e) {
            Log::error('POS Payment Error: ' . $e->getMessage());
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
                        'value_text' => 'Rp ' . number_format($order->grand_total, 0, ',', '.')
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

        $printerName = env('ESCPOS_PRINTER_NAME', 'POS-80');

        try {
            if (filter_var($printerName, FILTER_VALIDATE_IP)) {
                $connector = new \Mike42\Escpos\PrintConnectors\NetworkPrintConnector($printerName, 9100);
            } else {
                $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($printerName);
            }

            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->initialize();

            $this->generateEscposContent($printer);

            $printer->close();

            $this->dispatch('toast', title: 'Sukses', message: 'Perintah cetak thermal terkirim ke ' . $printerName, type: 'success');
        } catch (\Exception $e) {
            Log::error('ESCPOS Print Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Cetak Gagal', message: 'Tidak dapat mencetak ke ' . $printerName . ': ' . $e->getMessage(), type: 'error');
        }
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

            $data = $connector->getData();
            $base64 = base64_encode($data);

            $printer->close();

            $this->dispatch('print-rawbt', base64: $base64, orderNumber: $this->completedOrder->order_number);
        } catch (\Exception $e) {
            Log::error('ESCPOS Base64 Generation Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal memproses cetakan RawBT: ' . $e->getMessage(), type: 'error');
        }
    }

    private function generateEscposContent($printer)
    {
        // Store Title (Center, Large)
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(\Mike42\Escpos\Printer::MODE_DOUBLE_WIDTH | \Mike42\Escpos\Printer::MODE_DOUBLE_HEIGHT);
        $printer->text("TOKOPON\n");

        $printer->selectPrintMode();
        $storeName = $this->completedOrder->shipping_address_snapshot['store'] ?? 'Toko';
        $printer->text($storeName . "\n");
        $printer->text($this->completedOrder->created_at->format('d/m/Y H:i') . "\n");
        $printer->text("--------------------------------\n");

        // Info (Left)
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
        $printer->text($this->formatLine("No. Transaksi", $this->completedOrder->order_number, 58) . "\n");
        $printer->text($this->formatLine("Kasir", $this->completedOrder->handledBy->name ?? '-', 58) . "\n");
        $printer->text($this->formatLine("Customer", $this->completedOrder->user->name ?? '-', 58) . "\n");
        $printer->text("--------------------------------\n");

        // Items List
        foreach ($this->completedOrder->items as $item) {
            $v = $item->variant;
            $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
            if ($v) {
                $itemName .= " (" . $v->color . "/" . $v->storage . ")";
            }

            $printer->text($itemName . "\n");

            $qtyAndPrice = $item->qty . "x Rp " . number_format($item->price_at_checkout, 0, ',', '.');
            $subtotal = "Rp " . number_format($item->subtotal, 0, ',', '.');
            $printer->text($this->formatLine("  " . $qtyAndPrice, $subtotal, 58) . "\n");

            if ($item->serial_number) {
                $printer->text("  SN: " . $item->serial_number . "\n");
            }
        }
        $printer->text("--------------------------------\n");

        // Subtotal & Discount
        $printer->text($this->formatLine("Subtotal", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), 58) . "\n");
        // if ($this->completedOrder->discount_amount > 0) {
        //     $printer->text($this->formatLine("Diskon", "-Rp " . number_format($this->completedOrder->discount_amount, 0, ',', '.'), 58) . "\n");
        // }
        $printer->text("--------------------------------\n");

        // Grand Total (Bold)
        $printer->setEmphasis(true);
        $printer->text($this->formatLine("TOTAL", "Rp " . number_format($this->completedOrder->grand_total, 0, ',', '.'), 58) . "\n");
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");

        // Payments (Split Payments Support)
        foreach ($this->completedOrder->payments as $payment) {
            $label = "Bayar (" . ($payment->paymentMethod->name ?? 'Cash') . ($payment->paymentMethodRate ? ' - ' . $payment->paymentMethodRate->name : '') . ")";
            $amount = "Rp " . number_format($payment->amount, 0, ',', '.');
            $printer->text($this->formatLine($label, $amount, 58) . "\n");
        }

        if ($this->completedOrder->accurate_invoice_no) {
            $printer->text($this->formatLine("Accurate Invoice", $this->completedOrder->accurate_invoice_no, 58) . "\n");
        }
        $printer->text("--------------------------------\n");

        // Footer (Center)
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->text("\nTerima kasih telah berbelanja!\n");
        $printer->text("www.tokopon.id\n\n\n\n");

        $printer->cut();
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

    #[Layout('layouts.app', ['title' => 'Point of Sale'])]
    public function render()
    {
        return view('livewire.admin.pos.point-of-sale');
    }
}
