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

            // Map Syihab branches
            foreach ($syihabBranch['d'] as $item) {
                Branch::updateOrCreate(
                    ['name' => $item['name']],
                    ['branch_id' => $item['id']]
                );
            }

            // Map Second branches (Strip "GSK " prefix)
            foreach ($secondBranch['d'] as $item) {
                $localName = str_replace('GSK ', '', $item['name']);
                Branch::updateOrCreate(
                    ['name' => $localName],
                    ['second_branch_id' => $item['id']]
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

            // Map Syihab warehouses
            foreach ($syihabWarehouse['d'] as $item) {
                Warehouse::updateOrCreate(
                    ['name' => $item['name']],
                    ['warehouse_id' => $item['id']]
                );
            }

            // Map Second warehouses (Strip "GSK " prefix)
            foreach ($secondWarehouse['d'] as $item) {
                $localName = str_replace('GSK ', '', $item['name']);
                Warehouse::updateOrCreate(
                    ['name' => $localName],
                    ['second_warehouse_id' => $item['id']]
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
