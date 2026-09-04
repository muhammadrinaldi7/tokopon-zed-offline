<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Exports\InvoiceReportExport;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceReport extends Component
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

        return Order::with(['user', 'handledBy', 'accurateDocs', 'salesBy', 'paymentMethod', 'items.variant.product', 'promos'])
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED', 'down_payment', 'paid'])
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

    protected function getPaymentRows(): array
    {
        $rows = [];

        // Gunakan chunk untuk menghemat memori (optimalisasi memory limit)
        $this->ordersQuery->with([
            'payments.paymentMethod',
            'payments.paymentMethodRate',
            'items.variant' => function (\Illuminate\Database\Eloquent\Relations\MorphTo $morphTo) {
                $morphTo->morphWith([
                    \App\Models\ProductVariant::class => ['accurateData'],
                    \App\Models\SecondProductVariant::class => ['accurateData'],
                ]);
            },
        ])->chunk(100, function ($orders) use (&$rows) {
            foreach ($orders as $order) {
                $namaToko = $order->shipping_address_snapshot['store'] ?? null;
                $invoiceNo = $order->accurate_invoice_no ?? $order->accurate_so_number ?? null;
                $orderNo = $order->order_number;

                // 1. Kalkulasi Proyek dari Order Items
                $projectTotals = [];
                $totalItemPrice = 0;

                if ($order->items) {
                    foreach ($order->items as $item) {
                        // Cari proyek dari relasi variant -> productAccurate -> proyek
                        $proyek = 'UMUM'; // Default
                        if ($item->variant) {
                            if ($item->variant instanceof \App\Models\ProductAccurate) {
                                $proyek = trim(strtoupper($item->variant->proyek ?? 'UMUM'));
                            } elseif (method_exists($item->variant, 'accurateData') && $item->variant->accurateData) {
                                $proyek = trim(strtoupper($item->variant->accurateData->proyek ?? 'UMUM'));
                            } elseif (isset($item->variant->product_id)) {
                                // Fallback: cari di product_accurates berdasarkan product_id jika relasi tidak ada
                                $pa = \App\Models\ProductAccurate::where('product_id', $item->variant->product_id)->first();
                                if ($pa) {
                                    $proyek = trim(strtoupper($pa->proyek ?? 'UMUM'));
                                }
                            }
                        }

                        if (empty($proyek)) $proyek = 'UMUM';

                        $qty = (int)($item->qty ?? $item->quantity ?? 1);
                        $unitPrice = (float)($item->price_at_checkout ?? $item->price ?? 0);
                        $gross = (float)($item->subtotal ?? ($unitPrice * $qty));
                        $discount = (float)($item->discount_amount ?? 0) + (float)($item->promo_discount_amount ?? 0);
                        $net = max(0, $gross - $discount);
                        $subtotal = $net > 0 ? $net : $gross;

                        if (!isset($projectTotals[$proyek])) {
                            $projectTotals[$proyek] = 0;
                        }
                        $projectTotals[$proyek] += $subtotal;
                        $totalItemPrice += $subtotal;
                    }
                }

                // Denominator acuan proporsi = total keseluruhan order item
                $denominator = $totalItemPrice;

                // Jika subtotal seluruh barang adalah 0, kita tidak punya angka untuk acuan proporsi.
                // Maka, kita bagi rata persentasenya ke semua proyek yang ada di pesanan tersebut.
                if ($totalItemPrice <= 0) {
                    $projectCount = count($projectTotals);
                    if ($projectCount > 0) {
                        foreach ($projectTotals as $k => $v) {
                            $projectTotals[$k] = 1; // Bobot sama rata (1)
                        }
                        $denominator = $projectCount; // Paksa pembagi agar total proporsinya 100%
                    } else {
                        $projectTotals = ['UMUM' => 1];
                        $denominator = 1;
                    }
                }

                if ($order->payments && $order->payments->count() > 0) {
                    foreach ($order->payments as $payment) {
                        $paymentDate = $payment->paid_at ?? $payment->created_at;
                        $createdAt = $paymentDate ? $paymentDate->format('Y-m-d') : null;
                        $bankName = $payment->paymentMethod->bank_name ?? null;
                        $paymentType = $this->getPaymentType($payment);
                        $pmName = $payment->paymentMethod->name ?? null;
                        $pmrName = $payment->paymentMethodRate->name ?? null;
                        $mdrPct = $payment->paymentMethodRate->mdr_percentage ?? 0;
                        $amount = (float)($payment->amount ?? 0);
                        $jamBayar = $payment ? $payment->created_at->format('H:i:s') : null;
                        $mdr = round(($amount * $mdrPct) / 100);

                        // 2. Kalkulasi nominal pembayaran per proyek
                        $projectAmounts = [];
                        foreach ($projectTotals as $pName => $pTotal) {
                            $proportion = $pTotal / $denominator;
                            $projectAmounts[$pName] = round($amount * $proportion, 2);
                        }

                        $rows[] = [
                            'created_at' => $createdAt,
                            'nama_kasir' => $order->handledBy->name ?? '-',
                            'jam' => $jamBayar,
                            'nama_toko' => $namaToko,
                            'accurate_invoice_no' => $invoiceNo,
                            'order_number' => $orderNo,
                            'catatan' => $order->notes,
                            'no_kontrak' => $payment->no_kontrak,
                            'tipe_pembayaran' => $paymentType,
                            'bankName' => $bankName,
                            'paymentMethod' => $pmName,
                            'variantMethod' => $pmrName,
                            'amount' => $amount,
                            'mdr' => $mdr,
                            'proyek' => $projectAmounts, // Data dinamis proyek
                        ];
                    }
                } else {
                    $emptyProjectAmounts = [];
                    foreach ($projectTotals as $pName => $pTotal) {
                        $emptyProjectAmounts[$pName] = 0;
                    }
                    
                    $rows[] = [
                        'created_at' => $order->created_at ? $order->created_at->format('Y-m-d') : null,
                        'nama_kasir' => $order->handledBy->name ?? '-',
                        'jam' => $order->created_at ? $order->created_at->format('H:i:s') : '-',
                        'nama_toko' => $namaToko,
                        'accurate_invoice_no' => $invoiceNo,
                        'order_number' => $orderNo,
                        'catatan' => $order->notes,
                        'no_kontrak' => null,
                        'tipe_pembayaran' => null,
                        'bankName' => null,
                        'paymentMethod' => null,
                        'variantMethod' => null,
                        'amount' => null,
                        'mdr' => null,
                        'proyek' => $emptyProjectAmounts, // Menampilkan header proyek meskipun belum ada pembayaran
                    ];
                }
            }
        });

        return $rows;
    }

    public function exportExcelOrderPayments()
    {
        $rows = $this->getPaymentRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data pembayaran untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return;
        }

        $filename = 'laporan_pembayaran_' . $this->startDate . '_sd_' . $this->endDate . '.xlsx';
        return Excel::download(new InvoiceReportExport($rows), $filename);
    }

    public function exportCsvOrderPayments()
    {
        $rows = $this->getPaymentRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data pembayaran untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return;
        }

        $csvFileName = 'laporan_pembayaran_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;

        return response()->streamDownload(function () use ($rows, $separator) {
            $file = fopen('php://output', 'w');

            // Temukan semua proyek unik yang ada di dalam row
            $uniqueProjects = [];
            foreach ($rows as $row) {
                if (!empty($row['proyek'])) {
                    foreach (array_keys($row['proyek']) as $p) {
                        $uniqueProjects[$p] = true;
                    }
                }
            }
            $uniqueProjects = array_keys($uniqueProjects);
            sort($uniqueProjects);

            $headers = [
                'created_at',
                'nama_kasir',
                'jam',
                'nama_toko',
                'accurate_invoice_no',
                'order_number',
                'catatan',
                'no_kontrak',
                'tipe_pembayaran',
                'bankName',
                'paymentMethod',
                'variantMethod',
                'amount',
                'mdr'
            ];

            // Tambahkan header proyek
            foreach ($uniqueProjects as $p) {
                $headers[] = strtoupper($p) . ' (Rp)';
            }

            fputcsv($file, $headers, $separator);

            foreach ($rows as $row) {
                $rowValues = [
                    $row['created_at'],
                    $row['nama_kasir'],
                    $row['jam'],
                    $row['nama_toko'],
                    $row['accurate_invoice_no'],
                    $row['order_number'],
                    $row['catatan'],
                    $row['no_kontrak'],
                    $row['tipe_pembayaran'],
                    $row['bankName'],
                    $row['paymentMethod'],
                    $row['variantMethod'],
                    $row['amount'],
                    $row['mdr'],
                ];

                // Tambahkan nilai proyek secara berurutan sesuai urutan header
                foreach ($uniqueProjects as $p) {
                    $rowValues[] = isset($row['proyek'][$p]) ? round($row['proyek'][$p]) : 0;
                }

                fputcsv($file, $rowValues, $separator);
            }

            fclose($file);
        }, $csvFileName);
    }

    public function getPaymentType($payment)
    {
        if (!$payment || !$payment->paymentMethod) {
            return 'TUNAI';
        }

        $category = strtoupper($payment->paymentMethod->category ?? '');
        if ($category === 'TUNAI') {
            return 'TUNAI';
        }

        $bankName = strtolower($payment->paymentMethod->bank_name ?? '');
        $methodName = strtolower($payment->paymentMethod->name ?? '');

        // Daftar keyword untuk layanan Finance / Paylater
        $financeKeywords = [
            'Kredivo',
            'Home Credit Indonesia',
            'Yessscredit',
            'Kredit Plus',
            'Koperasi',
            'E-digital POS',
            'Shoope Pay',
            'VAST',
            'Samsung Finance Plus',
            'Avanto',
            'Akulaku',
            'Indodana',
            'Spectra',
            'Columbus'
        ];

        foreach ($financeKeywords as $keyword) {
            if (str_contains($bankName, strtolower($keyword)) || str_contains($methodName, strtolower($keyword))) {
                return 'FINANCE';
            }
        }

        // Cek langsung jika bank_name persis "FINANCE" (dari database)
        if ($bankName === 'finance') {
            return 'FINANCE';
        }

        return 'BANK';
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $orders = $this->ordersQuery->paginate(20);
        $availableBranches = \App\Models\Branch::orderBy('name')->pluck('name');

        $totalGross = $this->ordersQuery->sum('orders.total_amount');

        $totalGrandTotal = (clone $this->ordersQuery)->sum('orders.grand_total');

        $totalMdr = \Illuminate\Support\Facades\DB::table('order_payments')
            ->joinSub(clone $this->ordersQuery->select('orders.id'), 'filtered_orders', function ($join) {
                $join->on('order_payments.order_id', '=', 'filtered_orders.id');
            })
            ->leftJoin('payment_method_rates', 'order_payments.payment_method_rate_id', '=', 'payment_method_rates.id')
            ->sum(\Illuminate\Support\Facades\DB::raw('(order_payments.amount * COALESCE(payment_method_rates.mdr_percentage, 0)) / 100'));

        $totalNet = $totalGrandTotal - $totalMdr;

        return view('livewire.zoffline.reporting.invoice-report', [
            'orders' => $orders,
            'availableBranches' => $availableBranches,
            'summary' => [
                'count' => $orders->total(),
                'gross' => $totalGross,
                'net' => $totalNet
            ]
        ]);
    }
}
