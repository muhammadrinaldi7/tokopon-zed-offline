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
        $skusToSyncSn = [];

        if (isset($payload['data'])) {
            foreach ($payload['data'] as $itemData) {
                $itemNo = $itemData['itemNo'] ?? null;
                $warehouseName = $itemData['warehouseName'] ?? null;
                // INI HARTA KARUNNYA: Sisa stok absolut langsung dari payload
                $newQuantity = $itemData['quantity'] ?? null;

                if ($itemNo && $warehouseName && $newQuantity !== null) {
                    $hasSn = $this->updateLocalStock($itemNo, $warehouseName, $newQuantity);
                    if ($hasSn) {
                        $skusToSyncSn[$itemNo] = true;
                    }
                }
            }
        }

        // Jalankan sinkronisasi SN untuk SKU yang stoknya berubah
        if (!empty($skusToSyncSn)) {
            $syncService = app(\App\Services\SerialNumberSyncService::class);
            foreach (array_keys($skusToSyncSn) as $sku) {
                try {
                    $syncService->syncFromAccurate($sku, $log->database_source);
                    \Illuminate\Support\Facades\Log::info("Webhook SN Sync sukses untuk SKU: {$sku}");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Webhook SN Sync failed for SKU {$sku}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @return bool True jika varian membutuhkan Serial Number, False jika tidak
     */
    private function updateLocalStock($itemNo, $warehouseName, $newQuantity): bool
    {
        $warehouse = Warehouse::where('name', $warehouseName)->first();
        if (!$warehouse) return false;

        $variant = ProductVariant::with('product')->where('sku', $itemNo)->first()
            ?? SecondProductVariant::where('sku', $itemNo)->first();
        if (!$variant) return false;

        try {
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
            \Illuminate\Support\Facades\Log::info("Webhook Berhasil: Update Stok via ITEM_QUANTITY untuk SKU {$itemNo} di Gudang {$warehouseName} menjadi {$newQuantity}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Webhook Gagal: Gagal update stok SKU {$itemNo} di Gudang {$warehouseName}. Error: " . $e->getMessage());
        }

        // Cek apakah butuh SN
        if ($variant instanceof SecondProductVariant) {
            return true;
        } elseif ($variant instanceof ProductVariant) {
            return (bool) ($variant->has_sn ?? false);
        }

        return false;
    }
}
