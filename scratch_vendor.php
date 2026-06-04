<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

function hitAccurate($url, $params = []) {
    $timestamp = now()->toIso8601String();
    $signature = hash_hmac('sha256', $timestamp, env('ACCURATE_SECRET_KEY'));
    
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('ACCURATE_TOKEN'),
        'X-Api-Timestamp' => $timestamp,
        'X-Api-Signature'  => $signature,
        'Content-Type'  => 'application/json',
    ])->get(env('ACCURATE_HOST') . $url, $params);
    
    return $response->json();
}

$res = hitAccurate('/item/list.do', [
    'fields' => 'no,name,preferedVendorName,vendorName'
]);

echo json_encode($res, JSON_PRETTY_PRINT);
