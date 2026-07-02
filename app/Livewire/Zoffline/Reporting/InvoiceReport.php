<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

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

        return Order::with(['user', 'accurateDocs', 'salesBy', 'paymentMethod', 'items.variant.product', 'promos'])
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

    public function exportCsvOrderPayments()
    {
        // Eager load relasi yang dibutuhkan untuk menyimulasikan LEFT JOIN di SQL
        $orders = $this->ordersQuery->with(['payments.paymentMethod', 'payments.paymentMethodRate'])->get();
        $csvFileName = 'laporan_pembayaran_' . $this->startDate . '_sd_' . $this->endDate . '.csv';
        $separator = $this->csvSeparator;

        return response()->streamDownload(function () use ($orders, $separator) {
            $file = fopen('php://output', 'w');

            // 1. Susun Header sesuai dengan kolom SELECT pada SQL
            $headers = [
                'created_at',
                'nama_toko',
                'accurate_invoice_no',
                'order_number',
                'catatan',
                'no_kontrak',
                'bankName',
                'paymentMethod',
                'variantMethod',
                'amount',
                'mdr'
            ];

            fputcsv($file, $headers, $separator);

            // 2. Loop data order dan payment
            foreach ($orders as $order) {
                // Sama dengan JSON_VALUE(o.shipping_address_snapshot,'$.store')
                $namaToko = $order->shipping_address_snapshot['store'] ?? null;
                $createdAt = $order->created_at->format('Y-m-d H:i:s');
                $invoiceNo = $order->accurate_invoice_no ?? null;
                $orderNo = $order->order_number;

                // Simulasi: LEFT JOIN order_payments op ON o.id = op.order_id
                if ($order->payments && $order->payments->count() > 0) {
                    foreach ($order->payments as $payment) {

                        // Ekstrak data dari relasi (LEFT JOIN payment_methods & payment_method_rates)
                        $bankName = $payment->paymentMethod->bank_name ?? null;
                        $pmName = $payment->paymentMethod->name ?? null;
                        $pmrName = $payment->paymentMethodRate->name ?? null;
                        $mdrPct = $payment->paymentMethodRate->mdr_percentage ?? 0;
                        $amount = $payment->amount ?? 0;

                        // round(op.amount * pmr.mdr_percentage /100) as mdr
                        $mdr = round(($amount * $mdrPct) / 100);

                        fputcsv($file, [
                            $createdAt,
                            $namaToko,
                            $invoiceNo,
                            $orderNo,
                            $order->notes,
                            $payment->no_kontrak,
                            $bankName,
                            $pmName,
                            $pmrName,
                            $amount,
                            $mdr
                        ], $separator);
                    }
                } else {
                    // Sifat LEFT JOIN: Jika order tidak memiliki payment, baris tetap dirender dengan value payment kosong (null)
                    fputcsv($file, [
                        $createdAt,
                        $namaToko,
                        $invoiceNo,
                        $orderNo,
                        null, // no_kontrak
                        null, // paymentMethod
                        null, // variantMethod
                        null, // amount
                        null  // mdr
                    ], $separator);
                }
            }

            fclose($file);
        }, $csvFileName);
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
        // dd($this->ordersQuery);
        return view('livewire.zoffline.reporting.invoice-report', [
            'orders' => $orders,
            // 'payment' => $orders->accurateDocs->where('doc_type', 'SALES_RECEIPT')->get(),
            'availableBranches' => $availableBranches,
            'summary' => [
                'count' => $orders->total(),
                'gross' => $totalGross,
                'net' => $totalNet
            ]
        ]);
    }
}
