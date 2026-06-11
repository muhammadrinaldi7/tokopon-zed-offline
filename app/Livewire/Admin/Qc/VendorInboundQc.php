<?php

namespace App\Livewire\Admin\Qc;

use App\Models\ProductSerialNumber;
use App\Models\SecondProductVariant;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class VendorInboundQc extends Component
{
    use WithPagination;

    public $search = '';

    // Data for modal
    public $selectedSnId = null;
    public $selectedImei = '';
    public $showQcModal = false;

    protected $listeners = ['qc-inspection-saved' => 'onInspectionSaved'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openQcModal($snId, $imei)
    {
        $this->selectedSnId = $snId;
        $this->selectedImei = $imei;
        $this->showQcModal = true;
    }

    public function onInspectionSaved($verdict)
    {
        $this->showQcModal = false;
        $this->selectedSnId = null;
        $this->selectedImei = '';
        $this->resetPage();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        // Ambil semua sku dari SecondProductVariant
        $secondSkus = SecondProductVariant::pluck('sku')->toArray();

        $query = ProductSerialNumber::whereIn('item_no', $secondSkus)
            ->where('qc_status', 'Pending Inbound')
            ->where('status', '!=', 'Sold');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('serial_number', 'like', '%' . $this->search . '%')
                  ->orWhere('item_no', 'like', '%' . $this->search . '%');
            });
        }

        $items = $query->latest()->paginate(20);

        return view('livewire.admin.qc.vendor-inbound-qc', [
            'items' => $items
        ]);
    }
}
