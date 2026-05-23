<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_method_id',
        'name',
        'mdr_percentage',
        'is_active',
    ];

    protected $casts = [
        'mdr_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
