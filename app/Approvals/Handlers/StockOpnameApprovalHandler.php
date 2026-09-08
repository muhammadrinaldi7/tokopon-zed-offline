<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\StockOpname;
use Exception;

class StockOpnameApprovalHandler implements ApprovalHandlerInterface
{
    /**
     * Penanganan saat laporan hasil Stock Opname disetujui oleh Manajemen.
     * Sesuai ketentuan: Murni pengesahan laporan audit, TANPA penyesuaian stok live dan TANPA sinkronisasi Accurate.
     */
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $opname = $request->approvable;
        if (!$opname instanceof StockOpname) {
            throw new Exception("Data StockOpname tidak ditemukan untuk approval request #{$request->id}.");
        }

        $opname->update([
            'status'   => 'COMPLETED',
            'end_time' => $opname->end_time ?? now(),
        ]);
    }

    /**
     * Penanganan saat laporan hasil Stock Opname ditolak oleh Manajemen (misal perlu hitung ulang).
     */
    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        $opname = $request->approvable;
        if ($opname instanceof StockOpname) {
            $opname->update([
                'status' => 'REJECTED',
            ]);
        }
    }
}
