import React from 'react';
import type { KpiResponseData } from '../types';
import { formatCurrency, formatPercent } from '../utils/formatters';
import {
  TrendingUp,
  TrendingDown,
  CircleDollarSign,
  PieChart,
  ShoppingBag,
  CreditCard,
  AlertTriangle,
  CheckCircle2,
} from 'lucide-react';

interface KpiCardGridProps {
  data?: KpiResponseData | null;
  isLoading?: boolean;
}

export const KpiCardGrid: React.FC<KpiCardGridProps> = ({ data, isLoading }) => {
  if (isLoading || !data) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div
            key={i}
            className="glass-card rounded-2xl p-5 border border-slate-800 animate-pulse h-40 flex flex-col justify-between"
          >
            <div className="flex justify-between items-center">
              <div className="h-4 bg-slate-800 rounded w-28" />
              <div className="h-8 w-8 bg-slate-800 rounded-lg" />
            </div>
            <div className="h-8 bg-slate-800 rounded w-44 my-2" />
            <div className="h-4 bg-slate-800 rounded w-36" />
          </div>
        ))}
      </div>
    );
  }

  const { summary, mtd_comparison } = data;
  const growth = mtd_comparison?.growth;
  const lastMtd = mtd_comparison?.last_mtd;

  const renderGrowthBadge = (pct: number | undefined) => {
    if (pct === undefined) return null;
    const isPositive = pct >= 0;
    return (
      <span
        className={`inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold ${
          isPositive
            ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
            : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'
        }`}
      >
        {isPositive ? (
          <TrendingUp className="w-3 h-3 stroke-[2.5]" />
        ) : (
          <TrendingDown className="w-3 h-3 stroke-[2.5]" />
        )}
        <span>{formatPercent(pct)} MTD</span>
      </span>
    );
  };

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {/* 1. Net Sales (Omset Bersih) */}
      <div className="glass-card rounded-2xl p-5 relative overflow-hidden border border-slate-800/90 group">
        <div className="flex items-center justify-between">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
            Omset Bersih (Net Sales)
          </span>
          <div className="w-8 h-8 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shadow-sm">
            <CircleDollarSign className="w-4 h-4" />
          </div>
        </div>

        <div className="mt-3">
          <div className="text-2xl lg:text-3xl font-extrabold text-white tracking-tight tabular-nums">
            {formatCurrency(summary.net_sales)}
          </div>
          <div className="mt-2 flex items-center gap-2 flex-wrap">
            {renderGrowthBadge(growth?.net_sales_pct)}
            {lastMtd && (
              <span className="text-[11px] text-slate-400">
                vs {formatCurrency(lastMtd.net_sales)}
              </span>
            )}
          </div>
        </div>

        <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
          <span>Gross: {formatCurrency(summary.gross_sales)}</span>
          <span className="text-slate-500">•</span>
          <span>MDR: {formatCurrency(summary.total_mdr)}</span>
        </div>
      </div>

      {/* 2. Gross Profit (Laba Kotor) */}
      <div className="glass-card rounded-2xl p-5 relative overflow-hidden border border-slate-800/90 group">
        <div className="flex items-center justify-between">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
            Laba Kotor (Gross Profit)
          </span>
          <div className="w-8 h-8 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shadow-sm">
            <PieChart className="w-4 h-4" />
          </div>
        </div>

        <div className="mt-3">
          <div className="text-2xl lg:text-3xl font-extrabold text-emerald-400 tracking-tight tabular-nums">
            {formatCurrency(summary.gross_profit)}
          </div>
          <div className="mt-2 flex items-center gap-2 flex-wrap">
            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
              Margin {summary.profit_margin.toFixed(1)}%
            </span>
            {renderGrowthBadge(growth?.gross_profit_pct)}
          </div>
        </div>

        <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
          <span>Total HPP: {formatCurrency(summary.total_hpp)}</span>
          {summary.total_discount > 0 && (
            <>
              <span className="text-slate-500">•</span>
              <span>Diskon: {formatCurrency(summary.total_discount)}</span>
            </>
          )}
        </div>
      </div>

      {/* 3. Total Volume & AOV */}
      <div className="glass-card rounded-2xl p-5 relative overflow-hidden border border-slate-800/90 group">
        <div className="flex items-center justify-between">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
            Volume & Rata-rata Order
          </span>
          <div className="w-8 h-8 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400 shadow-sm">
            <ShoppingBag className="w-4 h-4" />
          </div>
        </div>

        <div className="mt-3">
          <div className="text-2xl lg:text-3xl font-extrabold text-white tracking-tight tabular-nums">
            {summary.total_orders.toLocaleString('id-ID')}{' '}
            <span className="text-sm font-semibold text-slate-400">Order</span>
          </div>
          <div className="mt-2 flex items-center gap-2 flex-wrap">
            <span className="text-xs font-semibold text-slate-300">
              {summary.total_qty.toLocaleString('id-ID')} unit terjual
            </span>
            {renderGrowthBadge(growth?.orders_pct)}
          </div>
        </div>

        <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
          <span>AOV (Rata-rata):</span>
          <span className="font-semibold text-slate-200">
            {formatCurrency(summary.average_order_value)}
          </span>
        </div>
      </div>

      {/* 4. Realisasi Kas & Piutang */}
      <div className="glass-card rounded-2xl p-5 relative overflow-hidden border border-slate-800/90 group">
        <div className="flex items-center justify-between">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
            Realisasi & Piutang
          </span>
          <div className="w-8 h-8 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-sm">
            <CreditCard className="w-4 h-4" />
          </div>
        </div>

        <div className="mt-3">
          <div className="text-2xl lg:text-3xl font-extrabold text-white tracking-tight tabular-nums">
            {formatCurrency(summary.completed_amount)}
          </div>
          <div className="mt-2 flex items-center gap-2 flex-wrap">
            <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-400">
              <CheckCircle2 className="w-3.5 h-3.5" />
              Kas Lunas
            </span>

            {summary.piutang_amount > 0 ? (
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                <AlertTriangle className="w-3 h-3" />
                Piutang: {formatCurrency(summary.piutang_amount)}
              </span>
            ) : (
              <span className="text-[11px] text-slate-400">Tidak ada piutang</span>
            )}
          </div>
        </div>

        <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
          <span>Status Pembayaran:</span>
          <span className="font-semibold text-emerald-400">
            {summary.net_sales > 0
              ? `${((summary.completed_amount / summary.net_sales) * 100).toFixed(0)}% Teralokasi`
              : '100%'}
          </span>
        </div>
      </div>
    </div>
  );
};
