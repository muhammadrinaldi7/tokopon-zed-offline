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

// Webhook untuk Approval Telegram Callback (n8n)
Route::post('/webhooks/approval', [\App\Http\Controllers\ApprovalController::class, 'apiProcess'])
    ->name('api.webhooks.approval');

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

// ============================================
// PUBLIC READ-ONLY TRADE-IN & PRICING API
// ============================================
Route::prefix('v1/public/trade-in')->middleware('throttle:60,1')->group(function () {
    Route::get('/brands', [\App\Http\Controllers\Api\PublicTradeInController::class, 'getBrands'])
        ->name('api.public.trade-in.brands');
    Route::get('/old-devices', [\App\Http\Controllers\Api\PublicTradeInController::class, 'getOldDevices'])
        ->name('api.public.trade-in.old-devices');
    Route::get('/target-devices', [\App\Http\Controllers\Api\PublicTradeInController::class, 'getTargetDevices'])
        ->name('api.public.trade-in.target-devices');
    Route::post('/calculate', [\App\Http\Controllers\Api\PublicTradeInController::class, 'calculate'])
        ->name('api.public.trade-in.calculate');
    Route::get('/device/{id}', [\App\Http\Controllers\Api\PublicTradeInController::class, 'getSingleDevicePrice'])
        ->name('api.public.trade-in.device');
});

// ============================================
// EXECUTIVE & DIRECTORS API (STAGE 1)
// ============================================
Route::prefix('v1/executive')->group(function () {
    // Auth login endpoint (rate-limited)
    Route::post('/login', [\App\Http\Controllers\Api\Executive\ExecutiveAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.executive.login');

    // Authenticated & role-guarded endpoints
    Route::middleware(['auth:sanctum', 'role:superadmin|admin|director'])->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\Executive\ExecutiveAuthController::class, 'me'])
            ->name('api.executive.me');
        Route::post('/logout', [\App\Http\Controllers\Api\Executive\ExecutiveAuthController::class, 'logout'])
            ->name('api.executive.logout');

        // Analytics & Metrics Endpoints
        Route::get('/filters', [\App\Http\Controllers\Api\Executive\ExecutiveDashboardController::class, 'filterOptions'])
            ->name('api.executive.filters');
        Route::get('/kpi-summary', [\App\Http\Controllers\Api\Executive\ExecutiveDashboardController::class, 'kpiSummary'])
            ->name('api.executive.kpi-summary');
        Route::get('/branch-comparison', [\App\Http\Controllers\Api\Executive\ExecutiveDashboardController::class, 'branchComparison'])
            ->name('api.executive.branch-comparison');
        Route::get('/sales-trend', [\App\Http\Controllers\Api\Executive\ExecutiveDashboardController::class, 'salesTrend'])
            ->name('api.executive.sales-trend');
        Route::get('/top-products', [\App\Http\Controllers\Api\Executive\ExecutiveDashboardController::class, 'topProducts'])
            ->name('api.executive.top-products');
        Route::get('/payment-breakdown', [\App\Http\Controllers\Api\Executive\ExecutiveDashboardController::class, 'paymentBreakdown'])
            ->name('api.executive.payment-breakdown');
        Route::get('/overview', [\App\Http\Controllers\Api\Executive\ExecutiveDashboardController::class, 'dashboardOverview'])
            ->name('api.executive.overview');

        // AI Executive Assistant Endpoints (Direct 9router Integration)
        Route::prefix('ai')->group(function () {
            Route::post('/chat', [\App\Http\Controllers\Api\Executive\ExecutiveAiController::class, 'chat'])
                ->name('api.executive.ai.chat');
            Route::get('/history', [\App\Http\Controllers\Api\Executive\ExecutiveAiController::class, 'history'])
                ->name('api.executive.ai.history');
            Route::post('/summarize', [\App\Http\Controllers\Api\Executive\ExecutiveAiController::class, 'summarize'])
                ->name('api.executive.ai.summarize');
            Route::delete('/history', [\App\Http\Controllers\Api\Executive\ExecutiveAiController::class, 'clearHistory'])
                ->name('api.executive.ai.clear');
        });
    });
});


