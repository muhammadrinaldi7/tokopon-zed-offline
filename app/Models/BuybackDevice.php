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
     * Cari mapping BuybackDevice berdasarkan SKU (ProductAccurate ID).
     */
    public static function findByProductAccurate(int $productAccurateId): ?self
    {
        return self::where('product_accurate_id', $productAccurateId)
                   ->where('is_active', true)
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
                    'key'      => \Illuminate\Support\Str::slug($category) . '_' . $idx,
                    'category' => $category,
                    'name'     => $item['name'],
                    'type'     => $item['type'],
                    'value'    => (float) $item['value'],
                ];
            }
        }
        return $flat;
    }
}
