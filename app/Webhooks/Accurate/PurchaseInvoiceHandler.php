<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;
        $dbSource = $log->database_source;
        $service = app(AccurateService::class);
        $syncService = app(\App\Services\SerialNumberSyncService::class);

        // Accurate mengirimkan detail event di dalam array 'data'
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $itemData) {

                // PERBAIKAN: Ambil dari 'purchaseInvoiceId' terlebih dahulu, jika tidak ada baru cari 'id'
                $purchaseInvoiceId = $itemData['purchaseInvoiceId'] ?? $itemData['id'] ?? null;

                if ($purchaseInvoiceId) {

                    // 1. Sinkronisasi SN, HPP, dan Vendor menggunakan service yang sudah ada
                    try {
                        $syncService->syncFromPurchaseInvoice($purchaseInvoiceId, $dbSource);
                        Log::info("PurchaseInvoiceHandler: SN, HPP, Vendor sync success for purchaseInvoiceId: {$purchaseInvoiceId}");
                    } catch (\Exception $e) {
                        Log::error("PurchaseInvoiceHandler: SN Sync failed for purchaseInvoiceId {$purchaseInvoiceId}: " . $e->getMessage());
                    }

                    // 2. Sinkronisasi Total Stok Gudang (WarehouseStock)
                    $apiData = $service->getPurchaseInvoiceDetail($purchaseInvoiceId, $dbSource);

                    if ($apiData && isset($apiData['detailItem']) && is_array($apiData['detailItem'])) {
                        foreach ($apiData['detailItem'] as $detail) {
                            $itemNo = $detail['item']['no'] ?? $detail['itemNo'] ?? null;
                            $warehouseName = $detail['warehouse']['name'] ?? $apiData['warehouse']['name'] ?? null;

                            if ($itemNo) {
                                $this->syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource);
                            }
                        }
                    } else {
                        Log::warning("PurchaseInvoiceHandler: API response tidak memiliki detailItem untuk purchaseInvoiceId " . $purchaseInvoiceId);
                    }
                } else {
                    Log::warning("PurchaseInvoiceHandler: Payload tidak memiliki id dokumen: " . json_encode($itemData));
                }
            }
        }
    }

    private function syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource): void
    {
        // 1. Validasi DB Lokal: Pastikan Gudang ada di Laravel Anda
        // Handle 'GSK ' prefix from Accurate Second DB
        $localWarehouseName = $dbSource === 'second' ? str_replace('GSK ', '', $warehouseName) : $warehouseName;
        $warehouse = Warehouse::where('name', $localWarehouseName)->first();
        if (!$warehouse) return;

        // 2. Validasi DB Lokal: Pastikan Varian (SKU) ada di Laravel Anda
        // Wajib menggunakan ProductAccurate karena tabel warehouse_stocks 
        // berelasi polymorphic (morphTo) ke variant_id dan variant_type milik ProductAccurate.
        $productAccurate = \App\Models\ProductAccurate::where('item_no', $itemNo)
            ->where('database_source', $dbSource)
            ->first();

        if (!$productAccurate) return;

        // 3. Tembak API Accurate (Hanya dieksekusi jika gudang & produk valid)
        $service = app(AccurateService::class);
        $stockData = $service->getStockPerItemWarehouse($itemNo, $warehouseName, $dbSource);

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
    }
}
