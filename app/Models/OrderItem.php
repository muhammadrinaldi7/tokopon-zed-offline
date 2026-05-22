<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function variant()
    {
        return $this->morphTo(__FUNCTION__, 'product_variant_type', 'product_variant_id');
    }

    public function review()
    {
        return $this->hasOne(ProductReview::class);
    }
}
