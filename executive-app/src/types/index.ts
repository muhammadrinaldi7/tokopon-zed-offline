export interface UserProfile {
  id: number;
  name: string;
  email: string;
  roles: string[];
  permissions?: string[];
  business_unit?: {
    id: number;
    name: string;
    code: string;
  } | null;
  available_business_units?: Array<{
    id: number;
    name: string;
    code: string;
  }>;
}

export interface KpiSummary {
  total_orders: number;
  total_qty: number;
  gross_sales: number;
  total_discount: number;
  grand_total: number;
  total_mdr: number;
  net_sales: number;
  total_hpp: number;
  gross_profit: number;
  profit_margin: number;
  average_order_value: number;
  piutang_amount: number;
  completed_amount: number;
}

export interface MtdComparisonPoint {
  start_date: string;
  end_date: string;
  net_sales: number;
  gross_profit: number;
  total_orders: number;
  total_qty?: number;
  total_discount?: number;
}

export interface MtdComparison {
  current_mtd: MtdComparisonPoint;
  last_mtd: MtdComparisonPoint;
  growth: {
    net_sales_pct: number;
    gross_profit_pct: number;
    orders_pct: number;
    qty_pct: number;
    discount_pct: number;
  };
}

export interface KpiResponseData {
  period: {
    range: string;
    start_date: string;
    end_date: string;
  };
  summary: KpiSummary;
  mtd_comparison: MtdComparison;
}

export interface BranchMetric {
  branch_name: string;
  orders_count: number;
  total_qty: number;
  gross_sales: number;
  total_discount: number;
  net_sales: number;
  total_hpp: number;
  gross_profit: number;
  margin_percentage: number;
  average_order_value: number;
  piutang_amount: number;
  completed_amount: number;
  contribution_percentage: number;
}

export interface TrendDataPoint {
  label: string;
  key?: string;
  date?: string;
  orders_count: number;
  qty: number;
  gross_sales: number;
  net_sales: number;
  hpp: number;
  gross_profit: number;
}

export interface SalesTrend {
  mode: 'hourly' | 'daily' | 'monthly';
  labels: string[];
  series: {
    gross_sales: number[];
    net_sales: number[];
    gross_profit: number[];
    orders_count: number[];
  };
  points: TrendDataPoint[];
}

export interface TopProduct {
  rank: number;
  sku: string;
  name: string;
  brand: string;
  qty_sold: number;
  revenue: number;
  avg_price: number;
}

export interface PaymentMethodMetric {
  payment_method_id: number | null;
  payment_method_name: string;
  bank_name: string;
  transactions_count: number;
  total_amount: number;
  total_mdr: number;
  net_amount: number;
  share_percentage: number;
}

export interface FilterOptions {
  business_units: Array<{ id: number; name: string; code: string }>;
  branches: Array<{ id: number; name: string; business_unit_id: number }>;
  order_stores: string[];
  date_presets: Array<{ value: string; label: string }>;
}

export interface DashboardOverviewData {
  kpi: KpiResponseData;
  branches: BranchMetric[];
  trend: SalesTrend;
  top_products: TopProduct[];
  payments: PaymentMethodMetric[];
}

export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
  errors?: Record<string, string[]>;
}

export interface FilterParams {
  date_range?: string;
  start_date?: string;
  end_date?: string;
  business_unit_id?: number | string | null;
  branch?: string | null;
  sort_by?: 'revenue' | 'qty';
  limit?: number;
}

export interface AiChatMessage {
  id?: number;
  role: 'user' | 'assistant' | 'system';
  message: string;
  created_at?: string;
}

export interface AiChatResponse {
  reply: string;
  session_id: string;
  created_at: string;
  model?: string;
}

// ─── NEW EXECUTIVE MODULE TYPES ──────────────────────────────────

export type ExecutiveTab = 'overview' | 'staff' | 'brands' | 'audit' | 'promos';

export interface SalesPersonKpi {
  rank: number;
  sales_id: number | null;
  sales_name: string;
  position: string;
  orders_count: number;
  total_qty: number;
  gross_sales: number;
  net_sales: number;
  aov: number;
  contribution_pct: number;
}

export interface CashierKpi {
  rank: number;
  cashier_id: number | null;
  cashier_name: string;
  orders_count: number;
  total_gross: number;
  completed_amount: number;
  aov: number;
  share_pct: number;
}

export interface StaffKpiData {
  sales: SalesPersonKpi[];
  cashiers: CashierKpi[];
  summary: {
    total_sales_count: number;
    total_cashiers_count: number;
    total_orders: number;
  };
}

export interface BrandMetricItem {
  rank: number;
  brand_name: string;
  qty_sold: number;
  gross_sales: number;
  hpp: number;
  gross_profit: number;
  margin_pct: number;
  market_share_pct: number;
}

export interface BrandAnalyticsData {
  brands: BrandMetricItem[];
  summary: {
    total_revenue: number;
    total_qty: number;
    total_brands: number;
  };
}

export interface CancellationLeaderboardItem {
  cashier_id: number | null;
  cashier_name: string;
  cancellation_count: number;
  total_amount: number;
  reasons: string[];
}

export interface CancellationLogItem {
  id: number;
  date: string;
  order_number: string;
  cashier_name: string;
  branch: string;
  grand_total: number;
  reason: string;
  status: string;
}

export interface CashierOverpayLeaderboardItem {
  cashier_id: number | null;
  cashier_name: string;
  overpay_count: number;
  total_overpay_amount: number;
  avg_overpay: number;
}

export interface SellPhoneAuditItem {
  id: number;
  date: string;
  branch: string;
  brand: string;
  model: string;
  ram_storage: string;
  imei: string;
  system_price: number;
  final_price: number;
  diff_amount: number;
  diff_pct: number;
  is_overpay: boolean;
  cashier_name: string;
  reason: string;
  status: string;
}

export interface CashierAuditData {
  cancellation_audit: {
    total_cancellations: number;
    total_cancelled_amount: number;
    cashier_leaderboard: CancellationLeaderboardItem[];
    recent_logs: CancellationLogItem[];
  };
  sell_phone_audit: {
    total_bought_units: number;
    total_bought_amount: number;
    total_system_amount: number;
    total_overpay_units: number;
    total_overpay_amount: number;
    cashier_overpay_leaderboard: CashierOverpayLeaderboardItem[];
    recent_logs: SellPhoneAuditItem[];
  };
}

export interface PromoLeaderboardItem {
  promo_name: string;
  times_used: number;
  total_discount: number;
}

export interface PromoClaimRowItem {
  date: string;
  order_number: string;
  branch: string;
  brand: string;
  product_name: string;
  promo_name: string;
  vendor_name: string;
  claim_amount: number;
}

export interface PromoClaimsData {
  summary: {
    total_discount_amount: number;
    orders_with_promo_count: number;
    total_promo_claims_count: number;
    avg_discount_per_order: number;
  };
  promo_leaderboard: PromoLeaderboardItem[];
  recent_claims: PromoClaimRowItem[];
}

