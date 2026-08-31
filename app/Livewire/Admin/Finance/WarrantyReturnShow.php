<?php

namespace App\Livewire\Admin\Finance;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\WarrantyClaim;
use App\Models\AccurateGlAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\AccurateService;

class WarrantyReturnShow extends Component
{
    use WithFileUploads;

    public WarrantyClaim $claim;
    public $banks;
    
    public $selectedBankNo = '';
    public $paymentReceipt; // Untuk file upload
    
    public $isDowngrade = false;
    public $refundAmount = 0;
    
    public function mount(WarrantyClaim $claim)
    {
        $this->claim = $claim->load(['warranty.orderItem.variant', 'customer', 'warranty.policy.businessUnit']);
        
        $this->isDowngrade = $this->claim->status === 'waiting_refund';
        $this->refundAmount = $this->claim->refund_amount ?? 0;
        
        // Load bank based on Business Unit
        $businessUnitCode = $this->claim->warranty->policy->businessUnit->code ?? 'syihab';
        
        $this->banks = AccurateGlAccount::where('account_type', 'CASH_BANK')
            ->where('database_source', $businessUnitCode)
            ->get();
            
        // Jika tidak ada bank spesifik BU, tampilkan semua (fallback)
        if ($this->banks->isEmpty()) {
            $this->banks = AccurateGlAccount::where('account_type', 'CASH_BANK')->get();
        }
    }

    #[Layout('layouts.z')]
    public function render()
    {
        return view('livewire.admin.finance.warranty-return-show');
    }
    
    public function processTransaction(AccurateService $accurateService)
    {
        $rules = [
            'selectedBankNo' => 'required',
        ];
        
        // Jika Refund (Uang Keluar), wajib upload bukti transfer sesuai diskusi ("refund saja")
        if ($this->isDowngrade) {
            $rules['paymentReceipt'] = 'required|image|max:5120'; // max 5MB
        }
        
        $this->validate($rules, [
            'selectedBankNo.required' => 'Pilih akun bank asal toko terlebih dahulu.',
            'paymentReceipt.required' => 'Bukti transfer wajib diunggah untuk proses refund.',
            'paymentReceipt.image' => 'File harus berupa gambar.',
            'paymentReceipt.max' => 'Ukuran gambar maksimal 5MB.'
        ]);
        
        try {
            // 1. Hit Accurate API
            if ($this->isDowngrade) {
                // Refund ke customer (Uang Keluar)
                $accurateService->processDowngradeRefund($this->claim, $this->selectedBankNo, $this->refundAmount);
            }
            // Catatan: Jika Upgrade (waiting_payment), saat ini AccurateService processWarrantyReplacement
            // dipanggil di langkah sebelumnya (saat CS approve replacement_different upgrade).
            // Jadi di Finance hanya mencatat bank saja (tidak ada proses pelunasan terpisah di AccurateService untuk Upgrade yang berdiri sendiri di form ini, kecuali kalau memang belum dilunasi).
            // Berdasarkan alur eksisting, `confirmResolve` hanya mengeksekusi `processDowngradeRefund` untuk waiting_refund.
            // Untuk waiting_payment, hanya set status = completed.
            
            // 2. Simpan path gambar jika ada
            $receiptPath = null;
            if ($this->paymentReceipt) {
                $receiptPath = $this->paymentReceipt->store('finance/warranty_refunds', 'public');
            }
            
            // 3. Update Status Klaim
            $this->claim->update([
                'status' => 'completed',
                'resolved_at' => Carbon::now(),
                'store_bank_no' => $this->selectedBankNo,
                'payment_receipt_path' => $receiptPath
            ]);
            
            $this->dispatch('toast', title: 'Berhasil', message: 'Transaksi berhasil diproses.', type: 'success');
            
            return redirect()->route('finance.warranty-return');
            
        } catch (\Exception $e) {
            Log::error("Gagal memproses transaksi finance garansi: " . $e->getMessage());
            $this->addError('general', 'Gagal memproses ke Accurate: ' . $e->getMessage());
        }
    }
}
