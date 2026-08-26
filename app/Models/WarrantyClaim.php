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

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => [
                'label' => 'Pending',
                'bg' => 'bg-amber-100 text-amber-700',
                'dot' => 'bg-amber-500'
            ],
            'approved' => [
                'label' => 'Disetujui',
                'bg' => 'bg-blue-100 text-blue-700',
                'dot' => 'bg-blue-500'
            ],
            'in_repair' => [
                'label' => 'Diproses',
                'bg' => 'bg-purple-100 text-purple-700',
                'dot' => 'bg-purple-500'
            ],
            'waiting_payment' => [
                'label' => 'Menunggu Pelunasan',
                'bg' => 'bg-yellow-100 text-yellow-700',
                'dot' => 'bg-yellow-500'
            ],
            'waiting_refund' => [
                'label' => 'Menunggu Refund',
                'bg' => 'bg-orange-100 text-orange-700',
                'dot' => 'bg-orange-500'
            ],
            'completed' => [
                'label' => 'Selesai',
                'bg' => 'bg-emerald-100 text-emerald-700',
                'dot' => 'bg-emerald-500'
            ],
            'rejected' => [
                'label' => 'Ditolak',
                'bg' => 'bg-rose-100 text-rose-700',
                'dot' => 'bg-rose-500'
            ],
        ];

        $status = $statuses[$this->status] ?? [
            'label' => ucfirst(str_replace('_', ' ', $this->status)),
            'bg' => 'bg-gray-100 text-gray-700',
            'dot' => 'bg-gray-500'
        ];

        return (object) $status;
    }
}

