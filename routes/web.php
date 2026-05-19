<?php

use App\Livewire\Admin\Users\UserOperational;
use App\Livewire\Pages\Buymobile;
use App\Livewire\Pages\PhoneRepair;
use App\Livewire\Pages\SellPhone;
use App\Livewire\Pages\SellPhoneHistory;
use App\Livewire\Pages\TradeIn;
use App\Livewire\Pages\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ──────────────────────────────────────────────
Route::livewire('/', 'pages::home');

Route::get('/buy-mobile', Buymobile::class)->name('buy-mobile');
Route::get('/phone-repair', PhoneRepair::class)->name('phone-repair');
Route::get('/trade-in/{product:slug?}', TradeIn::class)->name('trade-in');
Route::get('/sell-phone', SellPhone::class)->name('sell-phone');
Route::get('/products', \App\Livewire\Pages\ProductList::class)->name('products.index');
Route::get('/products/{product:slug}', \App\Livewire\Pages\ProductDetail::class)->name('products.show');
Route::get('/cart', \App\Livewire\Pages\CartPage::class)->name('cart');

// ─── Google OAuth Routes ────────────────────────────────────────
Route::get('/auth/google', [\App\Http\Controllers\GoogleCallbackController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleCallbackController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// ─── Customer Routes (requires auth + customer role) ────────────
Route::middleware(['auth', 'customer'])->group(function () {
    Route::get('/checkout', \App\Livewire\Pages\Checkout::class)->name('checkout');
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
    Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/users', 'pages::admin.user-management')->name('users');
    Route::livewire('/roles', 'pages::admin.role-permission')->name('roles');
    Route::get('/user/operational', UserOperational::class)->name('user.operational');
    Route::get('/products', \App\Livewire\Admin\Products\ProductManagement::class)->name('products');
    Route::get('/second-products', \App\Livewire\Admin\Products\SecondProductManagement::class)->name('second-products');
    Route::get('/orders', \App\Livewire\Admin\Orders\OrderManagement::class)->name('orders.management');
    Route::get('/categories', \App\Livewire\Admin\Products\CategoryManagement::class)->name('categories');
    Route::get('/brands', \App\Livewire\Admin\Products\BrandManagement::class)->name('brands');
    Route::get('/products/{product}/variants', \App\Livewire\Admin\Products\VariantManagement::class)->name('products.variants');
    Route::get('/second-products/{product}/variants', \App\Livewire\Admin\Products\SecondVariantManagement::class)->name('second-products.variants');
    Route::get('/accurate-products', \App\Livewire\Admin\Accurate\ProductAccurateManagement::class)->name('accurate-products');

    Route::get('/settings/payment', \App\Livewire\Admin\Settings\PaymentSettings::class)->name('settings.payment');
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
});

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

// ─── Erzap Webhook Routes (Dynamic Source Support) ────────────────
Route::post('/web_service/import_produk_json/new.json', [\App\Http\Controllers\Api\ErzapProductController::class, 'store']);
Route::post('/web_service/import_produk_json/new', [\App\Http\Controllers\Api\ErzapProductController::class, 'store']);
Route::post('/web_service/sinkronisasi_stok/new', [\App\Http\Controllers\Api\ErzapProductController::class, 'syncStock']);
