<?php

namespace App\Livewire\Admin\Products;

use Livewire\Component;
use App\Models\Brand;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class BrandManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $isEditing = false;
    public $brandId;

    public $name;
    public $logo;
    public $currentLogoUrl;

    protected function rules()
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'logo' => $this->isEditing ? 'nullable|image|max:2048' : 'nullable|image|max:2048',
        ];
    }

    public function create()
    {
        $this->resetFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetFields();
        $brand = Brand::findOrFail($id);
        $this->brandId = $id;
        $this->name = $brand->name;
        $this->currentLogoUrl = $brand->getFirstMediaUrl('logo');
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function store()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
        ];

        if (!$this->isEditing) {
            $data['business_unit_id'] = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        }

        if ($this->isEditing) {
            $brand = Brand::find($this->brandId);
            $brand->update($data);
        } else {
            $brand = Brand::create($data);
        }

        // Upload logo via Spatie Media Library
        if ($this->logo) {
            $brand->addMedia($this->logo->getRealPath())
                ->usingFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo');
        }

        $this->showModal = false;
        $this->resetFields();

        $this->dispatch(
            'toast',
            title: 'Berhasil',
            message: 'Data merek berhasil disimpan.',
            type: 'success'
        );
    }

    public function delete($id)
    {
        $brand = Brand::find($id);

        if ($brand && $brand->products()->count() > 0) {
            $this->dispatch('toast', title: 'Gagal', message: 'Merek ini masih digunakan oleh produk.', type: 'warning');
            return;
        }

        $brand?->delete();
        $this->dispatch('toast', title: 'Terhapus', message: 'Merek berhasil dihapus.', type: 'info');
    }

    public function syncFromAccurate()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        $accurateBrands = \App\Models\ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->select('brandName')
            ->distinct()
            ->pluck('brandName');

        $newCount = 0;
        foreach ($accurateBrands as $brandName) {
            $exists = Brand::where('business_unit_id', $buId)
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($brandName))])
                ->exists();

            if (!$exists) {
                Brand::create([
                    'name' => trim($brandName),
                    'business_unit_id' => $buId
                ]);
                $newCount++;
            }
        }

        $this->dispatch('toast', title: 'Sinkronisasi Selesai', message: "Berhasil menambahkan $newCount merek baru dari Accurate.", type: 'success');
    }

    public function resetFields()
    {
        $this->name = '';
        $this->logo = null;
        $this->currentLogoUrl = null;
        $this->brandId = null;
        $this->isEditing = false;
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        $brands = Brand::where('business_unit_id', $buId)
            ->withCount('products')
            ->orderBy('name')
            ->paginate(10);
        return view('livewire.admin.products.brand-management', [
            'brands' => $brands
        ]);
    }
}
