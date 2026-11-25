<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Services\OnlineStockService;
use App\Services\SecondUnitShowcaseService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected OnlineStockService $onlineStockService;
    protected SecondUnitShowcaseService $secondUnitService;

    public function __construct(
        OnlineStockService $onlineStockService,
        SecondUnitShowcaseService $secondUnitService
    ) {
        $this->onlineStockService = $onlineStockService;
        $this->secondUnitService = $secondUnitService;
    }

    /**
     * Mengambil daftar produk yang stoknya tersedia di gudang online (Multi-Warehouse Delivery / Pick Up).
     * 100% membaca DB lokal + dikurangi reservasi online aktif (ATP) agar respon < 50ms & aman dari rate limit Accurate.
     */
    public function index(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id') ? (int) $request->query('business_unit_id') : null;
        $fulfillmentType = strtolower(trim((string) $request->query('fulfillment_type', 'any'))); // delivery | pickup | any
        $warehouseId = $request->query('warehouse_id') ? (int) $request->query('warehouse_id') : null;
        $search = $request->query('search');
        $category = $request->query('category');
        $brand = $request->query('brand');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = $request->query('sort', 'latest'); // latest, price_asc, price_desc, name_asc
        $perPage = min((int) ($request->query('per_page', 20)), 50);

        // 1. Dapatkan daftar gudang online aktif (Multi-Warehouse) sesuai mode
        $deliveryWarehouseIds = $this->onlineStockService
            ->getOnlineWarehouses($businessUnitId, 'delivery')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $pickupWarehouseIds = $this->onlineStockService
            ->getOnlineWarehouses($businessUnitId, 'pickup')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if ($fulfillmentType === 'delivery') {
            $filterWarehouseIds = $deliveryWarehouseIds;
        } elseif ($fulfillmentType === 'pickup') {
            $filterWarehouseIds = $warehouseId
                ? (in_array($warehouseId, $pickupWarehouseIds, true) ? [$warehouseId] : [])
                : $pickupWarehouseIds;
        } else {
            $allIds = array_values(array_unique(array_merge($deliveryWarehouseIds, $pickupWarehouseIds)));
            $filterWarehouseIds = $warehouseId
                ? (in_array($warehouseId, $allIds, true) ? [$warehouseId] : [])
                : $allIds;
        }

        if (empty($filterWarehouseIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada produk atau gudang online store yang aktif untuk filter ini.',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ]
            ]);
        }

        // 2. Query produk yang memiliki stok fisik > 0 di gudang online terpilih
        $query = ProductAccurate::query()
            ->where('base_price', '>', 0)
            ->whereExists(function ($sub) use ($filterWarehouseIds) {
                $sub->select(DB::raw(1))
                    ->from('warehouse_stocks')
                    ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                    ->whereIn('warehouse_stocks.variant_type', [
                        ProductAccurate::class,
                        'App\\Models\\ProductAccurate',
                        'ProductAccurate'
                    ])
                    ->whereIn('warehouse_stocks.warehouse_id', $filterWarehouseIds)
                    ->where('warehouse_stocks.stock', '>', 0);
            })
            ->with([
                'businessUnit.media',
                'media',
                'product.media',
                'productVariants.media',
                'secondProductVariants.media',
            ]);

        // Filter per Business Unit (Toko)
        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        } else {
            $query->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        // Terapkan kurasi Kategori & Merek Aktif per Unit Usaha
        $this->applyCurationFilters($query, $businessUnitId);

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

        // 3. Hitung ATP (Stok Fisik - Reservasi Online Aktif) dalam 2 batch query (0 N+1)
        $pageProductIds = $paginated->getCollection()->pluck('id')->map(fn($id) => (int) $id)->all();
        $atpMap = $this->onlineStockService->getBatchAvailableStock(
            $pageProductIds,
            $deliveryWarehouseIds,
            $pickupWarehouseIds
        );

        // Format data response untuk konsumsi frontend Mobile
        $items = $paginated->getCollection()->map(function ($product) use ($atpMap, $fulfillmentType, $warehouseId) {
            $metrics = $atpMap[$product->id] ?? [
                'delivery_stock' => 0,
                'pickup_stock_total' => 0,
                'available_warehouse_count' => 0,
                'per_warehouse' => [],
            ];

            $deliveryStock = (int) $metrics['delivery_stock'];
            $pickupStockTotal = (int) $metrics['pickup_stock_total'];

            if ($warehouseId) {
                $effectiveStock = (int) ($metrics['per_warehouse'][$warehouseId] ?? 0);
            } elseif ($fulfillmentType === 'delivery') {
                $effectiveStock = $deliveryStock;
            } elseif ($fulfillmentType === 'pickup') {
                $effectiveStock = $pickupStockTotal;
            } else {
                $effectiveStock = max($deliveryStock, $pickupStockTotal);
            }

            // Ambil gambar produk terbaik
            $imageUrl = null;
            if ($product->hasMedia('cover')) {
                $imageUrl = $product->getFirstMediaUrl('cover');
            } elseif ($product->product && $product->product->hasMedia('cover')) {
                $imageUrl = $product->product->getFirstMediaUrl('cover');
            } elseif ($product->productVariants->isNotEmpty()) {
                $firstVariant = $product->productVariants->first();
                if ($firstVariant && $firstVariant->hasMedia('variant_image')) {
                    $imageUrl = $firstVariant->getFirstMediaUrl('variant_image');
                }
            } elseif ($product->secondProductVariants->isNotEmpty()) {
                $firstSecondVariant = $product->secondProductVariants->first();
                if ($firstSecondVariant && $firstSecondVariant->hasMedia('variant_image')) {
                    $imageUrl = $firstSecondVariant->getFirstMediaUrl('variant_image');
                }
            }

            $isSecond = $this->secondUnitService->isSecondProduct($product);

            return [
                'id' => $product->id,
                'item_no' => $product->item_no,
                'name' => $product->name,
                'category' => $product->categoryName,
                'brand' => $product->brandName,
                'proyek' => $product->proyek,
                'price' => (float) $product->base_price,
                'formatted_price' => 'Rp ' . number_format($product->base_price, 0, ',', '.'),
                'stock' => $effectiveStock,
                'stock_delivery' => $deliveryStock,
                'stock_pickup_total' => $pickupStockTotal,
                'available_warehouse_count' => (int) $metrics['available_warehouse_count'],
                'supports_pickup' => $pickupStockTotal > 0,
                'supports_delivery' => $deliveryStock > 0,
                'has_sn' => (bool) $product->has_sn,
                'is_second_unit' => $isSecond,
                'image_url' => $imageUrl,
                'business_unit' => $product->businessUnit ? [
                    'id' => $product->businessUnit->id,
                    'name' => $product->businessUnit->mobile_display_name ?: $product->businessUnit->name,
                    'code' => $product->businessUnit->code,
                    'category' => $product->businessUnit->mobile_category ?: 'GENERAL',
                    'logo_url' => $product->businessUnit->getFirstMediaUrl('logo') ?: null,
                    'brand_color' => $product->businessUnit->brand_color,
                    'warranty_highlight' => $product->businessUnit->warranty_highlight,
                ] : null,
            ];
        })->values();

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
     * Mengambil detail lengkap spesifikasi, foto, ketersediaan stok Multi-Warehouse (Live Accurate Cached 30s),
     * serta preview unit QC (khusus GSK Second dari tabel `device_inspections`).
     */
    public function show(int $id): JsonResponse
    {
        $product = ProductAccurate::with([
            'businessUnit.media',
            'media',
            'product.media',
            'productVariants.media',
            'secondProductVariants.media',
        ])->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        // Validasi kurasi kategori & merek aktif untuk Unit Usaha produk ini
        if ($product->business_unit_id) {
            $cats = $this->getActiveCategoriesForBu($product->business_unit_id);
            if ($cats !== null && !in_array($product->categoryName, $cats)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk ini tidak tersedia untuk e-commerce.',
                ], 404);
            }

            $brands = $this->getActiveBrandsForBu($product->business_unit_id);
            if ($brands !== null && !in_array($product->brandName, $brands)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk ini tidak tersedia untuk e-commerce.',
                ], 404);
            }
        }

        // Hybrid Stock Check: Live Accurate dengan Cache TTL 30s + dikurangi reservasi online lokal
        $stockBreakdown = $this->onlineStockService->getProductStockBreakdown($product, refreshLiveFromAccurate: true);

        // Kumpulkan semua foto produk
        $images = [];
        if ($product->hasMedia('cover')) {
            $images[] = $product->getFirstMediaUrl('cover');
        }
        if ($product->hasMedia('gallery')) {
            foreach ($product->getMedia('gallery') as $media) {
                $images[] = $media->getFullUrl();
            }
        }

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

        foreach ($product->secondProductVariants as $variant) {
            if ($variant->hasMedia('variant_image')) {
                $images[] = $variant->getFirstMediaUrl('variant_image');
            }
        }
        $images = array_values(array_unique(array_filter($images)));

        // Transparansi Unit GSK Second dari `device_inspections`
        $isSecond = $this->secondUnitService->isSecondProduct($product);
        $secondUnits = $isSecond ? $this->secondUnitService->getAvailableUnitsForProduct($product) : [];
        $unitsWithQcCount = count(array_filter($secondUnits, fn($u) => !empty($u['has_qc_report'])));

        // Rincian stok per gudang (backward-compatible)
        $legacyStockBreakdown = collect($stockBreakdown['pickup_warehouses'])
            ->merge($stockBreakdown['delivery_warehouses'])
            ->unique('warehouse_id')
            ->map(fn($w) => [
                'warehouse_id' => $w['warehouse_id'],
                'warehouse_name' => $w['warehouse_name'],
                'stock' => (int) $w['stock'],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil dimuat.',
            'data' => [
                'id' => $product->id,
                'item_no' => $product->item_no,
                'name' => $product->name,
                'category' => $product->categoryName,
                'brand' => $product->brandName,
                'proyek' => $product->proyek,
                'price' => (float) $product->base_price,
                'formatted_price' => 'Rp ' . number_format($product->base_price, 0, ',', '.'),
                'stock' => (int) $stockBreakdown['total_available_stock'],
                'total_delivery_stock' => (int) $stockBreakdown['total_delivery_stock'],
                'total_pickup_stock' => (int) $stockBreakdown['total_pickup_stock'],
                'stock_source' => $stockBreakdown['stock_source'],
                'has_sn' => (bool) $product->has_sn,
                'is_in_stock' => $stockBreakdown['total_available_stock'] > 0,
                'is_second_unit' => $isSecond,
                'requires_unit_selection' => false, // Pemesanan via SO berbasis Qty; pemilihan unit spesifik bersifat opsional untuk GSK Second
                'supports_unit_selection' => $isSecond && count($secondUnits) > 0,
                'units_with_qc_count' => $unitsWithQcCount,
                'available_units_preview' => array_slice($secondUnits, 0, 6),
                'images' => $images,
                'description' => $product->product?->description ?? ($product->raw_data['detailNotes'] ?? null),
                'business_unit' => $product->businessUnit ? [
                    'id' => $product->businessUnit->id,
                    'name' => $product->businessUnit->mobile_display_name ?: $product->businessUnit->name,
                    'code' => $product->businessUnit->code,
                    'category' => $product->businessUnit->mobile_category ?: 'GENERAL',
                    'logo_url' => $product->businessUnit->getFirstMediaUrl('logo') ?: null,
                    'brand_color' => $product->businessUnit->brand_color,
                    'warranty_highlight' => $product->businessUnit->warranty_highlight,
                ] : null,
                'pickup_warehouses' => $stockBreakdown['pickup_warehouses'],
                'delivery_warehouses' => $stockBreakdown['delivery_warehouses'],
                'stock_per_warehouse' => $legacyStockBreakdown,
            ]
        ]);
    }

    /**
     * Mengambil daftar kategori produk yang tersedia di online store (Multi-Warehouse).
     */
    public function categories(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id') ? (int) $request->query('business_unit_id') : null;
        $onlineWarehouseIds = $this->onlineStockService
            ->getOnlineWarehouses($businessUnitId, 'any')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($onlineWarehouseIds)) {
            return response()->json(['success' => true, 'data' => []]);
        }

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

        $this->applyCurationFilters($query, $businessUnitId);

        $categories = $query->distinct()->pluck('categoryName')->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Mengambil daftar merek produk yang tersedia di online store (Multi-Warehouse).
     */
    public function brands(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id') ? (int) $request->query('business_unit_id') : null;
        $onlineWarehouseIds = $this->onlineStockService
            ->getOnlineWarehouses($businessUnitId, 'any')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($onlineWarehouseIds)) {
            return response()->json(['success' => true, 'data' => []]);
        }

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

        $this->applyCurationFilters($query, $businessUnitId);

        $brands = $query->distinct()->pluck('brandName')->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $brands,
        ]);
    }

    /**
     * Endpoint daftar unit fisik & transparansi kondisi QC (`device_inspections`) untuk GSK Second.
     * - Untuk produk Baru (`Syihab`): tidak memerlukan pemilihan IMEI di awal (SO berbasis Qty).
     * - Untuk produk GSK Second: mengembalikan daftar unit lengkap dengan Battery Health, QC verdict,
     *   noted_issues, foto `qc_photos`, lokasi cabang, dan IMEI disamarkan (`****XXXX`).
     */
    public function serialNumbers(Request $request, int $id): JsonResponse
    {
        $product = ProductAccurate::with('businessUnit')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $isSecond = $this->secondUnitService->isSecondProduct($product);

        if (!$product->has_sn || !$isSecond) {
            return response()->json([
                'success' => true,
                'message' => $isSecond
                    ? 'Produk ini tidak menggunakan pelacakan nomor seri / IMEI.'
                    : 'Produk baru bersegel identik; IMEI akan ditentukan & dipindai otomatis oleh tim gudang saat pengiriman/pengambilan.',
                'is_second_unit' => $isSecond,
                'requires_unit_selection' => false,
                'supports_unit_selection' => false,
                'data' => [],
            ]);
        }

        $warehouseId = $request->query('warehouse_id') ? (int) $request->query('warehouse_id') : null;
        $units = $this->secondUnitService->getAvailableUnitsForProduct($product, $warehouseId);

        return response()->json([
            'success' => true,
            'message' => 'Daftar unit fisik & kondisi QC GSK Second berhasil dimuat.',
            'is_second_unit' => true,
            'requires_unit_selection' => false,
            'supports_unit_selection' => count($units) > 0,
            'data' => $units,
        ]);
    }

    protected function getActiveCategoriesForBu(int $buId): ?array
    {
        $settingService = app(SettingService::class);
        $saved = $settingService->get("ecommerce_active_categories_{$buId}");
        return is_array($saved) ? $saved : null;
    }

    protected function getActiveBrandsForBu(int $buId): ?array
    {
        $settingService = app(SettingService::class);
        $saved = $settingService->get("ecommerce_active_brands_{$buId}");
        return is_array($saved) ? $saved : null;
    }

    protected function applyCurationFilters($query, ?int $businessUnitId): void
    {
        if ($businessUnitId) {
            $cats = $this->getActiveCategoriesForBu($businessUnitId);
            if ($cats !== null) {
                $query->whereIn('categoryName', $cats);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('categoryName')
                      ->orWhere(function ($sub) {
                          $sub->where('categoryName', 'not like', '%LDU%')
                              ->where('categoryName', 'not like', '%DEMO%');
                      });
                });
            }

            $brands = $this->getActiveBrandsForBu($businessUnitId);
            if ($brands !== null) {
                $query->whereIn('brandName', $brands);
            }
        } else {
            $visibleBus = BusinessUnit::where('is_active', true)
                ->where('is_visible_mobile', true)
                ->get();

            if ($visibleBus->isNotEmpty()) {
                $query->where(function ($rootQ) use ($visibleBus) {
                    foreach ($visibleBus as $bu) {
                        $cats = $this->getActiveCategoriesForBu($bu->id);
                        $brands = $this->getActiveBrandsForBu($bu->id);

                        $rootQ->orWhere(function ($buQ) use ($bu, $cats, $brands) {
                            $buQ->where('business_unit_id', $bu->id);

                            if ($cats !== null) {
                                $buQ->whereIn('categoryName', $cats);
                            } else {
                                $buQ->where(function ($q) {
                                    $q->whereNull('categoryName')
                                      ->orWhere(function ($sub) {
                                          $sub->where('categoryName', 'not like', '%LDU%')
                                              ->where('categoryName', 'not like', '%DEMO%');
                                      });
                                });
                            }

                            if ($brands !== null) {
                                $buQ->whereIn('brandName', $brands);
                            }
                        });
                    }
                });
            }
        }
    }
}
