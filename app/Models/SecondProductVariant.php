<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SecondProductVariant extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    public function secondProduct()
    {
        return $this->belongsTo(SecondProduct::class);
    }

    public function product()
    {
        return $this->secondProduct();
    }

    public function warehouseStocks()
    {
        return $this->morphMany(WarehouseStock::class, 'variant');
    }

    public function sellPhone()
    {
        return $this->belongsTo(SellPhone::class);
    }

    public function accurateData()
    {
        return $this->belongsTo(ProductAccurate::class, 'product_accurate_id');
    }

    public function inspections()
    {
        return $this->hasMany(DeviceInspection::class, 'second_product_variant_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant_image')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);
    }
}
