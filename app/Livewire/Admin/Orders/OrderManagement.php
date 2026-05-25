<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalesReceiptMail;

class OrderManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // Ubah status pesanan
    public function updateOrderStatus(int $orderId, string $status): void
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->update(['order_status' => $status]);
            $this->dispatch('toast', title: 'Berhasil', message: "Status pesanan diubah ke $status", type: 'success');
        }
    }

    // Method Admin untuk kirim ulang Email
    public function resendEmail(int $orderId): void
    {
        $order = Order::with(['user', 'items', 'handledBy', 'paymentMethod'])->find($orderId);
        if (!$order || !$order->user?->email || str_contains($order->user->email, '@pos.tokopun.com')) {
            $this->dispatch('toast', title: 'Gagal', message: 'Data email order tidak valid.', type: 'warning');
            return;
        }
        // dd($order);
        try {
            Mail::mailer('pos_sales')
                ->to($order->user->email)
                ->send(new SalesReceiptMail($order));


            $this->dispatch('toast', title: 'Berhasil', message: "Re-send Email berhasil untuk #{$order->order_number}", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'SMTP Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // Method Admin untuk kirim ulang WhatsApp Mekari Qontak
    public function resendWhatsApp(int $orderId): void
    {
        $order = Order::with(['user.profile'])->find($orderId);
        $phone = $order->user->profile->phone_number ?? null;

        if (!$phone) {
            $this->dispatch('toast', title: 'Gagal', message: 'Nomor HP tidak ditemukan.', type: 'warning');
            return;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // 2. Tarik variabel dari env
        $fullUrl = env('QONTAK_API_URL');
        $method = 'POST';

        // Ganti skema parse_url ala JavaScript Postman
        $parsedUrl = parse_url($fullUrl);

        // $baseUrl akan berisi "https://api.mekari.com"
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        // $endpoint otomatis akan mengambil path murni "/qontak/chat/v1/broadcasts/whatsapp/direct"
        $endpoint = $parsedUrl['path'];

        $clientId = env('QONTAK_CLIENT_ID');
        $clientSecret = env('QONTAK_CLIENT_SECRET');

        // ─── 2. PROSES GENERATE HMAC SIGNATURE (TETAP AMAN & PRESISI) ────
        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";

        $stringToSign = "date: {$dateString}\n{$requestLine}";

        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);

        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // ─── 3. STRUKTUR PAYLOAD BODY JSON (100% SAMA DENGAN POSTMAN) ────
        $payload = [
            'to_name' => $order->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' => env('QONTAK_CHANNEL_INTEGRATION_ID'),
            'message_template_id' => env('QONTAK_TEMPLATE_ID'),
            'language' => [
                'code' => 'id'
            ],
            'parameters' => [
                'body' => [
                    [
                        'key' => '1',
                        'value' => 'nama',
                        'value_text' => $order->user->name ?? 'Customer'
                    ],
                    [
                        'key' => '2',
                        'value' => 'no_invoice',
                        'value_text' => $order->order_number
                    ],
                    [
                        'key' => '3',
                        'value' => 'total_tagihan',
                        'value_text' => 'Rp ' . number_format($order->grand_total, 0, ',', '.')
                    ]
                ]
            ]
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
            ])->post(env('QONTAK_API_URL'), $payload);

            if ($response->successful()) {
                $this->dispatch('toast', title: 'Berhasil', message: "Re-send WA Sukses untuk #{$order->order_number}", type: 'success');
            } else {
                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari error code: ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.admin', ['title' => 'Kelola Pesanan'])]
    public function render()
    {
        $query = Order::with(['user', 'items', 'shipping'])
            ->orderByDesc('created_at');

        if ($this->search) {
            $query->where('order_number', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        }

        if ($this->statusFilter) {
            $query->where('order_status', $this->statusFilter);
        }

        return view('livewire.admin.orders.order-management', [
            'orders' => $query->paginate(10),
        ]);
    }
}
