<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$accurateService = app(\App\Services\AccurateService::class);
$dbSource = 'second';

$siData = [
    'customerNo' => 'GSK_CUSTOMER_04278',
    'branchName' => 'GSK - Banjarbaru',
    'transDate' => '13/06/2026',
    'detailItem' => [
        [
            'itemNo' => '100019',
            'unitPrice' => 13999000,
            'quantity' => 1,
            'salesOrderNo' => 'SO.2026.06.00003'
        ]
    ],
    'inclusiveTax' => true,
    'taxable' => true,
    'detailDownPayment' => [
        [
            'invoiceNumber' => 'SID.2026.06.00004',
            // 'paymentAmount' => 5000000
        ]
    ]
];

$res = $accurateService->postSalesInvoice($siData, $dbSource);
print_r($res);
