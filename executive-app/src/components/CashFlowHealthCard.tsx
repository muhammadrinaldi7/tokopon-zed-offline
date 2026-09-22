import React from 'react';
import type { KpiSummary } from '../types';
import { ShieldCheck, AlertTriangle, Wallet, Clock, ArrowUpRight } from 'lucide-react';

interface CashFlowHealthCardProps {
  summary?: KpiSummary;
  isLoading?: boolean;
}

export const CashFlowHealthCard: React.FC<CashFlowHealthCardProps> = ({ summary, isLoading }) => {
  if (isLoading) {
    return (
      <div className="glass-card rounded-2xl p-5 border border-slate-800 animate-pulse h-44">
        <div className="h-4 bg-slate-800 rounded w-1/3 mb-4"></div>
        <div className="h-8 bg-slate-800 rounded w-1/2 mb-2"></div>
        <div className="h-3 bg-slate-800 rounded w-full"></div>
      </div>
    );
  }

  const completed = Number(summary?.completed_amount || 0);
  const piutang = Number(summary?.piutang_amount || 0);
  const totalBilled = completed + piutang;
  const collectionRate = totalBilled > 0 ? (completed / totalBilled) * 100 : 100;

  // Evaluation status
  let statusColor = 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10';
  let statusText = 'Likuiditas Sangat Sehat';
  let StatusIcon = ShieldCheck;

  if (collectionRate < 75) {
    statusColor = 'text-rose-400 border-rose-500/30 bg-rose-500/10';
    statusText = 'Perhatian: Risiko Piutang Tinggi';
    StatusIcon = AlertTriangle;
  } else if (collectionRate < 90) {
    statusColor = 'text-amber-400 border-amber-500/30 bg-amber-500/10';
    statusText = 'Moderat: Monitor Penagihan Piutang';
    StatusIcon = AlertTriangle;
  }

  const formatCurrency = (val: number) =>
    new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val);

  return (
    <div className="glass-card rounded-2xl p-6 border border-slate-800/80 hover:border-slate-700/80 transition-all shadow-xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800/70">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-2 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
              <Wallet className="w-5 h-5" />
            </span>
            <div>
              <h3 className="text-base font-bold text-slate-100 tracking-tight">
                Kesehatan Arus Kas & Realisasi Penjualan
              </h3>
              <p className="text-xs text-slate-400">
                Rasio penagihan kas masuk langsung vs piutang berjalan periode ini
              </p>
            </div>
          </div>
        </div>

        {/* Status Badge */}
        <div className={`flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-semibold ${statusColor} shrink-0`}>
          <StatusIcon className="w-4 h-4" />
          <span>{statusText}</span>
        </div>
      </div>

      {/* Metrics Row */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 my-5">
        {/* Realisasi Kas */}
        <div className="p-4 rounded-xl bg-slate-900/50 border border-slate-800/60">
          <div className="flex items-center justify-between text-xs text-slate-400 mb-1">
            <span className="flex items-center gap-1.5 font-medium">
              <span className="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
              Kas Lunas Masuk
            </span>
            <ArrowUpRight className="w-3.5 h-3.5 text-emerald-400" />
          </div>
          <div className="text-xl font-black text-emerald-300 tabular-nums">
            {formatCurrency(completed)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">Realisasi dana bersih diterima</p>
        </div>

        {/* Piutang Berjalan */}
        <div className="p-4 rounded-xl bg-slate-900/50 border border-slate-800/60">
          <div className="flex items-center justify-between text-xs text-slate-400 mb-1">
            <span className="flex items-center gap-1.5 font-medium">
              <span className="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
              Total Piutang Berjalan
            </span>
            <Clock className="w-3.5 h-3.5 text-amber-400" />
          </div>
          <div className="text-xl font-black text-amber-300 tabular-nums">
            {formatCurrency(piutang)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">Tagihan belum tertagih / tempo</p>
        </div>

        {/* Tingkat Penagihan */}
        <div className="p-4 rounded-xl bg-slate-900/50 border border-slate-800/60">
          <div className="flex items-center justify-between text-xs text-slate-400 mb-1">
            <span className="font-medium">Tingkat Penagihan (Collection Rate)</span>
            <span className="font-bold text-indigo-400">{collectionRate.toFixed(1)}%</span>
          </div>
          <div className="text-xl font-black text-indigo-300 tabular-nums">
            {collectionRate.toFixed(1)}%
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            {collectionRate >= 90 ? 'Sangat optimal' : 'Memerlukan tindak lanjut'}
          </p>
        </div>
      </div>

      {/* Visual Progress Bar */}
      <div className="space-y-1.5 pt-2">
        <div className="flex justify-between text-xs text-slate-400">
          <span>Komposisi Realisasi:</span>
          <span>
            <strong className="text-emerald-400">{collectionRate.toFixed(1)}% Lunas</strong> &bull;{' '}
            <strong className="text-amber-400">{(100 - collectionRate).toFixed(1)}% Piutang</strong>
          </span>
        </div>
        <div className="h-2.5 w-full bg-slate-800 rounded-full overflow-hidden flex">
          <div
            className="bg-emerald-500 h-full transition-all duration-700"
            style={{ width: `${Math.min(collectionRate, 100)}%` }}
            title={`Kas Lunas: ${collectionRate.toFixed(1)}%`}
          ></div>
          <div
            className="bg-amber-500 h-full transition-all duration-700"
            style={{ width: `${Math.max(0, 100 - collectionRate)}%` }}
            title={`Piutang: ${(100 - collectionRate).toFixed(1)}%`}
          ></div>
        </div>
      </div>
    </div>
  );
};
