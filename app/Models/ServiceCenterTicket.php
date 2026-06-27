<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCenterTicket extends Model
{
    protected $guarded = ['id'];

    public function claim()
    {
        return $this->belongsTo(WarrantyClaim::class, 'warranty_claim_id');
    }
}
