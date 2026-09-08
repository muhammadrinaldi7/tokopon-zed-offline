<?php

namespace App\Exports;

use App\Models\StockOpname;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOpnameExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping, WithTitle
{
    protected StockOpname $opname;
    protected Collection $items;

    public function __construct(StockOpname $opname)
    {
        $this->opname = $opname;
        $this->items = $opname->items()->orderBy('difference_qty', 'asc')->get();
    }

    public function collection(): Collection
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'SKU / Item No',
            'Nama Produk',
            'Tipe Barang',
            'Stok Buku (Sistem)',
            'Stok Fisik Riil',
            'Selisih Kuantitas',
            'HPP Satuan (Rp)',
            'Total Nilai Selisih (Rp)',
            'Catatan Khusus',
        ];
    }

    public function map($item): array
    {
        return [
            $item->item_no,
            $item->product_name,
            $item->is_serialized ? 'Handphone (IMEI)' : 'Aksesoris',
            $item->system_qty,
            $item->physical_qty,
            $item->difference_qty,
            (float) $item->unit_cost,
            (float) $item->difference_value,
            $item->notes ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Hasil Audit Stock Opname';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4E44DB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
