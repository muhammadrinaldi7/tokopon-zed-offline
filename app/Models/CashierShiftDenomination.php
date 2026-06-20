<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShiftDenomination extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'denomination' => 'decimal:2',
        'subtotal'     => 'decimal:2',
    ];

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }
}
