<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\CashierShift;
use App\Models\Branch;
use App\Models\BusinessUnit;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class ClosingKasirReport extends Component
{
    use WithPagination;

    public $dateRange = 'today';
    public $startDate;
    public $endDate;
    public $search = '';
    public $businessUnitFilter = '';
    public $branchFilter = '';
    public $csvSeparator = ';';

    public function mount()
    {
        $this->setDateRange();
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->setDateRange();
        }
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->dateRange = 'custom';
        $this->resetPage();
    }
    
    public function updatedEndDate()
    {
        $this->dateRange = 'custom';
        $this->resetPage();
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedBusinessUnitFilter()
    {
        $this->branchFilter = '';
        $this->resetPage();
    }
    
    public function updatedBranchFilter()
    {
        $this->resetPage();
    }

    private function setDateRange()
    {
        $now = now();
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
            case 'last_month':
                $this->startDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_year':
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getAvailableBusinessUnitsProperty()
    {
        return BusinessUnit::pluck('name')->toArray();
    }

    public function getAvailableBranchesProperty()
    {
        if (!empty($this->businessUnitFilter)) {
            return Branch::whereHas('businessUnit', function ($q) {
                $q->where('name', $this->businessUnitFilter);
            })->pluck('name')->toArray();
        }
        return Branch::pluck('name')->toArray();
    }

    private function buildQuery()
    {
        $query = CashierShift::with(['user', 'branch', 'businessUnit'])
            ->whereDate('shift_date', '>=', $this->startDate)
            ->whereDate('shift_date', '<=', $this->endDate);

        if (!empty($this->businessUnitFilter)) {
            $query->whereHas('businessUnit', function ($q) {
                $q->where('name', $this->businessUnitFilter);
            });
        }

        if (!empty($this->branchFilter)) {
            $query->whereHas('branch', function ($q) {
                $q->where('name', $this->branchFilter);
            });
        }

        if (!empty($this->search)) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('shift_date', 'desc')->orderBy('id', 'desc');
    }

    public function getSummaryProperty()
    {
        $query = $this->buildQuery();

        return [
            'total_shifts'    => $query->count(),
            'modal_awal'      => (float) $query->sum('starting_cash'),
            'expected_cash'   => (float) $query->sum('expected_cash'),
            'actual_cash'     => (float) $query->sum('actual_cash'),
            'cash_difference' => (float) $query->sum('cash_difference'),
        ];
    }

    public function exportCsv()
    {
        $query = $this->buildQuery();
        $shifts = $query->get();

        $sep = $this->csvSeparator;
        $headers = [
            'ID Shift',
            'Tanggal',
            'Waktu Buka',
            'Waktu Tutup',
            'Kasir',
            'Cabang',
            'Status',
            'Modal Awal',
            'Tunai Sistem (Expected)',
            'Setoran Fisik (Actual)',
            'Selisih'
        ];

        $csvData = implode($sep, $headers) . "\n";

        foreach ($shifts as $s) {
            $row = [
                $s->id,
                $s->shift_date ? $s->shift_date->format('Y-m-d') : '',
                $s->opened_at ? $s->opened_at->format('H:i:s') : '',
                $s->closed_at ? $s->closed_at->format('H:i:s') : '',
                $s->user ? str_replace($sep, ' ', $s->user->name) : '-',
                $s->branch ? str_replace($sep, ' ', $s->branch->name) : '-',
                strtoupper($s->status),
                $s->starting_cash,
                $s->expected_cash,
                $s->actual_cash,
                $s->cash_difference
            ];
            $csvData .= implode($sep, $row) . "\n";
        }

        $fileName = 'Laporan_Closing_Kasir_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $fileName);
    }

    public function render()
    {
        return view('livewire.zoffline.reporting.closing-kasir-report', [
            'shifts' => $this->buildQuery()->paginate(15),
            'availableBranches' => $this->availableBranches,
            'availableBusinessUnits' => $this->availableBusinessUnits,
            'summary' => $this->summary
        ]);
    }
}
