<?php

namespace App\Exports;

use App\Models\BuybackDevice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BuybackDeviceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return BuybackDevice::with(['tier', 'productAccurate'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tier Name',
            'SKU Accurate',
            'OS',
            'Kategori',
            'Brand',
            'Base Price',
            'Is Active'
        ];
    }

    public function map($device): array
    {
        return [
            $device->id,
            $device->tier ? $device->tier->name : '',
            $device->productAccurate ? $device->productAccurate->item_no : '',
            $device->os_name ?? '',
            $device->category_name ?? '',
            $device->brand_name ?? '',
            $device->base_price ?? 0,
            $device->is_active ? 1 : 0,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
