<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderResetLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'previous_payments_snapshot' => 'array',
        'previous_accurate_docs_snapshot' => 'array',
        'previous_grand_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function resetBy()
    {
        return $this->belongsTo(User::class, 'reset_by');
    }

    public function previousHandledBy()
    {
        return $this->belongsTo(User::class, 'previous_handled_by');
    }

    public function newHandledBy()
    {
        return $this->belongsTo(User::class, 'new_handled_by');
    }
}
