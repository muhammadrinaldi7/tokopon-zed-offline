<?php

namespace App\Livewire\Admin\Orders\SalesOrder;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $orders = Order::query()
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
        ])->layout('layouts.admin');
    }
}
