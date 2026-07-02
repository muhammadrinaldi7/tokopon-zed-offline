<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $guarded = ['id'];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function histories()
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function executeAction(array $params = [])
    {
        if ($this->status !== 'APPROVED') {
            throw new \Exception("Cannot execute a request that is not approved.");
        }

        // Handle Order Cancellation
        if ($this->approvable_type === \App\Models\Order::class && $this->request_type === 'ORDER_CANCELLATION') {
            $order = $this->approvable;
            if (!$order) {
                throw new \Exception("Order not found.");
            }

            // Execute Accurate Deletion using the new rollback method
            $accurateService = app(\App\Services\AccurateService::class);
            $accurateService->rollbackOrderDocuments($order);

            // Update local order status
            $order->update(['order_status' => 'CANCELLED']);
            $this->update(['status' => 'COMPLETED']);
            
            return true;
        }

        // Handle Warranty Extension
        if ($this->approvable_type === \App\Models\Warranty::class && $this->request_type === 'WARRANTY_EXTENSION') {
            $warranty = $this->approvable;
            if (!$warranty) {
                throw new \Exception("Warranty not found.");
            }

            $days = $params['extension_days'] ?? 7;
            
            // If already expired, start extension from today. If still active, extend from current expiry.
            $baseDate = ($warranty->expires_at && $warranty->expires_at > now()) ? $warranty->expires_at : now();
            
            $warranty->update([
                'expires_at' => $baseDate->addDays((int)$days),
                'status' => 'active' // Ensure status is active
            ]);

            $this->update(['status' => 'COMPLETED']);
            
            return true;
        }

        throw new \Exception("Execution logic for {$this->request_type} on {$this->approvable_type} is not defined.");
    }
}
