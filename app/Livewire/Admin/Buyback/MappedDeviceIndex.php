<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\BuybackTier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BuybackDeviceExport;
use App\Exports\BuybackDeviceTemplateExport;
use App\Imports\BuybackDeviceImport;

#[Layout('layouts.admin', ['title' => 'Perangkat Ter-mapping'])]
class MappedDeviceIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    
    // Excel Import/Export
    public $showImportModal = false;
    public $excelFile;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function exportExcel()
    {
        return Excel::download(new BuybackDeviceExport, 'buyback_devices_' . date('Ymd_His') . '.xlsx');
    }

    public function exportTemplate()
    {
        return Excel::download(new BuybackDeviceTemplateExport, 'template_buyback_devices_' . date('Ymd_His') . '.xlsx');
    }

    public function importExcel()
    {
        $this->validate([
            'excelFile' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        try {
            Excel::import(new BuybackDeviceImport, $this->excelFile);

            $this->showImportModal = false;
            $this->excelFile = null;

            $this->dispatch('toast', title: 'Import Berhasil', message: 'Data mapping perangkat berhasil diimport.', type: 'success');
        } catch (\Exception $e) {
            $this->addError('excelFile', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = BuybackTier::withCount('devices');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $tiers = $query->orderBy('name')->paginate(15);

        return view('livewire.admin.buyback.mapped-device-index', compact('tiers'));
    }
}
