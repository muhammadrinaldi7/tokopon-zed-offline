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

                    // Sync Vendor
                    $vendorId = null;
                    if (isset($detailPo['vendor']['id'])) {
                        $poVendorNo = $detailPo['vendor']['vendorNo'] ?? '';
                        if ($poVendorNo !== '') {
                            $vendor = Vendor::firstOrCreate(
                                [
                                    'accurate_vendor_id' => $detailPo['vendor']['id'],
                                    'database_source' => $bu->code
                                ],
                                [
                                    'vendor_no' => $poVendorNo,
                                    'vendor_name' => $detailPo['vendor']['name'] ?? 'Unknown',
                                ]
                            );
                            $vendorId = $vendor->id;
                        }
                    }

                    $po = PurchaseOrder::updateOrCreate(
                        [
                            'accurate_po_id' => $detailPo['id'] ?? $poData['id'], // Lebih aman
                            'database_source' => $bu->code,
                        ],
                        [
                            // UBAH $poData MENJADI $detailPo di sini
                            'po_number' => $detailPo['number'] ?? $detailPo['no'] ?? 'PO-' . ($poData['id']),

                            'vendor_id' => $vendorId,

                            // Gunakan juga $detailPo untuk tanggal agar lebih akurat
                            'po_date' => isset($detailPo['transDate'])
                                ? (str_contains($detailPo['transDate'], '/')
                                    ? \Carbon\Carbon::createFromFormat('d/m/Y', $detailPo['transDate'])->format('Y-m-d')
                                    : \Carbon\Carbon::parse($detailPo['transDate'])->format('Y-m-d'))
                                : null,
                            'description' => $detailPo['description'] ?? null,
                        ]
                    );

                    // Sync items
                    if (isset($detailPo['detailItem']) && is_array($detailPo['detailItem'])) {
                        // Don't group by unitPrice because we want to preserve the actual lines in PO.
                        // We use the ID from Accurate directly.
                        $existingItemIds = [];
                        foreach ($detailPo['detailItem'] as $item) {
                            $itemNo = $item['item']['no'] ?? $item['itemNo'];
                            $unitPrice = $item['unitPrice'] ?? 0;
                            $itemName = $item['item']['name'] ?? $item['itemName'];
                            $quantity = $item['quantity'] ?? 0;
                            $detailId = $item['id'] ?? null;

                            // We use accurate_detail_id as unique identifier if available
                            if ($detailId) {
                                $poItem = PurchaseOrderItem::updateOrCreate(
                                    [
                                        'purchase_order_id' => $po->id,
                                        'accurate_detail_id' => $detailId,
                                    ],
                                    [
                                        'item_no' => $itemNo,
                                        'unit_price' => $unitPrice,
                                        'item_name' => $itemName,
                                        'quantity_ordered' => $quantity,
                                    ]
                                );
                            } else {
                                // Fallback for older Accurate structures without detail ID
                                $poItem = PurchaseOrderItem::updateOrCreate(
                                    [
                                        'purchase_order_id' => $po->id,
                                        'item_no' => $itemNo,
                                        'unit_price' => $unitPrice,
                                    ],
                                    [
                                        'item_name' => $itemName,
                                        'quantity_ordered' => $quantity,
                                    ]
                                );
                            }
                            $existingItemIds[] = $poItem->id;
                        }

                        // Delete items that are no longer in PO
                        PurchaseOrderItem::where('purchase_order_id', $po->id)
                            ->whereNotIn('id', $existingItemIds)
                            ->delete();
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
