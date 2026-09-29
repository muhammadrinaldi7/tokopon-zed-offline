<?php

namespace App\Livewire\Admin\Inventory\StockAdjustment;

use App\Models\BusinessUnit;
use App\Models\BusinessUnitProject;
use App\Models\ProductAccurate;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\ApprovalService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.z', ['title' => 'Pemakaian Inventaris Toko'])]
class Index extends Component
{
    use WithPagination;

    // Filter & Search
    public $search = '';
    public $filterStatus = 'ALL';
    public $filterType = 'ALL';
    public $filterCategory = 'ALL';
    public $filterWarehouseId = 'ALL';
    public $dateFrom = '';
    public $dateTo = '';

    // Modal State
    public $showCreateModal = false;
    public $showDetailModal = false;
    public $selectedAdjustment = null;

    // Header State Form
    public $business_unit_id;
    public $warehouse_id;
    public $default_adjustment_type = 'OUT'; // 'OUT' (Pengurangan) atau 'IN' (Penambahan)
    public $reason_category = 'PEMELIHARAAN_INVENTARIS';
    public $notes = '';
    public $accurate_account_no = '50.03.005'; // Default COA Penyesuaian/Beban

    // Keranjang Item Penyesuaian (Multi Item)
    public $items = [];

    // Form Input Tambah Item Sementara (Temporary fields)
    public $searchItem = '';
    public $itemSearchResults = [];
    public $selectedItem = null;
    public $temp_item_no = '';
    public $temp_product_name = '';
    public $temp_adjustment_type = 'OUT';
    public $temp_quantity = 1;
    public $temp_unit_cost = 0;
    public $temp_current_stock = 0;
    public $temp_has_sn = false;
    public $temp_proyek = '';
    public $temp_project_no = '';
    public $temp_item_notes = '';

    // Barang Tujuan Alokasi (OPSIONAL untuk item, bisa multiple sampai dengan temp_quantity)
    public $searchTargetItem = '';
    public $targetItemSearchResults = [];
    public $selectedTargetItem = null;
    public $temp_target_item_no = null;
    public $temp_target_product_name = null;
    public $temp_target_serial_number = null; // Opsional SN / IMEI display
    public $temp_target_quantity = 1;
    public $temp_target_items = []; // Daftar alokasi unit tujuan: [['item_no' => ..., 'product_name' => ..., 'serial_number' => ..., 'quantity' => 1], ...]

    // Serial numbers untuk barang ber-SN sementara
    public $temp_serial_numbers = [];
    public $temp_input_serial_number = '';

    public function mount()
    {
        $user = Auth::user();
        $this->business_unit_id = $user->getActiveBusinessUnitId() ?? 1;

        // Default gudang berdasarkan penugasan user
        $this->setDefaultWarehouse();
    }

