<?php

namespace App\Livewire\Admin\Reporting;

use App\Models\Order;
use Carbon\Carbon;
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

        return Order::with(['user', 'salesBy', 'paymentMethod', 'items.variant.product', 'promos'])
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.order_status', 'COMPLETED')
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
            ->latest('orders.created_at');
    }

    public function exportCsv()
    {
        // Eager load relasi payments untuk performa saat generate CSV
        $orders = $this->ordersQuery->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy', 'paymentMethodRate', 'promos'])->get();
        $csvFileName = 'laporan_penjualan_detail_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;
        return response()->streamDownload(function () use ($orders, $separator) {
            $file = fopen('php://output', 'w');

            // 1. Kumpulkan semua Nama Metode Pembayaran & Promo unik dari koleksi order ini
            $allPaymentMethodNames = [];
            $allPromoNames = [];

            foreach ($orders as $order) {
                if ($order->payments && $order->payments->count() > 0) {
                    foreach ($order->payments as $payment) {
                        $methodName = $payment->paymentMethod ? $payment->paymentMethod->name : 'Unknown Payment';
                        $allPaymentMethodNames[$methodName] = true;
                    }
                } else {
                    $methodName = $order->paymentMethod ? $order->paymentMethod->name : 'Unknown Payment';
                    if ($methodName !== 'Unknown Payment') {
                        $allPaymentMethodNames[$methodName] = true;
                    }
                }

                foreach ($order->promos as $promo) {
                    $allPromoNames[$promo->name] = true;
                }
            }

            $uniquePayments = array_keys($allPaymentMethodNames);
            $uniquePromos = array_keys($allPromoNames);

            // 2. Susun Header Secara Dinamis
            $headers = [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
                'KASIR',
                'SALES',
                'PELANGGAN',
                'TELEPON',
                'CABANG',
                'NAMA PRODUK',
                'MERK PRODUK',
                'CATEGORY',
                'WARNA',
                'STORAGE',
                'SN (SerialNumber)',
                'CATATAN',
                'QTY',
                'HARGA SATUAN (Rp)',
                'DISKON ITEM (Rp)',
                'SUBTOTAL ITEM (Rp)',
                'GROSS ORDER (Rp)',
            ];

            foreach ($uniquePayments as $upm) {
                $headers[] = 'BYR: ' . strtoupper($upm) . ' (Rp)';
            }
            foreach ($uniquePromos as $upr) {
                $headers[] = 'PRM: ' . strtoupper($upr) . ' (Rp)';
            }

            $headers[] = 'MDR (Rp)';
            $headers[] = 'TOTAL TRANSAKSI (Rp)';
            $headers[] = 'NET SALES (Rp)';

            fputcsv($file, $headers, $separator);

            foreach ($orders as $order) {
                $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

                // Rekap Total Pembayaran per Metode untuk Order ini
                $orderPayments = [];
                if ($order->payments && $order->payments->count() > 0) {
                    foreach ($order->payments as $payment) {
                        $pmName = $payment->paymentMethod ? $payment->paymentMethod->name : 'Unknown Payment';
                        if (!isset($orderPayments[$pmName])) $orderPayments[$pmName] = 0;
                        $orderPayments[$pmName] += $payment->amount;
                    }
                } else {
                    $pmName = $order->paymentMethod ? $order->paymentMethod->name : 'Unknown Payment';
                    if ($pmName !== 'Unknown Payment') {
                        $orderPayments[$pmName] = $order->grand_total; // Fallback transaksi lama
                    }
                }

                // Rekap Total Promo untuk Order ini
                $orderPromos = [];
                foreach ($order->promos as $promo) {
                    if (!isset($orderPromos[$promo->name])) $orderPromos[$promo->name] = 0;
                    $orderPromos[$promo->name] += $promo->pivot->discount_applied ?? 0;
                }

                // Total Subtotal Item untuk menghitung bobot prorata
                $totalOrderItemsSubtotal = $order->items->sum('subtotal');
                if ($totalOrderItemsSubtotal == 0) $totalOrderItemsSubtotal = 1; // Mencegah division by zero

                $allocatedPayments = [];
                $allocatedPromos = [];
                $allocatedMdrTotal = 0;
                $allocatedGrossTotal = 0;

                $itemCount = $order->items->count();
                $currentIndex = 0;

                if ($itemCount > 0) {
                    foreach ($order->items as $item) {
                        $currentIndex++;
                        $isLastItem = ($currentIndex === $itemCount);
                        $weight = $item->subtotal / $totalOrderItemsSubtotal;

                        $variant = $item->variant;
                        // Gunakan null-safe operator (?->) agar tidak error jika variant sudah dihapus
                        $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                        $merk = $variant?->accurateData?->brandName ?? 'Unknown';
                        $category = $variant?->accurateData?->categoryName ?? 'Unknown';
                        // Prorata Gross Order
                        if ($isLastItem) {
                            $proratedGross = $order->total_amount - $allocatedGrossTotal;
                        } else {
                            $proratedGross = round($order->total_amount * $weight);
                            $allocatedGrossTotal += $proratedGross;
                        }

                        $rowData = [
                            $order->created_at->format('Y-m-d H:i'),
                            $order->order_number,
                            $order->accurate_invoice_no ?? '-',
                            $order->handledBy ? $order->handledBy->name : '-',
                            $order->user ? $order->user->name : 'Walk-in',
                            $order->user ? $order->user->profile->phone_number : '-',
                            $order->salesBy ? $order->salesBy->name : '-',
                            $branch,
                            $name,
                            $merk,
                            $category,
                            $variant?->color ?? '-',
                            ($variant?->ram ? $variant->ram . ' ' : '') . ($variant?->storage ? $variant->storage : '') ?? '-',
                            $item->serial_number ?? '-',
                            str_replace(["\r", "\n", "\t"], ' ', $order->notes),
                            $item->qty,
                            $item->price_at_checkout,
                            $item->discount_amount ?? 0,
                            $item->subtotal,
                            $proratedGross,
                        ];

                        $itemPromosTotal = 0;

                        // Alokasi Pembayaran
                        foreach ($uniquePayments as $upm) {
                            $orderAmount = $orderPayments[$upm] ?? 0;
                            if ($isLastItem) {
                                $allocated = $orderAmount - ($allocatedPayments[$upm] ?? 0);
                            } else {
                                $allocated = round($orderAmount * $weight);
                                if (!isset($allocatedPayments[$upm])) $allocatedPayments[$upm] = 0;
                                $allocatedPayments[$upm] += $allocated;
                            }
                            $rowData[] = $allocated;
                        }

                        // Alokasi Promo
                        foreach ($uniquePromos as $upr) {
                            $orderAmount = $orderPromos[$upr] ?? 0;
                            if ($isLastItem) {
                                $allocated = $orderAmount - ($allocatedPromos[$upr] ?? 0);
                            } else {
                                $allocated = round($orderAmount * $weight);
                                if (!isset($allocatedPromos[$upr])) $allocatedPromos[$upr] = 0;
                                $allocatedPromos[$upr] += $allocated;
                            }
                            $itemPromosTotal += $allocated;
                            $rowData[] = $allocated;
                        }

                        // Alokasi MDR
                        $mdrAmount = $order->mdr_amount ?? 0;
                        if ($isLastItem) {
                            $allocatedMdr = $mdrAmount - $allocatedMdrTotal;
                        } else {
                            $allocatedMdr = round($mdrAmount * $weight);
                            $allocatedMdrTotal += $allocatedMdr;
                        }
                        $rowData[] = $allocatedMdr;

                        // TOTAL TRANSAKSI (Subtotal - Total Promo Item Ini)
                        $itemTotalTransaksi = $item->subtotal - $itemPromosTotal;
                        $rowData[] = $itemTotalTransaksi;

                        // NET SALES
                        $rowData[] = $itemTotalTransaksi - $allocatedMdr;

                        fputcsv($file, $rowData, $separator);
                    }
                } else {
                    // Fallback jika tidak ada item (sangat jarang terjadi)
                    $rowData = [
                        $order->created_at->format('Y-m-d H:i'),
                        $order->order_number,
                        $order->accurate_invoice_no ?? '-',
                        $order->handledBy ? $order->handledBy->name : '-',
                        $order->user ? $order->user->name : 'Walk-in',
                        $order->user ? $order->user->profile->phone_number : '-',
                        $order->salesBy ? $order->salesBy->name : '-',
                        $branch,
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
                        $order->total_amount
                    ];

                    $itemPromosTotal = 0;
                    foreach ($uniquePayments as $upm) {
                        $rowData[] = $orderPayments[$upm] ?? 0;
                    }
                    foreach ($uniquePromos as $upr) {
                        $promoVal = $orderPromos[$upr] ?? 0;
                        $itemPromosTotal += $promoVal;
                        $rowData[] = $promoVal;
                    }

                    $mdrPct = $order->paymentMethodRate->mdr_percentage ?? 0;
                    $mdrAmount = ($order->grand_total * $mdrPct) / 100;
                    $rowData[] = $mdrAmount;
                    $rowData[] = $order->grand_total; // TOTAL TRANSAKSI
                    $rowData[] = $order->grand_total - $mdrAmount; // NET SALES

                    fputcsv($file, $rowData, $separator);
                }
            }
            fclose($file);
        }, $csvFileName);
    }

    public function exportCsvOpsi2()
    {
        // Eager load relasi payments untuk performa saat generate CSV
        $orders = $this->ordersQuery->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy', 'paymentMethodRate', 'promos'])->get();
        $csvFileName = 'laporan_penjualan_multi_row_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;
        return response()->streamDownload(function () use ($orders, $separator) {
            $file = fopen('php://output', 'w');

            // Header untuk Opsi 2 (Multi-row)
            fputcsv($file, [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
                'KASIR',
                'SALES',
                'PELANGGAN',
                'TELEPON',
                'CABANG',
                'TIPE BARIS',
                'NAMA PRODUK',
                'MERK PRODUK',
                'WARNA',
                'STORAGE',
                'SN (SerialNumber)',
                'CATATAN',
                'QTY',
                'HARGA SATUAN (Rp)',
                'DISKON ITEM (Rp)',
                'SUBTOTAL ITEM (Rp)',
                'METODE BAYAR / NAMA PROMO',
                'NOMINAL BAYAR / DISKON PROMO (Rp)',
                'GROSS ORDER (Rp)',
                'MDR (Rp)',
                'TOTAL TRANSAKSI (Rp)',
                'NET SALES (Rp)'
            ]);

            foreach ($orders as $order) {
                $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';
                $orderDate = $order->created_at->format('Y-m-d H:i');
                $orderNo = $order->order_number;
                $invNo = $order->accurate_invoice_no ?? '-';
                $kasir = $order->handledBy ? $order->handledBy->name : '-';
                $pelanggan = $order->user ? $order->user->name : 'Walk-in';
                $telp = $order->user ? $order->user->profile->phone_number : '-';
                $sales = $order->salesBy ? $order->salesBy->name : '-';

                $baseRow = [
                    $orderDate,
                    $orderNo,
                    $invNo,
                    $kasir,
                    $sales,
                    $pelanggan,
                    $telp,
                    $branch
                ];

                // 1. Tulis Baris Item
                if ($order->items->count() > 0) {
                    foreach ($order->items as $item) {
                        $variant = $item->variant;
                        $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                        $merk = $variant?->product?->brand?->name ?? 'Unknown';

                        $row = array_merge($baseRow, [
                            'ITEM', // TIPE BARIS
                            $name,
                            $merk,
                            $variant?->color ?? '-',
                            ($variant?->ram ? $variant->ram . ' ' : '') . ($variant?->storage ? $variant->storage : '') ?? '-',
                            $item->serial_number ?? '-',
                            str_replace(["\r", "\n", "\t"], ' ', $order->notes),
                            $item->qty,
                            $item->price_at_checkout,
                            $item->discount_amount ?? 0,
                            $item->subtotal,
                            '-', // METODE BAYAR / PROMO
                            '0', // NOMINAL
                            $order->total_amount, // GROSS
                            ($order->grand_total * ($order->paymentMethodRate->mdr_percentage ?? 0)) / 100,
                            $order->grand_total,
                            $order->grand_total - (($order->grand_total * ($order->paymentMethodRate->mdr_percentage ?? 0)) / 100)
                        ]);
                        fputcsv($file, $row, $separator);
                    }
                } else {
                    $row = array_merge($baseRow, [
                        'ITEM',
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
                        '-',
                        '0',
                        $order->total_amount,
                        ($order->grand_total * ($order->paymentMethodRate->mdr_percentage ?? 0)) / 100,
                        $order->grand_total,
                        $order->grand_total - (($order->grand_total * ($order->paymentMethodRate->mdr_percentage ?? 0)) / 100)
                    ]);
                    fputcsv($file, $row, $separator);
                }

                // 2. Tulis Baris Pembayaran
                if ($order->payments && $order->payments->count() > 0) {
                    foreach ($order->payments as $payment) {
                        $methodName = $payment->paymentMethod ? $payment->paymentMethod->name : 'Unknown Payment';
                        $rateName = $payment->paymentMethodRate ? $payment->paymentMethodRate->name : '';
                        $paymentLabel = $methodName . ($rateName ? " ($rateName)" : "");

                        $row = array_merge($baseRow, [
                            'PEMBAYARAN',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-', // Produk Info Kosong
                            '0',
                            '0',
                            '0',
                            '0', // Angka Item Kosong
                            $paymentLabel,
                            $payment->amount,
                            '0',
                            '0',
                            '0',
                            '0' // Angka Order Kosong agar tidak didouble count
                        ]);
                        fputcsv($file, $row, $separator);
                    }
                } else {
                    $methodName = $order->paymentMethod ? $order->paymentMethod->name : 'Unknown Payment';
                    if ($methodName !== 'Unknown Payment') {
                        $row = array_merge($baseRow, [
                            'PEMBAYARAN',
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
                            $methodName,
                            $order->grand_total,
                            '0',
                            '0',
                            '0',
                            '0'
                        ]);
                        fputcsv($file, $row, $separator);
                    }
                }

                // 3. Tulis Baris Promo
                foreach ($order->promos as $promo) {
                    $row = array_merge($baseRow, [
                        'PROMO',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-', // Produk Info Kosong
                        '0',
                        '0',
                        '0',
                        '0', // Angka Item Kosong
                        $promo->name,
                        $promo->pivot->discount_applied ?? 0,
                        '0',
                        '0',
                        '0',
                        '0' // Angka Order Kosong
                    ]);
                    fputcsv($file, $row, $separator);
                }
            }
            fclose($file);
        }, $csvFileName);
    }

    public function exportCsvOpsi3()
    {
        // Eager load relasi payments untuk performa saat generate CSV
        $orders = $this->ordersQuery->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy', 'paymentMethodRate', 'promos.skus', 'promos.bundleSkus'])->get();
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
                'METODE 2',
                'NOMINAL 2 (Rp)',
                'MDR 2 (%)',
                'BEBAN MDR 2 (Rp)',
                'TIPE BEBAN MDR 2',
                'METODE 3',
                'NOMINAL 3 (Rp)',
                'MDR 3 (%)',
                'BEBAN MDR 3 (Rp)',
                'TIPE BEBAN MDR 3',
                'METODE 4',
                'NOMINAL 4 (Rp)',
                'MDR 4 (%)',
                'BEBAN MDR 4 (Rp)',
                'TIPE BEBAN MDR 4',
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
                                'mdr_name' => $pmrName
                            ];
                        }
                        $orderPayments[$key]['amount'] += $payment->amount;
                        $orderPayments[$key]['mdr_amount'] += $mdrAmt;
                    }
                } else {
                    $pmName = $order->paymentMethod ? $order->paymentMethod->name : 'Unknown Payment';
                    if ($pmName !== 'Unknown Payment') {
                        $pmrPct = $order->paymentMethodRate ? $order->paymentMethodRate->mdr_percentage : 0;
                        $pmrName = $order->paymentMethodRate ? $order->paymentMethodRate->name : '-';
                        $mdrAmt = round(($order->grand_total * $pmrPct) / 100);

                        $key = $pmName . '|' . $pmrPct . '|' . $pmrName;
                        $orderPayments[$key] = [
                            'name' => $pmName,
                            'amount' => $order->grand_total,
                            'mdr_pct' => $pmrPct,
                            'mdr_amount' => $mdrAmt,
                            'mdr_name' => $pmrName
                        ];
                    }
                }
                $orderPayments = array_values($orderPayments);

                // Pra-kalkulasi kelayakan promo
                $promoEligibleSubtotals = [];
                foreach ($order->promos as $promo) {
                    $promoSkus = $promo->skus->pluck('sku')->toArray();
                    $bundleSkus = $promo->bundleSkus->pluck('sku')->toArray();

                    $validSubtotal = 0;
                    foreach ($order->items as $item) {
                        $sku = $item->variant?->sku;
                        $isMainEligible = ($promo->apply_to_all_items && !$promo->is_bundle) || in_array($sku, $promoSkus);
                        $isBundleEligible = $promo->is_bundle && in_array($sku, $bundleSkus);

                        if ($isMainEligible || $isBundleEligible) {
                            $validSubtotal += $item->subtotal;
                        }
                    }
                    $promoEligibleSubtotals[$promo->id] = $validSubtotal > 0 ? $validSubtotal : 1;
                }

                // PASS 1: Hitung Subtotal Aktual tiap item untuk Bobot Prorata Nominal Pembayaran
                $itemPromoData = [];
                $itemActualSubtotals = [];
                $totalOrderActualSubtotal = 0;
                $allocatedPromosTracker = [];

                $itemCount = $order->items->count();
                $currentIndex = 0;

                foreach ($order->items as $item) {
                    $currentIndex++;
                    $isLastItem = ($currentIndex === $itemCount);
                    $sku = $item->variant?->sku;

                    $itemPromosTotal = 0;
                    $promoNames = [];

                    foreach ($order->promos as $promo) {
                        $promoSkus = $promo->skus->pluck('sku')->toArray();
                        $bundleSkus = $promo->bundleSkus->pluck('sku')->toArray();

                        $isMainEligible = ($promo->apply_to_all_items && !$promo->is_bundle) || in_array($sku, $promoSkus);
                        $isBundleEligible = $promo->is_bundle && in_array($sku, $bundleSkus);

                        if ($isMainEligible || $isBundleEligible) {
                            $promoWeight = $item->subtotal / $promoEligibleSubtotals[$promo->id];
                            $orderAmount = $promo->pivot->discount_applied ?? 0;

                            if ($isLastItem) {
                                $allocated = $orderAmount - ($allocatedPromosTracker[$promo->id] ?? 0);
                            } else {
                                $allocated = round($orderAmount * $promoWeight);
                                if (!isset($allocatedPromosTracker[$promo->id])) $allocatedPromosTracker[$promo->id] = 0;
                                $allocatedPromosTracker[$promo->id] += $allocated;
                            }
                            $itemPromosTotal += $allocated;
                            $promoNames[] = $promo->name;
                        }
                    }

                    $actualItemSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;

                    $itemPromoData[$item->id] = [
                        'promo_names' => !empty($promoNames) ? implode(', ', $promoNames) : '-',
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
                        $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                        $merk = $variant?->accurateData?->brandName ?? 'Unknown';
                        $category = $variant?->accurateData?->categoryName ?? 'Unknown';

                        $promoNamesStr = $itemPromoData[$item->id]['promo_names'];
                        $itemPromosTotal = $itemPromoData[$item->id]['promo_total'];

                        // Penjualan Bersih = (Qty * Harga / 1.11) - diskon item - diskon promo
                        $penjualanBersih = round(($item->subtotal / 1.11) - ($item->discount_amount ?? 0) - $itemPromosTotal);

                        $rowData = [
                            $order->created_at->format('Y-m-d H:i'),
                            $order->order_number,
                            $order->accurate_invoice_no ?? '-',
                            $order->handledBy ? $order->handledBy->name : '-',
                            $order->salesBy ? $order->salesBy->name : '-',
                            $order->user ? $order->user->name : 'Walk-in',
                            $order->user ? $order->user->profile->phone_number : '-',
                            $branch,
                            $sku = $variant?->sku ?? '-',
                            $name,
                            $merk,
                            $category,
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

                                $itemTotalPembayaranKotor += $allocatedNominalKotor;
                            } else {
                                $rowData[] = '-';
                                $rowData[] = '0';
                                $rowData[] = '0';
                                $rowData[] = '0';
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

                            $itemTotalPembayaranKotor += $upm['amount'];
                        } else {
                            $rowData[] = '-';
                            $rowData[] = '0';
                            $rowData[] = '0';
                            $rowData[] = '0';
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
        $availableBranches = \App\Models\Branch::orderBy('name')->pluck('name');

        $totalGross = $this->ordersQuery->sum('total_amount');
        $netQuery = clone $this->ordersQuery;
        $totalNet = $netQuery->leftJoin('payment_method_rates', 'orders.payment_method_rate_id', '=', 'payment_method_rates.id')
            ->sum(\Illuminate\Support\Facades\DB::raw('orders.grand_total - ((orders.grand_total * COALESCE(payment_method_rates.mdr_percentage, 0)) / 100)'));

        return view('livewire.admin.reporting.sales-report', [
            'orders' => $orders,
            'availableBranches' => $availableBranches,
            'summary' => [
                'count' => $orders->total(),
                'gross' => $totalGross,
                'net' => $totalNet
            ]
        ])->layout('layouts.admin');
    }
}
