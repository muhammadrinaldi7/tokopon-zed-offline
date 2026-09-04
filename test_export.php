<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = new App\Livewire\Zoffline\Reporting\InvoiceReport();
$report->startDate = '2026-09-01';
$report->endDate = '2026-09-04';
$report->search = 'POS-SYB-20260901-9749-0002'; // Filter to just this order

// Karena getPaymentRows protected, kita buat hack
$reflection = new ReflectionClass(App\Livewire\Zoffline\Reporting\InvoiceReport::class);
$method = $reflection->getMethod('getPaymentRows');
$method->setAccessible(true);
$rows = $method->invoke($report);

echo "ROWS COUNT: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "ORDER: " . $r['order_number'] . "\n";
    echo "PROYEK: " . json_encode($r['proyek']) . "\n";
}

$export = new App\Exports\InvoiceReportExport($rows);
print_r($export->headings());
