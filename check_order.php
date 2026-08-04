<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$o = App\Models\Order::with(['items.promos'])->latest()->first();

echo "=== ORDER " . $o->order_number . " ===" . PHP_EOL;
echo "Total Amount: " . $o->total_amount . PHP_EOL;
echo "Total Discount: " . $o->discount_amount . PHP_EOL;
foreach ($o->items as $item) {
    echo "Item ID: " . $item->id . " | Name: " . $item->product_name . " | Price: " . $item->price_at_checkout . " | Qty: " . $item->qty . " | SNs: " . $item->serial_number . PHP_EOL;
    foreach ($item->promos as $promo) {
        echo "   -> Promo ID: " . $promo->id . " | Pivot Discount Amount: " . $promo->pivot->discount_amount . " | Pivot SN: " . $promo->pivot->serial_number . PHP_EOL;
    }
}
