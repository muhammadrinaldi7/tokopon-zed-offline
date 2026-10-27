<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionWithdrawal extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(UserBankAccount::class, 'user_bank_account_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}