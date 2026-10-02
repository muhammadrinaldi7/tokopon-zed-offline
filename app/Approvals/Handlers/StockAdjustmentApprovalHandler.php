<?php

namespace App\Approvals\Handlers;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Models\ApprovalRequest;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StockAdjustmentApprovalHandler implements ApprovalHandlerInterface
{
    /**
     * Eksekusi saat penyesuaian stok disetujui sepenuhnya oleh pimpinan.
     */
    public function handleApproved(ApprovalRequest $request, array $params = []): void
    {
        $adjustment = $request->approvable;
        if (!$adjustment instanceof StockAdjustment) {
            throw new Exception("Data StockAdjustment tidak ditemukan untuk approval request #{$request->id}.");
        }

        $adjustment->loadMissing(['items', 'warehouse', 'branch', 'businessUnit', 'reason']);

        // 1. Mutasi Stok Lokal di Gudang Terkait (jika tracking aktif)
        $this->mutateLocalStock($adjustment);

        // 2. Susun Payload dan Kirim ke Accurate Online
        try {
            $notesFull = "[{$adjustment->reason_label}] " . ($adjustment->notes ?: 'Penyesuaian Stok');
            
            // Tambahkan ringkasan SKU tujuan jika ada
            $targetSummaries = [];
            foreach ($adjustment->items as $item) {
                $targetList = $item->target_items_list;
                if (!empty($targetList)) {
                    foreach ($targetList as $t) {
                        $tName = $t['product_name'] ?? $t['item_no'];
                        $tQty = $t['quantity'] ?? 1;
                        $tSn = !empty($t['serial_number']) ? "[SN:{$t['serial_number']}]" : "";
                        $targetSummaries[] = "{$tQty}x {$tName} {$tSn}";
                    }
                } elseif (!empty($item->target_item_no)) {
                    $targetSummaries[] = "{$item->item_no} -> {$item->target_item_no}";
                }
            }
            if (!empty($targetSummaries)) {
                $notesFull .= " (Tujuan: " . implode(', ', array_slice($targetSummaries, 0, 5)) . ")";
            }

            $warehouseName = $adjustment->warehouse?->name ?? ($adjustment->warehouse_name ?? 'UTAMA');
            $detailItems = [];

            if ($adjustment->items->isNotEmpty()) {
                foreach ($adjustment->items as $item) {
                    $detailRow = [
                        'itemNo'             => $item->item_no,
                        'itemAdjustmentType' => $item->adjustment_type === 'OUT' ? 'ADJUSTMENT_OUT' : 'ADJUSTMENT_IN',
                        'quantity'           => (float) $item->quantity,
                        'warehouseName'      => $warehouseName,
                    ];

                    if (!empty($item->project_no)) {
                        $detailRow['projectNo'] = $item->project_no;
                    }

                    if (!empty($item->unit_cost) && $item->unit_cost > 0) {
                        $detailRow['unitCost'] = (float) $item->unit_cost;
                    }

                    if (!empty($item->serial_numbers) && is_array($item->serial_numbers)) {
                        $detailSN = [];
                        foreach ($item->serial_numbers as $sn) {
                            $snVal = is_array($sn) ? ($sn['serial_number'] ?? ($sn['sn'] ?? '')) : $sn;
                            if (!empty(trim((string)$snVal))) {
                                $detailSN[] = [
                                    'serialNumberNo' => trim((string)$snVal),
                                    'quantity'       => 1,
                                ];
                            }
                        }
                        if (!empty($detailSN)) {
                            $detailRow['detailSerialNumber'] = $detailSN;
                        }
                    }

                    $detailItems[] = $detailRow;
                }
            } else {
                // Fallback jika single item lama
                $detailRow = [
                    'itemNo'             => $adjustment->item_no,
                    'itemAdjustmentType' => $adjustment->adjustment_type === 'OUT' ? 'ADJUSTMENT_OUT' : 'ADJUSTMENT_IN',
                    'quantity'           => (float) $adjustment->quantity,
                    'warehouseName'      => $warehouseName,
                ];
                if (!empty($adjustment->project_no)) {
                    $detailRow['projectNo'] = $adjustment->project_no;
                }
                if (!empty($adjustment->unit_cost) && $adjustment->unit_cost > 0) {
                    $detailRow['unitCost'] = (float) $adjustment->unit_cost;
                }
                $detailItems[] = $detailRow;
            }

            $payload = [
                'transDate'           => now()->format('d/m/Y'),
                'adjustmentAccountNo' => $adjustment->accurate_account_no ?: '5101',
                'description'         => mb_substr($notesFull, 0, 250),
                'branchName'          => $adjustment->branch?->name,
                'detailItem'          => $detailItems,
            ];

            $buCode = $adjustment->businessUnit?->code ?? 'syihab';
            $accurateService = app(AccurateService::class);
            $response = $accurateService->postItemAdjustment($payload, $buCode);

            $accurateNo = null;
            if (is_array($response)) {
                $accurateNo = $response['number'] ?? ($response['r']['number'] ?? ($response['d']['number'] ?? ($response['id'] ?? null)));
            }

            $adjustment->update([
                'status'                 => 'SYNCED',
                'approved_by'            => Auth::id() ?? $request->requested_by,
                'approved_at'            => now(),
                'synced_at'              => now(),
                'accurate_adjustment_no' => (string) ($accurateNo ?: 'SYNCED-' . now()->timestamp),
                'sync_error'             => null,
            ]);

            Log::info("StockAdjustment #{$adjustment->id} ({$adjustment->adjustment_number}) berhasil disinkronkan ke Accurate: {$accurateNo}");
        } catch (\Throwable $e) {
            Log::error("Gagal sinkronisasi StockAdjustment #{$adjustment->id} ke Accurate: " . $e->getMessage());

            $adjustment->update([
                'status'      => 'FAILED_SYNC',
                'approved_by' => Auth::id() ?? $request->requested_by,
                'approved_at' => now(),
                'sync_error'  => $e->getMessage(),
            ]);

            throw new Exception("Penyesuaian stok disetujui di sistem lokal, namun gagal sinkron ke Accurate: " . $e->getMessage());
        }
    }

