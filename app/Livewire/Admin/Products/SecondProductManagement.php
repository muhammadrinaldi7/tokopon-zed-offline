<?php

namespace App\Livewire\Admin\Products;

use Livewire\Component;
use App\Models\SecondProduct;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;


class SecondProductManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $showDetailModal = false;
    public $isEditing = false;
    public $productId;
    public $detailProduct = null;

    public $name;
    public $description;
    public $specifications = [];
    public $categoryId;
    public $brandId;

    // Filters
    public $search = '';
    public $filterCategory = '';
    public $filterBrand = '';
    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterBrand' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterBrand() { $this->resetPage(); }

    // Media properties
    public $coverImage;
    public $galleryImages = [];
    public $currentCoverUrl;
    public $currentGallery = [];


    public function addSpecification()
    {
        $this->specifications[] = ['key' => '', 'value' => ''];
    }

    public function removeSpecification($index)
    {
        unset($this->specifications[$index]);
        $this->specifications = array_values($this->specifications);
    }

    public function create()
    {
        $this->resetFields();
        $this->currentCoverUrl = null;
        $this->currentGallery = [];
        $this->showModal = true;
    }


    public function viewDetail($id)
    {
        $this->detailProduct = SecondProduct::with(['category', 'brand'])->find($id);
        $this->showDetailModal = true;
    }

    public function edit($id)
    {
        $this->resetFields();
        $product = SecondProduct::findOrFail($id);
        $this->productId = $id;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->categoryId = $product->category_id;
        $this->brandId = $product->brand_id;

        // Format saved dict to array of key-value pairs for UI
        if (is_array($product->specifications)) {
            foreach ($product->specifications as $key => $value) {
                $this->specifications[] = ['key' => $key, 'value' => $value];
            }
        }

        $this->isEditing = true;

        // Load current media
        $this->currentCoverUrl = $product->getFirstMediaUrl('cover');
        $this->currentGallery = $product->getMedia('gallery')->map(fn($media) => [
            'id' => $media->id,
            'url' => $media->getUrl('thumb'),
        ])->toArray();

        $this->showModal = true;
    }


    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'categoryId' => 'required|exists:categories,id',
            'brandId' => 'nullable|exists:brands,id',
            'specifications.*.key' => 'required|string',
            'specifications.*.value' => 'required|string',
            'coverImage' => 'nullable|image|max:2048', // Max 2MB
            'galleryImages.*' => 'nullable|image|max:2048',
        ]);


        // Transform array back to dictionary for JSON storage
        $specsDict = [];
        foreach ($this->specifications as $spec) {
            if (!empty(trim($spec['key'])) && !empty(trim($spec['value']))) {
                $specsDict[trim($spec['key'])] = trim($spec['value']);
            }
        }

        if ($this->isEditing) {
            $product = SecondProduct::find($this->productId);
            $product->update([
                'name' => $this->name,
                'slug' => \Illuminate\Support\Str::slug($this->name) . '-' . time(),
                'description' => $this->description,
                'category_id' => $this->categoryId,
                'brand_id' => empty($this->brandId) ? null : $this->brandId,
                'specifications' => empty($specsDict) ? null : $specsDict,
            ]);
        } else {
            $product = SecondProduct::create([
                'name' => $this->name,
                'slug' => \Illuminate\Support\Str::slug($this->name) . '-' . time(),
                'description' => $this->description,
                'category_id' => $this->categoryId,
                'brand_id' => empty($this->brandId) ? null : $this->brandId,
                'specifications' => empty($specsDict) ? null : $specsDict,
                'is_active' => true,
                'business_unit_id' => \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId(),
            ]);
        }

        // Handle Media Uploads
        if ($this->coverImage) {
            $product->addMedia($this->coverImage->getRealPath())
                ->usingFileName($this->coverImage->getClientOriginalName())
                ->toMediaCollection('cover');
        }

        if (!empty($this->galleryImages)) {
            foreach ($this->galleryImages as $image) {
                $product->addMedia($image->getRealPath())
                    ->usingFileName($image->getClientOriginalName())
                    ->toMediaCollection('gallery');
            }
        }

        $this->showModal = false;

        $this->resetFields();
        $this->dispatch('toast', title: 'Berhasil', message: 'Produk berhasil disimpan.', type: 'success');
    }

    public function confirmDelete($id)
    {
        $product = SecondProduct::find($id);
        $this->dispatch(
            'show-confirm',
            title: 'Hapus Produk',
            message: 'Apakah Anda yakin ingin menghapus ' . $product->name . '?',
            confirmEvent: 'delete-product',
            confirmParams: [$id],
            type: 'danger',
            confirmText: 'Hapus',
            cancelText: 'Batal',
        );
    }
    #[On('delete-product')]
    public function delete($id)
    {
        $product = SecondProduct::find($id);
        if ($product) {
            // Spatie handles media deletion automatically if configured or manually
            $product->delete();
        }
        $this->dispatch('toast', title: 'Terhapus', message: 'Produk berhasil dihapus permanen.', type: 'info');
    }

    public function removeGalleryImage($mediaId)
    {
        $product = SecondProduct::find($this->productId);
        $product?->deleteMedia($mediaId);

        // Refresh gallery
        $this->currentGallery = $product?->getMedia('gallery')->map(fn($media) => [
            'id' => $media->id,
            'url' => $media->getUrl('thumb'),
        ])->toArray();
    }


    public function resetFields()
    {
        $this->name = '';
        $this->description = '';
        $this->productId = null;
        $this->specifications = [];
        $this->categoryId = null;
        $this->brandId = null;
        $this->coverImage = null;
        $this->galleryImages = [];
        $this->isEditing = false;
    }


    #[Layout('layouts.admin')]
    public function render()
    {
        $query = SecondProduct::with(['category', 'brand']);

        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        $query->where(function ($q) use ($buId) {
            $q->where('business_unit_id', $buId)->orWhereNull('business_unit_id');
        });

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterBrand) {
            $query->where('brand_id', $this->filterBrand);
        }

        $products = $query->orderByDesc('id')->paginate(10);
        $categoriesList = \App\Models\Category::orderBy('name')->get();
        $brandsList = \App\Models\Brand::orderBy('name')->get();

        return view('livewire.admin.products.second-product-management', [
            'products' => $products,
            'categoriesList' => $categoriesList,
            'brandsList' => $brandsList,
        ]);
    }
}
