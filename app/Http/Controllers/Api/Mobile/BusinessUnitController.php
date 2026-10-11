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
        // Hanya ambil BU yang aktif, diizinkan untuk mobile, dan memiliki setidaknya 1 gudang online
        $onlineBuIdsWithWarehouse = Warehouse::where('is_online_store', true)
            ->whereNotNull('business_unit_id')
            ->pluck('business_unit_id')
            ->unique()
            ->toArray();

        $query = BusinessUnit::query()
            ->with('media')
            ->where('is_active', true)
            ->where('is_visible_mobile', true);

        if (count($onlineBuIdsWithWarehouse) > 1) {
            $query->whereIn('id', $onlineBuIdsWithWarehouse);
        }

        $businessUnits = $query->orderBy('id')->get();

        $data = $businessUnits->map(function ($bu) {
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
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar unit bisnis (toko) online berhasil dimuat.',
            'data' => $data,
        ]);
    }
}
