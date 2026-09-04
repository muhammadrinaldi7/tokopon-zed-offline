<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AccurateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAccurateVendorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public string $databaseSource;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 15;

    public function __construct(User $user, string $databaseSource = 'syihab')
    {
        $this->user = $user;
        $this->databaseSource = $databaseSource;
    }

    public function handle(AccurateService $service): void
    {
        try {
            $service->syncVendor($this->user, $this->databaseSource);
            Log::channel('pos_accurate')->info("SyncAccurateVendorJob SUKSES: User {$this->user->name} (ID: {$this->user->id}) ke DB {$this->databaseSource}");
        } catch (\Throwable $e) {
            Log::channel('pos_accurate')->error("SyncAccurateVendorJob GAGAL: User {$this->user->name}: " . $e->getMessage());
            throw $e;
        }
    }
}
