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
     * Cari template terbaik untuk brand tertentu.
     * Prioritas: brand-specific → default → pertama aktif.
     */
    public static function findForBrand(?int $brandId): ?self
    {
        if ($brandId) {
            $specific = self::active()->where('brand_id', $brandId)->first();
            if ($specific) return $specific;
        }

        return self::active()->default()->first()
            ?? self::active()->first();
    }
}
