<?php

namespace App\Livewire\Pages;

use App\Models\SellPhone;
use Generator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\AccurateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\SellPhonePaymentReceiptMail;

class SellPhoneDetail extends Component
{
    public SellPhone $sellPhone;
    public string $customerShippingReceipt = '';
    public $dataParamPurchaseInvoice = [];

    // Form Bank Info
    public $isEditBankOpen = false;
    public $bank_name = '';
    public $account_number = '';
    public $account_name = '';

    // Form Email Bukti Transfer
    public $isEmailModalOpen = false;
    public $recipientEmail = '';

    public function mount(SellPhone $sellPhone)
    {

        $this->sellPhone = $sellPhone->load('buybackDevice', 'user', 'inspections');
        $this->customerShippingReceipt = $sellPhone->customer_shipping_receipt ?? '';
        // dd($this->sellPhone);
    }

    #[Computed]
    public function phoneData()
    {
        // loadMissing akan me-load relasi hanya saat data ini dipanggil di Blade
        return $this->sellPhone->loadMissing(['buybackDevice.secondProductVariant', 'user']);
    }
    public function acceptOffer()
    {
        if ($this->sellPhone->status === 'OFFERED') {
            $this->sellPhone->update(['status' => 'WAITING_FOR_DEVICE']);
            $this->dispatch('show-toast', type: 'success', message: 'Penawaran Diterima! Silakan kirimkan unit HP Anda ke toko kami.');
        } elseif ($this->sellPhone->status === 'REVISED_OFFER') {
            $this->sellPhone->update(['status' => 'PAYING']);
            $this->dispatch('show-toast', type: 'success', message: 'Revisi disetujui! Dana akan segera dicairkan.');
        }
    }

    public function cancel()
    {
        if (!in_array($this->sellPhone->status, ['PENDING', 'OFFERED', 'REVISED_OFFER'])) return;
        $this->sellPhone->update(['status' => 'CANCELLED']);
        $this->dispatch('show-toast', type: 'info', message: 'Pengajuan Jual HP dibatalkan.');
    }

    public function submitReceipt()
    {
        $this->validate(['customerShippingReceipt' => 'required|string|min:5']);
        $this->sellPhone->update([
            'customer_shipping_receipt' => $this->customerShippingReceipt,
            'status' => 'INSPECTING'
        ]);
        return $this->redirect(route('sell-phone-history'));
    }

    public function sendPaymentReceiptToQontak()
    {
        if (!$this->sellPhone->payment_receipt_path) {
            $this->dispatch('toast', title: 'Gagal', message: 'Bukti pembayaran belum diupload oleh finance.', type: 'warning');
            return;
        }

        $phone = $this->sellPhone->user->profile->phone_number ?? null;

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

        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";
        $stringToSign = "date: {$dateString}\n{$requestLine}";
        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);
        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        $extension = pathinfo($this->sellPhone->payment_receipt_path, PATHINFO_EXTENSION);
        
        // Qontak expects a DOCUMENT (e.g., pdf) because of the template header.
        // We will wrap the uploaded image into a PDF file on the fly.
        $imagePath = storage_path('app/public/' . $this->sellPhone->payment_receipt_path);
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
            $src = 'data:' . $mimeType . ';base64,' . $imageData;
            
            $html = '
            <html>
            <body style="margin: 0; padding: 20px; text-align: center;">
                <h3>Bukti Pembayaran Sell Phone</h3>
                <img src="' . $src . '" style="max-width: 100%; height: auto;">
            </body>
            </html>';
            
            $pdf = Pdf::loadHTML($html);
            $pdfPath = 'payment_receipts/pdf_bukti_' . $this->sellPhone->id . '.pdf';
            Storage::disk('public')->put($pdfPath, $pdf->output());
            
