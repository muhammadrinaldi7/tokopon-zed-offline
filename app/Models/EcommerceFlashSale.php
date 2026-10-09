<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceFlashSale extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EcommerceFlashSaleItem::class, 'flash_sale_id');
    }

    public function isRunning(): bool
    {
        $now = now();
        return $this->is_active && $now->between($this->start_time, $this->end_time);
    }
}
