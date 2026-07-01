<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSerialNumber extends Model
{
    protected $guarded = ['id'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function getVariantAttribute()
    {
        $variant = \App\Models\ProductVariant::with('product')->where('sku', $this->item_no)->first();
        if ($variant) {
            return $variant;
        }

        return \App\Models\SecondProductVariant::with('secondProduct')->where('sku', $this->item_no)->first();
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class, 'product_accurate_id');
    }
}
