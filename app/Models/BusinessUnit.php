<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessUnit extends Model
{
    protected $fillable = [
        'code',
        'customer_prefix',
        'prefix',
        'order_prefix',
        'draft_prefix',
        'store_title',
        'receipt_show_discount',
        'name',
        'accurate_host',
        'accurate_token',
        'accurate_secret_key',
        'accurate_database_id',
        'is_taxable',
        'is_active',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
