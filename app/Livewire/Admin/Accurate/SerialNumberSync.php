<?php

namespace App\Livewire\Admin\Accurate;

use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\Warehouse;
use App\Services\AccurateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class SerialNumberSync extends Component
{
    public $isSyncing = false;
    public $itemsToSync = [];
    public $totalItems = 0;
    public $processedItems = 0;
    public $currentItem = '';
    public $logs = [];

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.accurate.serial-number-sync');
    }

    public function startSync()
    {
        $this->isSyncing = true;
        $this->processedItems = 0;
        $this->logs = [];
        $this->itemsToSync = [];

        $this->addLog("Mengumpulkan data SKU yang membutuhkan Serial Number...");

        // Ambil SKU Produk Baru yang has_sn = true
        $newSkus = ProductVariant::whereHas('product', function ($q) {
            $q->where('has_sn', true);
        })->whereNotNull('sku')->where('sku', '!=', '')->pluck('sku')->toArray();

        // Ambil SKU Produk Bekas
        $secondSkus = SecondProductVariant::whereNotNull('sku')->where('sku', '!=', '')->pluck('sku')->toArray();

        // Gabungkan SKU & pastikan unique
        $this->itemsToSync = array_values(array_unique(array_merge($newSkus, $secondSkus)));
        $this->totalItems = count($this->itemsToSync);

        if ($this->totalItems == 0) {
            $this->addLog("Tidak ada produk yang membutuhkan Serial Number.");
            $this->isSyncing = false;
            return;
        }

        $this->addLog("Ditemukan {$this->totalItems} SKU unik. Memulai sinkronisasi dari Accurate...");
        $this->dispatch('sync-next-item');
    }

    #[On('sync-next-item')]
    public function syncNextItem()
    {
        if (empty($this->itemsToSync) || !$this->isSyncing) {
            $this->addLog("Proses sinkronisasi selesai!");
            $this->isSyncing = false;
            return;
        }

        // Ambil item pertama di array
        $sku = array_shift($this->itemsToSync);
        $this->currentItem = "Sedang memproses SKU: {$sku}";

        try {
            $service = app(AccurateService::class);
            // Coba ambil dari db source 'syihab' (Produk Baru)
            $snData = $service->getSerialNumberPerWarehouse($sku, 'syihab');

            // Jika kosong, mungkin dia barang bekas, coba ambil dari db source 'second'
            if (empty($snData)) {
                $snData = $service->getSerialNumberPerWarehouse($sku, 'second');
            }

            if (!empty($snData)) {
                $this->processSnData($sku, $snData);
                $this->addLog("[$sku] Tersinkronisasi " . count($snData) . " Serial Number.");
            } else {
                // Jika masih kosong, berarti SN tidak ada di kedua database
                $this->addLog("[$sku] Tidak ada data Serial Number di Accurate.");
                // Jika tadinya ada di DB lokal, kita set Unavailable semua
                ProductSerialNumber::where('item_no', $sku)
                    ->where('status', 'Available')
                    ->update(['status' => 'Unavailable']);
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync SN for SKU {$sku}: " . $e->getMessage());
            $this->addLog("[$sku] Error: " . $e->getMessage());
        }

        $this->processedItems++;

        // Panggil selanjutnya
        $this->dispatch('sync-next-item');
    }

    private function processSnData($sku, $accurateData)
    {
        $existingSnIds = ProductSerialNumber::where('item_no', $sku)
            ->where('status', 'Available')
            ->pluck('id', 'serial_number')
            ->toArray();

        $processedSerialNumbers = [];
        $upsertData = [];
        Log::info('data proses serial number: ' . json_encode($accurateData));
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
            ProductSerialNumber::upsert(
                $upsertData,
                ['serial_number'],
                ['accurate_sn_id', 'item_no', 'warehouse_id', 'status', 'updated_at']
            );
        }

        // Update status menjadi Unavailable untuk SN yang hilang dari API
        $missingSnList = array_diff(array_keys($existingSnIds), $processedSerialNumbers);
        if (count($missingSnList) > 0) {
            ProductSerialNumber::whereIn('serial_number', $missingSnList)
                ->where('status', 'Available')
                ->update(['status' => 'Unavailable']);
        }
    }

    private function addLog($message)
    {
        array_unshift($this->logs, "[" . now()->format('H:i:s') . "] " . $message);

        // Batasi log maksimal 50 baris agar memori tidak bengkak
        if (count($this->logs) > 50) {
            array_pop($this->logs);
        }
    }
}
