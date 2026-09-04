<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;

class CustomCashbackHandler implements ApprovalHandlerInterface
{
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        // Custom Cashback dieksekusi oleh kasir POS saat mendeteksi status APPROVED pada polling cart.
    }

    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        // Penolakan otomatis terbaca oleh kasir saat polling cart.
    }
}
