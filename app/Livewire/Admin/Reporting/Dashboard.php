<?php

namespace App\Livewire\Admin\Reporting;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $dateRange = 'today';
    public $startDate;
    public $endDate;
    public $branchFilter = '';
    public $businessUnitFilter = '';

    public $businessUnits = [];

    public function mount()
    {
        $this->setDateRange();
        $this->businessUnits = \App\Models\BusinessUnit::where('is_active', true)->get();
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->setDateRange();
        }
    }

    public function updatedStartDate() { $this->dateRange = 'custom'; }
    public function updatedEndDate() { $this->dateRange = 'custom'; }
    public function updatedBranchFilter() { /* Automatically triggers render */ }
    public function updatedBusinessUnitFilter() { /* Automatically triggers render */ }

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
            case 'last_7_days':
                $this->startDate = $now->copy()->subDays(6)->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
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
        // Tetap ada jika diperlukan
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $query = Order::whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->when($this->branchFilter, function($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            });

        // --- 1. KPI OVERVIEW ---
        $totalGross = (clone $query)->sum('total_amount');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $totalMdr = (clone $query)->sum('mdr_amount');
        $totalNet = (clone $query)->sum('grand_total') - $totalMdr;
        $totalTransactions = (clone $query)->count();

        $orderIds = (clone $query)->pluck('id');
        $totalQty = OrderItem::whereIn('order_id', $orderIds)->sum('qty');

        // --- 2. MTD (Month To Date) SECTION ---
        $now = now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $sameDayLastMonth = $now->copy()->subMonth();

        $mtdQuery = Order::where('order_status', 'COMPLETED')
            ->whereBetween('created_at', [$startOfThisMonth, $now])
            ->when($this->branchFilter, function($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            });
        
        $lastMtdQuery = Order::where('order_status', 'COMPLETED')
            ->whereBetween('created_at', [$startOfLastMonth, $sameDayLastMonth])
            ->when($this->branchFilter, function($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            });

        $mtdNetSales = (clone $mtdQuery)->sum('grand_total') - (clone $mtdQuery)->sum('mdr_amount');
        $lastMtdNetSales = (clone $lastMtdQuery)->sum('grand_total') - (clone $lastMtdQuery)->sum('mdr_amount');
        
        $mtdTransactions = (clone $mtdQuery)->count();
        $lastMtdTransactions = (clone $lastMtdQuery)->count();

        $mtdOrderIds = (clone $mtdQuery)->pluck('id');
        $lastMtdOrderIds = (clone $lastMtdQuery)->pluck('id');
        
        $mtdQty = OrderItem::whereIn('order_id', $mtdOrderIds)->sum('qty');
        $lastMtdQty = OrderItem::whereIn('order_id', $lastMtdOrderIds)->sum('qty');

        $mtdDiscount = (clone $mtdQuery)->sum('discount_amount');
        $lastMtdDiscount = (clone $lastMtdQuery)->sum('discount_amount');

        $calculateGrowth = function($current, $last) {
            if ($last > 0) {
                return (($current - $last) / $last) * 100;
            }
            return 0;
        };

        $mtdData = [
            'net_sales' => ['current' => $mtdNetSales, 'last' => $lastMtdNetSales, 'growth' => round($calculateGrowth($mtdNetSales, $lastMtdNetSales), 2)],
            'transactions' => ['current' => $mtdTransactions, 'last' => $lastMtdTransactions, 'growth' => round($calculateGrowth($mtdTransactions, $lastMtdTransactions), 2)],
            'qty' => ['current' => $mtdQty, 'last' => $lastMtdQty, 'growth' => round($calculateGrowth($mtdQty, $lastMtdQty), 2)],
            'discount' => ['current' => $mtdDiscount, 'last' => $lastMtdDiscount, 'growth' => round($calculateGrowth($mtdDiscount, $lastMtdDiscount), 2)],
        ];

        // Fetch all orders for chart processing
        $orders = (clone $query)->with(['paymentMethod', 'salesBy'])->get();

        // --- 3. TREND DATA (Line/Area Chart) ---
        $daysDiff = $start->diffInDays($end);
        $isSingleDay = $start->isSameDay($end);
        $isYearly = $daysDiff > 60;

        $trendDataRaw = $orders->groupBy(function($order) use ($isSingleDay, $isYearly) {
            if ($isSingleDay) return $order->created_at->format('H:00');
            if ($isYearly) return $order->created_at->format('M Y');
            return $order->created_at->format('d M');
        })->map(function($group) {
            return $group->sum('grand_total');
        })->toArray();

        $filledTrendData = [];
        if ($isSingleDay) {
            // Fill store hours with 0
            for ($i = 8; $i <= 22; $i++) {
                $hourStr = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $filledTrendData[$hourStr] = $trendDataRaw[$hourStr] ?? 0;
            }
            // Add any data outside 8-22
            foreach ($trendDataRaw as $k => $v) {
                $filledTrendData[$k] = $v;
            }
            ksort($filledTrendData);
        } elseif ($isYearly) {
            $currentDate = $start->copy()->startOfMonth();
            while ($currentDate->lte($end)) {
                $dateStr = $currentDate->format('M Y');
                $filledTrendData[$dateStr] = $trendDataRaw[$dateStr] ?? 0;
                $currentDate->addMonth();
            }
        } else {
            $currentDate = $start->copy();
            while ($currentDate->lte($end)) {
                $dateStr = $currentDate->format('d M');
                $filledTrendData[$dateStr] = $trendDataRaw[$dateStr] ?? 0;
                $currentDate->addDay();
            }
        }

        $trendData = [
            'labels' => array_keys($filledTrendData),
            'series' => array_values($filledTrendData),
        ];

        // --- 4. BRAND PROPORTION (Donut Chart) ---
        $orderItemsForBrand = OrderItem::with('variant.product.brand')
            ->whereIn('order_id', $orderIds)
            ->get();

        $brandDataRaw = $orderItemsForBrand->groupBy(function($item) {
            return $item->variant?->product?->brand?->name ?? 'Unknown';
        })->map(function($group) {
            return [
                'name' => $group->first()->variant?->product?->brand?->name ?? 'Unknown',
                'total' => $group->sum(function($item) { return $item->price_at_checkout * $item->qty; })
            ];
        })->sortByDesc('total')->values();

        $brandProportionData = [
            'labels' => $brandDataRaw->pluck('name')->toArray(),
            'series' => $brandDataRaw->pluck('total')->toArray(),
        ];

        // --- 5. PAYMENT METHOD PROPORTION (Donut Chart) ---
        $pmData = $orders->groupBy('payment_method_id')->map(function($group) {
            $pm = $group->first()->paymentMethod;
            return [
                'name' => $pm ? $pm->name : 'Unknown',
                'total' => $group->sum('grand_total')
            ];
        })->sortByDesc('total')->values();

        $paymentMethodData = [
            'labels' => $pmData->pluck('name')->toArray(),
            'series' => $pmData->pluck('total')->toArray(),
        ];

        // --- 6. TOP LISTS (Products, Branches, Sales) ---
        $topProducts = $orderItemsForBrand->groupBy('product_variant_id')->map(function($group) {
                $first = $group->first();
                $variant = $first->variant;
                $name = $variant?->name ?? $variant?->product?->name ?? $first->product_name ?? 'Unknown Product';
                $sku = $variant?->sku ?? '-';

                return [
                    'sku' => $sku,
                    'name' => $name,
                    'total_qty' => $group->sum('qty'),
                    'total_revenue' => $group->sum('subtotal')
                ];
            })
            ->sortByDesc('total_qty')
            ->take(5)
            ->values()
            ->toArray();

        $topBranches = $orders->groupBy(function($order) {
            return $order->shipping_address_snapshot['store'] ?? 'Unknown';
        })->map(function($group) {
            return [
                'name' => $group->first()->shipping_address_snapshot['store'] ?? 'Unknown',
                'total_transactions' => $group->count(),
                'total_revenue' => $group->sum('grand_total')
            ];
        })->sortByDesc('total_revenue')->take(5)->values()->toArray();

        $topSales = $orders->groupBy('sales_id')->map(function($group) {
            $sales = $group->first()->salesBy;
            return [
                'name' => $sales ? $sales->name : 'No Sales',
                'total_transactions' => $group->count(),
                'total_revenue' => $group->sum('grand_total')
            ];
        })->sortByDesc('total_revenue')->take(5)->values()->toArray();


        $availableBranches = Branch::orderBy('name')->pluck('name');

        // Update charts dynamically via event
        $this->dispatch('update-charts', [
            'trend' => $trendData,
            'brandProportion' => $brandProportionData,
            'paymentMethod' => $paymentMethodData
        ]);

        return view('livewire.admin.reporting.dashboard', [
            'totalGross' => $totalGross,
            'totalDiscount' => $totalDiscount,
            'totalNet' => $totalNet,
            'totalTransactions' => $totalTransactions,
            'totalQty' => $totalQty,
            'mtdData' => $mtdData,
            'trendData' => $trendData,
            'brandProportionData' => $brandProportionData,
            'paymentMethodData' => $paymentMethodData,
            'topBranches' => $topBranches,
            'topSales' => $topSales,
            'topProducts' => $topProducts,
            'availableBranches' => $availableBranches
        ])->layout('layouts.admin');
    }
}
