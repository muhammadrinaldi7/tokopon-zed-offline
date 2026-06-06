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

    public function getVariantAttribute()
    {
        $variant = \App\Models\ProductVariant::with('product')->where('sku', $this->item_no)->first();
        if ($variant) {
            return $variant;
        }

        return \App\Models\SecondProductVariant::with('product')->where('sku', $this->item_no)->first();
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class, 'item_no', 'item_no');
    }
}
