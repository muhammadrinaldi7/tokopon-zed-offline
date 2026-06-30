<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = \App\Models\Order::find(82);
if ($order) {
    $order->update(['accurate_receipt_no' => null]);
    $payment = \App\Models\OrderPayment::find(88);
    if ($payment) {
        $payment->update(['status' => 'PENDING', 'paid_at' => null]);
    }
}
