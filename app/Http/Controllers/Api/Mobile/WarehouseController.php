<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\OnlineStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected OnlineStockService $onlineStockService;

    public function __construct(OnlineStockService $onlineStockService)
    {
        $this->onlineStockService = $onlineStockService;
    }

    /**
     * Mengambil daftar gudang / cabang toko aktif untuk Mobile App (Pick Up / Delivery).
     * Query params:
     *   - business_unit_id (optional)
     *   - type: pickup | delivery | any (default: pickup)
     */
    public function index(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id') ? (int) $request->query('business_unit_id') : null;
        $type = strtolower(trim((string) $request->query('type', 'pickup')));
        if (!in_array($type, ['pickup', 'delivery', 'any'], true)) {
            $type = 'pickup';
        }

        $warehouses = $this->onlineStockService->getOnlineWarehouses($businessUnitId, $type);

        $data = $warehouses->map(function ($wh) {
            return [
                'id' => $wh->id,
                'business_unit_id' => $wh->business_unit_id,
                'name' => $wh->name,
                'city' => $wh->city,
                'address' => $wh->address,
                'phone' => $wh->phone,
                'operating_hours' => $wh->operating_hours,
                'latitude' => $wh->latitude,
                'longitude' => $wh->longitude,
                'allow_online_delivery' => (bool) $wh->allow_online_delivery,
                'allow_store_pickup' => (bool) $wh->allow_store_pickup,
                'fulfillment_priority' => (int) ($wh->fulfillment_priority ?? 100),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar cabang / gudang online berhasil dimuat.',
            'data' => $data,
        ]);
    }
}
