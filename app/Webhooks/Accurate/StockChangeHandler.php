<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Warehouse;
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
                    
                    // PROSES LANGSUNG SERIAL NUMBER JIKA DISEDIAKAN DALAM MUTASI (0 Detik)
                    $this->processDirectSerialNumberData($itemData, $warehouseName, $dbSource, $log);

                    $hasSn = $this->syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource, $log);
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
                            
                            $this->processDirectSerialNumberData($detail, $warehouseName, $dbSource, $log);

                            if ($itemNo) {
                                $hasSn = $this->syncItemStockFromAccurate($itemNo, $warehouseName, $dbSource, $log);
                                if ($hasSn) $skusToSyncSn[$itemNo] = true;
                            }
                        }
                    } else {
                        Log::warning("Accurate Webhook Payload Data tidak memiliki itemNo atau detailItem: " . json_encode($itemData));
                        \App\Services\SyncIssueRecorder::recordPayloadUnrecognized([$itemData], 'StockChangeHandler', $log);
                    }
                }
            }
        } else {
            throw new \Exception("Format payload tidak dikenali, array 'data' tidak ditemukan.");
        }

        // Jalankan sinkronisasi SN (fallback & verifikasi menyeluruh)
        if (!empty($skusToSyncSn)) {
            $syncService = app(\App\Services\SerialNumberSyncService::class);
            foreach (array_keys($skusToSyncSn) as $sku) {
                try {
                    $syncService->syncFromAccurate((string) $sku, $log->database_source);
                    Log::info("Webhook SN Sync sukses (dari StockChangeHandler) untuk SKU: {$sku}");
                } catch (\Exception $e) {
                    Log::error("Webhook SN Sync failed for SKU {$sku}: " . $e->getMessage());
                    \App\Services\SyncIssueRecorder::record(
                        reasonCode: 'SN_SYNC_FAILED',
                        stage: 'serial_number_sync_from_stock_change',
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
     * Memproses langsung rincian Serial Number dari payload Accurate (Fast-Path).
     * Jika mutasi positif (+1), langsung update warehouse_id dan set status Available.
     */
    private function processDirectSerialNumberData(array $itemData, ?string $warehouseName, string $dbSource, ?AccurateWebhookLog $log = null): void
    {
        $snList = $itemData['serialNumberData'] ?? $itemData['detailSerialNumber'] ?? [];
        if (empty($snList) || !is_array($snList)) {
            return;
        }

        $itemNo = $itemData['itemNo'] ?? ($itemData['no'] ?? ($itemData['item']['no'] ?? null));
        $transactionType = strtoupper((string)($itemData['transactionType'] ?? ''));
        $transactionNumber = $itemData['transactionNumber'] ?? null;

        // Cari gudang lokal
        $warehouse = null;
        if ($warehouseName) {
            $warehouse = Warehouse::where('name', $warehouseName)->first();
            if (!$warehouse && $dbSource === 'second') {
                $altName = str_starts_with($warehouseName, 'GSK - ')
                    ? str_replace('GSK - ', '', $warehouseName)
                    : 'GSK - ' . $warehouseName;
                $warehouse = Warehouse::where('name', $altName)->first();
            }
        }

        $bu = \App\Models\BusinessUnit::where('code', $dbSource)->first();

        foreach ($snList as $snItem) {
            $snString = $snItem['number'] ?? ($snItem['serialNumber']['number'] ?? null);
            $qty = (int)($snItem['quantity'] ?? 1);

            if (!$snString) {
                continue;
            }

            $snString = (string)$snString;

            try {
                $existingSn = \App\Models\ProductSerialNumber::where('serial_number', $snString)->first();

                // Skenario A: Mutasi Positif (+1) -> Barang masuk ke gudang ini (Transfer Masuk / Penyesuaian Masuk)
                if ($qty > 0) {
                    if ($warehouse) {
                        if ($existingSn) {
                            $existingSn->update([
                                'warehouse_id'     => $warehouse->id,
                                'status'           => 'Available',
                                'business_unit_id' => $bu?->id ?? $existingSn->business_unit_id,
                            ]);
                            Log::info("Fast-Path SN Transfer: SN {$snString} berhasil dipindahkan ke gudang '{$warehouse->name}' (Available). Transaksi: {$transactionNumber}");
                        } else {
                            // Jika SN belum pernah ada di database lokal sama sekali, buat baru
                            \App\Models\ProductSerialNumber::create([
                                'serial_number'    => $snString,
                                'item_no'          => (string)$itemNo,
                                'warehouse_id'     => $warehouse->id,
                                'business_unit_id' => $bu?->id,
                                'status'           => 'Available',
                            ]);
                            Log::info("Fast-Path SN Inbound: SN baru {$snString} berhasil dibuat di gudang '{$warehouse->name}' (Available).");
                        }
                    } else {
                        \App\Services\SyncIssueRecorder::recordWarehouseNotFound(
                            warehouseName: $warehouseName,
                            itemNo: (string)$itemNo,
                            databaseSource: $dbSource,
                            log: $log,
                            context: [
                                'serial_number' => $snString,
                                'transaction_number' => $transactionNumber,
                                'transaction_type' => $transactionType
                            ]
                        );
                    }
                } 
                // Skenario B: Mutasi Negatif (-1) karena Penjualan (SI / DO)
                elseif ($qty < 0 && in_array($transactionType, ['SI', 'DO', 'SALES_INVOICE', 'DELIVERY_ORDER'])) {
                    if ($existingSn && $existingSn->status === 'Available') {
                        $existingSn->update(['status' => 'Unavailable']);
                        Log::info("Fast-Path SN Outbound: SN {$snString} ditandai Unavailable (terjual via {$transactionNumber}).");
                    }
                }
            } catch (\Throwable $e) {
                // Isolasi error: Jangan biarkan kegagalan 1 SN menggagalkan proses webhook keseluruhan
                Log::error("Fast-Path SN Error untuk SN {$snString}: " . $e->getMessage());
                \App\Services\SyncIssueRecorder::recordDbError(
                    errorMsg: $e->getMessage(),
                    stage: 'direct_sn_mutation_processing',
                    itemNo: (string)$itemNo,
                    warehouseName: $warehouseName,
                    databaseSource: $dbSource,
                    log: $log,
                    context: [
                        'serial_number' => $snString,
                        'transaction_number' => $transactionNumber,
                        'transaction_type' => $transactionType
                    ]
                );
            }
        }
    }


}
