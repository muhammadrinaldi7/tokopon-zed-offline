<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;

use App\Models\ProductAccurate;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class StockReport extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'sku';
    public $sortDirection = 'asc';
    public $filterBU = '';
    public $filterWarehouse = '';
    public $filterCategory = '';
    public $isAdmin = false;
    public $businessUnits = [];

    public function mount()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->hasAnyRole(['superadmin', 'admin', 'director'])) {
            $this->isAdmin = true;
            $this->businessUnits = \App\Models\BusinessUnit::all();
            $this->filterBU = ''; // Semua BU
        } else {
            $this->isAdmin = false;
            $this->filterBU = $user->business_unit_id;
        }
    }

    public function updatingFilterBU()
    {
        $this->filterWarehouse = '';
        $this->resetPage();
    }

    public function updatingFilterWarehouse()
    {
        $this->resetPage();
    }

    public function updatingFilterCategory()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
            $this->sortField = $field;
        }
    }

    public function exportCsv()
    {
        $data = $this->getStockData();

        $filename = "laporan_stok_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = [
            'SKU',
            'NAMA PRODUK',
            'KATEGORI',
            'GUDANG',
            'STOK GUDANG',
            'SN',
            'HARGA BELI (MODAL)',
            'HARGA JUAL',
            'UMUR PRODUK (HARI)'
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $item) {
                fputcsv($file, [
                    $item['sku'],
                    $item['name'],
                    $item['category'],
                    $item['warehouse_name'],
                    $item['stock'],
                    $item['sn'],
                    $item['base_cost'],
                    $item['base_price'],
                    $item['age_days']
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getStockData()
    {
        $query = ProductAccurate::select('id', 'item_no', 'name', 'categoryName', 'base_cost', 'base_price', 'created_at', 'updated_at', 'stock', 'business_unit_id')
            ->with([
                'warehouseStocks.warehouse:id,name',
                'businessUnit:id,name',
                'productSerialNumbers' => function($q) {
                    $q->select('serial_number', 'warehouse_id', 'product_accurate_id')->where('status', 'Available');
                }
            ]);
        
        $validWarehouseIds = [];
        if (!empty($this->filterBU)) {
            $query->where('business_unit_id', $this->filterBU);
            $validWarehouseIds = \App\Models\Warehouse::where('business_unit_id', $this->filterBU)->pluck('id')->toArray();
        }

        // DB Level Filter: Search
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('item_no', 'like', $searchTerm)
                  ->orWhere('name', 'like', $searchTerm);
            });
        }

        // DB Level Filter: Category
        if (!empty($this->filterCategory)) {
            $query->where('categoryName', $this->filterCategory);
        }

        // DB Level Filter: Warehouse
        if (!empty($this->filterWarehouse)) {
            if ($this->filterWarehouse === 'Belum Dialokasikan') {
                $query->doesntHave('warehouseStocks');
            } else {
                $query->whereHas('warehouseStocks.warehouse', function($q) {
                    $q->where('name', $this->filterWarehouse);
                });
            }
        }

        $accurateProducts = $query->get()
            ->flatMap(function ($variant) use ($validWarehouseIds) {
                $items = [];
                $baseItem = [
                    'id' => 'a_' . $variant->id,
                    'sku' => $variant->item_no ?? '-',
                    'name' => $variant->name ?? 'Unknown',
                    'category' => $variant->categoryName ?? 'Lainnya',
                    'base_cost' => $variant->base_cost ?? 0,
                    'base_price' => $variant->base_price ?? 0,
                    'age_days' => $variant->created_at ? round($variant->created_at->diffInDays(now())) : 0,
                    'created_at' => $variant->created_at,
                    'sync_date' => $variant->updated_at ? $variant->updated_at->format('Y-m-d') : '-',
                    'sync_datetime' => $variant->updated_at ? $variant->updated_at->format('Y-m-d H:i:s') : '-',
                ];

                if ($variant->warehouseStocks->isEmpty()) {
                    if (empty($this->filterWarehouse) || $this->filterWarehouse === 'Belum Dialokasikan') {
                        $baseItem['warehouse_name'] = 'Belum Dialokasikan';
                        $baseItem['stock'] = $variant->stock ?? 0;
                        $sns = $variant->productSerialNumbers->whereNull('warehouse_id')->pluck('serial_number')->implode(', ');
                        $baseItem['sn'] = $sns ?: '-';
                        $items[] = $baseItem;
                    }
                } else {
                    foreach ($variant->warehouseStocks as $ws) {
                        if (!empty($this->filterBU) && !in_array($ws->warehouse_id, $validWarehouseIds)) {
                            continue;
                        }
                        
                        $whName = $ws->warehouse->name ?? 'Unknown';
                        
                        if (!empty($this->filterWarehouse) && $whName !== $this->filterWarehouse) {
                            continue;
                        }

                        $whItem = $baseItem;
                        $whItem['warehouse_name'] = $whName;
                        $whItem['stock'] = $ws->stock;
                        $sns = $variant->productSerialNumbers->where('warehouse_id', $ws->warehouse_id)->pluck('serial_number')->implode(', ');
                        $whItem['sn'] = $sns ?: '-';
                        $items[] = $whItem;
                    }
                }
                return $items;
            });

        return collect($accurateProducts)->sortBy([
            [$this->sortField, $this->sortDirection]
        ]);
    }

    public function render()
    {
        // Ambil opsi filter dari DB secara efisien
        $catQuery = ProductAccurate::query();
        $whQuery = \App\Models\Warehouse::query();
        
        if (!empty($this->filterBU)) {
            $catQuery->where('business_unit_id', $this->filterBU);
            $whQuery->where('business_unit_id', $this->filterBU);
        }
        
        $availableCategories = $catQuery->distinct()->pluck('categoryName')->filter()->sort()->values();
        $availableWarehouses = $whQuery->pluck('name')->sort()->values();

        $allData = $this->getStockData();

        // Pagination Manual untuk Collection
        $perPage = 15;
        $page = $this->getPage();

        $paginatedData = new LengthAwarePaginator(
            $allData->forPage($page, $perPage),
            $allData->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return view('livewire.zoffline.reporting.stock-report', [
            'stocks' => $paginatedData,
            'availableWarehouses' => $availableWarehouses,
            'availableCategories' => $availableCategories,
            'summary' => [
                'total_items' => $allData->sum('stock'),
                'total_cost' => $allData->sum(fn($item) => $item['base_cost'] * $item['stock']),
                'total_potential_revenue' => $allData->sum(fn($item) => $item['base_price'] * $item['stock']),
            ]
        ])->layout('layouts.z');
    }
}
