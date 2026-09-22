import React from 'react';
import { useAuth } from '../context/AuthContext';
import { useFilter } from '../context/FilterContext';
import type { FilterOptions, DashboardOverviewData } from '../types';
import { exportDashboardToCsv } from '../utils/exportCsv';
import {
  TrendingUp,
  RefreshCw,
  LogOut,
  Building2,
  Sparkles,
  Printer,
  FileSpreadsheet,
} from 'lucide-react';

interface HeaderProps {
  filterOptions?: FilterOptions | null;
  isLoading?: boolean;
  overview?: DashboardOverviewData | null;
}

export const Header: React.FC<HeaderProps> = ({ filterOptions, isLoading, overview }) => {
  const { user, logout } = useAuth();
  const {
    filters,
    setBusinessUnitId,
    lastRefreshed,
    refreshData,
    isAutoRefresh,
    setIsAutoRefresh,
  } = useFilter();

  const timeString = lastRefreshed.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });

  const handleExportCsv = () => {
    if (!overview) return;
    const periodLabel = filters.date_range || (filters.start_date ? `${filters.start_date} s/d ${filters.end_date}` : 'Periode Terpilih');
    exportDashboardToCsv(overview, periodLabel);
  };

  const handlePrintPdf = () => {
    window.print();
  };

  return (
    <header className="no-print sticky top-0 z-40 w-full border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16 gap-4">
          {/* Left: Brand Identity & Intelligence Badge */}
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
              <TrendingUp className="w-5 h-5 stroke-[2.5]" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="font-extrabold text-base tracking-tight text-white">
                  TOKOPON <span className="text-indigo-400 font-black">ZED</span>
                </span>
                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-500/15 border border-indigo-500/30 text-indigo-300">
                  <Sparkles className="w-2.5 h-2.5 text-indigo-400" />
                  Executive
                </span>
              </div>
              <p className="text-[11px] text-slate-400 font-medium hidden sm:block">
                C-Level Business Intelligence & Realtime Analytics
              </p>
            </div>
          </div>

          {/* Right: BU Selector, Export Actions, Sync Status, Profile & Logout */}
          <div className="flex items-center gap-2 sm:gap-3">
            {/* Business Unit Selector */}
            {filterOptions?.business_units && filterOptions.business_units.length > 1 && (
              <div className="relative hidden md:flex items-center">
                <div className="absolute left-2.5 text-slate-400 pointer-events-none">
                  <Building2 className="w-3.5 h-3.5" />
                </div>
                <select
                  value={filters.business_unit_id ?? ''}
                  onChange={(e) => setBusinessUnitId(e.target.value || null)}
                  className="pl-8 pr-7 py-1.5 bg-slate-900/90 border border-slate-700/80 rounded-lg text-xs font-medium text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                >
                  <option value="">Semua Unit Bisnis (Konsolidasi)</option>
                  {filterOptions.business_units.map((bu) => (
                    <option key={bu.id} value={bu.id}>
                      BU: {bu.name} ({bu.code})
                    </option>
                  ))}
                </select>
              </div>
            )}

            {/* Export CSV / Excel Button */}
            <button
              onClick={handleExportCsv}
              disabled={!overview || isLoading}
              title="Unduh Laporan Format Excel/CSV"
              className="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-900 hover:bg-emerald-950/40 border border-slate-800 hover:border-emerald-500/40 text-slate-300 hover:text-emerald-300 rounded-lg text-xs font-medium transition-all cursor-pointer disabled:opacity-40"
            >
              <FileSpreadsheet className="w-3.5 h-3.5 text-emerald-400" />
              <span className="hidden md:inline">Excel/CSV</span>
            </button>

            {/* Print / PDF Button */}
            <button
              onClick={handlePrintPdf}
              title="Cetak atau Simpan PDF Laporan Resmi"
              className="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-900 hover:bg-indigo-950/40 border border-slate-800 hover:border-indigo-500/40 text-slate-300 hover:text-indigo-300 rounded-lg text-xs font-medium transition-all cursor-pointer"
            >
              <Printer className="w-3.5 h-3.5 text-indigo-400" />
              <span className="hidden md:inline">Cetak PDF</span>
            </button>

            {/* Auto-Refresh Toggle Pill */}
            <button
              onClick={() => setIsAutoRefresh(!isAutoRefresh)}
              title="Toggle auto-refresh setiap 60 detik"
              className={`hidden lg:flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer ${
                isAutoRefresh
                  ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
                  : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200'
              }`}
            >
              <span
                className={`w-2 h-2 rounded-full ${
                  isAutoRefresh ? 'bg-emerald-400 animate-pulse' : 'bg-slate-600'
                }`}
              />
              <span>Live 60s</span>
            </button>

            {/* Instant Refresh Button */}
            <button
              onClick={refreshData}
              disabled={isLoading}
              title={`Diperbarui pukul ${timeString}. Klik untuk reload.`}
              className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-slate-800/80 border border-slate-800 text-slate-300 hover:text-white rounded-lg text-xs font-medium transition-all cursor-pointer disabled:opacity-50"
            >
              <RefreshCw
                className={`w-3.5 h-3.5 text-indigo-400 ${isLoading ? 'animate-spin' : ''}`}
              />
              <span className="hidden sm:inline tabular-nums">{timeString}</span>
            </button>

            {/* User Profile Chip */}
            <div className="flex items-center gap-2 pl-2 border-l border-slate-800">
              <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-slate-700 to-slate-800 border border-slate-700 flex items-center justify-center text-xs font-bold text-white shadow-inner">
                {user?.name?.charAt(0) || 'D'}
              </div>
              <div className="hidden xl:block text-left">
                <div className="text-xs font-semibold text-white leading-tight">
                  {user?.name}
                </div>
                <div className="text-[10px] text-slate-400 font-medium">
                  {user?.roles?.join(', ') || 'Direksi'}
                </div>
              </div>

              {/* Logout Button */}
              <button
                onClick={logout}
                title="Keluar dari sesi Direksi"
                className="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
};

