<?php

namespace App\Livewire\Zoffline\Inbound;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

#[Layout('layouts.z', ['title' => 'Inbound PO - TokoPun'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all'; // 'all', 'pending', 'completed'

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function syncPos()
    {
        $bu = Auth::user()->getActiveBusinessUnit();
        $buCode = $bu ? $bu->code : 'syihab';

        Artisan::call('accurate:sync-pos', ['bu_code' => $buCode]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Sinkronisasi PO berhasil.', type: 'success');
    }

    public function render()
    {
        $bu = Auth::user()->getActiveBusinessUnit();
        $buCode = $bu ? $bu->code : 'syihab';

        $baseQuery = PurchaseOrder::where('database_source', $buCode)->whereHas('items');

        $countAll = (clone $baseQuery)->count();
        $countPending = (clone $baseQuery)->whereIn('status', ['PENDING', 'PARTIAL'])->count();
        $countCompleted = (clone $baseQuery)->where('status', 'COMPLETED')->count();

        $query = PurchaseOrder::with(['vendor', 'items'])
            ->where('database_source', $buCode)
            ->whereHas('items')
            ->orderBy('id', 'desc');

        if ($this->statusFilter === 'pending') {
            $query->whereIn('status', ['PENDING', 'PARTIAL']);
        } elseif ($this->statusFilter === 'completed') {
            $query->where('status', 'COMPLETED');
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('po_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('vendor', function($v) {
                      $v->where('vendor_name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return view('livewire.zoffline.inbound.index', [
            'pos' => $query->paginate(15),
            'countAll' => $countAll,
            'countPending' => $countPending,
            'countCompleted' => $countCompleted,
        ]);
    }
}
