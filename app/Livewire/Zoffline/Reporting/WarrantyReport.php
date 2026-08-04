<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Branch;
use App\Models\Warranty;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class WarrantyReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
    public $branchId;
    public $csvSeparator = ';';
    public $activationStatus = 'activated';

    public function mount()
    {
        $this->setDateRange();
    }

    public function updatedBranchId()
    {
        $this->resetPage();
    }
    public function updatedActivationStatus()
    {
        $this->resetPage();
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

    public function getWarrantiesQueryProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        return \App\Models\OrderItemSerialNumber::with(['orderItem.order.salesBy', 'orderItem.order.user', 'orderItem.order.branch', 'orderItem.variant', 'warranty.policy', 'warranty.deviceInspection.inspector'])
            ->whereHas('orderItem.order', function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            })
            // Filter hanya barang yang berpotensi memiliki garansi (misal Handphone)
            // Bisa disesuaikan jika kategori di Variant bisa diakses, untuk saat ini asumsikan semua serial number
            ->when($this->activationStatus === 'activated', function($q) {
                $q->whereHas('warranty');
            })
            ->when($this->activationStatus === 'unactivated', function($q) {
                $q->whereDoesntHave('warranty');
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('serial_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('orderItem.order.user', function ($qc) {
                            $qc->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('orderItem.order.salesBy', function ($qs) {
                            $qs->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('warranty.deviceInspection.inspector', function ($qi) {
                            $qi->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->branchId, function ($query) {
                $query->whereHas('orderItem.order', function ($q) {
                    $q->where('branch_id', $this->branchId);
                });
            })
            ->latest('id');
    }

    public function exportCsv()
    {
        $warranties = $this->warrantiesQuery->get();
        $csvFileName = 'laporan_aktivasi_garansi_' . $this->startDate . '_sd_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($warranties) {
            $file = fopen('php://output', 'w');

            // Output UTF-8 BOM
            fputs($file, "\xEF\xBB\xBF");

            $headers = [
                'Tgl Transaksi',
                'Tgl Aktivasi',
                'No. Order',
                'Cabang',
                'SN / Perangkat',
                'Kategori',
                'Nama Barang',
                'Nama Pelanggan',
                'Tipe Garansi',
                'Inspektur (QC)',
                'Promotor (Sales)',
                'Status'
            ];

            fputcsv($file, $headers, $this->csvSeparator);

            foreach ($warranties as $w) {
                $orderDate = $w->orderItem->order->order_date ? $w->orderItem->order->order_date->format('Y-m-d') : ($w->orderItem->order->created_at ? $w->orderItem->order->created_at->format('Y-m-d') : '-');
                $category = $w->orderItem->variant->categoryName ?? '-';
                $productName = $w->orderItem->variant->name ?? '-';
                
                $orderNumber = $w->orderItem->order->order_number ?? '-';
                $branch = $w->orderItem->order->branch->name ?? '-';
                $customerName = $w->orderItem->order->user->name ?? '-';
                $policyName = $w->warranty->policy->name ?? '-';
                $inspector = $w->warranty->deviceInspection->inspector->name ?? '-';
                $promotor = $w->orderItem->order->salesBy->name ?? '-';

                $activatedAt = $w->warranty && $w->warranty->activated_at ? $w->warranty->activated_at->format('Y-m-d H:i:s') : '-';
                $status = $w->warranty ? $w->warranty->status : 'BELUM AKTIVASI';

                fputcsv($file, [
                    $orderDate,
                    $activatedAt,
                    $orderNumber,
                    $branch,
                    $w->serial_number,
                    $category,
                    $productName,
                    $customerName,
                    $policyName,
                    $inspector,
                    $promotor,
                    $status
                ], $this->csvSeparator);
            }

            fclose($file);
        }, $csvFileName, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    #[Layout('layouts.z')]
    public function render()
    {
        $branches = Branch::where('business_unit_id', Auth::user()->business_unit_id)->get();
        
        // Clone the query builder so paginate() doesn't mutate the query for get()
        $baseQuery = clone $this->warrantiesQuery;
        
        $warranties = $this->warrantiesQuery->paginate(20);
        $allWarranties = $baseQuery->get();

        $totalActivations = $allWarranties->count();

        // Hitung Performa
        $inspectors = collect();
        $promotors = collect();

        foreach ($allWarranties as $w) {
            if ($w->warranty && $w->warranty->deviceInspection && $w->warranty->deviceInspection->inspector) {
                $name = $w->warranty->deviceInspection->inspector->name;
                $inspectors[$name] = ($inspectors[$name] ?? 0) + 1;
            }
            if ($w->orderItem && $w->orderItem->order && $w->orderItem->order->salesBy) {
                $name = $w->orderItem->order->salesBy->name;
                $promotors[$name] = ($promotors[$name] ?? 0) + 1;
            }
        }

        $topInspectors = $inspectors->sortDesc()->take(5);
        $topPromotors = $promotors->sortDesc()->take(5);

        return view('livewire.zoffline.reporting.warranty-report', [
            'warranties' => $warranties,
            'totalActivations' => $totalActivations,
            'topInspectors' => $topInspectors,
            'topPromotors' => $topPromotors,
            'branches' => $branches,
        ]);
    }
}
