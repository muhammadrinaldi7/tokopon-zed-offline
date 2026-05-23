<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'account_owner',
        'accurate_bank_no',
        'mdr_percentage',
        'is_active',
    ];

    protected $casts = [
        'mdr_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rates()
    {
        return $this->hasMany(PaymentMethodRate::class);
    }
}
