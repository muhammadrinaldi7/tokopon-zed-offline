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
        return response()->streamDownload(function () use ($orders) {
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

            fputcsv($file, $headers);

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
                        $merk = $variant?->product?->brand?->name ?? 'Unknown';

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

                        fputcsv($file, $rowData);
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

                    fputcsv($file, $rowData);
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
        return response()->streamDownload(function () use ($orders) {
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
                        fputcsv($file, $row);
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
                    fputcsv($file, $row);
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
                        fputcsv($file, $row);
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
                        fputcsv($file, $row);
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
                    fputcsv($file, $row);
                }
            }
            fclose($file);
        }, $csvFileName);
    }
    public function exportCsvOpsi3()
    {
        // Eager load relasi payments untuk performa saat generate CSV
        $orders = $this->ordersQuery->with(['payments.paymentMethod', 'payments.paymentMethodRate', 'handledBy', 'paymentMethodRate', 'promos'])->get();
        $csvFileName = 'laporan_penjualan_kolom_statis_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        return response()->streamDownload(function () use ($orders) {
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
                'METODE 1',
                'NOMINAL 1 (Rp)',
                'METODE 2',
                'NOMINAL 2 (Rp)',
                'METODE 3',
                'NOMINAL 3 (Rp)',
                'METODE 4',
                'NOMINAL 4 (Rp)',
                'PROMO 1',
                'DISKON 1 (Rp)',
                'PROMO 2',
                'DISKON 2 (Rp)',
                'PROMO 3',
                'DISKON 3 (Rp)',
                'Tipe Beban',
                'MDR (Rp)',
                'TOTAL TRANSAKSI (Rp)',
                'NET SALES (Rp)'
            ]);

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
                        $orderPayments[$pmName] = $order->grand_total;
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
                if ($totalOrderItemsSubtotal == 0) $totalOrderItemsSubtotal = 1;

                $allocatedPaymentsTracker = [];
                $allocatedPromosTracker = [];
                $allocatedMdrTotal = 0;

                $itemCount = $order->items->count();
                $currentIndex = 0;

                if ($itemCount > 0) {
                    foreach ($order->items as $item) {
                        $currentIndex++;
                        $isLastItem = ($currentIndex === $itemCount);
                        $weight = $item->subtotal / $totalOrderItemsSubtotal;

                        $variant = $item->variant;
                        $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                        $merk = $variant?->product?->brand?->name ?? 'Unknown';

                        $rowData = [
                            $order->created_at->format('Y-m-d H:i'),
                            $order->order_number,
                            $order->accurate_invoice_no ?? '-',
                            $order->handledBy ? $order->handledBy->name : '-',
                            $order->salesBy ? $order->salesBy->name : '-',
                            $order->user ? $order->user->name : 'Walk-in',
                            $order->user ? $order->user->profile->phone_number : '-',
                            $branch,
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
                        ];

                        // Proses Slots Pembayaran (Maksimal 4)
                        $paymentSlots = array_keys($orderPayments);
                        for ($i = 0; $i < 4; $i++) {
                            if (isset($paymentSlots[$i])) {
                                $upm = $paymentSlots[$i];
                                $orderAmount = $orderPayments[$upm];
                                if ($isLastItem) {
                                    $allocated = $orderAmount - ($allocatedPaymentsTracker[$upm] ?? 0);
                                } else {
                                    $allocated = round($orderAmount * $weight);
                                    if (!isset($allocatedPaymentsTracker[$upm])) $allocatedPaymentsTracker[$upm] = 0;
                                    $allocatedPaymentsTracker[$upm] += $allocated;
                                }
                                $rowData[] = $upm;
                                $rowData[] = $allocated;
                            } else {
                                $rowData[] = '-';
                                $rowData[] = '0';
                            }
                        }

                        // Proses Slots Promo (Maksimal 3)
                        $itemPromosTotal = 0;
                        $promoSlots = array_keys($orderPromos);
                        for ($i = 0; $i < 3; $i++) {
                            if (isset($promoSlots[$i])) {
                                $upr = $promoSlots[$i];
                                $orderAmount = $orderPromos[$upr];
                                if ($isLastItem) {
                                    $allocated = $orderAmount - ($allocatedPromosTracker[$upr] ?? 0);
                                } else {
                                    $allocated = round($orderAmount * $weight);
                                    if (!isset($allocatedPromosTracker[$upr])) $allocatedPromosTracker[$upr] = 0;
                                    $allocatedPromosTracker[$upr] += $allocated;
                                }
                                $itemPromosTotal += $allocated;
                                $rowData[] = $upr;
                                $rowData[] = $allocated;
                            } else {
                                $rowData[] = '-';
                                $rowData[] = '0';
                            }
                        }

                        // Alokasi MDR
                        $rowData[] = $order->paymentMethodRate->name ?? '-';
                        $mdrPct = $order->paymentMethodRate->mdr_percentage ?? 0;
                        $mdrAmount = ($order->grand_total * $mdrPct) / 100;
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

                        fputcsv($file, $rowData);
                    }
                } else {
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
                        '0'
                    ];

                    $paymentSlots = array_keys($orderPayments);
                    for ($i = 0; $i < 4; $i++) {
                        if (isset($paymentSlots[$i])) {
                            $rowData[] = $paymentSlots[$i];
                            $rowData[] = $orderPayments[$paymentSlots[$i]];
                        } else {
                            $rowData[] = '-';
                            $rowData[] = '0';
                        }
                    }

                    $itemPromosTotal = 0;
                    $promoSlots = array_keys($orderPromos);
                    for ($i = 0; $i < 3; $i++) {
                        if (isset($promoSlots[$i])) {
                            $promoVal = $orderPromos[$promoSlots[$i]];
                            $itemPromosTotal += $promoVal;
                            $rowData[] = $promoSlots[$i];
                            $rowData[] = $promoVal;
                        } else {
                            $rowData[] = '-';
                            $rowData[] = '0';
                        }
                    }

                    $mdrPct = $order->paymentMethodRate->mdr_percentage ?? 0;
                    $mdrAmount = ($order->grand_total * $mdrPct) / 100;
                    $rowData[] = $mdrAmount;
                    $rowData[] = $order->grand_total; // TOTAL TRANSAKSI
                    $rowData[] = $order->grand_total - $mdrAmount; // NET SALES

                    fputcsv($file, $rowData);
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
