import React, { useEffect, useState } from 'react';
import { useFilter } from '../../context/FilterContext';
import { executiveApi } from '../../api/executive';
import type { PromoClaimsData, PromoLeaderboardItem, PromoClaimRowItem } from '../../types';
import { formatCurrency, formatNumber, formatDate } from '../../utils/formatters';
import {
  BadgePercent,
  Gift,
  ShoppingBag,
  TrendingDown,
  Building2,
  CheckCircle2,
} from 'lucide-react';

export const PromoClaimsTab: React.FC = () => {
  const { filters, refreshKey } = useFilter();
  const [data, setData] = useState<PromoClaimsData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    const fetchData = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const res = await executiveApi.getPromoClaims(filters);
        if (isMounted && res.success && res.data) {
          setData(res.data);
        }
      } catch (err: unknown) {
        if (!isMounted) return;
        const axiosErr = err as { response?: { data?: { message?: string } } };
        setError(axiosErr.response?.data?.message || 'Gagal memuat laporan klaim promo.');
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
        <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-28 bg-slate-900/60 rounded-2xl border border-slate-800" />
          ))}
        </div>
        <div className="h-44 bg-slate-900/60 rounded-2xl border border-slate-800" />
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

  const summary = data?.summary || {
    total_discount_amount: 0,
    orders_with_promo_count: 0,
    total_promo_claims_count: 0,
    avg_discount_per_order: 0,
  };
  const leaderboard: PromoLeaderboardItem[] = data?.promo_leaderboard || [];
  const claims: PromoClaimRowItem[] = data?.recent_claims || [];

  return (
    <div className="space-y-6">
      {/* 1. KPI Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 shrink-0">
            <BadgePercent className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">Total Subsidi & Diskon</p>
            <h4 className="text-xl font-bold text-indigo-300 mt-0.5 truncate">
              {formatCurrency(summary.total_discount_amount)}
            </h4>
            <p className="text-[11px] text-slate-400 mt-0.5">Biaya promosi terdistribusi</p>
          </div>
        </div>

        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 shrink-0">
            <ShoppingBag className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">Transaksi dg Promo</p>
            <h4 className="text-xl font-bold text-white mt-0.5 truncate">
              {formatNumber(summary.orders_with_promo_count)}{' '}
              <span className="text-xs font-normal text-slate-400">orders</span>
            </h4>
            <p className="text-[11px] text-cyan-300 mt-0.5">Memanfaatkan voucher/diskon</p>
          </div>
        </div>

        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 shrink-0">
            <Gift className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">Total Klaim Promo</p>
            <h4 className="text-xl font-bold text-white mt-0.5 truncate">
              {formatNumber(summary.total_promo_claims_count)}{' '}
              <span className="text-xs font-normal text-slate-400">klaim</span>
            </h4>
            <p className="text-[11px] text-purple-300 mt-0.5">Tingkat adopsi program</p>
          </div>
        </div>

        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 shrink-0">
            <TrendingDown className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">Rata-rata Diskon/Order</p>
            <h4 className="text-xl font-bold text-amber-300 mt-0.5 truncate">
              {formatCurrency(summary.avg_discount_per_order)}
            </h4>
            <p className="text-[11px] text-slate-400 mt-0.5">Subsidi per keranjang belanja</p>
          </div>
        </div>
      </div>

      {/* 2. Promo Program Leaderboard */}
      <div className="glass-card rounded-2xl border border-slate-800/80 p-5">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-base font-bold text-white flex items-center gap-2">
              <Gift className="w-4 h-4 text-indigo-400" />
              Klasemen Program Promo & Efektivitas Diskon
            </h3>
            <p className="text-xs text-slate-400 mt-0.5">
              Daftar program promo yang paling banyak digunakan konsumen dan total nilai pemotongan harga
            </p>
          </div>
        </div>

        {leaderboard.length === 0 ? (
          <div className="p-6 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800/60">
            <CheckCircle2 className="w-6 h-6 text-slate-500 mx-auto mb-2" />
            Tidak ada program promo atau voucher yang diklaim pada periode ini.
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {leaderboard.map((promo, idx) => (
              <div
                key={promo.promo_name || idx}
                className="p-4 rounded-xl bg-slate-900/80 border border-indigo-500/30 hover:border-indigo-500/60 transition-all flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-indigo-400">
                      #{idx + 1} Terpopuler
                    </span>
                    <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                      {formatNumber(promo.times_used)}x Digunakan
                    </span>
                  </div>
                  <h4 className="text-base font-bold text-white mt-2">{promo.promo_name}</h4>
                  <p className="text-xs text-slate-400">Program Promosi Penjualan</p>
                </div>

                <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                  <div>
                    <p className="text-[10px] text-slate-400">Total Potongan / Subsidi</p>
                    <p className="text-sm font-bold text-indigo-300 mt-0.5">
                      {formatCurrency(promo.total_discount)}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-[10px] text-slate-400">Rata-rata/Pakai</p>
                    <p className="text-xs font-semibold text-slate-300 mt-0.5">
                      {formatCurrency(
                        promo.times_used > 0 ? promo.total_discount / promo.times_used : 0
                      )}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* 3. Detailed Claims Log Table */}
      <div className="glass-card rounded-2xl border border-slate-800/80 overflow-hidden">
        <div className="p-5 border-b border-slate-800/80 flex items-center justify-between">
          <div>
            <h3 className="text-base font-bold text-white">Log Klaim Promo & Subsidi Vendor</h3>
            <p className="text-xs text-slate-400 mt-0.5">
              Rincian item produk yang mendapatkan diskon, nama program promo, dan vendor/brand penyedia subsidi
            </p>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs text-slate-300">
            <thead className="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
              <tr>
                <th className="py-3.5 px-4">Tanggal</th>
                <th className="py-3.5 px-4">No. Order / Nota</th>
                <th className="py-3.5 px-4">Cabang</th>
                <th className="py-3.5 px-4">Brand</th>
                <th className="py-3.5 px-4">Nama Produk</th>
                <th className="py-3.5 px-4">Nama Promo / Program</th>
                <th className="py-3.5 px-4">Vendor Penanggung</th>
                <th className="py-3.5 px-4 text-right">Nilai Klaim Subsidi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/50">
              {claims.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-slate-500">
                    Tidak ada catatan klaim promo pada periode ini
                  </td>
                </tr>
              ) : (
                claims.map((row, idx) => (
                  <tr key={idx} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-3.5 px-4 text-slate-400 whitespace-nowrap">
                      {formatDate(row.date)}
                    </td>
                    <td className="py-3.5 px-4 font-mono font-semibold text-white">
                      {row.order_number}
                    </td>
                    <td className="py-3.5 px-4 text-slate-400">{row.branch}</td>
                    <td className="py-3.5 px-4">
                      <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 border border-slate-700 text-slate-200">
                        {row.brand}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 font-medium text-white max-w-xs truncate">
                      {row.product_name}
                    </td>
                    <td className="py-3.5 px-4 text-indigo-300 font-semibold">
                      {row.promo_name}
                    </td>
                    <td className="py-3.5 px-4 text-slate-300 flex items-center gap-1.5 pt-4">
                      <Building2 className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                      <span>{row.vendor_name}</span>
                    </td>
                    <td className="py-3.5 px-4 text-right font-bold text-emerald-400">
                      {formatCurrency(row.claim_amount)}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
