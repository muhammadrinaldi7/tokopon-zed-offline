<?php

namespace App\Livewire\Admin\Warranty;

use App\Models\Brand;
use App\Models\WarrantyPolicy;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Policy Engine Garansi'])]
class PolicyManagement extends Component
{
    use WithPagination;

    public $search = '';

    public $showModal = false;
    public $isEdit = false;
    public $editId = null;

    public $name;
    public $type = 'store_normal'; // store_normal, store_discount, addon_warranty
    public $coverage_type = 'ganti_unit'; // ganti_unit, full_cover
    public $coverage_scope = []; // array of 'factory_defect', 'human_error'
    public $duration_days = 90;
    public $brand_rule = 'all_brands'; // all_brands, include, exclude
    public $brand_list = [];
    public $addon_product_list = [];
    public $searchProduct = '';
    public $is_active = true;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->isEdit = false;
        $this->editId = null;
        $this->name = '';
        $this->type = 'store_normal';
        $this->coverage_type = 'ganti_unit';
        $this->coverage_scope = ['factory_defect'];
        $this->duration_days = 90;
        $this->brand_rule = 'all_brands';
        $this->brand_list = [];
        $this->addon_product_list = [];
        $this->searchProduct = '';
        $this->is_active = true;
        
        $this->showModal = true;
    }

    public function edit($id)
    {
        $policy = WarrantyPolicy::findOrFail($id);
        $this->editId = $policy->id;
        $this->name = $policy->name;
        $this->type = $policy->type;
        $this->coverage_type = $policy->coverage_type;
        $this->coverage_scope = is_array($policy->coverage_scope) ? $policy->coverage_scope : [];
        $this->duration_days = $policy->duration_days;
        $this->brand_rule = $policy->brand_rule;
        
        $this->brand_list = $policy->brand_list;
        if (is_string($this->brand_list)) {
            $this->brand_list = json_decode($this->brand_list, true) ?? [];
        }

        $this->addon_product_list = $policy->addon_trigger_keywords ?? [];
        $this->searchProduct = '';
        $this->is_active = $policy->is_active;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:store_normal,store_discount,addon_warranty',
            'coverage_type' => 'required|in:ganti_unit,full_cover',
            'coverage_scope' => 'array',
            'duration_days' => 'required|integer|min:1',
            'brand_rule' => 'required|in:all_brands,include,exclude',
        ]);

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'coverage_type' => $this->coverage_type,
            'coverage_scope' => $this->coverage_scope,
            'duration_days' => $this->duration_days,
            'brand_rule' => $this->type !== 'addon_warranty' ? $this->brand_rule : 'all_brands',
            'brand_list' => ($this->type !== 'addon_warranty' && $this->brand_rule !== 'all_brands') ? array_map('intval', $this->brand_list) : [],
            'addon_trigger_keywords' => $this->type === 'addon_warranty' ? array_map('intval', $this->addon_product_list) : null,
            'business_unit_id' => \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId(),
            'is_active' => $this->is_active,
        ];

        if ($this->isEdit) {
            WarrantyPolicy::find($this->editId)->update($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Policy berhasil diupdate.', type: 'success');
        } else {
            WarrantyPolicy::create($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Policy ditambahkan.', type: 'success');
        }

        $this->showModal = false;
    }

    public function toggleActive($id)
    {
        $policy = WarrantyPolicy::findOrFail($id);
        $policy->update(['is_active' => !$policy->is_active]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Status policy diubah.', type: 'success');
    }

    public function delete($id)
    {
        WarrantyPolicy::findOrFail($id)->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Policy dihapus.', type: 'success');
    }

    public function render()
    {
        $activeUnitId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        
        $policies = WarrantyPolicy::where('business_unit_id', $activeUnitId)
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->paginate(15);
            
        $brands = \App\Models\Brand::where('is_active', true)->orderBy('name')->get();

        $searchedProducts = collect();
        if ($this->type === 'addon_warranty' && strlen($this->searchProduct) > 2) {
            $searchedProducts = \App\Models\ProductAccurate::where('name', 'like', '%' . $this->searchProduct . '%')
                ->limit(20)
                ->get();
        }

        return view('livewire.admin.warranty.policy-management', [
            'policies' => $policies,
            'brands' => $brands,
            'searchedProducts' => $searchedProducts,
        ]);
    }
}
