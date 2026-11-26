<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PromoReportExport extends StringValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle, WithCustomValueBinder, WithColumnFormatting
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Pastikan nilai teks (termasuk SN/IMEI panjang) disimpan sebagai string eksplisit
     * agar tidak berubah menjadi scientific notation di Excel, sementara angka klaim tetap numerik.
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_int($value) || is_float($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }

        $strValue = (string) ($value ?? '');
        if ($strValue !== '' && in_array($strValue[0], ['=', '+', '@'], true)) {
            $cell->getStyle()->setQuotePrefix(true);
        }

        $cell->setValueExplicit($strValue, DataType::TYPE_STRING);
        return true;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'TANGGAL',
            'UPDATED AT',
            'NO. ORDER',
            'NO. INVOICE',
            'CABANG',
            'SALES',
            'MERK PRODUK',
            'NAMA VENDOR',
            'TIPE ITEM',
            'NAMA PRODUK',
            'SN',
            'ITEM BUNDLING',
            'SN BUNDLING',
            'NAMA PROMO',
            'NILAI KLAIM PROMO (Rp)',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'K' => NumberFormat::FORMAT_TEXT,
            'M' => NumberFormat::FORMAT_TEXT,
            'O' => '#,##0',
        ];
    }

    public function title(): string
    {
        return 'Laporan Klaim Promo';
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
                    'startColor' => ['argb' => 'FF7C3AED'], // Purple / Violet brand
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
