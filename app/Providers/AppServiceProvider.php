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
        if (!app()->runningInConsole()) {
            ini_set('default_socket_timeout', 600);
            ini_set('max_execution_time', 600);
            set_time_limit(600);
        }

        // Merge guest cart saat user login
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\MergeGuestCartOnLogin::class
        );

        // Implicitly grant 'admin' and 'superadmin' roles all permissions
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole')) {
                return ($user->hasRole('superadmin') || $user->hasRole('admin')) ? true : null;
            }
            return null;
        });
    }
}
