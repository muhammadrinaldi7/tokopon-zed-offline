<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Models\OrderIssue;
use Illuminate\Support\Facades\Auth;
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
    public $completedOrder = null;

    // Properties for Issue Modal
    public $showIssueModal = false;
    public $selectedOrderForIssue = null;
    public $issueCategory = 'SALAH_METODE_BAYAR';
    public $issueComment = '';

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
        $this->completedOrder = Order::with(['items.variant', 'user', 'payments.paymentMethod', 'handledBy', 'salesBy'])->find($orderId);

        if ($this->completedOrder) {
            $this->showReceiptModal = true;
        }
    }

    public function closeReceipt(): void
    {
        $this->showReceiptModal = false;
        $this->completedOrder = null;
    }

    public function openIssues(int $orderId): void
    {
        $this->selectedOrderForIssue = Order::with(['issues.user'])->find($orderId);
        if ($this->selectedOrderForIssue) {
            $this->issueCategory = 'SALAH_METODE_BAYAR';
            $this->issueComment = '';
            $this->showIssueModal = true;
        }
    }

    public function closeIssues(): void
    {
        $this->showIssueModal = false;
        $this->selectedOrderForIssue = null;
        $this->issueCategory = 'SALAH_METODE_BAYAR';
        $this->issueComment = '';
    }

    public function saveIssue(): void
    {
        if (!$this->selectedOrderForIssue) {
            return;
        }

        $this->validate([
            'issueCategory' => 'required|string|max:50',
            'issueComment' => 'required|string|min:3',
        ], [
            'issueCategory.required' => 'Kategori wajib dipilih.',
            'issueComment.required' => 'Catatan kendala wajib diisi.',
            'issueComment.min' => 'Catatan kendala minimal 3 karakter.',
        ]);

        OrderIssue::create([
            'order_id' => $this->selectedOrderForIssue->id,
            'user_id' => Auth::id(),
            'category' => $this->issueCategory,
            'comment' => trim($this->issueComment),
            'status' => 'OPEN',
        ]);

        $this->issueComment = '';
        $this->issueCategory = 'SALAH_METODE_BAYAR';
        $this->selectedOrderForIssue->refresh();
        $this->selectedOrderForIssue->load(['issues.user']);

        $this->dispatch('toast', title: 'Berhasil', message: 'Catatan kendala berhasil ditambahkan.', type: 'success');
    }

    public function toggleIssueStatus(int $issueId): void
    {
        $issue = OrderIssue::find($issueId);
        if ($issue) {
            $newStatus = $issue->status === 'OPEN' ? 'RESOLVED' : 'OPEN';
            $issue->update(['status' => $newStatus]);

            if ($this->selectedOrderForIssue) {
                $this->selectedOrderForIssue->refresh();
                $this->selectedOrderForIssue->load(['issues.user']);
            }

            $message = $newStatus === 'RESOLVED' ? 'Kendala ditandai Selesai.' : 'Kendala dibuka kembali.';
            $this->dispatch('toast', title: 'Berhasil', message: $message, type: 'info');
        }
    }

    public function deleteIssue(int $issueId): void
    {
        $issue = OrderIssue::find($issueId);
        if ($issue) {
            $issue->delete();
            if ($this->selectedOrderForIssue) {
                $this->selectedOrderForIssue->refresh();
                $this->selectedOrderForIssue->load(['issues.user']);
            }
            $this->dispatch('toast', title: 'Berhasil', message: 'Catatan kendala dihapus.', type: 'success');
        }
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

    public function sendReceiptToEmail()
    {
        if (!$this->completedOrder) return;
        $this->resendEmail($this->completedOrder->id);
        $this->completedOrder->refresh();
    }

    public function sendReceiptToQontak()
    {
        if (!$this->completedOrder) return;
        $this->resendWhatsApp($this->completedOrder->id);
        $this->completedOrder->refresh();
    }

    public function getEscposBase64()
    {
        if (!$this->completedOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Tidak ada transaksi aktif untuk dicetak.', type: 'error');
            return;
        }

        try {
            $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->initialize();

            $this->generateEscposContent($printer);
            $printer->feed(1);
            $printer->cut();

            $data = $connector->getData();
            $base64 = base64_encode($data);

            $printer->close();

            $orderNumber = $this->completedOrder->order_number ?? 'terbaru';
            $this->dispatch('print-receipt', base64Data: $base64, orderNumber: $orderNumber);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ESCPOS Base64 Generation Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal memproses cetakan: ' . $e->getMessage(), type: 'error');
        }
    }

    private function generateEscposContent($printer)
    {
        $maxColumns = 40;
        $separator = str_repeat("-", $maxColumns) . "\n";

        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(
            \Mike42\Escpos\Printer::MODE_FONT_B |
                \Mike42\Escpos\Printer::MODE_DOUBLE_WIDTH |
                \Mike42\Escpos\Printer::MODE_DOUBLE_HEIGHT
        );
        $storeTitle = optional($this->completedOrder->businessUnit)->store_title ?? 'Z-POS STORE';
        $printer->text($storeTitle . "\n");
        $printer->selectPrintMode(\Mike42\Escpos\Printer::MODE_FONT_B);

        $storeName = $this->completedOrder->shipping_address_snapshot['store'] ?? 'Toko';
        $printer->text($storeName . "\n");
        $printer->text($this->completedOrder->created_at->format('d/m/Y H:i') . "\n");
        $printer->text($separator);

        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
        $printer->text($this->formatLine("No. Transaksi", $this->completedOrder->order_number, $maxColumns) . "\n");
        $printer->text($this->formatLine("Kasir", $this->completedOrder->handledBy->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Sales", $this->completedOrder->salesBy->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Customer", $this->completedOrder->user->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Customer No", $this->completedOrder->user->profile->phone_number ?? '-', $maxColumns) . "\n");
        $printer->text($separator);

        foreach ($this->completedOrder->items as $item) {
            $v = $item->variant;

            if ($v instanceof \App\Models\ProductAccurate) {
                $itemName = $v->name ?? '-';
                $ram = '';
                $storage = '';
                $color = '';
            } else {
                $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
                $ram = $v ? $v->ram ?? '' : '';
                $storage = $v ? $v->storage ?? '' : '';
                $color = $v ? $v->color ?? '' : '';
            }

            $itemName = preg_replace('/^(?:DS\s*-\s*HP\s*|DS\s*-\s*|HP\s*-\s*|HP\s*)/i', '', trim($itemName));

            if ($v && !($v instanceof \App\Models\ProductAccurate)) {
                $variantDetails = "";
                if ($ram != null && $ram !== '') $variantDetails .= $ram . "/";
                $variantDetails .= $storage;
                if ($color != null && $color !== '') $variantDetails .= " " . $color;
                if (trim($variantDetails) !== '') $itemName .= " " . trim($variantDetails);
            }

            $printer->text($itemName . "\n");

            $qtyAndPrice = $item->qty . "x Rp " . number_format($item->price_at_checkout, 0, ',', '.');
            $subtotal = "Rp " . number_format($item->subtotal, 0, ',', '.');

            $printer->text($this->formatLine(" " . $qtyAndPrice, $subtotal, $maxColumns) . "\n");

            if ($item->serial_number) {
                $printer->text(" SN: " . $item->serial_number . "\n");
            }
        }
        $printer->text($separator);

        $showDiscount = optional($this->completedOrder->businessUnit)->receipt_show_discount;
        if ($showDiscount) {
            $printer->text($this->formatLine("Subtotal", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), $maxColumns) . "\n");
            if ($this->completedOrder->discount_amount > 0) {
                $printer->text($this->formatLine("Diskon", "-Rp " . number_format($this->completedOrder->discount_amount, 0, ',', '.'), $maxColumns) . "\n");
            }
            $printer->text($this->formatLine("TOTAL", "Rp " . number_format($this->completedOrder->grand_total, 0, ',', '.'), $maxColumns) . "\n");
        } else {
            $printer->text($this->formatLine("Total", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), $maxColumns) . "\n");
        }
        $printer->text($separator);
        if ($this->completedOrder->accurate_invoice_no) {
            $printer->text($this->formatLine("No. SI", $this->completedOrder->accurate_invoice_no, $maxColumns) . "\n");
        }
        $printer->text($separator);
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->text("\nTerima kasih telah berbelanja!\n");
        $printer->text("Call Center : 0811-5600-6464\n");
        $printer->text("\n\n\n\n\n");
    }

    private function formatLine($left, $right, $width = 58)
    {
        $leftWidth = strlen($left);
        $rightWidth = strlen($right);
        $spaces = $width - $leftWidth - $rightWidth;
        if ($spaces < 1) $spaces = 1;
        return $left . str_repeat(' ', $spaces) . $right;
    }

    #[Layout('layouts.admin', ['title' => 'Kelola Pesanan'])]
    public function render()
    {
        $query = Order::with(['user', 'items', 'shipping'])
            ->withCount(['openIssues'])
            ->orderByDesc('created_at');

        if ($this->search) {
            $query->where('order_number', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('items', function ($iq) {
                    $iq->where('serial_number', 'like', '%' . $this->search . '%');
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
            'openIssuesTotal' => OrderIssue::where('status', 'OPEN')->count(),
        ]);
    }
}
