const fs = require('fs');
const path = require('path');

const targetDir = 'd:\\APP\\executive-app';

// 1. Update src/types/index.ts
const typesPath = path.join(targetDir, 'src', 'types', 'index.ts');
let typesContent = fs.readFileSync(typesPath, 'utf8');

if (!typesContent.includes("'projects'")) {
  typesContent = typesContent.replace(
    "export type ExecutiveTab = 'overview' | 'staff' | 'brands' | 'audit' | 'promos';",
    "export type ExecutiveTab = 'overview' | 'staff' | 'brands' | 'audit' | 'promos' | 'projects';"
  );
}

if (!typesContent.includes('ProjectSalesReportResponse')) {
  const projectTypes = `
// ─── PROJECT SALES REPORT TYPES ────────────────────────────────────────────────

export interface ProjectMatrixCell {
  nominal: number;
  qty: number;
  count?: number;
  hpp?: number;
  profit?: number;
}

export interface ProjectMatrixDate {
  raw: string;
  display: string;
  day_name: string;
}

export interface ProjectBreakdownItem {
  project: string;
  net_sales: number;
  total_qty: number;
  total_hpp: number;
  gross_profit: number;
  margin_percentage: number;
  contribution_percentage: number;
  orders_count: number;
}

export interface ProjectSalesGrandTotal {
  nominal: number;
  qty: number;
  hpp: number;
  profit: number;
}

export interface ProjectSalesReportResponse {
  period: {
    range: string;
    start_date: string;
    end_date: string;
    total_days: number;
  };
  summary: {
    total_net_sales: number;
    total_qty: number;
    total_hpp: number;
    gross_profit: number;
    profit_margin: number;
    total_projects_count: number;
    daily_average_sales: number;
    daily_average_qty: number;
  };
  project_breakdown: ProjectBreakdownItem[];
  columns: string[];
  dates: ProjectMatrixDate[];
  matrix: Record<string, Record<string, ProjectMatrixCell>>;
  row_totals: Record<string, { nominal: number; qty: number; profit: number }>;
  column_totals: Record<string, { nominal: number; qty: number; profit: number }>;
  grand_total: ProjectSalesGrandTotal;
  available_projects: string[];
}

export interface ProjectSalesDetailItem {
  order_number: string;
  invoice_no: string;
  time: string;
  customer_name: string;
  sales_name: string;
  handled_by: string;
  branch: string;
  project: string;
  product_name: string;
  sku: string;
  serial_number: string;
  qty: number;
  price: number;
  discount: number;
  subtotal: number;
  payment_method: string;
}
`;
  typesContent += projectTypes;
  fs.writeFileSync(typesPath, typesContent, 'utf8');
  console.log('Updated types/index.ts successfully');
} else {
  console.log('types/index.ts already contains project types');
}

// 2. Update src/api/executive.ts
const apiPath = path.join(targetDir, 'src', 'api', 'executive.ts');
let apiContent = fs.readFileSync(apiPath, 'utf8');

if (!apiContent.includes('ProjectSalesReportResponse')) {
  apiContent = apiContent.replace(
    '  PromoClaimsData,\n} from \'../types\';',
    '  PromoClaimsData,\n  ProjectSalesReportResponse,\n  ProjectSalesDetailItem,\n} from \'../types\';'
  );

  const apiMethods = `
  /**
   * Get Project Sales Matrix & Breakdown report.
   */
  async getProjectSales(params?: FilterParams) {
    const response = await apiClient.get<ApiResponse<ProjectSalesReportResponse>>('/project-sales', {
      params,
    });
    return response.data;
  },

  /**
   * Get Project Sales Drilldown items.
   */
  async getProjectSalesDetail(params: {
    date?: string;
    project?: string;
    search?: string;
    branch?: string | null;
    business_unit_id?: number | string | null;
  }) {
    const response = await apiClient.get<ApiResponse<ProjectSalesDetailItem[]>>('/project-sales/detail', {
      params,
    });
    return response.data;
  },
};
`;

  apiContent = apiContent.replace(/\s*};\s*$/, apiMethods);
  fs.writeFileSync(apiPath, apiContent, 'utf8');
  console.log('Updated api/executive.ts successfully');
} else {
  console.log('api/executive.ts already updated');
}
