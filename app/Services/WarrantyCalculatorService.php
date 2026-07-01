<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WarrantyPolicy;

class WarrantyCalculatorService
{
    /**
     * Menghitung dan mencari Policy Garansi yang berlaku untuk OrderItem tertentu.
     * Mengembalikan collection/array of WarrantyPolicy yang harus digenerate.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function calculateWarranties(Order $order, $orderItem)
    {
        $policiesToApply = collect();
        $brandId = $this->extractBrandId($orderItem);
        $businessUnitId = $order->business_unit_id;

        // Identifikasi Status Kondisi Barang (Baru/Bekas)
        $isNew = $orderItem->product_variant_type === \App\Models\ProductVariant::class;

        // Identifikasi Status Harga (Normal/Diskon Kasir atau Internal Promo)
        $hasManualDiscount = (float)$orderItem->discount_amount > 0;

        $hasInternalPromo = false;
        foreach ($orderItem->promos as $promo) {
            if (strtolower(trim($promo->category)) === 'internal') {
                $hasInternalPromo = true;
                break;
            }
        }

        $isDiscounted = $hasManualDiscount || $hasInternalPromo;

        // 1. EVALUASI GARANSI UTAMA (MAIN WARRANTY)
        $targetType = $isDiscounted ? 'store_discount' : 'store_normal';
        $mainPolicy = $this->findMainWarrantyPolicy($businessUnitId, $targetType, $brandId);

        if ($mainPolicy) {
            $policiesToApply->push($mainPolicy);
        }

        // 2. EVALUASI ASURANSI (ADDON WARRANTY)
        $addonPolicies = WarrantyPolicy::where('type', 'addon_warranty')
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->get();

        foreach ($addonPolicies as $addonPolicy) {
            if (!empty($addonPolicy->addon_trigger_keywords) && $addonPolicy->addon_trigger_keywords !== '[]') {
                if ($this->hasInsuranceQuota($order, $addonPolicy)) {
                    $policiesToApply->push($addonPolicy);
                }
            }
        }

        return $policiesToApply;
    }

    /**
     * Cari Garansi Utama yang paling cocok berdasarkan:
     * - Business Unit
     * - Tipe (Diskon / Normal)
     * - Filter Brand (Prioritaskan spesifik brand, lalu fallback ke all_brands)
     */
    private function findMainWarrantyPolicy($businessUnitId, $type, $brandId)
    {
        $policies = WarrantyPolicy::where('type', $type)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->get();

        // Cari yang spesifik brand dulu
        foreach ($policies as $policy) {
            if ($policy->brand_rule === 'include') {
                $brandList = is_array($policy->brand_list) ? $policy->brand_list : json_decode($policy->brand_list, true) ?? [];
                if (in_array($brandId, $brandList)) {
                    return $policy;
                }
            }
        }

        // Jika tidak ketemu yang spesifik, cari yang all_brands
        foreach ($policies as $policy) {
            if ($policy->brand_rule === 'all_brands') {
                return $policy;
            }
        }

        return null;
    }

    /**
     * Cek apakah ada Asuransi global di dalam keranjang (Fallback)
     */
    private function hasInsurance(Order $order, $item)
    {
        // Pertama, cek nama item itu sendiri
        $name = strtolower($item->product_name ?? '');
        if (str_contains($name, 'asuransi')) return true;

        // Kedua, cek semua item di order
        foreach ($order->items as $oItem) {
            $oName = strtolower($oItem->product_name ?? '');
            if (str_contains($oName, 'asuransi')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah order memiliki sisa kuota untuk item asuransi ini (Berdasarkan Qty beli vs Qty terpakai)
     */
    private function hasInsuranceQuota(Order $order, WarrantyPolicy $addonPolicy)
    {
        $insuranceProductIds = $addonPolicy->addon_trigger_keywords;
        $insuranceProductIds = is_array($insuranceProductIds) ? $insuranceProductIds : (json_decode($insuranceProductIds, true) ?? []);
        if (empty($insuranceProductIds)) return false;

        $totalPurchasedQty = 0;

        foreach ($order->items as $oItem) {
            $accurateId = null;

            // Ambil ProductAccurate ID berdasarkan Polymorphic Variant
            if ($oItem->variant) {
                if ($oItem->product_variant_type === \App\Models\ProductVariant::class) {
                    $accurateId = $oItem->variant->product_accurate_id;
                } elseif ($oItem->product_variant_type === \App\Models\SecondProductVariant::class) {
                    $accurateId = $oItem->variant->product_accurate_id;
                } elseif ($oItem->product_variant_type === \App\Models\ProductAccurate::class) {
                    $accurateId = $oItem->variant->id; // Variant IS the ProductAccurate itself
                }
            }

            // Jika item ini adalah asuransi yang terdaftar di Policy, tambahkan kuantitasnya
            if ($accurateId && in_array((string)$accurateId, array_map('strval', $insuranceProductIds))) {
                $totalPurchasedQty += (int)$oItem->quantity;
            }
        }

        // Jika tidak ada asuransi ini yang dibeli di nota, return false
        if ($totalPurchasedQty <= 0) return false;

        // Hitung berapa kali asuransi (policy) ini sudah diaktifkan untuk Order ini
        $usedQty = \App\Models\Warranty::where('warranty_policy_id', $addonPolicy->id)
            ->whereHas('orderItem', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })
            ->count();

        // Asuransi diberikan JIKA kuantitas yang dibeli LEBIH BESAR dari yang sudah terpakai
        return $totalPurchasedQty > $usedQty;
    }

    /**
     * Helper untuk extract Brand ID dari Polymorphic variant
     */
    private function extractBrandId($orderItem)
    {
        if (!$orderItem || !$orderItem->variant) return null;

        $variant = $orderItem->variant;
        $variantClass = get_class($variant);

        if ($variantClass === \App\Models\ProductAccurate::class) {
            $brand = \App\Models\Brand::where('name', $variant->brandName)->first();
            return $brand->id ?? null;
        } elseif ($variantClass === \App\Models\SecondProductVariant::class) {
            return $variant->device->brand_id ?? null;
        } elseif ($variantClass === \App\Models\ProductVariant::class) {
            return $variant->product->brand_id ?? null;
        }

        return null;
    }
}
