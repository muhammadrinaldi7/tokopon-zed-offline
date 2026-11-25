<?php

namespace App\Jobs;

use App\Services\OrderAccurateSyncService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOrderToAccurateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 15;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Delegasikan penerbitan Sales Invoice (SI) & Sales Receipt (SR) ke OrderAccurateSyncService
     * agar otomatis melampirkan `salesOrderNumber` (jika ada SO), `detailSerialNumber` (IMEI),
     * potongan diskon kupon/loyalty, MDR, dan pencatatan log sinkronisasi lengkap.
     */
    public function handle(OrderAccurateSyncService $syncService): void
    {
        $result = $syncService->syncOrder($this->orderId, 'MOBILE_FULFILLMENT');

        if (empty($result['success'])) {
            $err = $result['error'] ?? 'Gagal sinkronisasi Sales Invoice ke Accurate.';
            Log::channel('pos_accurate')->error("SyncOrderToAccurateJob gagal untuk Order ID #{$this->orderId}: {$err}");
            throw new Exception($err);
        }

        Log::channel('pos_accurate')->info(
            "SyncOrderToAccurateJob SUKSES untuk Order ID #{$this->orderId} (SI: " . ($result['invoice_no'] ?? '-') . ", SR: " . ($result['receipt_no'] ?? '-') . ")"
        );
    }
}
