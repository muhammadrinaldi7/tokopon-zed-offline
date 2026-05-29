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
                
                if ($itemNo) {
                    // Eksekusi logic sinkronisasi stock per item yang ditemukan
                    $this->syncItemStockFromAccurate($itemNo, $dbSource);
                } else {
                    Log::warning("Accurate Webhook Payload Data tidak memiliki itemNo: " . json_encode($itemData));
                }
            }
        } else {
            throw new \Exception("Format payload tidak dikenali, array 'data' tidak ditemukan.");
        }
    }

    private function syncItemStockFromAccurate($itemNo, $dbSource)
    {
        $service = app(AccurateService::class);
        // Karena ini trigger dari 1 item, kita ambil stok khusus item tersebut saja dari Accurate
        $stockData = $service->getStockPerItem($itemNo, $dbSource);

        foreach ($stockData as $stockItem) {
            $warehouseName = $stockItem['warehouseName'] ?? ($stockItem['warehouse']['name'] ?? null);
            $qty = $stockItem['quantity'] ?? ($stockItem['qty'] ?? 0);

            if (!$warehouseName) continue;

            $warehouse = Warehouse::where('name', $warehouseName)->first();
            if (!$warehouse) continue;

            $variant = ProductVariant::where('sku', $itemNo)->first() 
                ?? SecondProductVariant::where('sku', $itemNo)->first();

            if ($variant) {
                WarehouseStock::updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'variant_id' => $variant->id,
                        'variant_type' => get_class($variant),
                    ],
                    [
                        'stock' => $qty
                    ]
                );
            }
        }
    }
}
