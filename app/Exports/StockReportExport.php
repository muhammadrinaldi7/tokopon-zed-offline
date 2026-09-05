<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $formatted = [];
        $rowNumber = 0;
        foreach ($this->data as $item) {
            $rowNumber++;
            $formatted[] = [
                $rowNumber,
                $item['sku'] ?? '-',
                $item['name'] ?? '-',
                $item['brand'] ?? '-',
                $item['category'] ?? '-',
                $item['proyek'] ?? '-',
                $item['warehouse_name'] ?? '-',
                $item['stock'] ?? 0,
                $item['sn'] ?? '-',
                round($item['base_cost'] ?? 0),
                round($item['base_price'] ?? 0),
                $item['age_days'] ?? 0,
            ];
        }
        return $formatted;
    }

    public function headings(): array
    {
        return [
            'No.',
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
            'UMUR PRODUK (HARI)',
        ];
    }

    public function title(): string
    {
        return 'Laporan Stok Barang';
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
