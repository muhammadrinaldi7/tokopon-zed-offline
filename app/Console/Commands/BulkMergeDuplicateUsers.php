<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BulkMergeDuplicateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:bulk-merge-clean
                            {--chunk=100 : Jumlah kelompok per batch transaksi}
                            {--limit=0 : Batas total kelompok yang diproses (0 = semua)}
                            {--dry-run : Menjalankan simulasi tanpa mengubah data di database}
                            {--only-clean : Hanya proses nomor tanpa riwayat transaksi (orders & sell_phones)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Penggabungan massal akun pengguna duplikat secara aman dengan isolasi per kelompok transaksi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));
        $onlyClean = $this->option('only-clean') || true; // default true for safety

        $this->info("==========================================================");
        $this->info("        PENGGABUNGAN MASSAL AKUN DUPLIKAT TOKOPUN        ");
        $this->info("==========================================================");
        if ($isDryRun) {
            $this->warn(">> MODE SIMULASI (DRY-RUN): Tidak ada data yang akan diubah/dihapus <<");
        }

        // 1. Ambil staff IDs untuk dieksklusikan
        $this->line("Mengidentifikasi akun staf/karyawan...");
        $staffIds = User::where(function ($q) {
            $q->whereHas('roles', fn($r) => $r->whereNotIn('name', ['customer', 'user']))
                ->orWhereExists(fn($sub) => $sub->select(DB::raw(1))->from('employes')->whereColumn('employes.user_id', 'users.id'))
                ->orWhereExists(fn($sub) => $sub->select(DB::raw(1))->from('cashier_shifts')->whereColumn('cashier_shifts.user_id', 'users.id'));
        })->pluck('id')->toArray();
        $this->info("Ditemukan " . count($staffIds) . " akun staf (otomatis diproteksi & dikecualikan).");

        // 2. Query kelompok nomor telepon yang siap diproses
        $this->line("Menghitung total kelompok nomor telepon duplikat...");
        $baseQuery = DB::table('user_profiles as up')
            ->select('up.phone_number')
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->whereNotIn('up.user_id', $staffIds);

        if ($onlyClean) {
            // Hanya nomor tanpa pesanan dan tanpa pembelian HP
            $baseQuery->whereNotIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            })->whereNotIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('sell_phones as sp_sub', 'sp_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            });
        }

        $baseQuery->groupBy('up.phone_number')->havingRaw('COUNT(up.id) > 1');

        $totalGroups = (clone $baseQuery)->get()->count();

        if ($limit > 0 && $limit < $totalGroups) {
            $totalToProcess = $limit;
            $this->info("Total target: {$totalToProcess} kelompok (dibatasi opsi --limit={$limit} dari total {$totalGroups}).");
        } else {
            $totalToProcess = $totalGroups;
            $this->info("Total target: {$totalToProcess} kelompok nomor telepon tanpa transaksi.");
        }

        if ($totalToProcess === 0) {
            $this->warn("Tidak ada data kelompok duplikat yang memenuhi kriteria.");
            return Command::SUCCESS;
        }

        if (!$this->confirm("Apakah Anda yakin ingin melanjutkan proses penggabungan massal ini?", true)) {
            $this->warn("Proses dibatalkan oleh pengguna.");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalToProcess);
        $bar->start();

        $processed = 0;
        $success = 0;
        $skipped = 0;
        $failed = 0;

        // Ambil nomor telepon dalam batch
        $phoneList = (clone $baseQuery)->orderBy('up.phone_number')->pluck('phone_number')->toArray();
        if ($limit > 0) {
            $phoneList = array_slice($phoneList, 0, $limit);
        }

        foreach (array_chunk($phoneList, $chunkSize) as $chunk) {
            foreach ($chunk as $phone) {
                $res = $this->mergeGroup($phone, $staffIds, $isDryRun);
                if ($res['success']) {
                    $success++;
                } elseif ($res['skipped']) {
                    $skipped++;
                } else {
                    $failed++;
                }
                $processed++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("==========================================================");
        $this->info("                 RINGKASAN HASIL EKSEKUSI                 ");
        $this->info("==========================================================");
        $this->line("Total Diproses  : {$processed}");
        $this->info("Berhasil Dilebur : {$success}");
        $this->warn("Dilewati (Skip) : {$skipped}");
        if ($failed > 0) {
            $this->error("Gagal           : {$failed}");
        }
        $this->info("==========================================================");

        return Command::SUCCESS;
    }

    /**
     * Eksekusi merge untuk 1 kelompok nomor telepon
     */
    protected function mergeGroup(string $phone, array $staffIds, bool $isDryRun): array
    {
        $users = User::with(['profile'])
            ->whereNotIn('id', $staffIds)
            ->withCount(['orders', 'sellPhones'])
            ->whereHas('profile', fn($q) => $q->where('phone_number', $phone))
            ->orderByDesc('orders_count')
            ->orderByDesc('sell_phones_count')
            ->orderBy('created_at')
            ->get();

        if ($users->count() < 2) {
            return ['success' => false, 'skipped' => true, 'reason' => 'Kurang dari 2 akun'];
        }

        // Cek konflik transaksi
        $usersWithTransactions = $users->filter(fn($u) => $u->orders_count > 0 || $u->sell_phones_count > 0);
        if ($usersWithTransactions->count() > 1) {
            return ['success' => false, 'skipped' => true, 'reason' => 'Konflik transaksi multi-akun'];
        }

        // Target: akun pertama (terbanyak transaksi atau tertua)
        $targetUser = $users->first();
        $targetUserId = $targetUser->id;
        $sourceUserIds = $users->slice(1)->pluck('id')->toArray();

        if ($isDryRun) {
            return ['success' => true, 'skipped' => false, 'reason' => 'Simulasi sukses'];
        }

        try {
            DB::transaction(function () use ($targetUserId, $sourceUserIds) {
                // 1. orders
                DB::table('orders')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 2. sell_phones
                DB::table('sell_phones')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 3. sell_phone_issues
                DB::table('sell_phone_issues')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 4. trade_ins
                DB::table('trade_ins')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 5. warranties
                DB::table('warranties')->whereIn('customer_user_id', $sourceUserIds)->update(['customer_user_id' => $targetUserId]);
                // 6. warranty_claims
                DB::table('warranty_claims')->whereIn('customer_user_id', $sourceUserIds)->update(['customer_user_id' => $targetUserId]);
                // 7. customer_deposits
                DB::table('customer_deposits')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 8. order_issues
                DB::table('order_issues')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 9. user_addresses
                DB::table('user_addresses')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);

                // 10. user_bank_accounts
                $targetBankAccounts = DB::table('user_bank_accounts')
                    ->where('user_id', $targetUserId)
                    ->pluck('account_number')
                    ->filter()
                    ->toArray();

                $sourceBankAccounts = DB::table('user_bank_accounts')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceBankAccounts as $sba) {
                    if (!empty($sba->account_number) && !in_array($sba->account_number, $targetBankAccounts)) {
                        DB::table('user_bank_accounts')->where('id', $sba->id)->update(['user_id' => $targetUserId]);
                        $targetBankAccounts[] = $sba->account_number;
                    } else {
                        DB::table('user_bank_accounts')->where('id', $sba->id)->delete();
                    }
                }

                // 11. product_reviews
                DB::table('product_reviews')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);

                // 12. conversations & messages
                DB::table('conversations')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                DB::table('messages')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);

                if (Schema::hasTable('agent_conversations')) {
                    DB::table('agent_conversations')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                }
                if (Schema::hasTable('agent_conversation_messages')) {
                    DB::table('agent_conversation_messages')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                }

                // 13. carts & cart_items
                $targetCart = DB::table('carts')->where('user_id', $targetUserId)->first();
                $sourceCarts = DB::table('carts')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceCarts as $sCart) {
                    if ($targetCart) {
                        DB::table('cart_items')->where('cart_id', $sCart->id)->update(['cart_id' => $targetCart->id]);
                        DB::table('carts')->where('id', $sCart->id)->delete();
                    } else {
                        DB::table('carts')->where('id', $sCart->id)->update(['user_id' => $targetUserId]);
                        $targetCart = DB::table('carts')->where('user_id', $targetUserId)->first();
                    }
                }

                // 14. user_accurate_customers
                $targetAccurateBUs = DB::table('user_accurate_customers')
                    ->where('user_id', $targetUserId)
                    ->pluck('business_unit_id')
                    ->toArray();

                $sourceAccurateCustomers = DB::table('user_accurate_customers')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceAccurateCustomers as $sac) {
                    if (!in_array($sac->business_unit_id, $targetAccurateBUs)) {
                        DB::table('user_accurate_customers')->where('id', $sac->id)->update(['user_id' => $targetUserId]);
                        $targetAccurateBUs[] = $sac->business_unit_id;
                    } else {
                        DB::table('user_accurate_customers')->where('id', $sac->id)->delete();
                    }
                }

                // 15. user_accurate_vendors
                $targetVendorBUs = DB::table('user_accurate_vendors')
                    ->where('user_id', $targetUserId)
                    ->pluck('business_unit_id')
                    ->toArray();

                $sourceAccurateVendors = DB::table('user_accurate_vendors')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceAccurateVendors as $sav) {
                    if (!in_array($sav->business_unit_id, $targetVendorBUs)) {
                        DB::table('user_accurate_vendors')->where('id', $sav->id)->update(['user_id' => $targetUserId]);
                        $targetVendorBUs[] = $sav->business_unit_id;
                    } else {
                        DB::table('user_accurate_vendors')->where('id', $sav->id)->delete();
                    }
                }

                // 16. user_profiles
                $targetProfile = DB::table('user_profiles')->where('user_id', $targetUserId)->first();
                $sourceProfiles = DB::table('user_profiles')->whereIn('user_id', $sourceUserIds)->get();
                if ($targetProfile) {
                    $enrich = [];
                    if (empty($targetProfile->domisili)) {
                        $firstDom = $sourceProfiles->first(fn($p) => !empty($p->domisili))?->domisili;
                        if ($firstDom) $enrich['domisili'] = $firstDom;
                    }
                    if (empty($targetProfile->birth_date)) {
                        $firstBirth = $sourceProfiles->first(fn($p) => !empty($p->birth_date))?->birth_date;
                        if ($firstBirth) $enrich['birth_date'] = $firstBirth;
                    }
                    if (empty($targetProfile->gender)) {
                        $firstGender = $sourceProfiles->first(fn($p) => !empty($p->gender))?->gender;
                        if ($firstGender) $enrich['gender'] = $firstGender;
                    }
                    if (!empty($enrich)) {
                        DB::table('user_profiles')->where('id', $targetProfile->id)->update($enrich);
                    }
                }
                DB::table('user_profiles')->whereIn('user_id', $sourceUserIds)->delete();

                // 17. Roles & sessions & delete
                DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $sourceUserIds)->delete();
                DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $sourceUserIds)->delete();
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->whereIn('user_id', $sourceUserIds)->delete();
                }
                DB::table('users')->whereIn('id', $sourceUserIds)->delete();
            });

            return ['success' => true, 'skipped' => false, 'target_id' => $targetUserId];
        } catch (\Throwable $e) {
            return ['success' => false, 'skipped' => false, 'reason' => $e->getMessage()];
        }
    }
}
