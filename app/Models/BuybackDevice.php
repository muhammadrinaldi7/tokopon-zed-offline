<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuybackDevice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active'           => 'boolean',
        'product_accurate_id' => 'integer',
    ];


    // Relasi ke Tier (tier otomatis ter-assign berdasarkan base_price)
    public function tier()
    {
        return $this->belongsTo(BuybackTier::class, 'buyback_tier_id');
    }


    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class, 'product_accurate_id');
    }

    /**
     * Cari mapping BuybackDevice berdasarkan SKU (ProductAccurate).
     * Menggunakan hierarki:
     * 1. SKU (product_accurate_id)
     * 2. OS + Category + Brand
     * 3. Category + Brand
     * 4. OS + Category
     * 5. Category
     * 6. Brand
     * 7. Default Global (semua null)
     */
    public static function findByProductAccurate(ProductAccurate $productAccurate): ?self
    {
        // Level 1: Spesifik SKU
        $mapping = self::where('product_accurate_id', $productAccurate->id)
                   ->where('is_active', true)
                   ->first();
        if ($mapping) return $mapping;

        $os = $productAccurate->os;
        $category = $productAccurate->categoryName;
        $brand = $productAccurate->brandName;

        // Base query builder
        $query = function() {
            return self::where('is_active', true)->whereNull('product_accurate_id');
        };

        // Level 2: OS + Kategori + Brand
        if ($os && $category && $brand) {
            $mapping = $query()
                ->where('os_name', $os)
                ->where('category_name', $category)
                ->where('brand_name', $brand)
                ->first();
            if ($mapping) return $mapping;
        }

        // Level 3: Kategori + Brand
        if ($category && $brand) {
            $mapping = $query()
                ->whereNull('os_name')
                ->where('category_name', $category)
                ->where('brand_name', $brand)
                ->first();
            if ($mapping) return $mapping;
        }

        // Level 4: OS + Kategori
        if ($os && $category) {
            $mapping = $query()
                ->where('os_name', $os)
                ->where('category_name', $category)
                ->whereNull('brand_name')
                ->first();
            if ($mapping) return $mapping;
        }

        // Level 5: Kategori Saja
        if ($category) {
            $mapping = $query()
                ->whereNull('os_name')
                ->where('category_name', $category)
                ->whereNull('brand_name')
                ->first();
            if ($mapping) return $mapping;
        }

        // Level 6: Brand Saja
        if ($brand) {
            $mapping = $query()
                ->whereNull('os_name')
                ->whereNull('category_name')
                ->where('brand_name', $brand)
                ->first();
            if ($mapping) return $mapping;
        }

        // Level 7: Default Global
        return $query()
            ->whereNull('os_name')
            ->whereNull('category_name')
            ->whereNull('brand_name')
            ->first();
    }

    /**
     * Ambil rules aktif dari tier yang ter-assign.
     */
    public function getRules(): array
    {
        return $this->tier?->getRulesByCategory() ?? [];
    }

    /**
     * Flat rules: array of ['key' => 'cat.idx', 'name' => ..., 'type' => ..., 'value' => ..., 'category' => ...]
     */
    public function getFlatRules(): array
    {
        $flat = [];
        foreach ($this->getRules() as $category => $items) {
            foreach ($items as $idx => $item) {
                $flat[] = [
                    // Menggunakan slug dan underscore untuk menghindari masalah array bersarang di Livewire 
                    // akibat penamaan key dengan titik (.) seperti wire:model="selected_rules.Fisik.0"
                    'key'         => \Illuminate\Support\Str::slug($category) . '_' . $idx,
                    'category'    => $category,
                    'name'        => $item['name'],
                    'type'        => $item['type'],
                    'value'       => (float) $item['value'],
                    'description' => $item['description'] ?? '',
                ];
            }
        }
        return $flat;
    }
}
