<?php

namespace App\Livewire\Admin\Reporting;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;

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

    public function updatedStartDate() { $this->dateRange = 'custom'; }
    public function updatedEndDate() { $this->dateRange = 'custom'; }

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

    public function exportCsv()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->with(['sales', 'paymentMethod', 'items'])
            ->get();

        $csvFileName = 'laporan_penjualan_' . $this->startDate . '_sampai_' . $this->endDate . '.csv';

        return response()->streamDownload(function() use($orders) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'Order ID', 'Tanggal', 'Nomor Invoice', 'Total Gross (Rp)', 
                'Total Diskon (Rp)', 'Total MDR (Rp)', 'Total Net (Rp)', 
                'Metode Pembayaran', 'Cabang', 'Sales/Kasir', 'Daftar Produk (SKU - Nama - Qty)'
            ]);

            // Data
            foreach ($orders as $order) {
                $itemsStr = $order->items->map(function($item) {
                    return $item->sku . ' - ' . $item->name . ' (' . $item->qty . 'x)';
                })->implode(' | ');

                $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->accurate_invoice_no ?? '-',
                    $order->total_amount,
                    $order->discount_amount,
                    $order->mdr_amount,
                    $order->grand_total - $order->mdr_amount,
                    $order->paymentMethod ? $order->paymentMethod->name : '-',
                    $branch,
                    $order->sales ? $order->sales->name : '-',
                    $itemsStr
                ]);
            }

            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $query = Order::whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED');

        // 1. Sales Overview
        $totalGross = (clone $query)->sum('total_amount');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $totalMdr = (clone $query)->sum('mdr_amount');
        $totalNet = (clone $query)->sum('grand_total') - $totalMdr;
        $totalTransactions = (clone $query)->count();

        // Fetch all orders for memory processing (safe if not millions)
        // This avoids complex DB-specific JSON extractions for sqlite vs mysql
        $orders = (clone $query)->with(['paymentMethod', 'salesBy'])->get();

        // 2. Trend (Bar Chart Data)
        $trendDataRaw = $orders->groupBy(function($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function($group) {
            return $group->sum('grand_total');
        })->sortKeys();

        $trendData = [
            'labels' => $trendDataRaw->keys()->toArray(),
            'series' => $trendDataRaw->values()->toArray(),
        ];

        // 3. Payment Methods
        $paymentMethodDataRaw = $orders->groupBy('payment_method_id')->map(function($group) {
            $pm = $group->first()->paymentMethod;
            return [
                'name' => $pm ? $pm->name : 'Unknown',
                'total' => $group->sum('grand_total'),
                'count' => $group->count()
            ];
        })->sortByDesc('total')->values()->toArray();

        // 4. Branch Performance
        $branchDataRaw = $orders->groupBy(function($order) {
            return $order->shipping_address_snapshot['store'] ?? 'Unknown';
        })->map(function($group) {
            return [
                'store' => $group->first()->shipping_address_snapshot['store'] ?? 'Unknown',
                'total' => $group->sum('grand_total'),
                'count' => $group->count()
            ];
        })->sortByDesc('total')->values()->toArray();

        // 5. Sales Performance
        $salesDataRaw = $orders->groupBy('sales_id')->map(function($group) {
            $sales = $group->first()->salesBy;
            return [
                'name' => $sales ? $sales->name : 'No Sales',
                'total' => $group->sum('grand_total'),
                'count' => $group->count()
            ];
        })->sortByDesc('total')->values()->toArray();

        // 6. Top Products
        $orderIds = $orders->pluck('id');
        $topProductsRaw = OrderItem::whereIn('order_id', $orderIds)
            ->get()
            ->groupBy('sku')
            ->map(function($group) {
                return [
                    'sku' => $group->first()->sku,
                    'name' => $group->first()->name,
                    'total_qty' => $group->sum('qty'),
                    'total_revenue' => $group->sum(function($item) { return $item->price * $item->qty; })
                ];
            })
            ->sortByDesc('total_qty')
            ->take(10)
            ->values()
            ->toArray();

        return view('livewire.admin.reporting.dashboard', [
            'totalGross' => $totalGross,
            'totalDiscount' => $totalDiscount,
            'totalMdr' => $totalMdr,
            'totalNet' => $totalNet,
            'totalTransactions' => $totalTransactions,
            'trendData' => $trendData,
            'paymentMethodData' => $paymentMethodDataRaw,
            'branchData' => $branchDataRaw,
            'salesData' => $salesDataRaw,
            'topProducts' => $topProductsRaw,
        ])->layout('layouts.admin');
    }
}
