<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use App\Models\ProductSerialNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProfitReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
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
            case 'this_year':
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getOrdersQueryProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        return Order::with([
            'user',
            'payments.paymentMethodRate',
            'items.variant'
        ])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('orders.order_number', 'like', '%' . $this->search . '%')
                        ->orWhere('orders.accurate_invoice_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($qc) {
                            $qc->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            })
            ->where('orders.business_unit_id', Auth::user()->getActiveBusinessUnitId())
            ->latest('orders.order_date');
    }

    private function calculateOrderProfit($order)
    {
        $netSales = $order->grand_total;
        $totalHpp = 0;

        foreach ($order->items as $item) {
            $sns = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));

            if (count($sns) > 0) {
                // Ambil total HPP dari tabel product_serial_numbers
                $hppFromSns = ProductSerialNumber::whereIn('serial_number', $sns)->sum('hpp');
                $totalHpp += $hppFromSns;
            } else {
                // Ambil base_cost dari tabel product_accurates
                $variant = $item->variant;
                if ($variant) {
                    if (get_class($variant) === \App\Models\ProductAccurate::class) {
                        $totalHpp += ((float) $variant->base_cost * $item->qty);
                    } elseif (method_exists($variant, 'accurateData') && $variant->accurateData) {
                        $totalHpp += ((float) $variant->accurateData->base_cost * $item->qty);
                    }
                }
            }
        }

        $totalMdr = 0;
        foreach ($order->payments as $payment) {
            if ($payment->status === 'PAID' && $payment->paymentMethodRate) {
                $mdrPercentage = (float) $payment->paymentMethodRate->mdr_percentage;
                $totalMdr += ($payment->amount * ($mdrPercentage / 100));
            }
        }

        $netProfit = $netSales - $totalHpp - $totalMdr;
        $profitMargin = $netSales > 0 ? ($netProfit / $netSales) * 100 : 0;

        $order->calculated_hpp = $totalHpp;
        $order->calculated_mdr = $totalMdr;
        $order->calculated_net_profit = $netProfit;
        $order->calculated_profit_margin = $profitMargin;

        return $order;
    }

    public function exportCsv()
    {
        $orders = $this->ordersQuery->get()->map(function ($order) {
            return $this->calculateOrderProfit($order);
        });

        $csvFileName = 'laporan_laba_rugi_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;

        return response()->streamDownload(function () use ($orders, $separator) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE ACCURATE',
                'PELANGGAN',
                'CABANG',
                'GROSS SALES (Rp)',
                'TOTAL DISKON (Rp)',
                'NET SALES (Rp)',
                'TOTAL HPP (Rp)',
                'TOTAL MDR (Rp)',
                'NET PROFIT (Rp)',
                'MARGIN LABA (%)',
            ], $separator);

            foreach ($orders as $o) {
                fputcsv($file, [
                    $o->order_date ? \Carbon\Carbon::parse($o->order_date)->format('Y-m-d') : '-',
                    $o->order_number,
                    $o->accurate_invoice_no ?? '-',
                    $o->user->name ?? 'Pelanggan Umum',
                    $o->shipping_address_snapshot['store'] ?? '-',
                    round($o->total_amount, 2),
                    round($o->discount_amount, 2),
                    round($o->grand_total, 2),
                    round($o->calculated_hpp, 2),
                    round($o->calculated_mdr, 2),
                    round($o->calculated_net_profit, 2),
                    round($o->calculated_profit_margin, 2),
                ], $separator);
            }

            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        // 1. Dapatkan orders (Paginasi)
        $paginatedOrders = (clone $this->ordersQuery)->paginate(20);

        // 2. Hitung metrics untuk setiap item di paginasi saat ini
        $paginatedOrders->getCollection()->transform(function ($order) {
            return $this->calculateOrderProfit($order);
        });

        // 3. Hitung Global Summary
        $allOrdersForSummary = collect();
        (clone $this->ordersQuery)->chunk(100, function ($chunk) use (&$allOrdersForSummary) {
            foreach ($chunk as $o) {
                $allOrdersForSummary->push($this->calculateOrderProfit($o));
            }
        });

        $summary = [
            'total_net_sales' => $allOrdersForSummary->sum('grand_total'),
            'total_hpp' => $allOrdersForSummary->sum('calculated_hpp'),
            'total_mdr' => $allOrdersForSummary->sum('calculated_mdr'),
            'total_net_profit' => $allOrdersForSummary->sum('calculated_net_profit'),
        ];

        $summary['global_profit_margin'] = $summary['total_net_sales'] > 0
            ? ($summary['total_net_profit'] / $summary['total_net_sales']) * 100
            : 0;

        return view('livewire.zoffline.reporting.profit-report', [
            'orders' => $paginatedOrders,
            'summary' => $summary,
            'branches' => \App\Models\Branch::all()
        ])->layout('layouts.z');
    }
}
