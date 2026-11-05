<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'unit_hpp' => 'float',
        'total_hpp' => 'float',
        'price_at_checkout' => 'float',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'promo_discount_amount' => 'float',
    ];

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

    public function productVariant()
    {
        return $this->variant();
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
        // 1. Ambil dari kolom snapshot database jika sudah terisi dan valid
        if (!empty($this->attributes['vendor_name']) && $this->attributes['vendor_name'] !== 'Vendor tidak ditemukan') {
            return $this->attributes['vendor_name'];
        }

        // 2. Coba ambil dari Master Variant / Accurate jika ada
        if ($this->variant && !empty($this->variant->vendor_name)) {
            return $this->variant->vendor_name;
        }

        if ($this->variant && !empty($this->variant->accurateData?->vendor_name)) {
            return $this->variant->accurateData->vendor_name;
        }

        return null;
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
