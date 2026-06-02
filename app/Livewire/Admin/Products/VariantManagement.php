<?php

namespace App\Livewire\Admin\Products;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductAccurate;
use App\Models\ProductVariant;
use App\Models\ProductErzap;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

class VariantManagement extends Component
{
    use WithFileUploads;

    public Product $product;
    public $variants;

    // Form inputs
    public $ram;
    public $storage;
    public $color;
    public $condition = 'Baru';
    public $sku;
    public $has_sn = true;

    // Image properties
    public $variantImage;
    public $currentVariantImageUrl;

    // Autocomplete for Accurate
    public $searchAccurate = '';
    public $selectedAccurateId = null;
    public $selectedKode = null;
    public $searchResults = [];
    public $simulatedPrice = 0;
    public $simulatedStock = 0;
    public $manualPrice = 0;

    public $isEditing = false;
    public $editingVariantId = null;

    public function mount(Product $product)
    {
        $this->product = $product;
        $this->loadVariants();
    }

    public function loadVariants()
    {
        $this->variants = $this->product->variants()->with('accurateData')->get();
    }

    public function updatedSearchAccurate()
    {
        if (strlen($this->searchAccurate) > 2) {
            $source = 'syihab'; // Produk baru selalu dari database syihab
            $this->searchResults = ProductAccurate::where('database_source', $source)
                ->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->searchAccurate . '%')
                        ->orWhere('item_no', 'like', '%' . $this->searchAccurate . '%')
                        ->orWhere('accurate_id', 'like', '%' . $this->searchAccurate . '%');
                })
                ->doesntHave('productVariants')
                ->doesntHave('secondProductVariants')
                ->take(5)
                ->get();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectAccurate($id, $price, $stock, $kode = null)
    {
        $this->selectedAccurateId = $id;
        $this->selectedKode = $kode;
        $this->searchAccurate = $kode ? $kode . ' - ' . $id : $id;
        $this->simulatedPrice = $price;
        $this->simulatedStock = $stock;

        if (!$this->manualPrice) {
            $this->manualPrice = $price; // Auto-fill baseline price for second products
        }

        $this->searchResults = []; // close dropdown
    }

    public function clearAccurate()
    {
        $this->selectedAccurateId = null;
        $this->selectedKode = null;
        $this->searchAccurate = '';
        $this->simulatedPrice = 0;
        $this->simulatedStock = 0;
        $this->manualPrice = 0;
        if (!$this->isEditing) {
            $this->manualPrice = 0;
        }
    }

    public function saveVariant()
    {
        $this->validate([
            'condition' => 'required',
            'ram' => 'nullable|string',
            'storage' => 'nullable|string',
            'color' => 'nullable|string',
            'sku' => 'nullable|string',
            'variantImage' => 'nullable|image|max:2048',
            'manualPrice' => 'nullable',
            'has_sn' => 'boolean',
        ]);

        $isNew = false;
        if ($this->isEditing && $this->editingVariantId) {
            $variant = ProductVariant::find($this->editingVariantId);
            $variant->update([
                'product_accurate_id' => $this->selectedAccurateId,
                'condition' => $this->condition,
                'ram' => $this->ram,
                'storage' => $this->storage,
                'color' => $this->color,
                'sku' => $this->sku,
                'has_sn' => $this->has_sn,
                // Price & stock handle by observer mostly, but we set initial here
                'price' => $this->manualPrice > 0 ? $this->manualPrice : ($this->selectedAccurateId ? $this->simulatedPrice : 0),
                'stock' => $this->selectedAccurateId ? $this->simulatedStock : 0,
            ]);
        } else {
            // Create
            $variant = ProductVariant::create([
                'product_id' => $this->product->id,
                'product_accurate_id' => $this->selectedAccurateId,
                'condition' => $this->condition,
                'ram' => $this->ram,
                'storage' => $this->storage,
                'color' => $this->color,
                'sku' => $this->sku,
                'has_sn' => $this->has_sn,
                'price' => $this->manualPrice > 0 ? $this->manualPrice : ($this->selectedAccurateId ? $this->simulatedPrice : 0),
                'stock' => $this->selectedAccurateId ? $this->simulatedStock : 0,
            ]);
            $isNew = true;
        }

        if ($this->variantImage) {
            $variant->addMedia($this->variantImage->getRealPath())
                ->usingFileName($this->variantImage->getClientOriginalName())
                ->toMediaCollection('variant_image');
        }

        // Trigger manual update to ensure parent product gets re-calculated 
        // if this was the first active erzap variant.
        $this->triggerObserverCalculation();

        $this->resetForm();
        $this->loadVariants();

        $this->dispatch(
            'toast',
            title: 'Berhasil',
            message: $isNew ? 'Varian baru berhasil ditambahkan.' : 'Perubahan varian berhasil disimpan!',
            type: 'success'
        );
    }

    private function triggerObserverCalculation()
    {
        $variants = $this->product->variants()->get();
        $totalStock = $variants->sum('stock');
        $startingPrice = $variants->where('price', '>', 0)->min('price');
        $hasActiveAccurate = $variants->whereNotNull('product_accurate_id')->count() > 0;

        $this->product->update([
            'total_stock' => $totalStock,
            'starting_price' => $startingPrice,
            'has_active_accurate' => $hasActiveAccurate, 
        ]);
    }

    public function editVariant($id)
    {
        $variant = ProductVariant::find($id);
        if ($variant) {
            $this->isEditing = true;
            $this->editingVariantId = $id;
            $this->condition = $variant->condition;
            $this->ram = $variant->ram;
            $this->storage = $variant->storage;
            $this->color = $variant->color;
            $this->sku = $variant->sku;
            $this->has_sn = $variant->has_sn;
            $this->manualPrice = $variant->price;
            $this->currentVariantImageUrl = $variant->getFirstMediaUrl('variant_image', 'thumb');

            if ($variant->product_accurate_id) {
                $accurate = $variant->accurateData;
                if ($accurate) {
                    $kode = $accurate->item_no ?? null;
                    $this->selectAccurate($accurate->id, $variant->price, $variant->stock, $kode);
                }
            }
        }
    }

    public function confirmDelete($id)
    {
        $productDelete = ProductVariant::with('product')->find($id);
        $this->dispatch(
            'show-confirm',
            title: 'Hapus Varian',
            message: 'Apakah Anda yakin ingin menghapus varian ' . $productDelete->product->description . '?',
            confirmEvent: 'delete-variant',
            confirmParams: [$id],
            type: 'danger',
            confirmText: 'Hapus',
            cancelText: 'Batal',
        );
    }

    #[On('delete-variant')]
    public function deleteVariant($id)
    {
        ProductVariant::find($id)?->delete();
        $this->triggerObserverCalculation();
        $this->loadVariants();

        $this->dispatch(
            'toast',
            title: 'Terhapus',
            message: 'Varian produk berhasil dihapus dari sistem.',
            type: 'info'
        );
    }

    public function resetForm()
    {
        $this->isEditing = false;
        $this->editingVariantId = null;
        $this->condition = 'Baru';
        $this->ram = '';
        $this->storage = '';
        $this->color = '';
        $this->sku = '';
        $this->has_sn = true;
        $this->variantImage = null;
        $this->currentVariantImageUrl = null;
        $this->clearAccurate();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.products.variant-management');
    }
}
