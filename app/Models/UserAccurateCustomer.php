<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccurateCustomer extends Model
{
    protected $fillable = [
        'user_id',
        'business_unit_id',
        'accurate_customer_id',
        'accurate_customer_no',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }
}
