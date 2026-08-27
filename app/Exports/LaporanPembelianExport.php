<?php

namespace App\Exports;

use App\Models\SellPhone;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPembelianExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $filterStartDate;
    protected $filterEndDate;
    protected $filterBranchId;
    protected $filterStatus;
    
    private $rowNumber = 0;

    public function __construct($filters)
    {
        $this->search = $filters['search'] ?? '';
        $this->filterStartDate = $filters['filterStartDate'] ?? '';
        $this->filterEndDate = $filters['filterEndDate'] ?? '';
        $this->filterBranchId = $filters['filterBranchId'] ?? '';
        $this->filterStatus = $filters['filterStatus'] ?? '';
    }

    public function query()
    {
        $query = SellPhone::query()
            ->with(['user', 'handledBy', 'branch', 'productAccurate'])
            ->where('business_unit_id', 2);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhere('phone_model', 'like', '%' . $this->search . '%')
                  ->orWhere('phone_brand', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterStartDate)) {
            $query->whereDate('created_at', '>=', $this->filterStartDate);
        }

        if (!empty($this->filterEndDate)) {
            $query->whereDate('created_at', '<=', $this->filterEndDate);
        }

        if (!empty($this->filterBranchId)) {
            $query->where('branch_id', $this->filterBranchId);
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Transaksi',
            'No. Invoice',
            'Merek & Model HP',
            'Kategori',
            'Proyek',
            'Handled By',
            'Cabang',
            'Customer',
            'Status',
            'Harga Dasar',
            'Harga Sistem',
            'Harga Beli Aktual',
        ];
    }

    public function map($sellPhone): array
    {
        $this->rowNumber++;

        $kategori = $sellPhone->productAccurate ? $sellPhone->productAccurate->categoryName : '-';
        $proyek = $sellPhone->productAccurate ? $sellPhone->productAccurate->proyek : '-';
        $hargaDasar = $sellPhone->productAccurate ? $sellPhone->productAccurate->buy_price : 0;

        return [
            $this->rowNumber,
            $sellPhone->created_at->format('Y-m-d H:i:s'),
            $sellPhone->invoice_number ?: '-',
            $sellPhone->phone_brand . ' - ' . $sellPhone->phone_model,
            $kategori,
            $proyek,
            $sellPhone->handledBy ? $sellPhone->handledBy->name : '-',
            $sellPhone->branch ? $sellPhone->branch->name : '-',
            $sellPhone->user ? $sellPhone->user->name : 'Tamu',
            $sellPhone->status,
            $hargaDasar,
            $sellPhone->original_appraised_value ?? 0,
            $sellPhone->appraised_value ?? 0,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
