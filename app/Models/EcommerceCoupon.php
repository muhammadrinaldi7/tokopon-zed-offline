<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceCoupon extends Model
{
    use HasFactory;

    public const TYPE_PRODUCT_PERCENT = 'PRODUCT_DISCOUNT_PERCENT';
    public const TYPE_PRODUCT_FIXED = 'PRODUCT_DISCOUNT_FIXED';
    public const TYPE_SHIPPING_PERCENT = 'SHIPPING_DISCOUNT_PERCENT';
    public const TYPE_SHIPPING_FIXED = 'SHIPPING_DISCOUNT_FIXED';

    protected $fillable = [
        'business_unit_id',
        'code',
        'title',
        'description',
        'terms_conditions',
        'type',
        'discount_value',
        'max_discount_amount',
        'min_purchase_amount',
        'usage_limit_total',
        'usage_limit_per_user',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'usage_limit_total' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────────

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function redemptions()
    {
        return $this->hasMany(EcommerceCouponRedemption::class, 'coupon_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValidNow($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
        });
    }

    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit_total')
                    ->orWhereRaw('used_count < usage_limit_total');
            });
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function isShippingDiscount(): bool
    {
        return in_array($this->type, [self::TYPE_SHIPPING_PERCENT, self::TYPE_SHIPPING_FIXED], true);
    }

    public function isProductDiscount(): bool
    {
        return in_array($this->type, [self::TYPE_PRODUCT_PERCENT, self::TYPE_PRODUCT_FIXED], true);
    }

    public function isExpired(): bool
    {
        return $this->end_date && Carbon::now()->greaterThan($this->end_date);
    }

    public function isStarted(): bool
    {
        return !$this->start_date || Carbon::now()->greaterThanOrEqualTo($this->start_date);
    }

    public function hasQuota(): bool
    {
        if ($this->usage_limit_total === null) {
            return true;
        }
        return (int) $this->used_count < (int) $this->usage_limit_total;
    }

    public function canBeUsedByUser(?int $userId): bool
    {
        if (!$this->is_active || $this->isExpired() || !$this->isStarted() || !$this->hasQuota()) {
            return false;
        }

        if (!$userId) {
            return true;
        }

        if ($this->usage_limit_per_user <= 0) {
            return true;
        }

        $userRedemptionsCount = $this->redemptions()->where('user_id', $userId)->count();
        return $userRedemptionsCount < $this->usage_limit_per_user;
    }

    /**
     * Menghitung nilai nominal potongan kupon berdasarkan subtotal produk dan ongkir.
     */
    public function calculateDiscount(float $subtotal, float $shippingCost = 0): float
    {
        $discount = 0.0;
        $val = (float) $this->discount_value;
        $maxCap = $this->max_discount_amount !== null ? (float) $this->max_discount_amount : null;

        switch ($this->type) {
            case self::TYPE_PRODUCT_PERCENT:
                $discount = ($subtotal * $val) / 100;
                if ($maxCap !== null && $discount > $maxCap) {
                    $discount = $maxCap;
                }
                // Diskon tidak boleh melebihi subtotal produk
                $discount = min($discount, $subtotal);
                break;

            case self::TYPE_PRODUCT_FIXED:
                $discount = min($val, $subtotal);
                break;

            case self::TYPE_SHIPPING_PERCENT:
                $discount = ($shippingCost * $val) / 100;
                if ($maxCap !== null && $discount > $maxCap) {
                    $discount = $maxCap;
                }
                // Diskon ongkir tidak boleh melebihi nilai ongkir aktual
                $discount = min($discount, $shippingCost);
                break;

            case self::TYPE_SHIPPING_FIXED:
                $discount = min($val, $shippingCost);
                break;
        }

        return max(0.0, round($discount, 2));
    }
}
