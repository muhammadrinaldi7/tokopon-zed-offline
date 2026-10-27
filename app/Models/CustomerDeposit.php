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

    protected $casts = [
        'refunded_at' => 'datetime',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function usages()
    {
        return $this->hasMany(CustomerDepositUsage::class, 'customer_deposit_id');
    }

    public function refundGlAccount(): BelongsTo
    {
        return $this->belongsTo(AccurateGlAccount::class, 'refund_bank_no', 'account_no');
    }

    public function getRefundBankLabelAttribute(): string
    {
        if (empty($this->refund_bank_no)) {
            return '-';
        }

        if ($this->refund_bank_no === 'KAS-CASH') {
            return 'Kas Tunai Kasir (KAS-CASH)';
        }

        $gl = $this->refundGlAccount;
        if ($gl) {
            return "{$gl->name} ({$this->refund_bank_no})";
        }

        return $this->refund_bank_no;
    }
}
