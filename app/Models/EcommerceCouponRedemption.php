<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceCouponRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_applied',
        'discount_type',
    ];

    protected $casts = [
        'discount_applied' => 'decimal:2',
    ];

    public function coupon()
    {
        return $this->belongsTo(EcommerceCoupon::class, 'coupon_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
