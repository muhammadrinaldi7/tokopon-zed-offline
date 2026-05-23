<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'payment_payload' => 'array',
        'paid_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentMethodRate()
    {
        return $this->belongsTo(PaymentMethodRate::class);
    }
}
