<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReturnReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    protected Collection $claims;
    protected int $rowNumber = 0;

    public function __construct(Collection $claims)
    {
        $this->claims = $claims;
    }

    public function collection(): Collection
    {
        return $this->claims;
    }

    public function headings(): array
    {
        return [
            'No.',
            'No. Klaim',
            'Tanggal Klaim',
            'No. Pesanan',
            'No. Invoice Accurate',
            'Nama Pelanggan',
            'No. HP Pelanggan',
            'Nama Sales',
            'Produk',
            'Serial Number',
            'QTY',
            'HARGA SATUAN (Rp)',
            'DISKON ITEM (Rp)',
            'NAMA PROMO',
            'DISKON PROMO (Rp)',
            'SUBTOTAL ITEM (Rp)',
            'PENJUALAN BERSIH',
            'Kendala / Keluhan',
            'Diagnosis',
            'Status',
            'Resolusi',
            'Catatan Resolusi',
            'Nominal Refund',
            'Tanggal Selesai'
        ];
    }

    public function map($claim): array
    {
        $this->rowNumber++;

        $productName = $claim->warranty->orderItem->product_name 
            ?? ($claim->warranty->orderItem->variant->name ?? 'Produk Tidak Diketahui');

        $item = $claim->warranty->orderItem;
        $qty = $item->qty ?? 1;
        $hargaSatuan = $item->price_at_checkout ?? 0;
        $diskonItem = $item->discount_amount ?? 0;
        $subtotalItem = $item->subtotal ?? ($hargaSatuan * $qty);

        $promoNames = '-';
        $diskonPromo = 0;
        if ($item && $item->relationLoaded('promos') && $item->promos->count() > 0) {
            $promoNames = $item->promos->pluck('name')->unique()->implode(', ');
            $diskonPromo = $item->promos->sum('pivot.discount_amount');
        }

        $actualSubtotal = $subtotalItem - $diskonItem - $diskonPromo;
        $penjualanBersih = round($actualSubtotal / 1.11);

        return [
            $this->rowNumber,
            $claim->claim_number,
            $claim->claimed_at ? $claim->claimed_at->format('Y-m-d H:i:s') : '-',
            $item->order->order_number ?? '-',
            $item->order->accurate_invoice_no ?? '-',
            $claim->customer->name ?? '-',
            $claim->customer->profile->phone_number ?? '-',
            $item->order->salesBy->name ?? '-',
            $productName,
            $claim->serial_number,
            $qty,
            $hargaSatuan,
            $diskonItem,
            $promoNames,
            $diskonPromo,
            $subtotalItem,
            $penjualanBersih,
            $claim->issue_description,
            $claim->diagnosis ?? '-',
            $claim->status_badge->label ?? '-',
            $claim->resolution ? ucfirst($claim->resolution) : '-',
            $claim->resolution_notes ?? '-',
            $claim->refund_amount ? (float) $claim->refund_amount : 0,
            $claim->resolved_at ? $claim->resolved_at->format('Y-m-d H:i:s') : '-',
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
