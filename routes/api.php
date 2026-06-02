<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Xendit Webhooks
Route::post('/webhooks/xendit/invoice', [\App\Http\Controllers\Api\XenditWebhookController::class, 'handleInvoiceCallback'])
    ->name('api.webhooks.xendit.invoice');

// Accurate API Sync
Route::get('/accurate/import-items', [\App\Http\Controllers\Api\AccurateImportController::class, 'importItems'])
    ->name('api.accurate.import-items');

Route::post('/webhooks/accurate', [\App\Http\Controllers\Api\AccurateWebhookController::class, 'handle'])
    ->name('api.webhooks.accurate');


// ============================================
// API UNTUK QZ PRINT SIGNATURE (Self-Signed)
// ============================================
Route::post('/sign-qz', function (Request $request) {
    $toSign = $request->input('request');

    // Ambil private key yang sudah di-generate sebelumnya
    // Pastikan path ini sesuai dengan lokasi penyimpanan private-key.pem Anda
    $privateKey = file_get_contents(storage_path('app/private-key.pem'));

    $signature = '';
    // Sign request dengan SHA512
    openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512);

    // Kembalikan ke frontend dalam format base64
    return base64_encode($signature);
});
