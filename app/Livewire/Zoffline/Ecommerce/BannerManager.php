<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\EcommerceBanner;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.z')]
class BannerManager extends Component
{
    use WithFileUploads;

    public $banners = [];
    public $businessUnits = [];

    // Form fields
    public bool $showModal = false;
    public ?int $bannerId = null;
    public string $title = '';
    public $imageFile = null;
    public string $existingImageUrl = '';
    public string $target_type = 'none';
    public ?string $target_value = '';
    public ?int $business_unit_id = null;
    public int $sort_order = 0;
    public bool $is_active = true;

    public function mount()
    {
        $this->businessUnits = BusinessUnit::where('is_active', true)->where('is_visible_mobile', true)->get();
        $this->loadData();
    }

    public function loadData()
    {
        $this->banners = EcommerceBanner::with('businessUnit')
            ->orderBy('sort_order', 'asc')
            ->orderByDesc('id')
            ->get();
    }

    public function resetFields()
    {
        $this->bannerId = null;
        $this->title = '';
        $this->imageFile = null;
        $this->existingImageUrl = '';
        $this->target_type = 'none';
        $this->target_value = '';
        $this->business_unit_id = null;
        $this->sort_order = 0;
        $this->is_active = true;
    }

    public function openModal()
    {
        $this->resetFields();
        $this->showModal = true;
    }

    public function edit(int $id)
    {
        $banner = EcommerceBanner::findOrFail($id);
        $this->bannerId = $banner->id;
        $this->title = $banner->title;
        $this->existingImageUrl = $banner->image_url;
        $this->target_type = $banner->target_type;
        $this->target_value = $banner->target_value;
        $this->business_unit_id = $banner->business_unit_id;
        $this->sort_order = $banner->sort_order;
        $this->is_active = $banner->is_active;
        $this->imageFile = null;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'imageFile' => $this->bannerId ? 'nullable|image|max:3072' : 'required|image|max:3072',
            'target_type' => 'required|in:none,category,brand,product,url',
            'sort_order' => 'integer|min:0',
        ]);

        $finalImageUrl = $this->existingImageUrl;

        if ($this->imageFile) {
            $diskName = config('media-library.disk_name', 'r2');
            $fileName = 'banner-' . time() . '-' . uniqid() . '.' . $this->imageFile->getClientOriginalExtension();
            $path = $this->imageFile->storeAs('banners', $fileName, $diskName);
            $finalImageUrl = Storage::disk($diskName)->url($path);
        }

        EcommerceBanner::updateOrCreate(
            ['id' => $this->bannerId],
            [
                'title' => $this->title,
                'image_url' => $finalImageUrl,
                'target_type' => $this->target_type,
                'target_value' => $this->target_value ?: null,
                'business_unit_id' => $this->business_unit_id ?: null,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ]
        );

        $this->showModal = false;
        $this->resetFields();
        $this->loadData();
        $this->dispatch('toast', title: 'Berhasil', message: 'Banner berhasil disimpan.', type: 'success');
    }

    public function toggleActive(int $id)
    {
        $banner = EcommerceBanner::findOrFail($id);
        $banner->update(['is_active' => !$banner->is_active]);
        $this->loadData();
    }

    public function delete(int $id)
    {
        EcommerceBanner::findOrFail($id)->delete();
        $this->loadData();
        $this->dispatch('toast', title: 'Berhasil', message: 'Banner berhasil dihapus.', type: 'success');
    }

    public function render()
    {
        return view('livewire.zoffline.ecommerce.banner-manager');
    }
}
