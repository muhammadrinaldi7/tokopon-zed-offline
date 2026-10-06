<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Mengambil daftar produk yang stoknya tersedia di gudang online store (is_online_store = true).
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $brand = $request->query('brand');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = $request->query('sort', 'latest'); // latest, price_asc, price_desc, name_asc
        $perPage = min((int) ($request->query('per_page', 20)), 50);

        // 1. Dapatkan daftar ID gudang yang diizinkan untuk toko online
        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();

        if (empty($onlineWarehouseIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada gudang online store yang aktif saat ini.',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ]
            ]);
        }

        // 2. Query produk yang memiliki stok agregat > 0 di gudang online
        $query = ProductAccurate::query()
            ->where('base_price', '>', 0)
            ->whereExists(function ($sub) use ($onlineWarehouseIds) {
                $sub->select(DB::raw(1))
                    ->from('warehouse_stocks')
                    ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                    ->whereIn('warehouse_stocks.variant_type', [
                        ProductAccurate::class,
                        'App\\Models\\ProductAccurate',
                        'ProductAccurate'
                    ])
                    ->whereIn('warehouse_stocks.warehouse_id', $onlineWarehouseIds)
                    ->where('warehouse_stocks.stock', '>', 0);
            })
            ->with(['product.media', 'productVariants.media', 'warehouseStocks' => function ($q) use ($onlineWarehouseIds) {
                $q->whereIn('warehouse_id', $onlineWarehouseIds);
            }]);

        // Filter Pencarian
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_no', 'like', "%{$search}%");
            });
        }

        // Filter Kategori
        if (!empty($category)) {
            $query->where('categoryName', 'like', "%{$category}%");
        }

        // Filter Merek / Brand
        if (!empty($brand)) {
            $query->where('brandName', 'like', "%{$brand}%");
        }

        // Filter Rentang Harga
        if (is_numeric($minPrice)) {
            $query->where('base_price', '>=', (float) $minPrice);
        }
        if (is_numeric($maxPrice)) {
            $query->where('base_price', '<=', (float) $maxPrice);
        }

        // Pengurutan
        match ($sort) {
            'price_asc' => $query->orderBy('base_price', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->latest('id'),
        };

        $paginated = $query->paginate($perPage);

        // Format data response untuk konsumsi frontend Mobile
        $items = $paginated->getCollection()->map(function ($product) {
            $totalOnlineStock = $product->warehouseStocks->sum('stock');

            // Ambil gambar produk terbaik
            $imageUrl = null;
            if ($product->product && $product->product->hasMedia('cover')) {
                $imageUrl = $product->product->getFirstMediaUrl('cover');
            } elseif ($product->productVariants->isNotEmpty()) {
                $firstVariant = $product->productVariants->first();
                if ($firstVariant && $firstVariant->hasMedia('variant_image')) {
                    $imageUrl = $firstVariant->getFirstMediaUrl('variant_image');
                }
            }

            return [
                'id' => $product->id,
                'item_no' => $product->item_no,
                'name' => $product->name,
                'category' => $product->categoryName,
                'brand' => $product->brandName,
                'price' => (float) $product->base_price,
                'formatted_price' => 'Rp ' . number_format($product->base_price, 0, ',', '.'),
                'stock' => (int) $totalOnlineStock,
                'has_sn' => (bool) $product->has_sn,
                'image_url' => $imageUrl,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk online store berhasil dimuat.',
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    /**
     * Mengambil detail lengkap spesifikasi, foto, dan ketersediaan stok produk.
     */
    public function show(int $id): JsonResponse
    {
        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();

        $product = ProductAccurate::with([
            'product.media',
            'productVariants.media',
            'warehouseStocks' => function ($q) use ($onlineWarehouseIds) {
                $q->whereIn('warehouse_id', $onlineWarehouseIds)->with('warehouse');
            }
        ])->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $totalOnlineStock = $product->warehouseStocks->sum('stock');

        // Kumpulkan semua foto produk
        $images = [];
        if ($product->product) {
            foreach ($product->product->getMedia('cover') as $media) {
                $images[] = $media->getFullUrl();
            }
            foreach ($product->product->getMedia('gallery') as $media) {
                $images[] = $media->getFullUrl();
            }
        }

        foreach ($product->productVariants as $variant) {
            if ($variant->hasMedia('variant_image')) {
                $images[] = $variant->getFirstMediaUrl('variant_image');
            }
        }
        $images = array_values(array_unique(array_filter($images)));

        // Rincian stok per gudang online
        $stockBreakdown = $product->warehouseStocks->map(function ($ws) {
            return [
                'warehouse_id' => $ws->warehouse_id,
                'warehouse_name' => $ws->warehouse?->name ?? 'Gudang Online',
                'stock' => (int) $ws->stock,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil dimuat.',
            'data' => [
                'id' => $product->id,
                'item_no' => $product->item_no,
                'name' => $product->name,
                'category' => $product->categoryName,
                'brand' => $product->brandName,
                'price' => (float) $product->base_price,
                'formatted_price' => 'Rp ' . number_format($product->base_price, 0, ',', '.'),
                'stock' => (int) $totalOnlineStock,
                'has_sn' => (bool) $product->has_sn,
                'is_in_stock' => $totalOnlineStock > 0,
                'images' => $images,
                'description' => $product->product?->description ?? ($product->raw_data['detailNotes'] ?? null),
                'stock_per_warehouse' => $stockBreakdown,
            ]
        ]);
    }

    /**
     * Mengambil daftar kategori produk yang tersedia di online store.
     */
    public function categories(): JsonResponse
    {
        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();

        $categories = ProductAccurate::where('base_price', '>', 0)
            ->whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->whereExists(function ($sub) use ($onlineWarehouseIds) {
                $sub->select(DB::raw(1))
                    ->from('warehouse_stocks')
                    ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                    ->whereIn('warehouse_stocks.warehouse_id', $onlineWarehouseIds)
                    ->where('warehouse_stocks.stock', '>', 0);
            })
            ->distinct()
            ->pluck('categoryName')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Mengambil daftar merek produk yang tersedia di online store.
     */
    public function brands(): JsonResponse
    {
        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();

        $brands = ProductAccurate::where('base_price', '>', 0)
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->whereExists(function ($sub) use ($onlineWarehouseIds) {
                $sub->select(DB::raw(1))
                    ->from('warehouse_stocks')
                    ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                    ->whereIn('warehouse_stocks.warehouse_id', $onlineWarehouseIds)
                    ->where('warehouse_stocks.stock', '>', 0);
            })
            ->distinct()
            ->pluck('brandName')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $brands,
        ]);
    }
}
