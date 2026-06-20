<?php

namespace App\Livewire\Admin\Inbound;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

#[Layout('layouts.admin', ['title' => 'Inbound PO - TokoPun'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function syncPos()
    {
        $bu = Auth::user()->getActiveBusinessUnit();
        $buCode = $bu ? $bu->code : 'syihab';

        Artisan::call('accurate:sync-pos', ['bu_code' => $buCode]);
        $this->dispatch('admin-alert', type: 'success', message: 'Sinkronisasi PO berhasil.');
    }

    public function render()
    {
        $bu = Auth::user()->getActiveBusinessUnit();
        $buCode = $bu ? $bu->code : 'syihab';
        $query = PurchaseOrder::with(['vendor', 'items'])
            ->where('database_source', $buCode)
            ->orderBy('id', 'desc');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('po_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('vendor', function($v) {
                      $v->where('vendor_name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return view('livewire.admin.inbound.index', [
            'pos' => $query->paginate(15)
        ]);
    }
}
