<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLead extends Model
{
    public const TEMP_COLD = 'cold';
    public const TEMP_WARM = 'warm';
    public const TEMP_HOT = 'hot';

    public const LOST_REASONS = [
        'Harga terlalu tinggi',
        'Stok / varian kosong',
        'Estimasi Trade-In tidak cocok',
        'Beli di toko lain',
        'Belum ada budget / tunda dulu',
        'Tidak ada respon (Ghosting)',
        'Lainnya',
    ];

    protected $fillable = [
        'stage_id',
        'conversation_id',
        'user_id',
        'business_unit_id',
        'assigned_to_user_id',
        'won_by_user_id',
        'product_accurate_id',
        'order_id',
        'customer_name',
        'customer_phone',
        'temperature',
        'interest_type',
        'source',
        'estimated_value',
        'won_amount',
        'lost_reason',
        'next_follow_up_at',
        'won_at',
        'lost_at',
        'notes',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'won_amount' => 'decimal:2',
        'next_follow_up_at' => 'datetime',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'stage_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'business_unit_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function wonBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'won_by_user_id');
    }

    public function productAccurate(): BelongsTo
    {
        return $this->belongsTo(ProductAccurate::class, 'product_accurate_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmLeadActivity::class, 'lead_id')->latest();
    }

    /**
     * Scope visibilitas berbasis Spatie Permission:
     * - User dengan permission `view-all-crm-leads` atau role `superadmin` dapat melihat semua lead lintas CS.
     * - User lain hanya melihat lead miliknya sendiri ATAU antrean bebas (belum diambil CS).
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('superadmin') || $user->can('view-all-crm-leads')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('assigned_to_user_id', $user->id)
              ->orWhereNull('assigned_to_user_id');
        });
    }
}
