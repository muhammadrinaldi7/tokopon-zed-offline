<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReferralCode extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'total_clicks' => 'integer',
        'total_registered' => 'integer',
        'total_converted_orders' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}