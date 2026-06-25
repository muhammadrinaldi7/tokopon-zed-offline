<?php

namespace App\Livewire\Zoffline\Warehouse;

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
            ->where('status', '!=', 'Unavailable')
            ->pluck('item_no') // Mengambil SKU dari SN tanpa memandang milik gudang mana
            ->filter()
            ->unique()
            ->toArray();
        // =========================================================================

        // 2. Cari di ProductAccurate sebagai sumber kebenaran tunggal (Single Source of Truth)
        $activeBuId = Auth::user()->getActiveBusinessUnitId();

        $products = \App\Models\ProductAccurate::with('businessUnit')
            ->where('business_unit_id', $activeBuId)
            ->where(function ($query) use ($term, $snSkus) {
                $query->where('item_no', 'like', $term)
                    ->orWhere('name', 'like', $term);

                if (!empty($snSkus)) {
                    $query->orWhereIn('item_no', $snSkus);
                }
            })->take(20)->get();

        $results = [];

        foreach ($products as $p) {
            $results[] = [
                'id' => $p->id,
                'type' => 'accurate',
                'name' => $p->name ?? 'Unknown',
                'ram' => '', // ProductAccurate biasanya tidak nyimpan RAM/Storage terpisah
                'storage' => '',
                'color' => '',
                'sku' => $p->item_no,
                'price' => Format::rupiah($p->base_price ?? 0),
                'allStock' => $p->stock ?? 0,
                'business_unit_name' => $p->businessUnit->name ?? 'Unknown BU',
            ];
        }

        $this->searchResults = $results;
    }

    public function selectProduct($id, $type)
    {
        $userWarehouseId = Auth::user()->warehouse_id;
        $this->selectedProductId = $id;
        $this->selectedProductType = $type;

        $accurate = \App\Models\ProductAccurate::with([
            'productVariants.warehouseStocks.warehouse',
            'secondProductVariants.warehouseStocks.warehouse',
            'warehouseStocks.warehouse',
            'businessUnit'
        ])->find($id);

        if ($accurate) {
            $buName = $accurate->businessUnit->name ?? 'Unknown BU';
            $this->selectedProduct = ($accurate->name ?? 'Unknown') . " [" . $buName . "]";

            // =========================================================================
            // AMBIL DAN KELOMPOKKAN SN BERDASARKAN WAREHOUSE_ID
            // =========================================================================
            $groupedSns = \App\Models\ProductSerialNumber::with('vendor')
                ->where('item_no', $accurate->item_no)
                ->where('status', '!=', 'Unavailable')
                ->get()
                ->groupBy('warehouse_id'); // Menghasilkan array dengan key berupa warehouse_id

            // =========================================================================
            // GABUNGKAN STOCK DARI VARIANT LAMA JIKA ADA
            // =========================================================================
            $allStocks = collect();
            
            // 1. Ambil stok langsung dari ProductAccurate
            foreach ($accurate->warehouseStocks as $ws) {
                $allStocks->push($ws);
            }

            // 2. Ambil dari variant lama (jika masih ada)
            foreach ($accurate->productVariants as $pv) {
                foreach ($pv->warehouseStocks as $ws) {
                    $allStocks->push($ws);
                }
            }
            foreach ($accurate->secondProductVariants as $sv) {
                foreach ($sv->warehouseStocks as $ws) {
                    $allStocks->push($ws);
                }
            }

            $groupedStocks = $allStocks->groupBy('warehouse_id');
            $warehouses = \App\Models\Warehouse::where('business_unit_id', $accurate->business_unit_id)->get();

            $this->stockData = [];

            foreach ($warehouses as $wh) {
                $whSns = isset($groupedSns[$wh->id]) ? $groupedSns[$wh->id] : collect();
                $whStockItems = isset($groupedStocks[$wh->id]) ? $groupedStocks[$wh->id] : collect();

                $totalStock = $whStockItems->sum('stock');

                // Tampilkan gudang jika ada stok di tabel warehouse_stocks ATAU memiliki SN aktif
                if ($totalStock > 0 || $whSns->count() > 0) {
                    $this->stockData[] = [
                        'warehouse_name' => $wh->name ?? 'Gudang Tidak Diketahui',
                        'stock' => max($totalStock, $whSns->count()), // Ambil yang paling besar untuk antisipasi beda data
                        'is_current_user_warehouse' => $wh->id === $userWarehouseId,
                        'sns' => $whSns->map(function ($sn) {
                            return [
                                'serial_number' => $sn->serial_number,
                                'hpp' => $sn->hpp ?? 0,
                                'vendor_name' => $sn->vendor ? $sn->vendor->vendor_name : 'Tidak ada',
                                'receipt_date' => $sn->receipt_date ?? null,
                            ];
                        })->toArray(),
                    ];
                }
            }
        } else {
            $this->stockData = [];
            $this->selectedProduct = '';
            $this->dispatch('toast', title: 'Gagal', message: 'Data produk tidak ditemukan.', type: 'error');
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
        return view('livewire.zoffline.warehouse.cek-stock');
    }
}
