<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WarrantyPolicy;
use App\Models\Brand;
use App\Models\ProductAccurate;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;

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
        $businessUnitId = $order->business_unit_id;
        $brandId = $this->extractBrandId($orderItem, $businessUnitId);

        // Identifikasi Status Kondisi Barang (Baru/Bekas)
        $variant = $orderItem->variant; 
        $isNew = $variant && strtolower($variant->database_source ?? '') !== 'second';

        // Identifikasi Status Harga (Normal/Diskon Kasir atau Internal Promo)
        $hasManualDiscount = (float)($orderItem->discount_amount ?? 0) > 0;

        $hasInternalPromo = false;
        if ($orderItem->relationLoaded('promos') || method_exists($orderItem, 'promos')) {
            foreach ($orderItem->promos as $promo) {
                if (strtolower(trim($promo->category ?? '')) === 'internal') {
                    $hasInternalPromo = true;
                    break;
                }
            }
        }

        $isDiscounted = $hasManualDiscount || $hasInternalPromo;

        // 1. EVALUASI GARANSI UTAMA (MAIN WARRANTY)
        $targetType = $isDiscounted ? 'store_discount' : 'store_normal';
        $mainPolicy = $this->findMainWarrantyPolicy($businessUnitId, $targetType, $brandId, $orderItem);

        // Fallback: Jika barang diskon tetapi toko TIDAK membuat kebijakan 'store_discount',
        // otomatis gunakan kebijakan 'store_normal' agar pelanggan tidak kehilangan hak garansi.
        if (!$mainPolicy && $targetType === 'store_discount') {
            $mainPolicy = $this->findMainWarrantyPolicy($businessUnitId, 'store_normal', $brandId, $orderItem);
        }

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
     * - Filter Brand (Include / All Brands)
     * - Spesifikasi Produk (misal iPhone 90 hari vs Apple Ecosystem 10 hari)
     */
    private function findMainWarrantyPolicy($businessUnitId, $type, $brandId, $orderItem = null)
    {
        $policies = WarrantyPolicy::where('type', $type)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->get();

        if ($policies->isEmpty()) {
            return null;
        }

        $matchedPolicies = collect();

        // 1. Cari yang spesifik Brand (brand_rule === 'include')
        foreach ($policies as $policy) {
            if ($policy->brand_rule === 'include') {
                $brandList = is_array($policy->brand_list) ? $policy->brand_list : (json_decode($policy->brand_list, true) ?? []);
                $brandListStr = array_map('strval', $brandList);

                // Cek pencocokan langsung berdasarkan Brand ID
                $isMatched = $brandId && in_array((string)$brandId, $brandListStr);

                // Fallback pencocokan Nama Brand jika ID berbeda antar Business Unit
                if (!$isMatched && $brandId) {
                    $detectedBrand = Brand::find($brandId);
                    if ($detectedBrand) {
                        $detectedName = strtolower(trim($detectedBrand->name));
                        $policyBrandNames = Brand::whereIn('id', $brandList)->pluck('name')->map(fn($n) => strtolower(trim($n)))->toArray();

                        if (in_array($detectedName, $policyBrandNames)) {
                            $isMatched = true;
                        } elseif (in_array($detectedName, ['apple', 'iphone']) && (in_array('apple', $policyBrandNames) || in_array('iphone', $policyBrandNames))) {
                            $isMatched = true;
                        }
                    }
                }

                if ($isMatched) {
                    $matchedPolicies->push($policy);
                }
            }
        }

        if ($matchedPolicies->isNotEmpty()) {
            if ($matchedPolicies->count() === 1) {
                return $matchedPolicies->first();
            }

            // Jika lebih dari 1 policy cocok (contoh: Policy iPhone vs Policy Apple Ecosystem):
            $productName = strtolower($orderItem?->product_name ?? '');
            $variant = $orderItem?->variant;
            $categoryName = strtolower($variant->categoryName ?? '');

            $isIphone = str_contains($productName, 'iphone') 
                || ((str_contains($categoryName, 'handphone') || str_contains($categoryName, 'hp')) && str_contains($productName, 'hp 2nd'));

            if ($isIphone) {
                // Utamakan policy yang namanya khusus IPHONE
                $iphonePolicy = $matchedPolicies->first(function ($p) {
                    return str_contains(strtolower($p->name), 'iphone');
                });
                if ($iphonePolicy) return $iphonePolicy;
            } else {
                // Untuk non-iPhone (Apple Watch, iPad, MacBook, AirPods, Android), utamakan policy ECO/ANDROID
                $ecoPolicy = $matchedPolicies->first(function ($p) {
                    $name = strtolower($p->name);
                    return str_contains($name, 'eco') || str_contains($name, 'android');
                });
                if ($ecoPolicy) return $ecoPolicy;
            }

            // Fallback: Pilih policy yang paling spesifik (jumlah brand_list paling sedikit)
            return $matchedPolicies->sortBy(function ($p) {
                $bList = is_array($p->brand_list) ? $p->brand_list : (json_decode($p->brand_list, true) ?? []);
                return count($bList);
            })->first();
        }

        // 2. Jika tidak ada yang cocok spesifik brand, cari yang 'all_brands'
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
        $name = strtolower($item->product_name ?? '');
        if (str_contains($name, 'asuransi')) return true;

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

            if ($oItem->variant) {
                $accurateId = $oItem->variant->id; // Variant IS the ProductAccurate itself
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
     * Ekstrak Brand ID yang valid untuk Business Unit yang bersangkutan
     */
    public function extractBrandId($orderItem, $businessUnitId = null)
    {
        if (!$orderItem) return null;

        $brandName = null;
        $variant = $orderItem->variant;

        // 1. Ekstrak nama brand dari polymorphic variant
        if ($variant instanceof \App\Models\ProductAccurate) {
            $brandName = $variant->brandName;
        } elseif ($variant instanceof \App\Models\ProductVariant) {
            $brandName = $variant->product?->brand?->name;
            $directBrandId = $variant->product?->brand_id;
            if ($directBrandId && empty($brandName)) {
                $brandName = Brand::find($directBrandId)?->name;
            }
        } elseif ($variant instanceof \App\Models\SecondProductVariant) {
            $brandName = $variant->brand?->name ?? $variant->brandName ?? $variant->product?->brand?->name;
        }

        // Fallback: deteksi brand dari nama produk jika belum ditemukan
        if (empty($brandName)) {
            $productName = strtolower($orderItem->product_name ?? '');
            if (str_contains($productName, 'iphone') || str_contains($productName, 'apple') || str_contains($productName, 'ipad') || str_contains($productName, 'macbook') || str_contains($productName, 'airpods') || str_contains($productName, 'iwatch') || str_contains($productName, 'watch')) {
                $brandName = 'Apple';
            } elseif (str_contains($productName, 'samsung') || str_contains($productName, 'galaxy')) {
                $brandName = 'Samsung';
            } elseif (str_contains($productName, 'xiaomi') || str_contains($productName, 'redmi') || str_contains($productName, 'mi ')) {
                $brandName = 'Xiaomi';
            } elseif (str_contains($productName, 'oppo')) {
                $brandName = 'Oppo';
            } elseif (str_contains($productName, 'vivo')) {
                $brandName = 'Vivo';
            } elseif (str_contains($productName, 'realme')) {
                $brandName = 'Realme';
            } elseif (str_contains($productName, 'infinix')) {
                $brandName = 'Infinix';
            } elseif (str_contains($productName, 'tecno')) {
                $brandName = 'Tecno';
            } elseif (str_contains($productName, 'iqoo')) {
                $brandName = 'Iqoo';
            }
        }

        if (empty($brandName)) {
            return null;
        }

        $cleanBrandName = strtolower(trim($brandName));

        // 2. Cari Brand ID di business unit yang bersangkutan (prioritas utama)
        $brandQuery = Brand::query();
        if ($businessUnitId) {
            $brandQuery->where('business_unit_id', $businessUnitId);
        }

        $buBrand = (clone $brandQuery)->whereRaw('LOWER(name) = ?', [$cleanBrandName])->first();

        // Jika brand Apple/iPhone, cocokkan variasi Apple dan iPhone
        if (!$buBrand && in_array($cleanBrandName, ['apple', 'iphone', 'ios'])) {
            $buBrand = (clone $brandQuery)->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%apple%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%iphone%']);
            })->first();
        }

        // Cari pencocokan parsial di BU
        if (!$buBrand) {
            $buBrand = (clone $brandQuery)->whereRaw('LOWER(name) LIKE ?', ['%' . $cleanBrandName . '%'])->first();
        }

        if ($buBrand) {
            return $buBrand->id;
        }

        // 3. Fallback: Cari Brand secara global (jika BU belum diset pada tabel Brand)
        $globalBrand = Brand::whereRaw('LOWER(name) = ?', [$cleanBrandName])->first();
        if (!$globalBrand && in_array($cleanBrandName, ['apple', 'iphone', 'ios'])) {
            $globalBrand = Brand::where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%apple%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%iphone%']);
            })->first();
        }

        return $globalBrand->id ?? null;
    }
}
