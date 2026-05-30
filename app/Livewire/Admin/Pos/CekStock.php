<?php

namespace App\Livewire\Admin\Pos;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

use Livewire\Attributes\Layout;

#[Layout('layouts.z', ['title' => 'Cek Stok Gudang'])]
class CekStock extends Component
{
    public $searchQuery = '';
    public $searchResults = [];
    public $stockData = [];
    public $selectedProduct = '';

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            return;
        }

        $term = '%' . $this->searchQuery . '%';

        // Cari di Produk Baru
        $newVariants = \App\Models\ProductVariant::with('product')
            ->where('sku', 'like', $term)
            ->orWhereHas('product', function ($q) use ($term) {
                $q->where('name', 'like', $term);
            })->take(10)->get();

        // Cari di Produk Second
        $secondVariants = \App\Models\SecondProductVariant::with('secondProduct')
            ->where('sku', 'like', $term)
            ->orWhereHas('secondProduct', function ($q) use ($term) {
                $q->where('name', 'like', $term);
            })->take(10)->get();

        $results = [];

        foreach ($newVariants as $v) {
            $results[] = [
                'id' => $v->id,
                'type' => 'new',
                'name' => $v->product->name ?? 'Unknown',
                'storage' => $v->storage,
                'color' => $v->color,
                'sku' => $v->sku,
                'is_second' => false,
            ];
        }

        foreach ($secondVariants as $v) {
            $results[] = [
                'id' => $v->id,
                'type' => 'second',
                'name' => $v->secondProduct->name ?? 'Unknown',
                'storage' => $v->storage,
                'color' => $v->color,
                'sku' => $v->sku,
                'is_second' => true,
            ];
        }

        $this->searchResults = $results;
    }

    public function selectProduct($id, $type)
    {
        $userWarehouseId = Auth::user()->warehouse_id;

        if ($type === 'second') {
            $variant = \App\Models\SecondProductVariant::with('warehouseStocks.warehouse', 'secondProduct')->find($id);
            $this->selectedProduct = ($variant->secondProduct->name ?? 'Unknown') . " ({$variant->storage} - {$variant->color}) [Second]";
        } else {
            $variant = \App\Models\ProductVariant::with('warehouseStocks.warehouse', 'product')->find($id);
            $this->selectedProduct = ($variant->product->name ?? 'Unknown') . " ({$variant->storage} - {$variant->color}) [Baru]";
        }

        if ($variant) {
            // Mapping data stok
            $this->stockData = $variant->warehouseStocks->map(function ($ws) use ($userWarehouseId) {
                return [
                    'warehouse_name' => $ws->warehouse->name ?? 'Gudang Tidak Diketahui',
                    'stock' => $ws->stock,
                    'is_current_user_warehouse' => $ws->warehouse_id === $userWarehouseId,
                ];
            })->toArray();
        } else {
            $this->stockData = [];
            $this->selectedProduct = '';
            $this->dispatch('toast', title: 'Gagal', message: 'Data varian tidak ditemukan.', type: 'error');
        }

        // Reset pencarian setelah dipilih
        $this->searchResults = [];
        $this->searchQuery = '';
    }

    public function resetCheck()
    {
        $this->stockData = [];
        $this->selectedProduct = '';
        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function render()
    {
        return view('livewire.admin.pos.cek-stock');
    }
}
