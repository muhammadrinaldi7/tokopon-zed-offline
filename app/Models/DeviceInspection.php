<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DeviceInspection extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'checklist_results' => 'array',
        'inspected_at'      => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────

    public function variant()
    {
        return $this->belongsTo(SecondProductVariant::class, 'second_product_variant_id');
    }

    public function qcTemplate()
    {
        return $this->belongsTo(QcTemplate::class);
    }

    public function inspectable()
    {
        return $this->morphTo();
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeByImei($query, string $imei)
    {
        return $query->where('imei', $imei);
    }

    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('second_product_variant_id', $variantId);
    }

    // ─── Helpers ─────────────────────────────────────

    /**
     * Hitung jumlah item pass/fail dari checklist_results.
     * Dipanggil sebelum save.
     */
    public function calculateCounts(): void
    {
        $results = $this->checklist_results ?? [];
        $passed  = 0;
        $failed  = 0;

        foreach ($results as $item) {
            if (($item['type'] ?? 'boolean') === 'boolean') {
                // Boolean: true = OK, false = NOT OK
                !empty($item['value']) ? $passed++ : $failed++;
            } else {
                // Text items (contoh: Battery Health "92%") → selalu dianggap tercatat/OK
                $passed++;
            }
        }

        $this->passed_count = $passed;
        $this->failed_count = $failed;
        $this->total_items  = count($results);
    }

    /**
     * Label ringkasan untuk UI, misal: "18/22 OK"
     */
    public function getSummaryLabelAttribute(): string
    {
        return "{$this->passed_count}/{$this->total_items} OK";
    }

    // ─── Media Collections ───────────────────────────

    /**
     * 4-6 foto per sesi QC (layar, body depan, body belakang, dll)
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('qc_photos');
    }
}
