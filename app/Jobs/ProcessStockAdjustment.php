<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Storage;
use App\Services\AccurateService; // <── Panggil Service-mu di sini

class ProcessStockAdjustment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    public $timeout = 900;
    public $tries = 3;
    public $backoff = 5;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        if (!Storage::exists($this->filePath)) {
            Log::error("Job Gagal: File tidak ditemukan di storage: " . $this->filePath);
            return;
        }

        $fullPath = Storage::path($this->filePath);

        // 1. Baca CSV baris demi baris
        $rows = LazyCollection::make(function () use ($fullPath) {
            $handle = fopen($fullPath, 'r');
            $headers = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                yield array_combine($headers, $data);
            }
            fclose($handle);
        });

        $documents = [];

        // 2. Proses Grouping Dua Tingkat (Header & Detail Item)
        foreach ($rows as $row) {
            $dateNow = now()->format('d/m/Y');
            $headerKey = $dateNow . '_' . $row['AKUN_ADJUSTMENT'] . '_' . $row['CABANG'];

            if (!isset($documents[$headerKey])) {
                $documents[$headerKey] = [
                    "adjustmentAccountNo" => $row['AKUN_ADJUSTMENT'],
                    "transDate"           => $dateNow,
                    "description"         => "Sinkronisasi otomatis CSV - Cabang " . $row['CABANG'],
                    "branchName"          => $row['CABANG'],
                    "detailItem"          => []
                ];
            }

            $itemKey = $row['ITEM_NO_ACCURATE'] . '_' . $row['GUDANG'];

            if (!isset($documents[$headerKey]['detailItem'][$itemKey])) {
                $documents[$headerKey]['detailItem'][$itemKey] = [
                    "itemNo"             => $row['ITEM_NO_ACCURATE'],
                    "itemAdjustmentType" => "ADJUSTMENT_IN",
                    "unitCost"           => (int) $row['UNIT_COST'],
                    "quantity"           => 0,
                    "warehouseName"      => $row['GUDANG'],
                    "detailSerialNumber" => []
                ];
            }

            $documents[$headerKey]['detailItem'][$itemKey]['quantity'] += 1;
            $documents[$headerKey]['detailItem'][$itemKey]['detailSerialNumber'][] = [
                "serialNumberNo" => $row['SERIAL_NUMBER'],
                "quantity"       => 1
            ];
        }

        // 3. Normalisasi indeks agar berurutan (0, 1, 2...)
        $finalDocuments = [];
        foreach ($documents as $doc) {
            $doc['detailItem'] = array_values($doc['detailItem']);
            $finalDocuments[] = $doc;
        }

        // 4. Chunking: Potong kelompok dokumen maksimal 100 data per request
        $documentChunks = array_chunk($finalDocuments, 100);

        // 5. Inisialisasi Accurate Service
        $accurateService = new AccurateService();

        // 6. Looping Kirim Menggunakan Service yang Sudah Rapih
        foreach ($documentChunks as $index => $chunkData) {
            try {
                // Jauh lebih simpel, tinggal panggil fungsi service!
                $accurateService->bulkSaveItemAdjustment($chunkData);

                Log::info("Queue Stock Adjustment Sukses pada Batch ke-" . ($index + 1));
            } catch (\Exception $e) {
                Log::error("Queue Stock Adjustment Gagal pada Batch ke-" . ($index + 1) . ". Detail: " . $e->getMessage());

                // Lempar ulang exception agar Laravel Queue tahu job ini gagal dan memicu mekanisme retry/backoff
                throw $e;
            }
        }

        // 7. Hapus file CSV temporer setelah loop selesai bersih keseluruhan
        Storage::delete($this->filePath);
    }
}
