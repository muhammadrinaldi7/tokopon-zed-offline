<?php

namespace App\Livewire\Admin\SellPhone;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SellPhone;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public SellPhone $sellPhone;

    // QC Status
    public $qcPassed = false;

    // Appraisal Form
    public $appraisedValue = 0;

    // Convert to Second Product
    public $convertModal = false;
    public $sellPrice = 0;
    public $secondCondition = 'Bekas';
    public $existingProductId = null;
    public $dataParamPurchaseInvoice = [];
    // Revision
    public $isRevising = false;
    public $revisedAppraisedValue = 0;

    // Payment fields
    public $paymentReceipt;
    public $storeBankNo;
    public $accurateGlAccounts = [];
    public $isReuploading = false;

    // Reject Form
    public $isRejecting = false;
    public $rejectReason = '';

    public function mount(SellPhone $sellPhone)
    {
        $this->sellPhone = $sellPhone->load(['user.bankAccounts', 'buybackDevice.tier', 'businessUnit']);
        $this->appraisedValue = $this->sellPhone->appraised_value ?? 0;
        $this->qcPassed = $this->sellPhone->hasPassedQc();

        $dbSource = $this->sellPhone->businessUnit ? strtolower($this->sellPhone->businessUnit->code) : 'gsk';

        // Load Accurate GL Accounts for Bank selection
        $this->accurateGlAccounts = \App\Models\AccurateGlAccount::where('account_type', 'CASH_BANK')
            ->where('database_source', $dbSource)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    #[On('qc-inspection-saved')]
    public function handleQcSaved($verdict)
    {
        $this->qcPassed = ($verdict === 'pass');
    }

    #[Computed]
    public function phoneData()
    {
        // loadMissing akan me-load relasi hanya saat data ini dipanggil di Blade
        return $this->sellPhone->loadMissing(['buybackDevice.secondProductVariant', 'user']);
    }
    public function submitAppraisal()
    {
        $this->validate([
            'appraisedValue' => 'required|numeric|min:1000'
        ]);

        $this->sellPhone->update([
            'appraised_value' => $this->appraisedValue,
            'status' => 'OFFERED',
        ]);

        $this->dispatch('show-toast', type: 'success', message: 'Penawaran berhasil disimpan dan dikirim ke pengguna.');
    }

    public function submitRevision()
    {
        $this->validate([
            'revisedAppraisedValue' => 'required|numeric|min:1000'
        ]);

        $this->sellPhone->update([
            'appraised_value' => $this->revisedAppraisedValue,
            'status' => 'REVISED_OFFER',
        ]);

        $this->isRevising = false;
        $this->dispatch('show-toast', type: 'success', message: 'Revisi penawaran berhasil dikirim ke pengguna.');
    }

    public function markAsPaid()
    {
        if (!$this->qcPassed) {
            $this->dispatch('toast', ['type' => 'error', 'title' => 'Gagal', 'message' => 'Lakukan Inspeksi QC terlebih dahulu dan pastikan statusnya LAYAK BELI (PASS).']);
            return;
        }

        $billNumber = 'TPD-' . date('dmY') . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT);

        if ($this->sellPhone->status === 'COMPLETED' || $this->sellPhone->status === 'CANCELLED') return;
        if ($this->sellPhone->status === 'INSPECTING') {
            $this->sellPhone->update(['status' => 'PAYING']);
            $this->dispatch('toast', ['type' => 'success', 'title' => 'Inspected', 'message' => 'Status penjualan HP ditandai sebagai Checked.']);
        } else if ($this->sellPhone->status === 'PAYING') {

            $this->validate([
                'paymentReceipt' => 'required|image|max:5120',
                'storeBankNo' => 'required|string',
            ], [
                'paymentReceipt.required' => 'Bukti bayar wajib diunggah.',
                'paymentReceipt.image' => 'Bukti bayar harus berupa gambar.',
                'storeBankNo.required' => 'Rekening asal toko wajib dipilih.',
            ]);

            $phoneData = $this->phoneData;
            $flUser = $this->sellPhone->handledBy;
            $accurateBranchName = $flUser && $flUser->branch ? $flUser->branch->name : 'Banjarbaru';
            $accurateWarehouseName = $flUser && $flUser->warehouse ? $flUser->warehouse->name : 'Head Office';

            $itemNo = $phoneData->buybackDevice->productAccurate->item_no ?? null;
            if (!$itemNo || $itemNo === 'TES-001') {
                $this->dispatch('toast', ['type' => 'error', 'title' => 'Gagal', 'message' => 'Barang (Master HP Bekas) belum disinkronkan dari Accurate. Item No tidak valid.']);
                return;
            }

            // 1. Susun Array untuk detailItem terlebih dahulu agar lebih rapi
            $detailItem = [
                [
                    // Gunakan itemNo dari ProductAccurate yang terkait
                    'itemNo' => $itemNo,
                    'warehouseName' => $accurateWarehouseName,
                    'unitPrice' => (int) $this->sellPhone->appraised_value, // Harga yang disepakati
                    'quantity' => 1,
                    'useTax1' => false,
                    // Array di dalam array untuk serial number
                    'detailSerialNumber' => [
                        [
                            'serialNumberNo' => $this->sellPhone->imei ?? 'NO-IMEI-' . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT), // Kolom IMEI/SN HP
                            'quantity' => 1
                        ]
                    ]
                ]
            ];

            // Tentukan database source dari Business Unit kasir/admin
            $dbSource = $flUser && $flUser->businessUnit ? strtolower($flUser->businessUnit->code) : 'gsk';

            // 2. Masukkan ke dalam parameter utama Purchase Invoice Accurate
            $vendorNoAwal = $phoneData->user->getAccurateVendorNo($dbSource) ?? 'V-CASH';
            $this->dataParamPurchaseInvoice = [
                'billNumber' => $billNumber,
                'vendorNo' => str_replace('"', '', $vendorNoAwal),
                'branchName' => $accurateBranchName,
                'inclusiveTax' => false,
                // Field tambahan yang Anda tulis sebelumnya (opsional/dibutuhkan Accurate)
                // 'name' => $phoneData->user->profile->full_name ?? '',
                'transDate' => date('d/m/Y'),
                'currencyCode' => 'IDR',
                'description' => 'Pembelian HP - NIK:' . ($phoneData->user->identity ?? '-'),
                // Sisipkan array detailItem yang sudah dibentuk di atas
                'detailItem' => $detailItem,
            ];
            // dd($this->dataParamPurchaseInvoice);

            // Opsional: Cek struktur datanya sebelum di-hit ke API Accurate
            // dd($this->dataParamPurchaseInvoice);
            try {
                // 3. Sync Vendor ke Accurate agar Vendor No pasti terisi/terupdate
                $customerUser = $phoneData->user;
                // dd($customerUser);
                $accurateService = app(AccurateService::class);
                $accurateService->syncVendor($customerUser, $dbSource);
                $customerUser->refresh();

                // Update vendor No di param
                $vendorNoBaru = $customerUser->getAccurateVendorNo($dbSource) ?? 'V-CASH';
                $this->dataParamPurchaseInvoice['vendorNo'] = str_replace('"', '', $vendorNoBaru);

                // 4. Hit API menggunakan service yang di-inject JIKA BELUM ADA
                if (!$this->sellPhone->invoice_number) {
                    $accurateResponse = $accurateService->postPurchaseInvoice($this->dataParamPurchaseInvoice, $dbSource);
                    Log::info('data invoice yang masuk ke accurate : ', ['data' => $this->dataParamPurchaseInvoice, 'response' => $accurateResponse]);

                    if (isset($accurateResponse['r']['number'])) {
                        // Simpan state secara iteratif
                        $this->sellPhone->update(['invoice_number' => $accurateResponse['r']['number']]);
                    } else {
                        // Fallback jika tidak ada number
                        $this->sellPhone->update(['invoice_number' => $billNumber]);
                    }
                }

                // Hit Purchase Payment
                if ($this->paymentReceipt && $this->storeBankNo) {
                    $receiptPath = $this->paymentReceipt->store('payment_receipts', 'public');
                    $this->sellPhone->update([
                        'payment_receipt_path' => $receiptPath,
                        'store_bank_no' => $this->storeBankNo,
                    ]);

                    $paymentData = [
                        'bankNo' => $this->storeBankNo,
                        'vendorNo' => str_replace('"', '', $vendorNoBaru),
                        'paymentDate' => date('d/m/Y'),
                        'chequeAmount' => (int) $this->sellPhone->appraised_value,
                        'branchName' => Auth::user()->branch->name,
                        'detailInvoice' => [
                            [
                                'invoiceNo' => $this->sellPhone->invoice_number,
                                'paymentAmount' => (int) $this->sellPhone->appraised_value,
                            ]
                        ],

                    ];
                    $accurateService->postPurchasePayment($paymentData, $dbSource);
                }

                // JIKA BERHASIL: Update status dan redirect
                $this->sellPhone->update([
                    'status' => 'COMPLETED'
                ]);

                $this->dispatch('toast', [
                    'type' => 'success',
                    'title' => 'Success',
                    'message' => 'Invoice Accurate Berhasil Dibuat. Pengajuan Jual HP Selesai.'
                ]);
                return $this->redirect(route('admin.sell-phone.index'));
            } catch (\Exception $e) {
                // JIKA GAGAL: Tangkap error dari service dan tampilkan ke user via Toast
                // Status SellPhone TIDAK diupdate ke COMPLETED, sehingga user bisa mencoba klik submit lagi
                Log::error('API Accurate Failed: ' . $e->getMessage());
                $this->dispatch('toast', [
                    'type' => 'error',
                    'title' => 'Error',
                    'message' => 'Gagal membuat faktur di Accurate: ' . $e->getMessage()
                ]);
            }
        } else {
            return;
        }
    }

    public function reuploadReceipt()
    {
        $this->validate([
            'paymentReceipt' => 'required|image|max:5120',
        ], [
            'paymentReceipt.required' => 'Bukti bayar wajib diunggah.',
            'paymentReceipt.image' => 'Bukti bayar harus berupa gambar.',
        ]);

        $receiptPath = $this->paymentReceipt->store('payment_receipts', 'public');
        $this->sellPhone->update([
            'payment_receipt_path' => $receiptPath,
        ]);

        $this->isReuploading = false;
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Success',
            'message' => 'Bukti transfer berhasil diunggah ulang.'
        ]);
    }

    public function reject()
    {
        $this->validate([
            'rejectReason' => 'required|string|max:500'
        ], [
            'rejectReason.required' => 'Alasan pembatalan wajib diisi.',
            'rejectReason.max' => 'Alasan pembatalan terlalu panjang (maksimal 500 karakter).'
        ]);

        $this->sellPhone->update([
            'status' => 'CANCELLED',
            'reject_reason' => $this->rejectReason
        ]);

        $this->isRejecting = false;
        $this->dispatch('toast', ['title' => 'Ditolak', 'message' => 'Pembelian dibatalkan secara sepihak.', 'type' => 'info']);
    }

    // convertToProduct telah dihapus karena manajemen inventaris kini terpusat pada Accurate
    // dan ditarik melalui fitur Sinkronisasi Master Data ProductAccurate

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.sell-phone.show');
    }
}
