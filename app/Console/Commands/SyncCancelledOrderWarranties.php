<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSerialNumber;
use App\Models\ProductSerialNumber;
use App\Models\Warranty;
use Illuminate\Support\Facades\DB;

class SyncCancelledOrderWarranties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warranty:sync-cancelled {--dry-run : Only show what would be updated without modifying data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data berjalan: mem-void seluruh garansi aktif dari transaksi yang telah berstatus CANCELLED, DRAFT, atau RETURNED, serta memulihkan status serial number ke Available';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("=== SINKRONISASI GARANSI TRANSAKSI BATAL ===");
        if ($isDryRun) {
            $this->warn("[DRY-RUN MODE: Data tidak akan diubah di database]");
        }

        // 1. Ambil seluruh order yang dibatalkan / draft / retur
        $invalidOrders = Order::whereIn('order_status', ['CANCELLED', 'DRAFT', 'RETURNED'])
            ->with(['items'])
            ->get();

        $this->info("Ditemukan {$invalidOrders->count()} order berstatus CANCELLED/DRAFT/RETURNED.");

        $allOrderItemIds = [];
        $allSns = [];

        foreach ($invalidOrders as $order) {
            foreach ($order->items as $item) {
                $allOrderItemIds[] = $item->id;
                if (!empty($item->serial_number)) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    $allSns = array_merge($allSns, $sns);
                }
            }
        }

        // Ambil juga dari OrderItemSerialNumber
        if (!empty($allOrderItemIds)) {
            $dbSns = OrderItemSerialNumber::whereIn('order_item_id', $allOrderItemIds)
                ->pluck('serial_number')
                ->toArray();
            $allSns = array_merge($allSns, $dbSns);
        }

        $allOrderItemIds = array_unique(array_filter($allOrderItemIds));
        $allSns = array_unique(array_filter($allSns));

        // 2. Cari kartu garansi aktif yang terikat pada item atau SN dari order-order tersebut
        $warrantiesToVoid = Warranty::where('status', 'active')
            ->where(function ($q) use ($allOrderItemIds, $allSns) {
                if (!empty($allOrderItemIds)) {
                    $q->whereIn('order_item_id', $allOrderItemIds);
                }
                if (!empty($allSns)) {
                    $q->orWhereIn('serial_number', $allSns);
                }
            })
            ->get();

        $this->info("Ditemukan {$warrantiesToVoid->count()} kartu garansi aktif yang berasal dari transaksi batal.");

        if ($warrantiesToVoid->isNotEmpty()) {
            foreach ($warrantiesToVoid as $w) {
                $this->line(" - Warranty #{$w->id} | SN: {$w->serial_number} | OrderItem #{$w->order_item_id}");
            }

            if (!$isDryRun) {
                $voidedCount = Warranty::whereIn('id', $warrantiesToVoid->pluck('id'))->update(['status' => 'voided']);
                $this->info("Berhasil mengubah {$voidedCount} garansi menjadi 'voided'.");
            }
        }

        // 3. Pulihkan status ProductSerialNumber ke 'Available'
        if (!empty($allSns)) {
            $snsToRestore = ProductSerialNumber::whereIn('serial_number', $allSns)
                ->where('status', '!=', 'Available')
                ->get();

            $this->info("Ditemukan {$snsToRestore->count()} serial number yang masih Unavailable.");

            if ($snsToRestore->isNotEmpty()) {
                foreach ($snsToRestore as $sn) {
                    $this->line(" - Restore SN: {$sn->serial_number} (sebelumnya {$sn->status} -> Available)");
                }

                if (!$isDryRun) {
                    $restoredCount = ProductSerialNumber::whereIn('id', $snsToRestore->pluck('id'))
                        ->update(['status' => 'Available']);
                    $this->info("Berhasil mengembalikan {$restoredCount} serial number menjadi 'Available'.");
                }
            }
        }

        $this->info("=== SINKRONISASI SELESAI ===");
        return Command::SUCCESS;
    }
}
