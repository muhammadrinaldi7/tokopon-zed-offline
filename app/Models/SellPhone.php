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
        'product_accurate_id',
        'phone_brand',
        'phone_model',
        'phone_ram',
        'phone_storage',
        'minus_desc',
        'appraised_value',
        'original_appraised_value',
        'is_price_adjusted',
        'price_adjusted_by',
        'price_adjustment_reason',
        'is_wa_sent',
        'is_email_sent',
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
        'store_bank_no',
        'branch_id'
    ];

    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function secondProductVariant()
    {
        return $this->hasOne(SecondProductVariant::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function buybackDevice()
    {
        return $this->belongsTo(BuybackDevice::class, 'buyback_device_id');
    }

    public function inventoryStatus()
    {
        return $this->hasOne(\App\Models\ProductSerialNumber::class, 'serial_number', 'imei');
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

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function issues()
    {
        return $this->hasMany(SellPhoneIssue::class)->latest();
    }

    public function openIssues()
    {
        return $this->hasMany(SellPhoneIssue::class)->where('status', 'OPEN');
    }
}
