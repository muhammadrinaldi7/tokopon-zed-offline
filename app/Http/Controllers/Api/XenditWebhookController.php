<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function handleInvoiceCallback(Request $request, SettingService $settingService)
    {
        // 1. Verifikasi callback_token (Webhook Token) dari Xendit
        $xenditToken = $request->header('x-callback-token');
        $storedToken = $settingService->get('xendit_webhook_token');

        if (empty($storedToken) || $xenditToken !== $storedToken) {
            Log::warning('Xendit Webhook Mismatch Token', [
                'received' => $xenditToken,
            ]);
            return response()->json(['message' => 'Invalid Token'], 403);
        }

        // 2. Ambil data dari payload
        $payload = $request->all();
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$externalId) {
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        // 3. Update status OrderPayment
        $orderPayment = OrderPayment::where('xendit_external_id', $externalId)->first();

        if ($orderPayment) {
            $paymentStatus = $status; // PAID, EXPIRED, etc.
            
            $orderPayment->update([
                'status' => $paymentStatus,
                'payment_method' => $payload['payment_method'] ?? $orderPayment->payment_method,
                'paid_at' => isset($payload['paid_at']) ? \Carbon\Carbon::parse($payload['paid_at']) : null,
                'payment_payload' => array_merge((array)$orderPayment->payment_payload, $payload)
            ]);

            // 4. Update status Order utamanya
            if ($status === 'PAID' || $status === 'SETTLED') {
                $orderPayment->order->update([
                    'order_status' => 'PROCESSING'
                ]);

                // Jika order ini terkait Trade-In, tandai COMPLETED
                $tradeIn = \App\Models\TradeIn::where('order_id', $orderPayment->order_id)->first();
                if ($tradeIn && $tradeIn->status === 'PAYING') {
                    $tradeIn->update(['status' => 'COMPLETED']);
                }
            } elseif ($status === 'EXPIRED') {
                $order = $orderPayment->order;
                if ($order) {
                    try {
                        app(\App\Services\MobileOrderService::class)->cancelOrExpireOrder($order, 'PAYMENT_EXPIRED');
                    } catch (\Throwable $e) {
                        $order->update(['order_status' => 'CANCELLED']);
                    }
                }
            }
        } else {
             Log::warning('Xendit Webhook OrderPayment Not Found', [
                'external_id' => $externalId,
            ]);
        }

        return response()->json(['message' => 'Successfully processed']);
    }
}
