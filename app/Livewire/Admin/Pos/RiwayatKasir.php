<?php

namespace App\Livewire\Admin\Pos;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.z', ['title' => 'Riwayat Closing Kasir'])]
class RiwayatKasir extends Component
{
    use WithPagination;

    public $dateFilter;
    public $showDetailModal = false;
    public $selectedOrders = [];
    public $detailModalTitle = '';

    public function mount()
    {
        // Default filter ke hari ini
        $this->dateFilter = date('Y-m-d');
    }

    public function showDetail($date, $handledById)
    {
        $userWarehouseName = Auth::user()->warehouse->name ?? null;

        $query = Order::pos()
            ->whereDate('created_at', $date)
            ->where('handled_by', $handledById);

        if ($userWarehouseName) {
            $query->where('shipping_address_snapshot->store', $userWarehouseName);
        }

        $orders = $query->with(['handledBy', 'payments.paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->get();

        $kasirName = $orders->first()->handledBy->name ?? 'Kasir';
        $tanggal = \Carbon\Carbon::parse($date)->translatedFormat('d F Y');

        $this->detailModalTitle = "Rincian Transaksi - {$kasirName} ({$tanggal})";
        $this->selectedOrders = $orders;
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedOrders = [];
    }

    public function render()
    {
        $userWarehouseName = Auth::user()->warehouse->name ?? null;

        $query = Order::pos()
            ->select(
                DB::raw('DATE(created_at) as date'),
                'handled_by',
                DB::raw('COUNT(id) as total_invoice'),
                DB::raw('SUM(grand_total) as grand_total')
            );

        if ($userWarehouseName) {
            $query->where('shipping_address_snapshot->store', $userWarehouseName);
        }

        if ($this->dateFilter) {
            $query->whereDate('created_at', $this->dateFilter);
        }

        $reports = $query->with('handledBy')
            ->groupBy(DB::raw('DATE(created_at)'), 'handled_by')
            ->orderBy(DB::raw('DATE(created_at)'), 'desc')
            ->orderBy('handled_by')
            ->paginate(15);

        // Menghitung rincian pembayaran (Tunai vs Non-Tunai)
        $reports->getCollection()->transform(function ($report) use ($userWarehouseName) {
            $orderQuery = Order::pos()
                ->whereDate('created_at', $report->date)
                ->where('handled_by', $report->handled_by);

            if ($userWarehouseName) {
                $orderQuery->where('shipping_address_snapshot->store', $userWarehouseName);
            }

            $orderIds = $orderQuery->pluck('id');

            $payments = \App\Models\OrderPayment::whereIn('order_id', $orderIds)
                ->with('paymentMethod')
                ->get();

            $totalTunai = 0;
            $totalNonTunai = 0;

            foreach ($payments as $payment) {
                $methodName = strtolower($payment->paymentMethod->name ?? '');
                if (str_contains($methodName, 'tunai') || str_contains($methodName, 'cash') || str_contains($methodName, 'kas')) {
                    $totalTunai += $payment->amount;
                } else {
                    $totalNonTunai += $payment->amount;
                }
            }

            $report->total_tunai = $totalTunai;
            $report->total_non_tunai = $totalNonTunai;

            return $report;
        });

        return view('livewire.admin.pos.riwayat-kasir', [
            'reports' => $reports
        ]);
    }
}
