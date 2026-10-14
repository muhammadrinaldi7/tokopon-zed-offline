<?php

namespace App\Services;

use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\SellPhone;
use Illuminate\Database\Eloquent\Model;

class OrderHppResolverService
{
    /**
     * Resolve unit_hpp and total_hpp for a single order item.
     *
     * @param Model|null $variant ProductAccurate or ProductVariant
     * @param array|string|null $serialNumbers Array of SN strings or comma-separated string
     * @param int|null $businessUnitId Business Unit ID of the order
     * @param float|int $qty Quantity of item (can be negative for returns)
     * @param bool $isReturn Whether this item is considered a return
     * @return array ['unit_hpp' => float, 'total_hpp' => float]
     */
    public static function resolve(
        ?Model $variant,
        $serialNumbers = [],
        ?int $businessUnitId = null,
        $qty = 1,
        bool $isReturn = false
    ): array {
        $qty = (float)$qty;
        if ($qty < 0) {
            $isReturn = true;
        }

        // Parse serial numbers
        $cleanSns = [];
        if (is_array($serialNumbers)) {
            $cleanSns = array_values(array_filter(array_map('trim', $serialNumbers)));
        } elseif (is_string($serialNumbers) && !empty($serialNumbers)) {
            $cleanSns = array_values(array_filter(array_map('trim', explode(',', $serialNumbers))));
        }

        // 1. SN-based item
        if (!empty($cleanSns)) {
            $totalHpp = 0.0;
            $countSns = count($cleanSns);

            foreach ($cleanSns as $sn) {
                $snHpp = 0.0;

                // Priority 1: Check if this SN was a Buyback in SellPhone
                $sellPhone = SellPhone::where('imei', $sn)
                    ->where('status', 'COMPLETED')
                    ->where('appraised_value', '>', 0)
                    ->when($businessUnitId, function ($q) use ($businessUnitId) {
                        $q->where(function ($sub) use ($businessUnitId) {
                            $sub->where('business_unit_id', $businessUnitId);
                            // Fallback to any BU if order is GSK Second (BU ID: 2)
                            if ($businessUnitId == 2) {
                                $sub->orWhereNotNull('id');
                            }
                        });
                    })
                    ->latest()
                    ->first();

                if ($sellPhone && (float)$sellPhone->appraised_value > 0) {
                    $snHpp = (float)$sellPhone->appraised_value;
                } else {
                    // Priority 2: Check in product_serial_numbers for the same BU
                    $psn = null;
                    if ($businessUnitId) {
                        $psn = ProductSerialNumber::where('serial_number', $sn)
                            ->where('business_unit_id', $businessUnitId)
                            ->first();
                    }

                    // Priority 3: Cross-BU Fallback
                    if (!$psn || (float)($psn->hpp ?? 0) <= 0) {
                        $psn = ProductSerialNumber::where('serial_number', $sn)->first();
                    }

                    if ($psn && (float)($psn->hpp ?? 0) > 0) {
                        $snHpp = (float)$psn->hpp;
                    }
                }

                // Priority 4: Fallback to ProductAccurate base_cost -> buy_price
                if ($snHpp <= 0) {
                    $snHpp = self::extractVariantBaseCost($variant);
                }

                $totalHpp += $snHpp;
            }

            $unitHpp = $countSns > 0 ? ($totalHpp / $countSns) : $totalHpp;

            if ($isReturn && $totalHpp > 0) {
                $totalHpp = -$totalHpp;
            }

            return [
                'unit_hpp' => round($unitHpp, 2),
                'total_hpp' => round($totalHpp, 2),
            ];
        }

        // 2. Non-SN item (Accessories, etc.)
        $unitHpp = self::extractVariantBaseCost($variant);
        $totalHpp = $unitHpp * $qty;

        if ($isReturn && $totalHpp > 0) {
            $totalHpp = -$totalHpp;
        }

        return [
            'unit_hpp' => round($unitHpp, 2),
            'total_hpp' => round($totalHpp, 2),
        ];
    }

    /**
     * Extract base_cost from variant, with fallback to buy_price if base_cost is 0.
     */
    public static function extractVariantBaseCost(?Model $variant): float
    {
        if (!$variant) {
            return 0.0;
        }

        // Direct base_cost
        $baseCost = (float)($variant->base_cost ?? $variant->accurateData?->base_cost ?? 0);
        if ($baseCost > 0) {
            return $baseCost;
        }

        // Fallback to buy_price (master accurate purchase price)
        $buyPrice = (float)($variant->buy_price ?? $variant->accurateData?->buy_price ?? 0);
        if ($buyPrice > 0) {
            return $buyPrice;
        }

        return 0.0;
    }
}
