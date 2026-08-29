<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantySerialLog extends Model
{
    protected $guarded = ['id'];

    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    public function claim()
    {
        return $this->belongsTo(WarrantyClaim::class, 'warranty_claim_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
