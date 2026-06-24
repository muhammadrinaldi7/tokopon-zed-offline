<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductSerialNumber;
use App\Models\Warehouse;

class LaporanStok extends Component
{
    use WithPagination;

    public $search = '';
    public $warehouseId = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingWarehouseId()
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
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        $validWarehouseIds = Warehouse::where('business_unit_id', $buId)->pluck('id')->toArray();

        $query = ProductSerialNumber::with(['productAccurate', 'warehouse', 'vendor'])
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
            ->orderBy($this->sortField, $this->sortDirection);

        $data = $query->get();

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

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

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
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        $validWarehouseIds = Warehouse::where('business_unit_id', $buId)->pluck('id')->toArray();

        $query = ProductSerialNumber::with(['productAccurate', 'warehouse', 'vendor'])
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
            ->orderBy($this->sortField, $this->sortDirection);

        $stocks = $query->paginate(20);
        $warehouses = Warehouse::where('business_unit_id', $buId)->orderBy('name')->get();

        return view('livewire.zoffline.reporting.laporan-stok', [
            'stocks' => $stocks,
            'warehouses' => $warehouses
        ])->layout('layouts.z');
    }
}
