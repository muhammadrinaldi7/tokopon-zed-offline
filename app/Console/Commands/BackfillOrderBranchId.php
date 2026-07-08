<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Branch;

class BackfillOrderBranchId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-order-branch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengisi kolom branch_id pada transaksi (orders) lama yang masih null dengan menggunakan data snapshot alamat/toko.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses backfill branch_id...');
        
        $orders = Order::whereNull('branch_id')->get();
        $totalOrders = $orders->count();

        if ($totalOrders === 0) {
            $this->info('Tidak ada order yang branch_id-nya null. Proses selesai.');
            return;
        }

        $this->info("Ditemukan {$totalOrders} order yang membutuhkan branch_id.");
        $bar = $this->output->createProgressBar($totalOrders);
        $bar->start();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($orders as $order) {
            $snapshot = is_string($order->shipping_address_snapshot) 
                        ? json_decode($order->shipping_address_snapshot, true) 
                        : $order->shipping_address_snapshot;

            if ($snapshot && isset($snapshot['store'])) {
                $storeName = $snapshot['store'];

                $branch = Branch::where('name', 'LIKE', '%' . $storeName . '%')
                                ->where('business_unit_id', $order->business_unit_id)
                                ->first();

                if ($branch) {
                    $order->update(['branch_id' => $branch->id]);
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }
            } else {
                // Skip jika tidak ada informasi store di snapshot
                $skippedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("Proses Selesai!");
        $this->info("Berhasil diupdate : {$updatedCount} order.");
        $this->info("Dilewati (Skip)   : {$skippedCount} order.");
    }
}
