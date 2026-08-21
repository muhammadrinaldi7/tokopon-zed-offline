<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
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
            'KASIR',
            'SALES',
            'PELANGGAN',
            'TELEPON',
            'CABANG',
            'SKU',
            'NAMA PRODUK',
            'MERK PRODUK',
            'CATEGORY',
            'VENDOR',
            'WARNA',
            'STORAGE',
            'SN (SerialNumber)',
            'CATATAN',
            'QTY',
            'HARGA SATUAN (Rp)',
            'DISKON ITEM (Rp)',
            'NAMA PROMO',
            'DISKON PROMO (Rp)',
            'SUBTOTAL ITEM (Rp)',
            'PENJUALAN BERSIH',
            'METODE 1',
            'NOMINAL 1 (Rp)',
            'MDR 1 (%)',
            'BEBAN MDR 1 (Rp)',
            'TIPE BEBAN MDR 1',
            'NO KONTRAK 1',
            'METODE 2',
            'NOMINAL 2 (Rp)',
            'MDR 2 (%)',
            'BEBAN MDR 2 (Rp)',
            'TIPE BEBAN MDR 2',
            'NO KONTRAK 2',
            'METODE 3',
            'NOMINAL 3 (Rp)',
            'MDR 3 (%)',
            'BEBAN MDR 3 (Rp)',
            'TIPE BEBAN MDR 3',
            'NO KONTRAK 3',
            'METODE 4',
            'NOMINAL 4 (Rp)',
            'MDR 4 (%)',
            'BEBAN MDR 4 (Rp)',
            'TIPE BEBAN MDR 4',
            'NO KONTRAK 4',
            'TOTAL PEMBAYARAN'
        ];
    }

    public function title(): string
    {
        return 'Laporan Transaksi Penjualan';
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
                    'startColor' => ['argb' => 'FF1C69D4'], // Brand Blue color
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
