<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductAccurate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTradeInController extends Controller
{
    /**
     * Get distinct list of available brands filtered by context (new, second, or old)
     */
    public function getBrands(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all'); // 'new', 'second', 'old', or 'all'

        $query = ProductAccurate::query()
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '');

        if ($type === 'new') {
            // HP Baru: BU 1 (Syihab) & Kategori Handphone
            $query->where('business_unit_id', 1)
                ->where('categoryName', 'like', '%Handphone%')
                ->where('base_price', '>', 0);
        } elseif ($type === 'second') {
            // HP Second: BU 2 (GSK Second) & Kategori HP SECOND & Khusus APPLE
            $query->where('business_unit_id', 2)
                ->where('categoryName', 'like', '%HP SECOND%')
                ->where('base_price', '>', 0)
                ->whereRaw('UPPER(brandName) = ?', ['APPLE']);
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
     * Get list of old devices available for trade-in / buyback (buy_price or base_price > 0 & category Handphone/HP SECOND)
     */
    public function getOldDevices(Request $request): JsonResponse
    {
        $brand = $request->query('brand');
        $proyek = $request->query('proyek');
        $search = $request->query('search');
        $limit = min((int)($request->query('limit', 1000)), 2000);

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

        $query = $this->applySmartSearch($query, $search);

        $devices = $query->orderBy('name')
            ->take($limit)->get([
                'id',
                'item_no',
                'name',
                'brandName',
                'categoryName',
                'proyek',
                'buy_price',
                'base_price',
                'business_unit_id',
            ])->map(function ($device) {
                $effectiveBuyPrice = (float)($device->buy_price > 0 ? $device->buy_price : $device->base_price);
                $cleanName = preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $device->name);
                $isSecond = ($device->business_unit_id == 2) || (stripos($device->categoryName, 'SECOND') !== false) || (stripos($device->name, '2ND') !== false);

                return [
                    'id' => $device->id,
                    'item_no' => $device->item_no,
                    'name' => $device->name,
                    'clean_name' => $cleanName,
                    'brand' => $device->brandName,
                    'category' => $device->categoryName,
                    'proyek' => $device->proyek ?? 'NON-PROYEK',
                    'is_second' => $isSecond,
                    'condition_label' => $isSecond ? 'HP Second (2ND)' : 'Reguler',
                    'buy_price' => $effectiveBuyPrice,
                    'formatted_buy_price' => 'Rp ' . number_format($effectiveBuyPrice, 0, ',', '.'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'total' => $devices->count(),
            'data' => $devices,
        ]);
    }

    /**
     * Get list of target devices for trade-in:
     * - target_type = 'new'    -> BU 1 (Syihab), Category 'Handphone', base_price > 0
     * - target_type = 'second' -> BU 2 (GSK Second), Category 'HP SECOND', base_price > 0
     */
    public function getTargetDevices(Request $request): JsonResponse
    {
        $targetType = $request->query('target_type', 'new'); // 'new' or 'second'
        $brand = $request->query('brand');
        $proyek = $request->query('proyek');
        $search = $request->query('search');
        $limit = min((int)($request->query('limit', 1000)), 2000);

        $query = ProductAccurate::query()
            ->where('base_price', '>', 0);

        if ($targetType === 'second') {
            // HP Second: BU 2 & Kategori HP SECOND & Khusus APPLE
            $query->where('business_unit_id', 2)
                ->where('categoryName', 'like', '%HP SECOND%')
                ->whereRaw('UPPER(brandName) = ?', ['APPLE']);
        } else {
            // HP Baru: BU 1 & Kategori Handphone
            $query->where('business_unit_id', 1)
                ->where('categoryName', 'like', '%Handphone%');
        }

        $query->when($brand, function ($q) use ($brand) {
            $q->where('brandName', $brand);
        })
            ->when($proyek, function ($q) use ($proyek) {
                $q->where('proyek', $proyek);
            });

        $query = $this->applySmartSearch($query, $search);

        $devices = $query->orderBy('name')
            ->take($limit)->get([
                'id',
                'item_no',
                'name',
                'brandName',
                'categoryName',
                'proyek',
                'base_price',
                'business_unit_id',
            ])->map(function ($device) use ($targetType) {
                $cleanName = preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $device->name);

                return [
                    'id' => $device->id,
                    'item_no' => $device->item_no,
                    'name' => $device->name,
                    'clean_name' => $cleanName,
                    'brand' => $device->brandName,
                    'category' => $device->categoryName,
                    'proyek' => $device->proyek ?? 'NON-PROYEK',
                    'target_type' => $targetType,
                    'condition_label' => $targetType === 'second' ? 'Second / Bekas' : 'Baru / Segel',
                    'base_price' => (float)$device->base_price,
                    'formatted_base_price' => 'Rp ' . number_format((float)$device->base_price, 0, ',', '.'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'target_type' => $targetType,
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

        $oldDevice = ProductAccurate::findOrFail($request->input('old_device_id'));
        $targetDevice = ProductAccurate::findOrFail($request->input('target_device_id'));

        $oldPrice = (float)($oldDevice->buy_price > 0 ? $oldDevice->buy_price : ($oldDevice->base_price ?? 0));
        $targetPrice = (float)($targetDevice->base_price ?? 0);
        $difference = max(0, $targetPrice - $oldPrice);

        $targetTypeLabel = $targetDevice->business_unit_id == 2 ? 'HP SECOND' : 'HP BARU';
        $waNumber = config('services.whatsapp.default_phone', '62081156006464');

        $waText = "Halo GSK Store 👋\n"
            . "Saya ingin konsultasi Tukar Tambah HP ({$targetTypeLabel}):\n\n"
            . "📱 HP Lama: {$oldDevice->name} ({$oldDevice->proyek})\n"
            . "   ↳ Estimasi Beli: Rp " . number_format($oldPrice, 0, ',', '.') . "\n\n"
            . "✨ HP Tujuan ({$targetTypeLabel}):\n"
            . "   ↳ {$targetDevice->name} ({$targetDevice->proyek})\n"
            . "   ↳ Harga: Rp " . number_format($targetPrice, 0, ',', '.') . "\n\n"
            . "💰 Estimasi Tambah Bayar: Rp " . number_format($difference, 0, ',', '.') . "\n\n"
            . "Apakah unit ready di cabang terdekat? Terima kasih!";

        $whatsappUrl = "https://wa.me/{$waNumber}?text=" . rawurlencode($waText);

        return response()->json([
            'status' => 'success',
            'data' => [
                'old_device' => [
                    'id' => $oldDevice->id,
                    'name' => $oldDevice->name,
                    'clean_name' => preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $oldDevice->name),
                    'brand' => $oldDevice->brandName,
                    'proyek' => $oldDevice->proyek ?? 'NON-PROYEK',
                    'buy_price' => $oldPrice,
                    'formatted_buy_price' => 'Rp ' . number_format($oldPrice, 0, ',', '.'),
                ],
                'target_device' => [
                    'id' => $targetDevice->id,
                    'name' => $targetDevice->name,
                    'clean_name' => preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $targetDevice->name),
                    'brand' => $targetDevice->brandName,
                    'proyek' => $targetDevice->proyek ?? 'NON-PROYEK',
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
        $device = ProductAccurate::findOrFail($id);
        $buyPrice = (float)($device->buy_price > 0 ? $device->buy_price : ($device->base_price ?? 0));
        $waNumber = config('services.whatsapp.default_phone', '62081156006464');

        $waText = "Halo GSK Store 👋\n"
            . "Saya ingin jual HP second saya:\n"
            . "📱 Perangkat: {$device->name} ({$device->proyek})\n"
            . "💰 Estimasi Harga: Rp " . number_format($buyPrice, 0, ',', '.') . "\n\n"
            . "Bagaimana proses pengecekan fisik di store? Terima kasih!";

        $whatsappUrl = "https://wa.me/{$waNumber}?text=" . rawurlencode($waText);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $device->id,
                'name' => $device->name,
                'clean_name' => preg_replace('/^(HP\s+2ND\s+|HP\s+SECOND\s+|HP\s+)/i', '', $device->name),
                'brand' => $device->brandName,
                'category' => $device->categoryName,
                'proyek' => $device->proyek ?? 'NON-PROYEK',
                'buy_price' => $buyPrice,
                'formatted_buy_price' => 'Rp ' . number_format($buyPrice, 0, ',', '.'),
                'whatsapp_url' => $whatsappUrl,
            ],
        ]);
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
