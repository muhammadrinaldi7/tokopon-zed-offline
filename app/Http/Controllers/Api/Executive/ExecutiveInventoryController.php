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
     * Resolve database source code from request filters.
     */
    protected function resolveSource(Request $request): string
    {
        if ($request->filled('database_source')) {
            return trim($request->input('database_source'));
        }

        if ($request->filled('business_unit_id')) {
            $bu = BusinessUnit::find($request->input('business_unit_id'));
            if ($bu && $bu->code) {
                return $bu->code;
            }
        }

        return 'second';
    }

    /**
     * Get Complete Inventory Valuation and Stock Analytics for Executive Dashboard.
     */
    public function valuation(Request $request): JsonResponse
    {
        try {
            $source = $this->resolveSource($request);
            $asOfDate = $request->input('as_of_date') ?: ($request->input('end_date') ?: null);
            $forceRefresh = $request->boolean('force_refresh', false);

            $branchIds = (array)$request->input('branch_ids', []);
            $categoryIds = (array)$request->input('category_ids', []);

            $data = $this->inventoryService->getInventoryValuation(
                $source,
                $asOfDate,
                $branchIds,
                $categoryIds,
                $forceRefresh
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
            $source = $this->resolveSource($request);
            $asOfDate = $request->input('as_of_date') ?: null;

            $data = $this->inventoryService->getInventoryValuation($source, $asOfDate, [], [], true);

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
