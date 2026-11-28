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
    ArrowUpRight,
    Repeat
} from 'lucide-react';

export default function EcommerceSettingsIndex({
    globalSettings = {},
    referralSettings = {},
    tradeInSettings = {},
    availableProyeks = ['INTER', 'NON-PROYEK', 'RESMI'],
    availableTradeInBrands = [{ name: 'APPLE', count: 0 }],
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
    const proyekOptions = Array.isArray(availableProyeks)
        ? availableProyeks
        : Object.values(availableProyeks || {});
    const tradeInBrandOptions = Array.isArray(availableTradeInBrands)
        ? availableTradeInBrands
        : Object.values(availableTradeInBrands || {});
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
        crm_auto_reopen_after_won: globalSettings?.crm_auto_reopen_after_won ?? true,
        crm_repeat_lead_cooldown_minutes: Number(globalSettings?.crm_repeat_lead_cooldown_minutes ?? 120),
        crm_auto_unassign_on_reopen: globalSettings?.crm_auto_unassign_on_reopen ?? true,
    });
    const [isSavingGlobal, setIsSavingGlobal] = useState(false);

    // 2. Form Business Unit Settings
    const [activeBuId, setActiveBuId] = useState(selectedBuId || buList?.[0]?.id || null);
    const mapWarehousesToForm = (list) =>
        (Array.isArray(list) ? list : []).map((wh) => ({
            id: wh.id,
            name: wh.name,
            allow_online_delivery: Boolean(wh.allow_online_delivery ?? wh.is_online_store),
            allow_store_pickup: Boolean(wh.allow_store_pickup),
            fulfillment_priority: Number(wh.fulfillment_priority ?? 100),
            city: wh.city || '',
            address: wh.address || '',
            phone: wh.phone || '',
            operating_hours: wh.operating_hours || '',
        }));

    const [buForm, setBuForm] = useState({
        business_unit_id: activeBuId,
        online_warehouse_id: buSettings?.online_warehouse_id || '',
        online_warehouses: mapWarehousesToForm(warehouseList),
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

    // 4. Form Pengaturan Dinamis API Trade-In & Up Harga Accurate
    const [tradeInForm, setTradeInForm] = useState({
        tradein_old_business_unit_ids: Array.isArray(tradeInSettings?.tradein_old_business_unit_ids)
            ? tradeInSettings.tradein_old_business_unit_ids.map(Number)
            : [2],
        tradein_old_price_source: tradeInSettings?.tradein_old_price_source || 'buy_fallback',
        tradein_old_allowed_proyeks: Array.isArray(tradeInSettings?.tradein_old_allowed_proyeks)
            ? tradeInSettings.tradein_old_allowed_proyeks
            : [],
        tradein_target_new_allowed_proyeks: Array.isArray(tradeInSettings?.tradein_target_new_allowed_proyeks)
            ? tradeInSettings.tradein_target_new_allowed_proyeks
            : [],
        tradein_target_second_allowed_proyeks: Array.isArray(tradeInSettings?.tradein_target_second_allowed_proyeks)
            ? tradeInSettings.tradein_target_second_allowed_proyeks
            : [],
        tradein_old_allowed_brands: Array.isArray(tradeInSettings?.tradein_old_allowed_brands)
            ? tradeInSettings.tradein_old_allowed_brands
            : [],
        tradein_target_second_allowed_brands: Array.isArray(tradeInSettings?.tradein_target_second_allowed_brands)
            ? tradeInSettings.tradein_target_second_allowed_brands
            : ['APPLE'],
        tradein_target_new_allowed_brands: Array.isArray(tradeInSettings?.tradein_target_new_allowed_brands)
            ? tradeInSettings.tradein_target_new_allowed_brands
            : [],
        tradein_group_old_devices: tradeInSettings?.tradein_group_old_devices ?? true,
        tradein_group_target_devices: tradeInSettings?.tradein_group_target_devices ?? true,
        tradein_second_price_source: tradeInSettings?.tradein_second_price_source || 'custom_fallback',
        tradein_second_accurate_markup_percent: Number(tradeInSettings?.tradein_second_accurate_markup_percent ?? 0),
        tradein_second_accurate_rounding: Number(tradeInSettings?.tradein_second_accurate_rounding ?? 0),
        tradein_old_price_strategy: tradeInSettings?.tradein_old_price_strategy || 'min',
        tradein_target_price_strategy: tradeInSettings?.tradein_target_price_strategy || 'min',
        tradein_exclude_accessories: tradeInSettings?.tradein_exclude_accessories ?? true,
    });
    const [isSavingTradeIn, setIsSavingTradeIn] = useState(false);

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
            online_warehouses: mapWarehousesToForm(warehouseList),
            default_branch_name: buSettings?.default_branch_name || 'Banjarbaru',
            default_sales_id: buSettings?.default_sales_id || '',
            default_handled_by_id: buSettings?.default_handled_by_id || '',
            active_categories: Array.isArray(buSettings?.active_categories) ? buSettings.active_categories : [],
            active_brands: Array.isArray(buSettings?.active_brands) ? buSettings.active_brands : [],
        });
    }, [buSettings, warehouses, activeBuId]);

    const handleUpdateWarehouseRow = (whId, field, value) => {
        setBuForm((prev) => ({
            ...prev,
            online_warehouses: (prev.online_warehouses || []).map((row) =>
                row.id === whId ? { ...row, [field]: value } : row
            ),
        }));
    };

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

    // Save Trade-In Settings
    const handleSaveTradeIn = (e) => {
        e.preventDefault();
        setIsSavingTradeIn(true);

        router.post('/crm/settings/trade-in', tradeInForm, {
            onSuccess: () => setIsSavingTradeIn(false),
            onError: () => setIsSavingTradeIn(false),
        });
    };

    const toggleTradeInBu = (buId) => {
        setTradeInForm((prev) => {
            const id = Number(buId);
            const curr = prev.tradein_old_business_unit_ids || [];
            const next = curr.includes(id) ? curr.filter((x) => x !== id) : [...curr, id];
            return { ...prev, tradein_old_business_unit_ids: next };
        });
    };

    const toggleTradeInProyek = (field, proyek) => {
        setTradeInForm((prev) => {
            const curr = Array.isArray(prev[field]) ? prev[field] : [];
            const next = curr.includes(proyek) ? curr.filter((x) => x !== proyek) : [...curr, proyek];
            return { ...prev, [field]: next };
        });
    };

    const tradeInSimulation = useMemo(() => {
        const sampleBase = 5000000;
        const markup = Math.max(0, Number(tradeInForm.tradein_second_accurate_markup_percent) || 0);
        const rounding = Math.max(0, Number(tradeInForm.tradein_second_accurate_rounding) || 0);
        let result = Math.round(sampleBase * (1 + markup / 100));
        if (markup > 0 && rounding > 0) {
            result = Math.ceil(result / rounding) * rounding;
        }
        return { sampleBase, markup, result };
    }, [tradeInForm.tradein_second_accurate_markup_percent, tradeInForm.tradein_second_accurate_rounding]);

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

                    <button
                        type="button"
                        onClick={() => setSettingsTab('tradein')}
                        className={`px-4 py-2 rounded-xl transition-all flex items-center gap-2 shrink-0 ${
                            settingsTab === 'tradein'
                                ? 'bg-slate-900 text-white shadow-xs font-extrabold'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/60'
                        }`}
                    >
                        <Repeat className="w-3.5 h-3.5 text-amber-400" />
                        <span>Trade-In &amp; Up Harga Accurate</span>
                        {Number(tradeInForm.tradein_second_accurate_markup_percent) > 0 && (
                            <span className="text-[9px] px-1.5 py-0.2 rounded-full bg-amber-500 text-white font-black">
                                +{tradeInForm.tradein_second_accurate_markup_percent}%
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
                            {/* Multi-Warehouse Matrix (Delivery & Pick Up) */}
                            <div className="space-y-2.5">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <label className="block text-xs font-black text-slate-800 uppercase tracking-wider">
                                            Multi-Warehouse Online (Delivery &amp; Store Pick Up)
                                        </label>
                                        <span className="text-[11px] text-slate-500">
                                            Centang lebih dari 1 gudang untuk Pool Delivery dan/atau cabang Ambil di Toko (Pick Up). Biarkan kosong untuk gudang Retur/Rusak/Service.
                                        </span>
                                    </div>
                                    <span className="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 self-start sm:self-center">
                                        {(buForm.online_warehouses || []).filter((w) => w.allow_online_delivery || w.allow_store_pickup).length} gudang aktif online
                                    </span>
                                </div>

                                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/40">
                                    <table className="min-w-full divide-y divide-slate-200/80 text-xs">
                                        <thead className="bg-slate-100/80 text-[10.5px] font-black uppercase text-slate-500">
                                            <tr>
                                                <th className="px-3.5 py-2.5 text-left">Gudang / Cabang</th>
                                                <th className="px-3 py-2.5 text-center">Delivery</th>
                                                <th className="px-3 py-2.5 text-center">Pick Up</th>
                                                <th className="px-3 py-2.5 text-center">Prioritas</th>
                                                <th className="px-3 py-2.5 text-left">Kota</th>
                                                <th className="px-3 py-2.5 text-left">Jam Buka &amp; Alamat Cabang</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 bg-white">
                                            {(buForm.online_warehouses || []).map((wh) => {
                                                const isActive = wh.allow_online_delivery || wh.allow_store_pickup;
                                                return (
                                                    <tr key={wh.id} className={isActive ? 'bg-purple-50/30' : ''}>
                                                        <td className="px-3.5 py-2.5 font-bold text-slate-800 whitespace-nowrap">
                                                            {wh.name}
                                                            {isActive && (
                                                                <span className="ml-1.5 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[9.5px] font-black">
                                                                    AKTIF
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-2.5 text-center">
                                                            <input
                                                                type="checkbox"
                                                                checked={Boolean(wh.allow_online_delivery)}
                                                                onChange={(e) => handleUpdateWarehouseRow(wh.id, 'allow_online_delivery', e.target.checked)}
                                                                className="w-4 h-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500 cursor-pointer"
                                                            />
                                                        </td>
                                                        <td className="px-3 py-2.5 text-center">
                                                            <input
                                                                type="checkbox"
                                                                checked={Boolean(wh.allow_store_pickup)}
                                                                onChange={(e) => handleUpdateWarehouseRow(wh.id, 'allow_store_pickup', e.target.checked)}
                                                                className="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                                            />
                                                        </td>
                                                        <td className="px-3 py-2.5 text-center">
                                                            <input
                                                                type="number"
                                                                min="1"
                                                                max="999"
                                                                value={wh.fulfillment_priority}
                                                                onChange={(e) => handleUpdateWarehouseRow(wh.id, 'fulfillment_priority', Number(e.target.value || 100))}
                                                                className="w-16 text-center px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold"
                                                            />
                                                        </td>
                                                        <td className="px-3 py-2.5">
                                                            <input
                                                                type="text"
                                                                value={wh.city}
                                                                onChange={(e) => handleUpdateWarehouseRow(wh.id, 'city', e.target.value)}
                                                                placeholder="Banjarbaru"
                                                                className="w-28 px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs"
                                                            />
                                                        </td>
                                                        <td className="px-3 py-2.5">
                                                            <div className="flex flex-col sm:flex-row gap-1.5">
                                                                <input
                                                                    type="text"
                                                                    value={wh.operating_hours}
                                                                    onChange={(e) => handleUpdateWarehouseRow(wh.id, 'operating_hours', e.target.value)}
                                                                    placeholder="09:00-21:00"
                                                                    className="w-28 px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs"
                                                                />
                                                                <input
                                                                    type="text"
                                                                    value={wh.address}
                                                                    onChange={(e) => handleUpdateWarehouseRow(wh.id, 'address', e.target.value)}
                                                                    placeholder="Alamat lengkap cabang..."
                                                                    className="flex-1 min-w-[160px] px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs"
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">

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

                            {/* Pengaturan Smart Cooldown Multi-Closing CRM */}
                            <div className="p-5 bg-indigo-50/60 rounded-2xl border border-indigo-200/80 space-y-4">
                                <div className="flex items-start justify-between gap-3 flex-wrap">
                                    <div>
                                        <h3 className="text-sm font-black text-indigo-950 flex items-center gap-2">
                                            <Repeat className="w-4 h-4 text-indigo-600" />
                                            Smart Cooldown Multi-Closing CRM (Repeat Order)
                                        </h3>
                                        <p className="text-xs text-indigo-900/75 mt-0.5">
                                            Atur jeda waktu otomatis pembuatan kartu peluang/transaksi baru ketika customer yang sudah <strong>WON / LOST</strong> mengirim chat lagi (mis. beli HP pagi oleh CS A, lalu malam beli lagi oleh CS B tanpa menimpa omset pagi).
                                        </p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="p-3.5 bg-white rounded-xl border border-indigo-100 flex items-start gap-2.5">
                                        <input
                                            type="checkbox"
                                            id="crm_auto_reopen_after_won"
                                            checked={Boolean(globalForm.crm_auto_reopen_after_won)}
                                            onChange={(e) =>
                                                setGlobalForm({
                                                    ...globalForm,
                                                    crm_auto_reopen_after_won: e.target.checked,
                                                })
                                            }
                                            className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5"
                                        />
                                        <div>
                                            <label
                                                htmlFor="crm_auto_reopen_after_won"
                                                className="text-xs font-bold text-slate-900 cursor-pointer block"
                                            >
                                                Aktifkan Auto-Spawn Peluang Baru
                                            </label>
                                            <p className="text-[11px] text-slate-500 mt-0.5">
                                                Otomatis buat kartu lead baru jika customer chat lagi melewati batas cooldown.
                                            </p>
                                        </div>
                                    </div>

                                    <div className="p-3.5 bg-white rounded-xl border border-indigo-100 space-y-2">
                                        <label className="block text-xs font-bold text-slate-900">
                                            Durasi Smart Cooldown (Menit)
                                        </label>
                                        <input
                                            type="number"
                                            min={0}
                                            max={10080}
                                            value={globalForm.crm_repeat_lead_cooldown_minutes}
                                            onChange={(e) =>
                                                setGlobalForm({
                                                    ...globalForm,
                                                    crm_repeat_lead_cooldown_minutes: Number(e.target.value || 0),
                                                })
                                            }
                                            className="w-full px-3 py-1.5 font-mono font-bold text-xs bg-slate-50 border border-slate-200 rounded-lg"
                                        />
                                        <div className="flex flex-wrap gap-1">
                                            {[
                                                { label: '30m', val: 30 },
                                                { label: '1 Jam', val: 60 },
                                                { label: '2 Jam', val: 120 },
                                                { label: '6 Jam', val: 360 },
                                                { label: '24 Jam', val: 1440 },
                                            ].map((preset) => (
                                                <button
                                                    key={preset.val}
                                                    type="button"
                                                    onClick={() =>
                                                        setGlobalForm({
                                                            ...globalForm,
                                                            crm_repeat_lead_cooldown_minutes: preset.val,
                                                        })
                                                    }
                                                    className={`px-2 py-0.5 rounded-md text-[10px] font-bold border ${
                                                        Number(globalForm.crm_repeat_lead_cooldown_minutes) === preset.val
                                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                                            : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-indigo-50'
                                                    }`}
                                                >
                                                    {preset.label}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="p-3.5 bg-white rounded-xl border border-indigo-100 flex items-start gap-2.5">
                                        <input
                                            type="checkbox"
                                            id="crm_auto_unassign_on_reopen"
                                            checked={Boolean(globalForm.crm_auto_unassign_on_reopen)}
                                            onChange={(e) =>
                                                setGlobalForm({
                                                    ...globalForm,
                                                    crm_auto_unassign_on_reopen: e.target.checked,
                                                })
                                            }
                                            className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5"
                                        />
                                        <div>
                                            <label
                                                htmlFor="crm_auto_unassign_on_reopen"
                                                className="text-xs font-bold text-slate-900 cursor-pointer block"
                                            >
                                                Lepas Handle Otomatis Saat Re-Open
                                            </label>
                                            <p className="text-[11px] text-slate-500 mt-0.5">
                                                Masuk ke antrean bebas agar CS shift berikutnya bisa langsung mengambil chat baru (kredit closing CS lama tetap terkunci).
                                            </p>
                                        </div>
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

                {/* 8. Tab Content 5: Trade-In & Up Harga Jual Accurate */}
                {settingsTab === 'tradein' && (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-7 shadow-2xs space-y-6">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                    <Repeat className="w-5 h-5" />
                                </div>
                                <div>
                                    <h2 className="text-base font-black text-slate-900">
                                        Pengaturan API Trade-In &amp; Up Harga Jual Accurate
                                    </h2>
                                    <p className="text-xs text-slate-500">
                                        Mengatur persentase up harga saat fallback ke harga Accurate, pembulatan harga, filter proyek, dan sumber harga unit Second.
                                    </p>
                                </div>
                            </div>

                            <a
                                href="/admin/buyback/devices"
                                className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-bold transition-all shrink-0"
                            >
                                <span>Kelola Excel Harga Trade-In</span>
                                <ArrowUpRight className="w-3.5 h-3.5" />
                            </a>
                        </div>

                        <form onSubmit={handleSaveTradeIn} className="space-y-6">
                            {/* Section 1: Sumber Harga Jual BU Second & Up Harga Jual Accurate (%) */}
                            <div className="p-4 sm:p-5 rounded-2xl border border-blue-200/80 bg-blue-50/40 space-y-4">
                                <div>
                                    <label className="block text-xs font-extrabold text-slate-800 mb-1.5">
                                        Sumber Harga Jual Unit Bisnis Second (BU 2)
                                    </label>
                                    <select
                                        value={tradeInForm.tradein_second_price_source}
                                        onChange={(e) =>
                                            setTradeInForm({
                                                ...tradeInForm,
                                                tradein_second_price_source: e.target.value,
                                            })
                                        }
                                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    >
                                        <option value="custom_fallback">
                                            Utamakan Custom Sell Price (Excel), Fallback ke Harga Accurate [Rekomendasi]
                                        </option>
                                        <option value="custom_only">
                                            Hanya Custom Sell Price (Excel) — Sembunyikan produk yang belum diatur di Excel
                                        </option>
                                        <option value="accurate_only">
                                            Selalu Gunakan Harga Real Accurate (base_price)
                                        </option>
                                    </select>
                                    <span className="text-[11px] text-slate-500 mt-1 block">
                                        Produk yang sudah memiliki <code>custom_sell_price</code> tetap memakai harga custom tanpa ditambah persentase up.
                                    </span>
                                </div>

                                <div
                                    className={`bg-white p-4 rounded-xl border border-blue-200/70 space-y-4 ${
                                        tradeInForm.tradein_second_price_source === 'custom_only' ? 'opacity-60' : ''
                                    }`}
                                >
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <h3 className="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                                <Percent className="w-3.5 h-3.5 text-blue-600" />
                                                Up Harga Jual Trade-In Accurate (%)
                                            </h3>
                                            <p className="text-[11px] text-slate-500 mt-0.5">
                                                Kenaikan otomatis khusus ketika harga jual diambil dari Accurate (<code>base_price</code>) karena tim operasional men-setting harga terendah di Accurate.
                                            </p>
                                        </div>
                                        {tradeInForm.tradein_second_price_source === 'custom_only' && (
                                            <span className="text-[10px] font-bold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-full shrink-0">
                                                Nonaktif pada mode Custom Only
                                            </span>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-xs font-bold text-slate-700 mb-1">
                                                Persentase Up Harga (%)
                                            </label>
                                            <div className="relative">
                                                <input
                                                    type="number"
                                                    step="0.5"
                                                    min="0"
                                                    max="200"
                                                    value={tradeInForm.tradein_second_accurate_markup_percent}
                                                    onChange={(e) =>
                                                        setTradeInForm({
                                                            ...tradeInForm,
                                                            tradein_second_accurate_markup_percent:
                                                                e.target.value === '' ? 0 : parseFloat(e.target.value),
                                                        })
                                                    }
                                                    className="w-full px-3.5 py-2.5 pr-10 bg-slate-50 border border-slate-200 rounded-xl text-xs font-black focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                                />
                                                <span className="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                                                    %
                                                </span>
                                            </div>
                                            <span className="text-[10.5px] text-slate-400 mt-1 block">
                                                Isi 0 jika tanpa up harga. Contoh: 10 berarti harga Accurate × 110%.
                                            </span>
                                        </div>

                                        <div>
                                            <label className="block text-xs font-bold text-slate-700 mb-1">
                                                Pembulatan Harga Setelah Up
                                            </label>
                                            <select
                                                value={tradeInForm.tradein_second_accurate_rounding}
                                                onChange={(e) =>
                                                    setTradeInForm({
                                                        ...tradeInForm,
                                                        tradein_second_accurate_rounding: parseInt(e.target.value, 10) || 0,
                                                    })
                                                }
                                                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                            >
                                                <option value={0}>Tanpa Pembulatan Kelipatan (Persis)</option>
                                                <option value={1000}>Bulatkan ke atas per Rp 1.000</option>
                                                <option value={10000}>Bulatkan ke atas per Rp 10.000</option>
                                                <option value={50000}>Bulatkan ke atas per Rp 50.000</option>
                                                <option value={100000}>Bulatkan ke atas per Rp 100.000</option>
                                            </select>
                                            <span className="text-[10.5px] text-slate-400 mt-1 block">
                                                Membuat hasil up persentase tetap rapi (misal Rp 5.500.000).
                                            </span>
                                        </div>
                                    </div>

                                    {/* Live Simulation Bar */}
                                    <div className="flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                                        <span className="text-slate-600">
                                            Simulasi Harga Accurate{' '}
                                            <strong>Rp {tradeInSimulation.sampleBase.toLocaleString('id-ID')}</strong>
                                            {tradeInSimulation.markup > 0 ? (
                                                <>
                                                    {' '}
                                                    + Up <strong className="text-blue-600">{tradeInSimulation.markup}%</strong>
                                                </>
                                            ) : (
                                                ' (Tanpa Up)'
                                            )}
                                        </span>
                                        <span className="font-black text-blue-700 text-sm">
                                            → Rp {tradeInSimulation.result.toLocaleString('id-ID')}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Section 2: Business Unit Acuan HP Lama */}
                            <div className="p-4 sm:p-5 rounded-2xl border border-emerald-200/80 bg-emerald-50/30 space-y-4">
                                <div>
                                    <h3 className="text-xs font-black text-slate-800">
                                        Business Unit Acuan Daftar &amp; Harga Beli HP Lama (/old-devices)
                                    </h3>
                                    <p className="text-[11px] text-slate-500 mt-0.5">
                                        Pilih unit bisnis yang dijadikan sumber acuan Buy Price untuk HP lama customer.
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    {buList.map((bu) => {
                                        const checked = (tradeInForm.tradein_old_business_unit_ids || []).includes(Number(bu.id));
                                        return (
                                            <label
                                                key={bu.id}
                                                className={`flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all ${
                                                    checked
                                                        ? 'bg-white border-emerald-500 shadow-2xs'
                                                        : 'bg-white/70 border-slate-200 hover:border-emerald-300'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={() => toggleTradeInBu(bu.id)}
                                                    className="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                                                />
                                                <span className="text-xs font-bold text-slate-800">
                                                    {bu.name}{' '}
                                                    <span className="text-[10px] font-mono text-slate-400">
                                                        (#{bu.id})
                                                    </span>
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Sumber Harga Beli HP Lama (Buy Price)
                                    </label>
                                    <select
                                        value={tradeInForm.tradein_old_price_source}
                                        onChange={(e) =>
                                            setTradeInForm({
                                                ...tradeInForm,
                                                tradein_old_price_source: e.target.value,
                                            })
                                        }
                                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    >
                                        <option value="buy_fallback">
                                            Utamakan Buy Price (Excel), Fallback ke Harga Accurate [Default]
                                        </option>
                                        <option value="buy_only">
                                            Hanya perangkat yang sudah memiliki Buy Price &gt; 0
                                        </option>
                                    </select>
                                </div>
                            </div>

                            {/* Section 3: Filter Proyek Whitelist */}
                            <div className="p-4 sm:p-5 rounded-2xl border border-violet-200/80 bg-violet-50/30 space-y-3">
                                <div>
                                    <h3 className="text-xs font-black text-slate-800">
                                        Filter Proyek yang Ditampilkan di API Trade-In
                                    </h3>
                                    <p className="text-[11px] text-slate-500 mt-0.5">
                                        Centang proyek tertentu jika ingin membatasi (misal hanya RESMI &amp; INTER). Kosongkan untuk menampilkan semua proyek.
                                    </p>
                                </div>

                                {[
                                    { key: 'tradein_old_allowed_proyeks', label: '1. Proyek HP Lama (/old-devices)' },
                                    { key: 'tradein_target_second_allowed_proyeks', label: '2. Proyek HP Tujuan Second (target_type=second)' },
                                    { key: 'tradein_target_new_allowed_proyeks', label: '3. Proyek HP Tujuan Baru (target_type=new)' },
                                ].map((group) => {
                                    const selectedList = tradeInForm[group.key] || [];
                                    return (
                                        <div key={group.key} className="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-[11px] font-bold text-slate-700">{group.label}</span>
                                                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700">
                                                    {selectedList.length > 0 ? `${selectedList.length} Dipilih` : 'Semua Proyek'}
                                                </span>
                                            </div>
                                            <div className="flex flex-wrap gap-1.5">
                                                {proyekOptions.map((pr) => {
                                                    const isOn = selectedList.includes(pr);
                                                    return (
                                                        <button
                                                            key={pr}
                                                            type="button"
                                                            onClick={() => toggleTradeInProyek(group.key, pr)}
                                                            className={`px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all ${
                                                                isOn
                                                                    ? 'bg-violet-600 text-white border-violet-600'
                                                                    : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-violet-300'
                                                            }`}
                                                        >
                                                            {pr}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Section 3.5: Filter Brand Trade-In Dinamis */}
                            <div className="p-4 sm:p-5 rounded-2xl border border-sky-200/80 bg-sky-50/30 space-y-3">
                                <div>
                                    <h3 className="text-xs font-black text-slate-800">
                                        Filter Brand yang Ditampilkan di API Trade-In
                                    </h3>
                                    <p className="text-[11px] text-slate-500 mt-0.5">
                                        Aktifkan brand tertentu (misal APPLE &amp; SAMSUNG untuk HP Second). Kosongkan pilihan jika ingin menampilkan semua brand yang memiliki harga.
                                    </p>
                                </div>

                                {[
                                    { key: 'tradein_target_second_allowed_brands', label: '1. Brand HP Tujuan Second (type=second & target_type=second)' },
                                    { key: 'tradein_old_allowed_brands', label: '2. Brand HP Lama Customer (type=old & /old-devices)' },
                                    { key: 'tradein_target_new_allowed_brands', label: '3. Brand HP Tujuan Baru (type=new & target_type=new)' },
                                ].map((group) => {
                                    const selectedList = tradeInForm[group.key] || [];
                                    return (
                                        <div key={group.key} className="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-[11px] font-bold text-slate-700">{group.label}</span>
                                                <div className="flex items-center gap-1.5">
                                                    {selectedList.length > 0 && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setTradeInForm((prev) => ({ ...prev, [group.key]: [] }))
                                                            }
                                                            className="text-[10px] font-bold text-rose-600 hover:underline"
                                                        >
                                                            Reset (Semua)
                                                        </button>
                                                    )}
                                                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">
                                                        {selectedList.length > 0 ? `${selectedList.length} Brand Dipilih` : 'Semua Brand'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-1.5">
                                                {tradeInBrandOptions.map((br) => {
                                                    const brandName = typeof br === 'string' ? br : br.name;
                                                    const brandCount = typeof br === 'object' ? br.count : null;
                                                    const isOn = selectedList.includes(brandName);
                                                    return (
                                                        <button
                                                            key={brandName}
                                                            type="button"
                                                            onClick={() => toggleTradeInProyek(group.key, brandName)}
                                                            className={`px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all ${
                                                                isOn
                                                                    ? 'bg-sky-600 text-white border-sky-600'
                                                                    : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-sky-300'
                                                            }`}
                                                        >
                                                            {brandName}
                                                            {brandCount !== null && (
                                                                <span className={`ml-1 text-[10px] ${isOn ? 'text-sky-100' : 'text-slate-400'}`}>
                                                                    ({brandCount})
                                                                </span>
                                                            )}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Section 4: Grouping, Strategi Harga & Exclude Aksesoris */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label className="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={tradeInForm.tradein_group_old_devices}
                                        onChange={(e) =>
                                            setTradeInForm({ ...tradeInForm, tradein_group_old_devices: e.target.checked })
                                        }
                                        className="w-4 h-4 rounded text-blue-600 border-slate-300 mt-0.5"
                                    />
                                    <div>
                                        <span className="block text-xs font-bold text-slate-800">Ringkas HP Lama Tanpa Warna</span>
                                        <span className="block text-[11px] text-slate-500 mt-0.5">
                                            Gabungkan varian warna di <code>/old-devices</code> per Model + Storage + Proyek.
                                        </span>
                                    </div>
                                </label>

                                <label className="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={tradeInForm.tradein_group_target_devices}
                                        onChange={(e) =>
                                            setTradeInForm({ ...tradeInForm, tradein_group_target_devices: e.target.checked })
                                        }
                                        className="w-4 h-4 rounded text-blue-600 border-slate-300 mt-0.5"
                                    />
                                    <div>
                                        <span className="block text-xs font-bold text-slate-800">Ringkas HP Tujuan Tanpa Warna</span>
                                        <span className="block text-[11px] text-slate-500 mt-0.5">
                                            Gabungkan varian warna di <code>/target-devices</code> per Model + Storage + Proyek.
                                        </span>
                                    </div>
                                </label>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Strategi Harga Beli Grup HP Lama
                                    </label>
                                    <select
                                        value={tradeInForm.tradein_old_price_strategy}
                                        onChange={(e) =>
                                            setTradeInForm({ ...tradeInForm, tradein_old_price_strategy: e.target.value })
                                        }
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold"
                                    >
                                        <option value="min">Harga Terendah (Min) antar warna</option>
                                        <option value="max">Harga Tertinggi (Max) antar warna</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Strategi Harga Jual Grup HP Tujuan
                                    </label>
                                    <select
                                        value={tradeInForm.tradein_target_price_strategy}
                                        onChange={(e) =>
                                            setTradeInForm({ ...tradeInForm, tradein_target_price_strategy: e.target.value })
                                        }
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold"
                                    >
                                        <option value="min">Harga Terendah (Min — Mulai Dari) antar warna</option>
                                        <option value="max">Harga Tertinggi (Max) antar warna</option>
                                    </select>
                                </div>
                            </div>

                            <label className="flex items-start gap-3 p-3.5 rounded-xl border border-amber-200 bg-amber-50/40 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={tradeInForm.tradein_exclude_accessories}
                                    onChange={(e) =>
                                        setTradeInForm({ ...tradeInForm, tradein_exclude_accessories: e.target.checked })
                                    }
                                    className="w-4 h-4 rounded text-amber-600 border-slate-300 mt-0.5"
                                />
                                <div>
                                    <span className="block text-xs font-bold text-slate-800">
                                        Otomatis Sembunyikan Aksesoris Nyasar di Kategori HP
                                    </span>
                                    <span className="block text-[11px] text-slate-500 mt-0.5">
                                        Menyembunyikan item proyek ACCESSORIES, bertanda is_addon, atau diawali CASE / TEMPERED.
                                    </span>
                                </div>
                            </label>

                            <div className="pt-4 border-t border-slate-100 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={isSavingTradeIn}
                                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-xs font-bold shadow-md shadow-amber-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSavingTradeIn ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                                    Simpan Pengaturan Trade-In &amp; Up Harga
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        </EcommerceLayout>
    );
}
