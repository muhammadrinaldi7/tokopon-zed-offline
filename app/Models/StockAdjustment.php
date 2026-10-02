<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\StockAdjustment
 *
 * @property int $id
 * @property string $adjustment_number
 * @property int $business_unit_id
 * @property int|null $branch_id
 * @property int|null $warehouse_id
 * @property string|null $warehouse_name
 * @property string $adjustment_type
 * @property int $total_items
 * @property int $total_quantity
 * @property string|null $item_no
 * @property string|null $product_name
 * @property int|null $quantity
 * @property float|null $unit_cost
 * @property string|null $proyek
 * @property string|null $project_no
 * @property string|null $target_item_no
 * @property string|null $target_product_name
 * @property string|null $target_serial_number
 * @property string $reason_category
 * @property string|null $notes
 * @property string|null $accurate_account_no
 * @property string|null $accurate_adjustment_no
 * @property string $status
 * @property string|null $sync_error
 * @property int $requested_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockAdjustmentItem> $items
 * @property-read \App\Models\Warehouse|null $warehouse
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\BusinessUnit|null $businessUnit
 * @property-read \App\Models\User|null $requestedBy
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\ApprovalRequest|null $approvalRequest
 */
class StockAdjustment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'serial_numbers' => 'array',
        'quantity'       => 'integer',
        'unit_cost'      => 'decimal:2',
        'approved_at'    => 'datetime',
        'synced_at'      => 'datetime',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class, 'stock_adjustment_id');
    }

    public function approvalRequest()
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable');
    }

    public function reason()
    {
        return $this->belongsTo(StockAdjustmentReason::class, 'reason_category', 'code');
    }

    public function getReasonLabelAttribute(): string
    {
        $specificReason = StockAdjustmentReason::where('code', $this->reason_category)
            ->where(function ($q) {
                $q->where('business_unit_id', $this->business_unit_id)
                  ->orWhereNull('business_unit_id');
            })
            ->orderByRaw('business_unit_id IS NULL ASC')
            ->first();

        return $specificReason?->name ?? str_replace('_', ' ', $this->reason_category);
    }

    /**
     * Generate unique adjustment number: ADJ-YYYYMM-XXXX
     */
    public static function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ-' . now()->format('Ym') . '-';
        $lastRecord = static::where('adjustment_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastRecord && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastRecord->adjustment_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
