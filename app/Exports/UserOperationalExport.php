<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserOperationalExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    protected Collection $users;
    protected int $rowNumber = 0;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    public function collection(): Collection
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'No.',
            'ID Pengguna',
            'Nama Lengkap',
            'Email',
            'Unit Usaha',
            'Peran (Roles)',
            'Cabang (Branch)',
            'Gudang (Warehouse)',
            'Telegram Chat ID',
            'No. Identitas (KTP)',
            'NPWP',
            'Tanggal Terdaftar',
        ];
    }

    public function map($user): array
    {
        $this->rowNumber++;

        $roles = $user->roles && $user->roles->count() > 0 
            ? $user->roles->pluck('name')->map(fn($r) => ucfirst($r))->implode(', ')
            : 'User Biasa';

        return [
            $this->rowNumber,
            $user->id,
            $user->name ?? '-',
            $user->email ?? '-',
            $user->businessUnit->name ?? '-',
            $roles,
            $user->branch->name ?? '-',
            $user->warehouse->name ?? '-',
            $user->telegram_chat_id ?? '-',
            $user->identity ?? '-',
            $user->npwp ?? '-',
            $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4E44DB'], 
                ],
            ],
        ];
    }
}
