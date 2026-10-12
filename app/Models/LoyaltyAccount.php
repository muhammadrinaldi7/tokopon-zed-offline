<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'user_id',
        'customer_name',
        'points_balance',
        'lifetime_points',
        'total_spend',
        'tier',
        'tier_updated_at',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'lifetime_points' => 'integer',
        'total_spend' => 'decimal:2',
        'tier_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class)->latest();
    }

    public function canRedeem(int $points): bool
    {
        return $points > 0 && $this->points_balance >= $points;
    }
}
