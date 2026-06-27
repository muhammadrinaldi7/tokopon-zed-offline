<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyReplacement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function claim()
    {
        return $this->belongsTo(WarrantyClaim::class, 'warranty_claim_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
