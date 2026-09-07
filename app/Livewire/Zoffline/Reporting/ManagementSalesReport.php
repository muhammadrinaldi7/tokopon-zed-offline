<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\ManagementSalesReportExport;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ManagementSalesReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
    public $businessUnitFilter = '';
    public $branchFilter = '';
    public $vendorFilter = '';
    public $proyekFilter = [];
    public $csvSeparator = ';';

    public function mount()
    {
        $this->businessUnitFilter = (string)(Auth::user()->getActiveBusinessUnitId() ?? '');
        $this->setDateRange();
    }

    public function updatedBusinessUnitFilter()
    {
        $this->branchFilter = '';
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
        $buId = $this->businessUnitFilter ?: Auth::user()->getActiveBusinessUnitId();

        return Order::with([
            'user',
            'salesBy',
            'handledBy',
            'businessUnit',
            'items.variant',
            'items.promos',
            'promos'
        ])
        ->whereBetween('orders.order_date', [$start, $end])
        ->whereIn('orders.order_status', ['COMPLETED'])
        ->when($buId && $buId !== 'all', function ($query) use ($buId) {
            $query->where('orders.business_unit_id', $buId);
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
                $snList = ProductSerialNumber::whereHas('vendor', function ($qv) {
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
                $iq->whereHasMorph('variant', [ProductAccurate::class], function ($vq) {
                    $vq->whereIn('proyek', $this->proyekFilter);
                });
            });
        })
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('orders.order_number', 'like', '%' . $this->search . '%')
                    ->orWhere('orders.accurate_invoice_no', 'like', '%' . $this->search . '%')
                    ->orWhere('orders.accurate_so_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($qc) {
                        $qc->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('salesBy', function ($qs) {
                        $qs->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('handledBy', function ($qh) {
                        $qh->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('items', function ($qi) {
                        $qi->where('product_name', 'like', '%' . $this->search . '%')
                            ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                            ->orWhereHasMorph('variant', [ProductAccurate::class], function ($vq) {
                                $vq->where('item_no', 'like', '%' . $this->search . '%')
                                    ->orWhere('name', 'like', '%' . $this->search . '%');
                            });
                    });
            });
        })
        ->latest('orders.order_date');
    }

    public function getItemsQueryProperty()
    {
        return $this->buildItemsQuery();
    }

    public function buildItemsQuery()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $buId = $this->businessUnitFilter ?: Auth::user()->getActiveBusinessUnitId();

        return OrderItem::with([
            'order.user.profile',
            'order.salesBy',
            'order.handledBy',
            'order.businessUnit',
            'variant',
            'promos'
        ])
        ->whereHas('order', function ($oq) use ($start, $end, $buId) {
            $oq->whereBetween('order_date', [$start, $end])
                ->whereIn('order_status', ['COMPLETED'])
                ->when($buId && $buId !== 'all', function ($bq) use ($buId) {
                    $bq->where('business_unit_id', $buId);
                })
                ->when($this->branchFilter, function ($bq) {
                    $bq->where('shipping_address_snapshot->store', $this->branchFilter);
                });
        })
        ->when($this->search, function ($sq) {
            $sq->where(function ($q) {
                $q->where('order_items.product_name', 'like', '%' . $this->search . '%')
                    ->orWhere('order_items.serial_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('order', function ($qo) {
                        $qo->where('order_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_so_number', 'like', '%' . $this->search . '%')
                            ->orWhereHas('user', function ($qu) {
                                $qu->where('name', 'like', '%' . $this->search . '%');
                            })
                            ->orWhereHas('salesBy', function ($qs) {
                                $qs->where('name', 'like', '%' . $this->search . '%');
                            })
                            ->orWhereHas('handledBy', function ($qh) {
                                $qh->where('name', 'like', '%' . $this->search . '%');
                            });
                    })
                    ->orWhereHasMorph('variant', [ProductAccurate::class], function ($vq) {
                        $vq->where('item_no', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    });
            });
        })
        ->when($this->vendorFilter, function ($query) {
            if ($this->vendorFilter === 'unknown') {
                $query->where(function ($qi) {
                    $qi->whereNull('serial_number')->orWhere('serial_number', '');
                });
            } else {
                $snList = ProductSerialNumber::whereHas('vendor', function ($qv) {
                    $qv->where('vendor_name', $this->vendorFilter);
                })->pluck('serial_number')->toArray();

                if (!empty($snList)) {
                    $query->where(function ($qSub) use ($snList) {
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
                }
            }
        })
        ->when(!empty($this->proyekFilter), function ($query) {
            $query->whereHasMorph('variant', [ProductAccurate::class], function ($vq) {
                $vq->whereIn('proyek', $this->proyekFilter);
            });
        })
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->select('order_items.*')
        ->orderBy('orders.order_date', 'desc')
        ->orderBy('order_items.id', 'desc');
    }

    public function generateManagementData(): array
    {
        $items = $this->buildItemsQuery()->get();

        // Kumpulkan semua serial number untuk prefetch HPP & Vendor sekaligus
        $allSns = [];
        foreach ($items as $item) {
            if (!empty($item->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                foreach ($sns as $sn) {
                    $allSns[] = $sn;
                }
            }
        }
        $allSns = array_unique($allSns);

        $snMap = [];
        if (!empty($allSns)) {
            $snMap = ProductSerialNumber::with('vendor')
                ->whereIn('serial_number', $allSns)
                ->get()
                ->keyBy('serial_number');
        }

        $rows = [];

        foreach ($items as $item) {
            $order = $item->order;
            if (!$order) continue;

            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

            // Hitung Penjualan Bersih Item
            $itemPromosTotal = $item->promos->sum('pivot.discount_amount');
            $promoNamesArray = $item->promos->pluck('name')->unique()->toArray();
            $promoNamesStr = !empty($promoNamesArray) ? implode(', ', $promoNamesArray) : '-';

            $actualItemSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;
            $penjualanBersih = round($actualItemSubtotal);

            // Detail Varian Produk
            $variant = $item->variant;
            $sku = $variant?->item_no ?? $variant?->sku ?? $variant?->accurateData?->item_no ?? '-';
            $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
            $merk = $variant?->brandName ?? $variant?->accurateData?->brandName ?? $variant?->product?->brand?->name ?? 'Unknown';
            $category = $variant?->categoryName ?? $variant?->accurateData?->categoryName ?? 'Unknown';
            $proyek = $variant?->proyek ?? '-';

            $snList = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));
            $vendor = '-';
            $itemHpp = 0;

            if (!empty($snList)) {
                // Sesuai Aturan: Jika produk ber-SN, ambil HPP dari product_serial_numbers
                $vendorNames = [];
                foreach ($snList as $sn) {
                    $snModel = $snMap->get($sn);
                    if ($snModel?->vendor?->vendor_name) {
                        $vendorNames[] = $snModel->vendor->vendor_name;
                    }
                    $snHpp = (float)($snModel?->hpp ?? 0);
                    // Fallback jika HPP SN belum terisi / 0: ambil HPP rata-rata base_cost
                    if ($snHpp <= 0) {
                        $snHpp = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                    }
                    $itemHpp += $snHpp;
                }
                $vendorNames = array_unique($vendorNames);
                $vendor = !empty($vendorNames) ? implode(', ', $vendorNames) : '-';
            } else {
                // Sesuai Aturan: Jika non-SN, ambil HPP rata-rata (base_cost dari ProductAccurate) * qty
                $baseCost = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                $itemHpp = $baseCost * (float)$item->qty;
                if (!empty($variant?->vendor_name)) {
                    $vendor = $variant->vendor_name;
                }
            }

            $itemHpp = round($itemHpp);
            $margin = $penjualanBersih - $itemHpp;
            $marginPct = $penjualanBersih > 0 ? round(($margin / $penjualanBersih) * 100, 2) : 0;

            $businessUnitName = $order->businessUnit?->name ?? '-';

            $rows[] = [
                $order->order_date ? $order->order_date->format('d-m-Y') : $order->created_at->format('d-m-Y'),
                $order->order_number,
                $order->accurate_invoice_no ?? '-',
                $order->accurate_so_number ?? '-',
                $businessUnitName,
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
                $item->serial_number ?? '-',
                str_replace(["\n", "\r", "\t"], ' ', $order->notes ?? ''),
                $item->qty,
                $item->price_at_checkout,
                $item->discount_amount ?? 0,
                $promoNamesStr,
                $itemPromosTotal,
                $item->subtotal,
                $penjualanBersih,
                $itemHpp,
                $margin,
                $marginPct . '%'
            ];
        }

        return $rows;
    }

    public function exportCsv()
    {
        $rows = $this->generateManagementData();
        $csvFileName = 'laporan_penjualan_management_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;

        return response()->streamDownload(function () use ($rows, $separator) {
            $file = fopen('php://output', 'w');

            // Header Laporan Penjualan Management (sampai Penjualan Bersih + HPP, Margin, Margin %)
            fputcsv($file, [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
                'NO. SALES ORDER(SO)',
                'UNIT BISNIS',
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
                'SN (SerialNumber)',
                'CATATAN',
                'QTY',
                'HARGA SATUAN (Rp)',
                'DISKON ITEM (Rp)',
                'NAMA PROMO',
                'DISKON PROMO (Rp)',
                'SUBTOTAL ITEM (Rp)',
                'PENJUALAN BERSIH (Rp)',
                'HPP (Rp)',
                'MARGIN (Rp)',
                'MARGIN (%)'
            ], $separator);

            foreach ($rows as $row) {
                fputcsv($file, $row, $separator);
            }

            fclose($file);
        }, $csvFileName);
    }

    public function exportExcel()
    {
        $rows = $this->generateManagementData();
        $excelFileName = 'laporan_penjualan_management_' . $this->startDate . '_sd_' . $this->endDate . '.xlsx';

        return Excel::download(new ManagementSalesReportExport($rows), $excelFileName);
    }

    public function render()
    {
        $paginatedItems = $this->buildItemsQuery()->paginate(20);

        // Pre-fetch SN data khusus halaman aktif untuk performa cepat di Blade UI
        $pageSns = [];
        foreach ($paginatedItems as $item) {
            if (!empty($item->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                foreach ($sns as $sn) {
                    $pageSns[] = $sn;
                }
            }
        }
        $pageSns = array_unique($pageSns);

        $snDataMap = [];
        if (!empty($pageSns)) {
            $snDataMap = ProductSerialNumber::with('vendor')
                ->whereIn('serial_number', $pageSns)
                ->get()
                ->keyBy('serial_number');
        }

        // Hitung metrik ringkasan (Summary Cards)
        $allMatchingItems = $this->buildItemsQuery()->get();
        $totalOrdersCount = $this->ordersQuery->count();
        $totalPenjualanBersih = 0;
        $totalHpp = 0;

        // Kumpulkan semua SN untuk summary total
        $summarySns = [];
        foreach ($allMatchingItems as $item) {
            if (!empty($item->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                foreach ($sns as $sn) {
                    $summarySns[] = $sn;
                }
            }
        }
        $summarySns = array_unique($summarySns);

        $summarySnMap = [];
        if (!empty($summarySns)) {
            $summarySnMap = ProductSerialNumber::whereIn('serial_number', $summarySns)
                ->pluck('hpp', 'serial_number')
                ->toArray();
        }

        foreach ($allMatchingItems as $item) {
            $itemPromosTotal = $item->promos->sum('pivot.discount_amount');
            $actualItemSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;
            $penjualanBersihItem = round($actualItemSubtotal);
            $totalPenjualanBersih += $penjualanBersihItem;

            $itemHpp = 0;
            if (!empty($item->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                foreach ($sns as $sn) {
                    $snHpp = isset($summarySnMap[$sn]) ? (float)$summarySnMap[$sn] : 0;
                    if ($snHpp <= 0) {
                        $snHpp = (float)($item->variant?->base_cost ?? $item->variant?->accurateData?->base_cost ?? 0);
                    }
                    $itemHpp += $snHpp;
                }
            } else {
                $baseCost = (float)($item->variant?->base_cost ?? $item->variant?->accurateData?->base_cost ?? 0);
                $itemHpp = $baseCost * (float)$item->qty;
            }
            $totalHpp += round($itemHpp);
        }

        $totalMargin = $totalPenjualanBersih - $totalHpp;
        $overallMarginPct = $totalPenjualanBersih > 0 ? round(($totalMargin / $totalPenjualanBersih) * 100, 2) : 0;

        $businessUnits = BusinessUnit::where('is_active', true)->orderBy('name')->get();

        $buId = $this->businessUnitFilter ?: Auth::user()->getActiveBusinessUnitId();

        $availableBranches = Branch::when($buId && $buId !== 'all', function ($q) use ($buId) {
                $q->where('business_unit_id', $buId);
            })
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        $availableVendors = Vendor::orderBy('vendor_name')
            ->pluck('vendor_name')
            ->filter()
            ->unique()
            ->values();

        $availableProjects = ProductAccurate::when($buId && $buId !== 'all', function ($q) use ($buId) {
                $q->where('business_unit_id', $buId);
            })
            ->whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->orderBy('proyek')
            ->pluck('proyek')
            ->unique()
            ->values();

        return view('livewire.zoffline.reporting.management-sales-report', [
            'items' => $paginatedItems,
            'snDataMap' => $snDataMap,
            'businessUnits' => $businessUnits,
            'availableBranches' => $availableBranches,
            'availableVendors' => $availableVendors,
            'availableProjects' => $availableProjects,
            'summary' => [
                'orders_count' => $totalOrdersCount,
                'items_count' => $allMatchingItems->count(),
                'net_sales' => $totalPenjualanBersih,
                'total_hpp' => $totalHpp,
                'total_margin' => $totalMargin,
                'margin_pct' => $overallMarginPct,
            ]
        ])->layout('layouts.z');
    }
}
