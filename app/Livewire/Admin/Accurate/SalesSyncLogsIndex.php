<?php

namespace App\Livewire\Admin\Accurate;

use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderAccurateSyncLog;
use App\Services\OrderAccurateSyncService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Log & Antrean Sinkronisasi Accurate'])]
class SalesSyncLogsIndex extends Component
{
    use WithPagination;

    // Filter
    public string $search = '';
    public string $statusFilter = ''; // '', 'PENDING', 'SYNCED', 'FAILED'
    public string $triggerFilter = ''; // '', 'REALTIME_POS', 'AUTO_RETRY_SCHEDULE', 'MANUAL_ADMIN'
    public string $branchFilter = '';
    public string $businessUnitFilter = '';
    public string $datePreset = 'all'; // 'all', 'today', 'yesterday', 'this_week', 'this_month', 'custom'
    public string $startDate = '';
    public string $endDate = '';
    public int $perPage = 15;

    // Cron / Scheduler Settings
    public string $retryFrequency = 'every_ten_minutes';
    public ?string $lastRunAt = null;

    // Detail Modal State
    public bool $showDetailModal = false;
    public ?OrderAccurateSyncLog $selectedLog = null;
    public ?Order $selectedOrder = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'triggerFilter' => ['except' => ''],
        'branchFilter' => ['except' => ''],
        'businessUnitFilter' => ['except' => ''],
        'datePreset' => ['except' => 'all'],
    ];

    public function mount()
    {
        $settingService = app(SettingService::class);
        $this->retryFrequency = $settingService->get('accurate_sales_retry_frequency', 'every_ten_minutes');
        $this->lastRunAt = $settingService->get('accurate_sales_retry_last_run_at');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingTriggerFilter()
    {
        $this->resetPage();
    }

    public function updatingBranchFilter()
    {
        $this->resetPage();
    }

    public function updatingBusinessUnitFilter()
    {
        $this->resetPage();
    }

    public function setDatePreset(string $preset)
    {
        $this->datePreset = $preset;
        $now = Carbon::now();

        match ($preset) {
            'today' => [
                $this->startDate = $now->copy()->startOfDay()->toDateString(),
                $this->endDate = $now->copy()->endOfDay()->toDateString(),
            ],
            'yesterday' => [
                $this->startDate = $now->copy()->subDay()->startOfDay()->toDateString(),
                $this->endDate = $now->copy()->subDay()->endOfDay()->toDateString(),
            ],
            'this_week' => [
                $this->startDate = $now->copy()->startOfWeek()->toDateString(),
                $this->endDate = $now->copy()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                $this->startDate = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate = $now->copy()->endOfMonth()->toDateString(),
            ],
            'all' => [
                $this->startDate = '',
                $this->endDate = '',
            ],
            default => null,
        };

        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'triggerFilter', 'branchFilter', 'businessUnitFilter', 'startDate', 'endDate']);
        $this->datePreset = 'all';
        $this->resetPage();
    }

    public function saveScheduleSettings()
    {
        $this->validate([
            'retryFrequency' => 'required|in:every_minute,every_five_minutes,every_ten_minutes,every_fifteen_minutes,every_thirty_minutes,hourly,disabled',
        ]);

        $settingService = app(SettingService::class);
        $settingService->set('accurate_sales_retry_frequency', $this->retryFrequency);
        $this->dispatch('toast', title: 'Berhasil', message: 'Pengaturan jadwal retry otomatis berhasil disimpan.', type: 'success');
    }

    public function runCronNow()
    {
        try {
            Artisan::call('accurate:retry-pending-sales', ['--limit' => 50]);
            $output = Artisan::output();
            
            $settingService = app(SettingService::class);
            $this->lastRunAt = $settingService->get('accurate_sales_retry_last_run_at', now()->toDateTimeString());

            $this->dispatch('toast', title: 'Proses Selesai', message: 'Pemeriksaan dan retry antrean berhasil dijalankan.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal menjalankan cron: ' . $e->getMessage(), type: 'error');
        }
    }

    public function retrySingleOrder(int $orderId)
    {
        $syncService = app(OrderAccurateSyncService::class);
        $result = $syncService->syncOrder($orderId, 'MANUAL_ADMIN', Auth::id());

        if ($result['success']) {
            $this->dispatch('toast', title: 'Berhasil', message: "Order #{$result['invoice_no']} berhasil disinkronkan ke Accurate.", type: 'success');
        } else {
            $this->dispatch('toast', title: 'Gagal Sinkron', message: 'Penyebab: ' . ($result['error'] ?? 'Terjadi kesalahan.'), type: 'error');
        }
    }

    public function retryAllPending()
    {
        $orders = Order::where('order_status', 'COMPLETED')
            ->where(function ($q) {
                $q->whereNull('accurate_invoice_no')
                  ->orWhere('accurate_invoice_no', '')
                  ->orWhere('accurate_sync_status', 'PENDING');
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($orders->isEmpty()) {
            $this->dispatch('toast', title: 'Info', message: 'Tidak ada pesanan pending yang perlu disinkronkan.', type: 'info');
            return;
        }

        $syncService = app(OrderAccurateSyncService::class);
        $success = 0;
        $failed = 0;

        foreach ($orders as $order) {
            $result = $syncService->syncOrder($order, 'MANUAL_ADMIN', Auth::id());
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }

        $this->dispatch('toast', title: 'Selesai', message: "Sinkronisasi Massal Selesai. Sukses: {$success}, Gagal: {$failed}.", type: $failed > 0 ? 'warning' : 'success');
    }

    public function openDetailModal(int $logId)
    {
        $this->selectedLog = OrderAccurateSyncLog::with(['order.items', 'order.payments', 'order.branch', 'executedBy', 'businessUnit'])->find($logId);
        $this->selectedOrder = $this->selectedLog?->order;
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
        $this->selectedOrder = null;
    }

    public function render()
    {
        // 1. KPI Metrik
        $totalPending = Order::where('order_status', 'COMPLETED')
            ->where(function ($q) {
                $q->whereNull('accurate_invoice_no')
                  ->orWhere('accurate_invoice_no', '')
                  ->orWhere('accurate_sync_status', 'PENDING');
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $autoRecoveredYesterday = OrderAccurateSyncLog::where('trigger_type', 'AUTO_RETRY_SCHEDULE')
            ->where('status', 'SUCCESS')
            ->whereDate('created_at', Carbon::yesterday())
            ->count();

        $autoRecoveredToday = OrderAccurateSyncLog::where('trigger_type', 'AUTO_RETRY_SCHEDULE')
            ->where('status', 'SUCCESS')
            ->whereDate('created_at', Carbon::today())
            ->count();

        $failedNeedAction = Order::where('order_status', 'COMPLETED')
            ->where('accurate_sync_status', 'FAILED')
            ->count();

        $totalSyncedToday = Order::where('order_status', 'COMPLETED')
            ->where('accurate_sync_status', 'SYNCED')
            ->whereDate('accurate_synced_at', Carbon::today())
            ->count();

        // 2. Query Log
        $logsQuery = OrderAccurateSyncLog::with([
            'order.items',
            'order.user',
            'order.branch',
            'order.handledBy',
            'businessUnit',
            'branch',
            'executedBy'
        ])
        ->when($this->search, function ($q) {
            $q->where(function ($sub) {
                $sub->whereHas('order', function ($o) {
                    $o->where('order_number', 'like', "%{$this->search}%")
                      ->orWhere('accurate_invoice_no', 'like', "%{$this->search}%");
                })
                ->orWhere('accurate_invoice_no', 'like', "%{$this->search}%")
                ->orWhere('error_message', 'like', "%{$this->search}%");
            });
        })
        ->when($this->statusFilter, function ($q) {
            $q->where('status', $this->statusFilter);
        })
        ->when($this->triggerFilter, function ($q) {
            $q->where('trigger_type', $this->triggerFilter);
        })
        ->when($this->branchFilter, function ($q) {
            $q->where('branch_id', $this->branchFilter);
        })
        ->when($this->businessUnitFilter, function ($q) {
            $q->where('business_unit_id', $this->businessUnitFilter);
        })
        ->when($this->startDate, function ($q) {
            $q->whereDate('created_at', '>=', $this->startDate);
        })
        ->when($this->endDate, function ($q) {
            $q->whereDate('created_at', '<=', $this->endDate);
        })
        ->latest();

        $logs = $logsQuery->paginate($this->perPage);

        $branches = Branch::orderBy('name')->get();
        $businessUnits = BusinessUnit::all();

        return view('livewire.admin.accurate.sales-sync-logs-index', [
            'logs' => $logs,
            'totalPending' => $totalPending,
            'autoRecoveredYesterday' => $autoRecoveredYesterday,
            'autoRecoveredToday' => $autoRecoveredToday,
            'failedNeedAction' => $failedNeedAction,
            'totalSyncedToday' => $totalSyncedToday,
            'branches' => $branches,
            'businessUnits' => $businessUnits,
        ]);
    }
}

