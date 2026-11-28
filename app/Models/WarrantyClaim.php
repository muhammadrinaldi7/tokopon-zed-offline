<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected ?OrderItem $resolvedReturnedOrderItem = null;
    protected bool $hasResolvedReturnedOrderItem = false;
    protected ?ProductSerialNumber $resolvedReturnedSnRecord = null;
    protected bool $hasResolvedReturnedSnRecord = false;

    protected $casts = [
        'claimed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Cek apakah string serial_number pada OrderItem cocok dengan serial_number klaim ini.
     */
    private function itemMatchesClaimSerial(?OrderItem $item): bool
    {
        if (!$item || empty($this->serial_number) || empty($item->serial_number)) {
            return false;
        }

        $claimSn = strtolower(trim((string) $this->serial_number));
        $itemSns = array_map(
            fn($s) => strtolower(trim($s)),
            preg_split('/[\s,;]+/', (string) $item->serial_number) ?: []
        );

        return in_array($claimSn, $itemSns, true);
    }

    /**
     * Mengambil OrderItem ASLI dari unit yang diretur berdasarkan serial_number klaim.
     * Mencegah tampilan tertukar dengan produk pengganti saat warranty->order_item_id
     * sudah dipindahkan ke unit baru setelah proses replacement/upgrade/downgrade.
     */
    public function getReturnedOrderItemAttribute(): ?OrderItem
    {
        if ($this->hasResolvedReturnedOrderItem) {
            return $this->resolvedReturnedOrderItem;
        }

        $this->hasResolvedReturnedOrderItem = true;
        $warrantyItem = $this->warranty?->orderItem;

        // 1. Jika orderItem pada warranty masih cocok dengan SN yang diklaim, langsung pakai
        if ($this->itemMatchesClaimSerial($warrantyItem)) {
            $this->resolvedReturnedOrderItem = $warrantyItem;
            return $this->resolvedReturnedOrderItem;
        }

        // 2. Cari OrderItem penjualan asli berdasarkan serial_number klaim (abaikan order retur RET-%)
        $sn = trim((string) $this->serial_number);
        if ($sn !== '') {
            $baseQuery = OrderItem::with([
                'variant',
                'order.branch',
                'order.businessUnit',
                'order.salesBy',
                'promos',
            ])->whereHas('order', function ($oq) {
                $oq->where('order_number', 'not like', 'RET-%')
                   ->where('grand_total', '>=', 0);
            });

            // Utamakan exact match
            $matched = (clone $baseQuery)
                ->where('serial_number', $sn)
                ->when($this->claimed_at, fn($q) => $q->where('created_at', '<=', $this->claimed_at->copy()->addMinutes(5)))
                ->orderByDesc('id')
                ->first();

            // Fallback exact match tanpa batas tanggal (jika backdate)
            if (!$matched) {
                $matched = (clone $baseQuery)
                    ->where('serial_number', $sn)
                    ->orderByDesc('id')
                    ->first();
            }

            // Fallback LIKE jika 1 OrderItem memuat beberapa SN dipisah koma
            if (!$matched) {
                $matched = (clone $baseQuery)
                    ->where('serial_number', 'like', '%' . $sn . '%')
                    ->orderByDesc('id')
                    ->first();
            }

            if ($matched) {
                $this->resolvedReturnedOrderItem = $matched;
                return $this->resolvedReturnedOrderItem;
            }
        }

        // 3. Fallback terakhir ke warranty->orderItem
        $this->resolvedReturnedOrderItem = $warrantyItem;
        return $this->resolvedReturnedOrderItem;
    }

    private function getReturnedSnRecord(): ?ProductSerialNumber
    {
        if ($this->hasResolvedReturnedSnRecord) {
            return $this->resolvedReturnedSnRecord;
        }

        $this->hasResolvedReturnedSnRecord = true;
        $sn = trim((string) $this->serial_number);
        if ($sn !== '') {
            $this->resolvedReturnedSnRecord = ProductSerialNumber::with('productAccurate')
                ->where('serial_number', $sn)
                ->first();
        }

        return $this->resolvedReturnedSnRecord;
    }

    /**
     * Nama produk ASLI yang diretur sesuai serial_number klaim.
     */
    public function getReturnedProductNameAttribute(): string
    {
        $item = $this->returned_order_item;

        if ($this->itemMatchesClaimSerial($item)) {
            return $item->product_name ?? ($item->variant?->name ?? 'Produk Tidak Diketahui');
        }

        // Jika OrderItem fallback tidak cocok SN-nya, cek master ProductSerialNumber berdasarkan SN klaim
        $snRec = $this->getReturnedSnRecord();
        if ($snRec && !empty($snRec->product_name)) {
            return $snRec->product_name;
        }

        return $item?->product_name ?? ($item?->variant?->name ?? 'Produk Tidak Diketahui');
    }

    /**
     * Subkategori / Proyek dari produk ASLI yang diretur.
     */
    public function getReturnedSubCategoryAttribute(): ?string
    {
        $item = $this->returned_order_item;

        if ($this->itemMatchesClaimSerial($item)) {
            $variant = $item?->variant;
            return $variant?->proyek ?? ($variant?->accurateData?->proyek ?? null);
        }

        $snRec = $this->getReturnedSnRecord();
        if ($snRec && !empty($snRec->productAccurate?->proyek)) {
            return $snRec->productAccurate->proyek;
        }

        $variant = $item?->variant;
        return $variant?->proyek ?? ($variant?->accurateData?->proyek ?? null);
    }

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

    public function inspection()
    {
        return $this->morphOne(DeviceInspection::class, 'inspectable');
    }

    public function claimsHistory()
    {
        return $this->hasMany(WarrantyClaimHistory::class, 'claim_id');
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
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

