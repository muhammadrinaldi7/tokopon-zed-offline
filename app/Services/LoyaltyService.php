<?php

namespace App\Services;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Cek apakah sistem loyalty & poin sedang aktif secara global.
     */
    public function isActive(): bool
    {
        return filter_var($this->settingService->get('loyalty_is_active', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Normalisasi nomor telepon ke format standar (diawali 628...).
     */
    public function normalizePhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $clean = preg_replace('/[^0-9]/', '', (string) $phone);

        if (str_starts_with($clean, '08')) {
            return '628' . substr($clean, 2);
        }

        if (str_starts_with($clean, '8')) {
            return '628' . substr($clean, 1);
        }

        if (str_starts_with($clean, '628')) {
            return $clean;
        }

        return $clean;
    }

    /**
     * Ambil atau buat akun loyalty berdasarkan nomor HP pelanggan.
     */
    public function getOrCreateAccount(string $phone, ?string $name = null, ?User $user = null): ?LoyaltyAccount
    {
        $cleanPhone = $this->normalizePhone($phone);
        if (empty($cleanPhone) || strlen($cleanPhone) < 9) {
            return null;
        }

        $account = LoyaltyAccount::where('phone_number', $cleanPhone)->first();

        if (!$account) {
            $account = LoyaltyAccount::create([
                'phone_number' => $cleanPhone,
                'user_id' => $user?->id,
                'customer_name' => $name ?: $user?->name ?: 'Pelanggan Tokopon',
                'points_balance' => 0,
                'lifetime_points' => 0,
                'total_spend' => 0,
                'tier' => 'BRONZE',
            ]);
        } else {
            // Tautkan user_id jika sebelumnya belum terhubung dan sekarang user sudah ada
            $updates = [];
            if (!$account->user_id && $user) {
                $updates['user_id'] = $user->id;
            }
            if ((!$account->customer_name || $account->customer_name === 'Pelanggan Tokopon') && $name) {
                $updates['customer_name'] = $name;
            }
            if (!empty($updates)) {
                $account->update($updates);
            }
        }

        return $account;
    }

    /**
     * Hitung poin yang bisa didapatkan dari nilai belanja subtotal.
     * Default: Setiap kelipatan Rp 10.000 = 1 Poin.
     */
    public function calculateEarnablePoints(float $subtotal): int
    {
        $ratio = (float) $this->settingService->get('loyalty_earn_ratio', 10000);
        if ($ratio <= 0 || $subtotal <= 0) {
            return 0;
        }

        return (int) floor($subtotal / $ratio);
    }

    /**
     * Hitung nilai rupiah diskon dari sejumlah poin yang ditukarkan.
     * Default: 1 Poin = Rp 100.
     */
    public function calculatePointDiscount(int $points): float
    {
        $pointValue = (float) $this->settingService->get('loyalty_point_value', 100);
        if ($points <= 0 || $pointValue <= 0) {
            return 0;
        }

        return (float) ($points * $pointValue);
    }

    /**
     * Hitung jumlah poin yang dibutuhkan untuk menghasilkan diskon sebesar nominal rupiah.
     */
    public function calculatePointsNeededForDiscount(float $discountAmount): int
    {
        $pointValue = (float) $this->settingService->get('loyalty_point_value', 100);
        if ($discountAmount <= 0 || $pointValue <= 0) {
            return 0;
        }

        return (int) ceil($discountAmount / $pointValue);
    }

    /**
     * Tambahkan poin ke akun pelanggan saat transaksi selesai (COMPLETED di Mobile / POS).
     */
    public function earnPoints(Order $order): ?LoyaltyTransaction
    {
        if (!$this->isActive()) {
            return null;
        }

        // Hindari perolehan ganda untuk order yang sama
        $alreadyEarned = LoyaltyTransaction::where('order_id', $order->id)
            ->where('type', 'EARN')
            ->exists();

        if ($alreadyEarned) {
            return null;
        }

        // Tentukan nomor HP pelanggan dari order
        $phone = $this->extractCustomerPhoneFromOrder($order);
        if (empty($phone)) {
            return null;
        }

        $customerName = $this->extractCustomerNameFromOrder($order);
        $account = $this->getOrCreateAccount($phone, $customerName, $order->user);
        if (!$account) {
            return null;
        }

        // Hitung nilai belanja bersih yang berhak mendapatkan poin
        $netSubtotal = max(0, (float) $order->total_amount - (float) ($order->discount_amount ?? 0) - (float) ($order->loyalty_discount_amount ?? 0));
        $pointsToEarn = $this->calculateEarnablePoints($netSubtotal);

        if ($pointsToEarn <= 0) {
            return null;
        }

        return DB::transaction(function () use ($account, $order, $pointsToEarn, $netSubtotal) {
            /** @var LoyaltyAccount $lockedAccount */
            $lockedAccount = LoyaltyAccount::where('id', $account->id)->lockForUpdate()->first();

            $before = $lockedAccount->points_balance;
            $after = $before + $pointsToEarn;

            $lockedAccount->points_balance = $after;
            $lockedAccount->lifetime_points += $pointsToEarn;
            $lockedAccount->total_spend += $netSubtotal;
            $lockedAccount->save();

            $channel = ($order->order_channel === 'POS') ? 'POS' : 'MOBILE_APP';

            $tx = LoyaltyTransaction::create([
                'loyalty_account_id' => $lockedAccount->id,
                'order_id' => $order->id,
                'channel' => $channel,
                'type' => 'EARN',
                'points' => $pointsToEarn,
                'balance_before' => $before,
                'balance_after' => $after,
                'discount_amount' => 0,
                'description' => "Perolehan {$pointsToEarn} poin dari transaksi #{$order->order_number}",
                'created_by' => $order->handled_by,
            ]);

            $order->update(['loyalty_points_earned' => $pointsToEarn]);

            $this->updateTier($lockedAccount);

            return $tx;
        });
    }

    /**
     * Tukarkan (Redeem) poin menjadi potongan diskon pada pesanan.
     */
    public function redeemPoints(Order $order, int $pointsToRedeem, ?User $actor = null): ?LoyaltyTransaction
    {
        if (!$this->isActive() || $pointsToRedeem <= 0) {
            return null;
        }

        $minPoints = (int) $this->settingService->get('loyalty_min_redeem_points', 50);
        if ($pointsToRedeem < $minPoints) {
            throw new Exception("Minimal penukaran poin adalah {$minPoints} poin.");
        }

        $phone = $this->extractCustomerPhoneFromOrder($order);
        if (empty($phone)) {
            throw new Exception("Nomor HP pelanggan tidak ditemukan untuk penukaran poin.");
        }

        $account = $this->getOrCreateAccount($phone, null, $order->user);
        if (!$account || !$account->canRedeem($pointsToRedeem)) {
            $sisa = $account ? $account->points_balance : 0;
            throw new Exception("Saldo poin tidak mencukupi (Tersedia: {$sisa} poin, Diminta: {$pointsToRedeem} poin).");
        }

        $discountAmount = $this->calculatePointDiscount($pointsToRedeem);

        return DB::transaction(function () use ($account, $order, $pointsToRedeem, $discountAmount, $actor) {
            /** @var LoyaltyAccount $lockedAccount */
            $lockedAccount = LoyaltyAccount::where('id', $account->id)->lockForUpdate()->first();

            if ($lockedAccount->points_balance < $pointsToRedeem) {
                throw new Exception("Saldo poin tidak mencukupi saat proses penguncian.");
            }

            $before = $lockedAccount->points_balance;
            $after = $before - $pointsToRedeem;

            $lockedAccount->points_balance = $after;
            $lockedAccount->save();

            $channel = ($order->order_channel === 'POS') ? 'POS' : 'MOBILE_APP';

            $tx = LoyaltyTransaction::create([
                'loyalty_account_id' => $lockedAccount->id,
                'order_id' => $order->id,
                'channel' => $channel,
                'type' => 'REDEEM',
                'points' => -$pointsToRedeem,
                'balance_before' => $before,
                'balance_after' => $after,
                'discount_amount' => $discountAmount,
                'description' => "Penukaran {$pointsToRedeem} poin (Diskon Rp " . number_format($discountAmount, 0, ',', '.') . ") pada pesanan #{$order->order_number}",
                'created_by' => $actor?->id ?? $order->handled_by,
            ]);

            $order->update([
                'loyalty_points_redeemed' => $pointsToRedeem,
                'loyalty_discount_amount' => $discountAmount,
            ]);

            return $tx;
        });
    }

    /**
     * Kembalikan poin yang ditukarkan atau batalkan poin yang didapat jika order dibatalkan/refund.
     */
    public function refundPoints(Order $order): void
    {
        // 1. Kembalikan poin yang pernah di-redeem oleh pelanggan
        if ($order->loyalty_points_redeemed > 0) {
            $redeemTx = LoyaltyTransaction::where('order_id', $order->id)
                ->where('type', 'REDEEM')
                ->first();

            $alreadyRefunded = LoyaltyTransaction::where('order_id', $order->id)
                ->where('type', 'REFUND')
                ->exists();

            if ($redeemTx && !$alreadyRefunded) {
                DB::transaction(function () use ($redeemTx, $order) {
                    $account = LoyaltyAccount::where('id', $redeemTx->loyalty_account_id)->lockForUpdate()->first();
                    if ($account) {
                        $pointsToReturn = abs($redeemTx->points);
                        $before = $account->points_balance;
                        $after = $before + $pointsToReturn;

                        $account->points_balance = $after;
                        $account->save();

                        LoyaltyTransaction::create([
                            'loyalty_account_id' => $account->id,
                            'order_id' => $order->id,
                            'channel' => $redeemTx->channel,
                            'type' => 'REFUND',
                            'points' => $pointsToReturn,
                            'balance_before' => $before,
                            'balance_after' => $after,
                            'discount_amount' => $redeemTx->discount_amount,
                            'description' => "Pengembalian {$pointsToReturn} poin karena pesanan #{$order->order_number} dibatalkan",
                        ]);
                    }
                });
            }
        }

        // 2. Tarik kembali perolehan poin jika order dibatalkan setelah status earned
        if ($order->loyalty_points_earned > 0) {
            $earnTx = LoyaltyTransaction::where('order_id', $order->id)
                ->where('type', 'EARN')
                ->first();

            if ($earnTx) {
                DB::transaction(function () use ($earnTx, $order) {
                    $account = LoyaltyAccount::where('id', $earnTx->loyalty_account_id)->lockForUpdate()->first();
                    if ($account) {
                        $pointsToDeduct = $earnTx->points;
                        $before = $account->points_balance;
                        $after = max(0, $before - $pointsToDeduct);

                        $account->points_balance = $after;
                        $account->lifetime_points = max(0, $account->lifetime_points - $pointsToDeduct);
                        $account->save();

                        LoyaltyTransaction::create([
                            'loyalty_account_id' => $account->id,
                            'order_id' => $order->id,
                            'channel' => $earnTx->channel,
                            'type' => 'ADJUSTMENT',
                            'points' => -$pointsToDeduct,
                            'balance_before' => $before,
                            'balance_after' => $after,
                            'description' => "Penarikan {$pointsToDeduct} poin perolehan karena pesanan #{$order->order_number} dibatalkan",
                        ]);
                    }
                });
            }
        }
    }

    /**
     * Hitung status tier membership berdasarkan akumulasi nominal belanja.
     */
    public function evaluateTier(float $spend): string
    {
        $silverThreshold = (float) ($this->settingService->get('loyalty_silver_min_spend') ?? $this->settingService->get('loyalty_tier_silver_spend', 5000000));
        $goldThreshold = (float) ($this->settingService->get('loyalty_gold_min_spend') ?? $this->settingService->get('loyalty_tier_gold_spend', 15000000));
        $platinumThreshold = (float) ($this->settingService->get('loyalty_platinum_min_spend') ?? $this->settingService->get('loyalty_tier_platinum_spend', 50000000));

        if ($spend >= $platinumThreshold) {
            return 'PLATINUM';
        } elseif ($spend >= $goldThreshold) {
            return 'GOLD';
        } elseif ($spend >= $silverThreshold) {
            return 'SILVER';
        }

        return 'BRONZE';
    }

    /**
     * Evaluasi dan sinkronisasi Membership Tier berdasarkan akumulasi total belanja.
     */
    public function updateTier(LoyaltyAccount $account): void
    {
        $newTier = $this->evaluateTier((float) $account->total_spend);

        if ($account->tier !== $newTier) {
            $account->tier = $newTier;
            $account->tier_updated_at = now();
            $account->save();
        }
    }

    /**
     * Dapatkan ringkasan loyalty member (saldo poin, tier, progress, nilai rupiah) untuk Mobile App / POS.
     */
    public function getSummary(string $phone): array
    {
        $account = $this->getOrCreateAccount($phone);
        if (!$account) {
            return [
                'points_balance' => 0,
                'point_cash_value' => 0,
                'lifetime_points' => 0,
                'total_spend' => 0,
                'tier' => 'BRONZE',
                'next_tier' => 'SILVER',
                'next_tier_progress_pct' => 0,
                'spend_needed_for_next_tier' => 5000000,
            ];
        }

        $silverThreshold = (float) $this->settingService->get('loyalty_silver_min_spend', 5000000);
        $goldThreshold = (float) $this->settingService->get('loyalty_gold_min_spend', 15000000);
        $platinumThreshold = (float) $this->settingService->get('loyalty_platinum_min_spend', 50000000);

        $spend = (float) $account->total_spend;
        $tier = $account->tier;
        $nextTier = 'SILVER';
        $target = $silverThreshold;

        if ($tier === 'SILVER') {
            $nextTier = 'GOLD';
            $target = $goldThreshold;
        } elseif ($tier === 'GOLD') {
            $nextTier = 'PLATINUM';
            $target = $platinumThreshold;
        } elseif ($tier === 'PLATINUM') {
            $nextTier = 'PLATINUM';
            $target = $platinumThreshold;
        }

        $needed = max(0, $target - $spend);
        $progressPct = ($target > 0) ? min(100, round(($spend / $target) * 100)) : 100;

        return [
            'id' => $account->id,
            'phone_number' => $account->phone_number,
            'customer_name' => $account->customer_name,
            'points_balance' => (int) $account->points_balance,
            'point_cash_value' => $this->calculatePointDiscount((int) $account->points_balance),
            'lifetime_points' => (int) $account->lifetime_points,
            'total_spend' => $spend,
            'tier' => $tier,
            'next_tier' => $nextTier,
            'next_tier_progress_pct' => $progressPct,
            'spend_needed_for_next_tier' => $needed,
            'min_redeem_points' => (int) $this->settingService->get('loyalty_min_redeem_points', 50),
        ];
    }

    /**
     * Ekstrak nomor HP pelanggan dari entitas Order.
     */
    protected function extractCustomerPhoneFromOrder(Order $order): ?string
    {
        // 1. Coba dari shipping snapshot (order mobile)
        if (!empty($order->shipping_address_snapshot) && is_array($order->shipping_address_snapshot)) {
            $p = $order->shipping_address_snapshot['phone'] ?? null;
            if (!empty($p)) {
                return $p;
            }
        }

        // 2. Coba dari profil user
        if ($order->user && $order->user->profile && !empty($order->user->profile->phone_number)) {
            return $order->user->profile->phone_number;
        }

        // 3. Coba dari email user jika berupa nomor HP (@tokopon.internal)
        if ($order->user && str_contains($order->user->email, '@tokopon.internal')) {
            return explode('@', $order->user->email)[0];
        }

        return null;
    }

    /**
     * Ekstrak nama pelanggan dari entitas Order.
     */
    protected function extractCustomerNameFromOrder(Order $order): ?string
    {
        if (!empty($order->shipping_address_snapshot) && is_array($order->shipping_address_snapshot)) {
            $n = $order->shipping_address_snapshot['name'] ?? $order->shipping_address_snapshot['customer_name'] ?? null;
            if (!empty($n)) {
                return $n;
            }
        }

        return $order->user?->name;
    }

    /**
     * Dapatkan konfigurasi pengaturan sistem loyalty.
     */
    public function getSettings(): array
    {
        return [
            'loyalty_is_active' => filter_var($this->settingService->get('loyalty_is_active', true), FILTER_VALIDATE_BOOLEAN),
            'loyalty_earn_ratio' => (int) $this->settingService->get('loyalty_earn_ratio', 10000),
            'loyalty_point_value' => (int) $this->settingService->get('loyalty_point_value', 100),
            'loyalty_min_redeem_points' => (int) $this->settingService->get('loyalty_min_redeem_points', 50),
            'loyalty_tier_silver_spend' => (float) $this->settingService->get('loyalty_tier_silver_spend', 5000000),
            'loyalty_tier_gold_spend' => (float) $this->settingService->get('loyalty_tier_gold_spend', 15000000),
            'loyalty_tier_platinum_spend' => (float) $this->settingService->get('loyalty_tier_platinum_spend', 50000000),
        ];
    }

    /**
     * Simpan konfigurasi pengaturan sistem loyalty.
     */
    public function saveSettings(array $settings): void
    {
        if (isset($settings['loyalty_is_active'])) {
            $this->settingService->set('loyalty_is_active', (bool) $settings['loyalty_is_active']);
        }
        if (isset($settings['loyalty_earn_ratio'])) {
            $this->settingService->set('loyalty_earn_ratio', max(1000, (int) $settings['loyalty_earn_ratio']));
        }
        if (isset($settings['loyalty_point_value'])) {
            $this->settingService->set('loyalty_point_value', max(1, (int) $settings['loyalty_point_value']));
        }
        if (isset($settings['loyalty_min_redeem_points'])) {
            $this->settingService->set('loyalty_min_redeem_points', max(0, (int) $settings['loyalty_min_redeem_points']));
        }
        if (isset($settings['loyalty_tier_silver_spend'])) {
            $this->settingService->set('loyalty_tier_silver_spend', (float) $settings['loyalty_tier_silver_spend']);
        }
        if (isset($settings['loyalty_tier_gold_spend'])) {
            $this->settingService->set('loyalty_tier_gold_spend', (float) $settings['loyalty_tier_gold_spend']);
        }
        if (isset($settings['loyalty_tier_platinum_spend'])) {
            $this->settingService->set('loyalty_tier_platinum_spend', (float) $settings['loyalty_tier_platinum_spend']);
        }
    }

    /**
     * Dapatkan status metrik sinkronisasi pemetaan akun loyalty vs user lama secara efisien (database subqueries).
     */
    public function getSyncStatus(): array
    {
        $totalUsers = User::count();
        $totalProfilesWithPhone = \App\Models\UserProfile::whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->count();
        $totalLoyaltyAccounts = LoyaltyAccount::count();

        // 1. Profil belum terpetakan: query langsung di database via subquery (bebas timeout & tanpa N+1)
        $unmappedProfilesQuery = \App\Models\UserProfile::whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->whereNotIn('user_id', function ($sub) {
                $sub->select('user_id')->from('loyalty_accounts')->whereNotNull('user_id');
            });

        $unmappedCount = $unmappedProfilesQuery->count();

        // 2. Akun internal belanja tamu yang belum terpetakan (08xxx@tokopon.internal)
        $unmappedInternalCount = User::where('email', 'like', '%@tokopon.internal')
            ->whereNotIn('id', function ($sub) {
                $sub->select('user_id')->from('loyalty_accounts')->whereNotNull('user_id');
            })
            ->count();

        $totalUnmapped = $unmappedCount + $unmappedInternalCount;

        // Ambil sampel maksimal 15 baris untuk preview antrean tanpa membebani memory
        $unmappedSamples = (clone $unmappedProfilesQuery)
            ->with('user')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(function ($p) {
                return [
                    'user_id' => $p->user_id,
                    'name' => $p->full_name ?: ($p->user?->name ?? 'Pelanggan'),
                    'raw_phone' => $p->phone_number,
                    'normalized_phone' => $this->normalizePhone($p->phone_number),
                    'email' => $p->user?->email,
                    'registered_at' => $p->user?->created_at?->format('d M Y') ?? '-',
                ];
            })
            ->toArray();

        return [
            'total_users' => $totalUsers,
            'total_profiles_with_phone' => $totalProfilesWithPhone,
            'total_loyalty_accounts' => $totalLoyaltyAccounts,
            'unmapped_count' => $totalUnmapped,
            'unmapped_samples' => $unmappedSamples,
        ];
    }

    /**
     * Cari pengguna lama secara spesifik berdasarkan nomor HP, email, atau nama untuk dipetakan ke Loyalty.
     */
    public function searchUsersForMapping(string $search, int $limit = 25): array
    {
        $term = trim($search);
        if (empty($term)) {
            return [];
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $term);

        // Cari di UserProfile dan User
        $profiles = \App\Models\UserProfile::with(['user.loyaltyAccount'])
            ->where(function ($q) use ($term, $cleanPhone) {
                $q->where('phone_number', 'like', "%{$term}%")
                  ->orWhere('full_name', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', "%{$term}%")
                         ->orWhere('email', 'like', "%{$term}%");
                  });

                if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                    $q->orWhere('phone_number', 'like', "%{$cleanPhone}%");
                    if (str_starts_with($cleanPhone, '08')) {
                        $alt = '628' . substr($cleanPhone, 2);
                        $q->orWhere('phone_number', 'like', "%{$alt}%");
                    } elseif (str_starts_with($cleanPhone, '628')) {
                        $alt = '08' . substr($cleanPhone, 3);
                        $q->orWhere('phone_number', 'like', "%{$alt}%");
                    }
                }
            })
            ->limit($limit)
            ->get();

        $results = [];
        $foundUserIds = [];

        foreach ($profiles as $p) {
            $user = $p->user;
            $userId = $p->user_id;
            $foundUserIds[] = $userId;

            $normPhone = $this->normalizePhone($p->phone_number);
            $loyaltyAcc = $user?->loyaltyAccount;
            if (!$loyaltyAcc && $normPhone) {
                $loyaltyAcc = LoyaltyAccount::where('phone_number', $normPhone)->first();
            }

            $results[] = [
                'user_id' => $userId,
                'name' => $p->full_name ?: ($user?->name ?? 'Pelanggan'),
                'raw_phone' => $p->phone_number,
                'normalized_phone' => $normPhone,
                'email' => $user?->email,
                'registered_at' => $user?->created_at?->format('d M Y') ?? '-',
                'is_mapped' => (bool) $loyaltyAcc,
                'tier' => $loyaltyAcc?->tier,
                'points_balance' => (int) ($loyaltyAcc?->points_balance ?? 0),
            ];
        }

        // Cari juga pengguna yang mungkin belum memiliki profile tapi punya email atau guest internal user
        if (count($results) < $limit) {
            $remainingLimit = $limit - count($results);
            $usersDirect = User::with('loyaltyAccount')
                ->whereNotIn('id', $foundUserIds)
                ->where(function ($q) use ($term) {
                    $q->where('email', 'like', "%{$term}%")
                      ->orWhere('name', 'like', "%{$term}%");
                })
                ->limit($remainingLimit)
                ->get();

            foreach ($usersDirect as $u) {
                $rawPhone = '';
                if (str_contains($u->email, '@tokopon.internal')) {
                    $rawPhone = explode('@', $u->email)[0];
                }
                $normPhone = $this->normalizePhone($rawPhone);
                $loyaltyAcc = $u->loyaltyAccount;
                if (!$loyaltyAcc && $normPhone) {
                    $loyaltyAcc = LoyaltyAccount::where('phone_number', $normPhone)->first();
                }

                $results[] = [
                    'user_id' => $u->id,
                    'name' => $u->name,
                    'raw_phone' => $rawPhone ?: '-',
                    'normalized_phone' => $normPhone ?: '-',
                    'email' => $u->email,
                    'registered_at' => $u->created_at?->format('d M Y') ?? '-',
                    'is_mapped' => (bool) $loyaltyAcc,
                    'tier' => $loyaltyAcc?->tier,
                    'points_balance' => (int) ($loyaltyAcc?->points_balance ?? 0),
                ];
            }
        }

        return $results;
    }

    /**
     * Sinkronisasikan semua user dan profil lama ke dalam tabel loyalty_accounts secara efisien (Batch Chunked).
     */
    public function syncExistingUsers(bool $calculateHistoricalSpend = true): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $totalScanned = 0;
        $newlyCreated = 0;
        $linkedExisting = 0;
        $skipped = 0;

        // 1. Pra-kalkulasi total belanja semua user lampau dalam 1 SQL Query (menghindari puluhan ribu query N+1)
        $userSpends = [];
        if ($calculateHistoricalSpend) {
            $userSpends = DB::table('orders')
                ->where('order_status', 'COMPLETED')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->select('user_id', DB::raw('SUM(grand_total) as total_spent'))
                ->pluck('total_spent', 'user_id')
                ->toArray();
        }

        // 2. Proses data UserProfile secara bertahap dalam batch chunk (500 per iterasi)
        \App\Models\UserProfile::with('user')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->chunkById(500, function ($profiles) use (&$totalScanned, &$newlyCreated, &$linkedExisting, &$skipped, $userSpends, $calculateHistoricalSpend) {
                $phoneMap = [];
                foreach ($profiles as $p) {
                    $norm = $this->normalizePhone($p->phone_number);
                    if ($norm && strlen($norm) >= 8) {
                        $phoneMap[$norm][] = $p;
                    }
                }

                if (empty($phoneMap)) {
                    return;
                }

                // Batch fetch akun loyalty yang sudah ada pada chunk ini
                $existingAccounts = LoyaltyAccount::whereIn('phone_number', array_keys($phoneMap))
                    ->get()
                    ->keyBy('phone_number');

                $now = now();

                foreach ($phoneMap as $cleanPhone => $chunkProfiles) {
                    $totalScanned += count($chunkProfiles);
                    $profile = $chunkProfiles[0];
                    $user = $profile->user;
                    $userId = $user?->id ?? $profile->user_id;
                    $name = $profile->full_name ?: ($user?->name ?? 'Pelanggan Tokopon');
                    $historicalSpend = ($calculateHistoricalSpend && $userId) ? (float) ($userSpends[$userId] ?? 0) : 0;

                    if ($existingAccounts->has($cleanPhone)) {
                        $account = $existingAccounts->get($cleanPhone);
                        $needsUpdate = false;

                        if ($account->user_id === null && $userId) {
                            $account->user_id = $userId;
                            $needsUpdate = true;
                        }
                        if (empty($account->customer_name) && !empty($name)) {
                            $account->customer_name = $name;
                            $needsUpdate = true;
                        }
                        if ($calculateHistoricalSpend && $historicalSpend > (float) $account->total_spend) {
                            $account->total_spend = $historicalSpend;
                            $account->tier = $this->evaluateTier($historicalSpend);
                            $needsUpdate = true;
                        }

                        if ($needsUpdate) {
                            $account->save();
                            $linkedExisting++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $tier = $this->evaluateTier($historicalSpend);
                        $newAccount = LoyaltyAccount::create([
                            'phone_number' => $cleanPhone,
                            'user_id' => $userId,
                            'customer_name' => $name,
                            'points_balance' => 0,
                            'lifetime_points' => 0,
                            'total_spend' => $historicalSpend,
                            'tier' => $tier,
                            'tier_updated_at' => $now,
                        ]);
                        $existingAccounts->put($cleanPhone, $newAccount);
                        $newlyCreated++;
                    }
                }
            });

        // 3. Proses data internal users (tamu belanja mobile e.g. 08xxx@tokopon.internal) dalam batch chunk
        User::where('email', 'like', '%@tokopon.internal')
            ->whereNotIn('id', function ($sub) {
                $sub->select('user_id')->from('loyalty_accounts')->whereNotNull('user_id');
            })
            ->chunkById(500, function ($internalUsers) use (&$totalScanned, &$newlyCreated, &$linkedExisting, &$skipped, $userSpends, $calculateHistoricalSpend) {
                $phoneMap = [];
                foreach ($internalUsers as $u) {
                    $rawPhone = explode('@', $u->email)[0];
                    $norm = $this->normalizePhone($rawPhone);
                    if ($norm && strlen($norm) >= 8) {
                        $phoneMap[$norm] = $u;
                    }
                }

                if (empty($phoneMap)) {
                    return;
                }

                $existingAccounts = LoyaltyAccount::whereIn('phone_number', array_keys($phoneMap))
                    ->get()
                    ->keyBy('phone_number');

                $now = now();

                foreach ($phoneMap as $cleanPhone => $u) {
                    $totalScanned++;
                    $historicalSpend = ($calculateHistoricalSpend) ? (float) ($userSpends[$u->id] ?? 0) : 0;

                    if ($existingAccounts->has($cleanPhone)) {
                        $account = $existingAccounts->get($cleanPhone);
                        if ($account->user_id === null) {
                            $account->update(['user_id' => $u->id]);
                            $linkedExisting++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $tier = $this->evaluateTier($historicalSpend);
                        $newAccount = LoyaltyAccount::create([
                            'phone_number' => $cleanPhone,
                            'user_id' => $u->id,
                            'customer_name' => $u->name ?: 'Pelanggan Tokopon',
                            'points_balance' => 0,
                            'lifetime_points' => 0,
                            'total_spend' => $historicalSpend,
                            'tier' => $tier,
                            'tier_updated_at' => $now,
                        ]);
                        $existingAccounts->put($cleanPhone, $newAccount);
                        $newlyCreated++;
                    }
                }
            });

        return [
            'total_scanned' => $totalScanned,
            'newly_created' => $newlyCreated,
            'linked_existing' => $linkedExisting,
            'skipped' => $skipped,
        ];
    }

    /**
     * Petakan 1 user secara spesifik ke dalam akun loyalty.
     */
    public function mapSingleUser(int $userId, bool $calculateHistoricalSpend = true): ?LoyaltyAccount
    {
        $user = User::with('profile')->find($userId);
        if (!$user) return null;

        $rawPhone = $user->profile?->phone_number;
        if (empty($rawPhone) && str_contains($user->email, '@tokopon.internal')) {
            $rawPhone = explode('@', $user->email)[0];
        }

        if (empty($rawPhone)) return null;

        $cleanPhone = $this->normalizePhone($rawPhone);
        if (empty($cleanPhone)) return null;

        $account = LoyaltyAccount::where('phone_number', $cleanPhone)->first();

        $historicalSpend = 0;
        if ($calculateHistoricalSpend) {
            $historicalSpend = (float) Order::where('user_id', $user->id)
                ->where('order_status', 'COMPLETED')
                ->sum('grand_total');
        }

        $tier = $this->evaluateTier($historicalSpend);

        if (!$account) {
            return LoyaltyAccount::create([
                'phone_number' => $cleanPhone,
                'user_id' => $user->id,
                'customer_name' => $user->name ?: 'Pelanggan Tokopon',
                'points_balance' => 0,
                'lifetime_points' => 0,
                'total_spend' => $historicalSpend,
                'tier' => $tier,
                'tier_updated_at' => now(),
            ]);
        }

        $account->update([
            'user_id' => $user->id,
            'customer_name' => $user->name ?: $account->customer_name,
            'total_spend' => max((float) $account->total_spend, $historicalSpend),
            'tier' => $this->evaluateTier(max((float) $account->total_spend, $historicalSpend)),
        ]);

        return $account;
    }

    /**
     * Penyesuaian manual saldo poin oleh Admin / CS (Adjustment).
     */
    public function adjustPointsManually(
        LoyaltyAccount $account,
        int $pointsDelta,
        string $description,
        ?int $adminUserId = null
    ): LoyaltyTransaction {
        if ($pointsDelta === 0) {
            throw new Exception("Jumlah penyesuaian poin tidak boleh 0.");
        }

        return DB::transaction(function () use ($account, $pointsDelta, $description, $adminUserId) {
            $lockedAccount = LoyaltyAccount::where('id', $account->id)->lockForUpdate()->first();
            $oldBalance = (int) $lockedAccount->points_balance;
            $newBalance = $oldBalance + $pointsDelta;

            if ($newBalance < 0) {
                throw new Exception("Pengurangan poin melebihi sisa saldo member (Saldo: {$oldBalance}, Pengurangan: " . abs($pointsDelta) . ").");
            }

            $lockedAccount->points_balance = $newBalance;
            if ($pointsDelta > 0) {
                $lockedAccount->lifetime_points += $pointsDelta;
            }
            $lockedAccount->save();

            return LoyaltyTransaction::create([
                'loyalty_account_id' => $lockedAccount->id,
                'order_id' => null,
                'channel' => 'ADMIN_MANUAL',
                'type' => 'ADJUSTMENT',
                'points' => $pointsDelta,
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'discount_amount' => 0,
                'description' => $description,
                'created_by' => $adminUserId,
            ]);
        });
    }
}
