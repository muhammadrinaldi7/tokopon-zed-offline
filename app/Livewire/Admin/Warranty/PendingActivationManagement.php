<?php

namespace App\Livewire\Admin\Warranty;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\DeviceInspection;
use App\Models\OrderItem;
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
        
        $this->targetInspection = DeviceInspection::with([
            'inspector',
            'inspectable.order.user',
            'inspectable.order.businessUnit',
            'inspectable.variant',
            'inspectable.promos'
        ])->findOrFail($inspectionId);

        $this->targetOrderItem = $this->targetInspection->inspectable;
        if (!$this->targetOrderItem || !$this->targetOrderItem->order) {
            $this->dispatch('toast', title: 'Error', message: 'Data transaksi order tidak ditemukan untuk inspeksi ini.', type: 'error');
            return;
        }

        $this->targetOrder = $this->targetOrderItem->order;
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
            $now = Carbon::now();

            Warranty::create([
                'warranty_policy_id' => $policy->id,
                'order_item_id' => $orderItem->id,
                'serial_number' => $imei,
                'customer_user_id' => $order->user_id,
                'type' => $policy->coverage_type,
                'duration_days' => $policy->duration_days,
                'activated_at' => $now,
                'expires_at' => $now->copy()->addDays($policy->duration_days),
                'status' => 'active',
                'claims_used' => 0,
                'device_inspection_id' => $inspection->id,
                'source' => $policy->type === 'addon_warranty' ? 'purchase' : 'activation',
            ]);

            $this->dispatch('toast', 
                title: 'Berhasil', 
                message: "Garansi \"{$policy->name}\" ({$policy->duration_days} Hari) berhasil diterbitkan untuk IMEI {$imei}!", 
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

    public function render()
    {
        $calculator = new WarrantyCalculatorService();
        $businessUnits = BusinessUnit::orderBy('name')->get();

        // Base Query: Hanya inspeksi yang terkait dengan transaksi OrderItem
        $baseQuery = DeviceInspection::query()
            ->where('inspectable_type', OrderItem::class)
            ->whereNotNull('inspectable_id')
            ->whereNotNull('imei')
            ->where('imei', '!=', '');

        if ($this->selectedBuId) {
            $baseQuery->whereHasMorph('inspectable', [OrderItem::class], function ($q) {
                $q->whereHas('order', function ($sub) {
                    $sub->where('business_unit_id', $this->selectedBuId);
                });
            });
        }

        // Subquery Warranty Check
        $subWarrantyExists = function ($sub) {
            $sub->select(DB::raw(1))
                ->from('warranties')
                ->where('warranties.status', 'active')
                ->where(function ($w) {
                    $w->whereColumn('warranties.device_inspection_id', 'device_inspections.id')
                      ->orWhere(function ($w2) {
                          $w2->whereColumn('warranties.order_item_id', 'device_inspections.inspectable_id')
                             ->whereColumn('warranties.serial_number', 'device_inspections.imei');
                      });
                });
        };

        // Hitung statistik untuk cards di atas
        $totalCount = (clone $baseQuery)->count();
        $pendingCount = (clone $baseQuery)->whereNotExists($subWarrantyExists)->count();
        $activeCount = (clone $baseQuery)->whereExists($subWarrantyExists)->count();

        // Filter status
        $query = clone $baseQuery;
        if ($this->statusFilter === 'pending') {
            $query->whereNotExists($subWarrantyExists);
        } elseif ($this->statusFilter === 'active') {
            $query->whereExists($subWarrantyExists);
        }

        // Filter pencarian
        if (!empty(trim($this->search))) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('imei', 'like', "%{$s}%")
                  ->orWhereHasMorph('inspectable', [OrderItem::class], function ($qItem) use ($s) {
                      $qItem->where('product_name', 'like', "%{$s}%")
                            ->orWhere('serial_number', 'like', "%{$s}%")
                            ->orWhereHas('order', function ($qOrder) use ($s) {
                                $qOrder->where('order_number', 'like', "%{$s}%")
                                       ->orWhereHas('user', function ($qUser) use ($s) {
                                           $qUser->where('name', 'like', "%{$s}%")
                                                 ->orWhere('phone', 'like', "%{$s}%");
                                       });
                            });
                  });
            });
        }

        $inspections = $query->with([
            'inspector',
            'inspectable' => function ($morphTo) {
                $morphTo->morphWith([
                    OrderItem::class => ['order.user', 'order.businessUnit', 'variant', 'promos'],
                ]);
            },
        ])
        ->orderBy('id', 'desc')
        ->paginate($this->perPage);

        // Pasangkan data garansi aktif atau rekomendasi policy untuk tiap item
        foreach ($inspections as $ins) {
            $warranty = Warranty::with('policy')
                ->where('status', 'active')
                ->where(function ($q) use ($ins) {
                    $q->where('device_inspection_id', $ins->id)
                      ->orWhere(function ($sub) use ($ins) {
                          $sub->where('order_item_id', $ins->inspectable_id)
                              ->where('serial_number', trim($ins->imei));
                      });
                })->first();

            $ins->active_warranty = $warranty;

            if (!$warranty && $ins->inspectable && $ins->inspectable->order) {
                $calc = $calculator->calculateWarranties($ins->inspectable->order, $ins->inspectable);
                $ins->recommended_policy = $calc->first();
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
            'showQcModal' => $this->showQcModal,
            'showWarrantyModal' => $this->showWarrantyModal,
        ]);
    }
}
