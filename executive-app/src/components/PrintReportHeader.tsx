import React from 'react';
import type { DashboardOverviewData } from '../types';
import { useAuth } from '../context/AuthContext';
import { useFilter } from '../context/FilterContext';

interface PrintReportHeaderProps {
  overview: DashboardOverviewData | null;
}

export const PrintReportHeader: React.FC<PrintReportHeaderProps> = ({ overview }) => {
  const { user } = useAuth();
  const { filters } = useFilter();

  const printDate = new Date().toLocaleString('id-ID', {
    dateStyle: 'full',
    timeStyle: 'short',
  });

  const periodLabel = filters.date_range || (filters.start_date ? `${filters.start_date} s/d ${filters.end_date}` : 'Semua Periode');

  return (
    <div className="print-only mb-6 border-b-2 border-slate-900 pb-4">
      {/* Top Header Logo & Company Info */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black tracking-tight text-slate-900 uppercase">
            TOKOPON ZED &bull; EXECUTIVE INTELLIGENCE
          </h1>
          <p className="text-xs text-slate-600 font-medium">
            PT Syihab Store / GSK Group &bull; Sistem Monitoring Kinerja Bisnis & Keuangan C-Level
          </p>
        </div>
        <div className="text-right text-xs text-slate-600">
          <p className="font-semibold text-slate-900">DOKUMEN RAHASIA DIREKSI</p>
          <p>Dicetak: {printDate}</p>
          <p>Oleh: {user?.name || 'Direksi'} ({user?.email || '-'})</p>
        </div>
      </div>

      {/* Filter Metadata Bar */}
      <div className="mt-3 flex items-center gap-4 text-xs bg-slate-100 p-2 rounded border border-slate-300">
        <div>
          <span className="font-semibold text-slate-700">Periode Laporan: </span>
          <span className="font-bold text-indigo-900 uppercase">{periodLabel}</span>
        </div>
        {filters.branch && (
          <div>
            <span className="font-semibold text-slate-700">Cabang: </span>
            <span className="font-bold text-slate-900">{filters.branch}</span>
          </div>
        )}
        {overview?.kpi?.summary && (
          <div className="ml-auto flex items-center gap-3 font-semibold">
            <span>Net Sales: <strong className="text-emerald-700">Rp {Number(overview.kpi.summary.net_sales).toLocaleString('id-ID')}</strong></span>
            <span>Gross Profit: <strong className="text-indigo-700">Rp {Number(overview.kpi.summary.gross_profit).toLocaleString('id-ID')}</strong></span>
            <span>Margin: <strong className="text-slate-900">{overview.kpi.summary.profit_margin.toFixed(1)}%</strong></span>
          </div>
        )}
      </div>
    </div>
  );
};
