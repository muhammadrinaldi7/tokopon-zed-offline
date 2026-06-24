<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StaffReport extends Component
{
    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $businessUnitFilter = '';

    public function mount()
    {
        $this->setDateRange();
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->setDateRange();
        }
    }

    public function updatedStartDate()
    {
        $this->dateRange = 'custom';
    }
    public function updatedEndDate()
    {
        $this->dateRange = 'custom';
    }
    public function updatedBusinessUnitFilter()
    { /* Trigger render */
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
            case 'this_year':
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getStaffPerformanceProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // Using eloquent collections due to JSON fields grouping complexity across databases
        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->when($this->businessUnitFilter, function ($query) {
                $query->where('business_unit_id', $this->businessUnitFilter);
            })
            ->with(['salesBy', 'payments.paymentMethod', 'payments.paymentMethodRate'])
            ->get();

        $grouped = $orders->groupBy('sales_id');

        $staffData = $grouped->map(function ($group) {
            $sales = $group->first()->salesBy;
            $totalGross = $group->sum('total_amount');
            $totalMdr = $group->sum('mdr_amount');

            return [
                'name' => $sales ? $sales->name : 'Walk-in / No Sales',
                'transactions' => $group->count(),
                'gross_revenue' => $totalGross,
                'net_revenue' => $group->sum('grand_total') - $totalMdr,
                'avg_transaction' => $group->count() > 0 ? ($totalGross / $group->count()) : 0
            ];
        })->sortByDesc('net_revenue')->values();

        return $staffData;
    }

    public function exportCsv()
    {
        $staffData = $this->staffPerformance;
        $csvFileName = 'kinerja_sales_' . $this->startDate . '_sd_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($staffData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'NAMA SALES/KARYAWAN',
                'TOTAL TRANSAKSI',
                'GROSS REVENUE (Rp)',
                'NET REVENUE (Rp)',
                'RATA-RATA TRANSAKSI (Rp)'
            ]);

            foreach ($staffData as $staff) {
                fputcsv($file, [
                    $staff['name'],
                    $staff['transactions'],
                    $staff['gross_revenue'],
                    $staff['net_revenue'],
                    round($staff['avg_transaction'])
                ]);
            }
            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        return view('livewire.zoffline.reporting.staff-report', [
            'staffPerformance' => $this->staffPerformance
        ])->layout('layouts.z');
    }
}
