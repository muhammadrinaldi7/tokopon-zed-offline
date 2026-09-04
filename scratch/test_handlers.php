<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\ApprovalService::class);
$modules = ['ORDER_CANCELLATION', 'SELL_PHONE_APPROVAL', 'WARRANTY_REPLACEMENT', 'WARRANTY_EXTENSION', 'CUSTOM_CASHBACK'];

foreach ($modules as $m) {
    $handler = $service->getHandler($m);
    echo "Module: {$m} -> Handler: " . get_class($handler) . " [OK]\n";
}
echo "\nSemua Handler terdaftar dan dapat di-instantiate dengan sempurna!\n";
