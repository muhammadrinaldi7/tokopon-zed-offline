<?php

namespace App\Console\Commands;

use App\Models\BusinessUnit;
use App\Models\CommissionWithdrawal;
use App\Models\LoyaltyAccount;
use App\Models\Order;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\UserProfile;
use App\Models\UserReferralCode;
use App\Services\ReferralService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestReferralE2eCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:referral-e2e';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uji coba otomatis menyeluruh (E2E Simulation) untuk alur Klaim Akun POS, Komisi Referral, Anti-Fraud, dan Penarikan Dana.';

    public function handle(ReferralService $referralService): int
    {
        $this->info("================================================================");
        $this->info("🚀 MEMULAI SIMULASI END-TO-END (E2E) SISTEM REFERRAL & MEMBER POS");
        $this->info("================================================================");

        $testSuffix = time();
        $testUsers = [];
        $testOrders = [];

        try {
            // -------------------------------------------------------------
            // SKENARIO 1: MEMBER OFFLINE POS KLAIM AKUN DENGAN KODE REFERRAL
            // -------------------------------------------------------------
            $this->newLine();
            $this->warn("📌 [SKENARIO 1] Pengujian Smart Activation Akun Kasir POS ke Mobile...");

            // 1.1 Buat Akun Pengundang (Referrer)
            $referrer = User::create([
                'name' => "Mitra Pengundang {$testSuffix}",
                'email' => "referrer_{$testSuffix}@tokopon-test.com",
                'password' => Hash::make('password123'),
                'referral_code' => "TKP-TEST{$testSuffix}",
                'registration_channel' => 'MOBILE',
                'mobile_activated_at' => now(),
            ]);
            $testUsers[] = $referrer->id;

            $refCodeRecord = UserReferralCode::create([
                'user_id' => $referrer->id,
                'code' => $referrer->referral_code,
                'total_clicks' => 5,
                'total_registered' => 0,
                'total_converted_orders' => 0,
            ]);

            $this->line("  ✓ User Pengundang dibuat: <info>{$referrer->name}</info> (Kode: <comment>{$referrer->referral_code}</comment>)");

            // 1.2 Buat Data Pelanggan POS (Simulasi pernah beli di kasir)
            $posPhone = "0899" . substr((string)$testSuffix, -8);
            $posUser = User::create([
                'name' => 'Pelanggan Kasir',
                'email' => "{$posPhone}_dummy@zpos.com",
                'password' => Hash::make('randompospwd'),
                'registration_channel' => 'POS',
                'mobile_activated_at' => null, // Belum pernah login mobile
            ]);
            $testUsers[] = $posUser->id;

            $posProfile = UserProfile::create([
                'user_id' => $posUser->id,
                'full_name' => 'Pelanggan Kasir',
                'phone_number' => $posPhone,
                'domisili' => 'Makassar',
            ]);

            // Simulasi poin belanja POS
            $loyaltyAcc = LoyaltyAccount::create([
                'user_id' => $posUser->id,
                'phone_number' => $posPhone,
                'customer_name' => 'Pelanggan Kasir',
                'tier' => 'SILVER',
                'points_balance' => 250,
                'total_spend' => 3500000,
            ]);

            $this->line("  ✓ Pelanggan POS Eksisting: <info>ID #{$posUser->id}</info> (No. WA: {$posPhone}, Poin POS: 250)");

            // 1.3 Simulasi Pelanggan Mendaftar di Aplikasi Mobile Menggunakan No WA POS & Kode Referral
            $payload = [
                'name' => "Budi Santoso {$testSuffix}",
                'phone' => $posPhone,
                'email' => "budi_{$testSuffix}@gmail.com",
                'password' => 'BudiNewPass123!',
                'address' => 'Jl. Pengayoman No. 12',
                'referral_code' => $referrer->referral_code,
            ];

            $claimedUser = $referralService->registerOrClaimMember($payload);

            // Verifikasi Akun POS
            if ($claimedUser->id !== $posUser->id) {
                throw new Exception("FAIL: Akun POS diduplikasi! ID seharusnya {$posUser->id}, tapi menghasilkan {$claimedUser->id}");
            }
            if ($claimedUser->mobile_activated_at === null) {
                throw new Exception("FAIL: mobile_activated_at tidak terisi!");
            }
            if ($claimedUser->email !== "budi_{$testSuffix}@gmail.com") {
                throw new Exception("FAIL: Email asli tidak terupdate!");
            }
            if ($claimedUser->referred_by_id !== $referrer->id) {
                throw new Exception("FAIL: referred_by_id tidak terhubung ke pengundang!");
            }

            // Verifikasi Poin POS tetap utuh
            $freshLoyalty = LoyaltyAccount::where('user_id', $claimedUser->id)->first();
            if (!$freshLoyalty || $freshLoyalty->points_balance !== 250) {
                throw new Exception("FAIL: Poin loyalty POS hilang setelah aktivasi!");
            }

            // Verifikasi Metrik Kode Pengundang Bertambah
            $refCodeRecord->refresh();
            if ($refCodeRecord->total_registered !== 1) {
                throw new Exception("FAIL: total_registered pada kode referral tidak bertambah!");
            }

            $this->info("  ✅ HASIL SKENARIO 1: SUKSES 100%! Akun kasir POS berhasil diaktivasi ke Mobile tanpa duplikasi data, poin kasir tetap utuh, dan referral terhubung sempurna.");

            // -------------------------------------------------------------
            // SKENARIO 2: TRANSAKSI CHECKOUT MOBILE & KOMISI HOLD H+7
            // -------------------------------------------------------------
            $this->newLine();
            $this->warn("📌 [SKENARIO 2] Pengujian Perhitungan Komisi Referral & Masa Tahan (Hold H+7)...");

            $buId = BusinessUnit::first()?->id ?? 1;

            // Buat pesanan mobile Budi senilai Rp 5.000.000
            $order1 = Order::create([
                'user_id' => $claimedUser->id,
                'business_unit_id' => $buId,
                'order_number' => "ORD-TEST-{$testSuffix}-1",
                'total_amount' => 5000000,
                'grand_total' => 5000000,
                'order_status' => 'COMPLETED',
                'shipping_address_snapshot' => ['address' => 'Jl. Pengayoman No. 12', 'city' => 'Makassar'],
            ]);
            $testOrders[] = $order1->id;

            $commission1 = $referralService->recordOrderCommission($order1);

            if (!$commission1) {
                throw new Exception("FAIL: Komisi tidak terhitung / return null!");
            }
            if ($commission1->status !== 'PENDING') {
                throw new Exception("FAIL: Status komisi baru seharusnya PENDING (Hold), didapat: {$commission1->status}");
            }
            if ($commission1->commission_amount <= 0) {
                throw new Exception("FAIL: Nominal komisi bernilai nol!");
            }

            $wallet1 = $referralService->getWalletSummary($referrer);
            $this->line("  ✓ Komisi dicatat: <info>Rp " . number_format($commission1->commission_amount, 0, ',', '.') . "</info> (Hold Until: " . $commission1->hold_until->format('d M Y') . ")");
            $this->line("  ✓ Saldo Pengundang saat ini: <comment>Tersedia: Rp " . number_format($wallet1['available_balance'], 0, ',', '.') . " | Ditahan (Hold): Rp " . number_format($wallet1['pending_balance'], 0, ',', '.') . "</comment>");

            if ($wallet1['available_balance'] > 0) {
                throw new Exception("FAIL: Saldo yang masih dalam masa hold tidak boleh masuk ke Available Balance!");
            }

            $this->info("  ✅ HASIL SKENARIO 2: SUKSES 100%! Komisi pesanan berhasil dicatat dengan status PENDING dan diisolasi di Saldo Tertahan (Hold H+7).");

            // -------------------------------------------------------------
            // SKENARIO 3: PROTEKSI ANTI-FRAUD (PEMBATALAN ORDER -> VOID)
            // -------------------------------------------------------------
            $this->newLine();
            $this->warn("📌 [SKENARIO 3] Pengujian Proteksi Anti-Fraud saat Pesanan Dibatalkan (Order Cancelled)...");

            // Batalkan pesanan order1
            $order1->update(['order_status' => 'CANCELLED']);
            $referralService->handleOrderStatusChanged($order1);

            $commission1->refresh();
            if ($commission1->status !== 'VOID') {
                throw new Exception("FAIL: Status komisi seharusnya otomatis VOID saat pesanan dibatalkan!");
            }

            $walletAfterCancel = $referralService->getWalletSummary($referrer);
            $this->line("  ✓ Status Komisi Pesanan Dibatalkan: <info>{$commission1->status}</info>");
            $this->line("  ✓ Saldo Ditahan Pengundang setelah Batal: <comment>Rp " . number_format($walletAfterCancel['pending_balance'], 0, ',', '.') . "</comment>");

            if ($walletAfterCancel['pending_balance'] != 0) {
                throw new Exception("FAIL: Saldo ditahan seharusnya kembali menjadi 0!");
            }

            $this->info("  ✅ HASIL SKENARIO 3: SUKSES 100%! Proteksi anti-fraud bekerja sempurna. Komisi seketika VOID dan saldo tertahan batal tanpa kebocoran dana.");

            // -------------------------------------------------------------
            // SKENARIO 4: PELEPASAN KOMISI (MATURITY) & PENARIKAN DANA FINANCE
            // -------------------------------------------------------------
            $this->newLine();
            $this->warn("📌 [SKENARIO 4] Pengujian Pelepasan Komisi Matured (H+7) & Alur Penarikan Dana (Withdrawal)...");

            // Buat pesanan kedua senilai Rp 10.000.000
            $order2 = Order::create([
                'user_id' => $claimedUser->id,
                'business_unit_id' => $buId,
                'order_number' => "ORD-TEST-{$testSuffix}-2",
                'total_amount' => 10000000,
                'grand_total' => 10000000,
                'order_status' => 'COMPLETED',
                'shipping_address_snapshot' => ['address' => 'Jl. Pengayoman No. 12', 'city' => 'Makassar'],
            ]);
            $testOrders[] = $order2->id;

            $commission2 = $referralService->recordOrderCommission($order2);

            // Simulasi waktu berlalu melewati masa hold H+7
            $commission2->update(['hold_until' => now()->subMinutes(10)]);

            // Jalankan pelepasan komisi otomatis
            $releasedCount = $referralService->releaseMaturedCommissions();
            $this->line("  ✓ Eksekusi scheduler release-holds melepaskan: <info>{$releasedCount} komisi</info>");

            $commission2->refresh();
            if ($commission2->status !== 'APPROVED') {
                throw new Exception("FAIL: Komisi matured seharusnya berstatus APPROVED!");
            }

            $walletMatured = $referralService->getWalletSummary($referrer);
            $this->line("  ✓ Saldo Siap Ditarik Pengundang: <info>Rp " . number_format($walletMatured['available_balance'], 0, ',', '.') . "</info>");

            if ($walletMatured['available_balance'] < $commission2->commission_amount) {
                throw new Exception("FAIL: Saldo siap ditarik tidak bertambah setelah komisi matured!");
            }

            // Buat Rekening Bank untuk Pengundang
            $bankAcc = UserBankAccount::create([
                'user_id' => $referrer->id,
                'bank_name' => 'BCA',
                'account_number' => '8881234567',
                'account_name' => 'Mitra Pengundang',
                'is_primary' => true,
            ]);

            // Pengundang mengajukan penarikan dana
            $withdrawAmount = $walletMatured['available_balance'];
            $withdrawal = $referralService->requestWithdrawal($referrer, $bankAcc->id, $withdrawAmount);

            $this->line("  ✓ Pengajuan Penarikan Dibuat: <comment>Rp " . number_format($withdrawal->amount, 0, ',', '.') . " ke BCA 8881234567</comment> (Status: {$withdrawal->status})");

            // Cek saldo setelah request withdraw (saldo harus ter-reserve / 0)
            $walletAfterRequest = $referralService->getWalletSummary($referrer);
            if ($walletAfterRequest['available_balance'] != 0) {
                throw new Exception("FAIL: Saldo tidak terkunci saat penarikan pending!");
            }

            // Simulasi Admin Finance Menyetujui Transfer
            $referralService->approveWithdrawal(
                $withdrawal,
                'https://tokopon.id/storage/dummy-proof.jpg',
                'No. Ref BCA: 981729381',
                $referrer->id
            );

            $withdrawal->refresh();
            if ($withdrawal->status !== 'TRANSFERRED') {
                throw new Exception("FAIL: Status penarikan seharusnya TRANSFERRED setelah di-approve finance!");
            }

            $this->line("  ✓ Persetujuan Finance Selesai: <info>Status: {$withdrawal->status} (Bukti: {$withdrawal->proof_attachment_url})</info>");

            $this->info("  ✅ HASIL SKENARIO 4: SUKSES 100%! Alur pelepasan komisi, pengajuan penarikan dana, penguncian saldo, hingga transfer finance tervalidasi sempurna.");

            $this->newLine();
            $this->info("================================================================");
            $this->info("🎉 SELURUH SKENARIO PENGUJIAN TAHAP 3 BERHASIL DILALUI DENGAN SKOR 100%!");
            $this->info("================================================================");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("❌ PENGUJIAN GAGAL: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        } finally {
            // Bersihkan data pengujian
            $this->newLine();
            $this->line("🧹 Membersihkan data dummy pengujian...");
            if (!empty($testOrders)) {
                ReferralCommission::whereIn('order_id', $testOrders)->delete();
                Order::whereIn('id', $testOrders)->delete();
            }
            if (!empty($testUsers)) {
                CommissionWithdrawal::whereIn('user_id', $testUsers)->delete();
                UserBankAccount::whereIn('user_id', $testUsers)->delete();
                UserReferralCode::whereIn('user_id', $testUsers)->delete();
                LoyaltyAccount::whereIn('user_id', $testUsers)->delete();
                UserProfile::whereIn('user_id', $testUsers)->delete();
                User::whereIn('id', $testUsers)->delete();
            }
            $this->line("  ✓ Database bersih kembali.");
        }
    }
}