    /**
     * Eksekusi saat penyesuaian stok ditolak.
     */
    public function handleRejected(ApprovalRequest $request, array $params = []): void
    {
        $adjustment = $request->approvable;
        if ($adjustment instanceof StockAdjustment) {
            $adjustment->update([
                'status'      => 'REJECTED',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        }
    }

    /**
     * Mutasi stok lokal di tabel WarehouseStock & ProductSerialNumber.
     */
    protected function mutateLocalStock(StockAdjustment $adjustment): void
    {
        if (!$adjustment->warehouse_id) {
            return;
        }

        $items = $adjustment->items->isNotEmpty() ? $adjustment->items : collect([$adjustment]);

        foreach ($items as $item) {
            // Cari variant berdasarkan SKU
            $variant = ProductVariant::where('sku', $item->item_no)->first();
            if (!$variant) {
                $variant = ProductAccurate::where('item_no', $item->item_no)->first();
            }

            if ($variant) {
                $stock = WarehouseStock::where('warehouse_id', $adjustment->warehouse_id)
                    ->where('variant_type', get_class($variant))
                    ->where('variant_id', $variant->id)
                    ->first();

                if ($stock) {
                    if ($item->adjustment_type === 'OUT') {
                        $stock->decrement('stock', $item->quantity);
                    } else {
                        $stock->increment('stock', $item->quantity);
                    }
                }
            }

            // Jika ada serial number yang disesuaikan keluar
            if ($item->adjustment_type === 'OUT' && !empty($item->serial_numbers)) {
                foreach ($item->serial_numbers as $sn) {
                    $snVal = is_array($sn) ? ($sn['serial_number'] ?? ($sn['sn'] ?? '')) : $sn;
                    if (!empty($snVal)) {
                        ProductSerialNumber::where('item_no', $item->item_no)
                            ->where('serial_number', trim((string)$snVal))
                            ->update(['status' => 'ADJUSTED_OUT']);
                    }
                }
            }
        }
    }
}
