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

        $query = \App\Models\ProductAccurate::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('item_no', 'like', '%' . $this->search . '%');
            });

        if ($this->productType === 'new') {
            $query->where('database_source', 'syihab');
        } elseif ($this->productType === 'second') {
            $query->where('database_source', 'second');
        }

        return $query->take(15)->get();
    }

    #[Computed]
    public function subtotal()
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
    }

    public function addToCart($productId)
    {
        $product = \App\Models\ProductAccurate::find($productId);
        if (!$product) return;

        $warehouseId = Auth::user()->warehouse_id;

        // Cek stok (abaikan jika tipe Jasa/Service)
        $isNonInventory = in_array(strtoupper($product->itemType ?? ''), ['SERVICE', 'NON_INVENTORY']);
        if ($isNonInventory) {
            $stock = 9999;
        } else {
            $warehouseStock = \App\Models\WarehouseStock::where([
                'variant_id' => $product->id,
                'variant_type' => \App\Models\ProductAccurate::class,
                'warehouse_id' => $warehouseId
            ])->first();
            $stock = $warehouseStock ? (int) $warehouseStock->stock : 0;
        }

        if ($stock <= 0) {
            $this->dispatch('toast', title: 'Stok Habis', message: 'Stok produk ini tidak tersedia di gudang Anda.', type: 'warning');
            return;
        }

        // Check if already in cart
        $existingIndex = collect($this->cart)->search(
            fn($item) =>
            $item['variant_id'] == $product->id && $item['variant_type'] == \App\Models\ProductAccurate::class
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
                'variant_id' => $product->id,
                'variant_type' => \App\Models\ProductAccurate::class,
                'name' => $product->name,
                'storage' => '-',
                'color' => '-',
                'price' => (int) $product->base_price,
                'qty' => 1,
                'serial_number' => '', // legacy
                'serial_numbers' => [''], // array of SNs based on qty
                'sku' => $product->item_no ?? '',
                'is_second' => strtolower($product->database_source) === 'second',
            ];
        }

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
