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

        return Order::with(['user', 'salesBy', 'paymentMethod', 'items.variant.product'])
            ->whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%' . $this->search . '%')
                        ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($qc) {
                            $qc->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('salesBy', function ($qs) {
                            $qs->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->latest();
    }

    public function exportCsv()
    {
        $orders = $this->ordersQuery->get();
        $csvFileName = 'laporan_penjualan_detail_' . $this->startDate . '_sd_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
                'PELANGGAN',
                'TELEPON',
                'SALES',
                'CABANG',
                'METODE BAYAR',
                'NAMA PRODUK',
                'WARNA',
                'STORAGE',
                'SN (SerialNumber)',
                'QTY',
                'HARGA SATUAN (Rp)',
                'SUBTOTAL ITEM (Rp)',
                'GROSS ORDER (Rp)',
                'DISKON ORDER (Rp)',
                'MDR (Rp)',
                'TOTAL TRANSAKSI (Rp)',
                'NET SALES (Rp)'
            ]);

            foreach ($orders as $order) {
                $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

                if ($order->items->count() > 0) {
                    foreach ($order->items as $item) {
                        $variant = $item->variant;
                        $name = $variant->name ?? ($variant->product ? $variant->product->name : 'Unknown Product');

                        fputcsv($file, [
                            $order->created_at->format('Y-m-d H:i'),
                            $order->order_number,
                            $order->accurate_invoice_no ?? '-',
                            $order->user ? $order->user->name : 'Walk-in',
                            $order->user ? $order->user->profile->phone_number : '-',
                            $order->salesBy ? $order->salesBy->name : '-',
                            $branch,
                            $order->paymentMethod ? $order->paymentMethod->name : '-',
                            $name,
                            $variant->color ?? '-',
                            ($variant->ram ? $variant->ram . ' ' : '') . ($variant->storage ? $variant->storage : '') ?? '-',
                            $item->serial_number ?? '-',
                            $item->qty,
                            $item->price_at_checkout,
                            $item->subtotal,
                            $order->total_amount,
                            $order->discount_amount,
                            $order->mdr_amount,
                            $order->grand_total,
                            $order->grand_total - $order->mdr_amount
                        ]);
                    }
                } else {
                    // Fallback jika tidak ada items (sangat jarang terjadi)
                    fputcsv($file, [
                        $order->created_at->format('Y-m-d H:i'),
                        $order->order_number,
                        $order->accurate_invoice_no ?? '-',
                        $order->user ? $order->user->name : 'Walk-in',
                        $order->user ? $order->user->profile->phone_number : '-',
                        $order->salesBy ? $order->salesBy->name : '-',
                        $branch,
                        $order->paymentMethod ? $order->paymentMethod->name : '-',
                        '-',
                        '0',
                        '0',
                        '0',
                        $order->total_amount,
                        $order->discount_amount,
                        $order->mdr_amount,
                        $order->grand_total,
                        $order->grand_total - $order->mdr_amount
                    ]);
                }
            }
            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        $orders = $this->ordersQuery->paginate(20);

        $totalGross = $this->ordersQuery->sum('total_amount');
        $totalNet = $this->ordersQuery->sum('grand_total') - $this->ordersQuery->sum('mdr_amount');

        return view('livewire.admin.reporting.sales-report', [
            'orders' => $orders,
            'summary' => [
                'count' => $orders->total(),
                'gross' => $totalGross,
                'net' => $totalNet
            ]
        ])->layout('layouts.admin');
    }
}
