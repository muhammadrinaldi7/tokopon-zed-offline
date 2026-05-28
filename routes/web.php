<?php

use App\Livewire\Admin\Employe\EmployeManage;
use App\Livewire\Admin\Users\UserOperational;
use App\Livewire\Pages\Buymobile;
use App\Livewire\Pages\PhoneRepair;
use App\Livewire\Pages\SellPhone;
use App\Livewire\Pages\SellPhoneHistory;
use App\Livewire\Pages\TradeIn;
use App\Livewire\Pages\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─── POS Landing Page (requires auth + admin role) ──────────────
Route::get('/', \App\Livewire\Admin\Pos\PointOfSale::class)->middleware(['auth', 'admin'])->name('/');
Route::get('/zoffline', \App\Livewire\Zoffline\Home::class)->name('zoffline');
Route::get('/zoffline/pos', \App\Livewire\Zoffline\Pos\Pos::class)->name('zoffline.pos');
Route::get('/zoffline/trade-in', \App\Livewire\Zoffline\TradeIn\TradeIn::class)->name('zoffline.trade-in');
Route::get('/zoffline/sell-phone', \App\Livewire\Zoffline\SellPhone\SellPhone::class)->name('zoffline.sell-phone');

// ─── Trade In & Sell Phone Client Pages (accessible by authenticated users, e.g. FL or customer) ───
Route::middleware(['auth'])->group(function () {
    Route::get('/sell-phone', SellPhone::class)->name('sell-phone');
    Route::get('/sell-phone-history', SellPhoneHistory::class)->name('sell-phone-history');
    Route::get('/sell-phone/{sellPhone}/detail', \App\Livewire\Pages\SellPhoneDetail::class)->name('sell-phone.show');

    Route::get('/trade-in/{product:slug?}', TradeIn::class)->name('trade-in');
    Route::get('/trade-in-history', \App\Livewire\Pages\TradeInHistory::class)->name('trade-in-history');
    Route::get('/trade-in/{product}/submit', \App\Livewire\Pages\SubmitTradeIn::class)->name('trade-in.submit');
    Route::get('/trade-in/{tradeIn}/detail', \App\Livewire\Pages\TradeInDetail::class)->name('trade-ins.show');
    Route::get('/orders', \App\Livewire\Pages\OrderHistory::class)->name('orders.index');
    Route::get('/orders/{order}', \App\Livewire\Pages\OrderDetail::class)->name('orders.show');
    Route::get('/orders/{order}/confirmation', \App\Livewire\Pages\OrderConfirmation::class)->name('orders.confirmation');

    // Trade In Client
    Route::get('/trade-in-history', \App\Livewire\Pages\TradeInHistory::class)->name('trade-in-history');
    Route::get('/profile', UserProfile::class)->name('profile');
    Route::get('/sell-phone-history', SellPhoneHistory::class)->name('sell-phone-history');
    Route::get('/trade-in/{product}/submit', \App\Livewire\Pages\SubmitTradeIn::class)->name('trade-in.submit');
    Route::get('/trade-in/{tradeIn}/detail', \App\Livewire\Pages\TradeInDetail::class)->name('trade-ins.show');
    Route::get('/sell-phone/{sellPhone}/detail', \App\Livewire\Pages\SellPhoneDetail::class)->name('sell-phone.show');
});

