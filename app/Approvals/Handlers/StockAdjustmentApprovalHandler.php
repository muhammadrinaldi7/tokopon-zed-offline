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

        // 1. Mutasi Stok Lokal di Gudang Terkait (jika tracking aktif)
        $this->mutateLocalStock($adjustment);

        // 2. Susun Payload dan Kirim ke Accurate Online
        try {
            $notesFull = "[{$adjustment->reason_category}] " . ($adjustment->notes ?: 'Penyesuaian Stok');
            if ($adjustment->target_item_no) {
                $notesFull .= " (Tujuan Alokasi: {$adjustment->target_item_no} - {$adjustment->target_product_name}";
                if ($adjustment->target_serial_number) {
                    $notesFull .= " [SN/IMEI: {$adjustment->target_serial_number}]";
                }
                $notesFull .= ")";
            }

            $warehouseName = $adjustment->warehouse?->name ?? ($adjustment->warehouse_name ?? 'UTAMA');

            $detailItem = [
                'itemNo'             => $adjustment->item_no,
                'itemAdjustmentType' => $adjustment->adjustment_type === 'OUT' ? 'ADJUSTMENT_OUT' : 'ADJUSTMENT_IN',
                'quantity'           => (float) $adjustment->quantity,
                'warehouseName'      => $warehouseName,
            ];

            // Tambahkan Project No jika ada
            if (!empty($adjustment->project_no)) {
                $detailItem['projectNo'] = $adjustment->project_no;
            }

            // Tambahkan Unit Cost jika ada
            if (!empty($adjustment->unit_cost) && $adjustment->unit_cost > 0) {
                $detailItem['unitCost'] = (float) $adjustment->unit_cost;
            }

            // Tambahkan Serial Number jika ada
            if (!empty($adjustment->serial_numbers) && is_array($adjustment->serial_numbers)) {
                $detailSN = [];
                foreach ($adjustment->serial_numbers as $sn) {
                    $snVal = is_array($sn) ? ($sn['serial_number'] ?? ($sn['sn'] ?? '')) : $sn;
                    if (!empty(trim((string)$snVal))) {
                        $detailSN[] = [
                            'serialNumberNo' => trim((string)$snVal),
                            'quantity'       => 1,
                        ];
                    }
                }
                if (!empty($detailSN)) {
                    $detailItem['detailSerialNumber'] = $detailSN;
                }
            }

            $payload = [
                'transDate'           => now()->format('d/m/Y'),
                'adjustmentAccountNo' => $adjustment->accurate_account_no ?: '5101',
                'description'         => $notesFull,
                'branchName'          => $adjustment->branch?->name,
                'detailItem'          => [$detailItem],
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

        // Cari variant berdasarkan SKU
        $variant = ProductVariant::where('sku', $adjustment->item_no)->first();
        if (!$variant) {
            $variant = ProductAccurate::where('item_no', $adjustment->item_no)->first();
        }

        if ($variant) {
            $stock = WarehouseStock::where('warehouse_id', $adjustment->warehouse_id)
                ->where('variant_type', get_class($variant))
                ->where('variant_id', $variant->id)
                ->first();

            if ($stock) {
                if ($adjustment->adjustment_type === 'OUT') {
                    $stock->decrement('stock', $adjustment->quantity);
                } else {
                    $stock->increment('stock', $adjustment->quantity);
                }
            }
        }

        // Jika ada serial number yang disesuaikan keluar
        if ($adjustment->adjustment_type === 'OUT' && !empty($adjustment->serial_numbers)) {
            foreach ($adjustment->serial_numbers as $sn) {
                $snVal = is_array($sn) ? ($sn['serial_number'] ?? ($sn['sn'] ?? '')) : $sn;
                if (!empty($snVal)) {
                    ProductSerialNumber::where('item_no', $adjustment->item_no)
                        ->where('serial_number', trim((string)$snVal))
                        ->update(['status' => 'ADJUSTED_OUT']);
                }
            }
        }
    }
}
