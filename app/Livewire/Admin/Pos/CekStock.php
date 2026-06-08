<?php

namespace App\Livewire\Admin\Pos;

use App\Utils\Format;
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
    public $selectedProductId = null;
    public $selectedProductType = null;
    // Tambahkan properti ini di bagian atas class controller
    public $showSnModal = false;
    public $modalWarehouseName = '';
    public $modalSns = [];
    // Tambahkan fungsi ini untuk membuka modal dan mengoper data SN
    public function openSnModal($warehouseName, $sns)
    {
        $this->modalWarehouseName = $warehouseName;
        $this->modalSns = $sns;
        $this->showSnModal = true;
    }

    // Tambahkan fungsi ini untuk menutup modal
    public function closeSnModal()
    {
        $this->showSnModal = false;
        $this->modalWarehouseName = '';
        $this->modalSns = [];
    }
    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            return;
        }

        $term = '%' . $this->searchQuery . '%';

        // =========================================================================
        // 1. CARI SKU BERDASARKAN SN DI TABEL LOCAL (Semua Warehouse)
        // =========================================================================
        $snSkus = \Illuminate\Support\Facades\DB::table('product_serial_numbers')
            ->where('serial_number', 'like', $term)
            ->pluck('item_no') // Mengambil SKU dari SN tanpa memandang milik gudang mana
            ->filter()
            ->unique()
            ->toArray();
        // =========================================================================

        // 2. Cari di Produk Baru (Berdasarkan SKU, Nama Produk, atau Hasil SKU dari SN)
        $newVariants = \App\Models\ProductVariant::with(['product', 'accurateData'])
            ->where(function ($query) use ($term, $snSkus) {
                $query->where('sku', 'like', $term)
                    ->orWhereHas('product', function ($q) use ($term) {
                        $q->where('name', 'like', $term);
                    });

                if (!empty($snSkus)) {
                    $query->orWhereIn('sku', $snSkus);
                }
            })->take(10)->get();

        // 3. Cari di Produk Second (Berdasarkan SKU, Nama Produk, atau Hasil SKU dari SN)
        $secondVariants = \App\Models\SecondProductVariant::with(['secondProduct', 'accurateData'])
            ->where(function ($query) use ($term, $snSkus) {
                $query->where('sku', 'like', $term)
                    ->orWhereHas('secondProduct', function ($q) use ($term) {
                        $q->where('name', 'like', $term);
                    });

                if (!empty($snSkus)) {
                    $query->orWhereIn('sku', $snSkus);
                }
            })->take(10)->get();

        $results = [];

        foreach ($newVariants as $v) {
            $results[] = [
                'id' => $v->id,
                'type' => 'new',
                'name' => $v->product->name ?? 'Unknown',
                'ram' => $v->ram ?? '',
                'storage' => $v->storage,
                'color' => $v->color,
                'sku' => $v->sku,
                'price' => Format::rupiah($v->accurateData?->base_price ?? 0),
                'allStock' => $v->accurateData->stock ?? 0,
                'is_second' => false,
            ];
        }

        foreach ($secondVariants as $v) {
            $results[] = [
                'id' => $v->id,
                'type' => 'second',
                'name' => $v->secondProduct->name ?? 'Unknown',
                'ram' => $v->ram ?? '',
                'storage' => $v->storage,
                'color' => $v->color,
                'sku' => $v->sku,
                'price' => Format::rupiah($v->accurateData->base_price) ?? 0,
                'allStock' => $v->accurateData->stock ?? 0,
                'is_second' => true,
            ];
        }

        $this->searchResults = $results;
    }

    // public function selectProduct($id, $type)
    // {
    //     $userWarehouseId = Auth::user()->warehouse_id;
    //     $this->selectedProductId = $id;
    //     $this->selectedProductType = $type;

    //     if ($type === 'second') {
    //         $variant = \App\Models\SecondProductVariant::with('warehouseStocks.warehouse', 'secondProduct')->find($id);
    //         $ramStorage = !empty($variant->ram) ? $variant->ram . ' / ' . $variant->storage : $variant->storage;
    //         $this->selectedProduct = ($variant->secondProduct->name ?? 'Unknown') . " ({$ramStorage} - {$variant->color}) [Second]";
    //     } else {
    //         $variant = \App\Models\ProductVariant::with('warehouseStocks.warehouse', 'product')->find($id);
    //         $ramStorage = !empty($variant->ram) ? $variant->ram . ' / ' . $variant->storage : $variant->storage;
    //         $this->selectedProduct = ($variant->product->name ?? 'Unknown') . " ({$ramStorage} - {$variant->color}) [Baru]";
    //     }

    //     if ($variant) {
    //         // Mapping data stok
    //         $this->stockData = $variant->warehouseStocks->map(function ($ws) use ($userWarehouseId) {
    //             return [
    //                 'warehouse_name' => $ws->warehouse->name ?? 'Gudang Tidak Diketahui',
    //                 'stock' => $ws->stock,
    //                 'is_current_user_warehouse' => $ws->warehouse_id === $userWarehouseId,
    //             ];
    //         })->toArray();
    //     } else {
    //         $this->stockData = [];
    //         $this->selectedProduct = '';
    //         $this->dispatch('toast', title: 'Gagal', message: 'Data varian tidak ditemukan.', type: 'error');
    //     }
    // }
    public function selectProduct($id, $type)
    {
        $userWarehouseId = Auth::user()->warehouse_id;
        $this->selectedProductId = $id;
        $this->selectedProductType = $type;

        if ($type === 'second') {
            $variant = \App\Models\SecondProductVariant::with('warehouseStocks.warehouse', 'secondProduct')->find($id);
            $ramStorage = !empty($variant->ram) ? $variant->ram . ' / ' . $variant->storage : $variant->storage;
            $this->selectedProduct = ($variant->secondProduct->name ?? 'Unknown') . " ({$ramStorage} - {$variant->color}) [Second]";
        } else {
            $variant = \App\Models\ProductVariant::with('warehouseStocks.warehouse', 'product')->find($id);
            $ramStorage = !empty($variant->ram) ? $variant->ram . ' / ' . $variant->storage : $variant->storage;
            $this->selectedProduct = ($variant->product->name ?? 'Unknown') . " ({$ramStorage} - {$variant->color}) [Baru]";
        }

        if ($variant) {
            // =========================================================================
            // AMBIL DAN KELOMPOKKAN SN BERDASARKAN WAREHOUSE_ID
            // =========================================================================
            $groupedSns = \App\Models\ProductSerialNumber::with('vendor')
                ->where('item_no', $variant->sku)
                ->get()
                ->groupBy('warehouse_id'); // Menghasilkan array dengan key berupa warehouse_id

            // Mapping data stok
            $this->stockData = $variant->warehouseStocks->map(function ($ws) use ($userWarehouseId, $groupedSns) {

                // Ambil SN khusus untuk ID gudang saat ini dari hasil group tadi
                $currentWarehouseSns = isset($groupedSns[$ws->warehouse_id])
                    ? $groupedSns[$ws->warehouse_id]->map(function($sn) {
                        return [
                            'serial_number' => $sn->serial_number,
                            'hpp' => $sn->hpp ?? 0,
                            'vendor_name' => $sn->vendor ? $sn->vendor->vendor_name : 'Tidak ada',
                        ];
                    })->toArray()
                    : [];

                return [
                    'warehouse_name' => $ws->warehouse->name ?? 'Gudang Tidak Diketahui',
                    'stock' => $ws->stock,
                    'is_current_user_warehouse' => $ws->warehouse_id === $userWarehouseId,
                    'sns' => $currentWarehouseSns, // Hanya berisi SN milik gudang ini saja
                ];
            })->toArray();
        } else {
            $this->stockData = [];
            $this->selectedProduct = '';
            $this->dispatch('toast', title: 'Gagal', message: 'Data varian tidak ditemukan.', type: 'error');
        }
    }

    public function resetCheck()
    {
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->stockData = [];
        $this->selectedProduct = '';
        $this->selectedProductId = null;
        $this->selectedProductType = null;
    }

    public function render()
    {
        return view('livewire.admin.pos.cek-stock');
    }
}