            $documentUrl = asset('storage/' . $pdfPath);
            $filename = 'Bukti_Transfer_SPL' . $this->sellPhone->id . '.pdf';
        } else {
            // Fallback if file doesn't exist locally for some reason
            $documentUrl = asset('storage/' . $this->sellPhone->payment_receipt_path);
            $filename = 'Bukti_Transfer_SPL' . $this->sellPhone->id . '.' . ($extension ?: 'jpg');
        }

        $payload = [
            'to_name' => $this->sellPhone->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' =>  config('services.qontak.integration_id'),
            'message_template_id' => config('services.qontak.template_id'),
            'language' => ['code' => 'id'],
            'parameters' => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $documentUrl],
                        ['key' => 'filename', 'value' => $filename]
                    ]
                ],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $this->sellPhone->user->name ?? 'Customer'],
                    ['key' => '2', 'value' => 'no_invoice', 'value_text' => 'SPL-' . $this->sellPhone->id],
                    ['key' => '3', 'value' => 'total_tagihan', 'value_text' => 'Rp ' . number_format($this->sellPhone->appraised_value, 0, ',', '.')]
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
                $this->dispatch('toast', title: 'Berhasil', message: 'Bukti pembayaran berhasil dikirim via WA!', type: 'success');
            } else {
                Log::error('Mekari API Error: ' . $response->status() . ' - ' . $response->body());
                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari: Code ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            Log::error('Mekari API Exception: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openEmailModal()
    {
        if (!$this->sellPhone->payment_receipt_path) {
            $this->dispatch('toast', title: 'Gagal', message: 'Bukti pembayaran belum diupload oleh finance.', type: 'warning');
            return;
        }

        $email = $this->sellPhone->user->email ?? '';
        // If dummy pos/offline email, clear it so user can enter the customer's real email
        if (str_contains($email, '@customer.zpos.local') || str_contains($email, '@pos.tokopun.com') || str_contains($email, '@tokopon.com')) {
            $this->recipientEmail = '';
        } else {
            $this->recipientEmail = $email;
        }

        $this->isEmailModalOpen = true;
    }

    public function closeEmailModal()
    {
        $this->isEmailModalOpen = false;
    }

    public function sendPaymentReceiptToEmail()
    {
        if (!$this->sellPhone->payment_receipt_path) {
            $this->dispatch('toast', title: 'Gagal', message: 'Bukti pembayaran belum diupload oleh finance.', type: 'warning');
            return;
        }

        $this->validate([
            'recipientEmail' => 'required|email'
        ], [
            'recipientEmail.required' => 'Email penerima wajib diisi.',
            'recipientEmail.email' => 'Format email tidak valid.'
        ]);

        $fileExists = Storage::disk('public')->exists($this->sellPhone->payment_receipt_path)
            || file_exists(storage_path('app/public/' . $this->sellPhone->payment_receipt_path))
            || file_exists(public_path('storage/' . $this->sellPhone->payment_receipt_path));

        if (!$fileExists) {
            Log::warning("Bukti transfer file not found on disk: {$this->sellPhone->payment_receipt_path}");
        }

        try {
            $mailer = config('mail.mailers.pos_sales.host') ? Mail::mailer('pos_sales') : Mail::mailer();

            $mailer->to($this->recipientEmail)
                ->send(new SellPhonePaymentReceiptMail($this->sellPhone));

            $this->sellPhone->update(['is_email_sent' => true]);
            $this->sellPhone->refresh();

            $this->isEmailModalOpen = false;
            $this->dispatch('toast', title: 'Berhasil', message: 'Bukti pembayaran berhasil dikirim ke ' . $this->recipientEmail, type: 'success');
        } catch (\Exception $e) {
            Log::error('Send Payment Receipt Email Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Kirim Email', message: 'SMTP Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function saveBankInfo()
    {
        $this->validate([
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
        ]);

        $this->sellPhone->user->bankAccounts()->create([
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
        ]);

        $this->dispatch('toast', title: 'Berhasil', message: 'Informasi Rekening Bank berhasil disimpan.', type: 'success');
        $this->isEditBankOpen = false;
        $this->sellPhone->load('user.bankAccounts'); // Refresh data
    }



    public function submitComplete()
    {
        // Asumsi generator Anda, misal menambahkan prefix TPB- dengan ID SellPhone
        // Silakan ganti dengan class/fungsi Generator asli milik Anda
        $billNumber = 'TPD-' . date('dmY') . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT);

        if ($this->sellPhone->status === 'PAYING') {

            // Menggunakan method computed phoneData agar relasi ter-load dengan aman
            $phoneData = $this->phoneData;

            // 1. Susun Array untuk detailItem terlebih dahulu agar lebih rapi
            $detailItem = [
                [
                    // Pastikan memanggil kolom yang sesuai dari tabel devices/sell_phones Anda
                    'itemNo' => $phoneData->buybackDevice->secondProductVariant->sku ?? 'TES-001',
                    'warehouseName' => Auth::user()->warehouse->name, // Sesuaikan jika dinamis
                    'unitPrice' => (int) $this->sellPhone->appraised_value, // Harga yang disepakati
                    'quantity' => 1,

                    // Array di dalam array untuk serial number
                    'detailSerialNumber' => [
                        [
                            'serialNumberNo' => 'SN-' . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT) ?? 'SN-UNKNOWN', // Kolom IMEI/SN HP
                            'quantity' => 1
                        ]
                    ]
                ]
            ];

            // 2. Masukkan ke dalam parameter utama Purchase Invoice Accurate
            $this->dataParamPurchaseInvoice = [
                'billNumber' => $billNumber,
                'vendorNo' => str_replace('"', '', $phoneData->user->accurate_vendor_no),
                'branchName' => Auth::user()->branch->name,
                // Field tambahan yang Anda tulis sebelumnya (opsional/dibutuhkan Accurate)
                // 'name' => $phoneData->user->profile->full_name ?? '',
                'transDate' => date('d/m/Y'),
                'currencyCode' => 'IDR',
                'description' => 'Pembelian HP - NIK:' . ($phoneData->user->identity ?? '-'),
                // Sisipkan array detailItem yang sudah dibentuk di atas
                'detailItem' => $detailItem,
            ];

            // Opsional: Cek struktur datanya sebelum di-hit ke API Accurate
            // dd($this->dataParamPurchaseInvoice);
            // 4. Eksekusi Service API dengan Try-Catch
            DB::beginTransaction();
            try {
                // Hit API menggunakan service yang di-inject
                $accurateResponse = app(AccurateService::class)->postPurchaseInvoice($this->dataParamPurchaseInvoice);
                Log::info('data invoice yang masuk ke accurate : ', ['data' => $this->dataParamPurchaseInvoice, 'response' => $accurateResponse]);
                // JIKA BERHASIL: Update status dan redirect
                $this->sellPhone->update([
                    'invoice_number' => $billNumber,
                    'status' => 'COMPLETED'
                ]);

                $this->dispatch('toast', [
                    'type' => 'success',
                    'title' => 'Success',
                    'message' => 'Invoice Accurate Berhasil Dibuat. Pengajuan Jual HP Selesai.'
                ]);
                DB::commit();
                return $this->redirect(route('sell-phone-history'));
            } catch (\Exception $e) {
                // JIKA GAGAL: Tangkap error dari service dan tampilkan ke user via Toast
                // Status SellPhone TIDAK diupdate ke COMPLETED, sehingga user bisa mencoba klik submit lagi
                DB::rollBack();
                Log::error('API Accurate Failed: ' . $e->getMessage());
                $this->dispatch('toast', [
                    'type' => 'error',
                    'title' => 'Error',
                    'message' => 'Gagal membuat faktur di Accurate: ' . $e->getMessage()
                ]);
            }
        }
    }

    #[Layout('layouts.z', ['title' => 'Detail Penjualan HP'])]
    public function render()
    {
        return view('livewire.pages.sell-phone-detail');
    }
}
