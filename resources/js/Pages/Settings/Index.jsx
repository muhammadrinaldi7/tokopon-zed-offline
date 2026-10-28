import React, { useState, useEffect, useMemo } from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    Settings,
    Store,
    Phone,
    Truck,
    CreditCard,
    Save,
    Check,
    CheckCircle2,
    Layers,
    Tag,
    UserCheck,
    Building2,
    Loader2,
    Users,
    Search,
    Share2,
    ExternalLink,
    Filter,
    Percent,
    Sliders,
    Sparkles,
    ShieldCheck,
    ArrowUpRight
} from 'lucide-react';

export default function EcommerceSettingsIndex({
    globalSettings = {},
    referralSettings = {},
    selectedBuId = null,
    buSettings = {},
    warehouses = [],
    employees = [],
    users = []
}) {
    const pageProps = usePage().props || {};
    const businessUnits = pageProps.businessUnits || [];

    // Safe Array Coercions to prevent runtime type errors
    const buList = Array.isArray(businessUnits) ? businessUnits : Object.values(businessUnits || {});
    const warehouseList = Array.isArray(warehouses) ? warehouses : Object.values(warehouses || {});
    const employeeList = Array.isArray(employees) ? employees : Object.values(employees || {});
    const userList = Array.isArray(users) ? users : Object.values(users || {});
    const allCategoriesList = Array.isArray(buSettings?.all_categories)
        ? buSettings.all_categories
        : Object.values(buSettings?.all_categories || {});
    const allBrandsList = Array.isArray(buSettings?.all_brands)
        ? buSettings.all_brands
        : Object.values(buSettings?.all_brands || {});

    // Active Settings Tab ('store' | 'curation' | 'global' | 'referral')
    const [settingsTab, setSettingsTab] = useState('store');

    // 1. Form Global Settings
    const [globalForm, setGlobalForm] = useState({
        cs_phone: globalSettings?.cs_phone || '081298765432',
        dynamic_shipping: Boolean(globalSettings?.dynamic_shipping),
        instant_payment: Boolean(globalSettings?.instant_payment),
    });
    const [isSavingGlobal, setIsSavingGlobal] = useState(false);

    // 2. Form Business Unit Settings
    const [activeBuId, setActiveBuId] = useState(selectedBuId || buList?.[0]?.id || null);
    const [buForm, setBuForm] = useState({
        business_unit_id: activeBuId,
        online_warehouse_id: buSettings?.online_warehouse_id || '',
        default_branch_name: buSettings?.default_branch_name || 'Banjarbaru',
        default_sales_id: buSettings?.default_sales_id || '',
        default_handled_by_id: buSettings?.default_handled_by_id || '',
        active_categories: Array.isArray(buSettings?.active_categories) ? buSettings.active_categories : [],
        active_brands: Array.isArray(buSettings?.active_brands) ? buSettings.active_brands : [],
    });
    const [isSavingBu, setIsSavingBu] = useState(false);

    // 3. Form Referral & Komisi Settings
    const [referralForm, setReferralForm] = useState({
        referral_is_active: referralSettings?.referral_is_active ?? true,
        referral_commission_type: referralSettings?.referral_commission_type || 'PERCENTAGE',
        referral_commission_rate: referralSettings?.referral_commission_rate ?? 1.0,
        referral_reward_scope: referralSettings?.referral_reward_scope || 'FIRST_ORDER_ONLY',
        referral_max_commission_cap: referralSettings?.referral_max_commission_cap ?? 100000,
        referral_hold_days: referralSettings?.referral_hold_days ?? 7,
        referral_min_withdrawal: referralSettings?.referral_min_withdrawal ?? 50000,
    });
    const [isSavingReferral, setIsSavingReferral] = useState(false);

    // Live search filters for Category & Brand in Curation Tab
    const [categorySearch, setCategorySearch] = useState('');
    const [brandSearch, setBrandSearch] = useState('');

    // Sync form when props update
    useEffect(() => {
        if (selectedBuId) {
            setActiveBuId(selectedBuId);
        } else if (!activeBuId && buList?.length > 0) {
            setActiveBuId(buList[0].id);
        }
    }, [selectedBuId, businessUnits]);

    useEffect(() => {
        if (globalSettings) {
            setGlobalForm({
                cs_phone: globalSettings?.cs_phone || '081298765432',
                dynamic_shipping: Boolean(globalSettings?.dynamic_shipping),
                instant_payment: Boolean(globalSettings?.instant_payment),
            });
        }
    }, [globalSettings]);

    useEffect(() => {
        if (referralSettings) {
            setReferralForm({
                referral_is_active: referralSettings?.referral_is_active ?? true,
                referral_commission_type: referralSettings?.referral_commission_type || 'PERCENTAGE',
                referral_commission_rate: referralSettings?.referral_commission_rate ?? 1.0,
                referral_reward_scope: referralSettings?.referral_reward_scope || 'FIRST_ORDER_ONLY',
                referral_max_commission_cap: referralSettings?.referral_max_commission_cap ?? 100000,
                referral_hold_days: referralSettings?.referral_hold_days ?? 7,
                referral_min_withdrawal: referralSettings?.referral_min_withdrawal ?? 50000,
            });
        }
    }, [referralSettings]);

    useEffect(() => {
        setBuForm({
            business_unit_id: activeBuId,
            online_warehouse_id: buSettings?.online_warehouse_id || '',
            default_branch_name: buSettings?.default_branch_name || 'Banjarbaru',
            default_sales_id: buSettings?.default_sales_id || '',
            default_handled_by_id: buSettings?.default_handled_by_id || '',
            active_categories: Array.isArray(buSettings?.active_categories) ? buSettings.active_categories : [],
            active_brands: Array.isArray(buSettings?.active_brands) ? buSettings.active_brands : [],
        });
    }, [buSettings, activeBuId]);

    // Switch BU Tab
    const handleSwitchBu = (buId) => {
        setActiveBuId(buId);
        router.get(
            '/crm/settings',
            { bu: buId },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Save Global Settings
    const handleSaveGlobal = (e) => {
        e.preventDefault();
        setIsSavingGlobal(true);

        router.post('/crm/settings/global', globalForm, {
            onSuccess: () => setIsSavingGlobal(false),
            onError: () => setIsSavingGlobal(false),
        });
    };

    // Save BU Settings
    const handleSaveBu = (e) => {
        e.preventDefault();
        setIsSavingBu(true);

        router.post(
            '/crm/settings/business-unit',
            {
                ...buForm,
                business_unit_id: activeBuId,
            },
            {
                onSuccess: () => setIsSavingBu(false),
                onError: () => setIsSavingBu(false),
            }
        );
    };

    // Save Referral Settings
    const handleSaveReferral = (e) => {
        e.preventDefault();
        setIsSavingReferral(true);

        router.post('/crm/settings/referral', referralForm, {
            onSuccess: () => setIsSavingReferral(false),
            onError: () => setIsSavingReferral(false),
        });
    };

    // Toggle Category
    const handleToggleCategory = (catName) => {
        setBuForm((prev) => {
            const current = Array.isArray(prev.active_categories) ? prev.active_categories : [];
            if (current.includes(catName)) {
                return { ...prev, active_categories: current.filter((c) => c !== catName) };
            } else {
                return { ...prev, active_categories: [...current, catName] };
            }
        });
    };

    // Select All Categories
    const handleSelectAllCategories = () => {
        setBuForm((prev) => ({
            ...prev,
            active_categories: allCategoriesList.map((c) => c.name),
        }));
    };

    // Deselect All Categories
    const handleClearAllCategories = () => {
        setBuForm((prev) => ({
            ...prev,
            active_categories: [],
        }));
    };

    // Toggle Brand
    const handleToggleBrand = (brandName) => {
        setBuForm((prev) => {
            const current = Array.isArray(prev.active_brands) ? prev.active_brands : [];
            if (current.includes(brandName)) {
                return { ...prev, active_brands: current.filter((b) => b !== brandName) };
            } else {
                return { ...prev, active_brands: [...current, brandName] };
            }
        });
    };

    // Select All Brands
    const handleSelectAllBrands = () => {
        setBuForm((prev) => ({
            ...prev,
            active_brands: allBrandsList.map((b) => b.name),
        }));
    };

    // Deselect All Brands
    const handleClearAllBrands = () => {
        setBuForm((prev) => ({
            ...prev,
            active_brands: [],
        }));
    };

    // Filtered lists for live search
    const filteredCategories = useMemo(() => {
        if (!categorySearch.trim()) return allCategoriesList;
        return allCategoriesList.filter((c) =>
            c.name.toLowerCase().includes(categorySearch.toLowerCase())
        );
    }, [allCategoriesList, categorySearch]);

    const filteredBrands = useMemo(() => {
        if (!brandSearch.trim()) return allBrandsList;
        return allBrandsList.filter((b) =>
            b.name.toLowerCase().includes(brandSearch.toLowerCase())
        );
    }, [allBrandsList, brandSearch]);

    const currentBu = businessUnits?.find((b) => b.id === activeBuId) || businessUnits?.[0] || { name: 'Toko' };

    return (
        <EcommerceLayout title="Pengaturan App & Toko Online">
            <Head title="Pengaturan Aplikasi & Toko Online" />

            <div className="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header Toolbar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-purple-500/10 text-purple-600">
                                <Settings className="w-5 h-5 sm:w-6 sm:h-6" />
                            </span>
                            Pengaturan Aplikasi & Toko Online
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-1">
                            Konfigurasi gudang online, cabang Accurate, kurasi katalog mobile, dan sistem bagi hasil referral.
                        </p>
                    </div>

                    {/* Quick Link to Referral Hub */}
                    <div className="flex items-center gap-2">
                        <Link
                            href="/crm/referral"
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs transition-all"
                        >
                            <Users className="w-3.5 h-3.5 text-emerald-600" />
                            <span>Buka Hub Referral</span>
                            <ArrowUpRight className="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                {/* 2. Top Store Switcher Bar (Responsive Pill Carousel) */}
                <div className="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold shrink-0">
                            <Store className="w-4 h-4" />
                        </div>
                        <div>
                            <span className="text-[10px] font-bold uppercase text-slate-400 tracking-wider block">
                                Unit Usaha Aktif
                            </span>
                            <span className="text-xs sm:text-sm font-extrabold text-slate-900">
                                {currentBu?.name || 'Toko'}
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-1 sm:pb-0">
                        {buList?.map((bu) => (
                            <button
                                key={bu.id}
                                type="button"
                                onClick={() => handleSwitchBu(bu.id)}
                                className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 ${
                                    activeBuId === bu.id
                                        ? 'bg-purple-600 text-white shadow-2xs font-extrabold'
                                        : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200/60'
                                }`}
                            >
                                {bu.name}
                            </button>
                        ))}
                    </div>
                </div>

                {/* 3. Settings Navigation Tabs */}
                <div className="border-b border-slate-200 flex items-center gap-2 overflow-x-auto scrollbar-none pb-2 text-xs font-bold">
                    <button
                        type="button"
                        onClick={() => setSettingsTab('store')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            settingsTab === 'store'
                                ? 'bg-slate-900 text-white shadow-xs font-extrabold'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <Building2 className="w-3.5 h-3.5 text-purple-400" />
                        <span>Toko & Gudang Accurate</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setSettingsTab('curation')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            settingsTab === 'curation'
                                ? 'bg-slate-900 text-white shadow-xs font-extrabold'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <Layers className="w-3.5 h-3.5 text-indigo-400" />
                        <span>Kurasi Katalog Mobile</span>
                        <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-indigo-500/20 text-indigo-700">
                            {(buForm.active_categories?.length || 0) + (buForm.active_brands?.length || 0)}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setSettingsTab('global')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            settingsTab === 'global'
                                ? 'bg-slate-900 text-white shadow-xs font-extrabold'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <Sliders className="w-3.5 h-3.5 text-blue-400" />
                        <span>Integrasi & Kontak CS</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setSettingsTab('referral')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            settingsTab === 'referral'
                                ? 'bg-slate-900 text-white shadow-xs font-extrabold'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <Users className="w-3.5 h-3.5 text-emerald-400" />
                        <span>Referral & Komisi</span>
                        {referralForm.referral_is_active && (
                            <span className="text-[9px] px-1.5 py-0.2 rounded-full bg-emerald-500 text-white font-black">
                                AKTIF
                            </span>
                        )}
                    </button>
                </div>

                {/* 4. Tab Content 1: Toko & Gudang Accurate */}
                {settingsTab === 'store' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-7 shadow-2xs space-y-6">
                        <div className="flex items-center gap-3 pb-4 border-b border-slate-100">
                            <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                <Building2 className="w-5 h-5" />
                            </div>
                            <div>
                                <h2 className="text-base font-black text-slate-900 tracking-tight">
                                    Konfigurasi Toko & Integrasi Accurate ({currentBu?.name || 'Toko'})
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Tentukan gudang stok online, cabang Accurate, serta staf sales dan kasir default transaksi.
                                </p>
                            </div>
                        </div>

                        <form onSubmit={handleSaveBu} className="space-y-6">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                                {/* Gudang Online */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Gudang Online Store *
                                    </label>
                                    <select
                                        value={buForm.online_warehouse_id}
                                        onChange={(e) => setBuForm({ ...buForm, online_warehouse_id: e.target.value })}
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-purple-500/20"
                                    >
                                        <option value="">Pilih Gudang Online</option>
                                        {warehouseList?.map((wh) => (
                                            <option key={wh.id} value={wh.id}>
                                                {wh.name} {wh.is_online_store ? '(Online Aktif)' : ''}
                                            </option>
                                        ))}
                                    </select>
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Stok gudang ini akan menjadi acuan jumlah barang di aplikasi mobile.
                                    </span>
                                </div>

                                {/* Cabang Accurate */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Nama Cabang Accurate *
                                    </label>
                                    <input
                                        type="text"
                                        value={buForm.default_branch_name}
                                        onChange={(e) => setBuForm({ ...buForm, default_branch_name: e.target.value })}
                                        required
                                        placeholder="Contoh: Banjarbaru"
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-purple-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Nama cabang yang tercatat pada Sales Order saat sinkron ke Accurate.
                                    </span>
                                </div>

                                {/* Sales Default */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Sales Default Transaksi
                                    </label>
                                    <select
                                        value={buForm.default_sales_id}
                                        onChange={(e) => setBuForm({ ...buForm, default_sales_id: e.target.value })}
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-purple-500/20"
                                    >
                                        <option value="">Pilih Sales Default</option>
                                        {employeeList?.map((emp) => (
                                            <option key={emp.id} value={emp.id}>
                                                {emp.name}
                                            </option>
                                        ))}
                                    </select>
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Staf sales yang dikaitkan pada pesanan online masuk.
                                    </span>
                                </div>

                                {/* Kasir Default */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Kasir / Handled By Default
                                    </label>
                                    <select
                                        value={buForm.default_handled_by_id}
                                        onChange={(e) => setBuForm({ ...buForm, default_handled_by_id: e.target.value })}
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-purple-500/20"
                                    >
                                        <option value="">Pilih Kasir Default</option>
                                        {userList?.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name}
                                            </option>
                                        ))}
                                    </select>
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        User POS kasir yang menangani closing pesanan otomatis.
                                    </span>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-slate-100 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={isSavingBu}
                                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSavingBu ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                                    Simpan Pengaturan {currentBu?.name || 'Toko'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* 5. Tab Content 2: Kurasi Katalog Mobile */}
                {settingsTab === 'curation' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-7 shadow-2xs space-y-6">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                            <div>
                                <h2 className="text-base font-black text-slate-900 tracking-tight">
                                    Kurasi Kategori & Merek Produk ({currentBu?.name || 'Toko'})
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Pilih kategori dan brand produk yang ingin dimunculkan pada aplikasi mobile pembeli.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={handleSaveBu}
                                disabled={isSavingBu}
                                className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-500/20 disabled:opacity-50 transition-all"
                            >
                                {isSavingBu ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                                Simpan Kurasi Katalog
                            </button>
                        </div>

                        {/* Kurasi Kategori */}
                        <div className="space-y-3">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                <div className="flex items-center gap-2">
                                    <h3 className="text-xs font-black uppercase text-slate-700 tracking-wider">
                                        Kategori Produk Aktif
                                    </h3>
                                    <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                        {buForm.active_categories?.length || 0} dari {allCategoriesList.length} aktif
                                    </span>
                                </div>

                                <div className="flex items-center gap-2">
                                    {/* Search Input */}
                                    <div className="relative">
                                        <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                                        <input
                                            type="text"
                                            value={categorySearch}
                                            onChange={(e) => setCategorySearch(e.target.value)}
                                            placeholder="Cari kategori..."
                                            className="pl-8 pr-3 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:outline-none w-36 sm:w-48"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={handleSelectAllCategories}
                                        className="text-[11px] font-bold text-purple-600 hover:text-purple-800 px-2 py-1 hover:bg-purple-50 rounded-lg"
                                    >
                                        Pilih Semua
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleClearAllCategories}
                                        className="text-[11px] font-bold text-slate-400 hover:text-rose-600 px-2 py-1 hover:bg-rose-50 rounded-lg"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2 max-h-64 overflow-y-auto p-1 border border-slate-100 rounded-2xl bg-slate-50/50">
                                {filteredCategories.length === 0 ? (
                                    <p className="text-xs text-slate-400 italic p-3">
                                        Tidak ada kategori yang cocok dengan pencarian.
                                    </p>
                                ) : (
                                    filteredCategories.map((cat) => {
                                        const isSelected = Array.isArray(buForm.active_categories) && buForm.active_categories.includes(cat.name);
                                        return (
                                            <button
                                                key={cat.name}
                                                type="button"
                                                onClick={() => handleToggleCategory(cat.name)}
                                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 ${
                                                    isSelected
                                                        ? 'bg-purple-600 text-white shadow-2xs'
                                                        : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                                                }`}
                                            >
                                                <span>{cat.name}</span>
                                                <span
                                                    className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                                                        isSelected
                                                            ? 'bg-purple-700 text-white'
                                                            : 'bg-slate-100 text-slate-500'
                                                    }`}
                                                >
                                                    {cat.count}
                                                </span>
                                            </button>
                                        );
                                    })
                                )}
                            </div>
                        </div>

                        {/* Kurasi Brand */}
                        <div className="space-y-3 pt-4 border-t border-slate-100">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                <div className="flex items-center gap-2">
                                    <h3 className="text-xs font-black uppercase text-slate-700 tracking-wider">
                                        Brand / Merek Populer
                                    </h3>
                                    <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {buForm.active_brands?.length || 0} dari {allBrandsList.length} aktif
                                    </span>
                                </div>

                                <div className="flex items-center gap-2">
                                    {/* Search Input */}
                                    <div className="relative">
                                        <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                                        <input
                                            type="text"
                                            value={brandSearch}
                                            onChange={(e) => setBrandSearch(e.target.value)}
                                            placeholder="Cari brand..."
                                            className="pl-8 pr-3 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:outline-none w-36 sm:w-48"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={handleSelectAllBrands}
                                        className="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 px-2 py-1 hover:bg-indigo-50 rounded-lg"
                                    >
                                        Pilih Semua
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleClearAllBrands}
                                        className="text-[11px] font-bold text-slate-400 hover:text-rose-600 px-2 py-1 hover:bg-rose-50 rounded-lg"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2 max-h-64 overflow-y-auto p-1 border border-slate-100 rounded-2xl bg-slate-50/50">
                                {filteredBrands.length === 0 ? (
                                    <p className="text-xs text-slate-400 italic p-3">
                                        Tidak ada brand yang cocok dengan pencarian.
                                    </p>
                                ) : (
                                    filteredBrands.map((br) => {
                                        const isSelected = Array.isArray(buForm.active_brands) && buForm.active_brands.includes(br.name);
                                        return (
                                            <button
                                                key={br.name}
                                                type="button"
                                                onClick={() => handleToggleBrand(br.name)}
                                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 ${
                                                    isSelected
                                                        ? 'bg-indigo-600 text-white shadow-2xs'
                                                        : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                                                }`}
                                            >
                                                <span>{br.name}</span>
                                                <span
                                                    className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                                                        isSelected
                                                            ? 'bg-indigo-700 text-white'
                                                            : 'bg-slate-100 text-slate-500'
                                                    }`}
                                                >
                                                    {br.count}
                                                </span>
                                            </button>
                                        );
                                    })
                                )}
                            </div>
                        </div>

                        <div className="pt-4 border-t border-slate-100 flex justify-end">
                            <button
                                type="button"
                                onClick={handleSaveBu}
                                disabled={isSavingBu}
                                className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-500/20 disabled:opacity-50 transition-all"
                            >
                                {isSavingBu ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                                Simpan Kurasi {currentBu?.name || 'Toko'}
                            </button>
                        </div>
                    </div>
                )}

                {/* 6. Tab Content 3: Integrasi & Global */}
                {settingsTab === 'global' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-7 shadow-2xs space-y-6">
                        <div className="flex items-center gap-3 pb-4 border-b border-slate-100">
                            <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                <Sliders className="w-5 h-5" />
                            </div>
                            <div>
                                <h2 className="text-base font-black text-slate-900 tracking-tight">
                                    Pengaturan Fitur Global & WhatsApp CS
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Berlaku untuk seluruh operasional aplikasi mobile Tokopon.
                                </p>
                            </div>
                        </div>

                        <form onSubmit={handleSaveGlobal} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                                {/* Nomor WhatsApp */}
                                <div className="space-y-1">
                                    <label className="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                        <Phone className="w-3.5 h-3.5 text-blue-600" />
                                        Nomor WhatsApp CS / Call Center *
                                    </label>
                                    <input
                                        type="text"
                                        value={globalForm.cs_phone}
                                        onChange={(e) => setGlobalForm({ ...globalForm, cs_phone: e.target.value })}
                                        required
                                        placeholder="Contoh: 081298765432"
                                        className="w-full px-3.5 py-2.5 font-mono font-bold bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Tujuan panggilan/chat saat pembeli menekan 'Hubungi CS via WhatsApp'.
                                    </span>
                                </div>

                                {/* Ongkir Dinamis */}
                                <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="dynamic_shipping"
                                        checked={globalForm.dynamic_shipping}
                                        onChange={(e) => setGlobalForm({ ...globalForm, dynamic_shipping: e.target.checked })}
                                        className="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 mt-0.5"
                                    />
                                    <div>
                                        <label htmlFor="dynamic_shipping" className="text-xs font-bold text-slate-900 cursor-pointer block">
                                            Ongkir Dinamis (RajaOngkir/Kurir)
                                        </label>
                                        <p className="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                            Hitung ongkos kirim real-time JNE, J&T, SiCepat berdasarkan berat gram produk belanja.
                                        </p>
                                    </div>
                                </div>

                                {/* Pembayaran Instan */}
                                <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="instant_payment"
                                        checked={globalForm.instant_payment}
                                        onChange={(e) => setGlobalForm({ ...globalForm, instant_payment: e.target.checked })}
                                        className="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 mt-0.5"
                                    />
                                    <div>
                                        <label htmlFor="instant_payment" className="text-xs font-bold text-slate-900 cursor-pointer block">
                                            Pembayaran Instan (Payment Gateway)
                                        </label>
                                        <p className="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                            Otomatisasi verifikasi pembayaran dengan Midtrans / Xendit tanpa verifikasi mutasi manual.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-slate-100 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={isSavingGlobal}
                                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold shadow-md disabled:opacity-50 transition-all"
                                >
                                    {isSavingGlobal ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                                    Simpan Pengaturan Global
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* 7. Tab Content 4: Sistem Referral & Komisi */}
                {settingsTab === 'referral' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-7 shadow-2xs space-y-6">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                    <Users className="w-5 h-5" />
                                </div>
                                <div>
                                    <h2 className="text-base font-black text-slate-900 tracking-tight">
                                        Pengaturan Sistem Referral & Komisi Afiliasi
                                    </h2>
                                    <p className="text-xs text-slate-500">
                                        Atur persentase komisi bagi mitra yang membagikan link/kode referral aplikasi.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href="/crm/referral"
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition-all shrink-0"
                            >
                                <span>Kelola Pencairan Saldo</span>
                                <ArrowUpRight className="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <form onSubmit={handleSaveReferral} className="space-y-6">
                            {/* Toggle Status Aktif */}
                            <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    id="ref_active"
                                    checked={referralForm.referral_is_active}
                                    onChange={(e) => setReferralForm({ ...referralForm, referral_is_active: e.target.checked })}
                                    className="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 mt-0.5"
                                />
                                <div>
                                    <label htmlFor="ref_active" className="text-xs font-bold text-slate-900 cursor-pointer block">
                                        Aktifkan Program Referral & Komisi Pembeli
                                    </label>
                                    <p className="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                        Bila aktif, setiap pengguna mobile akan mendapatkan kode referral unik dan berhak atas komisi transaksi teman yang diajak.
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
                                        value={referralForm.referral_commission_type}
                                        onChange={(e) => setReferralForm({ ...referralForm, referral_commission_type: e.target.value })}
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    >
                                        <option value="PERCENTAGE">Persentase Belanja (%)</option>
                                        <option value="FIXED">Nominal Tetap (Rp)</option>
                                    </select>
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Persentase dihitung dari subtotal produk yang dibeli teman.
                                    </span>
                                </div>

                                {/* Nilai Komisi */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Besaran Komisi ({referralForm.referral_commission_type === 'PERCENTAGE' ? '%' : 'Rp'})
                                    </label>
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0.1"
                                        value={referralForm.referral_commission_rate}
                                        onChange={(e) => setReferralForm({ ...referralForm, referral_commission_rate: parseFloat(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Contoh: 1.0 (artinya 1% dari total nilai transaksi).
                                    </span>
                                </div>

                                {/* Scope Reward */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Cakupan Reward Komisi
                                    </label>
                                    <select
                                        value={referralForm.referral_reward_scope}
                                        onChange={(e) => setReferralForm({ ...referralForm, referral_reward_scope: e.target.value })}
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    >
                                        <option value="FIRST_ORDER_ONLY">Hanya Pesanan Pertama Pembeli</option>
                                        <option value="ALL_ORDERS">Setiap Pesanan Berulang (Lifetime)</option>
                                    </select>
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        FIRST_ORDER_ONLY direkomendasikan untuk efisiensi biaya promosi.
                                    </span>
                                </div>

                                {/* Maksimal Cap Komisi */}
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Maksimal Plafon Komisi per Order (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="5000"
                                        value={referralForm.referral_max_commission_cap}
                                        onChange={(e) => setReferralForm({ ...referralForm, referral_max_commission_cap: parseFloat(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Batas komisi tertinggi untuk 1 transaksi (misal Rp 100.000).
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
                                        value={referralForm.referral_hold_days}
                                        onChange={(e) => setReferralForm({ ...referralForm, referral_hold_days: parseInt(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Jumlah hari komisi ditahan (H+7) sebelum dapat ditarik member.
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
                                        value={referralForm.referral_min_withdrawal}
                                        onChange={(e) => setReferralForm({ ...referralForm, referral_min_withdrawal: parseFloat(e.target.value) || 0 })}
                                        required
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <span className="text-[10.5px] text-slate-400 mt-1 block">
                                        Ambang batas minimal penarikan oleh member di mobile app.
                                    </span>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-slate-100 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={isSavingReferral}
                                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSavingReferral ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                                    Simpan Pengaturan Referral & Komisi
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        </EcommerceLayout>
    );
}
