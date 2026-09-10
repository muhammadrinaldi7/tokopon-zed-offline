<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductAccurate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTradeInController extends Controller
{
    /**
     * Get distinct list of available brands
     */
    public function getBrands(): JsonResponse
    {
        $brands = ProductAccurate::query()
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->distinct()
            ->pluck('brandName')
            ->sort()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $brands,
        ]);
    }

    /**
     * Get list of old devices available for trade-in / buyback (buy_price > 0)
     */
    public function getOldDevices(Request $request): JsonResponse
    {
        $brand = $request->query('brand');
        $proyek = $request->query('proyek');
        $search = $request->query('search');
        $limit = min((int)($request->query('limit', 50)), 200);

        $query = ProductAccurate::query()
            ->where('buy_price', '>', 0)
            ->when($brand, function ($q) use ($brand) {
                $q->where('brandName', $brand);
            })
            ->when($proyek, function ($q) use ($proyek) {
                $q->where('proyek', $proyek);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('item_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $devices = $query->take($limit)->get([
            'id',
            'item_no',
            'name',
            'brandName',
            'categoryName',
            'proyek',
            'buy_price',
        ])->map(function ($device) {
            return [
                'id' => $device->id,
                'item_no' => $device->item_no,
                'name' => $device->name,
                'brand' => $device->brandName,
                'category' => $device->categoryName,
                'proyek' => $device->proyek ?? 'NON-PROYEK',
                'buy_price' => (float)$device->buy_price,
                'formatted_buy_price' => 'Rp ' . number_format((float)$device->buy_price, 0, ',', '.'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'total' => $devices->count(),
            'data' => $devices,
        ]);
    }

    /**
     * Get list of target (new) devices for trade-in (base_price > 0)
     */
    public function getTargetDevices(Request $request): JsonResponse
    {
        $brand = $request->query('brand');
        $category = $request->query('category');
        $proyek = $request->query('proyek');
        $search = $request->query('search');
        $limit = min((int)($request->query('limit', 50)), 200);

        $query = ProductAccurate::query()
            ->where('base_price', '>', 0)
            ->when($brand, function ($q) use ($brand) {
                $q->where('brandName', $brand);
            })
            ->when($category, function ($q) use ($category) {
                $q->where('categoryName', $category);
            })
            ->when($proyek, function ($q) use ($proyek) {
                $q->where('proyek', $proyek);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('item_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $devices = $query->take($limit)->get([
            'id',
            'item_no',
            'name',
            'brandName',
            'categoryName',
            'proyek',
            'base_price',
        ])->map(function ($device) {
            return [
                'id' => $device->id,
                'item_no' => $device->item_no,
                'name' => $device->name,
                'brand' => $device->brandName,
                'category' => $device->categoryName,
                'proyek' => $device->proyek ?? 'NON-PROYEK',
                'base_price' => (float)$device->base_price,
                'formatted_base_price' => 'Rp ' . number_format((float)$device->base_price, 0, ',', '.'),
            ];
        });

        return response()->json([
            'status' => 'success',
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
        ]);

        $oldDevice = ProductAccurate::findOrFail($request->input('old_device_id'));
        $targetDevice = ProductAccurate::findOrFail($request->input('target_device_id'));

        $oldPrice = (float)($oldDevice->buy_price ?? 0);
        $targetPrice = (float)($targetDevice->base_price ?? 0);
        $difference = max(0, $targetPrice - $oldPrice);

        // Format WhatsApp pre-filled text
        $customerName = $request->input('customer_name', 'Kak');
        $waNumber = config('services.whatsapp.default_phone', '6281140006464');

        $waText = "Halo Syihab Store 👋\n"
            . "Saya ingin konsultasi Tukar Tambah HP:\n\n"
            . "📱 HP Lama: {$oldDevice->name} ({$oldDevice->proyek})\n"
            . "   ↳ Estimasi Beli: Rp " . number_format($oldPrice, 0, ',', '.') . "\n\n"
            . "📱 HP Baru: {$targetDevice->name} ({$targetDevice->proyek})\n"
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
                    'brand' => $oldDevice->brandName,
                    'proyek' => $oldDevice->proyek ?? 'NON-PROYEK',
                    'buy_price' => $oldPrice,
                    'formatted_buy_price' => 'Rp ' . number_format($oldPrice, 0, ',', '.'),
                ],
                'target_device' => [
                    'id' => $targetDevice->id,
                    'name' => $targetDevice->name,
                    'brand' => $targetDevice->brandName,
                    'proyek' => $targetDevice->proyek ?? 'NON-PROYEK',
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
        $buyPrice = (float)($device->buy_price ?? 0);
        $waNumber = config('services.whatsapp.default_phone', '6281140006464');

        $waText = "Halo Syihab Store 👋\n"
            . "Saya ingin jual HP bekas saya:\n"
            . "📱 Perangkat: {$device->name} ({$device->proyek})\n"
            . "💰 Estimasi Harga: Rp " . number_format($buyPrice, 0, ',', '.') . "\n\n"
            . "Bagaimana proses pengecekan fisik di store? Terima kasih!";

        $whatsappUrl = "https://wa.me/{$waNumber}?text=" . rawurlencode($waText);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $device->id,
                'name' => $device->name,
                'brand' => $device->brandName,
                'category' => $device->categoryName,
                'proyek' => $device->proyek ?? 'NON-PROYEK',
                'buy_price' => $buyPrice,
                'formatted_buy_price' => 'Rp ' . number_format($buyPrice, 0, ',', '.'),
                'whatsapp_url' => $whatsappUrl,
            ],
        ]);
    }
}
