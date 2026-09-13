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
use App\Services\MessageDispatchService;

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

        $extension = pathinfo($this->sellPhone->payment_receipt_path, PATHINFO_EXTENSION);
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
            $documentUrl = asset('storage/' . $this->sellPhone->payment_receipt_path);
            $filename = 'Bukti_Transfer_SPL' . $this->sellPhone->id . '.' . ($extension ?: 'jpg');
        }

        $result = app(MessageDispatchService::class)->sendSellPhonePaymentProofWhatsApp($this->sellPhone, $documentUrl, $filename, Auth::user());

        if ($result['success']) {
            $this->dispatch('toast', title: 'Berhasil', message: 'Bukti pembayaran berhasil dikirim via WA!', type: 'success');
        } else {
            $this->dispatch('toast', title: 'Gagal', message: $result['message'], type: 'error');
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

        $result = app(MessageDispatchService::class)->sendSellPhonePaymentProofEmail($this->sellPhone, $this->recipientEmail, Auth::user());

        $this->sellPhone->refresh();

        if ($result['success']) {
            $this->isEmailModalOpen = false;
            $this->dispatch('toast', title: 'Berhasil', message: $result['message'], type: 'success');
        } else {
            $this->dispatch('toast', title: 'Gagal', message: $result['message'], type: 'error');
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
