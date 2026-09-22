import React from 'react';
import type { DashboardOverviewData } from '../../types';
import { AiExecutiveInsight } from '../AiExecutiveInsight';
import { KpiCardGrid } from '../KpiCardGrid';
import { CashFlowHealthCard } from '../CashFlowHealthCard';
import { TrendChart } from '../TrendChart';
import { BranchComparison } from '../BranchComparison';
import { PaymentBreakdown } from '../PaymentBreakdown';
import { TopProductsTable } from '../TopProductsTable';

interface FinancialOverviewTabProps {
  overview: DashboardOverviewData | null;
  isLoading: boolean;
}

export const FinancialOverviewTab: React.FC<FinancialOverviewTabProps> = ({
  overview,
  isLoading,
}) => {
  return (
    <div className="space-y-6">
      {/* AI Strategic Insight Preview */}
      <div className="break-inside-avoid">
        <AiExecutiveInsight
          kpi={overview?.kpi}
          branches={overview?.branches}
        />
      </div>

      {/* Row 1: 4 Key Executive Metric Cards */}
      <div className="break-inside-avoid">
        <KpiCardGrid data={overview?.kpi} isLoading={isLoading} />
      </div>

      {/* Row 2: Cash Flow Health & Receivables Risk Deep-Dive */}
      <div className="break-inside-avoid">
        <CashFlowHealthCard
          summary={overview?.kpi?.summary}
          isLoading={isLoading}
        />
      </div>

      {/* Row 3: Sales & Profit Trend Area Chart */}
      <div className="break-inside-avoid">
        <TrendChart trend={overview?.trend} isLoading={isLoading} />
      </div>

      {/* Row 4: Two-Column Performance Comparison */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 break-inside-avoid">
        {/* Left: Branch/Store Leaderboard */}
        <BranchComparison
          branches={overview?.branches}
          isLoading={isLoading}
        />

        {/* Right: Payment Composition & MDR Costs */}
        <PaymentBreakdown
          payments={overview?.payments}
          isLoading={isLoading}
        />
      </div>

      {/* Row 5: Top 10 Products Ranking Leaderboard */}
      <div className="break-inside-avoid">
        <TopProductsTable
          products={overview?.top_products}
          isLoading={isLoading}
        />
      </div>
    </div>
  );
};
