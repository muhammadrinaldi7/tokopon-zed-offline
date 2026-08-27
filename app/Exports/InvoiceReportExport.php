<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $formatted = [];
        foreach ($this->data as $item) {
            $formatted[] = [
                $item['created_at'] ?? '-',
                $item['nama_kasir'] ?? '-',
                $item['jam'] ?? '-',
                $item['nama_toko'] ?? '-',
                $item['accurate_invoice_no'] ?? '-',
                $item['order_number'] ?? '-',
                $item['catatan'] ?? '-',
                $item['no_kontrak'] ?? '-',
                $item['tipe_pembayaran'] ?? '-',
                $item['bankName'] ?? '-',
                $item['paymentMethod'] ?? '-',
                $item['variantMethod'] ?? '-',
                $item['amount'] !== null ? round($item['amount']) : null,
                $item['mdr'] !== null ? round($item['mdr']) : null,
            ];
        }
        return $formatted;
    }

    public function headings(): array
    {
        return [
            'TANGGAL',
            'NAMA KASIR',
            'JAM',
            'NAMA TOKO',
            'ACCURATE INVOICE NO',
            'ORDER NUMBER',
            'CATATAN',
            'NO KONTRAK',
            'TIPE PEMBAYARAN',
            'BANK NAME',
            'PAYMENT METHOD',
            'VARIANT METHOD',
            'AMOUNT (Rp)',
            'MDR (Rp)',
        ];
    }

    public function title(): string
    {
        return 'Laporan Pembayaran';
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
