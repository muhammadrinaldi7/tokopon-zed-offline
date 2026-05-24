<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
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
    public $activeTab = 'new'; // 'new' atau 'second'
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

    public function syncVariantStock($variantId, $isSecond = false)
    {
        $this->isLoading = true;

        try {
            $variant = $isSecond
                ? SecondProductVariant::findOrFail($variantId)
                : ProductVariant::findOrFail($variantId);

            if (empty($variant->sku)) {
                $this->dispatch('toast', title: 'Error', message: 'Varian ini tidak memiliki SKU untuk dicocokkan ke Accurate.', type: 'error');
                $this->isLoading = false;
                return;
            }

            $dbSource = $isSecond ? 'second' : 'syihab';

            $service = app(AccurateService::class);
            $stockData = $service->getItemStockPerWarehouse($variant->sku, $dbSource);

            if (empty($stockData)) {
                $this->dispatch('toast', title: 'Info', message: 'Tidak ada data stok di Accurate untuk SKU ' . $variant->sku, type: 'info');
                $this->isLoading = false;
                return;
            }

            // Reset current warehouse stock for this variant to 0 first (in case Accurate doesn't return some warehouses anymore)
            WarehouseStock::where('variant_id', $variant->id)
                ->where('variant_type', get_class($variant))
                ->update(['stock' => 0]);

            foreach ($stockData as $stockItem) {
                $warehouseName = $stockItem['warehouseName'] ?? ($stockItem['warehouse']['name'] ?? null);
                $qty = $stockItem['quantity'] ?? ($stockItem['qty'] ?? 0);

                if (!$warehouseName) continue;

                $warehouse = Warehouse::where('name', $warehouseName)->first();
                if (!$warehouse) continue;

                WarehouseStock::updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'variant_id' => $variant->id,
                        'variant_type' => get_class($variant),
                    ],
                    [
                        'stock' => $qty
                    ]
                );
            }

            $this->dispatch('toast', title: 'Berhasil', message: 'Stok varian ' . $variant->sku . ' berhasil disinkronkan.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal sinkronisasi: ' . $e->getMessage(), type: 'error');
        }

        $this->isLoading = false;
    }

    public function syncAllStocks()
    {
        $this->isLoading = true;

        try {
            $variants = $this->activeTab === 'second'
                ? SecondProductVariant::whereNotNull('sku')->get()
                : ProductVariant::whereNotNull('sku')->get();

            $dbSource = $this->activeTab === 'second' ? 'second' : 'syihab';
            $service = app(AccurateService::class);
            $syncedCount = 0;

            foreach ($variants as $variant) {
                try {
                    $stockData = $service->getItemStockPerWarehouse($variant->sku, $dbSource);
                    if (empty($stockData)) continue;
                    // Reset
                    WarehouseStock::where('variant_id', $variant->id)
                        ->where('variant_type', get_class($variant))
                        ->update(['stock' => 0]);

                    foreach ($stockData as $stockItem) {
                        $warehouseName = $stockItem['warehouseName'] ?? ($stockItem['warehouse']['name'] ?? null);
                        $qty = $stockItem['quantity'] ?? ($stockItem['qty'] ?? 0);

                        if (!$warehouseName) continue;

                        $warehouse = Warehouse::where('name', $warehouseName)->first();
                        if (!$warehouse) continue;

                        WarehouseStock::updateOrCreate(
                            [
                                'warehouse_id' => $warehouse->id,
                                'variant_id' => $variant->id,
                                'variant_type' => get_class($variant),
                            ],
                            [
                                'stock' => $qty
                            ]
                        );
                    }
                    $syncedCount++;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to sync SKU {$variant->sku} during bulk sync: " . $e->getMessage());
                }
            }

            $this->dispatch('toast', title: 'Selesai', message: "Berhasil menyelaraskan $syncedCount varian dengan Accurate.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal sinkronisasi massal: ' . $e->getMessage(), type: 'error');
        }

        $this->isLoading = false;
    }

    public function syncProductPerWh($whName)
    {
        $this->isLoading = true;

        try {
            $dbSource = $this->activeTab === 'second' ? 'second' : 'syihab';
            $service = app(AccurateService::class);

            // 1. Validasi Gudang Lokal
            $warehouse = Warehouse::where('name', $whName)->first();
            if (!$warehouse) {
                $this->dispatch('toast', title: 'Gagal', message: 'Gudang tidak ditemukan di database lokal.', type: 'error');
                $this->isLoading = false;
                return;
            }

            // 2. HIT API ACCURATE CUKUP 1 KALI (Di luar looping)
            // Ambil semua stok produk yang ada di gudang ini
            $stockData = $service->getItemStockPerWarehouse($whName, $dbSource);

            if (empty($stockData)) {
                $this->dispatch('toast', title: 'Info', message: "Tidak ada data stok di Accurate untuk gudang: $whName", type: 'info');
                $this->isLoading = false;
                return;
            }

            // 3. Ambil data varian lokal sesuai tab aktif
            $variants = $this->activeTab === 'second'
                ? SecondProductVariant::whereNotNull('sku')->get()
                : ProductVariant::whereNotNull('sku')->get();

            // 4. Ubah array response Accurate menjadi Laravel Collection agar mudah di-search
            $accurateStockCollection = collect($stockData)->keyBy('no');

            // 5. Reset semua stok di gudang INI khusus untuk jenis varian terkait menjadi 0 dahulu
            $variantClass = $this->activeTab === 'second' ? SecondProductVariant::class : ProductVariant::class;
            WarehouseStock::where('warehouse_id', $warehouse->id)
                ->where('variant_type', $variantClass)
                ->update(['stock' => 0]);

            $syncedCount = 0;
            // dd($accurateStockCollection);
            // 6. Lakukan pemetaan data di memori internal (Proses ini sangat cepat < 0.1 detik)
            foreach ($variants as $variant) {
                // Cek apakah SKU lokal kita ada di dalam list stok Accurate gudang tersebut
                if ($accurateStockCollection->has($variant->sku)) {
                    $accurateItem = $accurateStockCollection->get($variant->sku);
                    Log::info('accurateItem', [$accurateItem]);
                    $qty = $accurateItem['quantity'] ?? 0;

                    WarehouseStock::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'variant_id'   => $variant->id,
                            'variant_type' => get_class($variant),
                        ],
                        [
                            'stock'        => $qty
                        ]
                    );
                }
                $syncedCount++;
            }

            $this->dispatch('toast', title: 'Selesai', message: "Berhasil menyelaraskan $syncedCount varian untuk gudang $whName.", type: 'success');
        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi per gudang ($whName): " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal sinkronisasi per gudang: ' . $e->getMessage(), type: 'error');
        }

        $this->isLoading = false;
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $warehouses = Warehouse::orderBy('name')->get();

        if ($this->activeTab === 'second') {
            $query = SecondProductVariant::with(['secondProduct', 'warehouseStocks'])
                ->orderBy('id', 'desc');

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('sku', 'like', '%' . $this->search . '%')
                        ->orWhere('storage', 'like', '%' . $this->search . '%')
                        ->orWhere('color', 'like', '%' . $this->search . '%')
                        ->orWhereHas('secondProduct', function ($q2) {
                            $q2->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            }
        } else {
            $query = ProductVariant::with(['product', 'warehouseStocks'])
                ->orderBy('id', 'desc');

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('sku', 'like', '%' . $this->search . '%')
                        ->orWhere('storage', 'like', '%' . $this->search . '%')
                        ->orWhere('color', 'like', '%' . $this->search . '%')
                        ->orWhereHas('product', function ($q2) {
                            $q2->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            }
        }

        $variantsList = $query->paginate(15);

        return view('livewire.admin.warehouse.stock-management', [
            'variantsList' => $variantsList,
            'warehouses' => $warehouses,
        ]);
    }
}
