<?php

namespace App\Services;

use App\Models\CommissionWithdrawal;
use App\Models\Order;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserReferralCode;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralService
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Cek apakah sistem referral sedang aktif secara global.
     */
    public function isReferralActive(): bool
    {
        return filter_var($this->settingService->get('referral_is_active', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Mendapatkan atau membuat kode referral unik untuk user.
     */
    public function getOrCreateReferralCode(User $user): string
    {
        if (!empty($user->referral_code)) {
            // Pastikan ada di tabel user_referral_codes
            UserReferralCode::firstOrCreate(
                ['user_id' => $user->id],
                ['code' => $user->referral_code, 'is_active' => true]
            );
            return $user->referral_code;
        }

        // Generate kode unik berbasis nama atau token pendek
        $cleanName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $user->name));
        $prefix = substr($cleanName, 0, 4);
        if (strlen($prefix) < 3) {
            $prefix = 'TKP';
        }

        do {
            $candidate = $prefix . strtoupper(Str::random(4));
        } while (
            User::where('referral_code', $candidate)->exists() ||
            UserReferralCode::where('code', $candidate)->exists()
        );

        $user->update(['referral_code' => $candidate]);

        UserReferralCode::firstOrCreate(
            ['user_id' => $user->id],
            ['code' => $candidate, 'is_active' => true]
        );

        return $candidate;
    }

    /**
     * Validasi kode referral yang diinput.
     */
    public function validateCode(?string $code, ?User $currentUser = null): array
    {
        if (empty($code)) {
            return [
                'valid' => false,
                'message' => 'Kode referral kosong.',
            ];
        }

        $cleanCode = strtoupper(trim($code));

        $record = UserReferralCode::with('user')
            ->where('code', $cleanCode)
            ->where('is_active', true)
            ->first();

        $referrer = $record?->user ?: User::where('referral_code', $cleanCode)->first();

        if (!$referrer) {
            return [
                'valid' => false,
                'message' => 'Kode referral tidak ditemukan atau sudah tidak aktif.',
            ];
        }

        if ($currentUser && $currentUser->id === $referrer->id) {
            return [
                'valid' => false,
                'message' => 'Anda tidak dapat menggunakan kode referral Anda sendiri.',
            ];
        }

        return [
            'valid' => true,
            'code' => $cleanCode,
            'referrer_id' => $referrer->id,
            'referrer_name' => $referrer->name,
            'message' => "Kode referral dari {$referrer->name} aktif.",
        ];
    }

    /**
     * Smart Registration & Claim Member POS:
     * Menangani user baru murni maupun member POS eksisting yang baru pertama kali mengaktifkan Tokopon Mobile.
     */
    public function registerOrClaimMember(array $payload): User
    {
        $rawPhone = (string) ($payload['phone'] ?? '');
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (empty($cleanPhone) || strlen($cleanPhone) < 9) {
            throw new Exception("Nomor WhatsApp tidak valid (minimal 9 digit angka).");
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }

        $name = trim((string) ($payload['name'] ?? 'Pelanggan'));
        $password = (string) ($payload['password'] ?? '');
        if (strlen($password) < 6) {
            throw new Exception("Kata sandi minimal 6 karakter.");
        }

        $referralCodeInput = trim((string) ($payload['referral_code'] ?? ''));

        // Cek variasi nomor HP (08... dan 628...)
        $phoneVariations = [$cleanPhone];
        if (str_starts_with($cleanPhone, '0')) {
            $phoneVariations[] = '62' . substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '62')) {
            $phoneVariations[] = '0' . substr($cleanPhone, 2);
        }

        return DB::transaction(function () use (
            $phoneVariations,
            $cleanPhone,
            $email,
            $name,
            $password,
            $payload,
            $referralCodeInput
        ) {
            // Cari apakah nomor HP sudah ada di user_profiles
            $existingProfile = UserProfile::with('user')
                ->whereIn('phone_number', $phoneVariations)
                ->first();

            $existingUser = $existingProfile?->user;

            // KASUS 1: Member POS Eksisting (Klaim Akun Pertama Kali di Mobile)
            if ($existingUser) {
                // Cek apakah sudah pernah aktif di mobile app
                if ($existingUser->mobile_activated_at !== null) {
                    throw new Exception("Nomor WhatsApp ini sudah aktif di Tokopon Mobile. Silakan langsung masuk (login).");
                }

                // Cek apakah email baru bentrok dengan user lain
                $emailConflict = User::where('email', $email)
                    ->where('id', '!=', $existingUser->id)
                    ->exists();
                if ($emailConflict) {
                    throw new Exception("Email {$email} sudah digunakan oleh akun lain. Silakan gunakan email berbeda.");
                }

                // Update data user: Pasang password baru dan email asli
                $updateData = [
                    'email' => $email,
                    'password' => Hash::make($password),
                    'registration_channel' => 'MOBILE_CLAIM',
                    'mobile_activated_at' => now(),
                ];

                if (!empty($name) && ($existingUser->name === 'Pelanggan' || empty($existingUser->name))) {
                    $updateData['name'] = $name;
                }

                // Hubungkan kode referral jika disediakan dan belum pernah punya referrer
                if (!empty($referralCodeInput) && empty($existingUser->referred_by_id)) {
                    $val = $this->validateCode($referralCodeInput, $existingUser);
                    if ($val['valid']) {
                        $updateData['referred_by_id'] = $val['referrer_id'];

                        // Track total registrasi pada kode referral
                        UserReferralCode::where('code', $val['code'])->increment('total_registered');
                    }
                }

                $existingUser->update($updateData);

                // Update profile
                $existingProfile->update([
                    'full_name' => $existingUser->name,
                    'domisili' => $payload['address'] ?? $existingProfile->domisili,
                ]);

                // Pastikan role customer aktif
                if (method_exists($existingUser, 'hasRole') && !$existingUser->hasRole('customer')) {
                    $existingUser->assignRole('customer');
                }

                // Terbitkan kode referral untuk akun ini
                $this->getOrCreateReferralCode($existingUser);

                Log::info("Akun member POS #{$existingUser->id} ({$cleanPhone}) berhasil diklaim & diaktivasi di Tokopon Mobile.");

                return $existingUser;
            }

            // KASUS 2: Pure Brand New Customer
            $emailTaken = User::where('email', $email)->exists();
            if ($emailTaken) {
                throw new Exception("Email {$email} sudah terdaftar. Silakan gunakan email lain atau masuk.");
            }

            $referredById = null;
            if (!empty($referralCodeInput)) {
                $val = $this->validateCode($referralCodeInput);
                if ($val['valid']) {
                    $referredById = $val['referrer_id'];
                    UserReferralCode::where('code', $val['code'])->increment('total_registered');
                }
            }

            $newUser = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'referred_by_id' => $referredById,
                'registration_channel' => 'MOBILE',
                'mobile_activated_at' => now(),
            ]);

            UserProfile::create([
                'user_id' => $newUser->id,
                'full_name' => $newUser->name,
                'phone_number' => $cleanPhone,
                'domisili' => $payload['address'] ?? null,
            ]);

            // Buat Loyalty Account
            try {
                $loyaltyService = app(\App\Services\LoyaltyService::class);
                $loyaltyService->getOrCreateAccount($cleanPhone, $newUser->name, $newUser);
            } catch (\Throwable $e) {
                Log::warning("Gagal membuat loyalty account saat registrasi: " . $e->getMessage());
            }

            if (method_exists($newUser, 'assignRole')) {
                $newUser->assignRole('customer');
            }

            // Terbitkan kode referral untuk akun baru
            $this->getOrCreateReferralCode($newUser);

            return $newUser;
        });
    }

    /**
     * Hitung & catat komisi referral saat order checkout selesai dibuat di mobile.
     */
    public function recordOrderCommission(Order $order): ?ReferralCommission
    {
        if (!$this->isReferralActive()) {
            return null;
        }

        $buyerId = $order->user_id;
        if (!$buyerId) {
            return null;
        }

        $buyer = User::find($buyerId);
        $referrerId = $buyer?->referred_by_id;
        if (!$referrerId) {
            return null;
        }

        // Cek scope reward: FIRST_ORDER_ONLY vs ALL_ORDERS
        $rewardScope = $this->settingService->get('referral_reward_scope', 'FIRST_ORDER_ONLY');
        if ($rewardScope === 'FIRST_ORDER_ONLY') {
            $priorOrdersExist = Order::where('user_id', $buyerId)
                ->where('id', '!=', $order->id)
                ->whereIn('order_status', ['PAID', 'PROCESSING', 'SHIPPED', 'COMPLETED'])
                ->exists();

            if ($priorOrdersExist) {
                return null;
            }
        }

        // Hitung komisi dinamis
        $commissionType = strtoupper((string) $this->settingService->get('referral_commission_type', 'PERCENTAGE'));
        $commissionRate = (float) $this->settingService->get('referral_commission_rate', 1.0);
        $maxCap = (float) $this->settingService->get('referral_max_commission_cap', 100000);

        $orderAmount = (float) $order->total_amount;

        if ($commissionType === 'FIXED') {
            $commissionAmount = $commissionRate;
        } else {
            // PERCENTAGE
            $calculated = round(($orderAmount * $commissionRate) / 100, 2);
            $commissionAmount = ($maxCap > 0) ? min($calculated, $maxCap) : $calculated;
        }

        if ($commissionAmount <= 0) {
            return null;
        }

        $holdDays = (int) $this->settingService->get('referral_hold_days', 7);
        $holdUntil = now()->addDays($holdDays);

        $commission = ReferralCommission::create([
            'referrer_id' => $referrerId,
            'referee_id' => $buyerId,
            'order_id' => $order->id,
            'order_amount' => $orderAmount,
            'commission_type' => $commissionType,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'status' => 'PENDING',
            'hold_until' => $holdUntil,
            'notes' => "Komisi referral pesanan #{$order->order_number}",
        ]);

        // Update total converted orders pada kode pengundang
        $referrerUser = User::find($referrerId);
        if ($referrerUser && $referrerUser->referral_code) {
            UserReferralCode::where('code', $referrerUser->referral_code)->increment('total_converted_orders');
        }

        return $commission;
    }

    /**
     * Tangani perubahan status order untuk komisi (Listener).
     */
    public function handleOrderStatusChanged(Order $order): void
    {
        $status = strtoupper((string) $order->order_status);

        if ($status === 'CANCELLED') {
            ReferralCommission::where('order_id', $order->id)
                ->where('status', '!=', 'PAID')
                ->update([
                    'status' => 'VOID',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [Dibatalkan karena pesanan dibatalkan]')"),
                ]);
        } elseif (in_array($status, ['COMPLETED', 'PAID'], true)) {
            // Pastikan hold_until terisi
            $holdDays = (int) $this->settingService->get('referral_hold_days', 7);
            ReferralCommission::where('order_id', $order->id)
                ->where('status', 'PENDING')
                ->whereNull('hold_until')
                ->update([
                    'hold_until' => now()->addDays($holdDays),
                ]);
        }
    }

    /**
     * Ambil ringkasan dompet komisi & statistik referral user.
     */
    public function getWalletSummary(User $user): array
    {
        $referralCode = $this->getOrCreateReferralCode($user);

        // Komisi yang sudah cair (APPROVED atau PENDING yang sudah melewati hold_until)
        $approvedAmount = (float) ReferralCommission::where('referrer_id', $user->id)
            ->where(function ($q) {
                $q->where('status', 'APPROVED')
                  ->orWhere(function ($sub) {
                      $sub->where('status', 'PENDING')
                          ->whereNotNull('hold_until')
                          ->where('hold_until', '<=', now());
                  });
            })
            ->sum('commission_amount');

        // Total yang sedang/sudah ditarik
        $withdrawnOrPendingWithdrawal = (float) CommissionWithdrawal::where('user_id', $user->id)
            ->whereIn('status', ['PENDING', 'APPROVED', 'TRANSFERRED'])
            ->sum('amount');

        // Saldo riil yang bisa ditarik
        $availableBalance = max(0, $approvedAmount - $withdrawnOrPendingWithdrawal);

        // Komisi yang masih dalam masa penahanan (Hold H+7)
        $pendingBalance = (float) ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'PENDING')
            ->where(function ($q) {
                $q->whereNull('hold_until')
                  ->orWhere('hold_until', '>', now());
            })
            ->sum('commission_amount');

        // Total akumulasi komisi yang pernah didapat
        $totalEarned = (float) ReferralCommission::where('referrer_id', $user->id)
            ->where('status', '!=', 'VOID')
            ->sum('commission_amount');

        // Total sukses ditarik
        $totalWithdrawn = (float) CommissionWithdrawal::where('user_id', $user->id)
            ->where('status', 'TRANSFERRED')
            ->sum('net_amount');

        $totalFriends = User::where('referred_by_id', $user->id)->count();

        $minWithdrawal = (float) $this->settingService->get('referral_min_withdrawal', 50000);

        return [
            'referral_code' => $referralCode,
            'share_url' => "https://tokopon.id/ref/{$referralCode}",
            'available_balance' => $availableBalance,
            'pending_balance' => $pendingBalance,
            'total_earned' => $totalEarned,
            'total_withdrawn' => $totalWithdrawn,
            'total_friends' => $totalFriends,
            'min_withdrawal' => $minWithdrawal,
            'is_active' => $this->isReferralActive(),
            'commission_type' => $this->settingService->get('referral_commission_type', 'PERCENTAGE'),
            'commission_rate' => (float) $this->settingService->get('referral_commission_rate', 1.0),
            'max_commission_cap' => (float) $this->settingService->get('referral_max_commission_cap', 100000),
            'hold_days' => (int) $this->settingService->get('referral_hold_days', 7),
        ];
    }

    /**
     * Buat permintaan penarikan saldo komisi ke rekening bank.
     */
    public function requestWithdrawal(User $user, int $bankAccountId, float $amount): CommissionWithdrawal
    {
        $summary = $this->getWalletSummary($user);
        $available = $summary['available_balance'];
        $minWithdrawal = $summary['min_withdrawal'];

        if ($amount < $minWithdrawal) {
            throw new Exception("Minimal penarikan saldo komisi adalah Rp " . number_format($minWithdrawal, 0, ',', '.'));
        }

        if ($amount > $available) {
            throw new Exception("Saldo komisi yang tersedia tidak mencukupi (Tersedia: Rp " . number_format($available, 0, ',', '.') . ")");
        }

        // Pastikan rekening bank milik user
        $bankAccount = $user->bankAccounts()->find($bankAccountId);
        if (!$bankAccount) {
            throw new Exception("Rekening bank tujuan tidak ditemukan atau bukan milik akun Anda.");
        }

        return DB::transaction(function () use ($user, $bankAccount, $amount) {
            return CommissionWithdrawal::create([
                'user_id' => $user->id,
                'user_bank_account_id' => $bankAccount->id,
                'amount' => $amount,
                'admin_fee' => 0,
                'net_amount' => $amount,
                'status' => 'PENDING',
                'notes' => "Permohonan penarikan komisi ke {$bankAccount->bank_name} - {$bankAccount->account_number}",
            ]);
        });
    }
}