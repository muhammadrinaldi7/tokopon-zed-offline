<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDeposit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'business_unit_id');
    }

    public function originOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'origin_order_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages()
    {
        return $this->hasMany(CustomerDepositUsage::class, 'customer_deposit_id');
    }
}
