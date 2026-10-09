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
        $businessUnitId = $request->query('business_unit_id');
        $search = $request->query('search');
        $category = $request->query('category');
        $brand = $request->query('brand');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = $request->query('sort', 'latest'); // latest, price_asc, price_desc, name_asc
        $perPage = min((int) ($request->query('per_page', 20)), 50);

        // 1. Dapatkan daftar ID gudang yang diizinkan untuk toko online & unit bisnis terkait
        $warehouseQuery = Warehouse::where('is_online_store', true);
        if ($businessUnitId) {
            $warehouseQuery->where('business_unit_id', $businessUnitId);
        } else {
            // Hanya izinkan gudang dari Business Unit yang aktif dan disetujui untuk mobile
            $warehouseQuery->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }
        $onlineWarehouseIds = $warehouseQuery->pluck('id')->toArray();

        if (empty($onlineWarehouseIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada produk atau gudang online store yang aktif untuk toko ini.',
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
            ->with([
                'businessUnit',
                'product.media',
                'productVariants.media',
                'warehouseStocks' => function ($q) use ($onlineWarehouseIds) {
                    $q->whereIn('warehouse_id', $onlineWarehouseIds);
                }
            ]);

        // Filter per Business Unit (Toko)
        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        } else {
            $query->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

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
                'business_unit' => $product->businessUnit ? [
                    'id' => $product->businessUnit->id,
                    'name' => $product->businessUnit->mobile_display_name ?: $product->businessUnit->name,
                    'code' => $product->businessUnit->code,
                    'category' => $product->businessUnit->mobile_category ?: 'GENERAL',
                ] : null,
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
            'businessUnit',
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
                'business_unit' => $product->businessUnit ? [
                    'id' => $product->businessUnit->id,
                    'name' => $product->businessUnit->mobile_display_name ?: $product->businessUnit->name,
                    'code' => $product->businessUnit->code,
                    'category' => $product->businessUnit->mobile_category ?: 'GENERAL',
                ] : null,
                'stock_per_warehouse' => $stockBreakdown,
            ]
        ]);
    }

    /**
     * Mengambil daftar kategori produk yang tersedia di online store.
     */
    public function categories(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id');

        $warehouseQuery = Warehouse::where('is_online_store', true);
        if ($businessUnitId) {
            $warehouseQuery->where('business_unit_id', $businessUnitId);
        } else {
            $warehouseQuery->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }
        $onlineWarehouseIds = $warehouseQuery->pluck('id')->toArray();

        $query = ProductAccurate::where('base_price', '>', 0)
            ->whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->whereExists(function ($sub) use ($onlineWarehouseIds) {
                $sub->select(DB::raw(1))
                    ->from('warehouse_stocks')
                    ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                    ->whereIn('warehouse_stocks.warehouse_id', $onlineWarehouseIds)
                    ->where('warehouse_stocks.stock', '>', 0);
            });

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        } else {
            $query->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        $categories = $query->distinct()->pluck('categoryName')->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Mengambil daftar merek produk yang tersedia di online store.
     */
    public function brands(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id');

        $warehouseQuery = Warehouse::where('is_online_store', true);
        if ($businessUnitId) {
            $warehouseQuery->where('business_unit_id', $businessUnitId);
        } else {
            $warehouseQuery->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }
        $onlineWarehouseIds = $warehouseQuery->pluck('id')->toArray();

        $query = ProductAccurate::where('base_price', '>', 0)
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->whereExists(function ($sub) use ($onlineWarehouseIds) {
                $sub->select(DB::raw(1))
                    ->from('warehouse_stocks')
                    ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                    ->whereIn('warehouse_stocks.warehouse_id', $onlineWarehouseIds)
                    ->where('warehouse_stocks.stock', '>', 0);
            });

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        } else {
            $query->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        $brands = $query->distinct()->pluck('brandName')->values();

        return response()->json([
            'success' => true,
            'data' => $brands,
        ]);
    }

    /**
     * Mengambil daftar nomor seri (IMEI / SN) yang tersedia untuk produk tertentu di gudang online.
     * Khusus untuk produk yang memiliki SN (has_sn = true), seperti iPhone second, gadget bergaransi, dsb.
     */
    public function serialNumbers(Request $request, int $id): JsonResponse
    {
        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();
        $product = ProductAccurate::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        if (!$product->has_sn) {
            return response()->json([
                'success' => true,
                'message' => 'Produk ini tidak menggunakan nomor seri / IMEI.',
                'data' => [],
            ]);
        }

        $warehouseId = $request->query('warehouse_id');
        $query = \App\Models\ProductSerialNumber::with('warehouse')
            ->where('product_accurate_id', $product->id)
            ->where('status', 'Available');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        } else {
            $query->whereIn('warehouse_id', $onlineWarehouseIds);
        }

        $serialNumbers = $query->get()->map(function ($sn) {
            return [
                'id' => $sn->id,
                'serial_number' => $sn->serial_number,
                'warehouse_id' => $sn->warehouse_id,
                'warehouse_name' => $sn->warehouse?->name,
                'qc_status' => $sn->qc_status,
                'receipt_date' => $sn->receipt_date,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar nomor seri (SN/IMEI) yang tersedia berhasil dimuat.',
            'data' => $serialNumbers,
        ]);
    }
}
