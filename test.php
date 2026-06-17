<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\ProductAccurate::upsert(
    [
        [
            'accurate_id' => 'TEST_123',
            'database_source' => 'syihab',
            'item_no' => 'TEST_123',
            'name' => 'TEST',
            'has_sn' => true,
            'business_unit_id' => 1
        ]
    ],
    ['accurate_id', 'database_source'],
    ['has_sn', 'business_unit_id', 'item_no', 'name']
);

$p = \App\Models\ProductAccurate::where('accurate_id', 'TEST_123')->first();
echo "has_sn in DB: " . $p->has_sn . "\n";
echo "type: " . gettype($p->has_sn) . "\n";
