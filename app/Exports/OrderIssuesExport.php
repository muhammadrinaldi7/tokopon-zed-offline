<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderIssuesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    protected Collection $issues;
    protected int $rowNumber = 0;

    public function __construct(Collection $issues)
    {
        $this->issues = $issues;
    }

    public function collection(): Collection
    {
        return $this->issues;
    }

    public function headings(): array
    {
        return [
            'No.',
            'ID Issue',
            'Waktu Lapor',
            'No. Pesanan',
            'Status Pesanan',
            'Nama Customer',
            'No. HP Customer',
            'Cabang / Toko',
            'Kasir Transaksi',
            'Sales Transaksi',
            'Total Belanja (Rp)',
            'Pelapor Kendala',
            'Kategori Kesalahan',
            'Rincian Kendala / Komentar',
            'Status Kendala',
            'Terakhir Diperbarui'
        ];
    }

    public function map($issue): array
    {
        $this->rowNumber++;

        $categoryLabels = [
            'SALAH_METODE_BAYAR' => 'Salah Metode Bayar',
            'SALAH_DISKON' => 'Salah Input Diskon',
            'SALAH_ITEM' => 'Salah Input Item',
        ];

        $order = $issue->order;

        return [
            $this->rowNumber,
            $issue->id,
            $issue->created_at ? $issue->created_at->format('Y-m-d H:i:s') : '-',
            $order ? $order->order_number : 'Order Terhapus',
            $order ? $order->order_status : '-',
            $order && $order->user ? $order->user->name : 'Umum / Terhapus',
            $order && $order->user && $order->user->profile ? ($order->user->profile->phone_number ?? '-') : '-',
            $order && $order->branch ? $order->branch->name : ($order->shipping_address_snapshot['store'] ?? '-'),
            $order && $order->handledBy ? $order->handledBy->name : '-',
            $order && $order->salesBy ? $order->salesBy->name : '-',
            $order ? (float) $order->grand_total : 0,
            $issue->user ? $issue->user->name : 'User Terhapus',
            $categoryLabels[$issue->category] ?? $issue->category,
            $issue->comment,
            $issue->status === 'RESOLVED' ? 'SELESAI (RESOLVED)' : 'BELUM SELESAI (OPEN)',
            $issue->updated_at ? $issue->updated_at->format('Y-m-d H:i:s') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1C69D4'], // Brand Blue color
                ],
            ],
        ];
    }
}
