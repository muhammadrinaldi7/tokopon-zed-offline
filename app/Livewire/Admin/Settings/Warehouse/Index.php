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
            $branch = (new AccurateService())->getBranchList();
            foreach ($branch['d'] as $item) {
                Branch::updateOrCreate([
                    'name' => $item['name'],
                    'branch_id' => $item['id'],
                ]);
            }
            $this->loadAll();
            $this->dispatch('toast', ["type" => "success", 'title' => 'Berhasil', "message" => "Data berhasil disinkronkan"]);
        } catch (\Exception $e) {
            $this->dispatch('toast', ["type" => "error", 'title' => 'Gagal', "message" => $e->getMessage()]);
        }
    }

    public function synchronizeWarehouse()
    {
        try {
            $warehouse = (new AccurateService())->getWarehouseList();
            foreach ($warehouse['d'] as $item) {
                Warehouse::updateOrCreate([
                    'name' => $item['name'],
                    'warehouse_id' => $item['id'],
                ]);
            }
            $this->loadAll();
            $this->dispatch('toast', ["type" => "success", 'title' => 'Berhasil', "message" => "Data berhasil disinkronkan"]);
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
