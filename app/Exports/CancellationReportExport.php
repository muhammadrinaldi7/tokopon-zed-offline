<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CancellationReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
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
                $item['request_date'] ?? '-',
                $item['order_number'] ?? '-',
                $item['channel'] ?? '-',
                $item['accurate_no'] ?? '-',
                $item['cashier_name'] ?? '-',
                $item['branch_name'] ?? '-',
                $item['customer_name'] ?? '-',
                $item['customer_phone'] ?? '-',
                isset($item['grand_total']) ? (float)$item['grand_total'] : 0,
                $item['reason'] ?? '-',
                $item['status'] ?? '-',
                $item['processed_by'] ?? '-',
                $item['processed_at'] ?? '-',
                $item['approval_notes'] ?? '-',
            ];
        }

        return $formatted;
    }

    public function headings(): array
    {
        return [
            'NO.',
            'TANGGAL PENGAJUAN',
            'NO. ORDER',
            'CHANNEL',
            'NO. ACCURATE',
            'KASIR PENGAJU',
            'CABANG',
            'PELANGGAN',
            'NO. TELEPON',
            'TOTAL TRANSAKSI (RP)',
            'ALASAN PEMBATALAN',
            'STATUS PENGAJUAN',
            'DIPROSES OLEH',
            'WAKTU PROSES',
            'CATATAN APPROVAL',
        ];
    }

    public function title(): string
    {
        return 'Laporan Pembatalan';
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
                    'startColor' => ['argb' => 'FF1C69D4'], // Tokopon Brand Blue
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
