<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\AccurateService::class);

$id = 1047;
$databaseSource = 'second';

list($host, $token, $secretKey) = $service->getCredentials($databaseSource);

$timestamp = now()->toIso8601String();
$signature = hash_hmac('sha256', $timestamp, $secretKey);

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'X-Api-Timestamp' => $timestamp,
    'X-Api-Signature'  => $signature,
    'Content-Type'  => 'application/json',
])->get($host . '/sales-receipt/detail.do', [
    'id' => $id
]);

echo json_encode($response->json(), JSON_PRETTY_PRINT);
