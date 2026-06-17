<?php

namespace App\Livewire\Admin\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\ProductAccurate;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class StockReport extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'sku';
    public $sortDirection = 'asc';
    public $filterDate = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterDate()
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
            'WARNA',
            'KATEGORI',
            'GUDANG',
            'STOK GUDANG',
            'HARGA BELI (MODAL)',
            'HARGA JUAL',
            'UMUR PRODUK (HARI)',
            'TANGGAL TARIK STOK'
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $item) {
                fputcsv($file, [
                    $item['sku'],
                    $item['name'],
                    $item['color'],
                    $item['category'],
                    $item['warehouse_name'],
                    $item['stock'],
                    $item['base_cost'],
                    $item['base_price'],
                    $item['age_days'],
                    $item['sync_date']
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getStockData()
    {
        // Ambil barang baru
        $newProducts = ProductVariant::with(['product', 'accurateData', 'warehouseStocks.warehouse'])
            ->get()
            ->flatMap(function ($variant) {
                $items = [];
                $baseItem = [
                    'id' => 'n_' . $variant->id,
                    'sku' => $variant->sku ?? '-',
                    'name' => $variant->product->name ?? 'Unknown',
                    'color' => $variant->color ?? '-',
                    'category' => 'Baru',
                    'base_cost' => $variant->accurateData->base_cost ?? 0,
                    'base_price' => $variant->price ?? ($variant->accurateData->base_price ?? 0),
                    'age_days' => $variant->created_at ? round($variant->created_at->diffInDays(now())) : 0,
                    'created_at' => $variant->created_at,
                    'sync_date' => $variant->accurateData ? $variant->accurateData->updated_at->format('Y-m-d') : '-',
                    'sync_datetime' => $variant->accurateData ? $variant->accurateData->updated_at->format('Y-m-d H:i:s') : '-',
                ];

                if ($variant->warehouseStocks->isEmpty()) {
                    $baseItem['warehouse_name'] = 'Belum Dialokasikan';
                    $baseItem['stock'] = $variant->stock ?? 0;
                    $items[] = $baseItem;
                } else {
                    foreach ($variant->warehouseStocks as $ws) {
                        $whItem = $baseItem;
                        $whItem['warehouse_name'] = $ws->warehouse->name ?? 'Unknown';
                        $whItem['stock'] = $ws->stock;
                        $items[] = $whItem;
                    }
                }
                return $items;
            });

        // Ambil barang bekas
        $secondProducts = SecondProductVariant::with(['secondProduct', 'accurateData', 'warehouseStocks.warehouse'])
            ->get()
            ->flatMap(function ($variant) {
                $items = [];
                $baseItem = [
                    'id' => 's_' . $variant->id,
                    'sku' => $variant->sku ?? '-',
                    'name' => $variant->secondProduct->name ?? 'Unknown',
                    'color' => $variant->color ?? '-',
                    'category' => 'Bekas',
                    'base_cost' => $variant->buy_price ?? ($variant->accurateData->base_cost ?? 0),
                    'base_price' => $variant->price ?? 0,
                    'age_days' => $variant->created_at ? round($variant->created_at->diffInDays(now())) : 0,
                    'created_at' => $variant->created_at,
                    'sync_date' => $variant->accurateData ? $variant->accurateData->updated_at->format('Y-m-d') : '-',
                    'sync_datetime' => $variant->accurateData ? $variant->accurateData->updated_at->format('Y-m-d H:i:s') : '-',
                ];

                if ($variant->warehouseStocks->isEmpty()) {
                    $baseItem['warehouse_name'] = 'Belum Dialokasikan';
                    $baseItem['stock'] = $variant->stock ?? 0;
                    $items[] = $baseItem;
                } else {
                    foreach ($variant->warehouseStocks as $ws) {
                        $whItem = $baseItem;
                        $whItem['warehouse_name'] = $ws->warehouse->name ?? 'Unknown';
                        $whItem['stock'] = $ws->stock;
                        $items[] = $whItem;
                    }
                }
                return $items;
            });

        // Ambil data dari ProductAccurate (flow POS baru)
        $accurateProducts = ProductAccurate::with(['warehouseStocks.warehouse', 'businessUnit'])
            ->get()
            ->flatMap(function ($variant) {
                $items = [];
                $baseItem = [
                    'id' => 'a_' . $variant->id,
                    'sku' => $variant->item_no ?? '-',
                    'name' => $variant->name ?? 'Unknown',
                    'color' => '-',
                    'category' => $variant->categoryName ?? 'Lainnya',
                    'base_cost' => $variant->base_cost ?? 0,
                    'base_price' => $variant->base_price ?? 0,
                    'age_days' => $variant->created_at ? round($variant->created_at->diffInDays(now())) : 0,
                    'created_at' => $variant->created_at,
                    'sync_date' => $variant->updated_at ? $variant->updated_at->format('Y-m-d') : '-',
                    'sync_datetime' => $variant->updated_at ? $variant->updated_at->format('Y-m-d H:i:s') : '-',
                ];

                if ($variant->warehouseStocks->isEmpty()) {
                    $baseItem['warehouse_name'] = 'Belum Dialokasikan';
                    $baseItem['stock'] = $variant->stock ?? 0;
                    $items[] = $baseItem;
                } else {
                    foreach ($variant->warehouseStocks as $ws) {
                        $whItem = $baseItem;
                        $whItem['warehouse_name'] = $ws->warehouse->name ?? 'Unknown';
                        $whItem['stock'] = $ws->stock;
                        $items[] = $whItem;
                    }
                }
                return $items;
            });

        // Gabungkan dan kembalikan
        return collect($newProducts)->concat($secondProducts)->concat($accurateProducts);
    }

    public function render()
    {
        $allData = $this->getStockData();

        // Search filter
        if (!empty($this->search)) {
            $searchTerm = strtolower($this->search);
            $allData = $allData->filter(function ($item) use ($searchTerm) {
                return str_contains(strtolower($item['sku']), $searchTerm) ||
                    str_contains(strtolower($item['name']), $searchTerm);
            });
        }

        // Date filter
        if (!empty($this->filterDate)) {
            $allData = $allData->filter(function ($item) {
                return $item['sync_date'] === $this->filterDate;
            });
        }

        // Sorting
        $allData = $allData->sortBy([
            [$this->sortField, $this->sortDirection]
        ]);

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

        return view('livewire.admin.reporting.stock-report', [
            'stocks' => $paginatedData,
            'summary' => [
                'total_items' => $allData->sum('stock'),
                'total_cost' => $allData->sum(fn($item) => $item['base_cost'] * $item['stock']),
                'total_potential_revenue' => $allData->sum(fn($item) => $item['base_price'] * $item['stock']),
            ]
        ])->layout('layouts.admin');
    }
}
