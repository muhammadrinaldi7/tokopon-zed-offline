<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcTemplate extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'items'      => 'array',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function inspections()
    {
        return $this->hasMany(DeviceInspection::class);
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ─── Helpers ─────────────────────────────────────

    /**
     * Cari template terbaik untuk brand + jenis perangkat.
     * Prioritas:
     *   1. Brand + Device Category (paling spesifik)
     *   2. Kategori Saja (tanpa brand) -> ex: "Smartwatch" umum
     *   3. Brand saja (tanpa device_category / null)
     *   4. Template default
     *   5. Template aktif pertama (fallback terakhir)
     */
    public static function findForBrandAndCategory(?int $brandId, ?string $deviceCategory = null): ?self
    {
        // 1. Brand + Category (paling spesifik)
        if ($brandId && $deviceCategory) {
            $specific = self::active()
                ->where('brand_id', $brandId)
                ->where('device_category', $deviceCategory)
                ->first();
            if ($specific) return $specific;
        }

        // 2. Kategori saja (Tanpa Brand) -> Lebih spesifik dari sekadar Brand
        if ($deviceCategory) {
            $categoryOnly = self::active()
                ->whereNull('brand_id')
                ->where('device_category', $deviceCategory)
                ->first();
            if ($categoryOnly) return $categoryOnly;
        }

        // 3. Brand saja (tanpa kategori)
        if ($brandId) {
            $brandOnly = self::active()
                ->where('brand_id', $brandId)
                ->whereNull('device_category')
                ->first();
            if ($brandOnly) return $brandOnly;
        }

        // 4. Default
        // 5. Fallback
        return self::active()->default()->first()
            ?? self::active()->first();
    }

    /**
     * Mapping categoryName dari Accurate ke device_category yang seragam.
     */
    public static function normalizeDeviceCategory(?string $categoryName): ?string
    {
        if (!$categoryName) return null;

        $lower = strtolower(trim($categoryName));

        $map = [
            'handphone' => 'smartphone',
            'hp baru'   => 'smartphone',
            'hp second' => 'smartphone',
            'smartphone'=> 'smartphone',

            'smartwatch'    => 'smartwatch',
            'iwatch second' => 'smartwatch',

            'tablet'       => 'tablet',
            'ipad second'  => 'tablet',

            'laptop'         => 'laptop',
            'notebook'       => 'laptop',
            'macbook second' => 'laptop',

            'accessories' => 'accessories',
            'add on'      => 'accessories',
            'case'        => 'accessories',
            'adapter'     => 'accessories',
            'headset'     => 'accessories',
            'powerbank'   => 'accessories',
        ];

        return $map[$lower] ?? null;
    }
}
