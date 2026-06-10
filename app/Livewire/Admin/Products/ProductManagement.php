<?php

namespace App\Livewire\Admin\Products;

use Livewire\Component;
use App\Models\Product;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductVariant;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\WarehouseStock;


class ProductManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $showDetailModal = false;
    public $showImportModal = false;
    public $isEditing = false;
    public $productId;
    public $detailProduct = null;
    public $importFile;

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

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingFilterCategory()
    {
        $this->resetPage();
    }
    public function updatingFilterBrand()
    {
        $this->resetPage();
    }

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

    public function downloadTemplateCsv()
    {
        $filename = 'template_import_produk.csv';
        $headers = [
            'Product Name',
            'Category',
            'Brand',
            'Description',
            'SKU',
            'Condition',
            'RAM',
            'Storage',
            'Color',
            'Price',
            'Stock',
            'Kode Accurate'
        ];

        return response()->streamDownload(function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers, ',');
            fputcsv($file, ['iPhone 15 Pro Max', 'Smartphone', 'Apple', 'HP Baru', 'IPH-15-PM-256-BLK', 'Baru', '8GB', '256GB', 'Black Titanium', '', '', 'ITM-IPH15-256B'], ',');
            fclose($file);
        }, $filename);
    }

    public function exportAccurateDataCsv()
    {
        $filename = 'data_master_accurate_lokal.csv';
        $headers = [
            'ID Database Lokal',
            'Kode Accurate (Item No)',
            'Nama Produk Accurate',
            'Harga Dasar',
            'Stok Accurate'
        ];

        return response()->streamDownload(function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers, ',');

            $accurateItems = ProductAccurate::where('database_source', 'syihab')->orderBy('name')->get();
            foreach ($accurateItems as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->item_no,
                    $item->name,
                    (int) $item->base_price,
                    $item->stock
                ], ',');
            }
            fclose($file);
        }, $filename);
    }

    public function importCsv()
    {
        $this->validate([
            'importFile' => 'required|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $rowNum = 1;

        try {
            DB::beginTransaction();

            $file = fopen($this->importFile->getRealPath(), 'r');

            // Baca header
            $header = fgetcsv($file, 1000, ',');
            // Deteksi separator (koma atau titik koma)
            if (count($header) < 5) {
                fclose($file);
                $file = fopen($this->importFile->getRealPath(), 'r');
                $header = fgetcsv($file, 1000, ';');
                $delimiter = ';';
            } else {
                $delimiter = ',';
            }

            // Clean BOM
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);

            while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
                $rowNum++;
                if (count($row) < 11) continue; // Skip baris tidak valid

                $productName = trim($row[0]);
                $categoryName = trim($row[1]);
                $brandName = trim($row[2]);
                $description = trim($row[3]);
                $sku = trim($row[4]);
                $condition = trim($row[5]);
                $ram = trim($row[6]);
                $storage = trim($row[7]);
                $color = trim($row[8]);
                $price = (float) str_replace(['Rp', '.', ','], '', trim($row[9])); // Clean format Rp / titik / koma
                $stock = (int) trim($row[10]);
                $kodeAccurate = isset($row[11]) ? trim($row[11]) : null;

                if (empty($productName) || empty($categoryName)) continue;

                // 1. Cari atau buat Category
                $category = Category::firstOrCreate(
                    ['name' => $categoryName],
                    ['slug' => \Illuminate\Support\Str::slug($categoryName)]
                );

                // 2. Cari atau buat Brand (jika diisi)
                $brandId = null;
                if (!empty($brandName)) {
                    $brand = Brand::firstOrCreate(
                        ['name' => $brandName],
                        ['slug' => \Illuminate\Support\Str::slug($brandName)]
                    );
                    $brandId = $brand->id;
                }

                // 3. Cari atau buat Product Induk
                $product = Product::firstOrCreate(
                    ['name' => $productName],
                    [
                        'slug' => \Illuminate\Support\Str::slug($productName) . '-' . uniqid(),
                        'category_id' => $category->id,
                        'brand_id' => $brandId,
                        'description' => $description,
                        'is_active' => true,
                        'has_active_accurate' => false
                    ]
                );

                // 4. Integrasi Accurate
                $accurateId = null;
                $finalPrice = $price;
                $finalStock = $stock;

                if (!empty($kodeAccurate)) {
                    $accurateData = ProductAccurate::where('item_no', $kodeAccurate)->first();
                    if ($accurateData) {
                        $accurateId = $accurateData->id;
                        $finalPrice = (float) $accurateData->base_price;
                        $finalStock = (int) $accurateData->stock;

                        $product->has_active_accurate = true;
                        $product->save();
                    }
                }

                // 5. Buat Variant
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => empty($sku) ? null : $sku,
                    'condition' => empty($condition) ? 'Baru' : $condition,
                    'ram' => empty($ram) ? null : $ram,
                    'storage' => empty($storage) ? null : $storage,
                    'color' => empty($color) ? null : $color,
                    'price' => $finalPrice,
                    'stock' => 0, // Akan di-handle oleh WarehouseStock
                    'product_accurate_id' => $accurateId
                ]);

                // 6. Buat initial stock di Warehouse Stock
                $warehouseId = Auth::user()->warehouse_id ?? Warehouse::first()->id ?? null;
                if ($warehouseId) {
                    WarehouseStock::create([
                        'warehouse_id' => $warehouseId,
                        'variant_id' => $variant->id,
                        'variant_type' => get_class($variant),
                        'stock' => $finalStock
                    ]);
                    // Update total_stock di product (bisa ditrigger observer, tapi amannya kita panggil method)
                    $product->increment('total_stock', $finalStock);
                }
            }

            fclose($file);
            DB::commit();

            $this->showImportModal = false;
            $this->importFile = null;
            $this->dispatch('toast', title: 'Berhasil', message: 'Data Produk berhasil diimport massal.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', title: 'Gagal Import', message: 'Error pada baris ke-' . $rowNum . ': ' . $e->getMessage(), type: 'error');
        }
    }


    public function viewDetail($id)
    {
        $this->detailProduct = Product::with(['category', 'brand'])->find($id);
        $this->showDetailModal = true;
    }

    public function edit($id)
    {
        $this->resetFields();
        $product = Product::findOrFail($id);
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
            $product = Product::find($this->productId);
            $product->update([
                'name' => $this->name,
                'slug' => \Illuminate\Support\Str::slug($this->name) . '-' . uniqid(),
                'description' => $this->description,
                'category_id' => $this->categoryId,
                'brand_id' => empty($this->brandId) ? null : $this->brandId,
                'specifications' => empty($specsDict) ? null : $specsDict,
            ]);
        } else {
            $product = Product::create([
                'name' => $this->name,
                'slug' => \Illuminate\Support\Str::slug($this->name) . '-' . uniqid(),
                'description' => $this->description,
                'category_id' => $this->categoryId,
                'brand_id' => empty($this->brandId) ? null : $this->brandId,
                'specifications' => empty($specsDict) ? null : $specsDict,
                'is_active' => true,
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
        $product = Product::find($id);
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
        $product = Product::find($id);
        if ($product) {
            // Spatie handles media deletion automatically if configured or manually
            $product->delete();
        }
        $this->dispatch('toast', title: 'Terhapus', message: 'Produk berhasil dihapus permanen.', type: 'info');
    }

    public function removeGalleryImage($mediaId)
    {
        $product = Product::find($this->productId);
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
        $query = Product::with(['category', 'brand']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('variants', function ($v) {
                      $v->where('sku', 'like', '%' . $this->search . '%');
                  });
            });
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

        return view('livewire.admin.products.product-management', [
            'products' => $products,
            'categoriesList' => $categoriesList,
            'brandsList' => $brandsList,
        ]);
    }
}
