<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SellPhoneIssuesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
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
            'No. Transaksi',
            'Status Transaksi',
            'Perangkat',
            'Nama Pelanggan',
            'No. HP Pelanggan',
            'Bank Tujuan Transfer',
            'No. Rekening',
            'Atas Nama Rekening',
            'Nilai Taksiran (Rp)',
            'Toko / Unit Bisnis',
            'Frontliner',
            'Pelapor Kendala',
            'Kategori Kesalahan',
            'Rincian Kendala / Komentar',
            'Status Kendala',
            'Penyelesai Kendala',
            'Waktu Selesai',
            'Catatan Penyelesaian',
            'Terakhir Diperbarui'
        ];
    }

    public function map($issue): array
    {
        $this->rowNumber++;

        $categoryLabels = [
            'SALAH_NOREK' => 'Salah No. Rekening / Bank',
            'SALAH_NOMINAL' => 'Salah Input Nominal / Taksiran',
            'SALAH_QC' => 'Salah Hasil Inspeksi / Grade QC',
            'SALAH_IMEI' => 'Salah Input IMEI / SN',
            'GAGAL_TRANSFER' => 'Gagal Transfer / Rekening Pasif',
            'LAINNYA' => 'Kendala Lainnya',
        ];

        $sellPhone = $issue->sellPhone;
        $userBank = $sellPhone?->user?->bankAccounts?->first();

        $bankName = $sellPhone?->bank_name ?: ($userBank?->bank_name ?? '-');
        $bankAccountNo = $sellPhone?->bank_account_number ?: ($userBank?->account_number ?? '-');
        $bankAccountName = $sellPhone?->bank_account_name ?: ($userBank?->account_name ?? '-');

        return [
            $this->rowNumber,
            $issue->id,
            $issue->created_at ? $issue->created_at->format('Y-m-d H:i:s') : '-',
            $sellPhone ? ('SPL-' . $sellPhone->id) : 'SPL Terhapus',
            $sellPhone ? $sellPhone->status : '-',
            $sellPhone ? ($sellPhone->phone_brand . ' ' . $sellPhone->phone_model . ' (' . ($sellPhone->phone_ram ?: '-') . '/' . ($sellPhone->phone_storage ?: '-') . ')') : '-',
            $sellPhone && $sellPhone->user ? $sellPhone->user->name : 'Umum / Terhapus',
            $sellPhone && $sellPhone->user && $sellPhone->user->profile ? ($sellPhone->user->profile->phone_number ?? '-') : '-',
            $bankName,
            $bankAccountNo,
            $bankAccountName,
            $sellPhone ? (float) $sellPhone->appraised_value : 0,
            $sellPhone && $sellPhone->businessUnit ? $sellPhone->businessUnit->name : '-',
            $sellPhone && $sellPhone->handledBy ? $sellPhone->handledBy->name : '-',
            $issue->user ? $issue->user->name : 'User Terhapus',
            $categoryLabels[$issue->category] ?? $issue->category,
            $issue->comment,
            $issue->status === 'RESOLVED' ? 'SELESAI (RESOLVED)' : 'BELUM SELESAI (OPEN)',
            $issue->resolvedBy ? $issue->resolvedBy->name : '-',
            $issue->resolved_at ? $issue->resolved_at->format('Y-m-d H:i:s') : '-',
            $issue->resolution_notes ?: '-',
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
