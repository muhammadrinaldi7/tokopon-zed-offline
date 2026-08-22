<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::created(function ($orderItem) {
            if (!empty($orderItem->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $orderItem->serial_number)));
                foreach ($sns as $sn) {
                    \App\Models\OrderItemSerialNumber::firstOrCreate([
                        'order_item_id' => $orderItem->id,
                        'serial_number' => $sn
                    ]);
                }
            }
        });

        static::updated(function ($orderItem) {
            if ($orderItem->isDirty('serial_number')) {
                \App\Models\OrderItemSerialNumber::where('order_item_id', $orderItem->id)->delete();
                if (!empty($orderItem->serial_number)) {
                    $sns = array_filter(array_map('trim', explode(',', $orderItem->serial_number)));
                    foreach ($sns as $sn) {
                        \App\Models\OrderItemSerialNumber::firstOrCreate([
                            'order_item_id' => $orderItem->id,
                            'serial_number' => $sn
                        ]);
                    }
                }
            }
        });
    }

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

    public function serialNumbers()
    {
        return $this->hasMany(OrderItemSerialNumber::class, 'order_item_id');
    }
}
