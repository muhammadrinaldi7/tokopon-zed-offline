<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyAccount;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Dapatkan ringkasan saldo poin, tier status, nilai rupiah, dan benefit member.
     */
    public function summary(Request $request): JsonResponse
    {
        $phone = $this->resolveCustomerPhone($request);

        if (empty($phone)) {
            return response()->json([
                'success' => true,
                'message' => 'Status member belum terdaftar (Guest Mode).',
                'data' => [
                    'phone_number' => null,
                    'customer_name' => 'Pelanggan Tamu',
                    'points_balance' => 0,
                    'point_cash_value' => 0,
                    'lifetime_points' => 0,
                    'total_spend' => 0,
                    'tier' => 'BRONZE',
                    'tier_badge' => $this->getTierBadgeInfo('BRONZE'),
                    'next_tier' => 'SILVER',
                    'next_tier_progress_pct' => 0,
                    'spend_needed_for_next_tier' => 5000000,
                    'min_redeem_points' => 50,
                    'recent_transactions' => [],
                ]
            ]);
        }

        $summary = $this->loyaltyService->getSummary($phone);
        $summary['tier_badge'] = $this->getTierBadgeInfo($summary['tier'] ?? 'BRONZE');

        // Ambil 5 riwayat mutasi poin terbaru
        $cleanPhone = $this->loyaltyService->normalizePhone($phone);
        $account = LoyaltyAccount::where('phone_number', $cleanPhone)->first();
        $recentTransactions = [];

        if ($account) {
            $recentTransactions = $account->transactions()
                ->with('order:id,order_number')
                ->limit(5)
                ->get()
                ->map(fn($tx) => $this->formatTransaction($tx));
        }

        $summary['recent_transactions'] = $recentTransactions;

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan poin loyalty berhasil dimuat.',
            'data' => $summary,
        ]);
    }

    /**
     * Dapatkan daftar riwayat transaksi mutasi poin (Earn / Redeem / Refund) dengan paginasi.
     */
    public function history(Request $request): JsonResponse
    {
        $phone = $this->resolveCustomerPhone($request);

        if (empty($phone)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ]
            ]);
        }

        $cleanPhone = $this->loyaltyService->normalizePhone($phone);
        $account = LoyaltyAccount::where('phone_number', $cleanPhone)->first();

        if (!$account) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ]
            ]);
        }

        $limit = max(1, min(50, (int) $request->input('limit', 20)));
        $paginated = $account->transactions()
            ->with('order:id,order_number')
            ->paginate($limit);

        $formatted = $paginated->getCollection()->map(fn($tx) => $this->formatTransaction($tx));

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    /**
     * Format entitas transaksi ke bentuk respon JSON yang ramah mobile app.
     */
    protected function formatTransaction($tx): array
    {
        return [
            'id' => $tx->id,
            'channel' => $tx->channel,
            'type' => $tx->type,
            'points' => (int) $tx->points,
            'is_addition' => $tx->points > 0,
            'balance_before' => (int) $tx->balance_before,
            'balance_after' => (int) $tx->balance_after,
            'discount_amount' => (float) $tx->discount_amount,
            'description' => $tx->description,
            'order_number' => $tx->order?->order_number,
            'created_at' => $tx->created_at?->toIso8601String(),
            'formatted_date' => $tx->created_at?->format('d M Y, H:i'),
        ];
    }

    /**
     * Tentukan nomor HP pelanggan dari token autentikasi atau header request.
     */
    protected function resolveCustomerPhone(Request $request): ?string
    {
        $user = $request->user();

        // 1. Dari data terautentikasi (memeriksa profil, loyalty account, dan email internal)
        if ($user && !empty($user->customer_phone)) {
            return $user->customer_phone;
        }

        // 2. Dari parameter query / header
        $phoneParam = $request->query('phone') ?? $request->header('X-Customer-Phone');
        if (!empty($phoneParam)) {
            return (string) $phoneParam;
        }

        return null;
    }

    /**
     * Metadata visual untuk badge status tier member.
     */
    protected function getTierBadgeInfo(string $tier): array
    {
        return match (strtoupper($tier)) {
            'PLATINUM' => [
                'label' => 'Platinum Member',
                'color_hex' => '#0284C7',
                'bg_hex' => '#E0F2FE',
                'icon' => 'ribbon-outline',
            ],
            'GOLD' => [
                'label' => 'Gold Member',
                'color_hex' => '#D97706',
                'bg_hex' => '#FEF3C7',
                'icon' => 'shield-checkmark-outline',
            ],
            'SILVER' => [
                'label' => 'Silver Member',
                'color_hex' => '#64748B',
                'bg_hex' => '#F1F5F9',
                'icon' => 'medal-outline',
            ],
            default => [
                'label' => 'Bronze Member',
                'color_hex' => '#B45309',
                'bg_hex' => '#FFF7ED',
                'icon' => 'star-outline',
            ],
        };
    }
}
