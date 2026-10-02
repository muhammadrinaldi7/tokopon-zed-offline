<?php

namespace App\Livewire\Zoffline\Qc;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DeviceInspection;
use App\Models\BusinessUnit;
use App\Models\Branch;
use App\Models\Warranty;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('layouts.z')]
class ActivationList extends Component
{
    use WithPagination;

    #[Url]
    public $search = '';

    #[Url]
    public $statusFilter = 'all'; // all, active, inactive

    #[Url]
    public $businessUnitFilter = null;

    #[Url]
    public $branchFilter = 'all';

    #[Url]
    public $dateStart = null;
    
    #[Url]
    public $dateEnd = null;

    public $showQcModal = false;
    public $selectedInspection = null;

    // Modal Struk Nota Transaksi
    public $showReceiptModal = false;
    public $viewingOrder = null;

    // Modal Switch / Tautkan Garansi
    public $showSwitchModal = false;
    public $switchTargetSn = '';
    public $switchTargetOrderId = null;
    public $switchTargetOrderNumber = '';
    public $switchTargetProductName = '';
    public $switchTargetOrderItemId = null;

    // Data inspeksi QC lama yang ditemukan
    public $foundPreviousInspection = null;
    public $foundPreviousOrder = null;
    public $foundPreviousOrderItem = null;
    public $foundPreviousWarranties = [];

    // Form Pengajuan Switch
    public $switchReason = '';
    public $isSubmittingSwitch = false;

    public function mount()
    {
        $this->dateStart = $this->dateStart ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $this->dateEnd = $this->dateEnd ?? Carbon::now()->format('Y-m-d');

        if ($this->businessUnitFilter === null || $this->businessUnitFilter === '') {
            $userBuId = Auth::user()?->getActiveBusinessUnitId();
            $this->businessUnitFilter = $userBuId ? (string) $userBuId : 'all';
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedBusinessUnitFilter()
    {
        $this->branchFilter = 'all';
        $this->resetPage();
    }

    public function updatedBranchFilter()
    {
        $this->resetPage();
    }

    public function viewQc($inspectionId)
    {
        $this->selectedInspection = \App\Models\DeviceInspection::with('media', 'inspector')->find($inspectionId);
        if ($this->selectedInspection) {
            $this->showQcModal = true;
        }
    }
    
    public function closeQcModal()
    {
        $this->showQcModal = false;
        $this->selectedInspection = null;
    }

    /**
     * Membuka modal struk nota transaksi pesanan
     *
     * @param int $orderId
     * @return void
     */
    public function viewReceipt(int $orderId): void
    {
        $orderQuery = Order::with([
            'user.profile',
            'businessUnit',
            'branch',
            'handledBy',
            'salesBy',
            'items.variant',
            'items.promos',
            'payments.paymentMethod',
            'payments.paymentMethodRate',
        ]);

        if ($this->businessUnitFilter !== 'all' && !empty($this->businessUnitFilter)) {
            $orderQuery->where('business_unit_id', $this->businessUnitFilter);
        }

        $this->viewingOrder = $orderQuery->find($orderId);

        if (!$this->viewingOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Data nota transaksi tidak ditemukan atau di luar unit bisnis yang dipilih.', type: 'error');
            return;
        }

        $this->showReceiptModal = true;
    }

    /**
     * Menutup modal struk nota
     *
     * @return void
     */
    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->viewingOrder = null;
    }

    /**
     * Buka modal pengajuan alih / migrasi garansi dari order lama ke order baru
     */
    public function openSwitchModal(string $sn, int $orderId, string $orderNumber, string $productName): void
    {
        $user = Auth::user();
        if (!$user || (!$user->can('switch-warranty') && !$user->hasRole(['superadmin', 'admin', 'bm', 'bm_gsk']))) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki hak akses untuk mengajukan pengalihan garansi (switch-warranty).', type: 'error');
            return;
        }

        $sn = trim($sn);
        $this->switchTargetSn = $sn;
        $this->switchTargetOrderId = $orderId;
        $this->switchTargetOrderNumber = $orderNumber;
        $this->switchTargetProductName = $productName;
        $this->switchReason = '';
        $this->resetValidation();

        // 1. Cek apakah sudah ada pengajuan approval yang PENDING untuk IMEI ini
        $pending = ApprovalRequest::where('request_type', 'SWITCH_WARRANTY')
            ->where('status', 'PENDING')
            ->where(function ($q) use ($sn) {
                $q->whereJsonContains('payload->serial_number', $sn);
            })
            ->first();

        if ($pending) {
            $this->dispatch('toast', title: 'Pengajuan Sedang Berjalan', message: "IMEI {$sn} sedang dalam proses persetujuan (Level {$pending->current_level} / {$pending->required_level}). Mohon tunggu persetujuan BM & MO.", type: 'warning');
            return;
        }

        // 2. Ambil OrderItem tujuan (order baru)
        $newOrderItem = OrderItem::where('order_id', $orderId)
            ->where(function ($q) use ($sn) {
                $q->where('serial_number', 'LIKE', '%' . $sn . '%');
            })
            ->first();

        $this->switchTargetOrderItemId = $newOrderItem?->id;

        // 3. Cari bukti DeviceInspection sebelumnya untuk IMEI ini
        //    Prioritaskan yang inspectable_id bukan order_item_id baru ini
        $prevInspection = DeviceInspection::with(['inspector', 'media'])
            ->where('imei', $sn)
            ->when($newOrderItem, function ($q) use ($newOrderItem) {
                $q->where(function ($sub) use ($newOrderItem) {
                    $sub->where('inspectable_id', '!=', $newOrderItem->id)
                        ->orWhere('inspectable_type', '!=', get_class($newOrderItem));
                });
            })
            ->latest('id')
            ->first();

        if (!$prevInspection) {
            // Coba cari inspection apapun untuk IMEI ini
            $prevInspection = DeviceInspection::with(['inspector', 'media'])
                ->where('imei', $sn)
                ->latest('id')
                ->first();
        }

        if (!$prevInspection) {
            $this->dispatch('toast', title: 'QC Tidak Ditemukan', message: "Tidak ditemukan riwayat inspeksi QC unboxing sebelumnya untuk IMEI {$sn}. Silakan lakukan foto dan aktivasi QC baru.", type: 'warning');
            return;
        }

        $this->foundPreviousInspection = $prevInspection;
        $this->foundPreviousOrderItem = $prevInspection->inspectable;
        $this->foundPreviousOrder = $this->foundPreviousOrderItem?->order;

        $this->foundPreviousWarranties = Warranty::with('policy')
            ->where('serial_number', $sn)
            ->get();

        $this->showSwitchModal = true;
    }

