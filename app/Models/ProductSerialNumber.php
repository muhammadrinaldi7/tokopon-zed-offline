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
        return $this->belongsTo(ProductAccurate::class);
    }

    public function getProductNameAttribute()
    {
        // 1. Cek dari relasi ProductAccurate
        if ($this->relationLoaded('productAccurate') && $this->productAccurate) {
            return $this->productAccurate->name;
        }

        if ($this->product_accurate_id && $this->productAccurate) {
            return $this->productAccurate->name;
        }

        $accurate = \App\Models\ProductAccurate::where('item_no', $this->item_no)->first();
        if ($accurate) {
            return $accurate->name;
        }

        // 2. Cek dari katalog lokal ProductVariant
        $variant = \App\Models\ProductVariant::with('product')->where('sku', $this->item_no)->first();
        if ($variant && $variant->product) {
            return $variant->product->name . ($variant->name && $variant->name !== 'Default' ? ' - ' . $variant->name : '');
        }

        // 3. Cek dari katalog SecondProductVariant
        $secondVariant = \App\Models\SecondProductVariant::with('secondProduct')->where('sku', $this->item_no)->first();
        if ($secondVariant && $secondVariant->secondProduct) {
            return $secondVariant->secondProduct->name . ($secondVariant->name ? ' - ' . $secondVariant->name : '');
        }

        return null;
    }
}
