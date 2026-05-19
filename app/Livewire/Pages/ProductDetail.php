<?php

namespace App\Livewire\Pages;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

class ProductDetail extends Component
{
    public Product $product;
    public ?int $selectedVariantId = null;
    public ?ProductVariant $selectedVariant = null;
    public int $quantity = 1;
    public bool $added = false;
    public int $activeImageIndex = 0;

    public function mount(Product $product): void
    {
        $minStock = \App\Models\Setting::where('key', 'minimum_stock_threshold')->value('value') ?? 5;
        if (!$product->is_active || !$product->has_active_accurate || $product->total_stock < $minStock) {
            abort(404, 'Produk tidak ditemukan atau tidak tersedia.');
        }

        $this->product = $product->load([
            'variants' => fn($q) => $q->orderBy('price'),
            'brand',
            'category',
            'media',
            'reviews.user',
        ]);

        // Auto-select first available variant
        $firstAvailable = $this->product->variants->where('stock', '>', 0)->first();
        if ($firstAvailable) {
            $this->selectedVariantId = $firstAvailable->id;
            $this->selectedVariant = $firstAvailable;
        }
    }

    public function selectVariant(int $variantId): void
    {
        $variant = $this->product->variants->find($variantId);
        if ($variant) {
            $this->selectedVariantId = $variantId;
            $this->selectedVariant = $variant;
            $this->quantity = 1; // Reset qty on variant change
        }
    }

    public function setActiveImage(int $index): void
    {
        $this->activeImageIndex = $index;
    }

    public function incrementQty(): void
    {
        if ($this->selectedVariant && $this->quantity < $this->selectedVariant->stock) {
            $this->quantity++;
        }
    }

    public function decrementQty(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart(): void
    {
        if (!$this->selectedVariantId) return;

        $cartService = app(CartService::class);
        $cartService->addItem($this->selectedVariantId, $this->quantity);

        $this->added = true;
        $this->dispatch('cart-updated');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        // Collect all product images (cover + gallery)
        $images = collect();
        $cover = $this->product->getFirstMedia('cover');
        if ($cover) {
            $images->push($cover);
        }
        foreach ($this->product->getMedia('gallery') as $media) {
            $images->push($media);
        }

        // Include variant images in the gallery
        foreach ($this->product->variants as $variant) {
            if ($variantMedia = $variant->getFirstMedia('variant_image')) {
                // Prevent duplicate images if they happen to be similar, though they are distinct IDs
                $images->push($variantMedia);
            }
        }

        return view('livewire.pages.product-detail', [
            'images' => $images,
        ])->title($this->product->name . ' - TokoPun');
    }
}
