<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'claimed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deviceInspection()
    {
        return $this->belongsTo(DeviceInspection::class);
    }

    public function claimsHistory()
    {
        return $this->hasMany(WarrantyClaimHistory::class, 'claim_id');
    }

    public function receivingInspection()
    {
        return $this->belongsTo(DeviceInspection::class, 'receiving_inspection_id');
    }

    public function replacement()
    {
        return $this->hasOne(WarrantyReplacement::class);
    }

    public function serviceCenterTicket()
    {
        return $this->hasOne(ServiceCenterTicket::class);
    }
}

