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

    public function newTransaction()
    {
        return $this->redirect(route('zoffline.pos'), navigate: true);
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
        $storeTitle = optional($this->completedOrder->businessUnit)->code === 'second' ? 'GSK STORE' : 'SYIHAB STORE';
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
            
            // Bersihkan awalan nama
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

            // Mengurangi space di depan menjadi 1 spasi saja agar menghemat karakter yang makin sempit
            $printer->text($this->formatLine(" " . $qtyAndPrice, $subtotal, $maxColumns) . "\n");

            if ($item->serial_number) {
                $printer->text(" SN: " . $item->serial_number . "\n");
            }
        }
        $printer->text($separator);

        // Total Section
        $isGsk = optional($this->completedOrder->businessUnit)->code === 'second';
        if ($isGsk) {
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

    private function generateReceiptPdf($order)
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', compact('order'))
            ->setPaper([0, 0, 226, 600], 'portrait');
    }

    public function sendReceiptToEmail()
    {
        if (!$this->completedOrder) return;

        $orderId = $this->completedOrder->id;
        $order = Order::with('user')->find($orderId);
        $email = $order->user->email ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $order->is_email_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk email hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$email || str_contains($email, '@pos.tokopun.com')) {
            $this->dispatch('toast', title: 'Gagal Kirim', message: 'Email customer tidak valid atau kosong.', type: 'warning');
            return;
        }

        try {
            $pdf = $this->generateReceiptPdf($order);
            $pdfContent = $pdf->output();
            $filename = 'Struk_' . $order->order_number . '.pdf';

            \Illuminate\Support\Facades\Mail::mailer('pos_sales')
                ->to($email)
                ->send(new \App\Mail\SalesReceiptMail($order, $pdfContent, $filename));

            $order->update(['is_email_sent' => true]);
            $this->completedOrder->refresh();
            $this->dispatch('toast', title: 'Berhasil', message: 'Struk digital telah dikirim ke ' . $email, type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('POS Email Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Koneksi SMTP bermasalah: ' . $e->getMessage(), type: 'error');
        }
    }

    public function sendReceiptToQontak()
    {
        if (!$this->completedOrder) return;

        $orderId = $this->completedOrder->id;
        $order = Order::with('user.profile')->find($orderId);
        $phone = $order->user->profile->phone_number ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $order->is_wa_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk WhatsApp hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$phone) {
            $this->dispatch('toast', title: 'Gagal', message: 'Nomor HP customer tidak ditemukan.', type: 'warning');
            return;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $fullUrl = env('QONTAK_API_URL');
        $method = 'POST';
        $parsedUrl = parse_url($fullUrl);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $endpoint = $parsedUrl['path'];
        $clientId = env('QONTAK_CLIENT_ID');
        $clientSecret = env('QONTAK_CLIENT_SECRET');

        try {
            $pdf = $this->generateReceiptPdf($order);
            $filename = 'Struk_' . $order->order_number . '.pdf';
            $folderPath = 'receipts';
            $path = $folderPath . '/' . $filename;
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());
            $pdfPublicUrl = asset('storage/' . $path);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Qontak PDF Storage Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal menyimpan file PDF struk ke server.', type: 'error');
            return;
        }

        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";
        $stringToSign = "date: {$dateString}\n{$requestLine}";
        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);
        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        $payload = [
            'to_name' => $order->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' => env('QONTAK_CHANNEL_INTEGRATION_ID'),
            'message_template_id' => env('QONTAK_TEMPLATE_ID'),
            'language' => ['code' => 'id'],
            'parameters' => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $pdfPublicUrl],
                        ['key' => 'filename', 'value' => $filename]
                    ]
                ],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $order->user->name ?? 'Customer'],
                    ['key' => '2', 'value' => 'no_invoice', 'value_text' => $order->order_number],
                    ['key' => '3', 'value' => 'total_tagihan', 'value_text' => 'Rp ' . number_format($order->total_amount, 0, ',', '.')]
                ]
            ]
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($fullUrl, $payload);

            if ($response->successful()) {
                $order->update(['is_wa_sent' => true]);
                $this->completedOrder->refresh();
                $this->dispatch('toast', title: 'Berhasil', message: 'Struk WA dengan PDF berhasil dikirim!', type: 'success');
            } else {
                \Illuminate\Support\Facades\Log::error('Mekari Qontak Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari: Code ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Qontak HMAC Integration Crash: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }
}
