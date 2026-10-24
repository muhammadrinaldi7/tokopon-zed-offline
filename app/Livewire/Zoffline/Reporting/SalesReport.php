<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\SalesReportExport;
use App\Exports\SalesVendorReportExport;
use App\Models\BusinessUnit;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SalesReport extends Component
{
    use WithPagination;
    use \App\Traits\WithDynamicReportingFilters;

    public $activeTab = 'transactions';
    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
    public $businessUnitFilter = '';
    public $branchFilter = '';
    public $vendorFilter = '';
    public $proyekFilter = [];
    public $csvSeparator = ';';

    // Accurate Return Sync Modal Properties
    public $showSyncModal = false;
    public $syncBuCode = 'syihab';
    public $syncStartDate;
    public $syncEndDate;
    public $syncPreviewData = null;
    public $isSyncLoading = false;
    public $syncResultMessage = null;
    public $syncResultType = 'success';

    public function mount()
    {
        $this->businessUnitFilter = (string)(Auth::user()->getActiveBusinessUnitId() ?? '');
        $this->setDateRange();
    }

    public function updatedBusinessUnitFilter()
    {
        $this->branchFilter = '';
        // Reset filter dinamis gudang/cabang jika ada, karena opsi gudang terikat pada unit bisnis yang dipilih
        if (!empty($this->dynamicFilters)) {
            $this->dynamicFilters = array_values(array_filter($this->dynamicFilters, function ($f) {
                return !in_array($f['field'] ?? '', ['warehouse', 'branch']);
            }));
        }
        $this->resetPage();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function updatedVendorFilter()
    {
        $this->resetPage();
    }

    public function updatedProyekFilter()
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

    public function getOrdersQueryProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $query = Order::with(['user', 'salesBy', 'warehouse', 'payments.paymentMethod', 'payments.paymentMethodRate', 'items.variant', 'promos'])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED', 'piutang'])
            ->when($this->search, function ($query) {
                if ($this->activeTab === 'transactions') {
                    $query->where(function ($q) {
                        $q->where('orders.order_number', 'like', '%' . $this->search . '%')
                            ->orWhere('orders.accurate_invoice_no', 'like', '%' . $this->search . '%')
                            ->orWhereHas('user', function ($qc) {
                                $qc->where('name', 'like', '%' . $this->search . '%');
                            })
                            ->orWhereHas('salesBy', function ($qs) {
                                $qs->where('name', 'like', '%' . $this->search . '%');
                            });
                    });
                }
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            })
            ->when($this->vendorFilter, function ($query) {
                if ($this->vendorFilter === 'unknown') {
                    $query->whereHas('items', function ($qi) {
                        $qi->whereNull('serial_number')->orWhere('serial_number', '');
                    });
                } else {
                    $snList = \App\Models\ProductSerialNumber::whereHas('vendor', function ($qv) {
                        $qv->where('vendor_name', $this->vendorFilter);
                    })->pluck('serial_number')->toArray();

                    if (!empty($snList)) {
                        $query->whereHas('items', function ($qi) use ($snList) {
                            $qi->where(function ($qSub) use ($snList) {
                                $qSub->whereHas('serialNumbers', function ($qsn) use ($snList) {
                                    $qsn->whereIn('serial_number', $snList);
                                });
                                foreach (array_chunk($snList, 50) as $chunk) {
                                    $qSub->orWhere(function ($qc) use ($chunk) {
                                        foreach ($chunk as $sn) {
                                            $qc->orWhere('order_items.serial_number', 'like', '%' . $sn . '%');
                                        }
                                    });
                                }
                            });
                        });
                    }
                }
            })
            ->when(!empty($this->proyekFilter), function ($query) {
                $query->whereHas('items', function ($iq) {
                    $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) {
                        $vq->whereIn('proyek', $this->proyekFilter);
                    });
                });
            })
            ->when($this->businessUnitFilter && $this->businessUnitFilter !== 'all', function ($query) {
                $query->where('orders.business_unit_id', $this->businessUnitFilter);
            });

        // Terapkan Filter Dinamis ERP
        $query = $this->applyDynamicFilters($query);

        return $query->latest('orders.order_date');
    }

    /**
     * Definisi kamus filter dinamis ala ERP untuk Laporan Penjualan
     */
    public function getFilterableFields(): array
    {
        $buId = $this->businessUnitFilter;
        $isBuFiltered = ($buId && $buId !== 'all');

        // 1. Master Cabang & Gudang (dari tabel warehouses sesuai Business Unit yang dipilih)
        $whQuery = \App\Models\Warehouse::query();
        if ($isBuFiltered) {
            $whQuery->where('business_unit_id', $buId);
            $warehouses = $whQuery->orderBy('name')->pluck('name', 'name')->toArray();
        } else {
            $warehouses = $whQuery->with('businessUnit')->orderBy('name')->get()->mapWithKeys(function ($w) {
                $buSuffix = $w->businessUnit?->name ? " ({$w->businessUnit->name})" : '';
                return [$w->name => $w->name . $buSuffix];
            })->toArray();
        }

        // 2. Kategori Proyek (dari ProductAccurate sesuai Business Unit)
        $proyekQuery = \App\Models\ProductAccurate::whereNotNull('proyek')->where('proyek', '!=', '');
        if ($isBuFiltered) {
            $proyekQuery->where('business_unit_id', $buId);
        }
        $proyeks = $proyekQuery->orderBy('proyek')->pluck('proyek')->unique()->values()->toArray();
        if (empty($proyeks)) {
            $proyeks = \App\Models\ProductAccurate::whereNotNull('proyek')->where('proyek', '!=', '')->orderBy('proyek')->pluck('proyek')->unique()->values()->toArray();
        }
        if (empty($proyeks)) {
            $proyeks = ['RESMI', 'INTER', 'BEACUKAI', 'NON-PROYEK'];
        }

        // 3. Vendor SN (dari master vendors)
        $vendors = \App\Models\Vendor::orderBy('vendor_name')
            ->pluck('vendor_name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // 4. Merek / Brand (dari ProductAccurate sesuai Business Unit)
        $brandQuery = \App\Models\ProductAccurate::whereNotNull('brandName')->where('brandName', '!=', '');
        if ($isBuFiltered) {
            $brandQuery->where('business_unit_id', $buId);
        }
        $brands = $brandQuery->orderBy('brandName')->pluck('brandName')->unique()->values()->toArray();
        if (empty($brands)) {
            $brands = \App\Models\ProductAccurate::whereNotNull('brandName')->where('brandName', '!=', '')->orderBy('brandName')->pluck('brandName')->unique()->values()->toArray();
        }

        // 5. Kategori Produk (dari ProductAccurate sesuai Business Unit)
        $categoryQuery = \App\Models\ProductAccurate::whereNotNull('categoryName')->where('categoryName', '!=', '');
        if ($isBuFiltered) {
            $categoryQuery->where('business_unit_id', $buId);
        }
        $categories = $categoryQuery->orderBy('categoryName')->pluck('categoryName')->unique()->values()->toArray();
        if (empty($categories)) {
            $categories = \App\Models\ProductAccurate::whereNotNull('categoryName')->where('categoryName', '!=', '')->orderBy('categoryName')->pluck('categoryName')->unique()->values()->toArray();
        }

        // 6. Metode Pembayaran (dari master payment_methods)
        $paymentMethods = \App\Models\PaymentMethod::orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();

        // 7. Unit Bisnis
        $businessUnits = \App\Models\BusinessUnit::orderBy('name')->pluck('name', 'id')->toArray();

        $warehouseHandler = function ($query, $operator, $value) {
            $arr = is_array($value) ? $value : array_map('trim', explode(',', (string)$value));
            $arr = array_values(array_filter($arr, fn($v) => $v !== ''));

            $whList = \App\Models\Warehouse::where(function($q) use ($arr) {
                $q->whereIn('id', $arr)->orWhereIn('name', $arr);
            })->get();

            $ids = $whList->pluck('id')->toArray();
            $names = $whList->pluck('name')->toArray();
            $allNames = array_unique(array_merge($names, $arr));

            if ($operator === '!=' || $operator === 'not_in') {
                $query->where(function ($q) use ($ids, $allNames) {
                    $q->where(function ($sub) use ($ids) {
                        $sub->whereNull('orders.warehouse_id')->orWhereNotIn('orders.warehouse_id', $ids);
                    })->where(function ($sub) use ($allNames) {
                        $sub->whereNull('orders.shipping_address_snapshot->store')->orWhereNotIn('orders.shipping_address_snapshot->store', $allNames);
                    });
                });
            } elseif ($operator === '=' || $operator === 'in') {
                $query->where(function ($q) use ($ids, $allNames) {
                    $q->whereIn('orders.warehouse_id', $ids)
                      ->orWhereIn('orders.shipping_address_snapshot->store', $allNames);
                });
            } elseif ($operator === 'empty') {
                $query->whereNull('orders.warehouse_id')
                      ->where(function($q) {
                          $q->whereNull('orders.shipping_address_snapshot->store')
                            ->orWhere('orders.shipping_address_snapshot->store', '');
                      });
            } elseif ($operator === 'not_empty') {
                $query->where(function($q) {
                    $q->whereNotNull('orders.warehouse_id')
                      ->orWhere(function($sub) {
                          $sub->whereNotNull('orders.shipping_address_snapshot->store')
                              ->where('orders.shipping_address_snapshot->store', '!=', '');
                      });
                });
            } else {
                $val = is_array($value) ? ($value[0] ?? '') : $value;
                $query->where('orders.shipping_address_snapshot->store', 'like', "%{$val}%");
            }
        };

        return [
            'warehouse' => [
                'label' => 'Cabang / Gudang',
                'type' => 'select',
                'description' => 'Master Cabang & Gudang Toko POS Sesuai Unit Bisnis',
                'options' => $warehouses,
                'handler' => $warehouseHandler,
            ],
            'proyek' => [
                'label' => 'Kategori Proyek',
                'type' => 'select',
                'description' => 'Proyek Barang Accurate (RESMI, INTER, BEACUKAI)',
                'options' => $proyeks,
                'handler' => function ($query, $operator, $value) {
                    $arr = is_array($value) ? $value : array_map('trim', explode(',', (string)$value));
                    $arr = array_values(array_filter($arr, fn($v) => $v !== ''));

                    if ($operator === '!=' || $operator === 'not_in') {
                        $query->whereDoesntHave('items', function ($iq) use ($arr) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($arr) {
                                $vq->whereIn('proyek', $arr);
                            });
                        });
                    } elseif ($operator === '=' || $operator === 'in') {
                        $query->whereHas('items', function ($iq) use ($arr) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($arr) {
                                $vq->whereIn('proyek', $arr);
                            });
                        });
                    } elseif ($operator === 'empty') {
                        $query->whereHas('items', function ($iq) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) {
                                $vq->whereNull('proyek')->orWhere('proyek', '');
                            });
                        });
                    } elseif ($operator === 'not_empty') {
                        $query->whereHas('items', function ($iq) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) {
                                $vq->whereNotNull('proyek')->where('proyek', '!=', '');
                            });
                        });
                    } else { // like
                        $val = is_array($value) ? ($value[0] ?? '') : $value;
                        $query->whereHas('items', function ($iq) use ($val) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($val) {
                                $vq->where('proyek', 'like', "%{$val}%");
                            });
                        });
                    }
                },
            ],
            'vendor' => [
                'label' => 'Vendor SN',
                'type' => 'select',
                'description' => 'Supplier / Vendor Serial Number Unit',
                'options' => array_merge(['unknown' => 'Tanpa Vendor / Unknown'], $vendors),
                'handler' => function ($query, $operator, $value) {
                    $arr = is_array($value) ? $value : array_map('trim', explode(',', (string)$value));
                    $arr = array_values(array_filter($arr, fn($v) => $v !== ''));
                    $hasUnknown = in_array('unknown', $arr);
                    $namedVendors = array_values(array_diff($arr, ['unknown']));

                    $snList = [];
                    if (!empty($namedVendors)) {
                        $snList = \App\Models\ProductSerialNumber::whereHas('vendor', function ($qv) use ($namedVendors) {
                            $qv->whereIn('vendor_name', $namedVendors);
                        })->pluck('serial_number')->toArray();
                    }

                    if ($operator === '!=' || $operator === 'not_in') {
                        if ($hasUnknown && empty($namedVendors)) {
                            $query->whereHas('items', function ($qi) {
                                $qi->whereNotNull('serial_number')->where('serial_number', '!=', '');
                            });
                        } elseif (!empty($snList)) {
                            $query->whereDoesntHave('items.serialNumbers', function ($qsn) use ($snList) {
                                $qsn->whereIn('serial_number', $snList);
                            });
                        }
                    } else {
                        $query->where(function ($sub) use ($hasUnknown, $snList) {
                            if ($hasUnknown) {
                                $sub->whereHas('items', function ($qi) {
                                    $qi->whereNull('serial_number')->orWhere('serial_number', '');
                                });
                            }
                            if (!empty($snList)) {
                                $sub->orWhereHas('items.serialNumbers', function ($qsn) use ($snList) {
                                    $qsn->whereIn('serial_number', $snList);
                                });
                            }
                        });
                    }
                },
            ],
            'brand' => [
                'label' => 'Merek / Brand',
                'type' => 'select',
                'description' => 'Merek Produk (Apple, Samsung, Xiaomi, dll)',
                'options' => $brands,
                'handler' => function ($query, $operator, $value) {
                    $arr = is_array($value) ? $value : array_map('trim', explode(',', (string)$value));
                    $arr = array_values(array_filter($arr, fn($v) => $v !== ''));

                    if ($operator === '!=' || $operator === 'not_in') {
                        $query->whereDoesntHave('items', function ($iq) use ($arr) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($arr) {
                                $vq->whereIn('brandName', $arr);
                            });
                        });
                    } elseif ($operator === '=' || $operator === 'in') {
                        $query->whereHas('items', function ($iq) use ($arr) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($arr) {
                                $vq->whereIn('brandName', $arr);
                            });
                        });
                    } else {
                        $val = is_array($value) ? ($value[0] ?? '') : $value;
                        $query->whereHas('items', function ($iq) use ($val) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($val) {
                                $vq->where('brandName', 'like', "%{$val}%");
                            });
                        });
                    }
                },
            ],
            'category' => [
                'label' => 'Kategori Produk',
                'type' => 'select',
                'description' => 'Kategori Produk Accurate',
                'options' => $categories,
                'handler' => function ($query, $operator, $value) {
                    $arr = is_array($value) ? $value : array_map('trim', explode(',', (string)$value));
                    $arr = array_values(array_filter($arr, fn($v) => $v !== ''));

                    if ($operator === '!=' || $operator === 'not_in') {
                        $query->whereDoesntHave('items', function ($iq) use ($arr) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($arr) {
                                $vq->whereIn('categoryName', $arr);
                            });
                        });
                    } elseif ($operator === '=' || $operator === 'in') {
                        $query->whereHas('items', function ($iq) use ($arr) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($arr) {
                                $vq->whereIn('categoryName', $arr);
                            });
                        });
                    } else {
                        $val = is_array($value) ? ($value[0] ?? '') : $value;
                        $query->whereHas('items', function ($iq) use ($val) {
                            $iq->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($vq) use ($val) {
                                $vq->where('categoryName', 'like', "%{$val}%");
                            });
                        });
                    }
                },
            ],
            'payment_method' => [
                'label' => 'Metode Pembayaran',
                'type' => 'select',
                'description' => 'Metode Bayar (BCA, Mandiri, Cash, EDC, dll)',
                'options' => $paymentMethods,
                'handler' => function ($query, $operator, $value) {
                    $arr = is_array($value) ? $value : array_map('trim', explode(',', (string)$value));
                    $arr = array_values(array_filter($arr, fn($v) => $v !== ''));

                    if ($operator === '!=' || $operator === 'not_in') {
                        $query->whereDoesntHave('payments.paymentMethod', function ($pq) use ($arr) {
                            $pq->whereIn('name', $arr);
                        });
                    } elseif ($operator === '=' || $operator === 'in') {
                        $query->whereHas('payments.paymentMethod', function ($pq) use ($arr) {
                            $pq->whereIn('name', $arr);
                        });
                    } else {
                        $val = is_array($value) ? ($value[0] ?? '') : $value;
                        $query->whereHas('payments.paymentMethod', function ($pq) use ($val) {
                            $pq->where('name', 'like', "%{$val}%");
                        });
                    }
                },
            ],
            'order_status' => [
                'label' => 'Status Order',
                'type' => 'select',
                'description' => 'Status Transaksi (COMPLETED, piutang, DRAFT, CANCELLED)',
                'options' => ['COMPLETED', 'piutang', 'DRAFT', 'CANCELLED'],
                'column' => 'orders.order_status',
            ],
            'grand_total' => [
                'label' => 'Total Nilai Belanja (Rp)',
                'type' => 'number',
                'description' => 'Nominal Total Belanja Order',
                'column' => 'orders.grand_total',
            ],
            'business_unit' => [
                'label' => 'Unit Bisnis',
                'type' => 'select',
                'description' => 'Unit Bisnis (Syihab / GSK Second)',
                'options' => $businessUnits,
                'column' => 'orders.business_unit_id',
            ],
        ];
    }

    private function generateTransactionsData(): array
    {
        $orders = $this->ordersQuery->with([
            'payments.paymentMethod',
            'payments.paymentMethodRate',
            'handledBy',
            'items.promos',
            'promos.skus',
            'promos.bundleSkus'
        ])->get();

        // Kumpulkan semua serial number untuk query vendor sekaligus (bulk prefetch)
        $allSns = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->serial_number) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $allSns[] = $sn;
                    }
                }
            }
        }
        $allSns = array_unique($allSns);

        $snVendors = [];
        if (!empty($allSns)) {
            $snVendors = \App\Models\ProductSerialNumber::with('vendor')
                ->whereIn('serial_number', $allSns)
                ->get()
                ->keyBy('serial_number');
        }

        $rows = [];

        foreach ($orders as $order) {
            $branch = $order->warehouse?->name ?? ($order->shipping_address_snapshot['store'] ?? 'Unknown');

            // Rekap Pembayaran dan MDR secara granular untuk Order ini
            $orderPayments = [];
            if ($order->payments && $order->payments->count() > 0) {
                foreach ($order->payments as $payment) {
                    $pmName = $payment->paymentMethod ? $payment->paymentMethod->name : 'Unknown Payment';
                    $pmrPct = $payment->paymentMethodRate ? $payment->paymentMethodRate->mdr_percentage : 0;
                    $pmrName = $payment->paymentMethodRate ? $payment->paymentMethodRate->name : '-';
                    $mdrAmt = round(($payment->amount * $pmrPct) / 100);

                    $key = $pmName . '|' . $pmrPct . '|' . $pmrName;
                    if (!isset($orderPayments[$key])) {
                        $orderPayments[$key] = [
                            'name' => $pmName,
                            'amount' => 0,
                            'mdr_pct' => $pmrPct,
                            'mdr_amount' => 0,
                            'mdr_name' => $pmrName,
                            'no_kontrak' => $payment->no_kontrak ?? '-'
                        ];
                    }
                    $orderPayments[$key]['amount'] += $payment->amount;
                    $orderPayments[$key]['mdr_amount'] += $mdrAmt;
                }
            } else {
                $defaultPaymentName = 'Unknown Payment';
                if (str_starts_with($order->order_number, 'RET-')) {
                    $defaultPaymentName = 'Sales Return (Accurate)';
                } elseif (str_starts_with($order->order_number, 'WR-')) {
                    $defaultPaymentName = 'Ganti Unit (Accurate)';
                }

                $key = $defaultPaymentName . '|0|-';
                $orderPayments[$key] = [
                    'name' => $defaultPaymentName,
                    'amount' => $order->grand_total,
                    'mdr_pct' => 0,
                    'mdr_amount' => 0,
                    'mdr_name' => '-',
                    'no_kontrak' => '-'
                ];
            }
            $orderPayments = array_values($orderPayments);

            // Hitung Subtotal Aktual tiap item menggunakan data diskon promo dari DB (order_item_promos)
            $itemPromoData = [];
            $totalOrderActualSubtotal = 0;
            $itemCount = $order->items->count();

            foreach ($order->items as $item) {
                $itemPromosTotal = $item->promos->sum('pivot.discount_amount');
                $promoNamesArray = $item->promos->pluck('name')->unique()->toArray();
                $promoNamesStr = !empty($promoNamesArray) ? implode(', ', $promoNamesArray) : '-';

                $actualItemSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;

                $itemPromoData[$item->id] = [
                    'promo_names' => $promoNamesStr,
                    'promo_total' => $itemPromosTotal,
                    'actual_subtotal' => $actualItemSubtotal
                ];

                $totalOrderActualSubtotal += $actualItemSubtotal;
            }

            if ($totalOrderActualSubtotal == 0) $totalOrderActualSubtotal = 1;

            // Render Baris dengan Bobot
            $allocatedPaymentsTracker = [];
            $allocatedMdrTracker = [];
            $allocatedCouponTracker = 0;
            $allocatedLoyaltyTracker = 0;
            $currentIndex = 0;

            $orderCouponDiscount = (float)($order->coupon_discount_amount ?? 0);
            $orderLoyaltyDiscount = (float)($order->loyalty_discount_amount ?? 0);
            $orderCouponCode = $order->coupon_code ?: '-';

            if ($itemCount > 0) {
                foreach ($order->items as $item) {
                    $currentIndex++;
                    $isLastItem = ($currentIndex === $itemCount);

                    $actualItemSubtotal = $itemPromoData[$item->id]['actual_subtotal'];
                    $weight = $actualItemSubtotal / $totalOrderActualSubtotal;

                    // Alokasi diskon voucher & loyalty per item proporsional
                    if ($isLastItem) {
                        $itemCouponDiscount = max(0, $orderCouponDiscount - $allocatedCouponTracker);
                        $itemLoyaltyDiscount = max(0, $orderLoyaltyDiscount - $allocatedLoyaltyTracker);
                    } else {
                        $itemCouponDiscount = round($orderCouponDiscount * $weight);
                        $allocatedCouponTracker += $itemCouponDiscount;

                        $itemLoyaltyDiscount = round($orderLoyaltyDiscount * $weight);
                        $allocatedLoyaltyTracker += $itemLoyaltyDiscount;
                    }

                    $variant = $item->variant;
                    $sku = $variant?->item_no ?? $variant?->sku ?? $variant?->accurateData?->item_no ?? '-';
                    $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                    $merk = $variant?->brandName ?? $variant?->accurateData?->brandName ?? $variant?->product?->brand?->name ?? 'Unknown';
                    $category = $variant?->categoryName ?? $variant?->accurateData?->categoryName ?? 'Unknown';
                    $snList = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));
                    $vendor = '-';
                    $itemModal = 0;

                    if (!empty($snList)) {
                        $vendorNames = [];
                        foreach ($snList as $sn) {
                            $snModel = $snVendors->get($sn);
                            $vendorNames[] = $snModel?->vendor?->vendor_name ?? '-';
                            $snHpp = (float)($snModel?->hpp ?? 0);
                            $itemModal += ($item->qty < 0) ? -$snHpp : $snHpp;
                        }
                        $vendorNames = array_unique($vendorNames);
                        $vendor = implode(', ', $vendorNames);
                    } else {
                        // Non-SN: ambil base_cost dari variant / ProductAccurate
                        $baseCost = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                        $itemModal = $baseCost * (float)$item->qty;
                    }

                    $promoNamesStr = $itemPromoData[$item->id]['promo_names'];
                    $itemPromosTotal = $itemPromoData[$item->id]['promo_total'];
                    $penjualanBersih = round($actualItemSubtotal - $itemCouponDiscount - $itemLoyaltyDiscount);
                    $proyek = $variant?->proyek ?? '-';

                    $rowData = [
                        $order->order_date ? $order->order_date->format('d-m-Y') : $order->created_at->format('d-m-Y'),
                        $order->order_number,
                        $order->accurate_invoice_no ?? '-',
                        $order->accurate_so_number ?? '-',
                        $order->handledBy ? $order->handledBy->name : '-',
                        $order->salesBy ? $order->salesBy->name : '-',
                        $order->user ? $order->user->name : 'Walk-in',
                        $order->user && $order->user->profile ? ($order->user->profile->phone_number ?? '-') : '-',
                        $branch,
                        $proyek,
                        $sku,
                        $name,
                        $merk,
                        $category,
                        $vendor,
                        // $variant?->color ?? '-',
                        // ($variant?->ram ? $variant->ram . ' ' : '') . ($variant?->storage ? $variant->storage : '') ?: '-',
                        $item->serial_number ?? '-',
                        str_replace(["\n", "\r", "\t"], ' ', $order->notes ?? ''),
                        $item->qty,
                        $item->price_at_checkout,
                        $item->discount_amount ?? 0,
                        $promoNamesStr,
                        $itemPromosTotal,
                        $orderCouponCode,
                        $itemCouponDiscount,
                        $itemLoyaltyDiscount,
                        $item->subtotal,
                        $penjualanBersih,
                        $itemModal,
                    ];

                    $itemTotalPembayaranKotor = 0;
                    for ($i = 0; $i < 4; $i++) {
                        if (isset($orderPayments[$i])) {
                            $upm = $orderPayments[$i];

                            if ($isLastItem) {
                                $allocatedNominalKotor = $upm['amount'] - ($allocatedPaymentsTracker[$i] ?? 0);
                                $allocatedMdr = $upm['mdr_amount'] - ($allocatedMdrTracker[$i] ?? 0);
                            } else {
                                $allocatedNominalKotor = round($upm['amount'] * $weight);
                                if (!isset($allocatedPaymentsTracker[$i])) $allocatedPaymentsTracker[$i] = 0;
                                $allocatedPaymentsTracker[$i] += $allocatedNominalKotor;

                                $allocatedMdr = round($upm['mdr_amount'] * $weight);
                                if (!isset($allocatedMdrTracker[$i])) $allocatedMdrTracker[$i] = 0;
                                $allocatedMdrTracker[$i] += $allocatedMdr;
                            }

                            $nominalBersih = $allocatedNominalKotor - $allocatedMdr;

                            $rowData[] = $upm['name'];
                            $rowData[] = $nominalBersih;
                            $rowData[] = $upm['mdr_pct'];
                            $rowData[] = $allocatedMdr;
                            $rowData[] = $upm['mdr_name'];
                            $rowData[] = $upm['no_kontrak'];

                            $itemTotalPembayaranKotor += $allocatedNominalKotor;
                        } else {
                            $rowData[] = '-';
                            $rowData[] = 0;
                            $rowData[] = 0;
                            $rowData[] = 0;
                            $rowData[] = '-';
                            $rowData[] = '-';
                        }
                    }

                    $rowData[] = $itemTotalPembayaranKotor; // TOTAL PEMBAYARAN
                    $rows[] = $rowData;
                }
            } else {
                $promoNames = $order->promos->pluck('name')->toArray();
                $promoNamesStr = !empty($promoNames) ? implode(', ', $promoNames) : '-';
                $itemPromosTotal = $order->promos->sum('pivot.discount_applied');

                $rowData = [
                    $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d'),
                    $order->order_number,
                    $order->accurate_invoice_no ?? '-',
                    $order->accurate_so_number ?? '-',
                    $order->handledBy ? $order->handledBy->name : '-',
                    $order->salesBy ? $order->salesBy->name : '-',
                    $order->user ? $order->user->name : 'Walk-in',
                    $order->user && $order->user->profile ? ($order->user->profile->phone_number ?? '-') : '-',
                    $branch,
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    // '-',
                    // '-',
                    '-',
                    str_replace(["\n", "\r", "\t"], ' ', $order->notes ?? ''),
                    0,
                    0,
                    0,
                    $promoNamesStr,
                    $itemPromosTotal,
                    $order->coupon_code ?: '-',
                    (float)($order->coupon_discount_amount ?? 0),
                    (float)($order->loyalty_discount_amount ?? 0),
                    0, // Subtotal
                    0, // Penjualan Bersih
                    0, // Modal
                ];

                $itemTotalPembayaranKotor = 0;

                for ($i = 0; $i < 4; $i++) {
                    if (isset($orderPayments[$i])) {
                        $upm = $orderPayments[$i];
                        $nominalBersih = round($upm['amount'] - $upm['mdr_amount']);

                        $rowData[] = $upm['name'];
                        $rowData[] = $nominalBersih;
                        $rowData[] = $upm['mdr_pct'];
                        $rowData[] = $upm['mdr_amount'];
                        $rowData[] = $upm['mdr_name'];
                        $rowData[] = $upm['no_kontrak'];

                        $itemTotalPembayaranKotor += $upm['amount'];
                    } else {
                        $rowData[] = '-';
                        $rowData[] = 0;
                        $rowData[] = 0;
                        $rowData[] = 0;
                        $rowData[] = '-';
                        $rowData[] = '-';
                    }
                }

                $rowData[] = $itemTotalPembayaranKotor; // TOTAL PEMBAYARAN
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    public function exportCsvOpsi3()
    {
        $rows = $this->generateTransactionsData();
        $csvFileName = 'laporan_penjualan_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;

        return response()->streamDownload(function () use ($rows, $separator) {
            $file = fopen('php://output', 'w');

            // Header untuk Kolom Statis
            fputcsv($file, [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
                'NO. SALES ORDER(SO)',
                'KASIR',
                'SALES',
                'PELANGGAN',
                'TELEPON',
                'CABANG',
                'PROYEK',
                'SKU',
                'NAMA PRODUK',
                'MERK PRODUK',
                'CATEGORY',
                'VENDOR',
                // 'WARNA',
                // 'STORAGE',
                'SN (SerialNumber)',
                'CATATAN',
                'QTY',
                'HARGA SATUAN (Rp)',
                'DISKON ITEM (Rp)',
                'NAMA PROMO',
                'DISKON PROMO (Rp)',
                'KODE VOUCHER',
                'DISKON VOUCHER (Rp)',
                'DISKON POIN (Rp)',
                'SUBTOTAL ITEM (Rp)',
                'PENJUALAN BERSIH',
                'MODAL (Rp)',
                'METODE 1',
                'NOMINAL 1 (Rp)',
                'MDR 1 (%)',
                'BEBAN MDR 1 (Rp)',
                'TIPE BEBAN MDR 1',
                'NO KONTRAK 1',
                'METODE 2',
                'NOMINAL 2 (Rp)',
                'MDR 2 (%)',
                'BEBAN MDR 2 (Rp)',
                'TIPE BEBAN MDR 2',
                'NO KONTRAK 2',
                'METODE 3',
                'NOMINAL 3 (Rp)',
                'MDR 3 (%)',
                'BEBAN MDR 3 (Rp)',
                'TIPE BEBAN MDR 3',
                'NO KONTRAK 3',
                'METODE 4',
                'NOMINAL 4 (Rp)',
                'MDR 4 (%)',
                'BEBAN MDR 4 (Rp)',
                'TIPE BEBAN MDR 4',
                'NO KONTRAK 4',
                'TOTAL PEMBAYARAN'
            ], $separator);

            foreach ($rows as $row) {
                fputcsv($file, $row, $separator);
            }

            fclose($file);
        }, $csvFileName);
    }

    public function exportExcelOpsi3()
    {
        $rows = $this->generateTransactionsData();
        $excelFileName = 'laporan_penjualan_' . $this->startDate . '_sd_' . $this->endDate . '.xlsx';

        return Excel::download(new SalesReportExport($rows), $excelFileName);
    }

    public function openSyncModal()
    {
        $bu = \App\Models\BusinessUnit::find(Auth::user()->getActiveBusinessUnitId());
        $this->syncBuCode = $bu?->code ?? 'syihab';
        $this->syncStartDate = $this->startDate;
        $this->syncEndDate = $this->endDate;
        $this->syncPreviewData = null;
        $this->syncResultMessage = null;
        $this->showSyncModal = true;
        $this->loadSyncPreview();
    }

    public function closeSyncModal()
    {
        $this->showSyncModal = false;
        $this->syncPreviewData = null;
        $this->syncResultMessage = null;
    }

    public function loadSyncPreview()
    {
        $this->isSyncLoading = true;
        $this->syncResultMessage = null;

        Log::info("[SalesReport] Memulai loadSyncPreview Accurate", [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'active_bu_id' => Auth::user()?->getActiveBusinessUnitId(),
            'selected_bu_code' => $this->syncBuCode,
            'sync_start_date' => $this->syncStartDate,
            'sync_end_date' => $this->syncEndDate,
        ]);

        try {
            $syncService = app(\App\Services\AccurateReturnSyncService::class);
            $this->syncPreviewData = $syncService->previewReturns($this->syncStartDate, $this->syncEndDate, $this->syncBuCode);

            Log::info("[SalesReport] loadSyncPreview Accurate Berhasil", [
                'total_count' => $this->syncPreviewData['summary']['total_count'] ?? 0,
                'ready_to_sync_count' => $this->syncPreviewData['summary']['ready_to_sync_count'] ?? 0,
                'already_synced_count' => $this->syncPreviewData['summary']['already_synced_count'] ?? 0,
                'ready_to_sync_total_amount' => $this->syncPreviewData['summary']['ready_to_sync_total_amount'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error("[SalesReport] loadSyncPreview Accurate Gagal: " . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->syncResultMessage = "Gagal memuat pratinjau dari Accurate: " . $e->getMessage();
            $this->syncResultType = 'error';
            $this->syncPreviewData = null;
        } finally {
            $this->isSyncLoading = false;
        }
    }

    public function executeSyncReturns()
    {
        $this->isSyncLoading = true;
        $this->syncResultMessage = null;

        Log::info("[SalesReport] Memulai eksekusi executeSyncReturns Accurate", [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'active_bu_id' => Auth::user()?->getActiveBusinessUnitId(),
            'selected_bu_code' => $this->syncBuCode,
            'sync_start_date' => $this->syncStartDate,
            'sync_end_date' => $this->syncEndDate,
        ]);

        try {
            $syncService = app(\App\Services\AccurateReturnSyncService::class);
            $result = $syncService->syncAllReturns($this->syncStartDate, $this->syncEndDate, $this->syncBuCode, Auth::id());

            $syncedCount = $result['synced_count'];
            $skippedCount = $result['skipped_count'] ?? 0;
            $failedCount = $result['failed_count'] ?? 0;
            $totalAmountFormatted = number_format($result['total_synced_amount'], 0, ',', '.');

            Log::info("[SalesReport] Eksekusi executeSyncReturns Accurate Selesai", [
                'synced_count' => $syncedCount,
                'skipped_count' => $skippedCount,
                'failed_count' => $failedCount,
                'total_synced_amount' => $result['total_synced_amount'],
                'synced_items' => $result['synced_items'],
                'skipped_items' => $result['skipped_items'],
                'failed_items' => $result['failed_items'],
            ]);

            // Otomatis sesuaikan filter tanggal tabel utama agar data retur yang baru ditarik langsung terlihat
            $this->startDate = $this->syncStartDate;
            $this->endDate = $this->syncEndDate;
            $this->dateRange = 'custom';

            $bu = BusinessUnit::where('code', $this->syncBuCode)->first();
            $activeBuId = Auth::user()?->getActiveBusinessUnitId();

            $msg = "Berhasil menyinkronkan {$syncedCount} transaksi retur (Total Nilai: Rp {$totalAmountFormatted}) untuk periode {$this->syncStartDate} s/d {$this->syncEndDate}. Filter tabel otomatis disesuaikan!";
            
            if ($bu && $activeBuId != $bu->id) {
                $msg .= " (Perhatian: Unit Usaha aktif Anda saat ini berbeda dengan '{$bu->name}'. Silakan beralih ke Unit Usaha '{$bu->name}' untuk melihat data ini pada tabel).";
                Log::warning("[SalesReport] Unit Usaha aktif user (ID: {$activeBuId}) tidak sama dengan Unit Usaha yang disinkronkan (ID: {$bu->id} - {$bu->name})");
            }

            $this->syncResultMessage = $msg;
            $this->syncResultType = 'success';

            // Reload preview data to show synced state
            $this->syncPreviewData = $syncService->previewReturns($this->syncStartDate, $this->syncEndDate, $this->syncBuCode);

            // Reset pagination for main table
            $this->resetPage();
        } catch (\Exception $e) {
            Log::error("[SalesReport] Error saat executeSyncReturns Accurate: " . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->syncResultMessage = "Terjadi kesalahan saat sinkronisasi: " . $e->getMessage();
            $this->syncResultType = 'error';
        } finally {
            $this->isSyncLoading = false;
        }
    }

    private function generateVendorData(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $ordersQuery = Order::with(['items.variant', 'items.promos', 'user', 'salesBy', 'handledBy', 'warehouse'])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED', 'piutang'])
            ->when($this->businessUnitFilter && $this->businessUnitFilter !== 'all', function ($query) {
                $query->where('orders.business_unit_id', $this->businessUnitFilter);
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            });

        $ordersQuery = $this->applyDynamicFilters($ordersQuery);
        $orders = $ordersQuery->latest('orders.order_date')->get();

        $allSns = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->serial_number) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $allSns[] = $sn;
                    }
                }
            }
        }
        $allSns = array_unique($allSns);

        $snVendors = [];
        if (!empty($allSns)) {
            $snVendors = \App\Models\ProductSerialNumber::with('vendor')
                ->whereIn('serial_number', $allSns)
                ->get()
                ->keyBy('serial_number');
        }

        $rows = [];

        foreach ($orders as $order) {
            $branch = $order->warehouse?->name ?? ($order->shipping_address_snapshot['store'] ?? 'Unknown');

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $sku = $variant?->item_no ?? $variant?->sku ?? '-';
                $productName = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';

                $itemPromosTotal = $item->promos->sum('pivot.discount_amount');
                $actualItemSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;
                $penjualanBersih = round($actualItemSubtotal / 1.11);

                $vendorNames = [];
                $itemModal = 0;
                if ($item->serial_number) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $vendorModel = $snVendors->get($sn)?->vendor;
                        $vendorNames[] = $vendorModel?->vendor_name ?? 'Tanpa Vendor / Unknown';
                        $snHpp = (float)($snVendors->get($sn)?->hpp ?? 0);
                        $itemModal += ($item->qty < 0) ? -$snHpp : $snHpp;
                    }
                } else {
                    $baseCost = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                    $itemModal = $baseCost * (float)$item->qty;
                }

                $vendorDisplay = !empty($vendorNames) ? implode(', ', array_unique($vendorNames)) : 'Tanpa Vendor / Unknown';

                if ($this->vendorFilter && !str_contains(strtolower($vendorDisplay), strtolower($this->vendorFilter))) {
                    continue;
                }

                if ($this->search && $this->activeTab === 'vendor' && !str_contains(strtolower($vendorDisplay), strtolower($this->search))) {
                    continue;
                }

                $rows[] = [
                    $vendorDisplay,
                    $branch,
                    $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d'),
                    $order->order_number,
                    $order->accurate_invoice_no ?? '-',
                    $order->handledBy ? $order->handledBy->name : '-',
                    $order->salesBy ? $order->salesBy->name : '-',
                    $order->user ? $order->user->name : 'Walk-in',
                    $sku,
                    $productName,
                    $item->serial_number ?? '-',
                    $item->qty,
                    str_replace(["\n", "\r", "\t"], ' ', $order->notes ?: ($item->notes ?? '')),
                    $item->price_at_checkout ?? 0,
                    $item->discount_amount ?? 0,
                    $itemPromosTotal,
                    $actualItemSubtotal,
                    $penjualanBersih,
                    $itemModal,
                ];
            }
        }

        return $rows;
    }

    public function exportVendorCsv()
    {
        $rows = $this->generateVendorData();
        $csvFileName = 'laporan_penjualan_per_vendor_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;

        return response()->streamDownload(function () use ($rows, $separator) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'VENDOR',
                'CABANG',
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
                'KASIR',
                'SALES',
                'PELANGGAN',
                'SKU',
                'NAMA PRODUK',
                'SN (SerialNumber)',
                'QTY',
                'CATATAN',
                'HARGA SATUAN (Rp)',
                'DISKON ITEM (Rp)',
                'DISKON PROMO (Rp)',
                'SUBTOTAL ITEM (Rp)',
                'PENJUALAN BERSIH (Rp)',
                'MODAL (Rp)'
            ], $separator);

            foreach ($rows as $row) {
                fputcsv($file, $row, $separator);
            }

            fclose($file);
        }, $csvFileName);
    }

    public function exportVendorExcel()
    {
        $rows = $this->generateVendorData();
        $excelFileName = 'laporan_penjualan_per_vendor_' . $this->startDate . '_sd_' . $this->endDate . '.xlsx';

        return Excel::download(new SalesVendorReportExport($rows), $excelFileName);
    }

    public function getVendorSummaryProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $ordersQuery = Order::with(['items.variant', 'items.promos', 'warehouse'])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED'])
            ->when($this->businessUnitFilter && $this->businessUnitFilter !== 'all', function ($query) {
                $query->where('orders.business_unit_id', $this->businessUnitFilter);
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            });

        $ordersQuery = $this->applyDynamicFilters($ordersQuery);
        $orders = $ordersQuery->get();

        // Kumpulkan semua serial number dari seluruh order items untuk query vendor sekaligus (bulk)
        $allSns = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->serial_number) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $allSns[] = $sn;
                    }
                }
            }
        }
        $allSns = array_unique($allSns);

        // Query data vendor untuk Serial Number terkait
        $snVendors = [];
        if (!empty($allSns)) {
            $snVendors = \App\Models\ProductSerialNumber::with('vendor')
                ->whereIn('serial_number', $allSns)
                ->get()
                ->keyBy('serial_number');
        }

        $vendorData = [];

        foreach ($orders as $order) {
            $branch = $order->warehouse?->name ?? ($order->shipping_address_snapshot['store'] ?? 'Unknown Store');

            foreach ($order->items as $item) {
                $itemVendors = [];
                if ($item->serial_number) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $vendorModel = $snVendors->get($sn)?->vendor;
                        $vendorName = $vendorModel?->vendor_name ?? 'Tanpa Vendor / Unknown';
                        $itemVendors[$vendorName] = ($itemVendors[$vendorName] ?? 0) + 1;
                    }
                }

                // Fallback jika tidak menggunakan SN atau data vendor kosong
                if (empty($itemVendors)) {
                    $itemVendors = ['Tanpa Vendor / Unknown' => $item->qty];
                }

                $totalSnCount = array_sum($itemVendors);
                if ($totalSnCount <= 0) $totalSnCount = 1;

                // Hitung total nilai kotor, diskon, dan bersih item
                $itemPromosTotal = $item->promos->sum('pivot.discount_amount');
                $actualItemSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;

                // Alokasikan diskon voucher & loyalty per item jika ada
                $orderHeaderDiscount = (float)(($order->coupon_discount_amount ?? 0) + ($order->loyalty_discount_amount ?? 0));
                if ($orderHeaderDiscount > 0 && (float)$order->total_amount > 0) {
                    $itemOrderDiscount = round(($item->subtotal / $order->total_amount) * $orderHeaderDiscount);
                    $actualItemSubtotal = max(0, $actualItemSubtotal - $itemOrderDiscount);
                }

                $priceAtCheckout = $item->price_at_checkout ?? 0;
                $grossItemTotal = $priceAtCheckout * $item->qty;
                $discountItemTotal = $grossItemTotal - $actualItemSubtotal;

                // Distribusikan nilai item secara proporsional ke vendor masing-masing
                foreach ($itemVendors as $vendorName => $count) {
                    $ratio = $count / $totalSnCount;
                    $allocatedQty = $item->qty * $ratio;
                    $allocatedGross = $grossItemTotal * $ratio;
                    $allocatedDiscount = $discountItemTotal * $ratio;
                    $allocatedNet = $actualItemSubtotal * $ratio;

                    if (!isset($vendorData[$vendorName])) {
                        $vendorData[$vendorName] = [
                            'vendor_name' => $vendorName,
                            'qty' => 0,
                            'gross' => 0,
                            'discount' => 0,
                            'net' => 0,
                            'orders' => [],
                            'branches' => []
                        ];
                    }

                    $vendorData[$vendorName]['qty'] += $allocatedQty;
                    $vendorData[$vendorName]['gross'] += $allocatedGross;
                    $vendorData[$vendorName]['discount'] += $allocatedDiscount;
                    $vendorData[$vendorName]['net'] += $allocatedNet;
                    $vendorData[$vendorName]['orders'][$order->id] = true;

                    // Breakdown cabang per vendor
                    if (!isset($vendorData[$vendorName]['branches'][$branch])) {
                        $vendorData[$vendorName]['branches'][$branch] = [
                            'qty' => 0,
                            'gross' => 0,
                            'net' => 0
                        ];
                    }
                    $vendorData[$vendorName]['branches'][$branch]['qty'] += $allocatedQty;
                    $vendorData[$vendorName]['branches'][$branch]['gross'] += $allocatedGross;
                    $vendorData[$vendorName]['branches'][$branch]['net'] += $allocatedNet;
                }
            }
        }

        // Hitung total transaksi per vendor
        foreach ($vendorData as $vendorName => &$data) {
            $data['transaction_count'] = count($data['orders']);
            unset($data['orders']);
        }

        // Filter pencarian vendor
        if ($this->search && $this->activeTab === 'vendor') {
            $term = strtolower($this->search);
            $vendorData = array_filter($vendorData, function ($item) use ($term) {
                return str_contains(strtolower($item['vendor_name']), $term);
            });
        }

        // Filter vendor spesifik
        if ($this->vendorFilter) {
            $vf = strtolower($this->vendorFilter);
            $vendorData = array_filter($vendorData, function ($item) use ($vf) {
                return strtolower($item['vendor_name']) === $vf;
            });
        }

        // Urutkan berdasarkan Net Sales terbanyak
        usort($vendorData, function ($a, $b) {
            return $b['net'] <=> $a['net'];
        });

        return $vendorData;
    }

    public function render()
    {
        $orders = $this->ordersQuery->paginate(20);

        $branchQuery = \App\Models\Branch::query();
        if ($this->businessUnitFilter && $this->businessUnitFilter !== 'all') {
            $branchQuery->where('business_unit_id', $this->businessUnitFilter);
        }
        $availableBranches = $branchQuery->orderBy('name')->pluck('name');

        $availableVendors = \App\Models\Vendor::orderBy('vendor_name')
            ->pluck('vendor_name')
            ->filter()
            ->unique()
            ->values();

        $availableProjects = \App\Models\ProductAccurate::whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->orderBy('proyek')
            ->pluck('proyek')
            ->unique()
            ->values();

        $businessUnits = \App\Models\BusinessUnit::orderBy('name')->get();

        $totalGross = $this->ordersQuery->sum('total_amount');
        $netQuery = clone $this->ordersQuery;
        $totalNet = $netQuery->get()->sum(function ($order) {
            return $order->grand_total - $order->mdr_amount;
        });

        return view('livewire.zoffline.reporting.sales-report', [
            'orders' => $orders,
            'vendorSummary' => $this->vendorSummary,
            'businessUnits' => $businessUnits,
            'availableBranches' => $availableBranches,
            'availableVendors' => $availableVendors,
            'availableProjects' => $availableProjects,
            'summary' => [
                'count' => $orders->total(),
                'gross' => $totalGross,
                'net' => $totalNet
            ]
        ])->layout('layouts.z');
    }
}
