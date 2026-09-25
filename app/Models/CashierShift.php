<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashierShift extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'shift_date'     => 'date',
        'opened_at'      => 'datetime',
        'closed_at'      => 'datetime',
        'starting_cash'  => 'decimal:2',
        'expected_cash'  => 'decimal:2',
        'actual_cash'    => 'decimal:2',
        'cash_difference'=> 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_non_cash_sales' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    // Scope: hanya shift yang open
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function denominations()
    {
        return $this->hasMany(CashierShiftDenomination::class);
    }

    public function openingDenominations()
    {
        return $this->hasMany(CashierShiftDenomination::class)->where('type', 'opening');
    }

    public function closingDenominations()
    {
        return $this->hasMany(CashierShiftDenomination::class)->where('type', 'closing');
    }

    /**
     * Buka kembali shift kasir yang tertutup tidak sengaja.
     */
    public function reopen(string $reason = '', ?int $adminId = null): void
    {
        $admin = $adminId ? User::find($adminId) : \Illuminate\Support\Facades\Auth::user();
        $adminName = $admin?->name ?? 'Admin';
        $timestamp = now()->format('d/m/Y H:i');

        $auditEntry = "[Shift dibuka kembali pada {$timestamp} oleh {$adminName}" . ($reason ? " - Alasan: {$reason}" : "") . "]";
        $newNotes = trim(($this->closing_notes ? $this->closing_notes . "\n" : '') . $auditEntry);

        $this->update([
            'status'                => 'open',
            'closed_at'             => null,
            'expected_cash'         => null,
            'actual_cash'           => null,
            'cash_difference'       => null,
            'reconciliation_status' => null,
            'closing_notes'         => $newNotes,
        ]);

        // Hapus pecahan closing sebelumnya agar kasir bisa input ulang saat closing nanti
        $this->denominations()->where('type', 'closing')->delete();
    }
}
