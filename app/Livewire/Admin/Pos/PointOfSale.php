<?php

namespace App\Livewire\Admin\Pos;

use App\Mail\SalesReceiptMail;
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
    public $payment_method_id = null;
    public $payment_method_rate_id = null;
    public $discount_amount = 0;
    public $notes = '';

    public function updatedPaymentMethodId($value)
    {
        $this->payment_method_rate_id = null;
    }

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
        $this->completedOrder = Order::with(['items', 'user', 'paymentMethod', 'handledBy'])->find($orderId);
        if ($this->completedOrder) {
            $this->showHistoryModal = false; // tutup modal history
            $this->showReceiptModal = true;  // buka modal struk bawaan kamu
        }
    }

    // ─── Computed Properties ───────────────────────────────────

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::where('is_active', true)->get();
    }

    #[Computed]
    public function selectedPaymentMethod()
    {
        if (!$this->payment_method_id) return null;
        return PaymentMethod::find($this->payment_method_id);
    }

    #[Computed]
    public function paymentMethodRates()
    {
        if (!$this->payment_method_id) return collect();
        return PaymentMethodRate::where('payment_method_id', $this->payment_method_id)
            ->where('is_active', true)
            ->get();
    }

    #[Computed]
    public function selectedPaymentMethodRate()
    {
        if (!$this->payment_method_rate_id) return null;
        return PaymentMethodRate::find($this->payment_method_rate_id);
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
    public function mdrPercentage()
    {
        if ($this->payment_method_rate_id) {
            $rate = $this->selectedPaymentMethodRate;
            return $rate ? (float) $rate->mdr_percentage : 0;
        }

        $pm = $this->selectedPaymentMethod;
        return $pm ? (float) $pm->mdr_percentage : 0;
    }

    #[Computed]
    public function mdrAmount()
    {
        if ($this->mdrPercentage <= 0) return 0;
        return round(($this->subtotal - $this->discount_amount) * $this->mdrPercentage / 100, 0);
    }

    #[Computed]
    public function grandTotal()
    {
        return max(0, $this->subtotal - $this->discount_amount + $this->mdrAmount);
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
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // re-index
    }

    public function incrementCartItem($index)
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['qty']++;
        }
    }

    public function decrementCartItem($index)
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['qty'] > 1) {
            $this->cart[$index]['qty']--;
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

        if (!$this->payment_method_id) {
            $this->dispatch('toast', title: 'Metode Bayar', message: 'Pilih metode pembayaran.', type: 'warning');
            return;
        }

        if ($this->paymentMethodRates->count() > 0 && !$this->payment_method_rate_id) {
            $this->dispatch('toast', title: 'Tarif MDR Belum Dipilih', message: 'Silakan pilih tipe kartu / tenor cicilan terlebih dahulu.', type: 'warning');
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

            $paymentMethod = PaymentMethod::findOrFail($this->payment_method_id);

            if ($this->paymentMethodRates->count() > 0 && !$this->payment_method_rate_id) {
                $this->dispatch('toast', title: 'Tarif MDR Belum Dipilih', message: 'Silakan pilih tipe kartu / tenor cicilan terlebih dahulu.', type: 'warning');
                return;
            }

            // Generate order number
            $orderNumber = 'POS-' . now()->format('Ymd') . '-' . str_pad(
                Order::whereDate('created_at', today())->where('order_channel', 'POS')->count() + 1,
                3,
                '0',
                STR_PAD_LEFT
            );

            $subtotal = $this->subtotal;
            $discountAmount = (int) $this->discount_amount;
            $mdrPct = $this->mdrPercentage;
            $mdrAmt = round(($subtotal - $discountAmount) * $mdrPct / 100, 0);
            $grandTotal = max(0, $subtotal - $discountAmount + $mdrAmt);

            // Create Order
            $order = Order::create([
                'user_id' => $customerId,
                'order_number' => $orderNumber,
                'total_amount' => $subtotal,
                'shipping_cost' => 0,
                'discount_amount' => $discountAmount,
                'mdr_percentage' => $mdrPct,
                'mdr_amount' => $mdrAmt,
                'grand_total' => $grandTotal,
                'order_status' => 'COMPLETED',
                'order_channel' => 'POS',
                'handled_by' => Auth::id(),
                'payment_method_id' => $this->payment_method_id,
                'payment_method_rate_id' => $this->payment_method_rate_id,
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
                \App\Models\WarehouseStock::updateOrCreate(
                    [
                        'warehouse_id' => Auth::user()->warehouse_id,
                        'variant_id' => $item['variant_id'],
                        'variant_type' => $item['variant_type'],
                    ],
                    [
                        'stock' => \Illuminate\Support\Facades\DB::raw("GREATEST(0, stock - " . (int)$item['qty'] . ")")
                    ]
                );
            }

            // Create OrderPayment
            OrderPayment::create([
                'order_id' => $order->id,
                'xendit_external_id' => 'ORD-BUY-' . date('YmdHis') . rand(100, 999),
                'amount' => $grandTotal,
                'status' => 'PAID',
                'payment_method_id' => $this->payment_method_id,
                'payment_method_rate_id' => $this->payment_method_rate_id,
            ]);

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

                // Sales Receipt (jika belum ada dan ada invoice)
                if (!$order->accurate_receipt_no && $order->accurate_invoice_no) {
                    $srData = [
                        'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                        'branchName' => $branchName,
                        'bankNo' => $paymentMethod->accurate_bank_no ?? 'KAS-CASH',
                        'receiptAmount' => (float) $grandTotal,
                        'chequeAmount' => (float) $grandTotal,
                        'detailInvoice' => [
                            [
                                'invoiceNo' => $order->accurate_invoice_no,
                                'paymentAmount' => (float) $grandTotal,
                            ]
                        ]
                    ];

                    $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                    if (isset($srResult['r']['number'])) {
                        $order->update(['accurate_receipt_no' => $srResult['r']['number']]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('POS Accurate Integration Error: ' . $e->getMessage());
                // Don't block the POS sale; Accurate can be retried
            }

            // Success! Show receipt
            $this->completedOrder = $order->load(['items', 'user', 'paymentMethod', 'handledBy']);
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
            $this->payment_method_id = null;
            $this->payment_method_rate_id = null;

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
        $this->payment_method_id = null;
        $this->payment_method_rate_id = null;
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
            // Mengirim email menggunakan Mailable yang sudah dibuat
            Mail::mailer('pos_sales')
                ->to($email)
                ->send(new SalesReceiptMail($order));

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

    // ─── Kirim Struk via WA (Qontak) ─────────────────────────
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

        // 2. Tarik variabel dari env
        $fullUrl = env('QONTAK_API_URL');
        $method = 'POST';

        // Ganti skema parse_url ala JavaScript Postman
        $parsedUrl = parse_url($fullUrl);

        // $baseUrl akan berisi "https://api.mekari.com"
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        // $endpoint otomatis akan mengambil path murni "/qontak/chat/v1/broadcasts/whatsapp/direct"
        $endpoint = $parsedUrl['path'];

        $clientId = env('QONTAK_CLIENT_ID');
        $clientSecret = env('QONTAK_CLIENT_SECRET');

        // ─── 2. PROSES GENERATE HMAC SIGNATURE (TETAP AMAN & PRESISI) ────
        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";

        $stringToSign = "date: {$dateString}\n{$requestLine}";

        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);

        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // ─── 3. STRUKTUR PAYLOAD BODY JSON (100% SAMA DENGAN POSTMAN) ────
        $payload = [
            'to_name' => $order->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' => env('QONTAK_CHANNEL_INTEGRATION_ID'),
            'message_template_id' => env('QONTAK_TEMPLATE_ID'),
            'language' => [
                'code' => 'id'
            ],
            'parameters' => [
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

        // ─── 4. EXECUTE API CALL KE FULL URL ─────────────────────────────
        try {
            $response = Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($fullUrl, $payload);

            if ($response->successful()) {
                // Update ke database menggunakan instance fresh
                $order->update(['is_wa_sent' => true]);

                // REFRESH STATE LIVEWIRE UTAMA
                $this->completedOrder->refresh();

                $this->dispatch('toast', title: 'Berhasil', message: 'Struk WA berhasil dikirim via Mekari Qontak!', type: 'success');
            } else {
                Log::error('=== DEBUG MEKARI QONTAK ===');
                Log::error('Status Code: ' . $response->status());
                Log::error('Response Body: ' . $response->body());
                Log::error('Parsed Endpoint for HMAC: ' . $endpoint);
                Log::error('===========================');

                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari: Code ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            Log::error('Qontak HMAC Integration Crash: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.app', ['title' => 'Point of Sale'])]
    public function render()
    {
        return view('livewire.admin.pos.point-of-sale');
    }
}
