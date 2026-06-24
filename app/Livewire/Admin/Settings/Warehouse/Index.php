<?php

namespace App\Livewire\Admin\Settings\Warehouse;

use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\AccurateService;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;
    public $search = '';
    public $warehouse = [];
    public $branch = [];
    public $businessUnits = [];
    public $activeTab = null;
    public function getWarehouse()
    {
        try {
            $warehouse = (new AccurateService())->getWarehouseList();
            return $warehouse;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return [];
        }
    }

    public function synchronizeBranch()
    {
        try {
            $service = new AccurateService();
            $businessUnits = \App\Models\BusinessUnit::where('is_active', true)->get();

            foreach ($businessUnits as $bu) {
                $branchResponse = $service->getBranchList($bu->code);

                if (isset($branchResponse['d']) && is_array($branchResponse['d'])) {
                    foreach ($branchResponse['d'] as $item) {
                        Branch::updateOrCreate(
                            ['name' => $item['name'], 'business_unit_id' => $bu->id],
                            ['branch_id' => $item['id']]
                        );
                    }
                }
            }

            $this->loadAll();
            $this->dispatch('toast', ["type" => "success", 'title' => 'Berhasil', "message" => "Data Branch berhasil disinkronkan dari seluruh database"]);
        } catch (\Exception $e) {
            $this->dispatch('toast', ["type" => "error", 'title' => 'Gagal', "message" => $e->getMessage()]);
        }
    }

    public function synchronizeWarehouse()
    {
        try {
            $service = new AccurateService();
            $businessUnits = \App\Models\BusinessUnit::where('is_active', true)->get();

            foreach ($businessUnits as $bu) {
                $warehouseResponse = $service->getWarehouseList($bu->code);

                if (isset($warehouseResponse['d']) && is_array($warehouseResponse['d'])) {
                    foreach ($warehouseResponse['d'] as $item) {
                        Warehouse::updateOrCreate(
                            ['name' => $item['name'], 'business_unit_id' => $bu->id],
                            ['warehouse_id' => $item['id']]
                        );
                    }
                }
            }

            $this->loadAll();
            $this->dispatch('toast', ["type" => "success", 'title' => 'Berhasil', "message" => "Data Warehouse berhasil disinkronkan dari seluruh database"]);
        } catch (\Exception $e) {
            $this->dispatch('toast', ["type" => "error", 'title' => 'Gagal', "message" => $e->getMessage()]);
        }
    }

    public function loadAll()
    {
        $this->warehouse = Warehouse::all();
        $this->branch = Branch::all();
        $this->businessUnits = \App\Models\BusinessUnit::all();
        if (!$this->activeTab && count($this->businessUnits) > 0) {
            $this->activeTab = $this->businessUnits[0]->id;
        }
    }
    public function mount()
    {
        $this->loadAll();
    }
    public function render()
    {
        // dd($this->warehouse);
        return view('livewire.admin.settings.warehouse.index');
    }
}
