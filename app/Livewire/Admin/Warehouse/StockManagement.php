<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class StockManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'syihab'; // 'syihab' atau 'second'
    public $isLoading = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActiveTab()
    {
        $this->resetPage();
        $this->search = '';
    }

    public function syncAllStocks()
    {
        $this->isLoading = true;

        try {
            $dbSource = $this->activeTab;
            $service = app(AccurateService::class);
            $syncedCount = 0;

            // 1. Ambil daftar Gudang lokal sesuai Business Unit (1 = Syihab, 2 = Second)
            $buId = $this->activeTab === 'second' ? 2 : 1;
            $warehouses = Warehouse::where('business_unit_id', $buId)->get();
            $products = ProductAccurate::where('database_source', $dbSource)->get();

            foreach ($warehouses as $warehouse) {
                try {
                    // 2. Ambil stok Accurate per gudang
                    $stockData = $service->getItemStockPerWarehouse($warehouse->name, $dbSource);

                    if (empty($stockData)) continue;

                    // 3. Mapping data array ke Collection berbasis SKU (item_no)
                    $accurateStockCollection = collect($stockData)->keyBy(function ($item) {
                        return $item['itemNo'] ?? ($item['item']['no'] ?? ($item['no'] ?? null));
                    });

                    // 4. Reset stok khusus gudang INI untuk ProductAccurate menjadi 0 dulu
                    WarehouseStock::where('warehouse_id', $warehouse->id)
                        ->where('variant_type', ProductAccurate::class)
                        ->update(['stock' => 0]);

                    // 5. Looping produk accurate lokal dan simpan data stok per gudang
                    foreach ($products as $product) {
                        if ($accurateStockCollection->has($product->item_no)) {
                            $accurateItem = $accurateStockCollection->get($product->item_no);
                            $qty = $accurateItem['quantity'] ?? ($accurateItem['qty'] ?? 0);

                            WarehouseStock::updateOrCreate(
                                [
                                    'warehouse_id' => $warehouse->id,
                                    'variant_id'   => $product->id,
                                    'variant_type' => ProductAccurate::class,
                                ],
                                [
                                    'stock'        => $qty
                                ]
                            );
                            $syncedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to sync Warehouse {$warehouse->name} during bulk sync: " . $e->getMessage());
                }
            }

            $this->dispatch('toast', title: 'Selesai', message: "Berhasil menyelaraskan total $syncedCount stok item dari seluruh gudang dengan Accurate.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal sinkronisasi massal: ' . $e->getMessage(), type: 'error');
        }

        $this->isLoading = false;
    }

    public function syncProductPerWh($whName)
    {
        $this->isLoading = true;

        try {
            $dbSource = $this->activeTab;
            $service = app(AccurateService::class);

            // 1. Validasi Gudang Lokal
            $warehouse = Warehouse::where('name', $whName)->first();
            if (!$warehouse) {
                $this->dispatch('toast', title: 'Gagal', message: 'Gudang tidak ditemukan di database lokal.', type: 'error');
                $this->isLoading = false;
                return;
            }

            // 2. HIT API ACCURATE
            $stockData = $service->getItemStockPerWarehouse($whName, $dbSource);

            if (empty($stockData)) {
                $this->dispatch('toast', title: 'Info', message: "Tidak ada data stok di Accurate untuk gudang: $whName", type: 'info');
                $this->isLoading = false;
                return;
            }

            // 3. Ambil data produk lokal sesuai tab
            $products = ProductAccurate::where('database_source', $dbSource)->get();

            // 4. Ubah array response Accurate menjadi Laravel Collection
            $accurateStockCollection = collect($stockData)->keyBy(function ($item) {
                return $item['itemNo'] ?? ($item['item']['no'] ?? ($item['no'] ?? null));
            });

            // 5. Reset semua stok di gudang INI khusus untuk ProductAccurate
            WarehouseStock::where('warehouse_id', $warehouse->id)
                ->where('variant_type', ProductAccurate::class)
                ->update(['stock' => 0]);
            $syncedCount = 0;

            // 6. Pemetaan data ke lokal
            foreach ($products as $product) {
                if ($accurateStockCollection->has($product->item_no)) {
                    $accurateItem = $accurateStockCollection->get($product->item_no);
                    $qty = $accurateItem['quantity'] ?? ($accurateItem['qty'] ?? 0);

                    WarehouseStock::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'variant_id'   => $product->id,
                            'variant_type' => ProductAccurate::class,
                        ],
                        [
                            'stock'        => $qty
                        ]
                    );
                }
                $syncedCount++;
            }

            $this->dispatch('toast', title: 'Selesai', message: "Berhasil menyelaraskan stok untuk gudang $whName.", type: 'success');
        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi per gudang ($whName): " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal sinkronisasi per gudang: ' . $e->getMessage(), type: 'error');
        }

        $this->isLoading = false;
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $buId = $this->activeTab === 'second' ? 2 : 1;
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
