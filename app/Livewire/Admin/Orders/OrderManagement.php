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
    public $warehouseFilter = ''; // Filter per warehouse

    // Properties for Receipt Modal
    public $showReceiptModal = false;
    public $selectedOrderForReceipt = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function viewReceipt(int $orderId): void
    {
        $this->selectedOrderForReceipt = Order::with([
            'user.profile',
            'items.variant.product',
            // 'items.variant.secondProduct', 
            'handledBy',
            'salesBy',
            'payments.paymentMethod',
            'payments.paymentMethodRate'
        ])->find($orderId);

        $this->showReceiptModal = true;
    }

    public function closeReceipt(): void
    {
        $this->showReceiptModal = false;
        $this->selectedOrderForReceipt = null;
    }

    /**
     * Helper terpusat untuk bikin PDF
     */
    private function generateReceiptPdf($order)
    {
        // Menggunakan kertas thermal POS 80mm
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', compact('order'))
            ->setPaper([0, 0, 226, 600], 'portrait');
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

        // Generate file PDF
        $pdf = $this->generateReceiptPdf($order);
        $pdfContent = $pdf->output();
        $filename = 'Struk_' . $order->order_number . '.pdf';

        try {
            Mail::mailer('pos_sales')
                ->to($order->user->email)
                ->send(new SalesReceiptMail($order, $pdfContent, $filename));


            $this->dispatch('toast', title: 'Berhasil', message: "Re-send Email berhasil untuk #{$order->order_number}", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'SMTP Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // Method Admin untuk kirim ulang WhatsApp Mekari Qontak + PDF Attachment
    public function resendWhatsApp(int $orderId): void
    {
        // Ambil data order paling fresh beserta profile user-nya
        $order = Order::with(['user.profile'])->find($orderId);

        if (!$order) {
            $this->dispatch('toast', title: 'Gagal', message: 'Data order tidak ditemukan.', type: 'error');
            return;
        }

        $phone = $order->user->profile->phone_number ?? null;

        if (!$phone) {
            $this->dispatch('toast', title: 'Gagal', message: 'Nomor HP tidak ditemukan.', type: 'warning');
            return;
        }

        // Standardisasi nomor HP (08xx -> 628xx)
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // ─── 1. PROSES GENERATE PDF & SIMPAN KE STORAGE PUBLIK ────
        try {
            // Memanggil helper terpusat yang sudah kamu miliki
            $pdf = $this->generateReceiptPdf($order);

            $filename = 'Struk_' . $order->order_number . '.pdf';
            $folderPath = 'receipts';
            $path = $folderPath . '/' . $filename;

            // Simpan ke disk public dengan visibilitas public
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output(), 'public');

            // Generate URL Publik (Gunakan Ngrok/Expose saat testing lokal!)
            $pdfPublicUrl = asset('storage/' . $path);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Qontak Resend PDF Storage Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal memproses file PDF struk.', type: 'error');
            return;
        }

        // 2. Tarik variabel dari env
        $fullUrl = env('QONTAK_API_URL');
        $method = 'POST';

        $parsedUrl = parse_url($fullUrl);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $endpoint = $parsedUrl['path'];

        $clientId = env('QONTAK_CLIENT_ID');
        $clientSecret = env('QONTAK_CLIENT_SECRET');

        // ─── 2. PROSES GENERATE HMAC SIGNATURE ────
        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";

        $stringToSign = "date: {$dateString}\n{$requestLine}";

        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);

        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // ─── 3. STRUKTUR PAYLOAD BODY JSON (DENGAN HEADER ATTACHMENT) ────
        $payload = [
            'to_name' => $order->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' => env('QONTAK_CHANNEL_INTEGRATION_ID'),
            'message_template_id' => env('QONTAK_TEMPLATE_ID'),
            'language' => [
                'code' => 'id'
            ],
            'parameters' => [
                // Disuntikkan object header berkas PDF sesuai dokumentasi Postman
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        [
                            'key' => 'url',
                            'value' => $pdfPublicUrl
                        ],
                        [
                            'key' => 'filename',
                            'value' => $filename
                        ]
                    ]
                ],
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

        // ─── 4. EXECUTE API CALL ─────────────────────────────────────────
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($fullUrl, $payload);

            if ($response->successful()) {
                $this->dispatch('toast', title: 'Berhasil', message: "Re-send WA + PDF Sukses untuk #{$order->order_number}", type: 'success');
            } else {
                \Illuminate\Support\Facades\Log::error('=== DEBUG MEKARI QONTAK RESEND ERROR ===');
                \Illuminate\Support\Facades\Log::error('Status Code: ' . $response->status());
                \Illuminate\Support\Facades\Log::error('Response Body: ' . $response->body());
                \Illuminate\Support\Facades\Log::error('========================================');

                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari error code: ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Qontak Resend Crash: ' . $e->getMessage());
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

        if ($this->warehouseFilter) {
            $query->whereHas('handledBy', function ($q) {
                $q->where('warehouse_id', $this->warehouseFilter);
            });
        }

        return view('livewire.admin.orders.order-management', [
            'orders' => $query->paginate(10),
            'warehouses' => \App\Models\Warehouse::all(),
        ]);
    }
}
