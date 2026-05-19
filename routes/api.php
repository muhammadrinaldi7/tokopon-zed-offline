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
