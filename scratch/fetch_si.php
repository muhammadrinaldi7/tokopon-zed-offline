<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$accurateService = app(\App\Services\AccurateService::class);
$dbSource = 'syihab';

$reflection = new ReflectionClass($accurateService);
$method = $reflection->getMethod('getCredentials');
$method->setAccessible(true);
list($host, $token, $secretKey) = $method->invoke($accurateService, $dbSource);

$sessionMethod = $reflection->getMethod('getSession');
$sessionMethod->setAccessible(true);
$session = $sessionMethod->invoke($accurateService, $dbSource, $host, $token);

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'X-Session-ID' => $session
])->get($host . '/sales-invoice/list.do', [
    'fields' => 'id,number,detailItem,salesOrderId,salesOrderNo',
    'filter.keywords.val' => 'SI.2026.06.00010'
]);

print_r($response->json());
