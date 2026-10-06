<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Services\AccurateService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOrderToAccurateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    /**
     * Maksimal pengulangan jika terjadi error API atau jaringan.
     */
    public int $tries = 3;

    /**
     * Timeout job dalam detik.
     */
    public int $timeout = 120;

    /**
     * Jeda waktu sebelum retry (detik).
     */
    public int $backoff = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(AccurateService $accurateService): void
    {
        $order = Order::with([
            'items.variant',
            'user',
            'payments.paymentMethod',
            'payments.paymentMethodRate',
            'businessUnit',
            'warehouse',
            'branch'
        ])->find($this->orderId);

        if (!$order) {
            Log::channel('pos_accurate')->warning("SyncOrderToAccurateJob: Order #{$this->orderId} tidak ditemukan.");
            return;
        }

        if ($order->order_status !== 'COMPLETED') {
            Log::channel('pos_accurate')->info("SyncOrderToAccurateJob: Order #{$order->order_number} belum COMPLETED ({$order->order_status}), skip Accurate sync.");
            return;
        }

        $databaseSource = $order->businessUnit->code ?? 'syihab';
        $customerUser = $order->user;

        // 1. Sync Customer ke Accurate jika ada user
        if ($customerUser) {
            try {
                $accurateService->syncCustomer($customerUser, $databaseSource);
                $customerUser->refresh();
            } catch (Exception $e) {
                Log::channel('pos_accurate')->warning("Sync Customer Error for Order #{$order->order_number}: " . $e->getMessage());
            }
        }

        $customerNo = $customerUser
            ? $customerUser->getAccurateCustomerNo($databaseSource)
            : 'PELANGGAN-UMUM';

        $branchName = $order->branch?->name ?? 'Banjarbaru';
        $warehouseName = $order->warehouse?->name ?? 'Head Office';

        // 2. Buat Sales Invoice (jika belum ada)
        if (!$order->accurate_invoice_no) {
            $detailItems = [];

            foreach ($order->items as $item) {
                $rawSns = !empty($item->serial_number) ? explode(',', $item->serial_number) : [];
                $cleanSns = array_values(array_filter(array_map('trim', $rawSns)));

                $detailSN = [];
                if (!empty($cleanSns)) {
                    foreach ($cleanSns as $sn) {
                        $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                    }
                }

                $itemSku = $item->variant?->item_no ?? ($item->product_name ?? 'ITEM-UNKNOWN');

                $itemRow = [
                    'itemNo' => $itemSku,
                    'warehouseName' => $warehouseName,
                    'unitPrice' => (float) $item->price_at_checkout,
                    'quantity' => (float) $item->qty,
                    'itemCashDiscount' => (float) (($item->discount_amount ?? 0) + ($item->promo_discount_amount ?? 0)),
                ];

                if (!empty($detailSN)) {
                    $itemRow['detailSerialNumber'] = $detailSN;
                }

                $detailItems[] = $itemRow;
            }

            $isTaxable = (bool) ($order->businessUnit?->is_taxable ?? false);

            $siData = [
                'customerNo' => $customerNo,
                'branchName' => $branchName,
                'detailItem' => $detailItems,
                'transDate' => $order->order_date ? $order->order_date->format('d/m/Y') : now()->format('d/m/Y'),
                'inclusiveTax' => $isTaxable,
                'taxable' => $isTaxable,
                'useTax1' => $isTaxable,
                'description' => "Pesanan Mobile App #{$order->order_number}" . ($order->notes ? " - {$order->notes}" : ''),
            ];

            try {
                $siResult = $accurateService->postSalesInvoice($siData, $databaseSource);

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
                    ]);

                    Log::channel('pos_accurate')->info("Sales Invoice berhasil dibuat untuk Order #{$order->order_number}: {$invoiceNumber}");
                }
            } catch (Exception $e) {
                Log::channel('pos_accurate')->error("Gagal membuat Sales Invoice untuk Order #{$order->order_number}: " . $e->getMessage());
                throw $e;
            }
        }

        // 3. Buat Sales Receipt untuk pembayaran (jika invoice sudah ada dan receipt belum ada)
        if ($order->accurate_invoice_no && !$order->accurate_receipt_no) {
            $srNumbers = [];

            $paidPayments = $order->payments()->where('status', 'PAID')->get();

            foreach ($paidPayments as $payment) {
                $pm = $payment->paymentMethod;
                $bankNo = $pm?->accurate_bank_no ?? '110101';

                $srData = [
                    'customerNo' => $customerNo,
                    'branchName' => $branchName,
                    'bankNo' => $bankNo,
                    'receiptAmount' => (float) $payment->amount,
                    'chequeAmount' => (float) $payment->amount,
                    'transDate' => $payment->paid_at ? $payment->paid_at->format('d/m/Y') : now()->format('d/m/Y'),
                    'detailInvoice' => [
                        [
                            'invoiceNo' => $order->accurate_invoice_no,
                            'paymentAmount' => (float) $payment->amount,
                        ]
                    ],
                    'description' => "Pelunasan Mobile App #{$order->order_number}"
                ];

                try {
                    $srResult = $accurateService->postSalesReceipt($srData, $databaseSource);

                    if (isset($srResult['r']['number'])) {
                        $srNo = $srResult['r']['number'];
                        $srNumbers[] = $srNo;

                        OrderAccurateDoc::create([
                            'order_id' => $order->id,
                            'doc_type' => 'SALES_RECEIPT',
                            'doc_number' => $srNo,
                            'accurate_id' => $srResult['r']['id'] ?? null,
                            'amount' => (float) $payment->amount,
                            'status' => 'SUCCESS',
                        ]);

                        Log::channel('pos_accurate')->info("Sales Receipt berhasil dibuat untuk Order #{$order->order_number}: {$srNo}");
                    }
                } catch (Exception $e) {
                    Log::channel('pos_accurate')->error("Gagal membuat Sales Receipt untuk Order #{$order->order_number}: " . $e->getMessage());
                    // Jangan rethrow agar retry hanya jika invoice gagal
                }
            }

            if (!empty($srNumbers)) {
                $order->update(['accurate_receipt_no' => implode(', ', $srNumbers)]);
            }
        }
    }
}
