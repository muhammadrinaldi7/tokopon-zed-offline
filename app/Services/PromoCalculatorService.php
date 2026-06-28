<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Promo;

class PromoCalculatorService
{
    /**
     * Get eligible promos for a cart and branch
     */
    public function getEligiblePromos(array $cart, int $branchId, ?int $businessUnitId = null)
    {
        $promos = Promo::with(['skus', 'bundleSkus.variant.product', 'branches'])
            ->where('is_active', true)
            ->where(function ($q) use ($businessUnitId) {
                $q->whereNull('business_unit_id');
                if ($businessUnitId) {
                    $q->orWhere('business_unit_id', $businessUnitId);
                }
            })
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                // Check quota: if quota is set, it must be greater than used_quota
                $q->whereNull('quota')->orWhereColumn('quota', '>', 'used_quota');
            })
            ->where(function ($q) use ($branchId) {
                $q->whereDoesntHave('branches')
                  ->orWhereHas('branches', function ($bq) use ($branchId) {
                      $bq->where('branches.id', $branchId);
                  });
            })
            ->get();

        $eligiblePromos = [];
        foreach ($promos as $promo) {
            if ($this->isPromoEligible($promo, $cart)) {
                $eligiblePromos[] = $promo;
            }
        }
        return collect($eligiblePromos);
    }

    /**
     * Check if a promo is eligible based on the cart (Main Product only)
     */
    public function isPromoEligible(Promo $promo, array $cart)
    {
        $eligible = $this->calculateEligibleCart($promo, $cart, false);

        if ($promo->min_qty && $eligible['qty'] < $promo->min_qty) return false;
        if ($promo->min_transaction_amount && $eligible['amount'] < $promo->min_transaction_amount) return false;

        if (!$promo->apply_to_all_items && $eligible['qty'] == 0) return false;
        return true;
    }

    /**
     * Calculate eligible quantity and amount from cart for a promo
     */
    public function calculateEligibleCart(Promo $promo, array $cart, bool $forBundle = false, int $multiplier = 1)
    {
        $eligibleQty = 0;
        $eligibleAmount = 0;
        
        if ($promo->apply_to_all_items && !$forBundle) {
            $eligibleAmount = collect($cart)->sum(fn($item) => (int)$item['price'] * (int)$item['qty']);
            $eligibleQty = array_sum(array_column($cart, 'qty'));
        } else {
            $promoSkus = $forBundle ? $promo->bundleSkus->pluck('sku')->toArray() : $promo->skus->pluck('sku')->toArray();

            foreach ($cart as $item) {
                if (in_array($item['sku'], $promoSkus)) {
                    $eligibleQty += (int)$item['qty'];
                    $eligibleAmount += ((int)$item['qty'] * (float)$item['price']);
                }
            }
        }

        if ($forBundle && $promo->bundle_max_qty && $eligibleQty > ($promo->bundle_max_qty * $multiplier)) {
            $avgPrice = $eligibleQty > 0 ? ($eligibleAmount / $eligibleQty) : 0;
            $eligibleQty = $promo->bundle_max_qty * $multiplier;
            $eligibleAmount = $eligibleQty * $avgPrice;
        }

        return ['qty' => $eligibleQty, 'amount' => $eligibleAmount];
    }

    /**
     * Apply promos to cart array (mutates cart array by reference)
     * Returns true if successful, false if there is a conflict.
     */
    public function applyPromosToCart(array &$cart, array $selectedPromoIds)
    {
        // 1. Reset all promo discounts in cart
        foreach ($cart as $key => $item) {
            $cart[$key]['promo_discount'] = 0;
            $cart[$key]['promo_discounts'] = [];
        }

        if (empty($selectedPromoIds)) return true;

        $promos = Promo::with(['skus', 'bundleSkus'])->whereIn('id', $selectedPromoIds)->get();

        // Check combinable
        $hasNonCombinable = $promos->where('is_combinable', false)->count() > 0;
        if ($hasNonCombinable && count($selectedPromoIds) > 1) {
            return false; // Indicates conflict
        }

        foreach ($promos as $promo) {
            // 2. Kalkulasi Diskon Utama
            $eligibleMain = $this->calculateEligibleCart($promo, $cart, false);
            if ($eligibleMain['qty'] > 0) {
                $multiplier = 1;
                if ($promo->is_multiply) {
                    $divisor = $promo->min_qty > 0 ? $promo->min_qty : 1;
                    $multiplier = floor($eligibleMain['qty'] / $divisor);
                }

                $mainDiscountValue = $promo->discount_type === 'fixed' 
                    ? ($promo->discount_value * $multiplier)
                    : ($eligibleMain['amount'] * ($promo->discount_value / 100));
                
                if ($promo->max_discount) {
                    $mainDiscountValue = min($mainDiscountValue, $promo->max_discount * $multiplier);
                }

                // Distribusikan
                $mainSkus = $promo->apply_to_all_items ? array_column($cart, 'sku') : $promo->skus->pluck('sku')->toArray();
                foreach ($cart as $key => $item) {
                    if (in_array($item['sku'], $mainSkus)) {
                        $itemAmount = $item['qty'] * $item['price'];
                        $proportion = $eligibleMain['amount'] > 0 ? ($itemAmount / $eligibleMain['amount']) : 0;
                        $itemDiscount = round($mainDiscountValue * $proportion);
                        $cart[$key]['promo_discount'] += $itemDiscount;
                        $cart[$key]['promo_discounts'][$promo->id] = ($cart[$key]['promo_discounts'][$promo->id] ?? 0) + $itemDiscount;
                    }
                }
            }

            // 3. Kalkulasi Diskon Bundling
            if ($promo->is_bundle) {
                $multiplier = 1;
                if ($promo->is_multiply) {
                    $divisor = $promo->min_qty > 0 ? $promo->min_qty : 1;
                    $multiplier = floor($eligibleMain['qty'] / $divisor);
                }

                $eligibleBundle = $this->calculateEligibleCart($promo, $cart, true, $multiplier);
                
                if ($eligibleBundle['qty'] > 0) {
                    $applicableQty = $eligibleBundle['qty'];
                    $totalQtyApplied = 0;
                    
                    $bundleSkusInfo = $promo->bundleSkus->keyBy('sku');

                    foreach ($cart as $key => $item) {
                        if ($bundleSkusInfo->has($item['sku']) && $totalQtyApplied < $applicableQty) {
                            $bSku = $bundleSkusInfo->get($item['sku']);
                            
                            $type = $bSku->discount_value > 0 ? $bSku->discount_type : $promo->bundle_discount_type;
                            $val = $bSku->discount_value > 0 ? $bSku->discount_value : $promo->bundle_discount_value;
                            $max = $bSku->discount_value > 0 ? $bSku->max_discount : $promo->bundle_max_discount;
                            
                            if (!$val) continue;

                            $qtyToDiscount = min($item['qty'], $applicableQty - $totalQtyApplied);

                            $itemDiscount = $type === 'fixed' 
                                ? ($val * $qtyToDiscount) 
                                : (($item['price'] * $qtyToDiscount) * ($val / 100));

                            if ($max && $max > 0) {
                                $itemDiscount = min($itemDiscount, $max * $multiplier);
                            }

                            $cart[$key]['promo_discount'] += $itemDiscount;
                            $cart[$key]['promo_discounts'][$promo->id] = ($cart[$key]['promo_discounts'][$promo->id] ?? 0) + $itemDiscount;
                            
                            $totalQtyApplied += $qtyToDiscount;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Record promos to order pivot table and increment used quota
     * Assumes applyPromosToCart was already called and cart has promo_discount
     */
    public function recordPromosToOrder(Order $order, array $cart, array $selectedPromoIds)
    {
        if (empty($selectedPromoIds)) return;

        // Hitung total discount per promo dari cart yang SUDAH dikalkulasi
        $promoTotals = [];
        foreach ($cart as $item) {
            foreach ($item['promo_discounts'] ?? [] as $pid => $disc) {
                $promoTotals[$pid] = ($promoTotals[$pid] ?? 0) + $disc;
            }
        }

        // Attach to pivot
        foreach ($promoTotals as $promoId => $discountApplied) {
            if ($discountApplied > 0) {
                $order->promos()->attach($promoId, ['discount_applied' => $discountApplied]);
            }
        }

        // Increment quota
        Promo::whereIn('id', $selectedPromoIds)->increment('used_quota');
    }
}
