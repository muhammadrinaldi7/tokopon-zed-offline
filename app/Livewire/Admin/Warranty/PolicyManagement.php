<?php

namespace App\Livewire\Admin\Warranty;

use App\Models\Brand;
use App\Models\WarrantyPolicy;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Warranty Policies'])]
class PolicyManagement extends Component
{
    use WithPagination;

    public $search = '';

    public $showModal = false;
    public $isEdit = false;
    public $editId = null;

    public $name;
    public $brand_id;
    public $type = 'store_default';
    public $duration_days = 365;
    public $max_claims = 1;
    public $item_category;
    public $is_active = true;

    // Untuk coverage list dinamis
    public $coverageItems = [
        ['name' => 'LCD Rusak', 'covered' => true],
        ['name' => 'Baterai Drop', 'covered' => true],
        ['name' => 'Water Damage', 'covered' => false],
        ['name' => 'Kerusakan Fisik', 'covered' => false],
    ];

    public function addCoverageItem()
    {
        $this->coverageItems[] = ['name' => '', 'covered' => '1'];
    }

    public function removeCoverageItem($index)
    {
        unset($this->coverageItems[$index]);
        $this->coverageItems = array_values($this->coverageItems);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'brand_id', 'type', 'item_category', 'editId']);
        $this->duration_days = 365;
        $this->max_claims = 1;
        $this->is_active = true;
        $this->isEdit = false;
        
        $this->coverageItems = [
            ['name' => 'LCD Rusak', 'covered' => '1'],
            ['name' => 'Baterai Drop', 'covered' => '1'],
            ['name' => 'Water Damage', 'covered' => '0'],
            ['name' => 'Kerusakan Fisik', 'covered' => '0'],
        ];

        $this->showModal = true;
    }

    public function edit($id)
    {
        $policy = WarrantyPolicy::findOrFail($id);
        $this->editId = $policy->id;
        $this->name = $policy->name;
        $this->brand_id = $policy->brand_id;
        $this->type = $policy->type;
        $this->duration_days = $policy->duration_days;
        $this->max_claims = $policy->max_claims;
        $this->item_category = $policy->item_category;
        $this->is_active = $policy->is_active;

        if ($policy->coverage && is_array($policy->coverage)) {
            $this->coverageItems = $policy->coverage;
        } else {
            $this->coverageItems = [];
        }

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:store_default,insurance',
            'duration_days' => 'required|integer|min:1',
            'max_claims' => 'required|integer|min:1',
            'coverageItems.*.name' => 'required|string|max:255',
        ]);

        $data = [
            'name' => $this->name,
            'brand_id' => $this->brand_id ?: null,
            'type' => $this->type,
            'duration_days' => $this->duration_days,
            'max_claims' => $this->max_claims,
            'item_category' => $this->item_category,
            'is_active' => $this->is_active,
            'coverage' => array_values($this->coverageItems),
        ];

        if ($this->isEdit) {
            WarrantyPolicy::find($this->editId)->update($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Kebijakan garansi diupdate.', type: 'success');
        } else {
            WarrantyPolicy::create($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Kebijakan garansi ditambahkan.', type: 'success');
        }

        $this->showModal = false;
    }

    public function toggleActive($id)
    {
        $policy = WarrantyPolicy::findOrFail($id);
        $policy->update(['is_active' => !$policy->is_active]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Status garansi diubah.', type: 'success');
    }

    public function delete($id)
    {
        WarrantyPolicy::findOrFail($id)->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Kebijakan garansi dihapus.', type: 'success');
    }

    public function render()
    {
        $policies = WarrantyPolicy::with('brand')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);
            
        $brands = Brand::orderBy('name', 'asc')->get();

        return view('livewire.admin.warranty.policy-management', compact('policies', 'brands'));
    }
}
