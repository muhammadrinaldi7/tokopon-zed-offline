<?php

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class SyncLoyaltyAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:sync-users {--without-history : Lewati kalkulasi akumulasi belanja pesanan lampau}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasikan dan petakan pengguna lama (user_profiles / users) ke tabel loyalty_accounts';

    /**
     * Execute the console command.
     */
    public function handle(LoyaltyService $loyaltyService): int
    {
        $this->info('Memulai pemetaan data pengguna lama ke sistem Loyalty & Membership...');

        $calcHistory = !$this->option('without-history');
        if ($calcHistory) {
            $this->line('Mode: Menghitung akumulasi belanja order COMPLETED untuk penentuan Tier awal.');
        } else {
            $this->line('Mode: Akun baru dibuat dengan Tier BRONZE standar.');
        }

        $result = $loyaltyService->syncExistingUsers($calcHistory);

        $this->newLine();
        $this->info('=== Ringkasan Sinkronisasi Loyalty ===');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Profil / Pengguna Dipindai', $result['total_scanned']],
                ['Akun Loyalty Baru Dibuat', $result['newly_created']],
                ['Akun Lama Dihubungkan / Diperbarui', $result['linked_existing']],
                ['Dilewati (Nomor HP Tidak Valid / Sudah Sinkron)', $result['skipped']],
            ]
        );

        $this->info('Sinkronisasi selesai dengan sukses!');
        return Command::SUCCESS;
    }
}
