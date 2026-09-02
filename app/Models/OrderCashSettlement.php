<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCashSettlement extends Model
{
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function monitoringBy()
    {
        return $this->belongsTo(User::class, 'monitoring_by');
    }
}
