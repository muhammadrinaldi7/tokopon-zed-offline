<?php

namespace App\Livewire\Admin\Orders\SalesOrder;

use App\Models\Order;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    #[On('orderCancellationSubmitted')]
    public function orderCancellationSubmitted()
    {
        // Refresh the page when a cancellation is submitted
    }

    public function render()
    {
        $orders = Order::query()
            ->with(['user', 'accurateDocs', 'approvalRequests'])
            ->where('order_channel', 'SO')
            ->when($this->search, function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.orders.sales-order.index', [
            'orders' => $orders
        ])->layout('layouts.z');
    }
}
