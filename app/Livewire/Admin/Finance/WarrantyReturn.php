<?php

namespace App\Livewire\Admin\Finance;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\WarrantyClaim;

class WarrantyReturn extends Component
{
    public $activeTab = 'waiting_refund'; // waiting_refund (Uang Keluar) atau waiting_payment (Uang Masuk)

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    #[Layout('layouts.z')]
    public function render()
    {
        // Ambil klaim garansi yang butuh tindak lanjut finance
        $claims = WarrantyClaim::with(['warranty.orderItem.variant.secondProduct', 'customer'])
            ->where('status', $this->activeTab)
            ->orderBy('updated_at', 'desc')
            ->get();

        $summary = [
            'waiting_refund' => WarrantyClaim::where('status', 'waiting_refund')->count(),
            'waiting_payment' => WarrantyClaim::where('status', 'waiting_payment')->count(),
            'resolved' => WarrantyClaim::where('status', 'resolved')->count(),
        ];

        return view('livewire.admin.finance.warranty-return', [
            'claims' => $claims,
            'summary' => $summary
        ]);
    }

    // Dummy method for resolving a claim (to be implemented with Accurate logic later)
    public function resolveClaim($claimId)
    {
        $claim = WarrantyClaim::find($claimId);
        if ($claim) {
            $claim->status = 'resolved';
            $claim->save();
            $this->dispatch('toast', title: 'Berhasil', message: 'Klaim garansi berhasil diproses.', type: 'success');
        }
    }
}
