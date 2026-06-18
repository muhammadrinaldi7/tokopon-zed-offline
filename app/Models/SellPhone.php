<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SellPhone extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'user_id',
        'buyback_device_id',
        'phone_brand',
        'phone_model',
        'phone_ram',
        'phone_storage',
        'minus_desc',
        'appraised_value',
        'status',
        'customer_shipping_receipt',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'invoice_number',
        'handled_by',
        'imei',
        'business_unit_id',
        'reject_reason',
        'payment_receipt_path',
        'store_bank_no'
    ];

    public function buybackDevice()
    {
        return $this->belongsTo(BuybackDevice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function inspections()
    {
        return $this->morphMany(DeviceInspection::class, 'inspectable');
    }

    public function hasPassedQc(): bool
    {
        return $this->inspections()->where('verdict', 'pass')->exists();
    }
}
