<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccurateVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_unit_id',
        'accurate_vendor_id',
        'accurate_vendor_no'
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
