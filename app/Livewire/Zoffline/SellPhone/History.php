<?php

namespace App\Livewire\Zoffline\SellPhone;

use App\Models\SellPhone;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\SellPhoneReceiptMail;


#[Layout('layouts.z', ['title' => 'History Sell Phone'])]
class History extends Component
{
    public $showReceiptModal = false;
    public $selectedSell = null;

    public function showReceipt(SellPhone $sellPhone)
    {
        $this->selectedSell = $sellPhone->load(['handledBy', 'user.profile', 'user.bankAccounts', 'businessUnit']);
        $this->showReceiptModal = true;
    }

    public function closeReceipt()
    {
        $this->showReceiptModal = false;
        $this->selectedSell = null;
    }

    private function generateReceiptPdf(SellPhone $sellPhone)
    {
        // 1. Inisialisasi DOMPDF dengan config
        $pdf = Pdf::loadView('pdf.sell-phone-receipt', ['sellPhone' => $sellPhone]);

        // 2. Set ukuran custom (Thermal Printer width 80mm)
        // 80mm width. Tinggi dibiarkan panjang agar tidak terpotong (misal 500mm), 
        // thermal printer biasanya akan memotong otomatis setelah teks habis.
        $customPaper = array(0, 0, 226.77, 1000); 
        $pdf->setPaper($customPaper, 'portrait');

        return $pdf;
    }

    public function sendReceiptToQontak()
    {
        if (!$this->selectedSell) return;

        $sellPhoneId = $this->selectedSell->id;
        $sellPhone = SellPhone::with('user.profile')->find($sellPhoneId);
        $phone = $sellPhone->user->profile->phone_number ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $sellPhone->is_wa_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk WhatsApp hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$phone) {
            $this->dispatch('toast', title: 'Gagal', message: 'Nomor HP customer tidak ditemukan.', type: 'warning');
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        $fullUrl = config('services.qontak.api_url');
        if (empty($fullUrl)) {
            $this->dispatch('toast', title: 'Gagal', message: 'URL Qontak tidak ditemukan di konfigurasi (.env).', type: 'error');
            return;
        }
        if (!preg_match("~^(?:f|ht)tps?://~i", $fullUrl)) {
            $fullUrl = "https://" . $fullUrl;
        }

        $method = 'POST';
        $parsedUrl = parse_url($fullUrl);
        $endpoint = $parsedUrl['path'] ?? '';
        $clientId = config('services.qontak.client_id');
        $clientSecret = config('services.qontak.client_secret');

        try {
            $pdf = $this->generateReceiptPdf($sellPhone);
            $filename = 'Struk_SellPhone_' . $sellPhone->id . '.pdf';
            $folderPath = 'receipts_sellphone';
            $path = $folderPath . '/' . $filename;

            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());
            $pdfPublicUrl = asset('storage/' . $path);
        } catch (\Exception $e) {
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
            'to_name' => $sellPhone->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' =>  config('services.qontak.integration_id'),
            'message_template_id' => config('services.qontak.template_id'),
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
                    ['key' => '1', 'value' => 'nama', 'value_text' => $sellPhone->user->name ?? 'Customer'],
                    ['key' => '2', 'value' => 'no_invoice', 'value_text' => 'SPL-' . $sellPhone->id],
                    ['key' => '3', 'value' => 'total_tagihan', 'value_text' => 'Rp ' . number_format($sellPhone->appraised_value, 0, ',', '.')]
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($fullUrl, $payload);

            if ($response->successful()) {
                $sellPhone->update(['is_wa_sent' => true]);
                $this->selectedSell->refresh();
                $this->dispatch('toast', title: 'Berhasil', message: 'Struk WA berhasil dikirim!', type: 'success');
            } else {
                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari: Code ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }

    public function sendReceiptToEmail()
    {
        if (!$this->selectedSell) return;

        $sellPhoneId = $this->selectedSell->id;
        $sellPhone = SellPhone::with('user')->find($sellPhoneId);
        $email = $sellPhone->user->email ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $sellPhone->is_email_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk email hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$email || str_contains($email, '@pos.tokopun.com') || str_contains($email, '@tokopon.com')) {
            $this->dispatch('toast', title: 'Gagal Kirim', message: 'Email customer tidak valid atau kosong.', type: 'warning');
            return;
        }

        try {
            $pdf = $this->generateReceiptPdf($sellPhone);
            $pdfContent = $pdf->output();
            $filename = 'Struk_SellPhone_' . $sellPhone->id . '.pdf';

            Mail::mailer('pos_sales')
                ->to($email)
                ->send(new SellPhoneReceiptMail($sellPhone, $pdfContent, $filename));

            $sellPhone->update(['is_email_sent' => true]);
            $this->selectedSell->refresh();
            $this->dispatch('toast', title: 'Berhasil', message: 'Struk digital telah dikirim ke ' . $email, type: 'success');
        } catch (\Exception $e) {
            Log::error('POS Email Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Koneksi SMTP bermasalah: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $branch = Auth::user()->branch->id;
        if (Auth::user()) {
            // Tampilkan history yang kolom 'handled_by'-nya adalah ID FL yang sedang login
            $sells = SellPhone::with(['media', 'handledBy', 'branch'])
                ->where('branch_id', Auth::user()->branch_id)
                ->latest()
                ->get();
        }
        return view('livewire.zoffline.sell-phone.history', compact('sells'));
    }
}
