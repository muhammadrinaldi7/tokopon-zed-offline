<?php

namespace App\Livewire\Zoffline\Pos;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.z', ['title' => 'Riwayat Penjualan POS'])]
class RiwayatPenjualan extends Component
{
    use WithPagination;

    public $search = '';
    public $showReceiptModal = false;
    public $completedOrder = null;

    public function reprintOrder($orderId)
    {
        $this->completedOrder = Order::with(['items.variant', 'user', 'payments.paymentMethod', 'handledBy', 'salesBy'])->find($orderId);
        if ($this->completedOrder) {
            $this->showReceiptModal = true;
        }
    }

    public function closeReceipt()
    {
        $this->showReceiptModal = false;
        $this->completedOrder = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $userWarehouseName = $user->warehouse->name ?? null;

        $orders = Order::with(['user', 'items', 'payments', 'salesBy'])
            ->where('order_channel', 'POS')
            ->where('business_unit_id', $user->getActiveBusinessUnitId())
            ->where('shipping_address_snapshot->store', $userWarehouseName)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('identity', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.zoffline.pos.riwayat-penjualan', [
            'orders' => $orders
        ]);
    }
}
