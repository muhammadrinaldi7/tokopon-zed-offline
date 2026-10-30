<?php

namespace App\Livewire\Admin\Accurate;

use App\Jobs\ProcessAccurateWebhookJob;
use App\Models\AccurateSyncIssue;
use App\Models\AccurateWebhookLog;
use App\Models\BusinessUnit;
use App\Services\SyncIssueRecorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class SyncIssuesIndex extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $statusFilter = 'open'; // 'open', 'resolved', 'ignored', ''
    public string $reasonFilter = '';
    public string $severityFilter = '';
    public string $businessUnitFilter = '';
    public int $perPage = 20;

    // Detail Modal State
    public bool $showDetailModal = false;
    public ?AccurateSyncIssue $selectedIssue = null;
    public string $resolutionNote = '';

    // Action Feedback
    public string $flashMessage = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'open'],
        'reasonFilter' => ['except' => ''],
        'severityFilter' => ['except' => ''],
        'businessUnitFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingReasonFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBusinessUnitFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'open';
        $this->reasonFilter = '';
        $this->severityFilter = '';
        $this->businessUnitFilter = '';
        $this->resetPage();
    }

    public function showDetail(int $issueId): void
    {
        $this->selectedIssue = AccurateSyncIssue::with(['businessUnit', 'webhookLog', 'resolver'])->find($issueId);
        $this->resolutionNote = $this->selectedIssue?->resolution_note ?? '';
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedIssue = null;
        $this->resolutionNote = '';
    }

    public function markResolved(int $issueId): void
    {
        $issue = AccurateSyncIssue::find($issueId);
        if ($issue) {
            $issue->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
                'resolution_note' => $this->resolutionNote ?: ($issue->resolution_note ?: 'Ditandai selesai secara manual oleh admin.'),
            ]);

            $this->flashMessage = "Issue [{$issue->reason_code}] berhasil ditandai selesai.";
            if ($this->selectedIssue && $this->selectedIssue->id === $issueId) {
                $this->showDetailModal = false;
            }
        }
    }

    public function markIgnored(int $issueId): void
    {
        $issue = AccurateSyncIssue::find($issueId);
        if ($issue) {
            $issue->update([
                'status' => 'ignored',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
                'resolution_note' => $this->resolutionNote ?: ($issue->resolution_note ?: 'Diabaikan oleh admin.'),
            ]);

            $this->flashMessage = "Issue [{$issue->reason_code}] ditandai diabaikan.";
            if ($this->selectedIssue && $this->selectedIssue->id === $issueId) {
                $this->showDetailModal = false;
            }
        }
    }

    public function reopen(int $issueId): void
    {
        $issue = AccurateSyncIssue::find($issueId);
        if ($issue) {
            $issue->update([
                'status' => 'open',
                'resolved_at' => null,
                'resolved_by' => null,
            ]);

            $this->flashMessage = "Issue [{$issue->reason_code}] berhasil dibuka kembali.";
            if ($this->selectedIssue && $this->selectedIssue->id === $issueId) {
                $this->showDetailModal = false;
            }
        }
    }

    public function retryWebhook(int $webhookLogId): void
    {
        $log = AccurateWebhookLog::find($webhookLogId);
        if ($log) {
            $log->update(['status' => 'pending']);
            ProcessAccurateWebhookJob::dispatch($log->id);
            $this->flashMessage = "Webhook #{$webhookLogId} ({$log->event_type}) berhasil dikirim ulang ke antrean (Queue)!";
        }
    }

    public function render()
    {
        $query = AccurateSyncIssue::query()->with(['businessUnit', 'webhookLog']);

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (!empty($this->reasonFilter)) {
            $query->where('reason_code', $this->reasonFilter);
        }

        if (!empty($this->severityFilter)) {
            $query->where('severity', $this->severityFilter);
        }

        if (!empty($this->businessUnitFilter)) {
            $query->where('business_unit_id', $this->businessUnitFilter);
        }

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('item_no', 'like', "%{$s}%")
                    ->orWhere('serial_number', 'like', "%{$s}%")
                    ->orWhere('warehouse_name', 'like', "%{$s}%")
                    ->orWhere('event_type', 'like', "%{$s}%")
                    ->orWhere('reason_code', 'like', "%{$s}%")
                    ->orWhere('fingerprint', 'like', "%{$s}%");
            });
        }

        $issues = $query->orderByDesc('last_seen_at')->paginate($this->perPage);

        // KPI Counts
        $totalOpen = AccurateSyncIssue::where('status', 'open')->count();
        $criticalErrors = AccurateSyncIssue::where('status', 'open')
            ->whereIn('severity', ['critical', 'error'])
            ->count();
        $totalResolved = AccurateSyncIssue::where('status', 'resolved')->count();
        $totalIgnored = AccurateSyncIssue::where('status', 'ignored')->count();

        // Top reason
        $topReason = AccurateSyncIssue::where('status', 'open')
            ->select('reason_code', DB::raw('count(*) as count'))
            ->groupBy('reason_code')
            ->orderByDesc('count')
            ->first();

        // Distinct reason codes for dropdown
        $availableReasons = AccurateSyncIssue::select('reason_code')
            ->distinct()
            ->pluck('reason_code')
            ->toArray();

        $businessUnits = BusinessUnit::where('is_active', true)->get();

        return view('livewire.admin.accurate.sync-issues-index', [
            'issues' => $issues,
            'totalOpen' => $totalOpen,
            'criticalErrors' => $criticalErrors,
            'totalResolved' => $totalResolved,
            'totalIgnored' => $totalIgnored,
            'topReason' => $topReason,
            'availableReasons' => $availableReasons,
            'businessUnits' => $businessUnits,
        ])->layout('layouts.admin', ['title' => 'Sync Issues Tracker Accurate']);
    }
}
