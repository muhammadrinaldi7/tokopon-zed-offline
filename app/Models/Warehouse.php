<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_online_store' => 'boolean',
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
        return $query->where('is_online_store', true);
    }
}
