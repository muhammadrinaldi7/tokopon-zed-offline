<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_sent' => 'boolean',
        'payload' => 'array',
        'response_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Relasi ke User yang mengirim pesan (Kasir / Admin).
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Relasi ke Unit Usaha.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'business_unit_id');
    }

    /**
     * Relasi ke Cabang Toko.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Relasi Polymorphic ke Model sumber (Order, SellPhone, dll).
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope filter saluran (whatsapp / email).
     */
    public function scopeChannel($query, ?string $channel)
    {
        if (!empty($channel)) {
            $query->where('channel', $channel);
        }
        return $query;
    }

    /**
     * Scope filter status pengiriman.
     */
    public function scopeStatus($query, ?string $status)
    {
        if (!empty($status)) {
            $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope filter is_sent (sukses / gagal).
     */
    public function scopeSent($query, $isSent = null)
    {
        if ($isSent !== null && $isSent !== '') {
            $query->where('is_sent', (bool) $isSent);
        }
        return $query;
    }

    /**
     * Scope pencarian kata kunci.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}
