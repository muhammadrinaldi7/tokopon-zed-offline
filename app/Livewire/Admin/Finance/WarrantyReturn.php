<?php

namespace App\Livewire\Admin\Finance;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\WarrantyClaim;

class WarrantyReturn extends Component
{
    public $activeTab = 'waiting_refund'; // waiting_refund (Uang Keluar) atau waiting_payment (Uang Masuk)
    public $showResolveModal = false;
    public $showDetailsModal = false;
    public $selectedClaimId = null;
    public $selectedBankNo = '';
    public $selectedDetails = null;

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    #[Layout('layouts.z')]
    public function render()
    {
        // Ambil klaim garansi yang butuh tindak lanjut finance
        $claims = WarrantyClaim::with(['warranty.orderItem.variant', 'customer'])
            ->where('status', $this->activeTab)
            ->orderBy('updated_at', 'desc')
            ->get();

        $summary = [
            'waiting_refund' => WarrantyClaim::where('status', 'waiting_refund')->count(),
            'waiting_payment' => WarrantyClaim::where('status', 'waiting_payment')->count(),
            'resolved' => WarrantyClaim::where('status', 'completed')->count(),
        ];

        $banks = \App\Models\AccurateGlAccount::where('account_type', 'CASH_BANK')->get();

        return view('livewire.admin.finance.warranty-return', [
            'claims' => $claims,
            'summary' => $summary,
            'banks' => $banks
        ]);
    }

    public function openResolveModal($claimId)
    {
        $this->selectedClaimId = $claimId;
        $this->selectedBankNo = '';
        $this->showResolveModal = true;
    }

    public function closeResolveModal()
    {
        $this->showResolveModal = false;
        $this->selectedClaimId = null;
        $this->selectedBankNo = '';
    }

    public function confirmResolve()
    {
        $this->validate([
            'selectedBankNo' => 'required'
        ]);

        $claim = WarrantyClaim::find($this->selectedClaimId);
        if ($claim) {
            if ($claim->status === 'waiting_refund') {
                try {
                    $accurateService = app(\App\Services\AccurateService::class);
                    $accurateService->processDowngradeRefund($claim, $this->selectedBankNo, $claim->refund_amount);
                } catch (\Exception $e) {
                    $this->addError('selectedBankNo', 'Gagal memproses refund ke Accurate: ' . $e->getMessage());
                    return;
                }
            }

            $claim->status = 'completed';
            $claim->resolved_at = \Carbon\Carbon::now();
            $claim->save();
            $this->dispatch('toast', title: 'Berhasil', message: 'Klaim garansi berhasil diproses.', type: 'success');
        }

        $this->closeResolveModal();
    }

    public function viewDetails($id)
    {
        $this->selectedDetails = WarrantyClaim::with(['warranty.orderItem.variant', 'customer'])->find($id);
        $this->showDetailsModal = true;
    }

    public function closeDetails()
    {
        $this->showDetailsModal = false;
        $this->selectedDetails = null;
    }
}
