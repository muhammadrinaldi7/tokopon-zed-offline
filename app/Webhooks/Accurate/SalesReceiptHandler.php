<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Models\OrderPayment;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class SalesReceiptHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;

        if (!isset($payload['data']) || !is_array($payload['data'])) {
            Log::warning("Accurate Webhook Sales Receipt Data tidak memiliki array 'data': " . json_encode($payload));
            return;
        }

        $dbSource = $log->database_source ?? 'syihab';
        $accurateService = app(AccurateService::class);

        foreach ($payload['data'] as $dataItem) {
            // Accurate sends 'salesReceiptId' or 'salesReceiptNo'
            $salesReceiptId = $dataItem['salesReceiptId'] ?? null;
            $salesReceiptNo = $dataItem['salesReceiptNo'] ?? null;

            if (!$salesReceiptId && !$salesReceiptNo) {
                Log::warning("Accurate Webhook Sales Receipt Data tidak memiliki salesReceiptId atau salesReceiptNo: " . json_encode($dataItem));
                continue;
            }

            try {
                // get full detail to find the invoiceNo being paid
                $detail = null;
                if ($salesReceiptId) {
                    $detail = $accurateService->getDetailSalesReceipt($salesReceiptId, $dbSource);
                }

                if (!$detail) {
                    Log::warning("Accurate Webhook Sales Receipt Detail not found for id: {$salesReceiptId}");
                    continue;
                }

                $detailInvoices = $detail['detailInvoice'] ?? [];
                if (empty($detailInvoices)) {
                    Log::warning("Accurate Webhook Sales Receipt Detail does not contain detailInvoice: " . json_encode($detail));
                    continue;
                }

                foreach ($detailInvoices as $detailInvoice) {
                    $invoiceNo = $detailInvoice['invoice']['number'] ?? null;
                    $paymentAmount = (float) ($detailInvoice['paymentAmount'] ?? 0);

                    if (!$invoiceNo) {
                        continue;
                    }

                    Log::info("Processing Webhook Sales Receipt for Invoice: {$invoiceNo}, Amount: {$paymentAmount}");

                    // Find local order with this accurate_invoice_no
                    $order = Order::where('accurate_invoice_no', $invoiceNo)
                        ->orWhere('accurate_invoice_no', 'LIKE', '%' . $invoiceNo . '%')
                        ->first();

                    if (!$order) {
                        Log::warning("Order not found for accurate_invoice_no: {$invoiceNo}");
                        continue;
                    }

                    Log::info("Order found: ID {$order->id}. Updating accurate_receipt_no with {$salesReceiptNo}");

                    // Update accurate_receipt_no on Order if not already present
                    $existingReceipts = array_map('trim', explode(',', $order->accurate_receipt_no ?? ''));
                    if (!in_array($salesReceiptNo, $existingReceipts)) {
                        $newReceiptNo = empty($order->accurate_receipt_no) ? $salesReceiptNo : $order->accurate_receipt_no . ', ' . $salesReceiptNo;
                        $updated = $order->update(['accurate_receipt_no' => $newReceiptNo]);
                        Log::info("Order {$order->id} accurate_receipt_no updated to {$newReceiptNo}. Result: " . ($updated ? 'Success' : 'Failed'));
                    } else {
                        Log::info("Receipt No {$salesReceiptNo} already exists in Order {$order->id}");
                    }

                    // Find PENDING finance payments for this order
                    $pendingPayments = OrderPayment::where('order_id', $order->id)
                        ->where('status', 'PENDING')
                        ->get();

                    Log::info("Found {$pendingPayments->count()} PENDING payments for Order {$order->id}");

                    foreach ($pendingPayments as $payment) {
                        // Cek apakah payment method-nya finance (punya accurate_customer_no)
                        $pm = $payment->paymentMethod;
                        if ($pm && !empty($pm->accurate_customer_no)) {
                            $paymentUpdated = $payment->update([
                                'status' => 'PAID',
                                'paid_at' => now(),
                            ]);
                            Log::info("Updated OrderPayment {$payment->id} status to PAID (Finance Settled). Result: " . ($paymentUpdated ? 'Success' : 'Failed'));
                        } else {
                            Log::info("Skipped OrderPayment {$payment->id} because it is not a finance payment method (accurate_customer_no empty).");
                        }
                    }

                    // Record the OrderAccurateDoc
                    $existingDoc = OrderAccurateDoc::where('order_id', $order->id)
                        ->where('doc_type', 'SALES_RECEIPT')
                        ->where('doc_number', $salesReceiptNo)
                        ->first();

                    if (!$existingDoc) {
                        OrderAccurateDoc::create([
                            'order_id' => $order->id,
                            'doc_type' => 'SALES_RECEIPT',
                            'doc_number' => $salesReceiptNo,
                            'accurate_id' => $salesReceiptId,
                            'amount' => $paymentAmount,
                            'status' => 'SUCCESS',
                        ]);
                    }
                }

            } catch (\Exception $e) {
                Log::error("Error processing Sales Receipt Webhook for id {$salesReceiptId}: " . $e->getMessage());
            }
        }
    }
}
