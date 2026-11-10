<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderAccurateSyncService;
use App\Services\SettingService;
use Illuminate\Console\Command;

class RetryPendingSalesAccurate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accurate:retry-pending-sales 
                            {--limit=25 : Batas maksimal transaksi yang diproses per batch} 
                            {--order= : ID pesanan tertentu jika hanya ingin retry 1 order}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatisasi retry transaksi penjualan POS yang tertunda (pending/gagal) ke Accurate';

    /**
     * Execute the console command.
     */
    public function handle(OrderAccurateSyncService $syncService, SettingService $settingService): int
    {
        $specificOrderId = $this->option('order');
        $limit = (int) $this->option('limit') ?: 25;

        $settingService->set('accurate_sales_retry_last_run_at', now()->toDateTimeString());

        // Self-healing: tandai SYNCED untuk transaksi dari kode lama yang sudah memiliki nomor faktur & kwitansi Accurate
        // namun belum memiliki accurate_sync_status (atau berstatus PENDING tanpa riwayat gagal di log)
        Order::where('order_status', 'COMPLETED')
            ->where(function ($q) {
                $q->whereNull('accurate_sync_status')
                  ->orWhere(function ($sub) {
                      $sub->where('accurate_sync_status', 'PENDING')
                          ->whereDoesntHave('accurateSyncLogs');
                  });
            })
            ->whereNotNull('accurate_invoice_no')
            ->where('accurate_invoice_no', '!=', '')
            ->whereNotNull('accurate_receipt_no')
            ->where('accurate_receipt_no', '!=', '')
            ->update([
                'accurate_sync_status' => 'SYNCED',
                'accurate_synced_at' => now(),
            ]);

        if ($specificOrderId) {
            $orders = Order::where('id', $specificOrderId)->get();
        } else {
            $orders = Order::where('order_status', 'COMPLETED')
                ->where(function ($q) {
                    $q->where('accurate_sync_status', 'PENDING')
                      ->orWhere(function ($sub) {
                          $sub->whereNull('accurate_sync_status')
                              ->where(function ($inv) {
                                  $inv->whereNull('accurate_invoice_no')
                                      ->orWhere('accurate_invoice_no', '');
                              });
                      });
                })
                ->where('created_at', '>=', now()->subDays(7))
                ->where('accurate_sync_attempts', '<', 5)
                ->orderBy('id', 'asc')
                ->limit($limit)
                ->get();
        }

        if ($orders->isEmpty()) {
            $this->info('Tidak ada transaksi penjualan yang pending sinkronisasi Accurate.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$orders->count()} transaksi pending. Memulai proses sinkronisasi...");

        $successCount = 0;
        $failCount = 0;
        $tableRows = [];

        foreach ($orders as $order) {
            $this->line("Memproses Order #{$order->order_number}...");
            $result = $syncService->syncOrder($order, 'AUTO_RETRY_SCHEDULE');

            if ($result['success']) {
                $successCount++;
                $tableRows[] = [
                    $order->id,
                    $order->order_number,
                    $order->branch->name ?? '-',
                    'SUKSES',
                    $result['invoice_no'] ?? '-',
                    '-',
                ];
            } else {
                $failCount++;
                $tableRows[] = [
                    $order->id,
                    $order->order_number,
                    $order->branch->name ?? '-',
                    'GAGAL',
                    '-',
                    \Illuminate\Support\Str::limit($result['error'] ?? 'Gagal', 50),
                ];
            }
        }

        $this->table(['ID', 'Nomor Order', 'Cabang', 'Status', 'No Invoice', 'Penyebab Gagal'], $tableRows);
        $this->info("Selesai. Sukses: {$successCount}, Gagal: {$failCount}.");

        return self::SUCCESS;
    }
}

