<?php

namespace App\Livewire\Admin\Pos;

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
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PointOfSale extends Component
{
    // ─── Search & Filter ───────────────────────────────────────
    public $search = '';
    public $productType = 'all'; // all, new, second

    // ─── Cart (in-memory) ──────────────────────────────────────
    public $cart = []; // [{variant_id, variant_type, name, storage, color, price, qty, serial_number, sku}]

    // ─── Customer ──────────────────────────────────────────────
    public $isNewCustomer = false;
    public $searchCustomer = '';
    public $selectedCustomerId = null;
    public $customerName = '';
    public $customerPhone = '';
    public $customerEmail = '';

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
                'serial_number' => '',
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
            $this->syncSinglePaymentAmount();
        }
    }

    public function decrementCartItem($index)
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['qty'] > 1) {
            $this->cart[$index]['qty']--;
            $this->syncSinglePaymentAmount();
        }
    }

    public function updateSerialNumber($index, $value)
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['serial_number'] = $value;
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
                'payment_method_id' => $this->payments[0]['payment_method_id'] ?: null,
                'payment_method_rate_id' => $this->payments[0]['payment_method_rate_id'] ?: null,
                'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                'notes' => $this->notes,
            ]);

            // Create Order Items + reduce stock
            foreach ($this->cart as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_variant_type' => $item['variant_type'],
                    'qty' => $item['qty'],
                    'price_at_checkout' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'serial_number' => $item['serial_number'],
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
                        $detailItems[] = [
                            'itemNo' => $item['sku'] ?: 'ITEM-UNKNOWN',
                            'warehouseName' => $warehouseName,
                            'unitPrice' => $item['price'],
                            'itemCashDiscount' => $discountAmount,
                            'quantity' => $item['qty'],
                            'useTax1' => false,
                            'detailName' => $item['name'] . ' ' . $item['color'] . ' ' . $item['storage'],
                            'detailSerialNumber' => [
                                ['serialNumberNo' => $item['serial_number'], 'quantity' => $item['qty']]
                            ]
                        ];
                    }

                    $siData = [
                        'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                        'branchName' => $branchName,
                        'detailItem' => $detailItems,
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

    #[Layout('layouts.app', ['title' => 'Point of Sale'])]
    public function render()
    {
        return view('livewire.admin.pos.point-of-sale');
    }
}