    /**
     * Tutup modal pengajuan alih garansi
     */
    public function closeSwitchModal(): void
    {
        $this->showSwitchModal = false;
        $this->switchTargetSn = '';
        $this->switchTargetOrderId = null;
        $this->switchTargetOrderNumber = '';
        $this->switchTargetProductName = '';
        $this->switchTargetOrderItemId = null;
        $this->foundPreviousInspection = null;
        $this->foundPreviousOrder = null;
        $this->foundPreviousOrderItem = null;
        $this->foundPreviousWarranties = [];
        $this->switchReason = '';
        $this->resetValidation();
    }

    /**
     * Submit pengajuan approval alih garansi (BM -> MO)
     */
    public function submitSwitchRequest(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->can('switch-warranty') && !$user->hasRole(['superadmin', 'admin', 'bm', 'bm_gsk']))) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki hak akses switch-warranty.', type: 'error');
            return;
        }

        $this->validate([
            'switchReason' => 'required|string|min:5|max:500',
        ], [
            'switchReason.required' => 'Alasan pengalihan garansi wajib diisi.',
            'switchReason.min'      => 'Alasan minimal 5 karakter.',
        ]);

        if (!$this->foundPreviousInspection || !$this->switchTargetOrderItemId) {
            $this->dispatch('toast', title: 'Data Tidak Lengkap', message: 'Data inspeksi atau order item tujuan tidak valid.', type: 'error');
            return;
        }

        $this->isSubmittingSwitch = true;

        try {
            $approvalService = app(\App\Services\ApprovalService::class);

            $payload = [
                'serial_number'        => $this->switchTargetSn,
                'device_inspection_id' => $this->foundPreviousInspection->id,
                'old_order_id'         => $this->foundPreviousOrder?->id,
                'old_order_number'     => $this->foundPreviousOrder?->order_number ?? '-',
                'old_order_item_id'    => $this->foundPreviousOrderItem?->id,
                'new_order_id'         => $this->switchTargetOrderId,
                'new_order_number'     => $this->switchTargetOrderNumber,
                'new_order_item_id'    => $this->switchTargetOrderItemId,
                'product_name'         => $this->switchTargetProductName,
                'voided_warranty_ids'  => collect($this->foundPreviousWarranties)->pluck('id')->toArray(),
            ];

            $buId = $this->foundPreviousOrder?->business_unit_id ?? $user->getActiveBusinessUnitId();
            $branchId = $this->foundPreviousOrder?->branch_id ?? $user->branch_id;

            $approvalService->createRequest([
                'approvable_type'  => \App\Models\DeviceInspection::class,
                'approvable_id'    => $this->foundPreviousInspection->id,
                'request_type'     => 'SWITCH_WARRANTY',
                'requested_by'     => $user->id,
                'business_unit_id' => $buId,
                'branch_id'        => $branchId,
                'reason'           => trim($this->switchReason),
                'payload'          => $payload,
                'required_level'   => 2, // 2 Level: Level 1 (BM) -> Level 2 (MO)
            ]);

            $this->showSwitchModal = false;
            $this->dispatch('toast', 
                title: 'Pengajuan Berhasil', 
                message: "Pengajuan alih garansi IMEI {$this->switchTargetSn} berhasil diajukan. Menunggu persetujuan bertingkat (Level 1: BM ➔ Level 2: MO).", 
                type: 'success'
            );
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal Mengajukan', message: $e->getMessage(), type: 'error');
        } finally {
            $this->isSubmittingSwitch = false;
        }
    }

    public function render()
    {
        $businessUnits = BusinessUnit::where('is_active', true)->orderBy('name')->get();

        if ($this->businessUnitFilter !== 'all' && !empty($this->businessUnitFilter)) {
            $branches = Branch::where('business_unit_id', $this->businessUnitFilter)->orderBy('name')->get();
        } else {
            $branches = Branch::orderBy('name')->get();
        }

        $query = OrderItem::with(['order.user', 'order.businessUnit', 'inspections'])
            ->whereNotNull('serial_number')
            ->where('serial_number', '!=', '')
            ->whereHas('order', function($q) {
                $q->where('order_status', 'COMPLETED')
                  ->where('order_number', 'NOT LIKE', 'RET-CLM-%');

                if ($this->businessUnitFilter !== 'all' && !empty($this->businessUnitFilter)) {
                    $q->where('business_unit_id', $this->businessUnitFilter);
                }
                
                if ($this->dateStart && $this->dateEnd) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($this->dateStart)->startOfDay(),
                        Carbon::parse($this->dateEnd)->endOfDay()
                    ]);
                }

                if ($this->branchFilter !== 'all') {
                    $q->where('branch_id', $this->branchFilter);
                }
            });

        // Search logic
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('serial_number', 'LIKE', '%' . $this->search . '%')
                  ->orWhere('product_name', 'LIKE', '%' . $this->search . '%')
                  ->orWhereHas('order', function($q2) {
                      $q2->where('order_number', 'LIKE', '%' . $this->search . '%')
                         ->orWhereHas('user', function($q3) {
                             $q3->where('name', 'LIKE', '%' . $this->search . '%');
                         });
                  });
            });
        }

        $orderItems = $query->orderBy('created_at', 'desc')->get();

        // Ambil SN yang sedang memiliki pengajuan SWITCH_WARRANTY berstatus PENDING
        $pendingSwitchRequests = ApprovalRequest::where('request_type', 'SWITCH_WARRANTY')
            ->where('status', 'PENDING')
            ->get();

        $pendingSwitchMap = [];
        foreach ($pendingSwitchRequests as $psr) {
            $psn = strtolower(trim((string)($psr->payload['serial_number'] ?? '')));
            if (!empty($psn)) {
                $pendingSwitchMap[$psn] = [
                    'id'       => $psr->id,
                    'level'    => $psr->current_level,
                    'required' => $psr->required_level,
                ];
            }
        }

        // Process data manually to split SNs and apply status filter
        $processedList = collect();

        foreach ($orderItems as $item) {
            $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
            
            foreach ($sns as $sn) {
                $snClean = strtolower(trim($sn));
                $inspection = $item->inspections->first(function ($i) use ($snClean) {
                    return strtolower(trim($i->imei)) === $snClean;
                });
                
                $isActivated = $inspection ? true : false;

                // Status Filter
                if ($this->statusFilter === 'active' && !$isActivated) continue;
                if ($this->statusFilter === 'inactive' && $isActivated) continue;

                $pendingSwitch = $pendingSwitchMap[$snClean] ?? null;

                $processedList->push([
                    'order_id'           => $item->order_id,
                    'order_number'       => $item->order->order_number,
                    'customer_name'      => $item->order->user->name ?? 'Tamu',
                    'business_unit_name' => $item->order->businessUnit->name ?? null,
                    'order_date'         => $item->order->created_at,
                    'product_name'       => $item->product_name,
                    'serial_number'      => $sn,
                    'is_activated'       => $isActivated,
                    'inspection_id'      => $isActivated ? $inspection->id : null,
                    'pending_switch'     => $pendingSwitch,
                ]);
            }
        }

        // Manual Pagination for Collection
        $perPage = 15;
        $currentPage = $this->getPage();
        $total = $processedList->count();
        $pagedData = $processedList->slice(($currentPage - 1) * $perPage, $perPage);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $total,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.zoffline.qc.activation-list', [
            'paginatedItems' => $paginator,
            'businessUnits' => $businessUnits,
            'branches' => $branches
        ]);
    }
}
