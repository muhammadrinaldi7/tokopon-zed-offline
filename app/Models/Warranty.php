<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(WarrantyPolicy::class, 'warranty_policy_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function deviceInspection()
    {
        return $this->belongsTo(DeviceInspection::class);
    }

    public function claims()
    {
        return $this->hasMany(WarrantyClaim::class);
    }
}

