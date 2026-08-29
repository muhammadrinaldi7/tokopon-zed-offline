<?php

namespace App\Livewire\Admin\Qc;

use App\Models\DeviceInspection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

#[Layout('layouts.z', ['title' => 'Device Passport'])]
class DevicePassport extends Component
{
    public $imei;

    public $selectedQcId = null;
    public $showDetailModal = false;

    public function mount($imei)
    {
        $this->imei = $imei;
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

    public function viewQcDetail($id)
    {
        $this->selectedQcId = $id;
        $this->showDetailModal = true;
    }

    public function closeQcDetail()
    {
        $this->showDetailModal = false;
        $this->selectedQcId = null;
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
    public function selectedQc()
    {
        return $this->selectedQcId ? DeviceInspection::with('media')->find($this->selectedQcId) : null;
    }

    public function render()
    {
        return view('livewire.admin.qc.device-passport');
    }
}
