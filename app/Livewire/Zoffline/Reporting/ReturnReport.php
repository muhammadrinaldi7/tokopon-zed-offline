<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\WarrantyClaim;
use Carbon\Carbon;

#[Layout('layouts.z')]
class ReturnReport extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $status = '';

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

    public function render()
    {
        $query = WarrantyClaim::with([
            'customer.profile', 
            'warranty.orderItem.variant'
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

        $claims = $query->paginate(20);

        return view('livewire.zoffline.reporting.return-report', [
            'claims' => $claims
        ]);
    }
}
