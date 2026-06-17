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

class Show extends Component
{
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

    public function mount(SellPhone $sellPhone)
    {
        $this->sellPhone = $sellPhone->load(['user.bankAccounts', 'buybackDevice.tier']);
        $this->appraisedValue = $this->sellPhone->appraised_value ?? 0;
        $this->qcPassed = $this->sellPhone->hasPassedQc();
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
            $phoneData = $this->phoneData;
            $flUser = $this->sellPhone->handledBy;
            $accurateBranchName = $flUser && $flUser->branch ? $flUser->branch->name : 'Banjarbaru';
            $accurateWarehouseName = $flUser && $flUser->warehouse ? $flUser->warehouse->name : 'Head Office';

            // 1. Susun Array untuk detailItem terlebih dahulu agar lebih rapi
            $detailItem = [
                [
                    // Gunakan itemNo dari ProductAccurate yang terkait
                    'itemNo' => $phoneData->buybackDevice->productAccurate->item_no ?? 'TES-001',
                    'warehouseName' => $accurateWarehouseName,
                    'unitPrice' => (int) $this->sellPhone->appraised_value, // Harga yang disepakati
                    'quantity' => 1,

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

    public function reject()
    {
        $this->sellPhone->update(['status' => 'CANCELLED']);
        $this->dispatch('toast', title: 'Ditolak', message: 'Pembelian dibatalkan secara sepihak.', type: 'info');
    }

    // convertToProduct telah dihapus karena manajemen inventaris kini terpusat pada Accurate
    // dan ditarik melalui fitur Sinkronisasi Master Data ProductAccurate

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.sell-phone.show');
    }
}
