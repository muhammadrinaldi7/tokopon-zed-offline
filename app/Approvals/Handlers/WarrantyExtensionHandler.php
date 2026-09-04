<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\Warranty;
use Exception;

class WarrantyExtensionHandler implements ApprovalHandlerInterface
{
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $warranty = $request->approvable;
        if (!$warranty) {
            throw new Exception("Warranty record not found for approval #{$request->id}.");
        }

        $days = $params['extension_days'] ?? 7;

        // If already expired, start extension from today. If still active, extend from current expiry.
        $baseDate = ($warranty->expires_at && $warranty->expires_at > now()) ? $warranty->expires_at : now();

        $warranty->update([
            'expires_at' => $baseDate->addDays((int) $days),
            'status'     => 'active',
        ]);
    }

    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        // No state mutation needed for warranty extension rejection
    }
}
