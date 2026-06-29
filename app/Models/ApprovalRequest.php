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

    public function executeCancellation()
    {
        if ($this->status !== 'APPROVED') {
            throw new \Exception("Cannot execute a request that is not approved.");
        }

        // Only handle Order cancellation for now, can be extended for other models
        if ($this->approvable_type === \App\Models\Order::class) {
            $order = $this->approvable;
            if (!$order) {
                throw new \Exception("Order not found.");
            }

            // Execute Accurate Deletion
            $accurateDocs = $order->accurateDocs;
            
            // Accurate rules: must delete receipt first, then invoice
            $receiptDoc = $accurateDocs->whereIn('doc_type', ['SALES_RECEIPT', 'DP_RECEIPT', 'receipt'])->first();
            $invoiceDoc = $accurateDocs->whereIn('doc_type', ['SALES_INVOICE', 'DP_INVOICE', 'invoice'])->first();

            $accurateService = app(\App\Services\AccurateService::class);
            $dbSource = strtolower($order->businessUnit->code ?? 'syihab');

            if ($receiptDoc && $receiptDoc->accurate_id) {
                $accurateService->deleteSalesReceipt($receiptDoc->accurate_id, $dbSource);
                $receiptDoc->update(['status' => 'CANCELLED']);
            }

            if ($invoiceDoc && $invoiceDoc->accurate_id) {
                $accurateService->deleteSalesInvoice($invoiceDoc->accurate_id, $dbSource);
                $invoiceDoc->update(['status' => 'CANCELLED']);
            }

            // Update local order status
            $order->update(['order_status' => 'CANCELLED']);
            $this->update(['status' => 'COMPLETED']);

            // Note: Accurate automatically restores stock on their end when SI is deleted.
            // If Tokopon uses a separate local stock sync, you'd trigger the stock sync job here.
            // e.g. foreach($order->items as $item) { SyncItemFromAccurate($item->product->item_no); }
            
            return true;
        }

        throw new \Exception("Execution logic for {$this->approvable_type} is not defined.");
    }
}
