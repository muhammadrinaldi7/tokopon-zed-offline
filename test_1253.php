<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\AccurateService::class);

$id = 1253;
echo "Fetching detail for receive item ID: $id\n";

$detail = $service->getReceiveItemDetail($id, 'syihab');
if (!$detail) {
    echo "Detail not found for syihab, trying second...\n";
    $detail = $service->getReceiveItemDetail($id, 'second');
    if (!$detail) {
        echo "Detail completely not found.\n";
        exit;
    }
}

foreach ($detail['detailItem'] ?? [] as $item) {
    $data = [
        'receiveItemId' => $id,
        'itemNo' => $item['item']['no'] ?? null,
        'itemCost' => $item['itemCost'] ?? null,
        'unitPrice' => $item['unitPrice'] ?? null,
        'unitVendorPrice' => $item['unitVendorPrice'] ?? null,
        'item.unitVendorPrice' => $item['item']['unitVendorPrice'] ?? null,
    ];
    echo json_encode($data) . "\n";
}