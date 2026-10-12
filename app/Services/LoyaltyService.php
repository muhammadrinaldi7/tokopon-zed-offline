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
     * Evaluasi dan sinkronisasi Membership Tier berdasarkan akumulasi total belanja.
     */
    public function updateTier(LoyaltyAccount $account): void
    {
        $silverThreshold = (float) $this->settingService->get('loyalty_silver_min_spend', 5000000);
        $goldThreshold = (float) $this->settingService->get('loyalty_gold_min_spend', 15000000);
        $platinumThreshold = (float) $this->settingService->get('loyalty_platinum_min_spend', 50000000);

        $spend = (float) $account->total_spend;

        $newTier = 'BRONZE';
        if ($spend >= $platinumThreshold) {
            $newTier = 'PLATINUM';
        } elseif ($spend >= $goldThreshold) {
            $newTier = 'GOLD';
        } elseif ($spend >= $silverThreshold) {
            $newTier = 'SILVER';
        }

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
}
