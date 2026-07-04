<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

use App\Webhooks\Accurate\Traits\StockSyncTrait;

class StockChangeHandler implements WebhookHandlerInterface
{
    use StockSyncTrait;
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;
        $dbSource = $log->database_source;
        $skusToSyncSn = [];

        // Accurate mengirimkan detail event di dalam array 'data'
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $itemData) {
                // Skenario 1: Payload adalah detail tunggal (punya itemNo langsung)
                if (isset($itemData['itemNo']) || isset($itemData['no'])) {
                    $itemNo = $itemData['itemNo'] ?? $itemData['no'];
                    $warehouseName = $itemData['warehouseName'] ?? null;
                    $hasSn = $this->syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource);
                    if ($hasSn) $skusToSyncSn[$itemNo] = true;
                } 
                // Skenario 2: Payload adalah Dokumen Transaksi (punya detailItem/detailTransfer dll)
                else {
                    // Cari array detail di dalam dokumen (biasanya detailItem, detailTransfer, detailAdjustment)
                    $details = $itemData['detailItem'] ?? $itemData['detailTransfer'] ?? $itemData['detailAdjustment'] ?? $itemData['detailReceive'] ?? $itemData['detail'] ?? [];
                    
                    if (is_array($details) && count($details) > 0) {
                        foreach ($details as $detail) {
                            $itemNo = $detail['item']['no'] ?? $detail['itemNo'] ?? null;
                            $warehouseName = $detail['warehouse']['name'] ?? $itemData['warehouse']['name'] ?? null;
                            
                            if ($itemNo) {
                                $hasSn = $this->syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource);
                                if ($hasSn) $skusToSyncSn[$itemNo] = true;
                            }
                        }
                    } else {
                        Log::warning("Accurate Webhook Payload Data tidak memiliki itemNo atau detailItem: " . json_encode($itemData));
                    }
                }
            }
        } else {
            throw new \Exception("Format payload tidak dikenali, array 'data' tidak ditemukan.");
        }

        // Jalankan sinkronisasi SN
        if (!empty($skusToSyncSn)) {
            $syncService = app(\App\Services\SerialNumberSyncService::class);
            foreach (array_keys($skusToSyncSn) as $sku) {
                try {
                    $syncService->syncFromAccurate($sku, $log->database_source);
                    Log::info("Webhook SN Sync sukses (dari StockChangeHandler) untuk SKU: {$sku}");
                } catch (\Exception $e) {
                    Log::error("Webhook SN Sync failed for SKU {$sku}: " . $e->getMessage());
                }
            }
        }
    }


}
