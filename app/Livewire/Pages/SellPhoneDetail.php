<?php

namespace App\Livewire\Pages;

use App\Models\SellPhone;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SellPhoneDetail extends Component
{
    public SellPhone $sellPhone;
    public string $customerShippingReceipt = '';

    public function mount(SellPhone $sellPhone)
    {
        if ($sellPhone->user_id !== Auth::id() && !Auth::user()->hasRole('fl')) {
            abort(403);
        }
        $this->sellPhone = $sellPhone;
        $this->customerShippingReceipt = $sellPhone->customer_shipping_receipt ?? '';
    }

    public function acceptOffer()
    {
        if ($this->sellPhone->status === 'OFFERED') {
            $this->sellPhone->update(['status' => 'WAITING_FOR_DEVICE']);
            $this->dispatch('show-toast', type: 'success', message: 'Penawaran Diterima! Silakan kirimkan unit HP Anda ke toko kami.');
        } elseif ($this->sellPhone->status === 'REVISED_OFFER') {
            $this->sellPhone->update(['status' => 'PAYING']);
            $this->dispatch('show-toast', type: 'success', message: 'Revisi disetujui! Dana akan segera dicairkan.');
        }
    }

    public function cancel()
    {
        if (!in_array($this->sellPhone->status, ['PENDING', 'OFFERED', 'REVISED_OFFER'])) return;
        $this->sellPhone->update(['status' => 'CANCELLED']);
        $this->dispatch('show-toast', type: 'info', message: 'Pengajuan Jual HP dibatalkan.');
    }

    public function submitReceipt()
    {
        $this->validate(['customerShippingReceipt' => 'required|string|min:5']);
        $this->sellPhone->update([
            'customer_shipping_receipt' => $this->customerShippingReceipt,
            'status' => 'INSPECTING'
        ]);
        $this->dispatch('show-toast', type: 'success', message: 'Resi Disimpan. Kami akan melacak kedatangan paket Anda.');
        return $this->redirect(route('sell-phone-history'));
    }



    public function submitComplete() {}

    #[Layout('layouts.app', ['title' => 'Detail Penjualan HP'])]
    public function render()
    {
        return view('livewire.pages.sell-phone-detail');
    }
}
