<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductAccurate extends Model implements HasMedia
{
    use InteractsWithMedia;

    // Now using auto-increment ID
    protected $guarded = ['id'];

    protected $casts = [
        'raw_data' => 'array',
        'base_price' => 'decimal:2',
        'base_cost' => 'decimal:2',
        'buy_price' => 'integer',
        'custom_sell_price' => 'integer',
        'has_sn' => 'boolean',
        'is_addon' => 'boolean',
        'business_unit_id' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile();

        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300);
    }

    public function product()
    {
        return $this->hasOneThrough(Product::class, ProductVariant::class, 'product_accurate_id', 'id', 'id', 'product_id');
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class, 'product_accurate_id', 'id');
    }

    public function secondProductVariants()
    {
        return $this->hasMany(SecondProductVariant::class, 'product_accurate_id', 'id');
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function warehouseStocks()
    {
        return $this->morphMany(WarehouseStock::class, 'variant', 'variant_type', 'variant_id');
    }

    public function productSerialNumbers()
    {
        return $this->hasMany(ProductSerialNumber::class, 'product_accurate_id', 'id');
    }

    public function buybackDevice()
    {
        return $this->hasOne(BuybackDevice::class, 'product_accurate_id', 'id');
    }

    /**
     * Self-referencing relationship for polymorphic compatibility with ProductVariant/SecondProductVariant
     */
    public function accurateData()
    {
        return $this->belongsTo(self::class, 'id', 'id');
    }

    /**
     * Accessor fallback so $variant->accurateData always returns self when variant is ProductAccurate
     */
    public function getAccurateDataAttribute()
    {
        return $this;
    }

    /**
     * Hitung harga jual efektif untuk fitur Trade-In berdasarkan pengaturan sumber harga BU Second.
     *
     * @param string $secondPriceSource 'custom_fallback' | 'custom_only' | 'accurate_only'
     * @return float
     */
    public function getEffectiveTradeInSellPrice(string $secondPriceSource = 'custom_fallback'): float
    {
        if ((int) $this->business_unit_id !== 2) {
            return (float) ($this->base_price ?? 0);
        }

        $custom = (float) ($this->custom_sell_price ?? 0);
        $accurate = (float) ($this->base_price ?? 0);

        return match ($secondPriceSource) {
            'custom_only' => $custom > 0 ? $custom : 0.0,
            'accurate_only' => $accurate > 0 ? $accurate : 0.0,
            default => $custom > 0 ? $custom : ($accurate > 0 ? $accurate : 0.0),
        };
    }

    /**
     * Ekstrak atribut Model, RAM/Storage, Warna, dan Nama Tanpa Warna dari nama produk Accurate.
     *
     * Mendukung pola standar Accurate:
     * - "HP INFINIX GT 30 - 8/128GB - BLADE WHITE"
     * - "HP 2ND ASUS ROG PHONE 7 - 12/256GB - PHANTOM BLACK"
     * - "HP 2ND IPHONE 11 - 128GB - BLACK - INTER"
     * Maupun pola tanpa tanda hubung:
     * - "Apple iPhone 13 128GB Black (RESMI)"
     */
    public static function parseTradeInAttributes(?string $rawName, ?string $proyek = null, ?int $businessUnitId = null): array
    {
        $rawName = trim((string) $rawName);
        $normalizedProyek = strtoupper(trim((string) ($proyek ?: 'NON-PROYEK')));

        $prefix = '';
        if (preg_match('/^(HP\s+2ND|HP\s+SECOND)\s+/i', $rawName)) {
            $prefix = 'HP 2ND';
        } elseif (preg_match('/^HP\s+/i', $rawName)) {
            $prefix = 'HP';
        }

        $clean = trim((string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $rawName));

        $ignoredTrailingTokens = array_filter([
            'RESMI',
            'INTER',
            'INTERNASIONAL',
            'BEACUKAI',
            'NON-PROYEK',
            'SECOND',
            '2ND',
            $normalizedProyek !== 'NON-PROYEK' ? $normalizedProyek : null,
        ]);

        $model = $clean;
        $storage = '';
        $color = null;

        if (str_contains($clean, ' - ')) {
            $segments = array_values(array_filter(array_map('trim', explode(' - ', $clean)), fn($s) => $s !== ''));

            $storageIdx = null;
            foreach ($segments as $idx => $seg) {
                if ($idx === 0) {
                    continue;
                }
                if (preg_match('/^(?:\d+\s*\/\s*)?\d+\s*(?:GB|TB)$/i', $seg) || preg_match('/^\d+\s*\/\s*\d+(?:GB|TB)?$/i', $seg)) {
                    $storageIdx = $idx;
                    break;
                }
            }

            if ($storageIdx !== null) {
                $model = implode(' - ', array_slice($segments, 0, $storageIdx));
                $storage = strtoupper((string) preg_replace('/\s+/', '', $segments[$storageIdx]));

                $tailSegments = array_slice($segments, $storageIdx + 1);
                $colorParts = [];
                foreach ($tailSegments as $tail) {
                    $tailUpper = strtoupper(trim($tail, " ()\t\n\r\0\x0B"));
                    if (in_array($tailUpper, $ignoredTrailingTokens, true)) {
                        continue;
                    }
                    $colorParts[] = $tail;
                }
                $color = !empty($colorParts) ? implode(' - ', $colorParts) : null;
            } elseif (count($segments) >= 2) {
                $model = $segments[0];
                $tailSegments = array_slice($segments, 1);
                $colorParts = [];
                foreach ($tailSegments as $tail) {
                    $tailUpper = strtoupper(trim($tail, " ()\t\n\r\0\x0B"));
                    if (in_array($tailUpper, $ignoredTrailingTokens, true)) {
                        continue;
                    }
                    $colorParts[] = $tail;
                }
                $color = !empty($colorParts) ? implode(' - ', $colorParts) : null;
            }
        } else {
            // Format spasi tanpa " - ", misal: "Apple iPhone 13 128GB Black (RESMI)"
            $stripped = (string) preg_replace('/\s*\((?:RESMI|INTER|INTERNASIONAL|BEACUKAI|SECOND|2ND)\)\s*/i', ' ', $clean);
            $stripped = trim((string) preg_replace('/\s+\b(?:RESMI|INTER|INTERNASIONAL|BEACUKAI|SECOND|2ND)\b$/i', '', trim($stripped)));

            if (preg_match('/^(.*?)\s+((?:\d+\/)?\d+\s*(?:GB|TB))(?:\s+(.*))?$/i', $stripped, $matches)) {
                $model = trim($matches[1]);
                $storage = strtoupper((string) preg_replace('/\s+/', '', $matches[2]));
                $afterStorage = trim($matches[3] ?? '');
                if ($afterStorage !== '') {
                    $afterUpper = strtoupper(trim($afterStorage, " ()\t\n\r\0\x0B"));
                    if (!in_array($afterUpper, $ignoredTrailingTokens, true)) {
                        $color = $afterStorage;
                    }
                }
            } else {
                $model = $stripped !== '' ? $stripped : $clean;
            }
        }

        $shortName = $storage !== '' ? "{$model} - {$storage}" : $model;
        $groupedName = $prefix !== '' ? "{$prefix} {$shortName}" : $shortName;

        $buPart = $businessUnitId !== null ? (string) $businessUnitId : '0';
        $modelKey = strtoupper((string) preg_replace('/\s+/', ' ', trim($model)));
        $groupKey = "{$buPart}|{$modelKey}|{$storage}|{$normalizedProyek}";

        return [
            'prefix' => $prefix,
            'model' => $model,
            'storage' => $storage,
            'color' => $color,
            'proyek' => $normalizedProyek,
            'short_name' => $shortName,
            'grouped_name' => $groupedName,
            'group_key' => $groupKey,
        ];
    }

    /**
     * Dapatkan group key tanpa warna (Business Unit + Model + Storage + Proyek).
     */
    public function getTradeInGroupKey(): string
    {
        return self::parseTradeInAttributes($this->name, $this->proyek, $this->business_unit_id)['group_key'];
    }
}
