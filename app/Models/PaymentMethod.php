<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_unit_id',
        'bank_name',
        'account_number',
        'account_owner',
        'accurate_bank_no',
        'mdr_percentage',
        'is_active',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    protected $casts = [
        'mdr_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rates()
    {
        return $this->hasMany(PaymentMethodRate::class);
    }
}
