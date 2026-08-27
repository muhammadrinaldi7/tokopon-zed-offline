<?php

namespace App\Livewire\Admin\Qc;

use Livewire\Component;
use App\Models\WarrantyClaim;

class ReturnIndex extends Component
{
    public function render()
    {
        // Cari klaim garansi yang sudah selesai, statusnya 'replaced', 
        // dan belum ada DeviceInspection untuknya.
        // Asumsi: barang lama (yang rusak) butuh di-QC.
        
        $claims = WarrantyClaim::with(['warranty.orderItem.variant', 'customer'])
            ->where('status', 'completed')
            ->where('resolution', 'replaced')
            ->whereDoesntHave('inspection')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('livewire.admin.qc.return-index', [
            'claims' => $claims
        ]);
    }
}
