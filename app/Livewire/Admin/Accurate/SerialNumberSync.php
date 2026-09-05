<?php

namespace App\Livewire\Admin\Accurate;

use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\AccurateService;
use App\Services\SerialNumberSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class SerialNumberSync extends Component
{
    use WithPagination;

    // Filter & Search Properties
    public $search = '';
    public $filterTab = 'missing_both'; // missing_both, missing_vendor, missing_hpp, all_missing, all
    public $businessUnitId = '';
    public $warehouseId = '';
    public $snStatus = 'Available'; // Available, Unavailable, all
    public $perPage = 25;

    // Legacy / Mass Sync States
    public $isSyncing = false;
    public $isSyncingVendor = false;
    public $isSyncingHpp = false;
    public $itemsToSync = [];
    public $totalItems = 0;
    public $processedItems = 0;
    public $currentItem = '';
    public $logs = [];

    // Modal Tarik Dokumen Penerimaan Barang (Receive Item)
    public $showDocModal = false;
    public $docInput = '';
    public $docBusinessUnitId = '';
    public $recentDocsLimit = 25;

    // Modal Edit Manual Vendor & HPP
    public $showEditModal = false;
    public $editingSnId = null;
    public $editingSnNumber = '';
    public $editingProductName = '';
    public $editVendorId = '';
    public $editHpp = 0;
    public $editReceiptDate = '';

    // Collapsible legacy section toggle
    public $showLegacySection = false;

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\BusinessUnit>|array */
    public $businessUnits = [];

    public function mount()
    {
        $this->businessUnits = BusinessUnit::where('is_active', true)->get();
        $firstBu = collect($this->businessUnits)->first();
        $this->docBusinessUnitId = $firstBu?->id ?? '';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterTab()
    {
        $this->resetPage();
    }

    public function updatingBusinessUnitId()
    {
        $this->resetPage();
        $this->warehouseId = '';
    }

    public function updatingWarehouseId()
    {
        $this->resetPage();
    }

    public function updatingSnStatus()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        // 1. Base Query
        $query = ProductSerialNumber::with(['warehouse', 'businessUnit', 'vendor', 'productAccurate']);

        // 2. Filter Unit Usaha
        if ($this->businessUnitId) {
            $query->where('business_unit_id', $this->businessUnitId);
        }

        // 3. Filter Gudang
        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        // 4. Filter Status SN
        if ($this->snStatus !== 'all') {
            $query->where('status', $this->snStatus);
        }

        // 5. Filter Tab Kondisi Kelengkapan Data
        switch ($this->filterTab) {
            case 'missing_both':
                $query->whereNull('vendor_id')->where(function ($q) {
                    $q->whereNull('hpp')->orWhere('hpp', 0)->orWhere('hpp', '0');
                });
                break;
            case 'missing_vendor':
                $query->whereNull('vendor_id');
                break;
            case 'missing_hpp':
                $query->where(function ($q) {
                    $q->whereNull('hpp')->orWhere('hpp', 0)->orWhere('hpp', '0');
                });
                break;
            case 'all_missing':
                $query->where(function ($q) {
                    $q->whereNull('vendor_id')
                        ->orWhereNull('hpp')
                        ->orWhere('hpp', 0)
                        ->orWhere('hpp', '0');
                });
                break;
            case 'all':
            default:
                // Tidak ada filter kelengkapan
                break;
        }

        // 6. Pencarian (IMEI, SKU, atau Nama Produk)
        if (!empty(trim($this->search))) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('serial_number', 'like', $searchTerm)
                    ->orWhere('item_no', 'like', $searchTerm)
                    ->orWhereHas('productAccurate', function ($pa) use ($searchTerm) {
                        $pa->where('name', 'like', $searchTerm);
                    });
            });
        }

        $serialNumbers = $query->orderByDesc('id')->paginate($this->perPage);

        // 7. Ringkasan Statistik Global (Untuk 4 KPI Cards)
        $statsQuery = ProductSerialNumber::query();
        if ($this->businessUnitId) {
            $statsQuery->where('business_unit_id', $this->businessUnitId);
        }

        $stats = [
            'total_available' => (clone $statsQuery)->where('status', 'Available')->count(),
            'missing_both' => (clone $statsQuery)->whereNull('vendor_id')->where(function ($q) {
                $q->whereNull('hpp')->orWhere('hpp', 0)->orWhere('hpp', '0');
            })->count(),
            'missing_vendor' => (clone $statsQuery)->whereNull('vendor_id')->count(),
            'missing_hpp' => (clone $statsQuery)->where(function ($q) {
                $q->whereNull('hpp')->orWhere('hpp', 0)->orWhere('hpp', '0');
            })->count(),
        ];

        // 8. Opsi Gudang & Vendor
        $warehouses = Warehouse::when($this->businessUnitId, function ($q) {
            $q->where('business_unit_id', $this->businessUnitId);
        })->orderBy('name')->get();

        $vendors = Vendor::orderBy('vendor_name')->get();

        return view('livewire.admin.accurate.serial-number-sync', [
            'serialNumbers' => $serialNumbers,
            'stats' => $stats,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'businessUnits' => $this->businessUnits,
            'filterTab' => $this->filterTab,
            'isSyncing' => $this->isSyncing,
            'isSyncingVendor' => $this->isSyncingVendor,
            'isSyncingHpp' => $this->isSyncingHpp,
            'totalItems' => $this->totalItems,
            'processedItems' => $this->processedItems,
            'currentItem' => $this->currentItem,
            'logs' => $this->logs,
            'showDocModal' => $this->showDocModal,
            'showEditModal' => $this->showEditModal,
            'editingSnNumber' => $this->editingSnNumber,
            'editingProductName' => $this->editingProductName,
        ]);
    }

    /**
     * Sinkronisasi presisi untuk 1 IMEI terpilih
     */
    public function syncSingleSn($snId)
    {
        try {
            /** @var SerialNumberSyncService $service */
            $service = app(SerialNumberSyncService::class);
            $res = $service->syncSingleSerialNumber($snId);

            $msg = "SN [{$res['sn']}] berhasil disinkronkan. HPP: Rp " . number_format($res['new_hpp']) . " | Vendor: {$res['vendor_name']}";
            $this->addLog($msg);
            $this->dispatch('toast', title: 'Sinkronisasi Berhasil', message: $msg, type: 'success');
        } catch (\Exception $e) {
            $this->addLog("Gagal sinkron SN ID {$snId}: " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Sinkronisasi', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Buka Modal Tarik Dokumen Penerimaan Barang
     */
    public function openDocModal()
    {
        $firstBu = collect($this->businessUnits)->first();
        $this->docBusinessUnitId = $this->businessUnitId ?: ($firstBu?->id ?? '');
        $this->showDocModal = true;
    }

    public function closeDocModal()
    {
        $this->showDocModal = false;
        $this->docInput = '';
    }

    /**
     * Tarik Dokumen Penerimaan Barang Spesifik berdasarkan Nomor / ID
     */
    public function syncSpecificDoc()
    {
        $this->validate([
            'docInput' => 'required|string',
        ], [
            'docInput.required' => 'Nomor atau ID dokumen penerimaan barang wajib diisi.',
        ]);

        $bu = BusinessUnit::find($this->docBusinessUnitId);
        $source = $bu ? $bu->code : 'syihab';

        try {
            /** @var SerialNumberSyncService $service */
            $service = app(SerialNumberSyncService::class);
            $res = $service->syncSpecificReceiveItemDocument(trim($this->docInput), $source);

            $msg = "Dokumen '{$this->docInput}' ({$source}) berhasil diproses. {$res['updated_count']} Serial Number diperbarui.";
            $this->addLog($msg);
            $this->dispatch('toast', title: 'Dokumen Berhasil Diproses', message: $msg, type: 'success');
            $this->closeDocModal();
        } catch (\Exception $e) {
            $this->addLog("Gagal memproses dokumen '{$this->docInput}': " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Memproses Dokumen', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Tarik N Dokumen Penerimaan Barang Terbaru dari Accurate
     */
    public function syncRecentDocs()
    {
        $bu = BusinessUnit::find($this->docBusinessUnitId);
        $source = $bu ? $bu->code : 'syihab';

        try {
            /** @var SerialNumberSyncService $service */
            $service = app(SerialNumberSyncService::class);
            $res = $service->syncRecentReceiveItems($source, $this->recentDocsLimit);

            $msg = "Berhasil menarik {$res['total_docs']} dokumen terbaru ({$source}). {$res['total_sn_updated']} Serial Number diperbarui.";
            $this->addLog($msg);
            $this->dispatch('toast', title: 'Penarikan Dokumen Selesai', message: $msg, type: 'success');
            $this->closeDocModal();
        } catch (\Exception $e) {
            $this->addLog("Gagal menarik dokumen terbaru: " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Menarik Dokumen', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Jalankan Sinkronisasi HPP (Nearest Cost) khusus untuk SKU yang sedang terfilter
     */
    public function startSyncFilteredHpp()
    {
        $this->isSyncingHpp = true;
        $this->processedItems = 0;
        $this->logs = [];
        $this->itemsToSync = [];

        $this->addLog("Mengumpulkan data SKU dari filter saat ini yang belum memiliki HPP...");

        $query = ProductSerialNumber::query();
        if ($this->businessUnitId) {
            $query->where('business_unit_id', $this->businessUnitId);
        }
        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }
        if ($this->snStatus !== 'all') {
            $query->where('status', $this->snStatus);
        }

        $itemNos = $query->where(function ($q) {
            $q->whereNull('hpp')->orWhere('hpp', 0)->orWhere('hpp', '0');
        })->whereNotNull('item_no')
          ->distinct()
          ->pluck('item_no')
          ->toArray();

        $this->itemsToSync = array_values(array_unique($itemNos));
        $this->totalItems = count($this->itemsToSync);

        if ($this->totalItems == 0) {
            $this->addLog("Tidak ada SKU pada filter ini yang membutuhkan HPP.");
            $this->isSyncingHpp = false;
            return;
        }

        $this->addLog("Ditemukan {$this->totalItems} SKU terfilter. Memulai sinkronisasi HPP (Nearest Cost) dari Accurate...");
        $this->dispatch('sync-next-hpp-item');
    }

    /**
     * Buka Modal Edit Manual Vendor & HPP
     */
    public function openEditModal($snId)
    {
        $sn = ProductSerialNumber::find($snId);
        if (!$sn) return;

        $this->editingSnId = $sn->id;
        $this->editingSnNumber = $sn->serial_number;
        $this->editingProductName = $sn->product_name ?? ($sn->item_no ?? '-');
        $this->editVendorId = $sn->vendor_id ?? '';
        $this->editHpp = (float)$sn->hpp;
        $this->editReceiptDate = $sn->receipt_date ? Carbon::parse($sn->receipt_date)->format('Y-m-d') : '';
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingSnId = null;
    }

    /**
     * Simpan Perubahan Manual Vendor & HPP
     */
    public function saveManualEdit()
    {
        $this->validate([
            'editHpp' => 'nullable|numeric|min:0',
            'editVendorId' => 'nullable|exists:vendors,id',
            'editReceiptDate' => 'nullable|date',
        ]);

        $sn = ProductSerialNumber::find($this->editingSnId);
        if ($sn) {
            $sn->update([
                'vendor_id' => $this->editVendorId ?: null,
                'hpp' => $this->editHpp ?: 0,
                'receipt_date' => $this->editReceiptDate ?: null,
            ]);

            $this->addLog("Data manual untuk SN [{$sn->serial_number}] berhasil diperbarui.");
            $this->dispatch('toast', title: 'Berhasil', message: 'Data Serial Number berhasil disimpan.', type: 'success');
        }

        $this->closeEditModal();
    }

    // =========================================================================
    // METODE SINKRONISASI MASSAL (LEGACY - DIDUKUNG DALAM SECTION LANJUTAN)
    // =========================================================================

    public function startSync()
    {
        $this->isSyncing = true;
        $this->processedItems = 0;
        $this->logs = [];
        $this->itemsToSync = [];

        $this->addLog("Mengumpulkan data SKU yang membutuhkan Serial Number...");

        $query = ProductAccurate::where('has_sn', true)
            ->whereNotNull('item_no')
            ->where('item_no', '!=', '');

        if ($this->businessUnitId) {
            $query->where('business_unit_id', $this->businessUnitId);
        }

        $this->itemsToSync = array_values(array_unique($query->pluck('item_no')->toArray()));
        $this->totalItems = count($this->itemsToSync);

        if ($this->totalItems == 0) {
            $this->addLog("Tidak ada produk yang membutuhkan Serial Number.");
            $this->isSyncing = false;
            return;
        }

        $this->addLog("Ditemukan {$this->totalItems} SKU unik. Memulai sinkronisasi saldo SN dari Accurate...");
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

        $sku = array_shift($this->itemsToSync);
        $this->currentItem = "Sedang memproses SKU: {$sku}";

        try {
            $service = app(SerialNumberSyncService::class);
            $sourceCode = null;
            if ($this->businessUnitId) {
                $sourceCode = BusinessUnit::find($this->businessUnitId)?->code;
            }
            $snCount = $service->syncFromAccurate($sku, $sourceCode);

            if ($snCount > 0) {
                $this->addLog("[$sku] Tersinkronisasi $snCount Serial Number.");
            } else {
                $this->addLog("[$sku] Tidak ada data Serial Number di Accurate.");
            }

            $priceResult = $service->syncPriceFromAccurate($sku, $sourceCode);
            if ($priceResult['updated']) {
                $this->addLog("[$sku] 💰 Harga diperbarui: Rp " . number_format($priceResult['old_price']) . " → Rp " . number_format($priceResult['new_price']));
            }
        } catch (\Exception $e) {
            $this->addLog("[$sku] Error: " . $e->getMessage());
        }

        $this->processedItems++;
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
            $accurateService = app(AccurateService::class);

            $sources = [];
            if ($this->businessUnitId) {
                $bu = BusinessUnit::find($this->businessUnitId);
                if ($bu) $sources[] = $bu->code;
            } else {
                $sources = BusinessUnit::where('is_active', true)->pluck('code')->toArray();
            }

            foreach ($sources as $source) {
                try {
                    $ids = $accurateService->getReceiveItemList($source);
                    $data = array_map(function ($id) use ($source) {
                        return ['id' => $id, 'source' => $source];
                    }, $ids);
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

        $task = array_shift($this->itemsToSync);
        $receiveItemId = $task['id'];
        $source = $task['source'];

        $this->currentItem = "Sedang memproses dokumen ID: {$receiveItemId} ({$source})";

        try {
            $service = app(SerialNumberSyncService::class);
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
        $this->dispatch('sync-next-vendor-item');
    }

    public function startSyncHpp()
    {
        $this->isSyncingHpp = true;
        $this->processedItems = 0;
        $this->logs = [];
        $this->itemsToSync = [];

        $this->addLog("Mengumpulkan data Item yang belum memiliki HPP...");

        $itemNos = ProductSerialNumber::where(function ($q) {
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

        $itemNo = array_shift($this->itemsToSync);
        $this->currentItem = "Sedang memproses HPP untuk Item No: {$itemNo}";

        try {
            $service = app(SerialNumberSyncService::class);
            $sourceCode = null;
            if ($this->businessUnitId) {
                $sourceCode = BusinessUnit::find($this->businessUnitId)?->code;
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
        $this->dispatch('sync-next-hpp-item');
    }

    private function addLog($message)
    {
        array_unshift($this->logs, "[" . now()->format('H:i:s') . "] " . $message);

        if (count($this->logs) > 50) {
            array_pop($this->logs);
        }
    }
}
