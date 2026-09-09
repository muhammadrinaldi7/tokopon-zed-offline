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
                    $updatePayload = [
                        'hpp' => $hpp,
                        'vendor_id' => $localVendorId,
                        'receipt_date' => $receiptDate,
                        'business_unit_id' => $bu->id,
                    ];
                    if ($localWarehouseId) {
                        $updatePayload['warehouse_id'] = $localWarehouseId;
                    }
                    if ($productAccurateId) {
                        $updatePayload['product_accurate_id'] = $productAccurateId;
                    }
                    if ($sku) {
                        $updatePayload['item_no'] = $sku;
                    }
                    $existingSn->update($updatePayload);
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

    /**
     * Sinkronisasi presisi untuk 1 Serial Number (HPP & Vendor lintas Unit Usaha)
     * 
     * @param int|string $snId
     * @return array
     */
    public function syncSingleSerialNumber($snId)
    {
        $sn = ProductSerialNumber::find($snId);
        if (!$sn) {
            throw new \Exception("Data Serial Number dengan ID {$snId} tidak ditemukan.");
        }

        $bu = $sn->business_unit_id ? \App\Models\BusinessUnit::find($sn->business_unit_id) : null;
        $dbSource = $bu ? $bu->code : 'syihab';

        $hppUpdated = false;
        $vendorUpdated = false;
        $oldHpp = (float)$sn->hpp;

        $newHpp = null;
        $newVendorId = null;
        $newReceiptDate = null;

        // -------------------------------------------------------------
        // TIER 1: Cari dari transaksi lokal SellPhone / Buyback
        // (Khususnya jika perangkat dibeli dari customer oleh GSK / Second)
        // -------------------------------------------------------------
        $sellPhone = \App\Models\SellPhone::where('imei', $sn->serial_number)
            ->when($sn->business_unit_id, function ($q) use ($sn) {
                $q->where('business_unit_id', $sn->business_unit_id);
            })
            ->latest()
            ->first();

        if ($sellPhone) {
            if ($sellPhone->appraised_value > 0) {
                $newHpp = (float)$sellPhone->appraised_value;
            }
            $newReceiptDate = $sellPhone->created_at ? $sellPhone->created_at->format('Y-m-d') : null;

            // 1. Cek apakah ada vendor customer di tabel vendors untuk database_source ini
            $customerVendor = \App\Models\Vendor::where('database_source', $dbSource)
                ->where(function ($q) {
                    $q->where('vendor_name', 'like', '%CUSTOMER%')
                      ->orWhere('vendor_no', 'like', '%CUSTOMER%');
                })
                ->first();

            // 2. Jika user_id memiliki accurate vendor spesifik
            if ($sellPhone->user_id) {
                $uav = \App\Models\UserAccurateVendor::where('user_id', $sellPhone->user_id)
                    ->where('business_unit_id', $sn->business_unit_id)
                    ->first();
                if ($uav && $uav->accurate_vendor_id) {
                    $directVendor = \App\Models\Vendor::where('accurate_vendor_id', $uav->accurate_vendor_id)
                        ->where('database_source', $dbSource)
                        ->first();
                    if ($directVendor) {
                        $customerVendor = $directVendor;
                    }
                }
            }

            if ($customerVendor) {
                $newVendorId = $customerVendor->id;
            }
        }

        // -------------------------------------------------------------
        // TIER 2: Cari dari transaksi lokal Inbound PO (DeviceInspection -> PurchaseOrderItem -> PurchaseOrder)
        // -------------------------------------------------------------
        if ($newHpp === null || $newVendorId === null) {
            $inspection = \App\Models\DeviceInspection::where('imei', $sn->serial_number)
                ->where('inspectable_type', \App\Models\PurchaseOrderItem::class)
                ->latest()
                ->first();

            if ($inspection && $inspection->inspectable) {
                /** @var \App\Models\PurchaseOrderItem $poItem */
                $poItem = $inspection->inspectable;
                $po = $poItem->purchaseOrder;

                if ($po && (!$sn->business_unit_id || $po->database_source === $dbSource)) {
                    if ($newHpp === null && (float)$poItem->unit_price > 0) {
                        $newHpp = (float)$poItem->unit_price;
                    }
                    if ($newVendorId === null && $po->vendor_id) {
                        $newVendorId = $po->vendor_id;
                    }
                    if ($newReceiptDate === null && $po->po_date) {
                        $newReceiptDate = \Carbon\Carbon::parse($po->po_date)->format('Y-m-d');
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // TIER 3: Tarik dari Accurate API (Nearest Cost) untuk Business Unit ini jika HPP belum ditemukan
        // -------------------------------------------------------------
        if ($newHpp === null) {
            try {
                $costData = $this->accurateService->getNearestCost($sn->item_no, $dbSource);
                $cost = 0;
                if (is_numeric($costData)) {
                    $cost = (float) $costData;
                } elseif (is_array($costData)) {
                    $cost = (float) ($costData['cost'] ?? ($costData['nearestCost'] ?? current($costData)));
                }

                if ($cost > 0) {
                    $newHpp = $cost;
                }
            } catch (\Exception $e) {
                Log::warning("Gagal ambil nearestCost untuk SN {$sn->serial_number} (source: {$dbSource}): " . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // TIER 4: Inferensi Vendor dari Sibling SN pada BU yang sama (atau Vendor Default BU) jika belum ditemukan
        // -------------------------------------------------------------
        // Cek apakah vendor saat ini valid untuk BU aktif (database_source harus sama dengan $dbSource)
        $currentVendorValid = false;
        if ($sn->vendor_id) {
            $currentVendor = \App\Models\Vendor::find($sn->vendor_id);
            if ($currentVendor && $currentVendor->database_source === $dbSource) {
                $currentVendorValid = true;
            }
        }

        if ($newVendorId === null) {
            if (!$currentVendorValid) {
                // Cari sibling SN di BU yang sama yang punya vendor valid di database_source ini
                $siblingWithVendor = ProductSerialNumber::where('item_no', $sn->item_no)
                    ->where('business_unit_id', $sn->business_unit_id)
                    ->whereHas('vendor', function ($q) use ($dbSource) {
                        $q->where('database_source', $dbSource);
                    })
                    ->latest()
                    ->first();

                if ($siblingWithVendor) {
                    $newVendorId = $siblingWithVendor->vendor_id;
                    if ($newReceiptDate === null) {
                        $newReceiptDate = $siblingWithVendor->receipt_date;
                    }
                } elseif ($dbSource === 'second') {
                    // Default fallback untuk GSK Second: "CUSTOMER GSK"
                    $defaultGskVendor = \App\Models\Vendor::where('database_source', 'second')
                        ->where(function ($q) {
                            $q->where('vendor_name', 'like', '%CUSTOMER%')
                              ->orWhere('vendor_no', 'like', '%CUSTOMER%');
                        })
                        ->first();
                    if ($defaultGskVendor) {
                        $newVendorId = $defaultGskVendor->id;
                    }
                }
            } else {
                $newVendorId = $sn->vendor_id;
            }
        }

        // Eksekusi Update jika ada perubahan data
        $updateData = [];
        if ($newHpp !== null && (float)$newHpp != (float)$sn->hpp) {
            $updateData['hpp'] = $newHpp;
            $hppUpdated = true;
        }
        if ($newVendorId !== null && $newVendorId != $sn->vendor_id) {
            $updateData['vendor_id'] = $newVendorId;
            $vendorUpdated = true;
        } elseif (!$currentVendorValid && $newVendorId === null && $sn->vendor_id !== null) {
            // Jika vendor lama berasal dari BU lain dan tidak ada vendor valid, kosongkan agar tidak salah asosiasi
            $updateData['vendor_id'] = null;
            $vendorUpdated = true;
        }

        if ($newReceiptDate !== null && $newReceiptDate != $sn->receipt_date) {
            $updateData['receipt_date'] = $newReceiptDate;
        }

        if (!empty($updateData)) {
            $sn->update($updateData);
        }

        $sn->refresh();

        return [
            'sn' => $sn->serial_number,
            'item_no' => $sn->item_no,
            'hpp_updated' => $hppUpdated,
            'old_hpp' => $oldHpp,
            'new_hpp' => (float)$sn->hpp,
            'vendor_updated' => $vendorUpdated,
            'vendor_name' => $sn->vendor?->vendor_name ?? '-',
        ];
    }

    /**
     * Tarik dan sinkronkan dokumen Penerimaan Barang (Receive Item) terbaru
     * 
     * @param string $databaseSource
     * @param int $limit
     * @return array
     */
    public function syncRecentReceiveItems($databaseSource = 'syihab', $limit = 25)
    {
        $recentDocs = $this->accurateService->getRecentReceiveItemList($databaseSource, $limit);
        $totalDocs = count($recentDocs);
        $totalSnUpdated = 0;
        $processedDocDetails = [];

        foreach ($recentDocs as $doc) {
            try {
                $count = $this->syncFromReceiveItem($doc['id'], $databaseSource);
                $totalSnUpdated += $count;
                $processedDocDetails[] = [
                    'id' => $doc['id'],
                    'number' => $doc['number'],
                    'updated_count' => $count
                ];
            } catch (\Exception $e) {
                Log::error("Gagal sync recent receive item {$doc['id']} ({$doc['number']}): " . $e->getMessage());
            }
        }

        return [
            'total_docs' => $totalDocs,
            'total_sn_updated' => $totalSnUpdated,
            'docs' => $processedDocDetails
        ];
    }

    /**
     * Tarik dan sinkronkan dokumen Penerimaan Barang spesifik berdasarkan nomor dokumen atau ID
     * 
     * @param string|int $docNumberOrId
     * @param string $databaseSource
     * @return array
     */
    public function syncSpecificReceiveItemDocument($docNumberOrId, $databaseSource = 'syihab')
    {
        $docId = null;
        if (is_numeric($docNumberOrId)) {
            $docId = (int)$docNumberOrId;
        } else {
            $docId = $this->accurateService->findReceiveItemIdByNumber($docNumberOrId, $databaseSource);
        }

        if (!$docId) {
            throw new \Exception("Dokumen Penerimaan Barang '{$docNumberOrId}' tidak ditemukan di Accurate ({$databaseSource}).");
        }

        $updatedCount = $this->syncFromReceiveItem($docId, $databaseSource);

        return [
            'doc_id' => $docId,
            'updated_count' => $updatedCount
        ];
    }
}

