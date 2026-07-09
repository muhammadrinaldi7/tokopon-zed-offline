<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\ProductErzap::observe(\App\Observers\ProductErzapObserver::class);
        \App\Models\WarehouseStock::observe(\App\Observers\WarehouseStockObserver::class);

        // Merge guest cart saat user login
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\MergeGuestCartOnLogin::class
        );
    }
}
