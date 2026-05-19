<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuybackDevice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active'  => 'boolean',
        'base_price' => 'decimal:2',
    ];

    // Relasi ke Brand
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Relasi ke Tier (tier otomatis ter-assign berdasarkan base_price)
    public function tier()
    {
        return $this->belongsTo(BuybackTier::class, 'buyback_tier_id');
    }

    // Relasi ke Second Product Variant untuk data integrasi Accurate dan Master Bekas
    public function secondProductVariant()
    {
        return $this->belongsTo(SecondProductVariant::class, 'second_product_variant_id');
    }

    /**
     * Auto-assign tier berdasarkan base_price.
     * Dipanggil setelah device dibuat/diupdate.
     */
    public function assignTierByPrice(): void
    {
        $tier = BuybackTier::findByPrice((float) $this->base_price);
        $this->buyback_tier_id = $tier?->id;
        $this->saveQuietly();
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
