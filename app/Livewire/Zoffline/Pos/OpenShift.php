<?php

namespace App\Livewire\Zoffline\Pos;

use App\Models\CashierShift;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.z', ['title' => 'Buka Shift Kasir'])]
class OpenShift extends Component
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
    public $openingNotes = '';
    public $hasActiveShift = false;

    #[Computed]
    public function getTotalCashProperty(): int
    {
        $total = 0;
        foreach ($this->denominations as $denom => $qty) {
            $total += $denom * max(0, (int) $qty);
        }
        return (int) $total;
    }

    public function mount()
    {
        $buId = Auth::user()->getActiveBusinessUnitId();
        $existingShift = CashierShift::where('business_unit_id', $buId)
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();
            
        if ($existingShift) {
            $this->hasActiveShift = true;
        }
    }

    public function openShift()
    {
        $this->validate([
            'denominations.*' => 'numeric|min:0',
        ]);

        $buId = Auth::user()->getActiveBusinessUnitId();

        // Cek apakah sudah ada shift yang open (soft protection)
        $existingShift = CashierShift::where('business_unit_id', $buId)
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        if ($existingShift) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Anda masih memiliki shift yang belum ditutup.', type: 'warning');
            return redirect()->route('zoffline.pos');
        }

        // Cek apakah hari ini sudah pernah tutup shift (Opsi A: 1 kasir = 1 shift per hari)
        $shiftToday = CashierShift::where('business_unit_id', $buId)
            ->where('user_id', Auth::id())
            ->whereDate('shift_date', now()->toDateString())
            ->first();

        if ($shiftToday) {
             $this->dispatch('toast', title: 'Perhatian', message: 'Anda sudah pernah membuka shift hari ini. Sistem hanya mengizinkan 1 shift per hari untuk satu kasir.', type: 'error');
             return;
        }

        $shift = CashierShift::create([
            'business_unit_id' => $buId,
            'user_id'          => Auth::id(),
            'branch_id'        => Auth::user()->branch_id,
            'shift_date'       => now()->toDateString(),
            'opened_at'        => now(),
            'starting_cash'    => $this->totalCash,
            'status'           => 'open',
            'opening_notes'    => $this->openingNotes,
        ]);

        foreach ($this->denominations as $denom => $qty) {
            if ($qty > 0) {
                $shift->denominations()->create([
                    'type'         => 'opening',
                    'denomination' => $denom,
                    'quantity'     => $qty,
                    'subtotal'     => $denom * $qty,
                ]);
            }
        }

        $this->dispatch('toast', title: 'Shift Berhasil Dibuka', message: 'Selamat bertugas!', type: 'success');
        return redirect()->route('zoffline.pos');
    }

    #[Computed]
    public function recentShifts()
    {
        $buId = Auth::user()->getActiveBusinessUnitId();
        return CashierShift::where('business_unit_id', $buId)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.zoffline.pos.open-shift');
    }
}
