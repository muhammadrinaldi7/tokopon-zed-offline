<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanStokExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping, WithTitle
{
    protected Collection $items;

    public function __construct(Collection $items)
    {
        $this->items = $items;
    }

    public function collection(): Collection
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Serial Number',
            'SKU / Item No',
            'Nama Produk',
            'Brand',
            'Kategori',
            'Lokasi Gudang',
            'Harga Pokok (HPP)',
            'Vendor',
            'Tanggal Masuk',
            'Umur (Hari)',
            'Status',
        ];
    }

    public function map($item): array
    {
        $umur = $item->receipt_date 
            ? intval(\Carbon\Carbon::parse($item->receipt_date)->startOfDay()->diffInDays(now()->startOfDay())) . ' Hari' 
            : '-';

        return [
            $item->serial_number,
            $item->item_no,
            $item->productAccurate->name ?? '-',
            $item->productAccurate->brandName ?? '-',
            $item->productAccurate->categoryName ?? '-',
            $item->warehouse->name ?? 'Belum Dialokasikan',
            round($item->hpp ?? 0),
            $item->vendor->vendor_name ?? '-',
            $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('Y-m-d') : '-',
            $umur,
            $item->status,
        ];
    }

    public function title(): string
    {
        return 'Laporan Stok Serial Number';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1C69D4'], // Brand Blue
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
