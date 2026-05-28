<?php

namespace App\Livewire\Admin\Pos\Traits;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SecondProduct;
use App\Models\SecondProductVariant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait WithCart
{
    // ─── Search & Filter ───────────────────────────────────────
    public $search = '';
    public $productType = 'new'; // all, new, second

    // ─── Cart (in-memory) ──────────────────────────────────────
    public $cart = []; // [{variant_id, variant_type, name, storage, color, price, qty, serial_number, sku}]

    // ─── Variant Selection ─────────────────────────────────────
    public $showVariantModal = false;
    public $variantModalProduct = null;
    public $variantModalVariants = [];
    public $variantModalIsSecond = false;

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->search) < 2) return collect();

        $newProducts = collect();
        $secondProducts = collect();

        if ($this->productType !== 'second') {
            $newProducts = Product::with(['variants', 'brand', 'media'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('variants', function ($q2) {
                            $q2->where('sku', 'like', '%' . $this->search . '%');
                        });
                })
                ->take(10)->get()
                ->map(function ($p) {
                    $p->is_second_catalog = false;
                    return $p;
                });
        }

        if ($this->productType !== 'new') {
            $secondProducts = SecondProduct::with(['variants', 'brand', 'media'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('variants', function ($q2) {
                            $q2->where('sku', 'like', '%' . $this->search . '%');
                        });
                })
                ->take(10)->get()
                ->map(function ($p) {
                    $p->is_second_catalog = true;
                    return $p;
                });
        }

        return $newProducts->concat($secondProducts);
    }

    #[Computed]
    public function subtotal()
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
    }

    public function openVariantPicker($productId, $isSecond = false)
    {
        $warehouseId = Auth::user()->warehouse_id;

        if ($isSecond) {
            $product = SecondProduct::with([
                'variants' => function ($q) use ($warehouseId) {
                    $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
                        $q2->where('warehouse_id', $warehouseId);
                    }]);
                },
                'brand'
            ])->find($productId);

            $this->variantModalVariants = $product->variants->map(fn($v) => [
                'id' => $v->id,
                'label' => $v->color . ' - ' . $v->storage,
                'condition' => $v->condition ?? '',
                'price' => $v->price,
                'stock' => $v->warehouseStocks->first()?->stock ?? 0,
                'sku' => $v->sku ?? '',
            ])->toArray();
        } else {
            $product = Product::with([
                'variants' => function ($q) use ($warehouseId) {
                    $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
                        $q2->where('warehouse_id', $warehouseId);
                    }]);
                },
                'brand'
            ])->find($productId);

            $this->variantModalVariants = $product->variants->map(fn($v) => [
                'id' => $v->id,
                'label' => $v->color . ' - ' . $v->storage,
                'condition' => '',
                'price' => $v->price,
                'stock' => $v->warehouseStocks->first()?->stock ?? 0,
                'sku' => $v->sku ?? '',
            ])->toArray();
        }
        $this->variantModalProduct = $product;
        $this->variantModalIsSecond = $isSecond;
        $this->showVariantModal = true;
    }

    public function addVariantToCart($variantId)
    {
        $isSecond = $this->variantModalIsSecond;
        $product = $this->variantModalProduct;
        $warehouseId = Auth::user()->warehouse_id;

        if ($isSecond) {
            $variant = SecondProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->find($variantId);
            $variantType = SecondProductVariant::class;
        } else {
            $variant = ProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->find($variantId);
            $variantType = ProductVariant::class;
        }

        $stock = $variant ? ($variant->warehouseStocks->first()?->stock ?? 0) : 0;

        if (!$variant || $stock <= 0) {
            $this->dispatch('toast', title: 'Stok Habis', message: 'Varian ini tidak tersedia.', type: 'warning');
            return;
        }

        // Check if already in cart
        $existingIndex = collect($this->cart)->search(
            fn($item) =>
            $item['variant_id'] == $variantId && $item['variant_type'] == $variantType
        );

        if ($existingIndex !== false) {
            $currentQty = $this->cart[$existingIndex]['qty'];
            if ($currentQty < $stock) {
                $this->cart[$existingIndex]['qty']++;
                if (!isset($this->cart[$existingIndex]['serial_numbers'])) {
                    $this->cart[$existingIndex]['serial_numbers'] = [$this->cart[$existingIndex]['serial_number'] ?? ''];
                }
                $this->cart[$existingIndex]['serial_numbers'][] = '';
            } else {
                $this->dispatch('toast', title: 'Stok Tidak Cukup', message: 'Sudah mencapai batas stok.', type: 'warning');
            }
        } else {
            $this->cart[] = [
                'variant_id' => $variant->id,
                'variant_type' => $variantType,
                'name' => $product->name,
                'storage' => $variant->storage ?? '-',
                'color' => $variant->color ?? '-',
                'price' => (int) $variant->price,
                'qty' => 1,
                'serial_number' => '', // legacy
                'serial_numbers' => [''], // array of SNs based on qty
                'sku' => $variant->sku ?? '',
                'is_second' => $isSecond,
            ];
        }

        $this->showVariantModal = false;
        $this->variantModalProduct = null;
        $this->variantModalVariants = [];
        if (method_exists($this, 'syncSinglePaymentAmount')) {
            $this->syncSinglePaymentAmount();
        }
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // re-index
        if (method_exists($this, 'syncSinglePaymentAmount')) {
            $this->syncSinglePaymentAmount();
        }
    }

    public function incrementCartItem($index)
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['qty']++;
            if (!isset($this->cart[$index]['serial_numbers'])) {
                $this->cart[$index]['serial_numbers'] = [$this->cart[$index]['serial_number'] ?? ''];
            }
            $this->cart[$index]['serial_numbers'][] = '';
            if (method_exists($this, 'syncSinglePaymentAmount')) {
                $this->syncSinglePaymentAmount();
            }
        }
    }

    public function decrementCartItem($index)
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['qty'] > 1) {
            $this->cart[$index]['qty']--;
            if (isset($this->cart[$index]['serial_numbers'])) {
                array_pop($this->cart[$index]['serial_numbers']);
            }
            if (method_exists($this, 'syncSinglePaymentAmount')) {
                $this->syncSinglePaymentAmount();
            }
        }
    }

    public function updateSerialNumber($index, $snIndex, $value)
    {
        if (isset($this->cart[$index])) {
            if (!isset($this->cart[$index]['serial_numbers'])) {
                $this->cart[$index]['serial_numbers'] = [$this->cart[$index]['serial_number'] ?? ''];
            }
            $this->cart[$index]['serial_numbers'][$snIndex] = $value;
            // Also update legacy for backward compatibility
            if ($snIndex === 0) {
                $this->cart[$index]['serial_number'] = $value;
            }
        }
    }
}
