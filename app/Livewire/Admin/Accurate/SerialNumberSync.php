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
    public $isSyncingVendor = false;
    public $isSyncingHpp = false;
    public $itemsToSync = [];
    public $totalItems = 0;
    public $processedItems = 0;
    public $currentItem = '';
    public $logs = [];
    public $businessUnitId = '';
    public $businessUnits = [];

    public function mount()
    {
        $this->businessUnits = \App\Models\BusinessUnit::where('is_active', true)->get();
    }

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
            $service = app(\App\Services\SerialNumberSyncService::class);
            $sourceCode = null;
            if ($this->businessUnitId) {
                $sourceCode = \App\Models\BusinessUnit::find($this->businessUnitId)?->code;
            }
            $snCount = $service->syncFromAccurate($sku, $sourceCode);
            
            if ($snCount > 0) {
                $this->addLog("[$sku] Tersinkronisasi $snCount Serial Number.");
            } else {
                $this->addLog("[$sku] Tidak ada data Serial Number di Accurate.");
            }

            // Sync harga jual dari Accurate
            $priceResult = $service->syncPriceFromAccurate($sku, $sourceCode);
            if ($priceResult['updated']) {
                $this->addLog("[$sku] 💰 Harga diperbarui: Rp " . number_format($priceResult['old_price']) . " → Rp " . number_format($priceResult['new_price']));
            }
        } catch (\Exception $e) {
            $this->addLog("[$sku] Error: " . $e->getMessage());
        }

        $this->processedItems++;

        // Panggil selanjutnya
        $this->dispatch('sync-next-item');
    }

    public function startSyncVendor()
    {
        $this->isSyncingVendor = true;
        $this->processedItems = 0;
        $this->logs = [];
        $this->itemsToSync = [];

        $this->addLog("Mengumpulkan seluruh dokumen Penerimaan Barang (Receive Item)...");

        try {
            $accurateService = app(\App\Services\AccurateService::class);
            
            $sources = [];
            if ($this->businessUnitId) {
                $bu = \App\Models\BusinessUnit::find($this->businessUnitId);
                if ($bu) $sources[] = $bu->code;
            } else {
                $sources = \App\Models\BusinessUnit::where('is_active', true)->pluck('code')->toArray();
            }

            foreach ($sources as $source) {
                try {
                    $ids = $accurateService->getReceiveItemList($source);
                    $data = array_map(function($id) use ($source) { return ['id' => $id, 'source' => $source]; }, $ids);
                    $this->itemsToSync = array_merge($this->itemsToSync, $data);
                } catch (\Exception $e) {
                    $this->addLog("Gagal ambil receive item dari {$source}: " . $e->getMessage());
                }
            }

            $this->totalItems = count($this->itemsToSync);

            if ($this->totalItems == 0) {
                $this->addLog("Tidak ada dokumen Penerimaan Barang ditemukan.");
                $this->isSyncingVendor = false;
                return;
            }

            $this->addLog("Ditemukan {$this->totalItems} dokumen Penerimaan Barang. Memulai proses...");
            $this->dispatch('sync-next-vendor-item');
        } catch (\Exception $e) {
            $this->addLog("Error mengumpulkan dokumen: " . $e->getMessage());
            $this->isSyncingVendor = false;
        }
    }

    #[On('sync-next-vendor-item')]
    public function syncNextVendorItem()
    {
        if (empty($this->itemsToSync) || !$this->isSyncingVendor) {
            $this->addLog("Proses sinkronisasi Vendor & HPP via Receive Item selesai!");
            $this->isSyncingVendor = false;
            return;
        }

        // Ambil item pertama di array
        $task = array_shift($this->itemsToSync);
        $receiveItemId = $task['id'];
        $source = $task['source'];

        $this->currentItem = "Sedang memproses dokumen ID: {$receiveItemId} ({$source})";

        try {
            $service = app(\App\Services\SerialNumberSyncService::class);
            $snCount = $service->syncFromReceiveItem($receiveItemId, $source);
            
            if ($snCount > 0) {
                $this->addLog("[ID {$receiveItemId}] Berhasil update/insert $snCount Serial Number.");
            } else {
                $this->addLog("[ID {$receiveItemId}] Tidak ada Serial Number baru/diperbarui.");
            }
        } catch (\Exception $e) {
            $this->addLog("[ID {$receiveItemId}] Error: " . $e->getMessage());
        }

        $this->processedItems++;

        // Panggil selanjutnya
        $this->dispatch('sync-next-vendor-item');
    }

    public function startSyncHpp()
    {
        $this->isSyncingHpp = true;
        $this->processedItems = 0;
        $this->logs = [];
        $this->itemsToSync = [];

        $this->addLog("Mengumpulkan data Item yang belum memiliki HPP...");

        // Ambil list item_no yang unique dari product_serial_numbers yang hpp-nya 0 atau null
        $itemNos = \App\Models\ProductSerialNumber::where(function($q) {
            $q->whereNull('hpp')
              ->orWhere('hpp', 0)
              ->orWhere('hpp', '0');
        })->whereNotNull('item_no')
          ->distinct()
          ->pluck('item_no')
          ->toArray();

        $this->itemsToSync = $itemNos;
        $this->totalItems = count($this->itemsToSync);

        if ($this->totalItems == 0) {
            $this->addLog("Tidak ada item yang membutuhkan sinkronisasi HPP.");
            $this->isSyncingHpp = false;
            return;
        }

        $this->addLog("Ditemukan {$this->totalItems} Item unik. Memulai sinkronisasi HPP dari Accurate...");
        $this->dispatch('sync-next-hpp-item');
    }

    #[On('sync-next-hpp-item')]
    public function syncNextHppItem()
    {
        if (empty($this->itemsToSync) || !$this->isSyncingHpp) {
            $this->addLog("Proses sinkronisasi HPP selesai!");
            $this->isSyncingHpp = false;
            return;
        }

        // Ambil item_no pertama di array
        $itemNo = array_shift($this->itemsToSync);
        $this->currentItem = "Sedang memproses HPP untuk Item No: {$itemNo}";

        try {
            $service = app(\App\Services\SerialNumberSyncService::class);
            $sourceCode = null;
            if ($this->businessUnitId) {
                $sourceCode = \App\Models\BusinessUnit::find($this->businessUnitId)?->code;
            }
            $updatedCount = $service->syncHppFromNearestCost($itemNo, $sourceCode);
            
            if ($updatedCount > 0) {
                $this->addLog("[{$itemNo}] Berhasil update $updatedCount data HPP.");
            } else {
                $this->addLog("[{$itemNo}] Tidak ada data HPP yang diperbarui atau cost 0.");
            }
        } catch (\Exception $e) {
            $this->addLog("[{$itemNo}] Error: " . $e->getMessage());
        }

        $this->processedItems++;

        // Panggil selanjutnya
        $this->dispatch('sync-next-hpp-item');
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
