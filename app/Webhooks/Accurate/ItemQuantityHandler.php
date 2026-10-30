<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
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
                    $hasSn = $this->updateLocalStock($itemNo, $warehouseName, $newQuantity, $log->database_source, $log);
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
                    $syncService->syncFromAccurate((string) $sku, $log->database_source);
                    \Illuminate\Support\Facades\Log::info("Webhook SN Sync sukses untuk SKU: {$sku}");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Webhook SN Sync failed for SKU {$sku}: " . $e->getMessage());
                    \App\Services\SyncIssueRecorder::record(
                        reasonCode: 'SN_SYNC_FAILED',
                        stage: 'serial_number_sync',
                        log: $log,
                        itemNo: (string) $sku,
                        severity: 'error',
                        context: ['error' => $e->getMessage()]
                    );
                }
            }
        }
    }

    /**
     * @return bool True jika varian membutuhkan Serial Number, False jika tidak
     */
    private function updateLocalStock($itemNo, $warehouseName, $newQuantity, $databaseSource, $log = null): bool
    {
        $warehouse = Warehouse::where('name', $warehouseName)->first();
        if (!$warehouse) {
            \App\Services\SyncIssueRecorder::recordWarehouseNotFound(
                warehouseName: $warehouseName,
                itemNo: (string) $itemNo,
                databaseSource: $databaseSource,
                log: $log,
                context: ['attempted_qty' => $newQuantity]
            );
            return false;
        }

        $productAccurate = \App\Models\ProductAccurate::where('item_no', (string) $itemNo)
            ->where('database_source', $databaseSource)
            ->first();

        if (!$productAccurate) {
            \App\Services\SyncIssueRecorder::recordProductNotFound(
                itemNo: (string) $itemNo,
                databaseSource: $databaseSource,
                warehouseName: $warehouseName,
                log: $log,
                context: ['attempted_qty' => $newQuantity]
            );
            return false;
        }

        try {
            // LANGSUNG SIMPAN KE DB LOKAL (0 Detik, Tanpa HTTP Request ke Accurate!)
            WarehouseStock::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'variant_id'   => $productAccurate->id,
                    'variant_type' => get_class($productAccurate),
                ],
                [
                    'stock'        => (int) $newQuantity
                ]
            );
            \Illuminate\Support\Facades\Log::info("Webhook Berhasil: Update Stok via ITEM_QUANTITY untuk SKU {$itemNo} di Gudang {$warehouseName} menjadi {$newQuantity}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Webhook Gagal: Gagal update stok SKU {$itemNo} di Gudang {$warehouseName}. Error: " . $e->getMessage());
            \App\Services\SyncIssueRecorder::recordDbError(
                errorMsg: $e->getMessage(),
                stage: 'update_local_stock_quantity',
                itemNo: (string) $itemNo,
                warehouseName: $warehouseName,
                databaseSource: $databaseSource,
                log: $log
            );
        }

        // Cek apakah butuh SN
        return (bool) ($productAccurate->has_sn ?? false);
    }
}
