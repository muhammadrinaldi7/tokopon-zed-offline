<?php

namespace App\Http\Controllers\Api\Executive;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Services\AccurateFinancialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveFinancialController extends Controller
{
    protected AccurateFinancialService $financialService;

    public function __construct(AccurateFinancialService $financialService)
    {
        $this->financialService = $financialService;
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
     * Get AR & AP Combined Summary for Executive Dashboard.
     */
    public function arApSummary(Request $request): JsonResponse
    {
        try {
            $source = $this->resolveSource($request);
            $asOfDate = $request->input('as_of_date') ?: ($request->input('end_date') ?: null);
            $forceRefresh = $request->boolean('force_refresh', false);

            $data = $this->financialService->getArApSummary($source, $asOfDate, $forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat ringkasan piutang/hutang Accurate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Detailed Receivables (Piutang AR).
     */
    public function receivables(Request $request): JsonResponse
    {
        try {
            $source = $this->resolveSource($request);
            $asOfDate = $request->input('as_of_date') ?: ($request->input('end_date') ?: null);
            $forceRefresh = $request->boolean('force_refresh', false);

            $data = $this->financialService->getOutstandingReceivables($source, $asOfDate, $forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat rincian piutang Accurate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Detailed Payables (Hutang AP).
     */
    public function payables(Request $request): JsonResponse
    {
        try {
            $source = $this->resolveSource($request);
            $asOfDate = $request->input('as_of_date') ?: ($request->input('end_date') ?: null);
            $forceRefresh = $request->boolean('force_refresh', false);

            $data = $this->financialService->getOutstandingPayables($source, $asOfDate, $forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat rincian hutang Accurate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Cash and Bank Accounts Breakdown.
     */
    public function cashBank(Request $request): JsonResponse
    {
        try {
            $source = $this->resolveSource($request);
            $asOfDate = $request->input('as_of_date') ?: ($request->input('end_date') ?: null);
            $forceRefresh = $request->boolean('force_refresh', false);

            $data = $this->financialService->getCashAndBank($source, $asOfDate, $forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kas & bank Accurate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force Manual Sync from Accurate Online.
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $source = $this->resolveSource($request);
            $asOfDate = $request->input('as_of_date') ?: null;

            $summary = $this->financialService->getArApSummary($source, $asOfDate, true);
            $cashBank = $this->financialService->getCashAndBank($source, $asOfDate, true);

            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi data keuangan Accurate berhasil diperbarui.',
                'data' => [
                    'summary' => $summary,
                    'cash_bank' => $cashBank,
                    'synced_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi data Accurate: ' . $e->getMessage(),
            ], 500);
        }
    }
}
