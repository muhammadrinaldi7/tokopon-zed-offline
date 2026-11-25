<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_online_store' => 'boolean',
        'allow_online_delivery' => 'boolean',
        'allow_store_pickup' => 'boolean',
        'fulfillment_priority' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeOnlineStore($query)
    {
        return $query->where(function ($q) {
            $q->where('is_online_store', true)
                ->orWhere('allow_online_delivery', true)
                ->orWhere('allow_store_pickup', true);
        });
    }

    public function scopeOnlineDelivery($query)
    {
        return $query->where(function ($q) {
            $q->where('allow_online_delivery', true)
                ->orWhere(function ($legacy) {
                    $legacy->where('is_online_store', true)
                        ->where('allow_store_pickup', false);
                });
        });
    }

    public function scopeStorePickup($query)
    {
        return $query->where('allow_store_pickup', true);
    }
}
