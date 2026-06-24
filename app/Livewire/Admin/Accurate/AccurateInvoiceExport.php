<?php

namespace App\Livewire\Admin\Accurate;

use App\Models\MigrationInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class AccurateInvoiceExport extends Component
{
    use WithFileUploads;

    public $file;

    // 1. Fungsi Unduh Template (Sudah ada kolom serial_numbers)
    public function downloadTemplate()
    {
        $fileName = 'template_import_internal.csv';

        $columns = [
            'invoice_no',
            'invoice_date',
            'vendor_id',
            'branch_name',
            'description',
            'item_code',
            'quantity',
            'unit_price',
            'warehouse_name',
            'serial_numbers'
        ];

        // Contoh: Format 1 baris = 1 kuantitas = 1 Serial Number
        $exampleRow1 = [
            'INV-MIG-001',
            '25/06/2026',
            'GSK_VENDOR_40146',
            'GSK - Banjarbaru',
            'Migrasi dari Erzap',
            '100021',
            '1',
            '5000000',
            'GSK - Banjarbaru',
            '351234567890123'
        ];

        $exampleRow2 = [
            'INV-MIG-001',
            '25/06/2026',
            'GSK_VENDOR_40146',
            'GSK - Banjarbaru',
            'Migrasi dari Erzap',
            '100021',
            '1',
            '5000000',
            'GSK - Banjarbaru',
            '351234567890124'
        ];

        return response()->streamDownload(function () use ($columns, $exampleRow1, $exampleRow2) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $exampleRow1);
            fputcsv($file, $exampleRow2);
            fclose($file);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    // 2. Fungsi Import CSV dari User
    public function importData()
    {
        $this->validate([
            'file' => 'required|mimes:csv,txt|max:10240',
        ]);

        $filePath = $this->file->getRealPath();
        $fileHandle = fopen($filePath, 'r');
        $header = fgetcsv($fileHandle);

        DB::beginTransaction();
        try {
            $invoicesData = [];

            while ($row = fgetcsv($fileHandle)) {
                $data = array_combine($header, $row);
                $invoiceNo = trim($data['invoice_no']);

                if (!isset($invoicesData[$invoiceNo])) {
                    $invoicesData[$invoiceNo] = [
                        'invoice_date' => trim($data['invoice_date']),
                        'vendor_id' => trim($data['vendor_id']),
                        'branch_name' => trim($data['branch_name'] ?? ''),
                        'description' => trim($data['description'] ?? ''),
                        'items' => []
                    ];
                }

                $invoicesData[$invoiceNo]['items'][] = [
                    'item_code' => trim($data['item_code']),
                    'quantity' => trim($data['quantity']),
                    'unit_price' => trim($data['unit_price']),
                    'warehouse_name' => trim($data['warehouse_name'] ?? ''),
                    'serial_numbers' => trim($data['serial_numbers'] ?? ''), // Tangkap IMEI
                ];
            }
            fclose($fileHandle);

            foreach ($invoicesData as $invNo => $invData) {
                // Perbaikan format tanggal agar tidak error "Unexpected character"
                $rawDate = $invData['invoice_date'];
                $parsedDate = str_contains($rawDate, '/')
                    ? Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d')
                    : Carbon::parse($rawDate)->format('Y-m-d');

                $invoice = MigrationInvoice::updateOrCreate(
                    ['invoice_no' => $invNo],
                    [
                        'invoice_date' => $parsedDate,
                        'vendor_id' => $invData['vendor_id'],
                        'branch_name' => $invData['branch_name'],
                        'description' => $invData['description'],
                        'is_exported' => false,
                    ]
                );

                $invoice->items()->delete();

                foreach ($invData['items'] as $item) {
                    $invoice->items()->create([
                        'item_code' => $item['item_code'],
                        'quantity' => $item['quantity'],
                        'unit' => 'UNIT',
                        'unit_price' => $item['unit_price'],
                        'warehouse_name' => $item['warehouse_name'],
                        'serial_numbers' => $item['serial_numbers'], // Simpan IMEI
                    ]);
                }
            }

            DB::commit();
            $this->reset('file');
            session()->flash('success', 'Data CSV berhasil diimpor ke database draft!');
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
            session()->flash('error', 'Gagal memproses file. Error: ' . $e->getMessage());
        }
    }

    // 3. Fungsi Generate CSV Format Accurate
    public function exportCsv()
    {
        $invoices = MigrationInvoice::with('items')->where('is_exported', false)->get();

        if ($invoices->isEmpty()) {
            session()->flash('error', 'Tidak ada data faktur baru untuk diekspor.');
            return;
        }

        $fileName = 'accurate_purchase_invoice_' . now()->format('Ymd_His') . '.csv';

        $row1_header  = ["HEADER", "No Form", "No Faktur", "Tgl Faktur", "No Pemasok", "Alamat Faktur", "Kena PPN", "Total Termasuk PPN", "Nomor Faktur Pajak", "Tagihan Dimuka", "Diskon Faktur (%)", "Diskon Faktur (Rp)", "Keterangan", "Nama Cabang", "Pengiriman", "Tgl Pengiriman", "FOB", "Syarat Pembayaran", "Bank Pembayaran", "Nilai Pembayaran"];

        // Pemaksaan Kolom "Nomor Seri" di indeks ke-16
        $row2_item    = ["ITEM", "Kode Barang", "Nama Barang", "Kuantitas", "Satuan", "Harga Satuan", "Diskon Barang (%)", "Diskon Barang (Rp)", "Catatan Barang", "Nama Gudang", "Nama Dept Barang", "No Proyek Barang", "Kustom Karakter 1", "Kustom Karakter 2", "Kustom Karakter 3", "Kustom Karakter 4", "Nomor Seri"];

        $row3_expense = ["EXPENSE", "No Biaya", "Nama Biaya", "Nilai Biaya", "Catatan Biaya", "Nama Dept Biaya", "No Proyek Biaya"];

        $maxColumns = max(count($row1_header), count($row2_item), count($row3_expense));

        return response()->streamDownload(function () use ($invoices, $row1_header, $row2_item, $row3_expense, $maxColumns) {
            $file = fopen('php://output', 'w');

            fputcsv($file, array_pad($row1_header, $maxColumns, ''));
            fputcsv($file, array_pad($row2_item, $maxColumns, ''));
            fputcsv($file, array_pad($row3_expense, $maxColumns, ''));

            foreach ($invoices as $inv) {
                $headerRow = array_fill(0, count($row1_header), '');
                $headerRow[0]  = 'HEADER';
                $headerRow[2]  = $inv->invoice_no;
                $headerRow[3]  = Carbon::parse($inv->invoice_date)->format('d/m/Y');
                $headerRow[4]  = $inv->vendor_id;
                $headerRow[12] = $inv->description ?? '';
                $headerRow[13] = $inv->branch_name ?? '';

                fputcsv($file, array_pad($headerRow, $maxColumns, ''));

                foreach ($inv->items as $item) {
                    if (!empty(trim($item->serial_numbers))) {
                        // Bersihkan jika ada spasi atau titik koma berlebih di ujung teks
                        $cleanSn = trim($item->serial_numbers, " \t\n\r\0\x0B;");

                        // Pecah IMEI berdasarkan titik koma
                        $snArray = explode(';', $cleanSn);
                        $snCount = count($snArray);

                        // Pastikan jumlah IMEI harus SAMA PERSIS dengan Kuantitas
                        if ($snCount != $item->quantity) {
                            session()->flash('error', "GAGAL EXPORT! Pada Faktur {$inv->invoice_no}: Barang {$item->item_code} memiliki kuantitas {$item->quantity}, tetapi Anda memasukkan {$snCount} Nomor Seri. Periksa kembali Excel Anda.");
                            return; // Hentikan proses download
                        }

                        // Buat 1 baris per Serial Number dengan Kuantitas = 1
                        foreach ($snArray as $sn) {
                            $itemRow = array_fill(0, count($row2_item), '');
                            $itemRow[0] = 'ITEM';
                            $itemRow[1] = $item->item_code;
                            $itemRow[3] = 1; // Kuantitas dipecah jadi 1
                            $itemRow[4] = $item->unit;
                            $itemRow[5] = round($item->unit_price, 0);
                            $itemRow[9] = $item->warehouse_name ?? '';
                            $itemRow[16] = trim($sn); // Masukkan Serial Number

                            fputcsv($file, array_pad($itemRow, $maxColumns, ''));
                        }
                    } else {
                        // Jika tidak ada Serial Number, export utuh seperti biasa
                        $itemRow = array_fill(0, count($row2_item), '');
                        $itemRow[0] = 'ITEM';
                        $itemRow[1] = $item->item_code;
                        $itemRow[3] = $item->quantity;
                        $itemRow[4] = $item->unit;
                        $itemRow[5] = round($item->unit_price, 0);
                        $itemRow[9] = $item->warehouse_name ?? '';

                        fputcsv($file, array_pad($itemRow, $maxColumns, ''));
                    }
                }

                $inv->update(['is_exported' => true]);
            }
            fclose($file);
        }, $fileName);
    }

    public function pushToAccurateApi()
    {
        // Ambil maksimal 10 faktur per eksekusi
        $invoices = MigrationInvoice::with('items')
            ->where('is_exported', false)
            ->take(10)
            ->get();

        if ($invoices->isEmpty()) {
            session()->flash('error', 'Semua faktur sudah berhasil disinkronisasi ke Accurate.');
            return;
        }

        // Panggil service Accurate Anda
        $accurateService = app(\App\Services\AccurateService::class);

        $successCount = 0;
        $errorCount = 0;

        foreach ($invoices as $invoice) {
            $detailItemArray = [];

            foreach ($invoice->items as $item) {
                $itemData = [
                    'itemNo'        => $item->item_code,
                    'warehouseName' => $item->warehouse_name,
                    'unitPrice'     => (float) $item->unit_price,
                    'quantity'      => (float) $item->quantity,
                ];

                // Proses IMEI menjadi Array
                if (!empty(trim($item->serial_numbers))) {
                    $snList = explode(';', trim($item->serial_numbers));
                    $detailSerialNumber = [];

                    foreach ($snList as $sn) {
                        $cleanSn = trim($sn);
                        if (!empty($cleanSn)) {
                            $detailSerialNumber[] = ['serialNumberNo' => $cleanSn, 'quantity' => 1];
                        }
                    }

                    if (count($detailSerialNumber) > 0) {
                        $itemData['detailSerialNumber'] = $detailSerialNumber;
                    }
                }

                $detailItemArray[] = $itemData;
            }

            // Susun Payload Akhir
            $payload = [
                'transDate'  => $invoice->invoice_date->format('d/m/Y'),
                'billNumber' => $invoice->invoice_no,
                'vendorNo'   => $invoice->vendor_id,
                'branchName' => $invoice->branch_name,
                'detailItem' => $detailItemArray,
                'taxable' => false,
                'inclusiveTax' => false,
            ];

            try {
                // Tentukan Database Source (misal 'second', 'syihab', dll)
                // Jika unit usaha ini dinamis, Anda bisa mengambil nilainya dari mapping branch_name
                // atau menyimpan 'database_source' di tabel migration_invoices saat import.
                $databaseSource = Auth::user()->businessUnit->code;
                // Tembak API menggunakan Service
                $accurateService->savePurchaseInvoiceDo($payload, $databaseSource);

                // Jika tidak ada Exception, berarti sukses
                $invoice->update(['is_exported' => true]);
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Gagal push Faktur {$invoice->invoice_no}: " . $e->getMessage());
                $errorCount++;
            }
        }

        session()->flash('success', "Proses Selesai: {$successCount} Faktur berhasil dikirim, {$errorCount} gagal (cek file log laravel.log).");
    }
    #[Layout('layouts.admin')]
    public function render()
    {
        $draftInvoices = MigrationInvoice::withCount('items')
            ->where('is_exported', false)
            ->latest()
            ->paginate(10);
        return view('livewire.admin.accurate.accurate-invoice-export', [
            'draftInvoices' => $draftInvoices,
        ]);
    }
}
