<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessUnit;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class SyncPurchaseOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accurate:sync-pos {bu_code?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Purchase Orders from Accurate';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Purchase Order Sync...');
        $accurateService = app(AccurateService::class);
        Log::info("Starting PO SYNC");
        $buCode = $this->argument('bu_code');
        if ($buCode) {
            $bus = BusinessUnit::where('code', $buCode)->where('is_active', true)->get();
        } else {
            $bus = BusinessUnit::where('is_active', true)->get();
        }

        foreach ($bus as $bu) {
            $this->info("Syncing POs for BU: {$bu->code}");
            try {
                $pos = $accurateService->getPurchaseOrders($bu->code);
                $count = 0;

                foreach ($pos as $poData) {
                    // Cek jika statusnya bukan UNAPPROVED / REJECTED
                    $statusName = $poData['statusName'] ?? '';
                    if (in_array($statusName, ['UNAPPROVED', 'REJECTED'])) {
                        continue;
                    }

                    // Untuk PO, pastikan detail diambil
                    $detailPo = $accurateService->getPurchaseOrderDetail($poData['id'], $bu->code);
                    if (!$detailPo) continue;

                    // 1. Filter item persediaan (INVENTORY) dari Accurate terlebih dahulu
                    $inventoryDetailItems = [];
                    if (isset($detailPo['detailItem']) && is_array($detailPo['detailItem'])) {
                        foreach ($detailPo['detailItem'] as $item) {
                            $itemType = $item['item']['itemType'] ?? $item['itemType'] ?? null;
                            $itemTypeName = $item['item']['itemTypeName'] ?? $item['itemTypeName'] ?? null;
                            $itemNo = $item['item']['no'] ?? $item['itemNo'] ?? '';

                            // Fallback jika itemType belum ada di payload detail PO, cek master ProductAccurate
                            if (!$itemType && $itemNo !== '') {
                                $pa = \App\Models\ProductAccurate::where('item_no', $itemNo)
                                    ->where('database_source', $bu->code)
                                    ->first();
                                if (!$pa) {
                                    $pa = \App\Models\ProductAccurate::where('item_no', $itemNo)->first();
                                }
                                if ($pa) {
                                    $itemType = $pa->itemType;
                                }
                            }

                            // Verifikasi tipe item: hanya izinkan INVENTORY / Persediaan
                            $isInventory = false;
                            if ($itemType && strtoupper($itemType) === 'INVENTORY') {
                                $isInventory = true;
                            } elseif ($itemTypeName && (stripos($itemTypeName, 'persediaan') !== false || stripos($itemTypeName, 'inventory') !== false)) {
                                $isInventory = true;
                            } elseif (!$itemType && !$itemTypeName) {
                                if (!str_contains(strtoupper($itemNo), '.ADM.') && !str_starts_with(strtoupper($itemNo), 'ADM')) {
                                    $isInventory = true;
                                }
                            }

                            if ($isInventory) {
                                $inventoryDetailItems[] = $item;
                            }
                        }
                    }

                    // 2. JIKA TIDAK ADA SATUPUN ITEM PERSEDIAAN (100% Administrasi / Non-Inventory), LEWATI PO INI
                    if (empty($inventoryDetailItems)) {
                        // Jika PO sebelumnya sempat tersimpan di database lokal ZED dan belum pernah discan, bersihkan
                        $existingPo = PurchaseOrder::where('accurate_po_id', $detailPo['id'] ?? $poData['id'])
                            ->where('database_source', $bu->code)
                            ->first();
                        if ($existingPo) {
                            $hasInspections = \App\Models\DeviceInspection::whereIn('inspectable_id', $existingPo->items()->pluck('id'))
                                ->where('inspectable_type', PurchaseOrderItem::class)
                                ->exists();
                            if (!$hasInspections) {
                                $existingPo->items()->delete();
                                $existingPo->delete();
                            }
                        }
                        continue;
                    }

                    // 3. Sync Vendor untuk PO Persediaan
                    $vendorId = null;
                    if (isset($detailPo['vendor']['id'])) {
                        $poVendorNo = $detailPo['vendor']['vendorNo'] ?? '';
                        if ($poVendorNo !== '') {
                            $vendor = Vendor::updateOrCreate(
                                [
                                    'vendor_no' => $poVendorNo,
                                ],
                                [
                                    'accurate_vendor_id' => $detailPo['vendor']['id'],
                                    'database_source' => $bu->code,
                                    'vendor_name' => $detailPo['vendor']['name'] ?? 'Unknown',
                                ]
                            );
                            $vendorId = $vendor->id;
                        }
                    }

                    // Evaluasi status Accurate
                    $accStatus = strtoupper($detailPo['status'] ?? '');
                    $accStatusName = $detailPo['statusName'] ?? '';
                    $isAccFullyReceived = in_array($accStatus, ['FULLRECEIVED', 'CLOSED']) 
                        || (stripos($accStatusName, 'terproses') !== false && stripos($accStatusName, 'sebagian') === false);

                    $initialStatus = 'PENDING';
                    if ($isAccFullyReceived) {
                        $initialStatus = 'COMPLETED';
                    } elseif ($accStatus === 'WAITING' || stripos($accStatusName, 'sebagian') !== false) {
                        $initialStatus = 'PARTIAL';
                    }

                    // 4. Create atau Update Purchase Order
                    $po = PurchaseOrder::updateOrCreate(
                        [
                            'accurate_po_id' => $detailPo['id'] ?? $poData['id'],
                            'database_source' => $bu->code,
                        ],
                        [
                            'po_number' => $detailPo['number'] ?? $detailPo['no'] ?? 'PO-' . ($poData['id']),
                            'vendor_id' => $vendorId,
                            'status' => $initialStatus,
                            'po_date' => isset($detailPo['transDate'])
                                ? (str_contains($detailPo['transDate'], '/')
                                    ? \Carbon\Carbon::createFromFormat('d/m/Y', $detailPo['transDate'])->format('Y-m-d')
                                    : \Carbon\Carbon::parse($detailPo['transDate'])->format('Y-m-d'))
                                : null,
                            'description' => $detailPo['description'] ?? null,
                        ]
                    );

                    // 5. Sync items persediaan saja
                    $existingItemIds = [];
                    foreach ($inventoryDetailItems as $item) {
                        $itemNo = $item['item']['no'] ?? $item['itemNo'];
                        $unitPrice = $item['unitPrice'] ?? 0;
                        $itemName = $item['item']['name'] ?? $item['itemName'];
                        $quantity = (int) ($item['quantity'] ?? 0);
                        $shipQuantity = (int) ($item['shipQuantity'] ?? 0);
                        $remainingQuantity = (int) ($item['remainingQuantity'] ?? 0);
                        $isItemClosed = (bool) ($item['closed'] ?? false);
                        $detailId = $item['id'] ?? null;

                        // Ekstrak status apakah barang membutuhkan nomor serial / IMEI (manageSN)
                        $hasSn = false;
                        if (isset($item['item']['manageSN'])) {
                            $hasSn = (bool) $item['item']['manageSN'];
                        } elseif (isset($item['manageSN'])) {
                            $hasSn = (bool) $item['manageSN'];
                        } elseif (isset($item['item']['serialNumberType'])) {
                            $hasSn = $item['item']['serialNumberType'] === 'UNIQUE';
                        } else {
                            $pa = \App\Models\ProductAccurate::where('item_no', $itemNo)
                                ->where('database_source', $bu->code)
                                ->first();
                            if (!$pa) {
                                $pa = \App\Models\ProductAccurate::where('item_no', $itemNo)->first();
                            }
                            if ($pa) {
                                $hasSn = (bool) $pa->has_sn;
                            }
                        }

                        $poItem = null;

                        if ($detailId) {
                            $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                                ->where('accurate_detail_id', $detailId)
                                ->first();

                            if (!$poItem) {
                                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                                    ->whereNull('accurate_detail_id')
                                    ->where('item_no', $itemNo)
                                    ->where('unit_price', $unitPrice)
                                    ->first();
                            }
                        } else {
                            $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                                ->where('item_no', $itemNo)
                                ->where('unit_price', $unitPrice)
                                ->first();
                        }

                        // Hitung received dan pushed berdasarkan riwayat Accurate + lokal
                        $currentReceived = $poItem ? (int) $poItem->quantity_received : 0;
                        $currentPushed = $poItem ? (int) $poItem->quantity_pushed : 0;
                        $inspectionCount = $poItem ? $poItem->inspections()->count() : 0;

                        $newReceived = max($currentReceived, $inspectionCount, $shipQuantity);
                        $newPushed = max($currentPushed, $shipQuantity);

                        // Jika item atau PO di Accurate sudah berstatus selesai/terproses penuh:
                        if ($isAccFullyReceived || $isItemClosed || ($shipQuantity >= $quantity && $quantity > 0)) {
                            $newReceived = max($newReceived, $quantity);
                            $newPushed = max($newPushed, $quantity);
                        }

                        $itemPayload = [
                            'item_no' => $itemNo,
                            'unit_price' => $unitPrice,
                            'item_name' => $itemName,
                            'has_sn' => $hasSn,
                            'quantity_ordered' => $quantity,
                            'quantity_received' => $newReceived,
                            'quantity_pushed' => $newPushed,
                        ];

                        if ($detailId) {
                            $itemPayload['accurate_detail_id'] = $detailId;
                        }

                        if ($poItem) {
                            $poItem->update($itemPayload);
                        } else {
                            $itemPayload['purchase_order_id'] = $po->id;
                            $poItem = PurchaseOrderItem::create($itemPayload);
                        }

                        $existingItemIds[] = $poItem->id;
                    }

                    // 6. Evaluasi status akhir PO lokal berdasarkan total kuantitas
                    $totalOrdered = PurchaseOrderItem::where('purchase_order_id', $po->id)->sum('quantity_ordered');
                    $totalReceived = PurchaseOrderItem::where('purchase_order_id', $po->id)->sum('quantity_received');

                    if ($isAccFullyReceived || ($totalOrdered > 0 && $totalReceived >= $totalOrdered)) {
                        $po->update(['status' => 'COMPLETED']);
                    } elseif ($totalReceived > 0) {
                        $po->update(['status' => 'PARTIAL']);
                    } else {
                        $po->update(['status' => 'PENDING']);
                    }

                    // 7. Delete items yang sudah tidak ada di PO (hanya jika belum ada riwayat inspeksi scan)
                    $itemsToDelete = PurchaseOrderItem::where('purchase_order_id', $po->id)
                        ->whereNotIn('id', $existingItemIds)
                        ->get();

                    foreach ($itemsToDelete as $itemDel) {
                        $hasInsp = \App\Models\DeviceInspection::where('inspectable_id', $itemDel->id)
                            ->where('inspectable_type', PurchaseOrderItem::class)
                            ->exists();
                        if (!$hasInsp) {
                            $itemDel->delete();
                        }
                    }

                    $count++;
                }
                Log::info("Synced {$count} POs for BU {$bu->code}");
                $this->info("Synced {$count} POs for BU {$bu->code}");
            } catch (\Exception $e) {
                Log::error("Failed to sync POs for BU {$bu->code}: " . $e->getMessage());
                $this->error("Failed for BU {$bu->code}: " . $e->getMessage());
            }
        }

        $this->info('PO Sync Completed.');
    }
}
