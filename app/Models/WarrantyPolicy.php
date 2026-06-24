<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyPolicy extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'coverage' => 'array',
        'is_active' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }
}

