<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MessageLogExport extends StringValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle, WithCustomValueBinder
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Pastikan semua cell diekspor sebagai string biasa,
     * sehingga PhpSpreadsheet tidak pernah menginterpretasikan karakter '=' sebagai formula matematika.
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && strlen($value) > 0 && ($value[0] === '=' || str_starts_with(trim($value), '='))) {
            $cell->getStyle()->setQuotePrefix(true);
        }

        $cell->setValueExplicit((string) ($value ?? ''), DataType::TYPE_STRING);

        return true;
    }

    public function array(): array
    {
        $formatted = [];
        $rowNumber = 0;

        foreach ($this->data as $item) {
            $rowNumber++;
            $content = $item['content'] ?? '-';
            if (is_string($content)) {
                $content = str_replace('========================================', '----------------------------------------', $content);
                // Ubah baris apapun yang diawali '=' menjadi '-'
                $content = preg_replace('/^=+/m', '---', $content);
            }

            $row = [
                $rowNumber,
                $item['sent_at'] ?? '-',
                strtoupper($item['channel'] ?? '-'),
                ($item['is_sent'] ?? false) ? 'TERKIRIM' : 'GAGAL',
                $item['reference_number'] ?? '-',
                $item['recipient_name'] ?? '-',
                $item['recipient'] ?? '-',
                $item['subject'] ?? '-',
                $content,
                $item['attachment_name'] ?? '-',
                $item['sender_name'] ?? '-',
                $item['branch_name'] ?? '-',
                $item['business_unit_name'] ?? '-',
                $item['error_message'] ?? '-',
            ];

            // Cegah cell string diawali '='
            foreach ($row as $idx => $val) {
                if (is_string($val) && str_starts_with(trim($val), '=')) {
                    $row[$idx] = "'" . $val;
                }
            }

            $formatted[] = $row;
        }

        return $formatted;
    }

    public function headings(): array
    {
        return [
            'NO.',
            'WAKTU KIRIM',
            'SALURAN (CHANNEL)',
            'STATUS (IS_SENT)',
            'NO. REFERENSI',
            'NAMA PENERIMA',
            'NO. HP / EMAIL',
            'SUBJEK / TEMPLATE',
            'KONTEN / APA YANG DIKIRIM',
            'NAMA LAMPIRAN',
            'DIKIRIM OLEH',
            'CABANG',
            'UNIT USAHA',
            'KETERANGAN ERROR',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E293B'], // Dark slate
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Pesan Terkirim';
    }
}
