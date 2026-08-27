<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarrantyReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
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
                $item['order_date'] ?? '-',
                $item['activated_at'] ?? '-',
                $item['order_number'] ?? '-',
                $item['branch'] ?? '-',
                $item['serial_number'] ?? '-',
                $item['category'] ?? '-',
                $item['product_name'] ?? '-',
                $item['customer_name'] ?? '-',
                $item['policy_name'] ?? '-',
                $item['inspector'] ?? '-',
                $item['promotor'] ?? '-',
                $item['status'] ?? '-',
            ];
        }
        return $formatted;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Tgl Transaksi',
            'Tgl Aktivasi',
            'No. Order',
            'Cabang',
            'SN / Perangkat',
            'Kategori',
            'Nama Barang',
            'Nama Pelanggan',
            'Tipe Garansi',
            'Inspektur (QC)',
            'Promotor (Sales)',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Laporan Aktivasi Garansi';
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
