<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
    ];

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

            // Restore deposit balances if this order used any deposits
            $usages = \App\Models\CustomerDepositUsage::where('order_id', $order->id)->get();
            foreach ($usages as $usage) {
                $deposit = $usage->customerDeposit;
                if ($deposit) {
                    $deposit->balance += (float)$usage->amount_used;
                    $deposit->status = 'AVAILABLE'; // Ensure it's available again
                    $deposit->save();
                }
                $usage->delete(); // Remove the usage record since the order is cancelled
            }

            // Kembalikan deposit SO (yang berasal dari DP SO ini) ke AVAILABLE
            \App\Models\CustomerDeposit::where('origin_order_id', $order->id)
                ->where('status', 'USED')
                ->update(['status' => 'AVAILABLE']);

            $this->update(['status' => 'COMPLETED']);
            
            return true;
        }

        // Handle Custom Cashback (executed externally via POS cashier polling)
        if ($this->request_type === 'CUSTOM_CASHBACK') {
            // Kita tidak mengubah status menjadi COMPLETED di sini karena 
            // itu akan dihandle oleh kasir saat statusnya ditarik dari PENDING -> APPROVED
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

        // Handle Sell Phone Approval
        if ($this->approvable_type === \App\Models\SellPhone::class && $this->request_type === 'SELL_PHONE_APPROVAL') {
            $sellPhone = $this->approvable;
            if (!$sellPhone) {
                throw new \Exception("SellPhone not found.");
            }

            $sellPhone->update(['status' => 'PAYING']);

            $this->update(['status' => 'COMPLETED']);
            
            return true;
        }

        // Handle Warranty Replacement Approval
        if ($this->approvable_type === \App\Models\WarrantyClaim::class && $this->request_type === 'WARRANTY_REPLACEMENT') {
            $claim = $this->approvable;
            if (!$claim) {
                throw new \Exception("Warranty Claim not found.");
            }

            // Memastikan relasi dimuat
            $claim->load(['warranty.orderItem.order', 'warranty.orderItem.variant']);

            $payload = $this->payload;
            $replacement_imei = $payload['replacement_imei'] ?? null;
            $replacement_type = $payload['replacement_type'] ?? 'same';
            $replacement_item_no = $payload['replacement_item_no'] ?? null;
            $replacement_price = $payload['replacement_price'] ?? 0;
            $bank_no = $payload['bank_no'] ?? null;
            $original_price = $payload['original_price'] ?? 0;
            $selected_sales_id = $payload['selected_sales_id'] ?? null;
            $manual_note = $payload['manual_note'] ?? null;
            $resolution_notes = $payload['resolution_notes'] ?? null;
            $replacement_product_name = $payload['replacement_product_name'] ?? null;
            
            $business_unit_id = $payload['business_unit_id'] ?? 1;
            $branch_id = $payload['branch_id'] ?? null;
            $branch_name = $payload['branch_name'] ?? 'Toko';

            $originalPrice = (float) $original_price;
            $newItemNo = $replacement_type === 'different' ? $replacement_item_no : null;
            $newPrice = $replacement_type === 'different' ? (float) $replacement_price : $originalPrice;
            $priceDifference = $newPrice - $originalPrice;

            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                if ($priceDifference < 0) {
                    $claim->status = 'waiting_refund';
                    $claim->refund_amount = abs($priceDifference);
                } elseif ($priceDifference > 0) {
                    $claim->status = 'waiting_payment';
                    $claim->refund_amount = abs($priceDifference);
                } else {
                    $claim->status = 'completed';
                    $claim->refund_amount = null;
                }

                if ($claim->status === 'completed') {
                    $claim->resolved_at = \Carbon\Carbon::now();
                }
                $claim->resolution = 'replaced';
                $claim->resolution_type = $replacement_type === 'same' ? 'replacement_same' : 'replacement_different';
                $claim->replacement_serial_number = $replacement_imei;
                $claim->replacement_item_no = $newItemNo;
                $claim->replacement_product_name = $replacement_product_name;
                $noteType = $replacement_type === 'same' ? 'Ganti Unit' : ($priceDifference > 0 ? 'Upgrade Unit' : 'Downgrade Unit');

                $finalNotes = "{$noteType} ke IMEI: {$replacement_imei}" .
                    ($newItemNo ? " (Barang Baru: {$replacement_product_name})" : "");

                if (!empty($manual_note)) {
                    $finalNotes .= "\nCatatan Tambahan: {$manual_note}";
                }

                $claim->resolution_notes = $finalNotes . " | {$resolution_notes}";
                // approved_by is now the person who approved the request
                $claim->approved_by = \Illuminate\Support\Facades\Auth::id() ?? $this->requested_by;
                $claim->save();

                $warranty = $claim->warranty;
                $oldSn = $warranty->serial_number;

                if (!$warranty->original_serial_number) {
                    $warranty->original_serial_number = $oldSn;
                }

                $reason = $replacement_type === 'same'
                    ? 'replacement_same'
                    : ($priceDifference > 0 ? 'replacement_upgrade' : 'replacement_downgrade');

                \App\Models\WarrantySerialLog::create([
                    'warranty_id' => $warranty->id,
                    'warranty_claim_id' => $claim->id,
                    'old_serial_number' => $oldSn,
                    'new_serial_number' => $replacement_imei,
                    'reason' => $reason,
                    'changed_by' => $this->requested_by,
                    'notes' => $finalNotes,
                ]);

                \App\Models\WarrantyReplacement::create([
                    'warranty_claim_id' => $claim->id,
                    'old_imei' => $oldSn,
                    'new_imei' => $replacement_imei,
                    'processed_by' => $this->requested_by,
                    'system_notes' => $finalNotes,
                ]);

                $warranty->serial_number = $replacement_imei;
                $warranty->claims_used = ($warranty->claims_used ?? 0) + 1;
                $warranty->replacement_count = ($warranty->replacement_count ?? 0) + 1;
                $warranty->device_inspection_id = null;
                $warranty->status = 'active';

                $policy = $warranty->policy;
                if ($policy && $policy->replacement_type === 'reset') {
                    $warranty->activated_at = \Carbon\Carbon::now();
                    $warranty->expires_at = \Carbon\Carbon::now()->addDays($policy->duration_days);
                }

                $originalPriceForAccurate = $originalPrice > 0 ? $originalPrice : ($warranty->orderItem->price_at_checkout ?? 0);

                $salesmanNo = null;
                if ($selected_sales_id) {
                    $salesmanNo = \App\Models\Employe::find($selected_sales_id)?->employee_no;
                }

                $accurateService = app(\App\Services\AccurateService::class);
                $accurateResult = $accurateService->processWarrantyReplacement(
                    $claim,
                    $replacement_imei,
                    $newItemNo,
                    $newPrice,
                    $priceDifference,
                    $replacement_type,
                    $bank_no,
                    $originalPriceForAccurate,
                    $salesmanNo,
                    $branch_name
                );

                if ($replacement_type === 'different' && $newItemNo) {
                    $newVariant = \App\Models\ProductVariant::whereHas('accurateData', function ($q) use ($newItemNo) {
                        $q->where('item_no', $newItemNo);
                    })->first();

                    if (!$newVariant) {
                        $newVariant = \App\Models\SecondProductVariant::whereHas('accurateData', function ($q) use ($newItemNo) {
                            $q->where('item_no', $newItemNo);
                        })->first();
                    }
                }

                $warranty->save();

                // 3. Buat Rekaman Order Retur (Minus) agar Laporan Penjualan lokal imbang
                $retInvoiceNo = $accurateResult['sales_return']['number'] ?? null;
                $retOrderNumber = 'RET-' . $claim->claim_number;
                
                $retOrder = \App\Models\Order::create([
                    'business_unit_id' => $claim->warranty->policy?->business_unit_id ?? $business_unit_id,
                    'user_id' => $claim->customer_user_id,
                    'order_number' => $retOrderNumber,
                    'accurate_invoice_no' => $retInvoiceNo,
                    'order_date' => \Carbon\Carbon::now()->format('Y-m-d'),
                    'total_amount' => -$originalPriceForAccurate,
                    'shipping_cost' => 0,
                    'discount_amount' => 0,
                    'mdr_percentage' => 0,
                    'mdr_amount' => 0,
                    'grand_total' => -$originalPriceForAccurate,
                    'order_status' => 'COMPLETED',
                    'order_channel' => 'POS',
                    'handled_by' => $this->requested_by,
                    'sales_id' => $selected_sales_id ?? ($claim->warranty->orderItem->order->sales_id ?? $this->requested_by),
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branch_name, 'is_warranty_return' => true],
                    'notes' => "Pengembalian Unit Retur Garansi #{$claim->claim_number}. IMEI Lama: {$oldSn}",
                    'branch_id' => $branch_id,
                ]);

                \App\Models\OrderItem::create([
                    'order_id' => $retOrder->id,
                    'product_id' => $claim->warranty->orderItem->product_id,
                    'product_variant_type' => $claim->warranty->orderItem->product_variant_type,
                    'product_variant_id' => $claim->warranty->orderItem->product_variant_id,
                    'product_name' => $claim->warranty->orderItem->product_name,
                    'serial_number' => $oldSn,
                    'qty' => -1,
                    'price_at_checkout' => $originalPriceForAccurate,
                    'vendor_name_snapshot' => $claim->warranty->orderItem->vendor_name_snapshot,
                    'discount_amount' => 0,
                    'promo_discount_amount' => 0,
                    'subtotal' => -$originalPriceForAccurate,
                ]);

                // 4. Buat Rekaman Order Pengganti (WR-XXX)
                $newInvoiceNo = $accurateResult['sales_invoice']['number'] ?? null;
                $newOrderNumber = 'WR-' . $claim->claim_number;

                $order = \App\Models\Order::create([
                    'business_unit_id' => $claim->warranty->policy?->business_unit_id ?? $business_unit_id,
                    'user_id' => $claim->customer_user_id,
                    'order_number' => $newOrderNumber,
                    'accurate_invoice_no' => $newInvoiceNo,
                    'order_date' => \Carbon\Carbon::now()->format('Y-m-d'),
                    'total_amount' => $newPrice,
                    'shipping_cost' => 0,
                    'discount_amount' => 0,
                    'mdr_percentage' => 0,
                    'mdr_amount' => 0,
                    'grand_total' => $newPrice,
                    'order_status' => 'COMPLETED',
                    'order_channel' => 'POS',
                    'handled_by' => $this->requested_by,
                    'sales_id' => $selected_sales_id ?? ($claim->warranty->orderItem->order->sales_id ?? $this->requested_by),
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branch_name, 'is_warranty_replacement' => true],
                    'notes' => "Ganti Unit Klaim Garansi #{$claim->claim_number}. Pengganti untuk IMEI: {$oldSn}",
                    'branch_id' => $branch_id,
                ]);

                // Simpan referensi Accurate Documents
                if (isset($accurateResult['sales_return']) && !empty($accurateResult['sales_return']['id'])) {
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $retOrder->id, // Tautkan langsung ke retOrder
                        'doc_type' => 'SALES_RETURN',
                        'accurate_id' => $accurateResult['sales_return']['id'],
                        'doc_number' => $accurateResult['sales_return']['number'],
                        'status' => 'SUCCESS'
                    ]);
                }
                if (isset($accurateResult['sales_invoice']) && !empty($accurateResult['sales_invoice']['id'])) {
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_INVOICE',
                        'accurate_id' => $accurateResult['sales_invoice']['id'],
                        'doc_number' => $accurateResult['sales_invoice']['number'],
                        'status' => 'SUCCESS'
                    ]);
                }
                if (isset($accurateResult['sales_receipt']) && !empty($accurateResult['sales_receipt']['id'])) {
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_RECEIPT',
                        'accurate_id' => $accurateResult['sales_receipt']['id'],
                        'doc_number' => $accurateResult['sales_receipt']['number'],
                        'status' => 'SUCCESS'
                    ]);
                }

                $orderItem = \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $claim->warranty->orderItem->product_id,
                    'product_variant_type' => $replacement_type === 'different' && $newItemNo ?
                        (isset($newVariant) ? get_class($newVariant) : $claim->warranty->orderItem->product_variant_type) :
                        $claim->warranty->orderItem->product_variant_type,
                    'product_variant_id' => $replacement_type === 'different' && $newItemNo ?
                        ($newVariant->id ?? $claim->warranty->orderItem->product_variant_id) :
                        $claim->warranty->orderItem->product_variant_id,
                    'product_name' => $replacement_type === 'different' && $replacement_product_name ?
                        $replacement_product_name :
                        $claim->warranty->orderItem->product_name,
                    'serial_number' => $replacement_imei,
                    'qty' => 1,
                    'price_at_checkout' => $newPrice,
                    'vendor_name_snapshot' => $claim->warranty->orderItem->vendor_name_snapshot,
                    'discount_amount' => 0,
                    'promo_discount_amount' => 0,
                    'subtotal' => $newPrice,
                ]);

                // Tautkan garansi ke OrderItem baru agar datanya rapi
                $warranty->order_item_id = $orderItem->id;
                $warranty->save();

                $this->update(['status' => 'COMPLETED']);

                \Illuminate\Support\Facades\DB::commit();
                
                return true;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                \Illuminate\Support\Facades\Log::error("Failed to execute WARRANTY_REPLACEMENT approval: " . $e->getMessage());
                throw $e;
            }
        }

        throw new \Exception("Execution logic for {$this->request_type} on {$this->approvable_type} is not defined.");
    }

    public function executeRejectedAction(array $params = [])
    {
        if ($this->status !== 'REJECTED') {
            throw new \Exception("Cannot execute a request that is not rejected.");
        }

        // Handle Sell Phone Approval Rejection
        if ($this->approvable_type === \App\Models\SellPhone::class && $this->request_type === 'SELL_PHONE_APPROVAL') {
            $sellPhone = $this->approvable;
            if ($sellPhone) {
                $sellPhone->update(['status' => 'CANCELLED']);
            }
            return true;
        }

        // For other types, rejection might not require specific action other than just marking as rejected
        return true;
    }
}
