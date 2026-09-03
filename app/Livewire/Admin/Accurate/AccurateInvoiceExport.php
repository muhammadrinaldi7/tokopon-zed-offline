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
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class AccurateInvoiceExport extends Component
{
    use WithFileUploads;

    public $file;
    public $messageSuccess = null;
    public $messageError = null;

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

        $export = new class($exampleRow1, $exampleRow2, $columns) implements FromArray, WithHeadings {
            private $row1, $row2, $headers;
            public function __construct($row1, $row2, $headers)
            {
                $this->row1 = $row1;
                $this->row2 = $row2;
                $this->headers = $headers;
            }
            public function array(): array
            {
                return [$this->row1, $this->row2];
            }
            public function headings(): array
            {
                return $this->headers;
            }
        };

        $fileName = 'template_import_internal.xlsx';
        return Excel::download($export, $fileName);
    }

    // 1b. Export Master Product
    public function downloadMasterProduct()
    {
        $fileName = 'master_produk_accurate.xlsx';
        $columns = ['item_no', 'name', 'categoryName', 'brandName'];

        $export = new class($columns) implements FromArray, WithHeadings {
            private $headers;
            public function __construct($headers)
            {
                $this->headers = $headers;
            }
            public function array(): array
            {
                $data = [];
                \App\Models\ProductAccurate::chunk(500, function ($products) use (&$data) {
                    foreach ($products as $product) {
                        $data[] = [
                            $product->item_no,
                            $product->name,
                            $product->categoryName ?? '-',
                            $product->brandName ?? '-'
                        ];
                    }
                });
                return $data;
            }
            public function headings(): array
            {
                return $this->headers;
            }
        };

        return Excel::download($export, $fileName);
    }

    // 1c. Export Master Vendor
    public function downloadMasterVendor()
    {
        $fileName = 'master_vendor_accurate.xlsx';
        $columns = ['vendor_no', 'vendor_name', 'email', 'phone'];

        $export = new class($columns) implements FromArray, WithHeadings {
            private $headers;
            public function __construct($headers)
            {
                $this->headers = $headers;
            }
            public function array(): array
            {
                $data = [];
                \App\Models\Vendor::chunk(500, function ($vendors) use (&$data) {
                    foreach ($vendors as $vendor) {
                        $data[] = [
                            $vendor->vendor_no,
                            $vendor->vendor_name,
                            $vendor->email ?? '-',
                            $vendor->phone ?? '-'
                        ];
                    }
                });
                return $data;
            }
            public function headings(): array
            {
                return $this->headers;
            }
        };

        return Excel::download($export, $fileName);
    }

    // 1d. Clear Drafts
    public function clearDrafts()
    {
        try {
            DB::beginTransaction();
            $invoices = MigrationInvoice::where('is_exported', false)->get();
            foreach ($invoices as $inv) {
                $inv->items()->delete();
                $inv->delete();
            }
            DB::commit();
            $this->messageSuccess = 'Semua data draft berhasil dikosongkan.';
            $this->messageError = null;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->messageError = 'Gagal mengosongkan draft: ' . $e->getMessage();
            $this->messageSuccess = null;
        }
    }

    // 2. Fungsi Import CSV dari User
    public function importData($directSync = false)
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $filePath = $this->file->getRealPath();

        DB::beginTransaction();
        try {
            // Gunakan StringValueBinder agar semua cell dibaca sebagai string murni
            // Hal ini mencegah Excel menghilangkan angka 0 di depan kode barang (misal: 00123 jadi 123)
            $importConfig = new class extends StringValueBinder implements WithCustomValueBinder {};
            
            // Excel::toArray mengembalikan array dari sheet. Kita ambil sheet pertama (index 0).
            $sheets = Excel::toArray($importConfig, $filePath);
            if (empty($sheets) || empty($sheets[0])) {
                throw new \Exception("File kosong atau format tidak didukung.");
            }

            $rows = $sheets[0];
            $header = array_shift($rows); // Ambil baris pertama sebagai header

            // Format header agar lowercase dan menghilangkan spasi berlebih
            $header = array_map(function ($val) {
                return strtolower(trim((string)$val));
            }, $header);

            $invoicesData = [];
            $errors = [];
            $rowNumber = 1;

            // Untuk bulk validation
            $itemCodesToCheck = [];
            $vendorIdsToCheck = [];

            foreach ($rows as $row) {
                $rowNumber++;
                // Skip baris kosong
                if (empty(array_filter($row))) continue;

                // Pastikan jumlah elemen row sama dengan header untuk fungsi array_combine
                $paddedRow = array_pad($row, count($header), null);
                // Batasi array row maksimal sebesar header jika kepanjangan
                $paddedRow = array_slice($paddedRow, 0, count($header));

                $data = array_combine($header, $paddedRow);

                // Cek kemungkinan perbedaan key header jika ada spasi, 
                // tapi karena template kita baku, kita anggap sesuai nama kolom database
                $invoiceNo = trim((string)($data['invoice_no'] ?? ''));

                if (empty($invoiceNo)) {
                    $errors[] = "Baris $rowNumber: Kolom invoice_no tidak boleh kosong.";
                    continue;
                }

                $itemCode = trim((string)($data['item_code'] ?? ''));
                $vendorId = trim((string)($data['vendor_id'] ?? ''));
                $qty = (int) trim((string)($data['quantity'] ?? '1'));
                $snRaw = trim((string)($data['serial_numbers'] ?? ''));

                $itemCodesToCheck[] = $itemCode;
                $vendorIdsToCheck[] = $vendorId;

                // Bersihkan SN dan dukung pemisah koma (,) maupun titik koma (;)
                $snArray = [];
                if (!empty($snRaw)) {
                    // Ganti koma dengan titik koma agar seragam
                    $cleanSn = str_replace(',', ';', $snRaw);
                    $cleanSn = trim($cleanSn, " \t\n\r\0\x0B;");
                    // Pecah dan bersihkan spasi
                    $snArray = array_map('trim', explode(';', $cleanSn));
                    $snArray = array_filter($snArray, 'strlen'); // Buang yang kosong
                    
                    $snCount = count($snArray);
                    if ($snCount != $qty) {
                        $errors[] = "Baris $rowNumber: Barang '$itemCode' Kuantitas $qty tapi terdapat $snCount Serial Number (IMEI).";
                    }
                }

                if (!isset($invoicesData[$invoiceNo])) {
                    // Excel menyimpan tanggal dalam format aneh jika tipe cell Date. 
                    // Jika data excel berupa teks, akan aman. Jika number, parse excel date.
                    $rawDate = (string)($data['invoice_date'] ?? '');
                    if (is_numeric($rawDate)) {
                        $rawDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('d/m/Y');
                    }

                    $invoicesData[$invoiceNo] = [
                        'invoice_date' => trim($rawDate),
                        'vendor_id' => $vendorId,
                        'branch_name' => trim((string)($data['branch_name'] ?? '')),
                        'description' => trim((string)($data['description'] ?? '')),
                        'items' => []
                    ];
                }

                $unitPrice = trim((string)($data['unit_price'] ?? '0'));
                $warehouseName = trim((string)($data['warehouse_name'] ?? ''));
                
                // Smart Grouping: Kelompokkan berdasarkan item_code, unit_price, dan warehouse
                $itemKey = $itemCode . '|' . $unitPrice . '|' . $warehouseName;

                if (!isset($invoicesData[$invoiceNo]['items'][$itemKey])) {
                    $invoicesData[$invoiceNo]['items'][$itemKey] = [
                        'item_code' => $itemCode,
                        'quantity' => 0,
                        'unit_price' => $unitPrice,
                        'warehouse_name' => $warehouseName,
                        'serial_numbers' => [],
                    ];
                }

                // Tambahkan Qty dan gabungkan SN
                $invoicesData[$invoiceNo]['items'][$itemKey]['quantity'] += $qty;
                if (!empty($snArray)) {
                    // Merge array SN baru ke dalam array yang sudah ada
                    $invoicesData[$invoiceNo]['items'][$itemKey]['serial_numbers'] = array_merge(
                        $invoicesData[$invoiceNo]['items'][$itemKey]['serial_numbers'], 
                        $snArray
                    );
                }
            }

            // Bulk Validation Database (Lebih cepat daripada query per baris)
            $validItemCodes = \App\Models\ProductAccurate::whereIn('item_no', array_unique($itemCodesToCheck))->pluck('item_no')->toArray();
            $validVendorIds = \App\Models\Vendor::whereIn('vendor_no', array_unique($vendorIdsToCheck))->pluck('vendor_no')->toArray();

            $invalidItemCodes = array_diff(array_unique($itemCodesToCheck), $validItemCodes);
            if (!empty($invalidItemCodes)) {
                foreach ($invalidItemCodes as $invalidCode) {
                    $errors[] = "Kode Barang '$invalidCode' tidak ditemukan di master data (product_accurates).";
                }
            }

            $invalidVendorIds = array_diff(array_unique($vendorIdsToCheck), $validVendorIds);
            if (!empty($invalidVendorIds)) {
                foreach ($invalidVendorIds as $invalidVendor) {
                    $errors[] = "No Pemasok '$invalidVendor' tidak ditemukan di master data (vendors).";
                }
            }

            // Jika ada error, batalkan proses dan tampilkan semua pesan
            if (count($errors) > 0) {
                DB::rollBack();
                $this->messageError = implode('<br>', $errors);
                $this->messageSuccess = null;
                return;
            }

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
                        'unit' => '',
                        'unit_price' => $item['unit_price'],
                        'warehouse_name' => $item['warehouse_name'],
                        'serial_numbers' => implode(';', $item['serial_numbers']), // Gabungkan IMEI yang sudah di-group
                    ]);
                }
            }

            DB::commit();
            $this->reset('file');
            $this->messageSuccess = 'Data Excel berhasil divalidasi dan diimpor ke database draft!';
            $this->messageError = null;

            if ($directSync) {
                $this->pushToAccurateApi();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->messageError = 'Gagal memproses file. Error: ' . $e->getMessage();
            $this->messageSuccess = null;
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
                $headerRow[6]  = 'Tidak'; // Kena PPN
                $headerRow[12] = $inv->description ?? '';
                $headerRow[13] = $inv->branch_name ?? '';

                fputcsv($file, array_pad($headerRow, $maxColumns, ''));

                foreach ($inv->items as $item) {
                    $itemRow = array_fill(0, count($row2_item), '');
                    $itemRow[0] = 'ITEM';
                    $itemRow[1] = $item->item_code;
                    $itemRow[3] = $item->quantity; // Gunakan kuantitas asli hasil grouping
                    $itemRow[4] = $item->unit ?? ''; // Kosongkan agar Accurate memakai default
                    $itemRow[5] = round($item->unit_price, 0);
                    $itemRow[9] = $item->warehouse_name ?? '';

                    if (!empty(trim($item->serial_numbers))) {
                        $itemRow[16] = trim($item->serial_numbers, " \t\n\r\0\x0B;"); // Masukkan gabungan SN utuh
                    }

                    fputcsv($file, array_pad($itemRow, $maxColumns, ''));
                }

                $inv->update(['is_exported' => true]);
            }
            fclose($file);
        }, $fileName);
    }

    public function pushToAccurateApi()
    {
        $invoices = MigrationInvoice::where('is_exported', false)->get();

        if ($invoices->isEmpty()) {
            $this->messageError = 'Semua faktur sudah berhasil disinkronisasi ke Accurate.';
            $this->messageSuccess = null;
            return;
        }

        $databaseSource = Auth::user()->businessUnit->code;
        $jobCount = 0;

        foreach ($invoices as $invoice) {
            \App\Jobs\PushInvoiceToAccurateJob::dispatch($invoice, $databaseSource);
            $jobCount++;
        }

        $this->messageSuccess = "Memulai sinkronisasi! $jobCount faktur sedang diproses di latar belakang (Background Job).";
        $this->messageError = null;
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
