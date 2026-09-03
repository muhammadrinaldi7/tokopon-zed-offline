<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class MonitoringKasir extends Component
{
    public $showModal = false;
    public $selectedKasirId = null;
    public $kasirOrders = [];
    public $formSettle = [];

    public function openModalDetail($kasirId)
    {
        $this->selectedKasirId = $kasirId;
        $this->loadKasirOrders();
        $this->showModal = true;
    }

    public function closeModalDetail()
    {
        $this->showModal = false;
        $this->selectedKasirId = null;
        $this->kasirOrders = [];
        $this->formSettle = [];
    }

    public function loadKasirOrders()
    {
        if (!$this->selectedKasirId) return;

        $query = Order::with(['payments.paymentMethod', 'cashSettlement.monitoringBy'])
            ->whereHas('payments.paymentMethod', function ($q) {
                $q->where('name', 'like', '%TUNAI%')
                  ->orWhere('name', 'like', '%CASH%')
                  ->orWhere('category', 'like', '%CASH%');
            })
            ->where('business_unit_id', 2)
            ->where('handled_by', $this->selectedKasirId)
            ->whereDate('order_date', today());

        if (Auth::user()->branch_id) {
            $query->where('branch_id', Auth::user()->branch_id);
        }

        $orders = $query->get();

        $this->kasirOrders = [];
        foreach ($orders as $order) {
            $nominalTunai = $order->payments->filter(function ($payment) {
                $paymentName = strtoupper(optional($payment->paymentMethod)->name);
                $paymentCategory = strtoupper(optional($payment->paymentMethod)->category);
                return str_contains($paymentName, 'TUNAI') || str_contains($paymentName, 'CASH') || str_contains($paymentCategory, 'CASH');
            })->sum('amount');

            $this->kasirOrders[] = [
                'order_id' => $order->id,
                'order_number' => $order->order_number ?? 'Order #' . $order->id,
                'nominal_tunai' => $nominalTunai,
                'settlement' => $order->cashSettlement ? [
                    'nominal_settle' => $order->cashSettlement->nominal_settle,
                    'monitoring_by_name' => optional($order->cashSettlement->monitoringBy)->name,
                    'selisih' => $order->cashSettlement->selisih
                ] : null
            ];

            if (!$order->cashSettlement) {
                $this->formSettle[$order->id] = $nominalTunai;
            }
        }
    }

    public function terimaSettlement($orderId)
    {
        if (Auth::user()->getActiveBusinessUnitId() != 2) {
            abort(403, 'Unauthorized action.');
        }

        $nominalSettle = $this->formSettle[$orderId] ?? 0;
        // Konversi jika kosong/string menjadi float, hilangkan titik atau koma
        $nominalSettle = (float) str_replace(['.', ','], '', $nominalSettle);

        $order = Order::with('payments.paymentMethod')->find($orderId);
        if (!$order) return;

        $nominalTunai = $order->payments->filter(function ($payment) {
            $paymentName = strtoupper(optional($payment->paymentMethod)->name);
            $paymentCategory = strtoupper(optional($payment->paymentMethod)->category);
            return str_contains($paymentName, 'TUNAI') || str_contains($paymentName, 'CASH') || str_contains($paymentCategory, 'CASH');
        })->sum('amount');

        \App\Models\OrderCashSettlement::updateOrCreate(
            ['order_id' => $orderId],
            [
                'handled_by' => $order->handled_by,
                'nominal_tunai' => $nominalTunai,
                'nominal_settle' => $nominalSettle,
                'selisih' => $nominalSettle - $nominalTunai,
                'monitoring_by' => Auth::id(),
                'status' => 'settled'
            ]
        );

        $this->loadKasirOrders();
    }

    public function render()
    {
        if (Auth::user()->getActiveBusinessUnitId() != 2) {
            abort(403, 'Unauthorized action.');
        }

        $branchName = Auth::user()->branch->name ?? 'Semua Cabang';

        $query = Order::with(['handledBy', 'payments.paymentMethod', 'cashSettlement'])
            ->whereHas('payments.paymentMethod', function ($q) {
                $q->where('name', 'like', '%TUNAI%')
                  ->orWhere('name', 'like', '%CASH%')
                  ->orWhere('category', 'like', '%CASH%');
            })
            ->where('business_unit_id', 2)
            ->whereDate('created_at', today());

        if (Auth::user()->branch_id) {
            $query->where('branch_id', Auth::user()->branch_id);
        }

        $orders = $query->get();

        $monitoringData = $orders->groupBy('handled_by')->map(function ($group) {
            $nominalTunai = $group->flatMap->payments->filter(function ($payment) {
                $paymentName = strtoupper(optional($payment->paymentMethod)->name);
                $paymentCategory = strtoupper(optional($payment->paymentMethod)->category);
                return str_contains($paymentName, 'TUNAI') || str_contains($paymentName, 'CASH') || str_contains($paymentCategory, 'CASH');
            })->sum('amount');

            $hasSettlement = false;
            $nominalSettle = $group->sum(function ($order) use (&$hasSettlement) {
                if ($order->cashSettlement) {
                    $hasSettlement = true;
                    return $order->cashSettlement->nominal_settle;
                }
                return 0;
            });
            $selisih = $group->sum(function ($order) {
                return $order->cashSettlement ? $order->cashSettlement->selisih : 0;
            });

            return [
                'kasir_id' => $group->first()->handled_by,
                'nama' => $group->first()->handledBy->name ?? 'Unknown Kasir',
                'jumlah_struk' => $group->count(),
                'nominal_tunai' => $nominalTunai,
                'nominal_settle' => $hasSettlement ? $nominalSettle : null,
                'selisih' => $hasSettlement ? $selisih : null,
            ];
        })->values()->toArray();

        return view('livewire.zoffline.reporting.monitoring-kasir', [
            'monitoringData' => $monitoringData,
            'branchName' => $branchName
        ]);
    }
}
