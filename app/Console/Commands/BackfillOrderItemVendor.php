<?php

namespace App\Console\Commands;

use App\Models\DeviceInspection;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\PurchaseOrderItem;
use App\Models\SellPhone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOrderItemVendor extends Command
{
    protected $signature = 'orders:backfill-vendor {--chunk=500 : Number of items per chunk} {--force : Overwrite existing vendor values}';

    protected $description = 'Backfill vendor_name column in order_items table using inspection, serial numbers, buyback, and HPP matching';

    public function handle(): int
    {
        $chunkSize = (int)$this->option('chunk');
        $force = $this->option('force');

        $query = OrderItem::with('order');
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('vendor_name')
                  ->orWhere('vendor_name', '')
                  ->orWhere('vendor_name', '-');
            });
        }

        $totalCount = $query->count();
        $this->info("Ditemukan {$totalCount} order_items untuk diproses.");

        if ($totalCount === 0) {
            $this->info("Tidak ada data yang perlu di-backfill.");
            return 0;
        }

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $updatedCount = 0;
        $lastId = 0;

        while (true) {
            $items = OrderItem::with(['order', 'variant'])
                ->where('id', '>', $lastId)
                ->when(!$force, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNull('vendor_name')
                          ->orWhere('vendor_name', '')
                          ->orWhere('vendor_name', '-');
                    });
                })
                ->orderBy('id', 'asc')
                ->take($chunkSize)
                ->get();

            if ($items->isEmpty()) {
                break;
            }

            $lastId = $items->last()->id;

            // 1. Kumpulkan semua SN dan SKU di chunk ini
            $allSns = [];
            $allSkus = [];

            foreach ($items as $item) {
                if (!empty($item->serial_number)) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $allSns[] = $sn;
                    }
                }

                $sku = $item->variant?->item_no ?? $item->variant?->sku ?? $item->variant?->accurateData?->item_no ?? null;
                if ($sku) {
                    $allSkus[] = $sku;
                }
            }

            $allSns = array_unique($allSns);
            $allSkus = array_unique($allSkus);

            // 2. Pre-fetch DeviceInspection (Inbound PO asli per IMEI)
            $inspectionMap = [];
            if (!empty($allSns)) {
                $inspections = DeviceInspection::with('inspectable.purchaseOrder.vendor')
                    ->whereIn('imei', $allSns)
                    ->where('inspectable_type', PurchaseOrderItem::class)
                    ->latest()
                    ->get();

                foreach ($inspections as $ins) {
                    $vName = $ins->inspectable?->purchaseOrder?->vendor?->vendor_name;
                    if ($vName) {
                        $inspectionMap[$ins->imei] = $vName;
                    }
                }
            }

            // 3. Pre-fetch SellPhone (Customer Buyback)
            $sellPhoneMap = [];
            if (!empty($allSns)) {
                $sellPhones = SellPhone::with('user')
                    ->whereIn('imei', $allSns)
                    ->where('status', 'COMPLETED')
                    ->latest()
                    ->get();

                foreach ($sellPhones as $sp) {
                    $cName = $sp->bank_account_name ?: ($sp->user?->name ?? 'Customer');
                    $sellPhoneMap[$sp->imei] = 'Customer: ' . $cName;
                }
            }

            // 4. Pre-fetch ProductSerialNumber
            $psnMap = [];
            if (!empty($allSns)) {
                $psns = ProductSerialNumber::with('vendor')
                    ->whereIn('serial_number', $allSns)
                    ->get();

                foreach ($psns as $psn) {
                    if ($psn->vendor?->vendor_name) {
                        $psnMap[$psn->serial_number] = $psn->vendor->vendor_name;
                    }
                }
            }

            // 5. Pre-fetch PurchaseOrderItems by SKU untuk pencocokan HPP
            $poItemsBySku = [];
            if (!empty($allSkus)) {
                $poItems = PurchaseOrderItem::with('purchaseOrder.vendor')
                    ->whereIn('item_no', $allSkus)
                    ->get();

                foreach ($poItems as $poi) {
                    $vName = $poi->purchaseOrder?->vendor?->vendor_name;
                    if ($vName) {
                        $poItemsBySku[$poi->item_no][] = [
                            'unit_price' => (float)$poi->unit_price,
                            'vendor_name' => $vName,
                            'po_date' => $poi->purchaseOrder?->po_date,
                        ];
                    }
                }
            }

            // 6. Proses update setiap item
            DB::transaction(function () use (
                $items,
                $inspectionMap,
                $sellPhoneMap,
                $psnMap,
                $poItemsBySku,
                &$updatedCount
            ) {
                foreach ($items as $item) {
                    $cleanSns = [];
                    if (!empty($item->serial_number)) {
                        $cleanSns = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));
                    }

                    $variant = $item->variant;
                    $sku = $variant?->item_no ?? $variant?->sku ?? $variant?->accurateData?->item_no ?? null;
                    $masterVendor = $variant?->vendor_name ?? $variant?->accurateData?->vendor_name ?? null;

                    $resolvedVendor = null;

                    if (!empty($cleanSns)) {
                        $itemVendors = [];
                        foreach ($cleanSns as $sn) {
                            $snVendor = null;

                            // Prioritas 1: DeviceInspection Inbound PO (vendor asli saat fisik SN masuk)
                            if (isset($inspectionMap[$sn])) {
                                $snVendor = $inspectionMap[$sn];
                            }
                            // Prioritas 2: SellPhone (Customer)
                            elseif (isset($sellPhoneMap[$sn])) {
                                $snVendor = $sellPhoneMap[$sn];
                            }
                            // Prioritas 3: ProductSerialNumber
                            elseif (isset($psnMap[$sn])) {
                                $snVendor = $psnMap[$sn];
                            }
                            // Prioritas 4: Pencocokan HPP ke riwayat harga beli PO
                            elseif ($sku && isset($poItemsBySku[$sku]) && (float)$item->unit_hpp > 0) {
                                foreach ($poItemsBySku[$sku] as $poData) {
                                    if (abs($poData['unit_price'] - (float)$item->unit_hpp) < 1.0) {
                                        $snVendor = $poData['vendor_name'];
                                        break;
                                    }
                                }
                            }

                            if ($snVendor) {
                                $itemVendors[] = $snVendor;
                            }
                        }

                        $itemVendors = array_unique(array_filter($itemVendors));
                        if (!empty($itemVendors)) {
                            $resolvedVendor = implode(', ', $itemVendors);
                        }
                    } else {
                        // Non-SN: coba cocokkan HPP ke riwayat PO
                        if ($sku && isset($poItemsBySku[$sku]) && (float)$item->unit_hpp > 0) {
                            foreach ($poItemsBySku[$sku] as $poData) {
                                if (abs($poData['unit_price'] - (float)$item->unit_hpp) < 1.0) {
                                    $resolvedVendor = $poData['vendor_name'];
                                    break;
                                }
                            }
                        }
                    }

                    // Fallback ke master vendor
                    if (empty($resolvedVendor)) {
                        $resolvedVendor = $masterVendor;
                    }

                    if (!empty($resolvedVendor)) {
                        $item->update(['vendor_name' => $resolvedVendor]);
                        $updatedCount++;
                    }
                }
            });

            $bar->advance($items->count());
        }

        $bar->finish();
        $this->newLine();
        $this->info("Backfill vendor selesai! Sebanyak {$updatedCount} baris order_items berhasil diperbarui.");

        return 0;
    }
}

