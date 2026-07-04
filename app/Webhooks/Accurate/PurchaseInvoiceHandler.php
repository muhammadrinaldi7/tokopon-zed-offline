<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

use App\Webhooks\Accurate\Traits\StockSyncTrait;

class PurchaseInvoiceHandler implements WebhookHandlerInterface
{
    use StockSyncTrait;
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


}
