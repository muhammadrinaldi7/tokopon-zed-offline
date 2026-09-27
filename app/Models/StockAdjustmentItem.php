<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StockAdjustmentItem
 *
 * @property int $id
 * @property int $stock_adjustment_id
 * @property string $adjustment_type
 * @property string $item_no
 * @property string $product_name
 * @property int $quantity
 * @property float $unit_cost
 * @property string|null $proyek
 * @property string|null $project_no
 * @property string|null $target_item_no
 * @property string|null $target_product_name
 * @property string|null $target_serial_number
 * @property array|null $serial_numbers
 * @property string|null $item_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\StockAdjustment $adjustment
 */
class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'serial_numbers' => 'array',
        'quantity'       => 'integer',
        'unit_cost'      => 'decimal:2',
    ];

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }
}
