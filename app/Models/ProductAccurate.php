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
        'base_cost' => 'decimal:2',
        'has_sn' => 'boolean',
        'business_unit_id' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class, 'product_accurate_id', 'id');
    }

    public function secondProductVariants()
    {
        return $this->hasMany(SecondProductVariant::class, 'product_accurate_id', 'id');
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function warehouseStocks()
    {
        return $this->morphMany(WarehouseStock::class, 'variant', 'variant_type', 'variant_id');
    }
}
