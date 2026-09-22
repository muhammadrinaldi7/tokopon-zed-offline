import React from 'react';
import type { TopProduct } from '../types';
import { useFilter } from '../context/FilterContext';
import { formatCurrency } from '../utils/formatters';
import { PackageCheck, Trophy } from 'lucide-react';

interface TopProductsTableProps {
  products?: TopProduct[] | null;
  isLoading?: boolean;
}

export const TopProductsTable: React.FC<TopProductsTableProps> = ({ products, isLoading }) => {
  const { filters, setSortBy } = useFilter();

  if (isLoading || !products) {
    return (
      <div className="glass-card rounded-2xl p-6 border border-slate-800 animate-pulse h-96 flex flex-col justify-between">
        <div className="h-5 bg-slate-800 rounded w-48" />
        <div className="space-y-3 my-4">
          {[1, 2, 3, 4, 5].map((i) => (
            <div key={i} className="h-10 bg-slate-900/50 rounded-xl" />
          ))}
        </div>
      </div>
    );
  }

  const getRankBadge = (rank: number) => {
    switch (rank) {
      case 1:
        return 'bg-amber-500/20 text-amber-300 border-amber-500/40';
      case 2:
        return 'bg-slate-300/20 text-slate-200 border-slate-300/40';
      case 3:
        return 'bg-amber-700/20 text-amber-500 border-amber-700/40';
      default:
        return 'bg-slate-800 text-slate-400 border-slate-700';
    }
  };

  return (
    <div className="glass-card rounded-2xl p-6 border border-slate-800/90">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
        <div>
          <h3 className="text-base font-bold text-white tracking-tight flex items-center gap-2">
            <PackageCheck className="w-4 h-4 text-indigo-400" />
            Top 10 Produk Unggulan
          </h3>
          <p className="text-xs text-slate-400 mt-1">
            Peringkat produk terlaris berdasarkan omset kontribusi dan volume penjualan
          </p>
        </div>

        {/* Sort Switcher */}
        <div className="flex items-center gap-1 p-1 bg-slate-900/80 rounded-xl border border-slate-800 text-xs">
          <button
            onClick={() => setSortBy('revenue')}
            className={`px-3 py-1 rounded-lg font-medium transition-all cursor-pointer ${
              filters.sort_by === 'revenue'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Berdasarkan Omset
          </button>
          <button
            onClick={() => setSortBy('qty')}
            className={`px-3 py-1 rounded-lg font-medium transition-all cursor-pointer ${
              filters.sort_by === 'qty'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Berdasarkan Qty
          </button>
        </div>
      </div>

      {products.length === 0 ? (
        <div className="text-center py-10 text-slate-500 text-xs">
          Belum ada data penjualan produk untuk filter ini.
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                <th className="py-2.5 px-3">#</th>
                <th className="py-2.5 px-3">Produk & SKU</th>
                <th className="py-2.5 px-3">Brand</th>
                <th className="py-2.5 px-3 text-right">Terjual</th>
                <th className="py-2.5 px-3 text-right">Harga Rata-rata</th>
                <th className="py-2.5 px-3 text-right">Total Omset</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60 text-xs">
              {products.map((item) => (
                <tr key={`${item.rank}-${item.sku}`} className="hover:bg-slate-900/50 transition-colors">
                  <td className="py-3 px-3">
                    <span
                      className={`w-6 h-6 rounded-md inline-flex items-center justify-center font-bold text-xs border ${getRankBadge(
                        item.rank
                      )}`}
                    >
                      {item.rank <= 3 ? <Trophy className="w-3 h-3" /> : item.rank}
                    </span>
                  </td>
                  <td className="py-3 px-3">
                    <div className="font-semibold text-white max-w-xs sm:max-w-md truncate">
                      {item.name}
                    </div>
                    <div className="text-[10px] text-slate-500 font-mono mt-0.5">
                      SKU: {item.sku}
                    </div>
                  </td>
                  <td className="py-3 px-3">
                    <span className="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-800/80 text-slate-300 border border-slate-700/60">
                      {item.brand}
                    </span>
                  </td>
                  <td className="py-3 px-3 text-right font-semibold text-slate-200 tabular-nums">
                    {item.qty_sold.toLocaleString('id-ID')} unit
                  </td>
                  <td className="py-3 px-3 text-right text-slate-400 tabular-nums">
                    {formatCurrency(item.avg_price)}
                  </td>
                  <td className="py-3 px-3 text-right font-bold text-emerald-400 tabular-nums">
                    {formatCurrency(item.revenue)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};
