<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\CommissionWithdrawal;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\UserReferralCode;
use App\Services\ReferralService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class ReferralManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $activeTab = 'withdrawals'; // 'withdrawals', 'commissions', 'members', 'settings'

    // Tab 1: Withdrawals Filters & Modals
    public string $withdrawalSearch = '';
    public string $filterWithdrawalStatus = '';
    public bool $showApproveModal = false;
    public ?int $selectedWithdrawalId = null;
    public $proofFile = null;
    public string $approvalNotes = '';

    public bool $showRejectModal = false;
    public ?int $rejectWithdrawalId = null;
    public string $rejectionReason = '';

    // Tab 2: Commissions Filters
    public string $commissionSearch = '';
    public string $filterCommissionStatus = '';

    // Tab 3: Members Filters
    public string $memberSearch = '';

    // Tab 4: Settings Form
    public bool $referral_is_active = true;
    public string $referral_commission_type = 'PERCENTAGE';
    public float $referral_commission_rate = 1.0;
    public string $referral_reward_scope = 'FIRST_ORDER_ONLY';
    public float $referral_max_commission_cap = 100000;
    public int $referral_hold_days = 7;
    public float $referral_min_withdrawal = 50000;

    // Feedback Alert
    public ?string $feedbackMessage = null;
    public string $feedbackType = 'success';

    protected $paginationTheme = 'tailwind';

    public function mount(SettingService $settingService): void
    {
        $this->referral_is_active = (bool) $settingService->get('referral_is_active', true);
        $this->referral_commission_type = strtoupper((string) $settingService->get('referral_commission_type', 'PERCENTAGE'));
        $this->referral_commission_rate = (float) $settingService->get('referral_commission_rate', 1.0);
        $this->referral_reward_scope = strtoupper((string) $settingService->get('referral_reward_scope', 'FIRST_ORDER_ONLY'));
        $this->referral_max_commission_cap = (float) $settingService->get('referral_max_commission_cap', 100000);
        $this->referral_hold_days = (int) $settingService->get('referral_hold_days', 7);
        $this->referral_min_withdrawal = (float) $settingService->get('referral_min_withdrawal', 50000);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->resetPage();
    }

    public function openApproveModal(int $id): void
    {
        $this->selectedWithdrawalId = $id;
        $this->proofFile = null;
        $this->approvalNotes = '';
        $this->showApproveModal = true;
    }

    public function closeApproveModal(): void
    {
        $this->showApproveModal = false;
        $this->selectedWithdrawalId = null;
        $this->proofFile = null;
        $this->approvalNotes = '';
    }

    public function submitApprove(ReferralService $referralService): void
    {
        $withdrawal = CommissionWithdrawal::find($this->selectedWithdrawalId);
        if (!$withdrawal) {
            $this->feedbackMessage = 'Data pengajuan tidak ditemukan.';
            $this->feedbackType = 'error';
            return;
        }

        $proofUrl = null;
        if ($this->proofFile) {
            $this->validate(['proofFile' => 'image|max:5120']);
            $disk = config('media-library.disk_name', 'public');
            $fileName = 'withdraw-' . time() . '-' . uniqid() . '.' . $this->proofFile->getClientOriginalExtension();
            $path = $this->proofFile->storeAs('referral-withdrawals', $fileName, $disk);
            $proofUrl = Storage::disk($disk)->url($path);
        }

        try {
            $referralService->approveWithdrawal(
                $withdrawal,
                $proofUrl,
                $this->approvalNotes ?: null,
                Auth::id()
            );

            $this->closeApproveModal();
            $this->feedbackMessage = "Pengajuan penarikan sebesar Rp " . number_format($withdrawal->net_amount, 0, ',', '.') . " berhasil disetujui & ditandai telah ditransfer.";
            $this->feedbackType = 'success';
        } catch (\Throwable $e) {
            $this->feedbackMessage = 'Gagal memproses persetujuan: ' . $e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function openRejectModal(int $id): void
    {
        $this->rejectWithdrawalId = $id;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->rejectWithdrawalId = null;
        $this->rejectionReason = '';
    }

    public function submitReject(ReferralService $referralService): void
    {
        $this->validate([
            'rejectionReason' => 'required|min:3|max:255',
        ], [
            'rejectionReason.required' => 'Alasan penolakan wajib diisi untuk transparansi member.',
        ]);

        $withdrawal = CommissionWithdrawal::find($this->rejectWithdrawalId);
        if (!$withdrawal) {
            $this->feedbackMessage = 'Data pengajuan tidak ditemukan.';
            $this->feedbackType = 'error';
            return;
        }

        try {
            $referralService->rejectWithdrawal(
                $withdrawal,
                $this->rejectionReason,
                Auth::id()
            );

            $this->closeRejectModal();
            $this->feedbackMessage = "Pengajuan penarikan berhasil ditolak. Saldo sebesar Rp " . number_format($withdrawal->amount, 0, ',', '.') . " otomatis dikembalikan ke saldo aktif member.";
            $this->feedbackType = 'success';
        } catch (\Throwable $e) {
            $this->feedbackMessage = 'Gagal memproses penolakan: ' . $e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function saveSettings(SettingService $settingService): void
    {
        $this->validate([
            'referral_commission_type' => 'required|in:PERCENTAGE,FIXED',
            'referral_commission_rate' => 'required|numeric|min:0.01',
            'referral_reward_scope' => 'required|in:FIRST_ORDER_ONLY,ALL_ORDERS',
            'referral_max_commission_cap' => 'required|numeric|min:0',
            'referral_hold_days' => 'required|integer|min:0|max:90',
            'referral_min_withdrawal' => 'required|numeric|min:10000',
        ]);

        $settingService->set('referral_is_active', $this->referral_is_active ? '1' : '0');
        $settingService->set('referral_commission_type', $this->referral_commission_type);
        $settingService->set('referral_commission_rate', (string) $this->referral_commission_rate);
        $settingService->set('referral_reward_scope', $this->referral_reward_scope);
        $settingService->set('referral_max_commission_cap', (string) $this->referral_max_commission_cap);
        $settingService->set('referral_hold_days', (string) $this->referral_hold_days);
        $settingService->set('referral_min_withdrawal', (string) $this->referral_min_withdrawal);

        $this->feedbackMessage = 'Pengaturan Program Referral & Komisi berhasil diperbarui!';
        $this->feedbackType = 'success';
    }

    public function releaseHoldsManually(ReferralService $referralService): void
    {
        $count = $referralService->releaseMaturedCommissions();
        $this->feedbackMessage = "Sinkronisasi selesai! Sebanyak {$count} komisi referral yang melewati masa hold H+7 berhasil dilepaskan ke status APPROVED (Siap Ditarik).";
        $this->feedbackType = 'success';
    }

    public function render()
    {
        // 1. Withdrawals Query
        $withdrawalsQuery = CommissionWithdrawal::with(['user.profile', 'bankAccount', 'handler'])
            ->latest();

        if (!empty(trim($this->withdrawalSearch))) {
            $search = trim($this->withdrawalSearch);
            $withdrawalsQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('bankAccount', function ($bq) use ($search) {
                    $bq->where('account_number', 'like', "%{$search}%")
                       ->orWhere('account_name', 'like', "%{$search}%")
                       ->orWhere('bank_name', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($this->filterWithdrawalStatus)) {
            $withdrawalsQuery->where('status', $this->filterWithdrawalStatus);
        }

        $withdrawals = $withdrawalsQuery->paginate(15, ['*'], 'withdrawalsPage');

        // 2. Commissions Query
        $commissionsQuery = ReferralCommission::with(['referrer.profile', 'referee.profile', 'order'])
            ->latest();

        if (!empty(trim($this->commissionSearch))) {
            $search = trim($this->commissionSearch);
            $commissionsQuery->where(function ($q) use ($search) {
                $q->whereHas('referrer', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%");
                })->orWhereHas('referee', function ($fq) use ($search) {
                    $fq->where('name', 'like', "%{$search}%");
                })->orWhereHas('order', function ($oq) use ($search) {
                    $oq->where('order_number', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($this->filterCommissionStatus)) {
            $commissionsQuery->where('status', $this->filterCommissionStatus);
        }

        $commissions = $commissionsQuery->paginate(15, ['*'], 'commissionsPage');

        // 3. Members Query
        $membersQuery = User::whereNotNull('referral_code')
            ->with(['profile', 'referralCodeRecord'])
            ->withCount(['referrals', 'referralCommissions'])
            ->latest('id');

        if (!empty(trim($this->memberSearch))) {
            $search = trim($this->memberSearch);
            $membersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('referral_code', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($pq) use ($search) {
                      $pq->where('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        $members = $membersQuery->paginate(15, ['*'], 'membersPage');

        // Overview Stats
        $stats = [
            'total_pending_withdrawals_count' => CommissionWithdrawal::where('status', 'PENDING')->count(),
            'total_pending_withdrawals_amount' => (float) CommissionWithdrawal::where('status', 'PENDING')->sum('amount'),
            'total_withdrawn_transferred' => (float) CommissionWithdrawal::where('status', 'TRANSFERRED')->sum('net_amount'),
            'total_commissions_earned' => (float) ReferralCommission::where('status', '!=', 'VOID')->sum('commission_amount'),
            'total_commissions_pending_hold' => (float) ReferralCommission::where('status', 'PENDING')->where('hold_until', '>', now())->sum('commission_amount'),
            'total_referral_users' => User::whereNotNull('referral_code')->count(),
        ];

        $selectedWithdrawal = $this->selectedWithdrawalId
            ? CommissionWithdrawal::with(['user.profile', 'bankAccount'])->find($this->selectedWithdrawalId)
            : null;

        $rejectWithdrawal = $this->rejectWithdrawalId
            ? CommissionWithdrawal::with(['user.profile', 'bankAccount'])->find($this->rejectWithdrawalId)
            : null;

        return view('livewire.zoffline.ecommerce.referral-manager', [
            'withdrawals' => $withdrawals,
            'commissions' => $commissions,
            'members' => $members,
            'stats' => $stats,
            'selectedWithdrawal' => $selectedWithdrawal,
            'rejectWithdrawal' => $rejectWithdrawal,
        ]);
    }
}