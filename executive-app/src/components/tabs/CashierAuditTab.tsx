import React, { useEffect, useState } from 'react';
import { useFilter } from '../../context/FilterContext';
import { executiveApi } from '../../api/executive';
import type { CashierAuditData } from '../../types';
import { formatCurrency, formatNumber, formatDate } from '../../utils/formatters';
import {
  ShieldAlert,
  AlertTriangle,
  FileWarning,
  Smartphone,
  TrendingDown,
  UserX,
  CheckCircle2,
  Filter,
} from 'lucide-react';

export const CashierAuditTab: React.FC = () => {
  const { filters, refreshKey } = useFilter();
  const [data, setData] = useState<CashierAuditData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [subTab, setSubTab] = useState<'sell_phone' | 'cancellations'>('sell_phone');
  const [onlyOverpay, setOnlyOverpay] = useState<boolean>(false);

  useEffect(() => {
    let isMounted = true;
    const fetchData = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const res = await executiveApi.getCashierAudit(filters);
        if (isMounted && res.success && res.data) {
          setData(res.data);
        }
      } catch (err: unknown) {
        if (!isMounted) return;
        const axiosErr = err as { response?: { data?: { message?: string } } };
        setError(axiosErr.response?.data?.message || 'Gagal memuat data audit kasir.');
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
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
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

  const cancelAudit = data?.cancellation_audit || {
    total_cancellations: 0,
    total_cancelled_amount: 0,
    cashier_leaderboard: [],
    recent_logs: [],
  };

  const sellAudit = data?.sell_phone_audit || {
    total_bought_units: 0,
    total_bought_amount: 0,
    total_system_amount: 0,
    total_overpay_units: 0,
    total_overpay_amount: 0,
    cashier_overpay_leaderboard: [],
    recent_logs: [],
  };

  const filteredSellLogs = onlyOverpay
    ? sellAudit.recent_logs.filter((log) => log.is_overpay)
    : sellAudit.recent_logs;

  return (
    <div className="space-y-6">
      {/* 1. Header Alert Banner / Executive Warning */}
      <div className="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="p-2.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 shrink-0">
            <ShieldAlert className="w-6 h-6" />
          </div>
          <div>
            <h4 className="text-sm font-bold text-white">
              Sistem Audit Integritas & Kepatuhan Kasir
            </h4>
            <p className="text-xs text-rose-200/80 mt-0.5">
              Mendeteksi transaksi yang dibatalkan kasir (void) dan penyimpangan harga beli HP bekas (kasir membeli di atas taksiran sistem).
            </p>
          </div>
        </div>
      </div>

      {/* 2. Top Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1: Overpay Beli HP */}
        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 shrink-0">
            <AlertTriangle className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">Total Overpay Beli HP</p>
            <h4 className="text-xl font-bold text-rose-400 mt-0.5 truncate">
              {formatCurrency(sellAudit.total_overpay_amount)}
            </h4>
            <p className="text-[11px] text-slate-400 mt-0.5">
              {formatNumber(sellAudit.total_overpay_units)} kasus di atas sistem
            </p>
          </div>
        </div>

        {/* Card 2: Total HP Bekas Dibeli */}
        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 shrink-0">
            <Smartphone className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">HP Bekas Diterima Toko</p>
            <h4 className="text-xl font-bold text-white mt-0.5 truncate">
              {formatNumber(sellAudit.total_bought_units)} unit
            </h4>
            <p className="text-[11px] text-cyan-300 mt-0.5 truncate">
              Nilai: {formatCurrency(sellAudit.total_bought_amount)}
            </p>
          </div>
        </div>

        {/* Card 3: Frekuensi Pembatalan */}
        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 shrink-0">
            <FileWarning className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">Pembatalan Transaksi</p>
            <h4 className="text-xl font-bold text-white mt-0.5 truncate">
              {formatNumber(cancelAudit.total_cancellations)} kasus
            </h4>
            <p className="text-[11px] text-amber-300 mt-0.5">Order cancellation requests</p>
          </div>
        </div>

        {/* Card 4: Nilai Transaksi Dibatalkan */}
        <div className="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-4">
          <div className="p-3.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 shrink-0">
            <TrendingDown className="w-6 h-6" />
          </div>
          <div className="min-w-0">
            <p className="text-xs font-medium text-slate-400 truncate">Nilai Batal (Void)</p>
            <h4 className="text-xl font-bold text-purple-300 mt-0.5 truncate">
              {formatCurrency(cancelAudit.total_cancelled_amount)}
            </h4>
            <p className="text-[11px] text-slate-400 mt-0.5">Potensi omzet terbuang</p>
          </div>
        </div>
      </div>

      {/* 3. Sub-Tab Switching Controls */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-2 bg-slate-900/60 rounded-2xl border border-slate-800/80">
        <div className="flex items-center gap-2">
          <button
            onClick={() => setSubTab('sell_phone')}
            className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all ${
              subTab === 'sell_phone'
                ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <Smartphone className="w-4 h-4" />
            Audit Pembelian HP Bekas (Overpay vs Sistem)
            {sellAudit.total_overpay_units > 0 && (
              <span className="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-rose-600">
                {sellAudit.total_overpay_units}
              </span>
            )}
          </button>
          <button
            onClick={() => setSubTab('cancellations')}
            className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all ${
              subTab === 'cancellations'
                ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/25'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
            }`}
          >
            <FileWarning className="w-4 h-4" />
            Audit Pembatalan Transaksi Kasir (Void)
            {cancelAudit.total_cancellations > 0 && (
              <span className="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-amber-600">
                {cancelAudit.total_cancellations}
              </span>
            )}
          </button>
        </div>

        {subTab === 'sell_phone' && (
          <div className="flex items-center gap-2 px-3">
            <button
              onClick={() => setOnlyOverpay(!onlyOverpay)}
              className={`px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors flex items-center gap-1.5 ${
                onlyOverpay
                  ? 'bg-rose-500/20 text-rose-300 border-rose-500/40'
                  : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-slate-200'
              }`}
            >
              <Filter className="w-3.5 h-3.5" />
              {onlyOverpay ? 'Menampilkan Kasus Overpay Saja' : 'Tampilkan Hanya Overpay'}
            </button>
          </div>
        )}
      </div>

      {/* 4. Sub-Tab Content */}
      {subTab === 'sell_phone' ? (
        <div className="space-y-6">
          {/* Cashier Overpay Leaderboard */}
          <div className="glass-card rounded-2xl border border-slate-800/80 p-5">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h3 className="text-base font-bold text-white flex items-center gap-2">
                  <UserX className="w-4 h-4 text-rose-400" />
                  Klasemen Kasir dengan Pembelian di Atas Harga Sistem (Overpay)
                </h3>
                <p className="text-xs text-slate-400 mt-0.5">
                  Daftar kasir yang paling sering menyepakati harga beli lebih mahal dari rekomendasi taksiran sistem
                </p>
              </div>
            </div>

            {sellAudit.cashier_overpay_leaderboard.length === 0 ? (
              <div className="p-6 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800/60">
                <CheckCircle2 className="w-6 h-6 text-emerald-400 mx-auto mb-2" />
                Semua kasir mematuhi atau membeli di bawah/sesuai harga taksiran sistem. Tidak ada pelanggaran overpay.
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {sellAudit.cashier_overpay_leaderboard.map((item, idx) => (
                  <div
                    key={item.cashier_id || idx}
                    className="p-4 rounded-xl bg-slate-900/80 border border-rose-500/30 hover:border-rose-500/60 transition-all flex flex-col justify-between"
                  >
                    <div>
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-rose-400">
                          #{idx + 1} Sering Overpay
                        </span>
                        <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                          {item.overpay_count} Transaksi
                        </span>
                      </div>
                      <h4 className="text-base font-bold text-white mt-1.5">{item.cashier_name}</h4>
                      <p className="text-xs text-slate-400">Kasir Pelaksana (handled_by)</p>
                    </div>

                    <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                      <div>
                        <p className="text-[10px] text-slate-400">Total Selisih Overpay</p>
                        <p className="text-sm font-bold text-rose-400 mt-0.5">
                          {formatCurrency(item.total_overpay_amount)}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-[10px] text-slate-400">Rata-rata Lebih Bayar</p>
                        <p className="text-xs font-semibold text-slate-300 mt-0.5">
                          {formatCurrency(item.avg_overpay)}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* SellPhone Audit Log Table */}
          <div className="glass-card rounded-2xl border border-slate-800/80 overflow-hidden">
            <div className="p-5 border-b border-slate-800/80 flex items-center justify-between">
              <div>
                <h3 className="text-base font-bold text-white">Log Transaksi Pembelian HP Bekas</h3>
                <p className="text-xs text-slate-400 mt-0.5">
                  Membandingkan harga taksiran otomatis sistem dengan harga final yang disepakati oleh kasir
                </p>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-slate-300">
                <thead className="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                  <tr>
                    <th className="py-3.5 px-4">Tanggal</th>
                    <th className="py-3.5 px-4">Kasir Pelaksana</th>
                    <th className="py-3.5 px-4">Cabang</th>
                    <th className="py-3.5 px-4">Device & Spesifikasi</th>
                    <th className="py-3.5 px-4 text-right">Harga Sistem</th>
                    <th className="py-3.5 px-4 text-right">Harga Final (Beli)</th>
                    <th className="py-3.5 px-4 text-right">Selisih (Diff)</th>
                    <th className="py-3.5 px-4 text-center">Status Audit</th>
                    <th className="py-3.5 px-4">Alasan / Catatan</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/50">
                  {filteredSellLogs.length === 0 ? (
                    <tr>
                      <td colSpan={9} className="py-8 text-center text-slate-500">
                        Tidak ada log transaksi pembelian HP bekas yang sesuai
                      </td>
                    </tr>
                  ) : (
                    filteredSellLogs.map((row) => (
                      <tr key={row.id} className="hover:bg-slate-800/40 transition-colors">
                        <td className="py-3.5 px-4 text-slate-400 whitespace-nowrap">
                          {formatDate(row.date)}
                        </td>
                        <td className="py-3.5 px-4 font-semibold text-white">
                          {row.cashier_name}
                        </td>
                        <td className="py-3.5 px-4 text-slate-400">{row.branch}</td>
                        <td className="py-3.5 px-4">
                          <span className="font-semibold text-white">{row.brand} {row.model}</span>
                          <span className="text-[11px] text-slate-400 block">
                            {row.ram_storage} &bull; IMEI: {row.imei}
                          </span>
                        </td>
                        <td className="py-3.5 px-4 text-right text-slate-300">
                          {formatCurrency(row.system_price)}
                        </td>
                        <td className="py-3.5 px-4 text-right font-bold text-white">
                          {formatCurrency(row.final_price)}
                        </td>
                        <td className="py-3.5 px-4 text-right">
                          <span
                            className={`font-bold ${
                              row.is_overpay
                                ? 'text-rose-400'
                                : row.diff_amount < 0
                                ? 'text-emerald-400'
                                : 'text-slate-400'
                            }`}
                          >
                            {row.diff_amount > 0 ? `+${formatCurrency(row.diff_amount)}` : formatCurrency(row.diff_amount)}
                          </span>
                          <span className="text-[10px] text-slate-400 block">
                            {row.diff_pct > 0 ? `+${row.diff_pct}%` : `${row.diff_pct}%`}
                          </span>
                        </td>
                        <td className="py-3.5 px-4 text-center">
                          {row.is_overpay ? (
                            <span className="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">
                              ⚠️ OVERPAY
                            </span>
                          ) : (
                            <span className="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                              ✓ Sesuai / Untung
                            </span>
                          )}
                        </td>
                        <td className="py-3.5 px-4 text-slate-400 max-w-xs truncate" title={row.reason}>
                          {row.reason || '-'}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Cancellation Leaderboard by Cashier */}
          <div className="glass-card rounded-2xl border border-slate-800/80 p-5">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h3 className="text-base font-bold text-white flex items-center gap-2">
                  <UserX className="w-4 h-4 text-amber-400" />
                  Klasemen Kasir dengan Frekuensi Pembatalan Nota Terbanyak
                </h3>
                <p className="text-xs text-slate-400 mt-0.5">
                  Daftar kasir yang meminta approval pembatalan transaksi (order void / cancellation)
                </p>
              </div>
            </div>

            {cancelAudit.cashier_leaderboard.length === 0 ? (
              <div className="p-6 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800/60">
                <CheckCircle2 className="w-6 h-6 text-emerald-400 mx-auto mb-2" />
                Tidak ada riwayat pembatalan transaksi pada periode filter yang dipilih.
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {cancelAudit.cashier_leaderboard.map((item, idx) => (
                  <div
                    key={item.cashier_id || idx}
                    className="p-4 rounded-xl bg-slate-900/80 border border-amber-500/30 hover:border-amber-500/60 transition-all flex flex-col justify-between"
                  >
                    <div>
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-amber-400">
                          #{idx + 1} Pembatalan Terbanyak
                        </span>
                        <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                          {item.cancellation_count} Void
                        </span>
                      </div>
                      <h4 className="text-base font-bold text-white mt-1.5">{item.cashier_name}</h4>
                      <p className="text-xs text-slate-400">Kasir Pelaksana</p>
                    </div>

                    <div className="mt-4 pt-3 border-t border-slate-800/80">
                      <div className="flex items-center justify-between">
                        <p className="text-[10px] text-slate-400">Total Nominal Dibatalkan</p>
                        <p className="text-sm font-bold text-amber-400">
                          {formatCurrency(item.total_amount)}
                        </p>
                      </div>
                      {item.reasons.length > 0 && (
                        <p className="text-[10px] text-slate-400 mt-1 truncate">
                          Alasan: {item.reasons.slice(0, 2).join(', ')}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Cancellation Log Table */}
          <div className="glass-card rounded-2xl border border-slate-800/80 overflow-hidden">
            <div className="p-5 border-b border-slate-800/80 flex items-center justify-between">
              <div>
                <h3 className="text-base font-bold text-white">Log Rincian Pembatalan Transaksi</h3>
                <p className="text-xs text-slate-400 mt-0.5">
                  Daftar riwayat nota transaksi yang dibatalkan oleh kasir beserta alasan pembatalan
                </p>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-slate-300">
                <thead className="bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                  <tr>
                    <th className="py-3.5 px-4">Tanggal</th>
                    <th className="py-3.5 px-4">No. Order / Nota</th>
                    <th className="py-3.5 px-4">Kasir Pelaksana</th>
                    <th className="py-3.5 px-4">Cabang</th>
                    <th className="py-3.5 px-4 text-right">Nominal Void</th>
                    <th className="py-3.5 px-4">Alasan Kesalahan / Batal</th>
                    <th className="py-3.5 px-4 text-center">Status Approval</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/50">
                  {cancelAudit.recent_logs.length === 0 ? (
                    <tr>
                      <td colSpan={7} className="py-8 text-center text-slate-500">
                        Tidak ada catatan pembatalan nota
                      </td>
                    </tr>
                  ) : (
                    cancelAudit.recent_logs.map((row) => (
                      <tr key={row.id} className="hover:bg-slate-800/40 transition-colors">
                        <td className="py-3.5 px-4 text-slate-400 whitespace-nowrap">
                          {formatDate(row.date)}
                        </td>
                        <td className="py-3.5 px-4 font-mono font-semibold text-white">
                          {row.order_number}
                        </td>
                        <td className="py-3.5 px-4 font-semibold text-white">
                          {row.cashier_name}
                        </td>
                        <td className="py-3.5 px-4 text-slate-400">{row.branch}</td>
                        <td className="py-3.5 px-4 text-right font-bold text-amber-400">
                          {formatCurrency(row.grand_total)}
                        </td>
                        <td className="py-3.5 px-4 text-slate-300 max-w-sm">
                          <span className="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 text-[11px]">
                            {row.reason}
                          </span>
                        </td>
                        <td className="py-3.5 px-4 text-center">
                          <span className="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700 uppercase">
                            {row.status}
                          </span>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
