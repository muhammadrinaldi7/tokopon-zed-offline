<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_serialized' => 'boolean',
        'system_qty' => 'integer',
        'physical_qty' => 'integer',
        'difference_qty' => 'integer',
        'unit_cost' => 'decimal:2',
        'difference_value' => 'decimal:2',
    ];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function serials()
    {
        return $this->hasMany(StockOpnameSerial::class);
    }

    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class, 'item_no', 'item_no');
    }

    /**
     * Hitung ulang selisih kuantitas dan nominal selisih
     */
    public function recalculateDifference(): void
    {
        $diffQty = $this->physical_qty - $this->system_qty;
        $diffValue = $diffQty * (float) $this->unit_cost;

        $this->update([
            'difference_qty'   => $diffQty,
            'difference_value' => $diffValue,
        ]);
    }
}
