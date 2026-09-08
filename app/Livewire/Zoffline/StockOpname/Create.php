<?php

namespace App\Livewire\Zoffline\StockOpname;

use App\Models\Branch;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSerial;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.z', ['title' => 'Mulai Stock Opname Baru'])]
class Create extends Component
{
    public $branchId;
    public $warehouseId;
    public $type = 'ALL'; // ALL, SERIALIZED_ONLY, NON_SERIALIZED_ONLY, CATEGORY
    public $categoryFilter = '';
    public $notes = '';

    public $branchName = '';
    public $warehouseName = '';
    public $businessUnitName = '';

    public $totalAvailableSns = 0;
    public $totalNonSerialSkus = 0;
    public $categoryList = [];

    public function mount()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();

        $this->branchId = $user->branch_id;
        $this->warehouseId = $user->warehouse_id;

        // Fallback jika warehouse_id user belum diset
        if (!$this->warehouseId && $this->branchId) {
            $branch = Branch::find($this->branchId);
            if ($branch) {
                $wh = Warehouse::where('business_unit_id', $buId)
                    ->where('name', $branch->name)
                    ->first();
                if (!$wh) {
                    $wh = Warehouse::where('business_unit_id', $buId)->first();
                }
                $this->warehouseId = $wh ? $wh->id : null;
            }
        }

        $branch = Branch::find($this->branchId);
        $warehouse = Warehouse::find($this->warehouseId);
        $bu = $user->getActiveBusinessUnit();

        $this->branchName = $branch ? $branch->name : 'Cabang Utama';
        $this->warehouseName = $warehouse ? $warehouse->name : 'Gudang Utama';
        $this->businessUnitName = $bu ? $bu->name : 'Unit Bisnis';

        $this->loadEstimates();

