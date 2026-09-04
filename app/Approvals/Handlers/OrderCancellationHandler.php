<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\CustomerDeposit;
use App\Models\CustomerDepositUsage;
use App\Services\AccurateService;
use Exception;

class OrderCancellationHandler implements ApprovalHandlerInterface
{
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $order = $request->approvable;
        if (!$order) {
            throw new Exception("Order not found for approval #{$request->id}.");
        }

        // Execute Accurate Deletion using rollback method
        $accurateService = app(AccurateService::class);
        $accurateService->rollbackOrderDocuments($order);

        // Update local order status
        $order->update(['order_status' => 'CANCELLED']);

        // Restore deposit balances if this order used any deposits
        $usages = CustomerDepositUsage::where('order_id', $order->id)->get();
        foreach ($usages as $usage) {
            $deposit = $usage->customerDeposit;
            if ($deposit) {
                $deposit->balance += (float) $usage->amount_used;
                $deposit->status = 'AVAILABLE';
                $deposit->save();
            }
            $usage->delete();
        }

        // Kembalikan deposit SO (yang berasal dari DP SO ini) ke AVAILABLE
        CustomerDeposit::where('origin_order_id', $order->id)
            ->where('status', 'USED')
            ->update(['status' => 'AVAILABLE']);
    }

    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        // No state mutation needed for order cancellation rejection
    }
}
