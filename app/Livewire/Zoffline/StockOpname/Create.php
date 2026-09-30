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

    // Jenis Barang yang Dihitung: ALL (Semua), SERIALIZED_ONLY (Khusus HP / IMEI), NON_SERIALIZED_ONLY (Khusus Aksesoris)
    public $itemType = 'ALL';

    // 3 KUNCI UTAMA AUDIT (Bisa dikombinasikan secara bebas / multi-filter)
    public $brandFilter = '';
    public $categoryFilter = '';
    public $projectFilter = '';

    // Search query box untuk filter panjang
    public $searchBrand = '';
    public $searchCategory = '';
    public $searchProject = '';

    public $notes = '';

    public $branchName = '';
    public $warehouseName = '';
    public $businessUnitName = '';

    // Estimasi Real-Time
    public $totalAvailableSns = 0;
    public $totalNonSerialSkus = 0;
    public $totalEstimatedHpp = 0;
    public $sampleItems = [];

    // Master List Data
    public $brandList = [];
    public $categoryList = [];
    public $projectList = [];

    // Sesi aktif di cabang ini jika ada
    public $activeOpname = null;

    public function mount()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();
        $isGlobal = $user->hasAnyRole(['superadmin', 'admin', 'director', 'direktur', 'manager_operasional', 'manager_operasional_gsk']);
        $isBm = $user->hasAnyRole(['bm', 'bm_gsk']);

        if (!$isGlobal && !$isBm) {
            abort(403, 'Akses ditolak: Hanya Branch Manager (BM) yang berwenang membuka sesi Stock Opname di cabang.');
        }

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

        // Cek sesi aktif berstatus COUNTING pada cabang & gudang ini
        if ($this->warehouseId) {
            $this->activeOpname = StockOpname::with('user')
                ->where('warehouse_id', $this->warehouseId)
                ->where('status', 'COUNTING')
                ->latest()
                ->first();
        }

        // Ambil daftar Brand, Kategori, dan Proyek aktif
        $this->brandList = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->distinct()
            ->orderBy('brandName')
            ->pluck('brandName')
            ->toArray();

        $this->categoryList = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->distinct()
            ->orderBy('categoryName')
            ->pluck('categoryName')
            ->toArray();

        $this->projectList = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->distinct()
            ->orderBy('proyek')
            ->pluck('proyek')
            ->toArray();

        $this->loadEstimates();
    }

    /**
     * Pilih Jenis Barang: ALL, SERIALIZED_ONLY, NON_SERIALIZED_ONLY
     */
    public function setItemType(string $type)
    {
        $this->itemType = $type;
        $this->loadEstimates();
    }

    /**
     * Set / Clear Brand Filter
     */
    public function setBrandFilter(string $brand)
    {
        $this->brandFilter = $brand;
        $this->searchBrand = '';
        $this->loadEstimates();
    }

    /**
     * Set / Clear Kategori Filter
     */
    public function setCategoryFilter(string $category)
    {
        $this->categoryFilter = $category;
        $this->searchCategory = '';
        $this->loadEstimates();
    }

    /**
     * Set / Clear Proyek Filter
     */
    public function setProjectFilter(string $project)
    {
        $this->projectFilter = $project;
        $this->searchProject = '';
        $this->loadEstimates();
    }

    /**
     * Reset seluruh filter ke default (Semua Produk)
     */
    public function resetFilters()
    {
        $this->brandFilter = '';
        $this->categoryFilter = '';
        $this->projectFilter = '';
        $this->itemType = 'ALL';
        $this->searchBrand = '';
        $this->searchCategory = '';
        $this->searchProject = '';
        $this->loadEstimates();
    }

    public function updatedBrandFilter()
    {
        $this->loadEstimates();
    }

    public function updatedCategoryFilter()
    {
        $this->loadEstimates();
    }

    public function updatedProjectFilter()
    {
        $this->loadEstimates();
    }

    public function updatedItemType()
    {
        $this->loadEstimates();
    }

    /**
     * Ambil daftar SKU yang beririsan dengan ketiga kunci (Brand AND Category AND Proyek)
     */
    protected function getFilteredTargetSkus()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();

        $hasFilter = !empty($this->brandFilter) || !empty($this->categoryFilter) || !empty($this->projectFilter);
        if (!$hasFilter) {
            return null; // Tanpa filter = Seluruh SKU
        }

        $query = ProductAccurate::where('business_unit_id', $buId);

        if (!empty($this->brandFilter)) {
            $query->where('brandName', $this->brandFilter);
        }

        if (!empty($this->categoryFilter)) {
            $query->where('categoryName', $this->categoryFilter);
        }

        if (!empty($this->projectFilter)) {
            $query->where('proyek', $this->projectFilter);
        }

        return $query->pluck('item_no');
    }

    /**
     * Hitung perkiraan kuantitas dan nilai HPP aset yang akan diaudit
     */
    public function loadEstimates()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();

        if (!$this->warehouseId) {
            $this->totalAvailableSns = 0;
            $this->totalNonSerialSkus = 0;
            $this->totalEstimatedHpp = 0;
            $this->sampleItems = [];
            return;
        }

        $targetSkus = $this->getFilteredTargetSkus();
        $hasFilter = $targetSkus !== null;

        // Cek apakah jenis barang diikutsertakan
        $includeSn = in_array($this->itemType, ['ALL', 'SERIALIZED_ONLY']);
        $includeNonSn = in_array($this->itemType, ['ALL', 'NON_SERIALIZED_ONLY']);

        $totalHpp = 0;

        // 1. Estimasi Unit HP (IMEI)
        if ($includeSn) {
            if ($hasFilter && $targetSkus->isEmpty()) {
                $this->totalAvailableSns = 0;
            } else {
                $snQuery = ProductSerialNumber::where('warehouse_id', $this->warehouseId)
                    ->where('business_unit_id', $buId)
                    ->where('status', 'Available');

                if ($hasFilter) {
                    $snQuery->whereIn('item_no', $targetSkus);
                }

                $this->totalAvailableSns = $snQuery->count();
                $totalHpp += (float) $snQuery->sum('hpp');
            }
        } else {
            $this->totalAvailableSns = 0;
        }

        // 2. Estimasi SKU Aksesoris (Non-Serial)
        if ($includeNonSn) {
            if ($hasFilter && $targetSkus->isEmpty()) {
                $this->totalNonSerialSkus = 0;
            } else {
                $accQuery = ProductAccurate::where('business_unit_id', $buId)
                    ->where('has_sn', false)
                    ->whereHas('warehouseStocks', function ($q) {
                        $q->where('warehouse_id', $this->warehouseId)->where('stock', '>', 0);
                    });

                if ($hasFilter) {
                    $accQuery->whereIn('item_no', $targetSkus);
                }

                $this->totalNonSerialSkus = $accQuery->count();

                // Hitung HPP cepat via join
                $accHppQuery = ProductAccurate::join('warehouse_stocks', function ($join) {
                        $join->on('warehouse_stocks.variant_id', '=', 'product_accurates.id')
                             ->where('warehouse_stocks.variant_type', '=', ProductAccurate::class);
                    })
                    ->where('warehouse_stocks.warehouse_id', $this->warehouseId)
                    ->where('warehouse_stocks.stock', '>', 0)
                    ->where('product_accurates.has_sn', false)
                    ->where('product_accurates.business_unit_id', $buId);

                if ($hasFilter) {
                    $accHppQuery->whereIn('product_accurates.item_no', $targetSkus);
                }

                $totalHpp += (float) $accHppQuery->sum(DB::raw('warehouse_stocks.stock * product_accurates.base_cost'));
            }
        } else {
            $this->totalNonSerialSkus = 0;
        }

        $this->totalEstimatedHpp = $totalHpp;

        // Ambil sampel nama produk
        $this->sampleItems = $this->getSampleItems($targetSkus, $includeSn, $includeNonSn);
    }

    /**
     * Ambil 4 contoh barang yang masuk dalam cakupan untuk preview BM
     */
    protected function getSampleItems($targetSkus, bool $includeSn, bool $includeNonSn): array
    {
        $samples = [];
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();

        if ($includeSn) {
            $snQuery = ProductSerialNumber::with('productAccurate')
                ->where('warehouse_id', $this->warehouseId)
                ->where('business_unit_id', $buId)
                ->where('status', 'Available');

            if ($targetSkus !== null) {
                if ($targetSkus->isEmpty()) return [];
                $snQuery->whereIn('item_no', $targetSkus);
            }

            $snSamples = $snQuery->limit(3)->get();
            foreach ($snSamples as $sn) {
                $name = $sn->product_name ?: ($sn->productAccurate->name ?? $sn->item_no);
                if ($name) {
                    $samples[] = ['name' => $name, 'type' => 'IMEI'];
                }
            }
        }

        if ($includeNonSn && count($samples) < 4) {
            $accQuery = ProductAccurate::where('business_unit_id', $buId)
                ->where('has_sn', false)
                ->whereHas('warehouseStocks', function ($q) {
                    $q->where('warehouse_id', $this->warehouseId)->where('stock', '>', 0);
                });

            if ($targetSkus !== null) {
                if ($targetSkus->isEmpty()) return $samples;
                $accQuery->whereIn('item_no', $targetSkus);
            }

            $accSamples = $accQuery->limit(4 - count($samples))->pluck('name')->toArray();
            foreach ($accSamples as $name) {
                if ($name) {
                    $samples[] = ['name' => $name, 'type' => 'Aksesoris'];
                }
            }
        }

        return $samples;
    }

    /**
     * Eksekusi Pembukaan Sesi Stock Opname Baru
     */
    public function startOpname()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();

        if (!$this->branchId || !$this->warehouseId) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Cabang atau Gudang belum terdefinisi untuk akun Anda.', type: 'warning');
            return;
        }

        // Proteksi: cegah pembuatan sesi ganda jika sudah ada sesi yang masih COUNTING
        $existingActive = StockOpname::where('warehouse_id', $this->warehouseId)
            ->where('status', 'COUNTING')
            ->latest()
            ->first();

        if ($existingActive) {
            $this->dispatch('toast', title: 'Sesi Sudah Berjalan', message: "Terdapat sesi opname aktif ({$existingActive->opname_number}) di cabang ini. Anda dialihkan untuk bergabung scan bersama.", type: 'warning');
            return $this->redirectRoute('zoffline.stock-opname.count', $existingActive->id, navigate: true);
        }

        // Cek apakah ada barang terdaftar di cabang ini
        if ($this->totalAvailableSns === 0 && $this->totalNonSerialSkus === 0) {
            $this->dispatch('toast', title: 'Stok Kosong', message: 'Tidak ada stok barang terdaftar di gudang cabang ini untuk kombinasi filter yang dipilih.', type: 'error');
            return;
        }

        $targetSkus = $this->getFilteredTargetSkus();
        $includeSn = in_array($this->itemType, ['ALL', 'SERIALIZED_ONLY']);
        $includeNonSn = in_array($this->itemType, ['ALL', 'NON_SERIALIZED_ONLY']);

        // Tentukan tipe yang dicatat di database
        $savedType = $this->itemType;
        if ($this->itemType === 'ALL') {
            if ($this->brandFilter && !$this->categoryFilter && !$this->projectFilter) {
                $savedType = 'BRAND';
            } elseif ($this->categoryFilter && !$this->brandFilter && !$this->projectFilter) {
                $savedType = 'CATEGORY';
            } elseif ($this->projectFilter && !$this->brandFilter && !$this->categoryFilter) {
                $savedType = 'PROYEK';
            } else {
                $savedType = 'ALL';
            }
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
                'type'             => $savedType,
                'brand_filter'     => $this->brandFilter ?: null,
                'project_filter'   => $this->projectFilter ?: null,
                'category_filter'  => $this->categoryFilter ?: null,
                'start_time'       => now(),
                'notes'            => $this->notes,
            ]);

            // 3. Snapshot Barang Serialized (IMEI / Handphone)
            if ($includeSn) {
                $snQuery = ProductSerialNumber::with(['productAccurate', 'vendor'])
                    ->where('warehouse_id', $this->warehouseId)
                    ->where('business_unit_id', $buId)
                    ->where('status', 'Available');

                if ($targetSkus !== null) {
                    $snQuery->whereIn('item_no', $targetSkus);
                }

                $availableSns = $snQuery->get();
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
                        'physical_qty'     => 0,
                        'difference_qty'   => -$systemQty,
                        'unit_cost'        => $avgHpp,
                        'difference_value' => -($systemQty * $avgHpp),
                    ]);

                    // Snapshot SN ke status MISSING (belum discan)
                    $serialInserts = [];
                    foreach ($sns as $sn) {
                        $serialInserts[] = [
                            'stock_opname_id'      => $opname->id,
                            'stock_opname_item_id' => $opnameItem->id,
                            'item_no'              => $itemNo,
                            'serial_number'        => $sn->serial_number,
                            'status'               => 'MISSING',
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
            if ($includeNonSn) {
                $accQuery = ProductAccurate::with(['warehouseStocks' => function ($q) {
                    $q->where('warehouse_id', $this->warehouseId);
                }])
                    ->where('business_unit_id', $buId)
                    ->where('has_sn', false);

                if ($targetSkus !== null) {
                    $accQuery->whereIn('item_no', $targetSkus);
                }

                $accStocks = $accQuery->get();

                foreach ($accStocks as $prod) {
                    $whStock = $prod->warehouseStocks->first();
                    $systemQty = $whStock ? (int) $whStock->stock : 0;

                    // Catat hanya jika ada stok fisik buku toko > 0 di cabang ini
                    if ($systemQty > 0) {
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
            $this->dispatch('toast', title: 'Gagal Memulai Sesi', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Filtered list properties untuk live search
     */
    public function getFilteredBrandListProperty()
    {
        if (empty($this->searchBrand)) {
            return $this->brandList;
        }
        return array_values(array_filter($this->brandList, fn($b) => stripos($b, $this->searchBrand) !== false));
    }

    public function getFilteredCategoryListProperty()
    {
        if (empty($this->searchCategory)) {
            return $this->categoryList;
        }
        return array_values(array_filter($this->categoryList, fn($c) => stripos($c, $this->searchCategory) !== false));
    }

    public function getFilteredProjectListProperty()
    {
        if (empty($this->searchProject)) {
            return $this->projectList;
        }
        return array_values(array_filter($this->projectList, fn($p) => stripos($p, $this->searchProject) !== false));
    }

    public function render()
    {
        return view('livewire.zoffline.stock-opname.create');
    }
}
