<?php

namespace App\Jobs;

use App\Models\BusinessUnitProject;
use App\Models\MigrationInvoice;
use App\Models\ProductAccurate;
use App\Services\AccurateService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushInvoiceToAccurateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice;
    public $databaseSource;

    /**
     * Jumlah maksimal job ini akan diulang jika terjadi Exception (misal timeout).
     */
    public $tries = 3;

    /**
     * Maksimal waktu (detik) yang diperbolehkan sebelum job dimatikan paksa (timeout).
     */
    public $timeout = 120;

    /**
     * Waktu tunggu (detik) sebelum retry jika gagal.
     */
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(MigrationInvoice $invoice, string $databaseSource)
    {
        $this->invoice = $invoice;
        $this->databaseSource = $databaseSource;
    }

    /**
     * Execute the job.
     */
    public function handle(AccurateService $accurateService): void
    {
        // Skip jika sudah diexport
        if ($this->invoice->is_exported) {
            return;
        }

        $detailItemArray = [];

        // Ambil data produk terkait untuk memetakan nomor proyek (projectNo)
        $itemCodes = $this->invoice->items->pluck('item_code')->filter()->unique();
        $products = ProductAccurate::whereIn('item_no', $itemCodes)->get()->keyBy('item_no');

        foreach ($this->invoice->items as $item) {
            $itemData = [
                'itemNo'        => $item->item_code,
                'warehouseName' => $item->warehouse_name,
                'unitPrice'     => (float) $item->unit_price,
                'quantity'      => (float) $item->quantity,
            ];

            // Mapping Project No seperti pada Sales Invoice / POS
            $product = $products->get($item->item_code);
            if ($product) {
                $namaProyek = trim(strtoupper($product->proyek ?? ''));

                // 1. Cek dari mapping BusinessUnitProject jika dikonfigurasi per unit
                $projectNo = BusinessUnitProject::getProjectNoByBusinessUnit(
                    $product->business_unit_id,
                    $namaProyek,
                    null
                );

                // 2. Mapping standar sesuai POS / Sales Invoice
                if (empty($projectNo) && !empty($namaProyek)) {
                    $projectNo = match ($namaProyek) {
                        'SJU'   => 'P.00003',
                        'SAB'   => 'P.00004',
                        'RESMI' => 'P.00008',
                        default => $product->proyek ?? ''
                    };
                }

                if (!empty($projectNo)) {
                    $itemData['projectNo'] = $projectNo;
                }
            }

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
            'transDate'  => \Carbon\Carbon::parse($this->invoice->invoice_date)->format('d/m/Y'),
            'billNumber' => $this->invoice->invoice_no,
            'vendorNo'   => $this->invoice->vendor_id,
            'branchName' => $this->invoice->branch_name,
            'detailItem' => $detailItemArray,
            'taxable' => false,
            'inclusiveTax' => false,
        ];

        try {
            $accurateService->savePurchaseInvoiceDo($payload, $this->databaseSource);

            // Jika sukses
            $this->invoice->update([
                'is_exported' => true,
                'sync_error'  => null
            ]);
        } catch (Exception $e) {
            // Cek jika HTTP 400 (Bad Request) atau error validasi dari Accurate. 
            // Biasanya message akan mengandung text error JSON dari API.
            // Untuk error koneksi/timeout, message biasanya cURL error 28.
            $errorMsg = $e->getMessage();
            
            // Simpan error ke database
            $this->invoice->update([
                'sync_error' => substr($errorMsg, 0, 1000) // batas panjang text
            ]);

            // Melempar kembali exception agar Laravel Queue melakukan Retry (jika belum batas $tries)
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Fungsi ini otomatis dipanggil Laravel jika job gagal total 
        // (misalnya karena TimeoutExceededException atau melebih batas $tries)
        
        $errorMsg = $exception->getMessage();
        if (empty($errorMsg) && $exception instanceof \Illuminate\Queue\TimeoutExceededException) {
            $errorMsg = "Gagal memproses (Timeout): Server Accurate tidak merespons dalam " . $this->timeout . " detik.";
        }

        $this->invoice->update([
            'sync_error' => substr($errorMsg, 0, 1000)
        ]);
    }
}
