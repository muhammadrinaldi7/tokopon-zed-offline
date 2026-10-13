<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\EcommerceBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Mengambil daftar banner promo aktif untuk carousel beranda mobile app.
     */
    public function index(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id');

        $query = EcommerceBanner::query()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc');

        if ($businessUnitId) {
            $query->where(function ($q) use ($businessUnitId) {
                $q->whereNull('business_unit_id')
                  ->orWhere('business_unit_id', $businessUnitId);
            });
        }

        $banners = $query->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->image_url,
                'target_type' => $banner->target_type,
                'target_value' => $banner->target_value,
                'business_unit_id' => $banner->business_unit_id,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }
}
