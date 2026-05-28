<?php

namespace App\Livewire\Admin\Qc;

use App\Models\DeviceInspection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Device Passport'])]
class DevicePassport extends Component
{
    public $imei;

    // Untuk comparison view
    public $selectedQc1Id = null;
    public $selectedQc2Id = null;

    public function mount($imei)
    {
        $this->imei = $imei;

        $inspections = $this->inspections;
        if ($inspections->count() >= 2) {
            // By default, compare the last two inspections
            $this->selectedQc1Id = $inspections->last()->id; // Older
            $this->selectedQc2Id = $inspections->first()->id; // Newer
        } elseif ($inspections->count() == 1) {
            $this->selectedQc1Id = $inspections->first()->id;
        }
    }

    #[Computed]
    public function inspections()
    {
        // Descending order, newest first
        return DeviceInspection::with(['inspector', 'variant.secondProduct'])
            ->where('imei', $this->imei)
            ->orderBy('inspected_at', 'desc')
            ->get();
    }

    #[Computed]
    public function qc1()
    {
        return $this->selectedQc1Id ? DeviceInspection::with('media')->find($this->selectedQc1Id) : null;
    }

    #[Computed]
    public function qc2()
    {
        return $this->selectedQc2Id ? DeviceInspection::with('media')->find($this->selectedQc2Id) : null;
    }

    public function render()
    {
        return view('livewire.admin.qc.device-passport');
    }
}
