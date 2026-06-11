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

    // Modal state for new QC
    public $showQcModal = false;
    public $newQcLabel = 'QC Etalase';
    public $targetSnId = null;

    protected $listeners = ['qc-inspection-saved' => 'onInspectionSaved'];

    public function openQcModal()
    {
        // Cari ProductSerialNumber berdasarkan IMEI
        $snRecord = \App\Models\ProductSerialNumber::where('serial_number', $this->imei)->first();
        if ($snRecord) {
            $this->targetSnId = $snRecord->id;
            $this->showQcModal = true;
        } else {
            $this->dispatch('toast', title: 'Gagal', message: 'Serial Number tidak ditemukan di database.', type: 'error');
        }
    }

    public function onInspectionSaved($verdict)
    {
        $this->showQcModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Inspeksi baru berhasil ditambahkan.', type: 'success');
        unset($this->inspections); // clear computed cache
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
