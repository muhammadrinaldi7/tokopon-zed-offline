<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BusinessUnit extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'code',
        'customer_prefix',
        'prefix',
        'order_prefix',
        'draft_prefix',
        'store_title',
        'receipt_show_discount',
        'requires_inbound_qc',
        'name',
        'accurate_host',
        'accurate_token',
        'accurate_secret_key',
        'accurate_database_id',
        'accurate_return_warehouse_id',
        'accurate_return_warehouse_name',
        'is_active',
        'is_visible_mobile',
        'mobile_display_name',
        'mobile_category',
        'mobile_description',
        'brand_color',
        'warranty_highlight',
        'telegram_approval_webhook',
        'telegram_log_webhook',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'is_visible_mobile' => 'boolean',
        'receipt_show_discount' => 'boolean',
        'requires_inbound_qc' => 'boolean',
    ];

    /**
     * Ambil singkatan / tag Unit Bisnis dari Prefix Global atau Code.
     * Contoh: 'SYB', 'GSK', 'DST'
     */
    public function getBuTag(): string
    {
        if (!empty($this->prefix)) {
            return strtoupper(trim($this->prefix));
        }

        return match (strtolower(trim((string) $this->code))) {
            'second', 'gsk' => 'GSK',
            'syihab', 'syb' => 'SYB',
            default => strtoupper(trim((string) $this->code)),
        };
    }

    /**
     * Ambil awalan kode Vendor Accurate secara dinamis.
     * Contoh: 'SYB_', 'GSK_'
     */
    public function getVendorPrefix(): string
    {
        if (!empty($this->customer_prefix)) {
            return strtoupper(trim($this->customer_prefix));
        }

        return $this->getBuTag() . '_';
    }

    /**
     * Resolusi nama cabang untuk dikirim ke Accurate berdasarkan business_unit_id & Prefix Global.
     */
    public function resolveAccurateBranchName(string $branchName): string
    {
        $branchName = trim($branchName);
        if ($branchName === '') {
            return 'Banjarbaru';
        }

        // 1. Prioritas Utama: Exact match di tabel branches milik business_unit_id ini
        $exactBranch = $this->branches()
            ->where('name', $branchName)
            ->first();

        if ($exactBranch) {
            return $exactBranch->name;
        }

        // Bersihkan kemungkinan awalan tag jika nama cabang berasal dari pencocokan parsial
        $tag = $this->getBuTag();
        $cleanName = $tag !== ''
            ? trim(preg_replace('/^' . preg_quote($tag, '/') . '\s*[-\s]\s*/i', '', $branchName))
            : $branchName;

        // 2. Cari di tabel branches milik business_unit_id ini dengan kandidat nama ber-prefix atau parsial
        $buBranch = $this->branches()
            ->where(function ($q) use ($branchName, $cleanName, $tag) {
                $q->where('name', $cleanName);
                if ($tag !== '') {
                    $q->orWhere('name', "{$tag} - {$cleanName}")
                      ->orWhere('name', "{$tag} {$cleanName}");
                }
                $q->orWhere('name', 'like', "%{$cleanName}%");
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 WHEN name = ? THEN 1 WHEN name = ? THEN 2 ELSE 3 END', [
                $branchName,
                $tag !== '' ? "{$tag} - {$cleanName}" : $cleanName,
                $tag !== '' ? "{$tag} {$cleanName}" : $cleanName,
            ])
            ->first();

        if ($buBranch) {
            return $buBranch->name;
        }

        // 3. Fallback ke pola penamaan gudang unit bisnis ini (deteksi apakah memakai "TAG - Kota" atau "TAG Kota")
        if ($tag !== '' && !str_starts_with(strtoupper($branchName), $tag)) {
            $usesHyphenPrefix = $this->warehouses()
                ->where('name', 'like', "{$tag} - %")
                ->exists();

            if ($usesHyphenPrefix) {
                return "{$tag} - {$cleanName}";
            }

            $usesSpacePrefix = $this->warehouses()
                ->where('name', 'like', "{$tag} %")
                ->exists();

            if ($usesSpacePrefix) {
                return "{$tag} {$cleanName}";
            }
        }

        return $branchName;
    }

    /**
     * Mencocokkan nama gudang dari Webhook Accurate ke tabel warehouses lokal berdasarkan business_unit_id.
     */
    public function findLocalWarehouse(?string $accurateWarehouseName): ?Warehouse
    {
        $accurateWarehouseName = trim((string) $accurateWarehouseName);
        if ($accurateWarehouseName === '') {
            return null;
        }

        // 1. Prioritas Utama: Exact match pada business_unit_id ini
        $warehouse = $this->warehouses()
            ->where('name', $accurateWarehouseName)
            ->first();
        if ($warehouse) {
            return $warehouse;
        }

        // 2. Prioritas Kedua: Match dengan mengabaikan/menambahkan Prefix Global (misal 'GSK - ') pada BU ini
        $tag = $this->getBuTag();
        if ($tag !== '') {
            $cleanLocation = trim((string) preg_replace('/^' . preg_quote($tag, '/') . '\s*[-_]?\s*/i', '', $accurateWarehouseName));

            if ($cleanLocation !== '') {
                $warehouse = $this->warehouses()
                    ->where(function ($q) use ($cleanLocation, $tag) {
                        $q->where('name', $cleanLocation)
                            ->orWhere('name', "{$tag} - {$cleanLocation}")
                            ->orWhere('name', "{$tag} {$cleanLocation}");
                    })
                    ->first();
                if ($warehouse) {
                    return $warehouse;
                }
            }
        }

        // 3. Fallback global exact match jika gudang belum di-set business_unit_id-nya
        return Warehouse::where('name', $accurateWarehouseName)->first();
    }

    public function scopeVisibleMobile($query)
    {
        return $query->where('is_active', true)->where('is_visible_mobile', true);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function businessUnitProjects()
    {
        return $this->hasMany(BusinessUnitProject::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->hasMedia('logo') ? $this->getFirstMediaUrl('logo') : null;
    }
}
