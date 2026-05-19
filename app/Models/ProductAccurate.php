<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAccurate extends Model
{
    // Now using auto-increment ID
    protected $guarded = ['id'];

    protected $casts = [
        'raw_data' => 'array',
        'base_price' => 'decimal:2',
    ];

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class, 'product_accurate_id', 'id');
    }

    public function secondProductVariants()
    {
        return $this->hasMany(SecondProductVariant::class, 'product_accurate_id', 'id');
    }
}
