<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesVendorReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'VENDOR',
            'CABANG',
            'TANGGAL',
            'NO. ORDER',
            'NO. INVOICE',
            'KASIR',
            'SALES',
            'PELANGGAN',
            'SKU',
            'NAMA PRODUK',
            'SN (SerialNumber)',
            'QTY',
            'CATATAN',
            'HARGA SATUAN (Rp)',
            'DISKON ITEM (Rp)',
            'DISKON PROMO (Rp)',
            'SUBTOTAL ITEM (Rp)',
            'PENJUALAN BERSIH (Rp)'
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan Per Vendor';
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
                    'startColor' => ['argb' => 'FF059669'], // Emerald 600
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
