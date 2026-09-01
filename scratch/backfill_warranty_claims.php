<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApprovalRequest;
use App\Models\WarrantyClaim;
use Illuminate\Support\Facades\Log;

$requests = ApprovalRequest::where('approvable_type', WarrantyClaim::class)
    ->where('request_type', 'WARRANTY_REPLACEMENT')
    ->where('status', 'COMPLETED')
    ->get();

$count = 0;

foreach ($requests as $request) {
    $claim = WarrantyClaim::find($request->approvable_id);
    if (!$claim) continue;

    $payload = $request->payload;
    if (empty($payload)) continue;

    $replacement_imei = $payload['replacement_imei'] ?? null;
    $replacement_type = $payload['replacement_type'] ?? 'same';
    $replacement_item_no = $payload['replacement_item_no'] ?? null;
    $replacement_product_name = $payload['replacement_product_name'] ?? null;

    $newItemNo = $replacement_type === 'different' ? $replacement_item_no : null;
    
    // Set missing fields
    $updated = false;
    if (empty($claim->resolution_type)) {
        $claim->resolution_type = $replacement_type === 'same' ? 'replacement_same' : 'replacement_different';
        $updated = true;
    }
    if (empty($claim->replacement_serial_number) && $replacement_imei) {
        $claim->replacement_serial_number = $replacement_imei;
        $updated = true;
    }
    if (empty($claim->replacement_item_no) && $newItemNo) {
        $claim->replacement_item_no = $newItemNo;
        $updated = true;
    }
    if (empty($claim->replacement_product_name) && $replacement_product_name) {
        $claim->replacement_product_name = $replacement_product_name;
        $updated = true;
    }

    if ($updated) {
        $claim->save();
        $count++;
        echo "Updated Claim ID {$claim->id}: type={$claim->resolution_type}, IMEI={$claim->replacement_serial_number}, ItemNo={$claim->replacement_item_no}\n";
    }
}

echo "\nFinished backfilling. Total updated records: $count\n";
