<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use Illuminate\Support\Facades\Log;

class SalesInvoiceHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        // $payload = $log->payload;
        // Logic sinkronisasi Faktur Penjualan
        // Contoh: Update status pesanan di Tokopun jika Faktur dibuat/dibatalkan dari Accurate

        // Untuk sekarang, kita fallback sinkron stok
        // $stockHandler = new StockChangeHandler();
        // $stockHandler->handle($log);
        Log::info('SalesInvoiceHandler: ' . $log);
    }
}
