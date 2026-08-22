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

    // 1b. Export Master Product
    public function downloadMasterProduct()
    {
        $fileName = 'master_produk_accurate.csv';
        $columns = ['item_no', 'name', 'categoryName', 'brandName'];

        return response()->streamDownload(function () use ($columns) {
            $file = fopen('php://output', 'w');
            // Tambahkan BOM agar bisa dibaca Excel dengan baik (UTF-8)
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            
            \App\Models\ProductAccurate::chunk(500, function ($products) use ($file) {
                foreach ($products as $product) {
                    fputcsv($file, [
                        $product->item_no,
                        $product->name,
                        $product->categoryName ?? '-',
                        $product->brandName ?? '-'
                    ]);
                }
            });
            fclose($file);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    // 1c. Export Master Vendor
    public function downloadMasterVendor()
    {
        $fileName = 'master_vendor_accurate.csv';
        $columns = ['vendor_no', 'vendor_name', 'email', 'phone'];

        return response()->streamDownload(function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            
            \App\Models\Vendor::chunk(500, function ($vendors) use ($file) {
                foreach ($vendors as $vendor) {
                    fputcsv($file, [
                        $vendor->vendor_no,
                        $vendor->vendor_name,
                        $vendor->email ?? '-',
                        $vendor->phone ?? '-'
                    ]);
                }
            });
            fclose($file);
        }, $fileName, ['Content-Type' => 'text/csv']);
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
            session()->flash('success', 'Semua data draft berhasil dikosongkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal mengosongkan draft: ' . $e->getMessage());
        }
    }

    // 2. Fungsi Import CSV dari User
    public function importData()
    {
        $this->validate([
            'file' => 'required|mimes:csv,txt|max:10240',
        ]);

        $filePath = $this->file->getRealPath();
        $fileHandle = fopen($filePath, 'r');
        
        // Baca BOM dan hapus jika ada agar header pertama tidak error
        $bom = fread($fileHandle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fileHandle);
        }
        
        $header = fgetcsv($fileHandle);

        DB::beginTransaction();
        try {
            $invoicesData = [];
            $errors = [];
            $rowNumber = 1;
            
            // Untuk bulk validation
            $itemCodesToCheck = [];
            $vendorIdsToCheck = [];

            while ($row = fgetcsv($fileHandle)) {
                $rowNumber++;
                // Skip baris kosong
                if (empty(array_filter($row))) continue;
                
                $data = array_combine($header, $row);
                $invoiceNo = trim($data['invoice_no'] ?? '');
                
                if (empty($invoiceNo)) {
                    $errors[] = "Baris $rowNumber: Kolom invoice_no tidak boleh kosong.";
                    continue;
                }

                $itemCode = trim($data['item_code'] ?? '');
                $vendorId = trim($data['vendor_id'] ?? '');
                $qty = (int) trim($data['quantity'] ?? '1');
                $snRaw = trim($data['serial_numbers'] ?? '');

                $itemCodesToCheck[] = $itemCode;
                $vendorIdsToCheck[] = $vendorId;

                // Validasi Kuantitas vs SN
                if (!empty($snRaw)) {
                    $cleanSn = trim($snRaw, " \t\n\r\0\x0B;");
                    $snArray = array_filter(explode(';', $cleanSn), 'strlen');
                    $snCount = count($snArray);
                    if ($snCount != $qty) {
                        $errors[] = "Baris $rowNumber: Barang '$itemCode' Kuantitas $qty tapi terdapat $snCount Serial Number (IMEI).";
                    }
                }

                if (!isset($invoicesData[$invoiceNo])) {
                    $invoicesData[$invoiceNo] = [
                        'invoice_date' => trim($data['invoice_date'] ?? ''),
                        'vendor_id' => $vendorId,
                        'branch_name' => trim($data['branch_name'] ?? ''),
                        'description' => trim($data['description'] ?? ''),
                        'items' => []
                    ];
                }

                $invoicesData[$invoiceNo]['items'][] = [
                    'item_code' => $itemCode,
                    'quantity' => $qty,
                    'unit_price' => trim($data['unit_price'] ?? '0'),
                    'warehouse_name' => trim($data['warehouse_name'] ?? ''),
                    'serial_numbers' => $snRaw, 
                ];
            }
            fclose($fileHandle);
            
            // Bulk Validation Database (Lebih cepat daripada query per baris)
            $validItemCodes = \App\Models\ProductAccurate::whereIn('item_no', array_unique($itemCodesToCheck))->pluck('item_no')->toArray();
            $validVendorIds = \App\Models\Vendor::whereIn('vendor_no', array_unique($vendorIdsToCheck))->pluck('vendor_no')->toArray();
            
            $invalidItemCodes = array_diff(array_unique($itemCodesToCheck), $validItemCodes);
            if (!empty($invalidItemCodes)) {
                foreach($invalidItemCodes as $invalidCode) {
                    $errors[] = "Kode Barang '$invalidCode' tidak ditemukan di master data (product_accurates).";
                }
            }
            
            $invalidVendorIds = array_diff(array_unique($vendorIdsToCheck), $validVendorIds);
            if (!empty($invalidVendorIds)) {
                foreach($invalidVendorIds as $invalidVendor) {
                    $errors[] = "No Pemasok '$invalidVendor' tidak ditemukan di master data (vendors).";
                }
            }

            // Jika ada error, batalkan proses dan tampilkan semua pesan
            if (count($errors) > 0) {
                DB::rollBack();
                session()->flash('error', implode('<br>', $errors));
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
                        'unit' => 'UNIT',
                        'unit_price' => $item['unit_price'],
                        'warehouse_name' => $item['warehouse_name'],
                        'serial_numbers' => $item['serial_numbers'], // Simpan IMEI
                    ]);
                }
            }

            DB::commit();
            $this->reset('file');
            session()->flash('success', 'Data CSV berhasil divalidasi dan diimpor ke database draft!');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($fileHandle) && is_resource($fileHandle)) {
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
