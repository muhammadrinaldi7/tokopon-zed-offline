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
    private function syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource): bool
    {
        // 1. Validasi DB Lokal: Pastikan Gudang ada di Laravel Anda
        // Handle 'GSK ' prefix from Accurate Second DB
        $localWarehouseName = $dbSource === 'second' ? str_replace('GSK ', '', $warehouseName) : $warehouseName;
        $warehouse = Warehouse::where('name', $localWarehouseName)->first();
        if (!$warehouse) return false;

        // 2. Validasi DB Lokal: Pastikan Varian (SKU) ada di Laravel Anda
        // Wajib menggunakan ProductAccurate karena tabel warehouse_stocks 
        // berelasi polymorphic (morphTo) ke variant_id dan variant_type milik ProductAccurate.
        $productAccurate = \App\Models\ProductAccurate::where('item_no', $itemNo)
            ->where('database_source', $dbSource)
            ->first();

        if (!$productAccurate) return false;

        // 3. Tembak API Accurate (Hanya dieksekusi jika gudang & produk valid)
        $service = app(AccurateService::class);
        $stockData = $service->getStockPerItemWarehouse($itemNo, $warehouseName, $dbSource);

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
        }

        // Cek apakah butuh SN
        return (bool) ($productAccurate->has_sn ?? false);
    }
}
