<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\EcommerceCoupon;
use App\Models\EcommerceCouponRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    /**
     * Tampilan utama Manajemen Voucher & Kupon Promo (Inertia React)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $filterType = $request->input('type', '');
        $filterStatus = $request->input('status', 'all');
        $filterBu = $request->input('bu', null);

        $query = EcommerceCoupon::query()
            ->withCount('redemptions')
            ->with('businessUnit:id,name,code');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filterType)) {
            $query->where('type', $filterType);
        }

        if ($filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        if (!empty($filterBu)) {
            $query->where('business_unit_id', $filterBu);
        }

        $coupons = $query->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(function ($coupon) {
                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'title' => $coupon->title,
                    'description' => $coupon->description,
                    'type' => $coupon->type,
                    'discount_value' => (float) $coupon->discount_value,
                    'max_discount_amount' => $coupon->max_discount_amount !== null ? (float) $coupon->max_discount_amount : null,
                    'min_purchase_amount' => (float) $coupon->min_purchase_amount,
                    'usage_limit_total' => $coupon->usage_limit_total,
                    'usage_limit_per_user' => $coupon->usage_limit_per_user,
                    'used_count' => (int) $coupon->used_count,
                    'redemptions_count' => (int) $coupon->redemptions_count,
                    'start_date' => $coupon->start_date?->format('Y-m-d H:i'),
                    'end_date' => $coupon->end_date?->format('Y-m-d H:i'),
                    'start_date_iso' => $coupon->start_date?->format('Y-m-d\TH:i'),
                    'end_date_iso' => $coupon->end_date?->format('Y-m-d\TH:i'),
                    'is_active' => (bool) $coupon->is_active,
                    'business_unit_id' => $coupon->business_unit_id,
                    'business_unit_name' => $coupon->businessUnit?->name,
                    'is_expired' => $coupon->end_date ? $coupon->end_date->isPast() : false,
                ];
            });

        // Metrik Ringkasan KPI
        $stats = [
            'total_active' => EcommerceCoupon::where('is_active', true)->count(),
            'total_coupons' => EcommerceCoupon::count(),
            'total_redemptions' => EcommerceCouponRedemption::count(),
            'total_savings' => (float) EcommerceCouponRedemption::sum('discount_applied'),
        ];

        return Inertia::render('Vouchers/Index', [
            'coupons' => $coupons,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'type' => $filterType,
                'status' => $filterStatus,
                'bu' => $filterBu,
            ],
            'couponTypes' => [
                ['value' => EcommerceCoupon::TYPE_PRODUCT_FIXED, 'label' => 'Diskon Produk (Nominal Rp)'],
                ['value' => EcommerceCoupon::TYPE_PRODUCT_PERCENT, 'label' => 'Diskon Produk (Persen %)'],
                ['value' => EcommerceCoupon::TYPE_SHIPPING_FIXED, 'label' => 'Potongan Ongkir (Nominal Rp)'],
                ['value' => EcommerceCoupon::TYPE_SHIPPING_PERCENT, 'label' => 'Potongan Ongkir (Persen %)'],
            ],
        ]);
    }

    /**
     * Simpan kupon promo baru
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => strtoupper(trim($request->input('code', ''))),
        ]);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:ecommerce_coupons,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:' . implode(',', [
                EcommerceCoupon::TYPE_PRODUCT_PERCENT,
                EcommerceCoupon::TYPE_PRODUCT_FIXED,
                EcommerceCoupon::TYPE_SHIPPING_PERCENT,
                EcommerceCoupon::TYPE_SHIPPING_FIXED,
            ]),
            'discount_value' => 'required|numeric|min:1',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'business_unit_id' => 'nullable|exists:business_units,id',
        ]);

        $validated['used_count'] = 0;
        EcommerceCoupon::create($validated);

        return redirect()->back()->with('success', "Voucher promo #{$validated['code']} berhasil dibuat.");
    }

    /**
     * Perbarui kupon promo yang ada
     */
    public function update(Request $request, EcommerceCoupon $coupon): RedirectResponse
    {
        $request->merge([
            'code' => strtoupper(trim($request->input('code', ''))),
        ]);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:ecommerce_coupons,code,' . $coupon->id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:' . implode(',', [
                EcommerceCoupon::TYPE_PRODUCT_PERCENT,
                EcommerceCoupon::TYPE_PRODUCT_FIXED,
                EcommerceCoupon::TYPE_SHIPPING_PERCENT,
                EcommerceCoupon::TYPE_SHIPPING_FIXED,
            ]),
            'discount_value' => 'required|numeric|min:1',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'business_unit_id' => 'nullable|exists:business_units,id',
        ]);

        $coupon->update($validated);

        return redirect()->back()->with('success', "Voucher #{$coupon->code} berhasil diperbarui.");
    }

    /**
     * Toggle status aktif kupon
     */
    public function toggleActive(EcommerceCoupon $coupon): JsonResponse
    {
        $coupon->update(['is_active' => !$coupon->is_active]);

        $statusStr = $coupon->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'is_active' => $coupon->is_active,
            'message' => "Voucher #{$coupon->code} berhasil {$statusStr}.",
        ]);
    }

    /**
     * Hapus kupon (hanya jika belum pernah digunakan/diklaim)
     */
    public function destroy(EcommerceCoupon $coupon): RedirectResponse
    {
        if ($coupon->used_count > 0 || $coupon->redemptions()->exists()) {
            return redirect()->back()->with('error', "Voucher #{$coupon->code} tidak dapat dihapus karena sudah memiliki riwayat transaksi/klaim.");
        }

        $code = $coupon->code;
        $coupon->delete();

        return redirect()->back()->with('success', "Voucher #{$code} berhasil dihapus.");
    }

    /**
     * Ambil riwayat pemakaian / klaim kupon oleh customer
     */
    public function redemptions(EcommerceCoupon $coupon): JsonResponse
    {
        $redemptions = EcommerceCouponRedemption::with(['user.profile', 'order'])
            ->where('coupon_id', $coupon->id)
            ->latest()
            ->take(50)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'customer_name' => $r->user?->name ?? 'Guest/Tamu',
                    'customer_phone' => $r->user?->profile?->phone_number ?? '-',
                    'order_number' => $r->order?->order_number ?? ("#INV-" . $r->order_id),
                    'discount_applied' => (float) $r->discount_applied,
                    'created_at' => $r->created_at?->format('d M Y, H:i') ?? '-',
                ];
            });

        return response()->json([
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'title' => $coupon->title,
                'used_count' => $coupon->used_count,
            ],
            'redemptions' => $redemptions,
        ]);
    }
}
