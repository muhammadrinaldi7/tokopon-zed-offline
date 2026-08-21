<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\SalesReportExport;
use App\Exports\SalesVendorReportExport;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SalesReport extends Component
{
    use WithPagination;

    public $activeTab = 'transactions';
    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
    public $branchFilter = '';
    public $vendorFilter = '';
    public $csvSeparator = ';';

    public function mount()
    {
        $this->setDateRange();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function updatedVendorFilter()
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

        return Order::with(['user', 'salesBy', 'payments.paymentMethod', 'payments.paymentMethodRate', 'items.variant', 'promos'])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED'])
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
            ->where('orders.business_unit_id', Auth::user()->getActiveBusinessUnitId())
            ->latest('orders.order_date');
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
            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

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
                $key = 'Unknown Payment|0|-';
                $orderPayments[$key] = [
                    'name' => 'Unknown Payment',
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
            $currentIndex = 0;

            if ($itemCount > 0) {
                foreach ($order->items as $item) {
                    $currentIndex++;
                    $isLastItem = ($currentIndex === $itemCount);

                    $actualItemSubtotal = $itemPromoData[$item->id]['actual_subtotal'];
                    $weight = $actualItemSubtotal / $totalOrderActualSubtotal;

                    $variant = $item->variant;
                    $sku = $variant?->item_no ?? $variant?->sku ?? $variant?->accurateData?->item_no ?? '-';
                    $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                    $merk = $variant?->brandName ?? $variant?->accurateData?->brandName ?? $variant?->product?->brand?->name ?? 'Unknown';
                    $category = $variant?->categoryName ?? $variant?->accurateData?->categoryName ?? 'Unknown';
                    $snList = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));
                    $vendor = '-';

                    if (!empty($snList)) {
                        $vendorNames = [];
                        foreach ($snList as $sn) {
                            $vendorNames[] = $snVendors->get($sn)?->vendor?->vendor_name ?? '-';
                        }
                        $vendorNames = array_unique($vendorNames);
                        $vendor = implode(', ', $vendorNames);
                    }

                    $promoNamesStr = $itemPromoData[$item->id]['promo_names'];
                    $itemPromosTotal = $itemPromoData[$item->id]['promo_total'];
                    $penjualanBersih = round($actualItemSubtotal / 1.11);

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
                        $sku,
                        $name,
                        $merk,
                        $category,
                        $vendor,
                        $variant?->color ?? '-',
                        ($variant?->ram ? $variant->ram . ' ' : '') . ($variant?->storage ? $variant->storage : '') ?: '-',
                        $item->serial_number ?? '-',
                        str_replace(["\n", "\r", "\t"], ' ', $order->notes ?? ''),
                        $item->qty,
                        $item->price_at_checkout,
                        $item->discount_amount ?? 0,
                        $promoNamesStr,
                        $itemPromosTotal,
                        $actualItemSubtotal,
                        $penjualanBersih,
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
                    '-',
                    '-',
                    '-',
                    str_replace(["\n", "\r", "\t"], ' ', $order->notes ?? ''),
                    0,
                    0,
                    0,
                    $promoNamesStr,
                    $itemPromosTotal,
                    0, // Subtotal
                    0, // Penjualan Bersih
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
                'SKU',
                'NAMA PRODUK',
                'MERK PRODUK',
                'CATEGORY',
                'VENDOR',
                'WARNA',
                'STORAGE',
                'SN (SerialNumber)',
                'CATATAN',
                'QTY',
                'HARGA SATUAN (Rp)',
                'DISKON ITEM (Rp)',
                'NAMA PROMO',
                'DISKON PROMO (Rp)',
                'SUBTOTAL ITEM (Rp)',
                'PENJUALAN BERSIH',
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

    private function generateVendorData(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $orders = Order::with(['items.variant', 'items.promos', 'user', 'salesBy', 'handledBy'])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED'])
            ->where('orders.business_unit_id', Auth::user()->getActiveBusinessUnitId())
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            })
            ->latest('orders.order_date')
            ->get();

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
            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $sku = $variant?->item_no ?? $variant?->sku ?? '-';
                $productName = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';

                $itemPromosTotal = $item->promos->sum('pivot.discount_amount');
                $actualItemSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;
                $penjualanBersih = round($actualItemSubtotal / 1.11);

                $vendorNames = [];
                if ($item->serial_number) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $vendorModel = $snVendors->get($sn)?->vendor;
                        $vendorNames[] = $vendorModel?->vendor_name ?? 'Tanpa Vendor / Unknown';
                    }
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
                    $penjualanBersih
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
                'PENJUALAN BERSIH (Rp)'
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

        // Ambil order COMPLETED bulan ini untuk semua cabang (atau cabang terfilter)
        $orders = Order::with(['items.variant', 'items.promos'])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED'])
            ->where('orders.business_unit_id', Auth::user()->getActiveBusinessUnitId())
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            })
            ->get();

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
            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown Store';

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
        $availableBranches = \App\Models\Branch::where('business_unit_id', Auth::user()->getActiveBusinessUnitId())
            ->orderBy('name')
            ->pluck('name');

        $availableVendors = \App\Models\Vendor::orderBy('vendor_name')
            ->pluck('vendor_name')
            ->filter()
            ->unique()
            ->values();

        $totalGross = $this->ordersQuery->sum('total_amount');
        $netQuery = clone $this->ordersQuery;
        $totalNet = $netQuery->get()->sum(function ($order) {
            return $order->grand_total - $order->mdr_amount;
        });

        return view('livewire.zoffline.reporting.sales-report', [
            'orders' => $orders,
            'vendorSummary' => $this->vendorSummary,
            'availableBranches' => $availableBranches,
            'availableVendors' => $availableVendors,
            'summary' => [
                'count' => $orders->total(),
                'gross' => $totalGross,
                'net' => $totalNet
            ]
        ])->layout('layouts.z');
    }
}
