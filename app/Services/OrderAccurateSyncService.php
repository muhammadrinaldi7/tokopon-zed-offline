<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\BusinessUnitProject;
use App\Models\Employe;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Models\OrderAccurateSyncLog;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodRate;
use App\Models\ProductAccurate;
use App\Models\SecondProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderAccurateSyncService
{
    protected AccurateService $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    /**
     * Eksekusi sinkronisasi faktur (SI) dan penerimaan (SR) penjualan ke Accurate
     * 
     * @param Order|int $order
     * @param string $triggerType REALTIME_POS | AUTO_RETRY_SCHEDULE | MANUAL_ADMIN
     * @param int|null $executedBy ID User admin yang memicu (jika MANUAL_ADMIN)
     * @return array
     */
    public function syncOrder($order, string $triggerType = 'MANUAL_ADMIN', ?int $executedBy = null): array
    {
        if (is_numeric($order)) {
            $order = Order::find($order);
        }

        if (!$order) {
            return [
                'success' => false,
                'error' => 'Pesanan tidak ditemukan.',
                'invoice_no' => null,
                'receipt_no' => null,
            ];
        }

        // Muat seluruh relasi yang dibutuhkan
        $order->loadMissing([
            'items.variant',
            'payments.paymentMethod',
            'payments.paymentMethodRate',
            'user',
            'branch',
            'handledBy.warehouse',
            'handledBy.branch',
            'warehouse',
            'accurateDocs',
            'customerDeposits',
            'businessUnit',
            'salesBy',
        ]);

        if ($order->order_status !== 'COMPLETED') {
            return [
                'success' => false,
                'error' => "Hanya pesanan berstatus COMPLETED yang dapat disinkronkan ke Accurate (Status saat ini: {$order->order_status}).",
                'invoice_no' => $order->accurate_invoice_no,
                'receipt_no' => $order->accurate_receipt_no,
            ];
        }

        // Jika sudah memiliki nomor faktur dan nomor tanda terima, anggap sudah selesai
        if (!empty($order->accurate_invoice_no) && !empty($order->accurate_receipt_no)) {
            $order->update([
                'accurate_sync_status' => 'SYNCED',
                'accurate_synced_at' => $order->accurate_synced_at ?: now(),
                'accurate_sync_last_error' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Pesanan sudah tersinkronisasi sepenuhnya ke Accurate.',
                'invoice_no' => $order->accurate_invoice_no,
                'receipt_no' => $order->accurate_receipt_no,
            ];
        }

        $currentAttempt = (int)$order->accurate_sync_attempts + 1;
        $siDataSnapshot = null;
        $srDataSnapshots = [];
        $errorMessage = null;

        try {
            $bu = $order->businessUnit ?: BusinessUnit::find($order->business_unit_id);
            $dbSource = $bu ? $bu->code : 'syihab';

            // 1. SINKRONISASI DATA PELANGGAN
            $customerUser = $order->user;
            if ($customerUser) {
                try {
                    $this->accurateService->syncCustomer($customerUser, $dbSource);
                    $customerUser->refresh();
                } catch (Exception $e) {
                    Log::channel('pos_accurate')->warning("Gagal auto-sync customer Order #{$order->order_number}: " . $e->getMessage());
                }
            }

            // Tentukan nomor pelanggan faktur (apakah via Leasing/Finance atau Pelanggan Umum/User)
            $financePayment = null;
            foreach ($order->payments as $payment) {
                if ($payment->paymentMethod && !empty($payment->paymentMethod->accurate_customer_no)) {
                    $financePayment = $payment->paymentMethod;
                    break;
                }
            }

            $invoiceCustomerNo = $financePayment
                ? $financePayment->accurate_customer_no
                : ($customerUser ? $customerUser->getAccurateCustomerNo($dbSource) : 'PELANGGAN-UMUM');

            if (empty($invoiceCustomerNo)) {
                $invoiceCustomerNo = 'PELANGGAN-UMUM';
            }

            $accurateBranchName = $order->branch->name ?? 'Banjarbaru';
            $accurateWarehouseName = $order->handledBy?->warehouse?->name ?? ($order->warehouse?->name ?? 'Head Office');

            // 2. PROSES SALES INVOICE (FAKTUR PENJUALAN)
            if (empty($order->accurate_invoice_no)) {
                $detailItems = [];
                foreach ($order->items as $item) {
                    $sku = 'ITEM-UNKNOWN';
                    $projectNo = '';
                    $condition = '';

                    if ($item->product_variant_type == ProductAccurate::class) {
                        $product = ProductAccurate::find($item->product_variant_id);
                        if ($product) {
                            $sku = $product->item_no;
                            $buId = $order->business_unit_id ?? ($product->business_unit_id ?? 1);
                            $projectNo = BusinessUnitProject::getProjectNoByBusinessUnit($buId, $product->proyek, $product->proyek ?? '');
                        }
                    } elseif ($item->product_variant_type == SecondProduct::class) {
                        $product = SecondProduct::find($item->product_variant_id);
                        if ($product) {
                            $sku = $product->sku;
                            $condition = $product->condition ?? '';
                        }
                    } elseif ($item->variant) {
                        $sku = $item->variant->item_no ?? ($item->product_name ?? 'ITEM-UNKNOWN');
                    }

                    // SN
                    $rawSns = array_values(array_filter(array_map('trim', explode(',', $item->serial_number ?? ''))));
                    $detailSN = [];
                    foreach ($rawSns as $sn) {
                        if (!empty($sn)) {
                            $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                        }
                    }

                    // Salesman
                    $detailSalesman = [];
                    $salesEmployeId = $order->sales_id ?? ($order->salesBy?->id ?? null);
                    if ($salesEmployeId) {
                        $sales = Employe::find($salesEmployeId);
                        if ($sales && !empty($sales->employee_no)) {
                            $detailSalesman[] = (string) $sales->employee_no;
                        }
                    }

                    $itemData = [
                        'itemNo' => $sku,
                        'warehouseName' => $accurateWarehouseName,
                        'unitPrice' => (float) $item->price_at_checkout,
                        'quantity' => (float) $item->qty,
                        'itemCashDiscount' => (float) (($item->discount_amount ?? 0) + ($item->promo_discount_amount ?? 0)),
                        'salesmanListNumber' => $detailSalesman,
                        'projectNo' => $projectNo,
                    ];

                    // Departemen produk second (GSK)
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

                // Link Delivery Order (DO) atau Sales Order (SO) jika ada
                $doDoc = $order->accurateDocs()
                    ->where('doc_type', 'DELIVERY_ORDER')
                    ->where('status', 'SUCCESS')
                    ->first();

                if ($doDoc && $doDoc->doc_number && !$financePayment) {
                    foreach ($detailItems as &$itemRow) {
                        $itemRow['deliveryOrderNumber'] = $doDoc->doc_number;
                    }
                    unset($itemRow);
                } elseif ($order->accurate_so_number && !$financePayment) {
                    foreach ($detailItems as &$itemRow) {
                        $itemRow['salesOrderNumber'] = $order->accurate_so_number;
                    }
                    unset($itemRow);
                }

                $isTaxable = $bu ? (bool) $bu->is_taxable : false;

                $siData = [
                    'customerNo' => $invoiceCustomerNo,
                    'branchName' => $accurateBranchName,
                    'detailItem' => $detailItems,
                    'transDate' => $order->order_date ? Carbon::parse($order->order_date)->format('d/m/Y') : now()->format('d/m/Y'),
                    'inclusiveTax' => $isTaxable,
                    'taxable' => $isTaxable,
                    'useTax1' => $isTaxable,
                    'description' => "Pesanan #{$order->order_number}" . ($order->notes ? " - {$order->notes}" : ''),
                ];

                // Kumpulkan Down Payment (DP) jika ada
                $validDpInvoices = [];
                $usedDpInvoiceNumbers = [];

                // 1. Dari Sales Order (SO)
                $soDpDocs = $order->accurateDocs()
                    ->where('doc_type', 'DP_INVOICE')
                    ->where('status', 'SUCCESS')
                    ->get();

                foreach ($soDpDocs as $dpInv) {
                    $hasReceipt = $order->accurateDocs()
                        ->where('doc_type', 'DP_RECEIPT')
                        ->where('status', 'SUCCESS')
                        ->where('created_at', '>=', $dpInv->created_at)
                        ->exists();

                    if ($hasReceipt && !in_array($dpInv->doc_number, $usedDpInvoiceNumbers)) {
                        $validDpInvoices[] = [
                            'invoiceNumber' => $dpInv->doc_number,
                            'paymentAmount' => (float) $dpInv->amount,
                        ];
                        $usedDpInvoiceNumbers[] = $dpInv->doc_number;
                    }
                }

                // 2. Dari CustomerDeposit
                if (empty($usedDpInvoiceNumbers)) {
                    $originDeposits = $order->customerDeposits()
                        ->whereNotNull('accurate_invoice_no')
                        ->get();

                    foreach ($originDeposits as $dep) {
                        if (!in_array($dep->accurate_invoice_no, $usedDpInvoiceNumbers) && (float)$dep->amount > 0) {
                            $validDpInvoices[] = [
                                'invoiceNumber' => $dep->accurate_invoice_no,
                                'paymentAmount' => (float) $dep->amount,
                            ];
                            $usedDpInvoiceNumbers[] = $dep->accurate_invoice_no;
                        }
                    }
                }

                // 3. Dari CustomerDepositUsage
                $usages = \App\Models\CustomerDepositUsage::with('customerDeposit')->where('order_id', $order->id)->get();
                foreach ($usages as $usage) {
                    if ($usage->customerDeposit && !empty($usage->customerDeposit->accurate_invoice_no)) {
                        $invNo = $usage->customerDeposit->accurate_invoice_no;
                        if (!in_array($invNo, $usedDpInvoiceNumbers)) {
                            $validDpInvoices[] = [
                                'invoiceNumber' => $invNo,
                                'paymentAmount' => (float) $usage->amount_used,
                            ];
                            $usedDpInvoiceNumbers[] = $invNo;
                        }
                    }
                }

                if (count($validDpInvoices) > 0 && !$financePayment) {
                    $siData['detailDownPayment'] = $validDpInvoices;
                }

                // MDR Expenses
                $mdrExpenses = $order->getMdrExpenseDetails();
                if (!empty($mdrExpenses)) {
                    $siData['detailExpense'] = $mdrExpenses;
                }

                // Header Discount
                $headerDiscount = (float) (($order->coupon_discount_amount ?? 0) + ($order->loyalty_discount_amount ?? 0));
                if ($headerDiscount > 0) {
                    $siData['cashDiscount'] = $headerDiscount;
                }

                $siDataSnapshot = $siData;
                Log::channel('pos_accurate')->info("OrderAccurateSyncService SI Payload Order #{$order->order_number}: " . json_encode($siData));

                $siResult = $this->accurateService->postSalesInvoice($siData, $dbSource);

                if (isset($siResult['r']['number'])) {
                    $invoiceNumber = $siResult['r']['number'];
                    $order->update(['accurate_invoice_no' => $invoiceNumber]);

                    OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_INVOICE',
                        'doc_number' => $invoiceNumber,
                        'accurate_id' => $siResult['r']['id'] ?? null,
                        'amount' => (float) $order->grand_total,
                        'status' => 'SUCCESS',
                        'payload' => $siData,
                    ]);

                    \App\Models\CustomerDeposit::where('origin_order_id', $order->id)
                        ->where('status', 'AVAILABLE')
                        ->update(['status' => 'USED', 'balance' => 0]);
                } else {
                    $apiError = $siResult['d'][0] ?? ($siResult['message'] ?? json_encode($siResult));
                    throw new Exception("Gagal membuat Faktur Penjualan (SI) di Accurate: {$apiError}");
                }
            }

            // 3. PROSES SALES RECEIPT (PENERIMAAN PENJUALAN)
            if (empty($order->accurate_receipt_no) && !empty($order->accurate_invoice_no)) {
                $srNumbers = [];

                foreach ($order->payments as $payment) {
                    $rowTotal = (float)$payment->amount;
                    if ($rowTotal <= 0) continue;

                    // Lewati DP karena sudah dipotong langsung di Faktur Penjualan (detailDownPayment)
                    $extId = (string)($payment->xendit_external_id ?? '');
                    if (str_starts_with($extId, 'DP-SO-') || str_starts_with($extId, 'DP-USE-')) {
                        continue;
                    }

                    $pm = $payment->paymentMethod;
                    if (!$pm || !empty($pm->accurate_customer_no)) {
                        continue;
                    }

                    $pct = 0;
                    if ($payment->payment_method_rate_id) {
                        $rate = PaymentMethodRate::find($payment->payment_method_rate_id);
                        if ($rate) $pct = (float) $rate->mdr_percentage;
                    } elseif ($pm) {
                        $pct = (float) $pm->mdr_percentage;
                    }
                    $rowMdr = $pct > 0 ? round($rowTotal * $pct / 100, 0) : 0;
                    $netReceiptAmount = $rowTotal - $rowMdr;

                    $srData = [
                        'customerNo' => $invoiceCustomerNo,
                        'branchName' => $accurateBranchName,
                        'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                        'receiptAmount' => (float) $netReceiptAmount,
                        'chequeAmount' => (float) $netReceiptAmount,
                        'transDate' => $order->order_date ? Carbon::parse($order->order_date)->format('d/m/Y') : now()->format('d/m/Y'),
                        'detailInvoice' => [
                            [
                                'invoiceNo' => $order->accurate_invoice_no,
                                'paymentAmount' => $netReceiptAmount,
                            ]
                        ],
                        'description' => "Pelunasan Pesanan #{$order->order_number}",
                    ];

                    $srDataSnapshots[] = $srData;
                    Log::channel('pos_accurate')->info("OrderAccurateSyncService SR Payload Order #{$order->order_number}: " . json_encode($srData));

                    $srResult = $this->accurateService->postSalesReceipt($srData, $dbSource);

                    if (isset($srResult['r']['number'])) {
                        $srNo = $srResult['r']['number'];
                        $srNumbers[] = $srNo;

                        OrderAccurateDoc::create([
                            'order_id' => $order->id,
                            'doc_type' => 'SALES_RECEIPT',
                            'doc_number' => $srNo,
                            'accurate_id' => $srResult['r']['id'] ?? null,
                            'amount' => (float) $netReceiptAmount,
                            'status' => 'SUCCESS',
                            'payload' => $srData,
                        ]);
                    } else {
                        $apiError = $srResult['d'][0] ?? ($srResult['message'] ?? json_encode($srResult));
                        throw new Exception("Gagal membuat Penerimaan Penjualan (SR) di Accurate: {$apiError}");
                    }
                }

                if (!empty($srNumbers)) {
                    $order->update(['accurate_receipt_no' => implode(', ', $srNumbers)]);
                } elseif (!empty($validDpInvoices)) {
                    $totalDpAmount = array_sum(array_column($validDpInvoices, 'paymentAmount'));
                    if ($totalDpAmount >= (float)$order->grand_total) {
                        $dpReceiptDoc = $order->accurateDocs()->where('doc_type', 'DP_RECEIPT')->where('status', 'SUCCESS')->first();
                        $order->update(['accurate_receipt_no' => $dpReceiptDoc->doc_number ?? 'LUNAS-DP']);
                    }
                }
            }

            // SUKSES
            $order->update([
                'accurate_sync_status' => 'SYNCED',
                'accurate_sync_attempts' => $currentAttempt,
                'accurate_sync_last_error' => null,
                'accurate_synced_at' => now(),
            ]);

            OrderAccurateSyncLog::create([
                'order_id' => $order->id,
                'business_unit_id' => $order->business_unit_id,
                'branch_id' => $order->branch_id,
                'trigger_type' => $triggerType,
                'status' => 'SUCCESS',
                'attempt_number' => $currentAttempt,
                'accurate_invoice_no' => $order->accurate_invoice_no,
                'accurate_receipt_no' => $order->accurate_receipt_no,
                'error_category' => null,
                'error_message' => null,
                'payload_snapshot' => [
                    'si' => $siDataSnapshot,
                    'sr' => $srDataSnapshots,
                ],
                'executed_by' => $executedBy,
            ]);

            return [
                'success' => true,
                'invoice_no' => $order->accurate_invoice_no,
                'receipt_no' => $order->accurate_receipt_no,
                'error' => null,
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Log::channel('pos_accurate')->error("OrderAccurateSyncService Error Order #{$order->order_number}: " . $errorMessage);

            $errorCategory = OrderAccurateSyncLog::categorizeError($errorMessage);

            // Jika error adalah kendala data permanen (misal stok tidak mencukupi, akun tidak ada) atau sudah mencoba >= 5 kali
            $isPermanentDataError = in_array($errorCategory, ['INSUFFICIENT_STOCK', 'ITEM_NOT_FOUND', 'ACCOUNT_NOT_FOUND', 'AUTH_ERROR']);
            $newStatus = ($isPermanentDataError || $currentAttempt >= 5) ? 'FAILED' : 'PENDING';

            $order->update([
                'accurate_sync_status' => $newStatus,
                'accurate_sync_attempts' => $currentAttempt,
                'accurate_sync_last_error' => $errorMessage,
            ]);

            OrderAccurateSyncLog::create([
                'order_id' => $order->id,
                'business_unit_id' => $order->business_unit_id,
                'branch_id' => $order->branch_id,
                'trigger_type' => $triggerType,
                'status' => 'FAILED',
                'attempt_number' => $currentAttempt,
                'accurate_invoice_no' => $order->accurate_invoice_no,
                'accurate_receipt_no' => $order->accurate_receipt_no,
                'error_category' => $errorCategory,
                'error_message' => $errorMessage,
                'payload_snapshot' => [
                    'si' => $siDataSnapshot,
                    'sr' => $srDataSnapshots,
                ],
                'executed_by' => $executedBy,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_category' => $errorCategory,
                'invoice_no' => $order->accurate_invoice_no,
                'receipt_no' => $order->accurate_receipt_no,
            ];
        }
    }
}

