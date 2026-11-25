<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProductAccurate;
use App\Services\OnlineStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    protected OnlineStockService $onlineStockService;

    public function __construct(OnlineStockService $onlineStockService)
    {
        $this->onlineStockService = $onlineStockService;
    }

    /**
     * Mengambil daftar gudang / cabang toko aktif untuk Mobile App & Web E-Commerce (Pick Up / Delivery).
     * Query params:
     *   - business_unit_id (optional, single atau CSV: "1,2")
     *   - type: pickup | delivery | any (default: pickup)
     *   - city (optional, single atau CSV: "Banjarmasin,Banjarbaru")
     *   - lat, lng (optional, koordinat user untuk hitung distance_km & urutkan terdekat)
     *   - with_product_count (optional boolean, default false)
     */
    public function index(Request $request): JsonResponse
    {
        $buIds = $this->parseIntList($request->query('business_unit_id'));
        $cities = $this->parseCsvList($request->query('city'));
        $type = strtolower(trim((string) $request->query('type', 'pickup')));
        if (!in_array($type, ['pickup', 'delivery', 'any'], true)) {
            $type = 'pickup';
        }

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;
        $withProductCount = filter_var($request->query('with_product_count', false), FILTER_VALIDATE_BOOLEAN);

        $warehouses = $this->onlineStockService->getOnlineWarehouses(
            !empty($buIds) ? $buIds : null,
            $type,
            !empty($cities) ? $cities : null
        );

        $productCounts = [];
        if ($withProductCount && $warehouses->isNotEmpty()) {
            $whIds = $warehouses->pluck('id')->map(fn($id) => (int) $id)->all();
            $rows = DB::table('warehouse_stocks as ws')
                ->join('product_accurates as pa', 'pa.id', '=', 'ws.variant_id')
                ->whereIn('ws.warehouse_id', $whIds)
                ->whereIn('ws.variant_type', [
                    ProductAccurate::class,
                    'App\\Models\\ProductAccurate',
                    'ProductAccurate',
                ])
                ->where('ws.stock', '>', 0)
                ->where('pa.base_price', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('pa.is_addon')
                      ->orWhere('pa.is_addon', false)
                      ->orWhere('pa.is_addon', 0);
                })
                ->selectRaw('ws.warehouse_id, COUNT(DISTINCT ws.variant_id) as product_count')
                ->groupBy('ws.warehouse_id')
                ->get();

            foreach ($rows as $row) {
                $productCounts[(int) $row->warehouse_id] = (int) $row->product_count;
            }
        }

        $data = $warehouses->map(function ($wh) use ($lat, $lng, $withProductCount, $productCounts) {
            $distanceKm = null;
            if ($lat !== null && $lng !== null && is_numeric($wh->latitude) && is_numeric($wh->longitude)) {
                $distanceKm = round($this->haversineKm($lat, $lng, (float) $wh->latitude, (float) $wh->longitude), 2);
            }

            $item = [
                'id' => $wh->id,
                'business_unit_id' => $wh->business_unit_id,
                'name' => $wh->name,
                'city' => $wh->city,
                'address' => $wh->address,
                'phone' => $wh->phone,
                'operating_hours' => $wh->operating_hours,
                'latitude' => $wh->latitude !== null ? (float) $wh->latitude : null,
                'longitude' => $wh->longitude !== null ? (float) $wh->longitude : null,
                'allow_online_delivery' => (bool) $wh->allow_online_delivery,
                'allow_store_pickup' => (bool) $wh->allow_store_pickup,
                'fulfillment_priority' => (int) ($wh->fulfillment_priority ?? 100),
            ];

            if ($distanceKm !== null) {
                $item['distance_km'] = $distanceKm;
            }

            if ($withProductCount) {
                $item['product_count'] = (int) ($productCounts[$wh->id] ?? 0);
            }

            return $item;
        });

        // Jika koordinat user tersedia, urutkan dari cabang terdekat (null di belakang)
        if ($lat !== null && $lng !== null) {
            $data = $data->sortBy(fn($item) => $item['distance_km'] ?? 999999)->values();
        } else {
            $data = $data->values();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar cabang / gudang online berhasil dimuat.',
            'data' => $data,
        ]);
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

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
