<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccurateSyncIssue extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'expected' => 'array',
        'actual' => 'array',
        'context' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function webhookLog(): BelongsTo
    {
        return $this->belongsTo(AccurateWebhookLog::class, 'webhook_log_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
