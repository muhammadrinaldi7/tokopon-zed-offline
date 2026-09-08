<?php

namespace App\Livewire\Zoffline\StockOpname;

use App\Models\Branch;
use App\Models\StockOpname;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.z', ['title' => 'Stock Opname Cabang'])]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public $search = '';

    #[Url(except: '')]
    public $filterStatus = 'ALL';

    #[Url(except: '')]
    public $filterBranchId = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterBranchId()
    {
        $this->resetPage();
    }

    public function navigateToCreate()
    {
        return $this->redirectRoute('zoffline.stock-opname.create', navigate: true);
    }

    public function render()
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();
        $isGlobal = $user->hasAnyRole(['superadmin', 'admin', 'director']);

        // BM & BM GSK dikunci ke cabangnya sendiri
        $currentBranchId = $user->branch_id;

        $query = StockOpname::with(['user', 'branch', 'warehouse', 'latestApprovalRequest'])
            ->where('business_unit_id', $buId)
            ->when(!$isGlobal && $currentBranchId, function ($q) use ($currentBranchId) {
                $q->where('branch_id', $currentBranchId);
            })
            ->when($isGlobal && $this->filterBranchId, function ($q) {
                $q->where('branch_id', $this->filterBranchId);
            })
            ->when($this->filterStatus !== 'ALL', function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('opname_number', 'like', '%' . $this->search . '%')
                        ->orWhere('notes', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('created_at', 'desc');

        $opnames = $query->paginate(10);

        $branches = $isGlobal 
            ? Branch::where('business_unit_id', $buId)->get() 
            : collect();

        return view('livewire.zoffline.stock-opname.index', [
            'opnames'  => $opnames,
            'branches' => $branches,
            'isGlobal' => $isGlobal,
            'user'     => $user,
        ]);
    }
}
