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
 * @property array|null $target_items
 * @property array|null $serial_numbers
 * @property string|null $item_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\StockAdjustment $adjustment
 * @property-read array $target_items_list
 */
class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'target_items'   => 'array',
        'serial_numbers' => 'array',
        'quantity'       => 'integer',
        'unit_cost'      => 'decimal:2',
    ];

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    /**
     * Mendapatkan daftar target unit yang dinormalisasi sebagai array.
     */
    public function getTargetItemsListAttribute(): array
    {
        if (!empty($this->target_items) && is_array($this->target_items)) {
            return $this->target_items;
        }

        if (!empty($this->target_item_no)) {
            return [
                [
                    'item_no'       => $this->target_item_no,
                    'product_name'  => $this->target_product_name ?: $this->target_item_no,
                    'serial_number' => $this->target_serial_number,
                    'quantity'      => 1,
                ]
            ];
        }

        return [];
    }
}
