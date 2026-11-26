<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    // Konversi kolom JSON menjadi array otomatis di Laravel
    protected $casts = [
        'shipping_address_snapshot' => 'array',
        'order_date' => 'date',
        'payment_expired_at' => 'datetime',
        'coupon_discount_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            if ($order->wasChanged('order_status')) {
                $status = strtoupper((string) $order->order_status);

                if ($status === 'CANCELLED') {
                    try {
                        app(\App\Services\LoyaltyService::class)->refundPoints($order);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Loyalty refundPoints listener error on Order #{$order->order_number}: " . $e->getMessage());
                    }

                    try {
                        app(\App\Services\EcommerceCouponService::class)->rollbackCoupon($order);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Coupon rollbackCoupon listener error on Order #{$order->order_number}: " . $e->getMessage());
                    }
                } elseif (in_array($status, ['PAID', 'COMPLETED', 'PROCESSING', 'SHIPPED'], true)) {
                    try {
                        app(\App\Services\LoyaltyService::class)->earnPoints($order);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Loyalty earnPoints listener error on Order #{$order->order_number}: " . $e->getMessage());
                    }
                }
            }
        });
    }

    // Accessor untuk menggantikan kolom legacy mdr_amount
    public function getMdrAmountAttribute()
    {
        return $this->payments->sum(function ($payment) {
            $rate = $payment->paymentMethodRate;
            $pct = $rate ? $rate->mdr_percentage : ($payment->paymentMethod->mdr_percentage ?? 0);
            return round($payment->amount * $pct / 100);
        });
    }

    /**
     * Format tanggal transaksi untuk struk (Modal, PDF, Thermal ESC/POS).
     * Mengutamakan order_date (tanggal pelunasan / transaksi efektif) agar sinkron
     * dengan Laporan Penjualan & Accurate, bukan sekadar created_at (pembuatan SO/Draft).
     */
    public function getReceiptDateFormattedAttribute(): string
    {
        $baseDate = $this->order_date
            ? \Carbon\Carbon::parse($this->order_date)
            : ($this->created_at ? $this->created_at->copy() : now());

        $dateStr = $baseDate->format('Y-m-d');
        $timeStr = null;

        // 1. Cek jam dari pembayaran pelunasan terakhir yang tanggalnya sama dengan order_date
        if ($this->relationLoaded('payments') || $this->exists) {
            $latestPaid = $this->payments
                ->filter(fn($p) => !empty($p->paid_at))
                ->sortByDesc('paid_at')
                ->first();

            if ($latestPaid) {
                $paidAt = \Carbon\Carbon::parse($latestPaid->paid_at);
                if ($paidAt->format('Y-m-d') === $dateStr) {
                    $timeStr = $paidAt->format('H:i');
                }
            }
        }

        // 2. Jika tidak ada, cek jam dari updated_at jika di tanggal yang sama (misal pelunasan SO/Draft)
        if (!$timeStr && $this->updated_at && $this->updated_at->format('Y-m-d') === $dateStr) {
            $timeStr = $this->updated_at->format('H:i');
        }

        // 3. Jika tidak ada, cek jam dari created_at jika di tanggal yang sama (POS normal)
        if (!$timeStr && $this->created_at && $this->created_at->format('Y-m-d') === $dateStr) {
            $timeStr = $this->created_at->format('H:i');
        }

        // 4. Jika transaksi backdate murni (tanggal berbeda dari created_at & updated_at), tampilkan tanggal saja
        return $timeStr
            ? $baseDate->format('d/m/Y') . ' ' . $timeStr
            : $baseDate->format('d/m/Y');
    }

    /**
     * Mengembalikan tanggal pembuatan SO (d/m/Y) apabila order berasal dari SO
     * dan dilunasi pada tanggal berbeda dari tanggal pembuatan SO.
     */
    public function getSoCreatedDateLabelAttribute(): ?string
    {
        $isSo = strtoupper((string) $this->order_channel) === 'SO'
            || str_starts_with((string) $this->order_number, 'SO-');

        if (!$isSo || !$this->created_at || !$this->order_date) {
            return null;
        }

        $orderDateStr = \Carbon\Carbon::parse($this->order_date)->format('Y-m-d');
        $createdDateStr = $this->created_at->format('Y-m-d');

        if ($orderDateStr !== $createdDateStr) {
            return $this->created_at->format('d/m/Y');
        }

        return null;
    }

    public function getMdrExpenseDetails()
    {
        $expenses = [];

        foreach ($this->payments as $payment) {
            $rate = $payment->paymentMethodRate;
            $pct = $rate ? $rate->mdr_percentage : ($payment->paymentMethod->mdr_percentage ?? 0);
            $rowMdr = $pct > 0 ? round($payment->amount * $pct / 100) : 0;

            if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                $expenses[] = [
                    'accountNo' => $rate->accurate_account_no,
                    'expenseAmount' => -abs((float)$rowMdr),
                    'expenseNotes' => 'MDR ' . ($rate->name ?? ' ')
                ];
            }
        }

        return $expenses;
    }

    // ─── Relationships ─────────────────────────────────────────

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'order_promos')->withPivot('discount_applied')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->items();
    }

    public function payments()
    {
        // hasMany karena user bisa saja mencoba bayar berulang kali jika gagal
        return $this->hasMany(OrderPayment::class);
    }

    public function accurateDocs()
    {
        return $this->hasMany(OrderAccurateDoc::class);
    }

    public function accurateSyncLogs()
    {
        return $this->hasMany(OrderAccurateSyncLog::class)->latest();
    }

    public function latestAccurateSyncLog()
    {
        return $this->hasOne(OrderAccurateSyncLog::class)->latestOfMany();
    }

    public function resetLogs()
    {
        return $this->hasMany(OrderResetLog::class);
    }

    public function salesLogs()
    {
        return $this->hasMany(OrderSalesLog::class)->latest();
    }

    public function issues()
    {
        return $this->hasMany(OrderIssue::class)->latest();
    }

    public function openIssues()
    {
        return $this->hasMany(OrderIssue::class)->where('status', 'OPEN');
    }

    public function shipping()
    {
        // hasOne karena 1 order biasanya 1 pengiriman
        return $this->hasOne(OrderShipping::class);
    }

    public function cashSettlement()
    {
        return $this->hasOne(OrderCashSettlement::class);
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
    public function salesBy()
    {
        return $this->belongsTo(Employe::class, 'sales_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentMethodRate()
    {
        return $this->belongsTo(PaymentMethodRate::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function coupon()
    {
        return $this->belongsTo(EcommerceCoupon::class, 'coupon_id');
    }

    public function couponRedemption()
    {
        return $this->hasOne(EcommerceCouponRedemption::class, 'order_id');
    }


    // ─── Scopes ────────────────────────────────────────────────

    public function scopePos($query)
    {
        return $query->where('order_channel', 'POS');
    }

    public function scopeOnline($query)
    {
        return $query->where('order_channel', 'ONLINE');
    }

    public function scopeMobile($query)
    {
        return $query->where('order_channel', 'MOBILE_APP');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function isPosOrder(): bool
    {
        return $this->order_channel === 'POS';
    }

    public function isMobileAppOrder(): bool
    {
        return $this->order_channel === 'MOBILE_APP';
    }

    public function isWaitingPayment(): bool
    {
        return $this->order_status === 'WAITING_PAYMENT';
    }

    public function isWaitingVerification(): bool
    {
        return $this->order_status === 'WAITING_VERIFICATION';
    }

    public function messageLogs()
    {
        return $this->morphMany(MessageLog::class, 'source')->latest();
    }

    public function customerDeposits()
    {
        return $this->hasMany(CustomerDeposit::class, 'origin_order_id');
    }
}
