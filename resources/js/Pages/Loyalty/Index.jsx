import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    Crown,
    Search,
    Filter,
    Plus,
    Minus,
    Clock,
    DollarSign,
    Users,
    Sparkles,
    Settings,
    Check,
    X,
    Loader2,
    RefreshCw,
    Award
} from 'lucide-react';
import axios from 'axios';

export default function LoyaltyIndex({
    members = { data: [] },
    stats = {},
    settings = {},
    filters = {}
}) {
    const [activeTab, setActiveTab] = useState('members'); // 'members' | 'settings'

    // Filter states
    const [search, setSearch] = useState(filters?.search || '');
    const [selectedTier, setSelectedTier] = useState(filters?.tier || '');

    // Settings form state
    const [settingsForm, setSettingsForm] = useState({
        loyalty_is_active: settings?.loyalty_is_active ?? true,
        loyalty_earn_ratio: settings?.loyalty_earn_ratio ?? 10000,
        loyalty_point_value: settings?.loyalty_point_value ?? 100,
        loyalty_min_redeem_points: settings?.loyalty_min_redeem_points ?? 50,
        loyalty_tier_silver_spend: settings?.loyalty_tier_silver_spend ?? 5000000,
        loyalty_tier_gold_spend: settings?.loyalty_tier_gold_spend ?? 15000000,
        loyalty_tier_platinum_spend: settings?.loyalty_tier_platinum_spend ?? 50000000,
    });
    const [isSavingSettings, setIsSavingSettings] = useState(false);

    // Modal Adjust Points State
    const [isAdjustModalOpen, setIsAdjustModalOpen] = useState(false);
    const [targetAccount, setTargetAccount] = useState(null);
    const [adjustForm, setAdjustForm] = useState({
        points: 50,
        type: 'ADD', // 'ADD' | 'DEDUCT'
        description: 'Penyesuaian manual oleh CS / Admin',
    });
    const [isSubmittingAdjust, setIsSubmittingAdjust] = useState(false);

    // Modal History Ledger State
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
    const [historyAccount, setHistoryAccount] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [isLoadingHistory, setIsLoadingHistory] = useState(false);

    // Sync button loading
    const [isSyncing, setIsSyncing] = useState(false);

    // Debounced search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters?.search || '')) {
                applyFilters({ search });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    const applyFilters = (newFilters = {}) => {
        router.get(
            '/crm/loyalty',
            {
                search,
                tier: selectedTier,
                ...newFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Handle Sync Old Users
    const handleSyncUsers = () => {
        if (confirm('Jalankan pemindaian dan sinkronisasi seluruh database customer lama ke Akun Loyalty?')) {
            setIsSyncing(true);
            router.post(
                '/crm/loyalty/sync',
                { calc_spend: true },
                {
                    onFinish: () => setIsSyncing(false),
                }
            );
        }
    };

    // Open Adjust Modal
    const handleOpenAdjust = (account) => {
        setTargetAccount(account);
        setAdjustForm({
            points: 50,
            type: 'ADD',
            description: 'Penyesuaian poin oleh CS',
        });
        setIsAdjustModalOpen(true);
    };

    const handleSubmitAdjust = (e) => {
        e.preventDefault();
        if (!targetAccount) return;

        setIsSubmittingAdjust(true);
        router.post(
            `/crm/loyalty/${targetAccount.id}/adjust`,
            adjustForm,
            {
                onSuccess: () => {
                    setIsAdjustModalOpen(false);
                    setIsSubmittingAdjust(false);
                },
                onError: () => setIsSubmittingAdjust(false),
            }
        );
    };

    // Open History Modal
    const handleOpenHistory = async (account) => {
        setHistoryAccount(account);
        setIsHistoryModalOpen(true);
        setIsLoadingHistory(true);

        try {
            const res = await axios.get(`/crm/loyalty/${account.id}/history`);
            setTransactions(res.data?.transactions || []);
        } catch (e) {
            console.error('Failed to load loyalty history:', e);
        } finally {
            setIsLoadingHistory(false);
        }
    };

    // Save Settings Form
    const handleSaveSettings = (e) => {
        e.preventDefault();
        setIsSavingSettings(true);

        router.post('/crm/loyalty/settings', settingsForm, {
            onSuccess: () => setIsSavingSettings(false),
            onError: () => setIsSavingSettings(false),
        });
    };

    const getTierBadge = (tier) => {
        switch (tier?.toUpperCase()) {
            case 'PLATINUM':
                return 'bg-purple-100 text-purple-800 border-purple-200';
            case 'GOLD':
                return 'bg-amber-100 text-amber-800 border-amber-200';
            case 'SILVER':
                return 'bg-slate-200 text-slate-800 border-slate-300';
            default:
                return 'bg-orange-50 text-orange-700 border-orange-200';
        }
    };

    return (
        <EcommerceLayout title="Member Loyalty & Poin">
            <Head title="Manajemen Member Loyalty & Poin" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header Toolbar */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-yellow-500/10 text-yellow-600">
                                <Crown className="w-6 h-6" />
                            </span>
                            Member Loyalty & Poin
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Kelola saldo reward poin pelanggan, penyesuaian poin manual, dan kriteria tier loyalitas.
                        </p>
                    </div>

                    <button
                        onClick={handleSyncUsers}
                        disabled={isSyncing}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200/80 shadow-2xs transition-all disabled:opacity-50"
                    >
                        <RefreshCw className={`w-3.5 h-3.5 ${isSyncing ? 'animate-spin text-amber-500' : 'text-slate-500'}`} />
                        Sinkronisasi Akun Lama
                    </button>
                </div>

                {/* 2. KPI Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center font-bold">
                            <Users className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Member</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {(stats?.total_members ?? 0).toLocaleString('id-ID')}
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <Sparkles className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Saldo Poin Aktif</div>
                            <div className="text-2xl font-black text-indigo-600 mt-0.5">
                                {(stats?.total_points_balance ?? 0).toLocaleString('id-ID')}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">poin</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <Award className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Poin Diterbitkan</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {(stats?.total_lifetime_points ?? 0).toLocaleString('id-ID')}
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                            <DollarSign className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Belanja Member</div>
                            <div className="text-xl font-black text-slate-900 mt-0.5">
                                {stats?.formatted_total_spend || 'Rp 0'}
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3. Main Navigation Tabs */}
                <div className="flex items-center gap-2 border-b border-slate-200">
                    <button
                        onClick={() => setActiveTab('members')}
                        className={`pb-3 text-sm font-extrabold transition-all border-b-2 flex items-center gap-2 ${
                            activeTab === 'members'
                                ? 'border-yellow-500 text-slate-900'
                                : 'border-transparent text-slate-400 hover:text-slate-600'
                        }`}
                    >
                        <Users className="w-4 h-4" />
                        Daftar Akun Member & Poin
                    </button>
                    <button
                        onClick={() => setActiveTab('settings')}
                        className={`pb-3 text-sm font-extrabold transition-all border-b-2 flex items-center gap-2 ${
                            activeTab === 'settings'
                                ? 'border-yellow-500 text-slate-900'
                                : 'border-transparent text-slate-400 hover:text-slate-600'
                        }`}
                    >
                        <Settings className="w-4 h-4" />
                        Pengaturan Rasio Poin & Tier
                    </button>
                </div>

                {/* TAB 1: MEMBERS LIST */}
                {activeTab === 'members' && (
                    <div className="space-y-4">
                        {/* Search & Tier Filter */}
                        <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-wrap items-center justify-between gap-3">
                            <div className="relative flex-1 min-w-[260px]">
                                <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nomor HP, nama member..."
                                    className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-500"
                                />
                            </div>

                            <select
                                value={selectedTier}
                                onChange={(e) => {
                                    setSelectedTier(e.target.value);
                                    applyFilters({ tier: e.target.value });
                                }}
                                className="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2"
                            >
                                <option value="">Semua Tier Member</option>
                                <option value="BRONZE">BRONZE</option>
                                <option value="SILVER">SILVER</option>
                                <option value="GOLD">GOLD</option>
                                <option value="PLATINUM">PLATINUM</option>
                            </select>
                        </div>

                        {/* Table */}
                        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                            {members?.data?.length === 0 ? (
                                <div className="p-12 text-center text-slate-400">
                                    <Crown className="w-10 h-10 text-slate-300 mx-auto mb-2" />
                                    <p className="text-sm font-semibold">Tidak ada member loyalty yang sesuai.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs border-collapse">
                                        <thead>
                                            <tr className="bg-slate-50/80 border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                                <th className="py-3 px-4 font-bold">Pelanggan / Member</th>
                                                <th className="py-3 px-3 font-bold text-center">Tier</th>
                                                <th className="py-3 px-3 font-bold text-right">Saldo Poin</th>
                                                <th className="py-3 px-3 font-bold text-right">Lifetime Poin</th>
                                                <th className="py-3 px-4 font-bold text-right">Total Belanja</th>
                                                <th className="py-3 px-4 font-bold text-center">Aksi CS</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {members.data.map((mem) => (
                                                <tr key={mem.id} className="hover:bg-slate-50/60 transition-colors">
                                                    <td className="py-3 px-4">
                                                        <div className="font-extrabold text-slate-900 text-sm">
                                                            {mem.user_name}
                                                        </div>
                                                        <div className="text-[11px] text-slate-400 font-mono mt-0.5">
                                                            {mem.phone_number}
                                                        </div>
                                                    </td>
                                                    <td className="py-3 px-3 text-center">
                                                        <span className={`px-2 py-0.5 rounded-full text-[10.5px] font-black border ${getTierBadge(mem.tier)}`}>
                                                            {mem.tier}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-3 text-right font-black text-indigo-600 text-sm">
                                                        {mem.points_balance.toLocaleString('id-ID')}
                                                    </td>
                                                    <td className="py-3 px-3 text-right font-semibold text-slate-600">
                                                        {mem.lifetime_points.toLocaleString('id-ID')}
                                                    </td>
                                                    <td className="py-3 px-4 text-right font-extrabold text-slate-900">
                                                        {mem.formatted_spend}
                                                    </td>
                                                    <td className="py-3 px-4 text-center">
                                                        <div className="flex items-center justify-center gap-1.5">
                                                            <button
                                                                onClick={() => handleOpenAdjust(mem)}
                                                                className="px-2.5 py-1 rounded-lg bg-yellow-50 hover:bg-yellow-100 text-yellow-700 font-bold text-[11px] transition-colors"
                                                            >
                                                                Ubah Poin
                                                            </button>
                                                            <button
                                                                onClick={() => handleOpenHistory(mem)}
                                                                className="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition-colors"
                                                            >
                                                                Buku Besar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {members?.links && members.links.length > 3 && (
                                <div className="p-4 border-t border-slate-100 flex items-center justify-center gap-1">
                                    {members.links.map((link, idx) => (
                                        <button
                                            key={idx}
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
                                                link.active
                                                    ? 'bg-yellow-500 text-white shadow-2xs'
                                                    : link.url
                                                    ? 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                                                    : 'text-slate-300 cursor-not-allowed'
                                            }`}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* TAB 2: SETTINGS FORM */}
                {activeTab === 'settings' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-2xs max-w-2xl">
                        <h3 className="text-base font-black text-slate-900 tracking-tight">
                            Aturan Perolehan & Nilai Poin
                        </h3>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Konfigurasi rasio belanja untuk memperoleh poin dan syarat belanja minimal tier.
                        </p>

                        <form onSubmit={handleSaveSettings} className="space-y-4 mt-5">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Rasio Belanja per 1 Poin (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        value={settingsForm.loyalty_earn_ratio}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, loyalty_earn_ratio: parseInt(e.target.value) || 0 })}
                                        min={1000}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">Contoh: Rp 10.000 belanja = 1 poin</span>
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Nilai Tukar 1 Poin (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        value={settingsForm.loyalty_point_value}
                                        onChange={(e) => setSettingsForm({ ...settingsForm, loyalty_point_value: parseInt(e.target.value) || 0 })}
                                        min={1}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">Contoh: 1 poin bernilai diskon Rp 100</span>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Minimal Poin untuk Ditukar Saat Checkout
                                </label>
                                <input
                                    type="number"
                                    value={settingsForm.loyalty_min_redeem_points}
                                    onChange={(e) => setSettingsForm({ ...settingsForm, loyalty_min_redeem_points: parseInt(e.target.value) || 0 })}
                                    min={0}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold"
                                />
                            </div>

                            <div className="pt-3 border-t border-slate-100">
                                <h4 className="text-xs font-black uppercase text-slate-400 tracking-wider mb-3">
                                    Batas Akumulasi Belanja Naik Tier
                                </h4>

                                <div className="space-y-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                                            Tier SILVER (Min. Total Belanja Rp)
                                        </label>
                                        <input
                                            type="number"
                                            value={settingsForm.loyalty_tier_silver_spend}
                                            onChange={(e) => setSettingsForm({ ...settingsForm, loyalty_tier_silver_spend: parseFloat(e.target.value) || 0 })}
                                            className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                                            Tier GOLD (Min. Total Belanja Rp)
                                        </label>
                                        <input
                                            type="number"
                                            value={settingsForm.loyalty_tier_gold_spend}
                                            onChange={(e) => setSettingsForm({ ...settingsForm, loyalty_tier_gold_spend: parseFloat(e.target.value) || 0 })}
                                            className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-amber-700"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                                            Tier PLATINUM (Min. Total Belanja Rp)
                                        </label>
                                        <input
                                            type="number"
                                            value={settingsForm.loyalty_tier_platinum_spend}
                                            onChange={(e) => setSettingsForm({ ...settingsForm, loyalty_tier_platinum_spend: parseFloat(e.target.value) || 0 })}
                                            className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-purple-700"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-slate-100 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={isSavingSettings}
                                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-yellow-500 to-amber-600 hover:from-yellow-600 hover:to-amber-700 text-white text-xs font-bold shadow-md shadow-yellow-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSavingSettings && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Simpan Pengaturan Poin
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>

            {/* Modal Penyesuaian Poin */}
            {isAdjustModalOpen && targetAccount && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsAdjustModalOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-md w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-base font-black text-slate-900 tracking-tight">
                                    Penyesuaian Saldo Poin
                                </h3>
                                <p className="text-xs text-slate-500 font-mono">
                                    {targetAccount.phone_number} ({targetAccount.user_name})
                                </p>
                            </div>
                            <button
                                onClick={() => setIsAdjustModalOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitAdjust} className="space-y-4 mt-4">
                            <div className="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl text-xs font-bold">
                                <button
                                    type="button"
                                    onClick={() => setAdjustForm({ ...adjustForm, type: 'ADD' })}
                                    className={`py-2 rounded-lg flex items-center justify-center gap-1.5 transition-all ${
                                        adjustForm.type === 'ADD' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600'
                                    }`}
                                >
                                    <Plus className="w-3.5 h-3.5" />
                                    <span>Tambah Poin</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setAdjustForm({ ...adjustForm, type: 'DEDUCT' })}
                                    className={`py-2 rounded-lg flex items-center justify-center gap-1.5 transition-all ${
                                        adjustForm.type === 'DEDUCT' ? 'bg-rose-600 text-white shadow-2xs' : 'text-slate-600'
                                    }`}
                                >
                                    <Minus className="w-3.5 h-3.5" />
                                    <span>Kurangi Poin</span>
                                </button>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Jumlah Poin *</label>
                                <input
                                    type="number"
                                    value={adjustForm.points}
                                    onChange={(e) => setAdjustForm({ ...adjustForm, points: parseInt(e.target.value) || 0 })}
                                    required
                                    min={1}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-black text-slate-900"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Alasan Penyesuaian *</label>
                                <input
                                    type="text"
                                    value={adjustForm.description}
                                    onChange={(e) => setAdjustForm({ ...adjustForm, description: e.target.value })}
                                    required
                                    placeholder="Contoh: Reward closing CS / Kompensasi pesanan"
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setIsAdjustModalOpen(false)}
                                    className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmittingAdjust}
                                    className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-yellow-500 to-amber-600 text-white text-xs font-bold shadow-md shadow-yellow-500/20 disabled:opacity-50"
                                >
                                    {isSubmittingAdjust && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Terapkan Penyesuaian
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Buku Besar (Ledger History) */}
            {isHistoryModalOpen && historyAccount && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsHistoryModalOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-base font-black text-slate-900 tracking-tight">
                                    Buku Besar Transaksi Poin
                                </h3>
                                <p className="text-xs text-slate-500 font-mono">
                                    {historyAccount.phone_number} • Saldo Sekarang: <strong>{historyAccount.points_balance} poin</strong>
                                </p>
                            </div>
                            <button
                                onClick={() => setIsHistoryModalOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <div className="mt-4 max-h-96 overflow-y-auto">
                            {isLoadingHistory ? (
                                <div className="p-8 text-center text-slate-400 flex flex-col items-center gap-2">
                                    <Loader2 className="w-6 h-6 animate-spin text-yellow-500" />
                                    <span className="text-xs">Memuat riwayat transaksi...</span>
                                </div>
                            ) : transactions.length === 0 ? (
                                <div className="p-8 text-center text-slate-400">
                                    <p className="text-xs">Belum ada riwayat mutasi poin pada akun ini.</p>
                                </div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {transactions.map((tx) => (
                                        <div key={tx.id} className="py-2.5 flex items-center justify-between gap-3 text-xs">
                                            <div>
                                                <div className="font-bold text-slate-900">{tx.description}</div>
                                                <div className="text-[10.5px] text-slate-400 mt-0.5">
                                                    {tx.created_at} • Oleh: {tx.admin_name}
                                                </div>
                                            </div>

                                            <div className="text-right">
                                                {tx.points_in > 0 && (
                                                    <div className="font-extrabold text-emerald-600">
                                                        +{tx.points_in}
                                                    </div>
                                                )}
                                                {tx.points_out > 0 && (
                                                    <div className="font-extrabold text-rose-600">
                                                        -{tx.points_out}
                                                    </div>
                                                )}
                                                <div className="text-[10px] text-slate-400">
                                                    Sisa: {tx.balance_after}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="pt-4 border-t border-slate-100 flex justify-end">
                            <button
                                onClick={() => setIsHistoryModalOpen(false)}
                                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </EcommerceLayout>
    );
}
