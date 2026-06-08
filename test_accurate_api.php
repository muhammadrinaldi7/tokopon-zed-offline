<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\AccurateService::class);

// get a list of receive items
$ids = $service->getReceiveItemList('syihab');
if (empty($ids)) {
    echo "No receive items found.\n";
    exit;
}

$firstId = $ids[0];
echo "Fetching detail for receive item ID: $firstId\n";

$detail = $service->getReceiveItemDetail($firstId, 'syihab');
echo json_encode($detail, JSON_PRETTY_PRINT);
