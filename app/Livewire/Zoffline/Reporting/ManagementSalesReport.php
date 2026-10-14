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
        $this->businessUnitFilter = (string)(Auth::user()?->getActiveBusinessUnitId() ?? '');
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
        ->whereIn('orders.order_status', ['COMPLETED', 'PIUTANG'])
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

    public function buildBaseItemsQuery()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $buId = $this->businessUnitFilter ?: (Auth::user()?->getActiveBusinessUnitId() ?? '');

        return OrderItem::query()
            ->whereHas('order', function ($oq) use ($start, $end, $buId) {
                $oq->whereBetween('order_date', [$start, $end])
                    ->whereIn('order_status', ['COMPLETED', 'PIUTANG'])
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
            ->when($this->vendorFilter, function ($query) use ($buId) {
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

    public function buildItemsQuery()
    {
        return $this->buildBaseItemsQuery()->with([
            'order.user.profile',
            'order.salesBy',
            'order.handledBy',
            'order.businessUnit',
            'variant',
            'promos'
        ]);
    }

    public function isReturnItem($item, $order = null): bool
    {
        $order = $order ?? $item->order;
        if ($order) {
            if (str_starts_with($order->order_number ?? '', 'RET-')) {
                return true;
            }
            if (!empty($order->shipping_address_snapshot['is_warranty_return'])) {
                return true;
            }
            if (!empty($order->accurate_invoice_no) && str_starts_with($order->accurate_invoice_no, 'SRT')) {
                return true;
            }
            if ((float)($order->total_amount ?? 0) < 0) {
                return true;
            }
        }

        if ((float)($item->qty ?? 0) < 0 || (float)($item->subtotal ?? 0) < 0) {
            return true;
        }

        return false;
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

        $buId = $this->businessUnitFilter ?: (Auth::user()?->getActiveBusinessUnitId() ?? '');
        $snMap = collect();
        if (!empty($allSns)) {
            $snMap = ProductSerialNumber::when($buId && $buId !== 'all', function ($q) use ($buId) {
                    $q->where('business_unit_id', $buId);
                })
                ->with('vendor')
                ->whereIn('serial_number', $allSns)
                ->get()
                ->keyBy('serial_number');

            // Cross-BU Fallback: jika ada SN yang tidak ditemukan di BU aktif (misal transfer/cross-BU)
            $missingSns = array_values(array_diff($allSns, $snMap->keys()->toArray()));
            if (!empty($missingSns)) {
                $fallbackSnMap = ProductSerialNumber::with('vendor')
                    ->whereIn('serial_number', $missingSns)
                    ->get()
                    ->keyBy('serial_number');

                foreach ($fallbackSnMap as $sn => $model) {
                    $snMap->put($sn, $model);
                }
            }
        }

        $sellPhoneMap = collect();
        if (!empty($allSns)) {
            $sellPhones = \App\Models\SellPhone::with('user')
                ->whereIn('imei', $allSns)
                ->where('status', 'COMPLETED')
                ->where('appraised_value', '>', 0)
                ->latest()
                ->get();

            foreach ($sellPhones as $sp) {
                $sellPhoneMap->put($sp->imei . '_' . $sp->business_unit_id, $sp);
                if (!$sellPhoneMap->has($sp->imei)) {
                    $sellPhoneMap->put($sp->imei, $sp);
                }
            }
        }

        $rows = [];

        foreach ($items as $item) {
            $order = $item->order;
            if (!$order) continue;

            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';
            $isReturn = $this->isReturnItem($item, $order);

            // Hitung Penjualan Bersih Item
            $itemPromosTotal = (float)$item->promos->sum('pivot.discount_amount');
            $promoNamesArray = $item->promos->pluck('name')->unique()->toArray();
            $promoNamesStr = !empty($promoNamesArray) ? implode(', ', $promoNamesArray) : '-';

            $orderCouponDiscount = (float)($order->coupon_discount_amount ?? 0);
            $orderLoyaltyDiscount = (float)($order->loyalty_discount_amount ?? 0);
            $orderCouponCode = $order->coupon_code ?: '-';

            $orderTotalAmt = (float)($order->total_amount ?? 0);
            $itemRatio = ($orderTotalAmt > 0) ? ((float)$item->subtotal / $orderTotalAmt) : 0;
            $itemCouponDiscount = round($orderCouponDiscount * $itemRatio);
            $itemLoyaltyDiscount = round($orderLoyaltyDiscount * $itemRatio);

            $actualItemSubtotal = (float)$item->subtotal - (float)($item->discount_amount ?? 0) - $itemPromosTotal - $itemCouponDiscount - $itemLoyaltyDiscount;
            if ($isReturn && $actualItemSubtotal > 0) {
                $actualItemSubtotal = -$actualItemSubtotal;
            }
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
            $qty = (float)$item->qty;

            // 1. Prioritas Utama: Baca langsung dari kolom total_hpp yang sudah di-snapshot di order_items
            if (isset($item->total_hpp) && (float)$item->total_hpp != 0) {
                $itemHpp = (float)$item->total_hpp;
                // Ambil info vendor untuk display
                if (!empty($snList)) {
                    $vendorNames = [];
                    $orderBuId = $order->business_unit_id;
                    foreach ($snList as $sn) {
                        $spModel = $sellPhoneMap->get($sn . '_' . $orderBuId) ?? ($orderBuId == 2 ? $sellPhoneMap->get($sn) : null);
                        if ($spModel && (float)$spModel->appraised_value > 0) {
                            $customerName = $spModel->bank_account_name ?: ($spModel->user?->name ?? 'Customer');
                            $vendorNames[] = 'Customer: ' . $customerName;
                        } else {
                            $snModel = $snMap->get($sn);
                            if ($snModel?->vendor?->vendor_name) {
                                $vendorNames[] = $snModel->vendor->vendor_name;
                            }
                        }
                    }
                    $vendorNames = array_unique($vendorNames);
                    $vendor = !empty($vendorNames) ? implode(', ', $vendorNames) : '-';
                } else {
                    $vendor = $variant?->vendor_name ?? '-';
                }
            } elseif (!empty($snList)) {
                // Fallback runtime jika data belum di-backfill
                $vendorNames = [];
                $orderBuId = $order->business_unit_id;
                foreach ($snList as $sn) {
                    $snHpp = 0;
                    // Cek apakah perangkat dibeli via SellPhone (Buyback dari customer)
                    $spModel = $sellPhoneMap->get($sn . '_' . $orderBuId) ?? ($orderBuId == 2 ? $sellPhoneMap->get($sn) : null);
                    if ($spModel && (float)$spModel->appraised_value > 0) {
                        $snHpp = (float)$spModel->appraised_value;
                        $customerName = $spModel->bank_account_name ?: ($spModel->user?->name ?? 'Customer');
                        $vendorNames[] = 'Customer: ' . $customerName;
                    } else {
                        $snModel = $snMap->get($sn);
                        if ($snModel?->vendor?->vendor_name) {
                            $vendorNames[] = $snModel->vendor->vendor_name;
                        }
                        $snHpp = (float)($snModel?->hpp ?? 0);
                    }

                    // Fallback jika HPP SN belum terisi / 0: ambil base_cost -> buy_price
                    if ($snHpp <= 0) {
                        $snHpp = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                        if ($snHpp <= 0) {
                            $snHpp = (float)($variant?->buy_price ?? $variant?->accurateData?->buy_price ?? 0);
                        }
                    }
                    $itemHpp += $snHpp;
                }
                if ($isReturn) {
                    if ($qty < 0 && $itemHpp > 0) {
                        $itemHpp = -$itemHpp;
                    } elseif ($qty > 0 && $itemHpp > 0) {
                        $itemHpp = -$itemHpp;
                    }
                }
                $vendorNames = array_unique($vendorNames);
                $vendor = !empty($vendorNames) ? implode(', ', $vendorNames) : '-';
            } else {
                if (!empty($variant?->vendor_name)) {
                    $vendor = $variant->vendor_name;
                }
                // Sesuai Aturan: Jika non-SN, ambil base_cost -> buy_price * qty
                $baseCost = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                if ($baseCost <= 0) {
                    $baseCost = (float)($variant?->buy_price ?? $variant?->accurateData?->buy_price ?? 0);
                }
                $itemHpp = $baseCost * $qty;
                if ($isReturn && $itemHpp > 0) {
                    $itemHpp = -$itemHpp;
                }
            }

            $itemHpp = round($itemHpp);
            $margin = $penjualanBersih - $itemHpp;
            $marginPct = $penjualanBersih != 0 ? round(($margin / abs($penjualanBersih)) * 100, 2) : 0;

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
                $orderCouponCode,
                $itemCouponDiscount,
                $itemLoyaltyDiscount,
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
                'KODE VOUCHER',
                'DISKON VOUCHER (Rp)',
                'DISKON POIN (Rp)',
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

    public function calculateSummaryMetrics(): array
    {
        $buId = $this->businessUnitFilter ?: (Auth::user()?->getActiveBusinessUnitId() ?? '');
        $totalOrdersCount = $this->ordersQuery->count();

        $items = $this->buildBaseItemsQuery()
            ->with([
                'order:id,order_number,accurate_invoice_no,shipping_address_snapshot,total_amount,coupon_discount_amount,loyalty_discount_amount',
                'variant'
            ])
            ->withSum('promos as item_promos_discount', 'order_item_promos.discount_amount')
            ->get(['order_items.id', 'order_items.order_id', 'order_items.product_variant_type', 'order_items.product_variant_id', 'order_items.qty', 'order_items.subtotal', 'order_items.discount_amount', 'order_items.serial_number']);

        $totalItemRows = $items->count();
        $totalQty = 0;
        $totalPenjualanBersih = 0;
        $totalHpp = 0;

        $summarySns = [];
        foreach ($items as $item) {
            $totalQty += (float)$item->qty;
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
            $summarySnMap = ProductSerialNumber::when($buId && $buId !== 'all', function ($q) use ($buId) {
                    $q->where('business_unit_id', $buId);
                })
                ->whereIn('serial_number', $summarySns)
                ->pluck('hpp', 'serial_number')
                ->toArray();

            // Cross-BU Fallback: jika ada SN yang tidak ditemukan di BU aktif
            $missingSns = array_values(array_diff($summarySns, array_keys($summarySnMap)));
            if (!empty($missingSns)) {
                $fallbackSnMap = ProductSerialNumber::whereIn('serial_number', $missingSns)
                    ->pluck('hpp', 'serial_number')
                    ->toArray();

                $summarySnMap = $summarySnMap + $fallbackSnMap;
            }
        }

        $sellPhoneMap = collect();
        if (!empty($summarySns)) {
            $sellPhones = \App\Models\SellPhone::whereIn('imei', $summarySns)
                ->where('status', 'COMPLETED')
                ->where('appraised_value', '>', 0)
                ->get(['imei', 'business_unit_id', 'appraised_value']);

            foreach ($sellPhones as $sp) {
                $sellPhoneMap->put($sp->imei . '_' . $sp->business_unit_id, (float)$sp->appraised_value);
                if (!$sellPhoneMap->has($sp->imei)) {
                    $sellPhoneMap->put($sp->imei, (float)$sp->appraised_value);
                }
            }
        }

        foreach ($items as $item) {
            $order = $item->order;
            $isReturn = $this->isReturnItem($item, $order);

            $itemPromosTotal = (float)($item->item_promos_discount ?? 0);
            $orderHeaderDiscount = (float)(($order->coupon_discount_amount ?? 0) + ($order->loyalty_discount_amount ?? 0));
            $orderTotalAmt = (float)($order->total_amount ?? 0);
            $itemOrderDiscount = ($orderTotalAmt > 0 && $orderHeaderDiscount > 0) ? round(((float)$item->subtotal / $orderTotalAmt) * $orderHeaderDiscount) : 0;

            $actualItemSubtotal = (float)$item->subtotal - (float)($item->discount_amount ?? 0) - $itemPromosTotal - $itemOrderDiscount;
            if ($isReturn && $actualItemSubtotal > 0) {
                $actualItemSubtotal = -$actualItemSubtotal;
            }
            $penjualanBersihItem = round($actualItemSubtotal);
            $totalPenjualanBersih += $penjualanBersihItem;

            $variant = $item->variant;
            $itemHpp = 0;
            $qty = (float)$item->qty;

            // Prioritas Utama: gunakan total_hpp dari order_items jika sudah terisi
            if (isset($item->total_hpp) && (float)$item->total_hpp != 0) {
                $itemHpp = (float)$item->total_hpp;
            } elseif (!empty($item->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                $orderBuId = $order?->business_unit_id;
                foreach ($sns as $sn) {
                    $spHpp = $sellPhoneMap->get($sn . '_' . $orderBuId) ?? ($orderBuId == 2 ? $sellPhoneMap->get($sn) : null);
                    if ($spHpp && $spHpp > 0) {
                        $snHpp = $spHpp;
                    } else {
                        $snHpp = isset($summarySnMap[$sn]) ? (float)$summarySnMap[$sn] : 0;
                    }
                    if ($snHpp <= 0) {
                        $snHpp = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                        if ($snHpp <= 0) {
                            $snHpp = (float)($variant?->buy_price ?? $variant?->accurateData?->buy_price ?? 0);
                        }
                    }
                    $itemHpp += $snHpp;
                }
                if ($isReturn) {
                    if ($qty < 0 && $itemHpp > 0) {
                        $itemHpp = -$itemHpp;
                    } elseif ($qty > 0 && $itemHpp > 0) {
                        $itemHpp = -$itemHpp;
                    }
                }
            } else {
                $baseCost = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                if ($baseCost <= 0) {
                    $baseCost = (float)($variant?->buy_price ?? $variant?->accurateData?->buy_price ?? 0);
                }
                $itemHpp = $baseCost * $qty;
                if ($isReturn && $itemHpp > 0) {
                    $itemHpp = -$itemHpp;
                }
            }

            $totalHpp += round($itemHpp);
        }

        $totalMargin = $totalPenjualanBersih - $totalHpp;
        $overallMarginPct = $totalPenjualanBersih > 0 ? round(($totalMargin / $totalPenjualanBersih) * 100, 2) : 0;

        return [
            'orders_count' => $totalOrdersCount,
            'items_count' => $totalItemRows,
            'total_qty' => $totalQty,
            'net_sales' => $totalPenjualanBersih,
            'total_hpp' => $totalHpp,
            'total_margin' => $totalMargin,
            'margin_pct' => $overallMarginPct,
        ];
    }

    public function render()
    {
        $buId = $this->businessUnitFilter ?: (Auth::user()?->getActiveBusinessUnitId() ?? '');
        $paginatedItems = $this->buildItemsQuery()->paginate(20);

        // Pre-fetch SN data khusus halaman aktif untuk performa cepat di Blade UI (Tenant-Aware)
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

        $snDataMap = collect();
        if (!empty($pageSns)) {
            $snDataMap = ProductSerialNumber::when($buId && $buId !== 'all', function ($q) use ($buId) {
                    $q->where('business_unit_id', $buId);
                })
                ->with('vendor')
                ->whereIn('serial_number', $pageSns)
                ->get()
                ->keyBy('serial_number');

            // Cross-BU Fallback: jika ada SN yang tidak ditemukan di BU aktif
            $missingSns = array_values(array_diff($pageSns, $snDataMap->keys()->toArray()));
            if (!empty($missingSns)) {
                $fallbackSnMap = ProductSerialNumber::with('vendor')
                    ->whereIn('serial_number', $missingSns)
                    ->get()
                    ->keyBy('serial_number');

                foreach ($fallbackSnMap as $sn => $model) {
                    $snDataMap->put($sn, $model);
                }
            }
        }

        $sellPhoneMap = collect();
        if (!empty($pageSns)) {
            $sellPhones = \App\Models\SellPhone::with('user')
                ->whereIn('imei', $pageSns)
                ->where('status', 'COMPLETED')
                ->where('appraised_value', '>', 0)
                ->latest()
                ->get();

            foreach ($sellPhones as $sp) {
                $sellPhoneMap->put($sp->imei . '_' . $sp->business_unit_id, $sp);
                if (!$sellPhoneMap->has($sp->imei)) {
                    $sellPhoneMap->put($sp->imei, $sp);
                }
            }
        }

        // Hitung metrik ringkasan (Summary Cards) secara optimal dan hemat memori
        $summary = $this->calculateSummaryMetrics();

        $businessUnits = BusinessUnit::where('is_active', true)->orderBy('name')->get();

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
            'sellPhoneMap' => $sellPhoneMap,
            'businessUnits' => $businessUnits,
            'availableBranches' => $availableBranches,
            'availableVendors' => $availableVendors,
            'availableProjects' => $availableProjects,
            'summary' => $summary,
        ])->layout('layouts.z');
    }
}
