<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductAccurate;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTradeInController extends Controller
{
    public function __construct(protected SettingService $settingService)
    {
    }

    /**
     * Get distinct list of available brands filtered by context (new, second, or old)
     */
    public function getBrands(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all'); // 'new', 'second', 'old', or 'all'
        $secondPriceSource = (string) $this->settingService->get('tradein_second_price_source', 'custom_fallback');
        $excludeAccessories = (bool) $this->settingService->get('tradein_exclude_accessories', true);

        $query = ProductAccurate::query()
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '');

        $this->applyExcludeAccessories($query, $excludeAccessories);

        if ($type === 'new') {
            // HP Baru: BU 1 (Syihab) & Kategori Handphone
            $query->where('business_unit_id', 1)
                ->where('categoryName', 'like', '%Handphone%')
                ->where('base_price', '>', 0);
        } elseif ($type === 'second') {
            // HP Second: BU 2 (GSK Second) & Kategori HP SECOND & Khusus APPLE
            $query->where('business_unit_id', 2)
                ->where('categoryName', 'like', '%HP SECOND%')
                ->whereRaw('UPPER(brandName) = ?', ['APPLE']);

            $this->applySecondSellPriceFilter($query, $secondPriceSource);
        } elseif ($type === 'old') {
            // HP Lama: Smartphone yang memiliki buy_price > 0 ATAU base_price > 0
            $query->where(function ($q) {
                $q->where('buy_price', '>', 0)
                    ->orWhere('base_price', '>', 0);
            })->where(function ($q) {
                $q->where('categoryName', 'like', '%Handphone%')
                    ->orWhere('categoryName', 'like', '%HP SECOND%')
                    ->orWhere('name', 'like', '%HP 2ND%');
            });
        } else {
            // All: Smartphone (BU 1 Handphone ATAU BU 2 HP SECOND)
            $query->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('business_unit_id', 1)
                        ->where('categoryName', 'like', '%Handphone%');
                })->orWhere(function ($q2) {
                    $q2->where('business_unit_id', 2)
                        ->where('categoryName', 'like', '%HP SECOND%');
                })->orWhere('name', 'like', '%HP 2ND%');
            });
        }

        $brands = $query->distinct()
            ->pluck('brandName')
            ->sort()
            ->values();

        return response()->json([
            'status' => 'success',
            'type' => $type,
            'data' => $brands,
        ]);
    }

    /**
     * Get list of old devices available for trade-in / buyback
     */
    public function getOldDevices(Request $request): JsonResponse
    {
        $brand = $request->query('brand');
        $proyek = $request->query('proyek');
        $search = $request->query('search');
        $limit = min((int) ($request->query('limit', 5000)), 10000);

        $isGrouped = $request->has('grouped')
            ? filter_var($request->query('grouped'), FILTER_VALIDATE_BOOLEAN)
            : (bool) $this->settingService->get('tradein_group_old_devices', true);

        $priceStrategy = (string) $this->settingService->get('tradein_old_price_strategy', 'min');
        $excludeAccessories = (bool) $this->settingService->get('tradein_exclude_accessories', true);

        $query = ProductAccurate::query()
            ->where(function ($q) {
                $q->where('buy_price', '>', 0)
                    ->orWhere('base_price', '>', 0);
            })
            ->where(function ($q) {
                $q->where('categoryName', 'like', '%Handphone%')
                    ->orWhere('categoryName', 'like', '%HP SECOND%')
                    ->orWhere('name', 'like', '%HP 2ND%');
            })
            ->when($brand, function ($q) use ($brand) {
                $q->where('brandName', $brand);
            })
            ->when($proyek, function ($q) use ($proyek) {
                $q->where('proyek', $proyek);
            });

        $this->applyExcludeAccessories($query, $excludeAccessories);
        $query = $this->applySmartSearch($query, $search);

        $rawDevices = $query->orderBy('name')
            ->take($limit)
            ->get([
                'id',
                'item_no',
                'name',
                'brandName',
                'categoryName',
                'proyek',
                'buy_price',
                'base_price',
                'business_unit_id',
            ]);

        if ($isGrouped) {
            $devices = $rawDevices
                ->map(function (ProductAccurate $device) {
                    $effectiveBuyPrice = (float) ($device->buy_price > 0 ? $device->buy_price : ($device->base_price ?? 0));
                    $attrs = ProductAccurate::parseTradeInAttributes($device->name, $device->proyek, $device->business_unit_id);
                    return [
                        'model_instance' => $device,
                        'effective_buy_price' => $effectiveBuyPrice,
                        'attrs' => $attrs,
                    ];
                })
                ->filter(fn($item) => $item['effective_buy_price'] > 0)
                ->groupBy(fn($item) => $item['attrs']['group_key'])
                ->map(function ($group) use ($priceStrategy) {
                    $sorted = $priceStrategy === 'max'
                        ? $group->sortByDesc('effective_buy_price')
                        : $group->sortBy('effective_buy_price');

                    $chosen = $sorted->first();
                    /** @var ProductAccurate $rep */
                    $rep = $chosen['model_instance'];
                    $attrs = $chosen['attrs'];
                    $effectiveBuyPrice = (float) $chosen['effective_buy_price'];

                    $colors = $group->pluck('attrs.color')
                        ->filter(fn($c) => $c !== null && $c !== '')
                        ->unique(fn($c) => strtoupper($c))
                        ->values()
                        ->all();

                    $shouldUseColorlessName = ($attrs['color'] !== null) || ($group->count() > 1);
                    $displayName = $shouldUseColorlessName ? $attrs['grouped_name'] : $rep->name;
                    $cleanName = $shouldUseColorlessName
                        ? $attrs['short_name']
                        : (string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $rep->name);

                    $isSecond = ($rep->business_unit_id == 2)
                        || (stripos((string) $rep->categoryName, 'SECOND') !== false)
                        || (stripos((string) $rep->name, '2ND') !== false);

                    return [
                        'id' => $rep->id,
                        'item_no' => $rep->item_no,
                        'name' => $displayName,
                        'clean_name' => $cleanName,
                        'model' => $attrs['model'],
                        'storage' => $attrs['storage'],
                        'brand' => $rep->brandName,
                        'category' => $rep->categoryName,
                        'proyek' => $rep->proyek ?? 'NON-PROYEK',
                        'is_second' => $isSecond,
                        'condition_label' => $isSecond ? 'HP Second (2ND)' : 'Reguler',
                        'variant_count' => $group->count(),
                        'available_colors' => $colors,
                        'buy_price' => $effectiveBuyPrice,
                        'formatted_buy_price' => 'Rp ' . number_format($effectiveBuyPrice, 0, ',', '.'),
                    ];
                })
                ->values();
        } else {
            $devices = $rawDevices->map(function (ProductAccurate $device) {
                $effectiveBuyPrice = (float) ($device->buy_price > 0 ? $device->buy_price : ($device->base_price ?? 0));
                $attrs = ProductAccurate::parseTradeInAttributes($device->name, $device->proyek, $device->business_unit_id);
                $cleanName = (string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $device->name);
                $isSecond = ($device->business_unit_id == 2)
                    || (stripos((string) $device->categoryName, 'SECOND') !== false)
                    || (stripos((string) $device->name, '2ND') !== false);

                return [
                    'id' => $device->id,
                    'item_no' => $device->item_no,
                    'name' => $device->name,
                    'clean_name' => $cleanName,
                    'model' => $attrs['model'],
                    'storage' => $attrs['storage'],
                    'brand' => $device->brandName,
                    'category' => $device->categoryName,
                    'proyek' => $device->proyek ?? 'NON-PROYEK',
                    'is_second' => $isSecond,
                    'condition_label' => $isSecond ? 'HP Second (2ND)' : 'Reguler',
                    'variant_count' => 1,
                    'available_colors' => $attrs['color'] ? [$attrs['color']] : [],
                    'buy_price' => $effectiveBuyPrice,
                    'formatted_buy_price' => 'Rp ' . number_format($effectiveBuyPrice, 0, ',', '.'),
                ];
            })->filter(fn($item) => $item['buy_price'] > 0)->values();
        }

        return response()->json([
            'status' => 'success',
            'grouped' => $isGrouped,
            'total' => $devices->count(),
            'data' => $devices,
        ]);
    }

    /**
     * Get list of target devices for trade-in:
     * - target_type = 'new'    -> BU 1 (Syihab), Category 'Handphone', base_price > 0
     * - target_type = 'second' -> BU 2 (GSK Second), Category 'HP SECOND', price per dynamic setting
     */
    public function getTargetDevices(Request $request): JsonResponse
    {
        $targetType = $request->query('target_type', 'new'); // 'new' or 'second'
        $brand = $request->query('brand');
        $proyek = $request->query('proyek');
        $search = $request->query('search');
        $limit = min((int) ($request->query('limit', 5000)), 10000);

        $isGrouped = $request->has('grouped')
            ? filter_var($request->query('grouped'), FILTER_VALIDATE_BOOLEAN)
            : (bool) $this->settingService->get('tradein_group_target_devices', true);

        $secondPriceSource = (string) $this->settingService->get('tradein_second_price_source', 'custom_fallback');
        $priceStrategy = (string) $this->settingService->get('tradein_target_price_strategy', 'min');
        $excludeAccessories = (bool) $this->settingService->get('tradein_exclude_accessories', true);

        $query = ProductAccurate::query();

        if ($targetType === 'second') {
            // HP Second: BU 2 & Kategori HP SECOND & Khusus APPLE
            $query->where('business_unit_id', 2)
                ->where('categoryName', 'like', '%HP SECOND%')
                ->whereRaw('UPPER(brandName) = ?', ['APPLE']);

            $this->applySecondSellPriceFilter($query, $secondPriceSource);
        } else {
            // HP Baru: BU 1 & Kategori Handphone
            $query->where('business_unit_id', 1)
                ->where('categoryName', 'like', '%Handphone%')
                ->where('base_price', '>', 0);
        }

        $this->applyExcludeAccessories($query, $excludeAccessories);

        $query->when($brand, function ($q) use ($brand) {
            $q->where('brandName', $brand);
        })
            ->where(function ($q) {
                $q->whereNull('proyek')
                    ->orWhere('proyek', '!=', 'BEACUKAI');
            })
            ->when($proyek, function ($q) use ($proyek) {
                $q->where('proyek', $proyek);
            });

        $query = $this->applySmartSearch($query, $search);

        $rawDevices = $query->orderBy('name')
            ->take($limit)
            ->get([
                'id',
                'item_no',
                'name',
                'brandName',
                'categoryName',
                'proyek',
                'base_price',
                'custom_sell_price',
                'business_unit_id',
            ]);

        if ($isGrouped) {
            $devices = $rawDevices
                ->map(function (ProductAccurate $device) use ($secondPriceSource) {
                    $effectivePrice = $device->getEffectiveTradeInSellPrice($secondPriceSource);
                    $attrs = ProductAccurate::parseTradeInAttributes($device->name, $device->proyek, $device->business_unit_id);
                    $isUsingCustom = ($device->business_unit_id == 2)
                        && $secondPriceSource !== 'accurate_only'
                        && (($device->custom_sell_price ?? 0) > 0);

                    return [
                        'model_instance' => $device,
                        'effective_price' => $effectivePrice,
                        'price_source' => $isUsingCustom ? 'custom' : 'accurate',
                        'attrs' => $attrs,
                    ];
                })
                ->filter(fn($item) => $item['effective_price'] > 0)
                ->groupBy(fn($item) => $item['attrs']['group_key'])
                ->map(function ($group) use ($targetType, $priceStrategy) {
                    $sorted = $priceStrategy === 'max'
                        ? $group->sortByDesc('effective_price')
                        : $group->sortBy('effective_price');

                    $chosen = $sorted->first();
                    /** @var ProductAccurate $rep */
                    $rep = $chosen['model_instance'];
                    $attrs = $chosen['attrs'];
                    $effectivePrice = (float) $chosen['effective_price'];

                    $colors = $group->pluck('attrs.color')
                        ->filter(fn($c) => $c !== null && $c !== '')
                        ->unique(fn($c) => strtoupper($c))
                        ->values()
                        ->all();

                    $shouldUseColorlessName = ($attrs['color'] !== null) || ($group->count() > 1);
                    $displayName = $shouldUseColorlessName ? $attrs['grouped_name'] : $rep->name;
                    $cleanName = $shouldUseColorlessName
                        ? $attrs['short_name']
                        : (string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $rep->name);

                    return [
                        'id' => $rep->id,
                        'item_no' => $rep->item_no,
                        'name' => $displayName,
                        'clean_name' => $cleanName,
                        'model' => $attrs['model'],
                        'storage' => $attrs['storage'],
                        'brand' => $rep->brandName,
                        'category' => $rep->categoryName,
                        'proyek' => $rep->proyek ?? 'NON-PROYEK',
                        'target_type' => $targetType,
                        'condition_label' => $targetType === 'second' ? 'Second / Bekas' : 'Baru / Segel',
                        'variant_count' => $group->count(),
                        'available_colors' => $colors,
                        'price_source' => $chosen['price_source'],
                        'base_price' => $effectivePrice,
                        'formatted_base_price' => 'Rp ' . number_format($effectivePrice, 0, ',', '.'),
                    ];
                })
                ->values();
        } else {
            $devices = $rawDevices->map(function (ProductAccurate $device) use ($targetType, $secondPriceSource) {
                $effectivePrice = $device->getEffectiveTradeInSellPrice($secondPriceSource);
                $attrs = ProductAccurate::parseTradeInAttributes($device->name, $device->proyek, $device->business_unit_id);
                $cleanName = (string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $device->name);
                $isUsingCustom = ($device->business_unit_id == 2)
                    && $secondPriceSource !== 'accurate_only'
                    && (($device->custom_sell_price ?? 0) > 0);

                return [
                    'id' => $device->id,
                    'item_no' => $device->item_no,
                    'name' => $device->name,
                    'clean_name' => $cleanName,
                    'model' => $attrs['model'],
                    'storage' => $attrs['storage'],
                    'brand' => $device->brandName,
                    'category' => $device->categoryName,
                    'proyek' => $device->proyek ?? 'NON-PROYEK',
                    'target_type' => $targetType,
                    'condition_label' => $targetType === 'second' ? 'Second / Bekas' : 'Baru / Segel',
                    'variant_count' => 1,
                    'available_colors' => $attrs['color'] ? [$attrs['color']] : [],
                    'price_source' => $isUsingCustom ? 'custom' : 'accurate',
                    'base_price' => $effectivePrice,
                    'formatted_base_price' => 'Rp ' . number_format($effectivePrice, 0, ',', '.'),
                ];
            })->filter(fn($item) => $item['base_price'] > 0)->values();
        }

        return response()->json([
            'status' => 'success',
            'target_type' => $targetType,
            'grouped' => $isGrouped,
            'price_source_mode' => $targetType === 'second' ? $secondPriceSource : 'accurate_only',
            'total' => $devices->count(),
            'data' => $devices,
        ]);
    }

    /**
     * Calculate trade-in estimation and generate WhatsApp link
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'old_device_id' => 'required|integer|exists:product_accurates,id',
            'target_device_id' => 'required|integer|exists:product_accurates,id',
            'customer_name' => 'nullable|string|max:100',
            'target_type' => 'nullable|string|in:new,second',
        ]);

        /** @var ProductAccurate $oldDevice */
        $oldDevice = ProductAccurate::findOrFail($request->input('old_device_id'));
        /** @var ProductAccurate $targetDevice */
        $targetDevice = ProductAccurate::findOrFail($request->input('target_device_id'));

        $secondPriceSource = (string) $this->settingService->get('tradein_second_price_source', 'custom_fallback');
        $groupOld = (bool) $this->settingService->get('tradein_group_old_devices', true);
        $groupTarget = (bool) $this->settingService->get('tradein_group_target_devices', true);

        $oldPrice = (float) ($oldDevice->buy_price > 0 ? $oldDevice->buy_price : ($oldDevice->base_price ?? 0));
        $targetPrice = $targetDevice->getEffectiveTradeInSellPrice($secondPriceSource);
        $difference = max(0, $targetPrice - $oldPrice);

        $oldAttrs = ProductAccurate::parseTradeInAttributes($oldDevice->name, $oldDevice->proyek, $oldDevice->business_unit_id);
        $targetAttrs = ProductAccurate::parseTradeInAttributes($targetDevice->name, $targetDevice->proyek, $targetDevice->business_unit_id);

        $oldDisplayName = ($groupOld && $oldAttrs['color'] !== null) ? $oldAttrs['grouped_name'] : $oldDevice->name;
        $oldCleanName = ($groupOld && $oldAttrs['color'] !== null)
            ? $oldAttrs['short_name']
            : (string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $oldDevice->name);

        $targetDisplayName = ($groupTarget && $targetAttrs['color'] !== null) ? $targetAttrs['grouped_name'] : $targetDevice->name;
        $targetCleanName = ($groupTarget && $targetAttrs['color'] !== null)
            ? $targetAttrs['short_name']
            : (string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $targetDevice->name);

        $targetTypeLabel = $targetDevice->business_unit_id == 2 ? 'HP SECOND' : 'HP BARU';
        $waNumber = config('services.whatsapp.default_phone', '62081156006464');

        $oldProyekLabel = $oldDevice->proyek ?: 'NON-PROYEK';
        $targetProyekLabel = $targetDevice->proyek ?: 'NON-PROYEK';

        $waText = "Halo GSK Store 👋\n"
            . "Saya ingin konsultasi Tukar Tambah HP ({$targetTypeLabel}):\n\n"
            . "📱 HP Lama: {$oldDisplayName} ({$oldProyekLabel})\n"
            . "   ↳ Estimasi Beli: Rp " . number_format($oldPrice, 0, ',', '.') . "\n\n"
            . "✨ HP Tujuan ({$targetTypeLabel}):\n"
            . "   ↳ {$targetDisplayName} ({$targetProyekLabel})\n"
            . "   ↳ Harga: Rp " . number_format($targetPrice, 0, ',', '.') . "\n\n"
            . "💰 Estimasi Tambah Bayar: Rp " . number_format($difference, 0, ',', '.') . "\n\n"
            . "Apakah unit ready di cabang terdekat? Terima kasih!";

        $whatsappUrl = "https://wa.me/{$waNumber}?text=" . rawurlencode($waText);

        return response()->json([
            'status' => 'success',
            'data' => [
                'old_device' => [
                    'id' => $oldDevice->id,
                    'name' => $oldDisplayName,
                    'clean_name' => $oldCleanName,
                    'brand' => $oldDevice->brandName,
                    'proyek' => $oldProyekLabel,
                    'buy_price' => $oldPrice,
                    'formatted_buy_price' => 'Rp ' . number_format($oldPrice, 0, ',', '.'),
                ],
                'target_device' => [
                    'id' => $targetDevice->id,
                    'name' => $targetDisplayName,
                    'clean_name' => $targetCleanName,
                    'brand' => $targetDevice->brandName,
                    'proyek' => $targetProyekLabel,
                    'target_type' => $targetDevice->business_unit_id == 2 ? 'second' : 'new',
                    'target_type_label' => $targetTypeLabel,
                    'base_price' => $targetPrice,
                    'formatted_base_price' => 'Rp ' . number_format($targetPrice, 0, ',', '.'),
                ],
                'calculation' => [
                    'old_price' => $oldPrice,
                    'target_price' => $targetPrice,
                    'difference' => $difference,
                    'formatted_difference' => 'Rp ' . number_format($difference, 0, ',', '.'),
                ],
                'whatsapp_url' => $whatsappUrl,
            ],
        ]);
    }

    /**
     * Get single device price estimation for direct sell mode
     */
    public function getSingleDevicePrice($id): JsonResponse
    {
        /** @var ProductAccurate $device */
        $device = ProductAccurate::findOrFail($id);
        $groupOld = (bool) $this->settingService->get('tradein_group_old_devices', true);
        $attrs = ProductAccurate::parseTradeInAttributes($device->name, $device->proyek, $device->business_unit_id);

        $displayName = ($groupOld && $attrs['color'] !== null) ? $attrs['grouped_name'] : $device->name;
        $cleanName = ($groupOld && $attrs['color'] !== null)
            ? $attrs['short_name']
            : (string) preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $device->name);

        $proyekLabel = $device->proyek ?: 'NON-PROYEK';
        $buyPrice = (float) ($device->buy_price > 0 ? $device->buy_price : ($device->base_price ?? 0));
        $waNumber = config('services.whatsapp.default_phone', '62081156006464');

        $waText = "Halo GSK Store 👋\n"
            . "Saya ingin jual HP second saya:\n"
            . "📱 Perangkat: {$displayName} ({$proyekLabel})\n"
            . "💰 Estimasi Harga: Rp " . number_format($buyPrice, 0, ',', '.') . "\n\n"
            . "Bagaimana proses pengecekan fisik di store? Terima kasih!";

        $whatsappUrl = "https://wa.me/{$waNumber}?text=" . rawurlencode($waText);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $device->id,
                'name' => $displayName,
                'clean_name' => $cleanName,
                'brand' => $device->brandName,
                'category' => $device->categoryName,
                'proyek' => $proyekLabel,
                'buy_price' => $buyPrice,
                'formatted_buy_price' => 'Rp ' . number_format($buyPrice, 0, ',', '.'),
                'whatsapp_url' => $whatsappUrl,
            ],
        ]);
    }

    /**
     * Terapkan filter harga jual pada Unit Bisnis Second berdasarkan setting dinamis.
     */
    private function applySecondSellPriceFilter($query, string $secondPriceSource): void
    {
        if ($secondPriceSource === 'custom_only') {
            $query->where('custom_sell_price', '>', 0);
        } elseif ($secondPriceSource === 'accurate_only') {
            $query->where('base_price', '>', 0);
        } else {
            // 'custom_fallback': utamakan custom_sell_price > 0, fallback ke base_price Accurate > 0
            $query->where(function ($q) {
                $q->where('custom_sell_price', '>', 0)
                    ->orWhere('base_price', '>', 0);
            });
        }
    }

    /**
     * Kecualikan item aksesoris yang salah kategori di dalam kategori Handphone / HP SECOND.
     */
    private function applyExcludeAccessories($query, bool $exclude): void
    {
        if (!$exclude) {
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('proyek')
                ->orWhereRaw('UPPER(proyek) != ?', ['ACCESSORIES']);
        })
            ->where(function ($q) {
                $q->whereNull('is_addon')
                    ->orWhere('is_addon', false);
            })
            ->where('name', 'not like', 'CASE -%')
            ->where('name', 'not like', 'CASE %')
            ->where('name', 'not like', 'TG -%')
            ->where('name', 'not like', 'TEMPERED%');
    }

    /**
     * Apply flexible tokenized multi-word search with alias expansion
     */
    private function applySmartSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        $searchClean = trim($search);
        // Normalize punctuation like () / - to space
        $normalized = preg_replace('/[()\/,\-_]/', ' ', $searchClean);
        $tokens = array_filter(explode(' ', $normalized));

        if (empty($tokens)) {
            return $query;
        }

        // Add Relevance Sorting: Exact substring match gets higher priority
        $query->orderByRaw(
            "CASE 
                WHEN name LIKE ? THEN 1 
                WHEN name LIKE ? THEN 2 
                ELSE 3 
            END",
            ["{$searchClean}%", "%{$searchClean}%"]
        );

        return $query->where(function ($q) use ($tokens, $searchClean) {
            // Priority 1: Direct full substring match
            $q->where('name', 'like', "%{$searchClean}%")
                ->orWhere('item_no', 'like', "%{$searchClean}%")
                ->orWhere(function ($tokenQ) use ($tokens) {
                    // Priority 2: ALL tokens must be matched
                    foreach ($tokens as $token) {
                        $token = trim($token);
                        if (empty($token)) continue;

                        $tokenQ->where(function ($sub) use ($token) {
                            $sub->where('name', 'like', "%{$token}%")
                                ->orWhere('brandName', 'like', "%{$token}%")
                                ->orWhere('item_no', 'like', "%{$token}%")
                                ->orWhere('proyek', 'like', "%{$token}%");

                            $lower = strtolower($token);

                            // Smart alias for iPhone
                            if (in_array($lower, ['ip', 'iph', 'iphone'])) {
                                $sub->orWhere('name', 'like', '%iphone%')
                                    ->orWhere('brandName', 'like', '%apple%');
                            }

                            // Smart regex for ip13, ip14, etc.
                            if (preg_match('/^ip(\d+)$/i', $lower, $matches)) {
                                $num = $matches[1];
                                $sub->orWhere('name', 'like', "%iphone {$num}%")
                                    ->orWhere('name', 'like', "%iphone{$num}%");
                            }

                            // Smart alias for Pro Max
                            if (in_array($lower, ['promax', 'pm'])) {
                                $sub->orWhere('name', 'like', '%pro max%')
                                    ->orWhere('name', 'like', '%promax%');
                            }

                            // Smart alias for Samsung S23 Ultra etc.
                            if (preg_match('/^s(\d+)u$/i', $lower, $matches)) {
                                $num = $matches[1];
                                $sub->orWhere('name', 'like', "%s{$num} ultra%")
                                    ->orWhere('name', 'like', "%s {$num} ultra%");
                            }

                            // Smart alias for GB suffix e.g. "128gb" -> "128"
                            if (preg_match('/^(\d+)gb$/i', $lower, $matches)) {
                                $num = $matches[1];
                                $sub->orWhere('name', 'like', "%{$num}gb%")
                                    ->orWhere('name', 'like', "%{$num} gb%")
                                    ->orWhere('name', 'like', "%{$num}%");
                            }
                        });
                    }
                });
        });
    }
}
