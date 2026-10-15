<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;

use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\ProductSerialNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Exports\StockReportExport;
use Maatwebsite\Excel\Facades\Excel;

class StockReport extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'sku';
    public $sortDirection = 'asc';
    public $filterBU = '';
    public array $filterWarehouse = [];
    public array $filterCategory = [];
    public array $filterBrand = [];
    public array $filterProyek = [];
    public $isAdmin = false;
    public $businessUnits = [];
    public $csvSeparator = ';';
    public $hasAppliedFilter = false;

    public function mount()
    {
        $user = Auth::user();
        if ($user->hasAnyRole(['superadmin', 'admin', 'director'])) {
            $this->isAdmin = true;
            $allBU = \App\Models\BusinessUnit::all();
            $this->businessUnits = $allBU;
            // Default ke BU cabang aktif user (atau BU pertama)
            $this->filterBU = $user->business_unit_id 
                ? (string) $user->business_unit_id 
                : (string) ($allBU->first()?->id ?? '');
        } else {
            $this->isAdmin = false;
            $this->filterBU = (string) $user->business_unit_id;
        }

        // Data tidak langsung dimuat di awal (wajib filter terlebih dahulu)
        $this->hasAppliedFilter = false;
    }

    public function updatingFilterBU()
    {
        $this->filterWarehouse = [];
        $this->filterCategory = [];
        $this->filterBrand = [];
        $this->filterProyek = [];
        $this->hasAppliedFilter = false;
        $this->resetPage();
    }

    public function updatedFilterBU()
    {
        $this->filterWarehouse = [];
        $this->filterCategory = [];
        $this->filterBrand = [];
        $this->filterProyek = [];
        $this->hasAppliedFilter = false;
        $this->resetPage();
    }

    /**
     * Terapkan filter yang dipilih dan ambil data dari database
     */
    public function applyFilter()
    {
        $this->hasAppliedFilter = true;
        $this->resetPage();
    }

    /**
     * Reset filter ke kondisi default dan kembalikan ke empty state
     */
    public function resetFilter()
    {
        $user = Auth::user();
        if ($this->isAdmin) {
            $this->filterBU = $user->business_unit_id 
                ? (string) $user->business_unit_id 
                : (string) (collect($this->businessUnits)->first()?->id ?? '');
        }
        $this->filterWarehouse = [];
        $this->filterCategory = [];
        $this->filterBrand = [];
        $this->filterProyek = [];
        $this->search = '';
        $this->hasAppliedFilter = false;
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

        if ($this->hasAppliedFilter) {
            $this->resetPage();
        }
    }

    /**
     * Bangun query dasar yang menggabungkan produk, stok gudang, dan master gudang
     * langsung pada level database (SQL JOIN) dengan seluruh filter diterapkan.
     */
    private function buildFilteredQuery()
    {
        $query = DB::table('product_accurates as pa')
            ->leftJoin('warehouse_stocks as ws', function ($join) {
                $join->on('ws.variant_id', '=', 'pa.id')
                    ->whereIn('ws.variant_type', ['App\\Models\\ProductAccurate', 'ProductAccurate']);
            })
            ->leftJoin('warehouses as w', function ($join) {
                $join->on('w.id', '=', 'ws.warehouse_id');
                if (!empty($this->filterBU)) {
                    $join->where('w.business_unit_id', '=', $this->filterBU);
                }
            });

        // Filter Business Unit (Menggunakan index business_unit_id)
        if (!empty($this->filterBU)) {
            $query->where('pa.business_unit_id', $this->filterBU);
        }

        // Filter Search: SKU, Nama, Brand
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('pa.item_no', 'like', $searchTerm)
                    ->orWhere('pa.name', 'like', $searchTerm)
                    ->orWhere('pa.brandName', 'like', $searchTerm);
            });
        }

        // Filter Kategori (Multi-select)
        if (!empty($this->filterCategory)) {
            $categories = is_array($this->filterCategory) ? array_filter($this->filterCategory) : [$this->filterCategory];
            if (!empty($categories)) {
                $query->whereIn('pa.categoryName', $categories);
            }
        }

        // Filter Brand (Multi-select)
        if (!empty($this->filterBrand)) {
            $brands = is_array($this->filterBrand) ? array_filter($this->filterBrand) : [$this->filterBrand];
            if (!empty($brands)) {
                $query->whereIn('pa.brandName', $brands);
            }
        }

        // Filter Proyek (Multi-select)
        if (!empty($this->filterProyek)) {
            $proyeks = is_array($this->filterProyek) ? array_filter($this->filterProyek) : [$this->filterProyek];
            if (!empty($proyeks)) {
                $query->whereIn('pa.proyek', $proyeks);
            }
        }

        // Filter Gudang (Multi-select dengan dukungan 'Belum Dialokasikan')
        if (!empty($this->filterWarehouse)) {
            $warehouses = is_array($this->filterWarehouse) ? array_filter($this->filterWarehouse) : [$this->filterWarehouse];
            if (!empty($warehouses)) {
                $hasUnallocated = in_array('Belum Dialokasikan', $warehouses);
                $namedWarehouses = array_values(array_filter($warehouses, fn($w) => $w !== 'Belum Dialokasikan'));

                $query->where(function ($q) use ($hasUnallocated, $namedWarehouses) {
                    if ($hasUnallocated && !empty($namedWarehouses)) {
                        $q->whereNull('ws.id')->orWhereIn('w.name', $namedWarehouses);
                    } elseif ($hasUnallocated) {
                        $q->whereNull('ws.id');
                    } else {
                        $q->whereIn('w.name', $namedWarehouses);
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Terapkan sorting pada level SQL query
     */
    private function applySorting($query)
    {
        $dir = strtolower($this->sortDirection) === 'desc' ? 'desc' : 'asc';

        switch ($this->sortField) {
            case 'name':
                $query->orderBy('pa.name', $dir);
                break;
            case 'brand':
                $query->orderBy('pa.brandName', $dir);
                break;
            case 'stock':
                $query->orderByRaw("COALESCE(ws.stock, pa.stock, 0) {$dir}");
                break;
            case 'base_cost':
                $query->orderBy('pa.base_cost', $dir);
                break;
            case 'base_price':
                $query->orderBy('pa.base_price', $dir);
                break;
            case 'age_days':
                // Umur terkecil = produk terbaru (created_at DESC ketika ASC)
                $query->orderBy('pa.created_at', $dir === 'asc' ? 'desc' : 'asc');
                break;
            case 'sku':
            default:
                $query->orderBy('pa.item_no', $dir);
                break;
        }

        return $query;
    }

    private function getSelectColumns()
    {
        return [
            'pa.id as product_id',
            'pa.item_no as sku',
            'pa.name as name',
            'pa.categoryName as category',
            'pa.brandName as brand',
            'pa.vendor_name',
            'pa.proyek',
            'pa.base_cost',
            'pa.base_price',
            'pa.created_at',
            'pa.updated_at',
            DB::raw("COALESCE(w.name, 'Belum Dialokasikan') as warehouse_name"),
            DB::raw("COALESCE(ws.stock, pa.stock, 0) as stock"),
            'ws.warehouse_id',
        ];
    }

    /**
     * Export Excel dengan pre-fetch serial number tunggal dan cursor database
     */
    public function exportExcel()
    {
        if (!$this->hasAppliedFilter) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Silakan klik Terapkan Filter terlebih dahulu sebelum mengekspor data.', type: 'warning');
            return;
        }

        @ini_set('max_execution_time', 300);
        @set_time_limit(300);
        @ini_set('memory_limit', '1024M');

        $baseQuery = $this->applySorting($this->buildFilteredQuery())->select($this->getSelectColumns());

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data stok untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return;
        }

        $filename = "laporan_stok_" . date('Ymd_His') . ".xlsx";

        // Pre-fetch nomor seri hanya 1x ke memori (O(1) hash map) agar tidak query berulang dalam loop
        $snLookup = ProductSerialNumber::query()
            ->where('status', 'Available')
            ->whereNotNull('product_accurate_id')
            ->select('product_accurate_id', 'warehouse_id', 'serial_number')
            ->get()
            ->groupBy(fn($sn) => $sn->product_accurate_id . '_' . ($sn->warehouse_id ?? 'null'))
            ->map(fn($group) => $group->pluck('serial_number')->implode(', '));

        $exportData = [];
        $now = now();
        foreach ($baseQuery->cursor() as $row) {
            $snKey = $row->product_id . '_' . ($row->warehouse_id ?? 'null');
            $sns = $snLookup[$snKey] ?? '-';
            $createdAt = $row->created_at ? Carbon::parse($row->created_at) : null;
            $ageDays = $createdAt ? round($createdAt->diffInDays($now)) : 0;

            $exportData[] = [
                'sku' => $row->sku ?? '-',
                'name' => $row->name ?? 'Unknown',
                'brand' => $row->brand ?? '-',
                'category' => $row->category ?? 'Lainnya',
                'proyek' => $row->proyek ?? '-',
                'warehouse_name' => $row->warehouse_name,
                'stock' => (int) $row->stock,
                'sn' => $sns,
                'base_cost' => (float) ($row->base_cost ?? 0),
                'base_price' => (float) ($row->base_price ?? 0),
                'age_days' => $ageDays,
            ];
        }

        return Excel::download(new StockReportExport($exportData), $filename);
    }

    /**
     * Export CSV streaming dengan streamDownload dan UTF-8 BOM untuk Microsoft Excel
     */
    public function exportCsv()
    {
        if (!$this->hasAppliedFilter) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Silakan klik Terapkan Filter terlebih dahulu sebelum mengekspor data.', type: 'warning');
            return;
        }

        @ini_set('max_execution_time', 300);
        @set_time_limit(300);

        $baseQuery = $this->applySorting($this->buildFilteredQuery())->select($this->getSelectColumns());

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data stok untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return;
        }

        $filename = "laporan_stok_" . date('Ymd_His') . ".csv";
        $separator = $this->csvSeparator;

        // Pre-fetch nomor seri hanya 1x ke memori (O(1) hash map)
        $snLookup = ProductSerialNumber::query()
            ->where('status', 'Available')
            ->whereNotNull('product_accurate_id')
            ->select('product_accurate_id', 'warehouse_id', 'serial_number')
            ->get()
            ->groupBy(fn($sn) => $sn->product_accurate_id . '_' . ($sn->warehouse_id ?? 'null'))
            ->map(fn($group) => $group->pluck('serial_number')->implode(', '));

        return response()->streamDownload(function () use ($baseQuery, $separator, $snLookup) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM agar Excel otomatis membaca format koma/titik-koma dan karakter bahasa Indonesia
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'SKU',
                'NAMA PRODUK',
                'MEREK',
                'KATEGORI',
                'PROYEK',
                'GUDANG',
                'STOK GUDANG',
                'SN',
                'HARGA BELI (MODAL)',
                'HARGA JUAL',
                'UMUR PRODUK (HARI)'
            ], $separator);

            $now = now();
            foreach ($baseQuery->cursor() as $row) {
                $snKey = $row->product_id . '_' . ($row->warehouse_id ?? 'null');
                $sns = $snLookup[$snKey] ?? '-';
                $createdAt = $row->created_at ? Carbon::parse($row->created_at) : null;
                $ageDays = $createdAt ? round($createdAt->diffInDays($now)) : 0;

                fputcsv($file, [
                    $row->sku ?? '-',
                    $row->name ?? 'Unknown',
                    $row->brand ?? '-',
                    $row->category ?? 'Lainnya',
                    $row->proyek ?? '-',
                    $row->warehouse_name,
                    $row->stock,
                    $sns,
                    round($row->base_cost ?? 0),
                    round($row->base_price ?? 0),
                    $ageDays
                ], $separator);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function render()
    {
        @ini_set('max_execution_time', 180);
        @set_time_limit(180);

        // 1. Cache opsi dropdown filter (1 jam) agar tidak scan tabel setiap render
        $cacheKey = 'stock_report_filters_bu_' . ($this->filterBU ?: 'all');
        $filterOptions = Cache::remember($cacheKey, 3600, function () {
            // 1 query DISTINCT untuk mengambil semua merek, kategori, dan proyek sekaligus (memanfaatkan index BU)
            $distinctData = DB::table('product_accurates')
                ->when(!empty($this->filterBU), fn($q) => $q->where('business_unit_id', $this->filterBU))
                ->selectRaw('DISTINCT brandName, categoryName, proyek')
                ->get();

            $whQuery = Warehouse::query();
            if (!empty($this->filterBU)) {
                $whQuery->where('business_unit_id', $this->filterBU);
            }
            $whList = $whQuery->pluck('name')->sort()->values();
            if (!$whList->contains('Belum Dialokasikan')) {
                $whList->push('Belum Dialokasikan');
            }

            // Ambil master proyek dari business_unit_projects agar sinkron dengan proyek BU aktif
            $bupQuery = \App\Models\BusinessUnitProject::query();
            if (!empty($this->filterBU)) {
                $bupQuery->where('business_unit_id', $this->filterBU);
            }
            $bupNames = $bupQuery->pluck('name');

            $allProyeks = $distinctData->pluck('proyek')
                ->concat($bupNames)
                ->map(fn($p) => trim((string)$p))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            return [
                'brands' => $distinctData->pluck('brandName')->filter()->unique()->sort()->values(),
                'categories' => $distinctData->pluck('categoryName')->filter()->unique()->sort()->values(),
                'proyeks' => $allProyeks,
                'warehouses' => $whList,
            ];
        });

        // KONDISI ON-DEMAND: Sebelum user klik "Terapkan Filter", jangan jalankan query apa pun
        if (!$this->hasAppliedFilter) {
            return view('livewire.zoffline.reporting.stock-report', [
                'stocks' => new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 15, 1),
                'availableWarehouses' => $filterOptions['warehouses'],
                'availableCategories' => $filterOptions['categories'],
                'availableBrands' => $filterOptions['brands'],
                'availableProyeks' => $filterOptions['proyeks'],
                'summary' => [
                    'total_items' => 0,
                    'total_cost' => 0,
                    'total_potential_revenue' => 0,
                ],
                'hasAppliedFilter' => false,
            ])->layout('layouts.z');
        }

        // 2. Query dasar dengan seluruh filter aktif
        $baseQuery = $this->buildFilteredQuery();

        // 3. Kalkulasi summary cards sekaligus total baris dalam 1 query saja (menghilangkan query COUNT ganda dari paginate)
        $summaryRow = (clone $baseQuery)->selectRaw("
            COALESCE(SUM(COALESCE(ws.stock, pa.stock, 0)), 0) as total_items,
            COALESCE(SUM(COALESCE(pa.base_cost, 0) * COALESCE(ws.stock, pa.stock, 0)), 0) as total_cost,
            COALESCE(SUM(COALESCE(pa.base_price, 0) * COALESCE(ws.stock, pa.stock, 0)), 0) as total_potential_revenue,
            COUNT(*) as total_rows
        ")->first();

        $totalRows = (int) ($summaryRow->total_rows ?? 0);
        $summary = [
            'total_items' => (int) ($summaryRow->total_items ?? 0),
            'total_cost' => (float) ($summaryRow->total_cost ?? 0),
            'total_potential_revenue' => (float) ($summaryRow->total_potential_revenue ?? 0),
        ];

        // 4. Ambil 15 item untuk halaman aktif (LIMIT 15 OFFSET ...) tanpa melakukan query count terpisah
        $currentPage = $this->getPage();
        $items = $this->applySorting(clone $baseQuery)
            ->select($this->getSelectColumns())
            ->forPage($currentPage, 15)
            ->get();

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalRows,
            15,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        // 5. Query nomor seri HANYA untuk 15 produk di halaman ini
        $productIds = collect($paginated->items())->pluck('product_id')->unique()->filter()->all();

        $serialNumbers = !empty($productIds)
            ? ProductSerialNumber::query()
                ->whereIn('product_accurate_id', $productIds)
                ->where('status', 'Available')
                ->select('product_accurate_id', 'warehouse_id', 'serial_number')
                ->get()
                ->groupBy(fn($sn) => $sn->product_accurate_id . '_' . ($sn->warehouse_id ?? 'null'))
            : collect();

        $stocks = $paginated->through(function ($row) use ($serialNumbers) {
            $snKey = $row->product_id . '_' . ($row->warehouse_id ?? 'null');
            $sns = isset($serialNumbers[$snKey]) ? $serialNumbers[$snKey]->pluck('serial_number')->implode(', ') : '-';

            $createdAt = $row->created_at ? Carbon::parse($row->created_at) : null;
            $updatedAt = $row->updated_at ? Carbon::parse($row->updated_at) : null;

            return [
                'id' => 'a_' . $row->product_id . '_' . ($row->warehouse_id ?? '0'),
                'sku' => $row->sku ?? '-',
                'name' => $row->name ?? 'Unknown',
                'brand' => $row->brand ?? '-',
                'vendor_name' => $row->vendor_name ?? '-',
                'category' => $row->category ?? 'Lainnya',
                'proyek' => $row->proyek ?? '-',
                'warehouse_name' => $row->warehouse_name,
                'stock' => (int) $row->stock,
                'sn' => $sns ?: '-',
                'base_cost' => (float) ($row->base_cost ?? 0),
                'base_price' => (float) ($row->base_price ?? 0),
                'age_days' => $createdAt ? round($createdAt->diffInDays(now())) : 0,
                'created_at' => $createdAt,
                'sync_date' => $updatedAt ? $updatedAt->format('Y-m-d') : '-',
                'sync_datetime' => $updatedAt ? $updatedAt->format('Y-m-d H:i:s') : '-',
            ];
        });

        return view('livewire.zoffline.reporting.stock-report', [
            'stocks' => $stocks,
            'availableWarehouses' => $filterOptions['warehouses'],
            'availableCategories' => $filterOptions['categories'],
            'availableBrands' => $filterOptions['brands'],
            'availableProyeks' => $filterOptions['proyeks'],
            'summary' => $summary,
            'hasAppliedFilter' => true,
        ])->layout('layouts.z');
    }
}
