<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SalesReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
    public $branchFilter = '';
    public $csvSeparator = ';';

    public function mount()
    {
        $this->setDateRange();
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
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED'])
            ->when($this->search, function ($query) {
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
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            })
            ->where('orders.business_unit_id', Auth::user()->getActiveBusinessUnitId())
            ->latest('orders.created_at');
    }

    public function exportCsvOpsi3()
    {
        // Eager load relasi payments untuk performa saat generate CSV
        $orders = $this->ordersQuery->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy', 'items.promos', 'promos.skus', 'promos.bundleSkus'])->get();
        $csvFileName = 'laporan_penjualan_kolom_statis_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;
        return response()->streamDownload(function () use ($orders, $separator) {
            $file = fopen('php://output', 'w');

            // Header untuk Opsi 3 (Kolom Statis)
            fputcsv($file, [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
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

                // PASS 2: Render Baris CSV dengan Bobot Baru
                $allocatedPaymentsTracker = [];
                $allocatedMdrTracker = [];
                $currentIndex = 0;

                if ($itemCount > 0) {
                    foreach ($order->items as $item) {
                        $currentIndex++;
                        $isLastItem = ($currentIndex === $itemCount);

                        // Atasan menggunakan bobot berdasarkan Subtotal SETELAH Diskon
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
                            $psns = \App\Models\ProductSerialNumber::with('vendor')
                                ->whereIn('serial_number', $snList)
                                ->get()
                                ->keyBy('serial_number');

                            $vendorNames = [];
                            foreach ($snList as $sn) {
                                $vendorNames[] = $psns->get($sn)?->vendor?->vendor_name ?? '-';
                            }

                            $vendorNames = array_unique($vendorNames);
                            $vendor = implode(', ', $vendorNames);
                        }

                        $promoNamesStr = $itemPromoData[$item->id]['promo_names'];
                        $itemPromosTotal = $itemPromoData[$item->id]['promo_total'];

                        // Penjualan Bersih = (Subtotal setelah semua diskon) / 1.11
                        $penjualanBersih = round($actualItemSubtotal / 1.11);

                        $rowData = [
                            $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d'),
                            $order->order_number,
                            $order->accurate_invoice_no ?? '-',
                            $order->handledBy ? $order->handledBy->name : '-',
                            $order->salesBy ? $order->salesBy->name : '-',
                            $order->user ? $order->user->name : 'Walk-in',
                            $order->user ? $order->user->profile->phone_number : '-',
                            $branch,
                            $sku,
                            $name,
                            $merk,
                            $category,
                            $vendor,
                            $variant?->color ?? '-',
                            ($variant?->ram ? $variant->ram . ' ' : '') . ($variant?->storage ? $variant->storage : '') ?? '-',
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

                        // Proses Slots Pembayaran (Maksimal 4) dan MDR-nya
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
                                $rowData[] = '0';
                                $rowData[] = '0';
                                $rowData[] = '0';
                                $rowData[] = '-';
                                $rowData[] = '-';
                            }
                        }

                        $rowData[] = $itemTotalPembayaranKotor; // TOTAL PEMBAYARAN
                        fputcsv($file, $rowData, $separator);
                    }
                } else {
                    $promoNames = $order->promos->pluck('name')->toArray();
                    $promoNamesStr = !empty($promoNames) ? implode(', ', $promoNames) : '-';
                    $itemPromosTotal = $order->promos->sum('pivot.discount_applied');

                    $rowData = [
                        $order->created_at->format('Y-m-d H:i'),
                        $order->order_number,
                        $order->accurate_invoice_no ?? '-',
                        $order->handledBy ? $order->handledBy->name : '-',
                        $order->salesBy ? $order->salesBy->name : '-',
                        $order->user ? $order->user->name : 'Walk-in',
                        $order->user ? $order->user->profile->phone_number : '-',
                        $branch,
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '0',
                        '0',
                        '0',
                        '0',
                        $promoNamesStr,
                        $itemPromosTotal,
                        '0', // Subtotal
                        '0', // Penjualan Bersih
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
                            $rowData[] = '0';
                            $rowData[] = '0';
                            $rowData[] = '0';
                            $rowData[] = '-';
                            $rowData[] = '-';
                        }
                    }

                    $rowData[] = $itemTotalPembayaranKotor; // TOTAL PEMBAYARAN

                    fputcsv($file, $rowData, $separator);
                }
            }
            fclose($file);
        }, $csvFileName);
    }









    public function render()
    {
        $orders = $this->ordersQuery->paginate(20);
        $availableBranches = \App\Models\Branch::where('business_unit_id', Auth::user()->getActiveBusinessUnitId())
            ->orderBy('name')
            ->pluck('name');

        $totalGross = $this->ordersQuery->sum('total_amount');
        $netQuery = clone $this->ordersQuery;
        $totalNet = $netQuery->get()->sum(function ($order) {
            return $order->grand_total - $order->mdr_amount;
        });

        return view('livewire.zoffline.reporting.sales-report', [
            'orders' => $orders,
            'availableBranches' => $availableBranches,
            'summary' => [
                'count' => $orders->total(),
                'gross' => $totalGross,
                'net' => $totalNet
            ]
        ])->layout('layouts.z');
    }
}
