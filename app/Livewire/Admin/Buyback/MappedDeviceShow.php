<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\BuybackDevice;
use App\Models\BuybackTier;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Kelola Perangkat Tier'])]
class MappedDeviceShow extends Component
{
    public $tier;

    public function mount($tier_id)
    {
        $this->tier = BuybackTier::findOrFail($tier_id);
    }

    public function deleteMapping($id)
    {
        $device = BuybackDevice::where('id', $id)
            ->where('buyback_tier_id', $this->tier->id)
            ->first();

        if ($device) {
            $device->delete();
            $this->dispatch('toast', title: 'Berhasil', message: 'Mapping perangkat berhasil dihapus.', type: 'success');
        }
    }

    public function render()
    {
        $devices = BuybackDevice::with('productAccurate')
            ->where('buyback_tier_id', $this->tier->id)
            ->latest()
            ->get();

        return view('livewire.admin.buyback.mapped-device-show', compact('devices'));
    }
}
