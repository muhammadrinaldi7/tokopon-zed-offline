<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AccurateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAccurateCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $currentDatabaseSource;

    public function __construct(User $user, $currentDatabaseSource)
    {
        $this->user = $user;
        $this->currentDatabaseSource = $currentDatabaseSource;
    }

    public function handle(AccurateService $service)
    {
        // 1. Load relasi untuk menghindari N+1
        $this->user->load('accurateCustomers.businessUnit');
        
        $updatedSources = [];

        // 2. Loop ke setiap koneksi pelanggan yang ada
        foreach ($this->user->accurateCustomers as $pivot) {
            if ($pivot->businessUnit) {
                // Update ke Accurate
                $service->updateCustomer($this->user, $pivot->businessUnit->code);
                $updatedSources[] = $pivot->businessUnit->code;
            }
        }

        // 3. Pastikan database source saat ini juga tersinkronisasi
        // (Berjaga-jaga jika pelanggan belum terhubung dengan cabang tempat transaksi saat ini)
        if (!in_array($this->currentDatabaseSource, $updatedSources)) {
            $service->updateCustomer($this->user, $this->currentDatabaseSource);
        }
    }
}
