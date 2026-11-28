import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    TrendingUp,
    Users,
    CheckCircle2,
    Clock,
    XCircle,
    DollarSign,
    Award,
    Calendar,
    Filter,
    Store,
    ArrowUpRight,
    ShoppingBag,
    Percent,
    Crown,
    ChevronRight,
    MessageCircle,
    Target
} from 'lucide-react';

export default function ClosingAnalyticsIndex({
    metrics = {},
    csLeaderboard = [],
    recentDeals = [],
    funnelStages = [],
    pipelineMetrics = {},
    topLostReasons = [],
    filters = {}
}) {
    const { businessUnits } = usePage().props;
    const [selectedPeriod, setSelectedPeriod] = useState(filters?.period || 'this_month');
    const [selectedBu, setSelectedBu] = useState(filters?.bu || '');

    const handlePeriodChange = (p) => {
        setSelectedPeriod(p);
        router.get(
            '/crm/analytics',
            {
                period: p,
                bu: selectedBu,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleBuChange = (buId) => {
        setSelectedBu(buId);
        router.get(
            '/crm/analytics',
            {
                period: selectedPeriod,
                bu: buId,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const periodLabels = {
        today: 'Hari Ini',
        this_week: 'Minggu Ini',
        this_month: 'Bulan Ini',
        all: 'Semua Waktu',
    };

    return (
        <EcommerceLayout title="Analitik Closing & Performa CS">
            <Head title="Analitik Closing & Performa CS" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header Toolbar & Filters */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-emerald-500/10 text-emerald-600">
                                <TrendingUp className="w-6 h-6" />
                            </span>
                            Analitik Closing & Performa CS
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Pantau rasio konversi chat ke penjualan (*closing rate*) dan omset yang dihasilkan tim CS.
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5 flex-wrap">
                        {/* Period Pills */}
                        <div className="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200/80 shadow-2xs text-xs font-bold text-slate-600">
                            {['today', 'this_week', 'this_month', 'all'].map((pKey) => (
                                <button
                                    key={pKey}
                                    onClick={() => handlePeriodChange(pKey)}
                                    className={`px-3 py-1.5 rounded-lg transition-all ${
                                        selectedPeriod === pKey
                                            ? 'bg-emerald-600 text-white shadow-2xs'
                                            : 'hover:text-slate-900 hover:bg-slate-50'
                                    }`}
                                >
                                    {periodLabels[pKey]}
                                </button>
                            ))}
                        </div>

                        {/* Store / BU Selector */}
                        <select
                            value={selectedBu}
                            onChange={(e) => handleBuChange(e.target.value)}
                            className="bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2 shadow-2xs focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">Semua Store (Syihab & GSK)</option>
                            {businessUnits?.map((bu) => (
                                <option key={bu.id} value={bu.id}>
                                    {bu.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* 2. Hero KPI Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    {/* Total Omset Closing */}
                    <div className="bg-gradient-to-br from-emerald-600 to-teal-700 text-white p-5 rounded-2xl shadow-sm sm:col-span-2 relative overflow-hidden flex flex-col justify-between">
                        <div className="absolute right-0 top-0 translate-x-4 -translate-y-4 opacity-10 pointer-events-none">
                            <DollarSign className="w-40 h-40" />
                        </div>

                        <div>
                            <div className="text-xs font-extrabold uppercase tracking-wider text-emerald-100 flex items-center gap-1.5">
                                <span>⚡ Total Omset Closing CS</span>
                            </div>
                            <div className="text-3xl lg:text-4xl font-black mt-2 tracking-tight">
                                {metrics?.formatted_revenue || 'Rp 0'}
                            </div>
                        </div>

                        <div className="mt-4 pt-3 border-t border-emerald-500/40 flex items-center justify-between text-xs text-emerald-100">
                            <span>Rata-rata per Deal:</span>
                            <span className="font-extrabold text-white">
                                {metrics?.formatted_avg_deal_size || 'Rp 0'}
                            </span>
                        </div>
                    </div>

                    {/* Total Deals Closed */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Deals</span>
                            <span className="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                                <CheckCircle2 className="w-4 h-4" />
                            </span>
                        </div>
                        <div className="mt-3">
                            <div className="text-3xl font-black text-slate-900">{metrics?.total_deals ?? 0}</div>
                            <div className="text-xs text-emerald-600 font-bold mt-1 flex items-center gap-1">
                                <span>Berhasil Closing</span>
                            </div>
                        </div>
                    </div>

                    {/* Conversion Rate */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Closing Rate</span>
                            <span className="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                                <Target className="w-4 h-4" />
                            </span>
                        </div>
                        <div className="mt-3">
                            <div className="text-3xl font-black text-indigo-600">
                                {metrics?.conversion_rate ?? 0}%
                            </div>
                            <div className="text-xs text-slate-400 font-medium mt-1">
                                Dari {metrics?.total_chats ?? 0} total leads
                            </div>
                        </div>
                    </div>

                    {/* Antrean Follow-Up & Lost */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Follow Up / Lost</span>
                            <span className="p-2 rounded-xl bg-amber-50 text-amber-600">
                                <Clock className="w-4 h-4" />
                            </span>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-black text-slate-900">
                                {metrics?.total_follow_ups ?? 0}{' '}
                                <span className="text-xs font-medium text-slate-400">/ {metrics?.total_lost ?? 0} lost</span>
                            </div>
                            <div className="text-xs text-amber-600 font-bold mt-1">
                                Perlu tindak lanjut CS
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3. Conversion Pipeline Funnel Bar */}
                <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h3 className="text-sm font-extrabold text-slate-900 tracking-tight">
                                Sales Conversion Funnel ({periodLabels[selectedPeriod]})
                            </h3>
                        </div>
                        <span className="text-xs font-bold text-slate-500">
                            Efektivitas Closing: <span className="text-emerald-600">{metrics?.conversion_rate ?? 0}%</span>
                        </span>
                    </div>

                    {/* Stacked Funnel Bar */}
                    <div className="w-full bg-slate-100 h-3 rounded-full overflow-hidden flex">
                        <div
                            className="bg-emerald-500 h-full transition-all"
                            style={{
                                width: `${metrics?.total_chats > 0 ? (metrics?.total_deals / metrics?.total_chats) * 100 : 0}%`,
                            }}
                            title={`Deals: ${metrics?.total_deals}`}
                        />
                        <div
                            className="bg-amber-400 h-full transition-all"
                            style={{
                                width: `${metrics?.total_chats > 0 ? (metrics?.total_follow_ups / metrics?.total_chats) * 100 : 0}%`,
                            }}
                            title={`Follow Up: ${metrics?.total_follow_ups}`}
                        />
                        <div
                            className="bg-rose-400 h-full transition-all"
                            style={{
                                width: `${metrics?.total_chats > 0 ? (metrics?.total_lost / metrics?.total_chats) * 100 : 0}%`,
                            }}
                            title={`Lost: ${metrics?.total_lost}`}
                        />
                    </div>

                    {/* Legend */}
                    <div className="flex items-center justify-between text-xs text-slate-500 pt-1 flex-wrap gap-2">
                        <div className="flex items-center gap-1.5">
                            <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Deals Closed ({metrics?.total_deals ?? 0})</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                            <span className="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span>Follow Up Pending ({metrics?.total_follow_ups ?? 0})</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                            <span className="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span>Lost / Batal ({metrics?.total_lost ?? 0})</span>
                        </div>
                        <div className="flex items-center gap-1.5 text-slate-400 font-semibold">
                            <span>Total Leads: {metrics?.total_chats ?? 0}</span>
                        </div>
                    </div>
                </div>

                {/* 4. CS Leaderboard & Recent Deals Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* CS Leaderboard Table (2 Kolom) */}
                    <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <span className="p-1.5 rounded-lg bg-amber-50 text-amber-600">
                                    <Crown className="w-4 h-4" />
                                </span>
                                <h3 className="text-sm font-extrabold text-slate-900 tracking-tight">
                                    Leaderboard Performa Staf CS
                                </h3>
                            </div>
                            <span className="text-xs text-slate-400 font-medium">Diurutkan berdasarkan total omset</span>
                        </div>

                        {csLeaderboard.length === 0 ? (
                            <div className="p-8 text-center text-slate-400">
                                <Users className="w-8 h-8 mx-auto text-slate-300 mb-2" />
                                <p className="text-xs font-medium">Belum ada data closing CS pada periode ini.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr className="bg-slate-50/80 border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                            <th className="py-3 px-4 font-bold">Peringkat & Staf CS</th>
                                            <th className="py-3 px-3 font-bold text-center">Chat Handled</th>
                                            <th className="py-3 px-3 font-bold text-center">Deals</th>
                                            <th className="py-3 px-3 font-bold text-center">Closing Rate</th>
                                            <th className="py-3 px-4 font-bold text-right">Total Omset</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {csLeaderboard.map((cs) => {
                                            const medal =
                                                cs.rank === 1
                                                    ? '🥇'
                                                    : cs.rank === 2
                                                    ? '🥈'
                                                    : cs.rank === 3
                                                    ? '🥉'
                                                    : `#${cs.rank}`;

                                            return (
                                                <tr key={cs.user_id} className="hover:bg-slate-50/60 transition-colors">
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-3">
                                                            <span className="text-sm font-black w-6 text-center">
                                                                {medal}
                                                            </span>
                                                            <div className="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-extrabold flex items-center justify-center shrink-0 border border-slate-200">
                                                                {cs.agent_avatar ? (
                                                                    <img
                                                                        src={cs.agent_avatar}
                                                                        alt={cs.agent_name}
                                                                        className="w-full h-full rounded-full object-cover"
                                                                    />
                                                                ) : (
                                                                    cs.agent_name.slice(0, 1)
                                                                )}
                                                            </div>
                                                            <div>
                                                                <div className="font-bold text-slate-900">
                                                                    {cs.agent_name}
                                                                </div>
                                                                <div className="text-[10.5px] text-slate-400">
                                                                    {cs.agent_email || 'Staf CS'}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="py-3 px-3 text-center font-semibold text-slate-700">
                                                        {cs.total_handled}
                                                    </td>
                                                    <td className="py-3 px-3 text-center font-bold text-emerald-600">
                                                        {cs.deal_count}
                                                    </td>
                                                    <td className="py-3 px-3 text-center">
                                                        <span className="px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 text-indigo-600 border border-indigo-100">
                                                            {cs.rate}%
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4 text-right font-black text-slate-900">
                                                        {cs.formatted_omset}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {/* 15 Transaksi Closing Terakhir (1 Kolom) */}
                    <div className="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden flex flex-col">
                        <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <span className="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                                    <ShoppingBag className="w-4 h-4" />
                                </span>
                                <h3 className="text-sm font-extrabold text-slate-900 tracking-tight">
                                    Transaksi Closing Terbaru
                                </h3>
                            </div>
                            <span className="text-[11px] font-bold text-emerald-600">Live Feed</span>
                        </div>

                        <div className="p-3 divide-y divide-slate-100 overflow-y-auto max-h-[460px] flex-1">
                            {recentDeals.length === 0 ? (
                                <div className="p-8 text-center text-slate-400">
                                    <p className="text-xs font-medium">Belum ada transaksi closing terbaru.</p>
                                </div>
                            ) : (
                                recentDeals.map((deal) => (
                                    <div key={deal.id} className="py-3 first:pt-1 last:pb-1 text-xs space-y-1">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <div className="font-extrabold text-slate-900">
                                                    {deal.customer_name}
                                                </div>
                                                <div className="text-[10.5px] text-slate-400 font-mono">
                                                    {deal.customer_phone}
                                                </div>
                                            </div>
                                            <div className="font-black text-emerald-600 text-sm text-right">
                                                {deal.formatted_amount}
                                            </div>
                                        </div>

                                        {deal.product_name && (
                                            <div className="text-[11px] text-slate-600 font-medium truncate flex items-center gap-1">
                                                <span className="text-slate-400">📦 Unit:</span>
                                                <span>{deal.product_name}</span>
                                            </div>
                                        )}

                                        <div className="flex items-center justify-between text-[10.5px] text-slate-400 pt-0.5">
                                            <span>
                                                CS: <strong className="text-slate-600">{deal.closed_by_name}</strong>
                                            </span>
                                            <span>{deal.closed_at}</span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>

                {/* 4. Fase 3: Funnel Kualifikasi CRM & Analisis Alasan Batal */}
                {funnelStages.length > 0 && (
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        {/* Funnel Conversion Card (2 Kolom) */}
                        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-5 space-y-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div>
                                    <h3 className="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                        <Target className="w-4 h-4 text-indigo-600" />
                                        <span>Funnel Kualifikasi Customer (Lead → WON)</span>
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Potensi Pipeline Aktif: <strong className="text-indigo-700">{pipelineMetrics?.formatted_pipeline_value || 'Rp 0'}</strong>
                                        {' '}• Rata-rata Waktu Closing: <strong className="text-slate-800">{pipelineMetrics?.avg_hours_to_win || 0} jam</strong>
                                        {' '}• Overdue Follow-Up: <strong className="text-rose-600">{pipelineMetrics?.overdue_follow_ups || 0}</strong>
                                    </p>
                                </div>
                                <Link
                                    href="/crm/pipeline"
                                    className="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold inline-flex items-center gap-1 shrink-0"
                                >
                                    <span>Buka Papan Pipeline</span>
                                    <ChevronRight className="w-3.5 h-3.5" />
                                </Link>
                            </div>

                            <div className="space-y-3">
                                {funnelStages.map((stage) => {
                                    const barColor =
                                        stage.stageType === 'won'
                                            ? 'bg-emerald-500'
                                            : stage.stageType === 'lost'
                                            ? 'bg-rose-400'
                                            : 'bg-indigo-500';
                                    const widthPct = Math.max(stage.count > 0 ? 6 : 0, Math.min(100, stage.share_percent || 0));

                                    return (
                                        <div key={stage.id} className="space-y-1">
                                            <div className="flex items-center justify-between text-xs">
                                                <div className="font-bold text-slate-800 flex items-center gap-2">
                                                    <span>{stage.name}</span>
                                                    <span className="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                                                        {stage.count} customer ({stage.share_percent}%)
                                                    </span>
                                                </div>
                                                <span className="font-mono font-bold text-slate-700">
                                                    {stage.formatted_value}
                                                </span>
                                            </div>
                                            <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div
                                                    className={`h-full rounded-full transition-all duration-500 ${barColor}`}
                                                    style={{ width: `${widthPct}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Top Alasan Batal (Lost Reasons) */}
                        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-5 space-y-4">
                            <div className="border-b border-slate-100 pb-3">
                                <h3 className="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                    <XCircle className="w-4 h-4 text-rose-600" />
                                    <span>Top Alasan Batal (LOST)</span>
                                </h3>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    Total {pipelineMetrics?.lost_leads_count || 0} prospek gagal closing
                                </p>
                            </div>

                            {topLostReasons.length === 0 ? (
                                <div className="py-10 text-center text-xs text-slate-400">
                                    Belum ada data alasan batal pada periode ini.
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {topLostReasons.map((item, idx) => (
                                        <div key={idx} className="space-y-1">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="font-bold text-slate-700 truncate pr-2">
                                                    {item.reason}
                                                </span>
                                                <span className="font-extrabold text-rose-600 shrink-0">
                                                    {item.count} ({item.percent}%)
                                                </span>
                                            </div>
                                            <div className="w-full h-2 bg-rose-50 rounded-full overflow-hidden">
                                                <div
                                                    className="h-full bg-rose-500 rounded-full"
                                                    style={{ width: `${Math.max(8, item.percent)}%` }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </EcommerceLayout>
    );
}
