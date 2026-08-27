<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\ProductSerialNumber;
use App\Models\Warehouse;
use App\Models\Vendor;
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

    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $csvSeparator = ';';

    public function mount()
    {
        if (request()->has('vendor_id') && !empty(request()->query('vendor_id'))) {
            $this->vendor_id = request()->query('vendor_id');
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
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        $validWarehouseIds = Warehouse::where('business_unit_id', $buId)->pluck('id')->toArray();

        return ProductSerialNumber::with(['productAccurate', 'warehouse', 'vendor'])
            ->where('status', 'Available')
            ->whereIn('warehouse_id', $validWarehouseIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('serial_number', 'like', '%' . $this->search . '%')
                        ->orWhere('item_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('productAccurate', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->warehouseId, function ($query) {
                $query->where('warehouse_id', $this->warehouseId);
            })
            ->when($this->vendor_id, function ($query) {
                $query->where('vendor_id', $this->vendor_id);
            })
            ->orderBy($this->sortField, $this->sortDirection);
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
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        $stocks = $this->getStockQuery()->paginate(20);
        $warehouses = Warehouse::where('business_unit_id', $buId)->orderBy('name')->get();
        $vendors = Vendor::orderBy('vendor_name')->get();

        return view('livewire.zoffline.reporting.laporan-stok', [
            'stocks' => $stocks,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
        ])->layout('layouts.z');
    }
}
