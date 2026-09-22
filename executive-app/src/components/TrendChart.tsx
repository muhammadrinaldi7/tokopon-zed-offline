import React, { useState } from 'react';
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';
import type { SalesTrend } from '../types';
import { formatCurrency, formatCompact } from '../utils/formatters';
import { TrendingUp } from 'lucide-react';

interface TrendChartProps {
  trend?: SalesTrend | null;
  isLoading?: boolean;
}

export const TrendChart: React.FC<TrendChartProps> = ({ trend, isLoading }) => {
  const [viewMetric, setViewMetric] = useState<'both' | 'sales' | 'profit'>('both');

  if (isLoading || !trend) {
    return (
      <div className="glass-card rounded-2xl p-6 border border-slate-800 animate-pulse h-96 flex flex-col justify-between">
        <div className="flex justify-between items-center">
          <div className="h-5 bg-slate-800 rounded w-48" />
          <div className="h-8 bg-slate-800 rounded w-32" />
        </div>
        <div className="h-64 bg-slate-900/50 rounded-xl my-4" />
      </div>
    );
  }

  const chartData = trend.points.map((p) => ({
    name: p.label,
    net_sales: p.net_sales,
    gross_profit: p.gross_profit,
    orders: p.orders_count,
    qty: p.qty,
  }));

  const modeLabel =
    trend.mode === 'hourly'
      ? 'Per Jam'
      : trend.mode === 'monthly'
      ? 'Per Bulan'
      : 'Harian';

  return (
    <div className="glass-card rounded-2xl p-6 border border-slate-800/90">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
          <div className="flex items-center gap-2">
            <h3 className="text-base font-bold text-white tracking-tight flex items-center gap-2">
              <TrendingUp className="w-4 h-4 text-indigo-400" />
              Tren Penjualan & Laba Kotor
            </h3>
            <span className="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700">
              {modeLabel}
            </span>
          </div>
          <p className="text-xs text-slate-400 mt-1">
            Visualisasi dinamika pendapatan bersih vs laba kotor sepanjang periode
          </p>
        </div>

        {/* Metric View Selector */}
        <div className="flex items-center gap-1 p-1 bg-slate-900/80 rounded-xl border border-slate-800 text-xs">
          <button
            onClick={() => setViewMetric('both')}
            className={`px-3 py-1 rounded-lg font-medium transition-all cursor-pointer ${
              viewMetric === 'both'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Gabungan
          </button>
          <button
            onClick={() => setViewMetric('sales')}
            className={`px-3 py-1 rounded-lg font-medium transition-all cursor-pointer ${
              viewMetric === 'sales'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Omset Bersih
          </button>
          <button
            onClick={() => setViewMetric('profit')}
            className={`px-3 py-1 rounded-lg font-medium transition-all cursor-pointer ${
              viewMetric === 'profit'
                ? 'bg-emerald-600 text-white shadow-sm'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Laba Kotor
          </button>
        </div>
      </div>

      {/* Legend Pills */}
      <div className="flex items-center gap-4 mb-4 text-xs">
        {(viewMetric === 'both' || viewMetric === 'sales') && (
          <div className="flex items-center gap-1.5 text-slate-300">
            <span className="w-2.5 h-2.5 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500/50" />
            <span className="font-medium">Omset Bersih (Net Sales)</span>
          </div>
        )}
        {(viewMetric === 'both' || viewMetric === 'profit') && (
          <div className="flex items-center gap-1.5 text-slate-300">
            <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50" />
            <span className="font-medium">Laba Kotor (Gross Profit)</span>
          </div>
        )}
      </div>

      {/* Recharts Area Chart */}
      <div className="h-72 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={chartData} margin={{ top: 10, right: 10, left: -10, bottom: 0 }}>
            <defs>
              <linearGradient id="colorSales" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#6366f1" stopOpacity={0.4} />
                <stop offset="95%" stopColor="#6366f1" stopOpacity={0.0} />
              </linearGradient>
              <linearGradient id="colorProfit" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#34d399" stopOpacity={0.4} />
                <stop offset="95%" stopColor="#34d399" stopOpacity={0.0} />
              </linearGradient>
            </defs>

            <CartesianGrid strokeDasharray="3 3" stroke="#1e293b" vertical={false} />

            <XAxis
              dataKey="name"
              stroke="#64748b"
              fontSize={11}
              tickLine={false}
              axisLine={{ stroke: '#334155' }}
            />

            <YAxis
              stroke="#64748b"
              fontSize={11}
              tickLine={false}
              axisLine={false}
              tickFormatter={(v) => formatCompact(v)}
            />

            <Tooltip
              content={({ active, payload, label }) => {
                if (!active || !payload || !payload.length) return null;
                const data = payload[0].payload;
                return (
                  <div className="glass-panel p-3.5 rounded-xl shadow-2xl border border-slate-700/80 text-xs min-w-[200px]">
                    <div className="font-bold text-white mb-2 border-b border-slate-800 pb-1.5 flex items-center justify-between">
                      <span>{label}</span>
                      <span className="text-[10px] text-slate-400 font-normal">
                        {data.orders} Order ({data.qty} pcs)
                      </span>
                    </div>

                    <div className="space-y-1.5">
                      <div className="flex items-center justify-between gap-3">
                        <span className="text-indigo-300 font-medium flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-indigo-500" />
                          Net Sales:
                        </span>
                        <span className="font-bold text-white tabular-nums">
                          {formatCurrency(data.net_sales)}
                        </span>
                      </div>

                      <div className="flex items-center justify-between gap-3">
                        <span className="text-emerald-300 font-medium flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-emerald-400" />
                          Gross Profit:
                        </span>
                        <span className="font-bold text-emerald-400 tabular-nums">
                          {formatCurrency(data.gross_profit)}
                        </span>
                      </div>
                    </div>
                  </div>
                );
              }}
            />

            {(viewMetric === 'both' || viewMetric === 'sales') && (
              <Area
                type="monotone"
                dataKey="net_sales"
                stroke="#6366f1"
                strokeWidth={2.5}
                fillOpacity={1}
                fill="url(#colorSales)"
              />
            )}

            {(viewMetric === 'both' || viewMetric === 'profit') && (
              <Area
                type="monotone"
                dataKey="gross_profit"
                stroke="#34d399"
                strokeWidth={2.5}
                fillOpacity={1}
                fill="url(#colorProfit)"
              />
            )}
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};
