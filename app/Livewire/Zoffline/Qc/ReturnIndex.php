<?php

namespace App\Livewire\Zoffline\Qc;

use Livewire\Component;
use App\Models\WarrantyClaim;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class ReturnIndex extends Component
{
    public function render()
    {
        // Cari klaim garansi yang sudah selesai, statusnya 'replaced', 
        // dan belum ada DeviceInspection untuknya.
        // Asumsi: barang lama (yang rusak) butuh di-QC.

        $claims = WarrantyClaim::with(['warranty.orderItem.variant', 'customer'])
            ->whereIn('status', ['completed', 'waiting_refund', 'waiting_payment'])
            ->where('resolution', 'replaced')
            ->whereDoesntHave('inspection')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('livewire.zoffline.qc.return-index', [
            'claims' => $claims
        ]);
    }
}
