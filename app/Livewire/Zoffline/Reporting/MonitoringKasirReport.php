<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\OrderCashSettlement;
use App\Models\Branch;
use App\Models\BusinessUnit;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.z')]
class MonitoringKasirReport extends Component
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

    private function getBaseQuery()
    {
        $query = OrderCashSettlement::query()
            ->select('order_cash_settlements.*')
            ->join('orders', 'order_cash_settlements.order_id', '=', 'orders.id')
            ->whereDate('order_cash_settlements.created_at', '>=', $this->startDate)
            ->whereDate('order_cash_settlements.created_at', '<=', $this->endDate);

        if (!empty($this->businessUnitFilter)) {
            $query->whereHas('order.businessUnit', function ($q) {
                $q->where('name', $this->businessUnitFilter);
            });
        }

        if (!empty($this->branchFilter)) {
            $query->whereHas('order.branch', function ($q) {
                $q->where('name', $this->branchFilter);
            });
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('handledBy', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('monitoringBy', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('orders.order_number', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function getGroupedQuery()
    {
        return clone $this->getBaseQuery()
            ->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')
            ->select([
                \Illuminate\Support\Facades\DB::raw('DATE(order_cash_settlements.created_at) as date'),
                'branches.name as branch_name',
                'order_cash_settlements.handled_by',
                'order_cash_settlements.monitoring_by',
                \Illuminate\Support\Facades\DB::raw('SUM(order_cash_settlements.nominal_tunai) as total_tunai'),
                \Illuminate\Support\Facades\DB::raw('SUM(order_cash_settlements.nominal_settle) as total_settle'),
                \Illuminate\Support\Facades\DB::raw('SUM(order_cash_settlements.selisih) as total_selisih'),
                \Illuminate\Support\Facades\DB::raw('COUNT(order_cash_settlements.id) as total_struk')
            ])
            ->with(['handledBy', 'monitoringBy'])
            ->groupBy(
                \Illuminate\Support\Facades\DB::raw('DATE(order_cash_settlements.created_at)'),
                'branches.name',
                'order_cash_settlements.handled_by',
                'order_cash_settlements.monitoring_by'
            )
            ->orderBy('date', 'desc');
    }

    public function getDetailedQuery()
    {
        return clone $this->getBaseQuery()
            ->with(['handledBy', 'monitoringBy', 'order.branch'])
            ->orderBy('order_cash_settlements.created_at', 'desc')
            ->orderBy('order_cash_settlements.id', 'desc');
    }

    public function getSummaryProperty()
    {
        $query = clone $this->getBaseQuery();

        return [
            'total_settlements' => $query->count(),
            'total_tunai'       => (float) $query->sum('order_cash_settlements.nominal_tunai'),
            'total_settle'      => (float) $query->sum('order_cash_settlements.nominal_settle'),
            'total_selisih'     => (float) $query->sum('order_cash_settlements.selisih'),
        ];
    }

    public function exportXls()
    {
        $filters = [
            'startDate'          => $this->startDate,
            'endDate'            => $this->endDate,
            'businessUnitFilter' => $this->businessUnitFilter,
            'branchFilter'       => $this->branchFilter,
            'search'             => $this->search,
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MonitoringKasirExport($filters), 
            'Laporan_Monitoring_Kasir_' . date('Y-m-d_H-i') . '.xlsx'
        );
    }

    public function render()
    {
        return view('livewire.zoffline.reporting.monitoring-kasir-report', [
            'settlements' => $this->getGroupedQuery()->paginate(15),
            'availableBranches' => $this->availableBranches,
            'availableBusinessUnits' => $this->availableBusinessUnits,
            'summary' => $this->summary
        ]);
    }
}
