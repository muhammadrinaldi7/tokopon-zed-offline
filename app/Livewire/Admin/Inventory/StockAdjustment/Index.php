<?php

namespace App\Livewire\Admin\Inventory\StockAdjustment;

use App\Models\BusinessUnit;
use App\Models\BusinessUnitProject;
use App\Models\ProductAccurate;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\ApprovalService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Penyesuaian Stok Persediaan'])]
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

    // Form State for New Adjustment
    public $business_unit_id;
    public $warehouse_id;
    public $adjustment_type = 'OUT'; // 'OUT' (Pengurangan) atau 'IN' (Penambahan)

    // Barang Utama (Disesuaikan Stoknya)
    public $searchItem = '';
    public $itemSearchResults = [];
    public $selectedItem = null;
    public $item_no = '';
    public $product_name = '';
    public $quantity = 1;
    public $current_stock = 0;
    public $has_sn = false;
    public $proyek = '';
    public $project_no = '';

    // Barang Tujuan Alokasi (OPSIONAL)
    public $searchTargetItem = '';
    public $targetItemSearchResults = [];
    public $selectedTargetItem = null;
    public $target_item_no = null;
    public $target_product_name = null;
    public $target_serial_number = null; // Opsional SN / IMEI display

    // Serial numbers untuk barang ber-SN
    public $serial_numbers = [];
    public $input_serial_number = '';

    // Kategori Alasan & Accurate
    public $reason_category = 'PEMELIHARAAN_INVENTARIS';
    public $notes = '';
    public $accurate_account_no = '5101'; // Default COA Penyesuaian/Beban

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

        // 1. Ambil dari penugasan gudang user jika ada
        if (!empty($user->warehouse_id)) {
            $this->warehouse_id = $user->warehouse_id;
            return;
        }

        // 2. Fallback ke gudang pertama di Business Unit yang aktif
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
        $this->adjustment_type = 'OUT';
        $this->searchItem = '';
        $this->itemSearchResults = [];
        $this->selectedItem = null;
        $this->item_no = '';
        $this->product_name = '';
        $this->quantity = 1;
        $this->current_stock = 0;
        $this->has_sn = false;
        $this->proyek = '';
        $this->project_no = '';

        $this->searchTargetItem = '';
        $this->targetItemSearchResults = [];
        $this->selectedTargetItem = null;
        $this->target_item_no = null;
        $this->target_product_name = null;
        $this->target_serial_number = null;

        $this->serial_numbers = [];
        $this->input_serial_number = '';
        $this->reason_category = 'PEMELIHARAAN_INVENTARIS';
        $this->notes = '';
        $this->accurate_account_no = '5101';
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
        $this->item_no = $item->item_no;
        $this->product_name = $item->name;
        $this->has_sn = (bool) $item->has_sn;
        $this->proyek = $item->proyek ?? '';

        // Ambil project_no dari BusinessUnitProject
        $this->project_no = BusinessUnitProject::getProjectNoByBusinessUnit(
            $this->business_unit_id,
            $this->proyek,
            $this->proyek ?: null
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
        if (!$this->item_no || !$this->warehouse_id) {
            $this->current_stock = 0;
            return;
        }

        // Cari variant atau product accurate
        $variant = ProductVariant::where('sku', $this->item_no)->first();
        if ($variant) {
            $ws = WarehouseStock::where('warehouse_id', $this->warehouse_id)
                ->where('variant_type', get_class($variant))
                ->where('variant_id', $variant->id)
                ->first();
            $this->current_stock = $ws ? $ws->stock : 0;
        } else {
            $item = ProductAccurate::where('item_no', $this->item_no)->first();
            $this->current_stock = $item ? $item->stock : 0;
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
        $this->target_item_no = $item->item_no;
        $this->target_product_name = $item->name;
        $this->searchTargetItem = $item->name . ' (' . $item->item_no . ')';
        $this->targetItemSearchResults = [];
    }

    public function clearTargetItem()
    {
        $this->selectedTargetItem = null;
        $this->target_item_no = null;
        $this->target_product_name = null;
        $this->target_serial_number = null;
        $this->searchTargetItem = '';
        $this->targetItemSearchResults = [];
    }

    // --- SERIAL NUMBERS ---
    public function addSerialNumber()
    {
        $sn = trim($this->input_serial_number);
        if (empty($sn)) return;

        if (in_array($sn, $this->serial_numbers)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Serial Number ini sudah ditambahkan ke daftar.', type: 'warning');
            return;
        }

        $this->serial_numbers[] = $sn;
        $this->input_serial_number = '';
    }

    public function removeSerialNumber($index)
    {
        if (isset($this->serial_numbers[$index])) {
            unset($this->serial_numbers[$index]);
            $this->serial_numbers = array_values($this->serial_numbers);
        }
    }

    // --- SUBMIT PENYESUAIAN STOK ---
    public function submitAdjustment()
    {
        $this->validate([
            'warehouse_id'         => 'required|exists:warehouses,id',
            'adjustment_type'      => 'required|in:OUT,IN',
            'item_no'              => 'required|string',
            'product_name'         => 'required|string',
            'quantity'             => 'required|integer|min:1',
            'reason_category'      => 'required|string',
            'target_item_no'       => 'nullable|string',
            'target_product_name'  => 'nullable|string',
            'target_serial_number' => 'nullable|string',
            'notes'                => 'nullable|string|max:1000',
            'accurate_account_no'  => 'nullable|string',
        ], [
            'warehouse_id.required'    => 'Pilih gudang penyesuaian.',
            'item_no.required'         => 'Pilih barang/SKU yang akan disesuaikan.',
            'quantity.min'             => 'Jumlah penyesuaian minimal 1 unit.',
            'reason_category.required' => 'Pilih kategori alasan penyesuaian.',
        ]);

        if ($this->has_sn && count($this->serial_numbers) < $this->quantity) {
            $this->dispatch('toast', title: 'Serial Number Belum Lengkap', message: "Barang ini memiliki SN. Silakan masukkan {$this->quantity} serial number.", type: 'warning');
            return;
        }

        try {
            $adjNumber = StockAdjustment::generateAdjustmentNumber();
            $wh = Warehouse::find($this->warehouse_id);

            $adjustment = StockAdjustment::create([
                'adjustment_number'    => $adjNumber,
                'business_unit_id'     => $this->business_unit_id,
                'branch_id'            => Auth::user()->branch_id,
                'warehouse_id'         => $this->warehouse_id,
                'warehouse_name'       => $wh?->name ?? 'Gudang Utama',
                'adjustment_type'      => $this->adjustment_type,
                'item_no'              => $this->item_no,
                'product_name'         => $this->product_name,
                'quantity'             => $this->quantity,
                'unit_cost'            => $this->selectedItem?->base_cost ?? 0,
                'proyek'               => $this->proyek,
                'project_no'           => $this->project_no,
                'target_item_no'       => $this->target_item_no ?: null,
                'target_product_name'  => $this->target_product_name ?: null,
                'target_serial_number' => $this->target_serial_number ?: null,
                'reason_category'      => $this->reason_category,
                'notes'                => $this->notes,
                'serial_numbers'       => !empty($this->serial_numbers) ? $this->serial_numbers : null,
                'accurate_account_no'  => $this->accurate_account_no ?: '5101',
                'status'               => 'PENDING',
                'requested_by'         => Auth::id(),
            ]);

            // Kirim permohonan ke Approval Center
            $approvalService = app(ApprovalService::class);
            $reasonText = "[{$this->reason_category}] " . ($this->notes ?: 'Penyesuaian Stok');
            if ($this->target_item_no) {
                $reasonText .= " (Atas SKU Tujuan: {$this->target_item_no} - {$this->target_product_name})";
            }

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
                    'adjustment_number'   => $adjustment->adjustment_number,
                    'type'                => $adjustment->adjustment_type,
                    'item_no'             => $adjustment->item_no,
                    'product_name'        => $adjustment->product_name,
                    'quantity'            => $adjustment->quantity,
                    'proyek'              => $adjustment->proyek,
                    'project_no'          => $adjustment->project_no,
                    'target_item_no'      => $adjustment->target_item_no,
                    'target_product_name' => $adjustment->target_product_name,
                    'warehouse_name'      => $adjustment->warehouse_name,
                    'reason_category'     => $adjustment->reason_category,
                    'notes'               => $adjustment->notes,
                ],
            ]);

            $this->closeCreateModal();
            $this->dispatch('toast', title: 'Berhasil Diajukan', message: "Pengajuan penyesuaian stok {$adjustment->adjustment_number} berhasil dibuat dan menunggu persetujuan (approval).", type: 'success');
        } catch (\Throwable $e) {
            Log::error("Gagal submit StockAdjustment: " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Menyimpan', message: $e->getMessage(), type: 'error');
        }
    }

    // --- DETAIL & RETRY SYNC ---
    public function viewDetail($id)
    {
        $this->selectedAdjustment = StockAdjustment::with([
            'warehouse',
            'branch',
            'requestedBy',
            'approvedBy',
            'approvalRequest.histories.actedBy'
        ])->find($id);

        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedAdjustment = null;
    }

    public function retrySync($id)
    {
        $adjustment = StockAdjustment::find($id);
        if (!$adjustment) return;

        if ($adjustment->status !== 'FAILED_SYNC' && $adjustment->status !== 'APPROVED') {
            $this->dispatch('toast', title: 'Perhatian', message: 'Hanya transaksi yang gagal sinkron atau sudah disetujui yang dapat disinkronkan ulang.', type: 'warning');
            return;
        }

        try {
            $notesFull = "[{$adjustment->reason_category}] " . ($adjustment->notes ?: 'Penyesuaian Stok');
            if ($adjustment->target_item_no) {
                $notesFull .= " (Tujuan Alokasi: {$adjustment->target_item_no} - {$adjustment->target_product_name}";
                if ($adjustment->target_serial_number) {
                    $notesFull .= " [SN: {$adjustment->target_serial_number}]";
                }
                $notesFull .= ")";
            }

            $detailItem = [
                'itemNo'             => $adjustment->item_no,
                'itemAdjustmentType' => $adjustment->adjustment_type === 'OUT' ? 'ADJUSTMENT_OUT' : 'ADJUSTMENT_IN',
                'quantity'           => (float) $adjustment->quantity,
                'warehouseName'      => $adjustment->warehouse?->name ?? ($adjustment->warehouse_name ?? 'UTAMA'),
            ];

            if (!empty($adjustment->project_no)) {
                $detailItem['projectNo'] = $adjustment->project_no;
            }

            $payload = [
                'transDate'           => now()->format('d/m/Y'),
                'adjustmentAccountNo' => $adjustment->accurate_account_no ?: '5101',
                'description'         => $notesFull,
                'branchName'          => $adjustment->branch?->name,
                'detailItem'          => [$detailItem],
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

        $adjustments = StockAdjustment::with(['warehouse', 'branch', 'requestedBy', 'approvedBy'])
            ->where('business_unit_id', $this->business_unit_id)
            ->when($this->search, function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($sq) use ($term) {
                    $sq->where('adjustment_number', 'like', $term)
                       ->orWhere('item_no', 'like', $term)
                       ->orWhere('product_name', 'like', $term)
                       ->orWhere('target_item_no', 'like', $term)
                       ->orWhere('target_product_name', 'like', $term)
                       ->orWhere('notes', 'like', $term);
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
