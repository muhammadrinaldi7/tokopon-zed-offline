<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashierShift extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'shift_date'     => 'date',
        'opened_at'      => 'datetime',
        'closed_at'      => 'datetime',
        'starting_cash'  => 'decimal:2',
        'expected_cash'  => 'decimal:2',
        'actual_cash'    => 'decimal:2',
        'cash_difference'=> 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_non_cash_sales' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    // Scope: hanya shift yang open
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function denominations()
    {
        return $this->hasMany(CashierShiftDenomination::class);
    }

    public function openingDenominations()
    {
        return $this->hasMany(CashierShiftDenomination::class)->where('type', 'opening');
    }

    public function closingDenominations()
    {
        return $this->hasMany(CashierShiftDenomination::class)->where('type', 'closing');
    }
}
