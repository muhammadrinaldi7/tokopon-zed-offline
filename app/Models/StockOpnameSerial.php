<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameSerial extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'hpp' => 'decimal:2',
        'scanned_at' => 'datetime',
    ];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function stockOpnameItem()
    {
        return $this->belongsTo(StockOpnameItem::class);
    }

    public function scannedByUser()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function productSerialNumber()
    {
        return $this->belongsTo(ProductSerialNumber::class, 'serial_number', 'serial_number');
    }
}
