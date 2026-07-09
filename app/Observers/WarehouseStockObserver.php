<?php

namespace App\Observers;

use App\Models\ProductAccurate;
use App\Models\WarehouseStock;

class WarehouseStockObserver
{
    /**
     * Handle the WarehouseStock "saved" event.
     * This covers both created and updated events.
     */
    public function saved(WarehouseStock $warehouseStock): void
    {
        $this->syncProductAccurateStock($warehouseStock);
    }

    /**
     * Handle the WarehouseStock "deleted" event.
     */
    public function deleted(WarehouseStock $warehouseStock): void
    {
        $this->syncProductAccurateStock($warehouseStock);
    }

    /**
     * Sinkronisasikan total seluruh stok gudang ke field stock master ProductAccurate
     */
    private function syncProductAccurateStock(WarehouseStock $warehouseStock): void
    {
        // Hanya proses jika variant_type adalah ProductAccurate
        if ($warehouseStock->variant_type === ProductAccurate::class) {
            
            // Hitung akumulasi total stok dari semua gudang untuk varian ini
            $totalStock = WarehouseStock::where('variant_id', $warehouseStock->variant_id)
                ->where('variant_type', $warehouseStock->variant_type)
                ->sum('stock');

            // Cari produk master dan update stock globalnya jika berubah
            $productAccurate = ProductAccurate::find($warehouseStock->variant_id);
            
            if ($productAccurate && $productAccurate->stock != $totalStock) {
                // Update tanpa memicu observer (jika ProductAccurate punya observer kedepannya)
                // Menggunakan saveQuietly / sekadar update langsung
                $productAccurate->stock = $totalStock;
                $productAccurate->saveQuietly();
            }
        }
    }
}
