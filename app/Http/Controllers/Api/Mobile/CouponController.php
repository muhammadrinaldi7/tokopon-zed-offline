<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\EcommerceCoupon;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\EcommerceCouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    protected EcommerceCouponService $couponService;

    public function __construct(EcommerceCouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * Dapatkan daftar voucher yang tersedia untuk pengguna dan keranjang saat ini.
     */
    public function available(Request $request): JsonResponse
    {
        $subtotal = (float) $request->query('subtotal', 0);
        $shippingCost = (float) $request->query('shipping_cost', 0);
        $businessUnitId = $request->query('business_unit_id') ? (int) $request->query('business_unit_id') : null;

        $user = $this->resolveCustomerUser($request);

        $coupons = $this->couponService->getAvailableCoupons($user, $subtotal, $shippingCost, $businessUnitId);

        $formatted = $coupons->map(function (EcommerceCoupon $coupon) {
            $typeLabel = match ($coupon->type) {
                EcommerceCoupon::TYPE_PRODUCT_PERCENT => 'Diskon Persen Produk',
                EcommerceCoupon::TYPE_PRODUCT_FIXED => 'Potongan Harga Produk',
                EcommerceCoupon::TYPE_SHIPPING_PERCENT => 'Diskon Ongkir Persen',
                EcommerceCoupon::TYPE_SHIPPING_FIXED => 'Potongan Ongkos Kirim',
                default => 'Voucher Diskon',
            };

            return [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'title' => $coupon->title,
                'description' => $coupon->description,
                'type' => $coupon->type,
                'discount_type_label' => $typeLabel,
                'discount_value' => (float) $coupon->discount_value,
                'max_discount_amount' => $coupon->max_discount_amount !== null ? (float) $coupon->max_discount_amount : null,
                'min_purchase_amount' => (float) $coupon->min_purchase_amount,
                'formatted_min_purchase' => 'Rp ' . number_format($coupon->min_purchase_amount, 0, ',', '.'),
                'start_date' => $coupon->start_date?->toIso8601String(),
                'end_date' => $coupon->end_date?->toIso8601String(),
                'is_eligible' => (bool) $coupon->is_eligible,
                'ineligibility_reason' => $coupon->ineligibility_reason,
                'potential_discount' => (float) $coupon->potential_discount,
                'formatted_potential_discount' => 'Rp ' . number_format($coupon->potential_discount, 0, ',', '.'),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher berhasil dimuat.',
            'data' => $formatted,
        ]);
    }

    /**
     * Validasi kode voucher sebelum checkout (live check/apply).
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'business_unit_id' => 'nullable|integer|exists:business_units,id',
            'phone' => 'nullable|string|max:30',
        ]);

        $subtotal = (float) $validated['subtotal'];
        $shippingCost = (float) ($validated['shipping_cost'] ?? 0);
        $businessUnitId = isset($validated['business_unit_id']) ? (int) $validated['business_unit_id'] : null;
        $user = $this->resolveCustomerUser($request);

        $result = $this->couponService->validateCoupon(
            $validated['code'],
            $user,
            $subtotal,
            $shippingCost,
            $businessUnitId
        );

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => null,
            ], 422);
        }

        $coupon = $result['coupon'];

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'title' => $coupon->title,
                'type' => $coupon->type,
                'discount_type' => $result['discount_type'],
                'discount_amount' => (float) $result['discount'],
                'formatted_discount_amount' => 'Rp ' . number_format($result['discount'], 0, ',', '.'),
            ]
        ]);
    }

    /**
     * Temukan user dari token otentikasi atau variasi nomor telepon header/query/body.
     */
    protected function resolveCustomerUser(Request $request): ?User
    {
        $user = $request->user();
        if ($user) {
            return $user;
        }

        $phone = $request->input('phone') ?? $request->query('phone') ?? $request->header('X-Customer-Phone');
        if (!empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (!empty($cleanPhone)) {
                $phoneVariations = [$cleanPhone];
                if (str_starts_with($cleanPhone, '0')) {
                    $phoneVariations[] = '62' . substr($cleanPhone, 1);
                } elseif (str_starts_with($cleanPhone, '62')) {
                    $phoneVariations[] = '0' . substr($cleanPhone, 2);
                }

                $profile = UserProfile::whereIn('phone_number', $phoneVariations)->first();
                if ($profile && $profile->user) {
                    return $profile->user;
                }

                return User::where('email', $cleanPhone . '@tokopon.internal')->first();
            }
        }

        return null;
    }
}
