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
use Illuminate\Support\Facades\Schema;

class ExecutiveMetricsService
{
    /**
     * Parse date range string into Carbon start and end dates.
     */
    public function parseDateRange(?string $dateRange, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = now();

        if (!empty($startDate) && !empty($endDate) && (empty($dateRange) || $dateRange === 'custom')) {
            return [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
                $dateRange ?: 'custom',
            ];
        }

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
                foreach ($sns as $sn) {
                    $allSns[] = $sn;
                }

                // Selalu kumpulkan accurateIds dan variantIds agar fallback HPP dapat bekerja jika SN bernilai 0
                if ($item->product_variant_type === ProductAccurate::class) {
                    $accurateIds[] = $item->product_variant_id;
                } elseif ($item->product_variant_id) {
                    $variantIds[] = $item->product_variant_id;
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

        // 4. Batch lookup SN -> Proyek
        $hasProyekInSn = Schema::hasTable('product_serial_numbers') && Schema::hasColumn('product_serial_numbers', 'proyek');
        $hasProyekInAcc = Schema::hasTable('product_accurates') && Schema::hasColumn('product_accurates', 'proyek');
        $snProyekMap = [];

        if (!empty($allSns) && ($hasProyekInSn || $hasProyekInAcc)) {
            $uniqueSns = array_unique($allSns);
            if ($hasProyekInSn) {
                $snProyekMap = ProductSerialNumber::whereIn('serial_number', $uniqueSns)
                    ->whereNotNull('proyek')
                    ->pluck('proyek', 'serial_number')
                    ->toArray();
            } elseif ($hasProyekInAcc) {
                $snAccMap = ProductSerialNumber::whereIn('serial_number', $uniqueSns)
                    ->whereNotNull('product_accurate_id')
                    ->pluck('product_accurate_id', 'serial_number')
                    ->toArray();
                if (!empty($snAccMap)) {
                    $accProyeks = ProductAccurate::whereIn('id', array_values($snAccMap))
                        ->whereNotNull('proyek')
                        ->pluck('proyek', 'id')
                        ->toArray();
                    foreach ($snAccMap as $sn => $accId) {
                        if (isset($accProyeks[$accId])) {
                            $snProyekMap[$sn] = $accProyeks[$accId];
                        }
                    }
                }
            }
        }

        return [
            'snHppMap' => $snHppMap,
            'accurateBaseCostMap' => $accurateBaseCostMap,
            'variantBaseCostMap' => $variantBaseCostMap,
            'sn_hpp' => $snHppMap,
            'accurate_cost' => $accurateBaseCostMap,
            'variant_cost' => $variantBaseCostMap,
            'snProyekMap' => $snProyekMap,
            'hasProyekInAcc' => $hasProyekInAcc,
            'hasProyekInSn' => $hasProyekInSn,
        ];
    }

    /**
     * Calculate financial details for a single order given the pre-loaded lookups.
     */
    public function computeSingleOrderMetrics($order, array $lookups): array
    {
        $snHppMap = $lookups['snHppMap'] ?? ($lookups['sn_hpp'] ?? []);
        $accurateBaseCostMap = $lookups['accurateBaseCostMap'] ?? ($lookups['accurate_cost'] ?? []);
        $variantBaseCostMap = $lookups['variantBaseCostMap'] ?? ($lookups['variant_cost'] ?? []);

        $totalHpp = 0;
        $totalQty = 0;

        foreach ($order->items as $item) {
            $totalQty += $item->qty;
            $itemHpp = 0;
            $sns = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));

            // 1. Coba ambil HPP dari nomor seri (jika tercatat > 0)
            if (!empty($sns)) {
                foreach ($sns as $sn) {
                    $snCost = $snHppMap[$sn] ?? 0;
                    if ($snCost > 0) {
                        $itemHpp += $snCost;
                    }
                }
            }

            // 2. Fallback: jika nomor seri tidak memiliki HPP (atau = 0), ambil dari base_cost Accurate / Varian
            if ($itemHpp <= 0) {
                if ($item->product_variant_type === ProductAccurate::class) {
                    $baseCost = $accurateBaseCostMap[$item->product_variant_id] ?? 0;
                    $itemHpp = ($baseCost * $item->qty);
                } else {
                    $baseCost = $variantBaseCostMap[$item->product_variant_id] ?? 0;
                    $itemHpp = ($baseCost * $item->qty);
                }
            }

            $totalHpp += $itemHpp;
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
                'category' => $pm?->category ?? 'Lainnya',
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
        $orders = $this->baseOrderQuery($filters)
            ->with(['salesBy', 'handledBy', 'items:id,order_id,qty'])
            ->get();

        // 1. Salespersons Performance (sales_id)
        $salesGrouped = $orders->groupBy('sales_id')->map(function ($group, $salesId) {
            $sales = $group->first()->salesBy;
            $salesName = $sales ? $sales->name : 'Walk-in / Tanpa Sales';
            $totalOrders = $group->count();
            $totalQty = $group->sum(function ($order) {
                return (int)$order->items->sum('qty');
            });
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
            $totalQty = $group->sum(function ($order) {
                return (int)$order->items->sum('qty');
            });
            $totalGross = $group->sum('total_amount');
            $completedAmount = $group->where('order_status', 'COMPLETED')->sum('grand_total');
            $aov = $totalOrders > 0 ? round($totalGross / $totalOrders, 2) : 0;

            return [
                'cashier_id' => $handledBy ?: null,
                'cashier_name' => $cashierName,
                'orders_count' => $totalOrders,
                'total_qty' => (int)$totalQty,
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
        $items = OrderItem::whereIn('order_id', $orderIds)->get();

        // Prepare HPP lookups for items
        $allSns = [];
        $accurateIds = [];
        $variantIds = [];
        foreach ($items as $item) {
            $sns = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));
            foreach ($sns as $sn) {
                $allSns[] = $sn;
            }
            if ($item->product_variant_type === ProductAccurate::class) {
                $accurateIds[] = $item->product_variant_id;
            } elseif ($item->product_variant_id) {
                $variantIds[] = $item->product_variant_id;
            }
        }

        $snHppMap = !empty($allSns) ? ProductSerialNumber::whereIn('serial_number', array_unique($allSns))->pluck('hpp', 'serial_number')->map(fn($v) => (float)$v)->toArray() : [];
        $accurateBaseCostMap = !empty($accurateIds) ? ProductAccurate::whereIn('id', array_unique($accurateIds))->pluck('base_cost', 'id')->map(fn($v) => (float)$v)->toArray() : [];
        $accurateBrandMap = !empty($accurateIds) ? ProductAccurate::whereIn('id', array_unique($accurateIds))->whereNotNull('brandName')->pluck('brandName', 'id')->toArray() : [];

        $variantBaseCostMap = [];
        $variantBrandMap = [];
        if (!empty($variantIds)) {
            $variants = ProductVariant::with(['accurateData', 'product.brand'])->whereIn('id', array_unique($variantIds))->get();
            foreach ($variants as $variant) {
                $variantBaseCostMap[$variant->id] = (float)($variant->accurateData?->base_cost ?? 0);
                $brand = $variant->accurateData?->brandName ?: ($variant->product?->brand?->name ?: null);
                if ($brand) {
                    $variantBrandMap[$variant->id] = trim($brand);
                }
            }
        }

        $grouped = $items->groupBy(function ($item) use ($accurateBrandMap, $variantBrandMap) {
            if ($item->product_variant_type === ProductAccurate::class) {
                return $accurateBrandMap[$item->product_variant_id] ?? 'Lainnya / Aksesoris';
            }
            if ($item->product_variant_id && isset($variantBrandMap[$item->product_variant_id])) {
                return $variantBrandMap[$item->product_variant_id];
            }
            return 'Lainnya / Aksesoris';
        })->map(function ($brandItems, $brandName) use ($snHppMap, $accurateBaseCostMap, $variantBaseCostMap) {
            $totalQty = $brandItems->sum('qty');
            $grossSales = $brandItems->sum('subtotal');

            // HPP computation: SN -> Accurate/Variant base_cost -> estimated price_at_checkout
            $hpp = $brandItems->sum(function ($it) use ($snHppMap, $accurateBaseCostMap, $variantBaseCostMap) {
                $cost = 0;
                $sns = array_filter(array_map('trim', explode(',', $it->serial_number ?? '')));
                if (!empty($sns)) {
                    foreach ($sns as $sn) {
                        $val = $snHppMap[$sn] ?? 0;
                        if ($val > 0) $cost += $val;
                    }
                }
                if ($cost <= 0) {
                    if ($it->product_variant_type === ProductAccurate::class) {
                        $cost = ($accurateBaseCostMap[$it->product_variant_id] ?? 0) * (int)$it->qty;
                    } elseif ($it->product_variant_id) {
                        $cost = ($variantBaseCostMap[$it->product_variant_id] ?? 0) * (int)$it->qty;
                    }
                }
                if ($cost <= 0 && (float)($it->price_at_checkout ?? 0) > 0) {
                    $cost = (float)$it->price_at_checkout * 0.85 * (int)$it->qty;
                }
                return $cost;
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
        [$start, $end] = $this->parseDateRange(
            $filters['date_range'] ?? ($filters['period'] ?? null),
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $branchFilter = $filters['branch'] ?? null;
        $buFilter = $filters['business_unit_id'] ?? null;

        // ─────────────────────────────────────────────────────────────
        // 1. Audit Pembatalan Transaksi (Void & Cancellation)
        // ─────────────────────────────────────────────────────────────
        $cancelQuery = ApprovalRequest::with(['requestedBy', 'approvable.branch', 'approvable.handledBy', 'approvable.salesBy'])
            ->whereIn('request_type', ['ORDER_CANCELLATION', 'cancellation'])
            ->whereBetween('created_at', [$start, $end]);

        if ($branchFilter) {
            $cancelQuery->whereHasMorph('approvable', [Order::class], function ($q) use ($branchFilter) {
                if (is_numeric($branchFilter)) {
                    $q->where('branch_id', $branchFilter);
                } else {
                    $q->where(function ($sub) use ($branchFilter) {
                        $sub->where('shipping_address_snapshot->store', $branchFilter)
                            ->orWhereHas('branch', fn($bq) => $bq->where('name', $branchFilter));
                    });
                }
            });
        }

        if ($buFilter) {
            $cancelQuery->whereHasMorph('approvable', [Order::class], function ($q) use ($buFilter) {
                if (is_array($buFilter)) {
                    $q->whereIn('business_unit_id', $buFilter);
                } else {
                    $q->where('business_unit_id', $buFilter);
                }
            });
        }

        $cancellations = $cancelQuery->latest()->get();

        $cashierCancelLeaderboard = $cancellations->groupBy('requested_by')->map(function ($group) {
            $first = $group->first();
            $reqUser = $first->requestedBy ?? ($first->approvable?->handledBy ?? ($first->approvable?->salesBy ?? null));
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
                'cashier_name' => $c->requestedBy?->name ?? ($order?->handledBy?->name ?? ($order?->salesBy?->name ?? '-')),
                'branch' => $order?->branch?->name ?? ($order?->shipping_address_snapshot['store'] ?? '-'),
                'grand_total' => (float)($order?->grand_total ?? 0),
                'reason' => $c->reason ?: 'Tidak ada keterangan',
                'status' => $c->status,
            ];
        })->values()->toArray();

        // ─────────────────────────────────────────────────────────────
        // 2. Audit Pembelian HP Bekas (SellPhone Price Deviation)
        // ─────────────────────────────────────────────────────────────
        $sellPhoneQuery = SellPhone::with(['handledBy', 'branch', 'buybackDevice'])
            ->whereBetween('created_at', [$start, $end]);

        if ($branchFilter) {
            if (is_numeric($branchFilter)) {
                $sellPhoneQuery->where('branch_id', $branchFilter);
            } else {
                $sellPhoneQuery->whereHas('branch', function ($q) use ($branchFilter) {
                    $q->where('name', $branchFilter);
                });
            }
        }

        if ($buFilter) {
            if (is_array($buFilter)) {
                $sellPhoneQuery->whereIn('business_unit_id', $buFilter);
            } else {
                $sellPhoneQuery->where('business_unit_id', $buFilter);
            }
        }

        $sellPhones = $sellPhoneQuery->latest()->get();

        $totalBoughtUnits = $sellPhones->count();
        $totalBoughtAmount = $sellPhones->sum('appraised_value');

        // Safe price evaluation: fallback to buybackDevice base_price if original_appraised_value column not present/null
        $overpayItems = $sellPhones->filter(function ($sp) {
            $orig = (float)($sp->original_appraised_value ?? ($sp->buybackDevice?->base_price ?? 0));
            $final = (float)$sp->appraised_value;
            return ($sp->is_price_adjusted && $orig > 0 && $final > $orig) || ($orig > 0 && $final > $orig);
        });

        $totalSystemAmount = $sellPhones->sum(function ($sp) {
            return (float)($sp->original_appraised_value ?? ($sp->buybackDevice?->base_price ?? $sp->appraised_value));
        });

        $totalOverpayUnits = $overpayItems->count();
        $totalOverpayAmount = $overpayItems->sum(function ($sp) {
            $orig = (float)($sp->original_appraised_value ?? ($sp->buybackDevice?->base_price ?? 0));
            $final = (float)$sp->appraised_value;
            return max(0, $final - $orig);
        });

        // Cashier overpay leaderboard
        $cashierOverpayLeaderboard = $overpayItems->groupBy('handled_by')->map(function ($group) {
            $cashier = $group->first()->handledBy;
            $overpaySum = $group->sum(function ($sp) {
                $orig = (float)($sp->original_appraised_value ?? ($sp->buybackDevice?->base_price ?? 0));
                return max(0, (float)$sp->appraised_value - $orig);
            });
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
            $orig = (float)($sp->original_appraised_value ?? ($sp->buybackDevice?->base_price ?? $sp->appraised_value));
            $final = (float)$sp->appraised_value;
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
                'system_price' => (int)$orig,
                'final_price' => (int)$final,
                'diff_amount' => (int)$diff,
                'diff_pct' => $diffPct,
                'is_overpay' => $diff > 0,
                'cashier_name' => $sp->handledBy?->name ?? '-',
                'reason' => $sp->price_adjustment_reason ?: ($sp->reject_reason ?: ($sp->minus_desc ?: '-')),
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
            ->where(function ($q) {
                $q->whereHas('promos')
                  ->orWhereHas('items.promos');
            })
            ->with([
                'promos.brand',
                'branch',
                'items.promos',
                'items.variant.product.brand',
            ])
            ->get();

        if ($orders->isEmpty()) {
            return [
                'summary' => [
                    'total_discount_amount' => 0,
                    'orders_with_promo_count' => 0,
                    'total_promo_claims_count' => 0,
                    'avg_discount_per_order' => 0,
                    'total_brands_count' => 0,
                    'total_vendors_count' => 0,
                ],
                'promo_leaderboard' => [],
                'brand_breakdown' => [],
                'vendor_breakdown' => [],
                'recent_claims' => [],
            ];
        }

        // Batch collect serial numbers to find vendors in a single query
        $allSns = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (!empty($item->serial_number)) {
                    foreach (explode(',', $item->serial_number) as $sn) {
                        $clean = trim($sn);
                        if ($clean) $allSns[] = $clean;
                    }
                }
            }
        }

        $snVendorMap = [];
        if (!empty($allSns)) {
            $snVendorMap = ProductSerialNumber::whereIn('serial_number', array_unique($allSns))
                ->join('vendors', 'product_serial_numbers.vendor_id', '=', 'vendors.id')
                ->pluck('vendors.vendor_name', 'product_serial_numbers.serial_number')
                ->toArray();
        }

        $claimedRows = [];
        $totalDiscountSum = 0;
        $ordersWithPromoCount = 0;

        $normalizeBrand = function (?string $rawBrand): string {
            if (!$rawBrand) return 'Umum / Multi-Brand';
            $trimmed = trim($rawBrand);
            $lower = strtolower($trimmed);
            if (in_array($lower, ['iphone', 'apple', 'ios'])) {
                return 'Apple';
            } elseif ($lower === 'samsung') {
                return 'Samsung';
            } elseif ($lower === 'oppo') {
                return 'Oppo';
            } elseif ($lower === 'vivo') {
                return 'Vivo';
            } elseif (in_array($lower, ['xiaomi', 'redmi', 'mi', 'poco'])) {
                return 'Xiaomi';
            } elseif ($lower === 'realme') {
                return 'Realme';
            } elseif ($lower === 'infinix') {
                return 'Infinix';
            } elseif ($lower === 'tecno') {
                return 'Tecno';
            }
            return ucwords($lower);
        };

        foreach ($orders as $order) {
            $hasPromo = false;
            $branch = $order->shipping_address_snapshot['store'] ?? ($order->branch?->name ?? 'Unknown');
            $date = $order->created_at->format('Y-m-d H:i');

            $itemBrands = [];
            $itemVendors = [];
            $itemProductNames = [];

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $bName = $variant?->brandName ?? ($variant?->product?->brand?->name ?? null);
                if ($bName) {
                    $itemBrands[] = $normalizeBrand($bName);
                }

                $pName = $variant?->name ?? $item->product_name;
                if ($pName) {
                    $itemProductNames[] = $pName;
                }

                if (!empty($item->serial_number)) {
                    foreach (explode(',', $item->serial_number) as $sn) {
                        $cleanSn = trim($sn);
                        if (isset($snVendorMap[$cleanSn])) {
                            $itemVendors[] = $snVendorMap[$cleanSn];
                        }
                    }
                }
            }

            $dominantBrand = !empty($itemBrands) ? array_keys(array_count_values($itemBrands))[0] : 'Umum / Multi-Brand';
            $dominantVendor = !empty($itemVendors) ? array_keys(array_count_values($itemVendors))[0] : null;

            // 1. Order-level promos
            foreach ($order->promos as $op) {
                $hasPromo = true;
                $disc = (float)($op->pivot->discount_applied ?? 0);
                if ($disc <= 0) continue;
                $totalDiscountSum += $disc;

                $brand = $op->brand?->name ? $normalizeBrand($op->brand->name) : $dominantBrand;
                $vendor = $dominantVendor ?: ($op->vendor_name ?: ($brand !== 'Umum / Multi-Brand' ? "Distributor {$brand}" : 'Distributor Resmi / Brand'));

                $productDesc = !empty($itemProductNames)
                    ? implode(', ', array_slice(array_unique($itemProductNames), 0, 2))
                    : 'Diskon Faktur Belanja';

                $claimedRows[] = [
                    'date' => $date,
                    'order_number' => $order->order_number,
                    'branch' => $branch,
                    'brand' => $brand,
                    'product_name' => $productDesc,
                    'promo_name' => $op->name,
                    'vendor_name' => $vendor,
                    'claim_amount' => $disc,
                ];
            }

            // 2. Item-level promos
            foreach ($order->items as $item) {
                $variant = $item->variant;
                $pName = $variant?->name ?? $item->product_name ?? 'Produk';
                $brand = $variant?->brandName ?? ($variant?->product?->brand?->name ?? $dominantBrand);
                $brand = $normalizeBrand($brand);

                $itemVendor = null;
                if (!empty($item->serial_number)) {
                    foreach (explode(',', $item->serial_number) as $sn) {
                        $cleanSn = trim($sn);
                        if (isset($snVendorMap[$cleanSn])) {
                            $itemVendor = $snVendorMap[$cleanSn];
                            break;
                        }
                    }
                }

                foreach ($item->promos as $ip) {
                    $hasPromo = true;
                    $disc = (float)($ip->pivot->discount_amount ?? 0);
                    if ($disc <= 0) continue;
                    $totalDiscountSum += $disc;

                    $vendor = $ip->pivot->vendor_name;
                    if (empty($vendor) || $vendor === 'Vendor tidak ditemukan') {
                        $vendor = $itemVendor ?: ($dominantVendor ?: ($brand !== 'Umum / Multi-Brand' ? "Distributor {$brand}" : 'Distributor Resmi / Brand'));
                    }

                    $claimedRows[] = [
                        'date' => $date,
                        'order_number' => $order->order_number,
                        'branch' => $branch,
                        'brand' => $brand,
                        'product_name' => $pName,
                        'promo_name' => $ip->name,
                        'vendor_name' => $vendor,
                        'claim_amount' => $disc,
                    ];
                }
            }

            if ($hasPromo) {
                $ordersWithPromoCount++;
            }
        }

        $claimsCollection = collect($claimedRows);

        // Filter by brand or vendor if requested
        if (!empty($filters['brand'])) {
            $claimsCollection = $claimsCollection->filter(fn($r) => strcasecmp($r['brand'], $filters['brand']) === 0);
        }
        if (!empty($filters['vendor'])) {
            $claimsCollection = $claimsCollection->filter(fn($r) => stripos($r['vendor_name'], $filters['vendor']) !== false);
        }

        // Leaderboard by Program Promo
        $promoLeaderboard = $claimsCollection->groupBy('promo_name')->map(function ($group, $name) {
            $first = $group->first();
            return [
                'promo_name' => $name,
                'brand' => $first['brand'] ?? 'Multi-Brand',
                'top_vendor' => $group->groupBy('vendor_name')->sortByDesc(fn($g) => $g->count())->keys()->first() ?? '-',
                'times_used' => $group->count(),
                'total_discount' => round($group->sum('claim_amount'), 2),
            ];
        })->sortByDesc('total_discount')->values()->toArray();

        // Rekap Subsidi per Brand
        $brandBreakdown = $claimsCollection->groupBy('brand')->map(function ($group, $brandName) {
            $vendorBreakdown = $group->groupBy('vendor_name')->map(function ($vg, $vName) {
                return [
                    'vendor_name' => $vName,
                    'claims_count' => $vg->count(),
                    'total_subsidy' => round($vg->sum('claim_amount'), 2),
                ];
            })->sortByDesc('total_subsidy')->values()->toArray();

            return [
                'brand' => $brandName,
                'claims_count' => $group->count(),
                'total_subsidy' => round($group->sum('claim_amount'), 2),
                'vendors' => $vendorBreakdown,
            ];
        })->sortByDesc('total_subsidy')->values()->toArray();

        // Rekap Tagihan per Vendor
        $vendorBreakdown = $claimsCollection->groupBy('vendor_name')->map(function ($group, $vendorName) {
            return [
                'vendor_name' => $vendorName,
                'claims_count' => $group->count(),
                'total_subsidy' => round($group->sum('claim_amount'), 2),
                'brands' => $group->pluck('brand')->unique()->filter()->values()->toArray(),
                'promos' => $group->pluck('promo_name')->unique()->filter()->values()->toArray(),
            ];
        })->sortByDesc('total_subsidy')->values()->toArray();

        return [
            'summary' => [
                'total_discount_amount' => round($claimsCollection->sum('claim_amount'), 2),
                'orders_with_promo_count' => $ordersWithPromoCount,
                'total_promo_claims_count' => $claimsCollection->count(),
                'avg_discount_per_order' => $ordersWithPromoCount > 0 ? round($claimsCollection->sum('claim_amount') / $ordersWithPromoCount, 2) : 0,
                'total_brands_count' => count($brandBreakdown),
                'total_vendors_count' => count($vendorBreakdown),
            ],
            'promo_leaderboard' => $promoLeaderboard,
            'brand_breakdown' => $brandBreakdown,
            'vendor_breakdown' => $vendorBreakdown,
            'recent_claims' => $claimsCollection->take(100)->values()->toArray(),
        ];
    }

    /**
     * Get list of all distinct active project names from ProductAccurate.
     */
    public function getAvailableProjects(): Collection
    {
        $fromAcc = collect();
        if (Schema::hasTable('product_accurates') && Schema::hasColumn('product_accurates', 'proyek')) {
            $fromAcc = ProductAccurate::whereNotNull('proyek')
                ->where('proyek', '!=', '')
                ->pluck('proyek');
        }

        $defaultProjects = collect(ProductAccurate::$proyek ?? [
            'RESMI', 'INTER', 'BEACUKAI', 'ACCESSORIES', 'HANDPHONE', 'NON-PROYEK'
        ]);

        return $fromAcc->concat($defaultProjects)
            ->map(fn($p) => strtoupper(trim($p)))
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

        $hasProyekInAcc = $lookups['hasProyekInAcc'] ?? false;
        $snProyekMap = $lookups['snProyekMap'] ?? [];

        foreach ($orders as $order) {
            $orderDate = $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d');

            foreach ($order->items as $item) {
                $variant = $item->variant;

                $proyekName = null;
                if ($hasProyekInAcc) {
                    $proyekName = $variant?->proyek ?? ($variant?->accurateData?->proyek ?? null);
                }

                if (empty($proyekName) && !empty($item->serial_number)) {
                    $firstSn = trim(explode(',', $item->serial_number)[0] ?? '');
                    if ($firstSn && isset($snProyekMap[$firstSn])) {
                        $proyekName = $snProyekMap[$firstSn];
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

                // Determine item HPP with fallback hierarchy
                $itemHpp = 0;
                if (!empty($item->serial_number)) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $snCost = $lookups['snHppMap'][$sn] ?? ($lookups['sn_hpp'][$sn] ?? 0);
                        if ($snCost > 0) {
                            $itemHpp += $snCost;
                        }
                    }
                }

                // Fallback: jika nomor seri belum memiliki HPP (> 0), ambil dari base_cost Accurate / Varian
                if ($itemHpp <= 0) {
                    if ($item->product_variant_type === ProductAccurate::class) {
                        $baseCost = $lookups['accurateBaseCostMap'][$item->product_variant_id] ?? ($lookups['accurate_cost'][$item->product_variant_id] ?? 0);
                        $itemHpp = $baseCost * $qty;
                    } elseif ($item->product_variant_id) {
                        $baseCost = $lookups['variantBaseCostMap'][$item->product_variant_id] ?? ($lookups['variant_cost'][$item->product_variant_id] ?? 0);
                        $itemHpp = $baseCost * $qty;
                    }
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

        $hasProyekInSn = Schema::hasTable('product_serial_numbers') && Schema::hasColumn('product_serial_numbers', 'proyek');
        $hasProyekInAcc = Schema::hasTable('product_accurates') && Schema::hasColumn('product_accurates', 'proyek');

        $detailSns = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (!empty($item->serial_number)) {
                    $firstSn = trim(explode(',', $item->serial_number)[0] ?? '');
                    if ($firstSn) {
                        $detailSns[] = $firstSn;
                    }
                }
            }
        }

        $snProyekMap = [];
        if (!empty($detailSns) && ($hasProyekInSn || $hasProyekInAcc)) {
            $uniqueSns = array_unique($detailSns);
            if ($hasProyekInSn) {
                $snProyekMap = ProductSerialNumber::whereIn('serial_number', $uniqueSns)
                    ->whereNotNull('proyek')
                    ->pluck('proyek', 'serial_number')
                    ->toArray();
            } elseif ($hasProyekInAcc) {
                $snAccMap = ProductSerialNumber::whereIn('serial_number', $uniqueSns)
                    ->whereNotNull('product_accurate_id')
                    ->pluck('product_accurate_id', 'serial_number')
                    ->toArray();
                if (!empty($snAccMap)) {
                    $accProyeks = ProductAccurate::whereIn('id', array_values($snAccMap))
                        ->whereNotNull('proyek')
                        ->pluck('proyek', 'id')
                        ->toArray();
                    foreach ($snAccMap as $sn => $accId) {
                        if (isset($accProyeks[$accId])) {
                            $snProyekMap[$sn] = $accProyeks[$accId];
                        }
                    }
                }
            }
        }

        $items = [];

        foreach ($orders as $order) {
            $branch = $order->branch?->name ?? ($order->shipping_address_snapshot['store'] ?? 'Cabang Pusat');
            $time = $order->order_date ? $order->order_date->format('H:i') : $order->created_at->format('H:i');

            foreach ($order->items as $item) {
                $variant = $item->variant;

                $proyekName = null;
                if ($hasProyekInAcc) {
                    $proyekName = $variant?->proyek ?? ($variant?->accurateData?->proyek ?? null);
                }

                if (empty($proyekName) && !empty($item->serial_number)) {
                    $firstSn = trim(explode(',', $item->serial_number)[0] ?? '');
                    if ($firstSn && isset($snProyekMap[$firstSn])) {
                        $proyekName = $snProyekMap[$firstSn];
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

    /**
     * Get list of detailed invoices / transactions for a specific branch and period.
     */
    public function getBranchTransactions(array $filters): array
    {
        $orders = $this->baseOrderQuery($filters)
            ->with([
                'user.profile',
                'salesBy',
                'handledBy',
                'branch',
                'payments.paymentMethod',
                'payments.paymentMethodRate',
                'items.variant',
                'items.promos',
            ])
            ->latest('order_date')
            ->get();

        $search = strtolower(trim($filters['search'] ?? ''));

        $transactions = [];
        $totalQty = 0;
        $totalGrandTotal = 0;
        $totalNetSales = 0;
        $totalMdr = 0;

        foreach ($orders as $order) {
            $branchName = $order->shipping_address_snapshot['store'] ?? ($order->branch?->name ?? 'Cabang Pusat');
            $customerName = $order->user?->name ?? 'Walk-in Customer';
            $customerPhone = $order->user?->profile?->phone_number ?? '-';
            $salesName = $order->salesBy?->name ?? '-';
            $cashierName = $order->handledBy?->name ?? '-';
            $orderNo = $order->order_number;
            $invoiceNo = $order->accurate_invoice_no ?? '-';

            // Calculate MDR for this order
            $orderMdr = 0;
            $paymentsSummary = [];
            foreach ($order->payments as $payment) {
                $rate = $payment->paymentMethodRate;
                $pct = $rate ? (float)$rate->mdr_percentage : (float)($payment->paymentMethod?->mdr_percentage ?? 0);
                $mdrAmt = $pct > 0 ? round($payment->amount * $pct / 100) : 0;
                $orderMdr += $mdrAmt;
                $pmName = $payment->paymentMethod?->name ?? 'Pembayaran';
                $paymentsSummary[] = [
                    'name' => $pmName,
                    'amount' => (float)$payment->amount,
                    'rate_name' => $rate?->name ?? null,
                    'mdr_amount' => $mdrAmt,
                    'no_kontrak' => $payment->no_kontrak ?? null,
                ];
            }

            $orderItems = [];
            $orderQty = 0;
            $matchedSearch = empty($search);

            if (!$matchedSearch) {
                if (
                    str_contains(strtolower($orderNo), $search) ||
                    str_contains(strtolower($invoiceNo), $search) ||
                    str_contains(strtolower($customerName), $search) ||
                    str_contains(strtolower($customerPhone), $search) ||
                    str_contains(strtolower($salesName), $search) ||
                    str_contains(strtolower($cashierName), $search)
                ) {
                    $matchedSearch = true;
                }
            }

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $pName = $variant?->name ?? $item->product_name ?? 'Produk';
                $sku = $variant?->item_no ?? ($variant?->sku ?? '-');
                $qty = (int)$item->qty;
                $orderQty += $qty;

                if (!$matchedSearch) {
                    if (str_contains(strtolower($pName), $search) || str_contains(strtolower($sku), $search) || str_contains(strtolower($item->serial_number ?? ''), $search)) {
                        $matchedSearch = true;
                    }
                }

                $itemPromoTotal = (float)$item->promos->sum('pivot.discount_amount');
                $price = (float)($item->price_at_checkout ?? 0);
                $discount = (float)($item->discount_amount ?? 0) + $itemPromoTotal;
                $subtotal = ($price * $qty) - $discount;

                $orderItems[] = [
                    'product_name' => $pName,
                    'sku' => $sku,
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $discount,
                    'subtotal' => round($subtotal, 2),
                    'serial_number' => $item->serial_number ?? null,
                ];
            }

            if (!$matchedSearch) {
                continue;
            }

            $grandTotal = (float)$order->grand_total;
            $grossSales = (float)$order->total_amount;
            $discountTotal = (float)$order->discount_amount;
            $netSales = $grandTotal - $orderMdr;

            $totalQty += $orderQty;
            $totalGrandTotal += $grandTotal;
            $totalNetSales += $netSales;
            $totalMdr += $orderMdr;

            $transactions[] = [
                'order_id' => $order->id,
                'order_number' => $orderNo,
                'invoice_no' => $invoiceNo,
                'date' => $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d'),
                'time' => $order->order_date ? $order->order_date->format('H:i') : $order->created_at->format('H:i'),
                'branch' => $branchName,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'sales_name' => $salesName,
                'cashier_name' => $cashierName,
                'status' => $order->order_status,
                'total_qty' => $orderQty,
                'gross_sales' => round($grossSales, 2),
                'discount' => round($discountTotal, 2),
                'grand_total' => round($grandTotal, 2),
                'mdr' => round($orderMdr, 2),
                'net_sales' => round($netSales, 2),
                'payment_methods' => $paymentsSummary,
                'items' => $orderItems,
                'notes' => $order->notes ?? null,
            ];
        }

        return [
            'branch' => $filters['branch'] ?? 'Semua Cabang',
            'summary' => [
                'total_orders' => count($transactions),
                'total_qty' => $totalQty,
                'total_grand_total' => round($totalGrandTotal, 2),
                'total_net_sales' => round($totalNetSales, 2),
                'total_mdr' => round($totalMdr, 2),
            ],
            'transactions' => $transactions,
        ];
    }
}

