import React, { useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    Kanban,
    List,
    Plus,
    Search,
    RefreshCw,
    Download,
    SlidersHorizontal,
    Flame,
    Sun,
    Snowflake,
    Clock,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    MessageSquare,
    ExternalLink,
    Phone,
    User,
    Store,
    Package,
    DollarSign,
    Calendar,
    ArrowUp,
    ArrowDown,
    Trash2,
    X,
    Loader2,
    Edit3,
    Shield,
    Sparkles,
    Send,
    TrendingUp
} from 'lucide-react';

const COLOR_MAP = {
    slate: {
        bg: 'bg-slate-50',
        border: 'border-slate-200',
        header: 'bg-slate-100/90 text-slate-800',
        dot: 'bg-slate-500',
        badge: 'bg-slate-100 text-slate-700 border-slate-200',
    },
    sky: {
        bg: 'bg-sky-50/50',
        border: 'border-sky-200/80',
        header: 'bg-sky-100/80 text-sky-900',
        dot: 'bg-sky-500',
        badge: 'bg-sky-50 text-sky-700 border-sky-200',
    },
    indigo: {
        bg: 'bg-indigo-50/50',
        border: 'border-indigo-200/80',
        header: 'bg-indigo-100/80 text-indigo-900',
        dot: 'bg-indigo-500',
        badge: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    },
    amber: {
        bg: 'bg-amber-50/50',
        border: 'border-amber-200/80',
        header: 'bg-amber-100/80 text-amber-900',
        dot: 'bg-amber-500',
        badge: 'bg-amber-50 text-amber-800 border-amber-200',
    },
    violet: {
        bg: 'bg-violet-50/50',
        border: 'border-violet-200/80',
        header: 'bg-violet-100/80 text-violet-900',
        dot: 'bg-violet-500',
        badge: 'bg-violet-50 text-violet-700 border-violet-200',
    },
    emerald: {
        bg: 'bg-emerald-50/60',
        border: 'border-emerald-200',
        header: 'bg-emerald-100/90 text-emerald-900',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    },
    rose: {
        bg: 'bg-rose-50/50',
        border: 'border-rose-200/80',
        header: 'bg-rose-100/80 text-rose-900',
        dot: 'bg-rose-500',
        badge: 'bg-rose-50 text-rose-800 border-rose-200',
    },
    teal: {
        bg: 'bg-teal-50/50',
        border: 'border-teal-200/80',
        header: 'bg-teal-100/80 text-teal-900',
        dot: 'bg-teal-500',
        badge: 'bg-teal-50 text-teal-800 border-teal-200',
    },
    orange: {
        bg: 'bg-orange-50/50',
        border: 'border-orange-200/80',
        header: 'bg-orange-100/80 text-orange-900',
        dot: 'bg-orange-500',
        badge: 'bg-orange-50 text-orange-800 border-orange-200',
    },
};

const COLOR_OPTIONS = ['slate', 'sky', 'indigo', 'amber', 'violet', 'emerald', 'rose', 'teal', 'orange'];

const INTEREST_LABELS = {
    new: 'HP Baru',
    second: 'HP Second',
    trade_in: 'Trade-In',
    other: 'Lainnya',
};

const SOURCE_LABELS = {
    mobile_chat: 'Live Chat App',
    trade_in: 'Trade-In',
    whatsapp: 'WhatsApp',
    walk_in: 'Walk-in Toko',
    manual: 'Input Manual',
};

const formatRupiah = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID');

function TempBadge({ temp }) {
    if (temp === 'hot') {
        return (
            <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-rose-100 text-rose-700 border border-rose-200">
                <Flame className="w-3 h-3" /> HOT
            </span>
        );
    }
    if (temp === 'cold') {
        return (
            <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-sky-100 text-sky-700 border border-sky-200">
                <Snowflake className="w-3 h-3" /> COLD
            </span>
        );
    }
    return (
        <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">
            <Sun className="w-3 h-3" /> WARM
        </span>
    );
}

