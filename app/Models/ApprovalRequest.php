<?php

namespace App\Models;

use App\Services\ApprovalService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ApprovalRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload'      => 'array',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($request) {
            // Auto-fallback jika business_unit_id belum diisi caller
            if (empty($request->business_unit_id)) {
                if ($request->approvable && isset($request->approvable->business_unit_id)) {
                    $request->business_unit_id = $request->approvable->business_unit_id;
                } elseif (Auth::check()) {
                    $request->business_unit_id = Auth::user()->getActiveBusinessUnitId() ?? 1;
                } else {
                    $user = User::find($request->requested_by);
                    $request->business_unit_id = $user ? $user->getActiveBusinessUnitId() : 1;
                }
            }

            // Auto-fallback jika approvable belum diisi caller
            if (empty($request->approvable_type)) {
                $request->approvable_type = User::class;
                $request->approvable_id = $request->requested_by ?: 1;
            }

            // Auto-fallback jika branch_id belum diisi caller
            if (empty($request->branch_id)) {
                if ($request->approvable && isset($request->approvable->branch_id)) {
                    $request->branch_id = $request->approvable->branch_id;
                } elseif (Auth::check()) {
                    $request->branch_id = Auth::user()->branch_id;
                } else {
                    $user = User::find($request->requested_by);
                    $request->branch_id = $user?->branch_id;
                }
            }
        });
    }

    public function approvable()
    {
        return $this->morphTo();
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class, 'business_unit_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function histories()
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    /**
     * Eksekusi aksi yang disetujui (didelegasikan ke Handler masing-masing).
     */
    public function executeAction(array $params = [])
    {
        if ($this->status !== 'APPROVED') {
            throw new Exception("Cannot execute a request that is not approved.");
        }

        $handler = app(ApprovalService::class)->getHandler($this->request_type);
        $handler->handleApproved($this, $params);

        // Custom cashback di-complete oleh kasir saat checkout di POS
        if ($this->request_type !== 'CUSTOM_CASHBACK') {
            $this->update(['status' => 'COMPLETED']);
        }

        return true;
    }

    /**
     * Eksekusi aksi yang ditolak (didelegasikan ke Handler masing-masing).
     */
    public function executeRejectedAction(array $params = [])
    {
        if ($this->status !== 'REJECTED') {
            throw new Exception("Cannot execute a request that is not rejected.");
        }

        $handler = app(ApprovalService::class)->getHandler($this->request_type);
        $handler->handleRejected($this, $params);

        return true;
    }
}
