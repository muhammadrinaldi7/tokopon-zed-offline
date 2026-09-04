<?php

namespace App\Exports\Sheets;

use App\Models\ProductAccurate;
use App\Models\BuybackTier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class BuybackDeviceTemplateSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    public function collection()
    {
        // Ambil semua produk Accurate untuk referensi template (select kolom yang diperlukan saja agar tidak memuat raw_data)
        return ProductAccurate::with([
                'buybackDevice' => function ($q) {
                    $q->select('id', 'product_accurate_id', 'buyback_tier_id');
                },
                'buybackDevice.tier' => function ($q) {
                    $q->select('id', 'name');
                }
            ])
            ->select('id', 'item_no', 'os', 'categoryName', 'brandName', 'buy_price')
            ->where('business_unit_id', 2)
            ->orderBy('name')
            ->get();
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

    public function map($product): array
    {
        // Jika produk sudah pernah di-mapping sebelumnya, ambil ID mapping dan Nama Tier-nya
        $mappingId = $product->buybackDevice ? $product->buybackDevice->id : '';
        $tierName = ($product->buybackDevice && $product->buybackDevice->tier) ? $product->buybackDevice->tier->name : '';

        return [
            $mappingId, // ID kosong untuk data baru, atau terisi jika sudah pernah di-mapping
            $tierName, // Kosong jika belum di-mapping, terisi jika sudah
            $product->item_no ?? '',
            $product->os ?? '',
            $product->categoryName ?? '',
            $product->brandName ?? '',
            $product->buy_price ?? 0,
            1 // Is Active default 1
        ];
    }

    public function title(): string
    {
        return 'Data Produk';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Ambil jumlah data produk untuk batas row dropdown
                $rowCount = ProductAccurate::where('business_unit_id', 2)->count() + 100; // Tambah 100 row ekstra

                // Ambil jumlah Tier untuk range Referensi
                $tierCount = BuybackTier::count();

                if ($tierCount > 0) {
                    $validation = $sheet->getCell('B2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Tier Tidak Valid');
                    $validation->setError('Mohon pilih nama Tier dari opsi Dropdown yang tersedia (jangan ketik manual).');
                    $validation->setPromptTitle('Pilih Tier');
                    $validation->setPrompt('Pilih nama Tier untuk mem-mapping produk ini.');

                    // Rumus formula mengarah ke range A2 sampai A(jumlah+1) di Sheet 'Referensi Tier'
                    $validation->setFormula1('\'Referensi Tier\'!$A$2:$A$' . ($tierCount + 1));

                    // Set clone validation to all cells in column B (Tier Name)
                    $sheet->setDataValidation("B2:B{$rowCount}", clone $validation);
                }
            },
        ];
    }
}
