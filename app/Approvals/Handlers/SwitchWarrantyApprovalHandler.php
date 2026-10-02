<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\DeviceInspection;
use App\Models\OrderItem;
use App\Models\Warranty;
use App\Services\WarrantyCalculatorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SwitchWarrantyApprovalHandler implements ApprovalHandlerInterface
{
    /**
     * Menjalankan aksi setelah pengajuan alih garansi disetujui sepenuhnya (Level Final: MO).
     */
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $payload = $request->payload ?? [];
        $sn = trim((string)($payload['serial_number'] ?? ''));
        $inspectionId = $payload['device_inspection_id'] ?? null;
        $newOrderItemId = $payload['new_order_item_id'] ?? null;
        $oldOrderItemId = $payload['old_order_item_id'] ?? null;
        $oldOrderNumber = $payload['old_order_number'] ?? '-';

        if (empty($sn) || empty($newOrderItemId)) {
            throw new Exception("Payload tidak lengkap untuk approval alih garansi #{$request->id}.");
        }

        $newOrderItem = OrderItem::with('order')->findOrFail($newOrderItemId);
        $newOrder = $newOrderItem->order;

        $orderStatus = strtoupper((string)($newOrder->order_status ?? $newOrder->status ?? ''));

        if (!$newOrder || !in_array($orderStatus, ['COMPLETED', 'PIUTANG', 'SUCCESS'])) {
            throw new Exception("Order tujuan #{$newOrder?->order_number} tidak berstatus sah (COMPLETED / PIUTANG).");
        }

        DB::transaction(function () use ($sn, $inspectionId, $newOrderItem, $newOrder, $oldOrderItemId, $oldOrderNumber, $payload, $request) {
            // 1. Alihkan DeviceInspection ke OrderItem Baru
            $inspection = null;
            if ($inspectionId) {
                $inspection = DeviceInspection::find($inspectionId);
            }
            if (!$inspection && !empty($sn)) {
                $inspection = DeviceInspection::where('imei', $sn)
                    ->where('inspectable_type', OrderItem::class)
                    ->latest()
                    ->first();
            }

            if ($inspection) {
                $inspection->update([
                    'inspectable_type' => get_class($newOrderItem),
                    'inspectable_id'   => $newOrderItem->id,
                ]);
            }

            // 2. Alihkan / Aktifkan Kembali Kartu Garansi (Warranty)
            $warranties = Warranty::where('serial_number', $sn)
                ->where(function ($q) use ($payload, $oldOrderItemId) {
                    if (!empty($payload['voided_warranty_ids'])) {
                        $q->whereIn('id', $payload['voided_warranty_ids']);
                    } elseif ($oldOrderItemId) {
                        $q->where('order_item_id', $oldOrderItemId);
                    } else {
                        $q->where('status', 'voided');
                    }
                })
                ->get();

            if ($warranties->isNotEmpty()) {
                foreach ($warranties as $w) {
                    $w->update([
                        'order_item_id'        => $newOrderItem->id,
                        'customer_user_id'     => $newOrder->user_id,
                        'device_inspection_id' => $inspection?->id ?? $w->device_inspection_id,
                        'status'               => 'active',
                    ]);
                }
            } else {
                // Fallback: Jika belum ada kartu garansi sebelumnya, buat baru via WarrantyCalculatorService
                $calculator = new WarrantyCalculatorService();
                $policies = $calculator->calculateWarranties($newOrder, $newOrderItem);
                $now = Carbon::now();

                foreach ($policies as $policy) {
                    Warranty::create([
                        'warranty_policy_id'   => $policy->id,
                        'order_item_id'        => $newOrderItem->id,
                        'serial_number'        => $sn,
                        'customer_user_id'     => $newOrder->user_id,
                        'type'                 => $policy->coverage_type,
                        'duration_days'        => $policy->duration_days,
                        'activated_at'         => $now,
                        'expires_at'           => $now->copy()->addDays($policy->duration_days),
                        'status'               => 'active',
                        'claims_used'          => 0,
                        'device_inspection_id' => $inspection?->id,
                        'source'               => $policy->type === 'addon_warranty' ? 'purchase' : 'activation',
                    ]);
                }
            }

            Log::info("Switch Warranty Selesai: SN {$sn} berhasil dialihkan dari Order #{$oldOrderNumber} ke Order #{$newOrder->order_number} via Approval #{$request->id}");
        });
    }

    /**
     * Menjalankan aksi jika pengajuan ditolak.
     */
    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        Log::info("Switch Warranty Ditolak: Approval #{$request->id} untuk SN " . ($request->payload['serial_number'] ?? '-') . " ditolak.");
    }
}
