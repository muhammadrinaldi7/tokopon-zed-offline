<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OrderPayment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];
    protected $casts = [
        'payment_payload' => 'array',
        'paid_at' => 'datetime'
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('payment_proof')->singleFile();
    }

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
