import { apiClient } from './client';
import type {
  ApiResponse,
  UserProfile,
  DashboardOverviewData,
  KpiResponseData,
  BranchMetric,
  SalesTrend,
  TopProduct,
  PaymentMethodMetric,
  FilterOptions,
  FilterParams,
  AiChatMessage,
  AiChatResponse,
  StaffKpiData,
  BrandAnalyticsData,
  CashierAuditData,
  PromoClaimsData,
} from '../types';

export const executiveApi = {
  /**
   * Login executive user and receive token.
   */
  async login(credentials: { email: string; password: string; device_name?: string }) {
    const response = await apiClient.post<ApiResponse<{ token: string; user: UserProfile }>>(
      '/login',
      credentials
    );
    return response.data;
  },

  /**
   * Get current executive user profile.
   */
  async getMe() {
    const response = await apiClient.get<ApiResponse<UserProfile>>('/me');
    return response.data;
  },

  /**
   * Logout current executive session.
   */
  async logout() {
    const response = await apiClient.post<ApiResponse<{ message: string }>>('/logout');
    return response.data;
  },

  /**
   * Get all-in-one dashboard payload in a single HTTP request.
   */
  async getOverview(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<DashboardOverviewData>>('/overview', {
      params,
    });
    return response.data;
  },

  /**
   * Get KPI summary and MTD comparison.
   */
  async getKpiSummary(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<KpiResponseData>>('/kpi-summary', {
      params,
    });
    return response.data;
  },

  /**
   * Get branch performance comparison.
   */
  async getBranchComparison(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<BranchMetric[]>>('/branch-comparison', {
      params,
    });
    return response.data;
  },

  /**
   * Get sales & profit timeline trend.
   */
  async getSalesTrend(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<SalesTrend>>('/sales-trend', {
      params,
    });
    return response.data;
  },

  /**
   * Get top products ranking.
   */
  async getTopProducts(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<TopProduct[]>>('/top-products', {
      params,
    });
    return response.data;
  },

  /**
   * Get payment methods breakdown.
   */
  async getPaymentBreakdown(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<PaymentMethodMetric[]>>('/payment-breakdown', {
      params,
    });
    return response.data;
  },

  /**
   * Get available dropdown filters.
   */
  async getFilterOptions() {
    const response = await apiClient.get<ApiResponse<FilterOptions>>('/filters');
    return response.data;
  },

  /**
   * Send prompt to 9router AI Assistant with live context.
   */
  async sendAiChat(message: string, sessionId?: string, contextData?: Record<string, unknown>) {
    const response = await apiClient.post<ApiResponse<AiChatResponse>>('/ai/chat', {
      message,
      session_id: sessionId,
      context_data: contextData,
    });
    return response.data;
  },

  /**
   * Get chat history for specific session.
   */
  async getAiHistory(sessionId?: string, limit = 50) {
    const response = await apiClient.get<ApiResponse<AiChatMessage[]>>('/ai/history', {
      params: { session_id: sessionId, limit },
    });
    return response.data;
  },

  /**
   * Generate 1-click executive summary from current dashboard snapshot.
   */
  async getAiSummary(contextData: Record<string, unknown>) {
    const response = await apiClient.post<ApiResponse<AiChatResponse>>('/ai/summarize', {
      context_data: contextData,
    });
    return response.data;
  },

  /**
   * Clear chat history for specific session.
   */
  async clearAiHistory(sessionId: string) {
    const response = await apiClient.delete<ApiResponse<{ message: string }>>('/ai/history', {
      data: { session_id: sessionId },
    });
    return response.data;
  },

  /**
   * Get Staff KPI (Cashiers & Salespersons rankings).
   */
  async getStaffKpi(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<StaffKpiData>>('/staff-kpi', {
      params,
    });
    return response.data;
  },

  /**
   * Get Brand Analytics (revenue, units, margins, market share).
   */
  async getBrandAnalytics(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<BrandAnalyticsData>>('/brand-analytics', {
      params,
    });
    return response.data;
  },

  /**
   * Get Cashier Audit (order cancellations & SellPhone buyback overpayment).
   */
  async getCashierAudit(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<CashierAuditData>>('/cashier-audit', {
      params,
    });
    return response.data;
  },

  /**
   * Get Promo Claims & Subsidiaries report.
   */
  async getPromoClaims(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<PromoClaimsData>>('/promo-claims', {
      params,
    });
    return response.data;
  },
};
