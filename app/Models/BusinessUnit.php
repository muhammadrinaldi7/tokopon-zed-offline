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
        'accurate_return_warehouse_id',
        'accurate_return_warehouse_name',
        'is_taxable',
        'is_active',
        'telegram_approval_webhook',
        'telegram_log_webhook',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function businessUnitProjects()
    {
        return $this->hasMany(BusinessUnitProject::class);
    }
}
