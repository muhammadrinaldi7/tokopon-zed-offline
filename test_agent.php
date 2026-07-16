<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

ini_set('max_execution_time', 0);

$agent = new \App\Ai\Agents\DatabaseAnalyzerAgent(); // No admin ID to avoid history
$response = $agent->prompt('jumlah user saat ini berapa?');
echo "\n--- FINAL TEXT ---\n";
echo $response->text;
echo "\n--- RAW RESPONSE JSON ---\n";
echo json_encode($response, JSON_PRETTY_PRINT);
echo "\n";
