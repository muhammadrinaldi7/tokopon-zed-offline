<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$item = \App\Models\OrderItem::where('serial_number', '23456')->first();
if(!$item) {
    echo "Item not found";
    exit;
}
$order = $item->order;
foreach($order->items as $i) {
    echo "ID: {$i->id}, Name: {$i->product_name}, VarType: {$i->product_variant_type}, VarID: {$i->product_variant_id}\n";
    if ($i->variant) {
        $accId = null;
        if (method_exists($i->variant, 'accurateData') && $i->variant->accurateData) {
            $accId = $i->variant->accurateData->id;
        } else {
            $accId = $i->variant->product_accurate_id ?? 'null';
        }
        echo "   AccurateID: " . $accId . "\n";
    } else {
        echo "   No Variant relation loaded or null.\n";
    }
}