    public function setDefaultWarehouse()
    {
        $user = Auth::user();

        if (!empty($user->warehouse_id)) {
            $this->warehouse_id = $user->warehouse_id;
            return;
        }

        $firstWarehouse = Warehouse::where('business_unit_id', $this->business_unit_id)->first();
        if ($firstWarehouse) {
            $this->warehouse_id = $firstWarehouse->id;
        } else {
            $anyWarehouse = Warehouse::first();
            $this->warehouse_id = $anyWarehouse?->id;
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->setDefaultWarehouse();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->default_adjustment_type = 'OUT';
        $this->reason_category = 'PEMELIHARAAN_INVENTARIS';
        $this->notes = '';
        $this->accurate_account_no = '5101';
        $this->items = [];

        $this->resetTempItemInput();
    }

    public function resetTempItemInput()
    {
        $this->searchItem = '';
        $this->itemSearchResults = [];
        $this->selectedItem = null;
        $this->temp_item_no = '';
        $this->temp_product_name = '';
        $this->temp_adjustment_type = $this->default_adjustment_type;
        $this->temp_quantity = 1;
        $this->temp_unit_cost = 0;
        $this->temp_current_stock = 0;
        $this->temp_has_sn = false;
        $this->temp_proyek = '';
        $this->temp_project_no = '';
        $this->temp_item_notes = '';

        $this->clearTempTargetInput();
        $this->temp_target_items = [];

        $this->temp_serial_numbers = [];
        $this->temp_input_serial_number = '';
    }

    public function clearTempTargetInput()
    {
        $this->searchTargetItem = '';
        $this->targetItemSearchResults = [];
        $this->selectedTargetItem = null;
        $this->temp_target_item_no = null;
        $this->temp_target_product_name = null;
        $this->temp_target_serial_number = null;
        $this->temp_target_quantity = 1;
    }

    public function updatedDefaultAdjustmentType($value)
    {
        $this->temp_adjustment_type = $value;
    }

    // --- PENCARIAN SKU BARANG UTAMA ---
    public function updatedSearchItem($value)
    {
        $term = trim($value);
        if (strlen($term) < 2) {
            $this->itemSearchResults = [];
            return;
        }

        $this->itemSearchResults = ProductAccurate::where('business_unit_id', $this->business_unit_id)
            ->where(function ($q) use ($term) {
                $q->where('item_no', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get(['id', 'item_no', 'name', 'stock', 'proyek', 'has_sn', 'base_cost'])
            ->toArray();
    }

    public function selectItem($id)
    {
        $item = ProductAccurate::find($id);
        if (!$item) return;

        $this->selectedItem = $item;
        $this->temp_item_no = $item->item_no;
        $this->temp_product_name = $item->name;
        $this->temp_has_sn = (bool) $item->has_sn;
        $this->temp_proyek = $item->proyek ?? '';
        $this->temp_unit_cost = (float) ($item->base_cost ?? 0);

        // Ambil project_no dari BusinessUnitProject
        $this->temp_project_no = BusinessUnitProject::getProjectNoByBusinessUnit(
            $this->business_unit_id,
            $this->temp_proyek,
            $this->temp_proyek ?: null
        ) ?? '';

        // Ambil stok terkini di gudang yang dipilih
        $this->fetchCurrentStock();

        $this->searchItem = $item->name . ' (' . $item->item_no . ')';
        $this->itemSearchResults = [];
    }

    public function updatedWarehouseId()
    {
        $this->fetchCurrentStock();
    }

    protected function fetchCurrentStock()
    {
        if (!$this->temp_item_no || !$this->warehouse_id) {
            $this->temp_current_stock = 0;
            return;
        }

        $variant = ProductVariant::where('sku', $this->temp_item_no)->first();
        if ($variant) {
            $ws = WarehouseStock::where('warehouse_id', $this->warehouse_id)
                ->where('variant_type', get_class($variant))
                ->where('variant_id', $variant->id)
                ->first();
            $this->temp_current_stock = $ws ? $ws->stock : 0;
        } else {
            $item = ProductAccurate::where('item_no', $this->temp_item_no)->first();
            $this->temp_current_stock = $item ? $item->stock : 0;
        }
    }

    // --- PENCARIAN SKU TUJUAN ALOKASI (OPSIONAL) ---
    public function updatedSearchTargetItem($value)
    {
        $term = trim($value);
        if (strlen($term) < 2) {
            $this->targetItemSearchResults = [];
            return;
        }

        $this->targetItemSearchResults = ProductAccurate::where('business_unit_id', $this->business_unit_id)
            ->where(function ($q) use ($term) {
                $q->where('item_no', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get(['id', 'item_no', 'name', 'proyek'])
            ->toArray();
    }

    public function selectTargetItem($id)
    {
        $item = ProductAccurate::find($id);
        if (!$item) return;

        $this->selectedTargetItem = $item;
        $this->temp_target_item_no = $item->item_no;
        $this->temp_target_product_name = $item->name;
        $this->searchTargetItem = $item->name . ' (' . $item->item_no . ')';
        $this->targetItemSearchResults = [];

        // Auto-set kuantiti alokasi ke sisa kuantiti yang belum dialokasikan (minimal 1)
        $allocated = array_sum(array_column($this->temp_target_items, 'quantity'));
        $remaining = max(1, (int) $this->temp_quantity - $allocated);
        $this->temp_target_quantity = 1;
    }

    public function addTempTargetItem()
    {
        if (empty($this->temp_target_item_no) || empty($this->temp_target_product_name)) {
            $this->dispatch('toast', title: 'Pilih Unit Display', message: 'Silakan cari dan pilih unit HP display terlebih dahulu.', type: 'warning');
            return;
        }

        $targetQty = (int) $this->temp_target_quantity;
        if ($targetQty < 1) {
            $this->dispatch('toast', title: 'Jumlah Tidak Valid', message: 'Kuantiti alokasi minimal 1 unit.', type: 'error');
            return;
        }

        $itemQty = (int) $this->temp_quantity;
        $currentAllocated = array_sum(array_column($this->temp_target_items, 'quantity'));
        if (($currentAllocated + $targetQty) > $itemQty) {
            $this->dispatch('toast', title: 'Melebihi Kuantiti', message: "Total alokasi (" . ($currentAllocated + $targetQty) . " unit) melebihi kuantiti barang yang disesuaikan ({$itemQty} unit).", type: 'error');
            return;
        }

        $this->temp_target_items[] = [
            'item_no'        => $this->temp_target_item_no,
            'product_name'   => $this->temp_target_product_name,
            'serial_number'  => $this->temp_target_serial_number ? trim($this->temp_target_serial_number) : null,
            'quantity'       => $targetQty,
        ];

        $unitName = $this->temp_target_product_name;
        $this->clearTempTargetInput();
        $this->dispatch('toast', title: 'Unit Alokasi Ditambahkan', message: "{$targetQty}x {$unitName} ditambahkan ke alokasi barang ini.", type: 'success');
    }

    public function removeTempTargetItem($index)
    {
        if (isset($this->temp_target_items[$index])) {
            $unitName = $this->temp_target_items[$index]['product_name'];
            unset($this->temp_target_items[$index]);
            $this->temp_target_items = array_values($this->temp_target_items);
            $this->dispatch('toast', title: 'Unit Alokasi Dihapus', message: "{$unitName} dihapus dari alokasi barang.", type: 'info');
        }
    }

    public function clearTempTargetItem()
    {
        $this->clearTempTargetInput();
    }

    // --- SERIAL NUMBERS ITEM SEMENTARA ---
    public function addTempSerialNumber()
    {
        $sn = trim($this->temp_input_serial_number);
        if (empty($sn)) return;

        if (in_array($sn, $this->temp_serial_numbers)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Serial Number ini sudah ditambahkan ke daftar item ini.', type: 'warning');
            return;
        }

        $this->temp_serial_numbers[] = $sn;
        $this->temp_input_serial_number = '';
    }

    public function removeTempSerialNumber($index)
    {
        if (isset($this->temp_serial_numbers[$index])) {
            unset($this->temp_serial_numbers[$index]);
            $this->temp_serial_numbers = array_values($this->temp_serial_numbers);
        }
    }

    // --- TAMBAH ITEM KE KERANJANG PENYESUAIAN ---
    public function addItemToList()
    {
        if (empty($this->temp_item_no) || empty($this->temp_product_name)) {
            $this->dispatch('toast', title: 'Pilih Barang', message: 'Silakan cari dan pilih barang terlebih dahulu.', type: 'error');
            return;
        }

        $qty = (int) $this->temp_quantity;
        if ($qty < 1) {
            $this->dispatch('toast', title: 'Jumlah Tidak Valid', message: 'Jumlah kuantiti penyesuaian minimal 1 unit.', type: 'error');
            return;
        }

        if ($this->temp_has_sn && $this->temp_adjustment_type === 'OUT' && count($this->temp_serial_numbers) < $qty) {
            $this->dispatch('toast', title: 'Serial Number Kurang', message: "Barang ini memiliki SN. Silakan masukkan {$qty} serial number.", type: 'warning');
            return;
        }

        // Jika user telah memilih target item di input tapi belum klik '+ Tambah Unit Alokasi', otomatis masukkan jika kuota mencukupi
        if (!empty($this->temp_target_item_no) && !empty($this->temp_target_product_name)) {
            $currentAllocated = array_sum(array_column($this->temp_target_items, 'quantity'));
            $tQty = (int) $this->temp_target_quantity;
            if ($tQty < 1) $tQty = 1;
            if (($currentAllocated + $tQty) <= $qty) {
                $this->temp_target_items[] = [
                    'item_no'        => $this->temp_target_item_no,
                    'product_name'   => $this->temp_target_product_name,
                    'serial_number'  => $this->temp_target_serial_number ? trim($this->temp_target_serial_number) : null,
                    'quantity'       => $tQty,
                ];
            }
        }

        // Validasi total target items tidak melebihi kuantiti penyesuaian
        $totalAllocated = array_sum(array_column($this->temp_target_items, 'quantity'));
        if ($totalAllocated > $qty) {
            $this->dispatch('toast', title: 'Alokasi Melebihi Kuantiti', message: "Total alokasi unit display ({$totalAllocated}) melebihi kuantiti penyesuaian ({$qty}).", type: 'error');
            return;
        }

        // Tentukan nilai backward compatible untuk header / single summary
        $firstTarget = !empty($this->temp_target_items) ? $this->temp_target_items[0] : null;
        $targetCount = count($this->temp_target_items);
        $targetItemNo = $firstTarget ? $firstTarget['item_no'] : null;
        $targetProductName = null;
        if ($firstTarget) {
            $targetProductName = $targetCount > 1
                ? "{$firstTarget['product_name']} (+ " . ($targetCount - 1) . " unit lain)"
                : $firstTarget['product_name'];
        }
        $targetSerialNumber = $firstTarget ? ($firstTarget['serial_number'] ?? null) : null;

        // Tambah baris baru
        $this->items[] = [
            'item_no'              => $this->temp_item_no,
            'product_name'         => $this->temp_product_name,
            'adjustment_type'      => $this->temp_adjustment_type,
            'quantity'             => $qty,
            'unit_cost'            => $this->temp_unit_cost,
            'current_stock'        => $this->temp_current_stock,
            'has_sn'               => $this->temp_has_sn,
            'proyek'               => $this->temp_proyek,
            'project_no'           => $this->temp_project_no,
            'target_item_no'       => $targetItemNo,
            'target_product_name'  => $targetProductName,
            'target_serial_number' => $targetSerialNumber,
            'target_items'         => $this->temp_target_items,
            'serial_numbers'       => $this->temp_serial_numbers,
            'item_notes'           => $this->temp_item_notes,
        ];
        $this->dispatch('toast', title: 'Item Ditambahkan', message: "{$this->temp_product_name} ({$qty} pcs) berhasil dimasukkan ke daftar.", type: 'success');

        // Reset input sementara untuk barang berikutnya
        $this->resetTempItemInput();
    }

    public function removeItemFromList($index)
    {
        if (isset($this->items[$index])) {
            $name = $this->items[$index]['product_name'];
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->dispatch('toast', title: 'Item Dihapus', message: "{$name} dihapus dari daftar penyesuaian.", type: 'info');
        }
    }

    public function updateItemQuantity($index, $qty)
    {
        $qty = (int) $qty;
        if (isset($this->items[$index]) && $qty >= 1) {
            $this->items[$index]['quantity'] = $qty;
        }
    }

    // --- SUBMIT PENYESUAIAN STOK (HEADER + DETAIL) ---
    public function submitAdjustment()
    {
        $this->validate([
            'warehouse_id'        => 'required|exists:warehouses,id',
            'reason_category'     => 'required|string',
            'notes'               => 'nullable|string|max:1000',
            'accurate_account_no' => 'nullable|string',
        ], [
            'warehouse_id.required'    => 'Pilih gudang penyesuaian.',
            'reason_category.required' => 'Pilih kategori alasan penyesuaian.',
        ]);

        if (empty($this->items)) {
            $this->dispatch('toast', title: 'Daftar Barang Masih Kosong', message: 'Silakan tambahkan minimal 1 barang ke dalam daftar sebelum mengajukan.', type: 'warning');
            return;
        }

        DB::beginTransaction();
        try {
            $adjNumber = StockAdjustment::generateAdjustmentNumber();
            $wh = Warehouse::find($this->warehouse_id);

            $totalItems = count($this->items);
            $totalQty = array_sum(array_column($this->items, 'quantity'));

            $firstItem = $this->items[0];
            $summaryProductName = $totalItems === 1
                ? $firstItem['product_name']
                : $firstItem['product_name'] . ' (+' . ($totalItems - 1) . ' item lainnya)';

            // 1. Buat Record Header
            $adjustment = StockAdjustment::create([
                'adjustment_number'    => $adjNumber,
                'business_unit_id'     => $this->business_unit_id,
                'branch_id'            => Auth::user()->branch_id,
                'warehouse_id'         => $this->warehouse_id,
                'warehouse_name'       => $wh?->name ?? 'Gudang Utama',
                'adjustment_type'      => $this->default_adjustment_type,
                'total_items'          => $totalItems,
                'total_quantity'       => $totalQty,
                'item_no'              => $firstItem['item_no'],
                'product_name'         => $summaryProductName,
                'quantity'             => $totalQty,
                'unit_cost'            => $firstItem['unit_cost'] ?? 0,
                'proyek'               => $firstItem['proyek'] ?? null,
                'project_no'           => $firstItem['project_no'] ?? null,
                'target_item_no'       => $firstItem['target_item_no'] ?? null,
                'target_product_name'  => $firstItem['target_product_name'] ?? null,
                'target_serial_number' => $firstItem['target_serial_number'] ?? null,
                'reason_category'      => $this->reason_category,
                'notes'                => $this->notes,
                'accurate_account_no'  => $this->accurate_account_no ?: '5101',
                'status'               => 'PENDING',
                'requested_by'         => Auth::id(),
            ]);

            // 2. Buat Record Detail Items
            foreach ($this->items as $it) {
                StockAdjustmentItem::create([
                    'stock_adjustment_id'  => $adjustment->id,
                    'adjustment_type'      => $it['adjustment_type'],
                    'item_no'              => $it['item_no'],
                    'product_name'         => $it['product_name'],
                    'quantity'             => $it['quantity'],
                    'unit_cost'            => $it['unit_cost'],
                    'proyek'               => $it['proyek'],
                    'project_no'           => $it['project_no'],
                    'target_item_no'       => $it['target_item_no'],
                    'target_product_name'  => $it['target_product_name'],
                    'target_serial_number' => $it['target_serial_number'],
                    'target_items'         => !empty($it['target_items']) ? $it['target_items'] : null,
                    'serial_numbers'       => !empty($it['serial_numbers']) ? $it['serial_numbers'] : null,
                    'item_notes'           => $it['item_notes'] ?? null,
                ]);
            }

            // 3. Susun teks alasan dan payload untuk Approval Center
            $reasonCategoryLabel = $this->reason_category === 'PEMELIHARAAN_INVENTARIS'
                ? 'Pemakaian / Pemeliharaan Inventaris'
                : str_replace('_', ' ', $this->reason_category);
            $reasonText = "[{$reasonCategoryLabel}] " . ($this->notes ?: 'Pemakaian Operasional Toko');
            $reasonText .= " ({$totalItems} jenis item, Total: {$totalQty} pcs)";

            $itemsPayload = array_map(function ($it) {
                return [
                    'item_no'              => $it['item_no'],
                    'product_name'         => $it['product_name'],
                    'quantity'             => $it['quantity'],
                    'adjustment_type'      => 'OUT',
                    'unit_cost'            => $it['unit_cost'],
                    'proyek'               => $it['proyek'],
                    'project_no'           => $it['project_no'],
                    'target_item_no'       => $it['target_item_no'],
                    'target_product_name'  => $it['target_product_name'],
                    'target_serial_number' => $it['target_serial_number'],
                    'target_items'         => $it['target_items'] ?? [],
                ];
            }, $this->items);

            $approvalService = app(ApprovalService::class);
            $approvalService->createRequest([
                'approvable'       => $adjustment,
                'approvable_type'  => StockAdjustment::class,
                'approvable_id'    => $adjustment->id,
                'request_type'     => 'STOCK_ADJUSTMENT',
                'requested_by'     => Auth::id(),
                'business_unit_id' => $this->business_unit_id,
                'branch_id'        => Auth::user()->branch_id,
                'reason'           => $reasonText,
                'payload'          => [
                    'adjustment_number' => $adjustment->adjustment_number,
                    'warehouse_name'    => $adjustment->warehouse_name,
                    'reason_category'   => $adjustment->reason_category,
                    'total_items'       => $totalItems,
                    'total_quantity'    => $totalQty,
                    'items'             => $itemsPayload,
                    'notes'             => $adjustment->notes,
                    'created_at'        => now()->toIso8601String(),
                ],
            ]);

            DB::commit();

            $this->closeCreateModal();
            $this->dispatch('toast', title: 'Berhasil Diajukan', message: "Pengajuan pemakaian inventaris {$adjNumber} ({$totalItems} barang) berhasil dikirim untuk approval.", type: 'success');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Gagal membuat StockAdjustment: " . $e->getMessage());
            $this->dispatch('toast', title: 'Terjadi Kesalahan', message: $e->getMessage(), type: 'error');
        }
    }

    public function viewDetail($id)
    {
        /** @var StockAdjustment|null $selected */
        $selected = StockAdjustment::with([
            'items',
            'warehouse',
            'branch',
            'requestedBy',
            'approvedBy',
            'approvalRequest.histories.actedBy'
        ])->where('id', $id)->first();

        $this->selectedAdjustment = $selected;
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedAdjustment = null;
    }

    public function retrySync($id)
    {
        /** @var StockAdjustment|null $adjustment */
        $adjustment = StockAdjustment::with(['items', 'warehouse', 'branch', 'businessUnit'])->where('id', $id)->first();
        if (!$adjustment) return;

        if ($adjustment->status !== 'FAILED_SYNC' && $adjustment->status !== 'APPROVED') {
            $this->dispatch('toast', title: 'Perhatian', message: 'Hanya transaksi yang gagal sinkron atau sudah disetujui yang dapat disinkronkan ulang.', type: 'warning');
            return;
        }

        try {
            $notesFull = "[{$adjustment->reason_category}] " . ($adjustment->notes ?: 'Penyesuaian Stok');

            $targetSummaries = [];
            foreach ($adjustment->items as $item) {
                $targetsList = $item->target_items_list;
                if (!empty($targetsList)) {
                    $tDesc = [];
                    foreach ($targetsList as $t) {
                        $qtyStr = ($t['quantity'] ?? 1) > 1 ? ($t['quantity'] . 'x ') : '';
                        $tDesc[] = "{$qtyStr}" . ($t['product_name'] ?? $t['item_no']);
                    }
                    $targetSummaries[] = "{$item->item_no} -> (" . implode(', ', $tDesc) . ")";
                } elseif (!empty($item->target_item_no)) {
                    $targetSummaries[] = "{$item->item_no} -> {$item->target_item_no}";
                }
            }
            if (!empty($targetSummaries)) {
                $notesFull .= " (Tujuan: " . implode(', ', array_slice($targetSummaries, 0, 5)) . ")";
            }

            $warehouseName = $adjustment->warehouse?->name ?? ($adjustment->warehouse_name ?? 'UTAMA');
            $detailItems = [];

            if ($adjustment->items->isNotEmpty()) {
                foreach ($adjustment->items as $item) {
                    $detailRow = [
                        'itemNo'             => $item->item_no,
                        'itemAdjustmentType' => $item->adjustment_type === 'OUT' ? 'ADJUSTMENT_OUT' : 'ADJUSTMENT_IN',
                        'quantity'           => (float) $item->quantity,
                        'warehouseName'      => $warehouseName,
                    ];
                    if (!empty($item->project_no)) {
                        $detailRow['projectNo'] = $item->project_no;
                    }
                    if (!empty($item->unit_cost) && $item->unit_cost > 0) {
                        $detailRow['unitCost'] = (float) $item->unit_cost;
                    }
                    $detailItems[] = $detailRow;
                }
            } else {
                $detailItems[] = [
                    'itemNo'             => $adjustment->item_no,
                    'itemAdjustmentType' => $adjustment->adjustment_type === 'OUT' ? 'ADJUSTMENT_OUT' : 'ADJUSTMENT_IN',
                    'quantity'           => (float) $adjustment->quantity,
                    'warehouseName'      => $warehouseName,
                ];
            }

            $payload = [
                'transDate'           => now()->format('d/m/Y'),
                'adjustmentAccountNo' => $adjustment->accurate_account_no ?: '5101',
                'description'         => mb_substr($notesFull, 0, 250),
                'branchName'          => $adjustment->branch?->name,
                'detailItem'          => $detailItems,
            ];

            $buCode = $adjustment->businessUnit?->code ?? 'syihab';
            $accurateService = app(\App\Services\AccurateService::class);
            $response = $accurateService->postItemAdjustment($payload, $buCode);

            $accurateNo = null;
            if (is_array($response)) {
                $accurateNo = $response['number'] ?? ($response['r']['number'] ?? ($response['d']['number'] ?? ($response['id'] ?? null)));
            }

            $adjustment->update([
                'status'                 => 'SYNCED',
                'synced_at'              => now(),
                'accurate_adjustment_no' => (string) ($accurateNo ?: 'SYNCED-' . now()->timestamp),
                'sync_error'             => null,
            ]);

            $this->dispatch('toast', title: 'Sinkronisasi Berhasil', message: "Penyesuaian stok berhasil disinkronkan ke Accurate dengan nomor: {$accurateNo}", type: 'success');
        } catch (\Throwable $e) {
            $adjustment->update([
                'status'     => 'FAILED_SYNC',
                'sync_error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', title: 'Gagal Sinkron', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $warehouses = Warehouse::where('business_unit_id', $this->business_unit_id)->get();

        /** @var \Illuminate\Pagination\LengthAwarePaginator<StockAdjustment> $adjustments */
        $adjustments = StockAdjustment::with(['items', 'warehouse', 'branch', 'requestedBy', 'approvedBy'])
            ->where('business_unit_id', $this->business_unit_id)
            ->when($this->search, function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($sq) use ($term) {
                    $sq->where('adjustment_number', 'like', $term)
                        ->orWhere('item_no', 'like', $term)
                        ->orWhere('product_name', 'like', $term)
                        ->orWhere('target_item_no', 'like', $term)
                        ->orWhere('target_product_name', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('items', function ($iq) use ($term) {
                            $iq->where('item_no', 'like', $term)
                                ->orWhere('product_name', 'like', $term)
                                ->orWhere('target_item_no', 'like', $term)
                                ->orWhere('target_product_name', 'like', $term);
                        });
                });
            })
            ->when($this->filterStatus !== 'ALL', function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterType !== 'ALL', function ($q) {
                $q->where('adjustment_type', $this->filterType);
            })
            ->when($this->filterCategory !== 'ALL', function ($q) {
                $q->where('reason_category', $this->filterCategory);
            })
            ->when($this->filterWarehouseId !== 'ALL', function ($q) {
                $q->where('warehouse_id', $this->filterWarehouseId);
            })
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('components.admin.inventory.stock-adjustment.index', [
            'adjustments' => $adjustments,
            'warehouses'  => $warehouses,
        ]);
    }
}
