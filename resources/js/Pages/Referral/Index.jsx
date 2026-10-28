import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    Users,
    DollarSign,
    Clock,
    CheckCircle2,
    XCircle,
    Search,
    Filter,
    Settings,
    Share2,
    Copy,
    Check,
    Save,
    RefreshCw,
    AlertCircle,
    Upload,
    FileText,
    ExternalLink,
    Loader2,
    ArrowUpRight,
    Building2,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Percent
} from 'lucide-react';

export default function ReferralIndex({
    stats = {},
    withdrawals = { data: [] },
    commissions = { data: [] },
    members = { data: [] },
    settings = {},
    filters = {}
}) {
    const pageProps = usePage().props || {};
    const [activeTab, setActiveTab] = useState(filters?.tab || 'withdrawals');

    // Filter states
    const [withdrawalSearch, setWithdrawalSearch] = useState(filters?.withdrawal_search || '');
    const [withdrawalStatus, setWithdrawalStatus] = useState(filters?.withdrawal_status || '');
    const [commissionSearch, setCommissionSearch] = useState(filters?.commission_search || '');
    const [commissionStatus, setCommissionStatus] = useState(filters?.commission_status || '');
    const [memberSearch, setMemberSearch] = useState(filters?.member_search || '');

    // Copy to clipboard notification
    const [copiedCode, setCopiedCode] = useState(null);

    // Sync button state
    const [isSyncing, setIsSyncing] = useState(false);

    // Settings form state
    const [settingsForm, setSettingsForm] = useState({
        referral_is_active: settings?.referral_is_active ?? true,
        referral_commission_type: settings?.referral_commission_type || 'PERCENTAGE',
        referral_commission_rate: settings?.referral_commission_rate ?? 1.0,
        referral_reward_scope: settings?.referral_reward_scope || 'FIRST_ORDER_ONLY',
        referral_max_commission_cap: settings?.referral_max_commission_cap ?? 100000,
        referral_hold_days: settings?.referral_hold_days ?? 7,
        referral_min_withdrawal: settings?.referral_min_withdrawal ?? 50000,
    });
    const [isSavingSettings, setIsSavingSettings] = useState(false);

    // Approve Modal State
    const [approveModal, setApproveModal] = useState({
        isOpen: false,
        withdrawal: null,
        notes: '',
        proofFile: null,
        isSubmitting: false,
    });

    // Reject Modal State
    const [rejectModal, setRejectModal] = useState({
        isOpen: false,
        withdrawal: null,
        rejectionReason: '',
        isSubmitting: false,
    });

    // Debounced search handler for withdrawals
    useEffect(() => {
        const timer = setTimeout(() => {
            if (activeTab === 'withdrawals' && withdrawalSearch !== (filters?.withdrawal_search || '')) {
                applyWithdrawalFilter({ withdrawal_search: withdrawalSearch });
            }
        }, 350);
        return () => clearTimeout(timer);
    }, [withdrawalSearch]);

    // Debounced search handler for commissions
    useEffect(() => {
        const timer = setTimeout(() => {
            if (activeTab === 'commissions' && commissionSearch !== (filters?.commission_search || '')) {
                applyCommissionFilter({ commission_search: commissionSearch });
            }
        }, 350);
        return () => clearTimeout(timer);
    }, [commissionSearch]);

    // Debounced search handler for members
    useEffect(() => {
        const timer = setTimeout(() => {
            if (activeTab === 'members' && memberSearch !== (filters?.member_search || '')) {
                applyMemberFilter({ member_search: memberSearch });
            }
        }, 350);
        return () => clearTimeout(timer);
    }, [memberSearch]);

    const handleTabChange = (newTab) => {
        setActiveTab(newTab);
        router.get(
            '/crm/referral',
            {
                tab: newTab,
                withdrawal_search: withdrawalSearch,
                withdrawal_status: withdrawalStatus,
                commission_search: commissionSearch,
                commission_status: commissionStatus,
                member_search: memberSearch,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const applyWithdrawalFilter = (extraParams = {}) => {
        router.get(
            '/crm/referral',
            {
                tab: 'withdrawals',
                withdrawal_search: withdrawalSearch,
                withdrawal_status: withdrawalStatus,
                ...extraParams,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const applyCommissionFilter = (extraParams = {}) => {
        router.get(
            '/crm/referral',
            {
                tab: 'commissions',
                commission_search: commissionSearch,
                commission_status: commissionStatus,
                ...extraParams,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const applyMemberFilter = (extraParams = {}) => {
        router.get(
            '/crm/referral',
            {
                tab: 'members',
                member_search: memberSearch,
                ...extraParams,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleCopy = (code) => {
        if (!code) return;
        navigator.clipboard.writeText(code);
        setCopiedCode(code);
        setTimeout(() => setCopiedCode(null), 2000);
    };

    const handleSaveSettings = (e) => {
        e.preventDefault();
        setIsSavingSettings(true);
        router.post('/crm/referral/settings', settingsForm, {
            onSuccess: () => setIsSavingSettings(false),
            onError: () => setIsSavingSettings(false),
        });
    };

    const handleReleaseHolds = () => {
        if (isSyncing) return;
        setIsSyncing(true);
        router.post('/crm/referral/release-holds', {}, {
            onSuccess: () => setIsSyncing(false),
            onError: () => setIsSyncing(false),
        });
    };

    const openApprove = (withdrawal) => {
        setApproveModal({
            isOpen: true,
            withdrawal,
            notes: '',
            proofFile: null,
            isSubmitting: false,
        });
    };

    const closeApprove = () => {
        setApproveModal({
            isOpen: false,
            withdrawal: null,
            notes: '',
            proofFile: null,
            isSubmitting: false,
        });
    };

    const submitApprove = (e) => {
        e.preventDefault();
        if (!approveModal.withdrawal) return;

        setApproveModal((prev) => ({ ...prev, isSubmitting: true }));
        const formData = new FormData();
        if (approveModal.proofFile) {
            formData.append('proof_file', approveModal.proofFile);
        }
        if (approveModal.notes) {
            formData.append('notes', approveModal.notes);
        }

        router.post(`/crm/referral/withdrawals/${approveModal.withdrawal.id}/approve`, formData, {
            onSuccess: () => closeApprove(),
            onError: () => setApproveModal((prev) => ({ ...prev, isSubmitting: false })),
        });
    };

    const openReject = (withdrawal) => {
        setRejectModal({
            isOpen: true,
            withdrawal,
            rejectionReason: '',
            isSubmitting: false,
        });
    };

    const closeReject = () => {
        setRejectModal({
            isOpen: false,
            withdrawal: null,
            rejectionReason: '',
            isSubmitting: false,
        });
    };

    const submitReject = (e) => {
        e.preventDefault();
        if (!rejectModal.withdrawal) return;

        setRejectModal((prev) => ({ ...prev, isSubmitting: true }));
        router.post(
            `/crm/referral/withdrawals/${rejectModal.withdrawal.id}/reject`,
            { rejection_reason: rejectModal.rejectionReason },
            {
                onSuccess: () => closeReject(),
                onError: () => setRejectModal((prev) => ({ ...prev, isSubmitting: false })),
            }
        );
    };

    return (
        <EcommerceLayout title="Referral & Komisi">
            <Head title="Manajemen Referral & Komisi Afiliasi" />

            <div className="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header & Live Indicator */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-emerald-500/10 text-emerald-600">
                                <Users className="w-5 h-5 sm:w-6 sm:h-6" />
                            </span>
                            Manajemen Referral & Komisi Afiliasi
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-1">
                            Kelola pencairan komisi mitra, pantau ledger komisi pesanan, serta aturan bagi hasil referral.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={handleReleaseHolds}
                            disabled={isSyncing}
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold shadow-2xs transition-all disabled:opacity-50"
                            title="Periksa dan lepaskan komisi yang telah melewati masa hold H+7 menjadi Siap Ditarik"
                        >
                            <RefreshCw className={`w-3.5 h-3.5 text-indigo-600 ${isSyncing ? 'animate-spin' : ''}`} />
                            <span className="hidden sm:inline">Sinkronkan Komisi Matured</span>
                            <span className="sm:hidden">Sinkron Matured</span>
                        </button>
                    </div>
                </div>

                {/* 2. Top Metric Stats Overview */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    {/* Card 1: Withdrawals Pending */}
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                        <div className="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200/50">
                            <Clock className="w-5 h-5" />
                        </div>
                        <div className="min-w-0">
                            <div className="flex items-center gap-1.5">
                                <p className="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">
                                    Tarik Menunggu
                                </p>
                                {stats?.total_pending_withdrawals_count > 0 && (
                                    <span className="px-1.5 py-0.2 bg-amber-500 text-white rounded-md text-[9px] font-black animate-pulse">
                                        {stats.total_pending_withdrawals_count}
                                    </span>
                                )}
                            </div>
                            <h4 className="text-base sm:text-lg font-black text-amber-600 mt-0.5 truncate">
                                Rp {Number(stats?.total_pending_withdrawals_amount || 0).toLocaleString('id-ID')}
                            </h4>
                            <p className="text-[10px] text-slate-400 truncate">
                                {stats?.total_pending_withdrawals_count || 0} pengajuan perlu diproses
                            </p>
                        </div>
                    </div>

                    {/* Card 2: Komisi Ditransfer */}
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                        <div className="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200/50">
                            <CheckCircle2 className="w-5 h-5" />
                        </div>
                        <div className="min-w-0">
                            <p className="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">
                                Komisi Ditransfer
                            </p>
                            <h4 className="text-base sm:text-lg font-black text-emerald-600 mt-0.5 truncate">
                                Rp {Number(stats?.total_withdrawn_transferred || 0).toLocaleString('id-ID')}
                            </h4>
                            <p className="text-[10px] text-slate-400 truncate">Pencairan sukses ke rekening</p>
                        </div>
                    </div>

                    {/* Card 3: Komisi Hold Garansi */}
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                        <div className="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-200/50">
                            <DollarSign className="w-5 h-5" />
                        </div>
                        <div className="min-w-0">
                            <p className="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">
                                Hold Garansi H+7
                            </p>
                            <h4 className="text-base sm:text-lg font-black text-indigo-600 mt-0.5 truncate">
                                Rp {Number(stats?.total_commissions_pending_hold || 0).toLocaleString('id-ID')}
                            </h4>
                            <p className="text-[10px] text-slate-400 truncate">Cooling-off return/garansi</p>
                        </div>
                    </div>

                    {/* Card 4: Mitra Afiliasi */}
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                        <div className="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 border border-purple-200/50">
                            <Share2 className="w-5 h-5" />
                        </div>
                        <div className="min-w-0">
                            <p className="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">
                                Mitra Afiliasi
                            </p>
                            <h4 className="text-base sm:text-lg font-black text-slate-900 mt-0.5 truncate">
                                {Number(stats?.total_referral_users || 0).toLocaleString('id-ID')}
                            </h4>
                            <p className="text-[10px] text-slate-400 truncate">Total kode aktif terdaftar</p>
                        </div>
                    </div>
                </div>

                {/* 3. Navigation Tabs Bar */}
                <div className="border-b border-slate-200 flex items-center gap-2 overflow-x-auto scrollbar-none pb-2 text-xs font-bold">
                    <button
                        type="button"
                        onClick={() => handleTabChange('withdrawals')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            activeTab === 'withdrawals'
                                ? 'bg-slate-900 text-white shadow-xs'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <span>💸</span>
                        <span>Pengajuan Pencairan</span>
                        {stats?.total_pending_withdrawals_count > 0 && (
                            <span className="bg-amber-500 text-white text-[10px] px-1.5 py-0.2 rounded-full font-black animate-pulse">
                                {stats.total_pending_withdrawals_count}
                            </span>
                        )}
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('commissions')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            activeTab === 'commissions'
                                ? 'bg-slate-900 text-white shadow-xs'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <span>📋</span>
                        <span>Ledger Komisi Pesanan</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('members')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            activeTab === 'members'
                                ? 'bg-slate-900 text-white shadow-xs'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <span>👥</span>
                        <span>Mitra & Performa Afiliasi</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('settings')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            activeTab === 'settings'
                                ? 'bg-slate-900 text-white shadow-xs'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <span>⚙️</span>
                        <span>Pengaturan Program</span>
                    </button>
                </div>

                {/* 4. Tab 1: Pengajuan Pencairan Dana (Withdrawals) */}
                {activeTab === 'withdrawals' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        {/* Filter Toolbar */}
                        <div className="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            <div className="relative flex-1 max-w-md">
                                <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    value={withdrawalSearch}
                                    onChange={(e) => setWithdrawalSearch(e.target.value)}
                                    placeholder="Cari nama member, email, atau no rekening..."
                                    className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <select
                                    value={withdrawalStatus}
                                    onChange={(e) => {
                                        setWithdrawalStatus(e.target.value);
                                        applyWithdrawalFilter({ withdrawal_status: e.target.value });
                                    }}
                                    className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="PENDING">Menunggu Persetujuan</option>
                                    <option value="TRANSFERRED">Berhasil Ditransfer</option>
                                    <option value="REJECTED">Ditolak</option>
                                </select>
                            </div>
                        </div>

                        {/* Withdrawals Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50 text-slate-500 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-100">
                                    <tr>
                                        <th className="py-3 px-4">Member & Kontak</th>
                                        <th className="py-3 px-4">Rekening Tujuan</th>
                                        <th className="py-3 px-4">Nominal Bersih</th>
                                        <th className="py-3 px-4">Waktu Pengajuan</th>
                                        <th className="py-3 px-4">Status</th>
                                        <th className="py-3 px-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 font-medium text-slate-700">
                                    {withdrawals?.data?.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="py-12 text-center text-slate-400">
                                                <p className="text-sm font-bold">Tidak ada data pengajuan penarikan dana.</p>
                                                <p className="text-xs mt-1">Pengajuan penarikan dari aplikasi mobile akan muncul di sini.</p>
                                            </td>
                                        </tr>
                                    ) : (
                                        withdrawals.data.map((item) => {
                                            const status = item.status;
                                            return (
                                                <tr key={item.id} className="hover:bg-slate-50/80 transition-colors">
                                                    <td className="py-3 px-4">
                                                        <div className="font-bold text-slate-900">{item.user?.name || 'User #' + item.user_id}</div>
                                                        <div className="text-[11px] text-slate-400">{item.user?.email || '-'}</div>
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <div className="font-bold text-slate-800">
                                                            {item.bank_account?.bank_name || 'Bank'} - {item.bank_account?.account_number || '-'}
                                                        </div>
                                                        <div className="text-[11px] text-slate-400">
                                                            a.n. {item.bank_account?.account_name || '-'}
                                                        </div>
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <span className="font-extrabold text-sm text-slate-900">
                                                            Rp {Number(item.net_amount || item.amount).toLocaleString('id-ID')}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4 text-slate-500 text-[11px]">
                                                        {item.created_at ? new Date(item.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-'}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        {status === 'PENDING' && (
                                                            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                                                <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                                Menunggu
                                                            </span>
                                                        )}
                                                        {status === 'TRANSFERRED' && (
                                                            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                                                                Ditransfer
                                                            </span>
                                                        )}
                                                        {status === 'REJECTED' && (
                                                            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                                                <XCircle className="w-3 h-3 text-rose-600" />
                                                                Ditolak
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-4 text-right">
                                                        {status === 'PENDING' ? (
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => openApprove(item)}
                                                                    className="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold shadow-2xs transition-all"
                                                                >
                                                                    Setujui
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => openReject(item)}
                                                                    className="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 text-[11px] font-bold transition-all"
                                                                >
                                                                    Tolak
                                                                </button>
                                                            </div>
                                                        ) : item.proof_attachment_url ? (
                                                            <a
                                                                href={item.proof_attachment_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800"
                                                            >
                                                                <span>Bukti</span>
                                                                <ExternalLink className="w-3 h-3" />
                                                            </a>
                                                        ) : (
                                                            <span className="text-[11px] text-slate-400 italic">Selesai</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* 5. Tab 2: Ledger Komisi Pesanan */}
                {activeTab === 'commissions' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        {/* Filter Toolbar */}
                        <div className="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            <div className="relative flex-1 max-w-md">
                                <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    value={commissionSearch}
                                    onChange={(e) => setCommissionSearch(e.target.value)}
                                    placeholder="Cari no pesanan, nama mitra, atau nama pembeli..."
                                    className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <select
                                    value={commissionStatus}
                                    onChange={(e) => {
                                        setCommissionStatus(e.target.value);
                                        applyCommissionFilter({ commission_status: e.target.value });
                                    }}
                                    className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="PENDING">Hold / Pending</option>
                                    <option value="APPROVED">Siap Ditarik (Approved)</option>
                                    <option value="WITHDRAWN">Telah Dicairkan</option>
                                    <option value="VOID">Batal / Void</option>
                                </select>
                            </div>
                        </div>

                        {/* Commissions Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50 text-slate-500 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-100">
                                    <tr>
                                        <th className="py-3 px-4">No. Pesanan</th>
                                        <th className="py-3 px-4">Mitra Pengundang</th>
                                        <th className="py-3 px-4">Pembeli Baru</th>
                                        <th className="py-3 px-4">Subtotal Belanja</th>
                                        <th className="py-3 px-4">Komisi (Rate)</th>
                                        <th className="py-3 px-4">Status & Masa Hold</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 font-medium text-slate-700">
                                    {commissions?.data?.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="py-12 text-center text-slate-400">
                                                <p className="text-sm font-bold">Belum ada transaksi dengan komisi referral.</p>
                                                <p className="text-xs mt-1">Komisi otomatis dihitung saat pembeli bertransaksi memakai kode referral mitra.</p>
                                            </td>
                                        </tr>
                                    ) : (
                                        commissions.data.map((item) => (
                                            <tr key={item.id} className="hover:bg-slate-50/80 transition-colors">
                                                <td className="py-3 px-4 font-mono font-bold text-slate-900">
                                                    #{item.order?.order_number || item.order_id}
                                                </td>
                                                <td className="py-3 px-4 font-bold text-slate-900">
                                                    {item.referrer?.name || 'Mitra #' + item.referrer_id}
                                                </td>
                                                <td className="py-3 px-4 text-slate-600">
                                                    {item.referee?.name || 'Pembeli #' + item.referee_id}
                                                </td>
                                                <td className="py-3 px-4">
                                                    Rp {Number(item.order_amount || 0).toLocaleString('id-ID')}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <span className="font-extrabold text-emerald-600">
                                                        Rp {Number(item.commission_amount || 0).toLocaleString('id-ID')}
                                                    </span>
                                                    <span className="text-[10px] text-slate-400 ml-1">
                                                        ({item.commission_rate}%)
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4">
                                                    {item.status === 'PENDING' && (
                                                        <div>
                                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                Hold Cooling-off
                                                            </span>
                                                            {item.hold_until && (
                                                                <div className="text-[10px] text-slate-400 mt-0.5">
                                                                    S/d {new Date(item.hold_until).toLocaleDateString('id-ID')}
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                    {item.status === 'APPROVED' && (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            Siap Ditarik
                                                        </span>
                                                    )}
                                                    {item.status === 'WITHDRAWN' && (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                            Sudah Dicairkan
                                                        </span>
                                                    )}
                                                    {item.status === 'VOID' && (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                            Batal
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* 6. Tab 3: Mitra & Performa Afiliasi */}
                {activeTab === 'members' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        {/* Filter Toolbar */}
                        <div className="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            <div className="relative flex-1 max-w-md">
                                <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    value={memberSearch}
                                    onChange={(e) => setMemberSearch(e.target.value)}
                                    placeholder="Cari nama, email, kode referral, atau no HP..."
                                    className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>
                        </div>

                        {/* Members Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50 text-slate-500 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-100">
                                    <tr>
                                        <th className="py-3 px-4">Nama Member</th>
                                        <th className="py-3 px-4">Kode Referral</th>
                                        <th className="py-3 px-4">Teman Terdaftar</th>
                                        <th className="py-3 px-4">Total Pesanan Sukses</th>
                                        <th className="py-3 px-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 font-medium text-slate-700">
                                    {members?.data?.length === 0 ? (
                                        <tr>
                                            <td colSpan="5" className="py-12 text-center text-slate-400">
                                                <p className="text-sm font-bold">Belum ada member dengan kode referral aktif.</p>
                                            </td>
                                        </tr>
                                    ) : (
                                        members.data.map((item) => (
                                            <tr key={item.id} className="hover:bg-slate-50/80 transition-colors">
                                                <td className="py-3 px-4">
                                                    <div className="font-bold text-slate-900">{item.name}</div>
                                                    <div className="text-[11px] text-slate-400">{item.email}</div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="font-mono font-black text-xs px-2.5 py-1 bg-slate-100 text-slate-800 rounded-lg">
                                                            {item.referral_code}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleCopy(item.referral_code)}
                                                            className="p-1 text-slate-400 hover:text-slate-700 rounded transition-colors"
                                                            title="Salin kode"
                                                        >
                                                            {copiedCode === item.referral_code ? (
                                                                <Check className="w-3.5 h-3.5 text-emerald-600" />
                                                            ) : (
                                                                <Copy className="w-3.5 h-3.5" />
                                                            )}
                                                        </button>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4 font-bold text-slate-800">
                                                    {item.referrals_count || 0} akun
                                                </td>
                                                <td className="py-3 px-4 font-bold text-slate-800">
                                                    {item.referral_commissions_count || 0} kali
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <a
                                                        href={`https://wa.me/?text=Daftar%20di%20Tokopon%20pakai%20kode%20${item.referral_code}`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-[11px] font-bold text-emerald-600 hover:text-emerald-800 inline-flex items-center gap-1"
                                                    >
                                                        <span>Bagikan</span>
                                                        <Share2 className="w-3 h-3" />
                                                    </a>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* 7. Tab 4: Pengaturan Program Referral & Komisi */}
                {activeTab === 'settings' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-7 shadow-2xs">
                        <div className="flex items-center gap-3 pb-5 border-b border-slate-100">
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                ⚙️
                            </div>
                            <div>
                                <h2 className="text-base font-black text-slate-900 tracking-tight">
                                    Pengaturan Program Referral & Komisi Afiliasi
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Aturan persentase komisi, batas penarikan, dan masa garansi cooling-off.
                                </p>
                            </div>
                        </div>

                        <form onSubmit={handleSaveSettings} className="mt-6 space-y-6">
                            {/* Toggle Aktif */}
                            <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    id="referral_is_active"
                                    checked={settingsForm.referral_is_active}
                                    onChange={(e) => setSettingsForm({ ...settingsForm, referral_is_active: e.target.checked })}
                                    className="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 mt-0.5"
                                />
                                <div>
                                    <label htmlFor="referral_is_active" className="text-xs font-bold text-slate-900 cursor-pointer block">
                                        Aktifkan Program Referral & Komisi Afiliasi
                                    </label>
                                    <p className="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                        Jika dinonaktifkan, pendaftaran user baru tidak akan menghasilkan komisi dan kode referral disembunyikan di aplikasi.
                                    </p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                {/* Tipe Komisi */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Tipe Perhitungan Komisi
                                    </label>
                                    <select
                                        value={settingsForm.referral_commission_type}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, referral_commission_type: e.target.value })}
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    >
                                        <option value="PERCENTAGE">Persentase Belanja (%)</option>
                                        <option value="FIXED">Nominal Tetap (Rp)</option>
                                    </select>
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Standar: Persentase dari total subtotal produk belanjaan.
                                    </span>
                                </div>

                                {/* Nilai Komisi */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Besaran Komisi ({settingsForm.referral_commission_type === 'PERCENTAGE' ? '%' : 'Rp'})
                                    </label>
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0.1"
                                        value={settingsForm.referral_commission_rate}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, referral_commission_rate: parseFloat(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Contoh: 1.0 (artinya 1% dari total pesanan pembeli).
                                    </span>
                                </div>

                                {/* Scope Reward */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Cakupan Reward
                                    </label>
                                    <select
                                        value={settingsForm.referral_reward_scope}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, referral_reward_scope: e.target.value })}
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    >
                                        <option value="FIRST_ORDER_ONLY">Hanya Pesanan Pertama Pembeli</option>
                                        <option value="ALL_ORDERS">Setiap Pesanan Berulang (Lifetime)</option>
                                    </select>
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Rekomendasi: Pesanan pertama untuk menghindari over-spending komisi.
                                    </span>
                                </div>

                                {/* Maksimal Cap Komisi */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Batas Maksimal Komisi per Pesanan (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="5000"
                                        value={settingsForm.referral_max_commission_cap}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, referral_max_commission_cap: parseFloat(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Plafon proteksi margin profit (misal max Rp 100.000).
                                    </span>
                                </div>

                                {/* Masa Tahan Hold Days */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Masa Tahan Garansi Cooling-off (Hari)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="60"
                                        value={settingsForm.referral_hold_days}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, referral_hold_days: parseInt(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        H+7 hari setelah pesanan selesai, komisi otomatis menjadi Siap Ditarik.
                                    </span>
                                </div>

                                {/* Minimal Penarikan */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Batas Minimal Pencairan Saldo (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="10000"
                                        step="5000"
                                        value={settingsForm.referral_min_withdrawal}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, referral_min_withdrawal: parseFloat(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Minimal penarikan oleh member di mobile app (misal Rp 50.000).
                                    </span>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-slate-100 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={isSavingSettings}
                                    className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSavingSettings ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                                    Simpan Pengaturan Program Referral
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>

            {/* Modal Approve Withdrawal */}
            {approveModal.isOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                    <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 className="text-base font-black text-slate-900">
                                Konfirmasi Persetujuan Pencairan Dana
                            </h3>
                            <button
                                type="button"
                                onClick={closeApprove}
                                className="text-slate-400 hover:text-slate-600 font-bold"
                            >
                                ✕
                            </button>
                        </div>

                        <div className="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-100 text-xs text-emerald-800 space-y-1">
                            <div className="font-extrabold text-sm text-emerald-900">
                                Rp {Number(approveModal.withdrawal?.net_amount || approveModal.withdrawal?.amount).toLocaleString('id-ID')}
                            </div>
                            <p>
                                Tujuan: {approveModal.withdrawal?.bank_account?.bank_name} - {approveModal.withdrawal?.bank_account?.account_number}
                            </p>
                            <p>a.n. {approveModal.withdrawal?.bank_account?.account_name}</p>
                        </div>

                        <form onSubmit={submitApprove} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Upload Bukti Transfer Bank (Opsional)
                                </label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setApproveModal({ ...approveModal, proofFile: e.target.files[0] })}
                                    className="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Catatan Persetujuan (Opsional)
                                </label>
                                <input
                                    type="text"
                                    value={approveModal.notes}
                                    onChange={(e) => setApproveModal({ ...approveModal, notes: e.target.value })}
                                    placeholder="Contoh: Transfer via BCA Mobile ref #12345"
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none"
                                />
                            </div>

                            <div className="pt-3 flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={closeApprove}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={approveModal.isSubmitting}
                                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50"
                                >
                                    {approveModal.isSubmitting ? 'Memproses...' : 'Tandai Telah Ditransfer'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Reject Withdrawal */}
            {rejectModal.isOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                    <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 className="text-base font-black text-rose-700">
                                Tolak Pengajuan Pencairan Dana
                            </h3>
                            <button
                                type="button"
                                onClick={closeReject}
                                className="text-slate-400 hover:text-slate-600 font-bold"
                            >
                                ✕
                            </button>
                        </div>

                        <div className="p-3.5 bg-rose-50 rounded-2xl border border-rose-100 text-xs text-rose-800">
                            Saldo komisi sebesar <strong>Rp {Number(rejectModal.withdrawal?.amount).toLocaleString('id-ID')}</strong> akan otomatis dikembalikan ke saldo aktif mitra.
                        </div>

                        <form onSubmit={submitReject} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Alasan Penolakan *
                                </label>
                                <textarea
                                    value={rejectModal.rejectionReason}
                                    onChange={(e) => setRejectModal({ ...rejectModal, rejectionReason: e.target.value })}
                                    required
                                    rows="3"
                                    placeholder="Contoh: Nomor rekening bank tidak valid / nama tidak sesuai KTP"
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                                />
                            </div>

                            <div className="pt-3 flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={closeReject}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={rejectModal.isSubmitting}
                                    className="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-500/20 disabled:opacity-50"
                                >
                                    {rejectModal.isSubmitting ? 'Memproses...' : 'Tolak & Kembalikan Saldo'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </EcommerceLayout>
    );
}
