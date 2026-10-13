<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceFlashSaleItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'flash_sale_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'quota_stock' => 'integer',
        'sold_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(EcommerceFlashSale::class, 'flash_sale_id');
    }

    public function productAccurate(): BelongsTo
    {
        return $this->belongsTo(ProductAccurate::class, 'product_accurate_id');
    }

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->original_price || $this->original_price <= $this->flash_sale_price) {
            return 0;
        }
        return (int) round((($this->original_price - $this->flash_sale_price) / $this->original_price) * 100);
    }
}
