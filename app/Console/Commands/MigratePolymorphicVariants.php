<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePolymorphicVariants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:polymorphic-variants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old ProductVariant and SecondProductVariant polymorphic data to ProductAccurate';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai migrasi polymorphic data ke ProductAccurate...');

        // 1. ORDER ITEMS
        $this->info('Migrasi tabel order_items...');
        $orderItemsNew = DB::table('order_items')->where('product_variant_type', 'App\Models\ProductVariant')->get();
        $countOrderItemsNew = 0;
        foreach ($orderItemsNew as $item) {
            $variant = DB::table('product_variants')->where('id', $item->product_variant_id)->first();
            if ($variant && !empty($variant->product_accurate_id)) {
                DB::table('order_items')->where('id', $item->id)->update([
                    'product_variant_type' => 'App\Models\ProductAccurate',
                    'product_variant_id' => $variant->product_accurate_id
                ]);
                $countOrderItemsNew++;
            }
        }
        $this->line("- Migrasi $countOrderItemsNew order_items (ProductVariant) selesai.");

        $orderItemsSecond = DB::table('order_items')->where('product_variant_type', 'App\Models\SecondProductVariant')->get();
        $countOrderItemsSecond = 0;
        foreach ($orderItemsSecond as $item) {
            $variant = DB::table('second_product_variants')->where('id', $item->product_variant_id)->first();
            if ($variant && !empty($variant->product_accurate_id)) {
                DB::table('order_items')->where('id', $item->id)->update([
                    'product_variant_type' => 'App\Models\ProductAccurate',
                    'product_variant_id' => $variant->product_accurate_id
                ]);
                $countOrderItemsSecond++;
            }
        }
        $this->line("- Migrasi $countOrderItemsSecond order_items (SecondProductVariant) selesai.");

        // 2. WAREHOUSE STOCKS
        $this->info('Migrasi tabel warehouse_stocks...');
        $stocksNew = DB::table('warehouse_stocks')->where('variant_type', 'App\Models\ProductVariant')->get();
        $countStocksNew = 0;
        foreach ($stocksNew as $stock) {
            $variant = DB::table('product_variants')->where('id', $stock->variant_id)->first();
            if ($variant && !empty($variant->product_accurate_id)) {
                $existingAccurateStock = DB::table('warehouse_stocks')
                    ->where('warehouse_id', $stock->warehouse_id)
                    ->where('variant_id', $variant->product_accurate_id)
                    ->where('variant_type', 'App\Models\ProductAccurate')
                    ->first();

                if ($existingAccurateStock) {
                    // Hapus baris ProductVariant lama tanpa menjumlahkan stok
                    DB::table('warehouse_stocks')->where('id', $stock->id)->delete();
                } else {
                    // Update baris lama ke ProductAccurate
                    DB::table('warehouse_stocks')->where('id', $stock->id)->update([
                        'variant_type' => 'App\Models\ProductAccurate',
                        'variant_id' => $variant->product_accurate_id
                    ]);
                }
                $countStocksNew++;
            }
        }
        $this->line("- Migrasi $countStocksNew warehouse_stocks (ProductVariant) selesai.");

        $stocksSecond = DB::table('warehouse_stocks')->where('variant_type', 'App\Models\SecondProductVariant')->get();
        $countStocksSecond = 0;
        foreach ($stocksSecond as $stock) {
            $variant = DB::table('second_product_variants')->where('id', $stock->variant_id)->first();
            if ($variant && !empty($variant->product_accurate_id)) {
                $existingAccurateStock = DB::table('warehouse_stocks')
                    ->where('warehouse_id', $stock->warehouse_id)
                    ->where('variant_id', $variant->product_accurate_id)
                    ->where('variant_type', 'App\Models\ProductAccurate')
                    ->first();

                if ($existingAccurateStock) {
                    // Hapus baris SecondProductVariant lama tanpa menjumlahkan stok
                    DB::table('warehouse_stocks')->where('id', $stock->id)->delete();
                } else {
                    DB::table('warehouse_stocks')->where('id', $stock->id)->update([
                        'variant_type' => 'App\Models\ProductAccurate',
                        'variant_id' => $variant->product_accurate_id
                    ]);
                }
                $countStocksSecond++;
            }
        }
        $this->line("- Migrasi $countStocksSecond warehouse_stocks (SecondProductVariant) selesai.");

        // 3. TRADE INS
        $this->info('Migrasi tabel trade_ins...');
        $tradeInsNew = DB::table('trade_ins')->where('product_variant_type', 'App\Models\ProductVariant')->get();
        $countTradeInsNew = 0;
        foreach ($tradeInsNew as $trade) {
            $variant = DB::table('product_variants')->where('id', $trade->product_variant_id)->first();
            if ($variant && !empty($variant->product_accurate_id)) {
                DB::table('trade_ins')->where('id', $trade->id)->update([
                    'product_variant_type' => 'App\Models\ProductAccurate',
                    'product_variant_id' => $variant->product_accurate_id
                ]);
                $countTradeInsNew++;
            }
        }
        $this->line("- Migrasi $countTradeInsNew trade_ins (ProductVariant) selesai.");

        $tradeInsSecond = DB::table('trade_ins')->where('product_variant_type', 'App\Models\SecondProductVariant')->get();
        $countTradeInsSecond = 0;
        foreach ($tradeInsSecond as $trade) {
            $variant = DB::table('second_product_variants')->where('id', $trade->product_variant_id)->first();
            if ($variant && !empty($variant->product_accurate_id)) {
                DB::table('trade_ins')->where('id', $trade->id)->update([
                    'product_variant_type' => 'App\Models\ProductAccurate',
                    'product_variant_id' => $variant->product_accurate_id
                ]);
                $countTradeInsSecond++;
            }
        }
        $this->line("- Migrasi $countTradeInsSecond trade_ins (SecondProductVariant) selesai.");

        $this->info('Selesai! Semua data polymorphic berhasil dimigrasi ke ProductAccurate.');
    }
}
