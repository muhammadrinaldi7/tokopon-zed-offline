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
    public function syncFromAccurate($sku)
    {
        try {
            // Coba ambil dari db source 'syihab' (Produk Baru)
            $snData = $this->accurateService->getSerialNumberPerWarehouse($sku, 'syihab');

            // Jika kosong, mungkin dia barang bekas, coba ambil dari db source 'second'
            if (empty($snData)) {
                $snData = $this->accurateService->getSerialNumberPerWarehouse($sku, 'second');
            }

            if (!empty($snData)) {
                return $this->processSnData($sku, $snData);
            } else {
                // Jika masih kosong, berarti SN tidak ada di kedua database
                // Jika tadinya ada di DB lokal, kita set Unavailable semua
                ProductSerialNumber::where('item_no', $sku)
                    ->where('status', 'Available')
                    ->update(['status' => 'Unavailable']);

                return 0;
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync SN for SKU {$sku}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Memproses mapping dan upsert data SN ke database
     */
    private function processSnData($sku, $accurateData)
    {
        // Ambil list Serial Number yang ada di DB lokal
        // Jangan gunakan pluck('id', 'serial_number') karena PHP akan mengubah key string angka menjadi integer,
        // yang akan membuat query WHERE IN() gagal di MySQL saat membandingkan string.
        $existingSns = ProductSerialNumber::where('item_no', $sku)
            ->where('status', 'Available')
            ->pluck('serial_number')
            ->toArray();

        $processedSerialNumbers = [];
        $upsertData = [];

        foreach ($accurateData as $item) {
            $accurateWarehouseId = $item['warehouse']['id'] ?? null;
            $serialNumberStr = $item['serialNumber']['number'] ?? null;
            $accurateSnId = $item['serialNumber']['id'] ?? null;

            if (!$serialNumberStr || !$accurateWarehouseId) continue;

            // Konversi paksa ke string (berjaga-jaga jika payload API mereturn tipe integer)
            $serialNumberStr = (string) $serialNumberStr;

            // Cari ID gudang lokal berdasarkan ID gudang accurate
            $localWarehouse = Warehouse::where('warehouse_id', $accurateWarehouseId)->first();
            $localWarehouseId = $localWarehouse ? $localWarehouse->id : null;

            $upsertData[] = [
                'accurate_sn_id' => $accurateSnId,
                'item_no'        => $sku,
                'warehouse_id'   => $localWarehouseId,
                'serial_number'  => $serialNumberStr,
                'status'         => 'Available',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            $processedSerialNumbers[] = $serialNumberStr;
        }

        // Jalankan Upsert berdasarkan kolom unik serial_number
        if (count($upsertData) > 0) {
            try {
                ProductSerialNumber::upsert(
                    $upsertData,
                    ['serial_number'], // <- Acuan Pencarian (Unique)
                    ['accurate_sn_id', 'item_no', 'warehouse_id', 'status', 'updated_at'] // <- Yang di-update
                );
                Log::info("Webhook/Sync Berhasil: Upsert " . count($upsertData) . " Serial Number untuk SKU {$sku}");
            } catch (\Exception $e) {
                Log::error("Webhook/Sync Gagal: Gagal upsert Serial Number untuk SKU {$sku}. Error: " . $e->getMessage());
            }
        }

        // Update status menjadi Unavailable untuk SN yang hilang dari API
        $missingSnList = array_diff($existingSns, $processedSerialNumbers);

        // Pastikan array hanya berisi string sebelum di binding ke Eloquent (PDO string binding)
        $missingSnList = array_map('strval', $missingSnList);

        if (count($missingSnList) > 0) {
            ProductSerialNumber::whereIn('serial_number', $missingSnList)
                ->where('status', 'Available')
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
                $localVendor = \App\Models\Vendor::where('accurate_vendor_id', $accurateVendorId)->first();
                if (!$localVendor && $vendorName) {
                    $localVendor = \App\Models\Vendor::where('vendor_name', $vendorName)->first();
                }
                if ($localVendor) {
                    $localVendorId = $localVendor->id;
                }
            }

            // 2. Iterasi detailItem
            $detailItems = $detail['detailItem'] ?? [];
            Log::info("ReceiveItem {$receiveItemId}: Ditemukan " . count($detailItems) . " detail item untuk diproses.");

            foreach ($detailItems as $item) {
                Log::info("ReceiveItem {$receiveItemId}: Memproses iterasi item", ['item_data' => $item]);

                $sku = $item['item']['no'] ?? $item['detailName'] ?? null; // Coba fallback
                // Pada output receive-item, item no ada di `item.no` namun API return array nested, mari pastikan format:
                if (isset($item['item']['no'])) {
                    $sku = $item['item']['no'];
                } elseif (isset($item['itemNo'])) {
                    $sku = $item['itemNo'];
                } elseif (isset($item['no'])) {
                    $sku = $item['no'];
                }

                if (!$sku) continue; // Skip jika tidak ada SKU

                $hpp = $item['item']['unitVendorPrice'] ?? 0;
                $accurateWarehouseId = $item['warehouseId'] ?? ($item['warehouse']['id'] ?? null);

                $localWarehouseId = null;
                if ($accurateWarehouseId) {
                    $localWarehouse = Warehouse::where('warehouse_id', $accurateWarehouseId)->first();
                    if ($localWarehouse) {
                        $localWarehouseId = $localWarehouse->id;
                    }
                }

                $snList = $item['detailSerialNumber'] ?? [];

                foreach ($snList as $snItem) {
                    $sn = $snItem['serialNumber']['number'] ?? null;
                    if (!$sn) continue;
                    $sn = (string)$sn;

                    // 3. Proses Update/Insert ke DB Lokal
                    $existingSn = ProductSerialNumber::where('serial_number', $sn)->first();

                    if ($existingSn) {
                        // Jika sudah ada, update hpp dan vendor_id (biarkan statusnya tidak berubah)
                        $existingSn->update([
                            'hpp' => $hpp,
                            'vendor_id' => $localVendorId,
                            'receipt_date' => $receiptDate,
                        ]);
                        $updatedCount++;
                    } else {
                        // CEK EKSTRA: Pastikan SN ini belum pernah terjual (belum ada di order_items)
                        // order_items menyimpan SN dalam bentuk string dipisah koma
                        $isAlreadySold = \App\Models\OrderItem::where('serial_number', 'LIKE', '%' . $sn . '%')->exists();
                        $finalStatus = $isAlreadySold ? 'Unavailable' : 'Available';

                        // Jika belum ada, buat baru
                        ProductSerialNumber::create([
                            'serial_number' => $sn,
                            'item_no' => $sku,
                            'warehouse_id' => $localWarehouseId,
                            'hpp' => $hpp,
                            'vendor_id' => $localVendorId,
                            'status' => $finalStatus,
                            'receipt_date' => $receiptDate,
                        ]);
                        $updatedCount++;
                    }
                }
            }

            return $updatedCount;
        } catch (\Exception $e) {
            Log::error("Failed to sync Receive Item {$receiveItemId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function syncHppFromNearestCost($itemNo)
    {
        try {
            // Coba ambil dari db source 'syihab' (Produk Baru)
            $costData = null;
            try {
                $costData = $this->accurateService->getNearestCost($itemNo, 'syihab');
            } catch (\Exception $e) {
                // ignore
            }

            if (!$costData) {
                // Coba ambil dari db source 'second' (Produk Bekas)
                try {
                    $costData = $this->accurateService->getNearestCost($itemNo, 'second');
                } catch (\Exception $e) {
                    // ignore
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
                    ->where(function($q) {
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
}
