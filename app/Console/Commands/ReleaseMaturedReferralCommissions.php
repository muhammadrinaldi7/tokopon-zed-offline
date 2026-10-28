<?php

namespace App\Console\Commands;

use App\Services\ReferralService;
use Illuminate\Console\Command;

class ReleaseMaturedReferralCommissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:release-holds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis lepaskan komisi referral yang telah melewati batas hold_until H+7 menjadi status APPROVED (Siap Ditarik)';

    /**
     * Execute the console command.
     */
    public function handle(ReferralService $referralService): int
    {
        $this->info("Memeriksa komisi referral berstatus PENDING yang telah melewati masa hold...");

        $count = $referralService->releaseMaturedCommissions();

        $this->info("Selesai! Sebanyak {$count} komisi referral berhasil diubah menjadi APPROVED.");

        return self::SUCCESS;
    }
}