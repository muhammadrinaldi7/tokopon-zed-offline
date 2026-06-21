<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MigrationInvoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'invoice_date',
        'vendor_id',
        'branch_name',
        'description',
        'is_exported'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'is_exported' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MigrationInvoiceItem::class);
    }
}
