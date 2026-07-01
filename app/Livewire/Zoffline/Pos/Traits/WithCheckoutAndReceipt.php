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

    public function saveDraft()
    {
        $handler = Auth::user();
        if (!$handler || !$handler->branch || !$handler->warehouse) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Akun Anda belum terhubung dengan Cabang (Branch) atau Gudang. Harap hubungi Admin.', type: 'error');
            return;
        }

        // Jalankan validasi Customer (sama seperti Checkout)
        if (!$this->selectedCustomerId) {
            if (strlen($this->searchCustomer) >= 2 && !empty($this->customerPhone)) {
                $this->isNewCustomer = true;
                $this->customerName = $this->searchCustomer;
            }

            if (!$this->isNewCustomer) {
                $this->dispatch('toast', title: 'Customer Belum Lengkap', message: 'Pilih customer dari daftar, atau lengkapi Nama & Nomor HP untuk membuat pelanggan baru sebelum menyimpan draft.', type: 'warning');
                return;
            }
        }

        try {
            $customerId = $this->resolveCustomerId();
            if (!$customerId) {
                $this->dispatch('toast', title: 'Error', message: 'Customer belum dipilih.', type: 'error');
                return;
            }

            if (!empty($this->selectedPromos)) {
                $handler = Auth::user();
                $service = app(\App\Services\PromoCalculatorService::class);
                $promoValid = $service->validatePromosBeforeCheckout($this->selectedPromos, $handler->branch_id ?? 0, $handler->getActiveBusinessUnitId());
                if ($promoValid !== true) {
                    $this->dispatch('toast', title: 'Promo Gagal', message: $promoValid, type: 'error');
                    return;
                }
            }

            $subtotal = $this->subtotal();
            $manualDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1));
            $promoDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['promo_discount'] ?? 0));
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;
            $grandTotal = max(0, $subtotal - $totalDiscountAmount);

            \Illuminate\Support\Facades\DB::beginTransaction();

            $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();

            // Jika sebelumnya meload draft, kita update draft yang sudah ada
            $order = null;
            if ($this->loadedDraftId) {
                $order = Order::find($this->loadedDraftId);
                if ($order) {
                    $this->restoreStockFromOldItems($order);

                    $order->update([
                        'user_id' => $customerId,
                        'order_date' => $dateToUse->format('Y-m-d'),
                        'total_amount' => $subtotal,
                        'shipping_cost' => 0,
                        'discount_amount' => $totalDiscountAmount,
                        'mdr_percentage' => 0,
                        'mdr_amount' => 0,
                        'grand_total' => $grandTotal,
                        'order_status' => 'DRAFT', // Tetap DRAFT
                        'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                        'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                        'notes' => $this->notes,
                        'branch_id' => Auth::user()->branch_id,
                    ]);
                }
            }

            if (!$order) {
                // Buat Order Number baru jika belum ada
                $businessUnit = \Illuminate\Support\Facades\Auth::user()->businessUnit;
                $draftPrefix = $businessUnit->draft_prefix ?? 'POS-DRF-';

                $orderNumber = $draftPrefix . $dateToUse->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
                    Order::whereDate('order_date', $dateToUse->format('Y-m-d'))
                        ->where('order_channel', 'POS')
                        ->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // Create Order DRAFT baru
                $order = Order::create([
                    'business_unit_id' => Auth::user()->getActiveBusinessUnitId() ?? 1,
                    'user_id' => $customerId,
                    'order_number' => $orderNumber,
                    'order_date' => $dateToUse->format('Y-m-d'),
                    'total_amount' => $subtotal,
                    'shipping_cost' => 0,
                    'discount_amount' => $totalDiscountAmount,
                    'mdr_percentage' => 0,
                    'mdr_amount' => 0,
                    'grand_total' => $grandTotal,
                    'order_status' => 'DRAFT',
                    'order_channel' => 'POS',
                    'handled_by' => Auth::id(),
                    'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                    'notes' => $this->notes,
                    'branch_id' => Auth::user()->branch_id,
                ]);
            }

            // Save promos to pivot table
            if (!empty($this->selectedPromos)) {
                $service = app(\App\Services\PromoCalculatorService::class);
                $service->recordPromosToOrder($order, $this->cart, $this->selectedPromos);
            }

            $this->createOrderItemsFromCart($order);

            \Illuminate\Support\Facades\DB::commit();

            $this->resetCheckout();
            $this->dispatch('toast', title: 'Draft Disimpan', message: 'Transaksi berhasil disimpan sebagai Draft dan stok telah dikunci.', type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('POS Save Draft Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal menyimpan draft: ' . $e->getMessage(), type: 'error');
        }
    }

    public function processPayment()
    {
        $handler = Auth::user();
        if (!$handler || !$handler->branch || !$handler->warehouse) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Akun Anda belum terhubung dengan Cabang (Branch) atau Gudang. Harap hubungi Admin.', type: 'error');
            return;
        }

        try {
            $customerId = $this->resolveCustomerId();
            if (!$customerId) return;

            // Validasi Promo vs Metode Pembayaran
            if (!empty($this->selectedPromos)) {
                $service = app(\App\Services\PromoCalculatorService::class);
                $promoValid = $service->validatePromosBeforeCheckout($this->selectedPromos, $handler->branch_id ?? 0, $handler->getActiveBusinessUnitId());
                if ($promoValid !== true) {
                    $this->dispatch('toast', title: 'Promo Gagal', message: $promoValid, type: 'error');
                    return;
                }

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
            $manualDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1));
            $promoDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['promo_discount'] ?? 0));
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;

            $mdrAmt = $this->mdrAmount();
            $grandTotal = max(0, $subtotal - $totalDiscountAmount);

            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($this->isSoFulfillment) {
                return $this->processSoFulfillment($grandTotal);
            }

            if ($this->isPiutangSettlement) {
                $order = Order::find($this->loadedDraftId);

                if (!$order) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    $this->dispatch('toast', title: 'Error', message: 'Faktur Piutang tidak ditemukan.', type: 'error');
                    return;
                }

                $orderNumber = $order->order_number;

                $order->update([
                    'order_status' => 'COMPLETED',
                    'notes' => $this->notes, // User might add notes during settlement
                ]);

                // Create OrderPayments (for each split payment row)
                foreach ($this->payments as $payment) {
                    $rowTotal = (float)$payment['amount'];

                    OrderPayment::create([
                        'order_id' => $order->id,
                        'xendit_external_id' => 'ORD-PTG-' . date('YmdHis') . rand(1000, 9999),
                        'amount' => $rowTotal,
                        'status' => 'PAID',
                        'payment_method_id' => $payment['payment_method_id'],
                        'payment_method_rate_id' => $payment['payment_method_rate_id'] ?: null,
                        'no_kontrak' => $payment['no_kontrak'] ?? null,
                    ]);
                }

                \Illuminate\Support\Facades\DB::commit();

                // Only do Accurate Sales Receipt, NO Sales Invoice needed because it's already there
                try {
                    $accurateService = app(AccurateService::class);
                    $dbSource = $order->businessUnit->code ?? 'syihab';

                    $handler = Auth::user();
                    $branchName = $handler->branch->name ?? 'Banjarbaru';

                    if (!$order->accurate_receipt_no && $order->accurate_invoice_no) {
                        $srNumbers = [];
                        foreach ($this->payments as $index => $payment) {
                            $pm = \App\Models\PaymentMethod::findOrFail($payment['payment_method_id']);

                            // SKIP jika ini adalah payment finance (punya accurate_customer_no)
                            if (!empty($pm->accurate_customer_no)) {
                                continue;
                            }

                            $rate = $payment['payment_method_rate_id'] ? \App\Models\PaymentMethodRate::find($payment['payment_method_rate_id']) : null;

                            $pct = $this->getMdrPercentage($payment);
                            $rowMdr = $pct > 0 ? round((float)$payment['amount'] * $pct / 100, 0) : 0;
                            $rowBaseAmount = (float)$payment['amount'];
                            $netReceiptAmount = $rowBaseAmount - $rowMdr;

                            $detailDiscounts = [];
                            if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                                $detailDiscounts[] = [
                                    'accountNo' => $rate->accurate_account_no,
                                    'amount' => (float) $rowMdr,
                                    'departmentName' => $branchName,
                                    'discountNotes' => 'MDR ' . ($rate->name ?? ' ')
                                ];
                            }

                            $detailInvoiceItem = [
                                'invoiceNo' => $order->accurate_invoice_no,
                                'paymentAmount' => $rowBaseAmount,
                            ];

                            if (!empty($detailDiscounts)) {
                                $detailInvoiceItem['detailDiscount'] = $detailDiscounts;
                            }

                            $srData = [
                                'customerNo' => $order->user->getAccurateCustomerNo($dbSource),
                                'branchName' => $branchName,
                                'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                                'receiptAmount' => (float) $netReceiptAmount,
                                'chequeAmount' => (float) $netReceiptAmount,
                                'transDate'    => now()->format('d/m/Y'),
                                'detailInvoice' => [
                                    $detailInvoiceItem
                                ],
                                'description' => 'Pelunasan Piutang POS'
                            ];

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
                    \Illuminate\Support\Facades\Log::error('POS Accurate Integration Error (Pelunasan Piutang): ' . $e->getMessage());
                }

                $this->completedOrder = $order->load(['items', 'user', 'payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy']);
                $this->showCheckoutModal = false;
                $this->showReceiptModal = true;

                $this->resetCheckout();
                $this->dispatch('toast', title: 'Transaksi Berhasil', message: 'Pelunasan Piutang ' . $orderNumber . ' berhasil diproses.', type: 'success');
                return;
            }

            $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();

            $order = null;
            if ($this->loadedDraftId) {
                $order = Order::find($this->loadedDraftId);
                if ($order) {
                    $this->restoreStockFromOldItems($order);
                    $order->payments()->delete(); // in case there are payments attached to draft

                    $order->update([
                        'user_id' => $customerId,
                        'order_date' => $dateToUse->format('Y-m-d'),
                        'total_amount' => $subtotal,
                        'shipping_cost' => 0,
                        'discount_amount' => $totalDiscountAmount,
                        'grand_total' => $grandTotal,
                        'order_status' => 'COMPLETED',
                        'order_channel' => 'POS',
                        'handled_by' => Auth::id(),
                        'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                        'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                        'notes' => $this->notes,
                    ]);
                    $orderNumber = $order->order_number;
                }
            }

            if (!$order) {
                // 1. Tentukan tanggal yang digunakan (dari input form atau hari ini)
                $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();

                // 2. Generate order number berdasarkan order_date
                $businessUnit = \Illuminate\Support\Facades\Auth::user()->businessUnit;
                $completedPrefix = $businessUnit->order_prefix ?? 'POS-';

                $orderNumber = $completedPrefix . $dateToUse->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
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
                    'grand_total' => $grandTotal,
                    'order_status' => 'COMPLETED',
                    'order_channel' => 'POS',
                    'handled_by' => Auth::id(),
                    'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                    'notes' => $this->notes,
                    'branch_id' => Auth::user()->branch_id,
                ]);
            }

            // Save promos to pivot table
            if (!empty($this->selectedPromos)) {
                $service = app(\App\Services\PromoCalculatorService::class);
                $service->recordPromosToOrder($order, $this->cart, $this->selectedPromos);
            }


            $this->createOrderItemsFromCart($order);

            // Create OrderPayments (for each split payment row)
            foreach ($this->payments as $payment) {
                $rowTotal = (float)$payment['amount'];

                $pm = \App\Models\PaymentMethod::find($payment['payment_method_id']);
                $isFinance = $pm && !empty($pm->accurate_customer_no);

                OrderPayment::create([
                    'order_id' => $order->id,
                    'xendit_external_id' => 'ORD-BUY-' . date('YmdHis') . rand(1000, 9999),
                    'amount' => $rowTotal,
                    'status' => $isFinance ? 'PENDING' : 'PAID',
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

                $dbSource = Auth::user()->getActiveBusinessUnit()?->code ?? 'syihab';

                // Gunakan nama Cabang & Gudang asli untuk dikirim ke Accurate
                $accurateBranchName = $branchName;
                $accurateWarehouseName = $warehouseName;

                // Sync Customer to Accurate
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();

                // Tentukan Customer No untuk Invoice (Cek apakah ada payment finance)
                $financePayment = null;
                foreach ($this->payments as $payment) {
                    $pm = \App\Models\PaymentMethod::find($payment['payment_method_id']);
                    if ($pm && !empty($pm->accurate_customer_no)) {
                        $financePayment = $pm;
                        break;
                    }
                }

                $invoiceCustomerNo = $financePayment
                    ? $financePayment->accurate_customer_no
                    : $customerUser->getAccurateCustomerNo($dbSource);

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
                            'itemCashDiscount' => ((int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1)) + (int)($item['promo_discount'] ?? 0),
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

                        $detailItems[] = $itemData;
                    }

                    $buConfig = \App\Models\BusinessUnit::where('code', $dbSource)->first();
                    $isTaxable = $buConfig ? (bool) $buConfig->is_taxable : false;

                    $siData = [
                        'customerNo' => $invoiceCustomerNo,
                        'branchName' => $accurateBranchName,
                        'detailItem' => $detailItems,
                        // 'cashDiscount' => $manualDiscountAmount,
                        'transDate'    => $this->order_date
                            ? Carbon::parse($this->order_date)->format('d/m/Y')
                            : now()->format('d/m/Y'),
                        'inclusiveTax' => $isTaxable,
                        'taxable' => $isTaxable,
                        'useTax1' => $isTaxable,
                        'description' => $this->notes
                    ];

                    $mdrExpenses = $order->getMdrExpenseDetails();
                    if (!empty($mdrExpenses)) {
                        $siData['detailExpense'] = $mdrExpenses;
                    }

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

                        // SKIP jika ini adalah payment finance (punya accurate_customer_no)
                        if (!empty($pm->accurate_customer_no)) {
                            continue;
                        }

                        $rate = $payment['payment_method_rate_id'] ? \App\Models\PaymentMethodRate::find($payment['payment_method_rate_id']) : null;

                        $pct = $this->getMdrPercentage($payment);
                        $rowMdr = $pct > 0 ? round((float)$payment['amount'] * $pct / 100, 0) : 0;
                        $netReceiptAmount = (float)$payment['amount'] - $rowMdr;

                        $detailInvoiceItem = [
                            'invoiceNo' => $order->accurate_invoice_no,
                            'paymentAmount' => $netReceiptAmount, // Bayar sisa tagihan invoice net
                        ];

                        $srData = [
                            'customerNo' => $invoiceCustomerNo,
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
                $this->dispatch('toast', title: 'Peringatan', message: 'Transaksi berhasil, tapi sinkronisasi ke Accurate gagal.', type: 'warning');
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

    public function processPiutang()
    {
        if ($this->isSoFulfillment) {
            $this->dispatch('toast', title: 'Tidak Diizinkan', message: 'SO tidak dapat diproses sebagai Piutang POS baru.', type: 'error');
            return;
        }

        $handler = Auth::user();
        if (!$handler || !$handler->branch || !$handler->warehouse) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Akun Anda belum terhubung dengan Cabang (Branch) atau Gudang. Harap hubungi Admin.', type: 'error');
            return;
        }

        try {
            $customerId = $this->resolveCustomerId();
            if (!$customerId) return;

            $subtotal = $this->subtotal();
            $manualDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1));
            $promoDiscountAmount = collect($this->cart)->sum(fn($item) => (int)($item['promo_discount'] ?? 0));
            $totalDiscountAmount = $manualDiscountAmount + $promoDiscountAmount;

            $mdrAmt = 0;
            $grandTotal = max(0, $subtotal - $totalDiscountAmount);

            \Illuminate\Support\Facades\DB::beginTransaction();

            $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();

            $order = null;
            if ($this->loadedDraftId) {
                $order = Order::find($this->loadedDraftId);
                if ($order) {
                    $this->restoreStockFromOldItems($order);
                    $order->payments()->delete();

                    $order->update([
                        'user_id' => $customerId,
                        'order_date' => $dateToUse->format('Y-m-d'),
                        'total_amount' => $subtotal,
                        'shipping_cost' => 0,
                        'discount_amount' => $totalDiscountAmount,
                        'mdr_percentage' => 0,
                        'mdr_amount' => 0,
                        'grand_total' => $grandTotal,
                        'order_status' => 'PIUTANG',
                        'order_channel' => 'POS',
                        'handled_by' => Auth::id(),
                        'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                        'payment_method_id' => null,
                        'payment_method_rate_id' => null,
                        'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                        'notes' => $this->notes,
                    ]);
                    $orderNumber = $order->order_number;
                }
            }

            if (!$order) {
                $businessUnit = \Illuminate\Support\Facades\Auth::user()->businessUnit;
                $completedPrefix = $businessUnit->order_prefix ?? 'POS-';

                $orderNumber = $completedPrefix . $dateToUse->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
                    Order::whereDate('order_date', $dateToUse->format('Y-m-d'))
                        ->where('order_channel', 'POS')
                        ->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                $order = Order::create([
                    'business_unit_id' => Auth::user()->getActiveBusinessUnitId() ?? 1,
                    'user_id' => $customerId,
                    'order_number' => $orderNumber,
                    'order_date' => $dateToUse->format('Y-m-d'),
                    'total_amount' => $subtotal,
                    'shipping_cost' => 0,
                    'discount_amount' => $totalDiscountAmount,
                    'mdr_percentage' => 0,
                    'mdr_amount' => 0,
                    'grand_total' => $grandTotal,
                    'order_status' => 'PIUTANG',
                    'order_channel' => 'POS',
                    'handled_by' => Auth::id(),
                    'sales_id' => count($this->selectedSales) > 0 ? $this->selectedSales[0]['id'] : null,
                    'payment_method_id' => null,
                    'payment_method_rate_id' => null,
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko'],
                    'notes' => $this->notes,
                    'branch_id' => Auth::user()->branch_id,
                ]);
            }

            if (!empty($this->selectedPromos)) {
                $service = app(\App\Services\PromoCalculatorService::class);
                $service->recordPromosToOrder($order, $this->cart, $this->selectedPromos);
            }

            $this->createOrderItemsFromCart($order);

            \Illuminate\Support\Facades\DB::commit();

            try {
                $accurateService = app(AccurateService::class);
                $customerUser = User::find($customerId);
                $handler = Auth::user();
                $branchName = $handler->branch->name ?? 'Banjarbaru';
                $warehouseName = $handler->warehouse->name ?? 'Head Office';

                $dbSource = Auth::user()->getActiveBusinessUnit()?->code ?? 'syihab';

                $accurateBranchName = $branchName;
                $accurateWarehouseName = $warehouseName;

                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();

                if (!$order->accurate_invoice_no) {
                    $detailItems = [];
                    foreach ($this->cart as $item) {

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
                            'itemCashDiscount' => ((int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1)) + (int)($item['promo_discount'] ?? 0),
                            'salesmanListNumber' => $detailSalesman,
                        ];

                        $condition = $item['condition'] ?? '';
                        if (in_array($condition, ['Inter', 'Resmi'])) {
                            $city = trim(str_replace(['GSK -', 'GSK '], '', $accurateWarehouseName));
                            $departmentPrefix = ($condition === 'Inter') ? 'Distri' : 'Retail';
                            $itemData['departmentName'] = $departmentPrefix . ' ' . $city;
                        }

                        if (!empty($detailSN)) {
                            $itemData['detailSerialNumber'] = $detailSN;
                        }

                        $detailItems[] = $itemData;
                    }

                    $buConfig = \App\Models\BusinessUnit::where('code', $dbSource)->first();
                    $isTaxable = $buConfig ? (bool) $buConfig->is_taxable : false;

                    $siData = [
                        'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                        'branchName' => $accurateBranchName,
                        'detailItem' => $detailItems,
                        'inclusiveTax' => $isTaxable,
                        'transDate'    => $this->order_date
                            ? Carbon::parse($this->order_date)->format('d/m/Y')
                            : now()->format('d/m/Y'),
                        'taxable' => $isTaxable,
                        'useTax1' => $isTaxable,
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
            } catch (\Exception $e) {
                Log::error('POS Accurate Integration Error (Piutang): ' . $e->getMessage());
            }

            $this->completedOrder = $order->load(['items', 'user', 'payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy']);
            $this->showCheckoutModal = false;
            $this->showPiutangModal = false; // Add this too
            $this->showReceiptModal = true;

            $this->resetCheckout();
            $this->dispatch('toast', title: 'Transaksi Piutang Berhasil', message: 'Order ' . $orderNumber . ' berhasil diproses.', type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('POS Payment Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: $e->getMessage(), type: 'error');
        }
    }

    public function resetCheckout()
    {
        $this->isPiutangSettlement = false;
        $this->loadedDraftId = null;
        $this->cart = [];
        $this->selectedSales = [];
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
                'category' => '',
                'bank_name' => '',
                'payment_method_id' => '',
                'payment_method_rate_id' => '',
                'no_kontrak' => '',
                'amount' => 0,
            ]
        ];
        $this->order_date = null;
        $this->currentStep = 1;
        $this->loadedDraftId = null;
    }

    public function closeReceipt()
    {
        $this->newTransaction();
    }

    public function newTransaction()
    {
        $this->cart = [];
        $this->notes = '';
        $this->payments = [
            [
                'category' => '',
                'bank_name' => '',
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
        $fullUrl = config('services.qontak.api_url');
        if (empty($fullUrl)) {
            $this->dispatch('toast', title: 'Gagal', message: 'URL Qontak tidak ditemukan di konfigurasi (.env).', type: 'error');
            return;
        }

        if (!preg_match("~^(?:f|ht)tps?://~i", $fullUrl)) {
            $fullUrl = "https://" . $fullUrl;
        }

        $method = 'POST';
        $parsedUrl = parse_url($fullUrl);
        $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
        $endpoint = $parsedUrl['path'] ?? '';
        $clientId = config('services.qontak.client_id');
        $clientSecret = config('services.qontak.client_secret');

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
            'channel_integration_id' =>  config('services.qontak.integration_id'),
            'message_template_id' => config('services.qontak.template_id'),
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
        $storeTitle = optional($this->completedOrder->businessUnit)->store_title ?? 'Z-POS STORE';
        $printer->text($storeTitle . "\n");

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

            if ($v instanceof \App\Models\ProductAccurate) {
                $itemName = $v->name ?? '-';
                $ram = '';
                $storage = '';
                $color = '';
            } else {
                $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
                $ram = $v ? $v->ram ?? '' : '';
                $storage = $v ? $v->storage ?? '' : '';
                $color = $v ? $v->color ?? '' : '';
            }

            // Bersihkan awalan nama
            $itemName = preg_replace('/^(?:DS\s*-\s*HP\s*|DS\s*-\s*|HP\s*-\s*|HP\s*)/i', '', trim($itemName));

            if ($v && !($v instanceof \App\Models\ProductAccurate)) {
                $variantDetails = "";
                if ($ram != null && $ram !== '') $variantDetails .= $ram . "/";
                $variantDetails .= $storage;
                if ($color != null && $color !== '') $variantDetails .= " " . $color;
                if (trim($variantDetails) !== '') $itemName .= " " . trim($variantDetails);
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

        // Total Section
        $showDiscount = optional($this->completedOrder->businessUnit)->receipt_show_discount;
        if ($showDiscount) {
            $printer->text($this->formatLine("Subtotal", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), $maxColumns) . "\n");
            if ($this->completedOrder->discount_amount > 0) {
                $printer->text($this->formatLine("Diskon", "-Rp " . number_format($this->completedOrder->discount_amount, 0, ',', '.'), $maxColumns) . "\n");
            }
            $printer->text($this->formatLine("TOTAL", "Rp " . number_format($this->completedOrder->grand_total, 0, ',', '.'), $maxColumns) . "\n");
        } else {
            $printer->text($this->formatLine("Total", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), $maxColumns) . "\n");
        }
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

    /**
     * Resolve customer ID — find existing or create new.
     * @return int|null Customer ID, or null if validation fails (toast dispatched)
     */
    private function resolveCustomerId(): ?int
    {
        if ($this->selectedCustomerId) {
            return $this->selectedCustomerId;
        }

        if ($this->isNewCustomer) {
            if (preg_match('/^0+$/', (string) $this->customerPhone)) {
                $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: 'Nomor HP tidak boleh hanya berisi angka 0.', type: 'error');
                return null;
            }

            $emailToValidate = $this->customerEmail ?: ($this->customerPhone . rand(1000, 9999) . '@zpos.com');

            $validator = \Illuminate\Support\Facades\Validator::make(
                [
                    'customerName'  => $this->customerName,
                    'customerPhone' => $this->customerPhone,
                    'customerEmail' => $emailToValidate,
                ],
                [
                    'customerName'  => 'required|string|max:255',
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

            if ($validator->fails()) {
                $errors = $validator->errors();
                $failedRules = $validator->failed();
                $firstErrorMessage = $errors->first();

                if (isset($failedRules['customerPhone']['Unique'])) {
                    $existingProfile = \Illuminate\Support\Facades\DB::table('user_profiles')
                        ->where('phone_number', $this->customerPhone)
                        ->first();

                    if ($existingProfile) {
                        $namaCustomer = $existingProfile->full_name ?? 'Customer Lain';
                        $firstErrorMessage = "Nomor HP sudah terdaftar atas nama: {$namaCustomer}. Silakan pilih customer dari daftar pencarian.";
                    }
                }

                $this->dispatch('toast', title: 'Data Customer Tidak Valid', message: $firstErrorMessage, type: 'error');
                return null;
            }

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

            return $newUser->id;
        }

        return null;
    }

    /**
     * Restore stock and SN from old draft items.
     */
    private function restoreStockFromOldItems(Order $order): void
    {
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

            if (!empty($oldItem->serial_number)) {
                $oldSns = array_values(array_filter(array_map('trim', explode(',', $oldItem->serial_number))));
                if (!empty($oldSns)) {
                    \App\Models\ProductSerialNumber::whereIn('serial_number', $oldSns)
                        ->update(['status' => 'Available']);
                }
            }
        }
        $order->items()->delete();

        // Kembalikan kuota promo sebelum detach
        $promoIds = $order->promos()->pluck('promos.id')->toArray();
        if (!empty($promoIds)) {
            \App\Models\Promo::whereIn('id', $promoIds)->decrement('used_quota');
        }

        $order->promos()->detach();
    }

    /**
     * Create order items from cart, reduce stock, update SN status, and attach promos.
     */
    private function createOrderItemsFromCart(Order $order): void
    {
        foreach ($this->cart as $item) {
            $rawSns = $item['serial_numbers'] ?? [];
            $cleanSns = array_values(array_filter(array_map('trim', $rawSns)));

            if (!empty($cleanSns)) {
                \App\Models\ProductSerialNumber::whereIn('serial_number', $cleanSns)
                    ->update(['status' => 'Unavailable']);
            }

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $item['variant_id'],
                'product_variant_type' => $item['variant_type'],
                'product_name' => $item['name'] ?? 'Unknown Product',
                'qty' => $item['qty'],
                'price_at_checkout' => $item['price'],
                'subtotal' => $item['price'] * $item['qty'],
                'discount_amount' => (int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1),
                'promo_discount_amount' => (int)($item['promo_discount'] ?? 0),
                'serial_number' => !empty($cleanSns) ? implode(', ', $cleanSns) : '',
            ]);

            $this->attachPromoBreakdownToItem($orderItem, $item, $cleanSns);
            $this->reduceWarehouseStock($item);
        }
    }

    /**
     * Attach promo discount breakdown per SN to order_item_promos pivot.
     */
    private function attachPromoBreakdownToItem(OrderItem $orderItem, array $item, array $cleanSns): void
    {
        $vendorNameFallback = (clone $orderItem)->vendor_name;

        foreach ($item['promo_discounts'] ?? [] as $promoId => $discAmount) {
            if ($discAmount <= 0) continue;

            if (!empty($cleanSns)) {
                $discountPerSn = round($discAmount / max(1, count($cleanSns)));
                foreach ($cleanSns as $sn) {
                    $snModel = \App\Models\ProductSerialNumber::with('vendor')->where('serial_number', $sn)->first();
                    $actualVendorName = $snModel?->vendor?->vendor_name ?? $vendorNameFallback;

                    $orderItem->promos()->attach($promoId, [
                        'discount_amount' => $discountPerSn,
                        'serial_number' => $sn,
                        'vendor_name' => $actualVendorName,
                    ]);
                }
            } else {
                $orderItem->promos()->attach($promoId, [
                    'discount_amount' => $discAmount,
                    'serial_number' => '',
                    'vendor_name' => $vendorNameFallback,
                ]);
            }
        }
    }

    /**
     * Reduce warehouse stock for a cart item.
     */
    private function reduceWarehouseStock(array $item): void
    {
        $warehouseStock = \App\Models\WarehouseStock::firstOrCreate(
            [
                'warehouse_id' => Auth::user()->warehouse_id,
                'variant_id' => $item['variant_id'],
                'variant_type' => $item['variant_type'],
            ],
            ['stock' => 0]
        );
        $warehouseStock->update([
            'stock' => max(0, $warehouseStock->stock - (int)$item['qty'])
        ]);
    }

    public function processSoFulfillment($grandTotal)
    {
        try {
            $order = Order::with(['items.variant', 'user', 'accurateDocs', 'businessUnit'])->find($this->loadedSoOrderId);
            if (!$order) {
                \Illuminate\Support\Facades\DB::rollBack();
                $this->dispatch('toast', title: 'Error', message: 'Pesanan SO tidak ditemukan.', type: 'error');
                return;
            }

            $accurateService = app(AccurateService::class);
            $dbSource = $order->businessUnit->code ?? 'syihab';
            $handler = Auth::user();
            $warehouseName = $handler->warehouse->name ?? 'Gudang Utama';
            $branchName = $handler->branch->name ?? 'Banjarbaru';

            $accurateBranchName = $branchName;
            if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                $accurateBranchName = 'GSK ' . $accurateBranchName;
            }

            // 1. Update SN in Local DB and collect for Accurate
            $doDetailItems = [];
            $siDetailItems = [];
            $hasSN = false;

            foreach ($this->cart as $cartItem) {
                $orderItem = \App\Models\OrderItem::find($cartItem['item_id'] ?? 0);
                if ($orderItem) {
                    $snInput = implode(',', array_filter(array_map('trim', $cartItem['serial_numbers'] ?? [])));
                    if ($snInput !== ($orderItem->serial_number ?? '')) {
                        $orderItem->update(['serial_number' => $snInput]);
                    }
                    if (!empty($snInput)) {
                        $hasSN = true;
                    }
                }

                $detailSNs = [];
                if (!empty($cartItem['serial_numbers'])) {
                    foreach ($cartItem['serial_numbers'] as $sn) {
                        if (trim($sn)) {
                            $detailSNs[] = ['serialNumberNo' => trim($sn), 'quantity' => 1];
                        }
                    }
                }

                $sku = $cartItem['sku'] ?: 'ITEM-UNKNOWN';

                // For DO
                $doItem = [
                    'itemNo' => $sku,
                    'quantity' => (float)$cartItem['qty'],
                    'warehouseName' => $warehouseName,
                    'salesOrderNumber' => $order->accurate_so_number,
                ];
                if (!empty($detailSNs)) {
                    $doItem['detailSerialNumber'] = $detailSNs;
                }
                $doDetailItems[] = $doItem;

                // For SI
                $siItem = [
                    'itemNo' => $sku,
                    'unitPrice' => (float)$cartItem['price'],
                    'quantity' => (float)$cartItem['qty'],
                    'detailName' => $cartItem['name'],
                    'itemCashDiscount' => ((float)($cartItem['discount_amount'] ?? 0) * (float)$cartItem['qty']) + (float)($cartItem['promo_discount'] ?? 0),
                ];
                if (!empty($detailSNs)) {
                    $siItem['detailSerialNumber'] = $detailSNs;
                }
                $siDetailItems[] = $siItem;
            }

            // 2. Check if DO exists, if not and has SN -> Create DO
            $doDoc = $order->accurateDocs()->where('doc_type', 'DELIVERY_ORDER')->where('status', 'SUCCESS')->first();

            if (!$doDoc && $hasSN) {
                $doData = [
                    'customerNo' => $order->user->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'transDate' => now()->format('d/m/Y'),
                    'salesOrderNumber' => $order->accurate_so_number,
                    'description' => 'DO Otomatis dari Pelunasan POS',
                    'detailItem' => $doDetailItems
                ];

                $doResult = $accurateService->postDeliveryOrder($doData, $dbSource);
                if (isset($doResult['r']['number'])) {
                    $doDoc = \App\Models\OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'DELIVERY_ORDER',
                        'doc_number' => $doResult['r']['number'],
                        'accurate_id' => $doResult['r']['id'] ?? null,
                        'amount' => $order->grand_total,
                        'status' => 'SUCCESS',
                    ]);
                }
            }

            // 3. Create SI (Sales Invoice)
            if (!$order->accurate_invoice_no) {
                if ($doDoc) {
                    foreach ($siDetailItems as &$i) {
                        $i['deliveryOrderNumber'] = $doDoc->doc_number;
                    }
                } elseif ($order->accurate_so_number) {
                    foreach ($siDetailItems as &$i) {
                        $i['salesOrderNumber'] = $order->accurate_so_number;
                    }
                }

                $siData = [
                    'customerNo' => $order->user->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'transDate' => now()->format('d/m/Y'),
                    'detailItem' => $siDetailItems,
                    'inclusiveTax' => true,
                    'taxable' => true,
                    'description' => 'Pelunasan SO via POS'
                ];

                // DP
                $dpInvoices = $order->accurateDocs()
                    ->where('doc_type', 'DP_INVOICE')
                    ->where('status', 'SUCCESS')
                    ->get();
                $validDpInvoices = [];
                foreach ($dpInvoices as $dpInv) {
                    $hasReceipt = $order->accurateDocs()
                        ->where('doc_type', 'DP_RECEIPT')
                        ->where('status', 'SUCCESS')
                        ->where('created_at', '>=', $dpInv->created_at)
                        ->exists();
                    if ($hasReceipt) {
                        $validDpInvoices[] = [
                            'invoiceNumber' => $dpInv->doc_number,
                            'paymentAmount' => (float) $dpInv->amount,
                        ];
                    }
                }
                if (count($validDpInvoices) > 0) {
                    $siData['detailDownPayment'] = $validDpInvoices;
                }

                $mdrExpenses = [];
                foreach ($this->payments as $payment) {
                    $rate = $payment['payment_method_rate_id'] ? \App\Models\PaymentMethodRate::find($payment['payment_method_rate_id']) : null;
                    $pct = $this->getMdrPercentage($payment);
                    $rowMdr = $pct > 0 ? round((float)$payment['amount'] * $pct / 100, 0) : 0;

                    if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                        $mdrExpenses[] = [
                            'accountNo' => $rate->accurate_account_no,
                            'expenseAmount' => -abs((float)$rowMdr),
                            'expenseNotes' => 'MDR ' . ($rate->name ?? ' ')
                        ];
                    }
                }

                if (!empty($mdrExpenses)) {
                    $siData['detailExpense'] = $mdrExpenses;
                }

                $siResult = $accurateService->postSalesInvoice($siData, $dbSource);
                if (isset($siResult['r']['number'])) {
                    $order->update(['accurate_invoice_no' => $siResult['r']['number']]);
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_INVOICE',
                        'doc_number' => $siResult['r']['number'],
                        'accurate_id' => $siResult['r']['id'] ?? null,
                        'amount' => $order->grand_total,
                        'status' => 'SUCCESS',
                    ]);
                }
            }

            // 4. Create SR (Sales Receipt) and save local OrderPayments
            if ($order->accurate_invoice_no) {
                $srNumbers = [];
                foreach ($this->payments as $payment) {
                    $rowTotal = (float)$payment['amount'];

                    OrderPayment::create([
                        'order_id' => $order->id,
                        'xendit_external_id' => 'ORD-SO-POS-' . date('YmdHis') . rand(1000, 9999),
                        'amount' => $rowTotal,
                        'status' => 'PAID',
                        'payment_method_id' => $payment['payment_method_id'],
                        'payment_method_rate_id' => $payment['payment_method_rate_id'] ?: null,
                        'no_kontrak' => $payment['no_kontrak'] ?? null,
                    ]);

                    $pm = \App\Models\PaymentMethod::findOrFail($payment['payment_method_id']);
                    if (!empty($pm->accurate_customer_no)) {
                        continue; // Finance
                    }

                    $rate = $payment['payment_method_rate_id'] ? \App\Models\PaymentMethodRate::find($payment['payment_method_rate_id']) : null;
                    $pct = $this->getMdrPercentage($payment);
                    $rowMdr = $pct > 0 ? round((float)$payment['amount'] * $pct / 100, 0) : 0;
                    $rowBaseAmount = (float)$payment['amount'];
                    $netReceiptAmount = $rowBaseAmount - $rowMdr;

                    $detailInvoiceItem = [
                        'invoiceNo' => $order->accurate_invoice_no,
                        'paymentAmount' => $netReceiptAmount,
                    ];

                    $srData = [
                        'customerNo' => $order->user->getAccurateCustomerNo($dbSource),
                        'branchName' => $branchName,
                        'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                        'receiptAmount' => (float) $netReceiptAmount,
                        'chequeAmount' => (float) $netReceiptAmount,
                        'transDate' => now()->format('d/m/Y'),
                        'detailInvoice' => [$detailInvoiceItem],
                        'description' => 'Pelunasan SO via POS'
                    ];

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

            $order->update(['order_status' => 'COMPLETED']);
            \Illuminate\Support\Facades\DB::commit();

            $this->completedOrder = $order->load(['items', 'user', 'payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy']);
            $this->showCheckoutModal = false;
            $this->showReceiptModal = true;

            $this->resetCheckout();
            $this->dispatch('toast', title: 'Transaksi Berhasil', message: 'Pelunasan SO ' . $order->order_number . ' berhasil diproses.', type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('POS SO Fulfillment Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Error', message: 'Gagal memproses pelunasan SO: ' . $e->getMessage(), type: 'error');
        }
    }
}
