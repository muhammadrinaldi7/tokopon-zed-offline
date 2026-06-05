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
            $service = app(\App\Services\SerialNumberSyncService::class);
            $snCount = $service->syncFromAccurate($sku);
            
            if ($snCount > 0) {
                $this->addLog("[$sku] Tersinkronisasi $snCount Serial Number.");
            } else {
                $this->addLog("[$sku] Tidak ada data Serial Number di Accurate.");
            }
        } catch (\Exception $e) {
            $this->addLog("[$sku] Error: " . $e->getMessage());
        }

        $this->processedItems++;

        // Panggil selanjutnya
        $this->dispatch('sync-next-item');
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
