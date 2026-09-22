import React, { useEffect, useState } from 'react';
import { useFilter } from '../../context/FilterContext';
import { executiveApi } from '../../api/executive';
import type { BrandAnalyticsData, BrandMetricItem } from '../../types';
import { formatCurrency, formatNumber } from '../../utils/formatters';
import {
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Tooltip as RechartsTooltip,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
} from 'recharts';
import {
  Tag,
  ShoppingBag,
  TrendingUp,
  Layers,
  PieChart as PieIcon,
  BarChart3,
} from 'lucide-react';

const BRAND_PALETTE = [
  '#6366f1', // indigo
  '#06b6d4', // cyan
  '#10b981', // emerald
  '#f59e0b', // amber
  '#ec4899', // pink
  '#8b5cf6', // purple
  '#3b82f6', // blue
  '#14b8a6', // teal
  '#f97316', // orange
  '#64748b', // slate
];

export const BrandAnalyticsTab: React.FC = () => {
  const { filters, refreshKey } = useFilter();
  const [data, setData] = useState<BrandAnalyticsData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    const fetchData = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const res = await executiveApi.getBrandAnalytics(filters);
        if (isMounted && res.success && res.data) {
          setData(res.data);
        }
      } catch (err: unknown) {
        if (!isMounted) return;
        const axiosErr = err as { response?: { data?: { message?: string } } };
        setError(axiosErr.response?.data?.message || 'Gagal memuat analisis brand.');
      } finally {
        if (isMounted) setIsLoading(false);
      }
    };

    fetchData();
    return () => {
      isMounted = false;
    };
  }, [filters, refreshKey]);

  if (isLoading) {
    return (
      <div className="space-y-6 animate-pulse">
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-28 bg-slate-900/60 rounded-2xl border border-slate-800" />
          ))}
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="h-80 bg-slate-900/60 rounded-2xl border border-slate-800" />
          <div className="h-80 bg-slate-900/60 rounded-2xl border border-slate-800" />
        </div>
        <div className="h-96 bg-slate-900/60 rounded-2xl border border-slate-800" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300">
        <p className="font-semibold">Terjadi kesalahan:</p>
        <p className="text-sm mt-1">{error}</p>
      </div>
    );
  }

  const brands: BrandMetricItem[] = data?.brands || [];
  const summary = data?.summary || { total_revenue: 0, total_qty: 0, total_brands: 0 };

  // Prepare Pie Chart Data (Top 6 Brands + Others)
  const topBrandsForPie = brands.slice(0, 6).map((b, i) => ({
    name: b.brand_name,
    value: b.gross_sales,
    market_share: b.market_share_pct,
    color: BRAND_PALETTE[i % BRAND_PALETTE.length],
  }));

  const remainingSales = brands.slice(6).reduce((acc, b) => acc + b.gross_sales, 0);
  if (remainingSales > 0) {
    topBrandsForPie.push({
      name: 'Lainnya',
      value: remainingSales,
      market_share: Number(
        (
          (remainingSales / (summary.total_revenue || 1)) *
          100
        ).toFixed(1)
      ),
      color: '#64748b',
    });
  }

  // Prepare Bar Chart Data for Margins (Top 8 Brands)
  const topBrandsForBar = brands.slice(0, 8).map((b) => ({
    name: b.brand_name,
    margin_pct: b.margin_pct,
    gross_profit: b.gross_profit,
  }));

  return (
    <div className="space-y-6">
      {/* 1. KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
            <TrendingUp className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-medium text-slate-400">Total Omzet Brand</p>
            <h4 className="text-2xl font-bold text-white mt-0.5">
              {formatCurrency(summary.total_revenue)}
            </h4>
            <p className="text-[11px] text-indigo-300 mt-0.5">Seluruh produk terjual</p>
          </div>
        </div>

        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
            <ShoppingBag className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-medium text-slate-400">Total Unit Terjual</p>
            <h4 className="text-2xl font-bold text-white mt-0.5">
              {formatNumber(summary.total_qty)}{' '}
              <span className="text-xs font-normal text-slate-400">unit</span>
            </h4>
            <p className="text-[11px] text-emerald-300 mt-0.5">Volume penjualan agregat</p>
          </div>
        </div>

        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400">
            <Layers className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-medium text-slate-400">Brand Terdaftar Aktif</p>
            <h4 className="text-2xl font-bold text-white mt-0.5">
              {formatNumber(summary.total_brands)}{' '}
              <span className="text-xs font-normal text-slate-400">merek</span>
            </h4>
            <p className="text-[11px] text-purple-300 mt-0.5">Tercatat transaksi penjualan</p>
          </div>
        </div>
      </div>

      {/* 2. Visual Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Left: Donut Chart Market Share */}
        <div className="glass-card rounded-2xl p-6 border border-slate-800/90 flex flex-col justify-between">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <PieIcon className="w-4 h-4 text-indigo-400" />
              <h3 className="text-base font-bold text-white">Komposisi Pangsa Pasar (Market Share)</h3>
            </div>
            <span className="text-[11px] text-slate-400">Berdasarkan Nilai Penjualan</span>
          </div>

          {topBrandsForPie.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
              <div className="h-60 flex items-center justify-center">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={topBrandsForPie}
                      cx="50%"
                      cy="50%"
                      innerRadius={60}
                      outerRadius={85}
                      paddingAngle={3}
                      dataKey="value"
                    >
                      {topBrandsForPie.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <RechartsTooltip
                      content={({ active, payload }) => {
                        if (active && payload && payload.length) {
                          const dataItem = payload[0].payload;
                          return (
                            <div className="bg-slate-900/95 backdrop-blur-md border border-slate-700 p-3 rounded-xl shadow-xl text-xs">
                              <p className="font-bold text-white">{dataItem.name}</p>
                              <p className="text-emerald-400 mt-1">
                                {formatCurrency(dataItem.value)}
                              </p>
                              <p className="text-slate-400 text-[11px] mt-0.5">
                                Pangsa Pasar: <span className="text-indigo-300 font-semibold">{dataItem.market_share}%</span>
                              </p>
                            </div>
                          );
                        }
                        return null;
                      }}
                    />
                  </PieChart>
                </ResponsiveContainer>
              </div>

              {/* Legend List */}
              <div className="space-y-2">
                {topBrandsForPie.map((item, idx) => (
                  <div key={idx} className="flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2 truncate pr-2">
                      <span
                        className="w-2.5 h-2.5 rounded-full shrink-0"
                        style={{ backgroundColor: item.color }}
                      />
                      <span className="text-slate-300 font-medium truncate">{item.name}</span>
                    </div>
                    <div className="text-right shrink-0">
                      <span className="font-semibold text-white">{item.market_share}%</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            <div className="h-60 flex items-center justify-center text-xs text-slate-500">
              Belum ada data brand tersedia
            </div>
          )}
        </div>

        {/* Right: Bar Chart Profit Margin % */}
        <div className="glass-card rounded-2xl p-6 border border-slate-800/90 flex flex-col justify-between">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <BarChart3 className="w-4 h-4 text-emerald-400" />
              <h3 className="text-base font-bold text-white">Profit Margin (%) per Brand</h3>
            </div>
            <span className="text-[11px] text-slate-400">Persentase Margin Laba Kotor</span>
          </div>

          {topBrandsForBar.length > 0 ? (
            <div className="h-60">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={topBrandsForBar} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#1e293b" vertical={false} />
                  <XAxis
                    dataKey="name"
                    stroke="#64748b"
                    fontSize={10}
                    tickLine={false}
                    axisLine={{ stroke: '#334155' }}
                  />
                  <YAxis
                    stroke="#64748b"
                    fontSize={10}
                    tickLine={false}
                    axisLine={{ stroke: '#334155' }}
                    tickFormatter={(val) => `${val}%`}
                  />
                  <RechartsTooltip
                    content={({ active, payload }) => {
                      if (active && payload && payload.length) {
                        const dataItem = payload[0].payload;
                        return (
                          <div className="bg-slate-900/95 backdrop-blur-md border border-slate-700 p-3 rounded-xl shadow-xl text-xs">
                            <p className="font-bold text-white">{dataItem.name}</p>
                            <p className="text-emerald-400 mt-1">
                              Margin: <span className="font-semibold">{dataItem.margin_pct}%</span>
                            </p>
                            <p className="text-slate-400 text-[11px] mt-0.5">
                              Laba: {formatCurrency(dataItem.gross_profit)}
                            </p>
                          </div>
                        );
                      }
                      return null;
                    }}
                  />
                  <Bar
                    dataKey="margin_pct"
                    fill="#10b981"
                    radius={[6, 6, 0, 0]}
                  />
                </BarChart>
              </ResponsiveContainer>
            </div>
          ) : (
            <div className="h-60 flex items-center justify-center text-xs text-slate-500">
              Belum ada data margin tersedia
            </div>
          )}
        </div>
      </div>

      {/* 3. Detailed Brand Performance Table */}
      <div className="glass-card rounded-2xl border border-slate-800/80 overflow-hidden">
        <div className="p-5 border-b border-slate-800/80 flex items-center justify-between">
          <div>
            <h3 className="text-base font-bold text-white">Tabel Kinerja & Profitabilitas Brand</h3>
            <p className="text-xs text-slate-400 mt-0.5">
              Rincian penjualan, estimasi modal (HPP), laba kotor, dan margin tiap brand
            </p>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs text-slate-300">
            <thead className="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
              <tr>
                <th className="py-3.5 px-4 w-12 text-center">Rank</th>
                <th className="py-3.5 px-4">Nama Merek</th>
                <th className="py-3.5 px-4 text-center">Unit Terjual</th>
                <th className="py-3.5 px-4 text-right">Omzet Kotor</th>
                <th className="py-3.5 px-4 text-right">Estimasi HPP</th>
                <th className="py-3.5 px-4 text-right">Laba Kotor</th>
                <th className="py-3.5 px-4 text-center">Margin %</th>
                <th className="py-3.5 px-4 w-40">Pangsa Pasar (%)</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/50">
              {brands.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-slate-500">
                    Tidak ada transaksi brand pada periode yang dipilih
                  </td>
                </tr>
              ) : (
                brands.map((row) => {
                  const marginColor =
                    row.margin_pct >= 20
                      ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                      : row.margin_pct >= 10
                      ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30'
                      : row.margin_pct >= 5
                      ? 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                      : 'bg-rose-500/10 text-rose-400 border-rose-500/30';

                  return (
                    <tr key={row.rank} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-3.5 px-4 text-center">
                        <span
                          className={`inline-flex items-center justify-center w-6 h-6 rounded-full text-[11px] font-bold ${
                            row.rank === 1
                              ? 'bg-indigo-500 text-white font-extrabold'
                              : row.rank === 2
                              ? 'bg-slate-300 text-slate-950 font-bold'
                              : row.rank === 3
                              ? 'bg-amber-700 text-white font-bold'
                              : 'bg-slate-800 text-slate-400'
                          }`}
                        >
                          {row.rank}
                        </span>
                      </td>
                      <td className="py-3.5 px-4">
                        <div className="flex items-center gap-2">
                          <span className="p-1 rounded bg-slate-800 border border-slate-700">
                            <Tag className="w-3.5 h-3.5 text-indigo-400" />
                          </span>
                          <span className="font-semibold text-white">{row.brand_name}</span>
                        </div>
                      </td>
                      <td className="py-3.5 px-4 text-center font-medium text-slate-200">
                        {formatNumber(row.qty_sold)}
                      </td>
                      <td className="py-3.5 px-4 text-right font-semibold text-slate-200">
                        {formatCurrency(row.gross_sales)}
                      </td>
                      <td className="py-3.5 px-4 text-right text-slate-400">
                        {formatCurrency(row.hpp)}
                      </td>
                      <td className="py-3.5 px-4 text-right font-bold text-emerald-400">
                        {formatCurrency(row.gross_profit)}
                      </td>
                      <td className="py-3.5 px-4 text-center">
                        <span
                          className={`inline-block px-2 py-0.5 rounded text-[11px] font-bold border ${marginColor}`}
                        >
                          {row.margin_pct}%
                        </span>
                      </td>
                      <td className="py-3.5 px-4">
                        <div className="flex items-center gap-2">
                          <div className="flex-1 bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div
                              className="bg-indigo-500 h-full rounded-full"
                              style={{ width: `${Math.min(row.market_share_pct, 100)}%` }}
                            />
                          </div>
                          <span className="text-[11px] font-semibold text-slate-300 w-12 text-right">
                            {row.market_share_pct}%
                          </span>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
