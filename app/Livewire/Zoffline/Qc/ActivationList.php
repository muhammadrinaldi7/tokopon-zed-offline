<?php

namespace App\Livewire\Zoffline\Qc;

use App\Models\OrderItem;
use App\Models\DeviceInspection;
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
    public $dateStart = null;
    
    #[Url]
    public $dateEnd = null;

    public function mount()
    {
        $this->dateStart = $this->dateStart ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $this->dateEnd = $this->dateEnd ?? Carbon::now()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $activeUnitId = Auth::user()->getActiveBusinessUnitId();
        
        $query = OrderItem::with(['order.user', 'inspections'])
            ->whereNotNull('serial_number')
            ->where('serial_number', '!=', '')
            ->whereHas('order', function($q) use ($activeUnitId) {
                $q->where('business_unit_id', $activeUnitId)
                  ->where('status', 'COMPLETED');
                
                if ($this->dateStart && $this->dateEnd) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($this->dateStart)->startOfDay(),
                        Carbon::parse($this->dateEnd)->endOfDay()
                    ]);
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
                $isActivated = $item->inspections->contains('imei', $sn);

                // Status Filter
                if ($this->statusFilter === 'active' && !$isActivated) continue;
                if ($this->statusFilter === 'inactive' && $isActivated) continue;

                $processedList->push([
                    'order_id' => $item->order_id,
                    'order_number' => $item->order->order_number,
                    'customer_name' => $item->order->user->name ?? 'Tamu',
                    'order_date' => $item->order->created_at,
                    'product_name' => $item->product_name,
                    'serial_number' => $sn,
                    'is_activated' => $isActivated,
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
            'paginatedItems' => $paginator
        ]);
    }
}
