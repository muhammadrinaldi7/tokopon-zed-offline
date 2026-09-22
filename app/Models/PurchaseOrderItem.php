<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'accurate_detail_id',
        'item_no',
        'item_name',
        'has_sn',
        'unit_price',
        'quantity_ordered',
        'quantity_received',
        'quantity_pushed',
    ];

    protected $casts = [
        'has_sn' => 'boolean',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inspections()
    {
        return $this->morphMany(DeviceInspection::class, 'inspectable');
    }

    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class, 'item_no', 'item_no');
    }

    public function getProyekAttribute()
    {
        return $this->productAccurate?->proyek ?? '-';
    }
}
