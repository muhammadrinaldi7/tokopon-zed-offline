<?php

namespace App\Livewire\Admin\SellPhone;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SellPhone;
use App\Models\SellPhoneIssue;
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

    // Issue / Kendala Form
    public string $issueCategory = 'SALAH_NOREK';
    public string $issueComment = '';
    public bool $showIssueForm = false;

    // Bank Correction Form
    public bool $isEditingBank = false;
    public string $editBankName = '';
    public string $editBankAccountNumber = '';
    public string $editBankAccountName = '';

    public $showQcWarningModal = false;
    public $forceProcessQc = false;
    public $latestQcVerdict = '';

    // SKU Correction Modal
    public bool $showCorrectionModal = false;
    public ?int $targetProductAccurateId = null;
    public string $searchProductQuery = '';
    public string $correctionReason = '';
    public bool $syncAccurateOnCorrection = true;

    // Cancel / Reset Modal
    public bool $showCancelModal = false;
    public string $cancelActionType = 'RESET_TO_DRAFT'; // 'RESET_TO_DRAFT' | 'CANCELLED'
    public string $cancelReason = '';

    public function mount(SellPhone $sellPhone)
    {
        $this->sellPhone = $sellPhone->load(['user.bankAccounts', 'user.profile', 'buybackDevice.tier', 'businessUnit', 'issues.user', 'resetLogs.resetBy', 'productAccurate']);
        $this->appraisedValue = $this->sellPhone->appraised_value ?? 0;
        $this->qcPassed = $this->sellPhone->hasPassedQc();

        $userBank = $this->sellPhone->user?->bankAccounts?->first();
        $this->editBankName = $this->sellPhone->bank_name ?: ($userBank?->bank_name ?? '');
        $this->editBankAccountNumber = $this->sellPhone->bank_account_number ?: ($userBank?->account_number ?? '');
        $this->editBankAccountName = $this->sellPhone->bank_account_name ?: ($userBank?->account_name ?? '');

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
        $this->latestQcVerdict = $verdict;
    }

    #[Computed]
    public function phoneData()
    {
        // loadMissing akan me-load relasi hanya saat data ini dipanggil di Blade
        return $this->sellPhone->loadMissing(['buybackDevice.secondProductVariant', 'user']);
    }

    public function updatedAppraisedValue($value)
    {
        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9]/', '', $value);
            $this->appraisedValue = $cleaned !== '' ? (int) $cleaned : 0;
        }
    }

    public function updatedRevisedAppraisedValue($value)
    {
        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9]/', '', $value);
            $this->revisedAppraisedValue = $cleaned !== '' ? (int) $cleaned : 0;
        }
    }

    public function submitAppraisal()
    {
        $this->appraisedValue = (int) preg_replace('/[^0-9]/', '', (string)$this->appraisedValue);

        $this->validate([
            'appraisedValue' => 'required|numeric|min:1000'
        ]);

        $this->sellPhone->update([
            'appraised_value' => $this->appraisedValue,
            'status' => 'OFFERED',
        ]);

        $this->dispatch('show-toast', type: 'success', message: 'Penawaran berhasil disimpan dan dikirim ke pengguna.');
    }

    public function updatePrice()
    {
        $this->appraisedValue = (int) preg_replace('/[^0-9]/', '', (string)$this->appraisedValue);

        $this->validate([
            'appraisedValue' => 'required|numeric|min:1000'
        ]);

        $this->sellPhone->update([
            'appraised_value' => $this->appraisedValue,
            'is_price_adjusted' => true
        ]);

        // Refresh the local model just to be safe
        $this->sellPhone->refresh();
        $this->dispatch('toast', ['type' => 'success', 'title' => 'Sukses', 'message' => 'Harga disepakati berhasil diperbarui.']);
    }

    public function submitRevision()
    {
        $this->revisedAppraisedValue = (int) preg_replace('/[^0-9]/', '', (string)$this->revisedAppraisedValue);

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
        if (!$this->qcPassed && !$this->forceProcessQc) {
            $latestInspection = $this->sellPhone->inspections()->latest()->first();
            if ($latestInspection && in_array($latestInspection->verdict, ['conditional', 'fail'])) {
                $this->latestQcVerdict = $latestInspection->verdict;
                $this->showQcWarningModal = true;
                return;
            } else {
                $this->dispatch('toast', ['type' => 'error', 'title' => 'Gagal', 'message' => 'Lakukan Inspeksi QC terlebih dahulu dan pastikan statusnya LAYAK BELI (PASS).']);
                return;
            }
        }

        $this->showQcWarningModal = false; // Hide if open

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

            $itemNo = $phoneData->productAccurate->item_no ?? null;
            if (!$itemNo || $itemNo === 'TES-001') {
                $this->dispatch('toast', ['type' => 'error', 'title' => 'Gagal', 'message' => 'Barang (Master HP Bekas) belum disinkronkan dari Accurate. Item No tidak valid.']);
                return;
            }

            // Tentukan nomor proyek berdasarkan Business Unit & jenis proyek produk
            $namaProyek = trim(strtoupper($phoneData->productAccurate->proyek ?? ''));
            
            // Mengambil secara dinamis dari tabel business_unit_projects
            // Jika tidak ditemukan, fallback ke nama aslinya (jika kosong jadi null agar tidak dikirim ke accurate)
            $projectNo = \App\Models\BusinessUnitProject::getProjectNoByBusinessUnit(
                $this->sellPhone->business_unit_id,
                $namaProyek,
                $namaProyek ?: null
            );

            // 1. Susun Array untuk detailItem terlebih dahulu agar lebih rapi
            $itemDetail = [
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
            ];

            // Tambahkan projectNo jika ada
            if ($projectNo) {
                $itemDetail['projectNo'] = $projectNo;
            }

            $detailItem = [$itemDetail];

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
                        'branchName' => $accurateBranchName,
                        'charField1' => $namaProyek ?: 'UMUM',
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

                // Otomatis void garansi aktif jika unit ini pernah dibeli sebelumnya di toko
                // karena unit telah dibeli kembali (buyback) oleh toko.
                if (!empty($this->sellPhone->imei)) {
                    \App\Models\Warranty::where('serial_number', $this->sellPhone->imei)
                        ->where('status', 'active')
                        ->update([
                            'status' => 'voided',
                        ]);
                }

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

    public function addIssue(): void
    {
        if (!Auth::user()->can('manage-sell-phone-issues') && !Auth::user()->can('manage-trade-in')) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin mencatat kendala.', type: 'error');
            return;
        }

        $this->validate([
            'issueCategory' => 'required|string|max:50',
            'issueComment' => 'required|string|min:3',
        ], [
            'issueCategory.required' => 'Kategori kendala wajib dipilih.',
            'issueComment.required' => 'Catatan kendala wajib diisi.',
            'issueComment.min' => 'Catatan kendala minimal 3 karakter.',
        ]);

        SellPhoneIssue::create([
            'sell_phone_id' => $this->sellPhone->id,
            'user_id' => Auth::id(),
            'category' => $this->issueCategory,
            'comment' => trim($this->issueComment),
            'status' => 'OPEN',
        ]);

        $this->issueComment = '';
        $this->issueCategory = 'SALAH_NOREK';
        $this->showIssueForm = false;
        $this->sellPhone->refresh();
        $this->sellPhone->load(['issues.user', 'issues.resolvedBy']);

        $this->dispatch('toast', title: 'Berhasil', message: 'Catatan kendala berhasil ditambahkan.', type: 'success');
    }

    public function toggleIssueStatus(int $issueId, ?string $notes = null): void
    {
        if (!Auth::user()->can('manage-sell-phone-issues') && !Auth::user()->can('manage-trade-in')) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin mengubah status kendala.', type: 'error');
            return;
        }

        $issue = SellPhoneIssue::find($issueId);
        if ($issue && $issue->sell_phone_id === $this->sellPhone->id) {
            if ($issue->status === 'OPEN') {
                $issue->update([
                    'status' => 'RESOLVED',
                    'resolved_by' => Auth::id(),
                    'resolved_at' => now(),
                    'resolution_notes' => $notes ? trim($notes) : 'Ditandai selesai oleh ' . (Auth::user()->name ?? 'Admin'),
                ]);
                $message = 'Kendala berhasil diselesaikan.';
            } else {
                $issue->update([
                    'status' => 'OPEN',
                    'resolved_by' => null,
                    'resolved_at' => null,
                    'resolution_notes' => null,
                ]);
                $message = 'Kendala dibuka kembali.';
            }

            $this->sellPhone->refresh();
            $this->sellPhone->load(['issues.user', 'issues.resolvedBy']);

            $this->dispatch('toast', title: 'Berhasil', message: $message, type: 'info');
        }
    }

    public function deleteIssue(int $issueId): void
    {
        if (!Auth::user()->can('manage-sell-phone-issues') && !Auth::user()->can('manage-trade-in')) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin menghapus kendala.', type: 'error');
            return;
        }

        $issue = SellPhoneIssue::find($issueId);
        if ($issue && $issue->sell_phone_id === $this->sellPhone->id) {
            $issue->delete();
            $this->sellPhone->refresh();
            $this->sellPhone->load(['issues.user', 'issues.resolvedBy']);
            $this->dispatch('toast', title: 'Berhasil', message: 'Catatan kendala dihapus.', type: 'success');
        }
    }

    public function openEditBank(): void
    {
        if (!Auth::user()->can('correct-sell-phone-bank') && !Auth::user()->can('manage-trade-in')) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin koreksi rekening.', type: 'error');
            return;
        }

        $userBank = $this->sellPhone->user?->bankAccounts?->first();
        $this->editBankName = $this->sellPhone->bank_name ?: ($userBank?->bank_name ?? '');
        $this->editBankAccountNumber = $this->sellPhone->bank_account_number ?: ($userBank?->account_number ?? '');
        $this->editBankAccountName = $this->sellPhone->bank_account_name ?: ($userBank?->account_name ?? '');
        $this->isEditingBank = true;
    }

    public function saveBankInfo(): void
    {
        if (!Auth::user()->can('correct-sell-phone-bank') && !Auth::user()->can('manage-trade-in')) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin koreksi rekening.', type: 'error');
            return;
        }

        $this->validate([
            'editBankName' => 'required|string|max:100',
            'editBankAccountNumber' => 'required|string|max:50',
            'editBankAccountName' => 'required|string|max:100',
        ], [
            'editBankName.required' => 'Nama Bank wajib diisi.',
            'editBankAccountNumber.required' => 'Nomor Rekening wajib diisi.',
            'editBankAccountName.required' => 'Atas Nama Rekening wajib diisi.',
        ]);

        $bankName = trim($this->editBankName);
        $bankAccNo = trim($this->editBankAccountNumber);
        $bankAccName = trim($this->editBankAccountName);

        $this->sellPhone->update([
            'bank_name' => $bankName,
            'bank_account_number' => $bankAccNo,
            'bank_account_name' => $bankAccName,
        ]);

        if ($this->sellPhone->user) {
            $primaryBank = $this->sellPhone->user->bankAccounts()->where('is_primary', true)->first()
                ?: $this->sellPhone->user->bankAccounts()->first();

            if ($primaryBank) {
                $primaryBank->update([
                    'bank_name' => $bankName,
                    'account_number' => $bankAccNo,
                    'account_name' => $bankAccName,
                ]);
            }
        }

        // Otomatis Selesaikan (RESOLVE) kendala SALAH_NOREK atau GAGAL_TRANSFER yang masih OPEN
        $openBankIssues = $this->sellPhone->issues()
            ->where('status', 'OPEN')
            ->whereIn('category', ['SALAH_NOREK', 'GAGAL_TRANSFER'])
            ->get();

        foreach ($openBankIssues as $openIssue) {
            $openIssue->update([
                'status' => 'RESOLVED',
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
                'resolution_notes' => "Rekening berhasil dikoreksi menjadi {$bankName} - {$bankAccNo} a.n {$bankAccName} oleh " . (Auth::user()->name ?? 'Admin'),
            ]);
        }

        $this->isEditingBank = false;
        $this->sellPhone->refresh();
        $this->sellPhone->load(['issues.user', 'issues.resolvedBy']);
        
        $resolvedCount = $openBankIssues->count();
        $msg = $resolvedCount > 0 
            ? "Data rekening berhasil diperbarui & {$resolvedCount} kendala rekening ditandai Selesai."
            : "Data rekening bank tujuan berhasil diperbarui.";

        $this->dispatch('toast', title: 'Berhasil', message: $msg, type: 'success');
    }

    #[Computed]
    public function productSearchResults()
    {
        if (strlen($this->searchProductQuery) < 2) {
            return [];
        }

        $buId = $this->sellPhone->business_unit_id ?? 2;

        return \App\Models\ProductAccurate::where('business_unit_id', $buId)
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchProductQuery . '%')
                  ->orWhere('item_no', 'like', '%' . $this->searchProductQuery . '%');
            })
            ->take(15)
            ->get();
    }

    #[Computed]
    public function targetProductAccurate()
    {
        if (!$this->targetProductAccurateId) return null;
        return \App\Models\ProductAccurate::find($this->targetProductAccurateId);
    }

    public function selectTargetProduct($id)
    {
        $this->targetProductAccurateId = (int) $id;
        $pa = \App\Models\ProductAccurate::find($id);
        if ($pa) {
            $this->searchProductQuery = $pa->name . ' (' . $pa->item_no . ')';
        }
    }

    public function openCorrectionModal()
    {
        $this->showCorrectionModal = true;
        $this->targetProductAccurateId = null;
        $this->searchProductQuery = '';
        $this->correctionReason = '';
        $this->syncAccurateOnCorrection = true;
    }

    public function closeCorrectionModal()
    {
        $this->showCorrectionModal = false;
        $this->targetProductAccurateId = null;
        $this->searchProductQuery = '';
        $this->correctionReason = '';
    }

    public function executeSkuCorrection()
    {
        if (!Auth::user()->can('manage-sell-phone') && !Auth::user()->can('manage-trade-in') && !Auth::user()->hasRole(['Admin', 'Super Admin'])) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin mengoreksi SKU pembelian.', type: 'error');
            return;
        }

        $this->validate([
            'targetProductAccurateId' => 'required|exists:product_accurates,id',
            'correctionReason' => 'required|string|min:5',
        ], [
            'targetProductAccurateId.required' => 'Silakan cari dan pilih master produk target (SKU Baru).',
            'targetProductAccurateId.exists' => 'Produk target tidak valid.',
            'correctionReason.required' => 'Alasan koreksi wajib diisi.',
            'correctionReason.min' => 'Alasan koreksi minimal 5 karakter.',
        ]);

        $newPa = \App\Models\ProductAccurate::find($this->targetProductAccurateId);
        if (!$newPa) {
            $this->dispatch('toast', title: 'Error', message: 'Master produk tidak ditemukan.', type: 'error');
            return;
        }

        // Cek status SN di ProductSerialNumber
        if (!empty($this->sellPhone->imei)) {
            $snRecord = \App\Models\ProductSerialNumber::where('serial_number', $this->sellPhone->imei)
                ->where('business_unit_id', $this->sellPhone->business_unit_id)
                ->first();

            if ($snRecord && $snRecord->status !== 'Available') {
                $this->dispatch('toast', title: 'Tidak Dapat Dikoreksi', message: "Unit dengan SN/IMEI {$this->sellPhone->imei} sudah berstatus '{$snRecord->status}' (kemungkinan sudah terjual di POS).", type: 'error');
                return;
            }
        }

        try {
            DB::beginTransaction();

            $previousModel = $this->sellPhone->phone_model;
            $previousItemNo = $this->sellPhone->buybackDevice?->productAccurate?->item_no ?? $this->sellPhone->productAccurate?->item_no;
            $previousPaId = $this->sellPhone->product_accurate_id;
            $previousInvoiceNo = $this->sellPhone->invoice_number;
            $previousAppraisedValue = $this->sellPhone->appraised_value;
            $dbSource = $this->sellPhone->businessUnit ? strtolower($this->sellPhone->businessUnit->code) : 'gsk';
            $accurateService = app(AccurateService::class);

            $accurateDocsSnapshot = [];
            $paymentsSnapshot = [
                'bank_no' => $this->sellPhone->store_bank_no,
                'receipt_path' => $this->sellPhone->payment_receipt_path,
                'amount' => $this->sellPhone->appraised_value,
            ];

            // 1. Rollback & Re-create di Accurate jika ada nomor faktur dan sync aktif
            $newInvoiceNumber = $previousInvoiceNo;
            if ($this->syncAccurateOnCorrection && $previousInvoiceNo) {
                // Rollback dokumen lama di Accurate (Hapus Pembayaran & Faktur Lama)
                $rollbackResult = $accurateService->rollbackPurchaseDocuments($this->sellPhone);
                $accurateDocsSnapshot = $rollbackResult['deleted_docs'] ?? [];

                // Susun ulang Purchase Invoice untuk SKU baru
                $flUser = $this->sellPhone->handledBy;
                $accurateBranchName = $flUser && $flUser->branch ? $flUser->branch->name : 'Banjarbaru';
                $accurateWarehouseName = $flUser && $flUser->warehouse ? $flUser->warehouse->name : 'Head Office';

                $namaProyek = trim(strtoupper($newPa->proyek ?? ''));
                $projectNo = \App\Models\BusinessUnitProject::getProjectNoByBusinessUnit(
                    $this->sellPhone->business_unit_id,
                    $namaProyek,
                    $namaProyek ?: null
                );

                $itemDetail = [
                    'itemNo' => $newPa->item_no,
                    'warehouseName' => $accurateWarehouseName,
                    'unitPrice' => (int) $this->sellPhone->appraised_value,
                    'quantity' => 1,
                    'useTax1' => false,
                    'detailSerialNumber' => [
                        [
                            'serialNumberNo' => $this->sellPhone->imei ?? 'NO-IMEI-' . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT),
                            'quantity' => 1
                        ]
                    ]
                ];

                if ($projectNo) {
                    $itemDetail['projectNo'] = $projectNo;
                }

                $customerUser = $this->sellPhone->user;
                $vendorNoBaru = $customerUser ? ($customerUser->getAccurateVendorNo($dbSource) ?? 'V-CASH') : 'V-CASH';
                $billNumber = 'TPD-' . date('dmY') . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT);

                $piPayload = [
                    'billNumber' => $billNumber,
                    'vendorNo' => str_replace('"', '', $vendorNoBaru),
                    'branchName' => $accurateBranchName,
                    'inclusiveTax' => false,
                    'transDate' => date('d/m/Y'),
                    'currencyCode' => 'IDR',
                    'description' => 'Pembelian HP (Koreksi SKU) - NIK:' . ($customerUser?->identity ?? '-'),
                    'detailItem' => [$itemDetail],
                ];

                $accurateResponse = $accurateService->postPurchaseInvoice($piPayload, $dbSource);
                if (isset($accurateResponse['r']['number'])) {
                    $newInvoiceNumber = $accurateResponse['r']['number'];
                }

                // Buat ulang Pembayaran Pembelian
                if ($this->sellPhone->store_bank_no) {
                    $paymentData = [
                        'bankNo' => $this->sellPhone->store_bank_no,
                        'vendorNo' => str_replace('"', '', $vendorNoBaru),
                        'paymentDate' => date('d/m/Y'),
                        'chequeAmount' => (int) $this->sellPhone->appraised_value,
                        'branchName' => $accurateBranchName,
                        'charField1' => $namaProyek ?: 'UMUM',
                        'detailInvoice' => [
                            [
                                'invoiceNo' => $newInvoiceNumber,
                                'paymentAmount' => (int) $this->sellPhone->appraised_value,
                            ]
                        ],
                    ];
                    $accurateService->postPurchasePayment($paymentData, $dbSource);
                }
            }

            // 2. Update ProductSerialNumber
            if (!empty($this->sellPhone->imei)) {
                \App\Models\ProductSerialNumber::where('serial_number', $this->sellPhone->imei)
                    ->where('business_unit_id', $this->sellPhone->business_unit_id)
                    ->update([
                        'item_no' => $newPa->item_no,
                        'product_accurate_id' => $newPa->id,
                    ]);
            }

            // 3. Update WarehouseStock
            $warehouseId = $this->sellPhone->handledBy?->warehouse_id;
            if ($warehouseId) {
                // Kurangi stok lama
                if ($previousPaId) {
                    $oldStock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
                        ->where('variant_id', $previousPaId)
                        ->where('variant_type', \App\Models\ProductAccurate::class)
                        ->first();
                    if ($oldStock && $oldStock->stock > 0) {
                        $oldStock->decrement('stock', 1);
                    }
                }

                // Tambah stok baru
                $newStock = \App\Models\WarehouseStock::firstOrCreate([
                    'warehouse_id' => $warehouseId,
                    'variant_id' => $newPa->id,
                    'variant_type' => \App\Models\ProductAccurate::class,
                ], ['stock' => 0]);
                $newStock->increment('stock', 1);
            }

            // 4. Update SellPhone
            $matchingBuybackDevice = \App\Models\BuybackDevice::where('product_accurate_id', $newPa->id)->first();
            $this->sellPhone->update([
                'phone_model' => $newPa->name,
                'product_accurate_id' => $newPa->id,
                'buyback_device_id' => $matchingBuybackDevice?->id ?? $this->sellPhone->buyback_device_id,
                'invoice_number' => $newInvoiceNumber,
            ]);

            // 5. Catat Snapshot ke SellPhoneResetLog
            \App\Models\SellPhoneResetLog::create([
                'sell_phone_id' => $this->sellPhone->id,
                'action_type' => 'CORRECTION_SKU',
                'reset_by' => Auth::id(),
                'reason' => $this->correctionReason,
                'previous_status' => $this->sellPhone->status,
                'new_status' => $this->sellPhone->status,
                'previous_phone_model' => $previousModel,
                'new_phone_model' => $newPa->name,
                'previous_item_no' => $previousItemNo,
                'new_item_no' => $newPa->item_no,
                'previous_product_accurate_id' => $previousPaId,
                'new_product_accurate_id' => $newPa->id,
                'previous_appraised_value' => $previousAppraisedValue,
                'new_appraised_value' => $this->sellPhone->appraised_value,
                'previous_invoice_number' => $previousInvoiceNo,
                'new_invoice_number' => $newInvoiceNumber,
                'previous_accurate_docs_snapshot' => $accurateDocsSnapshot,
                'previous_payments_snapshot' => $paymentsSnapshot,
            ]);

            DB::commit();

            $this->closeCorrectionModal();
            $this->sellPhone->refresh();
            $this->sellPhone->load(['buybackDevice.tier', 'productAccurate', 'resetLogs.resetBy']);

            $this->dispatch('toast', title: 'Berhasil', message: "SKU berhasil dikoreksi menjadi {$newPa->name} ({$newPa->item_no}). Stok dan Accurate telah disinkronkan.", type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Koreksi SKU Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan saat mengoreksi SKU: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openCancelModal($action = 'RESET_TO_DRAFT')
    {
        $this->cancelActionType = $action;
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->cancelReason = '';
    }

    public function executeCancelOrReset()
    {
        if (!Auth::user()->can('manage-sell-phone') && !Auth::user()->can('manage-trade-in') && !Auth::user()->hasRole(['Admin', 'Super Admin'])) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki izin membatalkan atau mereset transaksi.', type: 'error');
            return;
        }

        $this->validate([
            'cancelReason' => 'required|string|min:5',
        ], [
            'cancelReason.required' => 'Alasan wajib diisi.',
            'cancelReason.min' => 'Alasan minimal 5 karakter.',
        ]);

        // Cek status SN
        if (!empty($this->sellPhone->imei)) {
            $snRecord = \App\Models\ProductSerialNumber::where('serial_number', $this->sellPhone->imei)
                ->where('business_unit_id', $this->sellPhone->business_unit_id)
                ->first();

            if ($snRecord && $snRecord->status !== 'Available') {
                $this->dispatch('toast', title: 'Tidak Dapat Dibatalkan', message: "Unit dengan SN/IMEI {$this->sellPhone->imei} sudah berstatus '{$snRecord->status}' (kemungkinan sudah terjual di POS).", type: 'error');
                return;
            }
        }

        try {
            DB::beginTransaction();

            $accurateService = app(AccurateService::class);
            $previousInvoiceNo = $this->sellPhone->invoice_number;
            $previousStatus = $this->sellPhone->status;

            // 1. Rollback Dokumen di Accurate
            $rollbackResult = $accurateService->rollbackPurchaseDocuments($this->sellPhone);
            $accurateDocsSnapshot = $rollbackResult['deleted_docs'] ?? [];

            // 2. Hapus / Update ProductSerialNumber
            if (!empty($this->sellPhone->imei)) {
                \App\Models\ProductSerialNumber::where('serial_number', $this->sellPhone->imei)
                    ->where('business_unit_id', $this->sellPhone->business_unit_id)
                    ->delete();
            }

            // 3. Kurangi WarehouseStock
            $warehouseId = $this->sellPhone->handledBy?->warehouse_id;
            $paId = $this->sellPhone->product_accurate_id;
            if ($warehouseId && $paId) {
                $stock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('variant_id', $paId)
                    ->where('variant_type', \App\Models\ProductAccurate::class)
                    ->first();
                if ($stock && $stock->stock > 0) {
                    $stock->decrement('stock', 1);
                }
            }

            // 4. Update Status SellPhone
            $newStatus = $this->cancelActionType === 'RESET_TO_DRAFT' ? 'PAYING' : 'CANCELLED';
            $this->sellPhone->update([
                'status' => $newStatus,
                'invoice_number' => null,
                'reject_reason' => $this->cancelActionType === 'CANCELLED' ? $this->cancelReason : $this->sellPhone->reject_reason,
            ]);

            // 5. Catat Snapshot ke SellPhoneResetLog
            \App\Models\SellPhoneResetLog::create([
                'sell_phone_id' => $this->sellPhone->id,
                'action_type' => $this->cancelActionType,
                'reset_by' => Auth::id(),
                'reason' => $this->cancelReason,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'previous_phone_model' => $this->sellPhone->phone_model,
                'new_phone_model' => $this->sellPhone->phone_model,
                'previous_item_no' => $this->sellPhone->productAccurate?->item_no,
                'new_item_no' => $this->sellPhone->productAccurate?->item_no,
                'previous_product_accurate_id' => $this->sellPhone->product_accurate_id,
                'new_product_accurate_id' => $this->sellPhone->product_accurate_id,
                'previous_appraised_value' => $this->sellPhone->appraised_value,
                'new_appraised_value' => $this->sellPhone->appraised_value,
                'previous_invoice_number' => $previousInvoiceNo,
                'new_invoice_number' => null,
                'previous_accurate_docs_snapshot' => $accurateDocsSnapshot,
                'previous_payments_snapshot' => [
                    'bank_no' => $this->sellPhone->store_bank_no,
                    'receipt_path' => $this->sellPhone->payment_receipt_path,
                    'amount' => $this->sellPhone->appraised_value,
                ],
            ]);

            DB::commit();

            $this->closeCancelModal();
            $this->sellPhone->refresh();
            $this->sellPhone->load(['buybackDevice.tier', 'productAccurate', 'resetLogs.resetBy']);

            $msg = $this->cancelActionType === 'RESET_TO_DRAFT'
                ? "Transaksi berhasil direset ke status Belum Lunas (PAYING). Dokumen Accurate & Stok telah dibatalkan."
                : "Transaksi Beli HP berhasil dibatalkan total (CANCELLED).";

            $this->dispatch('toast', title: 'Berhasil', message: $msg, type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cancel/Reset Beli HP Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.z')]
    public function render()
    {
        return view('livewire.admin.sell-phone.show');
    }
}
