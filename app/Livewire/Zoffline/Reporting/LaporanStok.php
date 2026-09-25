<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\ProductSerialNumber;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\BusinessUnitProject;
use App\Exports\LaporanStokExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanStok extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public $search = '';

    #[Url(except: '')]
    public $warehouseId = '';

    #[Url(as: 'vendor_id', except: '')]
    public $vendor_id = '';

    #[Url(as: 'subkategori', except: '')]
    public $subkategori = '';

    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $csvSeparator = ';';

    public function mount()
    {
        if (request()->has('vendor_id') && !empty(request()->query('vendor_id'))) {
            $this->vendor_id = request()->query('vendor_id');
        }

        if (request()->has('subkategori') && !empty(request()->query('subkategori'))) {
            $this->subkategori = request()->query('subkategori');
        } elseif (request()->has('proyek') && !empty(request()->query('proyek'))) {
            $this->subkategori = request()->query('proyek');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingWarehouseId()
    {
        $this->resetPage();
    }

    public function updatedWarehouseId()
    {
        $this->resetPage();
    }

    public function updatingVendorId()
    {
        $this->resetPage();
    }

    public function updatedVendorId()
    {
        $this->resetPage();
    }

    public function updatingSubkategori()
    {
        $this->resetPage();
    }

    public function updatedSubkategori()
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

    protected function getStockQuery()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()?->getActiveBusinessUnitId();
        $validWarehouseIds = Warehouse::where('business_unit_id', $buId)->pluck('id')->toArray();

        return ProductSerialNumber::query()
            ->select('product_serial_numbers.*')
            ->with(['productAccurate', 'warehouse', 'vendor'])
            ->where('product_serial_numbers.status', 'Available')
            ->whereIn('product_serial_numbers.warehouse_id', $validWarehouseIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('product_serial_numbers.serial_number', 'like', '%' . $this->search . '%')
                        ->orWhere('product_serial_numbers.item_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('productAccurate', function ($sub) {
                            $sub->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('proyek', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->warehouseId, function ($query) {
                $query->where('product_serial_numbers.warehouse_id', $this->warehouseId);
            })
            ->when($this->vendor_id, function ($query) {
                $query->where('product_serial_numbers.vendor_id', $this->vendor_id);
            })
            ->when($this->subkategori, function ($query) {
                $sub = $this->subkategori;
                $query->where(function ($q) use ($sub) {
                    $q->whereHas('productAccurate', function ($paQuery) use ($sub) {
                        $paQuery->where('proyek', $sub);
                    })->orWhere(function ($fallbackQuery) use ($sub) {
                        $fallbackQuery->whereNull('product_serial_numbers.product_accurate_id')
                            ->whereIn('product_serial_numbers.item_no', function ($inner) use ($sub) {
                                $inner->select('item_no')
                                    ->from('product_accurates')
                                    ->where('proyek', $sub);
                            });
                    });
                });
            })
            ->when($this->sortField === 'subkategori', function ($query) {
                $query->leftJoin('product_accurates', 'product_serial_numbers.product_accurate_id', '=', 'product_accurates.id')
                    ->orderBy('product_accurates.proyek', $this->sortDirection);
            }, function ($query) {
                $query->orderBy('product_serial_numbers.' . $this->sortField, $this->sortDirection);
            });
    }

    public function exportExcel()
    {
        $data = $this->getStockQuery()->get();

        if ($data->isEmpty()) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data stok yang sesuai filter untuk diexport.', type: 'warning');
            return;
        }

        $filename = "laporan_stok_sn_" . date('Ymd_His') . ".xlsx";
        return Excel::download(new LaporanStokExport($data), $filename);
    }

    public function exportCsv()
    {
        $data = $this->getStockQuery()->get();

        if ($data->isEmpty()) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data stok yang sesuai filter untuk diexport.', type: 'warning');
            return;
        }

        $filename = "laporan_stok_sn_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = [
            'SERIAL NUMBER',
            'SKU',
            'NAMA PRODUK',
            'BRAND',
            'KATEGORI',
            'SUBKATEGORI',
            'GUDANG',
            'HPP',
            'VENDOR',
            'STATUS',
            'TANGGAL TERIMA',
            'UMUR (HARI)'
        ];

        $separator = $this->csvSeparator;

        $callback = function () use ($data, $columns, $separator) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns, $separator);

            foreach ($data as $item) {
                $umur = $item->receipt_date ? intval(\Carbon\Carbon::parse($item->receipt_date)->startOfDay()->diffInDays(now()->startOfDay())) . ' Hari' : '-';
                fputcsv($file, [
                    $item->serial_number,
                    $item->item_no,
                    $item->productAccurate->name ?? '-',
                    $item->productAccurate->brandName ?? '-',
                    $item->productAccurate->categoryName ?? '-',
                    $item->productAccurate->proyek ?? ($item->proyek ?? '-'),
                    $item->warehouse->name ?? '-',
                    round($item->hpp ?? 0),
                    $item->vendor->vendor_name ?? '-',
                    $item->status,
                    $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('Y-m-d') : '-',
                    $umur
                ], $separator);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()?->getActiveBusinessUnitId();

        $stocks = $this->getStockQuery()->paginate(20);
        $warehouses = Warehouse::where('business_unit_id', $buId)->orderBy('name')->get();
        $vendors = Vendor::orderBy('vendor_name')->get();

        $proyekQuery = ProductAccurate::whereNotNull('proyek')
            ->where('proyek', '!=', '');

        if ($buId) {
            $proyekQuery->where('business_unit_id', $buId);
        }

        $subkategoris = $proyekQuery->distinct()
            ->pluck('proyek')
            ->map(fn($p) => trim((string)$p))
            ->filter();

        // Fallback jika BU saat ini belum memiliki proyek terisi di ProductAccurate
        if ($subkategoris->isEmpty()) {
            $subkategoris = ProductAccurate::whereNotNull('proyek')
                ->where('proyek', '!=', '')
                ->distinct()
                ->pluck('proyek')
                ->map(fn($p) => trim((string)$p))
                ->filter();
        }

        $bupQuery = BusinessUnitProject::query();
        if ($buId) {
            $bupQuery->where('business_unit_id', $buId);
        }
        $bupNames = $bupQuery->pluck('name')->map(fn($p) => trim((string)$p))->filter();

        $subkategoris = $subkategoris->concat($bupNames)
            ->unique()
            ->sort()
            ->values();

        return view('livewire.zoffline.reporting.laporan-stok', [
            'stocks' => $stocks,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'subkategoris' => $subkategoris,
        ])->layout('layouts.z');
    }
}
