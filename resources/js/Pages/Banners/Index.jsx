import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    Image as ImageIcon,
    Plus,
    Trash2,
    Edit2,
    ToggleLeft,
    ToggleRight,
    ArrowUp,
    ArrowDown,
    ExternalLink,
    Smartphone,
    Layers,
    Check,
    X,
    Loader2,
    Upload,
    Sparkles,
    Eye
} from 'lucide-react';
import axios from 'axios';

export default function BannersIndex({
    banners = [],
    stats = {},
    targetTypes = []
}) {
    const { businessUnits } = usePage().props;

    const [bannerList, setBannerList] = useState(banners);
    const [previewIndex, setPreviewIndex] = useState(0);

    // Modal Form State (Create / Edit)
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingBanner, setEditingBanner] = useState(null);
    const [formData, setFormData] = useState({
        title: '',
        imageFile: null,
        target_type: 'none',
        target_value: '',
        business_unit_id: '',
        sort_order: 0,
        is_active: true,
    });
    const [imagePreview, setImagePreview] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [formErrors, setFormErrors] = useState({});

    // Sync banner list when props change
    useEffect(() => {
        setBannerList(banners);
    }, [banners]);

    // Active banners for mobile mockup preview
    const activeBanners = bannerList.filter((b) => b.is_active);

    // Auto rotate mockup carousel
    useEffect(() => {
        if (activeBanners.length <= 1) return;
        const interval = setInterval(() => {
            setPreviewIndex((prev) => (prev + 1) % activeBanners.length);
        }, 4000);
        return () => clearInterval(interval);
    }, [activeBanners.length]);

    // Open Create Modal
    const handleOpenCreate = () => {
        setEditingBanner(null);
        setFormData({
            title: '',
            imageFile: null,
            target_type: 'none',
            target_value: '',
            business_unit_id: '',
            sort_order: bannerList.length,
            is_active: true,
        });
        setImagePreview(null);
        setFormErrors({});
        setIsFormOpen(true);
    };

    // Open Edit Modal
    const handleOpenEdit = (banner) => {
        setEditingBanner(banner);
        setFormData({
            title: banner.title,
            imageFile: null,
            target_type: banner.target_type || 'none',
            target_value: banner.target_value || '',
            business_unit_id: banner.business_unit_id || '',
            sort_order: banner.sort_order || 0,
            is_active: banner.is_active,
        });
        setImagePreview(banner.image_url);
        setFormErrors({});
        setIsFormOpen(true);
    };

    // Handle Image File Selection
    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setFormData({ ...formData, imageFile: file });
            setImagePreview(URL.createObjectURL(file));
        }
    };

    // Submit Form
    const handleSubmit = (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setFormErrors({});

        const data = new FormData();
        data.append('title', formData.title);
        if (formData.imageFile) {
            data.append('imageFile', formData.imageFile);
        }
        data.append('target_type', formData.target_type);
        data.append('target_value', formData.target_value || '');
        if (formData.business_unit_id) {
            data.append('business_unit_id', formData.business_unit_id);
        }
        data.append('sort_order', formData.sort_order);
        data.append('is_active', formData.is_active ? '1' : '0');

        if (editingBanner) {
            router.post(`/crm/banners/${editingBanner.id}/update`, data, {
                onSuccess: () => {
                    setIsFormOpen(false);
                    setIsSubmitting(false);
                },
                onError: (errs) => {
                    setFormErrors(errs);
                    setIsSubmitting(false);
                },
            });
        } else {
            router.post('/crm/banners', data, {
                onSuccess: () => {
                    setIsFormOpen(false);
                    setIsSubmitting(false);
                },
                onError: (errs) => {
                    setFormErrors(errs);
                    setIsSubmitting(false);
                },
            });
        }
    };

    // Toggle Active
    const handleToggleActive = async (banner) => {
        try {
            await axios.post(`/crm/banners/${banner.id}/toggle-active`);
            router.reload({ preserveScroll: true });
        } catch (err) {
            alert('Gagal mengubah status banner');
        }
    };

    // Delete Banner
    const handleDelete = (banner) => {
        if (confirm(`Yakin ingin menghapus banner '${banner.title}'?`)) {
            router.delete(`/crm/banners/${banner.id}`, {
                preserveScroll: true,
            });
        }
    };

    // Reorder Handlers (Up / Down)
    const handleMoveOrder = async (index, direction) => {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= bannerList.length) return;

        const updated = [...bannerList];
        const [moved] = updated.splice(index, 1);
        updated.splice(targetIndex, 0, moved);

        // Update local state sort_order
        const orderPayload = updated.map((b, i) => ({ id: b.id, sort_order: i }));
        setBannerList(updated.map((b, i) => ({ ...b, sort_order: i })));

        try {
            await axios.post('/crm/banners/reorder', { orders: orderPayload });
        } catch (e) {
            console.error('Reorder failed:', e);
        }
    };

    return (
        <EcommerceLayout title="Banner Promo Mobile">
            <Head title="Manajemen Banner Promo Mobile" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header & Actions Bar */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-blue-500/10 text-blue-600">
                                <ImageIcon className="w-6 h-6" />
                            </span>
                            Banner Promo Mobile
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Atur spanduk slider promosi dan iklan visual di halaman utama aplikasi Tokopon mobile.
                        </p>
                    </div>

                    <button
                        onClick={handleOpenCreate}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 hover:shadow-lg transition-all active:scale-95"
                    >
                        <Plus className="w-4 h-4" />
                        Unggah Banner Baru
                    </button>
                </div>

                {/* 2. KPI Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <Layers className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Banner</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {stats?.total_banners ?? 0}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">visual</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <Smartphone className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tampil di App Mobile</div>
                            <div className="text-2xl font-black text-emerald-600 mt-0.5">
                                {stats?.active_banners ?? 0}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">aktif</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center font-bold">
                            <Eye className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rasio Gambar Ideal</div>
                            <div className="text-sm font-black text-slate-800 mt-0.5">
                                16:9 atau 21:9 (1080 x 540 px)
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3. Main Grid: Banner List (Kiri) + Smartphone Live Mockup (Kanan) */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    {/* Kolom Kiri: Daftar Banner (2 Kolom) */}
                    <div className="lg:col-span-2 space-y-4">
                        {bannerList.length === 0 ? (
                            <div className="bg-white rounded-3xl border border-slate-200/80 p-12 text-center">
                                <div className="w-16 h-16 rounded-2xl bg-blue-50 text-blue-500 mx-auto flex items-center justify-center mb-3">
                                    <ImageIcon className="w-8 h-8" />
                                </div>
                                <h3 className="text-base font-bold text-slate-800">Belum ada banner promo</h3>
                                <p className="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                                    Unggah banner promo pertama untuk mempercantik tampilan awal aplikasi mobile pembeli.
                                </p>
                                <button
                                    onClick={handleOpenCreate}
                                    className="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-2xs"
                                >
                                    Unggah Banner Sekarang
                                </button>
                            </div>
                        ) : (
                            bannerList.map((banner, index) => (
                                <div
                                    key={banner.id}
                                    className={`bg-white rounded-2xl border transition-all duration-200 p-4 shadow-2xs hover:shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 ${
                                        banner.is_active
                                            ? 'border-slate-200/90 hover:border-blue-300'
                                            : 'border-slate-200 opacity-60 bg-slate-50/50'
                                    }`}
                                >
                                    <div className="flex items-center gap-3 w-full sm:w-auto">
                                        {/* Sort Reorder Buttons */}
                                        <div className="flex flex-col gap-1 shrink-0">
                                            <button
                                                disabled={index === 0}
                                                onClick={() => handleMoveOrder(index, -1)}
                                                className="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 transition-colors"
                                                title="Naikkan urutan"
                                            >
                                                <ArrowUp className="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                disabled={index === bannerList.length - 1}
                                                onClick={() => handleMoveOrder(index, 1)}
                                                className="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 transition-colors"
                                                title="Turunkan urutan"
                                            >
                                                <ArrowDown className="w-3.5 h-3.5" />
                                            </button>
                                        </div>

                                        {/* Banner Thumbnail */}
                                        <div className="w-32 h-18 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200/70 shadow-2xs relative group">
                                            <img
                                                src={banner.image_url}
                                                alt={banner.title}
                                                className="w-full h-full object-cover"
                                            />
                                        </div>

                                        {/* Info */}
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="font-extrabold text-sm text-slate-900 tracking-tight">
                                                    {banner.title}
                                                </span>
                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                                    Urutan #{banner.sort_order + 1}
                                                </span>
                                            </div>

                                            <div className="flex items-center gap-2 text-xs text-slate-400 mt-1 flex-wrap">
                                                <span className="text-[11px] font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">
                                                    Target: {banner.target_type.toUpperCase()}
                                                    {banner.target_value ? ` (${banner.target_value})` : ''}
                                                </span>
                                                <span>•</span>
                                                <span className="text-[11px] text-slate-500">
                                                    {banner.business_unit_name}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Action Tools */}
                                    <div className="flex items-center gap-2 self-end sm:self-center">
                                        <button
                                            onClick={() => handleToggleActive(banner)}
                                            className="text-slate-400 hover:text-slate-600 transition-colors"
                                            title={banner.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'}
                                        >
                                            {banner.is_active ? (
                                                <ToggleRight className="w-7 h-7 text-emerald-500" />
                                            ) : (
                                                <ToggleLeft className="w-7 h-7 text-slate-300" />
                                            )}
                                        </button>

                                        <button
                                            onClick={() => handleOpenEdit(banner)}
                                            className="p-2 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Edit banner"
                                        >
                                            <Edit2 className="w-4 h-4" />
                                        </button>

                                        <button
                                            onClick={() => handleDelete(banner)}
                                            className="p-2 rounded-xl text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus banner"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Kolom Kanan: Smartphone Live Mockup Preview (1 Kolom) */}
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-2xs flex flex-col items-center">
                        <div className="text-center mb-4">
                            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-blue-50 text-blue-600 mb-1">
                                <Smartphone className="w-3.5 h-3.5" />
                                Live Mockup App
                            </div>
                            <h3 className="text-sm font-bold text-slate-800">
                                Tampilan di Layar Pembeli
                            </h3>
                        </div>

                        {/* Smartphone Bezel */}
                        <div className="w-[280px] bg-slate-900 rounded-[2.5rem] p-3 shadow-2xl border-4 border-slate-800 relative">
                            {/* Speaker Notch */}
                            <div className="w-20 h-3.5 bg-black rounded-full mx-auto mb-2 flex items-center justify-center">
                                <div className="w-2 h-2 rounded-full bg-slate-800 mr-2"></div>
                                <div className="w-8 h-1 bg-slate-800 rounded-full"></div>
                            </div>

                            {/* Mobile Screen Surface */}
                            <div className="bg-slate-50 rounded-[2rem] overflow-hidden min-h-[420px] flex flex-col text-slate-800">
                                {/* Fake Mobile Header */}
                                <div className="p-3 bg-white border-b border-slate-100 flex items-center justify-between">
                                    <div className="text-[11px] font-black tracking-tight text-slate-900 flex items-center gap-1">
                                        <span className="text-indigo-600 font-extrabold">⚡</span> Tokopon
                                    </div>
                                    <div className="w-5 h-5 rounded-full bg-slate-100 text-[10px] flex items-center justify-center font-bold text-slate-500">
                                        🛒
                                    </div>
                                </div>

                                {/* Banner Carousel in Mobile Screen */}
                                <div className="p-3">
                                    <div className="relative aspect-[16/9] w-full rounded-2xl overflow-hidden shadow-sm bg-slate-200">
                                        {activeBanners.length > 0 ? (
                                            <img
                                                src={activeBanners[previewIndex]?.image_url}
                                                alt={activeBanners[previewIndex]?.title}
                                                className="w-full h-full object-cover transition-all duration-300"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex flex-col items-center justify-center text-slate-400 p-2 text-center text-[10.5px]">
                                                <ImageIcon className="w-6 h-6 mb-1 text-slate-300" />
                                                Tidak ada banner aktif
                                            </div>
                                        )}

                                        {/* Pagination Dots */}
                                        {activeBanners.length > 1 && (
                                            <div className="absolute bottom-2 left-0 right-0 flex items-center justify-center gap-1">
                                                {activeBanners.map((_, dotIdx) => (
                                                    <span
                                                        key={dotIdx}
                                                        onClick={() => setPreviewIndex(dotIdx)}
                                                        className={`h-1.5 rounded-full transition-all cursor-pointer ${
                                                            previewIndex === dotIdx
                                                                ? 'w-4 bg-white'
                                                                : 'w-1.5 bg-white/50'
                                                        }`}
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Fake Categories under banner */}
                                <div className="px-3 py-1 grid grid-cols-4 gap-2 text-center text-[9px] font-bold text-slate-600">
                                    <div className="space-y-1">
                                        <div className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 mx-auto flex items-center justify-center text-xs">📱</div>
                                        <span>Gadget</span>
                                    </div>
                                    <div className="space-y-1">
                                        <div className="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center text-xs">⚡</div>
                                        <span>Flash Sale</span>
                                    </div>
                                    <div className="space-y-1">
                                        <div className="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 mx-auto flex items-center justify-center text-xs">🎟️</div>
                                        <span>Voucher</span>
                                    </div>
                                    <div className="space-y-1">
                                        <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center text-xs">💬</div>
                                        <span>Chat CS</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal Form: Unggah / Edit Banner */}
            {isFormOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsFormOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-lg font-black text-slate-900 tracking-tight">
                                    {editingBanner ? `Edit Banner: ${editingBanner.title}` : 'Unggah Banner Baru'}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Pilih gambar spanduk dan tautan tujuan klik di mobile app.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsFormOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4 mt-4">
                            {/* Image Dropzone Preview */}
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Gambar Banner * (Disarankan 1080 x 540 px)
                                </label>
                                <div className="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-2xl p-4 text-center cursor-pointer relative bg-slate-50/60 overflow-hidden transition-colors group">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={handleFileChange}
                                        className="absolute inset-0 opacity-0 cursor-pointer z-10"
                                        required={!editingBanner}
                                    />
                                    {imagePreview ? (
                                        <div className="relative aspect-[16/9] w-full rounded-xl overflow-hidden bg-white shadow-2xs">
                                            <img
                                                src={imagePreview}
                                                alt="Preview"
                                                className="w-full h-full object-cover"
                                            />
                                            <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition-opacity">
                                                Klik untuk ganti gambar
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="py-6 flex flex-col items-center gap-2 text-slate-400">
                                            <Upload className="w-8 h-8 text-blue-500 group-hover:scale-110 transition-transform" />
                                            <div>
                                                <span className="text-xs font-bold text-blue-600">Klik untuk unggah</span>
                                                <span className="text-xs text-slate-500"> atau seret file ke sini</span>
                                            </div>
                                            <span className="text-[10.5px] text-slate-400">PNG, JPG, WEBP maks 4MB</span>
                                        </div>
                                    )}
                                </div>
                                {formErrors.imageFile && (
                                    <p className="text-rose-500 text-[11px] mt-1">{formErrors.imageFile}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Judul Banner *</label>
                                <input
                                    type="text"
                                    value={formData.title}
                                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                    required
                                    placeholder="Contoh: Promo Spesial iPhone 15 Pro"
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                                {formErrors.title && <p className="text-rose-500 text-[11px] mt-1">{formErrors.title}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Aksi Target Klik</label>
                                    <select
                                        value={formData.target_type}
                                        onChange={(e) => setFormData({ ...formData, target_type: e.target.value })}
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    >
                                        {targetTypes.map((t) => (
                                            <option key={t.value} value={t.value}>
                                                {t.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Nilai Target</label>
                                    <input
                                        type="text"
                                        value={formData.target_value}
                                        onChange={(e) => setFormData({ ...formData, target_value: e.target.value })}
                                        placeholder={
                                            formData.target_type === 'url'
                                                ? 'https://...'
                                                : formData.target_type === 'product'
                                                ? 'ID Produk'
                                                : 'Nama Kategori / Brand'
                                        }
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Khusus Store / Unit Bisnis</label>
                                    <select
                                        value={formData.business_unit_id}
                                        onChange={(e) => setFormData({ ...formData, business_unit_id: e.target.value })}
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    >
                                        <option value="">Semua Store (Syihab & GSK)</option>
                                        {businessUnits?.map((bu) => (
                                            <option key={bu.id} value={bu.id}>
                                                {bu.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Urutan Tampil (Sort Order)</label>
                                    <input
                                        type="number"
                                        value={formData.sort_order}
                                        onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })}
                                        min={0}
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="banner-active-check"
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    className="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                                />
                                <label htmlFor="banner-active-check" className="text-xs font-bold text-slate-700">
                                    Tampilkan Banner Ini di Mobile App
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
                                    className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Simpan Banner
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </EcommerceLayout>
    );
}
