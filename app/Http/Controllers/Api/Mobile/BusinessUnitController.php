<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessUnitController extends Controller
{
    /**
     * Mengambil daftar Business Unit (Toko) yang diizinkan tampil di Mobile App (is_visible_mobile = true).
     */
    public function index(Request $request): JsonResponse
    {
        // Hanya ambil BU yang aktif, diizinkan untuk mobile, dan memiliki setidaknya 1 gudang online (Delivery / Pick Up)
        $onlineBuIdsWithWarehouse = Warehouse::onlineStore()
            ->whereNotNull('business_unit_id')
            ->pluck('business_unit_id')
            ->unique()
            ->toArray();

        $pickupCountByBu = Warehouse::storePickup()
            ->whereNotNull('business_unit_id')
            ->selectRaw('business_unit_id, count(*) as total')
            ->groupBy('business_unit_id')
            ->pluck('total', 'business_unit_id')
            ->toArray();

        $deliveryCountByBu = Warehouse::onlineDelivery()
            ->whereNotNull('business_unit_id')
            ->selectRaw('business_unit_id, count(*) as total')
            ->groupBy('business_unit_id')
            ->pluck('total', 'business_unit_id')
            ->toArray();

        $query = BusinessUnit::query()
            ->with('media')
            ->where('is_active', true)
            ->where('is_visible_mobile', true);

        if (count($onlineBuIdsWithWarehouse) > 1) {
            $query->whereIn('id', $onlineBuIdsWithWarehouse);
        }

        $businessUnits = $query->orderBy('id')->get();

        $data = $businessUnits->map(function ($bu) use ($pickupCountByBu, $deliveryCountByBu) {
            $isSecond = ($bu->mobile_category === 'SECOND') || str_contains(strtoupper($bu->name), 'SECOND');

            return [
                'id' => $bu->id,
                'name' => $bu->mobile_display_name ?: $bu->name,
                'code' => $bu->code,
                'category' => $bu->mobile_category ?: 'GENERAL',
                'description' => $bu->mobile_description,
                'store_title' => $bu->store_title ?: $bu->name,
                'logo_url' => $bu->getFirstMediaUrl('logo') ?: null,
                'brand_color' => $bu->brand_color,
                'warranty_highlight' => $bu->warranty_highlight,
                'is_second' => $isSecond,
                'supports_pickup' => (int) ($pickupCountByBu[$bu->id] ?? 0) > 0,
                'supports_delivery' => (int) ($deliveryCountByBu[$bu->id] ?? 0) > 0,
                'pickup_warehouse_count' => (int) ($pickupCountByBu[$bu->id] ?? 0),
                'delivery_warehouse_count' => (int) ($deliveryCountByBu[$bu->id] ?? 0),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar unit bisnis (toko) online berhasil dimuat.',
            'data' => $data,
        ]);
    }
}
