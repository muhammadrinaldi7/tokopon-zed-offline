<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
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
    public static function findForBrandAndCategory(?int $brandId, ?string $deviceCategory = null, ?int $businessUnitId = null): ?self
    {
        if (is_null($businessUnitId) && Auth::check()) {
            $businessUnitId = Auth::user()->getActiveBusinessUnitId();
        }

        $findWithBU = function ($buId) use ($brandId, $deviceCategory) {
            // 1. Brand + Category (paling spesifik)
            if ($brandId && $deviceCategory) {
                $specific = self::active()
                    ->where('business_unit_id', $buId)
                    ->where('brand_id', $brandId)
                    ->where('device_category', $deviceCategory)
                    ->first();
                if ($specific) return $specific;
            }

            // 2. Kategori saja (Tanpa Brand)
            if ($deviceCategory) {
                $categoryOnly = self::active()
                    ->where('business_unit_id', $buId)
                    ->whereNull('brand_id')
                    ->where('device_category', $deviceCategory)
                    ->first();
                if ($categoryOnly) return $categoryOnly;
            }

            // 3. Brand saja (tanpa kategori)
            if ($brandId) {
                $brandOnly = self::active()
                    ->where('business_unit_id', $buId)
                    ->where('brand_id', $brandId)
                    ->whereNull('device_category')
                    ->first();
                if ($brandOnly) return $brandOnly;
            }

            // 4. Generic template (tanpa brand & tanpa kategori)
            $genericTemplate = self::active()
                ->where('business_unit_id', $buId)
                ->whereNull('brand_id')
                ->whereNull('device_category');

            // Prioritaskan yang is_default = true
            $defaultGeneric = (clone $genericTemplate)->default()->first();
            if ($defaultGeneric) return $defaultGeneric;

            // Jika tidak ada yang default, ambil yang pertama
            $anyGeneric = $genericTemplate->first();
            if ($anyGeneric) return $anyGeneric;

            return null;
        };

        // Coba cari untuk BU spesifik terlebih dahulu
        if ($businessUnitId) {
            $template = $findWithBU($businessUnitId);
            if ($template) return $template;
        }

        // Fallback ke Global Template (business_unit_id = null)
        $template = $findWithBU(null);
        if ($template) return $template;

        // Fallback terakhir: Template apapun yang default atau aktif
        return self::active()->where('business_unit_id', $businessUnitId)->default()->first()
            ?? self::active()->whereNull('business_unit_id')->default()->first()
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
            'smartphone' => 'smartphone',

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
