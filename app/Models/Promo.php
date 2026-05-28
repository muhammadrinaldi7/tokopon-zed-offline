<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_promos')->withPivot('discount_applied')->withTimestamps();
    }
}
