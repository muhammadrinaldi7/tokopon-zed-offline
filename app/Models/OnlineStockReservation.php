<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineStockReservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productSerialNumber()
    {
        return $this->belongsTo(ProductSerialNumber::class);
    }

    /**
     * Reservasi yang sedang aktif mengunci kuota stok online
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
