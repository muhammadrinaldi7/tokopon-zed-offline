<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class LoyaltyManager extends Component
{
    use WithPagination;

    public string $activeTab = 'members'; // 'members', 'mapping', 'settings'

    // Tab 1: Member List Filter
    public string $search = '';
    public string $filterTier = '';

    // Modal Ledger History
    public bool $showHistoryModal = false;
    public ?int $selectedAccountId = null;

    // Modal Manual Adjustment
    public bool $showAdjustModal = false;
    public ?int $adjustAccountId = null;
    public int $adjustPoints = 0;
    public string $adjustDescription = '';
    public string $adjustType = 'ADD'; // 'ADD' or 'DEDUCT'

    // Tab 2: Mapping Pelanggan Lama
    public bool $calcHistoricalSpend = true;
    public ?array $syncResult = null;
    public string $mappingSearch = '';

    // Tab 3: Settings Form
    public bool $loyalty_is_active = true;
    public int $loyalty_earn_ratio = 10000;
    public int $loyalty_point_value = 100;
    public int $loyalty_min_redeem_points = 50;
    public float $loyalty_silver_min_spend = 5000000;
    public float $loyalty_gold_min_spend = 15000000;
    public float $loyalty_platinum_min_spend = 50000000;

    protected $paginationTheme = 'tailwind';

    public function mount(LoyaltyService $loyaltyService): void
    {
        $this->loadSettings($loyaltyService);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTier(): void
    {
        $this->resetPage();
    }

    // ─── SETTINGS ACTIONS ──────────────────────────────────────────

    public function loadSettings(LoyaltyService $loyaltyService): void
    {
        $settings = $loyaltyService->getSettings();
        $this->loyalty_is_active = (bool) ($settings['loyalty_is_active'] ?? true);
        $this->loyalty_earn_ratio = (int) ($settings['loyalty_earn_ratio'] ?? 10000);
        $this->loyalty_point_value = (int) ($settings['loyalty_point_value'] ?? 100);
        $this->loyalty_min_redeem_points = (int) ($settings['loyalty_min_redeem_points'] ?? 50);
        $this->loyalty_silver_min_spend = (float) ($settings['loyalty_tier_silver_spend'] ?? 5000000);
        $this->loyalty_gold_min_spend = (float) ($settings['loyalty_tier_gold_spend'] ?? 15000000);
        $this->loyalty_platinum_min_spend = (float) ($settings['loyalty_tier_platinum_spend'] ?? 50000000);
    }

    public function saveSettings(LoyaltyService $loyaltyService): void
    {
        $this->validate([
            'loyalty_earn_ratio' => 'required|integer|min:1000',
            'loyalty_point_value' => 'required|integer|min:1',
            'loyalty_min_redeem_points' => 'required|integer|min:0',
            'loyalty_silver_min_spend' => 'required|numeric|min:0',
            'loyalty_gold_min_spend' => 'required|numeric|min:0',
            'loyalty_platinum_min_spend' => 'required|numeric|min:0',
        ]);

        $loyaltyService->saveSettings([
            'loyalty_is_active' => $this->loyalty_is_active,
            'loyalty_earn_ratio' => $this->loyalty_earn_ratio,
            'loyalty_point_value' => $this->loyalty_point_value,
            'loyalty_min_redeem_points' => $this->loyalty_min_redeem_points,
            'loyalty_tier_silver_spend' => $this->loyalty_silver_min_spend,
            'loyalty_tier_gold_spend' => $this->loyalty_gold_min_spend,
            'loyalty_tier_platinum_spend' => $this->loyalty_platinum_min_spend,
        ]);

        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Pengaturan Tersimpan',
            'message' => 'Konfigurasi sistem poin & loyalty berhasil diperbarui.'
        ]);
    }

    // ─── MAPPING ACTIONS ───────────────────────────────────────────

    public function runSync(LoyaltyService $loyaltyService): void
    {
        @set_time_limit(300);
        try {
            $this->syncResult = $loyaltyService->syncExistingUsers($this->calcHistoricalSpend);
            \Illuminate\Support\Facades\Cache::forget('loyalty_manager_stats');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Sinkronisasi Selesai',
                'message' => "Berhasil memindai {$this->syncResult['total_scanned']} user. Dibuat {$this->syncResult['newly_created']} akun baru, dihubungkan {$this->syncResult['linked_existing']} akun."
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Sinkronisasi',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function mapSingleUser(int $userId, LoyaltyService $loyaltyService): void
    {
        try {
            $account = $loyaltyService->mapSingleUser($userId, $this->calcHistoricalSpend);
            if ($account) {
                \Illuminate\Support\Facades\Cache::forget('loyalty_manager_stats');
                $this->dispatch('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil Dipetakan',
                    'message' => "Pengguna #{$userId} berhasil dipetakan ke Akun Loyalty ({$account->phone_number})."
                ]);
            } else {
                $this->dispatch('toast', [
                    'type' => 'warning',
                    'title' => 'Gagal',
                    'message' => "Nomor telepon pengguna tidak valid atau tidak ditemukan."
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Terjadi Kesalahan',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ─── LEDGER & ADJUSTMENT ACTIONS ───────────────────────────────

    public function openHistoryModal(int $accountId): void
    {
        $this->selectedAccountId = $accountId;
        $this->showHistoryModal = true;
    }

    public function closeHistoryModal(): void
    {
        $this->showHistoryModal = false;
        $this->selectedAccountId = null;
    }

    public function openAdjustModal(int $accountId): void
    {
        $this->adjustAccountId = $accountId;
        $this->adjustPoints = 50;
        $this->adjustType = 'ADD';
        $this->adjustDescription = 'Penyesuaian manual oleh Admin';
        $this->showAdjustModal = true;
    }

    public function closeAdjustModal(): void
    {
        $this->showAdjustModal = false;
        $this->adjustAccountId = null;
    }

    public function saveAdjustment(LoyaltyService $loyaltyService): void
    {
        $this->validate([
            'adjustPoints' => 'required|integer|min:1',
            'adjustDescription' => 'required|string|min:3|max:200',
        ]);

        $account = LoyaltyAccount::find($this->adjustAccountId);
        if (!$account) {
            $this->dispatch('toast', ['type' => 'error', 'title' => 'Error', 'message' => 'Akun tidak ditemukan.']);
            return;
        }

        $delta = ($this->adjustType === 'ADD') ? abs($this->adjustPoints) : -abs($this->adjustPoints);

        try {
            $loyaltyService->adjustPointsManually($account, $delta, $this->adjustDescription, Auth::id());
            \Illuminate\Support\Facades\Cache::forget('loyalty_manager_stats');
            $this->closeAdjustModal();
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Poin Berhasil Disesuaikan',
                'message' => "Saldo akun {$account->phone_number} telah disesuaikan sebesar " . ($delta > 0 ? "+{$delta}" : "{$delta}") . " poin."
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Menyesuaikan Poin',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function clearMappingSearch(): void
    {
        $this->mappingSearch = '';
    }

    // ─── RENDER ────────────────────────────────────────────────────

    public function render(LoyaltyService $loyaltyService)
    {
        $accounts = LoyaltyAccount::with('user')
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $cleanPhone = preg_replace('/[^0-9]/', '', $term);

                $q->where(function ($sub) use ($term, $cleanPhone) {
                    $sub->where('customer_name', 'like', "%{$term}%")
                        ->orWhere('phone_number', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($uq) use ($term) {
                            $uq->where('email', 'like', "%{$term}%")
                               ->orWhere('name', 'like', "%{$term}%");
                        });

                    if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                        $sub->orWhere('phone_number', 'like', "%{$cleanPhone}%");
                        if (str_starts_with($cleanPhone, '08')) {
                            $alt = '628' . substr($cleanPhone, 2);
                            $sub->orWhere('phone_number', 'like', "%{$alt}%");
                        } elseif (str_starts_with($cleanPhone, '628')) {
                            $alt = '08' . substr($cleanPhone, 3);
                            $sub->orWhere('phone_number', 'like', "%{$alt}%");
                        }
                    }
                });
            })
            ->when($this->filterTier, function ($q) {
                $q->where('tier', $this->filterTier);
            })
            ->orderBy('points_balance', 'desc')
            ->paginate(15);

        $selectedAccount = $this->selectedAccountId
            ? LoyaltyAccount::with(['transactions' => fn($q) => $q->latest()->limit(30), 'user'])->find($this->selectedAccountId)
            : null;

        $syncStatus = ($this->activeTab === 'mapping')
            ? $loyaltyService->getSyncStatus()
            : [];

        $mappingResults = [];
        if ($this->activeTab === 'mapping' && !empty(trim($this->mappingSearch))) {
            $mappingResults = $loyaltyService->searchUsersForMapping($this->mappingSearch, 25);
        }

        // Global stats with cache for 30 seconds to eliminate redundant query load
        $stats = \Illuminate\Support\Facades\Cache::remember('loyalty_manager_stats', 30, function () {
            $accStats = \Illuminate\Support\Facades\DB::table('loyalty_accounts')
                ->selectRaw('COUNT(*) as total_members, COALESCE(SUM(points_balance), 0) as total_points, COALESCE(SUM(total_spend), 0) as total_spend')
                ->first();

            $txCount = \Illuminate\Support\Facades\DB::table('loyalty_transactions')->count();

            return [
                'total_members' => (int) ($accStats->total_members ?? 0),
                'total_points_circulating' => (int) ($accStats->total_points ?? 0),
                'total_spend_recorded' => (float) ($accStats->total_spend ?? 0),
                'total_transactions' => (int) $txCount,
            ];
        });

        return view('livewire.zoffline.ecommerce.loyalty-manager', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'syncStatus' => $syncStatus,
            'mappingResults' => $mappingResults,
            'stats' => $stats,
        ]);
    }
}
