import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
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
    Loader2
} from 'lucide-react';

export default function EcommerceSettingsIndex({
    globalSettings = {},
    selectedBuId = null,
    buSettings = {},
    warehouses = [],
    employees = [],
    users = []
}) {
    const { businessUnits } = usePage().props;

    // Form Global Settings
    const [globalForm, setGlobalForm] = useState({
        cs_phone: globalSettings?.cs_phone || '081298765432',
        dynamic_shipping: globalSettings?.dynamic_shipping ?? false,
        instant_payment: globalSettings?.instant_payment ?? false,
    });
    const [isSavingGlobal, setIsSavingGlobal] = useState(false);

    // Form Business Unit Settings
    const [activeBuId, setActiveBuId] = useState(selectedBuId || businessUnits?.[0]?.id);
    const [buForm, setBuForm] = useState({
        business_unit_id: activeBuId,
        online_warehouse_id: buSettings?.online_warehouse_id || '',
        default_branch_name: buSettings?.default_branch_name || 'Banjarbaru',
        default_sales_id: buSettings?.default_sales_id || '',
        default_handled_by_id: buSettings?.default_handled_by_id || '',
        active_categories: buSettings?.active_categories || [],
        active_brands: buSettings?.active_brands || [],
    });
    const [isSavingBu, setIsSavingBu] = useState(false);

    // Switch BU Tab
    const handleSwitchBu = (buId) => {
        setActiveBuId(buId);
        router.get(
            '/crm/settings',
            { bu: buId },
            { preserveState: false, preserveScroll: true }
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

    // Toggle Category
    const handleToggleCategory = (catName) => {
        setBuForm((prev) => {
            const current = prev.active_categories || [];
            if (current.includes(catName)) {
                return { ...prev, active_categories: current.filter((c) => c !== catName) };
            } else {
                return { ...prev, active_categories: [...current, catName] };
            }
        });
    };

    // Toggle Brand
    const handleToggleBrand = (brandName) => {
        setBuForm((prev) => {
            const current = prev.active_brands || [];
            if (current.includes(brandName)) {
                return { ...prev, active_brands: current.filter((b) => b !== brandName) };
            } else {
                return { ...prev, active_brands: [...current, brandName] };
            }
        });
    };

    const currentBu = businessUnits?.find((b) => b.id === activeBuId) || businessUnits?.[0];

    return (
        <EcommerceLayout title="Pengaturan E-Commerce">
            <Head title="Pengaturan Aplikasi & Toko Online" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-8">
                {/* 1. Header Toolbar */}
                <div>
                    <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span className="p-2 rounded-xl bg-slate-500/10 text-slate-700">
                            <Settings className="w-6 h-6" />
                        </span>
                        Pengaturan Aplikasi & Toko Online
                    </h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Konfigurasikan integrasi sistem penjualan, gudang online, kontak CS, dan kurasi kategori mobile.
                    </p>
                </div>

                {/* 2. Global Features Card */}
                <div className="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-2xs">
                    <div className="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            ⚡
                        </div>
                        <div>
                            <h2 className="text-base font-black text-slate-900 tracking-tight">
                                Pengaturan Global Fitur Aplikasi
                            </h2>
                            <p className="text-xs text-slate-500">
                                Berlaku untuk seluruh aplikasi mobile Tokopon.
                            </p>
                        </div>
                    </div>

                    <form onSubmit={handleSaveGlobal} className="mt-5 space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <Phone className="w-3.5 h-3.5 text-indigo-600" />
                                    Nomor WhatsApp CS / Call Center
                                </label>
                                <input
                                    type="text"
                                    value={globalForm.cs_phone}
                                    onChange={(e) => setGlobalForm({ ...globalForm, cs_phone: e.target.value })}
                                    required
                                    placeholder="Contoh: 081298765432"
                                    className="w-full px-3.5 py-2 font-mono font-bold bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <span className="text-[10.5px] text-slate-400 mt-1 block">
                                    Digunakan saat pembeli menekan tombol 'Hubungi CS via WhatsApp'.
                                </span>
                            </div>

                            <div className="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    id="dynamic_shipping"
                                    checked={globalForm.dynamic_shipping}
                                    onChange={(e) => setGlobalForm({ ...globalForm, dynamic_shipping: e.target.checked })}
                                    className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5"
                                />
                                <div>
                                    <label htmlFor="dynamic_shipping" className="text-xs font-bold text-slate-800 cursor-pointer block">
                                        Ongkir Dinamis (RajaOngkir/Kurir)
                                    </label>
                                    <p className="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Hitung ongkos kirim real-time JNE, J&T, SiCepat berdasarkan berat gram produk.
                                    </p>
                                </div>
                            </div>

                            <div className="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    id="instant_payment"
                                    checked={globalForm.instant_payment}
                                    onChange={(e) => setGlobalForm({ ...globalForm, instant_payment: e.target.checked })}
                                    className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5"
                                />
                                <div>
                                    <label htmlFor="instant_payment" className="text-xs font-bold text-slate-800 cursor-pointer block">
                                        Pembayaran Instan (Payment Gateway)
                                    </label>
                                    <p className="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Otomatisasi verifikasi pembayaran dengan Midtrans / Xendit tanpa cek mutasi manual.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="pt-3 flex justify-end">
                            <button
                                type="submit"
                                disabled={isSavingGlobal}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold shadow-md disabled:opacity-50 transition-all"
                            >
                                {isSavingGlobal ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                                Simpan Pengaturan Global
                            </button>
                        </div>
                    </form>
                </div>

                {/* 3. Business Unit Specific Settings */}
                <div className="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-2xs space-y-6">
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                <Store className="w-5 h-5" />
                            </div>
                            <div>
                                <h2 className="text-base font-black text-slate-900 tracking-tight">
                                    Pengaturan Spesifik Unit Usaha Toko
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Gudang online, default sales, kasir, dan kurasi kategori untuk toko terpilih.
                                </p>
                            </div>
                        </div>

                        {/* Store Switcher Tabs */}
                        <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold">
                            {businessUnits?.map((bu) => (
                                <button
                                    key={bu.id}
                                    type="button"
                                    onClick={() => handleSwitchBu(bu.id)}
                                    className={`px-4 py-2 rounded-xl transition-all ${
                                        activeBuId === bu.id
                                            ? 'bg-purple-600 text-white shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    {bu.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    <form onSubmit={handleSaveBu} className="space-y-6">
                        {/* Integration Mapping */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Gudang Online Store *</label>
                                <select
                                    value={buForm.online_warehouse_id}
                                    onChange={(e) => setBuForm({ ...buForm, online_warehouse_id: e.target.value })}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-purple-500/20"
                                >
                                    <option value="">Pilih Gudang Online</option>
                                    {warehouses?.map((wh) => (
                                        <option key={wh.id} value={wh.id}>
                                            {wh.name} {wh.is_online_store ? '(Online Aktif)' : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Nama Cabang Accurate *</label>
                                <input
                                    type="text"
                                    value={buForm.default_branch_name}
                                    onChange={(e) => setBuForm({ ...buForm, default_branch_name: e.target.value })}
                                    required
                                    placeholder="Banjarbaru"
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Sales Default Transaksi</label>
                                <select
                                    value={buForm.default_sales_id}
                                    onChange={(e) => setBuForm({ ...buForm, default_sales_id: e.target.value })}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium"
                                >
                                    <option value="">Pilih Sales Default</option>
                                    {employees?.map((emp) => (
                                        <option key={emp.id} value={emp.id}>
                                            {emp.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Kasir / Handled By Default</label>
                                <select
                                    value={buForm.default_handled_by_id}
                                    onChange={(e) => setBuForm({ ...buForm, default_handled_by_id: e.target.value })}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium"
                                >
                                    <option value="">Pilih Kasir Default</option>
                                    {users?.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Kurasi Kategori Produk */}
                        <div className="pt-4 border-t border-slate-100">
                            <div className="flex items-center justify-between mb-3">
                                <div>
                                    <h3 className="text-xs font-black uppercase text-slate-400 tracking-wider">
                                        Kurasi Kategori Produk yang Tampil di Mobile ({currentBu?.name})
                                    </h3>
                                    <p className="text-[11px] text-slate-500">
                                        Klik untuk menyalakan/mematikan kategori yang ingin dimunculkan pada menu belanja pembeli.
                                    </p>
                                </div>
                                <span className="text-xs font-bold text-purple-600">
                                    {buForm.active_categories.length} kategori aktif
                                </span>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {buSettings?.all_categories?.length === 0 ? (
                                    <p className="text-xs text-slate-400 italic">Tidak ada kategori produk terdaftar di toko ini.</p>
                                ) : (
                                    buSettings?.all_categories?.map((cat) => {
                                        const isSelected = buForm.active_categories.includes(cat.name);
                                        return (
                                            <button
                                                key={cat.name}
                                                type="button"
                                                onClick={() => handleToggleCategory(cat.name)}
                                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 ${
                                                    isSelected
                                                        ? 'bg-purple-600 text-white shadow-2xs'
                                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                                }`}
                                            >
                                                <span>{cat.name}</span>
                                                <span className={`text-[10px] px-1.5 py-0.2 rounded-full ${isSelected ? 'bg-purple-700 text-white' : 'bg-slate-200 text-slate-500'}`}>
                                                    {cat.count}
                                                </span>
                                            </button>
                                        );
                                    })
                                )}
                            </div>
                        </div>

                        {/* Kurasi Brand / Merek */}
                        <div className="pt-4 border-t border-slate-100">
                            <div className="flex items-center justify-between mb-3">
                                <div>
                                    <h3 className="text-xs font-black uppercase text-slate-400 tracking-wider">
                                        Kurasi Brand / Merek Populer ({currentBu?.name})
                                    </h3>
                                    <p className="text-[11px] text-slate-500">
                                        Merek yang terpilih akan tampil di filter cepat beranda aplikasi.
                                    </p>
                                </div>
                                <span className="text-xs font-bold text-purple-600">
                                    {buForm.active_brands.length} brand aktif
                                </span>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {buSettings?.all_brands?.length === 0 ? (
                                    <p className="text-xs text-slate-400 italic">Tidak ada brand terdaftar di toko ini.</p>
                                ) : (
                                    buSettings?.all_brands?.map((br) => {
                                        const isSelected = buForm.active_brands.includes(br.name);
                                        return (
                                            <button
                                                key={br.name}
                                                type="button"
                                                onClick={() => handleToggleBrand(br.name)}
                                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 ${
                                                    isSelected
                                                        ? 'bg-indigo-600 text-white shadow-2xs'
                                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                                }`}
                                            >
                                                <span>{br.name}</span>
                                                <span className={`text-[10px] px-1.5 py-0.2 rounded-full ${isSelected ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-500'}`}>
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
                                type="submit"
                                disabled={isSavingBu}
                                className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-purple-500/20 disabled:opacity-50 transition-all"
                            >
                                {isSavingBu ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                                Simpan Pengaturan {currentBu?.name}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </EcommerceLayout>
    );
}
