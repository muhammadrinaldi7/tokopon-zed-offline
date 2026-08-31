<?php

namespace App\Livewire\Admin\SellPhone;

use App\Models\SellPhone;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = ''; // Digunakan juga untuk tab aktif
    public string $status_inspeksi = ''; // Default empty untuk view tab (dulu default 'pass')
    public string $date_filter = 'all'; // all, today, last_7_days, this_month

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function setTab($status)
    {
        $this->status = $status;
        $this->resetPage();
    }

    #[Layout('layouts.z')]
    public function render()
    {
        $activeUnitId = \App\Models\User::findOrFail(\Illuminate\Support\Facades\Auth::id())->getActiveBusinessUnitId();

        $baseQuery = SellPhone::with(['user', 'handledBy', 'businessUnit', 'inspections', 'openIssues'])
            ->where('business_unit_id', $activeUnitId);

        // Kumpulkan data untuk Summary Cards
        $payingQuery = clone $baseQuery;
        $payingSummary = $payingQuery->where('status', 'PAYING')
            ->selectRaw('COUNT(id) as count, SUM(appraised_value) as total')
            ->first();
            
        $inspectingCount = (clone $baseQuery)->where('status', 'INSPECTING')->count();
        $pendingApprovalCount = (clone $baseQuery)->where('status', 'PENDING_APPROVAL')->count();
        
        $completedSummary = (clone $baseQuery)->where('status', 'COMPLETED')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->selectRaw('COUNT(id) as count, SUM(appraised_value) as total')
            ->first();

        $openIssuesCount = \App\Models\SellPhoneIssue::where('status', 'OPEN')
            ->whereHas('sellPhone', function ($q) use ($activeUnitId) {
                $q->where('business_unit_id', $activeUnitId);
            })->count();

        $summary = [
            'paying_count' => $payingSummary->count ?? 0,
            'paying_total' => $payingSummary->total ?? 0,
            'inspecting_count' => $inspectingCount,
            'pending_approval_count' => $pendingApprovalCount,
            'completed_count' => $completedSummary->count ?? 0,
            'completed_total' => $completedSummary->total ?? 0,
            'open_issues_count' => $openIssuesCount,
        ];

        // Terapkan filter pada query utama
        $query = clone $baseQuery;

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($q2) {
                    $q2->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('phone_model', 'like', '%' . $this->search . '%')
                    ->orWhere('phone_brand', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . str_replace('#SPL-', '', $this->search) . '%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->status_inspeksi) {
            $query->whereHas('inspections', function ($q) {
                $q->where('verdict', $this->status_inspeksi);
            });
        }

        if ($this->date_filter !== 'all') {
            if ($this->date_filter === 'today') {
                $query->whereDate('created_at', now()->toDateString());
            } elseif ($this->date_filter === 'last_7_days') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($this->date_filter === 'this_month') {
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
            }
        }

        return view('livewire.admin.sell-phone.index', [
            'sellPhones' => $query->latest()->paginate(10),
            'summary' => $summary
        ]);
    }
}
