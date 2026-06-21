<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationInvoiceItem extends Model
{
    protected $fillable = [
        'migration_invoice_id',
        'item_code',
        'quantity',
        'unit',
        'unit_price',
        'warehouse_name',
        'serial_numbers'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(MigrationInvoice::class, 'migration_invoice_id');
    }
}
