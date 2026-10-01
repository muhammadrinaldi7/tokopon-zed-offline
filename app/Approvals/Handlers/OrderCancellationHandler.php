<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\CustomerDeposit;
use App\Models\CustomerDepositUsage;
use App\Models\OrderItemSerialNumber;
use App\Models\ProductSerialNumber;
use App\Models\Promo;
use App\Models\WarehouseStock;
use App\Models\Warranty;
use App\Services\AccurateService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        // Load items, promos, and handledBy user
        $order->loadMissing(['items.promos', 'handledBy']);

        $itemIds = $order->items->pluck('id')->toArray();
        $allSns = [];

        foreach ($order->items as $item) {
            if (!empty($item->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                $allSns = array_merge($allSns, $sns);
            }
        }

        // Ambil juga dari OrderItemSerialNumber jika ada
        if (!empty($itemIds)) {
            $dbSns = OrderItemSerialNumber::whereIn('order_item_id', $itemIds)->pluck('serial_number')->toArray();
            $allSns = array_merge($allSns, $dbSns);
        }

        $allSns = array_unique(array_filter($allSns));

        // 1. Batalkan / Void Garansi Aktif Terkait
        if (!empty($itemIds)) {
            Warranty::whereIn('order_item_id', $itemIds)
                ->where('status', 'active')
                ->update(['status' => 'voided']);
        }
        if (!empty($allSns)) {
            Warranty::whereIn('serial_number', $allSns)
                ->where('status', 'active')
                ->update(['status' => 'voided']);

            // 2. Kembalikan Status Serial Number (IMEI) ke Available
            ProductSerialNumber::whereIn('serial_number', $allSns)
                ->update(['status' => 'Available']);
        }

        // 3. Kembalikan Stok Gudang (WarehouseStock)
        $warehouseId = $order->handledBy?->warehouse_id ?? Auth::user()?->warehouse_id;
        if ($warehouseId) {
            foreach ($order->items as $item) {
                $warehouseStock = WarehouseStock::where([
                    'warehouse_id' => $warehouseId,
                    'variant_id'   => $item->product_variant_id,
                    'variant_type' => $item->product_variant_type,
                ])->first();

                if ($warehouseStock) {
                    $warehouseStock->increment('stock', (int)$item->qty);
                }
            }
        }

        // 4. Kembalikan Kuota Promo jika ada
        $promoIds = $order->promos()->pluck('promos.id')->toArray();
        if (!empty($promoIds)) {
            Promo::whereIn('id', $promoIds)->decrement('used_quota');
        }

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

        Log::info("Order cancellation approved: Order #{$order->order_number} cancelled. Warranties voided, SNs restored to Available, Stock replenished.");
    }

    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        // No state mutation needed for order cancellation rejection
    }
}
