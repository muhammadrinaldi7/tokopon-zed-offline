import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    Ticket,
    Plus,
    Search,
    Filter,
    Copy,
    Check,
    Calendar,
    Users,
    TrendingUp,
    Sparkles,
    AlertCircle,
    X,
    Loader2,
    Edit2,
    Trash2,
    ToggleLeft,
    ToggleRight,
    Store,
    ExternalLink,
    Percent,
    DollarSign,
    Clock,
    ShoppingBag
} from 'lucide-react';
import axios from 'axios';

export default function VouchersIndex({
    coupons = { data: [] },
    stats = {},
    filters = {},
    couponTypes = []
}) {
    const { businessUnits } = usePage().props;

    // Filter states
    const [search, setSearch] = useState(filters?.search || '');
    const [selectedType, setSelectedType] = useState(filters?.type || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || 'all');
    const [selectedBu, setSelectedBu] = useState(filters?.bu || '');
    const [copiedCode, setCopiedCode] = useState(null);

    // Modal Form State (Create / Edit)
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingCoupon, setEditingCoupon] = useState(null);
    const [formData, setFormData] = useState({
        code: '',
        title: '',
        description: '',
        type: 'PRODUCT_DISCOUNT_FIXED',
        discount_value: 10000,
        max_discount_amount: '',
        min_purchase_amount: 50000,
        usage_limit_total: '',
        usage_limit_per_user: 1,
        start_date: '',
        end_date: '',
        business_unit_id: '',
        is_active: true,
    });
    const [formErrors, setFormErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Redemptions History Modal State
    const [isRedemptionsOpen, setIsRedemptionsOpen] = useState(false);
    const [selectedCouponRedemptions, setSelectedCouponRedemptions] = useState(null);
    const [redemptionsList, setRedemptionsList] = useState([]);
    const [isLoadingRedemptions, setIsLoadingRedemptions] = useState(false);

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
            '/crm/vouchers',
            {
                search,
                type: selectedType,
                status: selectedStatus,
                bu: selectedBu,
                ...newFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleCopy = (code) => {
        navigator.clipboard.writeText(code);
        setCopiedCode(code);
        setTimeout(() => setCopiedCode(null), 2000);
    };

    // Open Form for Create
    const handleOpenCreate = () => {
        const now = new Date();
        const thirtyDaysLater = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000);

        setEditingCoupon(null);
        setFormData({
            code: 'PROMO' + Math.floor(100 + Math.random() * 900),
            title: 'Kupon Promo Spesial Tokopon',
            description: 'Gunakan kupon ini saat checkout untuk potongan harga ekstra.',
            type: 'PRODUCT_DISCOUNT_FIXED',
            discount_value: 25000,
            max_discount_amount: '',
            min_purchase_amount: 250000,
            usage_limit_total: 100,
            usage_limit_per_user: 1,
            start_date: now.toISOString().slice(0, 16),
            end_date: thirtyDaysLater.toISOString().slice(0, 16),
            business_unit_id: '',
            is_active: true,
        });
        setFormErrors({});
        setIsFormOpen(true);
    };

    // Open Form for Edit
    const handleOpenEdit = (coupon) => {
        setEditingCoupon(coupon);
        setFormData({
            code: coupon.code,
            title: coupon.title,
            description: coupon.description || '',
            type: coupon.type,
            discount_value: coupon.discount_value,
            max_discount_amount: coupon.max_discount_amount || '',
            min_purchase_amount: coupon.min_purchase_amount || 0,
            usage_limit_total: coupon.usage_limit_total || '',
            usage_limit_per_user: coupon.usage_limit_per_user || 1,
            start_date: coupon.start_date_iso || '',
            end_date: coupon.end_date_iso || '',
            business_unit_id: coupon.business_unit_id || '',
            is_active: coupon.is_active,
        });
        setFormErrors({});
        setIsFormOpen(true);
    };

    // Submit Form
    const handleSubmitForm = (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setFormErrors({});

        if (editingCoupon) {
            router.put(`/crm/vouchers/${editingCoupon.id}`, formData, {
                onSuccess: () => {
                    setIsFormOpen(false);
                    setIsSubmitting(false);
                },
                onError: (errors) => {
                    setFormErrors(errors);
                    setIsSubmitting(false);
                },
            });
        } else {
            router.post('/crm/vouchers', formData, {
                onSuccess: () => {
                    setIsFormOpen(false);
                    setIsSubmitting(false);
                },
                onError: (errors) => {
                    setFormErrors(errors);
                    setIsSubmitting(false);
                },
            });
        }
    };

    // Toggle Active
    const handleToggleActive = async (coupon) => {
        try {
            await axios.post(`/crm/vouchers/${coupon.id}/toggle-active`);
            applyFilters();
        } catch (err) {
            alert('Gagal mengubah status kupon');
        }
    };

    // Delete Coupon
    const handleDelete = (coupon) => {
        if (coupon.used_count > 0 || coupon.redemptions_count > 0) {
            alert('Voucher yang sudah pernah digunakan tidak dapat dihapus. Anda dapat menonaktifkannya.');
            return;
        }

        if (confirm(`Yakin ingin menghapus voucher #${coupon.code}?`)) {
            router.delete(`/crm/vouchers/${coupon.id}`, {
                preserveScroll: true,
            });
        }
    };

    // View Redemptions History
    const handleViewRedemptions = async (coupon) => {
        setSelectedCouponRedemptions(coupon);
        setIsRedemptionsOpen(true);
        setIsLoadingRedemptions(true);

        try {
            const res = await axios.get(`/crm/vouchers/${coupon.id}/redemptions`);
            setRedemptionsList(res.data?.redemptions || []);
        } catch (err) {
            console.error('Gagal mengambil riwayat redemption:', err);
        } finally {
            setIsLoadingRedemptions(false);
        }
    };

    return (
        <EcommerceLayout title="Voucher & Kupon Diskon">
            <Head title="Manajemen Kupon & Voucher Promo" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header & Actions Bar */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-amber-500/10 text-amber-600">
                                <Ticket className="w-6 h-6" />
                            </span>
                            Voucher & Kupon Diskon
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Kelola kupon diskon belanja, potongan harga closing CS, dan voucher subsidi ongkir.
                        </p>
                    </div>

                    <button
                        onClick={handleOpenCreate}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold text-sm shadow-md shadow-amber-500/20 hover:shadow-lg transition-all active:scale-95"
                    >
                        <Plus className="w-4 h-4" />
                        Buat Voucher Baru
                    </button>
                </div>

                {/* 2. KPI Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <Ticket className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kupon Aktif</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {stats?.total_active ?? 0}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">/ {stats?.total_coupons ?? 0} total</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <ShoppingBag className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Klaim / Pakai</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {(stats?.total_redemptions ?? 0).toLocaleString('id-ID')}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">kali</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <DollarSign className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Subsidi Diskon</div>
                            <div className="text-xl font-black text-slate-900 mt-0.5">
                                Rp {Number(stats?.total_savings ?? 0).toLocaleString('id-ID')}
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-indigo-900 to-slate-900 text-white p-4 rounded-2xl shadow-sm flex items-center justify-between">
                        <div>
                            <div className="text-xs font-semibold text-indigo-200 uppercase tracking-wider flex items-center gap-1.5">
                                <Sparkles className="w-3.5 h-3.5 text-amber-300" />
                                Integrasi CS Chat
                            </div>
                            <p className="text-xs text-slate-300 mt-1 leading-relaxed">
                                Kupon aktif otomatis tampil pada drawer Quick Voucher di CS Chat.
                            </p>
                        </div>
                    </div>
                </div>

                {/* 3. Filter & Search Toolbar */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3 flex-1 min-w-[260px]">
                        <div className="relative flex-1 max-w-md">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari kode kupon, judul, deskripsi..."
                                className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all"
                            />
                        </div>

                        {/* Status Filter Tabs */}
                        <div className="hidden md:flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-bold text-slate-600">
                            {[
                                { key: 'all', label: 'Semua' },
                                { key: 'active', label: 'Aktif' },
                                { key: 'inactive', label: 'Nonaktif' },
                            ].map((tab) => (
                                <button
                                    key={tab.key}
                                    onClick={() => {
                                        setSelectedStatus(tab.key);
                                        applyFilters({ status: tab.key });
                                    }}
                                    className={`px-3 py-1.5 rounded-lg transition-all ${
                                        selectedStatus === tab.key
                                            ? 'bg-white text-slate-900 shadow-2xs'
                                            : 'hover:text-slate-900'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="flex items-center gap-2.5 flex-wrap">
                        {/* Tipe Diskon Dropdown */}
                        <select
                            value={selectedType}
                            onChange={(e) => {
                                setSelectedType(e.target.value);
                                applyFilters({ type: e.target.value });
                            }}
                            className="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                        >
                            <option value="">Semua Tipe Diskon</option>
                            {couponTypes?.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.label}
                                </option>
                            ))}
                        </select>

                        {/* Business Unit Filter */}
                        <select
                            value={selectedBu}
                            onChange={(e) => {
                                setSelectedBu(e.target.value);
                                applyFilters({ bu: e.target.value });
                            }}
                            className="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                        >
                            <option value="">Semua Toko / BU</option>
                            {businessUnits?.map((bu) => (
                                <option key={bu.id} value={bu.id}>
                                    {bu.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* 4. Voucher Grid Cards */}
                {coupons?.data?.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-amber-50 text-amber-500 mx-auto flex items-center justify-center mb-3">
                            <Ticket className="w-8 h-8" />
                        </div>
                        <h3 className="text-base font-bold text-slate-800">Tidak ada voucher ditemukan</h3>
                        <p className="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                            Coba ubah kata kunci pencarian atau buat kupon voucher diskon baru untuk pembeli.
                        </p>
                        <button
                            onClick={handleOpenCreate}
                            className="mt-4 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-all shadow-2xs"
                        >
                            Buat Kupon Sekarang
                        </button>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {coupons.data.map((coupon) => {
                            const isPercent = coupon.type.includes('PERCENT');
                            const isShipping = coupon.type.includes('SHIPPING');

                            return (
                                <div
                                    key={coupon.id}
                                    className={`relative bg-white rounded-2xl border transition-all duration-200 overflow-hidden shadow-2xs hover:shadow-md ${
                                        coupon.is_active
                                            ? 'border-slate-200/90 hover:border-amber-400'
                                            : 'border-slate-200 opacity-70 bg-slate-50/50'
                                    }`}
                                >
                                    {/* Top Strip */}
                                    <div className="p-4 border-b border-dashed border-slate-200 flex items-start justify-between gap-3">
                                        <div className="flex items-center gap-2.5">
                                            <div
                                                className={`w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shrink-0 ${
                                                    isShipping
                                                        ? 'bg-blue-50 text-blue-600 border border-blue-200/60'
                                                        : 'bg-amber-50 text-amber-600 border border-amber-200/60'
                                                }`}
                                            >
                                                {isPercent ? <Percent className="w-5 h-5" /> : <DollarSign className="w-5 h-5" />}
                                            </div>

                                            <div>
                                                <div className="flex items-center gap-1.5">
                                                    <span className="font-extrabold text-sm text-slate-900 tracking-tight">
                                                        {coupon.title}
                                                    </span>
                                                </div>
                                                <span className="text-[11px] text-slate-400 font-medium">
                                                    {isShipping ? 'Potongan Ongkir' : 'Diskon Produk'} • {coupon.business_unit_name || 'Semua Store'}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Status Toggle Switch */}
                                        <button
                                            onClick={() => handleToggleActive(coupon)}
                                            title={coupon.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'}
                                            className="text-slate-400 hover:text-slate-600 transition-colors"
                                        >
                                            {coupon.is_active ? (
                                                <ToggleRight className="w-7 h-7 text-emerald-500" />
                                            ) : (
                                                <ToggleLeft className="w-7 h-7 text-slate-300" />
                                            )}
                                        </button>
                                    </div>

                                    {/* Middle Voucher Value */}
                                    <div className="p-4 space-y-3">
                                        <div className="flex items-baseline justify-between">
                                            <div className="text-xl font-black text-slate-900 tracking-tight">
                                                {isPercent
                                                    ? `${coupon.discount_value}% OFF`
                                                    : `Rp ${Number(coupon.discount_value).toLocaleString('id-ID')}`}
                                            </div>

                                            {/* Code Badge */}
                                            <button
                                                onClick={() => handleCopy(coupon.code)}
                                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-mono font-bold text-xs tracking-wider border border-amber-200/80 transition-colors group"
                                            >
                                                <span>{coupon.code}</span>
                                                {copiedCode === coupon.code ? (
                                                    <Check className="w-3.5 h-3.5 text-emerald-600" />
                                                ) : (
                                                    <Copy className="w-3.5 h-3.5 text-amber-600 opacity-60 group-hover:opacity-100" />
                                                )}
                                            </button>
                                        </div>

                                        <p className="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                            {coupon.description || 'Tidak ada deskripsi tambahan.'}
                                        </p>

                                        {/* Syarat & Ketentuan ringkas */}
                                        <div className="grid grid-cols-2 gap-2 text-[11px] text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                            <div>
                                                <span className="text-slate-400 block">Min. Belanja:</span>
                                                <span className="font-bold text-slate-700">
                                                    Rp {Number(coupon.min_purchase_amount).toLocaleString('id-ID')}
                                                </span>
                                            </div>
                                            <div>
                                                <span className="text-slate-400 block">Maks. Diskon:</span>
                                                <span className="font-bold text-slate-700">
                                                    {coupon.max_discount_amount
                                                        ? `Rp ${Number(coupon.max_discount_amount).toLocaleString('id-ID')}`
                                                        : 'Tanpa Batas'}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Usage Progress Bar */}
                                        <div className="space-y-1">
                                            <div className="flex items-center justify-between text-[11px]">
                                                <span className="text-slate-400 font-medium">Terpakai:</span>
                                                <span className="font-bold text-slate-700">
                                                    {coupon.used_count}{' '}
                                                    <span className="text-slate-400 font-normal">
                                                        / {coupon.usage_limit_total ? `${coupon.usage_limit_total} kuota` : 'Unlimited'}
                                                    </span>
                                                </span>
                                            </div>
                                            {coupon.usage_limit_total && (
                                                <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                    <div
                                                        className={`h-full rounded-full transition-all ${
                                                            coupon.used_count >= coupon.usage_limit_total
                                                                ? 'bg-rose-500'
                                                                : 'bg-amber-500'
                                                        }`}
                                                        style={{
                                                            width: `${Math.min(
                                                                100,
                                                                (coupon.used_count / coupon.usage_limit_total) * 100
                                                            )}%`,
                                                        }}
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Bottom Footer Actions */}
                                    <div className="px-4 py-2.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-1.5 text-slate-400 text-[11px]">
                                            <Clock className="w-3.5 h-3.5" />
                                            <span>
                                                {coupon.end_date
                                                    ? coupon.is_expired
                                                        ? 'Kedaluwarsa'
                                                        : `Berakhir ${coupon.end_date.slice(0, 10)}`
                                                    : 'Selamanya'}
                                            </span>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            <button
                                                onClick={() => handleViewRedemptions(coupon)}
                                                className="px-2 py-1 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 font-semibold text-[11px] transition-colors"
                                                title="Lihat riwayat pemakaian"
                                            >
                                                Riwayat ({coupon.redemptions_count})
                                            </button>
                                            <button
                                                onClick={() => handleOpenEdit(coupon)}
                                                className="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                                title="Edit voucher"
                                            >
                                                <Edit2 className="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(coupon)}
                                                className="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                title="Hapus voucher"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* 5. Pagination */}
                {coupons?.links && coupons.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1 mt-6">
                        {coupons.links.map((link, idx) => (
                            <button
                                key={idx}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
                                    link.active
                                        ? 'bg-amber-500 text-white shadow-2xs'
                                        : link.url
                                        ? 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                                        : 'text-slate-300 cursor-not-allowed'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Form (Create / Edit) */}
            {isFormOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsFormOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-2xl w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-lg font-black text-slate-900 tracking-tight">
                                    {editingCoupon ? `Edit Voucher #${editingCoupon.code}` : 'Buat Kupon Promo Baru'}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Kupon ini akan langsung tersedia untuk checkout customer dan CS chat.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsFormOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitForm} className="space-y-4 mt-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Kode Voucher *</label>
                                    <div className="relative">
                                        <input
                                            type="text"
                                            value={formData.code}
                                            onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                                            placeholder="Contoh: HEMAT50"
                                            required
                                            className="w-full px-3.5 py-2 uppercase font-mono font-bold bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                        />
                                    </div>
                                    {formErrors.code && <p className="text-rose-500 text-[11px] mt-1">{formErrors.code}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Judul Promo *</label>
                                    <input
                                        type="text"
                                        value={formData.title}
                                        onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                        placeholder="Contoh: Diskon Gajian Seru"
                                        required
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    />
                                    {formErrors.title && <p className="text-rose-500 text-[11px] mt-1">{formErrors.title}</p>}
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Deskripsi Singkat</label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows={2}
                                    placeholder="Syarat dan penjelasan voucher untuk pembeli..."
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Tipe Diskon *</label>
                                    <select
                                        value={formData.type}
                                        onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    >
                                        {couponTypes?.map((t) => (
                                            <option key={t.value} value={t.value}>
                                                {t.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Nilai Diskon ({formData.type.includes('PERCENT') ? '%' : 'Rp'}) *
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.discount_value}
                                        onChange={(e) => setFormData({ ...formData, discount_value: e.target.value })}
                                        required
                                        min={1}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    />
                                    {formErrors.discount_value && <p className="text-rose-500 text-[11px] mt-1">{formErrors.discount_value}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Min. Belanja (Rp)</label>
                                    <input
                                        type="number"
                                        value={formData.min_purchase_amount}
                                        onChange={(e) => setFormData({ ...formData, min_purchase_amount: e.target.value })}
                                        min={0}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Maks. Diskon (Rp, Optional)</label>
                                    <input
                                        type="number"
                                        value={formData.max_discount_amount}
                                        onChange={(e) => setFormData({ ...formData, max_discount_amount: e.target.value })}
                                        placeholder="Kosongkan jika tanpa batas"
                                        min={0}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Batas Kuota Total</label>
                                    <input
                                        type="number"
                                        value={formData.usage_limit_total}
                                        onChange={(e) => setFormData({ ...formData, usage_limit_total: e.target.value })}
                                        placeholder="Kosongkan jika unlimited"
                                        min={1}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Khusus Store / Unit Bisnis</label>
                                    <select
                                        value={formData.business_unit_id}
                                        onChange={(e) => setFormData({ ...formData, business_unit_id: e.target.value })}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
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

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Mulai Berlaku</label>
                                    <input
                                        type="datetime-local"
                                        value={formData.start_date}
                                        onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Berakhir Pada</label>
                                    <input
                                        type="datetime-local"
                                        value={formData.end_date}
                                        onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2 pt-2">
                                <input
                                    type="checkbox"
                                    id="voucher-active-toggle"
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    className="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-slate-300"
                                />
                                <label htmlFor="voucher-active-toggle" className="text-xs font-bold text-slate-700">
                                    Aktifkan Voucher Ini Sekarang
                                </label>
                            </div>

                            <div className="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setIsFormOpen(false)}
                                    className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-bold shadow-md shadow-amber-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    {editingCoupon ? 'Simpan Perubahan' : 'Terbitkan Voucher'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Riwayat Klaim / Redemptions */}
            {isRedemptionsOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsRedemptionsOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-2xl w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                                    <Ticket className="w-5 h-5 text-amber-500" />
                                    Riwayat Klaim #{selectedCouponRedemptions?.code}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Daftar transaksi dan pembeli yang menggunakan voucher ini.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsRedemptionsOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <div className="mt-4 max-h-96 overflow-y-auto">
                            {isLoadingRedemptions ? (
                                <div className="p-8 text-center text-slate-400 flex flex-col items-center gap-2">
                                    <Loader2 className="w-6 h-6 animate-spin text-amber-500" />
                                    <span className="text-xs font-medium">Memuat riwayat pemakaian...</span>
                                </div>
                            ) : redemptionsList.length === 0 ? (
                                <div className="p-8 text-center text-slate-400">
                                    <Ticket className="w-8 h-8 mx-auto text-slate-300 mb-2" />
                                    <p className="text-xs font-medium">Belum ada pelanggan yang mengklaim voucher ini.</p>
                                </div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {redemptionsList.map((item) => (
                                        <div key={item.id} className="py-3 flex items-center justify-between gap-3 text-xs">
                                            <div>
                                                <div className="font-bold text-slate-900">{item.customer_name}</div>
                                                <div className="text-[11px] text-slate-400 font-mono mt-0.5">
                                                    {item.customer_phone} • Order {item.order_number}
                                                </div>
                                            </div>

                                            <div className="text-right">
                                                <div className="font-extrabold text-emerald-600">
                                                    - Rp {Number(item.discount_applied).toLocaleString('id-ID')}
                                                </div>
                                                <div className="text-[10.5px] text-slate-400 mt-0.5">{item.created_at}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="pt-4 border-t border-slate-100 flex justify-end">
                            <button
                                onClick={() => setIsRedemptionsOpen(false)}
                                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors"
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
