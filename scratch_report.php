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

$endpoints = [
    '/api/report/list.do',
    '/report/list.do',
    '/api/report/stock.do',
    '/api/report/item.do'
];

foreach ($endpoints as $ep) {
    echo "Testing $ep...\n";
    $res = hitAccurate($ep);
    if(isset($res['s']) && $res['s'] == true) {
        echo json_encode($res, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "Failed or not found\n\n";
    }
}
