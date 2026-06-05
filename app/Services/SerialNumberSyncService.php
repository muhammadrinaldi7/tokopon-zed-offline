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
        $existingSnIds = ProductSerialNumber::where('item_no', $sku)
            ->where('status', 'Available')
            ->pluck('id', 'serial_number')
            ->toArray();

        $processedSerialNumbers = [];
        $upsertData = [];

        foreach ($accurateData as $item) {
            $accurateWarehouseId = $item['warehouse']['id'] ?? null;
            $serialNumberStr = $item['serialNumber']['number'] ?? null;
            $accurateSnId = $item['serialNumber']['id'] ?? null;

            if (!$serialNumberStr || !$accurateWarehouseId) continue;

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
        $missingSnList = array_diff(array_keys($existingSnIds), $processedSerialNumbers);
        if (count($missingSnList) > 0) {
            ProductSerialNumber::whereIn('serial_number', $missingSnList)
                ->where('status', 'Available')
                ->update(['status' => 'Unavailable']);
        }

        return count($processedSerialNumbers);
    }
}
