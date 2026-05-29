<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;

class ItemSaveHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        // $payload = $log->payload;
        // Logic sinkronisasi Item (Barang) seperti perubahan nama, harga modal, dll.
        // Untuk saat ini bisa kita pass ke StockChangeHandler jika kebutuhannya hanya stok
        // Atau buat implementasi khusus untuk update detail Item (ProductVariant) di sini.
        
        // Memanggil fungsi sync stock yang ada di StockChangeHandler
        $stockHandler = new StockChangeHandler();
        $stockHandler->handle($log);
    }
}
