<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAccurateSyncLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload_snapshot' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /**
     * Kategorisasi error otomatis dari pesan exception/API Accurate
     */
    public static function categorizeError(?string $message): string
    {
        if (empty($message)) {
            return 'UNKNOWN';
        }

        $lower = strtolower($message);

        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout') || str_contains($lower, 'curl error 28') || str_contains($lower, 'could not resolve host') || str_contains($lower, 'connection reset') || str_contains($lower, '504 gateway')) {
            return 'NETWORK_TIMEOUT';
        }

        if (str_contains($lower, 'stok') || str_contains($lower, 'kuantitas') || str_contains($lower, 'tidak mencukupi') || str_contains($lower, 'tidak cukup') || str_contains($lower, 'minus') || str_contains($lower, 'insufficient')) {
            return 'INSUFFICIENT_STOCK';
        }

        if (str_contains($lower, 'item') || str_contains($lower, 'barang') || str_contains($lower, 'sku') || str_contains($lower, 'item-unknown')) {
            return 'ITEM_NOT_FOUND';
        }

        if (str_contains($lower, 'akun') || str_contains($lower, 'bank') || str_contains($lower, 'kas') || str_contains($lower, 'coa')) {
            return 'ACCOUNT_NOT_FOUND';
        }

        if (str_contains($lower, 'token') || str_contains($lower, 'unauthorized') || str_contains($lower, 'secret key') || str_contains($lower, 'akses ditolak')) {
            return 'AUTH_ERROR';
        }

        if (str_contains($lower, 'sudah ada') || str_contains($lower, 'terdaftar') || str_contains($lower, 'duplicate')) {
            return 'DUPLICATE_DOC';
        }

        return 'VALIDATION_ERROR';
    }

    /**
     * Label representatif untuk kategori error
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->error_category) {
            'NETWORK_TIMEOUT' => 'Jaringan / Timeout',
            'INSUFFICIENT_STOCK' => 'Stok Accurate Kurang',
            'ITEM_NOT_FOUND' => 'Barang / SKU Salah',
            'ACCOUNT_NOT_FOUND' => 'Akun Kas/Bank Salah',
            'AUTH_ERROR' => 'Otentikasi / Token Accurate',
            'DUPLICATE_DOC' => 'Dokumen Ganda',
            'VALIDATION_ERROR' => 'Kendala Validasi Accurate',
            default => 'Lainnya / Umum',
        };
    }
}

