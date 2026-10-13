<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BusinessUnit extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'code',
        'customer_prefix',
        'prefix',
        'order_prefix',
        'draft_prefix',
        'store_title',
        'receipt_show_discount',
        'name',
        'accurate_host',
        'accurate_token',
        'accurate_secret_key',
        'accurate_database_id',
        'accurate_return_warehouse_id',
        'accurate_return_warehouse_name',
        'is_active',
        'is_visible_mobile',
        'mobile_display_name',
        'mobile_category',
        'mobile_description',
        'brand_color',
        'warranty_highlight',
        'telegram_approval_webhook',
        'telegram_log_webhook',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'is_visible_mobile' => 'boolean',
        'receipt_show_discount' => 'boolean',
    ];

    public function scopeVisibleMobile($query)
    {
        return $query->where('is_active', true)->where('is_visible_mobile', true);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function businessUnitProjects()
    {
        return $this->hasMany(BusinessUnitProject::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->hasMedia('logo') ? $this->getFirstMediaUrl('logo') : null;
    }
}