        // Kategori / Proyek produk untuk filter kategori
        $this->categoryList = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->distinct()
            ->pluck('proyek')
            ->toArray();
    }

    public function updatedType()
    {
        $this->loadEstimates();
    }

    public function updatedCategoryFilter()
    {
        $this->loadEstimates();
    }

    public function loadEstimates()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();

        if (!$this->warehouseId) {
            $this->totalAvailableSns = 0;
            $this->totalNonSerialSkus = 0;
            return;
        }

        // Estimasi SN Aktif
        $snQuery = ProductSerialNumber::where('warehouse_id', $this->warehouseId)
            ->where('business_unit_id', $buId)
            ->where('status', 'Available');

        if ($this->type === 'CATEGORY' && $this->categoryFilter) {
            $snQuery->whereHas('productAccurate', function ($q) {
                $q->where('proyek', $this->categoryFilter);
            });
        }

        $this->totalAvailableSns = $snQuery->count();

        // Estimasi Aksesoris (Non-Serial)
        $whStockQuery = WarehouseStock::where('warehouse_id', $this->warehouseId)
            ->where('stock', '>', 0);

        $this->totalNonSerialSkus = $whStockQuery->count();
    }

    public function startOpname()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();

        if (!$this->branchId || !$this->warehouseId) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Cabang atau Gudang belum terdefinisi untuk akun Anda.', type: 'warning');
            return;
        }

        $this->validate([
            'type' => 'required|in:ALL,SERIALIZED_ONLY,NON_SERIALIZED_ONLY,CATEGORY',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($this->type === 'CATEGORY' && empty($this->categoryFilter)) {
            $this->addError('categoryFilter', 'Silakan pilih kategori/proyek terlebih dahulu.');
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Generate nomor referensi Opname unik
            $opnameNumber = StockOpname::generateOpnameNumber($buId, $this->branchId);

            // 2. Buat header sesi opname
            $opname = StockOpname::create([
                'opname_number'    => $opnameNumber,
                'business_unit_id' => $buId,
                'branch_id'        => $this->branchId,
                'warehouse_id'     => $this->warehouseId,
                'user_id'          => $user->id,
                'status'           => 'COUNTING',
                'type'             => $this->type,
                'category_filter'  => $this->type === 'CATEGORY' ? $this->categoryFilter : null,
                'start_time'       => now(),
                'notes'            => $this->notes,
            ]);

            // 3. Snapshot Barang Serialized (IMEI / Handphone)
            if (in_array($this->type, ['ALL', 'SERIALIZED_ONLY', 'CATEGORY'])) {
                $snQuery = ProductSerialNumber::with(['productAccurate', 'vendor'])
                    ->where('warehouse_id', $this->warehouseId)
                    ->where('business_unit_id', $buId)
                    ->where('status', 'Available');

                if ($this->type === 'CATEGORY' && $this->categoryFilter) {
                    $snQuery->whereHas('productAccurate', function ($q) {
                        $q->where('proyek', $this->categoryFilter);
                    });
                }

                $availableSns = $snQuery->get();

                // Kelompokkan per SKU (item_no)
                $groupedBySku = $availableSns->groupBy('item_no');

                foreach ($groupedBySku as $itemNo => $sns) {
                    $firstSn = $sns->first();
                    $productName = $firstSn->product_name ?? ($firstSn->productAccurate->name ?? "Produk {$itemNo}");
                    $systemQty = $sns->count();
                    $avgHpp = $sns->avg('hpp') ?: ($firstSn->productAccurate->base_cost ?? 0);

                    // Buat StockOpnameItem
                    $opnameItem = StockOpnameItem::create([
                        'stock_opname_id'  => $opname->id,
                        'item_no'          => $itemNo,
                        'product_name'     => $productName,
                        'is_serialized'    => true,
                        'system_qty'       => $systemQty,
                        'physical_qty'     => 0, // Awalnya 0, bertambah saat BM scan
                        'difference_qty'   => -$systemQty,
                        'unit_cost'        => $avgHpp,
                        'difference_value' => -($systemQty * $avgHpp),
                    ]);

                    // Masukkan seluruh SN snapshot ke tabel serials dengan status MISSING (belum discan)
                    $serialInserts = [];
                    foreach ($sns as $sn) {
                        $serialInserts[] = [
                            'stock_opname_id'      => $opname->id,
                            'stock_opname_item_id' => $opnameItem->id,
                            'item_no'              => $itemNo,
                            'serial_number'        => $sn->serial_number,
                            'status'               => 'MISSING', // Belum discan oleh BM
                            'hpp'                  => $sn->hpp ?? $avgHpp,
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ];
                    }

                    if (!empty($serialInserts)) {
                        StockOpnameSerial::insert($serialInserts);
                    }
                }
            }

            // 4. Snapshot Barang Non-Serialized (Aksesoris)
            if (in_array($this->type, ['ALL', 'NON_SERIALIZED_ONLY', 'CATEGORY'])) {
                // Ambil stok dari ProductAccurate yang tidak berserial (has_sn = false)
                $accStocks = ProductAccurate::with(['warehouseStocks' => function ($q) {
                    $q->where('warehouse_id', $this->warehouseId);
                }])
                    ->where('business_unit_id', $buId)
                    ->where('has_sn', false)
                    ->when($this->type === 'CATEGORY' && $this->categoryFilter, function ($q) {
                        $q->where('proyek', $this->categoryFilter);
                    })
                    ->get();

                foreach ($accStocks as $prod) {
                    $whStock = $prod->warehouseStocks->first();
                    $systemQty = $whStock ? (int) $whStock->stock : 0;

                    // Catat hanya jika ada stok sistem atau jika type khusus aksesoris
                    if ($systemQty > 0 || $this->type === 'NON_SERIALIZED_ONLY') {
                        $unitCost = (float) ($prod->base_cost ?? 0);
                        StockOpnameItem::create([
                            'stock_opname_id'  => $opname->id,
                            'item_no'          => $prod->item_no,
                            'product_name'     => $prod->name,
                            'is_serialized'    => false,
                            'system_qty'       => $systemQty,
                            'physical_qty'     => 0,
                            'difference_qty'   => -$systemQty,
                            'unit_cost'        => $unitCost,
                            'difference_value' => -($systemQty * $unitCost),
                        ]);
                    }
                }
            }

            // 5. Hitung total awal
            $opname->calculateTotals();

            DB::commit();

            return $this->redirectRoute('zoffline.stock-opname.count', $opname->id, navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.zoffline.stock-opname.create');
    }
}
