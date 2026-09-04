<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\SellPhone;
use Exception;

class SellPhoneApprovalHandler implements ApprovalHandlerInterface
{
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $sellPhone = $request->approvable;
        if (!$sellPhone) {
            throw new Exception("SellPhone record not found for approval #{$request->id}.");
        }

        $updateData = ['status' => 'PAYING'];

        $originalPrice = (float) ($sellPhone->appraised_value ?? 0);
        $newPrice = isset($params['adjusted_price']) ? (float) $params['adjusted_price'] : 0;
        $isPriceChanged = $newPrice > 0 && abs($newPrice - $originalPrice) > 0.01;

        if ($isPriceChanged) {
            $updateData['appraised_value'] = $newPrice;
            $updateData['is_price_adjusted'] = true;
            $updateData['price_adjusted_by'] = $params['price_adjusted_by'] ?? null;
            $updateData['price_adjustment_reason'] = $params['price_adjustment_reason'] ?? null;
        }

        $sellPhone->update($updateData);
    }

    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        $sellPhone = $request->approvable;
        if ($sellPhone) {
            $sellPhone->update(['status' => 'CANCELLED']);
        }
    }
}
