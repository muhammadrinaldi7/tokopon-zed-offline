<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class StockManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab;
    public $isLoading = false;

    // State Progressive Batching Sync
    public bool $isSyncing = false;
    public array $syncQueue = [];                // Antrean nama gudang yang akan disinkronkan
    public ?string $currentWarehouseName = null; // Gudang yang sedang aktif diproses
    public int $syncCurrentPage = 1;             // Halaman Accurate API saat ini
    public int $syncImportedCount = 0;           // Total stok barang yang berhasil diselaraskan
    public int $totalWarehousesToSync = 0;       // Total gudang dalam antrean
    public int $completedWarehousesCount = 0;    // Jumlah gudang yang sudah selesai diproses

    public function mount()
    {
        $businessUnits = \App\Models\BusinessUnit::where('is_active', true)->get();
        if ($businessUnits->isNotEmpty()) {
            $this->activeTab = $businessUnits->first()->code;
        } else {
            $this->activeTab = 'syihab'; // fallback
        }
    }

    public function getBusinessUnitsProperty()
    {
        return \App\Models\BusinessUnit::where('is_active', true)->get();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActiveTab()
    {
        if ($this->isSyncing) {
            $this->cancelSync();
        }
        $this->resetPage();
        $this->search = '';
    }

    /**
     * Memulai sinkronisasi massal seluruh gudang dalam unit usaha aktif secara progressive batching
     */
    public function syncAllStocks()
    {
        if ($this->isSyncing) return;

        $bu = \App\Models\BusinessUnit::where('code', $this->activeTab)->first();
        $buId = $bu ? $bu->id : null;
        $warehouses = Warehouse::where('business_unit_id', $buId)->pluck('name')->toArray();

        if (empty($warehouses)) {
            $this->dispatch('toast', title: 'Info', message: 'Tidak ada data gudang pada unit usaha ini.', type: 'info');
            return;
        }

        $this->isSyncing = true;
        $this->syncQueue = $warehouses;
        $this->totalWarehousesToSync = count($warehouses);
        $this->completedWarehousesCount = 0;
        $this->syncImportedCount = 0;
        $this->currentWarehouseName = array_shift($this->syncQueue);
        $this->syncCurrentPage = 1;

        // Reset stok ProductAccurate untuk gudang pertama yang diproses
        $firstWh = Warehouse::where('name', $this->currentWarehouseName)->first();
        if ($firstWh) {
            WarehouseStock::where('warehouse_id', $firstWh->id)
                ->where('variant_type', ProductAccurate::class)
                ->update(['stock' => 0]);
        }

        $this->dispatch('trigger-next-stock-page');
    }

    /**
     * Memulai sinkronisasi stok satu gudang tertentu secara progressive batching
     */
    public function syncProductPerWh($whName)
    {
        if ($this->isSyncing) return;

        $warehouse = Warehouse::where('name', $whName)->first();
        if (!$warehouse) {
            $this->dispatch('toast', title: 'Gagal', message: 'Gudang tidak ditemukan di database lokal.', type: 'error');
            return;
        }

        $this->isSyncing = true;
        $this->syncQueue = []; // Hanya satu gudang
        $this->totalWarehousesToSync = 1;
        $this->completedWarehousesCount = 0;
        $this->syncImportedCount = 0;
        $this->currentWarehouseName = $whName;
        $this->syncCurrentPage = 1;

        // Reset stok ProductAccurate untuk gudang ini di awal proses agar bersih
        WarehouseStock::where('warehouse_id', $warehouse->id)
            ->where('variant_type', ProductAccurate::class)
            ->update(['stock' => 0]);

        $this->dispatch('trigger-next-stock-page');
    }

    /**
     * Membatalkan proses sinkronisasi yang sedang berjalan
     */
    public function cancelSync()
    {
        $this->isSyncing = false;
        $this->syncQueue = [];
        $this->currentWarehouseName = null;
        $this->syncCurrentPage = 1;
        $this->dispatch('toast', title: 'Dibatalkan', message: 'Proses sinkronisasi stok dihentikan.', type: 'info');
    }

    /**
     * Listener event yang mengeksekusi 1 halaman request Accurate (Progressive Batching)
     */
    #[On('trigger-next-stock-page')]
    public function processNextStockPage()
    {
        if (!$this->isSyncing || !$this->currentWarehouseName) {
            return;
        }

        try {
            $dbSource = $this->activeTab;
            $service = app(AccurateService::class);
            $pageSize = 100;

            $warehouse = Warehouse::where('name', $this->currentWarehouseName)->first();
            if (!$warehouse) {
                $this->advanceToNextWarehouse();
                return;
            }

            // 1. Tarik HANYA 1 halaman (100 item) dari Accurate API (sangat cepat, < 1 detik)
            $items = $service->getItemStockPerWarehousePage(
                $this->currentWarehouseName,
                $this->syncCurrentPage,
                $pageSize,
                $dbSource
            );

            // 2. Jika ada item di halaman ini, lakukan bulk upsert ke database
            if (!empty($items)) {
                $itemNos = collect($items)->map(function ($item) {
                    return $item['itemNo'] ?? ($item['item']['no'] ?? ($item['no'] ?? null));
                })->filter()->unique()->values()->all();

                $localProducts = ProductAccurate::where('database_source', $dbSource)
                    ->whereIn('item_no', $itemNos)
                    ->get()
                    ->keyBy('item_no');

                $upsertData = [];
                foreach ($items as $item) {
                    $itemNo = $item['itemNo'] ?? ($item['item']['no'] ?? ($item['no'] ?? null));
                    if (!$itemNo || !$localProducts->has($itemNo)) {
                        continue;
                    }

                    $product = $localProducts->get($itemNo);
                    $qty = (int) round($item['quantity'] ?? ($item['qty'] ?? 0));

                    $upsertData[] = [
                        'warehouse_id' => $warehouse->id,
                        'variant_id'   => $product->id,
                        'variant_type' => ProductAccurate::class,
                        'stock'        => $qty,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }

                if (!empty($upsertData)) {
                    WarehouseStock::withoutEvents(function () use ($upsertData) {
                        WarehouseStock::upsert(
                            $upsertData,
                            ['warehouse_id', 'variant_id', 'variant_type'],
                            ['stock', 'updated_at']
                        );
                    });

                    $this->syncImportedCount += count($upsertData);
                }
            }

            // 3. Cek apakah masih ada halaman berikutnya untuk gudang ini
            if (count($items) < $pageSize) {
                // Data untuk gudang ini sudah selesai
                $this->completedWarehousesCount++;
                $this->advanceToNextWarehouse();
            } else {
                // Lanjut ke halaman berikutnya untuk gudang ini
                $this->syncCurrentPage++;
                $this->dispatch('trigger-next-stock-page');
            }

        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi stok {$this->currentWarehouseName} halaman {$this->syncCurrentPage}: " . $e->getMessage());
            $this->isSyncing = false;
            $this->dispatch('toast', title: 'Gagal', message: "Error sync gudang {$this->currentWarehouseName}: " . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Melanjutkan proses ke gudang berikutnya dalam antrean, atau menyelesaikan proses jika antrean habis
     */
    private function advanceToNextWarehouse()
    {
        if (!empty($this->syncQueue)) {
            $this->currentWarehouseName = array_shift($this->syncQueue);
            $this->syncCurrentPage = 1;

            $nextWh = Warehouse::where('name', $this->currentWarehouseName)->first();
            if ($nextWh) {
                WarehouseStock::where('warehouse_id', $nextWh->id)
                    ->where('variant_type', ProductAccurate::class)
                    ->update(['stock' => 0]);
            }

            $this->dispatch('trigger-next-stock-page');
        } else {
            $this->finishSync();
        }
    }

    /**
     * Menyelesaikan siklus sinkronisasi dan memperbarui akumulasi total stok ProductAccurate secara efisien
     */
    private function finishSync()
    {
        $this->isSyncing = false;
        $this->currentWarehouseName = null;
        $this->syncCurrentPage = 1;

        // Update akumulasi total stok global di master ProductAccurate sekaligus via 1 query SQL
        try {
            DB::statement("
                UPDATE product_accurates
                SET stock = (
                    SELECT COALESCE(SUM(ws.stock), 0)
                    FROM warehouse_stocks ws
                    WHERE ws.variant_id = product_accurates.id
                      AND ws.variant_type = ?
                )
                WHERE database_source = ?
            ", [ProductAccurate::class, $this->activeTab]);
        } catch (\Exception $e) {
            Log::warning("Gagal re-kalkulasi total stok global ProductAccurate: " . $e->getMessage());
        }

        $this->dispatch('toast', title: 'Selesai', message: "Berhasil menyelaraskan total {$this->syncImportedCount} stok item dari {$this->completedWarehousesCount} gudang dengan Accurate.", type: 'success');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $bu = \App\Models\BusinessUnit::where('code', $this->activeTab)->first();
        $buId = $bu ? $bu->id : null;
        $warehouses = Warehouse::where('business_unit_id', $buId)->orderBy('name')->get();

        $query = ProductAccurate::with(['warehouseStocks'])
            ->where('database_source', $this->activeTab)
            ->orderBy('id', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('item_no', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%');
            });
        }

        $productList = $query->paginate(15);

        return view('livewire.admin.warehouse.stock-management', [
            'productList' => $productList,
            'warehouses' => $warehouses,
        ]);
    }
}
