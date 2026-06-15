<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'is_bundle' => 'boolean',
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_transaction_amount' => 'decimal:2',
        'min_qty' => 'integer',
        'bundle_discount_value' => 'decimal:2',
        'bundle_max_discount' => 'decimal:2',
        'bundle_max_qty' => 'integer',
        'apply_to_all_items' => 'boolean',
        'is_multiply' => 'boolean',
        'is_combinable' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'promo_branches');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_promos')->withPivot('discount_applied')->withTimestamps();
    }

    public function skus()
    {
        return $this->hasMany(PromoSku::class);
    }

    public function bundleSkus()
    {
        return $this->hasMany(PromoBundleSku::class);
    }

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'promo_payment_methods');
    }
}

