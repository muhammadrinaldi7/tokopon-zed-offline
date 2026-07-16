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

        // Hapus batas waktu 60 detik bawaan Laravel HTTP Client (diperlukan untuk AI lokal VPS yang sangat lambat)
        if (method_exists(\Illuminate\Support\Facades\Http::class, 'globalTimeout')) {
            \Illuminate\Support\Facades\Http::globalTimeout(600); // 10 Menit
        }
        ini_set('default_socket_timeout', 600);
        ini_set('max_execution_time', 600);
        set_time_limit(600);

        // Merge guest cart saat user login
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\MergeGuestCartOnLogin::class
        );
    }
}
