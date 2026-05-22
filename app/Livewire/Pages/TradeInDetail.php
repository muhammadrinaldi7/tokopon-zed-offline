<?php

namespace App\Livewire\Pages;

use App\Models\TradeIn;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class TradeInDetail extends Component
{
    public TradeIn $tradeIn;
    public string $customerShippingReceipt = '';

    public function mount(TradeIn $tradeIn)
    {
        if ($tradeIn->user_id !== Auth::id() && !Auth::user()->hasRole('fl')) {
            abort(403);
        }
        $this->tradeIn = $tradeIn->load(['targetProduct.media', 'unitOptions.variant', 'media']);
        $this->customerShippingReceipt = $tradeIn->customer_shipping_receipt ?? '';
    }

    public function selectVariant($variantId)
    {
        if ($this->tradeIn->status !== 'OFFERED') {
            return;
        }

        // Tandai opsi yang dipilih
        foreach ($this->tradeIn->unitOptions as $option) {
            if ($option->product_variant_id == $variantId) {
                $option->update(['is_selected' => true]);
            } else {
                $option->update(['is_selected' => false]);
            }
        }

        if ($this->tradeIn->customer_shipping_receipt) {
            // Jika ini adalah revisi (barang sudah di toko / resi sudah ada)
            $this->tradeIn->update(['status' => 'INSPECTING']);
            $this->tradeIn->refresh();
            $this->dispatch('toast', title: 'Penawaran Disetujui', message: 'Anda telah menyetujui harga revisi. Menunggu tagihan akhir.', type: 'success');
        } else {
            // Alur normal pertama kali
            $this->tradeIn->update(['status' => 'WAITING_FOR_DEVICE']);
            $this->tradeIn->refresh();
            $this->dispatch('toast', title: 'Berhasil Memilih', message: 'Silakan kirimkan unit HP lama Anda ke kurir ekspedisi.', type: 'success');
        }
    }

    public function cancel()
    {
        if (!in_array($this->tradeIn->status, ['PENDING', 'OFFERED'])) {
            return;
        }

        $this->tradeIn->update(['status' => 'CANCELLED']);
        $this->dispatch('toast', title: 'Dibatalkan', message: 'Tukar tambah berhasil dibatalkan.', type: 'info');
    }

    public function acceptOffer()
    {
        if ($this->tradeIn->status !== 'OFFERED') return;

        \Illuminate\Support\Facades\DB::transaction(function () {
            $topupAmount = $this->tradeIn->topup_amount;
            $variant = $this->tradeIn->productVariant;

            // Build temporary Order
            $order = \App\Models\Order::create([
                'user_id' => $this->tradeIn->user_id,
                'order_number' => 'ORD-TRD-' . date('YmdHis') . rand(100, 999),
                'total_amount' => $topupAmount,
                'shipping_cost' => 0,
                'discount_amount' => 0,
                'grand_total' => $topupAmount,
                'order_status' => 'PENDING',
                'shipping_address_snapshot' => ['address' => 'Tukar Tambah Unit', 'phone_number' => '0000', 'city' => '-', 'postal_code' => '0000'],
            ]);

            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $variant->id,
                'product_variant_type' => get_class($variant),
                'qty' => 1,
                'price_at_checkout' => $variant->price,
                'subtotal' => $variant->price
            ]);
            
            // Deduct Stock immediately
            if ($variant) {
                $variant->decrement('stock', 1);
            }

            if ($topupAmount > 0) {
                // Manual Payment Flow
                \App\Models\OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'manual',
                    'xendit_external_id' => $order->order_number,
                    'amount' => $topupAmount,
                    'status' => 'PENDING',
                ]);
                $this->tradeIn->update(['status' => 'WAITING_PAYMENT', 'order_id' => $order->id]);
            } else {
                // Lunas karena harga sama atau appraise > harga unit incaran
                \App\Models\OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'wallet',
                    'xendit_external_id' => $order->order_number,
                    'amount' => 0,
                    'status' => 'PAID',
                ]);
                $order->update(['order_status' => 'COMPLETED']);
                $this->tradeIn->update(['status' => 'COMPLETED', 'order_id' => $order->id]);
            }
        });

        $this->dispatch('toast', title: 'Penawaran Disetujui', message: 'Silakan lanjutkan pembayaran.', type: 'success');
    }

    public function rejectOffer()
    {
        if ($this->tradeIn->status !== 'OFFERED') return;

        $this->tradeIn->update(['status' => 'CANCELLED']);
        $this->dispatch('toast', title: 'Dibatalkan', message: 'Penawaran ditolak secara sepihak.', type: 'info');
    }

    public function submitReceipt()
    {
        $this->validate(['customerShippingReceipt' => 'required|string|max:100']);

        $this->tradeIn->update([
            'customer_shipping_receipt' => $this->customerShippingReceipt,
            'status' => 'INSPECTING' // Beritahu Admin tiket ini sudah dikirim fisiknya
        ]);

        $this->dispatch('toast', title: 'Resi Terkirim', message: 'Manajer Cabang akan memvalidasi fisik HP Anda setelah barang tiba.', type: 'success');
    }

    #[Layout('layouts.app', ['title' => 'Detail Tukar Tambah'])]
    public function render()
    {
        return view('livewire.pages.trade-in-detail');
    }
}
