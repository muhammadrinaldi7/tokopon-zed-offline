<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyController extends Controller
{
    /**
     * Tampilan utama Manajemen Member Loyalty & Poin (Inertia React)
     */
    public function index(Request $request, LoyaltyService $loyaltyService): Response
    {
        $search = $request->input('search', '');
        $filterTier = $request->input('tier', '');

        $query = LoyaltyAccount::with(['user.profile']);

        if (!empty($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('phone_number', 'like', "%{$s}%")
                    ->orWhereHas('user', function ($u) use ($s) {
                        $u->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                    })
                    ->orWhereHas('user.profile', function ($p) use ($s) {
                        $p->where('full_name', 'like', "%{$s}%")
                            ->orWhere('phone_number', 'like', "%{$s}%");
                    });
            });
        }

        if (!empty($filterTier)) {
            $query->where('tier', $filterTier);
        }

        $members = $query->orderByDesc('points_balance')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($acc) {
                $userName = $acc->user?->name ?? 'Guest/Pelanggan';
                $userPhone = $acc->phone_number ?: ($acc->user?->profile?->phone_number ?? '-');

                return [
                    'id' => $acc->id,
                    'user_id' => $acc->user_id,
                    'user_name' => $userName,
                    'user_email' => $acc->user?->email,
                    'phone_number' => $userPhone,
                    'tier' => $acc->tier ?? 'BRONZE',
                    'points_balance' => (int) $acc->points_balance,
                    'lifetime_points' => (int) $acc->lifetime_points,
                    'total_spend' => (float) $acc->total_spend,
                    'formatted_spend' => 'Rp ' . number_format($acc->total_spend, 0, ',', '.'),
                    'is_active' => (bool) $acc->is_active,
                    'created_at' => $acc->created_at?->format('d M Y'),
                ];
            });

        // Metrik Ringkasan
        $stats = [
            'total_members' => LoyaltyAccount::count(),
            'total_points_balance' => (int) LoyaltyAccount::sum('points_balance'),
            'total_lifetime_points' => (int) LoyaltyAccount::sum('lifetime_points'),
            'total_member_spend' => (float) LoyaltyAccount::sum('total_spend'),
            'formatted_total_spend' => 'Rp ' . number_format(LoyaltyAccount::sum('total_spend'), 0, ',', '.'),
        ];

        // Pengaturan Sistem Poin
        $settings = $loyaltyService->getSettings();

        return Inertia::render('Loyalty/Index', [
            'members' => $members,
            'stats' => $stats,
            'settings' => [
                'loyalty_is_active' => (bool) ($settings['loyalty_is_active'] ?? true),
                'loyalty_earn_ratio' => (int) ($settings['loyalty_earn_ratio'] ?? 10000),
                'loyalty_point_value' => (int) ($settings['loyalty_point_value'] ?? 100),
                'loyalty_min_redeem_points' => (int) ($settings['loyalty_min_redeem_points'] ?? 50),
                'loyalty_tier_silver_spend' => (float) ($settings['loyalty_tier_silver_spend'] ?? 5000000),
                'loyalty_tier_gold_spend' => (float) ($settings['loyalty_tier_gold_spend'] ?? 15000000),
                'loyalty_tier_platinum_spend' => (float) ($settings['loyalty_tier_platinum_spend'] ?? 50000000),
            ],
            'filters' => [
                'search' => $search,
                'tier' => $filterTier,
            ],
        ]);
    }

    /**
     * Penyesuaian manual saldo poin oleh CS / Admin
     */
    public function adjustPoints(Request $request, LoyaltyService $loyaltyService, LoyaltyAccount $account): RedirectResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'type' => 'required|in:ADD,DEDUCT',
            'description' => 'required|string|min:3|max:255',
        ]);

        $delta = ($validated['type'] === 'ADD') ? abs($validated['points']) : -abs($validated['points']);

        try {
            $loyaltyService->adjustPointsManually($account, $delta, $validated['description'], Auth::id());
            $sign = $delta > 0 ? "+{$delta}" : "{$delta}";
            return redirect()->back()->with('success', "Saldo poin {$account->phone_number} berhasil disesuaikan ({$sign} poin).");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyesuaikan poin: ' . $e->getMessage());
        }
    }

    /**
     * Ambil riwayat buku besar (ledger) transaksi poin akun member
     */
    public function history(LoyaltyAccount $account): JsonResponse
    {
        $transactions = LoyaltyTransaction::with(['creator:id,name'])
            ->where('loyalty_account_id', $account->id)
            ->latest()
            ->take(50)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'points_in' => (int) ($tx->points_in ?? 0),
                    'points_out' => (int) ($tx->points_out ?? 0),
                    'balance_after' => (int) $tx->balance_after,
                    'description' => $tx->description,
                    'admin_name' => $tx->creator?->name ?? 'Sistem',
                    'created_at' => $tx->created_at?->format('d M Y, H:i') ?? '-',
                ];
            });

        return response()->json([
            'account' => [
                'id' => $account->id,
                'phone_number' => $account->phone_number,
                'user_name' => $account->user?->name ?? 'Pelanggan',
                'points_balance' => (int) $account->points_balance,
                'tier' => $account->tier,
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * Simpan konfigurasi sistem poin & tier loyalty
     */
    public function saveSettings(Request $request, LoyaltyService $loyaltyService): RedirectResponse
    {
        $validated = $request->validate([
            'loyalty_is_active' => 'boolean',
            'loyalty_earn_ratio' => 'required|integer|min:1000',
            'loyalty_point_value' => 'required|integer|min:1',
            'loyalty_min_redeem_points' => 'required|integer|min:0',
            'loyalty_tier_silver_spend' => 'required|numeric|min:0',
            'loyalty_tier_gold_spend' => 'required|numeric|min:0',
            'loyalty_tier_platinum_spend' => 'required|numeric|min:0',
        ]);

        $loyaltyService->saveSettings($validated);

        return redirect()->back()->with('success', 'Konfigurasi sistem loyalty & poin berhasil diperbarui.');
    }

    /**
     * Sinkronisasi akun member dari database customer lama
     */
    public function syncUsers(Request $request, LoyaltyService $loyaltyService): RedirectResponse
    {
        @set_time_limit(300);
        try {
            $calcHistoricalSpend = $request->boolean('calc_spend', true);
            $res = $loyaltyService->syncExistingUsers($calcHistoricalSpend);
            return redirect()->back()->with('success', "Sinkronisasi selesai! Berhasil memindai {$res['total_scanned']} customer, dibuat {$res['newly_created']} akun member baru.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }
}
