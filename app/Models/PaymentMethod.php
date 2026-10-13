<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'business_unit_id',
        'bank_name',
        'account_number',
        'account_owner',
        'accurate_bank_no',
        'accurate_customer_no',
        'mdr_percentage',
        'is_active',
        'is_visible_mobile',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    protected $casts = [
        'mdr_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_visible_mobile' => 'boolean',
    ];

    public function rates()
    {
        return $this->hasMany(PaymentMethodRate::class);
    }

    public function scopeVisibleMobile($query)
    {
        return $query->where('is_active', true)->where('is_visible_mobile', true);
    }
}
