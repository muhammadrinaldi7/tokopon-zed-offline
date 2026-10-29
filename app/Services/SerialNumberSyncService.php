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
        $sku = (string) $sku;
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
        $sku = (string) $sku;
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

        $existingReceiptDates = ProductSerialNumber::where('item_no', $sku)
            ->whereNotNull('receipt_date')
            ->pluck('receipt_date', 'serial_number')
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $processedSerialNumbers = [];
        $upsertData = [];

        foreach ($accurateData as $item) {
            $accurateWarehouseId = $item['warehouse']['id'] ?? null;
            $serialNumberStr = $item['serialNumber']['number'] ?? null;
            $accurateSnId = $item['serialNumber']['id'] ?? null;
            $createDateStr = $item['serialNumber']['createDate'] ?? null;

            if (!$serialNumberStr || !$accurateWarehouseId) continue;
            $serialNumberStr = (string) $serialNumberStr;

            $localWarehouseId = $warehouseMap[$accurateWarehouseId] ?? null;

            $parsedCreateDate = null;
            if ($createDateStr) {
                $datePart = explode(' ', trim($createDateStr))[0];
                try {
                    $parsedCreateDate = \Carbon\Carbon::createFromFormat('d/m/Y', $datePart)->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        $parsedCreateDate = \Carbon\Carbon::parse($createDateStr)->format('Y-m-d');
                    } catch (\Exception $e2) {
                        $parsedCreateDate = null;
                    }
                }
            }

            $upsertData[] = [
                'accurate_sn_id'      => $accurateSnId,
                'item_no'             => $sku,
                'warehouse_id'        => $localWarehouseId,
                'business_unit_id'    => $bu ? $bu->id : null,
                'product_accurate_id' => $productAccurateId,
                'serial_number'       => $serialNumberStr,
                'status'              => 'Available',
                'receipt_date'        => $existingReceiptDates[$serialNumberStr] ?? $parsedCreateDate,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];

            $processedSerialNumbers[] = $serialNumberStr;
        }

        // Jalankan Upsert berdasarkan kolom unik serial_number
        if (count($upsertData) > 0) {
            try {
                ProductSerialNumber::upsert(
                    $upsertData,
                    ['serial_number'], // <- Acuan Pencarian (Unique)
                    ['accurate_sn_id', 'item_no', 'warehouse_id', 'business_unit_id', 'product_accurate_id', 'status', 'receipt_date', 'updated_at'] // <- Yang di-update
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

    /**
     * Cari atau buat otomatis Vendor lokal dari objek vendor Accurate
     */
    private function resolveOrCreateLocalVendor(?array $vendorData, string $databaseSource): ?int
    {
        if (empty($vendorData)) {
            return null;
        }

        $vendorName = $vendorData['name'] ?? null;
        $accurateVendorId = $vendorData['id'] ?? null;
        $vendorNo = $vendorData['vendorNo'] ?? ($vendorData['no'] ?? null);

        $localVendor = null;
        if ($accurateVendorId) {
            $localVendor = \App\Models\Vendor::where('accurate_vendor_id', $accurateVendorId)
                ->where('database_source', $databaseSource)
                ->first();
        }
        if (!$localVendor && $vendorNo) {
            $localVendor = \App\Models\Vendor::where('vendor_no', $vendorNo)
                ->where('database_source', $databaseSource)
                ->first();
        }
        if (!$localVendor && $vendorName) {
            $localVendor = \App\Models\Vendor::where('vendor_name', $vendorName)
                ->where('database_source', $databaseSource)
                ->first();
        }

        if (!$localVendor && ($accurateVendorId || $vendorName)) {
            try {
                $localVendor = \App\Models\Vendor::create([
                    'accurate_vendor_id' => $accurateVendorId,
                    'vendor_no'          => $vendorNo,
                    'vendor_name'        => $vendorName ?: ('Vendor #' . $accurateVendorId),
                    'database_source'    => $databaseSource,
                ]);
            } catch (\Exception $e) {
                Log::warning("Gagal auto-create vendor lokal '{$vendorName}' ({$databaseSource}): " . $e->getMessage());
            }
        }

        return $localVendor ? $localVendor->id : null;
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

            // 1. Ekstrak Vendor (auto-create jika belum ada di tabel lokal)
            $localVendorId = $this->resolveOrCreateLocalVendor($detail['vendor'] ?? null, $databaseSource);

            // 2. Iterasi detailItem (isFromReceiveItem = true -> tanggal Receive Item adalah sumber utama aging)
            $detailItems = $detail['detailItem'] ?? [];
            Log::info("ReceiveItem {$receiveItemId}: Ditemukan " . count($detailItems) . " detail item untuk diproses.");

            return $this->processDocumentDetailItems($detailItems, $databaseSource, $receiptDate, $localVendorId, true);
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

            // 1. Ekstrak Vendor (auto-create jika belum ada di tabel lokal)
            $localVendorId = $this->resolveOrCreateLocalVendor($detail['vendor'] ?? null, $databaseSource);

            // 2. Iterasi detailItem (isFromReceiveItem = false -> jangan timpa receipt_date jika sudah ada dari Receive Item)
            $detailItems = $detail['detailItem'] ?? [];
            Log::info("PurchaseInvoice {$purchaseInvoiceId}: Ditemukan " . count($detailItems) . " detail item untuk diproses.");

            return $this->processDocumentDetailItems($detailItems, $databaseSource, $receiptDate, $localVendorId, false);
        } catch (\Exception $e) {
            Log::error("Failed to sync Purchase Invoice {$purchaseInvoiceId}: " . $e->getMessage());
            throw $e;
        }
    }

    private function processDocumentDetailItems($detailItems, $databaseSource, $receiptDate, $localVendorId, $isFromReceiveItem = true)
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

            $hpp = $item['unitPrice'] ?? $item['itemCost'] ?? ($item['availableUnitPrice'] ?? 0);
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
                        'business_unit_id' => $bu->id,
                    ];
                    if ((float)$hpp > 0 || (float)$existingSn->hpp <= 0) {
                        $updatePayload['hpp'] = $hpp;
                    }
                    if ($localVendorId !== null) {
                        $updatePayload['vendor_id'] = $localVendorId;
                    }
                    // Jika dari Receive Item, selalu gunakan tanggal Receive Item sebagai acuan aging.
                    // Jika dari Purchase Invoice, hanya isi jika receipt_date masih kosong agar tidak menimpa tanggal Receive Item.
                    if ($receiptDate !== null && ($isFromReceiveItem || empty($existingSn->receipt_date))) {
                        $updatePayload['receipt_date'] = $receiptDate;
                    }
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
        $itemNo = (string) $itemNo;
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
        $sku = (string) $sku;
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
        $oldVendorId = $sn->vendor_id;

        $newHpp = null;
        $newVendorId = null;
        $newReceiptDate = null;
        $sourceDocNumber = null;

        // -------------------------------------------------------------
        // TIER 1: Lacak Histori Mutasi Asli IMEI di Accurate API (/report/serial-number-mutation.do)
        // Memastikan receipt_date sesuai dengan dokumen Receive Item (RI) / Faktur Pembelian (PI) asli di Accurate
        // -------------------------------------------------------------
        if (!empty($sn->item_no) && !empty($sn->serial_number)) {
            try {
                $mutations = $this->accurateService->getSerialNumberMutation($sn->item_no, $sn->serial_number, $dbSource);

                if (!empty($mutations) && is_array($mutations)) {
                    $riTx = null;
                    $piTx = null;
                    $firstInboundTx = null;

                    foreach ($mutations as $mut) {
                        $tx = $mut['transaction'] ?? null;
                        if (!$tx || !is_array($tx)) continue;

                        $txType = strtoupper((string)($tx['type'] ?? ''));
                        $txQty = (float)($tx['quantity'] ?? 0);

                        if ($txQty > 0) {
                            if ($txType === 'RI' && !$riTx) {
                                $riTx = $tx;
                            } elseif ($txType === 'PI' && !$piTx) {
                                $piTx = $tx;
                            } elseif ($txType !== 'IT' && !$firstInboundTx) {
                                $firstInboundTx = $tx;
                            }
                        }
                    }

                    // Prioritas 1A: Dokumen Receive Item (Penerimaan Barang) Asli di Accurate
                    if ($riTx) {
                        $sourceDocNumber = $riTx['number'] ?? null;
                        if (!empty($riTx['transactionDate'])) {
                            try {
                                $newReceiptDate = \Carbon\Carbon::parse($riTx['transactionDate'])->format('Y-m-d');
                            } catch (\Exception $e) {
                                $newReceiptDate = null;
                            }
                        }

                        if ($sourceDocNumber) {
                            $riId = $this->accurateService->findReceiveItemIdByNumber($sourceDocNumber, $dbSource);
                            if ($riId) {
                                $this->syncFromReceiveItem($riId, $dbSource);
                                $sn->refresh();
                                if ((float)$sn->hpp > 0) {
                                    $newHpp = (float)$sn->hpp;
                                }
                                if ($sn->vendor_id) {
                                    $newVendorId = $sn->vendor_id;
                                }
                                if ($sn->receipt_date) {
                                    $newReceiptDate = \Carbon\Carbon::parse($sn->receipt_date)->format('Y-m-d');
                                }
                            }
                        }
                    }
                    // Prioritas 1B: Dokumen Purchase Invoice (Faktur Pembelian) Asli jika tidak melalui RI terpisah
                    elseif ($piTx) {
                        $sourceDocNumber = $piTx['number'] ?? null;
                        if (!empty($piTx['transactionDate'])) {
                            try {
                                $newReceiptDate = \Carbon\Carbon::parse($piTx['transactionDate'])->format('Y-m-d');
                            } catch (\Exception $e) {
                                $newReceiptDate = null;
                            }
                        }

                        if ($sourceDocNumber) {
                            $this->syncFromPurchaseInvoice($sourceDocNumber, $dbSource);
                            $sn->refresh();
                            if ((float)$sn->hpp > 0) {
                                $newHpp = (float)$sn->hpp;
                            }
                            if ($sn->vendor_id) {
                                $newVendorId = $sn->vendor_id;
                            }
                            if ($sn->receipt_date) {
                                $newReceiptDate = \Carbon\Carbon::parse($sn->receipt_date)->format('Y-m-d');
                            }
                        }
                    }
                    // Prioritas 1C: Mutasi masuk lainnya (misal IA / Penyesuaian Persediaan)
                    elseif ($firstInboundTx && !empty($firstInboundTx['transactionDate'])) {
                        $sourceDocNumber = $firstInboundTx['number'] ?? null;
                        try {
                            $newReceiptDate = \Carbon\Carbon::parse($firstInboundTx['transactionDate'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            $newReceiptDate = null;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Gagal melacak mutasi Accurate untuk SN {$sn->serial_number} (source: {$dbSource}): " . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // TIER 1.5: Fallback Tanggal Terima per-IMEI dari createDate Accurate (/report/serial-number-per-warehouse.do)
        // Hanya dijalankan jika tanggal terima dari mutasi dokumen belum ditemukan
        // -------------------------------------------------------------
        if ($newReceiptDate === null && empty($sn->receipt_date) && !empty($sn->item_no)) {
            try {
                $warehouseSnList = $this->accurateService->getSerialNumberPerWarehouse($sn->item_no, $dbSource);
                foreach ($warehouseSnList as $wItem) {
                    $num = (string)($wItem['serialNumber']['number'] ?? '');
                    if ($num === (string)$sn->serial_number && !empty($wItem['serialNumber']['createDate'])) {
                        $datePart = explode(' ', trim($wItem['serialNumber']['createDate']))[0];
                        try {
                            $newReceiptDate = \Carbon\Carbon::createFromFormat('d/m/Y', $datePart)->format('Y-m-d');
                        } catch (\Exception $e) {
                            try {
                                $newReceiptDate = \Carbon\Carbon::parse($wItem['serialNumber']['createDate'])->format('Y-m-d');
                            } catch (\Exception $e2) {
                                $newReceiptDate = null;
                            }
                        }
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Abaikan jika gagal
            }
        }

        // -------------------------------------------------------------
        // TIER 2: Cari dari transaksi lokal SellPhone / Buyback
        // (Khususnya jika perangkat dibeli dari customer oleh GSK / Second)
        // -------------------------------------------------------------
        if ($newHpp === null || $newVendorId === null || ($newReceiptDate === null && empty($sn->receipt_date))) {
            $sellPhone = \App\Models\SellPhone::where('imei', $sn->serial_number)
                ->when($sn->business_unit_id, function ($q) use ($sn) {
                    $q->where('business_unit_id', $sn->business_unit_id);
                })
                ->latest()
                ->first();

            if ($sellPhone) {
                if ($newHpp === null && $sellPhone->appraised_value > 0) {
                    $newHpp = (float)$sellPhone->appraised_value;
                }
                if ($newReceiptDate === null && empty($sn->receipt_date) && $sellPhone->created_at) {
                    $newReceiptDate = $sellPhone->created_at->format('Y-m-d');
                }

                if ($newVendorId === null) {
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
            }
        }

        // -------------------------------------------------------------
        // TIER 3: Cari dari transaksi lokal Inbound PO (DeviceInspection -> PurchaseOrderItem -> PurchaseOrder)
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
                    if ($newReceiptDate === null && empty($sn->receipt_date) && $po->po_date) {
                        $newReceiptDate = \Carbon\Carbon::parse($po->po_date)->format('Y-m-d');
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // TIER 4: Tarik dari Accurate API (Nearest Cost) untuk Business Unit ini jika HPP belum ditemukan
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
        // TIER 5: Inferensi Vendor dari Sibling SN pada BU yang sama (atau Vendor Default BU) jika belum ditemukan
        // CATATAN PENTING: Jangan menyalin receipt_date dari sibling SN agar data aging per-IMEI tetap akurat!
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
        }
        if ($newVendorId !== null && $newVendorId != $sn->vendor_id) {
            $updateData['vendor_id'] = $newVendorId;
        } elseif (!$currentVendorValid && $newVendorId === null && $sn->vendor_id !== null) {
            // Jika vendor lama berasal dari BU lain dan tidak ada vendor valid, kosongkan agar tidak salah asosiasi
            $updateData['vendor_id'] = null;
        }

        $currentReceiptDateYmd = $sn->receipt_date ? \Carbon\Carbon::parse($sn->receipt_date)->format('Y-m-d') : null;
        if ($newReceiptDate !== null && $newReceiptDate !== $currentReceiptDateYmd) {
            $updateData['receipt_date'] = $newReceiptDate;
        }

        if (!empty($updateData)) {
            $sn->update($updateData);
        }

        $sn->refresh();

        $hppUpdated = (float)$sn->hpp != $oldHpp;
        $vendorUpdated = $sn->vendor_id != $oldVendorId;

        return [
            'sn' => $sn->serial_number,
            'item_no' => $sn->item_no,
            'hpp_updated' => $hppUpdated,
            'old_hpp' => $oldHpp,
            'new_hpp' => (float)$sn->hpp,
            'vendor_updated' => $vendorUpdated,
            'vendor_name' => $sn->vendor?->vendor_name ?? '-',
            'receipt_date' => $sn->receipt_date ? \Carbon\Carbon::parse($sn->receipt_date)->format('d/m/Y') : '-',
            'source_doc' => $sourceDocNumber,
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

    /**
     * Sinkronisasi HPP Rata-Rata (Balance Unit Cost / Nearest Cost) & Vendor untuk 1 produk Non-SN
     *
     * @param int|string $productAccurateId
     * @return array
     */
    public function syncSingleNonSnProduct($productAccurateId)
    {
        $product = \App\Models\ProductAccurate::find($productAccurateId);
        if (!$product) {
            throw new \Exception("Produk Accurate dengan ID {$productAccurateId} tidak ditemukan.");
        }

        $bu = $product->business_unit_id ? \App\Models\BusinessUnit::find($product->business_unit_id) : null;
        $dbSource = $bu ? $bu->code : ($product->database_source ?: 'syihab');

        $sku = (string) $product->item_no;
        $newCost = null;
        $newVendorName = null;

        // TIER 1: Tarik detail item dari Accurate API (/item/detail.do) -> balanceUnitCost
        try {
            $itemDetail = $this->accurateService->itemDetailDo($sku, $dbSource);
            if ($itemDetail) {
                // balanceUnitCost adalah moving average cost resmi di Accurate
                if (!empty($itemDetail['balanceUnitCost']) && (float)$itemDetail['balanceUnitCost'] > 0) {
                    $newCost = (float)$itemDetail['balanceUnitCost'];
                }

                // Cek vendor jika ada di detail item (misal vendor / defaultVendor)
                if (!empty($itemDetail['vendor']['name'])) {
                    $newVendorName = $itemDetail['vendor']['name'];
                } elseif (!empty($itemDetail['defaultVendor']['name'])) {
                    $newVendorName = $itemDetail['defaultVendor']['name'];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Gagal fetch itemDetailDo untuk Non-SN {$sku}: " . $e->getMessage());
        }

        // TIER 2: Fallback ke nearestCost (/item/get-nearest-cost.do) jika balanceUnitCost 0 / belum ada
        if ($newCost === null || $newCost <= 0) {
            try {
                $costData = $this->accurateService->getNearestCost($sku, $dbSource);
                if (is_numeric($costData) && (float)$costData > 0) {
                    $newCost = (float)$costData;
                } elseif (is_array($costData)) {
                    $val = (float)($costData['cost'] ?? ($costData['nearestCost'] ?? current($costData)));
                    if ($val > 0) {
                        $newCost = $val;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Gagal fetch nearestCost untuk Non-SN {$sku}: " . $e->getMessage());
            }
        }

        // TIER 3: Cari Vendor dari Purchase Order Item lokal jika vendor belum ada
        if (empty($newVendorName)) {
            $poItem = \App\Models\PurchaseOrderItem::where('item_no', $sku)
                ->whereHas('purchaseOrder', function ($q) use ($dbSource) {
                    $q->where('database_source', $dbSource);
                })
                ->latest()
                ->first();
            if ($poItem && $poItem->purchaseOrder && $poItem->purchaseOrder->vendor) {
                $newVendorName = $poItem->purchaseOrder->vendor->vendor_name;
                if (($newCost === null || $newCost <= 0) && (float)$poItem->unit_price > 0) {
                    $newCost = (float)$poItem->unit_price;
                }
            }
        }

        // Simpan pembaruan ke ProductAccurate
        $updatePayload = [];
        if ($newCost !== null && $newCost > 0) {
            $updatePayload['base_cost'] = $newCost;
        }
        if (!empty($newVendorName)) {
            $updatePayload['vendor_name'] = $newVendorName;
        }

        if (!empty($updatePayload)) {
            $product->update($updatePayload);
        }

        return [
            'product'     => $product,
            'sku'         => $sku,
            'name'        => $product->name,
            'new_cost'    => $newCost ?? (float)$product->base_cost,
            'vendor_name' => $newVendorName ?? $product->vendor_name,
            'updated'     => !empty($updatePayload),
        ];
    }

    /**
     * Sinkronisasi HPP Rata-Rata untuk SKU Non-SN via Nearest Cost / Balance Cost
     *
     * @param string $sku
     * @param string|null $databaseSource
     * @return int
     */
    public function syncNonSnHppFromAccurate($sku, $databaseSource = null)
    {
        $products = \App\Models\ProductAccurate::where('item_no', $sku)
            ->where('has_sn', false)
            ->when($databaseSource, function ($q) use ($databaseSource) {
                $q->where('database_source', $databaseSource);
            })
            ->get();

        if ($products->isEmpty()) {
            return 0;
        }

        $source = $databaseSource ?: ($products->first()->database_source ?: 'syihab');
        $cost = 0;

        // Coba itemDetailDo dulu untuk balanceUnitCost
        try {
            $detail = $this->accurateService->itemDetailDo($sku, $source);
            if (!empty($detail['balanceUnitCost']) && (float)$detail['balanceUnitCost'] > 0) {
                $cost = (float)$detail['balanceUnitCost'];
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Fallback nearest-cost
        if ($cost <= 0) {
            try {
                $costData = $this->accurateService->getNearestCost($sku, $source);
                if (is_numeric($costData) && (float)$costData > 0) {
                    $cost = (float)$costData;
                } elseif (is_array($costData)) {
                    $val = (float)($costData['cost'] ?? ($costData['nearestCost'] ?? current($costData)));
                    if ($val > 0) {
                        $cost = $val;
                    }
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        if ($cost > 0) {
            $count = \App\Models\ProductAccurate::where('item_no', $sku)
                ->where('has_sn', false)
                ->update(['base_cost' => $cost]);
            return $count;
        }

        return 0;
    }
}

