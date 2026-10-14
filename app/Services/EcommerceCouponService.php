<?php

namespace App\Services;

use App\Models\EcommerceCoupon;
use App\Models\EcommerceCouponRedemption;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcommerceCouponService
{
    /**
     * Mendapatkan daftar kupon aktif yang tersedia untuk user dan keranjang belanja saat ini.
     *
     * @param User|null $user
     * @param float $subtotal
     * @param float $shippingCost
     * @return Collection
     */
    public function getAvailableCoupons(?User $user = null, float $subtotal = 0, float $shippingCost = 0, ?int $businessUnitId = null): Collection
    {
        $query = EcommerceCoupon::active()
            ->validNow();

        if ($businessUnitId !== null) {
            $query->where(function ($q) use ($businessUnitId) {
                $q->whereNull('business_unit_id')->orWhere('business_unit_id', $businessUnitId);
            });
        }

        $coupons = $query->get();

        return $coupons->map(function (EcommerceCoupon $coupon) use ($user, $subtotal, $shippingCost) {
            $isEligible = true;
            $ineligibilityReason = null;

            // 1. Cek kuota global
            if ($coupon->usage_limit_total !== null && $coupon->used_count >= $coupon->usage_limit_total) {
                $isEligible = false;
                $ineligibilityReason = 'Kuota voucher telah habis.';
            }

            // 2. Cek limit per user
            if ($isEligible && $user && !$coupon->canBeUsedByUser($user->id)) {
                $isEligible = false;
                $ineligibilityReason = 'Anda telah mencapai batas penggunaan voucher ini.';
            }

            // 3. Cek minimum belanja
            if ($isEligible && $coupon->min_purchase_amount > 0 && $subtotal < $coupon->min_purchase_amount) {
                $isEligible = false;
                $ineligibilityReason = 'Minimum belanja Rp ' . number_format($coupon->min_purchase_amount, 0, ',', '.') . ' belum terpenuhi.';
            }

            // 4. Cek apakah diskon ongkir tapi ongkir 0
            $potentialDiscount = $coupon->calculateDiscount($subtotal, $shippingCost);
            if ($isEligible && str_contains($coupon->type, 'SHIPPING') && $shippingCost <= 0) {
                $isEligible = false;
                $ineligibilityReason = 'Tidak ada biaya ongkir untuk dipotong.';
            }

            $coupon->is_eligible = $isEligible;
            $coupon->ineligibility_reason = $ineligibilityReason;
            $coupon->potential_discount = $potentialDiscount;

            return $coupon;
        })->sortByDesc(function (EcommerceCoupon $c) {
            return ($c->is_eligible ? 1000000000 : 0) + $c->potential_discount;
        })->values();
    }

    /**
     * Validasi kode voucher untuk checkout.
     *
     * @param string $code
     * @param User|null $user
     * @param float $subtotal
     * @param float $shippingCost
     * @return array{valid: bool, message: string, coupon: ?EcommerceCoupon, discount: float, discount_type: ?string}
     */
    public function validateCoupon(string $code, ?User $user = null, float $subtotal = 0, float $shippingCost = 0, ?int $businessUnitId = null): array
    {
        $normalizedCode = strtoupper(trim($code));

        if (empty($normalizedCode)) {
            return [
                'valid' => false,
                'message' => 'Kode voucher tidak boleh kosong.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        $coupon = EcommerceCoupon::where('code', $normalizedCode)->first();

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => 'Kode voucher tidak valid atau tidak ditemukan.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        if (!$coupon->is_active) {
            return [
                'valid' => false,
                'message' => 'Voucher saat ini sedang tidak aktif.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        if ($coupon->business_unit_id !== null && $businessUnitId !== null && (int) $coupon->business_unit_id !== (int) $businessUnitId) {
            return [
                'valid' => false,
                'message' => 'Voucher ini tidak berlaku untuk toko yang dipilih.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return [
                'valid' => false,
                'message' => 'Voucher baru dapat digunakan mulai ' . $coupon->start_date->format('d M Y H:i'),
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        if ($coupon->end_date && $now->gt($coupon->end_date)) {
            return [
                'valid' => false,
                'message' => 'Voucher telah kedaluwarsa pada ' . $coupon->end_date->format('d M Y H:i'),
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        if ($coupon->usage_limit_total !== null && $coupon->used_count >= $coupon->usage_limit_total) {
            return [
                'valid' => false,
                'message' => 'Kuota pemakaian voucher ini telah habis.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        if ($user && !$coupon->canBeUsedByUser($user->id)) {
            return [
                'valid' => false,
                'message' => 'Anda telah mencapai batas maksimal penggunaan voucher ini.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        if ($coupon->min_purchase_amount > 0 && $subtotal < $coupon->min_purchase_amount) {
            return [
                'valid' => false,
                'message' => 'Total belanja belum memenuhi syarat minimum Rp ' . number_format($coupon->min_purchase_amount, 0, ',', '.'),
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        $discount = $coupon->calculateDiscount($subtotal, $shippingCost);
        $discountType = str_contains($coupon->type, 'SHIPPING') ? 'SHIPPING' : 'PRODUCT';

        if ($discountType === 'SHIPPING' && $shippingCost <= 0) {
            return [
                'valid' => false,
                'message' => 'Voucher gratis ongkir tidak dapat digunakan karena ongkos kirim Rp 0.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        if ($discount <= 0) {
            return [
                'valid' => false,
                'message' => 'Voucher tidak menghasilkan potongan harga.',
                'coupon' => null,
                'discount' => 0.0,
                'discount_type' => null,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Voucher berhasil diterapkan!',
            'coupon' => $coupon,
            'discount' => $discount,
            'discount_type' => $discountType,
        ];
    }

    /**
     * Terapkan kupon pada order secara atomik di dalam Database Transaction.
     *
     * @param EcommerceCoupon $coupon
     * @param Order $order
     * @param User $user
     * @param float $discountAmount
     * @return EcommerceCouponRedemption
     * @throws Exception
     */
    public function applyCouponToOrder(EcommerceCoupon $coupon, Order $order, User $user, float $discountAmount): EcommerceCouponRedemption
    {
        return DB::transaction(function () use ($coupon, $order, $user, $discountAmount) {
            // Lock coupon row for concurrency safety
            $lockedCoupon = EcommerceCoupon::where('id', $coupon->id)->lockForUpdate()->first();

            if (!$lockedCoupon || !$lockedCoupon->is_active) {
                throw new Exception("Voucher tidak valid atau tidak aktif.");
            }

            if ($lockedCoupon->usage_limit_total !== null && $lockedCoupon->used_count >= $lockedCoupon->usage_limit_total) {
                throw new Exception("Kuota pemakaian voucher {$coupon->code} telah habis.");
            }

            if ($lockedCoupon->usage_limit_per_user !== null) {
                $userRedemptionsCount = EcommerceCouponRedemption::where('coupon_id', $lockedCoupon->id)
                    ->where('user_id', $user->id)
                    ->count();

                if ($userRedemptionsCount >= $lockedCoupon->usage_limit_per_user) {
                    throw new Exception("Anda telah mencapai batas maksimal penggunaan voucher {$coupon->code}.");
                }
            }

            // Increment atomic used_count
            $lockedCoupon->increment('used_count');

            $discountType = str_contains($lockedCoupon->type, 'SHIPPING') ? 'SHIPPING' : 'PRODUCT';

            // Catat ke riwayat redemption
            $redemption = EcommerceCouponRedemption::create([
                'coupon_id' => $lockedCoupon->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'discount_applied' => $discountAmount,
                'discount_type' => $discountType,
                'redeemed_at' => now(),
            ]);

            // Update order
            $order->update([
                'coupon_id' => $lockedCoupon->id,
                'coupon_code' => $lockedCoupon->code,
                'coupon_discount_amount' => $discountAmount,
            ]);

            Log::info("Voucher {$lockedCoupon->code} berhasil diterapkan ke Order #{$order->order_number} sebesar Rp {$discountAmount}");

            return $redemption;
        });
    }

    /**
     * Rollback penggunaan voucher saat order dibatalkan atau kedaluwarsa.
     * Mengurangi used_count pada kupon dan menghapus catatan redemption.
     *
     * @param Order $order
     * @return bool
     */
    public function rollbackCoupon(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $redemption = EcommerceCouponRedemption::where('order_id', $order->id)->first();

            // Jika tidak ada redemption dan order tidak memiliki coupon_id, tidak ada yang perlu di-rollback
            if (!$redemption && !$order->coupon_id) {
                return false;
            }

            $couponId = $redemption?->coupon_id ?? $order->coupon_id;
            if ($couponId) {
                $coupon = EcommerceCoupon::where('id', $couponId)->lockForUpdate()->first();
                if ($coupon && $coupon->used_count > 0) {
                    $coupon->decrement('used_count');
                    Log::info("Used count untuk kupon {$coupon->code} di-rollback (-1). Sisa: {$coupon->used_count}");
                }
            }

            if ($redemption) {
                $redemption->delete();
                Log::info("Catatan redemption kupon untuk Order #{$order->order_number} berhasil dihapus.");
            }

            // Reset coupon fields di order
            $order->update([
                'coupon_id' => null,
                'coupon_code' => null,
                'coupon_discount_amount' => 0,
            ]);

            return true;
        });
    }
}
