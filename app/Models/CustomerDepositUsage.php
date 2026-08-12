<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDepositUsage extends Model
{
    protected $guarded = ['id'];

    public function customerDeposit()
    {
        return $this->belongsTo(CustomerDeposit::class, 'customer_deposit_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
