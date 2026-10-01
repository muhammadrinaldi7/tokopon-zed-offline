<?php

namespace App\Livewire\Admin\Warranty;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\DeviceInspection;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Warranty;
use App\Models\WarrantyPolicy;
use App\Models\BusinessUnit;
use App\Services\WarrantyCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.admin', ['title' => 'Generate Garansi Tertunda'])]
class PendingActivationManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'pending'; // 'pending' (Gantung), 'active' (Sudah Aktif), 'all' (Semua)
    public $selectedBuId = null;
    public $perPage = 15;

    // Modal Generate Garansi
    public $showGenerateModal = false;
    public $selectedInspectionId = null;
    /** @var \App\Models\DeviceInspection|null */
    public $targetInspection = null;
    /** @var \App\Models\OrderItem|null */
    public $targetOrderItem = null;
    /** @var \App\Models\Order|null */
    public $targetOrder = null;
    /** @var \App\Models\WarrantyPolicy|null */
    public $suggestedPolicy = null;
    public $selectedPolicyId = null;
    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\WarrantyPolicy>|array */
    public $availablePolicies = [];
    public $isSubmitting = false;

    // Modal Detail QC Checklist
    public $showQcModal = false;
    /** @var \App\Models\DeviceInspection|null */
    public $viewingInspection = null;

    // Modal Detail Garansi Aktif
    public $showWarrantyModal = false;
    /** @var \App\Models\Warranty|null */
    public $viewingWarranty = null;

    // Modal Lihat Struk Transaksi Mandiri (dibuka dari tabel)
    public $showReceiptModal = false;
    /** @var \App\Models\Order|null */
    public $viewingOrder = null;

    // Toggle tampilan struk di dalam modal Generate Garansi
    public $showReceiptInGenerateModal = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'pending'],
        'selectedBuId' => ['except' => null],
    ];

    public function mount()
    {
        $this->selectedBuId = Auth::user()->getActiveBusinessUnitId() ?? 1;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSelectedBuId()
    {
        $this->resetPage();
    }

    /**
     * Buka modal untuk generate garansi pada 1 IMEI tertentu
     */
    public function openGenerateModal($inspectionId)
    {
        $this->resetValidation();
        $this->selectedInspectionId = $inspectionId;
        $this->showReceiptInGenerateModal = true;
        
        $this->targetInspection = DeviceInspection::with([
            'inspector',
            'inspectable.order.user.profile',
            'inspectable.order.businessUnit',
            'inspectable.order.handledBy',
            'inspectable.order.salesBy',
            'inspectable.order.items.variant',
            'inspectable.order.items.promos',
            'inspectable.order.payments.paymentMethod',
            'inspectable.order.payments.paymentMethodRate',
            'inspectable.variant',
            'inspectable.promos'
        ])->findOrFail($inspectionId);

        $this->targetOrderItem = $this->targetInspection->inspectable;
        if (!$this->targetOrderItem || !$this->targetOrderItem->order) {
            $this->dispatch('toast', title: 'Error', message: 'Data transaksi order tidak ditemukan untuk inspeksi ini.', type: 'error');
            return;
        }

        $this->targetOrder = $this->targetOrderItem->order;

        if (!in_array($this->targetOrder->order_status, ['COMPLETED', 'PIUTANG'])) {
            $this->dispatch('toast', title: 'Transaksi Tidak Sah', message: "Transaksi order #{$this->targetOrder->order_number} berstatus {$this->targetOrder->order_status}. Garansi hanya bisa digenerate untuk transaksi sah (COMPLETED / PIUTANG).", type: 'warning');
            return;
        }

        $buId = $this->targetOrder->business_unit_id ?? 1;

        // Cek apakah perangkat ini sudah ada garansi aktif
        $existingWarranty = Warranty::where('status', 'active')
            ->where(function ($q) {
                $q->where('device_inspection_id', $this->targetInspection->id)
                  ->orWhere(function ($sub) {
                      $sub->where('order_item_id', $this->targetOrderItem->id)
                          ->where('serial_number', trim($this->targetInspection->imei));
                  });
            })->first();

        if ($existingWarranty) {
            $this->dispatch('toast', title: 'Perhatian', message: 'IMEI ini sudah memiliki garansi aktif.', type: 'warning');
            return;
        }

        // Ambil semua kebijakan garansi aktif untuk Business Unit terkait
        $this->availablePolicies = WarrantyPolicy::where('business_unit_id', $buId)
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        // Hitung kalkulasi rekomendasi otomatis berdasarkan rule produk & brand
        $calculator = new WarrantyCalculatorService();
        $suggestedPolicies = $calculator->calculateWarranties($this->targetOrder, $this->targetOrderItem);
        $this->suggestedPolicy = $suggestedPolicies->first();

        // Set default pilihan policy
        $firstPolicy = collect($this->availablePolicies)->first();
        $this->selectedPolicyId = $this->suggestedPolicy 
            ? $this->suggestedPolicy->id 
            : ($firstPolicy?->id ?? null);

        $this->showGenerateModal = true;
    }

    /**
     * Eksekusi penerbitan garansi untuk 1 IMEI
     */
    public function confirmGenerate()
    {
        $this->validate([
            'selectedPolicyId' => 'required|exists:warranty_policies,id',
        ], [
            'selectedPolicyId.required' => 'Silakan pilih kebijakan garansi terlebih dahulu.',
        ]);

        if ($this->isSubmitting) return;
        $this->isSubmitting = true;

        try {
            $inspection = DeviceInspection::findOrFail($this->selectedInspectionId);
            $orderItem = OrderItem::with('order')->findOrFail($inspection->inspectable_id);
            $order = $orderItem->order;

            if (!$order || !in_array($order->order_status, ['COMPLETED', 'PIUTANG'])) {
                $this->dispatch('toast', title: 'Transaksi Tidak Sah', message: "Transaksi order berstatus " . ($order->order_status ?? 'Tidak Ditemukan') . ". Garansi hanya dapat diterbitkan untuk transaksi COMPLETED atau PIUTANG.", type: 'error');
                $this->showGenerateModal = false;
                $this->isSubmitting = false;
                return;
            }

            $imei = trim($inspection->imei);

            // Double check cegah duplikasi
            $existingWarranty = Warranty::where('status', 'active')
                ->where(function ($q) use ($inspection, $orderItem, $imei) {
                    $q->where('device_inspection_id', $inspection->id)
                      ->orWhere(function ($sub) use ($orderItem, $imei) {
                          $sub->where('order_item_id', $orderItem->id)
                              ->where('serial_number', $imei);
                      });
                })->first();

            if ($existingWarranty) {
                $this->dispatch('toast', title: 'Sudah Aktif', message: "IMEI {$imei} sudah memiliki kartu garansi aktif.", type: 'warning');
                $this->showGenerateModal = false;
                $this->isSubmitting = false;
                return;
            }

            $policy = WarrantyPolicy::findOrFail($this->selectedPolicyId);

            // Waktu aktivasi garansi mengacu pada waktu inspeksi QC dilakukan
            $activationDate = $inspection->inspected_at 
                ? Carbon::parse($inspection->inspected_at) 
                : ($inspection->created_at ? Carbon::parse($inspection->created_at) : ($order->created_at ? Carbon::parse($order->created_at) : Carbon::now()));

            $expiresAt = $activationDate->copy()->addDays($policy->duration_days);
            $status = $expiresAt->isPast() ? 'expired' : 'active';

            Warranty::create([
                'warranty_policy_id' => $policy->id,
                'order_item_id' => $orderItem->id,
                'serial_number' => $imei,
                'customer_user_id' => $order->user_id,
                'type' => $policy->coverage_type,
                'duration_days' => $policy->duration_days,
                'activated_at' => $activationDate,
                'expires_at' => $expiresAt,
                'status' => $status,
                'claims_used' => 0,
                'device_inspection_id' => $inspection->id,
                'source' => $policy->type === 'addon_warranty' ? 'purchase' : 'activation',
            ]);

            $this->dispatch('toast', 
                title: 'Berhasil', 
                message: "Garansi \"{$policy->name}\" ({$policy->duration_days} Hari) berhasil diterbitkan untuk IMEI {$imei} terhitung dari tanggal QC ({$activationDate->format('d/m/Y')})!", 
                type: 'success'
            );

            $this->showGenerateModal = false;
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isSubmitting = false;
        }
    }

    /**
     * Buka modal rincian checklist QC inspeksi
     */
    public function openQcModal($inspectionId)
    {
        $this->viewingInspection = DeviceInspection::with(['inspector', 'qcTemplate'])->find($inspectionId);
        $this->showQcModal = true;
    }

    /**
     * Buka modal rincian garansi yang sudah aktif
     */
    public function openWarrantyModal($inspectionId)
    {
        $inspection = DeviceInspection::find($inspectionId);
        if (!$inspection) return;

        $this->viewingWarranty = Warranty::with(['policy', 'orderItem.order', 'customer'])
            ->where('status', 'active')
            ->where(function ($q) use ($inspection) {
                $q->where('device_inspection_id', $inspection->id)
                  ->orWhere(function ($sub) use ($inspection) {
                      $sub->where('order_item_id', $inspection->inspectable_id)
                          ->where('serial_number', trim($inspection->imei));
                  });
            })->first();

        $this->showWarrantyModal = true;
    }

    /**
     * Buka modal struk transaksi mandiri dari row tabel
     */
    public function openReceiptModal($orderId)
    {
        $this->viewingOrder = Order::with([
            'user.profile',
            'businessUnit',
            'handledBy',
            'salesBy',
            'items.variant',
            'items.promos',
            'payments.paymentMethod',
            'payments.paymentMethodRate'
        ])->find($orderId);

        if (!$this->viewingOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Data transaksi order tidak ditemukan.', type: 'error');
            return;
        }

        $this->showReceiptModal = true;
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->viewingOrder = null;
    }

    /**
     * Toggle tampilan struk di dalam modal generate garansi
     */
    public function toggleReceiptInGenerateModal()
    {
        $this->showReceiptInGenerateModal = !$this->showReceiptInGenerateModal;
    }

    public function render()
    {
        $calculator = new WarrantyCalculatorService();
        $businessUnits = BusinessUnit::orderBy('name')->get();

        // Base Query: Hanya inspeksi QC untuk transaksi penjualan resmi (COMPLETED / PIUTANG)
        $baseQuery = DeviceInspection::query()
            ->select('device_inspections.*')
            ->join('order_items', function ($j) {
                $j->on('device_inspections.inspectable_id', '=', 'order_items.id')
                  ->where('device_inspections.inspectable_type', '=', OrderItem::class);
            })
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.order_status', ['COMPLETED', 'PIUTANG'])
            ->whereNotNull('device_inspections.imei')
            ->where('device_inspections.imei', '!=', '');

        if ($this->selectedBuId) {
            $baseQuery->where('orders.business_unit_id', $this->selectedBuId);
        }

        // Filter pencarian
        if (!empty(trim($this->search))) {
            $s = trim($this->search);
            $baseQuery->leftJoin('users as customer_users', 'orders.user_id', '=', 'customer_users.id')
                ->where(function ($q) use ($s) {
                    $q->where('device_inspections.imei', 'like', "%{$s}%")
                      ->orWhere('order_items.product_name', 'like', "%{$s}%")
                      ->orWhere('order_items.serial_number', 'like', "%{$s}%")
                      ->orWhere('orders.order_number', 'like', "%{$s}%")
                      ->orWhere('customer_users.name', 'like', "%{$s}%")
                      ->orWhere('customer_users.email', 'like', "%{$s}%");
                });
        }

        // Subquery Warranty Check (De Morgan: Not exists by inspection_id AND Not exists by order_item_id + imei)
        $filterPending = function ($q) {
            return $q->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('warranties')
                    ->where('warranties.status', 'active')
                    ->whereColumn('warranties.device_inspection_id', 'device_inspections.id');
            })->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('warranties')
                    ->where('warranties.status', 'active')
                    ->whereColumn('warranties.order_item_id', 'device_inspections.inspectable_id')
                    ->whereColumn('warranties.serial_number', 'device_inspections.imei');
            });
        };

        $filterActive = function ($q) {
            return $q->where(function ($subQ) {
                $subQ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('warranties')
                        ->where('warranties.status', 'active')
                        ->whereColumn('warranties.device_inspection_id', 'device_inspections.id');
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('warranties')
                        ->where('warranties.status', 'active')
                        ->whereColumn('warranties.order_item_id', 'device_inspections.inspectable_id')
                        ->whereColumn('warranties.serial_number', 'device_inspections.imei');
                });
            });
        };

        // Hitung statistik untuk cards di atas secara efisien
        $totalCount = (clone $baseQuery)->count('device_inspections.id');
        $pendingCount = $filterPending(clone $baseQuery)->count('device_inspections.id');
        $activeCount = max(0, $totalCount - $pendingCount);

        // Filter status tabel
        $query = clone $baseQuery;
        if ($this->statusFilter === 'pending') {
            $filterPending($query);
        } elseif ($this->statusFilter === 'active') {
            $filterActive($query);
        }

        $inspections = $query->with([
            'inspector:id,name',
            'inspectable' => function ($morphTo) {
                $morphTo->morphWith([
                    OrderItem::class => [
                        'order.user:id,name,email',
                        'order.businessUnit:id,name',
                        'variant',
                        'promos'
                    ],
                ]);
            },
        ])
        ->orderBy('device_inspections.id', 'desc')
        ->paginate($this->perPage);

        // Batched Query untuk Garansi Aktif (1 query untuk seluruh 15 baris)
        $inspectionIds = $inspections->pluck('id')->filter()->toArray();
        $orderItemIds = $inspections->pluck('inspectable_id')->filter()->toArray();
        $imeis = $inspections->pluck('imei')->map(fn($sn) => trim($sn))->filter()->toArray();

        $activeWarranties = collect();
        if (!empty($inspectionIds) || (!empty($orderItemIds) && !empty($imeis))) {
            $activeWarranties = Warranty::with('policy')
                ->where('status', 'active')
                ->where(function ($q) use ($inspectionIds, $orderItemIds, $imeis) {
                    if (!empty($inspectionIds)) {
                        $q->whereIn('device_inspection_id', $inspectionIds);
                    }
                    if (!empty($orderItemIds) && !empty($imeis)) {
                        $q->orWhere(function ($sub) use ($orderItemIds, $imeis) {
                            $sub->whereIn('order_item_id', $orderItemIds)
                                ->whereIn('serial_number', $imeis);
                        });
                    }
                })
                ->get();
        }

        $warrantiesByInspectionId = $activeWarranties->whereNotNull('device_inspection_id')->keyBy('device_inspection_id');
        $warrantiesByItemAndSn = $activeWarranties->keyBy(function ($w) {
            return $w->order_item_id . '_' . trim($w->serial_number);
        });

        // Pasangkan data garansi aktif atau rekomendasi policy secara instan dari memori
        foreach ($inspections as $ins) {
            $warranty = $warrantiesByInspectionId->get($ins->id)
                ?? $warrantiesByItemAndSn->get($ins->inspectable_id . '_' . trim($ins->imei));

            $ins->active_warranty = $warranty;

            if (!$warranty && $ins->inspectable && $ins->inspectable->order) {
                // Gunakan getMainWarrantyPolicy yang ringan dan menggunakan cache in-memory
                $ins->recommended_policy = $calculator->getMainWarrantyPolicy($ins->inspectable->order, $ins->inspectable);
            } else {
                $ins->recommended_policy = null;
            }
        }

        return view('livewire.admin.warranty.pending-activation-management', [
            'inspections' => $inspections,
            'businessUnits' => $businessUnits,
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'activeCount' => $activeCount,
            'statusFilter' => $this->statusFilter,
            'search' => $this->search,
            'selectedBuId' => $this->selectedBuId,
            'showGenerateModal' => $this->showGenerateModal,
            'targetOrder' => $this->targetOrder,
            'targetInspection' => $this->targetInspection,
            'targetOrderItem' => $this->targetOrderItem,
            'suggestedPolicy' => $this->suggestedPolicy,
            'availablePolicies' => $this->availablePolicies,
            'selectedPolicyId' => $this->selectedPolicyId,
            'selectedInspectionId' => $this->selectedInspectionId,
            'isSubmitting' => $this->isSubmitting,
            'showQcModal' => $this->showQcModal,
            'viewingInspection' => $this->viewingInspection,
            'showWarrantyModal' => $this->showWarrantyModal,
            'viewingWarranty' => $this->viewingWarranty,
            'showReceiptModal' => $this->showReceiptModal,
            'viewingOrder' => $this->viewingOrder,
            'showReceiptInGenerateModal' => $this->showReceiptInGenerateModal,
        ]);
    }
}