export default function PipelineIndex({
    stages: initialStages = [],
    allStages: initialAllStages = [],
    leads: initialLeads = [],
    summary: initialSummary = {},
    csAgents = [],
    lostReasons = [],
    permissions = {},
    filters: initialFilters = {},
}) {
    const { businessUnits } = usePage().props;
    const [viewMode, setViewMode] = useState('kanban'); // kanban | list
    const [stages, setStages] = useState(initialStages);
    const [allStages, setAllStages] = useState(initialAllStages);
    const [leads, setLeads] = useState(initialLeads);

    // Filter state
    const [search, setSearch] = useState(initialFilters?.search || '');
    const [filterBu, setFilterBu] = useState(initialFilters?.bu || '');
    const [filterAssignee, setFilterAssignee] = useState(initialFilters?.assignee || '');
    const [filterTemp, setFilterTemp] = useState(initialFilters?.temperature || '');
    const [filterInterest, setFilterInterest] = useState(initialFilters?.interest_type || '');
    const [filterFollowUp, setFilterFollowUp] = useState(initialFilters?.follow_up || 'all');
    const [filterPeriod, setFilterPeriod] = useState(initialFilters?.period || 'all');

    // Drag & drop state
    const [draggedLeadId, setDraggedLeadId] = useState(null);
    const [dragOverStageId, setDragOverStageId] = useState(null);

    // Modals & drawers
    const [isSyncing, setIsSyncing] = useState(false);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showStageModal, setShowStageModal] = useState(false);
    const [selectedLead, setSelectedLead] = useState(null);
    const [pendingTransition, setPendingTransition] = useState(null); // { lead, targetStage }
    const [toastMsg, setToastMsg] = useState(null);

    const notify = (msg) => {
        setToastMsg(msg);
        setTimeout(() => setToastMsg(null), 3500);
    };

    // Sync props when Inertia reloads
    React.useEffect(() => {
        setStages(initialStages);
        setAllStages(initialAllStages);
        setLeads(initialLeads);
    }, [initialStages, initialAllStages, initialLeads]);

    const applyServerFilters = (overrides = {}) => {
        const next = {
            search,
            bu: filterBu,
            assignee: filterAssignee,
            temperature: filterTemp,
            interest_type: filterInterest,
            follow_up: filterFollowUp,
            period: filterPeriod,
            ...overrides,
        };
        router.get('/crm/pipeline', next, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Hitung ulang KPI secara live saat kartu dipindah di UI
    const liveSummary = useMemo(() => {
        const open = leads.filter((l) => l.stageType === 'open');
        const won = leads.filter((l) => l.stageType === 'won');
        const lost = leads.filter((l) => l.stageType === 'lost');
        const pipelineVal = open.reduce((s, l) => s + Number(l.estimatedValue || 0), 0);
        const wonRev = won.reduce((s, l) => s + Number(l.wonAmount ?? l.estimatedValue ?? 0), 0);
        const overdue = open.filter((l) => l.isOverdue).length;
        const today = open.filter((l) => l.isFollowUpToday).length;
        const rate = leads.length > 0 ? ((won.length / leads.length) * 100).toFixed(1) : 0;
        return {
            total_leads: leads.length,
            open_count: open.length,
            won_count: won.length,
            lost_count: lost.length,
            pipeline_value: pipelineVal,
            won_revenue: wonRev,
            overdue_follow_ups: overdue,
            today_follow_ups: today,
            conversion_rate: rate,
        };
    }, [leads]);

    const leadsByStage = useMemo(() => {
        const map = {};
        stages.forEach((s) => {
            map[s.id] = [];
        });
        leads.forEach((l) => {
            if (!map[l.stageId]) {
                map[l.stageId] = [];
            }
            map[l.stageId].push(l);
        });
        return map;
    }, [stages, leads]);

    const updateLeadInList = (updatedLead) => {
        setLeads((prev) => prev.map((l) => (l.id === updatedLead.id ? updatedLead : l)));
        setSelectedLead((prev) => (prev && prev.id === updatedLead.id ? updatedLead : prev));
    };

    const executeStageMove = async (lead, targetStage, extraPayload = {}) => {
        // Optimistic update
        const previousLeads = leads;
        setLeads((prev) =>
            prev.map((l) =>
                l.id === lead.id
                    ? {
                          ...l,
                          stageId: targetStage.id,
                          stageName: targetStage.name,
                          stageColor: targetStage.color,
                          stageType: targetStage.stageType,
                      }
                    : l
            )
        );

        try {
            const res = await axios.post(`/crm/pipeline/leads/${lead.id}/stage`, {
                stage_id: targetStage.id,
                ...extraPayload,
            });
            if (res.data?.success && res.data.lead) {
                updateLeadInList(res.data.lead);
                notify(`"${lead.customerName}" dipindahkan ke ${targetStage.name}`);
            }
        } catch (err) {
            setLeads(previousLeads);
            alert('Gagal memindahkan tahap: ' + (err.response?.data?.message || err.message));
        }
    };

    const requestStageMove = (lead, targetStage) => {
        if (!lead || !targetStage || lead.stageId === targetStage.id) return;
        if (targetStage.stageType === 'won' || targetStage.stageType === 'lost') {
            setPendingTransition({ lead, targetStage });
        } else {
            executeStageMove(lead, targetStage);
        }
    };

    const handleDropOnStage = (e, targetStage) => {
        e.preventDefault();
        setDragOverStageId(null);
        if (!draggedLeadId) return;
        const lead = leads.find((l) => l.id === draggedLeadId);
        setDraggedLeadId(null);
        if (lead && lead.stageId !== targetStage.id) {
            requestStageMove(lead, targetStage);
        }
    };

    const handleSyncConversations = async () => {
        setIsSyncing(true);
        try {
            const res = await axios.post('/crm/pipeline/sync-conversations');
            notify(res.data?.message || 'Sinkronisasi selesai.');
            router.reload({ only: ['leads', 'summary'] });
        } catch (err) {
            alert('Gagal sinkronisasi: ' + (err.response?.data?.message || err.message));
        } finally {
            setIsSyncing(false);
        }
    };

    const exportUrl = useMemo(() => {
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (filterBu) params.set('bu', filterBu);
        if (filterAssignee) params.set('assignee', filterAssignee);
        if (filterTemp) params.set('temperature', filterTemp);
        if (filterInterest) params.set('interest_type', filterInterest);
        if (filterFollowUp && filterFollowUp !== 'all') params.set('follow_up', filterFollowUp);
        if (filterPeriod && filterPeriod !== 'all') params.set('period', filterPeriod);
        return `/crm/pipeline/export?${params.toString()}`;
    }, [search, filterBu, filterAssignee, filterTemp, filterInterest, filterFollowUp, filterPeriod]);

    return (
        <EcommerceLayout title="CRM Sales Pipeline (Kualifikasi sampai WON)">
            <Head title="CRM Sales Pipeline" />

            {/* Floating Notification Toast */}
            {toastMsg && (
                <div className="fixed bottom-5 right-5 z-50 bg-slate-900 text-white text-xs font-bold px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2 animate-in fade-in slide-in-from-bottom-2">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                    <span>{toastMsg}</span>
                </div>
            )}

            <div className="p-4 md:p-6 max-w-[1600px] mx-auto space-y-5">
                {/* 1. Header & Actions */}
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2.5 flex-wrap">
                            <span className="p-2 rounded-xl bg-indigo-600/10 text-indigo-600">
                                <Kanban className="w-6 h-6" />
                            </span>
                            <h1 className="text-2xl font-black text-slate-900 tracking-tight">
                                CRM Sales Pipeline
                            </h1>
                            <span
                                className={`inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full border ${
                                    permissions?.canViewAll
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-amber-50 text-amber-800 border-amber-200'
                                }`}
                                title="Diatur melalui hak akses Spatie: view-all-crm-leads"
                            >
                                <Shield className="w-3 h-3" />
                                {permissions?.canViewAll ? 'Akses: Semua CS' : 'Akses: Lead Saya & Antrean Bebas'}
                            </span>
                        </div>
                        <p className="text-xs md:text-sm text-slate-500 mt-1">
                            Kelompokkan kualifikasi customer dari prospek baru sampai <strong>WON</strong>. Tarik kartu antar kolom atau ubah label tahap kapan saja.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        {/* View Mode Switcher */}
                        <div className="flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-2xs">
                            <button
                                type="button"
                                onClick={() => setViewMode('kanban')}
                                className={`px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all ${
                                    viewMode === 'kanban'
                                        ? 'bg-slate-900 text-white'
                                        : 'text-slate-600 hover:bg-slate-100'
                                }`}
                            >
                                <Kanban className="w-3.5 h-3.5" /> Kanban
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('list')}
                                className={`px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all ${
                                    viewMode === 'list'
                                        ? 'bg-slate-900 text-white'
                                        : 'text-slate-600 hover:bg-slate-100'
                                }`}
                            >
                                <List className="w-3.5 h-3.5" /> Tabel
                            </button>
                        </div>

                        <button
                            type="button"
                            onClick={handleSyncConversations}
                            disabled={isSyncing}
                            className="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 shadow-2xs transition-all"
                            title="Sinkronkan percakapan Live Chat yang belum masuk papan pipeline"
                        >
                            <RefreshCw className={`w-3.5 h-3.5 ${isSyncing ? 'animate-spin text-indigo-600' : ''}`} />
                            <span>Sync Chat</span>
                        </button>

                        <a
                            href={exportUrl}
                            className="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 shadow-2xs transition-all"
                        >
                            <Download className="w-3.5 h-3.5" />
                            <span>Export CSV</span>
                        </a>

                        {permissions?.canManageStages && (
                            <button
                                type="button"
                                onClick={() => setShowStageModal(true)}
                                className="px-3 py-2 rounded-xl bg-white hover:bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold flex items-center gap-1.5 shadow-2xs transition-all"
                            >
                                <SlidersHorizontal className="w-3.5 h-3.5" />
                                <span>Kelola Tahap</span>
                            </button>
                        )}

                        <button
                            type="button"
                            onClick={() => setShowCreateModal(true)}
                            className="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all"
                        >
                            <Plus className="w-4 h-4" />
                            <span>Tambah Lead</span>
                        </button>
                    </div>
                </div>

                {/* 2. KPI Summary Cards */}
                <div className="grid grid-cols-2 lg:grid-cols-5 gap-3">
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
                        <div className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            Potensi Pipeline Aktif
                        </div>
                        <div className="text-xl font-black text-indigo-700 mt-1">
                            {formatRupiah(liveSummary.pipeline_value)}
                        </div>
                        <div className="text-[11px] text-slate-400 mt-0.5">
                            {liveSummary.open_count} prospek sedang berjalan
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-4 shadow-sm">
                        <div className="text-[11px] font-bold text-emerald-100 uppercase tracking-wider">
                            Total Omset WON
                        </div>
                        <div className="text-xl font-black mt-1">
                            {formatRupiah(liveSummary.won_revenue)}
                        </div>
                        <div className="text-[11px] text-emerald-100/90 mt-0.5">
                            {liveSummary.won_count} customer berhasil closing
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
                        <div className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            Rasio Konversi (WON)
                        </div>
                        <div className="text-xl font-black text-slate-900 mt-1">
                            {liveSummary.conversion_rate}%
                        </div>
                        <div className="text-[11px] text-slate-400 mt-0.5">
                            Dari total {liveSummary.total_leads} lead ({liveSummary.lost_count} lost)
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={() => {
                            const next = filterFollowUp === 'overdue' ? 'all' : 'overdue';
                            setFilterFollowUp(next);
                            applyServerFilters({ follow_up: next });
                        }}
                        className={`text-left rounded-2xl border p-4 shadow-2xs transition-all ${
                            filterFollowUp === 'overdue'
                                ? 'bg-rose-50 border-rose-400 ring-2 ring-rose-500/20'
                                : 'bg-white border-slate-200/80 hover:border-rose-300'
                        }`}
                    >
                        <div className="text-[11px] font-bold text-rose-600 uppercase tracking-wider flex items-center gap-1">
                            <AlertTriangle className="w-3.5 h-3.5" /> Follow-Up Terlambat
                        </div>
                        <div className="text-xl font-black text-rose-700 mt-1">
                            {liveSummary.overdue_follow_ups}
                        </div>
                        <div className="text-[11px] text-slate-400 mt-0.5">
                            Klik untuk filter prospek overdue
                        </div>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            const next = filterFollowUp === 'today' ? 'all' : 'today';
                            setFilterFollowUp(next);
                            applyServerFilters({ follow_up: next });
                        }}
                        className={`text-left rounded-2xl border p-4 shadow-2xs transition-all ${
                            filterFollowUp === 'today'
                                ? 'bg-amber-50 border-amber-400 ring-2 ring-amber-500/20'
                                : 'bg-white border-slate-200/80 hover:border-amber-300'
                        }`}
                    >
                        <div className="text-[11px] font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1">
                            <Clock className="w-3.5 h-3.5" /> Jadwal Hari Ini
                        </div>
                        <div className="text-xl font-black text-amber-700 mt-1">
                            {liveSummary.today_follow_ups}
                        </div>
                        <div className="text-[11px] text-slate-400 mt-0.5">
                            Klik untuk fokus follow-up hari ini
                        </div>
                    </button>
                </div>

                {/* 3. Filter Bar */}
                <div className="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs flex flex-wrap items-center gap-2.5">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyServerFilters({ search });
                        }}
                        className="relative flex-1 min-w-[220px]"
                    >
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama customer, no HP, produk, catatan... (Enter)"
                            className="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearch('');
                                    applyServerFilters({ search: '' });
                                }}
                                className="absolute right-2.5 top-2.5 text-slate-400 hover:text-rose-500"
                            >
                                <X className="w-3.5 h-3.5" />
                            </button>
                        )}
                    </form>

                    <select
                        value={filterBu}
                        onChange={(e) => {
                            setFilterBu(e.target.value);
                            applyServerFilters({ bu: e.target.value });
                        }}
                        className="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 outline-none"
                    >
                        <option value="">Semua Toko</option>
                        {businessUnits?.map((bu) => (
                            <option key={bu.id} value={bu.id}>
                                {bu.name}
                            </option>
                        ))}
                    </select>

                    {permissions?.canViewAll && (
                        <select
                            value={filterAssignee}
                            onChange={(e) => {
                                setFilterAssignee(e.target.value);
                                applyServerFilters({ assignee: e.target.value });
                            }}
                            className="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 outline-none"
                        >
                            <option value="">Semua Agen CS</option>
                            <option value="mine">Lead Saya</option>
                            <option value="unassigned">Belum Diambil CS</option>
                            {csAgents.map((agent) => (
                                <option key={agent.id} value={agent.id}>
                                    {agent.name}
                                </option>
                            ))}
                        </select>
                    )}

                    <select
                        value={filterTemp}
                        onChange={(e) => {
                            setFilterTemp(e.target.value);
                            applyServerFilters({ temperature: e.target.value });
                        }}
                        className="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 outline-none"
                    >
                        <option value="">Semua Suhu</option>
                        <option value="hot">🔥 Hot Lead</option>
                        <option value="warm">🌤️ Warm Lead</option>
                        <option value="cold">❄️ Cold Lead</option>
                    </select>

                    <select
                        value={filterInterest}
                        onChange={(e) => {
                            setFilterInterest(e.target.value);
                            applyServerFilters({ interest_type: e.target.value });
                        }}
                        className="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 outline-none"
                    >
                        <option value="">Semua Minat</option>
                        <option value="new">HP Baru</option>
                        <option value="second">HP Second</option>
                        <option value="trade_in">Trade-In</option>
                        <option value="other">Lainnya</option>
                    </select>

                    <select
                        value={filterPeriod}
                        onChange={(e) => {
                            setFilterPeriod(e.target.value);
                            applyServerFilters({ period: e.target.value });
                        }}
                        className="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 outline-none"
                    >
                        <option value="all">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                    </select>

                    <Link
                        href="/crm/analytics"
                        className="ml-auto text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 px-2 py-1"
                    >
                        <TrendingUp className="w-3.5 h-3.5" /> Lihat Funnel Analitik
                    </Link>
                </div>

                {/* 4. KANBAN VIEW */}
                {viewMode === 'kanban' ? (
                    <div className="flex gap-3.5 overflow-x-auto pb-4 items-start">
                        {stages.map((stage) => {
                            const palette = COLOR_MAP[stage.color] || COLOR_MAP.slate;
                            const colLeads = leadsByStage[stage.id] || [];
                            const colValue = colLeads.reduce(
                                (sum, item) =>
                                    sum +
                                    Number(
                                        stage.stageType === 'won'
                                            ? item.wonAmount ?? item.estimatedValue ?? 0
                                            : item.estimatedValue || 0
                                    ),
                                0
                            );
                            const isOver = dragOverStageId === stage.id;

                            return (
                                <div
                                    key={stage.id}
                                    onDragOver={(e) => {
                                        e.preventDefault();
                                        if (dragOverStageId !== stage.id) setDragOverStageId(stage.id);
                                    }}
                                    onDragLeave={() => {
                                        if (dragOverStageId === stage.id) setDragOverStageId(null);
                                    }}
                                    onDrop={(e) => handleDropOnStage(e, stage)}
                                    className={`w-[300px] shrink-0 rounded-2xl border transition-all flex flex-col max-h-[72vh] ${
                                        palette.bg
                                    } ${
                                        isOver
                                            ? 'border-indigo-500 ring-2 ring-indigo-500/30 scale-[1.01]'
                                            : palette.border
                                    }`}
                                >
                                    {/* Column Header */}
                                    <div className={`px-3.5 py-3 rounded-t-2xl border-b ${palette.border} ${palette.header}`}>
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className={`w-2.5 h-2.5 rounded-full shrink-0 ${palette.dot}`} />
                                                <h3 className="text-xs font-black truncate uppercase tracking-wide">
                                                    {stage.name}
                                                </h3>
                                            </div>
                                            <span className="px-2 py-0.5 rounded-full bg-white/80 text-slate-800 text-[11px] font-black shrink-0">
                                                {colLeads.length}
                                            </span>
                                        </div>
                                        <div className="text-[11px] font-bold opacity-80 mt-1 flex items-center justify-between">
                                            <span>{stage.stageType === 'won' ? 'Omset:' : 'Estimasi:'}</span>
                                            <span className="font-mono">{formatRupiah(colValue)}</span>
                                        </div>
                                    </div>

                                    {/* Column Cards */}
                                    <div className="p-2.5 space-y-2.5 overflow-y-auto flex-1">
                                        {colLeads.length === 0 ? (
                                            <div className="py-10 text-center text-[11px] text-slate-400 border-2 border-dashed border-slate-200/80 rounded-xl">
                                                Tarik kartu customer ke sini
                                            </div>
                                        ) : (
                                            colLeads.map((lead) => (
                                                <div
                                                    key={lead.id}
                                                    draggable
                                                    onDragStart={() => setDraggedLeadId(lead.id)}
                                                    onDragEnd={() => {
                                                        setDraggedLeadId(null);
                                                        setDragOverStageId(null);
                                                    }}
                                                    onClick={() => setSelectedLead(lead)}
                                                    className={`bg-white rounded-xl p-3 border shadow-2xs cursor-grab active:cursor-grabbing hover:border-indigo-300 hover:shadow-sm transition-all space-y-2 ${
                                                        lead.isOverdue
                                                            ? 'border-rose-300 ring-1 ring-rose-200'
                                                            : 'border-slate-200/90'
                                                    }`}
                                                >
                                                    {/* Top row: Name + Temperature */}
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="min-w-0">
                                                            <div className="text-xs font-extrabold text-slate-900 truncate">
                                                                {lead.customerName}
                                                            </div>
                                                            {lead.customerPhone && (
                                                                <div className="text-[11px] font-mono text-slate-500">
                                                                    {lead.customerPhone}
                                                                </div>
                                                            )}
                                                        </div>
                                                        <TempBadge temp={lead.temperature} />
                                                    </div>

                                                    {/* Tags: Interest + Source */}
                                                    <div className="flex items-center gap-1.5 flex-wrap">
                                                        <span className="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">
                                                            {INTEREST_LABELS[lead.interestType] || lead.interestType}
                                                        </span>
                                                        <span className="px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                                            {SOURCE_LABELS[lead.source] || lead.source}
                                                        </span>
                                                        {lead.orderNumber && (
                                                            <span className="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                                #{lead.orderNumber}
                                                            </span>
                                                        )}
                                                    </div>

                                                    {/* Product & Value */}
                                                    {lead.productName && (
                                                        <div className="text-[11px] text-slate-600 flex items-center gap-1 truncate">
                                                            <Package className="w-3 h-3 text-slate-400 shrink-0" />
                                                            <span className="truncate">{lead.productName}</span>
                                                        </div>
                                                    )}

                                                    <div className="flex items-center justify-between pt-1 border-t border-slate-100">
                                                        <span className="text-xs font-black font-mono text-slate-900">
                                                            {formatRupiah(
                                                                lead.stageType === 'won'
                                                                    ? lead.wonAmount ?? lead.estimatedValue
                                                                    : lead.estimatedValue
                                                            )}
                                                        </span>
                                                        <span className="text-[10px] font-bold text-slate-500 truncate max-w-[115px]">
                                                            {lead.assignedToName ? `👤 ${lead.assignedToName}` : '⏳ Bebas'}
                                                        </span>
                                                    </div>

                                                    {/* Follow-Up Reminder or Lost Reason */}
                                                    {lead.stageType === 'lost' && lead.lostReason && (
                                                        <div className="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-1 rounded-lg border border-rose-200/80">
                                                            Alasan: {lead.lostReason}
                                                        </div>
                                                    )}

                                                    {lead.stageType === 'open' && lead.nextFollowUpLabel && (
                                                        <div
                                                            className={`text-[10px] font-bold px-2 py-1 rounded-lg flex items-center gap-1 ${
                                                                lead.isOverdue
                                                                    ? 'bg-rose-100 text-rose-800'
                                                                    : lead.isFollowUpToday
                                                                    ? 'bg-amber-100 text-amber-800'
                                                                    : 'bg-slate-100 text-slate-600'
                                                            }`}
                                                        >
                                                            <Calendar className="w-3 h-3 shrink-0" />
                                                            <span className="truncate">
                                                                {lead.isOverdue ? 'Terlambat: ' : 'FU: '}
                                                                {lead.nextFollowUpLabel}
                                                            </span>
                                                        </div>
                                                    )}

                                                    {/* Quick action links */}
                                                    <div
                                                        className="flex items-center justify-between gap-1.5 pt-1"
                                                        onClick={(e) => e.stopPropagation()}
                                                    >
                                                        <div className="flex items-center gap-1">
                                                            {lead.conversationId && (
                                                                <Link
                                                                    href={`/crm/chat?active=${lead.conversationId}`}
                                                                    className="px-2 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center gap-1"
                                                                >
                                                                    <MessageSquare className="w-3 h-3" /> Chat
                                                                </Link>
                                                            )}
                                                            {lead.waUrl && (
                                                                <a
                                                                    href={lead.waUrl}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[10px] font-bold flex items-center gap-1"
                                                                >
                                                                    <ExternalLink className="w-3 h-3" /> WA
                                                                </a>
                                                            )}
                                                        </div>

                                                        <select
                                                            value={lead.stageId}
                                                            onChange={(e) => {
                                                                const target = stages.find(
                                                                    (s) => String(s.id) === String(e.target.value)
                                                                );
                                                                if (target) requestStageMove(lead, target);
                                                            }}
                                                            className="text-[10px] font-bold bg-slate-100 border border-slate-200 rounded-lg px-1.5 py-1 text-slate-700 max-w-[115px] outline-none"
                                                        >
                                                            {stages.map((s) => (
                                                                <option key={s.id} value={s.id}>
                                                                    {s.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    /* 5. LIST / TABLE VIEW */
                    <div className="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-slate-50 border-b border-slate-200 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                    <th className="px-4 py-3">Customer</th>
                                    <th className="px-4 py-3">Tahap Kualifikasi</th>
                                    <th className="px-4 py-3">Suhu / Minat</th>
                                    <th className="px-4 py-3">Produk & Nilai</th>
                                    <th className="px-4 py-3">CS Handle</th>
                                    <th className="px-4 py-3">Jadwal Follow-Up</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-xs">
                                {leads.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-12 text-center text-slate-400">
                                            Belum ada data lead sesuai filter saat ini.
                                        </td>
                                    </tr>
                                ) : (
                                    leads.map((lead) => {
                                        const palette = COLOR_MAP[lead.stageColor] || COLOR_MAP.slate;
                                        return (
                                            <tr
                                                key={lead.id}
                                                className="hover:bg-slate-50/80 transition-colors cursor-pointer"
                                                onClick={() => setSelectedLead(lead)}
                                            >
                                                <td className="px-4 py-3">
                                                    <div className="font-extrabold text-slate-900">{lead.customerName}</div>
                                                    <div className="text-[11px] text-slate-500 font-mono">
                                                        {lead.customerPhone || '-'} • {lead.businessUnitName}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                                                    <select
                                                        value={lead.stageId}
                                                        onChange={(e) => {
                                                            const target = stages.find(
                                                                (s) => String(s.id) === String(e.target.value)
                                                            );
                                                            if (target) requestStageMove(lead, target);
                                                        }}
                                                        className={`text-xs font-bold px-2.5 py-1 rounded-lg border outline-none ${palette.badge}`}
                                                    >
                                                        {stages.map((s) => (
                                                            <option key={s.id} value={s.id}>
                                                                {s.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-1.5">
                                                        <TempBadge temp={lead.temperature} />
                                                        <span className="text-[11px] font-semibold text-slate-600">
                                                            {INTEREST_LABELS[lead.interestType] || lead.interestType}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="font-bold font-mono text-slate-900">
                                                        {formatRupiah(
                                                            lead.stageType === 'won'
                                                                ? lead.wonAmount ?? lead.estimatedValue
                                                                : lead.estimatedValue
                                                        )}
                                                    </div>
                                                    {lead.productName && (
                                                        <div className="text-[11px] text-slate-500 truncate max-w-[200px]">
                                                            {lead.productName}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-slate-700 font-semibold">
                                                    {lead.assignedToName || (
                                                        <span className="text-amber-600">Belum Diambil</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {lead.nextFollowUpLabel ? (
                                                        <span
                                                            className={`px-2 py-0.5 rounded-md text-[11px] font-bold ${
                                                                lead.isOverdue
                                                                    ? 'bg-rose-100 text-rose-700'
                                                                    : 'bg-slate-100 text-slate-700'
                                                            }`}
                                                        >
                                                            {lead.nextFollowUpLabel}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400">-</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right" onClick={(e) => e.stopPropagation()}>
                                                    <div className="inline-flex items-center gap-1.5">
                                                        {lead.conversationId && (
                                                            <Link
                                                                href={`/crm/chat?active=${lead.conversationId}`}
                                                                className="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-[11px]"
                                                            >
                                                                Chat
                                                            </Link>
                                                        )}
                                                        {lead.waUrl && (
                                                            <a
                                                                href={lead.waUrl}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-[11px]"
                                                            >
                                                                WA
                                                            </a>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* MODAL 1: KONFIRMASI WON / LOST */}
            {pendingTransition && (
                <TransitionConfirmModal
                    lead={pendingTransition.lead}
                    targetStage={pendingTransition.targetStage}
                    lostReasons={lostReasons}
                    onClose={() => setPendingTransition(null)}
                    onConfirm={async (payload) => {
                        await executeStageMove(pendingTransition.lead, pendingTransition.targetStage, payload);
                        setPendingTransition(null);
                    }}
                />
            )}

            {/* MODAL 2: TAMBAH LEAD MANUAL */}
            {showCreateModal && (
                <CreateLeadModal
                    stages={stages}
                    businessUnits={businessUnits}
                    csAgents={csAgents}
                    canViewAll={permissions?.canViewAll}
                    onClose={() => setShowCreateModal(false)}
                    onCreated={(newLead) => {
                        setLeads((prev) => [newLead, ...prev]);
                        setShowCreateModal(false);
                        notify(`Lead "${newLead.customerName}" berhasil ditambahkan.`);
                    }}
                />
            )}

            {/* MODAL 3: KELOLA TAHAP DINAMIS (EDIT LABEL / WARNA / URUTAN) */}
            {showStageModal && permissions?.canManageStages && (
                <StageManagerModal
                    initialStages={allStages}
                    onClose={() => setShowStageModal(false)}
                    onChanged={() => {
                        router.reload({ only: ['stages', 'allStages', 'leads', 'summary'] });
                        notify('Konfigurasi tahap kualifikasi diperbarui.');
                    }}
                />
            )}

            {/* DRAWER: DETAIL LEAD & TIMELINE AKTIVITAS */}
            {selectedLead && (
                <LeadDetailDrawer
                    lead={selectedLead}
                    stages={stages}
                    businessUnits={businessUnits}
                    csAgents={csAgents}
                    canViewAll={permissions?.canViewAll}
                    onClose={() => setSelectedLead(null)}
                    onUpdated={(updated) => {
                        updateLeadInList(updated);
                        notify('Perubahan lead disimpan.');
                    }}
                    onRequestStageMove={(stage) => requestStageMove(selectedLead, stage)}
                    onDeleted={(deletedId) => {
                        setLeads((prev) => prev.filter((l) => l.id !== deletedId));
                        setSelectedLead(null);
                        notify('Lead berhasil dihapus.');
                    }}
                />
            )}
        </EcommerceLayout>
    );
}

/* ─────────────────── SUB-COMPONENTS ─────────────────── */

function TransitionConfirmModal({ lead, targetStage, lostReasons, onClose, onConfirm }) {
    const isWon = targetStage.stageType === 'won';
    const [wonAmount, setWonAmount] = useState(String(lead.wonAmount ?? lead.estimatedValue ?? ''));
    const [lostReason, setLostReason] = useState(lead.lostReason || lostReasons[0] || 'Lainnya');
    const [notes, setNotes] = useState(lead.notes || '');
    const [saving, setSaving] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            await onConfirm(
                isWon
                    ? { won_amount: Number(String(wonAmount).replace(/[^0-9]/g, '')) || 0, notes }
                    : { lost_reason: lostReason, notes }
            );
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={onClose} />
            <form
                onSubmit={submit}
                className="relative z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-4"
            >
                <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 className="text-base font-black text-slate-900 flex items-center gap-2">
                            {isWon ? (
                                <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                            ) : (
                                <XCircle className="w-5 h-5 text-rose-600" />
                            )}
                            <span>Pindahkan ke {targetStage.name}</span>
                        </h3>
                        <p className="text-xs text-slate-500 mt-0.5">{lead.customerName}</p>
                    </div>
                    <button type="button" onClick={onClose} className="p-1.5 rounded-full hover:bg-slate-100">
                        <X className="w-4 h-4 text-slate-500" />
                    </button>
                </div>

                {isWon ? (
                    <div>
                        <label className="block text-[11px] font-extrabold text-slate-700 uppercase mb-1">
                            Nominal Closing / Omset Final (Rp)
                        </label>
                        <input
                            type="number"
                            value={wonAmount}
                            onChange={(e) => setWonAmount(e.target.value)}
                            required
                            min={0}
                            placeholder="Contoh: 12500000"
                            className="w-full px-3 py-2.5 text-sm font-mono font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-emerald-500"
                        />
                    </div>
                ) : (
                    <div>
                        <label className="block text-[11px] font-extrabold text-slate-700 uppercase mb-1">
                            Alasan Batal (Lost Reason)
                        </label>
                        <select
                            value={lostReason}
                            onChange={(e) => setLostReason(e.target.value)}
                            className="w-full px-3 py-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-rose-500"
                        >
                            {lostReasons.map((r) => (
                                <option key={r} value={r}>
                                    {r}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                <div>
                    <label className="block text-[11px] font-extrabold text-slate-700 uppercase mb-1">
                        Catatan Tambahan
                    </label>
                    <textarea
                        rows={3}
                        value={notes}
                        onChange={(e) => setNotes(e.target.value)}
                        placeholder={isWon ? 'Nomor invoice / detail unit terjual...' : 'Detail kendala atau masukan customer...'}
                        className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-indigo-500"
                    />
                </div>

                <div className="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={saving}
                        className={`px-4 py-2 rounded-xl text-xs font-bold text-white flex items-center gap-1.5 ${
                            isWon ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'
                        }`}
                    >
                        {saving && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                        <span>Simpan Status {targetStage.name}</span>
                    </button>
                </div>
            </form>
        </div>
    );
}

function CreateLeadModal({ stages, businessUnits, csAgents, canViewAll, onClose, onCreated }) {
    const defaultStage = stages.find((s) => s.isDefault) || stages[0];
    const [form, setForm] = useState({
        customer_name: '',
        customer_phone: '',
        stage_id: defaultStage?.id || '',
        business_unit_id: '',
        assigned_to_user_id: '',
        temperature: 'warm',
        interest_type: 'new',
        source: 'whatsapp',
        estimated_value: '',
        next_follow_up_at: '',
        notes: '',
    });
    const [saving, setSaving] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            const res = await axios.post('/crm/pipeline/leads', {
                ...form,
                estimated_value: form.estimated_value ? Number(form.estimated_value) : 0,
                business_unit_id: form.business_unit_id || null,
                assigned_to_user_id: form.assigned_to_user_id || null,
                next_follow_up_at: form.next_follow_up_at || null,
            });
            if (res.data?.success && res.data.lead) {
                onCreated(res.data.lead);
            }
        } catch (err) {
            alert('Gagal menambah lead: ' + (err.response?.data?.message || err.message));
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={onClose} />
            <form
                onSubmit={submit}
                className="relative z-10 w-full max-w-lg bg-white rounded-3xl shadow-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto"
            >
                <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 className="text-base font-black text-slate-900">Tambah Lead Customer Manual</h3>
                        <p className="text-xs text-slate-500">Catat prospek dari WhatsApp, Walk-in, atau Instagram</p>
                    </div>
                    <button type="button" onClick={onClose} className="p-1.5 rounded-full hover:bg-slate-100">
                        <X className="w-4 h-4 text-slate-500" />
                    </button>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Nama Customer *</label>
                        <input
                            type="text"
                            required
                            value={form.customer_name}
                            onChange={(e) => setForm({ ...form, customer_name: e.target.value })}
                            placeholder="Budi Santoso"
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Nomor HP / WA</label>
                        <input
                            type="text"
                            value={form.customer_phone}
                            onChange={(e) => setForm({ ...form, customer_phone: e.target.value })}
                            placeholder="0812xxxxxxxx"
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Tahap Awal</label>
                        <select
                            value={form.stage_id}
                            onChange={(e) => setForm({ ...form, stage_id: e.target.value })}
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                        >
                            {stages.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Estimasi Nilai (Rp)</label>
                        <input
                            type="number"
                            min={0}
                            value={form.estimated_value}
                            onChange={(e) => setForm({ ...form, estimated_value: e.target.value })}
                            placeholder="10000000"
                            className="w-full px-3 py-2 text-xs font-mono bg-slate-50 border border-slate-200 rounded-xl outline-none"
                        />
                    </div>

                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Suhu Prospek</label>
                        <select
                            value={form.temperature}
                            onChange={(e) => setForm({ ...form, temperature: e.target.value })}
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                        >
                            <option value="hot">🔥 Hot</option>
                            <option value="warm">🌤️ Warm</option>
                            <option value="cold">❄️ Cold</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Kategori Minat</label>
                        <select
                            value={form.interest_type}
                            onChange={(e) => setForm({ ...form, interest_type: e.target.value })}
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                        >
                            <option value="new">HP Baru</option>
                            <option value="second">HP Second</option>
                            <option value="trade_in">Trade-In</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Sumber Lead</label>
                        <select
                            value={form.source}
                            onChange={(e) => setForm({ ...form, source: e.target.value })}
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                        >
                            <option value="whatsapp">WhatsApp</option>
                            <option value="walk_in">Walk-in Toko</option>
                            <option value="trade_in">Trade-In</option>
                            <option value="mobile_chat">Live Chat App</option>
                            <option value="manual">Input Manual</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Toko / Unit Bisnis</label>
                        <select
                            value={form.business_unit_id}
                            onChange={(e) => setForm({ ...form, business_unit_id: e.target.value })}
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                        >
                            <option value="">Default Toko</option>
                            {businessUnits?.map((bu) => (
                                <option key={bu.id} value={bu.id}>
                                    {bu.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    {canViewAll && (
                        <div>
                            <label className="block text-[11px] font-bold text-slate-600 mb-1">Tugaskan ke CS</label>
                            <select
                                value={form.assigned_to_user_id}
                                onChange={(e) => setForm({ ...form, assigned_to_user_id: e.target.value })}
                                className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                            >
                                <option value="">Otomatis (Saya)</option>
                                {csAgents.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Jadwal Follow-Up</label>
                        <input
                            type="datetime-local"
                            value={form.next_follow_up_at}
                            onChange={(e) => setForm({ ...form, next_follow_up_at: e.target.value })}
                            className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                        />
                    </div>
                </div>

                <div>
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Catatan Kebutuhan</label>
                    <textarea
                        rows={3}
                        value={form.notes}
                        onChange={(e) => setForm({ ...form, notes: e.target.value })}
                        placeholder="Misal: Cari iPhone 15 Pro 256GB Natural Titanium, mau tukar tambah iPhone 13..."
                        className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none"
                    />
                </div>

                <div className="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={saving}
                        className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center gap-1.5"
                    >
                        {saving && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                        <span>Simpan Lead</span>
                    </button>
                </div>
            </form>
        </div>
    );
}

function StageManagerModal({ initialStages, onClose, onChanged }) {
    const [stageList, setStageList] = useState(initialStages);
    const [newName, setNewName] = useState('');
    const [newColor, setNewColor] = useState('teal');
    const [newType, setNewType] = useState('open');
    const [busyId, setBusyId] = useState(null);

    const handleRenameOrUpdate = async (stage, patch) => {
        setBusyId(stage.id);
        try {
            const res = await axios.put(`/crm/pipeline/stages/${stage.id}`, patch);
            if (res.data?.success && res.data.stage) {
                setStageList((prev) => prev.map((s) => (s.id === stage.id ? res.data.stage : s)));
                onChanged();
            }
        } catch (err) {
            alert(err.response?.data?.message || 'Gagal memperbarui tahap.');
        } finally {
            setBusyId(null);
        }
    };

    const handleAddStage = async (e) => {
        e.preventDefault();
        if (!newName.trim()) return;
        setBusyId('new');
        try {
            const res = await axios.post('/crm/pipeline/stages', {
                name: newName.trim(),
                color: newColor,
                stage_type: newType,
            });
            if (res.data?.success && res.data.stage) {
                setStageList((prev) => [...prev, res.data.stage]);
                setNewName('');
                onChanged();
            }
        } catch (err) {
            alert(err.response?.data?.message || 'Gagal menambah tahap.');
        } finally {
            setBusyId(null);
        }
    };

    const handleMoveOrder = async (index, direction) => {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= stageList.length) return;
        const copy = [...stageList];
        const [moved] = copy.splice(index, 1);
        copy.splice(targetIndex, 0, moved);
        setStageList(copy);
        try {
            await axios.post('/crm/pipeline/stages/reorder', {
                stage_ids: copy.map((s) => s.id),
            });
            onChanged();
        } catch (err) {
            alert('Gagal mengubah urutan tahap.');
        }
    };

    const handleDeleteStage = async (stage) => {
        if (!confirm(`Hapus tahap "${stage.name}"? Lead yang ada di tahap ini akan otomatis dipindahkan ke tahap lain.`)) {
            return;
        }
        setBusyId(stage.id);
        try {
            await axios.delete(`/crm/pipeline/stages/${stage.id}`);
            setStageList((prev) => prev.filter((s) => s.id !== stage.id));
            onChanged();
        } catch (err) {
            alert(err.response?.data?.message || 'Tidak dapat menghapus tahap ini.');
        } finally {
            setBusyId(null);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={onClose} />
            <div className="relative z-10 w-full max-w-2xl bg-white rounded-3xl shadow-2xl p-6 space-y-5 max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 className="text-base font-black text-slate-900">
                            Kelola Tahapan Kualifikasi (Dinamis)
                        </h3>
                        <p className="text-xs text-slate-500">
                            Ubah nama/label, warna, urutan, atau tambah tahap baru kapan saja tanpa merusak data customer.
                        </p>
                    </div>
                    <button type="button" onClick={onClose} className="p-1.5 rounded-full hover:bg-slate-100">
                        <X className="w-4 h-4 text-slate-500" />
                    </button>
                </div>

                {/* Existing Stages */}
                <div className="space-y-2">
                    {stageList.map((st, idx) => (
                        <div
                            key={st.id}
                            className="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                        >
                            <div className="flex flex-col gap-0.5">
                                <button
                                    type="button"
                                    disabled={idx === 0}
                                    onClick={() => handleMoveOrder(idx, -1)}
                                    className="p-0.5 rounded hover:bg-slate-200 disabled:opacity-30"
                                >
                                    <ArrowUp className="w-3 h-3" />
                                </button>
                                <button
                                    type="button"
                                    disabled={idx === stageList.length - 1}
                                    onClick={() => handleMoveOrder(idx, 1)}
                                    className="p-0.5 rounded hover:bg-slate-200 disabled:opacity-30"
                                >
                                    <ArrowDown className="w-3 h-3" />
                                </button>
                            </div>

                            <input
                                type="text"
                                defaultValue={st.name}
                                onBlur={(e) => {
                                    const v = e.target.value.trim();
                                    if (v && v !== st.name) {
                                        handleRenameOrUpdate(st, { name: v });
                                    }
                                }}
                                className="flex-1 px-2.5 py-1.5 text-xs font-bold bg-white border border-slate-200 rounded-lg outline-none focus:border-indigo-500"
                            />

                            <select
                                value={st.color}
                                onChange={(e) => handleRenameOrUpdate(st, { color: e.target.value })}
                                className="px-2 py-1.5 text-xs font-semibold bg-white border border-slate-200 rounded-lg outline-none"
                            >
                                {COLOR_OPTIONS.map((c) => (
                                    <option key={c} value={c}>
                                        {c}
                                    </option>
                                ))}
                            </select>

                            <span
                                className={`px-2 py-1 rounded-lg text-[10px] font-black uppercase ${
                                    st.stageType === 'won'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : st.stageType === 'lost'
                                        ? 'bg-rose-100 text-rose-800'
                                        : 'bg-slate-200 text-slate-700'
                                }`}
                            >
                                {st.stageType}
                            </span>

                            <button
                                type="button"
                                onClick={() => handleRenameOrUpdate(st, { is_active: !st.isActive })}
                                className={`px-2 py-1 rounded-lg text-[10px] font-bold border ${
                                    st.isActive
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-slate-100 text-slate-400 border-slate-200'
                                }`}
                            >
                                {st.isActive ? 'Aktif' : 'Nonaktif'}
                            </button>

                            <button
                                type="button"
                                disabled={busyId === st.id}
                                onClick={() => handleDeleteStage(st)}
                                className="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50"
                                title="Hapus tahap"
                            >
                                <Trash2 className="w-3.5 h-3.5" />
                            </button>
                        </div>
                    ))}
                </div>

                {/* Add new stage form */}
                <form
                    onSubmit={handleAddStage}
                    className="p-3.5 rounded-2xl bg-indigo-50/60 border border-indigo-200/80 space-y-2.5"
                >
                    <div className="text-xs font-extrabold text-indigo-900">+ Tambah Tahap Kualifikasi Baru</div>
                    <div className="flex flex-wrap gap-2">
                        <input
                            type="text"
                            required
                            value={newName}
                            onChange={(e) => setNewName(e.target.value)}
                            placeholder="Misal: Cek Fisik di Toko / Pengajuan Cicilan"
                            className="flex-1 min-w-[200px] px-3 py-2 text-xs bg-white border border-indigo-200 rounded-xl outline-none"
                        />
                        <select
                            value={newColor}
                            onChange={(e) => setNewColor(e.target.value)}
                            className="px-2.5 py-2 text-xs bg-white border border-indigo-200 rounded-xl outline-none"
                        >
                            {COLOR_OPTIONS.map((c) => (
                                <option key={c} value={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                        <select
                            value={newType}
                            onChange={(e) => setNewType(e.target.value)}
                            className="px-2.5 py-2 text-xs bg-white border border-indigo-200 rounded-xl outline-none"
                        >
                            <option value="open">Tipe: Open (Proses)</option>
                            <option value="won">Tipe: WON (Deal)</option>
                            <option value="lost">Tipe: LOST (Batal)</option>
                        </select>
                        <button
                            type="submit"
                            disabled={busyId === 'new'}
                            className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold"
                        >
                            Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function LeadDetailDrawer({
    lead,
    stages,
    businessUnits,
    csAgents,
    canViewAll,
    onClose,
    onUpdated,
    onRequestStageMove,
    onDeleted,
}) {
    const [form, setForm] = useState({
        customer_name: lead.customerName || '',
        customer_phone: lead.customerPhone || '',
        temperature: lead.temperature || 'warm',
        interest_type: lead.interestType || 'new',
        estimated_value: String(lead.estimatedValue || 0),
        business_unit_id: lead.businessUnitId || '',
        assigned_to_user_id: lead.assignedToId || '',
        next_follow_up_at: lead.nextFollowUpAt || '',
        notes: lead.notes || '',
    });
    const [saving, setSaving] = useState(false);
    const [activityText, setActivityText] = useState('');
    const [postingNote, setPostingNote] = useState(false);

    React.useEffect(() => {
        setForm({
            customer_name: lead.customerName || '',
            customer_phone: lead.customerPhone || '',
            temperature: lead.temperature || 'warm',
            interest_type: lead.interestType || 'new',
            estimated_value: String(lead.estimatedValue || 0),
            business_unit_id: lead.businessUnitId || '',
            assigned_to_user_id: lead.assignedToId || '',
            next_follow_up_at: lead.nextFollowUpAt || '',
            notes: lead.notes || '',
        });
    }, [lead.id, lead.updatedAtHuman]);

    const handleSave = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            const res = await axios.put(`/crm/pipeline/leads/${lead.id}`, {
                ...form,
                estimated_value: Number(form.estimated_value) || 0,
                business_unit_id: form.business_unit_id || null,
                assigned_to_user_id: form.assigned_to_user_id || null,
                next_follow_up_at: form.next_follow_up_at || null,
            });
            if (res.data?.success && res.data.lead) {
                onUpdated(res.data.lead);
            }
        } catch (err) {
            alert(err.response?.data?.message || 'Gagal menyimpan lead.');
        } finally {
            setSaving(false);
        }
    };

    const handleAddActivity = async (e) => {
        e.preventDefault();
        if (!activityText.trim()) return;
        setPostingNote(true);
        try {
            const res = await axios.post(`/crm/pipeline/leads/${lead.id}/activities`, {
                description: activityText.trim(),
                activity_type: 'follow_up',
            });
            if (res.data?.success && res.data.activity) {
                setActivityText('');
                onUpdated({
                    ...lead,
                    activities: [res.data.activity, ...(lead.activities || [])],
                });
            }
        } catch (err) {
            alert('Gagal menambah log aktivitas.');
        } finally {
            setPostingNote(false);
        }
    };

    const handleDelete = async () => {
        if (!confirm(`Hapus kartu lead "${lead.customerName}" dari pipeline?`)) return;
        try {
            await axios.delete(`/crm/pipeline/leads/${lead.id}`);
            onDeleted(lead.id);
        } catch (err) {
            alert(err.response?.data?.message || 'Gagal menghapus lead.');
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex justify-end">
            <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-2xs" onClick={onClose} />
            <div className="relative z-10 w-full max-w-lg bg-white h-full shadow-2xl flex flex-col">
                {/* Drawer Header */}
                <div className="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div>
                        <h3 className="text-sm font-black text-slate-900">{lead.customerName}</h3>
                        <p className="text-[11px] text-slate-500">
                            Lead #{lead.id} • Dibuat {lead.createdAt}
                        </p>
                    </div>
                    <div className="flex items-center gap-1.5">
                        {lead.conversationId && (
                            <Link
                                href={`/crm/chat?active=${lead.conversationId}`}
                                className="px-2.5 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-bold flex items-center gap-1"
                            >
                                <MessageSquare className="w-3.5 h-3.5" /> Buka Chat
                            </Link>
                        )}
                        <button type="button" onClick={onClose} className="p-1.5 rounded-full hover:bg-slate-200">
                            <X className="w-4 h-4 text-slate-600" />
                        </button>
                    </div>
                </div>

                {/* Drawer Body */}
                <div className="flex-1 overflow-y-auto p-4 space-y-5">
                    {/* Stage Quick Stepper */}
                    <div className="space-y-1.5">
                        <label className="text-[11px] font-extrabold text-slate-500 uppercase">
                            Tahap Kualifikasi Saat Ini
                        </label>
                        <div className="flex flex-wrap gap-1.5">
                            {stages.map((s) => {
                                const active = s.id === lead.stageId;
                                const p = COLOR_MAP[s.color] || COLOR_MAP.slate;
                                return (
                                    <button
                                        key={s.id}
                                        type="button"
                                        onClick={() => onRequestStageMove(s)}
                                        className={`px-2.5 py-1 rounded-xl text-[11px] font-bold border transition-all ${
                                            active
                                                ? 'bg-slate-900 text-white border-slate-900 shadow-2xs'
                                                : `${p.badge} hover:opacity-80`
                                        }`}
                                    >
                                        {s.name}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Edit Details Form */}
                    <form onSubmit={handleSave} className="space-y-3 p-3.5 rounded-2xl border border-slate-200 bg-slate-50/60">
                        <div className="grid grid-cols-2 gap-2.5">
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                    Nama Customer
                                </label>
                                <input
                                    type="text"
                                    value={form.customer_name}
                                    onChange={(e) => setForm({ ...form, customer_name: e.target.value })}
                                    className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                    Nomor HP
                                </label>
                                <input
                                    type="text"
                                    value={form.customer_phone}
                                    onChange={(e) => setForm({ ...form, customer_phone: e.target.value })}
                                    className="w-full px-2.5 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                    Suhu Lead
                                </label>
                                <select
                                    value={form.temperature}
                                    onChange={(e) => setForm({ ...form, temperature: e.target.value })}
                                    className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg"
                                >
                                    <option value="hot">🔥 Hot</option>
                                    <option value="warm">🌤️ Warm</option>
                                    <option value="cold">❄️ Cold</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                    Minat
                                </label>
                                <select
                                    value={form.interest_type}
                                    onChange={(e) => setForm({ ...form, interest_type: e.target.value })}
                                    className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg"
                                >
                                    <option value="new">HP Baru</option>
                                    <option value="second">HP Second</option>
                                    <option value="trade_in">Trade-In</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                    Estimasi Nilai (Rp)
                                </label>
                                <input
                                    type="number"
                                    value={form.estimated_value}
                                    onChange={(e) => setForm({ ...form, estimated_value: e.target.value })}
                                    className="w-full px-2.5 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                    Jadwal Follow-Up
                                </label>
                                <input
                                    type="datetime-local"
                                    value={form.next_follow_up_at}
                                    onChange={(e) => setForm({ ...form, next_follow_up_at: e.target.value })}
                                    className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                    Toko
                                </label>
                                <select
                                    value={form.business_unit_id}
                                    onChange={(e) => setForm({ ...form, business_unit_id: e.target.value })}
                                    className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg"
                                >
                                    <option value="">Semua Toko</option>
                                    {businessUnits?.map((bu) => (
                                        <option key={bu.id} value={bu.id}>
                                            {bu.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {canViewAll && (
                                <div>
                                    <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                        CS Penanggung Jawab
                                    </label>
                                    <select
                                        value={form.assigned_to_user_id}
                                        onChange={(e) => setForm({ ...form, assigned_to_user_id: e.target.value })}
                                        className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg"
                                    >
                                        <option value="">Antrean Bebas</option>
                                        {csAgents.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}
                        </div>

                        <div>
                            <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1">Catatan</label>
                            <textarea
                                rows={2}
                                value={form.notes}
                                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                                className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg"
                            />
                        </div>

                        <div className="flex items-center justify-between pt-1">
                            <button
                                type="button"
                                onClick={handleDelete}
                                className="text-xs font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1"
                            >
                                <Trash2 className="w-3.5 h-3.5" /> Hapus Lead
                            </button>
                            <button
                                type="submit"
                                disabled={saving}
                                className="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold flex items-center gap-1.5"
                            >
                                {saving && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>

                    {/* Activity Timeline */}
                    <div className="space-y-3">
                        <div className="text-[11px] font-extrabold text-slate-500 uppercase">
                            Timeline Follow-Up & Riwayat Tahap
                        </div>

                        <form onSubmit={handleAddActivity} className="flex gap-2">
                            <input
                                type="text"
                                value={activityText}
                                onChange={(e) => setActivityText(e.target.value)}
                                placeholder="Tambah catatan hasil telepon / WA follow-up..."
                                className="flex-1 px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-indigo-500"
                            />
                            <button
                                type="submit"
                                disabled={postingNote}
                                className="px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center gap-1"
                            >
                                <Send className="w-3.5 h-3.5" /> Catat
                            </button>
                        </form>

                        <div className="space-y-2">
                            {(lead.activities || []).length === 0 ? (
                                <p className="text-xs text-slate-400 italic">Belum ada catatan aktivitas.</p>
                            ) : (
                                lead.activities.map((act) => (
                                    <div
                                        key={act.id}
                                        className="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs space-y-0.5"
                                    >
                                        <div className="flex items-center justify-between text-[10px] font-bold text-slate-500">
                                            <span>
                                                {act.type === 'auto_won' ? '⚡ ' : ''}
                                                {act.actorName}
                                            </span>
                                            <span>{act.createdAt}</span>
                                        </div>
                                        <p className="text-slate-700 leading-relaxed">{act.description}</p>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
