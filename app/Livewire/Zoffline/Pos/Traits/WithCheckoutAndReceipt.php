<?php

namespace App\Livewire\Zoffline\Pos\Traits;

use App\Mail\SalesReceiptMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

trait WithCheckoutAndReceipt
{
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

        if (!$this->selectedCustomerId) {
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

        if (!$this->isPaymentsValid()) {
            $this->dispatch('toast', title: 'Pembayaran Belum Sesuai', message: 'Pastikan total pembayaran cocok dengan tagihan dan semua metode pembayaran sudah dipilih.', type: 'warning');
            return;
        }

        $this->showCheckoutModal = true;
    }

    public function processPayment()
    {
        $handler = Auth::user();
        if (!$handler || !$handler->branch || !$handler->warehouse) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Akun Anda belum terhubung dengan Cabang (Branch) atau Gudang. Harap hubungi Admin.', type: 'error');
            return;
        }

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

            // Validasi Promo vs Metode Pembayaran
            if (!empty($this->selectedPromos)) {
                $promos = \App\Models\Promo::with('paymentMethods')->whereIn('id', $this->selectedPromos)->get();
                $usedPaymentMethodIds = collect($this->payments)->pluck('payment_method_id')->filter()->unique()->toArray();

                foreach ($promos as $promo) {
                    if ($promo->paymentMethods->count() > 0) {
                        $requiredPmIds = $promo->paymentMethods->pluck('id')->toArray();
                        $hasValidPm = count(array_intersect($usedPaymentMethodIds, $requiredPmIds)) > 0;
                        if (!$hasValidPm) {
                            $pmNames = $promo->paymentMethods->pluck('name')->implode(', ');
                            $this->dispatch('toast', title: 'Promo Tidak Berlaku', message: "Promo {$promo->name} hanya berlaku untuk pembayaran menggunakan: {$pmNames}.", type: 'error');
                            return;
                        }
                    }
                }
            }

            if (!$this->isPaymentsValid()) {
                $this->dispatch('toast', title: 'Error', message: 'Pembayaran belum valid.', type: 'error');
                return;
            }

            $subtotal = $this->subtotal();
            $manualDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0));
            $promoDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['promo_discount'] ?? 0));
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;

            $mdrAmt = $this->mdrAmount();
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
                    'business_unit_id' => Auth::user()->getActiveBusinessUnitId() ?? 1,
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
                $service = app(\App\Services\PromoCalculatorService::class);
                $service->recordPromosToOrder($order, $this->cart, $this->selectedPromos);
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
                    'promo_discount_amount' => (int) ($item['promo_discount'] ?? 0),
                    'applied_promo_id' => $item['applied_promo_id'] ?? null,
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

                // Gunakan nama Cabang & Gudang asli untuk dikirim ke Accurate
                $accurateBranchName = $branchName;
                $accurateWarehouseName = $warehouseName;

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
                            'itemCashDiscount' => (int)($item['discount_amount'] ?? 0) + (int)($item['promo_discount'] ?? 0),
                            'salesmanListNumber' => $detailSalesman,
                        ];

                        $condition = $item['condition'] ?? '';
                        if (in_array($condition, ['Inter', 'Resmi'])) {
                            // Ekstrak nama kota dari cabang (Misal "GSK - Banjarbaru" atau "GSK Martapura" -> "Banjarbaru")
                            $city = trim(str_replace(['GSK -', 'GSK '], '', $accurateWarehouseName));

                            // Inter = Distri, Resmi = Retail
                            $departmentPrefix = ($condition === 'Inter') ? 'Distri' : 'Retail';

                            $itemData['departmentName'] = $departmentPrefix . ' ' . $city;
                        }

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
                        'useTax1' => true,
                        'description' => $this->notes
                    ];

                    $siResult = $accurateService->postSalesInvoice($siData, $dbSource);
                    if (isset($siResult['r']['number'])) {
                        $order->update(['accurate_invoice_no' => $siResult['r']['number']]);
                        \App\Models\OrderAccurateDoc::create([
                            'order_id' => $order->id,
                            'doc_type' => 'SALES_INVOICE',
                            'doc_number' => $siResult['r']['number'],
                            'accurate_id' => $siResult['r']['id'] ?? null,
                            'amount' => $grandTotal,
                            'status' => 'SUCCESS',
                        ]);
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

                        $detailInvoiceItem = [
                            'invoiceNo' => $order->accurate_invoice_no,
                            'paymentAmount' => $rowBaseAmount, // Bayar sisa tagihan invoice = Cash
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
                            \App\Models\OrderAccurateDoc::create([
                                'order_id' => $order->id,
                                'doc_type' => 'SALES_RECEIPT',
                                'doc_number' => $srResult['r']['number'],
                                'accurate_id' => $srResult['r']['id'] ?? null,
                                'amount' => (float) $netReceiptAmount,
                                'status' => 'SUCCESS',
                            ]);
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

        if (!$this->selectedCustomerId) {
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
            $promoDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['promo_discount'] ?? 0));
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;

            $mdrAmt = $this->mdrAmount();
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
                    'business_unit_id' => Auth::user()->getActiveBusinessUnitId() ?? 1,
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
                $service = app(\App\Services\PromoCalculatorService::class);
                $service->recordPromosToOrder($order, $this->cart, $this->selectedPromos);
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
                    'promo_discount_amount' => (int) ($item['promo_discount'] ?? 0),
                    'applied_promo_id' => $item['applied_promo_id'] ?? null,
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
        $this->newTransaction();
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
        
        // Reset wizard steps to step 1
        $this->currentStep = 1;
        $this->paymentWizardStep = 1;
        $this->paymentMode = null;
        $this->activePaymentIndex = 0;
        
        // Reset promo data
        $this->selectedPromos = [];
        $this->promo_discount = 0;
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
