<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockOpname extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_system_qty' => 'integer',
        'total_physical_qty' => 'integer',
        'total_difference_qty' => 'integer',
        'total_loss_value' => 'decimal:2',
        'total_surplus_value' => 'decimal:2',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function serials()
    {
        return $this->hasMany(StockOpnameSerial::class);
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function latestApprovalRequest()
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    /**
     * Label cakupan audit yang ramah pengguna
     */
    public function getScopeLabelAttribute(): string
    {
        return match ($this->type) {
            'SERIALIZED_ONLY'     => 'Khusus Unit HP (IMEI)',
            'NON_SERIALIZED_ONLY' => 'Khusus Aksesoris',
            'BRAND'               => 'Per Brand: ' . ($this->brand_filter ?: 'Semua Brand'),
            'PROYEK'              => 'Per Proyek: ' . ($this->project_filter ?: 'Semua Proyek'),
            'CATEGORY'            => 'Per Kategori: ' . ($this->category_filter ?: 'Semua Kategori'),
            default               => 'Semua Produk (Full Audit)',
        };
    }

    /**
     * Ringkasan kontributor personil BM yang melakukan scan
     */
    public function getScannersSummary(): \Illuminate\Support\Collection
    {
        return $this->serials()
            ->whereNotNull('scanned_by')
            ->with('scannedByUser')
            ->get()
            ->groupBy('scanned_by')
            ->map(function ($serials, $userId) {
                $user = $serials->first()->scannedByUser;
                return [
                    'user_id'    => (int) $userId,
                    'user_name'  => $user ? $user->name : "User #{$userId}",
                    'matched'    => $serials->where('status', 'MATCHED')->count(),
                    'unexpected' => $serials->where('status', 'UNEXPECTED')->count(),
                    'total'      => $serials->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Hitung ulang seluruh total kuantitas dan nilai selisih (HPP)
     */
    public function calculateTotals(): void
    {
        $items = $this->items()->get();

        $totalSystem = 0;
        $totalPhysical = 0;
        $totalLoss = 0;
        $totalSurplus = 0;

        foreach ($items as $item) {
            $totalSystem += $item->system_qty;
            $totalPhysical += $item->physical_qty;

            $diffValue = $item->difference_value;
            if ($diffValue < 0) {
                $totalLoss += abs($diffValue);
            } elseif ($diffValue > 0) {
                $totalSurplus += $diffValue;
            }
        }

        $this->update([
            'total_system_qty'     => $totalSystem,
            'total_physical_qty'   => $totalPhysical,
            'total_difference_qty' => $totalPhysical - $totalSystem,
            'total_loss_value'     => $totalLoss,
            'total_surplus_value'  => $totalSurplus,
        ]);
    }

    /**
     * Generate format nomor Opname otomatis
     * Contoh: SO-SYH-KYT-20260908-001
     */
    public static function generateOpnameNumber(int $businessUnitId, int $branchId): string
    {
        $bu = BusinessUnit::find($businessUnitId);
        $buCode = $bu ? strtoupper(substr($bu->code ?? $bu->name, 0, 3)) : 'BU';

        $branch = Branch::find($branchId);
        $branchCode = 'CAB';
        if ($branch) {
            // Bersihkan nama cabang dan ambil singkatan
            $cleanName = str_ireplace(['gsk', 'syihab', '-', ' '], '', $branch->name);
            $branchCode = strtoupper(substr($cleanName, 0, 3));
            if (empty($branchCode)) {
                $branchCode = 'BR' . $branch->id;
            }
        }

        $dateStr = now()->format('Ymd');
        $prefix = "SO-{$buCode}-{$branchCode}-{$dateStr}";

        $lastSo = self::where('opname_number', 'like', "{$prefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastSo) {
            $parts = explode('-', $lastSo->opname_number);
            $lastSeq = (int) end($parts);
            $sequence = $lastSeq + 1;
        }

        return sprintf("%s-%03d", $prefix, $sequence);
    }
}
