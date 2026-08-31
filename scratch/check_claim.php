<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$claims = App\Models\WarrantyClaim::whereHas('warranty', function($q) { 
    $q->where('serial_number', 'LIKE', '%RNDEV07%'); 
})->orWhere('serial_number', 'LIKE', '%RNDEV07%')->get();

foreach($claims as $c) { 
    echo 'Claim ID: ' . $c->id . ', Status: ' . $c->status . ', Resolution: ' . $c->resolution . ', Has Inspection: ' . ($c->inspection()->exists() ? 'Yes' : 'No') . PHP_EOL; 
}
