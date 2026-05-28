<?php

namespace App\Livewire\Pages;

use App\Models\DeviceInspection;
use Livewire\Component;

class PublicDeviceQc extends Component
{
    public $imei;
    public $inspection;

    public function mount($imei)
    {
        $this->imei = $imei;
        
        // Cari QC record terakhir untuk IMEI ini
        $this->inspection = DeviceInspection::with(['variant.secondProduct', 'inspector', 'media'])
            ->where('imei', $this->imei)
            ->orderBy('inspected_at', 'desc')
            ->first();
            
        if (!$this->inspection) {
            abort(404, 'Data QC tidak ditemukan untuk perangkat ini.');
        }
    }

    public function render()
    {
        return view('livewire.pages.public-device-qc')->layout('layouts.app');
    }
}
