<?php

namespace App\Livewire\Zoffline\Qc;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DeviceInspection;
use App\Models\BusinessUnit;
use App\Models\Branch;
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

        // Process data manually to split SNs and apply status filter
        $processedList = collect();

        foreach ($orderItems as $item) {
            $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
            
            foreach ($sns as $sn) {
                $inspection = $item->inspections->first(function ($i) use ($sn) {
                    return strtolower(trim($i->imei)) === strtolower(trim($sn));
                });
                
                $isActivated = $inspection ? true : false;

                // Status Filter
                if ($this->statusFilter === 'active' && !$isActivated) continue;
                if ($this->statusFilter === 'inactive' && $isActivated) continue;

                $processedList->push([
                    'order_id' => $item->order_id,
                    'order_number' => $item->order->order_number,
                    'customer_name' => $item->order->user->name ?? 'Tamu',
                    'business_unit_name' => $item->order->businessUnit->name ?? null,
                    'order_date' => $item->order->created_at,
                    'product_name' => $item->product_name,
                    'serial_number' => $sn,
                    'is_activated' => $isActivated,
                    'inspection_id' => $isActivated ? $inspection->id : null,
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
