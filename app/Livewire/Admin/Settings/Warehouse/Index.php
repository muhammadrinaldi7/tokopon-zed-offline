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
            $syihabBranch = $service->getBranchList('syihab');
            $secondBranch = $service->getBranchList('second');
            $syihabUnitId = \App\Models\BusinessUnit::where('code', 'syihab')->value('id');
            $secondUnitId = \App\Models\BusinessUnit::where('code', 'second')->value('id');

            // Map Syihab branches
            foreach ($syihabBranch['d'] as $item) {
                Branch::updateOrCreate(
                    ['name' => $item['name'], 'business_unit_id' => $syihabUnitId],
                    ['branch_id' => $item['id']]
                );
            }

            // Map Second branches
            foreach ($secondBranch['d'] as $item) {
                Branch::updateOrCreate(
                    ['name' => $item['name'], 'business_unit_id' => $secondUnitId],
                    ['branch_id' => $item['id']]
                );
            }

            $this->loadAll();
            $this->dispatch('toast', ["type" => "success", 'title' => 'Berhasil', "message" => "Data Branch berhasil disinkronkan dari kedua database"]);
        } catch (\Exception $e) {
            $this->dispatch('toast', ["type" => "error", 'title' => 'Gagal', "message" => $e->getMessage()]);
        }
    }

    public function synchronizeWarehouse()
    {
        try {
            $service = new AccurateService();
            $syihabWarehouse = $service->getWarehouseList('syihab');
            $secondWarehouse = $service->getWarehouseList('second');
            $syihabUnitId = \App\Models\BusinessUnit::where('code', 'syihab')->value('id');
            $secondUnitId = \App\Models\BusinessUnit::where('code', 'second')->value('id');

            // Map Syihab warehouses
            foreach ($syihabWarehouse['d'] as $item) {
                Warehouse::updateOrCreate(
                    ['name' => $item['name'], 'business_unit_id' => $syihabUnitId],
                    ['warehouse_id' => $item['id']]
                );
            }

            // Map Second warehouses
            foreach ($secondWarehouse['d'] as $item) {
                Warehouse::updateOrCreate(
                    ['name' => $item['name'], 'business_unit_id' => $secondUnitId],
                    ['warehouse_id' => $item['id']]
                );
            }

            $this->loadAll();
            $this->dispatch('toast', ["type" => "success", 'title' => 'Berhasil', "message" => "Data Warehouse berhasil disinkronkan dari kedua database"]);
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
