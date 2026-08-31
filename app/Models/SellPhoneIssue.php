<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellPhoneIssue extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function sellPhone(): BelongsTo
    {
        return $this->belongsTo(SellPhone::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
