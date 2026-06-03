<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$api = app(\App\Services\AccurateService::class);
$employees = $api->getEmployees();

if (!empty($employees)) {
    echo json_encode($employees[0], JSON_PRETTY_PRINT);
} else {
    echo "No employees found";
}
