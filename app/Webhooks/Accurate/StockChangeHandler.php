<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class StockChangeHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;
        $dbSource = $log->database_source;

        // Accurate mengirimkan detail event di dalam array 'data'
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $itemData) {
                // Biasanya ada key 'itemNo', jika tidak ada cari alternatif lain 
                $itemNo = $itemData['itemNo'] ?? ($itemData['no'] ?? null);
                $warehouseName = $itemData['warehouseName'] ?? null;
                if ($itemNo) {
                    // Eksekusi logic sinkronisasi stock per item yang ditemukan
                    $this->syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource);
                } else {
                    Log::warning("Accurate Webhook Payload Data tidak memiliki itemNo: " . json_encode($itemData));
                }
            }
        } else {
            throw new \Exception("Format payload tidak dikenali, array 'data' tidak ditemukan.");
        }
    }

    private function syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource)
    {
        // 1. Validasi DB Lokal: Pastikan Gudang ada di Laravel Anda
        $warehouse = Warehouse::where('name', $warehouseName)->first();
        if (!$warehouse) return;

        // 2. Validasi DB Lokal: Pastikan Varian (SKU) ada di Laravel Anda
        $variant = ProductVariant::where('sku', $itemNo)->first()
            ?? SecondProductVariant::where('sku', $itemNo)->first();
        if (!$variant) return;

        // 3. Tembak API Accurate (Hanya dieksekusi jika gudang & produk valid)
        $service = app(AccurateService::class);
        $stockData = $service->getStockPerItemWarehouse($itemNo, $warehouseName, $dbSource);

        // --- PERBAIKAN DI SINI ---
        // Langsung ambil nilai availableStock. Jika tidak ada/null, jadikan 0.
        $qty = $stockData['availableStock'] ?? 0;

        // 4. Update Stok di Database Laravel
        WarehouseStock::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'variant_id'   => $variant->id,
                'variant_type' => get_class($variant),
            ],
            [
                // Lakukan casting ke (int) agar 5.000000 menjadi 5 di database Laravel Anda
                'stock'        => (int) $qty
            ]
        );
    }
}
