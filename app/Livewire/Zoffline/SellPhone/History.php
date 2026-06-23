<?php

namespace App\Livewire\Zoffline\SellPhone;

use App\Models\SellPhone;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;


#[Layout('layouts.z', ['title' => 'History Sell Phone'])]
class History extends Component
{
    public function render()
    {
        $branch = Auth::user()->branch->id;
        if (Auth::user()) {
            // Tampilkan history yang kolom 'handled_by'-nya adalah ID FL yang sedang login
            $sells = SellPhone::with(['media', 'handledBy', 'branch'])
                ->where('branch_id', Auth::user()->branch_id)
                ->latest()
                ->get();
        }
        return view('livewire.zoffline.sell-phone.history', compact('sells'));
    }
}
