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

        $businessUnits = BusinessUnit::query()
            ->where('is_active', true)
            ->where('is_visible_mobile', true)
            ->whereIn('id', $onlineBuIdsWithWarehouse)
            ->orderBy('id')
            ->get();

        $data = $businessUnits->map(function ($bu) {
            return [
                'id' => $bu->id,
                'name' => $bu->mobile_display_name ?: $bu->name,
                'code' => $bu->code,
                'category' => $bu->mobile_category ?: 'GENERAL',
                'description' => $bu->mobile_description,
                'store_title' => $bu->store_title ?: $bu->name,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar unit bisnis (toko) online berhasil dimuat.',
            'data' => $data,
        ]);
    }
}
