<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanUnitRentangHargaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping, WithTitle
{
    protected Collection $items;
    protected string $mode; // 'sku' or 'sn'

    public function __construct(Collection $items, string $mode = 'sku')
    {
        $this->items = $items;
        $this->mode = $mode;
    }

    public function collection(): Collection
    {
        return $this->items;
    }

    public function headings(): array
    {
        if ($this->mode === 'sku') {
            return [
                'SKU / Item No',
                'Nama Produk',
                'Brand',
                'Kategori',
                'Subkategori / Proyek',
                'Harga Jual (SKU)',
                'Total Unit Ready (Qty)',
                'Daftar Serial Number & Modal (HPP)',
                'Lokasi Gudang',
            ];
        }

        return [
            'Serial Number',
            'SKU / Item No',
            'Nama Produk',
            'Brand',
            'Kategori',
            'Subkategori / Proyek',
            'Lokasi Gudang',
            'Harga Pokok (HPP)',
            'Harga Jual',
            'Vendor',
            'Tanggal Masuk',
            'Umur (Hari)',
            'Status',
        ];
    }

    public function map($item): array
    {
        if ($this->mode === 'sku') {
            $sns = $item->productSerialNumbers ?? collect();
            $snList = $sns->map(function ($sn) {
                return $sn->serial_number . ' [HPP: Rp ' . number_format($sn->hpp ?? 0, 0, ',', '.') . ']';
            })->implode(', ');

            $warehouses = $sns->map(fn($sn) => $sn->warehouse->name ?? 'Belum Dialokasikan')->unique()->implode(', ');

            return [
                $item->item_no,
                $item->name ?? '-',
                $item->brandName ?? '-',
                $item->categoryName ?? '-',
                $item->proyek ?? '-',
                round($item->base_price ?? 0),
                $sns->count(),
                $snList ?: '-',
                $warehouses ?: '-',
            ];
        }

        $umur = $item->receipt_date
            ? intval(\Carbon\Carbon::parse($item->receipt_date)->startOfDay()->diffInDays(now()->startOfDay())) . ' Hari'
            : '-';

        return [
            $item->serial_number,
            $item->item_no,
            $item->productAccurate->name ?? '-',
            $item->productAccurate->brandName ?? '-',
            $item->productAccurate->categoryName ?? '-',
            $item->productAccurate->proyek ?? ($item->proyek ?? '-'),
            $item->warehouse->name ?? 'Belum Dialokasikan',
            round($item->hpp ?? 0),
            round($item->productAccurate->base_price ?? ($item->base_price ?? 0)),
            $item->vendor->vendor_name ?? '-',
            $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('Y-m-d') : '-',
            $umur,
            $item->status,
        ];
    }

    public function title(): string
    {
        return $this->mode === 'sku' ? 'Cek Harga per SKU & SN' : 'Cek Unit Rentang Harga';
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
