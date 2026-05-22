<?php

namespace App\Livewire\Pages;

use App\Models\TradeIn;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class TradeInHistory extends Component
{
    #[Layout('layouts.app', ['title' => 'Riwayat Tukar Tambah'])]
    public function render()
    {
        if (Auth::user()->hasRole('fl')) {
            // Tampilkan history yang kolom 'handled_by'-nya adalah ID FL yang sedang login
            $tradeIns = TradeIn::with(['media', 'handledBy'])
                ->where('handled_by', Auth::id())
                ->latest()
                ->get();
        } else {
            // Tampilkan history milik customer biasa berdasarkan 'user_id'
            $tradeIns = TradeIn::with('media')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();
        }

        return view('livewire.pages.trade-in-history', compact('tradeIns'));
    }
}
