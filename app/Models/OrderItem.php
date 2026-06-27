<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function variant()
    {
        return $this->morphTo(__FUNCTION__, 'product_variant_type', 'product_variant_id');
    }

    public function review()
    {
        return $this->hasOne(ProductReview::class);
    }

    public function appliedPromo()
    {
        return $this->belongsTo(Promo::class, 'applied_promo_id');
    }

    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'order_item_promos')->withPivot('discount_amount', 'serial_number', 'vendor_name');
    }

    public function getVendorNameAttribute()
    {
        if (!$this->variant) return 'Vendor tidak ditemukan';
        
        // Coba ambil dari ProductAccurate
        if (isset($this->variant->vendor_name)) {
            return $this->variant->vendor_name;
        }

        return 'Vendor tidak ditemukan';
    }

    public function getTotalDiscountAttribute()
    {
        return (int)$this->discount_amount + (int)$this->promo_discount_amount;
    }

    public function inspections()
    {
        return $this->morphMany(DeviceInspection::class, 'inspectable');
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }
}
