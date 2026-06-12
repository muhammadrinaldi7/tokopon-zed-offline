<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAccurateDoc extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
