<?php

namespace App\Jobs;

use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodRate;
use App\Models\User;
use App\Services\AccurateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPosOrderToAccurateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;
    public string $mode;
    public array $context;

    /**
     * Jumlah maksimal percobaan ulang jika terjadi kegagalan (misal timeout koneksi API Accurate).
     */
    public $tries = 3;

    /**
     * Maksimal waktu eksekusi job (dalam detik) sebelum dimatikan paksa.
     */
    public $timeout = 180;

    /**
     * Waktu tunggu (detik) sebelum retry otomatis.
     */
    public $backoff = 20;

    /**
     * Create a new job instance.
     *
     * @param int $orderId ID Order yang akan disinkronkan
     * @param string $mode 'REGULAR', 'SO_FULFILLMENT', 'PIUTANG_NEW', 'PIUTANG_SETTLEMENT'
     * @param array $context Data kontekstual POS (cart, payments, branch_name, warehouse_name, dll)
     */
    public function __construct(int $orderId, string $mode = 'REGULAR', array $context = [])
    {
        $this->orderId = $orderId;
        $this->mode = $mode;
        $this->context = $context;
    }

    /**
     * Execute the job.
     */
    public function handle(AccurateService $accurateService): void
    {
        $order = Order::with([
            'user',
            'items.variant',
            'payments.paymentMethod',
            'payments.paymentMethodRate',
            'businessUnit',
            'handledBy.branch',
            'handledBy.warehouse'
        ])->find($this->orderId);

        if (!$order) {
            Log::channel('pos_accurate')->warning("SyncPosOrderToAccurateJob: Order ID {$this->orderId} tidak ditemukan.");
            return;
        }

        Log::channel('pos_accurate')->info("SyncPosOrderToAccurateJob START: Order #{$order->order_number} (ID: {$order->id}), Mode: {$this->mode}");

        $dbSource = $this->context['db_source'] ?? $order->businessUnit->code ?? 'syihab';
        $branchName = $this->context['branch_name'] ?? $order->handledBy->branch->name ?? $order->branch->name ?? 'Banjarbaru';
        $warehouseName = $this->context['warehouse_name'] ?? $order->handledBy->warehouse->name ?? $order->warehouse->name ?? 'Head Office';

        $accurateBranchName = $branchName;
        if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
            $accurateBranchName = 'GSK ' . $accurateBranchName;
        }
        $accurateWarehouseName = $warehouseName;

        try {
            switch ($this->mode) {
                case 'REGULAR':
                    $this->handleRegularCheckout($order, $accurateService, $dbSource, $accurateBranchName, $accurateWarehouseName);
                    break;

                case 'SO_FULFILLMENT':
                    $this->handleSoFulfillment($order, $accurateService, $dbSource, $accurateBranchName, $accurateWarehouseName, $branchName, $warehouseName);
                    break;

                case 'PIUTANG_NEW':
                    $this->handlePiutangNew($order, $accurateService, $dbSource, $accurateBranchName, $accurateWarehouseName);
                    break;

                case 'PIUTANG_SETTLEMENT':
                    $this->handlePiutangSettlement($order, $accurateService, $dbSource, $branchName);
                    break;

                default:
                    Log::channel('pos_accurate')->warning("SyncPosOrderToAccurateJob: Mode '{$this->mode}' tidak dikenal.");
                    break;
            }

            Log::channel('pos_accurate')->info("SyncPosOrderToAccurateJob COMPLETED: Order #{$order->order_number}");
        } catch (\Throwable $e) {
            Log::channel('pos_accurate')->error("SyncPosOrderToAccurateJob FAILED: Order #{$order->order_number} ({$this->mode}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            // Rethrow exception agar Laravel Queue melakukan retry sesuai $tries & $backoff
            throw $e;
        }
    }

    /**
     * 1. Mode REGULAR Checkout (Sales Invoice + Sales Receipts)
     */
    protected function handleRegularCheckout(
        Order $order,
        AccurateService $accurateService,
        string $dbSource,
        string $accurateBranchName,
        string $accurateWarehouseName
    ): void {
        $customerUser = $order->user;
        if ($customerUser) {
            try {
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();
            } catch (\Exception $e) {
                Log::channel('pos_accurate')->warning("Gagal sync customer ke Accurate: " . $e->getMessage());
            }
        }

        $payments = $this->context['payments'] ?? $this->extractPaymentsFromOrder($order);
        $financePayment = $this->resolveFinancePayment($payments);

        $invoiceCustomerNo = $financePayment
            ? $financePayment->accurate_customer_no
            : ($customerUser ? $customerUser->getAccurateCustomerNo($dbSource) : 'UMUM');

        // A. Post Sales Invoice jika belum ada
        if (!$order->accurate_invoice_no) {
            $cartItems = $this->context['cart'] ?? $this->extractCartFromOrder($order);
            $selectedSales = $this->context['selected_sales'] ?? [];
            $detailSalesman = $this->resolveSalesmanList($selectedSales, $order);

            $detailItems = [];
            foreach ($cartItems as $item) {
                $rawSns = $item['serial_numbers'] ?? [];
                $cleanSns = array_values(array_filter(array_map('trim', (array)$rawSns)));

                $detailSN = [];
                foreach ($cleanSns as $sn) {
                    $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                }

                $itemData = [
                    'itemNo' => !empty($item['sku']) ? $item['sku'] : 'ITEM-UNKNOWN',
                    'warehouseName' => $accurateWarehouseName,
                    'unitPrice' => (float)$item['price'],
                    'quantity' => (float)$item['qty'],
                    'itemCashDiscount' => ((int)($item['discount_amount'] ?? 0) * (int)($item['qty'] ?? 1)) + (int)($item['promo_discount'] ?? 0),
                    'salesmanListNumber' => $detailSalesman,
                    'projectNo' => $item['project_number'] ?? ''
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

            $buConfig = BusinessUnit::where('code', $dbSource)->first();
            $isTaxable = $buConfig ? (bool)$buConfig->is_taxable : false;

            $transDate = !empty($this->context['order_date'])
                ? Carbon::parse($this->context['order_date'])->format('d/m/Y')
                : ($order->order_date ? Carbon::parse($order->order_date)->format('d/m/Y') : now()->format('d/m/Y'));

            $siData = [
                'customerNo' => $invoiceCustomerNo,
                'branchName' => $accurateBranchName,
                'detailItem' => $detailItems,
                'transDate' => $transDate,
                'inclusiveTax' => $isTaxable,
                'taxable' => $isTaxable,
                'useTax1' => $isTaxable,
                'description' => $this->context['notes'] ?? $order->notes ?? ''
            ];

            $validDpInvoices = $this->context['valid_dp_invoices'] ?? [];
            if (count($validDpInvoices) > 0) {
                $siData['detailDownPayment'] = $validDpInvoices;
            }

            $mdrExpenses = $order->getMdrExpenseDetails();
            if (!empty($mdrExpenses)) {
                $siData['detailExpense'] = $mdrExpenses;
            }

            $siResult = $accurateService->postSalesInvoice($siData, $dbSource);
            if (isset($siResult['r']['number'])) {
                $order->update(['accurate_invoice_no' => $siResult['r']['number']]);
                OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_INVOICE',
                    'doc_number' => $siResult['r']['number'],
                    'accurate_id' => $siResult['r']['id'] ?? null,
                    'amount' => $order->grand_total,
                    'status' => 'SUCCESS',
                ]);
            } else {
                throw new Exception('Gagal membuat Faktur Penjualan (SI) Accurate: ' . ($siResult['d'][0] ?? json_encode($siResult)));
            }
        }

        // B. Post Sales Receipts jika ada invoice dan belum ada receipt
        $order->refresh();
        if (!$order->accurate_receipt_no && $order->accurate_invoice_no) {
            $srNumbers = [];

            foreach ($payments as $payment) {
                $rowTotal = (float)($payment['amount'] ?? 0);
                if ($rowTotal <= 0) continue;

                $pm = PaymentMethod::find($payment['payment_method_id'] ?? null);
                if (!$pm || !empty($pm->accurate_customer_no)) {
                    continue; // Skip finance payment
                }

                $pct = $this->getMdrPercentage($payment);
                $rowMdr = $pct > 0 ? round($rowTotal * $pct / 100, 0) : 0;
                $netReceiptAmount = $rowTotal - $rowMdr;

                $transDate = !empty($this->context['order_date'])
                    ? Carbon::parse($this->context['order_date'])->format('d/m/Y')
                    : ($order->order_date ? Carbon::parse($order->order_date)->format('d/m/Y') : now()->format('d/m/Y'));

                $srData = [
                    'customerNo' => $invoiceCustomerNo,
                    'branchName' => $accurateBranchName,
                    'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                    'receiptAmount' => (float)$netReceiptAmount,
                    'chequeAmount' => (float)$netReceiptAmount,
                    'transDate' => $transDate,
                    'detailInvoice' => [
                        [
                            'invoiceNo' => $order->accurate_invoice_no,
                            'paymentAmount' => $netReceiptAmount,
                        ]
                    ],
                    'description' => $this->context['notes'] ?? $order->notes ?? ''
                ];

                $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                if (isset($srResult['r']['number'])) {
                    $srNumbers[] = $srResult['r']['number'];
                    OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_RECEIPT',
                        'doc_number' => $srResult['r']['number'],
                        'accurate_id' => $srResult['r']['id'] ?? null,
                        'amount' => (float)$netReceiptAmount,
                        'status' => 'SUCCESS',
                    ]);
                } else {
                    Log::channel('pos_accurate')->error("Gagal post SR Accurate untuk Order #{$order->order_number}: " . json_encode($srResult));
                }
            }

            if (!empty($srNumbers)) {
                $order->update(['accurate_receipt_no' => implode(', ', $srNumbers)]);
            }
        }
    }

    /**
     * 2. Mode SO FULFILLMENT (DO + SI + Close SO jika leasing + SR)
     */
    protected function handleSoFulfillment(
        Order $order,
        AccurateService $accurateService,
        string $dbSource,
        string $accurateBranchName,
        string $accurateWarehouseName,
        string $branchName,
        string $warehouseName
    ): void {
        $payments = $this->context['payments'] ?? $this->extractPaymentsFromOrder($order);
        $financePayment = $this->resolveFinancePayment($payments);
        $isFinance = $financePayment !== null;

        $invoiceCustomerNo = $financePayment
            ? $financePayment->accurate_customer_no
            : ($order->user ? $order->user->getAccurateCustomerNo($dbSource) : 'UMUM');

        $cartItems = $this->context['cart'] ?? $this->extractCartFromOrder($order);
        $selectedSales = $this->context['selected_sales'] ?? [];
        $detailSalesman = $this->resolveSalesmanList($selectedSales, $order);

        $doDetailItems = [];
        $siDetailItems = [];
        $hasSN = false;

        foreach ($cartItems as $cartItem) {
            $rawSns = $cartItem['serial_numbers'] ?? [];
            $cleanSns = array_values(array_filter(array_map('trim', (array)$rawSns)));

            if (!empty($cleanSns)) {
                $hasSN = true;
            }

            $detailSNs = [];
            foreach ($cleanSns as $sn) {
                $detailSNs[] = ['serialNumberNo' => $sn, 'quantity' => 1];
            }

            $sku = !empty($cartItem['sku']) ? $cartItem['sku'] : 'ITEM-UNKNOWN';
            $projectNo = $cartItem['project_number'] ?? '';

            // DO item
            $doItem = [
                'itemNo' => $sku,
                'quantity' => (float)$cartItem['qty'],
                'warehouseName' => $warehouseName,
            ];
            if (!empty($projectNo)) {
                $doItem['projectNo'] = $projectNo;
            }
            if (!empty($cartItem['item_id']) && !$isFinance) {
                $doItem['salesOrderNumber'] = $order->accurate_so_number;
            }
            if (!empty($detailSNs)) {
                $doItem['detailSerialNumber'] = $detailSNs;
            }
            $doDetailItems[] = $doItem;

            // SI item
            $siItem = [
                'itemNo' => $sku,
                'unitPrice' => (float)$cartItem['price'],
                'quantity' => (float)$cartItem['qty'],
                'detailName' => $cartItem['name'] ?? '',
                'itemCashDiscount' => ((float)($cartItem['discount_amount'] ?? 0) * (float)$cartItem['qty']) + (float)($cartItem['promo_discount'] ?? 0),
            ];
            if (!empty($projectNo)) {
                $siItem['projectNo'] = $projectNo;
            }
            if (!empty($detailSalesman)) {
                $siItem['salesmanListNumber'] = $detailSalesman;
            }
            if (!empty($detailSNs)) {
                $siItem['detailSerialNumber'] = $detailSNs;
            }
            $siDetailItems[] = $siItem;
        }

        // A. Delivery Order (DO) jika belum ada dan memiliki SN
        $doDoc = $order->accurateDocs()->where('doc_type', 'DELIVERY_ORDER')->where('status', 'SUCCESS')->first();
        if (!$doDoc && $hasSN) {
            $doData = [
                'customerNo' => $invoiceCustomerNo,
                'branchName' => $accurateBranchName,
                'transDate' => now()->format('d/m/Y'),
                'description' => 'DO Otomatis dari Pelunasan POS' . (!empty($this->context['notes']) ? ' - ' . $this->context['notes'] : ''),
                'detailItem' => $doDetailItems
            ];

            if (!$isFinance) {
                $doData['salesOrderNumber'] = $order->accurate_so_number;
            }

            $doResult = $accurateService->postDeliveryOrder($doData, $dbSource);
            if (isset($doResult['r']['number'])) {
                $doDoc = OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'DELIVERY_ORDER',
                    'doc_number' => $doResult['r']['number'],
                    'accurate_id' => $doResult['r']['id'] ?? null,
                    'amount' => $order->grand_total,
                    'status' => 'SUCCESS',
                ]);
            } else {
                throw new Exception('Gagal membuat Pengiriman Pesanan (DO) Accurate: ' . ($doResult['d'][0] ?? json_encode($doResult)));
            }
        }

        // B. Sales Invoice (SI)
        if (!$order->accurate_invoice_no) {
            if ($doDoc) {
                foreach ($siDetailItems as $index => &$i) {
                    $i['deliveryOrderNumber'] = $doDoc->doc_number;
                }
            } elseif ($order->accurate_so_number && !$isFinance) {
                foreach ($siDetailItems as $index => &$i) {
                    if (!empty($cartItems[$index]['item_id'])) {
                        $i['salesOrderNumber'] = $order->accurate_so_number;
                    }
                }
            }

            $siData = [
                'customerNo' => $invoiceCustomerNo,
                'branchName' => $accurateBranchName,
                'transDate' => now()->format('d/m/Y'),
                'detailItem' => $siDetailItems,
                'inclusiveTax' => true,
                'taxable' => true,
                'description' => 'Pelunasan SO via POS' . (!empty($this->context['notes']) ? ' - ' . $this->context['notes'] : '')
            ];

            $validDpInvoices = $this->context['valid_dp_invoices'] ?? [];
            if (count($validDpInvoices) > 0 && !$isFinance) {
                $siData['detailDownPayment'] = $validDpInvoices;
            }

            $mdrExpenses = [];
            foreach ($payments as $payment) {
                $rate = !empty($payment['payment_method_rate_id']) ? PaymentMethodRate::find($payment['payment_method_rate_id']) : null;
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
                OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_INVOICE',
                    'doc_number' => $siResult['r']['number'],
                    'accurate_id' => $siResult['r']['id'] ?? null,
                    'amount' => $order->grand_total,
                    'status' => 'SUCCESS',
                ]);

                // Close SO jika leasing
                if ($isFinance && $order->accurate_so_number) {
                    try {
                        $accurateService->closeSalesOrder($order->accurate_so_number, $dbSource);
                        Log::channel('pos_accurate')->info("Sales Order {$order->accurate_so_number} ditutup paksa karena pelunasan menggunakan Leasing.");
                    } catch (\Exception $e) {
                        Log::channel('pos_accurate')->error("Gagal menutup SO {$order->accurate_so_number}: " . $e->getMessage());
                    }
                }
            } else {
                throw new Exception('Gagal membuat Faktur Penjualan (SI) Accurate: ' . ($siResult['d'][0] ?? json_encode($siResult)));
            }
        }

        // C. Sales Receipts (SR)
        $order->refresh();
        if ($order->accurate_invoice_no) {
            $srNumbers = [];
            foreach ($payments as $payment) {
                $rowTotal = (float)($payment['amount'] ?? 0);
                if ($rowTotal <= 0) continue;

                $pm = PaymentMethod::find($payment['payment_method_id'] ?? null);
                if (!$pm || !empty($pm->accurate_customer_no)) {
                    continue; // Skip finance
                }

                $pct = $this->getMdrPercentage($payment);
                $rowMdr = $pct > 0 ? round($rowTotal * $pct / 100, 0) : 0;
                $netReceiptAmount = $rowTotal - $rowMdr;

                $srData = [
                    'customerNo' => $invoiceCustomerNo,
                    'branchName' => $branchName,
                    'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                    'receiptAmount' => (float)$netReceiptAmount,
                    'chequeAmount' => (float)$netReceiptAmount,
                    'transDate' => now()->format('d/m/Y'),
                    'detailInvoice' => [
                        [
                            'invoiceNo' => $order->accurate_invoice_no,
                            'paymentAmount' => $netReceiptAmount,
                        ]
                    ],
                    'description' => 'Pelunasan SO via POS' . (!empty($this->context['notes']) ? ' - ' . $this->context['notes'] : '')
                ];

                $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                if (isset($srResult['r']['number'])) {
                    $srNumbers[] = $srResult['r']['number'];
                    OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_RECEIPT',
                        'doc_number' => $srResult['r']['number'],
                        'accurate_id' => $srResult['r']['id'] ?? null,
                        'amount' => (float)$netReceiptAmount,
                        'status' => 'SUCCESS',
                    ]);
                } else {
                    Log::channel('pos_accurate')->error("Gagal post SR SO Fulfillment Order #{$order->order_number}: " . json_encode($srResult));
                }
            }

            if (!empty($srNumbers)) {
                $order->update(['accurate_receipt_no' => implode(', ', $srNumbers)]);
            }
        }
    }

    /**
     * 3. Mode PIUTANG_NEW (Hanya Sales Invoice, tanpa Sales Receipts)
     */
    protected function handlePiutangNew(
        Order $order,
        AccurateService $accurateService,
        string $dbSource,
        string $accurateBranchName,
        string $accurateWarehouseName
    ): void {
        $customerUser = $order->user;
        if ($customerUser) {
            try {
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();
            } catch (\Exception $e) {
                Log::channel('pos_accurate')->warning("Gagal sync customer piutang ke Accurate: " . $e->getMessage());
            }
        }

        if (!$order->accurate_invoice_no) {
            $cartItems = $this->context['cart'] ?? $this->extractCartFromOrder($order);
            $selectedSales = $this->context['selected_sales'] ?? [];
            $detailSalesman = $this->resolveSalesmanList($selectedSales, $order);

            $detailItems = [];
            foreach ($cartItems as $item) {
                $rawSns = $item['serial_numbers'] ?? [];
                $cleanSns = array_values(array_filter(array_map('trim', (array)$rawSns)));

                $detailSN = [];
                foreach ($cleanSns as $sn) {
                    $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                }

                $itemData = [
                    'itemNo' => !empty($item['sku']) ? $item['sku'] : 'ITEM-UNKNOWN',
                    'warehouseName' => $accurateWarehouseName,
                    'unitPrice' => (float)$item['price'],
                    'quantity' => (float)$item['qty'],
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

            $buConfig = BusinessUnit::where('code', $dbSource)->first();
            $isTaxable = $buConfig ? (bool)$buConfig->is_taxable : false;

            $transDate = !empty($this->context['order_date'])
                ? Carbon::parse($this->context['order_date'])->format('d/m/Y')
                : ($order->order_date ? Carbon::parse($order->order_date)->format('d/m/Y') : now()->format('d/m/Y'));

            $siData = [
                'customerNo' => $customerUser ? $customerUser->getAccurateCustomerNo($dbSource) : 'UMUM',
                'branchName' => $accurateBranchName,
                'detailItem' => $detailItems,
                'inclusiveTax' => $isTaxable,
                'transDate' => $transDate,
                'taxable' => $isTaxable,
                'useTax1' => $isTaxable,
                'description' => $this->context['notes'] ?? $order->notes ?? ''
            ];

            $siResult = $accurateService->postSalesInvoice($siData, $dbSource);
            if (isset($siResult['r']['number'])) {
                $order->update(['accurate_invoice_no' => $siResult['r']['number']]);
                OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_INVOICE',
                    'doc_number' => $siResult['r']['number'],
                    'accurate_id' => $siResult['r']['id'] ?? null,
                    'amount' => $order->grand_total,
                    'status' => 'SUCCESS',
                ]);
            } else {
                throw new Exception('Gagal membuat Faktur Penjualan (SI) Piutang Accurate: ' . ($siResult['d'][0] ?? json_encode($siResult)));
            }
        }
    }

    /**
     * 4. Mode PIUTANG_SETTLEMENT (Pelunasan Piutang Lama: Hanya Sales Receipts)
     */
    protected function handlePiutangSettlement(
        Order $order,
        AccurateService $accurateService,
        string $dbSource,
        string $branchName
    ): void {
        if (!$order->accurate_receipt_no && $order->accurate_invoice_no) {
            $payments = $this->context['payments'] ?? $this->extractPaymentsFromOrder($order);
            $srNumbers = [];

            foreach ($payments as $payment) {
                $pm = PaymentMethod::find($payment['payment_method_id'] ?? null);
                if (!$pm || !empty($pm->accurate_customer_no)) {
                    continue; // Skip finance
                }

                $rate = !empty($payment['payment_method_rate_id']) ? PaymentMethodRate::find($payment['payment_method_rate_id']) : null;
                $pct = $this->getMdrPercentage($payment);
                $rowBaseAmount = (float)($payment['amount'] ?? 0);
                $rowMdr = $pct > 0 ? round($rowBaseAmount * $pct / 100, 0) : 0;
                $netReceiptAmount = $rowBaseAmount - $rowMdr;

                $detailDiscounts = [];
                if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                    $detailDiscounts[] = [
                        'accountNo' => $rate->accurate_account_no,
                        'amount' => (float)$rowMdr,
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
                    'customerNo' => $order->user ? $order->user->getAccurateCustomerNo($dbSource) : 'UMUM',
                    'branchName' => $branchName,
                    'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                    'receiptAmount' => (float)$netReceiptAmount,
                    'chequeAmount' => (float)$netReceiptAmount,
                    'transDate' => now()->format('d/m/Y'),
                    'detailInvoice' => [$detailInvoiceItem],
                    'description' => 'Pelunasan Piutang POS'
                ];

                $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                if (isset($srResult['r']['number'])) {
                    $srNumbers[] = $srResult['r']['number'];
                    OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_RECEIPT',
                        'doc_number' => $srResult['r']['number'],
                        'accurate_id' => $srResult['r']['id'] ?? null,
                        'amount' => (float)$netReceiptAmount,
                        'status' => 'SUCCESS',
                    ]);
                } else {
                    Log::channel('pos_accurate')->error("Gagal post SR Pelunasan Piutang Order #{$order->order_number}: " . json_encode($srResult));
                }
            }

            if (!empty($srNumbers)) {
                $order->update(['accurate_receipt_no' => implode(', ', $srNumbers)]);
            }
        }
    }

    /**
     * Helper untuk menghitung persentase MDR suatu baris pembayaran
     */
    protected function getMdrPercentage(array $payment): float
    {
        $pmId = $payment['payment_method_id'] ?? null;
        $rateId = $payment['payment_method_rate_id'] ?? null;

        if (!$pmId) return 0;

        if ($rateId) {
            $rate = PaymentMethodRate::find($rateId);
            return $rate ? (float)$rate->mdr_percentage : 0;
        }

        $pm = PaymentMethod::find($pmId);
        return $pm ? (float)$pm->mdr_percentage : 0;
    }

    /**
     * Helper untuk menentukan payment finance jika ada
     */
    protected function resolveFinancePayment(array $payments): ?PaymentMethod
    {
        foreach ($payments as $payment) {
            $pm = PaymentMethod::find($payment['payment_method_id'] ?? null);
            if ($pm && !empty($pm->accurate_customer_no)) {
                return $pm;
            }
        }
        return null;
    }

    /**
     * Helper untuk mendapatkan daftar employee number sales
     */
    protected function resolveSalesmanList(array $selectedSales, Order $order): array
    {
        $salesmen = [];
        foreach ($selectedSales as $sales) {
            if (!empty($sales['employee_no'])) {
                $salesmen[] = (string)$sales['employee_no'];
            }
        }

        if (empty($salesmen) && $order->salesBy && !empty($order->salesBy->employee_no)) {
            $salesmen[] = (string)$order->salesBy->employee_no;
        }

        return $salesmen;
    }

    /**
     * Fallback untuk mengekstrak payments dari model Order jika context kosong
     */
    protected function extractPaymentsFromOrder(Order $order): array
    {
        $payments = [];
        foreach ($order->payments as $payment) {
            $payments[] = [
                'payment_method_id' => $payment->payment_method_id,
                'payment_method_rate_id' => $payment->payment_method_rate_id,
                'amount' => (float)$payment->amount,
                'no_kontrak' => $payment->no_kontrak,
            ];
        }
        return $payments;
    }

    /**
     * Fallback untuk mengekstrak cart items dari model Order jika context kosong
     */
    protected function extractCartFromOrder(Order $order): array
    {
        $cart = [];
        foreach ($order->items as $item) {
            $sns = !empty($item->serial_number)
                ? array_filter(array_map('trim', explode(',', $item->serial_number)))
                : [];

            $cart[] = [
                'sku' => $item->variant->sku ?? $item->product_name ?? 'ITEM-UNKNOWN',
                'name' => $item->product_name,
                'price' => (float)$item->price_at_checkout,
                'qty' => (float)$item->qty,
                'discount_amount' => (int)$item->discount_amount,
                'promo_discount' => (int)$item->promo_discount_amount,
                'serial_numbers' => $sns,
                'condition' => $item->variant->condition ?? '',
                'project_number' => $item->project_number ?? '',
            ];
        }
        return $cart;
    }
}