// ─── Admin Routes (requires auth + admin role) ──────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pos', \App\Livewire\Admin\Pos\PointOfSale::class)->name('pos');
    Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/users', 'pages::admin.user-management')->name('users');
    Route::livewire('/roles', 'pages::admin.role-permission')->name('roles');
    Route::get('/user/operational', UserOperational::class)->name('user.operational');
    Route::get('/user/employes', EmployeManage::class)->name('user.employes');
    Route::get('/products', \App\Livewire\Admin\Products\ProductManagement::class)->name('products');
    Route::get('/second-products', \App\Livewire\Admin\Products\SecondProductManagement::class)->name('second-products');
    Route::get('/orders', \App\Livewire\Admin\Orders\OrderManagement::class)->name('orders.management');
    Route::get('/categories', \App\Livewire\Admin\Products\CategoryManagement::class)->name('categories');
    Route::get('/brands', \App\Livewire\Admin\Products\BrandManagement::class)->name('brands');
    Route::get('/products/{product}/variants', \App\Livewire\Admin\Products\VariantManagement::class)->name('products.variants');
    Route::get('/second-products/{product}/variants', \App\Livewire\Admin\Products\SecondVariantManagement::class)->name('second-products.variants');
    Route::get('/accurate-products', \App\Livewire\Admin\Accurate\ProductAccurateManagement::class)->name('accurate-products');
    Route::get('/warehouse-stocks', \App\Livewire\Admin\Warehouse\StockManagement::class)->name('warehouse-stocks');

    Route::get('/settings/payment', \App\Livewire\Admin\Settings\PaymentSettings::class)->name('settings.payment');
    Route::get('/settings/payment-methods', \App\Livewire\Admin\Settings\PaymentMethodIndex::class)->name('settings.payment-methods');
    Route::get('/settings/shipping', \App\Livewire\Admin\Settings\ShippingSettings::class)->name('settings.shipping');
    Route::get('/settings/catalog', \App\Livewire\Admin\Settings\CatalogSettings::class)->name('settings.catalog');
    Route::get('/settings/warehouse', \App\Livewire\Admin\Settings\Warehouse\Index::class)->name('settings.warehouse');

    Route::get('/trade-ins', App\Livewire\Admin\TradeIn\Index::class)->name('trade-ins.index');
    Route::get('/trade-ins/{tradeIn}', App\Livewire\Admin\TradeIn\Show::class)->name('trade-ins.show');

    Route::get('/sell-phones', App\Livewire\Admin\SellPhone\Index::class)->name('sell-phones.index');
    Route::get('/sell-phones/{sellPhone}', App\Livewire\Admin\SellPhone\Show::class)->name('sell-phones.show');

    Route::prefix('buyback')->name('buyback.')->group(function () {
        Route::get('/devices', App\Livewire\Admin\Buyback\DeviceIndex::class)->name('index');
        Route::get('/devices/create', App\Livewire\Admin\Buyback\DeviceForm::class)->name('create');
        Route::get('/tiers', App\Livewire\Admin\Buyback\TierIndex::class)->name('tiers');
    });

    Route::prefix('qc')->name('qc.')->group(function () {
        Route::get('/templates', App\Livewire\Admin\Qc\TemplateIndex::class)->name('templates');
        Route::get('/device', App\Livewire\Admin\Qc\DeviceSearch::class)->name('device-search');
        Route::get('/device/{imei}', App\Livewire\Admin\Qc\DevicePassport::class)->name('device-passport');
        Route::get('/inspect/{secondProductVariant}', App\Livewire\Admin\Qc\InspectionForm::class)->name('inspect');
    });
});

Route::get('/qc/device/{imei}', App\Livewire\Pages\PublicDeviceQc::class)->name('public.device-qc');

// ─── CS Chat Route (requires auth + admin middleware + cs role) ──
Route::livewire('/admin/cs-chat', 'pages::cs-dashboard')
    ->middleware(['auth', 'admin'])
    ->name('admin.cs-chat');

// ─── Logout ─────────────────────────────────────────────────────
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

// ─── Google OAuth Routes ────────────────────────────────────────
Route::get('/auth/google', [\App\Http\Controllers\GoogleCallbackController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleCallbackController::class, 'handleGoogleCallback'])->name('auth.google.callback');


// ─── Erzap Webhook Routes (Dynamic Source Support) ────────────────
Route::post('/web_service/import_produk_json/new.json', [\App\Http\Controllers\Api\ErzapProductController::class, 'store']);
Route::post('/web_service/import_produk_json/new', [\App\Http\Controllers\Api\ErzapProductController::class, 'store']);
Route::post('/web_service/sinkronisasi_stok/new', [\App\Http\Controllers\Api\ErzapProductController::class, 'syncStock']);
