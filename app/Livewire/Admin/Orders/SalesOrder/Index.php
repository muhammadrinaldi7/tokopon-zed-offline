<?php

namespace App\Livewire\Admin\Orders\SalesOrder;

use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filterBusinessUnitId = '';
    public $filterBranchId = '';

    public function mount()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $isAdmin = $user->hasAnyRole(['superadmin', 'admin', 'director', 'direktur']);
        $isMo = !$isAdmin && $user->hasAnyRole(['manager_operasional', 'manager_operasional_gsk']);

        if ($isAdmin) {
            // Sesuai active business unit id
            $activeBu = $user->getActiveBusinessUnitId();
            $this->filterBusinessUnitId = $activeBu ? (string) $activeBu : '';
            $this->filterBranchId = '';
        } elseif ($isMo) {
            // MO terkunci ke unit bisnisnya, bebas pilih cabang
            $this->filterBusinessUnitId = (string) ($user->business_unit_id ?? $user->getActiveBusinessUnitId() ?? '');
            $this->filterBranchId = '';
        } else {
            // Kasir, FL, BM terkunci ke cabang dan unit bisnisnya
            $this->filterBusinessUnitId = (string) ($user->business_unit_id ?? '');
            $this->filterBranchId = (string) ($user->branch_id ?? '');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterBusinessUnitId()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasAnyRole(['superadmin', 'admin', 'director', 'direktur']);

        // Jika bukan admin, jangan biarkan ubah unit bisnis
        if (!$isAdmin) {
            $this->filterBusinessUnitId = (string) ($user->business_unit_id ?? $user->getActiveBusinessUnitId() ?? '');
        }

        $this->filterBranchId = '';
        $this->resetPage();
    }

    public function updatedFilterBranchId()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasAnyRole(['superadmin', 'admin', 'director', 'direktur']);
        $isMo = $user && !$isAdmin && $user->hasAnyRole(['manager_operasional', 'manager_operasional_gsk']);

        // Jika Kasir/FL/BM, jangan biarkan ubah cabang
        if (!$isAdmin && !$isMo) {
            $this->filterBranchId = (string) ($user->branch_id ?? '');
        }

        $this->resetPage();
    }

    public function resetFilters()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasAnyRole(['superadmin', 'admin', 'director', 'direktur']);
        $isMo = $user && !$isAdmin && $user->hasAnyRole(['manager_operasional', 'manager_operasional_gsk']);

        $this->search = '';

        if ($isAdmin) {
            $activeBu = $user->getActiveBusinessUnitId();
            $this->filterBusinessUnitId = $activeBu ? (string) $activeBu : '';
            $this->filterBranchId = '';
        } elseif ($isMo) {
            $this->filterBusinessUnitId = (string) ($user->business_unit_id ?? $user->getActiveBusinessUnitId() ?? '');
            $this->filterBranchId = '';
        } else {
            $this->filterBusinessUnitId = (string) ($user->business_unit_id ?? '');
            $this->filterBranchId = (string) ($user->branch_id ?? '');
        }

        $this->resetPage();
    }

    #[On('orderCancellationSubmitted')]
    public function orderCancellationSubmitted()
    {
        // Refresh the page when a cancellation is submitted
    }

    public function render()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasAnyRole(['superadmin', 'admin', 'director', 'direktur']);
        $isMo = $user && !$isAdmin && $user->hasAnyRole(['manager_operasional', 'manager_operasional_gsk']);
        $isBranchLocked = !$isAdmin && !$isMo;

        // Opsi Business Unit & Branch untuk filter dropdown
        $businessUnits = BusinessUnit::where('is_active', true)->orderBy('name')->get();

        $branchQuery = Branch::query()->orderBy('name');
        if ($isBranchLocked) {
            $branchQuery->where('id', $user->branch_id ?? 0);
        } elseif ($isMo) {
            $moBuId = $user->business_unit_id ?? $user->getActiveBusinessUnitId();
            if ($moBuId) {
                $branchQuery->where('business_unit_id', $moBuId);
            }
        } elseif ($isAdmin && !empty($this->filterBusinessUnitId)) {
            $branchQuery->where('business_unit_id', $this->filterBusinessUnitId);
        }
        $branches = $branchQuery->get();

        // Query utama Order
        $ordersQuery = Order::query()
            ->with(['user', 'accurateDocs', 'approvalRequests', 'salesBy', 'branch', 'businessUnit'])
            ->where('order_channel', 'SO');

        // Scoping keamanan berdasarkan role
        if ($isBranchLocked) {
            // Kasir, FL, BM hanya bisa lihat cabang miliknya
            $ordersQuery->where('branch_id', $user->branch_id ?? 0);
            if ($user && $user->business_unit_id) {
                $ordersQuery->where('business_unit_id', $user->business_unit_id);
            }
        } elseif ($isMo) {
            // MO bisa lihat semua cabang sesuai unit bisnisnya
            $moBuId = $user->business_unit_id ?? $user->getActiveBusinessUnitId();
            if ($moBuId) {
                $ordersQuery->where('business_unit_id', $moBuId);
            }
            if (!empty($this->filterBranchId)) {
                $ordersQuery->where('branch_id', $this->filterBranchId);
            }
        } else {
            // Admin bisa lihat semua / filter fleksibel
            if (!empty($this->filterBusinessUnitId)) {
                $ordersQuery->where('business_unit_id', $this->filterBusinessUnitId);
            }
            if (!empty($this->filterBranchId)) {
                $ordersQuery->where('branch_id', $this->filterBranchId);
            }
        }

        // Pencarian aman
        $ordersQuery->when($this->search, function ($q) {
            $term = trim($this->search);
            $q->where(function ($sub) use ($term) {
                $sub->where('order_number', 'like', '%' . $term . '%')
                    ->orWhere('accurate_so_number', 'like', '%' . $term . '%')
                    ->orWhereHas('user', function ($q2) use ($term) {
                        $q2->where('name', 'like', '%' . $term . '%')
                           ->orWhere('email', 'like', '%' . $term . '%');
                    });
            });
        });

        $orders = $ordersQuery->latest()->paginate(15);

        return view('livewire.admin.orders.sales-order.index', [
            'orders' => $orders,
            'businessUnits' => $businessUnits,
            'branches' => $branches,
            'isAdmin' => $isAdmin,
            'isMo' => $isMo,
            'isBranchLocked' => $isBranchLocked,
            'userBranch' => $user?->branch,
            'userBu' => $user?->businessUnit,
        ])->layout('layouts.z');
    }
}

