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

    public $topPerformerSortBy = 'revenue'; // 'revenue' or 'qty'
    public $topPerformerLimit = 5;

    public $businessUnits = [];

    public function mount()
    {
        $this->setDateRange();
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->hasAnyRole(['superadmin', 'director', 'admin'])) {
            $this->businessUnits = \App\Models\BusinessUnit::where('is_active', true)->get();
        } else {
            $this->businessUnitFilter = $user->business_unit_id;
            $this->businessUnits = \App\Models\BusinessUnit::where('id', $user->business_unit_id)->get();
        }
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
    public function updatedBranchFilter()
    { /* Automatically triggers render */
    }
    public function updatedBusinessUnitFilter()
    { /* Automatically triggers render */
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
            ->when($this->branchFilter, function ($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            }, function ($q) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (!$user->hasAnyRole(['superadmin', 'director', 'admin'])) {
                    $q->where('business_unit_id', $user->business_unit_id);
                }
            });

        // --- 1. KPI OVERVIEW ---
        $orderIds = (clone $query)->pluck('id');
        $totalGross = (clone $query)->sum('total_amount');
        $totalDiscount = (clone $query)->sum('discount_amount');

        $totalMdr = \App\Models\OrderPayment::whereIn('order_id', $orderIds)
            ->with(['paymentMethod', 'paymentMethodRate'])
            ->get()
            ->sum(function ($payment) {
                $rate = $payment->paymentMethodRate;
                $pct = $rate ? $rate->mdr_percentage : ($payment->paymentMethod->mdr_percentage ?? 0);
                return round($payment->amount * $pct / 100);
            });

        $totalNet = (clone $query)->sum('grand_total') - $totalMdr;
        $totalTransactions = (clone $query)->count();

        $totalQty = OrderItem::whereIn('order_id', $orderIds)->sum('qty');

        // --- 2. MTD (Month To Date) SECTION ---
        $now = now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $sameDayLastMonth = $now->copy()->subMonth();

        $mtdQuery = Order::where('order_status', 'COMPLETED')
            ->whereBetween('created_at', [$startOfThisMonth, $now])
            ->when($this->branchFilter, function ($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            }, function ($q) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (!$user->hasAnyRole(['superadmin', 'director', 'admin'])) {
                    $q->where('business_unit_id', $user->business_unit_id);
                }
            });

        $lastMtdQuery = Order::where('order_status', 'COMPLETED')
            ->whereBetween('created_at', [$startOfLastMonth, $sameDayLastMonth])
            ->when($this->branchFilter, function ($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            }, function ($q) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (!$user->hasAnyRole(['superadmin', 'director', 'admin'])) {
                    $q->where('business_unit_id', $user->business_unit_id);
                }
            });

        $mtdOrderIds = (clone $mtdQuery)->pluck('id');
        $lastMtdOrderIds = (clone $lastMtdQuery)->pluck('id');

        $mtdMdr = \App\Models\OrderPayment::whereIn('order_id', $mtdOrderIds)
            ->with(['paymentMethod', 'paymentMethodRate'])
            ->get()
            ->sum(function ($payment) {
                $rate = $payment->paymentMethodRate;
                $pct = $rate ? $rate->mdr_percentage : ($payment->paymentMethod->mdr_percentage ?? 0);
                return round($payment->amount * $pct / 100);
            });

        $lastMtdMdr = \App\Models\OrderPayment::whereIn('order_id', $lastMtdOrderIds)
            ->with(['paymentMethod', 'paymentMethodRate'])
            ->get()
            ->sum(function ($payment) {
                $rate = $payment->paymentMethodRate;
                $pct = $rate ? $rate->mdr_percentage : ($payment->paymentMethod->mdr_percentage ?? 0);
                return round($payment->amount * $pct / 100);
            });

        $mtdNetSales = (clone $mtdQuery)->sum('grand_total') - $mtdMdr;
        $lastMtdNetSales = (clone $lastMtdQuery)->sum('grand_total') - $lastMtdMdr;

        $mtdTransactions = (clone $mtdQuery)->count();
        $lastMtdTransactions = (clone $lastMtdQuery)->count();

        $mtdQty = OrderItem::whereIn('order_id', $mtdOrderIds)->sum('qty');
        $lastMtdQty = OrderItem::whereIn('order_id', $lastMtdOrderIds)->sum('qty');

        $mtdDiscount = (clone $mtdQuery)->sum('discount_amount');
        $lastMtdDiscount = (clone $lastMtdQuery)->sum('discount_amount');

        $calculateGrowth = function ($current, $last) {
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
        $orders = (clone $query)->with(['salesBy', 'handledBy', 'items', 'payments.paymentMethod'])->get();

        // --- 3. TREND DATA (Line/Area Chart) ---
        $daysDiff = $start->diffInDays($end);
        $isSingleDay = $start->isSameDay($end);
        $isYearly = $daysDiff > 60;

        $trendDataRaw = $orders->groupBy(function ($order) use ($isSingleDay, $isYearly) {
            if ($isSingleDay) return $order->created_at->format('H:00');
            if ($isYearly) return $order->created_at->format('M Y');
            return $order->created_at->format('d M');
        })->map(function ($group) {
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
        $orderItemsForBrand = OrderItem::with(['variant' => function ($morphTo) {
            $morphTo->morphWith([
                \App\Models\ProductVariant::class => ['accurateData'],
                \App\Models\SecondProductVariant::class => ['accurateData'],
            ]);
        }])
            ->whereIn('order_id', $orderIds)
            ->get();

        $brandDataRaw = $orderItemsForBrand->groupBy(function ($item) {
            if ($item->variant instanceof \App\Models\ProductAccurate) {
                return $item->variant->brandName ?? 'Unknown';
            }
            return $item->variant?->accurateData?->brandName ?? 'Unknown';
        })->map(function ($group) {
            $firstVariant = $group->first()->variant;
            $brandName = $firstVariant instanceof \App\Models\ProductAccurate 
                ? ($firstVariant->brandName ?? 'Unknown') 
                : ($firstVariant?->accurateData?->brandName ?? 'Unknown');

            return [
                'name' => $brandName,
                'total' => $group->sum(function ($item) {
                    return $item->price_at_checkout * $item->qty;
                })
            ];
        })->sortByDesc('total')->values();

        $brandProportionData = [
            'labels' => $brandDataRaw->pluck('name')->toArray(),
            'series' => $brandDataRaw->pluck('total')->toArray(),
        ];

        // --- 5. PAYMENT METHOD PROPORTION (Donut Chart) ---
        $orderPaymentsForChart = \App\Models\OrderPayment::with('paymentMethod')->whereIn('order_id', $orderIds)->get();
        $pmData = $orderPaymentsForChart->groupBy('payment_method_id')->map(function ($group) {
            $pm = $group->first()->paymentMethod;
            return [
                'name' => $pm ? $pm->name : 'Unknown',
                'total' => $group->sum('amount')
            ];
        })->sortByDesc('total')->values();

        $paymentMethodData = [
            'labels' => $pmData->pluck('name')->toArray(),
            'series' => $pmData->pluck('total')->toArray(),
        ];

        // --- 6. TOP LISTS (Products, Branches, Sales) ---
        $topProducts = $orderItemsForBrand->groupBy('product_variant_id')->map(function ($group) {
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
            ->sortByDesc($this->topPerformerSortBy === 'qty' ? 'total_qty' : 'total_revenue')
            ->take($this->topPerformerLimit)
            ->values()
            ->toArray();

        $topBrands = $orderItemsForBrand->groupBy(function ($item) {
            if ($item->variant instanceof \App\Models\ProductAccurate) {
                return $item->variant->brandName ?? 'Unknown';
            }
            return $item->variant?->accurateData?->brandName ?? 'Unknown';
        })->map(function ($group) {
            $firstVariant = $group->first()->variant;
            $brandName = $firstVariant instanceof \App\Models\ProductAccurate 
                ? ($firstVariant->brandName ?? 'Unknown') 
                : ($firstVariant?->accurateData?->brandName ?? 'Unknown');

            return [
                'name' => $brandName,
                'total_qty' => $group->sum('qty'),
                'total_revenue' => $group->sum('subtotal')
            ];
        })->sortByDesc($this->topPerformerSortBy === 'qty' ? 'total_qty' : 'total_revenue')->take($this->topPerformerLimit)->values()->toArray();

        $topSales = $orders->groupBy('sales_id')->map(function ($group) {
            $sales = $group->first()->salesBy;
            return [
                'name' => $sales ? $sales->name : 'No Sales',
                'total_transactions' => $group->count(),
                'total_revenue' => $group->sum('grand_total')
            ];
        })->sortByDesc($this->topPerformerSortBy === 'qty' ? 'total_transactions' : 'total_revenue')->take($this->topPerformerLimit)->values()->toArray();


        $availableBranches = Branch::orderBy('name')->pluck('name');

        // Update charts dynamically via event
        $this->dispatch('update-charts', [
            'trend' => $trendData,
            'brandProportion' => $brandProportionData,
            'paymentMethod' => $paymentMethodData
        ]);

        // --- 7. TRANSAKSI KASIR ---
        $cashierData = $orders->groupBy('handled_by')->map(function ($group) {
            $cashier = $group->first()->handledBy;

            $qty = 0;
            $amount = 0;
            $cashback = 0;
            $promo = 0;
            $tunai = 0;
            $nonTunai = 0;

            foreach ($group as $order) {
                $amount += $order->grand_total;

                foreach ($order->items as $item) {
                    $qty += $item->qty;
                    $cashback += $item->discount_amount;
                    $promo += $item->promo_discount_amount;
                }

                foreach ($order->payments as $payment) {
                    $bankName = strtolower($payment->paymentMethod->bank_name ?? '');
                    if (str_contains($bankName, 'finance') || $bankName === 'finance') {
                        $nonTunai += $payment->amount;
                    } else {
                        $tunai += $payment->amount;
                    }
                }
            }

            return [
                'name' => $cashier ? $cashier->name : 'Unknown',
                'qty' => $qty,
                'amount' => $amount,
                'cashback' => $cashback,
                'promo' => $promo,
                'tunai' => $tunai,
                'non_tunai' => $nonTunai,
            ];
        })->sortByDesc('amount')->values()->toArray();

        // --- 8. CASHBACK PER SALES ---
        $cashbackPerSalesRaw = $orders->groupBy('sales_id')->map(function ($group) {
            $sales = $group->first()->salesBy;
            $cashbackAmount = 0;
            $cashbackQty = 0;
            $totalSalesQty = 0;
            $totalSalesAmount = 0;

            foreach ($group as $order) {
                $totalSalesAmount += $order->grand_total;
                foreach ($order->items as $item) {
                    $totalSalesQty += $item->qty;
                    if ($item->discount_amount > 0) {
                        $cashbackAmount += $item->discount_amount;
                        $cashbackQty += $item->qty;
                    }
                }
            }

            return [
                'name' => $sales ? $sales->name : 'No Sales',
                'cashback_amount' => $cashbackAmount,
                'cashback_qty' => $cashbackQty,
                'total_sales_qty' => $totalSalesQty,
                'total_sales_amount' => $totalSalesAmount,
            ];
        });

        $totalCashbackAmount = $cashbackPerSalesRaw->sum('cashback_amount');
        $totalCashbackQty = $cashbackPerSalesRaw->sum('cashback_qty');

        $cashbackPerSales = $cashbackPerSalesRaw->map(function ($data) {
            $amountPct = $data['total_sales_amount'] > 0 ? ($data['cashback_amount'] / $data['total_sales_amount']) * 100 : 0;
            $qtyPct = $data['total_sales_qty'] > 0 ? ($data['cashback_qty'] / $data['total_sales_qty']) * 100 : 0;
            
            $data['amount_pct'] = round($amountPct, 2);
            $data['qty_pct'] = round($qtyPct, 2);
            
            return $data;
        })->sortByDesc('cashback_amount')->values()->toArray();

        // --- 9. PERFORMA LEASING ---
        $leasingPerformaRaw = $orders->flatMap(function ($order) {
            $leasingPayments = $order->payments->filter(function ($payment) {
                $bankName = strtolower($payment->paymentMethod->bank_name ?? '');
                return str_contains($bankName, 'finance') || $bankName === 'finance';
            });
            
            return $leasingPayments->map(function ($payment) use ($order) {
                return [
                    'leasing_name' => $payment->paymentMethod->name ?? 'Unknown',
                    'amount' => $payment->amount,
                    'qty' => $order->items->sum('qty'),
                ];
            });
        });

        $leasingPerforma = $leasingPerformaRaw->groupBy('leasing_name')->map(function ($group, $name) {
            return [
                'name' => $name,
                'total_amount' => $group->sum('amount'),
                'total_qty' => $group->sum('qty'),
            ];
        })->sortByDesc('total_amount')->values()->toArray();

        // --- 10. TRANSAKSI PIUTANG ---
        // 1. Pure Piutang (order_status = 'PIUTANG')
        $purePiutangOrders = Order::with(['user'])
            ->whereBetween('created_at', [$start, $end])
            ->where('order_status', 'PIUTANG')
            ->when($this->branchFilter, function ($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            }, function ($q) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (!$user->hasAnyRole(['superadmin', 'director', 'admin'])) {
                    $q->where('business_unit_id', $user->business_unit_id);
                }
            })
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user ? $order->user->name : 'Pelanggan Umum',
                    'payment_method' => 'Piutang Toko',
                    'unpaid_amount' => $order->grand_total,
                    'grand_total' => $order->grand_total,
                    'date' => $order->created_at,
                ];
            });

        // 2. Finance Piutang (order_status = 'COMPLETED' and has PENDING payment)
        $financePiutangOrders = Order::with(['user', 'payments.paymentMethod', 'payments.paymentMethodRate'])
            ->whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->whereHas('payments', function ($q) {
                $q->where('status', 'PENDING');
            })
            ->when($this->branchFilter, function ($q) {
                $q->where('shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            }, function ($q) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (!$user->hasAnyRole(['superadmin', 'director', 'admin'])) {
                    $q->where('business_unit_id', $user->business_unit_id);
                }
            })
            ->get()
            ->flatMap(function ($order) {
                // Find pending payments for this order
                $pendingPayments = $order->payments->where('status', 'PENDING');
                
                return $pendingPayments->map(function ($payment) use ($order) {
                    $pm = $payment->paymentMethod;
                    $rate = $payment->paymentMethodRate;
                    
                    // Hitung MDR
                    $pct = $rate ? ($rate->mdr_percentage ?? 0) : ($pm->mdr_percentage ?? 0);
                    $rowMdr = $pct > 0 ? round((float)$payment->amount * (float)$pct / 100, 0) : 0;
                    $expectedNetAmount = (float)$payment->amount - $rowMdr;

                    return [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->user ? $order->user->name : 'Pelanggan Umum',
                        'payment_method' => $pm->name ?? 'Finance',
                        'unpaid_amount' => $expectedNetAmount, // Sudah dipotong MDR
                        'grand_total' => $order->grand_total,
                        'date' => $order->created_at,
                    ];
                });
            });

        // Gabungkan, urutkan berdasarkan yang terbaru
        $piutangTransactions = $purePiutangOrders->concat($financePiutangOrders)
            ->sortByDesc('date')
            ->values()
            ->toArray();

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
            'topBrands' => $topBrands,
            'topSales' => $topSales,
            'topProducts' => $topProducts,
            'availableBranches' => $availableBranches,
            'cashierData' => $cashierData,
            'cashbackPerSales' => $cashbackPerSales,
            'leasingPerforma' => $leasingPerforma,
            'piutangTransactions' => $piutangTransactions
        ])->layout('layouts.admin');
    }
}
