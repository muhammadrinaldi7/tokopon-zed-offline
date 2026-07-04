<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateOrderSerialNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:order-sn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate comma-separated serial numbers from order_items into order_item_serial_numbers table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SN migration...');
        
        $orderItems = \App\Models\OrderItem::whereNotNull('serial_number')->where('serial_number', '!=', '')->get();
        $count = 0;

        foreach ($orderItems as $item) {
            // Split by comma and remove spaces
            $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
            
            foreach ($sns as $sn) {
                \App\Models\OrderItemSerialNumber::firstOrCreate([
                    'order_item_id' => $item->id,
                    'serial_number' => $sn
                ]);
                $count++;
            }
        }

        $this->info("Migration completed. Extracted {$count} serial numbers.");
    }
}
