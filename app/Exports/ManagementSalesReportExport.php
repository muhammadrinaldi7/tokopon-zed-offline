<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ManagementSalesReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
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
            'TANGGAL',
            'NO. ORDER',
            'NO. INVOICE',
            'NO. SALES ORDER(SO)',
            'UNIT BISNIS',
            'KASIR',
            'SALES',
            'PELANGGAN',
            'TELEPON',
            'CABANG',
            'PROYEK',
            'SKU',
            'NAMA PRODUK',
            'MERK PRODUK',
            'CATEGORY',
            'VENDOR',
            'SN (SerialNumber)',
            'CATATAN',
            'QTY',
            'HARGA SATUAN (Rp)',
            'DISKON ITEM (Rp)',
            'NAMA PROMO',
            'DISKON PROMO (Rp)',
            'SUBTOTAL ITEM (Rp)',
            'PENJUALAN BERSIH (Rp)',
            'HPP (Rp)',
            'MARGIN (Rp)',
            'MARGIN (%)'
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan Management';
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
                    'startColor' => ['argb' => 'FF1E293B'], // Dark slate / corporate navy header
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
