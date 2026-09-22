import React from 'react';
import type { BranchMetric } from '../types';
import { formatCurrency } from '../utils/formatters';
import { Store, Trophy } from 'lucide-react';

interface BranchComparisonProps {
  branches?: BranchMetric[] | null;
  isLoading?: boolean;
}

export const BranchComparison: React.FC<BranchComparisonProps> = ({ branches, isLoading }) => {
  if (isLoading || !branches) {
    return (
      <div className="glass-card rounded-2xl p-6 border border-slate-800 animate-pulse h-96 flex flex-col justify-between">
        <div className="h-5 bg-slate-800 rounded w-44" />
        <div className="space-y-4 my-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-14 bg-slate-900/50 rounded-xl" />
          ))}
        </div>
      </div>
    );
  }

  if (branches.length === 0) {
    return (
      <div className="glass-card rounded-2xl p-6 border border-slate-800 text-center py-12">
        <Store className="w-10 h-10 text-slate-600 mx-auto mb-2" />
        <h4 className="text-sm font-semibold text-slate-300">Tidak ada data cabang</h4>
        <p className="text-xs text-slate-500 mt-1">
          Tidak ada data transaksi cabang pada periode dan filter yang dipilih.
        </p>
      </div>
    );
  }

  return (
    <div className="glass-card rounded-2xl p-6 border border-slate-800/90">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h3 className="text-base font-bold text-white tracking-tight flex items-center gap-2">
            <Store className="w-4 h-4 text-indigo-400" />
            Performa Cabang Toko
          </h3>
          <p className="text-xs text-slate-400 mt-1">
            Komparasi omset, laba kotor, dan kontribusi terhadap pendapatan
          </p>
        </div>
        <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
          {branches.length} Cabang Aktif
        </span>
      </div>

      <div className="space-y-3 mt-4">
        {branches.map((branch, idx) => {
          const isTop = idx === 0 && branch.net_sales > 0;
          return (
            <div
              key={branch.branch_name}
              className={`p-4 rounded-xl border transition-all ${
                isTop
                  ? 'bg-gradient-to-r from-indigo-950/40 via-slate-900/90 to-slate-900/90 border-indigo-500/40 shadow-lg shadow-indigo-950/30'
                  : 'bg-slate-900/70 border-slate-800/80 hover:border-slate-700'
              }`}
            >
              <div className="flex items-start justify-between gap-2">
                <div className="flex items-center gap-2.5">
                  <div
                    className={`w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold ${
                      idx === 0
                        ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 shadow-sm shadow-amber-500/20'
                        : idx === 1
                        ? 'bg-slate-300/20 text-slate-200 border border-slate-400/40'
                        : idx === 2
                        ? 'bg-amber-700/20 text-amber-500 border border-amber-600/40'
                        : 'bg-slate-800 text-slate-400'
                    }`}
                  >
                    {idx === 0 ? (
                      <Trophy className="w-3.5 h-3.5 text-amber-400" />
                    ) : (
                      `#${idx + 1}`
                    )}
                  </div>

                  <div>
                    <h4 className="text-sm font-bold text-white flex items-center gap-2">
                      {branch.branch_name}
                      {idx === 0 && (
                        <span className="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-amber-400/15 text-amber-300 border border-amber-400/30">
                          Juara Omset
                        </span>
                      )}
                    </h4>
                    <div className="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                      <span>{branch.orders_count} Transaksi</span>
                      <span>•</span>
                      <span>{branch.total_qty} pcs</span>
                      <span>•</span>
                      <span>AOV: {formatCurrency(branch.average_order_value)}</span>
                    </div>
                  </div>
                </div>

                <div className="text-right">
                  <div className="text-sm font-extrabold text-white tabular-nums">
                    {formatCurrency(branch.net_sales)}
                  </div>
                  <div className="text-[11px] font-semibold text-emerald-400 mt-0.5 tabular-nums flex items-center justify-end gap-1.5">
                    <span>Laba: {formatCurrency(branch.gross_profit)}</span>
                    <span
                      className={`text-[10px] font-bold px-1.5 py-0.2 rounded border ${
                        branch.margin_percentage >= 25
                          ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-300'
                          : branch.margin_percentage >= 15
                          ? 'bg-amber-500/15 border-amber-500/30 text-amber-300'
                          : 'bg-rose-500/15 border-rose-500/30 text-rose-300'
                      }`}
                      title={`Margin Cabang: ${branch.margin_percentage.toFixed(1)}%`}
                    >
                      {branch.margin_percentage.toFixed(1)}%
                    </span>
                  </div>
                </div>
              </div>

              {/* Progress Contribution Bar */}
              <div className="mt-3">
                <div className="flex items-center justify-between text-[11px] text-slate-400 mb-1 font-medium">
                  <span>Pangsa Kontribusi</span>
                  <span className="text-white font-bold">{branch.contribution_percentage}%</span>
                </div>
                <div className="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full transition-all duration-500 ${
                      isTop
                        ? 'bg-gradient-to-r from-indigo-500 to-emerald-400'
                        : 'bg-indigo-600'
                    }`}
                    style={{ width: `${Math.max(branch.contribution_percentage, 1)}%` }}
                  />
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};
