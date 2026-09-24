<?php

namespace App\Http\Controllers\Api\Executive;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExecutiveDashboardController extends Controller
{
    protected ExecutiveMetricsService $metricsService;

    public function __construct(ExecutiveMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Extract and validate filter parameters from request.
     */
    protected function extractFilters(Request $request): array
    {
        $user = $request->user();

        $filters = [
            'date_range' => $request->input('date_range', $request->input('period', 'this_month')),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'branch' => $request->input('branch'),
            'order_status' => $request->input('order_status', ['COMPLETED', 'PIUTANG']),
        ];

        // Multi-BU access control: if user is not superadmin/director/admin, lock to their BU
        $canAccessAllBu = $user && $user->hasAnyRole(['superadmin', 'director', 'admin']);

        if (!$canAccessAllBu) {
            $filters['business_unit_id'] = $user ? $user->business_unit_id : $request->input('business_unit_id');
        } else {
            $filters['business_unit_id'] = $request->input('business_unit_id');
        }

        return $filters;
    }

    /**
     * Get KPI Overview Summary.
     */
    public function kpiSummary(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getKpiSummary($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Branch Performance Comparison.
     */
    public function branchComparison(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getBranchComparison($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Sales & Profit Timeline Trend.
     */
    public function salesTrend(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getSalesTrend($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Top Selling Products.
     */
    public function topProducts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:50',
            'sort_by' => 'nullable|in:revenue,qty',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filters = $this->extractFilters($request);
        $limit = (int)$request->input('limit', 10);
        $sortBy = $request->input('sort_by', 'revenue');

        $data = $this->metricsService->getTopProducts($filters, $limit, $sortBy);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Payment Methods Breakdown.
     */
    public function paymentBreakdown(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getPaymentMethodBreakdown($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get available dropdown filters (business units, branches, date presets).
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $data = $this->metricsService->getFilterOptions($request->user());

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Consolidated all-in-one dashboard payload to minimize HTTP requests for frontend.
     */
    public function dashboardOverview(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);

        $limit = (int)$request->input('limit', 10);
        $sortBy = $request->input('sort_by', 'revenue');

        $kpi = $this->metricsService->getKpiSummary($filters);
        $branches = $this->metricsService->getBranchComparison($filters);
        $trend = $this->metricsService->getSalesTrend($filters);
        $topProducts = $this->metricsService->getTopProducts($filters, $limit, $sortBy);
        $payments = $this->metricsService->getPaymentMethodBreakdown($filters);

        return response()->json([
            'success' => true,
            'data' => [
                'kpi' => $kpi,
                'branches' => $branches,
                'trend' => $trend,
                'top_products' => $topProducts,
                'payments' => $payments,
            ],
        ]);
    }

    /**
     * Get Staff KPI (Salespersons vs Cashiers performance).
     */
    public function staffKpi(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getStaffKpi($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Brand Analytics (Market share, revenue, and gross margins per brand).
     */
    public function brandAnalytics(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getBrandAnalytics($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Cashier Audit (Order cancellations & SellPhone buyback deviation).
     */
    public function cashierAudit(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getCashierAudit($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Promo Claims & Vendor Subsidies Analytics.
     */
    public function promoClaims(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        if ($request->filled('brand')) {
            $filters['brand'] = $request->input('brand');
        }
        if ($request->filled('vendor')) {
            $filters['vendor'] = $request->input('vendor');
        }
        $data = $this->metricsService->getPromoClaims($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Project Sales Report (Matrix & breakdown per project e.g. RESMI, INTER, etc.)
     */
    public function projectSales(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $filters['projects'] = $request->input('projects', $request->input('project'));
        $data = $this->metricsService->getProjectSalesReport($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Project Sales Transaction Drill-down Items for specific date & project.
     */
    public function projectSalesDetail(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $filters['date'] = $request->input('date');
        $filters['project'] = $request->input('project');
        $filters['search'] = $request->input('search');

        $data = $this->metricsService->getProjectSalesDetail($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Branch Detailed Invoices / Transactions.
     */
    public function branchTransactions(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        if ($request->filled('branch')) {
            $filters['branch'] = $request->input('branch');
        }
        if ($request->filled('search')) {
            $filters['search'] = $request->input('search');
        }

        $data = $this->metricsService->getBranchTransactions($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Piutang & Outstanding Receivables Report (adheres to Dashboard.php).
     */
    public function piutangReport(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $data = $this->metricsService->getPiutangReport($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}


