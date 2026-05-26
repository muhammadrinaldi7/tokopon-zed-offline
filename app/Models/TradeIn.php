<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TradeIn extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targetProduct()
    {
        return $this->morphTo(null, 'target_product_type', 'target_product_id');
    }

    public function buybackDevice()
    {
        return $this->belongsTo(BuybackDevice::class, 'buyback_device_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function unitOptions()
    {
        return $this->hasMany(TradeInUnitOption::class);
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function productVariant()
    {
        return $this->morphTo(null, 'product_variant_type', 'product_variant_id');
    }

    public function inspections()
    {
        return $this->morphMany(DeviceInspection::class, 'inspectable');
    }

    public function hasPassedQc(): bool
    {
        return $this->inspections()->where('verdict', 'pass')->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
        $this->addMediaCollection('admin_inspection_photos');
    }
}
