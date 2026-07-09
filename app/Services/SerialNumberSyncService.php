<?php

namespace App\Services;

use App\Models\ProductSerialNumber;
use App\Models\Warehouse;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class SerialNumberSyncService
{
    protected $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    /**
     * Sinkronisasi Serial Number untuk 1 SKU tertentu dari Accurate API
     * 
     * @param string $sku
     * @return int Jumlah Serial Number yang disinkronisasi
     */
    public function syncFromAccurate($sku, $databaseSource = null)
    {
        try {
            $sources = [];
            if ($databaseSource) {
                $sources[] = $databaseSource;
            } else {
                $sources = \App\Models\BusinessUnit::where('is_active', true)->pluck('code')->toArray();
            }


            $totalProcessed = 0;

            foreach ($sources as $source) {
                $snData = null;
                try {
                    $snData = $this->accurateService->getSerialNumberPerWarehouse($sku, $source);
                } catch (\Exception $e) {
                    // Ignore error and continue to next source if one fails
                    Log::warning("Failed to fetch SN for SKU {$sku} from source {$source}: " . $e->getMessage());
                    continue;
                }

                if (!empty($snData)) {
                    $totalProcessed += $this->processSnData($sku, $snData, $source);
                } else {
                    // Jika tidak ada data dari Accurate untuk BUID ini, kita harus membuat SN untuk BUID ini menjadi Unavailable.
                    // Karena jika sebelumnya ada, berarti sekarang sudah habis/terjual.
                    $bu = \App\Models\BusinessUnit::where('code', $source)->first();
                    if ($bu) {
                        $warehouseIds = \App\Models\Warehouse::where('business_unit_id', $bu->id)->pluck('id')->toArray();
                        if (!empty($warehouseIds)) {
                            ProductSerialNumber::where('item_no', $sku)
                                ->where('status', 'Available')
                                ->whereIn('warehouse_id', $warehouseIds)
                                ->update(['status' => 'Unavailable']);
                        }
                    }
                }
            }

            return $totalProcessed;
        } catch (\Exception $e) {
            Log::error("Failed to sync SN for SKU {$sku}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Memproses mapping dan upsert data SN ke database
     */
    private function processSnData($sku, $accurateData, $databaseSource = 'syihab')
    {
        $bu = \App\Models\BusinessUnit::where('code', $databaseSource)->first();
        if (!$bu) return 0;
        
        $warehouseMap = \App\Models\Warehouse::where('business_unit_id', $bu->id)->pluck('id', 'warehouse_id')->toArray();
        $warehouseIds = array_values($warehouseMap);
        
        $pa = \App\Models\ProductAccurate::where('item_no', $sku)
            ->where('business_unit_id', $bu->id)
            ->first();
        $productAccurateId = $pa ? $pa->id : null;

        $existingSns = ProductSerialNumber::where('item_no', $sku)
            ->where('status', 'Available')
            ->whereIn('warehouse_id', $warehouseIds)
            ->pluck('serial_number')
            ->toArray();

        $processedSerialNumbers = [];
        $upsertData = [];

        foreach ($accurateData as $item) {
            $accurateWarehouseId = $item['warehouse']['id'] ?? null;
            $serialNumberStr = $item['serialNumber']['number'] ?? null;
            $accurateSnId = $item['serialNumber']['id'] ?? null;

            if (!$serialNumberStr || !$accurateWarehouseId) continue;
            $serialNumberStr = (string) $serialNumberStr;

            $localWarehouseId = $warehouseMap[$accurateWarehouseId] ?? null;

            $upsertData[] = [
                'accurate_sn_id'      => $accurateSnId,
                'item_no'             => $sku,
                'warehouse_id'        => $localWarehouseId,
                'business_unit_id'    => $bu ? $bu->id : null,
                'product_accurate_id' => $productAccurateId,
                'serial_number'    => $serialNumberStr,
                'status'           => 'Available',
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            $processedSerialNumbers[] = $serialNumberStr;
        }

        // Jalankan Upsert berdasarkan kolom unik serial_number
        if (count($upsertData) > 0) {
            try {
                ProductSerialNumber::upsert(
                    $upsertData,
                    ['serial_number'], // <- Acuan Pencarian (Unique)
                    ['accurate_sn_id', 'item_no', 'warehouse_id', 'business_unit_id', 'product_accurate_id', 'status', 'updated_at'] // <- Yang di-update
                );
                Log::info("Webhook/Sync Berhasil: Upsert " . count($upsertData) . " Serial Number untuk SKU {$sku}");
            } catch (\Exception $e) {
                Log::error("Webhook/Sync Gagal: Gagal upsert Serial Number untuk SKU {$sku}. Error: " . $e->getMessage());
            }
        }

        // Update status menjadi Unavailable untuk SN yang hilang dari API (hanya untuk BUID ini)
        $missingSnList = array_diff($existingSns, $processedSerialNumbers);

        // Pastikan array hanya berisi string sebelum di binding ke Eloquent (PDO string binding)
        $missingSnList = array_map('strval', $missingSnList);

        if (count($missingSnList) > 0) {
            ProductSerialNumber::whereIn('serial_number', $missingSnList)
                ->where('status', 'Available')
                ->whereIn('warehouse_id', $warehouseIds)
                ->update(['status' => 'Unavailable']);
        }

        return count($processedSerialNumbers);
    }

    public function syncFromReceiveItem($receiveItemId, $databaseSource = 'syihab')
    {
        try {
            $detail = $this->accurateService->getReceiveItemDetail($receiveItemId, $databaseSource);

            if (!$detail) {
                return 0; // Gagal ambil detail
            }

            // Ekstrak receipt date
            $transDateStr = $detail['transDate'] ?? null;
            $receiptDate = null;
            if ($transDateStr) {
                try {
                    $receiptDate = \Carbon\Carbon::createFromFormat('d/m/Y', $transDateStr)->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        $receiptDate = \Carbon\Carbon::parse($transDateStr)->format('Y-m-d');
                    } catch (\Exception $e2) {
                        $receiptDate = null;
                    }
                }
            }

            $updatedCount = 0;

            // 1. Ekstrak Vendor
            $vendorName = $detail['vendor']['name'] ?? null;
            $accurateVendorId = $detail['vendor']['id'] ?? null;
            $localVendorId = null;

            if ($accurateVendorId) {
                $localVendor = \App\Models\Vendor::where('accurate_vendor_id', $accurateVendorId)
                    ->where('database_source', $databaseSource)
                    ->first();
                if (!$localVendor && $vendorName) {
                    $localVendor = \App\Models\Vendor::where('vendor_name', $vendorName)
                        ->where('database_source', $databaseSource)
                        ->first();
                }
                if ($localVendor) {
                    $localVendorId = $localVendor->id;
                }
            }

            // 2. Iterasi detailItem
            $detailItems = $detail['detailItem'] ?? [];
            Log::info("ReceiveItem {$receiveItemId}: Ditemukan " . count($detailItems) . " detail item untuk diproses.");

            return $this->processDocumentDetailItems($detailItems, $databaseSource, $receiptDate, $localVendorId);
        } catch (\Exception $e) {
            Log::error("Failed to sync Receive Item {$receiveItemId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function syncFromPurchaseInvoice($purchaseInvoiceId, $databaseSource = 'syihab')
    {
        try {
            $detail = $this->accurateService->getPurchaseInvoiceDetail($purchaseInvoiceId, $databaseSource);

            if (!$detail) {
                return 0; // Gagal ambil detail
            }

            // Ekstrak receipt date
            $transDateStr = $detail['transDate'] ?? null;
            $receiptDate = null;
            if ($transDateStr) {
                try {
                    $receiptDate = \Carbon\Carbon::createFromFormat('d/m/Y', $transDateStr)->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        $receiptDate = \Carbon\Carbon::parse($transDateStr)->format('Y-m-d');
                    } catch (\Exception $e2) {
                        $receiptDate = null;
                    }
                }
            }

            $updatedCount = 0;

            // 1. Ekstrak Vendor
            $vendorName = $detail['vendor']['name'] ?? null;
            $accurateVendorId = $detail['vendor']['id'] ?? null;
            $localVendorId = null;

            if ($accurateVendorId) {
                $localVendor = \App\Models\Vendor::where('accurate_vendor_id', $accurateVendorId)
                    ->where('database_source', $databaseSource)
                    ->first();
                if (!$localVendor && $vendorName) {
                    $localVendor = \App\Models\Vendor::where('vendor_name', $vendorName)
                        ->where('database_source', $databaseSource)
                        ->first();
                }
                if ($localVendor) {
                    $localVendorId = $localVendor->id;
                }
            }

            // 2. Iterasi detailItem
            $detailItems = $detail['detailItem'] ?? [];
            Log::info("PurchaseInvoice {$purchaseInvoiceId}: Ditemukan " . count($detailItems) . " detail item untuk diproses.");

            return $this->processDocumentDetailItems($detailItems, $databaseSource, $receiptDate, $localVendorId);
        } catch (\Exception $e) {
            Log::error("Failed to sync Purchase Invoice {$purchaseInvoiceId}: " . $e->getMessage());
            throw $e;
        }
    }

    private function processDocumentDetailItems($detailItems, $databaseSource, $receiptDate, $localVendorId)
    {
        $updatedCount = 0;
        
        $bu = \App\Models\BusinessUnit::where('code', $databaseSource)->first();
        if (!$bu) return 0;
        
        // 1. PRE-FETCH: Ambil semua gudang untuk Business Unit ini (O(1) query)
        $warehouseMap = \App\Models\Warehouse::where('business_unit_id', $bu->id)->pluck('id', 'warehouse_id')->toArray();
        
        // 2. PRE-FETCH: Kumpulkan semua SKU yang ada di detail item untuk meminimalisir query ke ProductAccurate
        $skus = [];
        foreach ($detailItems as $item) {
            $sku = $item['item']['no'] ?? $item['detailName'] ?? $item['itemNo'] ?? $item['no'] ?? null;
            if ($sku) $skus[] = $sku;
        }
        // Ambil semua ProductAccurate yang sesuai (O(1) query)
        $paMap = \App\Models\ProductAccurate::where('business_unit_id', $bu->id)
            ->whereIn('item_no', $skus)
            ->pluck('id', 'item_no')
            ->toArray();

        // 3. Iterasi dan Proses (Tanpa N+1 Query)
        foreach ($detailItems as $item) {
            $sku = $item['item']['no'] ?? $item['detailName'] ?? $item['itemNo'] ?? $item['no'] ?? null;
            if (!$sku) continue;

            $hpp = $item['unitPrice'] ?? $item['itemCost'] ?? 0;
            $accurateWarehouseId = $item['warehouseId'] ?? ($item['warehouse']['id'] ?? null);

            $localWarehouseId = $warehouseMap[$accurateWarehouseId] ?? null;
            $productAccurateId = $paMap[$sku] ?? null;

            $snList = $item['detailSerialNumber'] ?? [];
            foreach ($snList as $snItem) {
                $sn = $snItem['serialNumber']['number'] ?? null;
                if (!$sn) continue;
                $sn = (string)$sn;

                $existingSn = ProductSerialNumber::where('serial_number', $sn)->first();
                $qcStatus = $databaseSource === 'second' ? 'Pending Inbound' : null;

                if ($existingSn) {
                    $existingSn->update([
                        'hpp' => $hpp,
                        'vendor_id' => $localVendorId,
                        'receipt_date' => $receiptDate,
                    ]);
                    $updatedCount++;
                } else {
                    $isAlreadySold = \App\Models\OrderItemSerialNumber::where('serial_number', $sn)->exists();
                    $finalStatus = $isAlreadySold ? 'Unavailable' : 'Available';

                    ProductSerialNumber::create([
                        'serial_number' => $sn,
                        'item_no' => $sku,
                        'warehouse_id' => $localWarehouseId,
                        'business_unit_id' => $bu->id,
                        'product_accurate_id' => $productAccurateId,
                        'hpp' => $hpp,
                        'vendor_id' => $localVendorId,
                        'status' => $finalStatus,
                        'receipt_date' => $receiptDate,
                        'qc_status' => $qcStatus,
                    ]);
                    $updatedCount++;
                }
            }
        }
        
        return $updatedCount;
    }

    public function syncHppFromNearestCost($itemNo, $databaseSource = null)
    {
        try {
            $sources = [];
            if ($databaseSource) {
                $sources[] = $databaseSource;
            } else {
                $sources = \App\Models\BusinessUnit::where('is_active', true)->pluck('code')->toArray();
            }

            $costData = null;
            foreach ($sources as $source) {
                try {
                    $costData = $this->accurateService->getNearestCost($itemNo, $source);
                    if ($costData !== null) {
                        break;
                    }
                } catch (\Exception $e) {
                    // ignore and try next
                }
            }

            if ($costData === null) {
                return 0;
            }

            $hpp = 0;
            if (is_numeric($costData)) {
                $hpp = (float) $costData;
            } elseif (is_array($costData)) {
                // Fallback jika API return array object
                if (isset($costData['cost'])) {
                    $hpp = (float) $costData['cost'];
                } elseif (isset($costData['nearestCost'])) {
                    $hpp = (float) $costData['nearestCost'];
                } else {
                    $hpp = (float) current($costData);
                }
            }

            if ($hpp > 0) {
                // Update HPP di lokal
                $updatedCount = ProductSerialNumber::where('item_no', $itemNo)
                    ->where(function ($q) {
                        $q->whereNull('hpp')
                            ->orWhere('hpp', 0)
                            ->orWhere('hpp', '0');
                    })
                    ->update(['hpp' => $hpp]);

                return $updatedCount;
            }

            return 0;
        } catch (\Exception $e) {
            Log::error("Failed to sync HPP for Item {$itemNo}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sinkronisasi harga jual (unitPrice) dari Accurate ke ProductVariant/SecondProductVariant lokal
     *
     * @param string $sku
     * @param string|null $databaseSource
     * @return array ['updated' => bool, 'old_price' => int, 'new_price' => int]
     */
    public function syncPriceFromAccurate($sku, $databaseSource = null)
    {
        $sources = [];
        if ($databaseSource) {
            $sources[] = $databaseSource;
        } else {
            $sources = \App\Models\BusinessUnit::where('is_active', true)->pluck('code')->toArray();
        }

        foreach ($sources as $source) {
            try {
                $itemDetail = $this->accurateService->itemDetailDo($sku, $source);

                if (!$itemDetail) continue;

                $unitPrice = $itemDetail['unitPrice'] ?? null;
                if (!$unitPrice || $unitPrice <= 0) continue;

                $unitPrice = (int) $unitPrice;

                // Cari variant lokal berdasarkan SKU
                $productAccurate = \App\Models\ProductAccurate::where('item_no', $sku)
                    ->where('database_source', $source)
                    ->first();

                if (!$productAccurate) continue;

                $oldPrice = (int) $productAccurate->base_price;

                // Hanya update jika harga berubah
                if ($oldPrice !== $unitPrice) {
                    $productAccurate->update(['base_price' => $unitPrice]);
                    Log::info("Price Sync [{$sku}]: Harga berubah dari Rp " . number_format($oldPrice) . " → Rp " . number_format($unitPrice) . " (source: {$source})");
                    return ['updated' => true, 'old_price' => $oldPrice, 'new_price' => $unitPrice];
                }

                return ['updated' => false, 'old_price' => $oldPrice, 'new_price' => $unitPrice];
            } catch (\Exception $e) {
                Log::warning("Price Sync [{$sku}] gagal dari source {$source}: " . $e->getMessage());
                continue;
            }
        }

        return ['updated' => false, 'old_price' => 0, 'new_price' => 0];
    }
}
