import React, { useEffect, useState } from 'react';
import { useFilter } from '../context/FilterContext';
import { executiveApi } from '../api/executive';
import type { DashboardOverviewData, FilterOptions, ExecutiveTab } from '../types';
import { Header } from '../components/Header';
import { NavigationTabs } from '../components/NavigationTabs';
import { FilterBar } from '../components/FilterBar';
import { FinancialOverviewTab } from '../components/tabs/FinancialOverviewTab';
import { StaffKpiTab } from '../components/tabs/StaffKpiTab';
import { BrandAnalyticsTab } from '../components/tabs/BrandAnalyticsTab';
import { CashierAuditTab } from '../components/tabs/CashierAuditTab';
import { PromoClaimsTab } from '../components/tabs/PromoClaimsTab';
import { AiAssistantDrawer } from '../components/AiAssistantDrawer';
import { PrintReportHeader } from '../components/PrintReportHeader';
import { PrintReportFooter } from '../components/PrintReportFooter';
import { AlertCircle } from 'lucide-react';

export const DashboardPage: React.FC = () => {
  const { filters, refreshKey } = useFilter();

  const [activeTab, setActiveTab] = useState<ExecutiveTab>('overview');
  const [overview, setOverview] = useState<DashboardOverviewData | null>(null);
  const [filterOptions, setFilterOptions] = useState<FilterOptions | null>(null);
  const [isLoadingOverview, setIsLoadingOverview] = useState<boolean>(true);
  const [overviewError, setOverviewError] = useState<string | null>(null);

  // Fetch dropdown filter options once
  useEffect(() => {
    const fetchOptions = async () => {
      try {
        const res = await executiveApi.getFilterOptions();
        if (res.success && res.data) {
          setFilterOptions(res.data);
        }
      } catch (err) {
        console.error('Failed to load filter options:', err);
      }
    };

    fetchOptions();
  }, []);

  // Fetch dashboard overview data when overview tab is active or initially
  useEffect(() => {
    let isMounted = true;

    // Only query heavy overview endpoint if user is on overview tab or overview is not yet loaded
    if (activeTab !== 'overview' && overview !== null) {
      return;
    }

    const fetchOverview = async () => {
      setIsLoadingOverview(true);
      setOverviewError(null);

      try {
        const res = await executiveApi.getOverview(filters);
        if (isMounted && res.success && res.data) {
          setOverview(res.data);
        }
      } catch (err: unknown) {
        if (!isMounted) return;
        const axiosErr = err as { response?: { data?: { message?: string } } };
        setOverviewError(
          axiosErr.response?.data?.message ||
            'Gagal memuat data metrik dashboard. Silakan periksa koneksi backend.'
        );
      } finally {
        if (isMounted) {
          setIsLoadingOverview(false);
        }
      }
    };

    fetchOverview();

    return () => {
      isMounted = false;
    };
  }, [filters, refreshKey, activeTab]);

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col">
      {/* 1. Header Navigation */}
      <Header
        filterOptions={filterOptions}
        isLoading={isLoadingOverview}
        overview={overview}
      />

      {/* 2. Executive Suite Module Navigation Tabs */}
      <NavigationTabs activeTab={activeTab} onTabChange={setActiveTab} />

      {/* 3. Global Filter Toolbar */}
      <FilterBar filterOptions={filterOptions} />

      {/* 4. Main Dashboard Body */}
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        {/* Printable Official Document Header (Visible only on print/PDF) */}
        <PrintReportHeader overview={overview} />

        {/* Overview Error Alert */}
        {overviewError && activeTab === 'overview' && (
          <div className="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center gap-3 text-rose-300 text-xs">
            <AlertCircle className="w-5 h-5 text-rose-400 shrink-0" />
            <div className="flex-1">
              <span className="font-bold block">Kesalahan Pengambilan Data Finansial:</span>
              {overviewError}
            </div>
          </div>
        )}

        {/* Active Tab Content (On-Demand Lazy Loaded) */}
        {activeTab === 'overview' && (
          <FinancialOverviewTab
            overview={overview}
            isLoading={isLoadingOverview}
          />
        )}

        {activeTab === 'staff' && <StaffKpiTab />}

        {activeTab === 'brands' && <BrandAnalyticsTab />}

        {activeTab === 'audit' && <CashierAuditTab />}

        {activeTab === 'promos' && <PromoClaimsTab />}

        {/* Printable Official Document Signatures (Visible only on print/PDF) */}
        <PrintReportFooter />
      </main>

      {/* AI Assistant Floating Drawer */}
      <AiAssistantDrawer overviewData={overview} />

      {/* Footer */}
      <footer className="no-print w-full border-t border-slate-900 bg-slate-950/80 py-4 px-4 text-center text-xs text-slate-500">
        Tokopon Zed Executive Intelligence Suite &bull; Real-time C-Level Business Dashboard &bull; v1.0.0
      </footer>
    </div>
  );
};
