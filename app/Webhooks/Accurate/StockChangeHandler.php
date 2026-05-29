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
        $itemNo = $payload['item_no'] ?? ($payload['itemNo'] ?? ($payload['no'] ?? null));
        $dbSource = $log->database_source;

        if (!$itemNo) {
            throw new \Exception("item_no is missing from payload");
        }

        // Logic sync yang dulunya ada di Controller dipindah ke sini
        $this->syncItemStockFromAccurate($itemNo, $dbSource);
    }

    private function syncItemStockFromAccurate($itemNo, $dbSource)
    {
        $service = app(AccurateService::class);
        $stockData = $service->getItemStockPerWarehouse($itemNo, $dbSource);

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
