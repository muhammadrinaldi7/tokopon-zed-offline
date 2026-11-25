<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\OnlineStockReservation;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnlineStockService
{
    public const LIVE_CACHE_TTL_SECONDS = 30;

    protected AccurateService $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    /**
     * Ambil daftar gudang online aktif sesuai mode (delivery | pickup | any).
     * Mendukung single atau multi business_unit_id serta filter kota (case-insensitive).
     *
     * @param int|array<int>|null $businessUnitId
     * @param string $mode
     * @param array<string>|null $cities
     */
    public function getOnlineWarehouses(int|array|null $businessUnitId = null, string $mode = 'any', ?array $cities = null): Collection
    {
        $query = Warehouse::query();

        if ($mode === 'delivery') {
            $query->onlineDelivery();
        } elseif ($mode === 'pickup') {
            $query->storePickup();
        } else {
            $query->onlineStore();
        }

        if (is_array($businessUnitId)) {
            $buIds = array_values(array_unique(array_filter(array_map('intval', $businessUnitId), fn($id) => $id > 0)));
            if (!empty($buIds)) {
                $query->whereIn('business_unit_id', $buIds);
            } else {
                $query->whereHas('businessUnit', function ($buQ) {
                    $buQ->where('is_active', true)->where('is_visible_mobile', true);
                });
            }
        } elseif ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        } else {
            $query->whereHas('businessUnit', function ($buQ) {
                $buQ->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        if (!empty($cities)) {
            $lowerCities = array_values(array_unique(array_filter(array_map(
                fn($c) => mb_strtolower(trim((string) $c)),
                $cities
            ))));
            if (!empty($lowerCities)) {
                $placeholders = implode(',', array_fill(0, count($lowerCities), '?'));
                $query->whereRaw("LOWER(TRIM(city)) IN ({$placeholders})", $lowerCities);
            }
        }

        return $query->orderBy('fulfillment_priority')->orderBy('name')->get();
    }

    /**
     * Hitung Available-to-Promise (ATP) secara batch untuk listing katalog produk (< 50ms, tanpa N+1).
     *
     * Formula:
     *   ATP per gudang = max(0, physical_stock - reserved_for_warehouse)
     *   ATP Delivery   = max(0, sum(ATP per delivery warehouse) - reserved_delivery_pool)
     *
     * @param array<int> $productAccurateIds
     * @param array<int> $deliveryWarehouseIds
     * @param array<int> $pickupWarehouseIds
     * @return array<int, array{delivery_stock: int, pickup_stock_total: int, available_warehouse_count: int, per_warehouse: array<int, int>}>
     */
    public function getBatchAvailableStock(
        array $productAccurateIds,
        array $deliveryWarehouseIds,
        array $pickupWarehouseIds
    ): array {
        if (empty($productAccurateIds)) {
            return [];
        }

        $allWarehouseIds = array_values(array_unique(array_merge($deliveryWarehouseIds, $pickupWarehouseIds)));
        if (empty($allWarehouseIds)) {
            return [];
        }

        // 1. Ambil stok fisik dari warehouse_stocks (1 query)
        $physicalRows = WarehouseStock::query()
            ->whereIn('variant_id', $productAccurateIds)
            ->whereIn('variant_type', [
                ProductAccurate::class,
                'App\\Models\\ProductAccurate',
                'ProductAccurate',
            ])
            ->whereIn('warehouse_id', $allWarehouseIds)
            ->get(['variant_id', 'warehouse_id', 'stock']);

        $physicalMap = [];
        foreach ($physicalRows as $row) {
            $pId = (int) $row->variant_id;
            $wId = (int) $row->warehouse_id;
            $physicalMap[$pId][$wId] = ($physicalMap[$pId][$wId] ?? 0) + max(0, (int) $row->stock);
        }

        // 2. Ambil seluruh reservasi online yang masih ACTIVE & belum kadaluarsa (1 query)
        $reservationRows = OnlineStockReservation::query()
            ->active()
            ->whereIn('product_accurate_id', $productAccurateIds)
            ->selectRaw('product_accurate_id, warehouse_id, SUM(qty) as total_reserved')
            ->groupBy('product_accurate_id', 'warehouse_id')
            ->get();

        $reservedByWarehouse = [];
        $reservedPool = [];
        foreach ($reservationRows as $res) {
            $pId = (int) $res->product_accurate_id;
            $qty = (int) $res->total_reserved;
            if ($res->warehouse_id === null) {
                $reservedPool[$pId] = ($reservedPool[$pId] ?? 0) + $qty;
            } else {
                $wId = (int) $res->warehouse_id;
                $reservedByWarehouse[$pId][$wId] = ($reservedByWarehouse[$pId][$wId] ?? 0) + $qty;
            }
        }

        // 3. Rakit peta ATP per produk
        $result = [];
        foreach ($productAccurateIds as $pId) {
            $pId = (int) $pId;
            $perWh = [];
            $deliverySum = 0;
            $pickupSum = 0;
            $availableWhCount = 0;

            foreach ($allWarehouseIds as $wId) {
                $phys = $physicalMap[$pId][$wId] ?? 0;
                $resWh = $reservedByWarehouse[$pId][$wId] ?? 0;
                $net = max(0, $phys - $resWh);
                $perWh[$wId] = $net;

                if ($net > 0) {
                    $availableWhCount++;
                }
                if (in_array($wId, $deliveryWarehouseIds, true)) {
                    $deliverySum += $net;
                }
                if (in_array($wId, $pickupWarehouseIds, true)) {
                    $pickupSum += $net;
                }
            }

            $poolRes = $reservedPool[$pId] ?? 0;
            $deliveryStock = max(0, $deliverySum - $poolRes);

            $result[$pId] = [
                'delivery_stock' => $deliveryStock,
                'pickup_stock_total' => $pickupSum,
                'available_warehouse_count' => $availableWhCount,
                'per_warehouse' => $perWh,
            ];
        }

        return $result;
    }

    /**
     * Ambil rincian stok lengkap untuk 1 produk (Detail Produk & Validasi Checkout).
     * Dapat memicu Live Verification ke Accurate API dengan Cache TTL 30 detik.
     */
    public function getProductStockBreakdown(
        ProductAccurate $product,
        bool $refreshLiveFromAccurate = false,
        bool $forceRefresh = false
    ): array {
        $stockSource = 'local_db';
        if ($refreshLiveFromAccurate) {
            $liveResult = $this->refreshLiveStockForProduct($product, $forceRefresh);
            $stockSource = $liveResult['source'];
        }

        $buId = $product->business_unit_id ? (int) $product->business_unit_id : null;
        $deliveryWarehouses = $this->getOnlineWarehouses($buId, 'delivery');
        $pickupWarehouses = $this->getOnlineWarehouses($buId, 'pickup');

        $deliveryIds = $deliveryWarehouses->pluck('id')->map(fn($id) => (int) $id)->all();
        $pickupIds = $pickupWarehouses->pluck('id')->map(fn($id) => (int) $id)->all();

        $batch = $this->getBatchAvailableStock([$product->id], $deliveryIds, $pickupIds);
        $metrics = $batch[$product->id] ?? [
            'delivery_stock' => 0,
            'pickup_stock_total' => 0,
            'available_warehouse_count' => 0,
            'per_warehouse' => [],
        ];

        $perWh = $metrics['per_warehouse'];

        $pickupList = $pickupWarehouses->map(function (Warehouse $wh) use ($perWh) {
            $stock = (int) ($perWh[$wh->id] ?? 0);
            return [
                'warehouse_id' => $wh->id,
                'warehouse_name' => $wh->name,
                'city' => $wh->city,
                'address' => $wh->address,
                'phone' => $wh->phone,
                'operating_hours' => $wh->operating_hours,
                'latitude' => $wh->latitude,
                'longitude' => $wh->longitude,
                'stock' => $stock,
                'is_ready_today' => $stock > 0,
            ];
        })->values()->all();

        $deliveryList = $deliveryWarehouses->map(function (Warehouse $wh) use ($perWh) {
            return [
                'warehouse_id' => $wh->id,
                'warehouse_name' => $wh->name,
                'priority' => (int) ($wh->fulfillment_priority ?? 100),
                'stock' => (int) ($perWh[$wh->id] ?? 0),
            ];
        })->values()->all();

        return [
            'stock_source' => $stockSource,
            'total_delivery_stock' => (int) $metrics['delivery_stock'],
            'total_pickup_stock' => (int) $metrics['pickup_stock_total'],
            'total_available_stock' => max((int) $metrics['delivery_stock'], (int) $metrics['pickup_stock_total']),
            'per_warehouse_map' => $perWh,
            'pickup_warehouses' => $pickupList,
            'delivery_warehouses' => $deliveryList,
        ];
    }

    /**
     * Live Verification ke Accurate Online untuk 1 SKU (dibungkus Cache 30 detik).
     * Sekaligus memperbarui tabel `warehouse_stocks` lokal secara oportunistik.
     * Jika Accurate timeout/error/tidak ada kredensial, fallback mulus ke DB lokal.
     */
    public function refreshLiveStockForProduct(ProductAccurate $product, bool $force = false): array
    {
        $sku = trim((string) $product->item_no);
        if ($sku === '') {
            return ['synced' => false, 'source' => 'local_db'];
        }

        $bu = $product->businessUnit ?: BusinessUnit::find($product->business_unit_id);
        $dbSource = $bu?->code ?: ($product->database_source ?: 'syihab');

        // Skip jika kredensial Accurate BU belum dikonfigurasi (misal environment lokal/testing)
        if (!$bu || empty($bu->accurate_token)) {
            return ['synced' => false, 'source' => 'local_db'];
        }

        $cacheKey = "online_live_stock:{$dbSource}:{$sku}";
        if (!$force && Cache::has($cacheKey)) {
            return ['synced' => true, 'source' => 'accurate_live_cached'];
        }

        try {
            // 1 panggilan HTTP /item/detail.do menarik stok seluruh gudang untuk SKU ini sekaligus!
            $detail = $this->accurateService->itemDetailDo($sku, $dbSource);
            $warehouseRows = $detail['detailWarehouseData'] ?? [];

            if (is_array($warehouseRows) && !empty($warehouseRows)) {
                $updatedWarehouses = 0;
                foreach ($warehouseRows as $wRow) {
                    $whName = $wRow['warehouseName'] ?? ($wRow['warehouse']['name'] ?? null);
                    $accurateWhId = $wRow['warehouse']['id'] ?? ($wRow['warehouseId'] ?? null);
                    $qty = (int) round((float) ($wRow['balance'] ?? ($wRow['quantity'] ?? 0)));

                    $localWh = null;
                    if ($accurateWhId) {
                        $localWh = Warehouse::where('business_unit_id', $bu->id)
                            ->where('warehouse_id', $accurateWhId)
                            ->first();
                    }
                    if (!$localWh && $whName) {
                        $localWh = $bu->findLocalWarehouse($whName);
                    }

                    if ($localWh) {
                        WarehouseStock::updateOrCreate(
                            [
                                'warehouse_id' => $localWh->id,
                                'variant_id' => $product->id,
                                'variant_type' => ProductAccurate::class,
                            ],
                            [
                                'stock' => max(0, $qty),
                            ]
                        );
                        $updatedWarehouses++;
                    }
                }

                // Update juga stok agregat produk
                if (isset($detail['availableToSell']) || isset($detail['quantity'])) {
                    $totalStock = (int) round((float) ($detail['availableToSell'] ?? $detail['quantity'] ?? 0));
                    $product->updateQuietly(['stock' => max(0, $totalStock)]);
                }

                Cache::put($cacheKey, [
                    'synced_at' => now()->toIso8601String(),
                    'updated_warehouses' => $updatedWarehouses,
                ], now()->addSeconds(self::LIVE_CACHE_TTL_SECONDS));

                return ['synced' => true, 'source' => 'accurate_live_fresh'];
            }

            // Fallback: jika detailWarehouseData kosong, tandai cache singkat agar tidak spam
            Cache::put($cacheKey, ['synced_at' => now()->toIso8601String()], now()->addSeconds(15));
            return ['synced' => false, 'source' => 'local_db'];
        } catch (\Throwable $e) {
            Log::warning("OnlineStockService live refresh fallback untuk SKU {$sku} ({$dbSource}): " . $e->getMessage());
            // Cache failure singkat (10 detik) agar tidak membebani Accurate saat sedang lambat
            Cache::put($cacheKey, ['fallback' => true], now()->addSeconds(10));
            return ['synced' => false, 'source' => 'local_fallback'];
        }
    }

    /**
     * Tandai reservasi yang sudah melewati `expires_at` menjadi EXPIRED.
     */
    public function releaseExpiredReservations(): int
    {
        return OnlineStockReservation::where('status', 'ACTIVE')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'EXPIRED']);
    }

    /**
     * Rekomendasikan gudang pengirim terbaik (berdasarkan prioritas & ketersediaan stok) untuk order Delivery.
     */
    public function suggestFulfillmentWarehouse(int $productAccurateId, int $qty, int $businessUnitId): ?Warehouse
    {
        $deliveryWarehouses = $this->getOnlineWarehouses($businessUnitId, 'delivery');
        if ($deliveryWarehouses->isEmpty()) {
            return null;
        }

        $ids = $deliveryWarehouses->pluck('id')->map(fn($id) => (int) $id)->all();
        $batch = $this->getBatchAvailableStock([$productAccurateId], $ids, []);
        $perWh = $batch[$productAccurateId]['per_warehouse'] ?? [];

        foreach ($deliveryWarehouses as $wh) {
            if (($perWh[$wh->id] ?? 0) >= $qty) {
                return $wh;
            }
        }

        return $deliveryWarehouses->first();
    }
}
