<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\WarehouseStock;

class ItemQuantityHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;

        if (isset($payload['data'])) {
            foreach ($payload['data'] as $itemData) {
                $itemNo = $itemData['itemNo'] ?? null;
                $warehouseName = $itemData['warehouseName'] ?? null;
                // INI HARTA KARUNNYA: Sisa stok absolut langsung dari payload
                $newQuantity = $itemData['quantity'] ?? null;

                if ($itemNo && $warehouseName && $newQuantity !== null) {
                    $this->updateLocalStock($itemNo, $warehouseName, $newQuantity);
                }
            }
        }
    }

    private function updateLocalStock($itemNo, $warehouseName, $newQuantity)
    {
        $warehouse = Warehouse::where('name', $warehouseName)->first();
        if (!$warehouse) return;

        $variant = ProductVariant::where('sku', $itemNo)->first()
            ?? SecondProductVariant::where('sku', $itemNo)->first();
        if (!$variant) return;

        // LANGSUNG SIMPAN KE DB LOKAL (0 Detik, Tanpa HTTP Request ke Accurate!)
        WarehouseStock::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'variant_id'   => $variant->id,
                'variant_type' => get_class($variant),
            ],
            [
                'stock'        => (int) $newQuantity
            ]
        );
    }
}
