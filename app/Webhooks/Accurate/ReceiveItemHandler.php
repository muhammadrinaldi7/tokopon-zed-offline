<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class ReceiveItemHandler implements WebhookHandlerInterface
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
                // Untuk RECEIVE_ITEM, webhook memberikan 'receiveItemId'
                if (isset($itemData['receiveItemId'])) {
                    $receiveItemId = $itemData['receiveItemId'];

                    // 1. Sinkronisasi SN, HPP, dan Vendor menggunakan service yang sudah ada
                    try {
                        $syncService->syncFromReceiveItem($receiveItemId, $dbSource);
                        Log::info("ReceiveItemHandler: SN, HPP, Vendor sync success for receiveItemId: {$receiveItemId}");
                    } catch (\Exception $e) {
                        Log::error("ReceiveItemHandler: SN Sync failed for receiveItemId {$receiveItemId}: " . $e->getMessage());
                    }

                    // 2. Sinkronisasi Total Stok Gudang (WarehouseStock)
                    $apiData = $service->getReceiveItemDetail($receiveItemId, $dbSource);
                    
                    if ($apiData && isset($apiData['detailItem']) && is_array($apiData['detailItem'])) {
                        foreach ($apiData['detailItem'] as $detail) {
                            $itemNo = $detail['item']['no'] ?? $detail['itemNo'] ?? null;
                            // Nama gudang bisa ada di level detail atau di level header dokumen
                            $warehouseName = $detail['warehouse']['name'] ?? $apiData['warehouse']['name'] ?? null;
                            
                            if ($itemNo) {
                                $this->syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource);
                            }
                        }
                    } else {
                        Log::warning("ReceiveItemHandler: API response tidak memiliki detailItem untuk receiveItemId " . $receiveItemId);
                    }
                } else {
                    Log::warning("ReceiveItemHandler: Payload tidak memiliki receiveItemId: " . json_encode($itemData));
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
        // Wajib menggunakan ProductVariant atau SecondProductVariant karena tabel warehouse_stocks 
        // berelasi polymorphic (morphTo) ke variant_id dan variant_type milik Varian, bukan ProductAccurate.
        $variant = ProductVariant::with('product')->where('sku', $itemNo)->first()
            ?? SecondProductVariant::where('sku', $itemNo)->first();
        if (!$variant) return;

        // 3. Tembak API Accurate (Hanya dieksekusi jika gudang & produk valid)
        $service = app(AccurateService::class);
        $stockData = $service->getStockPerItemWarehouse($itemNo, $warehouseName, $dbSource);

        $qty = $stockData['availableStock'] ?? 0;

        try {
            // 4. Update Stok di Database Laravel
            WarehouseStock::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'variant_id'   => $variant->id,
                    'variant_type' => get_class($variant),
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
