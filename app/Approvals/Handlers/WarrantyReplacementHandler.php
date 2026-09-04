<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\Employe;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\WarrantyClaim;
use App\Models\WarrantyReplacement;
use App\Models\WarrantySerialLog;
use App\Services\AccurateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarrantyReplacementHandler implements ApprovalHandlerInterface
{
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $claim = $request->approvable;
        if (!$claim) {
            throw new Exception("Warranty Claim not found for approval #{$request->id}.");
        }

        // Memastikan relasi dimuat
        $claim->load(['warranty.orderItem.order', 'warranty.orderItem.variant']);

        $payload = $request->payload ?? [];
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

        $business_unit_id = $request->business_unit_id ?? ($payload['business_unit_id'] ?? 1);
        $branch_id = $request->branch_id ?? ($payload['branch_id'] ?? null);
        $branch_name = $payload['branch_name'] ?? 'Toko';

        $originalPrice = (float) $original_price;
        $newItemNo = $replacement_type === 'different' ? $replacement_item_no : null;
        $newPrice = $replacement_type === 'different' ? (float) $replacement_price : $originalPrice;
        $priceDifference = $newPrice - $originalPrice;

        DB::beginTransaction();
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
                $claim->resolved_at = Carbon::now();
            }
            $claim->resolution = 'replaced';
            $claim->resolution_type = $replacement_type === 'same' ? 'replacement_same' : 'replacement_different';
            $claim->replacement_item_no = $newItemNo;
            $claim->replacement_product_name = $replacement_product_name;
            $noteType = $replacement_type === 'same' ? 'Ganti Unit' : ($priceDifference > 0 ? 'Upgrade Unit' : 'Downgrade Unit');

            $finalNotes = "{$noteType} ke IMEI: {$replacement_imei}" .
                ($newItemNo ? " (Barang Baru: {$replacement_product_name})" : "");

            if (!empty($manual_note)) {
                $finalNotes .= "\nCatatan Tambahan: {$manual_note}";
            }

            $claim->resolution_notes = $finalNotes . " | {$resolution_notes}";
            $claim->approved_by = Auth::id() ?? $request->requested_by;
            $claim->save();

            $warranty = $claim->warranty;
            $oldSn = $warranty->serial_number;

            if (!$warranty->original_serial_number) {
                $warranty->original_serial_number = $oldSn;
            }

            $reason = $replacement_type === 'same'
                ? 'replacement_same'
                : ($priceDifference > 0 ? 'replacement_upgrade' : 'replacement_downgrade');

            WarrantySerialLog::create([
                'warranty_id' => $warranty->id,
                'warranty_claim_id' => $claim->id,
                'old_serial_number' => $oldSn,
                'new_serial_number' => $replacement_imei,
                'reason' => $reason,
                'changed_by' => $request->requested_by,
                'notes' => $finalNotes,
            ]);

            WarrantyReplacement::create([
                'warranty_claim_id' => $claim->id,
                'old_imei' => $oldSn,
                'new_imei' => $replacement_imei,
                'processed_by' => $request->requested_by,
                'system_notes' => $finalNotes,
            ]);

            $warranty->serial_number = $replacement_imei;
            $warranty->claims_used = ($warranty->claims_used ?? 0) + 1;
            $warranty->replacement_count = ($warranty->replacement_count ?? 0) + 1;
            $warranty->device_inspection_id = null;
            $warranty->status = 'active';

            $policy = $warranty->policy;
            if ($policy && $policy->replacement_type === 'reset') {
                $warranty->activated_at = Carbon::now();
                $warranty->expires_at = Carbon::now()->addDays($policy->duration_days);
            }

            $originalPriceForAccurate = $originalPrice > 0 ? $originalPrice : ($warranty->orderItem->price_at_checkout ?? 0);

            $salesmanNo = null;
            if ($selected_sales_id) {
                $salesmanNo = Employe::find($selected_sales_id)?->employee_no;
            }

            $accurateService = app(AccurateService::class);
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
                $newVariant = ProductVariant::whereHas('accurateData', function ($q) use ($newItemNo) {
                    $q->where('item_no', $newItemNo);
                })->first();

                if (!$newVariant) {
                    $newVariant = SecondProductVariant::whereHas('accurateData', function ($q) use ($newItemNo) {
                        $q->where('item_no', $newItemNo);
                    })->first();
                }
            }

            $warranty->save();

            // Rekaman Order Retur (Minus)
            $retInvoiceNo = $accurateResult['sales_return']['number'] ?? null;
            $retOrderNumber = 'RET-' . $claim->claim_number;

            $retOrder = Order::create([
                'business_unit_id' => $claim->warranty->policy?->business_unit_id ?? $business_unit_id,
                'user_id' => $claim->customer_user_id,
                'order_number' => $retOrderNumber,
                'accurate_invoice_no' => $retInvoiceNo,
                'order_date' => Carbon::now()->format('Y-m-d'),
                'total_amount' => -$originalPriceForAccurate,
                'shipping_cost' => 0,
                'discount_amount' => 0,
                'mdr_percentage' => 0,
                'mdr_amount' => 0,
                'grand_total' => -$originalPriceForAccurate,
                'order_status' => 'COMPLETED',
                'order_channel' => 'POS',
                'handled_by' => $request->requested_by,
                'sales_id' => $claim->warranty->orderItem->order->sales_id ?? $request->requested_by,
                'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branch_name, 'is_warranty_return' => true],
                'notes' => "Pengembalian Unit Retur Garansi #{$claim->claim_number}. IMEI Lama: {$oldSn}",
                'branch_id' => $branch_id,
            ]);

            OrderItem::create([
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

            // Rekaman Order Pengganti (WR-XXX)
            $newInvoiceNo = $accurateResult['sales_invoice']['number'] ?? null;
            $newOrderNumber = 'WR-' . $claim->claim_number;

            $order = Order::create([
                'business_unit_id' => $claim->warranty->policy?->business_unit_id ?? $business_unit_id,
                'user_id' => $claim->customer_user_id,
                'order_number' => $newOrderNumber,
                'accurate_invoice_no' => $newInvoiceNo,
                'order_date' => Carbon::now()->format('Y-m-d'),
                'total_amount' => $newPrice,
                'shipping_cost' => 0,
                'discount_amount' => 0,
                'mdr_percentage' => 0,
                'mdr_amount' => 0,
                'grand_total' => $newPrice,
                'order_status' => 'COMPLETED',
                'order_channel' => 'POS',
                'handled_by' => $request->requested_by,
                'sales_id' => $selected_sales_id ?? ($claim->warranty->orderItem->order->sales_id ?? $request->requested_by),
                'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branch_name, 'is_warranty_replacement' => true],
                'notes' => "Ganti Unit Klaim Garansi #{$claim->claim_number}. Pengganti untuk IMEI: {$oldSn}",
                'branch_id' => $branch_id,
            ]);

            if (isset($accurateResult['sales_return']) && !empty($accurateResult['sales_return']['id'])) {
                OrderAccurateDoc::create([
                    'order_id' => $retOrder->id,
                    'doc_type' => 'SALES_RETURN',
                    'accurate_id' => $accurateResult['sales_return']['id'],
                    'doc_number' => $accurateResult['sales_return']['number'],
                    'status' => 'SUCCESS',
                ]);
            }
            if (isset($accurateResult['sales_invoice']) && !empty($accurateResult['sales_invoice']['id'])) {
                OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_INVOICE',
                    'accurate_id' => $accurateResult['sales_invoice']['id'],
                    'doc_number' => $accurateResult['sales_invoice']['number'],
                    'status' => 'SUCCESS',
                ]);
            }
            if (isset($accurateResult['sales_receipt']) && !empty($accurateResult['sales_receipt']['id'])) {
                OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_RECEIPT',
                    'accurate_id' => $accurateResult['sales_receipt']['id'],
                    'doc_number' => $accurateResult['sales_receipt']['number'],
                    'status' => 'SUCCESS',
                ]);
            }

            $orderItem = OrderItem::create([
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

            $warranty->order_item_id = $orderItem->id;
            $warranty->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to execute WARRANTY_REPLACEMENT approval #{$request->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        // No specific rollback needed on rejection
    }
}
