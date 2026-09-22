import React, { useEffect, useState } from 'react';
import { useFilter } from '../../context/FilterContext';
import { executiveApi } from '../../api/executive';
import type { StaffKpiData, SalesPersonKpi, CashierKpi } from '../../types';
import { formatCurrency, formatNumber } from '../../utils/formatters';
import {
  Users,
  UserCheck,
  ShoppingBag,
  Trophy,
  Award,
  Medal,
  CreditCard,
} from 'lucide-react';

export const StaffKpiTab: React.FC = () => {
  const { filters, refreshKey } = useFilter();
  const [data, setData] = useState<StaffKpiData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [staffRole, setStaffRole] = useState<'sales' | 'cashier'>('sales');

  useEffect(() => {
    let isMounted = true;
    const fetchData = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const res = await executiveApi.getStaffKpi(filters);
        if (isMounted && res.success && res.data) {
          setData(res.data);
        }
      } catch (err: unknown) {
        if (!isMounted) return;
        const axiosErr = err as { response?: { data?: { message?: string } } };
        setError(axiosErr.response?.data?.message || 'Gagal memuat data KPI staff.');
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
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {[1, 2, 3].map((i) => (
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

  const salesList: SalesPersonKpi[] = data?.sales || [];
  const cashiersList: CashierKpi[] = data?.cashiers || [];
  const summary = data?.summary || { total_sales_count: 0, total_cashiers_count: 0, total_orders: 0 };

  const top3Sales = salesList.slice(0, 3);
  const top3Cashiers = cashiersList.slice(0, 3);

  return (
    <div className="space-y-6">
      {/* 1. Header Metrics Summary */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
            <Users className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-medium text-slate-400">Tenaga Salesperson</p>
            <h4 className="text-2xl font-bold text-white mt-0.5">
              {formatNumber(summary.total_sales_count)}{' '}
              <span className="text-xs font-normal text-slate-400">orang</span>
            </h4>
            <p className="text-[11px] text-indigo-300 mt-0.5">Ditelusuri via field sales_id</p>
          </div>
        </div>

        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400">
            <UserCheck className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-medium text-slate-400">Kasir Bertugas</p>
            <h4 className="text-2xl font-bold text-white mt-0.5">
              {formatNumber(summary.total_cashiers_count)}{' '}
              <span className="text-xs font-normal text-slate-400">orang</span>
            </h4>
            <p className="text-[11px] text-cyan-300 mt-0.5">Ditelusuri via field handled_by</p>
          </div>
        </div>

        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
            <ShoppingBag className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-medium text-slate-400">Total Transaksi Selesai</p>
            <h4 className="text-2xl font-bold text-white mt-0.5">
              {formatNumber(summary.total_orders)}{' '}
              <span className="text-xs font-normal text-slate-400">orders</span>
            </h4>
            <p className="text-[11px] text-emerald-300 mt-0.5">Pada periode filter aktif</p>
          </div>
        </div>
      </div>

      {/* 2. Role Selector Tabs */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-2 bg-slate-900/60 rounded-2xl border border-slate-800/80">
        <div className="flex items-center gap-2">
          <button
            onClick={() => setStaffRole('sales')}
            className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all ${
              staffRole === 'sales'
                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <Users className="w-4 h-4" />
            Ranking Salesperson ({salesList.length})
          </button>
          <button
            onClick={() => setStaffRole('cashier')}
            className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all ${
              staffRole === 'cashier'
                ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <UserCheck className="w-4 h-4" />
            Ranking Kasir ({cashiersList.length})
          </button>
        </div>

        <div className="text-xs text-slate-400 px-3">
          {staffRole === 'sales'
            ? 'Metrik sales dihitung berdasarkan penjualan yang di-closing oleh masing-masing staff.'
            : 'Metrik kasir dihitung berdasarkan transaksi pembayaran yang diproses dan dicetak nota oleh kasir.'}
        </div>
      </div>

      {/* 3. Top 3 Podium Cards */}
      {staffRole === 'sales' ? (
        top3Sales.length > 0 && (
          <div>
            <div className="flex items-center gap-2 mb-3">
              <Trophy className="w-4 h-4 text-amber-400" />
              <h3 className="text-sm font-bold text-white uppercase tracking-wider">
                Top 3 Performa Salesperson
              </h3>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {top3Sales.map((sales, idx) => {
                const medalColors = [
                  'from-amber-500/20 to-amber-600/10 border-amber-500/40 text-amber-300',
                  'from-slate-400/20 to-slate-500/10 border-slate-400/40 text-slate-200',
                  'from-amber-700/20 to-amber-800/10 border-amber-700/40 text-amber-500',
                ];
                const badgeText = idx === 0 ? 'Juara 1' : idx === 1 ? 'Juara 2' : 'Juara 3';

                return (
                  <div
                    key={sales.sales_id || idx}
                    className={`glass-card rounded-2xl p-5 border bg-gradient-to-b ${medalColors[idx]} relative overflow-hidden`}
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <span className="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-900/60 border border-slate-700">
                          {badgeText}
                        </span>
                        <h4 className="text-lg font-bold text-white mt-1.5">{sales.sales_name}</h4>
                        <p className="text-xs text-slate-400">{sales.position}</p>
                      </div>
                      <div className="w-10 h-10 rounded-full flex items-center justify-center bg-slate-900/60 border border-slate-700 shadow">
                        {idx === 0 ? (
                          <Trophy className="w-5 h-5 text-amber-400" />
                        ) : idx === 1 ? (
                          <Award className="w-5 h-5 text-slate-300" />
                        ) : (
                          <Medal className="w-5 h-5 text-amber-600" />
                        )}
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-slate-800/60">
                      <div>
                        <p className="text-[10px] text-slate-400">Total Omzet Bersih</p>
                        <p className="text-sm font-bold text-emerald-400 mt-0.5">
                          {formatCurrency(sales.net_sales)}
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-slate-400">Kontribusi Omzet</p>
                        <p className="text-sm font-bold text-indigo-300 mt-0.5">
                          {sales.contribution_pct}%
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-slate-400">Transaksi Selesai</p>
                        <p className="text-xs font-semibold text-slate-200 mt-0.5">
                          {formatNumber(sales.orders_count)} order ({sales.total_qty} unit)
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-slate-400">Rata-rata Basket (AOV)</p>
                        <p className="text-xs font-semibold text-slate-200 mt-0.5">
                          {formatCurrency(sales.aov)}
                        </p>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )
      ) : (
        top3Cashiers.length > 0 && (
          <div>
            <div className="flex items-center gap-2 mb-3">
              <Trophy className="w-4 h-4 text-cyan-400" />
              <h3 className="text-sm font-bold text-white uppercase tracking-wider">
                Top 3 Performa Kasir (Volume & Penyelesaian)
              </h3>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {top3Cashiers.map((cashier, idx) => {
                const medalColors = [
                  'from-cyan-500/20 to-cyan-600/10 border-cyan-500/40 text-cyan-300',
                  'from-slate-400/20 to-slate-500/10 border-slate-400/40 text-slate-200',
                  'from-amber-700/20 to-amber-800/10 border-amber-700/40 text-amber-500',
                ];
                const badgeText = idx === 0 ? 'Top 1' : idx === 1 ? 'Top 2' : 'Top 3';

                return (
                  <div
                    key={cashier.cashier_id || idx}
                    className={`glass-card rounded-2xl p-5 border bg-gradient-to-b ${medalColors[idx]} relative overflow-hidden`}
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <span className="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-900/60 border border-slate-700">
                          {badgeText}
                        </span>
                        <h4 className="text-lg font-bold text-white mt-1.5">{cashier.cashier_name}</h4>
                        <p className="text-xs text-slate-400">Kasir Operasional</p>
                      </div>
                      <div className="w-10 h-10 rounded-full flex items-center justify-center bg-slate-900/60 border border-slate-700 shadow">
                        <CreditCard className="w-5 h-5 text-cyan-400" />
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-slate-800/60">
                      <div>
                        <p className="text-[10px] text-slate-400">Total Nominal Diproses</p>
                        <p className="text-sm font-bold text-cyan-400 mt-0.5">
                          {formatCurrency(cashier.completed_amount)}
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-slate-400">Share Checkout</p>
                        <p className="text-sm font-bold text-indigo-300 mt-0.5">
                          {cashier.share_pct}%
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-slate-400">Orders Ditangani</p>
                        <p className="text-xs font-semibold text-slate-200 mt-0.5">
                          {formatNumber(cashier.orders_count)} transaksi
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-slate-400">Rata-rata Transaksi</p>
                        <p className="text-xs font-semibold text-slate-200 mt-0.5">
                          {formatCurrency(cashier.aov)}
                        </p>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )
      )}

      {/* 4. Detailed Ranking Table */}
      <div className="glass-card rounded-2xl border border-slate-800/80 overflow-hidden">
        <div className="p-5 border-b border-slate-800/80 flex items-center justify-between">
          <div>
            <h3 className="text-base font-bold text-white">
              {staffRole === 'sales' ? 'Tabel Klasemen Salesperson' : 'Tabel Klasemen Kasir'}
            </h3>
            <p className="text-xs text-slate-400 mt-0.5">
              Urutan berdasarkan omzet tertinggi dan kontribusi terhadap total toko
            </p>
          </div>
        </div>

        <div className="overflow-x-auto">
          {staffRole === 'sales' ? (
            <table className="w-full text-left text-xs text-slate-300">
              <thead className="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                <tr>
                  <th className="py-3.5 px-4 w-12 text-center">Rank</th>
                  <th className="py-3.5 px-4">Nama Sales</th>
                  <th className="py-3.5 px-4">Jabatan</th>
                  <th className="py-3.5 px-4 text-center">Qty Unit</th>
                  <th className="py-3.5 px-4 text-center">Orders</th>
                  <th className="py-3.5 px-4 text-right">Penjualan Kotor</th>
                  <th className="py-3.5 px-4 text-right">Penjualan Bersih</th>
                  <th className="py-3.5 px-4 text-right">AOV (Keranjang)</th>
                  <th className="py-3.5 px-4 w-36">Kontribusi (%)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/50">
                {salesList.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="py-8 text-center text-slate-500">
                      Tidak ada data sales pada periode yang dipilih
                    </td>
                  </tr>
                ) : (
                  salesList.map((row) => (
                    <tr key={row.sales_id || row.rank} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-3.5 px-4 text-center">
                        <span
                          className={`inline-flex items-center justify-center w-6 h-6 rounded-full text-[11px] font-bold ${
                            row.rank === 1
                              ? 'bg-amber-400 text-slate-950 font-extrabold'
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
                      <td className="py-3.5 px-4 font-semibold text-white">{row.sales_name}</td>
                      <td className="py-3.5 px-4 text-slate-400">{row.position}</td>
                      <td className="py-3.5 px-4 text-center font-medium text-slate-200">
                        {formatNumber(row.total_qty)}
                      </td>
                      <td className="py-3.5 px-4 text-center font-medium text-slate-200">
                        {formatNumber(row.orders_count)}
                      </td>
                      <td className="py-3.5 px-4 text-right text-slate-400">
                        {formatCurrency(row.gross_sales)}
                      </td>
                      <td className="py-3.5 px-4 text-right font-bold text-emerald-400">
                        {formatCurrency(row.net_sales)}
                      </td>
                      <td className="py-3.5 px-4 text-right text-slate-300">
                        {formatCurrency(row.aov)}
                      </td>
                      <td className="py-3.5 px-4">
                        <div className="flex items-center gap-2">
                          <div className="flex-1 bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div
                              className="bg-indigo-500 h-full rounded-full"
                              style={{ width: `${Math.min(row.contribution_pct, 100)}%` }}
                            />
                          </div>
                          <span className="text-[11px] font-semibold text-slate-300 w-10 text-right">
                            {row.contribution_pct}%
                          </span>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          ) : (
            <table className="w-full text-left text-xs text-slate-300">
              <thead className="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                <tr>
                  <th className="py-3.5 px-4 w-12 text-center">Rank</th>
                  <th className="py-3.5 px-4">Nama Kasir</th>
                  <th className="py-3.5 px-4 text-center">Orders Diproses</th>
                  <th className="py-3.5 px-4 text-right">Gross Transaksi</th>
                  <th className="py-3.5 px-4 text-right">Nominal Selesai</th>
                  <th className="py-3.5 px-4 text-right">Rata-rata Nota</th>
                  <th className="py-3.5 px-4 w-36">Share Transaksi (%)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/50">
                {cashiersList.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="py-8 text-center text-slate-500">
                      Tidak ada data kasir pada periode yang dipilih
                    </td>
                  </tr>
                ) : (
                  cashiersList.map((row) => (
                    <tr
                      key={row.cashier_id || row.rank}
                      className="hover:bg-slate-800/40 transition-colors"
                    >
                      <td className="py-3.5 px-4 text-center">
                        <span
                          className={`inline-flex items-center justify-center w-6 h-6 rounded-full text-[11px] font-bold ${
                            row.rank === 1
                              ? 'bg-cyan-400 text-slate-950 font-extrabold'
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
                      <td className="py-3.5 px-4 font-semibold text-white">{row.cashier_name}</td>
                      <td className="py-3.5 px-4 text-center font-medium text-slate-200">
                        {formatNumber(row.orders_count)}
                      </td>
                      <td className="py-3.5 px-4 text-right text-slate-400">
                        {formatCurrency(row.total_gross)}
                      </td>
                      <td className="py-3.5 px-4 text-right font-bold text-cyan-400">
                        {formatCurrency(row.completed_amount)}
                      </td>
                      <td className="py-3.5 px-4 text-right text-slate-300">
                        {formatCurrency(row.aov)}
                      </td>
                      <td className="py-3.5 px-4">
                        <div className="flex items-center gap-2">
                          <div className="flex-1 bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div
                              className="bg-cyan-500 h-full rounded-full"
                              style={{ width: `${Math.min(row.share_pct, 100)}%` }}
                            />
                          </div>
                          <span className="text-[11px] font-semibold text-slate-300 w-10 text-right">
                            {row.share_pct}%
                          </span>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
};
