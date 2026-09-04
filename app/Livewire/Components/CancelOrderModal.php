<?php

namespace App\Livewire\Components;

use App\Models\Order;
use App\Models\ApprovalRule;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CancelOrderModal extends Component
{
    public $showModal = false;
    public $orderId;
    public $cancelReason = '';

    #[On('openCancelModal')]
    public function openModal($orderId)
    {
        $this->orderId = $orderId;
        $this->cancelReason = '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->orderId = null;
        $this->cancelReason = '';
    }

    public function submitCancellation()
    {
        $this->validate([
            'cancelReason' => 'required|min:5'
        ], [
            'cancelReason.required' => 'Alasan pembatalan wajib diisi.',
            'cancelReason.min' => 'Alasan pembatalan minimal 5 karakter.'
        ]);

        $order = Order::find($this->orderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Transaksi tidak ditemukan.', type: 'error');
            return;
        }

        // Check if there is already a pending request
        $existing = $order->approvalRequests()->where('status', 'PENDING')->where('request_type', 'ORDER_CANCELLATION')->first();
        if ($existing) {
            $this->dispatch('toast', title: 'Info', message: 'Transaksi ini sudah dalam proses pengajuan pembatalan.', type: 'info');
            $this->closeModal();
            return;
        }

        $request = app(\App\Services\ApprovalService::class)->createRequest([
            'approvable'       => $order,
            'request_type'     => 'ORDER_CANCELLATION',
            'requested_by'     => Auth::id(),
            'business_unit_id' => $order->business_unit_id,
            'branch_id'        => $order->branch_id,
            'total_amount'     => $order->grand_total,
            'reason'           => $this->cancelReason,
        ]);

        $this->dispatch('toast', title: 'Berhasil', message: 'Pengajuan pembatalan berhasil dikirim ke Pusat.', type: 'success');
        $this->dispatch('orderCancellationSubmitted'); // So parent can refresh
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.components.cancel-order-modal');
    }
}
