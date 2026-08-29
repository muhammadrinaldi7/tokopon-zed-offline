<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\WarrantyClaim;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturnReportExport;

#[Layout('layouts.z')]
class ReturnReport extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $status = '';

    public $showDetailPanel = false;
    public $selectedClaimId = null;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStartDate()
    {
        $this->resetPage();
    }

    public function updatingEndDate()
    {
        $this->resetPage();
    }
    
    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function showDetail($id)
    {
        $this->selectedClaimId = $id;
        $this->showDetailPanel = true;
    }

    public function closeDetail()
    {
        $this->showDetailPanel = false;
        $this->selectedClaimId = null;
    }

    public function getSelectedClaimProperty()
    {
        if (!$this->selectedClaimId) return null;
        
        return WarrantyClaim::with([
            'customer.profile', 
            'warranty.orderItem.variant',
            'warranty.orderItem.order',
            'claimedBy',
            'approvedBy',
            'inspection',
            'receivingInspection'
        ])->find($this->selectedClaimId);
    }

    private function buildQuery()
    {
        return WarrantyClaim::with([
            'customer.profile', 
            'warranty.orderItem.variant',
            'warranty.orderItem.order',
            'warranty.orderItem.promos'
        ])
        ->when($this->startDate && $this->endDate, function ($q) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            return $q->where(function($dateQ) use ($start, $end) {
                 $dateQ->whereBetween('claimed_at', [$start, $end])
                       ->orWhereBetween('created_at', [$start, $end]);
            });
        })
        ->when($this->status, function ($q) {
            return $q->where('status', $this->status);
        })
        ->when($this->search, function ($q) {
            $term = '%' . $this->search . '%';
            $q->where(function ($query) use ($term) {
                $query->where('claim_number', 'like', $term)
                      ->orWhere('serial_number', 'like', $term)
                      ->orWhereHas('customer', function ($cq) use ($term) {
                          $cq->where('name', 'like', $term)
                             ->orWhereHas('profile', function ($pq) use ($term) {
                                 $pq->where('phone_number', 'like', $term);
                             });
                      });
            });
        })
        ->orderBy('created_at', 'desc');
    }

    public function exportExcel()
    {
        $claims = $this->buildQuery()->get();

        if ($claims->isEmpty()) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return null;
        }

        $filename = 'Laporan-Retur-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new ReturnReportExport($claims), $filename);
    }

    public function render()
    {
        $claims = $this->buildQuery()->paginate(20);

        return view('livewire.zoffline.reporting.return-report', [
            'claims' => $claims
        ]);
    }
}
