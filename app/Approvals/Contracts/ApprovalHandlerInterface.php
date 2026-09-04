<?php

namespace App\Approvals\Contracts;

use App\Models\ApprovalRequest;

interface ApprovalHandlerInterface
{
    /**
     * Menjalankan aksi setelah pengajuan disetujui sepenuhnya (Level Final).
     */
    public function handleApproved(ApprovalRequest $request, array $params = []): void;

    /**
     * Menjalankan aksi jika pengajuan ditolak.
     */
    public function handleRejected(ApprovalRequest $request, array $params = []): void;
}
