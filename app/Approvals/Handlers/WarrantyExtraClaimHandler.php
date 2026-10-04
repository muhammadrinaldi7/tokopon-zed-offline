<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\Warranty;
use Exception;

class WarrantyExtraClaimHandler implements ApprovalHandlerInterface
{
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $warranty = $request->approvable;
        if (!$warranty) {
            throw new Exception("Warranty record not found for approval #{$request->id}.");
        }

        $extraCount = (int) ($params['extra_claims_count'] ?? 1);

        // Tambah kuota klaim ekstra dan pastikan status garansi aktif
        $warranty->increment('extra_claims', $extraCount);
        
        // Jika garansi dalam kondisi expired, berikan toleransi aktif 7 hari agar klaim dapat diproses
        if ($warranty->expires_at && $warranty->expires_at < now()) {
            $warranty->expires_at = now()->addDays(7);
        }

        $warranty->status = 'active';
        $warranty->save();
    }

    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        // Tidak ada mutasi state yang diperlukan jika approval ditolak
    }
}
