<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\EcommerceFlashSaleItem;
use App\Models\ProductAccurate;
use App\Services\OnlineStockService;
use App\Services\SecondUnitShowcaseService;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected OnlineStockService $onlineStockService;
    protected SecondUnitShowcaseService $secondUnitService;
    protected static ?bool $hasFlashSaleTables = null;

    protected const VARIANT_TYPES = [
        ProductAccurate::class,
        'App\\Models\\ProductAccurate',
        'ProductAccurate',
    ];

    public function __construct(
        OnlineStockService $onlineStockService,
        SecondUnitShowcaseService $secondUnitService
    ) {
        $this->onlineStockService = $onlineStockService;
        $this->secondUnitService = $secondUnitService;
    }

    /**
     * Mengambil daftar produk yang tersedia di online store (Mobile App & Web E-Commerce).
     * 100% membaca DB lokal + pengurangan reservasi online aktif (ATP) langsung di SQL agar akurat & < 50ms.
     *
     * Supported query params:
     *   - business_unit_id : int atau CSV/array ("1,2")
     *   - fulfillment_type : delivery | pickup | any (default: any)
     *   - warehouse_id     : int atau CSV/array ("1,3")
     *   - city             : string atau CSV/array ("Banjarmasin,Banjarbaru")
     *   - search           : string (tokenized multi-word search di name, item_no, brand, category, proyek)
     *   - category         : string atau CSV/array (case-insensitive exact match)
     *   - brand            : string atau CSV/array (case-insensitive exact match)
     *   - proyek           : string atau CSV/array ("RESMI,INTER")
     *   - os               : string atau CSV/array ("IOS,ANDROID")
     *   - condition        : new | second
     *   - min_price        : numeric
     *   - max_price        : numeric
     *   - on_flash_sale    : boolean (1/true)
     *   - has_qc_report    : boolean (1/true — khusus unit yang punya laporan QC device_inspections)
     *   - in_stock_only    : boolean (default: true — ATP > 0)
     *   - exclude_addon    : boolean (default: true — kecualikan item is_addon=1)
     *   - sort             : latest | price_asc | price_desc | name_asc | name_desc | stock_desc | best_seller
     *   - per_page         : int (default: 20, max: 50)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $ctx = $this->resolveWarehouseContext($filters);
        $perPage = max(1, min((int) ($request->query('per_page', $request->query('limit', 20))), 50));

        if ($filters['in_stock_only'] && empty($ctx['filter_warehouse_ids'])) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada produk atau gudang online store yang aktif untuk filter ini.',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'applied_filters' => $this->formatAppliedFilters($filters),
                ],
            ]);
        }

        $query = $this->buildCatalogQuery($ctx, $filters)
            ->with([
                'businessUnit.media',
                'media',
                'product.media',
                'productVariants.media',
                'secondProductVariants.media',
            ]);

        $this->applySorting($query, $filters['sort'], $ctx['filter_warehouse_ids']);

        $paginated = $query->paginate($perPage);

        // Hitung ATP (Stok Fisik - Reservasi Online Aktif) dalam 2 batch query (0 N+1)
        $pageProductIds = $paginated->getCollection()->pluck('id')->map(fn($id) => (int) $id)->all();
        $atpMap = $this->onlineStockService->getBatchAvailableStock(
            $pageProductIds,
            $ctx['delivery_warehouse_ids'],
            $ctx['pickup_warehouse_ids']
        );

        // Batch query Flash Sale aktif untuk halaman ini (0 N+1)
        $flashSaleMap = $this->getActiveFlashSaleMap($pageProductIds);

        $selectedWhIds = $filters['warehouse_ids'];
        $fulfillmentType = $filters['fulfillment_type'];
        $filteredWhIds = $ctx['filter_warehouse_ids'];

        $items = $paginated->getCollection()->map(function ($product) use ($atpMap, $flashSaleMap, $fulfillmentType, $selectedWhIds, $filteredWhIds, $filters) {
            $metrics = $atpMap[$product->id] ?? [
                'delivery_stock' => 0,
                'pickup_stock_total' => 0,
                'available_warehouse_count' => 0,
                'per_warehouse' => [],
            ];

            $deliveryStock = (int) $metrics['delivery_stock'];
            $pickupStockTotal = (int) $metrics['pickup_stock_total'];

            if (!empty($selectedWhIds) || !empty($filters['cities'])) {
                $effectiveStock = 0;
                foreach ($filteredWhIds as $wId) {
                    $effectiveStock += (int) ($metrics['per_warehouse'][$wId] ?? 0);
                }
            } elseif ($fulfillmentType === 'delivery') {
                $effectiveStock = $deliveryStock;
            } elseif ($fulfillmentType === 'pickup') {
                $effectiveStock = $pickupStockTotal;
            } else {
                $effectiveStock = max($deliveryStock, $pickupStockTotal);
            }

            $imageUrl = $this->resolvePrimaryImage($product);
            $isSecond = $this->secondUnitService->isSecondProduct($product);
            $flashInfo = $flashSaleMap[$product->id] ?? null;

            $basePrice = (float) $product->base_price;
            $flashPrice = $flashInfo ? (float) $flashInfo['flash_sale_price'] : null;
            $originalPrice = $flashInfo ? (float) $flashInfo['original_price'] : $basePrice;
            $discountPercent = $flashInfo ? (int) $flashInfo['discount_percent'] : 0;

            return [
                'id' => $product->id,
                'slug' => $this->makeProductSlug($product),
                'item_no' => $product->item_no,
                'name' => $product->name,
                'category' => $product->categoryName,
                'brand' => $product->brandName,
                'proyek' => $product->proyek,
                'os' => $product->os,
                'condition' => $isSecond ? 'second' : 'new',
                'is_addon' => (bool) $product->is_addon,
                'price' => $basePrice,
                'formatted_price' => 'Rp ' . number_format($basePrice, 0, ',', '.'),
                'is_flash_sale' => $flashInfo !== null,
                'flash_sale_price' => $flashPrice,
                'formatted_flash_sale_price' => $flashPrice !== null ? 'Rp ' . number_format($flashPrice, 0, ',', '.') : null,
                'original_price' => $originalPrice,
                'discount_percent' => $discountPercent,
                'stock' => $effectiveStock,
                'stock_delivery' => $deliveryStock,
                'stock_pickup_total' => $pickupStockTotal,
                'available_warehouse_count' => (int) $metrics['available_warehouse_count'],
                'supports_pickup' => $pickupStockTotal > 0,
                'supports_delivery' => $deliveryStock > 0,
                'has_sn' => (bool) $product->has_sn,
                'is_second_unit' => $isSecond,
                'image_url' => $imageUrl,
                'business_unit' => $this->formatBusinessUnit($product->businessUnit),
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
                'applied_filters' => $this->formatAppliedFilters($filters),
            ],
        ]);
    }

    /**
     * Endpoint Unified Faceted Filters untuk Sidebar Web E-Commerce & Filter Drawer Mobile App.
     * Mengembalikan seluruh opsi filter beserta jumlah produk (cross-filtered) dan rentang harga dalam 1 request.
     */
    public function filters(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $ctx = $this->resolveWarehouseContext($filters);

        if ($filters['in_stock_only'] && empty($ctx['all_online_warehouse_ids'])) {
            return response()->json([
                'success' => true,
                'message' => 'Opsi filter katalog berhasil dimuat.',
                'data' => [
                    'categories' => [],
                    'brands' => [],
                    'proyeks' => [],
                    'os' => [],
                    'conditions' => [],
                    'warehouses' => [],
                    'cities' => [],
                    'price_range' => ['min' => 0, 'max' => 0],
                    'sort_options' => $this->getSortOptions(),
                ],
            ]);
        }

        // 1. Facet Categories (terapkan semua filter aktif KECUALI category)
        $categories = [];
        if (!$filters['in_stock_only'] || !empty($ctx['filter_warehouse_ids'])) {
            $categories = $this->aggregateStringFacet(
                $this->buildCatalogQuery($ctx, $filters, ['category']),
                'categoryName'
            );
        }

        // 2. Facet Brands (terapkan semua filter aktif KECUALI brand — case-normalized agar APPLE & Apple menyatu)
        $brands = [];
        if (!$filters['in_stock_only'] || !empty($ctx['filter_warehouse_ids'])) {
            $brands = $this->aggregateStringFacet(
                $this->buildCatalogQuery($ctx, $filters, ['brand']),
                'brandName'
            );
        }

        // 3. Facet Proyek / Garansi (RESMI, INTER, ACCESSORIES, dll.)
        $proyeks = [];
        if (!$filters['in_stock_only'] || !empty($ctx['filter_warehouse_ids'])) {
            $proyeks = $this->aggregateStringFacet(
                $this->buildCatalogQuery($ctx, $filters, ['proyek']),
                'proyek'
            );
        }

        // 4. Facet OS (IOS, ANDROID, NON)
        $osList = [];
        if (!$filters['in_stock_only'] || !empty($ctx['filter_warehouse_ids'])) {
            $osList = $this->aggregateStringFacet(
                $this->buildCatalogQuery($ctx, $filters, ['os']),
                'os'
            );
        }

        // 5. Facet Condition (new vs second — terapkan semua filter KECUALI condition)
        $conditions = [];
        if (!$filters['in_stock_only'] || !empty($ctx['filter_warehouse_ids'])) {
            $newCount = (clone $this->buildCatalogQuery($ctx, $filters, ['condition']))
                ->tap(fn($q) => $this->applyConditionFilter($q, 'new'))
                ->count();
            $secondCount = (clone $this->buildCatalogQuery($ctx, $filters, ['condition']))
                ->tap(fn($q) => $this->applyConditionFilter($q, 'second'))
                ->count();

            if ($newCount > 0) {
                $conditions[] = ['value' => 'new', 'label' => 'Baru', 'count' => $newCount];
            }
            if ($secondCount > 0) {
                $conditions[] = ['value' => 'second', 'label' => 'Second / Bekas', 'count' => $secondCount];
            }
        }

        // 6. Facet Price Range (terapkan semua filter aktif KECUALI min_price & max_price)
        $priceRange = ['min' => 0.0, 'max' => 0.0];
        if (!$filters['in_stock_only'] || !empty($ctx['filter_warehouse_ids'])) {
            $priceRow = $this->buildCatalogQuery($ctx, $filters, ['min_price', 'max_price'])
                ->selectRaw('MIN(base_price) as min_price, MAX(base_price) as max_price')
                ->first();
            $priceRange = [
                'min' => $priceRow && $priceRow->min_price !== null ? (float) $priceRow->min_price : 0.0,
                'max' => $priceRow && $priceRow->max_price !== null ? (float) $priceRow->max_price : 0.0,
            ];
        }

        // 7. Facet Warehouses & Cities (gunakan semua gudang mode fulfillment tanpa filter warehouse_id & city)
        $baseWarehouseFilters = array_merge($filters, ['warehouse_ids' => [], 'cities' => []]);
        $unfilteredWhCtx = $this->resolveWarehouseContext($baseWarehouseFilters);
        $candidateWhIds = $unfilteredWhCtx['filter_warehouse_ids'];

        $warehousesFacet = [];
        $citiesFacet = [];
        if (!empty($candidateWhIds)) {
            // Dapatkan ID produk yang cocok dengan filter non-lokasi
            $matchingProductIdsQuery = $this->buildCatalogQuery(
                $unfilteredWhCtx,
                $baseWarehouseFilters,
                ['in_stock_only']
            )->select('product_accurates.id');

            $whCountRows = DB::table('warehouse_stocks as ws')
                ->whereIn('ws.warehouse_id', $candidateWhIds)
                ->whereIn('ws.variant_type', self::VARIANT_TYPES)
                ->where('ws.stock', '>', 0)
                ->whereIn('ws.variant_id', $matchingProductIdsQuery)
                ->selectRaw('ws.warehouse_id, COUNT(DISTINCT ws.variant_id) as product_count')
                ->groupBy('ws.warehouse_id')
                ->get()
                ->keyBy('warehouse_id');

            $cityProductIds = DB::table('warehouse_stocks as ws')
                ->join('warehouses as w', 'w.id', '=', 'ws.warehouse_id')
                ->whereIn('ws.warehouse_id', $candidateWhIds)
                ->whereIn('ws.variant_type', self::VARIANT_TYPES)
                ->where('ws.stock', '>', 0)
                ->whereNotNull('w.city')
                ->where('w.city', '!=', '')
                ->whereIn('ws.variant_id', $matchingProductIdsQuery)
                ->selectRaw('TRIM(w.city) as city_name, COUNT(DISTINCT ws.variant_id) as product_count')
                ->groupBy(DB::raw('TRIM(w.city)'))
                ->get();

            $cityMap = [];
            foreach ($cityProductIds as $row) {
                $key = mb_strtolower(trim((string) $row->city_name));
                if ($key === '') {
                    continue;
                }
                if (!isset($cityMap[$key])) {
                    $cityMap[$key] = ['name' => trim((string) $row->city_name), 'count' => 0];
                }
                $cityMap[$key]['count'] += (int) $row->product_count;
            }
            uasort($cityMap, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            $citiesFacet = array_values($cityMap);

            $warehousesFacet = $unfilteredWhCtx['mode_warehouses']->map(function ($wh) use ($whCountRows) {
                $count = (int) ($whCountRows->get($wh->id)->product_count ?? 0);
                return [
                    'id' => $wh->id,
                    'business_unit_id' => $wh->business_unit_id,
                    'name' => $wh->name,
                    'city' => $wh->city,
                    'allow_store_pickup' => (bool) $wh->allow_store_pickup,
                    'allow_online_delivery' => (bool) $wh->allow_online_delivery,
                    'count' => $count,
                ];
            })->values()->all();
        }

        return response()->json([
            'success' => true,
            'message' => 'Opsi filter katalog berhasil dimuat.',
            'data' => [
                'categories' => $categories,
                'brands' => $brands,
                'proyeks' => $proyeks,
                'os' => $osList,
                'conditions' => $conditions,
                'warehouses' => $warehousesFacet,
                'cities' => $citiesFacet,
                'price_range' => $priceRange,
                'sort_options' => $this->getSortOptions(),
            ],
        ]);
    }

    /**
     * Mengambil detail lengkap spesifikasi, foto, ketersediaan stok Multi-Warehouse (Live Accurate Cached 30s),
     * serta preview unit QC (khusus GSK Second dari tabel `device_inspections`).
     * Mendukung lookup via numeric ID, item_no (SKU), maupun SEO slug (`nama-produk-{id}`).
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->resolveProductByIdentifier($id, [
            'businessUnit.media',
            'media',
            'product.media',
            'productVariants.media',
            'secondProductVariants.media',
        ]);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        // Validasi kurasi kategori & merek aktif untuk Unit Usaha produk ini
        if ($product->business_unit_id) {
            $cats = $this->getActiveCategoriesForBu($product->business_unit_id);
            if ($cats !== null && !$this->inArrayCaseInsensitive((string) $product->categoryName, $cats)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk ini tidak tersedia untuk e-commerce.',
                ], 404);
            }

            $brands = $this->getActiveBrandsForBu($product->business_unit_id);
            if ($brands !== null && !$this->inArrayCaseInsensitive((string) $product->brandName, $brands)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk ini tidak tersedia untuk e-commerce.',
                ], 404);
            }
        }

        // Hybrid Stock Check: Live Accurate dengan Cache TTL 30s + dikurangi reservasi online lokal
        $stockBreakdown = $this->onlineStockService->getProductStockBreakdown($product, refreshLiveFromAccurate: true);

        // Kumpulkan semua foto produk
        $images = $this->collectAllImages($product);

        // Transparansi Unit GSK Second dari `device_inspections`
        $isSecond = $this->secondUnitService->isSecondProduct($product);
        $secondUnits = $isSecond ? $this->secondUnitService->getAvailableUnitsForProduct($product) : [];
        $unitsWithQcCount = count(array_filter($secondUnits, fn($u) => !empty($u['has_qc_report'])));

        // Cek Flash Sale aktif untuk produk ini
        $flashMap = $this->getActiveFlashSaleMap([$product->id]);
        $flashInfo = $flashMap[$product->id] ?? null;
        $basePrice = (float) $product->base_price;
        $flashPrice = $flashInfo ? (float) $flashInfo['flash_sale_price'] : null;

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
                'slug' => $this->makeProductSlug($product),
                'item_no' => $product->item_no,
                'name' => $product->name,
                'category' => $product->categoryName,
                'brand' => $product->brandName,
                'proyek' => $product->proyek,
                'os' => $product->os,
                'condition' => $isSecond ? 'second' : 'new',
                'is_addon' => (bool) $product->is_addon,
                'price' => $basePrice,
                'formatted_price' => 'Rp ' . number_format($basePrice, 0, ',', '.'),
                'is_flash_sale' => $flashInfo !== null,
                'flash_sale_price' => $flashPrice,
                'formatted_flash_sale_price' => $flashPrice !== null ? 'Rp ' . number_format($flashPrice, 0, ',', '.') : null,
                'original_price' => $flashInfo ? (float) $flashInfo['original_price'] : $basePrice,
                'discount_percent' => $flashInfo ? (int) $flashInfo['discount_percent'] : 0,
                'stock' => (int) $stockBreakdown['total_available_stock'],
                'total_delivery_stock' => (int) $stockBreakdown['total_delivery_stock'],
                'total_pickup_stock' => (int) $stockBreakdown['total_pickup_stock'],
                'stock_source' => $stockBreakdown['stock_source'],
                'has_sn' => (bool) $product->has_sn,
                'is_in_stock' => $stockBreakdown['total_available_stock'] > 0,
                'is_second_unit' => $isSecond,
                'requires_unit_selection' => false,
                'supports_unit_selection' => $isSecond && count($secondUnits) > 0,
                'units_with_qc_count' => $unitsWithQcCount,
                'available_units_preview' => array_slice($secondUnits, 0, 6),
                'images' => $images,
                'description' => $product->product?->description ?? ($product->raw_data['detailNotes'] ?? null),
                'business_unit' => $this->formatBusinessUnit($product->businessUnit),
                'pickup_warehouses' => $stockBreakdown['pickup_warehouses'],
                'delivery_warehouses' => $stockBreakdown['delivery_warehouses'],
                'stock_per_warehouse' => $legacyStockBreakdown,
            ],
        ]);
    }

    /**
     * Mengambil daftar kategori produk yang tersedia di online store (Multi-Warehouse).
     * Backward-compatible: default mengembalikan array string terurut abjad.
     * Tambahan: mendukung seluruh cross-filter (brand, warehouse_id, city, dll.) + ?with_count=1.
     */
    public function categories(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $ctx = $this->resolveWarehouseContext($filters);
        $withCount = $this->parseBool($request->query('with_count'), false);

        if ($filters['in_stock_only'] && empty($ctx['filter_warehouse_ids'])) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $facets = $this->aggregateStringFacet(
            $this->buildCatalogQuery($ctx, $filters, ['category']),
            'categoryName'
        );

        return response()->json([
            'success' => true,
            'data' => $withCount ? $facets : array_column($facets, 'name'),
        ]);
    }

    /**
     * Mengambil daftar merek produk yang tersedia di online store (Multi-Warehouse).
     * Backward-compatible: default mengembalikan array string terurut abjad & deduplikasi case-insensitive.
     * Tambahan: mendukung seluruh cross-filter (category, warehouse_id, city, dll.) + ?with_count=1.
     */
    public function brands(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $ctx = $this->resolveWarehouseContext($filters);
        $withCount = $this->parseBool($request->query('with_count'), false);

        if ($filters['in_stock_only'] && empty($ctx['filter_warehouse_ids'])) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $facets = $this->aggregateStringFacet(
            $this->buildCatalogQuery($ctx, $filters, ['brand']),
            'brandName'
        );

        return response()->json([
            'success' => true,
            'data' => $withCount ? $facets : array_column($facets, 'name'),
        ]);
    }

    /**
     * Endpoint daftar unit fisik & transparansi kondisi QC (`device_inspections`) untuk GSK Second.
     * Query params opsional:
     *   - warehouse_id       : int atau CSV ("1,3")
     *   - city               : string atau CSV ("Banjarmasin")
     *   - has_qc_report      : boolean (1/true)
     *   - min_battery_health : int (contoh: 85 -> hanya unit dengan BH >= 85%)
     *   - qc_status          : string atau CSV (contoh: "passed")
     *   - sort               : warehouse | battery_desc | battery_asc | newest
     */
    public function serialNumbers(Request $request, string $id): JsonResponse
    {
        $product = $this->resolveProductByIdentifier($id, ['businessUnit']);

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

        $warehouseIds = $this->parseIntList($request->query('warehouse_id'));
        $cities = array_map('mb_strtolower', $this->parseCsvList($request->query('city')));
        $hasQcReport = $request->query('has_qc_report') !== null
            ? $this->parseBool($request->query('has_qc_report'), false)
            : null;
        $minBatteryHealth = is_numeric($request->query('min_battery_health'))
            ? (int) $request->query('min_battery_health')
            : null;
        $qcStatuses = array_map('mb_strtolower', $this->parseCsvList($request->query('qc_status')));
        $sort = strtolower(trim((string) $request->query('sort', 'warehouse')));

        // Jika hanya 1 warehouse_id, push filter ke SQL langsung
        $singleWhId = count($warehouseIds) === 1 ? $warehouseIds[0] : null;
        $units = collect($this->secondUnitService->getAvailableUnitsForProduct($product, $singleWhId));

        if (count($warehouseIds) > 1) {
            $units = $units->filter(fn($u) => in_array((int) ($u['warehouse_id'] ?? 0), $warehouseIds, true));
        }

        if (!empty($cities)) {
            $units = $units->filter(function ($u) use ($cities) {
                $whCity = mb_strtolower(trim((string) ($u['warehouse_city'] ?? '')));
                return $whCity !== '' && in_array($whCity, $cities, true);
            });
        }

        if ($hasQcReport !== null) {
            $units = $units->filter(fn($u) => (bool) ($u['has_qc_report'] ?? false) === $hasQcReport);
        }

        if ($minBatteryHealth !== null) {
            $units = $units->filter(function ($u) use ($minBatteryHealth) {
                $bh = $this->parseBatteryHealthNumber($u['battery_health'] ?? null);
                return $bh !== null && $bh >= $minBatteryHealth;
            });
        }

        if (!empty($qcStatuses)) {
            $units = $units->filter(function ($u) use ($qcStatuses) {
                $status = mb_strtolower(trim((string) ($u['qc_status'] ?? '')));
                $verdict = mb_strtolower(trim((string) ($u['qc_verdict'] ?? '')));
                return in_array($status, $qcStatuses, true) || in_array($verdict, $qcStatuses, true);
            });
        }

        $units = match ($sort) {
            'battery_desc' => $units->sortByDesc(fn($u) => $this->parseBatteryHealthNumber($u['battery_health'] ?? null) ?? -1),
            'battery_asc' => $units->sortBy(fn($u) => $this->parseBatteryHealthNumber($u['battery_health'] ?? null) ?? 999),
            'newest' => $units->sortByDesc(fn($u) => $u['inspected_at'] ?? ($u['receipt_date'] ?? '')),
            default => $units,
        };

        $finalUnits = $units->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'Daftar unit fisik & kondisi QC GSK Second berhasil dimuat.',
            'is_second_unit' => true,
            'requires_unit_selection' => false,
            'supports_unit_selection' => count($finalUnits) > 0,
            'total_units' => count($finalUnits),
            'data' => $finalUnits,
        ]);
    }

    // =========================================================================
    // INTERNAL FILTER, FACET & QUERY HELPERS
    // =========================================================================

    protected function extractFilters(Request $request): array
    {
        $fulfillmentType = strtolower(trim((string) $request->query('fulfillment_type', 'any')));
        if (!in_array($fulfillmentType, ['delivery', 'pickup', 'any'], true)) {
            $fulfillmentType = 'any';
        }

        $condition = strtolower(trim((string) $request->query('condition', '')));
        if (!in_array($condition, ['new', 'second'], true)) {
            $condition = null;
        }

        $rawSort = strtolower(trim((string) $request->query('sort', 'latest')));
        // Alias backward-compatible untuk build Mobile App lama
        $sortAliases = [
            'price_low' => 'price_asc',
            'price_high' => 'price_desc',
            'popular' => 'best_seller',
        ];
        $sort = $sortAliases[$rawSort] ?? $rawSort;
        $allowedSorts = ['latest', 'price_asc', 'price_desc', 'name_asc', 'name_desc', 'stock_desc', 'best_seller'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        return [
            'business_unit_ids' => $this->parseIntList($request->query('business_unit_id')),
            'fulfillment_type' => $fulfillmentType,
            'warehouse_ids' => $this->parseIntList($request->query('warehouse_id')),
            'cities' => $this->parseCsvList($request->query('city')),
            'search' => trim((string) $request->query('search', '')),
            'categories' => $this->parseCsvList($request->query('category')),
            'brands' => $this->parseCsvList($request->query('brand')),
            'proyeks' => $this->parseCsvList($request->query('proyek')),
            'os_list' => $this->parseCsvList($request->query('os')),
            'condition' => $condition,
            'min_price' => is_numeric($request->query('min_price')) ? (float) $request->query('min_price') : null,
            'max_price' => is_numeric($request->query('max_price')) ? (float) $request->query('max_price') : null,
            'on_flash_sale' => $this->parseBool($request->query('on_flash_sale'), false),
            'has_qc_report' => $this->parseBool($request->query('has_qc_report'), false),
            'in_stock_only' => $this->parseBool($request->query('in_stock_only'), true),
            'exclude_addon' => $this->parseBool($request->query('exclude_addon'), true),
            'sort' => $sort,
        ];
    }

    /**
     * Resolusi daftar gudang aktif untuk delivery, pickup, dan target filter berdasarkan BU, fulfillment_type, warehouse_id, dan city.
     */
    protected function resolveWarehouseContext(array $filters): array
    {
        $buArg = !empty($filters['business_unit_ids']) ? $filters['business_unit_ids'] : null;
        $cityArg = !empty($filters['cities']) ? $filters['cities'] : null;

        $deliveryWarehouses = $this->onlineStockService->getOnlineWarehouses($buArg, 'delivery', $cityArg);
        $pickupWarehouses = $this->onlineStockService->getOnlineWarehouses($buArg, 'pickup', $cityArg);

        $deliveryWarehouseIds = $deliveryWarehouses->pluck('id')->map(fn($id) => (int) $id)->all();
        $pickupWarehouseIds = $pickupWarehouses->pluck('id')->map(fn($id) => (int) $id)->all();
        $allOnlineIds = array_values(array_unique(array_merge($deliveryWarehouseIds, $pickupWarehouseIds)));

        $fulfillmentType = $filters['fulfillment_type'];
        if ($fulfillmentType === 'delivery') {
            $modeWarehouses = $deliveryWarehouses;
            $baseModeIds = $deliveryWarehouseIds;
        } elseif ($fulfillmentType === 'pickup') {
            $modeWarehouses = $pickupWarehouses;
            $baseModeIds = $pickupWarehouseIds;
        } else {
            $modeWarehouses = $deliveryWarehouses->merge($pickupWarehouses)->unique('id')->values();
            $baseModeIds = $allOnlineIds;
        }

        if (!empty($filters['warehouse_ids'])) {
            $filterWarehouseIds = array_values(array_intersect($baseModeIds, $filters['warehouse_ids']));
        } else {
            $filterWarehouseIds = $baseModeIds;
        }

        return [
            'delivery_warehouse_ids' => $deliveryWarehouseIds,
            'pickup_warehouse_ids' => $pickupWarehouseIds,
            'all_online_warehouse_ids' => $allOnlineIds,
            'filter_warehouse_ids' => $filterWarehouseIds,
            'mode_warehouses' => $modeWarehouses,
        ];
    }

    /**
     * Bangun Eloquent query katalog yang konsisten untuk index(), filters(), categories(), dan brands().
     * Parameter $excludeKeys memungkinkan cross-faceted counting (misal: hitung brand tanpa memotong dengan filter brand aktif).
     */
    protected function buildCatalogQuery(array $ctx, array $filters, array $excludeKeys = []): Builder
    {
        $query = ProductAccurate::query()
            ->where('product_accurates.base_price', '>', 0);

        // 1. Kecualikan Add-On secara default agar tidak bocor ke katalog utama
        if ($filters['exclude_addon'] && !in_array('exclude_addon', $excludeKeys, true)) {
            $query->where(function ($q) {
                $q->whereNull('product_accurates.is_addon')
                    ->orWhere('product_accurates.is_addon', false)
                    ->orWhere('product_accurates.is_addon', 0);
            });
        }

        // 2. Filter Stok ATP > 0 (Stok Fisik - Reservasi Online Aktif) langsung di SQL
        if ($filters['in_stock_only'] && !in_array('in_stock_only', $excludeKeys, true)) {
            $this->applyAtpInStockFilter($query, $ctx['filter_warehouse_ids'], $filters['fulfillment_type']);
        }

        // 3. Filter Business Unit & Visibilitas Mobile/Web
        if (!empty($filters['business_unit_ids'])) {
            $query->whereIn('product_accurates.business_unit_id', $filters['business_unit_ids']);
        } else {
            $query->whereHas('businessUnit', function ($buQuery) {
                $buQuery->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        // 4. Kurasi Kategori & Merek Aktif per Unit Usaha
        $this->applyCurationFilters($query, $filters['business_unit_ids']);

        // 5. Tokenized Multi-Word Search (misal: "iphone 13 128" cocok ke "HP 2ND IPHONE 13 - 128GB - MIDNIGHT")
        if ($filters['search'] !== '' && !in_array('search', $excludeKeys, true)) {
            $tokens = array_slice(
                array_values(array_filter(preg_split('/\s+/', $filters['search']) ?: [])),
                0,
                8
            );

            foreach ($tokens as $token) {
                $like = '%' . mb_strtolower($token) . '%';
                $query->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(product_accurates.name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(product_accurates.item_no) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(product_accurates.brandName, \'\')) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(product_accurates.categoryName, \'\')) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(product_accurates.proyek, \'\')) LIKE ?', [$like]);
                });
            }
        }

        // 6. Multi-Select Category (Case-insensitive exact match)
        if (!empty($filters['categories']) && !in_array('category', $excludeKeys, true)) {
            $this->applyCaseInsensitiveIn($query, 'product_accurates.categoryName', $filters['categories']);
        }

        // 7. Multi-Select Brand (Case-insensitive exact match -> menyatukan APPLE & Apple)
        if (!empty($filters['brands']) && !in_array('brand', $excludeKeys, true)) {
            $this->applyCaseInsensitiveIn($query, 'product_accurates.brandName', $filters['brands']);
        }

        // 8. Multi-Select Proyek / Garansi (RESMI, INTER, ACCESSORIES, SAB, SJU, dll.)
        if (!empty($filters['proyeks']) && !in_array('proyek', $excludeKeys, true)) {
            $this->applyCaseInsensitiveIn($query, 'product_accurates.proyek', $filters['proyeks']);
        }

        // 9. Multi-Select OS (IOS, ANDROID, NON)
        if (!empty($filters['os_list']) && !in_array('os', $excludeKeys, true)) {
            $this->applyCaseInsensitiveIn($query, 'product_accurates.os', $filters['os_list']);
        }

        // 10. Filter Kondisi (new | second)
        if ($filters['condition'] !== null && !in_array('condition', $excludeKeys, true)) {
            $this->applyConditionFilter($query, $filters['condition']);
        }

        // 11. Filter Rentang Harga
        if ($filters['min_price'] !== null && !in_array('min_price', $excludeKeys, true)) {
            $query->where('product_accurates.base_price', '>=', $filters['min_price']);
        }
        if ($filters['max_price'] !== null && !in_array('max_price', $excludeKeys, true)) {
            $query->where('product_accurates.base_price', '<=', $filters['max_price']);
        }

        // 12. Filter Sedang Flash Sale Aktif
        if ($filters['on_flash_sale'] && !in_array('on_flash_sale', $excludeKeys, true)) {
            if ($this->hasFlashSaleTables()) {
                $now = now()->toDateTimeString();
                $query->whereExists(function ($sub) use ($now) {
                    $sub->select(DB::raw(1))
                        ->from('ecommerce_flash_sale_items as fsi')
                        ->join('ecommerce_flash_sales as fs', 'fs.id', '=', 'fsi.flash_sale_id')
                        ->whereColumn('fsi.product_accurate_id', 'product_accurates.id')
                        ->where('fsi.is_active', true)
                        ->whereColumn('fsi.quota_stock', '>', 'fsi.sold_stock')
                        ->where('fs.is_active', true)
                        ->where('fs.start_time', '<=', $now)
                        ->where('fs.end_time', '>=', $now);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // 13. Filter Khusus Unit yang Sudah Memiliki Laporan QC (device_inspections)
        if ($filters['has_qc_report'] && !in_array('has_qc_report', $excludeKeys, true)) {
            $whIds = $ctx['filter_warehouse_ids'];
            if (empty($whIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereExists(function ($sub) use ($whIds) {
                    $sub->select(DB::raw(1))
                        ->from('product_serial_numbers as psn')
                        ->join('device_inspections as di', 'di.imei', '=', 'psn.serial_number')
                        ->where('psn.status', 'Available')
                        ->whereIn('psn.warehouse_id', $whIds)
                        ->where(function ($matchQ) {
                            $matchQ->whereColumn('psn.product_accurate_id', 'product_accurates.id')
                                ->orWhere(function ($skuQ) {
                                    $skuQ->whereColumn('psn.item_no', 'product_accurates.item_no')
                                        ->whereColumn('psn.business_unit_id', 'product_accurates.business_unit_id');
                                });
                        });
                });
            }
        }

        return $query;
    }

    /**
     * Filter SQL ATP > 0: (Total Stok Fisik di Gudang Terpilih - Total Reservasi Online Aktif) > 0.
     * Menjamin produk yang stoknya habis karena sedang di-booking tidak tampil dengan stock: 0 di pagination.
     */
    protected function applyAtpInStockFilter(Builder $query, array $warehouseIds, string $fulfillmentType): void
    {
        if (empty($warehouseIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $whPlaceholders = implode(',', array_fill(0, count($warehouseIds), '?'));
        $vtPlaceholders = implode(',', array_fill(0, count(self::VARIANT_TYPES), '?'));
        $nowStr = now()->toDateTimeString();

        // Pada mode delivery, reservasi pool (warehouse_id IS NULL) ikut mengurangi kuota delivery
        $reservationWhClause = $fulfillmentType === 'delivery'
            ? "(osr.warehouse_id IS NULL OR osr.warehouse_id IN ({$whPlaceholders}))"
            : "osr.warehouse_id IN ({$whPlaceholders})";

        $sql = "(
            COALESCE((
                SELECT SUM(ws.stock)
                FROM warehouse_stocks ws
                WHERE ws.variant_id = product_accurates.id
                  AND ws.variant_type IN ({$vtPlaceholders})
                  AND ws.warehouse_id IN ({$whPlaceholders})
                  AND ws.stock > 0
            ), 0)
            -
            COALESCE((
                SELECT SUM(osr.qty)
                FROM online_stock_reservations osr
                WHERE osr.product_accurate_id = product_accurates.id
                  AND osr.status = 'active'
                  AND osr.expires_at > ?
                  AND {$reservationWhClause}
            ), 0)
        ) > 0";

        $bindings = array_merge(
            self::VARIANT_TYPES,
            $warehouseIds,
            [$nowStr],
            $warehouseIds
        );

        $query->whereRaw($sql, $bindings);
    }

    protected function applyConditionFilter(Builder $query, string $condition): void
    {
        $secondClause = function ($q) {
            $q->where('product_accurates.business_unit_id', 2)
                ->orWhereRaw("UPPER(COALESCE(product_accurates.name, '')) LIKE '%HP 2ND%'")
                ->orWhereRaw("UPPER(COALESCE(product_accurates.name, '')) LIKE '%SECOND%'")
                ->orWhereRaw("UPPER(COALESCE(product_accurates.categoryName, '')) LIKE '%SECOND%'")
                ->orWhereHas('businessUnit', function ($buQ) {
                    $buQ->whereRaw("UPPER(COALESCE(mobile_category, '')) = 'SECOND'")
                        ->orWhereRaw("UPPER(COALESCE(code, '')) = 'SECOND'")
                        ->orWhereRaw("UPPER(COALESCE(name, '')) LIKE '%SECOND%'");
                });
        };

        if ($condition === 'second') {
            $query->where($secondClause);
        } else {
            $query->whereNot($secondClause);
        }
    }

    protected function applySorting(Builder $query, string $sort, array $warehouseIds): void
    {
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('product_accurates.base_price', 'asc')
                    ->orderByDesc('product_accurates.id');
                break;
            case 'price_desc':
                $query->orderBy('product_accurates.base_price', 'desc')
                    ->orderByDesc('product_accurates.id');
                break;
            case 'name_asc':
                $query->orderBy('product_accurates.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('product_accurates.name', 'desc');
                break;
            case 'stock_desc':
                if (!empty($warehouseIds)) {
                    $whPlaceholders = implode(',', array_fill(0, count($warehouseIds), '?'));
                    $vtPlaceholders = implode(',', array_fill(0, count(self::VARIANT_TYPES), '?'));
                    $stockSub = "COALESCE((
                        SELECT SUM(ws.stock)
                        FROM warehouse_stocks ws
                        WHERE ws.variant_id = product_accurates.id
                          AND ws.variant_type IN ({$vtPlaceholders})
                          AND ws.warehouse_id IN ({$whPlaceholders})
                          AND ws.stock > 0
                    ), 0)";
                    $query->orderByRaw("{$stockSub} DESC", array_merge(self::VARIANT_TYPES, $warehouseIds))
                        ->orderByDesc('product_accurates.id');
                } else {
                    $query->orderByDesc('product_accurates.stock')
                        ->orderByDesc('product_accurates.id');
                }
                break;
            case 'best_seller':
                $vtPlaceholders = implode(',', array_fill(0, count(self::VARIANT_TYPES), '?'));
                $soldSub = "COALESCE((
                    SELECT SUM(oi.qty)
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE oi.product_variant_id = product_accurates.id
                      AND oi.product_variant_type IN ({$vtPlaceholders})
                      AND UPPER(COALESCE(o.order_status, '')) IN ('PAID', 'PROCESSING', 'SHIPPED', 'COMPLETED')
                ), 0)";
                $query->orderByRaw("{$soldSub} DESC", self::VARIANT_TYPES)
                    ->orderByDesc('product_accurates.id');
                break;
            default:
                $query->orderByDesc('product_accurates.id');
                break;
        }
    }

    /**
     * Agregasi facet teks dengan normalisasi case-insensitive (menggabungkan APPLE & Apple) + count.
     *
     * @return array<int, array{name: string, count: int}>
     */
    protected function aggregateStringFacet(Builder $query, string $column): array
    {
        $rows = $query
            ->whereNotNull("product_accurates.{$column}")
            ->where("product_accurates.{$column}", '!=', '')
            ->selectRaw("TRIM(product_accurates.{$column}) as facet_value, COUNT(*) as total_count")
            ->groupBy(DB::raw("TRIM(product_accurates.{$column})"))
            ->get();

        // Gabungkan label yang hanya berbeda kapitalisasi (pilih ejaan yang populasinya terbanyak)
        $merged = [];
        foreach ($rows as $row) {
            $label = trim((string) $row->facet_value);
            if ($label === '') {
                continue;
            }
            $key = mb_strtolower($label);
            $cnt = (int) $row->total_count;

            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'name' => $label,
                    'dominant_count' => $cnt,
                    'count' => $cnt,
                ];
            } else {
                $merged[$key]['count'] += $cnt;
                if ($cnt > $merged[$key]['dominant_count']) {
                    $merged[$key]['name'] = $label;
                    $merged[$key]['dominant_count'] = $cnt;
                }
            }
        }

        uasort($merged, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return array_values(array_map(fn($entry) => [
            'name' => $entry['name'],
            'count' => $entry['count'],
        ], $merged));
    }

    /**
     * Ambil peta harga Flash Sale aktif secara batch untuk array ID produk (0 N+1).
     */
    protected function getActiveFlashSaleMap(array $productIds): array
    {
        if (empty($productIds) || !$this->hasFlashSaleTables()) {
            return [];
        }

        $now = now();
        $items = EcommerceFlashSaleItem::query()
            ->whereIn('product_accurate_id', $productIds)
            ->where('is_active', true)
            ->whereColumn('quota_stock', '>', 'sold_stock')
            ->whereHas('flashSale', function ($q) use ($now) {
                $q->where('is_active', true)
                    ->where('start_time', '<=', $now)
                    ->where('end_time', '>=', $now);
            })
            ->orderBy('flash_sale_price', 'asc')
            ->get();

        $map = [];
        foreach ($items as $item) {
            $pId = (int) $item->product_accurate_id;
            if (isset($map[$pId])) {
                continue;
            }
            $orig = (float) ($item->original_price ?: $item->flash_sale_price);
            $sale = (float) $item->flash_sale_price;
            $discount = $orig > $sale ? (int) round((($orig - $sale) / $orig) * 100) : 0;

            $map[$pId] = [
                'flash_sale_id' => $item->flash_sale_id,
                'flash_sale_price' => $sale,
                'original_price' => $orig,
                'discount_percent' => $discount,
                'available_quota' => max(0, (int) $item->quota_stock - (int) $item->sold_stock),
            ];
        }

        return $map;
    }

    protected function hasFlashSaleTables(): bool
    {
        if (self::$hasFlashSaleTables === null) {
            self::$hasFlashSaleTables = Schema::hasTable('ecommerce_flash_sales')
                && Schema::hasTable('ecommerce_flash_sale_items');
        }
        return self::$hasFlashSaleTables;
    }

    /**
     * Cari produk berdasarkan numeric ID, item_no (SKU), atau Web SEO slug (`iphone-13-128gb-123`).
     */
    protected function resolveProductByIdentifier(string $identifier, array $relations = []): ?ProductAccurate
    {
        $clean = trim($identifier);
        if ($clean === '') {
            return null;
        }

        $query = ProductAccurate::with($relations);

        if (ctype_digit($clean)) {
            return $query->find((int) $clean);
        }

        // Cek exact match item_no terlebih dahulu (case-insensitive)
        $bySku = (clone $query)
            ->whereRaw('LOWER(item_no) = ?', [mb_strtolower($clean)])
            ->first();
        if ($bySku) {
            return $bySku;
        }

        // Fallback Web SEO slug berakhiran `-{id}` (contoh: hp-2nd-iphone-13-128gb-452)
        if (preg_match('/-(\d+)$/', $clean, $matches)) {
            return (clone $query)->find((int) $matches[1]);
        }

        return null;
    }

    protected function makeProductSlug(ProductAccurate $product): string
    {
        $base = Str::slug((string) ($product->name ?: $product->item_no ?: 'produk'));
        return ($base !== '' ? $base : 'produk') . '-' . $product->id;
    }

    protected function resolvePrimaryImage(ProductAccurate $product): ?string
    {
        if ($product->hasMedia('cover')) {
            return $product->getFirstMediaUrl('cover');
        }
        if ($product->product && $product->product->hasMedia('cover')) {
            return $product->product->getFirstMediaUrl('cover');
        }
        if ($product->productVariants->isNotEmpty()) {
            $firstVariant = $product->productVariants->first();
            if ($firstVariant && $firstVariant->hasMedia('variant_image')) {
                return $firstVariant->getFirstMediaUrl('variant_image');
            }
        }
        if ($product->secondProductVariants->isNotEmpty()) {
            $firstSecondVariant = $product->secondProductVariants->first();
            if ($firstSecondVariant && $firstSecondVariant->hasMedia('variant_image')) {
                return $firstSecondVariant->getFirstMediaUrl('variant_image');
            }
        }
        return null;
    }

    protected function collectAllImages(ProductAccurate $product): array
    {
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
        return array_values(array_unique(array_filter($images)));
    }

    protected function formatBusinessUnit(?BusinessUnit $bu): ?array
    {
        if (!$bu) {
            return null;
        }
        return [
            'id' => $bu->id,
            'name' => $bu->mobile_display_name ?: $bu->name,
            'code' => $bu->code,
            'category' => $bu->mobile_category ?: 'GENERAL',
            'logo_url' => $bu->getFirstMediaUrl('logo') ?: null,
            'brand_color' => $bu->brand_color,
            'warranty_highlight' => $bu->warranty_highlight,
        ];
    }

    protected function formatAppliedFilters(array $filters): array
    {
        return [
            'business_unit_ids' => $filters['business_unit_ids'],
            'fulfillment_type' => $filters['fulfillment_type'],
            'warehouse_ids' => $filters['warehouse_ids'],
            'cities' => $filters['cities'],
            'search' => $filters['search'] !== '' ? $filters['search'] : null,
            'categories' => $filters['categories'],
            'brands' => $filters['brands'],
            'proyeks' => $filters['proyeks'],
            'os' => $filters['os_list'],
            'condition' => $filters['condition'],
            'min_price' => $filters['min_price'],
            'max_price' => $filters['max_price'],
            'on_flash_sale' => $filters['on_flash_sale'],
            'has_qc_report' => $filters['has_qc_report'],
            'in_stock_only' => $filters['in_stock_only'],
            'exclude_addon' => $filters['exclude_addon'],
            'sort' => $filters['sort'],
        ];
    }

    protected function getSortOptions(): array
    {
        return [
            ['value' => 'latest', 'label' => 'Terbaru'],
            ['value' => 'best_seller', 'label' => 'Terlaris'],
            ['value' => 'price_asc', 'label' => 'Harga Terendah'],
            ['value' => 'price_desc', 'label' => 'Harga Tertinggi'],
            ['value' => 'stock_desc', 'label' => 'Stok Terbanyak'],
            ['value' => 'name_asc', 'label' => 'Nama A-Z'],
            ['value' => 'name_desc', 'label' => 'Nama Z-A'],
        ];
    }

    protected function applyCaseInsensitiveIn(Builder $query, string $column, array $values): void
    {
        $lowerValues = array_values(array_unique(array_filter(array_map(
            fn($v) => mb_strtolower(trim((string) $v)),
            $values
        ))));
        if (empty($lowerValues)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($lowerValues), '?'));
        $query->whereRaw("LOWER(TRIM(COALESCE({$column}, ''))) IN ({$placeholders})", $lowerValues);
    }

    protected function inArrayCaseInsensitive(string $needle, array $haystack): bool
    {
        $lowerNeedle = mb_strtolower(trim($needle));
        foreach ($haystack as $item) {
            if (mb_strtolower(trim((string) $item)) === $lowerNeedle) {
                return true;
            }
        }
        return false;
    }

    protected function parseCsvList($value): array
    {
        if (is_array($value)) {
            $raw = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $raw = explode(',', $value);
        } else {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn($v) => trim((string) $v),
            $raw
        ), fn($v) => $v !== '')));
    }

    protected function parseIntList($value): array
    {
        $items = $this->parseCsvList($value);
        return array_values(array_unique(array_filter(array_map('intval', $items), fn($id) => $id > 0)));
    }

    protected function parseBool($value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? $default : $parsed;
    }

    protected function parseBatteryHealthNumber(?string $bh): ?int
    {
        if ($bh === null || trim($bh) === '') {
            return null;
        }
        if (preg_match('/(\d+)/', $bh, $matches)) {
            return (int) $matches[1];
        }
        return null;
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

    /**
     * Terapkan kurasi Kategori & Merek aktif per Business Unit (mendukung 1 atau multi BU).
     *
     * @param Builder $query
     * @param array<int> $businessUnitIds
     */
    protected function applyCurationFilters(Builder $query, array $businessUnitIds): void
    {
        $targetBus = !empty($businessUnitIds)
            ? BusinessUnit::whereIn('id', $businessUnitIds)->get()
            : BusinessUnit::where('is_active', true)->where('is_visible_mobile', true)->get();

        if ($targetBus->isEmpty()) {
            return;
        }

        $query->where(function ($rootQ) use ($targetBus) {
            foreach ($targetBus as $bu) {
                $cats = $this->getActiveCategoriesForBu($bu->id);
                $brands = $this->getActiveBrandsForBu($bu->id);

                $rootQ->orWhere(function ($buQ) use ($bu, $cats, $brands) {
                    $buQ->where('product_accurates.business_unit_id', $bu->id);

                    if ($cats !== null) {
                        $this->applyCaseInsensitiveIn($buQ, 'product_accurates.categoryName', $cats);
                    } else {
                        $buQ->where(function ($q) {
                            $q->whereNull('product_accurates.categoryName')
                                ->orWhere(function ($sub) {
                                    $sub->where('product_accurates.categoryName', 'not like', '%LDU%')
                                        ->where('product_accurates.categoryName', 'not like', '%DEMO%');
                                });
                        });
                    }

                    if ($brands !== null) {
                        $this->applyCaseInsensitiveIn($buQ, 'product_accurates.brandName', $brands);
                    }
                });
            }
        });
    }
}
