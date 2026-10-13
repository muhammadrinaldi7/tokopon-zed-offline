<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class ProductCuration extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public ?int $selectedBusinessUnitId = null;
    public string $selectedCategory = '';
    public string $selectedBrand = '';
    public bool $onlyInStock = true; // Sesuai permintaan: jika stok 0 jangan muncul (default true)
    public string $mediaFilter = 'all'; // all, has_media, no_media
    public $businessUnits = [];
    public $categories = [];
    public $brands = [];

    // Modal Upload Foto Produk
    public bool $showUploadModal = false;
    public ?int $selectedProductId = null;
    public ?ProductAccurate $selectedProduct = null;
    public $coverPhoto;
    public $galleryPhotos = [];

    public function mount()
    {
        $this->businessUnits = BusinessUnit::where('is_active', true)->where('is_visible_mobile', true)->get();
        
        $this->categories = ProductAccurate::whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->distinct()
            ->orderBy('categoryName')
            ->pluck('categoryName')
            ->values();

        $this->brands = ProductAccurate::whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->distinct()
            ->orderBy('brandName')
            ->pluck('brandName')
            ->values();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedBusinessUnitId()
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory()
    {
        $this->resetPage();
    }

    public function updatingSelectedBrand()
    {
        $this->resetPage();
    }

    public function updatingOnlyInStock()
    {
        $this->resetPage();
    }

    public function updatingMediaFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedBusinessUnitId = null;
        $this->selectedCategory = '';
        $this->selectedBrand = '';
        $this->onlyInStock = true;
        $this->mediaFilter = 'all';
        $this->resetPage();
    }

    public function openUploadModal(int $productId)
    {
        $this->selectedProductId = $productId;
        // Hanya muat relasi yang esensial agar modal terbuka instan tanpa delay
        $this->selectedProduct = ProductAccurate::with([
            'businessUnit',
            'media',
        ])->find($productId);

        $this->coverPhoto = null;
        $this->galleryPhotos = [];
        $this->showUploadModal = true;
    }

    public function closeUploadModal()
    {
        $this->showUploadModal = false;
        $this->selectedProductId = null;
        $this->selectedProduct = null;
        $this->coverPhoto = null;
        $this->galleryPhotos = [];
        $this->resetErrorBag();
    }

    public function saveProductPhotos()
    {
        if (!$this->coverPhoto && empty($this->galleryPhotos)) {
            $this->addError('coverPhoto', 'Foto belum dipilih atau proses unggah belum selesai. Harap pilih foto dan tunggu hingga 100%.');
            return;
        }

        $this->validate([
            'coverPhoto' => 'nullable|image|max:10240',
            'galleryPhotos.*' => 'nullable|image|max:10240',
        ]);

        if (!$this->selectedProductId) {
            return;
        }

        $prod = ProductAccurate::findOrFail($this->selectedProductId);

        // Upload Cover Baru
        if ($this->coverPhoto) {
            $prod->addMedia($this->coverPhoto->getRealPath())
                ->usingFileName($this->coverPhoto->getClientOriginalName())
                ->toMediaCollection('cover');
        }

        // Upload Galeri Foto Tambahan
        if (!empty($this->galleryPhotos)) {
            foreach ($this->galleryPhotos as $photo) {
                $prod->addMedia($photo->getRealPath())
                    ->usingFileName($photo->getClientOriginalName())
                    ->toMediaCollection('gallery');
            }
        }

        $this->dispatch('toast', title: 'Tersimpan', message: 'Foto produk berhasil diunggah ke Cloudflare R2!', type: 'success');
        $this->closeUploadModal();
    }

    public function removeCoverPhoto(int $productId)
    {
        $prod = ProductAccurate::findOrFail($productId);
        $prod->clearMediaCollection('cover');
        $this->dispatch('toast', title: 'Terhapus', message: 'Foto sampul berhasil dihapus.', type: 'info');
        if ($this->showUploadModal && $this->selectedProductId === $productId) {
            $this->selectedProduct = $prod->fresh(['media']);
        }
    }

    public function render()
    {
        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();

        $query = ProductAccurate::query()
            ->where('base_price', '>', 0)
            ->with([
                'businessUnit',
                'media',
                'product.media',
                'productVariants.media',
                'secondProductVariants.media',
                'warehouseStocks' => function ($q) use ($onlineWarehouseIds) {
                    $q->whereIn('warehouse_id', $onlineWarehouseIds);
                }
            ]);

        // Filter stok ready di gudang online (Stok > 0)
        if ($this->onlyInStock) {
            if (!empty($onlineWarehouseIds)) {
                $query->whereExists(function ($sub) use ($onlineWarehouseIds) {
                    $sub->select(DB::raw(1))
                        ->from('warehouse_stocks')
                        ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                        ->whereIn('warehouse_stocks.variant_type', [
                            ProductAccurate::class,
                            'App\\Models\\ProductAccurate',
                            'ProductAccurate'
                        ])
                        ->whereIn('warehouse_stocks.warehouse_id', $onlineWarehouseIds)
                        ->where('warehouse_stocks.stock', '>', 0);
                });
            } else {
                $query->where('stock', '>', 0);
            }
        }

        if ($this->selectedBusinessUnitId) {
            $query->where('business_unit_id', $this->selectedBusinessUnitId);
        } else {
            $query->whereHas('businessUnit', function ($bu) {
                $bu->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('item_no', 'like', "%{$s}%");
            });
        }

        if (!empty($this->selectedCategory)) {
            $query->where('categoryName', $this->selectedCategory);
        }

        if (!empty($this->selectedBrand)) {
            $query->where('brandName', $this->selectedBrand);
        }

        if ($this->mediaFilter === 'has_media') {
            $query->where(function ($q) {
                $q->whereHas('media')
                  ->orWhereHas('product.media')
                  ->orWhereHas('productVariants.media')
                  ->orWhereHas('secondProductVariants.media');
            });
        } elseif ($this->mediaFilter === 'no_media') {
            $query->whereDoesntHave('media')
                  ->whereDoesntHave('product.media')
                  ->whereDoesntHave('productVariants.media')
                  ->whereDoesntHave('secondProductVariants.media');
        }

        $products = $query->paginate(24);

        return view('livewire.zoffline.ecommerce.product-curation', [
            'products' => $products,
            'onlineWarehouseCount' => count($onlineWarehouseIds),
        ]);
    }
}
