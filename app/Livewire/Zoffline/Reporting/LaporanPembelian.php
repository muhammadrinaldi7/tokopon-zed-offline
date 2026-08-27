<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\SellPhone;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Carbon;

#[Layout('layouts.z')]
class LaporanPembelian extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStartDate = '';
    public $filterEndDate = '';
    public $filterBranchId = '';
    public $filterStatus = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStartDate()
    {
        $this->resetPage();
    }

    public function updatingFilterEndDate()
    {
        $this->resetPage();
    }

    public function updatingFilterBranchId()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function exportXls()
    {
        $filters = [
            'search' => $this->search,
            'filterStartDate' => $this->filterStartDate,
            'filterEndDate' => $this->filterEndDate,
            'filterBranchId' => $this->filterBranchId,
            'filterStatus' => $this->filterStatus,
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LaporanPembelianExport($filters), 'Laporan_Pembelian_' . date('Y-m-d_H-i') . '.xlsx');
    }

    public function back()
    {
        return $this->redirectRoute('zoffline.reporting', navigate: true);
    }

    public function render()
    {
        // Get all branches that belong to Business Unit 2
        $availableBranches = Branch::where('business_unit_id', 2)->orderBy('name')->get();

        // Base query for Laporan Pembelian (SellPhone) for Business Unit 2
        $query = SellPhone::with(['user', 'handledBy', 'branch'])
            ->where('business_unit_id', 2);

        // Apply Search Filter (Invoice or Phone Model)
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhere('phone_model', 'like', '%' . $this->search . '%')
                  ->orWhere('phone_brand', 'like', '%' . $this->search . '%');
            });
        }

        // Apply Date Filters
        if (!empty($this->filterStartDate)) {
            $query->whereDate('created_at', '>=', $this->filterStartDate);
        }

        if (!empty($this->filterEndDate)) {
            $query->whereDate('created_at', '<=', $this->filterEndDate);
        }

        // Apply Branch Filter
        if (!empty($this->filterBranchId)) {
            $query->where('branch_id', $this->filterBranchId);
        }

        // Apply Status Filter
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $purchases = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.zoffline.reporting.laporan-pembelian', compact('purchases', 'availableBranches'));
    }
}
