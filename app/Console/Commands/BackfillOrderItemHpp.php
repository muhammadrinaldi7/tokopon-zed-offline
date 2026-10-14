<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\SellPhone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOrderItemHpp extends Command
{
    protected $signature = 'orders:backfill-hpp {--chunk=500 : Number of items per chunk} {--force : Overwrite existing HPP values} {--reset : Reset all unit_hpp and total_hpp values to 0}';

    protected $description = 'Backfill unit_hpp and total_hpp columns in order_items table';

    public function handle(): int
    {
        if ($this->option('reset')) {
            $force = $this->option('force');
            if (!$force && !$this->confirm('Apakah Anda yakin ingin me-reset (mengosongkan ke 0) nilai unit_hpp dan total_hpp di seluruh order_items?')) {
                $this->info("Operasi dibatalkan.");
                return 0;
            }

            $affected = DB::table('order_items')->update([
                'unit_hpp' => 0,
                'total_hpp' => 0,
            ]);

            $this->info("Berhasil di-rollback! Sebanyak " . number_format($affected) . " records order_items telah di-reset kembali ke 0.");
            return 0;
        }

        $chunkSize = (int)$this->option('chunk');
        $force = $this->option('force');

        $query = OrderItem::with('order');
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('unit_hpp')
                  ->orWhereNull('total_hpp')
                  ->orWhere(function ($sq) {
                      $sq->where('unit_hpp', 0)->where('total_hpp', 0);
                  });
            });
        }

        $totalCount = $query->count();
        $this->info("Found {$totalCount} order_items to process.");

        if ($totalCount === 0) {
            $this->info("Nothing to backfill.");
            return 0;
        }

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $updatedCount = 0;
        $withHppCount = 0;
        $zeroHppCount = 0;

        // Process by ID in chunks
        $lastId = 0;
        while (true) {
            $items = OrderItem::with('order')
                ->where('id', '>', $lastId)
                ->when(!$force, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNull('unit_hpp')
                          ->orWhereNull('total_hpp')
                          ->orWhere(function ($ssq) {
                              $ssq->where('unit_hpp', 0)->where('total_hpp', 0);
                          });
                    });
                })
                ->orderBy('id', 'asc')
                ->take($chunkSize)
                ->get();

            if ($items->isEmpty()) {
                break;
            }

            $lastId = $items->last()->id;

            // 1. Collect all SNs and Variant IDs in this chunk
            $allSns = [];
            $accurateIds = [];
            $variantIds = [];

            foreach ($items as $item) {
                if (!empty($item->serial_number)) {
                    $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                    foreach ($sns as $sn) {
                        $allSns[] = $sn;
                    }
                }

                if ($item->product_variant_type === 'App\Models\ProductAccurate' || $item->product_variant_type === ProductAccurate::class) {
                    $accurateIds[] = $item->product_variant_id;
                } elseif ($item->product_variant_type === 'App\Models\ProductVariant' || $item->product_variant_type === ProductVariant::class) {
                    $variantIds[] = $item->product_variant_id;
                }
            }

            $allSns = array_unique($allSns);
            $accurateIds = array_unique($accurateIds);
            $variantIds = array_unique($variantIds);

            // 2. Pre-fetch SellPhones
            $sellPhoneMap = [];
            if (!empty($allSns)) {
                $sellPhones = SellPhone::whereIn('imei', $allSns)
                    ->where('status', 'COMPLETED')
                    ->where('appraised_value', '>', 0)
                    ->get(['imei', 'business_unit_id', 'appraised_value']);

                foreach ($sellPhones as $sp) {
                    $sellPhoneMap[$sp->imei . '_' . $sp->business_unit_id] = (float)$sp->appraised_value;
                    if (!isset($sellPhoneMap[$sp->imei])) {
                        $sellPhoneMap[$sp->imei] = (float)$sp->appraised_value;
                    }
                }
            }

            // 3. Pre-fetch ProductSerialNumbers
            $snMap = [];
            if (!empty($allSns)) {
                $psns = ProductSerialNumber::whereIn('serial_number', $allSns)
                    ->where('hpp', '>', 0)
                    ->get(['serial_number', 'business_unit_id', 'hpp']);

                foreach ($psns as $psn) {
                    $snMap[$psn->serial_number . '_' . $psn->business_unit_id] = (float)$psn->hpp;
                    if (!isset($snMap[$psn->serial_number])) {
                        $snMap[$psn->serial_number] = (float)$psn->hpp;
                    }
                }
            }

            // 4. Pre-fetch ProductAccurates
            $accurateCostMap = [];
            if (!empty($accurateIds)) {
                $accurates = ProductAccurate::whereIn('id', $accurateIds)
                    ->get(['id', 'base_cost', 'buy_price']);

                foreach ($accurates as $acc) {
                    $cost = (float)$acc->base_cost;
                    if ($cost <= 0) {
                        $cost = (float)$acc->buy_price;
                    }
                    $accurateCostMap[$acc->id] = $cost;
                }
            }

            // 5. Pre-fetch ProductVariants
            $variantCostMap = [];
            if (!empty($variantIds)) {
                $variants = ProductVariant::with('accurateData:id,base_cost,buy_price')
                    ->whereIn('id', $variantIds)
                    ->get(['id', 'product_accurate_id']);

                foreach ($variants as $pv) {
                    $cost = 0.0;
                    if ($pv->accurateData) {
                        $cost = (float)$pv->accurateData->base_cost;
                        if ($cost <= 0) {
                            $cost = (float)$pv->accurateData->buy_price;
                        }
                    }
                    $variantCostMap[$pv->id] = $cost;
                }
            }

            // 6. Process each item in the chunk
            DB::transaction(function () use (
                $items,
                $sellPhoneMap,
                $snMap,
                $accurateCostMap,
                $variantCostMap,
                &$updatedCount,
                &$withHppCount,
                &$zeroHppCount
            ) {
                foreach ($items as $item) {
                    $order = $item->order;
                    $orderBuId = $order?->business_unit_id;
                    $qty = (float)$item->qty;

                    // Determine if return
                    $isReturn = false;
                    if ($qty < 0 || (float)$item->subtotal < 0) {
                        $isReturn = true;
                    } elseif ($order) {
                        if (str_starts_with($order->order_number ?? '', 'RET-') ||
                            !empty($order->shipping_address_snapshot['is_warranty_return']) ||
                            (!empty($order->accurate_invoice_no) && str_starts_with($order->accurate_invoice_no, 'SRT')) ||
                            (float)($order->total_amount ?? 0) < 0
                        ) {
                            $isReturn = true;
                        }
                    }

                    // Variant base cost with buy_price fallback
                    $variantFallbackCost = 0.0;
                    if ($item->product_variant_type === 'App\Models\ProductAccurate' || $item->product_variant_type === ProductAccurate::class) {
                        $variantFallbackCost = $accurateCostMap[$item->product_variant_id] ?? 0.0;
                    } elseif ($item->product_variant_type === 'App\Models\ProductVariant' || $item->product_variant_type === ProductVariant::class) {
                        $variantFallbackCost = $variantCostMap[$item->product_variant_id] ?? 0.0;
                    }

                    $cleanSns = [];
                    if (!empty($item->serial_number)) {
                        $cleanSns = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));
                    }

                    if (!empty($cleanSns)) {
                        $lineHpp = 0.0;
                        foreach ($cleanSns as $sn) {
                            $snHpp = 0.0;

                            // 1. SellPhone Buyback
                            $spCost = $sellPhoneMap[$sn . '_' . $orderBuId] ?? ($orderBuId == 2 ? ($sellPhoneMap[$sn] ?? 0.0) : 0.0);
                            if ($spCost > 0) {
                                $snHpp = $spCost;
                            } else {
                                // 2. ProductSerialNumber (same BU)
                                $psnCost = $snMap[$sn . '_' . $orderBuId] ?? 0.0;
                                if ($psnCost <= 0) {
                                    // 3. Cross-BU fallback
                                    $psnCost = $snMap[$sn] ?? 0.0;
                                }
                                $snHpp = $psnCost;
                            }

                            // 4. Fallback to variant base_cost / buy_price
                            if ($snHpp <= 0) {
                                $snHpp = $variantFallbackCost;
                            }

                            $lineHpp += $snHpp;
                        }

                        $count = count($cleanSns);
                        $unitHpp = $count > 0 ? ($lineHpp / $count) : $lineHpp;
                        $totalHpp = $lineHpp;
                    } else {
                        $unitHpp = $variantFallbackCost;
                        $totalHpp = $unitHpp * $qty;
                    }

                    if ($isReturn && $totalHpp > 0) {
                        $totalHpp = -$totalHpp;
                    }

                    $unitHpp = round($unitHpp, 2);
                    $totalHpp = round($totalHpp, 2);

                    DB::table('order_items')
                        ->where('id', $item->id)
                        ->update([
                            'unit_hpp' => $unitHpp,
                            'total_hpp' => $totalHpp,
                        ]);

                    $updatedCount++;
                    if ($totalHpp != 0) {
                        $withHppCount++;
                    } else {
                        $zeroHppCount++;
                    }
                }
            });

            $bar->advance($items->count());
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== BACKFILL RESULT ===");
        $this->info("Total processed : " . number_format($updatedCount));
        $this->info("Items with HPP > 0 : " . number_format($withHppCount));
        $this->info("Items with HPP = 0 : " . number_format($zeroHppCount));

        return 0;
    }
}
