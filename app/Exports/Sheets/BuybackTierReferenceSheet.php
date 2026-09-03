<?php

namespace App\Exports\Sheets;

use App\Models\BuybackTier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BuybackTierReferenceSheet implements FromCollection, WithTitle, WithHeadings
{
    public function collection()
    {
        // Get all tier names
        return BuybackTier::select('name')->get();
    }

    public function headings(): array
    {
        return [
            'Daftar Tier Valid (Jangan Dihapus)'
        ];
    }

    public function title(): string
    {
        return 'Referensi Tier';
    }
}
