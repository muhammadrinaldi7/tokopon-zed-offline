<?php

namespace App\Livewire\Zoffline\Pos;

use App\Models\CashierShift;
use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.z', ['title' => 'Tutup Shift Kasir'])]
class ClosingKasir extends Component
{
    public array $denominations = [
        100000 => 0,
        50000  => 0,
        20000  => 0,
        10000  => 0,
        5000   => 0,
        2000   => 0,
        1000   => 0,
        500    => 0,
        200    => 0,
        100    => 0,
    ];
    public $closingNotes = '';

    #[Computed]
    public function getTotalCashProperty(): int
    {
        $total = 0;
        foreach ($this->denominations as $denom => $qty) {
            $total += $denom * max(0, (int) $qty);
        }
        return (int) $total;
    }

    public $showStatusModal = false;
    public $statusMessage = '';
    public $statusType = 'success';

    public function closeShift()
    {
        $this->validate([
            'denominations.*' => 'numeric|min:0',
        ]);

        $buId = Auth::user()->getActiveBusinessUnitId();
        $shift = CashierShift::where('business_unit_id', $buId)
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            $this->dispatch('toast', title: 'Error', message: 'Anda tidak memiliki shift yang sedang aktif.', type: 'error');
            return;
        }

        $summary = $this->shiftSummary;

        $expectedCash = $summary['expected_balance'];
        $actualCashFloat = (float) $this->totalCash;
        $cashDifference = $actualCashFloat - $expectedCash;

        if (abs($cashDifference) < 0.01) {
            $reconciliationStatus = 'balanced';
        } elseif ($cashDifference > 0) {
            $reconciliationStatus = 'over';
        } else {
            $reconciliationStatus = 'short';
        }

        $shift->update([
            'closed_at'              => now(),
            'expected_cash'          => $expectedCash,
            'actual_cash'            => $actualCashFloat,
            'cash_difference'        => $cashDifference,
            'total_cash_sales'       => $summary['total_cash'],
            'total_non_cash_sales'   => $summary['total_non_cash'],
            'total_transactions'     => $summary['total_transactions'],
            'status'                 => 'closed',
            'closing_notes'          => $this->closingNotes,
            'reconciliation_status'  => $reconciliationStatus,
        ]);

        foreach ($this->denominations as $denom => $qty) {
            if ($qty > 0) {
                $shift->denominations()->create([
                    'type'         => 'closing',
                    'denomination' => $denom,
                    'quantity'     => $qty,
                    'subtotal'     => $denom * $qty,
                ]);
            }
        }

        $message = 'Shift berhasil ditutup (Balance)';
        $type = 'success';

        if ($actualCashFloat < $expectedCash) {
            $diff = $expectedCash - $actualCashFloat;
            $message = 'Shift ditutup dengan selisih kurang Rp ' . number_format($diff, 0, ',', '.');
            $type = 'warning';
        }

        $this->statusMessage = $message;
        $this->statusType = $type;
        $this->showStatusModal = true;

        unset($this->shiftSummary);
    }

    #[Computed]
    public function shiftSummary()
    {
        $buId = Auth::user()->getActiveBusinessUnitId();
        $shift = CashierShift::where('business_unit_id', $buId)
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        if (!$shift) return null;

        $orders = Order::where('business_unit_id', $buId)
            ->where('handled_by', Auth::id())
            ->where('order_channel', 'POS')
            ->where('order_status', 'COMPLETED')
            ->where('created_at', '>=', $shift->opened_at)
            ->with('payments.paymentMethod')
            ->get();

        $cashTotal    = 0;
        $nonCashTotal = 0;
        $breakdown    = [];

        foreach ($orders as $order) {
            foreach ($order->payments as $payment) {
                if ($payment->status !== 'PAID') continue;
                $methodName = $payment->paymentMethod->name ?? 'Unknown';
                $category   = $payment->paymentMethod->category ?? 'TUNAI';
                $amount     = (float) $payment->amount;

                if ($category === 'TUNAI') {
                    $cashTotal += $amount;
                } else {
                    $nonCashTotal += $amount;
                    $breakdown[$methodName] = ($breakdown[$methodName] ?? 0) + $amount;
                }
            }
        }

        $expectedBalance = (float) $shift->starting_cash + $cashTotal;

        return [
            'shift'              => $shift,
            'orders'             => $orders,
            'total_transactions' => $orders->count(),
            'total_cash'         => $cashTotal,
            'total_non_cash'     => $nonCashTotal,
            'total_sales'        => $cashTotal + $nonCashTotal,
            'expected_balance'   => $expectedBalance,
            'breakdown'          => $breakdown,
        ];
    }

    public function render()
    {
        return view('livewire.zoffline.pos.closing-kasir');
    }
}
