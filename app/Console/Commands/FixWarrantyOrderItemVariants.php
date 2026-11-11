<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWarrantyOrderItemVariants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warranty:fix-replacement-variants {--dry-run : Simulasi pengecekan tanpa mengubah database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memeriksa dan memperbaiki product_variant_id & product_variant_type pada order_items faktur hasil ganti unit garansi yang masih mengarah ke produk lama';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('[DRY-RUN MODE] Tidak ada perubahan yang akan disimpan ke database.');
        }

        $this->info('Memindai faktur penggantian garansi (WR)...');

        // Cari order hasil penggantian unit garansi
        $orders = Order::where(function ($q) {
            $q->where('order_number', 'like', 'WR-%')
              ->orWhere('notes', 'like', '%Ganti Unit Klaim Garansi%')
              ->orWhere('notes', 'like', '%Pengganti untuk IMEI%');
        })->with(['items'])->get();

        $this->line("Ditemukan {$orders->count()} faktur penggantian garansi.");
        $fixedCount = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $correctAccurate = null;

                // 1. Deteksi dari nomor seri fisik
                if (!empty($item->serial_number)) {
                    $cleanSn = trim(explode(',', $item->serial_number)[0]);
                    $psn = ProductSerialNumber::where('serial_number', $cleanSn)->first();
                    if ($psn) {
                        if ($psn->product_accurate_id) {
                            $correctAccurate = ProductAccurate::find($psn->product_accurate_id);
                        } elseif ($psn->item_no) {
                            $correctAccurate = ProductAccurate::where('item_no', $psn->item_no)
                                ->when($order->business_unit_id, function ($q) use ($order) {
                                    $q->where('business_unit_id', $order->business_unit_id);
                                })->first() ?? ProductAccurate::where('item_no', $psn->item_no)->first();
                        }
                    }
                }

                // 2. Deteksi dari nama produk jika belum ketemu dari serial number
                if (!$correctAccurate && !empty($item->product_name)) {
                    $correctAccurate = ProductAccurate::where('name', $item->product_name)
                        ->when($order->business_unit_id, function ($q) use ($order) {
                            $q->where('business_unit_id', $order->business_unit_id);
                        })->first() ?? ProductAccurate::where('name', $item->product_name)->first();
                }

                if ($correctAccurate) {
                    $isMismatch = ($item->product_variant_type !== ProductAccurate::class && $item->product_variant_type !== 'App\Models\ProductAccurate')
                        || ((int) $item->product_variant_id !== (int) $correctAccurate->id);

                    if ($isMismatch) {
                        $this->warn("Mismatch ditemukan pada Order {$order->order_number} (Item ID: {$item->id}):");
                        $this->line(" - Nama Produk     : {$item->product_name}");
                        $this->line(" - Serial Number   : {$item->serial_number}");
                        $this->line(" - Variant ID Lama : {$item->product_variant_id} ({$item->product_variant_type})");
                        $this->info(" -> Variant ID Baru: {$correctAccurate->id} (" . ProductAccurate::class . " - SKU: {$correctAccurate->item_no})");

                        if (!$isDryRun) {
                            $item->update([
                                'product_variant_type' => ProductAccurate::class,
                                'product_variant_id'   => $correctAccurate->id,
                            ]);
                            $this->info("   [OK] Berhasil diperbaiki!");
                        }
                        $fixedCount++;
                    }
                }
            }
        }

        if ($fixedCount === 0) {
            $this->info('Semua faktur penggantian garansi sudah valid dan sinkron.');
        } else {
            $status = $isDryRun ? 'terdeteksi perlu diperbaiki' : 'berhasil diperbaiki';
            $this->info("Selesai! Sebanyak {$fixedCount} item {$status}.");
        }

        return 0;
    }
}

