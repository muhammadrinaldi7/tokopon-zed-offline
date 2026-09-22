import React from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from 'recharts';
import type { PaymentMethodMetric } from '../types';
import { formatCurrency } from '../utils/formatters';
import { CreditCard, Wallet } from 'lucide-react';

interface PaymentBreakdownProps {
  payments?: PaymentMethodMetric[] | null;
  isLoading?: boolean;
}

const COLORS = [
  '#6366f1', // Indigo
  '#10b981', // Emerald
  '#f59e0b', // Amber
  '#8b5cf6', // Purple
  '#ec4899', // Pink
  '#06b6d4', // Cyan
  '#64748b', // Slate
];

export const PaymentBreakdown: React.FC<PaymentBreakdownProps> = ({ payments, isLoading }) => {
  if (isLoading || !payments) {
    return (
      <div className="glass-card rounded-2xl p-6 border border-slate-800 animate-pulse h-96 flex flex-col justify-between">
        <div className="h-5 bg-slate-800 rounded w-44" />
        <div className="h-44 w-44 rounded-full bg-slate-800 mx-auto my-4" />
      </div>
    );
  }

  if (payments.length === 0) {
    return (
      <div className="glass-card rounded-2xl p-6 border border-slate-800 text-center py-12">
        <Wallet className="w-10 h-10 text-slate-600 mx-auto mb-2" />
        <h4 className="text-sm font-semibold text-slate-300">Tidak ada transaksi pembayaran</h4>
        <p className="text-xs text-slate-500 mt-1">
          Belum ada catatan pembayaran pada periode yang dipilih.
        </p>
      </div>
    );
  }

  const chartData = payments.map((pm) => ({
    name: pm.payment_method_name,
    value: pm.total_amount,
    count: pm.transactions_count,
    mdr: pm.total_mdr,
    share: pm.share_percentage,
  }));

  const totalPayments = payments.reduce((acc, curr) => acc + curr.total_amount, 0);
  const totalMdr = payments.reduce((acc, curr) => acc + curr.total_mdr, 0);

  return (
    <div className="glass-card rounded-2xl p-6 border border-slate-800/90">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h3 className="text-base font-bold text-white tracking-tight flex items-center gap-2">
            <CreditCard className="w-4 h-4 text-indigo-400" />
            Distribusi Metode Bayar & MDR
          </h3>
          <p className="text-xs text-slate-400 mt-1">
            Komposisi kas masuk, non-tunai, dan pemotongan biaya fee merchant
          </p>
        </div>
        <div className="text-right">
          <div className="text-xs text-slate-400">Total Potongan MDR</div>
          <div className="text-xs font-bold text-rose-400 tabular-nums">
            {formatCurrency(totalMdr)}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-center mt-2">
        {/* Donut Chart */}
        <div className="h-56 relative flex items-center justify-center">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie
                data={chartData}
                innerRadius={60}
                outerRadius={85}
                paddingAngle={3}
                dataKey="value"
              >
                {chartData.map((_, index) => (
                  <Cell
                    key={`cell-${index}`}
                    fill={COLORS[index % COLORS.length]}
                    stroke="#0f172a"
                    strokeWidth={2}
                  />
                ))}
              </Pie>
              <Tooltip
                content={({ active, payload }) => {
                  if (!active || !payload || !payload.length) return null;
                  const item = payload[0].payload;
                  return (
                    <div className="glass-panel p-2.5 rounded-xl shadow-xl text-xs border border-slate-700">
                      <div className="font-bold text-white mb-1">{item.name}</div>
                      <div className="text-indigo-300 font-bold tabular-nums">
                        {formatCurrency(item.value)} ({item.share}%)
                      </div>
                      <div className="text-slate-400 text-[10px] mt-0.5">
                        {item.count} transaksi • MDR: {formatCurrency(item.mdr)}
                      </div>
                    </div>
                  );
                }}
              />
            </PieChart>
          </ResponsiveContainer>

          {/* Center Summary */}
          <div className="absolute text-center pointer-events-none">
            <span className="text-[10px] uppercase font-semibold text-slate-400 block">
              Total Masuk
            </span>
            <span className="text-sm font-extrabold text-white tabular-nums">
              {formatCurrency(totalPayments)}
            </span>
          </div>
        </div>

        {/* Legend / Metrics List */}
        <div className="space-y-2 max-h-56 overflow-y-auto pr-1">
          {payments.map((pm, idx) => (
            <div
              key={pm.payment_method_id ?? idx}
              className="flex items-center justify-between p-2 rounded-lg bg-slate-900/60 border border-slate-800/80 text-xs"
            >
              <div className="flex items-center gap-2">
                <span
                  className="w-2.5 h-2.5 rounded-full shrink-0"
                  style={{ backgroundColor: COLORS[idx % COLORS.length] }}
                />
                <span className="font-semibold text-slate-200 truncate max-w-[130px]">
                  {pm.payment_method_name}
                </span>
              </div>

              <div className="text-right">
                <div className="font-bold text-white tabular-nums">
                  {formatCurrency(pm.total_amount)}
                </div>
                <div className="text-[10px] text-slate-400">
                  {pm.share_percentage}% • {pm.transactions_count} trs
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
