<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_unit_id',
        'name',
        'code',
        'accurate_account_no',
        'accurate_account_name',
        'adjustment_type',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where(function ($q) use ($businessUnitId) {
            $q->where('business_unit_id', $businessUnitId)
              ->orWhereNull('business_unit_id');
        });
    }
}
