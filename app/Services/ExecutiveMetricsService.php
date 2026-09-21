<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExecutiveMetricsService
{
    /**
     * Parse date range string into Carbon start and end dates.
     */
    public function parseDateRange(?string $dateRange, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = now();
        $range = $dateRange ?: 'this_month';

        switch ($range) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;
            case 'last_7_days':
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'this_quarter':
                $start = $now->copy()->firstOfQuarter();
                $end = $now->copy()->lastOfQuarter();
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'last_year':
                $start = $now->copy()->subYear()->startOfYear();
                $end = $now->copy()->subYear()->endOfYear();
                break;
            case 'custom':
            default:
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfMonth();
                $end = $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfDay();
                break;
        }

        return [$start, $end, $range];
    }

    /**
     * Build base query for orders with standard executive filters.
     */
    public function baseOrderQuery(array $filters): Builder
    {
        [$start, $end] = $this->parseDateRange(
            $filters['date_range'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $statuses = $filters['order_status'] ?? ['COMPLETED', 'PIUTANG'];
        if (is_string($statuses)) {
            $statuses = array_map('trim', explode(',', $statuses));
        }

        return Order::whereIn('order_status', $statuses)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('order_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->whereNull('order_date')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->when(!empty($filters['business_unit_id']), function ($q) use ($filters) {
                if (is_array($filters['business_unit_id'])) {
                    $q->whereIn('business_unit_id', $filters['business_unit_id']);
                } else {
                    $q->where('business_unit_id', $filters['business_unit_id']);
                }
            })
            ->when(!empty($filters['branch']), function ($q) use ($filters) {
                $branch = $filters['branch'];
                if (is_numeric($branch)) {
                    $q->where('branch_id', $branch);
                } else {
                    $q->where('shipping_address_snapshot->store', $branch);
                }
            });
    }

    /**
     * Pre-load lookup maps for high-performance HPP calculation in batch.
     */
    protected function prepareHppLookups(Collection $orders): array
    {
        $allSns = [];
        $accurateIds = [];
        $variantIds = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));
                if (!empty($sns)) {
                    foreach ($sns as $sn) {
                        $allSns[] = $sn;
                    }
                } else {
                    if ($item->product_variant_type === ProductAccurate::class) {
                        $accurateIds[] = $item->product_variant_id;
                    } elseif ($item->product_variant_id) {
                        $variantIds[] = $item->product_variant_id;
                    }
                }
            }
        }

        // 1. Batch lookup SN -> HPP
        $snHppMap = [];
        if (!empty($allSns)) {
            $allSns = array_unique($allSns);
            $snHppMap = ProductSerialNumber::whereIn('serial_number', $allSns)
                ->pluck('hpp', 'serial_number')
                ->map(fn($v) => (float)$v)
                ->toArray();
        }

        // 2. Batch lookup ProductAccurate ID -> base_cost
        $accurateBaseCostMap = [];
        if (!empty($accurateIds)) {
            $accurateIds = array_unique($accurateIds);
            $accurateBaseCostMap = ProductAccurate::whereIn('id', $accurateIds)
                ->pluck('base_cost', 'id')
                ->map(fn($v) => (float)$v)
                ->toArray();
        }

        // 3. Batch lookup ProductVariant ID -> base_cost (via accurateData)
        $variantBaseCostMap = [];
        if (!empty($variantIds)) {
            $variantIds = array_unique($variantIds);
            $variants = ProductVariant::with('accurateData')->whereIn('id', $variantIds)->get();
            foreach ($variants as $variant) {
                $baseCost = $variant->accurateData?->base_cost ?? 0;
                $variantBaseCostMap[$variant->id] = (float)$baseCost;
            }
        }

        return [
            'snHppMap' => $snHppMap,
            'accurateBaseCostMap' => $accurateBaseCostMap,
            'variantBaseCostMap' => $variantBaseCostMap,
        ];
    }

    /**
     * Calculate financial details for a single order given the pre-loaded lookups.
     */
    public function computeSingleOrderMetrics($order, array $lookups): array
    {
        $snHppMap = $lookups['snHppMap'] ?? [];
        $accurateBaseCostMap = $lookups['accurateBaseCostMap'] ?? [];
        $variantBaseCostMap = $lookups['variantBaseCostMap'] ?? [];

        $totalHpp = 0;
        $totalQty = 0;

        foreach ($order->items as $item) {
            $totalQty += $item->qty;
            $sns = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));

            if (!empty($sns)) {
                foreach ($sns as $sn) {
                    $totalHpp += ($snHppMap[$sn] ?? 0);
                }
            } else {
                if ($item->product_variant_type === ProductAccurate::class) {
                    $baseCost = $accurateBaseCostMap[$item->product_variant_id] ?? 0;
                    $totalHpp += ($baseCost * $item->qty);
                } else {
                    $baseCost = $variantBaseCostMap[$item->product_variant_id] ?? 0;
                    $totalHpp += ($baseCost * $item->qty);
                }
            }
        }

        // Calculate MDR from payments
        $totalMdr = 0;
        foreach ($order->payments as $payment) {
            $rate = $payment->paymentMethodRate;
            $pct = $rate ? (float)$rate->mdr_percentage : (float)($payment->paymentMethod->mdr_percentage ?? 0);
            if ($pct > 0) {
                $totalMdr += round($payment->amount * $pct / 100);
            }
        }

        $grossSales = (float)$order->total_amount;
        $discount = (float)$order->discount_amount;
        $netSales = (float)$order->grand_total - $totalMdr;
        $grossProfit = $netSales - $totalHpp;
        $marginPct = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0;

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_status' => $order->order_status,
            'gross_sales' => $grossSales,
            'discount' => $discount,
            'grand_total' => (float)$order->grand_total,
            'mdr' => (float)$totalMdr,
            'net_sales' => (float)$netSales,
            'hpp' => (float)$totalHpp,
            'gross_profit' => (float)$grossProfit,
            'margin_pct' => $marginPct,
            'qty' => $totalQty,
        ];
    }

    /**
     * Compute aggregated financial metrics for a set of orders.
     */
    public function computeAggregates(Collection $orders): array
    {
        if ($orders->isEmpty()) {
            return [
                'total_orders' => 0,
                'total_qty' => 0,
                'gross_sales' => 0,
                'total_discount' => 0,
                'grand_total' => 0,
                'total_mdr' => 0,
                'net_sales' => 0,
                'total_hpp' => 0,
                'gross_profit' => 0,
                'profit_margin' => 0,
                'average_order_value' => 0,
                'piutang_amount' => 0,
                'completed_amount' => 0,
            ];
        }

        $lookups = $this->prepareHppLookups($orders);

        $totalOrders = $orders->count();
        $totalQty = 0;
        $grossSales = 0;
        $totalDiscount = 0;
        $grandTotal = 0;
        $totalMdr = 0;
        $totalHpp = 0;
        $piutangAmount = 0;
        $completedAmount = 0;

        foreach ($orders as $order) {
            $metrics = $this->computeSingleOrderMetrics($order, $lookups);

            $grossSales += $metrics['gross_sales'];
            $totalDiscount += $metrics['discount'];
            $grandTotal += $metrics['grand_total'];
            $totalMdr += $metrics['mdr'];
            $totalHpp += $metrics['hpp'];
            $totalQty += $metrics['qty'];

            if ($order->order_status === 'PIUTANG') {
                $piutangAmount += (float)$order->grand_total;
            } elseif ($order->order_status === 'COMPLETED') {
                $completedAmount += (float)$order->grand_total;
            }
        }

        $netSales = $grandTotal - $totalMdr;
        $grossProfit = $netSales - $totalHpp;
        $profitMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0;
        $aov = $totalOrders > 0 ? round($netSales / $totalOrders, 2) : 0;

        return [
            'total_orders' => $totalOrders,
            'total_qty' => $totalQty,
            'gross_sales' => round($grossSales, 2),
            'total_discount' => round($totalDiscount, 2),
            'grand_total' => round($grandTotal, 2),
            'total_mdr' => round($totalMdr, 2),
            'net_sales' => round($netSales, 2),
            'total_hpp' => round($totalHpp, 2),
            'gross_profit' => round($grossProfit, 2),
            'profit_margin' => $profitMargin,
            'average_order_value' => $aov,
            'piutang_amount' => round($piutangAmount, 2),
            'completed_amount' => round($completedAmount, 2),
        ];
    }

    /**
     * Get Executive KPI Summary including MTD vs Last MTD comparisons.
     */
    public function getKpiSummary(array $filters): array
    {
        [$start, $end, $range] = $this->parseDateRange(
            $filters['date_range'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        // Fetch current period orders
        $currentOrders = $this->baseOrderQuery($filters)
            ->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'items'])
            ->get();

        $currentAggregates = $this->computeAggregates($currentOrders);

        // Compute Month-To-Date (MTD) and Last Month-To-Date (Last MTD)
        $now = now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $sameDayLastMonth = $now->copy()->subMonth();

        $mtdFilters = array_merge($filters, [
            'date_range' => 'custom',
            'start_date' => $startOfThisMonth->format('Y-m-d'),
            'end_date' => $now->format('Y-m-d'),
        ]);

        $lastMtdFilters = array_merge($filters, [
            'date_range' => 'custom',
            'start_date' => $startOfLastMonth->format('Y-m-d'),
            'end_date' => $sameDayLastMonth->format('Y-m-d'),
        ]);

        $mtdOrders = $this->baseOrderQuery($mtdFilters)
            ->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'items'])
            ->get();

        $lastMtdOrders = $this->baseOrderQuery($lastMtdFilters)
            ->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'items'])
            ->get();

        $mtdAggregates = $this->computeAggregates($mtdOrders);
        $lastMtdAggregates = $this->computeAggregates($lastMtdOrders);

        $calculateGrowth = function ($current, $last) {
            if ($last > 0) {
                return round((($current - $last) / $last) * 100, 2);
            }
            return $current > 0 ? 100.0 : 0.0;
        };

        return [
            'period' => [
                'range' => $range,
                'start_date' => $start->format('Y-m-d H:i:s'),
                'end_date' => $end->format('Y-m-d H:i:s'),
            ],
            'summary' => $currentAggregates,
            'mtd_comparison' => [
                'current_mtd' => [
                    'start_date' => $startOfThisMonth->format('Y-m-d'),
                    'end_date' => $now->format('Y-m-d'),
                    'net_sales' => $mtdAggregates['net_sales'],
                    'gross_profit' => $mtdAggregates['gross_profit'],
                    'total_orders' => $mtdAggregates['total_orders'],
                    'total_qty' => $mtdAggregates['total_qty'],
                    'total_discount' => $mtdAggregates['total_discount'],
                ],
                'last_mtd' => [
                    'start_date' => $startOfLastMonth->format('Y-m-d'),
                    'end_date' => $sameDayLastMonth->format('Y-m-d'),
                    'net_sales' => $lastMtdAggregates['net_sales'],
                    'gross_profit' => $lastMtdAggregates['gross_profit'],
                    'total_orders' => $lastMtdAggregates['total_orders'],
                    'total_qty' => $lastMtdAggregates['total_qty'],
                    'total_discount' => $lastMtdAggregates['total_discount'],
                ],
                'growth' => [
                    'net_sales_pct' => $calculateGrowth($mtdAggregates['net_sales'], $lastMtdAggregates['net_sales']),
                    'gross_profit_pct' => $calculateGrowth($mtdAggregates['gross_profit'], $lastMtdAggregates['gross_profit']),
                    'orders_pct' => $calculateGrowth($mtdAggregates['total_orders'], $lastMtdAggregates['total_orders']),
                    'qty_pct' => $calculateGrowth($mtdAggregates['total_qty'], $lastMtdAggregates['total_qty']),
                    'discount_pct' => $calculateGrowth($mtdAggregates['total_discount'], $lastMtdAggregates['total_discount']),
                ],
            ],
        ];
    }

    /**
     * Get branch performance comparison breakdown.
     */
    public function getBranchComparison(array $filters): array
    {
        $orders = $this->baseOrderQuery($filters)
            ->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'items'])
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $lookups = $this->prepareHppLookups($orders);
        $totalCompanyNetSales = 0;

        // Group orders by branch / store name
        $grouped = $orders->groupBy(function ($order) {
            return $order->shipping_address_snapshot['store'] ?? ($order->branch_id ? 'Branch #' . $order->branch_id : 'Pusat / Toko Utama');
        });

        $branchStats = [];

        foreach ($grouped as $storeName => $branchOrders) {
            $branchQty = 0;
            $branchGross = 0;
            $branchDiscount = 0;
            $branchGrandTotal = 0;
            $branchMdr = 0;
            $branchHpp = 0;
            $branchPiutang = 0;
            $branchCompleted = 0;

            foreach ($branchOrders as $order) {
                $metrics = $this->computeSingleOrderMetrics($order, $lookups);

                $branchQty += $metrics['qty'];
                $branchGross += $metrics['gross_sales'];
                $branchDiscount += $metrics['discount'];
                $branchGrandTotal += $metrics['grand_total'];
                $branchMdr += $metrics['mdr'];
                $branchHpp += $metrics['hpp'];

                if ($order->order_status === 'PIUTANG') {
                    $branchPiutang += (float)$order->grand_total;
                } else {
                    $branchCompleted += (float)$order->grand_total;
                }
            }

            $branchNetSales = $branchGrandTotal - $branchMdr;
            $branchGrossProfit = $branchNetSales - $branchHpp;
            $marginPct = $branchNetSales > 0 ? round(($branchGrossProfit / $branchNetSales) * 100, 2) : 0;
            $aov = count($branchOrders) > 0 ? round($branchNetSales / count($branchOrders), 2) : 0;

            $totalCompanyNetSales += $branchNetSales;

            $branchStats[] = [
                'branch_name' => $storeName,
                'orders_count' => count($branchOrders),
                'total_qty' => $branchQty,
                'gross_sales' => round($branchGross, 2),
                'total_discount' => round($branchDiscount, 2),
                'net_sales' => round($branchNetSales, 2),
                'total_hpp' => round($branchHpp, 2),
                'gross_profit' => round($branchGrossProfit, 2),
                'margin_percentage' => $marginPct,
                'average_order_value' => $aov,
                'piutang_amount' => round($branchPiutang, 2),
                'completed_amount' => round($branchCompleted, 2),
            ];
        }

        // Calculate contribution %
        foreach ($branchStats as &$b) {
            $b['contribution_percentage'] = $totalCompanyNetSales > 0
                ? round(($b['net_sales'] / $totalCompanyNetSales) * 100, 2)
                : 0;
        }
        unset($b);

        // Sort by Net Sales descending
        usort($branchStats, fn($a, $b) => $b['net_sales'] <=> $a['net_sales']);

        return $branchStats;
    }

    /**
     * Get sales & profit trend time-series data.
     */
    public function getSalesTrend(array $filters): array
    {
        [$start, $end] = $this->parseDateRange(
            $filters['date_range'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $orders = $this->baseOrderQuery($filters)
            ->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'items'])
            ->get();

        $lookups = $this->prepareHppLookups($orders);

        $daysDiff = $start->diffInDays($end);
        $isSingleDay = $start->isSameDay($end);
        $isYearly = $daysDiff > 60;

        // Group orders by time bucket
        $timeline = [];

        if ($isSingleDay) {
            // Fill business hours 08:00 to 22:00
            for ($i = 8; $i <= 22; $i++) {
                $hourKey = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $timeline[$hourKey] = [
                    'label' => $hourKey,
                    'key' => $hourKey,
                    'orders_count' => 0,
                    'qty' => 0,
                    'gross_sales' => 0,
                    'net_sales' => 0,
                    'hpp' => 0,
                    'gross_profit' => 0,
                ];
            }
        } elseif ($isYearly) {
            $curr = $start->copy()->startOfMonth();
            while ($curr->lte($end)) {
                $monthKey = $curr->format('M Y');
                $timeline[$monthKey] = [
                    'label' => $monthKey,
                    'key' => $monthKey,
                    'orders_count' => 0,
                    'qty' => 0,
                    'gross_sales' => 0,
                    'net_sales' => 0,
                    'hpp' => 0,
                    'gross_profit' => 0,
                ];
                $curr->addMonth();
            }
        } else {
            $curr = $start->copy();
            while ($curr->lte($end)) {
                $dayKey = $curr->format('d M');
                $isoDate = $curr->format('Y-m-d');
                $timeline[$dayKey] = [
                    'label' => $dayKey,
                    'date' => $isoDate,
                    'orders_count' => 0,
                    'qty' => 0,
                    'gross_sales' => 0,
                    'net_sales' => 0,
                    'hpp' => 0,
                    'gross_profit' => 0,
                ];
                $curr->addDay();
            }
        }

        foreach ($orders as $order) {
            $date = $order->order_date ? Carbon::parse($order->order_date) : $order->created_at;

            if ($isSingleDay) {
                $key = $order->created_at->format('H:00');
            } elseif ($isYearly) {
                $key = $date->format('M Y');
            } else {
                $key = $date->format('d M');
            }

            if (!isset($timeline[$key])) {
                $timeline[$key] = [
                    'label' => $key,
                    'key' => $key,
                    'orders_count' => 0,
                    'qty' => 0,
                    'gross_sales' => 0,
                    'net_sales' => 0,
                    'hpp' => 0,
                    'gross_profit' => 0,
                ];
            }

            $metrics = $this->computeSingleOrderMetrics($order, $lookups);

            $timeline[$key]['orders_count'] += 1;
            $timeline[$key]['qty'] += $metrics['qty'];
            $timeline[$key]['gross_sales'] += $metrics['gross_sales'];
            $timeline[$key]['net_sales'] += $metrics['net_sales'];
            $timeline[$key]['hpp'] += $metrics['hpp'];
            $timeline[$key]['gross_profit'] += $metrics['gross_profit'];
        }

        // Format into serial response
        $labels = [];
        $grossSeries = [];
        $netSeries = [];
        $profitSeries = [];
        $orderCountSeries = [];

        foreach ($timeline as $point) {
            $labels[] = $point['label'];
            $grossSeries[] = round($point['gross_sales'], 2);
            $netSeries[] = round($point['net_sales'], 2);
            $profitSeries[] = round($point['gross_profit'], 2);
            $orderCountSeries[] = $point['orders_count'];
        }

        return [
            'mode' => $isSingleDay ? 'hourly' : ($isYearly ? 'monthly' : 'daily'),
            'labels' => $labels,
            'series' => [
                'gross_sales' => $grossSeries,
                'net_sales' => $netSeries,
                'gross_profit' => $profitSeries,
                'orders_count' => $orderCountSeries,
            ],
            'points' => array_values($timeline),
        ];
    }

    /**
     * Get top products ranked by revenue or quantity sold.
     */
    public function getTopProducts(array $filters, int $limit = 10, string $sortBy = 'revenue'): array
    {
        $orders = $this->baseOrderQuery($filters)->select('id')->get();
        if ($orders->isEmpty()) {
            return [];
        }

        $orderIds = $orders->pluck('id');

        $orderItems = OrderItem::with('variant')
            ->whereIn('order_id', $orderIds)
            ->get();

        $grouped = $orderItems->groupBy('product_variant_id')->map(function ($items) {
            $first = $items->first();
            $variant = $first->variant;

            $name = $variant?->name ?? $variant?->product?->name ?? $first->product_name ?? 'Unknown Product';
            $sku = $variant?->sku ?? ($first->sku ?? '-');

            $brand = 'Unknown';
            if ($variant instanceof ProductAccurate) {
                $brand = $variant->brandName ?? 'Unknown';
            } elseif ($variant && method_exists($variant, 'accurateData') && $variant->accurateData) {
                $brand = $variant->accurateData->brandName ?? 'Unknown';
            }

            $totalQty = $items->sum('qty');
            $totalRevenue = $items->sum('subtotal');
            $avgPrice = $totalQty > 0 ? round($totalRevenue / $totalQty, 2) : 0;

            return [
                'sku' => $sku,
                'name' => $name,
                'brand' => $brand,
                'qty_sold' => $totalQty,
                'revenue' => round($totalRevenue, 2),
                'avg_price' => $avgPrice,
            ];
        });

        $sorted = $grouped->sortByDesc($sortBy === 'qty' ? 'qty_sold' : 'revenue')
            ->take($limit)
            ->values();

        // Add rank 1..N
        return $sorted->map(function ($item, $idx) {
            $item['rank'] = $idx + 1;
            return $item;
        })->toArray();
    }

    /**
     * Get breakdown of payment methods used.
     */
    public function getPaymentMethodBreakdown(array $filters): array
    {
        $orders = $this->baseOrderQuery($filters)->select('id')->get();
        if ($orders->isEmpty()) {
            return [];
        }

        $orderIds = $orders->pluck('id');

        $payments = OrderPayment::with(['paymentMethod', 'paymentMethodRate'])
            ->whereIn('order_id', $orderIds)
            ->get();

        $totalPaymentsAmount = $payments->sum('amount');

        $grouped = $payments->groupBy('payment_method_id')->map(function ($pmPayments) use ($totalPaymentsAmount) {
            $first = $pmPayments->first();
            $pm = $first->paymentMethod;
            $amount = $pmPayments->sum('amount');
            $count = $pmPayments->count();

            $totalMdr = $pmPayments->sum(function ($p) {
                $rate = $p->paymentMethodRate;
                $pct = $rate ? (float)$rate->mdr_percentage : (float)($p->paymentMethod->mdr_percentage ?? 0);
                return round($p->amount * $pct / 100);
            });

            return [
                'payment_method_id' => $pm?->id,
                'payment_method_name' => $pm?->name ?? 'Unknown',
                'bank_name' => $pm?->bank_name ?? '-',
                'transactions_count' => $count,
                'total_amount' => round($amount, 2),
                'total_mdr' => round($totalMdr, 2),
                'net_amount' => round($amount - $totalMdr, 2),
                'share_percentage' => $totalPaymentsAmount > 0 ? round(($amount / $totalPaymentsAmount) * 100, 2) : 0,
            ];
        })->sortByDesc('total_amount')->values()->toArray();

        return $grouped;
    }

    /**
     * Get filter options (branches, business units, date presets) for the UI.
     */
    public function getFilterOptions(?User $user = null): array
    {
        $canAccessAllBu = $user && $user->hasAnyRole(['superadmin', 'director', 'admin']);

        $businessUnits = BusinessUnit::where('is_active', true)
            ->when(!$canAccessAllBu && $user, function ($q) use ($user) {
                $q->where('id', $user->business_unit_id);
            })
            ->get(['id', 'code', 'name']);

        $branches = Branch::when(!$canAccessAllBu && $user, function ($q) use ($user) {
            $q->where('business_unit_id', $user->business_unit_id);
        })
            ->orderBy('name')
            ->get(['id', 'business_unit_id', 'name']);

        // Distinct stores recorded in orders
        $orderStores = Order::distinct()
            ->whereNotNull('shipping_address_snapshot->store')
            ->pluck('shipping_address_snapshot->store')
            ->filter()
            ->unique()
            ->values();

        return [
            'business_units' => $businessUnits,
            'branches' => $branches,
            'order_stores' => $orderStores,
            'date_presets' => [
                ['value' => 'today', 'label' => 'Hari Ini'],
                ['value' => 'yesterday', 'label' => 'Kemarin'],
                ['value' => 'last_7_days', 'label' => '7 Hari Terakhir'],
                ['value' => 'this_week', 'label' => 'Minggu Ini'],
                ['value' => 'last_week', 'label' => 'Minggu Lalu'],
                ['value' => 'this_month', 'label' => 'Bulan Ini'],
                ['value' => 'last_month', 'label' => 'Bulan Lalu'],
                ['value' => 'this_quarter', 'label' => 'Kuartal Ini'],
                ['value' => 'this_year', 'label' => 'Tahun Ini'],
                ['value' => 'custom', 'label' => 'Kustom (Tanggal)'],
            ],
        ];
    }
}
