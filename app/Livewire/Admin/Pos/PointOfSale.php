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

use App\Livewire\Admin\Pos\Traits\WithCart;
use App\Livewire\Admin\Pos\Traits\WithCustomer;
use App\Livewire\Admin\Pos\Traits\WithPayment;

class PointOfSale extends Component
{
    use WithCart, WithCustomer, WithPayment;

    // ─── Modals ────────────────────────────────────────────────
    public $showCheckoutModal = false;
    public $showReceiptModal = false;
    public $completedOrder = null;

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

    #[Computed]
    public function grandTotal()
    {
        return max(0, $this->subtotal - $this->totalDiscount);
    }

    // ─── Cart Actions ──────────────────────────────────────────

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
            $manualDiscountAmount = (int) $this->discount_amount;
            $promoDiscountAmount = $this->totalPromoDiscount;
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;

            $mdrAmt = $this->mdrAmount;
            $grandTotal = max(0, $subtotal - $totalDiscountAmount);

            // Create Order
            $order = Order::create([
                'business_unit_id' => \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId() ?? 1,
                'user_id' => $customerId,
                'order_number' => $orderNumber,
                'total_amount' => $subtotal,
                'shipping_cost' => 0,
                'discount_amount' => $totalDiscountAmount, // Total semua diskon
                'grand_total' => $grandTotal,
                'order_status' => 'COMPLETED',
                'order_channel' => 'POS',
                'handled_by' => Auth::id(),
                'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                'notes' => $this->notes,
            ]);

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
                                $detailSalesman[] = (string) $sales['employee_no'];
                            }
                        }

                        $detailItems[] = [
                            'itemNo' => $item['sku'] ?: 'ITEM-UNKNOWN',
                            'warehouseName' => $warehouseName,
                            'unitPrice' => $item['price'],
                            // 'itemCashDiscount' => $manualDiscountAmount, // Hanya diskon manual yang memotong invoice
                            'quantity' => $item['qty'],
                            'detailName' => $item['name'] . ' ' . $item['color'] . ' ' . $item['storage'],
                            'detailSerialNumber' => $detailSN,
                            'salesmanListNumber' => $detailSalesman
                        ];
                    }

                    $siData = [
                        'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                        'branchName' => $branchName,
                        'detailItem' => $detailItems,
                        'inclusiveTax' => true,
                        'taxable' => true,
                        'cashDiscount' => $manualDiscountAmount,
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
                                'departmentName' => $branchName,
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
                                        'departmentName' => $branchName,
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
                            'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                            'branchName' => $branchName,
                            'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                            'receiptAmount' => (float) $netReceiptAmount, // Net cash ke bank
                            'chequeAmount' => (float) $netReceiptAmount,
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
