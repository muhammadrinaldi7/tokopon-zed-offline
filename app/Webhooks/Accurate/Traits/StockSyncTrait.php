<?php

namespace App\Webhooks\Accurate\Traits;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

trait StockSyncTrait
{
    /**
     * @return bool True jika varian membutuhkan Serial Number, False jika tidak
     */
    private function syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource, $log = null): bool
    {
        // 1. Validasi DB Lokal: Pastikan Gudang ada di Laravel Anda
        // Cek langsung dengan nama gudang asli terlebih dahulu
        $warehouse = Warehouse::where('name', $warehouseName)->first();
        if (!$warehouse && $dbSource === 'second') {
            // Fallback toleransi jika di Accurate ada/tidak ada prefix 'GSK '
            $altWarehouseName = str_starts_with($warehouseName, 'GSK - ') 
                ? str_replace('GSK - ', '', $warehouseName)
                : 'GSK - ' . $warehouseName;
            $warehouse = Warehouse::where('name', $altWarehouseName)->first();
        }

        if (!$warehouse) {
            \App\Services\SyncIssueRecorder::recordWarehouseNotFound(
                warehouseName: $warehouseName,
                itemNo: (string) $itemNo,
                databaseSource: $dbSource,
                log: $log
            );
            return false;
        }

        // 2. Validasi DB Lokal: Pastikan Varian (SKU) ada di Laravel Anda
        // Wajib menggunakan ProductAccurate karena tabel warehouse_stocks 
        // berelasi polymorphic (morphTo) ke variant_id dan variant_type milik ProductAccurate.
        $productAccurate = \App\Models\ProductAccurate::where('item_no', (string) $itemNo)
            ->where('database_source', $dbSource)
            ->first();

        if (!$productAccurate) {
            \App\Services\SyncIssueRecorder::recordProductNotFound(
                itemNo: (string) $itemNo,
                databaseSource: $dbSource,
                warehouseName: $warehouseName,
                log: $log
            );
            return false;
        }

        // 3. Tembak API Accurate (Hanya dieksekusi jika gudang & produk valid)
        $service = app(AccurateService::class);
        $stockData = null;
        try {
            $stockData = $service->getStockPerItemWarehouse($itemNo, $warehouseName, $dbSource);
        } catch (\Throwable $e) {
            \App\Services\SyncIssueRecorder::record(
                reasonCode: 'API_ERROR',
                stage: 'fetch_stock_from_api',
                log: $log,
                itemNo: (string) $itemNo,
                warehouseName: $warehouseName,
                severity: 'error',
                context: ['error' => $e->getMessage(), 'database_source' => $dbSource],
                databaseSource: $dbSource
            );
            return false;
        }

        // Langsung ambil nilai availableStock. Jika tidak ada/null, jadikan 0.
        $qty = $stockData['availableStock'] ?? 0;

        try {
            // 4. Update Stok di Database Laravel
            WarehouseStock::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'variant_id'   => $productAccurate->id,
                    'variant_type' => get_class($productAccurate),
                ],
                [
                    'stock'        => (int) $qty
                ]
            );
            Log::info("Webhook Berhasil: Update Stok SKU {$itemNo} di Gudang {$warehouseName} menjadi {$qty}");
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal update stok SKU {$itemNo} di Gudang {$warehouseName}. Error: " . $e->getMessage());
            \App\Services\SyncIssueRecorder::recordDbError(
                errorMsg: $e->getMessage(),
                stage: 'update_stock_trait',
                itemNo: (string) $itemNo,
                warehouseName: $warehouseName,
                databaseSource: $dbSource,
                log: $log
            );
        }

        // Cek apakah butuh SN
        return (bool) ($productAccurate->has_sn ?? false);
    }
}
