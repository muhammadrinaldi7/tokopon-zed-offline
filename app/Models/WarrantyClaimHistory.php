<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyClaimHistory extends Model
{
    protected $guarded = ['id'];

    public function claim()
    {
        return $this->belongsTo(WarrantyClaim::class, 'claim_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
