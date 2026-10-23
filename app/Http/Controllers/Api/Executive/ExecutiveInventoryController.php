<?php

namespace App\Http\Controllers\Api\Executive;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Services\AccurateInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveInventoryController extends Controller
{
    protected AccurateInventoryService $inventoryService;

    public function __construct(AccurateInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get Complete Inventory Valuation and Stock Analytics for Executive Dashboard.
     */
    public function valuation(Request $request): JsonResponse
    {
        try {
            $buName = 'Semua Unit Bisnis';
            $source = 'all';

            if ($request->filled('business_unit_id')) {
                $bu = BusinessUnit::find($request->input('business_unit_id'));
                if ($bu) {
                    $source = $bu->code ?: 'second';
                    $buName = $bu->name;
                }
            } elseif ($request->filled('database_source')) {
                $source = trim($request->input('database_source'));
                $bu = BusinessUnit::where('code', $source)->first();
                if ($bu) {
                    $buName = $bu->name;
                }
            }

            $asOfDate = $request->input('as_of_date') ?: ($request->input('end_date') ?: null);
            $forceRefresh = $request->boolean('force_refresh', false);

            $branch = $request->input('branch');
            $category = $request->input('category');

            $data = $this->inventoryService->getInventoryValuation(
                $source,
                $asOfDate,
                $branch,
                $category,
                $forceRefresh,
                $buName
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat valuasi persediaan Accurate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force Sync Inventory Valuation Data from Accurate.
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $buName = 'Semua Unit Bisnis';
            $source = 'second';

            if ($request->filled('business_unit_id')) {
                $bu = BusinessUnit::find($request->input('business_unit_id'));
                if ($bu) {
                    $source = $bu->code ?: 'second';
                    $buName = $bu->name;
                }
            }

            $asOfDate = $request->input('as_of_date') ?: null;
            $branch = $request->input('branch');
            $category = $request->input('category');

            $data = $this->inventoryService->getInventoryValuation(
                $source,
                $asOfDate,
                $branch,
                $category,
                true,
                $buName
            );

            return response()->json([
                'success' => true,
                'message' => 'Data valuasi stok berhasil disinkronkan dari Accurate Online.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi data valuasi Accurate: ' . $e->getMessage(),
            ], 500);
        }
    }
}
