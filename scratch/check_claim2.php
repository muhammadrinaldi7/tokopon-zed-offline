<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$claims = App\Models\WarrantyClaim::get();
foreach($claims as $c) {
    if (strpos($c->serial_number, 'RNDEV07') !== false || strpos($c->resolution_notes, 'RNDEV07') !== false) {
        echo "Found Claim ID: {$c->id}, Status: {$c->status}, Resolution: {$c->resolution}\n";
        
        $hasInspection = $c->inspection()->exists();
        echo "Has morphOne inspection (QC Retur)? " . ($hasInspection ? "Yes" : "No") . "\n";
        
        $hasRecvInspection = $c->receiving_inspection_id ? "Yes ({$c->receiving_inspection_id})" : "No";
        echo "Has receiving inspection (QC CS)? " . $hasRecvInspection . "\n";
    }
}
