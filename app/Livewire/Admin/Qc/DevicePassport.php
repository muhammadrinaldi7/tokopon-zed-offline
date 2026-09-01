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

    public $selectedQc1Id = null;
    public $selectedQc2Id = null;

    public function mount($imei)
    {
        $this->imei = $imei;

        $inspections = $this->inspections;
        if ($inspections->count() >= 2) {
            // Default: Compare the last two inspections
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

        // Update comparison to include the new one
        if ($this->inspections->count() >= 2) {
            $this->selectedQc2Id = $this->inspections->first()->id;
        } elseif ($this->inspections->count() == 1) {
            $this->selectedQc1Id = $this->inspections->first()->id;
        }
    }

    #[Computed]
    public function productName()
    {
        // Prioritaskan dari ProductSerialNumber yang sudah mengarah ke ProductAccurate
        $sn = \App\Models\ProductSerialNumber::with('productAccurate')->where('serial_number', $this->imei)->first();
        if ($sn && $sn->product_accurate_id) {
            return $sn->productAccurate->name ?? 'Unknown Product';
        }

        // Fallback: Jika IMEI ada di SellPhone
        $sellPhone = \App\Models\SellPhone::with('productAccurate')->where('imei', $this->imei)->first();
        if ($sellPhone && $sellPhone->productAccurate) {
            return $sellPhone->productAccurate->name ?? 'Unknown Product';
        }

        // Fallback: Jika ada inspeksi yang menyimpan second_product_variant_id lama
        $first = $this->inspections->first();
        if ($first && $first->variant && $first->variant->accurateData) {
            return $first->variant->accurateData->name ?? 'Unknown Product';
        }

        return 'Unknown Product';
    }

    #[Computed]
    public function inspections()
    {
        // Descending order, newest first
        return DeviceInspection::with(['inspector', 'variant.accurateData'])
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
