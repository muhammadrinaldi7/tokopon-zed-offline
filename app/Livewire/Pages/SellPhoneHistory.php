<?php

namespace App\Livewire\Pages;

use App\Models\SellPhone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SellPhoneHistory extends Component
{
    #[Layout('layouts.app', ['title' => 'Riwayat Jual HP'])]
    public function render()
    {
        // Cukup gunakan Auth::user()->hasRole() tanpa perlu findOrFail lagi
        if (Auth::user()->hasRole('fl')) {
            // Tampilkan history yang kolom 'handled_by'-nya adalah ID FL yang sedang login
            $sells = \App\Models\SellPhone::with(['media', 'handledBy'])
                ->where('handled_by', Auth::id())
                ->latest()
                ->get();
        } else {
            // Tampilkan history milik customer biasa berdasarkan 'user_id'
            $sells = \App\Models\SellPhone::with('media')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();
        }
        return view('livewire.pages.sell-phone-history', compact('sells'));
    }
}
