<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CommissionWithdrawal;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Services\ReferralService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    /**
     * Tampilan utama Manajemen Referral & Komisi (Inertia React)
     */
    public function index(Request $request, SettingService $settingService): Response
    {
        try {
            $activeTab = $request->input('tab', 'withdrawals');
            $withdrawalSearch = trim((string) $request->input('withdrawal_search', ''));
            $withdrawalStatus = trim((string) $request->input('withdrawal_status', ''));
            $commissionSearch = trim((string) $request->input('commission_search', ''));
            $commissionStatus = trim((string) $request->input('commission_status', ''));
            $memberSearch = trim((string) $request->input('member_search', ''));

            // 1. Overview Stats
            $stats = [
                'total_pending_withdrawals_count' => 0,
                'total_pending_withdrawals_amount' => 0,
                'total_withdrawn_transferred' => 0,
                'total_commissions_earned' => 0,
                'total_commissions_pending_hold' => 0,
                'total_referral_users' => 0,
            ];

            try {
                if (Schema::hasTable('commission_withdrawals')) {
                    $stats['total_pending_withdrawals_count'] = CommissionWithdrawal::where('status', 'PENDING')->count();
                    $stats['total_pending_withdrawals_amount'] = (float) CommissionWithdrawal::where('status', 'PENDING')->sum('amount');
                    $stats['total_withdrawn_transferred'] = (float) CommissionWithdrawal::where('status', 'TRANSFERRED')->sum('net_amount');
                }
                if (Schema::hasTable('referral_commissions')) {
                    $stats['total_commissions_earned'] = (float) ReferralCommission::where('status', '!=', 'VOID')->sum('commission_amount');
                    $stats['total_commissions_pending_hold'] = (float) ReferralCommission::where('status', 'PENDING')
                        ->where('hold_until', '>', now())
                        ->sum('commission_amount');
                }
                if (Schema::hasTable('users')) {
                    $stats['total_referral_users'] = User::whereNotNull('referral_code')->count();
                }
            } catch (\Throwable $e) {
                Log::warning('ReferralController: stats error: ' . $e->getMessage());
            }

            // 2. Withdrawals Query
            $withdrawals = [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ];

            try {
                if (Schema::hasTable('commission_withdrawals')) {
                    $withdrawalsQuery = CommissionWithdrawal::with(['user.profile', 'bankAccount', 'handler'])
                        ->latest();

                    if (!empty($withdrawalSearch)) {
                        $withdrawalsQuery->where(function ($q) use ($withdrawalSearch) {
                            $q->whereHas('user', function ($uq) use ($withdrawalSearch) {
                                $uq->where('name', 'like', "%{$withdrawalSearch}%")
                                    ->orWhere('email', 'like', "%{$withdrawalSearch}%");
                            })->orWhereHas('bankAccount', function ($bq) use ($withdrawalSearch) {
                                $bq->where('account_number', 'like', "%{$withdrawalSearch}%")
                                    ->orWhere('account_name', 'like', "%{$withdrawalSearch}%")
                                    ->orWhere('bank_name', 'like', "%{$withdrawalSearch}%");
                            });
                        });
                    }

                    if (!empty($withdrawalStatus)) {
                        $withdrawalsQuery->where('status', $withdrawalStatus);
                    }

                    $withdrawals = $withdrawalsQuery->paginate(15, ['*'], 'withdrawals_page')
                        ->appends($request->all());
                }
            } catch (\Throwable $e) {
                Log::warning('ReferralController: withdrawals query error: ' . $e->getMessage());
            }

            // 3. Commissions Query
            $commissions = [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ];

            try {
                if (Schema::hasTable('referral_commissions')) {
                    $commissionsQuery = ReferralCommission::with(['referrer.profile', 'referee.profile', 'order'])
                        ->latest();

                    if (!empty($commissionSearch)) {
                        $commissionsQuery->where(function ($q) use ($commissionSearch) {
                            $q->whereHas('referrer', function ($rq) use ($commissionSearch) {
                                $rq->where('name', 'like', "%{$commissionSearch}%");
                            })->orWhereHas('referee', function ($fq) use ($commissionSearch) {
                                $fq->where('name', 'like', "%{$commissionSearch}%");
                            })->orWhereHas('order', function ($oq) use ($commissionSearch) {
                                $oq->where('order_number', 'like', "%{$commissionSearch}%");
                            });
                        });
                    }

                    if (!empty($commissionStatus)) {
                        $commissionsQuery->where('status', $commissionStatus);
                    }

                    $commissions = $commissionsQuery->paginate(15, ['*'], 'commissions_page')
                        ->appends($request->all());
                }
            } catch (\Throwable $e) {
                Log::warning('ReferralController: commissions query error: ' . $e->getMessage());
            }

            // 4. Members Query
            $members = [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ];

            try {
                if (Schema::hasTable('users')) {
                    $membersQuery = User::whereNotNull('referral_code')
                        ->with(['profile', 'referralCodeRecord'])
                        ->withCount(['referrals', 'referralCommissions'])
                        ->latest('id');

                    if (!empty($memberSearch)) {
                        $membersQuery->where(function ($q) use ($memberSearch) {
                            $q->where('name', 'like', "%{$memberSearch}%")
                                ->orWhere('email', 'like', "%{$memberSearch}%")
                                ->orWhere('referral_code', 'like', "%{$memberSearch}%")
                                ->orWhereHas('profile', function ($pq) use ($memberSearch) {
                                    $pq->where('phone_number', 'like', "%{$memberSearch}%");
                                });
                        });
                    }

                    $members = $membersQuery->paginate(15, ['*'], 'members_page')
                        ->appends($request->all());
                }
            } catch (\Throwable $e) {
                Log::warning('ReferralController: members query error: ' . $e->getMessage());
            }

            // 5. Settings
            $settings = [
                'referral_is_active' => filter_var($settingService->get('referral_is_active', true), FILTER_VALIDATE_BOOLEAN),
                'referral_commission_type' => strtoupper((string) $settingService->get('referral_commission_type', 'PERCENTAGE')),
                'referral_commission_rate' => (float) $settingService->get('referral_commission_rate', 1.0),
                'referral_reward_scope' => strtoupper((string) $settingService->get('referral_reward_scope', 'FIRST_ORDER_ONLY')),
                'referral_max_commission_cap' => (float) $settingService->get('referral_max_commission_cap', 100000),
                'referral_hold_days' => (int) $settingService->get('referral_hold_days', 7),
                'referral_min_withdrawal' => (float) $settingService->get('referral_min_withdrawal', 50000),
            ];

            return Inertia::render('Referral/Index', [
                'stats' => $stats,
                'withdrawals' => $withdrawals,
                'commissions' => $commissions,
                'members' => $members,
                'settings' => $settings,
                'filters' => [
                    'tab' => $activeTab,
                    'withdrawal_search' => $withdrawalSearch,
                    'withdrawal_status' => $withdrawalStatus,
                    'commission_search' => $commissionSearch,
                    'commission_status' => $commissionStatus,
                    'member_search' => $memberSearch,
                ],
            ]);
        } catch (\Throwable $fatal) {
            Log::error('ReferralController::index fatal: ' . $fatal->getMessage() . "\n" . $fatal->getTraceAsString());
            return Inertia::render('Referral/Index', [
                'stats' => [
                    'total_pending_withdrawals_count' => 0,
                    'total_pending_withdrawals_amount' => 0,
                    'total_withdrawn_transferred' => 0,
                    'total_commissions_earned' => 0,
                    'total_commissions_pending_hold' => 0,
                    'total_referral_users' => 0,
                ],
                'withdrawals' => ['data' => [], 'total' => 0],
                'commissions' => ['data' => [], 'total' => 0],
                'members' => ['data' => [], 'total' => 0],
                'settings' => [
                    'referral_is_active' => true,
                    'referral_commission_type' => 'PERCENTAGE',
                    'referral_commission_rate' => 1.0,
                    'referral_reward_scope' => 'FIRST_ORDER_ONLY',
                    'referral_max_commission_cap' => 100000,
                    'referral_hold_days' => 7,
                    'referral_min_withdrawal' => 50000,
                ],
                'filters' => ['tab' => 'withdrawals'],
            ]);
        }
    }

    /**
     * Simpan pengaturan program referral
     */
    public function saveSettings(Request $request, SettingService $settingService): RedirectResponse
    {
        $validated = $request->validate([
            'referral_is_active' => 'boolean',
            'referral_commission_type' => 'required|in:PERCENTAGE,FIXED',
            'referral_commission_rate' => 'required|numeric|min:0.01',
            'referral_reward_scope' => 'required|in:FIRST_ORDER_ONLY,ALL_ORDERS',
            'referral_max_commission_cap' => 'required|numeric|min:0',
            'referral_hold_days' => 'required|integer|min:0|max:90',
            'referral_min_withdrawal' => 'required|numeric|min:10000',
        ]);

        $settingService->set('referral_is_active', $validated['referral_is_active'] ? '1' : '0');
        $settingService->set('referral_commission_type', $validated['referral_commission_type']);
        $settingService->set('referral_commission_rate', (string) $validated['referral_commission_rate']);
        $settingService->set('referral_reward_scope', $validated['referral_reward_scope']);
        $settingService->set('referral_max_commission_cap', (string) $validated['referral_max_commission_cap']);
        $settingService->set('referral_hold_days', (string) $validated['referral_hold_days']);
        $settingService->set('referral_min_withdrawal', (string) $validated['referral_min_withdrawal']);

        return redirect()->back()->with('success', 'Pengaturan Program Referral & Komisi berhasil disimpan.');
    }

    /**
     * Setujui pengajuan penarikan dana
     */
    public function approveWithdrawal(
        Request $request,
        CommissionWithdrawal $withdrawal,
        ReferralService $referralService
    ): RedirectResponse {
        $request->validate([
            'proof_file' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:255',
        ]);

        $proofUrl = null;
        if ($request->hasFile('proof_file')) {
            $disk = config('media-library.disk_name', 'public');
            $file = $request->file('proof_file');
            $fileName = 'withdraw-' . time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('referral-withdrawals', $fileName, $disk);
            $proofUrl = Storage::disk($disk)->url($path);
        }

        try {
            $referralService->approveWithdrawal(
                $withdrawal,
                $proofUrl,
                $request->input('notes'),
                Auth::id()
            );

            return redirect()->back()->with(
                'success',
                'Pengajuan penarikan Rp ' . number_format($withdrawal->net_amount, 0, ',', '.') . ' berhasil disetujui & ditandai telah ditransfer.'
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memproses persetujuan: ' . $e->getMessage());
        }
    }

    /**
     * Tolak pengajuan penarikan dana
     */
    public function rejectWithdrawal(
        Request $request,
        CommissionWithdrawal $withdrawal,
        ReferralService $referralService
    ): RedirectResponse {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:3|max:255',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi untuk transparansi member.',
        ]);

        try {
            $referralService->rejectWithdrawal(
                $withdrawal,
                $validated['rejection_reason'],
                Auth::id()
            );

            return redirect()->back()->with(
                'success',
                'Pengajuan penarikan berhasil ditolak. Saldo Rp ' . number_format($withdrawal->amount, 0, ',', '.') . ' dikembalikan ke saldo aktif member.'
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memproses penolakan: ' . $e->getMessage());
        }
    }

    /**
     * Lepaskan komisi yang melewati masa hold H+7 secara manual
     */
    public function releaseHolds(ReferralService $referralService): RedirectResponse
    {
        try {
            $count = $referralService->releaseMaturedCommissions();
            return redirect()->back()->with(
                'success',
                "Sinkronisasi berhasil! {$count} komisi referral yang melewati masa hold garansi berhasil dilepaskan ke status APPROVED (Siap Ditarik)."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal melepaskan komisi hold: ' . $e->getMessage());
        }
    }
}
