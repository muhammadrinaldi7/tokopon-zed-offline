<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    // Konversi kolom JSON menjadi array otomatis di Laravel
    protected $casts = [
        'shipping_address_snapshot' => 'array',
        'mdr_percentage' => 'decimal:2',
        'mdr_amount' => 'decimal:2',
        'order_date' => 'date',
    ];

    // ─── Relationships ─────────────────────────────────────────

    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'order_promos')->withPivot('discount_applied')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        // hasMany karena user bisa saja mencoba bayar berulang kali jika gagal
        return $this->hasMany(OrderPayment::class);
    }

    public function shipping()
    {
        // hasOne karena 1 order biasanya 1 pengiriman
        return $this->hasOne(OrderShipping::class);
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
    public function salesBy()
    {
        return $this->belongsTo(Employe::class, 'sales_id', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentMethodRate()
    {
        return $this->belongsTo(PaymentMethodRate::class);
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopePos($query)
    {
        return $query->where('order_channel', 'POS');
    }

    public function scopeOnline($query)
    {
        return $query->where('order_channel', 'ONLINE');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function isPosOrder(): bool
    {
        return $this->order_channel === 'POS';
    }
}
