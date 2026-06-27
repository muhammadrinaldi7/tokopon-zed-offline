<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- POLICIES ---\n";
$policies = \App\Models\WarrantyPolicy::all();
foreach($policies as $p) {
    echo "ID: {$p->id}, Type: {$p->type}, Keywords: {$p->addon_trigger_keywords}, IsActive: {$p->is_active}\n";
}

echo "\n--- ORDER ITEMS FOR 23456 ---\n";
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
        if ($i->product_variant_type === \App\Models\ProductAccurate::class) {
            $accId = $i->variant->id;
        } elseif (method_exists($i->variant, 'accurateData') && $i->variant->accurateData) {
            $accId = $i->variant->accurateData->id;
        } else {
            $accId = $i->variant->product_accurate_id ?? 'null';
        }
        echo "   AccurateID (Resolved): " . $accId . "\n";
    } else {
        echo "   No Variant relation loaded or null.\n";
    }
}

echo "\n--- TESTING SERVICE ---\n";
$service = new \App\Services\WarrantyCalculatorService();
$res = $service->calculateWarranties($order, $item);
echo "Policies to apply:\n";
foreach($res as $r) {
    echo " -> ID: {$r->id}, Name: {$r->name}\n";
}
