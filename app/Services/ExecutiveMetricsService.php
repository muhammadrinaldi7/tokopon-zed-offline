<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\Promo;
use App\Models\SellPhone;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
            $filters['date_range'] ?? ($filters['period'] ?? null),
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $statuses = $filters['order_status'] ?? ['COMPLETED', 'PIUTANG'];
        if (is_string($statuses)) {
            $statuses = array_map('trim', explode(',', $statuses));
        }

        return Order::whereIn('order_status', $statuses)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('order_date', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->whereNull('order_date')
                            ->whereBetween('created_at', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')]);
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
            $filters['date_range'] ?? ($filters['period'] ?? null),
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
            $filters['date_range'] ?? ($filters['period'] ?? null),
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

        // Distinct stores recorded in branches and recent orders (driver agnostic)
        $branchStores = Branch::orderBy('name')->pluck('name');
        $orderStoreNames = Order::whereNotNull('shipping_address_snapshot')
            ->latest('id')
            ->take(500)
            ->get(['shipping_address_snapshot'])
            ->map(function ($o) {
                return $o->shipping_address_snapshot['store'] ?? null;
            })
            ->filter();

        $orderStores = $branchStores->concat($orderStoreNames)->unique()->values();

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

    /**
     * Get Staff KPI (Salespersons vs Cashiers performance).
     */
    public function getStaffKpi(array $filters): array
    {
        $orders = $this->baseOrderQuery($filters)->with(['salesBy', 'handledBy'])->get();

        // 1. Salespersons Performance (sales_id)
        $salesGrouped = $orders->groupBy('sales_id')->map(function ($group, $salesId) {
            $sales = $group->first()->salesBy;
            $salesName = $sales ? $sales->name : 'Walk-in / Tanpa Sales';
            $totalOrders = $group->count();
            $totalQty = $group->sum('total_qty');
            $grossSales = $group->sum('total_amount');
            $netSales = $group->sum('grand_total');
            $aov = $totalOrders > 0 ? round($netSales / $totalOrders, 2) : 0;

            return [
                'sales_id' => $salesId ?: null,
                'sales_name' => $salesName,
                'position' => $sales?->position ?? 'Sales Staff',
                'orders_count' => $totalOrders,
                'total_qty' => (int)$totalQty,
                'gross_sales' => round($grossSales, 2),
                'net_sales' => round($netSales, 2),
                'aov' => $aov,
            ];
        })->sortByDesc('net_sales')->values();

        $totalSalesNet = $salesGrouped->sum('net_sales');
        $salesData = $salesGrouped->map(function ($item, $idx) use ($totalSalesNet) {
            $item['rank'] = $idx + 1;
            $item['contribution_pct'] = $totalSalesNet > 0 ? round(($item['net_sales'] / $totalSalesNet) * 100, 1) : 0;
            return $item;
        })->toArray();

        // 2. Cashiers Performance (handled_by)
        $cashierGrouped = $orders->groupBy('handled_by')->map(function ($group, $handledBy) {
            $cashier = $group->first()->handledBy;
            $cashierName = $cashier ? $cashier->name : 'Sistem / Tidak Tercatat';
            $totalOrders = $group->count();
            $totalGross = $group->sum('total_amount');
            $completedAmount = $group->where('order_status', 'COMPLETED')->sum('grand_total');
            $aov = $totalOrders > 0 ? round($totalGross / $totalOrders, 2) : 0;

            return [
                'cashier_id' => $handledBy ?: null,
                'cashier_name' => $cashierName,
                'orders_count' => $totalOrders,
                'total_gross' => round($totalGross, 2),
                'completed_amount' => round($completedAmount, 2),
                'aov' => $aov,
            ];
        })->sortByDesc('orders_count')->values();

        $totalCashierOrders = $cashierGrouped->sum('orders_count');
        $cashierData = $cashierGrouped->map(function ($item, $idx) use ($totalCashierOrders) {
            $item['rank'] = $idx + 1;
            $item['share_pct'] = $totalCashierOrders > 0 ? round(($item['orders_count'] / $totalCashierOrders) * 100, 1) : 0;
            return $item;
        })->toArray();

        return [
            'sales' => $salesData,
            'cashiers' => $cashierData,
            'summary' => [
                'total_sales_count' => count($salesData),
                'total_cashiers_count' => count($cashierData),
                'total_orders' => $orders->count(),
            ],
        ];
    }

    /**
     * Get Brand Analytics (Market share, revenue, and gross margins per brand).
     */
    public function getBrandAnalytics(array $filters): array
    {
        $orders = $this->baseOrderQuery($filters)->select('id')->get();
        if ($orders->isEmpty()) {
            return [
                'brands' => [],
                'summary' => [
                    'total_revenue' => 0,
                    'total_qty' => 0,
                    'total_brands' => 0,
                ],
            ];
        }

        $orderIds = $orders->pluck('id');
        $items = OrderItem::with(['variant.product.brand'])->whereIn('order_id', $orderIds)->get();

        $grouped = $items->groupBy(function ($item) {
            $variant = $item->variant;
            if ($variant instanceof ProductAccurate && !empty($variant->brandName)) {
                return trim($variant->brandName);
            }
            if ($variant && method_exists($variant, 'accurateData') && !empty($variant->accurateData?->brandName)) {
                return trim($variant->accurateData->brandName);
            }
            if (!empty($variant?->product?->brand?->name)) {
                return trim($variant->product->brand->name);
            }
            return 'Lainnya / Aksesoris';
        })->map(function ($brandItems, $brandName) {
            $totalQty = $brandItems->sum('qty');
            $grossSales = $brandItems->sum('subtotal');

            // HPP estimation from accurate_cogs or 80% fallback
            $hpp = $brandItems->sum(function ($it) {
                $cogs = $it->accurate_cogs ?? ($it->price * 0.8);
                return $cogs * $it->qty;
            });

            $grossProfit = $grossSales - $hpp;
            $margin = $grossSales > 0 ? round(($grossProfit / $grossSales) * 100, 1) : 0;

            return [
                'brand_name' => $brandName,
                'qty_sold' => (int)$totalQty,
                'gross_sales' => round($grossSales, 2),
                'hpp' => round($hpp, 2),
                'gross_profit' => round($grossProfit, 2),
                'margin_pct' => $margin,
            ];
        })->sortByDesc('gross_sales')->values();

        $totalRevenue = $grouped->sum('gross_sales');
        $brands = $grouped->map(function ($item, $idx) use ($totalRevenue) {
            $item['rank'] = $idx + 1;
            $item['market_share_pct'] = $totalRevenue > 0 ? round(($item['gross_sales'] / $totalRevenue) * 100, 1) : 0;
            return $item;
        })->toArray();

        return [
            'brands' => $brands,
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_qty' => $grouped->sum('qty_sold'),
                'total_brands' => count($brands),
            ],
        ];
    }

    /**
     * Get Cashier Audit:
     * 1. Order Cancellations & Void analysis (human error tracking)
     * 2. SellPhone Buyback Price Deviation (Cashier overpaying above system price)
     */
    public function getCashierAudit(array $filters): array
    {
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();
        $branchFilter = $filters['branch'] ?? null;

        // ─────────────────────────────────────────────────────────────
        // 1. Audit Pembatalan Transaksi (Void & Cancellation)
        // ─────────────────────────────────────────────────────────────
        $cancelQuery = ApprovalRequest::with(['requestedBy', 'approvable.branch'])
            ->where('request_type', 'ORDER_CANCELLATION')
            ->whereBetween('created_at', [$start, $end]);

        if ($branchFilter) {
            $cancelQuery->whereHasMorph('approvable', [Order::class], function ($q) use ($branchFilter) {
                $q->where('shipping_address_snapshot->store', $branchFilter);
            });
        }

        $cancellations = $cancelQuery->latest()->get();

        $cashierCancelLeaderboard = $cancellations->groupBy('requested_by')->map(function ($group) {
            $reqUser = $group->first()->requestedBy;
            $totalAmount = $group->sum(fn($r) => $r->approvable?->grand_total ?? 0);
            return [
                'cashier_id' => $reqUser?->id,
                'cashier_name' => $reqUser?->name ?? 'Kasir Tidak Tercatat',
                'cancellation_count' => $group->count(),
                'total_amount' => round($totalAmount, 2),
                'reasons' => $group->pluck('reason')->filter()->unique()->take(3)->values()->toArray(),
            ];
        })->sortByDesc('cancellation_count')->values()->toArray();

        $recentCancellations = $cancellations->take(25)->map(function ($c) {
            $order = $c->approvable;
            return [
                'id' => $c->id,
                'date' => $c->created_at->format('Y-m-d H:i'),
                'order_number' => $order?->order_number ?? '-',
                'cashier_name' => $c->requestedBy?->name ?? '-',
                'branch' => $order?->branch?->name ?? ($order?->shipping_address_snapshot['store'] ?? '-'),
                'grand_total' => (float)($order?->grand_total ?? 0),
                'reason' => $c->reason ?: 'Tidak ada keterangan',
                'status' => $c->status,
            ];
        })->values()->toArray();

        // ─────────────────────────────────────────────────────────────
        // 2. Audit Pembelian HP Bekas (SellPhone Price Deviation)
        // ─────────────────────────────────────────────────────────────
        $sellPhoneQuery = SellPhone::with(['handledBy', 'branch'])
            ->whereBetween('created_at', [$start, $end]);

        if ($branchFilter) {
            $sellPhoneQuery->whereHas('branch', function ($q) use ($branchFilter) {
                $q->where('name', $branchFilter);
            });
        }

        $sellPhones = $sellPhoneQuery->latest()->get();

        $totalBoughtUnits = $sellPhones->count();
        $totalBoughtAmount = $sellPhones->sum('appraised_value');
        $totalSystemAmount = $sellPhones->sum('original_appraised_value');

        // Filter overpay: kasir membeli di atas harga sistem
        $overpayItems = $sellPhones->filter(function ($sp) {
            return $sp->original_appraised_value > 0 && $sp->appraised_value > $sp->original_appraised_value;
        });

        $totalOverpayUnits = $overpayItems->count();
        $totalOverpayAmount = $overpayItems->sum(function ($sp) {
            return $sp->appraised_value - $sp->original_appraised_value;
        });

        // Cashier overpay leaderboard
        $cashierOverpayLeaderboard = $overpayItems->groupBy('handled_by')->map(function ($group) {
            $cashier = $group->first()->handledBy;
            $overpaySum = $group->sum(fn($sp) => $sp->appraised_value - $sp->original_appraised_value);
            $count = $group->count();
            return [
                'cashier_id' => $cashier?->id,
                'cashier_name' => $cashier?->name ?? 'Kasir Tidak Tercatat',
                'overpay_count' => $count,
                'total_overpay_amount' => round($overpaySum, 2),
                'avg_overpay' => $count > 0 ? round($overpaySum / $count, 2) : 0,
            ];
        })->sortByDesc('total_overpay_amount')->values()->toArray();

        $recentSellPhones = $sellPhones->take(30)->map(function ($sp) {
            $orig = (int)$sp->original_appraised_value;
            $final = (int)$sp->appraised_value;
            $diff = $orig > 0 ? ($final - $orig) : 0;
            $diffPct = $orig > 0 ? round(($diff / $orig) * 100, 1) : 0;

            return [
                'id' => $sp->id,
                'date' => $sp->created_at->format('Y-m-d H:i'),
                'branch' => $sp->branch?->name ?? '-',
                'brand' => $sp->phone_brand ?? '-',
                'model' => $sp->phone_model ?? '-',
                'ram_storage' => trim(($sp->phone_ram ?? '') . '/' . ($sp->phone_storage ?? ''), '/'),
                'imei' => $sp->imei ?? '-',
                'system_price' => $orig,
                'final_price' => $final,
                'diff_amount' => $diff,
                'diff_pct' => $diffPct,
                'is_overpay' => $diff > 0,
                'cashier_name' => $sp->handledBy?->name ?? '-',
                'reason' => $sp->price_adjustment_reason ?: '-',
                'status' => $sp->status,
            ];
        })->values()->toArray();

        return [
            'cancellation_audit' => [
                'total_cancellations' => $cancellations->count(),
                'total_cancelled_amount' => round($cancellations->sum(fn($r) => $r->approvable?->grand_total ?? 0), 2),
                'cashier_leaderboard' => $cashierCancelLeaderboard,
                'recent_logs' => $recentCancellations,
            ],
            'sell_phone_audit' => [
                'total_bought_units' => $totalBoughtUnits,
                'total_bought_amount' => round($totalBoughtAmount, 2),
                'total_system_amount' => round($totalSystemAmount, 2),
                'total_overpay_units' => $totalOverpayUnits,
                'total_overpay_amount' => round($totalOverpayAmount, 2),
                'cashier_overpay_leaderboard' => $cashierOverpayLeaderboard,
                'recent_logs' => $recentSellPhones,
            ],
        ];
    }

    /**
     * Get Promo Claims & Vendor Subsidies Analytics.
     */
    public function getPromoClaims(array $filters): array
    {
        $orders = $this->baseOrderQuery($filters)
            ->with(['promos', 'items.promos', 'branch'])
            ->get();

        $claimedRows = [];
        $totalDiscountSum = 0;
        $ordersWithPromoCount = 0;

        foreach ($orders as $order) {
            $hasPromo = false;
            $branch = $order->shipping_address_snapshot['store'] ?? ($order->branch?->name ?? 'Unknown');
            $date = $order->created_at->format('Y-m-d H:i');

            // Order-level promos
            foreach ($order->promos as $op) {
                $hasPromo = true;
                $disc = (float)($op->pivot->discount_applied ?? 0);
                $totalDiscountSum += $disc;

                $claimedRows[] = [
                    'date' => $date,
                    'order_number' => $order->order_number,
                    'branch' => $branch,
                    'brand' => 'Konsolidasi Order',
                    'product_name' => 'Diskon Keranjang / Faktur',
                    'promo_name' => $op->name,
                    'vendor_name' => $op->vendor_name ?? 'Internal Store',
                    'claim_amount' => $disc,
                ];
            }

            // Item-level promos
            foreach ($order->items as $item) {
                $variant = $item->variant;
                $pName = $variant?->name ?? $item->product_name ?? 'Produk';
                $brand = $variant?->brandName ?? ($variant?->product?->brand?->name ?? 'Unknown');

                foreach ($item->promos as $ip) {
                    $hasPromo = true;
                    $disc = (float)($ip->pivot->discount_amount ?? 0);
                    $totalDiscountSum += $disc;

                    $claimedRows[] = [
                        'date' => $date,
                        'order_number' => $order->order_number,
                        'branch' => $branch,
                        'brand' => $brand,
                        'product_name' => $pName,
                        'promo_name' => $ip->name,
                        'vendor_name' => $ip->pivot->vendor_name ?? ($ip->vendor_name ?? $brand),
                        'claim_amount' => $disc,
                    ];
                }
            }

            if ($hasPromo) {
                $ordersWithPromoCount++;
            }
        }

        // Promo leaderboard by total subsidy amount
        $promoLeaderboard = collect($claimedRows)->groupBy('promo_name')->map(function ($group, $name) {
            return [
                'promo_name' => $name,
                'times_used' => $group->count(),
                'total_discount' => round($group->sum('claim_amount'), 2),
            ];
        })->sortByDesc('total_discount')->values()->toArray();

        return [
            'summary' => [
                'total_discount_amount' => round($totalDiscountSum, 2),
                'orders_with_promo_count' => $ordersWithPromoCount,
                'total_promo_claims_count' => count($claimedRows),
                'avg_discount_per_order' => $ordersWithPromoCount > 0 ? round($totalDiscountSum / $ordersWithPromoCount, 2) : 0,
            ],
            'promo_leaderboard' => $promoLeaderboard,
            'recent_claims' => array_slice($claimedRows, 0, 40),
        ];
    }

    /**
     * Get list of all distinct active project names from ProductAccurate.
     */
    public function getAvailableProjects(): Collection
    {
        return ProductAccurate::whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->orderBy('proyek')
            ->pluck('proyek')
            ->unique()
            ->values();
    }

    /**
     * Get aggregated Project Sales Report (Daily matrix & executive project breakdown).
     */
    public function getProjectSalesReport(array $filters): array
    {
        [$start, $end, $range] = $this->parseDateRange(
            $filters['date_range'] ?? ($filters['period'] ?? null),
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());
        $dates = [];
        foreach ($period as $dt) {
            $dates[] = [
                'raw' => $dt->format('Y-m-d'),
                'display' => $dt->format('d-m-Y'),
                'day_name' => $dt->translatedFormat('l')
            ];
        }

        $orders = $this->baseOrderQuery($filters)
            ->with(['items.variant.product', 'items.promos', 'branch'])
            ->get();

        $lookups = $this->prepareHppLookups($orders);

        $rawMatrix = [];
        $encounteredProjects = [];
        $projectBreakdownData = [];

        foreach ($orders as $order) {
            $orderDate = $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d');

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $proyekName = $variant?->proyek ?? ($variant?->accurateData?->proyek ?? null);
                
                if (empty($proyekName) && !empty($item->serial_number)) {
                    $firstSn = trim(explode(',', $item->serial_number)[0] ?? '');
                    if ($firstSn) {
                        $proyekName = ProductSerialNumber::where('serial_number', $firstSn)->value('proyek');
                    }
                }

                if (empty($proyekName) || trim($proyekName) === '') {
                    $proyekName = 'NON-PROYEK';
                }
                $proyekName = strtoupper(trim($proyekName));
                $encounteredProjects[$proyekName] = true;

                $itemPromosTotal = $item->promos ? $item->promos->sum('pivot.discount_amount') : 0;
                $netSubtotal = (float)$item->subtotal - (float)($item->discount_amount ?? 0) - (float)$itemPromosTotal;
                $qty = (int)$item->qty;

                // Determine item HPP
                $itemHpp = 0;
                if (!empty($item->serial_number)) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $itemHpp += $lookups['sn_hpp'][$sn] ?? 0;
                    }
                } elseif ($item->product_variant_type === ProductAccurate::class) {
                    $itemHpp = ($lookups['accurate_cost'][$item->product_variant_id] ?? 0) * $qty;
                } elseif ($item->product_variant_id) {
                    $itemHpp = ($lookups['variant_cost'][$item->product_variant_id] ?? 0) * $qty;
                }

                $grossProfit = $netSubtotal - $itemHpp;

                if (!isset($rawMatrix[$orderDate][$proyekName])) {
                    $rawMatrix[$orderDate][$proyekName] = [
                        'nominal' => 0,
                        'qty' => 0,
                        'count' => 0,
                        'hpp' => 0,
                        'profit' => 0,
                    ];
                }

                $rawMatrix[$orderDate][$proyekName]['nominal'] += $netSubtotal;
                $rawMatrix[$orderDate][$proyekName]['qty'] += $qty;
                $rawMatrix[$orderDate][$proyekName]['count'] += 1;
                $rawMatrix[$orderDate][$proyekName]['hpp'] += $itemHpp;
                $rawMatrix[$orderDate][$proyekName]['profit'] += $grossProfit;

                if (!isset($projectBreakdownData[$proyekName])) {
                    $projectBreakdownData[$proyekName] = [
                        'project' => $proyekName,
                        'net_sales' => 0,
                        'total_qty' => 0,
                        'total_hpp' => 0,
                        'gross_profit' => 0,
                        'orders_count' => 0,
                    ];
                }

                $projectBreakdownData[$proyekName]['net_sales'] += $netSubtotal;
                $projectBreakdownData[$proyekName]['total_qty'] += $qty;
                $projectBreakdownData[$proyekName]['total_hpp'] += $itemHpp;
                $projectBreakdownData[$proyekName]['gross_profit'] += $grossProfit;
                $projectBreakdownData[$proyekName]['orders_count'] += 1;
            }
        }

        // Filter projects if specific projects are requested
        $requestedProjects = $filters['projects'] ?? ($filters['project'] ?? null);
        if ($requestedProjects) {
            if (is_string($requestedProjects)) {
                $requestedProjects = array_map('trim', explode(',', $requestedProjects));
            }
            $selectedColumns = array_map(fn($p) => strtoupper(trim($p)), $requestedProjects);
            $columns = array_values(array_unique($selectedColumns));
        } else {
            if (!empty($encounteredProjects)) {
                $columns = array_keys($encounteredProjects);
                sort($columns);
            } else {
                $columns = $this->getAvailableProjects()->map(fn($p) => strtoupper(trim($p)))->take(6)->toArray();
            }
        }

        $matrix = [];
        $rowTotals = [];
        $columnTotals = array_fill_keys($columns, ['nominal' => 0, 'qty' => 0, 'profit' => 0]);
        $grandTotal = ['nominal' => 0, 'qty' => 0, 'hpp' => 0, 'profit' => 0];

        foreach ($dates as $d) {
            $dateKey = $d['raw'];
            $rowTotals[$dateKey] = ['nominal' => 0, 'qty' => 0, 'profit' => 0];

            foreach ($columns as $col) {
                $cellData = $rawMatrix[$dateKey][$col] ?? ['nominal' => 0, 'qty' => 0, 'count' => 0, 'hpp' => 0, 'profit' => 0];
                $matrix[$dateKey][$col] = $cellData;

                $rowTotals[$dateKey]['nominal'] += $cellData['nominal'];
                $rowTotals[$dateKey]['qty'] += $cellData['qty'];
                $rowTotals[$dateKey]['profit'] += $cellData['profit'];

                $columnTotals[$col]['nominal'] += $cellData['nominal'];
                $columnTotals[$col]['qty'] += $cellData['qty'];
                $columnTotals[$col]['profit'] += $cellData['profit'];

                $grandTotal['nominal'] += $cellData['nominal'];
                $grandTotal['qty'] += $cellData['qty'];
                $grandTotal['hpp'] += $cellData['hpp'];
                $grandTotal['profit'] += $cellData['profit'];
            }
        }

        $totalCompanyNetSales = $grandTotal['nominal'];
        $projectBreakdown = [];
        foreach ($projectBreakdownData as $name => $b) {
            $marginPct = $b['net_sales'] > 0 ? round(($b['gross_profit'] / $b['net_sales']) * 100, 2) : 0;
            $sharePct = $totalCompanyNetSales > 0 ? round(($b['net_sales'] / $totalCompanyNetSales) * 100, 2) : 0;

            $projectBreakdown[] = [
                'project' => $name,
                'net_sales' => round($b['net_sales'], 2),
                'total_qty' => $b['total_qty'],
                'total_hpp' => round($b['total_hpp'], 2),
                'gross_profit' => round($b['gross_profit'], 2),
                'margin_percentage' => $marginPct,
                'contribution_percentage' => $sharePct,
                'orders_count' => $b['orders_count'],
            ];
        }

        usort($projectBreakdown, fn($a, $b) => $b['net_sales'] <=> $a['net_sales']);

        $totalDays = count($dates);
        $dailyAverage = $totalDays > 0 ? round($grandTotal['nominal'] / $totalDays, 2) : 0;
        $dailyAverageQty = $totalDays > 0 ? round($grandTotal['qty'] / $totalDays, 1) : 0;

        return [
            'period' => [
                'range' => $range,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'total_days' => $totalDays,
            ],
            'summary' => [
                'total_net_sales' => round($grandTotal['nominal'], 2),
                'total_qty' => $grandTotal['qty'],
                'total_hpp' => round($grandTotal['hpp'], 2),
                'gross_profit' => round($grandTotal['profit'], 2),
                'profit_margin' => $grandTotal['nominal'] > 0 ? round(($grandTotal['profit'] / $grandTotal['nominal']) * 100, 2) : 0,
                'total_projects_count' => count($columns),
                'daily_average_sales' => $dailyAverage,
                'daily_average_qty' => $dailyAverageQty,
            ],
            'project_breakdown' => $projectBreakdown,
            'columns' => $columns,
            'dates' => $dates,
            'matrix' => $matrix,
            'row_totals' => $rowTotals,
            'column_totals' => $columnTotals,
            'grand_total' => $grandTotal,
            'available_projects' => $this->getAvailableProjects(),
        ];
    }

    /**
     * Get transaction item details for drill-down modal on specific date and project.
     */
    public function getProjectSalesDetail(array $filters): array
    {
        $targetDate = $filters['date'] ?? null;
        $targetProject = strtoupper(trim($filters['project'] ?? ''));
        $search = strtolower(trim($filters['search'] ?? ''));

        if (!$targetDate) {
            return [];
        }

        $dateObj = Carbon::parse($targetDate);

        $orders = Order::with([
            'user',
            'salesBy',
            'handledBy',
            'branch',
            'payments.paymentMethod',
            'items.variant.product',
            'items.promos'
        ])
        ->whereDate('order_date', $dateObj)
        ->whereIn('order_status', ['COMPLETED', 'piutang', 'PIUTANG'])
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
        })
        ->get();

        $items = [];

        foreach ($orders as $order) {
            $branch = $order->branch?->name ?? ($order->shipping_address_snapshot['store'] ?? 'Cabang Pusat');
            $time = $order->order_date ? $order->order_date->format('H:i') : $order->created_at->format('H:i');

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $proyekName = $variant?->proyek ?? ($variant?->accurateData?->proyek ?? null);

                if (empty($proyekName) && !empty($item->serial_number)) {
                    $firstSn = trim(explode(',', $item->serial_number)[0] ?? '');
                    if ($firstSn) {
                        $proyekName = ProductSerialNumber::where('serial_number', $firstSn)->value('proyek');
                    }
                }

                if (empty($proyekName) || trim($proyekName) === '') {
                    $proyekName = 'NON-PROYEK';
                }
                $proyekName = strtoupper(trim($proyekName));

                // Match project (or if targetProject is 'ALL', include all)
                if ($targetProject !== 'ALL' && $targetProject !== '' && $proyekName !== $targetProject) {
                    continue;
                }

                $itemPromosTotal = $item->promos ? $item->promos->sum('pivot.discount_amount') : 0;
                $netSubtotal = (float)$item->subtotal - (float)($item->discount_amount ?? 0) - (float)$itemPromosTotal;
                $productName = $variant?->name ?? ($variant?->product?->name ?? ($item->product_name ?? 'Unknown Product'));
                $sku = $variant?->item_no ?? ($variant?->sku ?? '-');

                if ($search !== '') {
                    $matched = str_contains(strtolower($order->order_number), $search)
                        || str_contains(strtolower($order->accurate_invoice_no ?? ''), $search)
                        || str_contains(strtolower($productName), $search)
                        || str_contains(strtolower($sku), $search)
                        || str_contains(strtolower($order->user?->name ?? ''), $search)
                        || str_contains(strtolower($order->salesBy?->name ?? ''), $search)
                        || str_contains(strtolower($order->handledBy?->name ?? ''), $search);

                    if (!$matched) {
                        continue;
                    }
                }

                $items[] = [
                    'order_number' => $order->order_number,
                    'invoice_no' => $order->accurate_invoice_no ?? '-',
                    'time' => $time,
                    'customer_name' => $order->user?->name ?? 'Walk-in Customer',
                    'sales_name' => $order->salesBy?->name ?? '-',
                    'handled_by' => $order->handledBy?->name ?? '-',
                    'branch' => $branch,
                    'project' => $proyekName,
                    'product_name' => $productName,
                    'sku' => $sku,
                    'serial_number' => $item->serial_number ?? '-',
                    'qty' => (int)$item->qty,
                    'price' => (float)($item->price_at_checkout ?? 0),
                    'discount' => (float)($item->discount_amount ?? 0) + (float)$itemPromosTotal,
                    'subtotal' => round($netSubtotal, 2),
                    'payment_method' => $order->payments->first()?->paymentMethod?->name ?? '-',
                ];
            }
        }

        return $items;
    }
}
