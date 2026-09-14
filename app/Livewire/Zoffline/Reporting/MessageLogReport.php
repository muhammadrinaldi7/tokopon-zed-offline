<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\MessageLogExport;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\MessageLog;
use App\Services\MessageDispatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.z', ['title' => 'Laporan Pengiriman Pesan (WA & Email)'])]
class MessageLogReport extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'this_month';
    public string $startDate = '';
    public string $endDate = '';
    public string $channelFilter = ''; // '', 'whatsapp', 'email'
    public string $statusFilter = '';  // '', '1' (sent), '0' (failed)
    public string $businessUnitFilter = '';
    public string $branchFilter = '';
    public string $search = '';

    // Detail Modal State
    public bool $showDetailModal = false;
    public ?MessageLog $selectedLog = null;

    public function mount()
    {
        $this->applyDateRange();

        $user = Auth::user();
        if ($user && !$this->isAdmin()) {
            $this->businessUnitFilter = (string) $user->getActiveBusinessUnitId();
            if ($user->branch_id) {
                $this->branchFilter = (string) $user->branch_id;
            }
        }
    }

    public function updatedDateRange()
    {
        $this->applyDateRange();
        $this->resetPage();
    }

    public function updated($property)
    {
        if (in_array($property, ['startDate', 'endDate', 'channelFilter', 'statusFilter', 'businessUnitFilter', 'branchFilter', 'search'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->dateRange = 'this_month';
        $this->applyDateRange();
        $this->channelFilter = '';
        $this->statusFilter = '';
        $this->businessUnitFilter = '';
        $this->branchFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    protected function applyDateRange()
    {
        $now = Carbon::now();
        switch ($this->dateRange) {
            case 'today':
                $this->startDate = $now->copy()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = $now->copy()->subDay()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->subDay()->endOfDay()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_year':
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;
            case 'custom':
                // Do not override user custom dates
                break;
        }
    }

    protected function buildBaseQuery()
    {
        $query = MessageLog::with(['sentBy', 'businessUnit', 'branch', 'source']);

        if (!empty($this->startDate)) {
            $query->whereDate('sent_at', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $query->whereDate('sent_at', '<=', $this->endDate);
        }

        if (!empty($this->channelFilter)) {
            $query->channel($this->channelFilter);
        }

        if ($this->statusFilter !== '') {
            $query->sent((bool) $this->statusFilter);
        }

        if (!empty($this->businessUnitFilter)) {
            $query->where('business_unit_id', $this->businessUnitFilter);
        }

        if (!empty($this->branchFilter)) {
            $query->where('branch_id', $this->branchFilter);
        }

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        return $query;
    }

    public function viewDetails(int $id)
    {
        $this->selectedLog = MessageLog::with(['sentBy', 'businessUnit', 'branch', 'source'])->find($id);
        if ($this->selectedLog) {
            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    public function exportExcel()
    {
        $records = $this->buildBaseQuery()->latest('sent_at')->get();

        $data = $records->map(function ($log) {
            return [
                'sent_at' => $log->sent_at ? $log->sent_at->format('d/m/Y H:i') : '-',
                'channel' => $log->channel,
                'is_sent' => $log->is_sent,
                'reference_number' => $log->reference_number ?: '-',
                'recipient_name' => $log->recipient_name ?: '-',
                'recipient' => $log->recipient ?: '-',
                'subject' => $log->subject ?: '-',
                'content' => $log->content ?: '-',
                'attachment_name' => $log->attachment_name ?: '-',
                'sender_name' => $log->sentBy?->name ?: 'Sistem',
                'branch_name' => $log->branch?->name ?: '-',
                'business_unit_name' => $log->businessUnit?->name ?: '-',
                'error_message' => $log->error_message ?: '-',
            ];
        })->toArray();

        $filename = 'Laporan_Pesan_WA_Email_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new MessageLogExport($data), $filename);
    }

    public function isAdmin(): bool
    {
        $user = Auth::user();
        return (bool) ($user && $user->hasAnyRole(['superadmin', 'admin', 'director']));
    }

    public function canSync(): bool
    {
        $user = Auth::user();
        return (bool) ($user && ($user->can('sync-message-logs') || $this->isAdmin()));
    }

    public function syncHistoricalLogs()
    {
        if (!$this->canSync()) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin (sync-message-logs) untuk menjalankan sinkronisasi data lama.', type: 'error');
            return;
        }

        try {
            $result = app(MessageDispatchService::class)->backfillHistoricalLogs();
            $this->dispatch('toast', 
                title: 'Sinkronisasi Selesai', 
                message: "Berhasil menambahkan {$result['created']} data log lama ({$result['skipped']} data sudah ada).", 
                type: 'success'
            );
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal Sinkronisasi', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $logs = $this->buildBaseQuery()->latest('sent_at')->paginate(15);

        // KPI Counts
        $statsBase = $this->buildBaseQuery();
        $totalMessages = (clone $statsBase)->count();
        $totalWaSuccess = (clone $statsBase)->where('channel', 'whatsapp')->where('is_sent', true)->count();
        $totalEmailSuccess = (clone $statsBase)->where('channel', 'email')->where('is_sent', true)->count();
        $totalFailed = (clone $statsBase)->where('is_sent', false)->count();

        $businessUnits = BusinessUnit::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('livewire.zoffline.reporting.message-log-report', [
            'logs' => $logs,
            'businessUnits' => $businessUnits,
            'branches' => $branches,
            'stats' => [
                'total' => $totalMessages,
                'wa_success' => $totalWaSuccess,
                'email_success' => $totalEmailSuccess,
                'failed' => $totalFailed,
            ],
            'canSync' => $this->canSync(),
            'isAdmin' => $this->isAdmin(),
        ]);
    }
}
