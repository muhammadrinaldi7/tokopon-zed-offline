<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\EcommerceFlashSale;
use App\Models\ProductAccurate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    /**
     * Helper untuk memformat gambar produk
     */
    protected function getProductThumbnail(?ProductAccurate $product): string
    {
        if (!$product) {
            return 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
        }

        if ($product->product && $product->product->hasMedia('cover')) {
            return $product->product->getFirstMediaUrl('cover');
        }

        if ($product->productVariants && $product->productVariants->isNotEmpty()) {
            $first = $product->productVariants->first();
            if ($first && $first->hasMedia('variant_image')) {
                return $first->getFirstMediaUrl('variant_image');
            }
        }

        return 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
    }

    /**
     * Mengambil sesi flash sale aktif saat ini beserta daftar produknya.
     */
    public function index(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id');
        $now = now();

        $query = EcommerceFlashSale::query()
            ->where('is_active', true)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->with(['items.productAccurate.product.media', 'items.productAccurate.productVariants.media']);

        if ($businessUnitId) {
            $query->where(function ($q) use ($businessUnitId) {
                $q->whereNull('business_unit_id')
                  ->orWhere('business_unit_id', $businessUnitId);
            });
        }

        $flashSale = $query->latest('start_time')->first();

        if (!$flashSale) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Tidak ada event flash sale yang sedang berlangsung.',
            ]);
        }

        $items = $flashSale->items->where('is_active', true)->map(function ($item) {
            $product = $item->productAccurate;
            $originalPrice = (float) ($item->original_price ?: ($product ? $product->base_price : $item->flash_sale_price));
            $salePrice = (float) $item->flash_sale_price;
            $discountPct = $originalPrice > $salePrice ? (int) round((($originalPrice - $salePrice) / $originalPrice) * 100) : 0;

            return [
                'id' => $item->id,
                'product_id' => $product?->id,
                'name' => $product?->name ?? 'Produk Flash Sale',
                'brand' => $product?->brandName ?? '',
                'category' => $product?->categoryName ?? '',
                'thumbnail' => $this->getProductThumbnail($product),
                'flash_sale_price' => $salePrice,
                'original_price' => $originalPrice,
                'discount_percent' => $discountPct,
                'quota_stock' => $item->quota_stock,
                'sold_stock' => $item->sold_stock,
                'available_stock' => max(0, $item->quota_stock - $item->sold_stock),
                'stock_percentage' => $item->quota_stock > 0 ? (int) round(($item->sold_stock / $item->quota_stock) * 100) : 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $flashSale->id,
                'title' => $flashSale->title,
                'business_unit_id' => $flashSale->business_unit_id,
                'start_time' => $flashSale->start_time->toIso8601String(),
                'end_time' => $flashSale->end_time->toIso8601String(),
                'seconds_remaining' => max(0, $flashSale->end_time->diffInSeconds($now)),
                'items' => $items,
            ],
        ]);
    }
}
