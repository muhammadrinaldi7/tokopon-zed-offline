<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$logs = App\Models\WarrantySerialLog::where('old_serial_number', 'LIKE', '%RNDEV07%')->get();
foreach($logs as $l) {
    echo "Found log: Claim ID: {$l->warranty_claim_id}, Old SN: {$l->old_serial_number}, New SN: {$l->new_serial_number}\n";
    $c = App\Models\WarrantyClaim::find($l->warranty_claim_id);
    if($c) {
        echo "Claim ID: {$c->id}, Status: {$c->status}, Resolution: {$c->resolution}, Has Inspection: " . ($c->inspection()->exists() ? 'Yes' : 'No') . "\n";
    }
}
