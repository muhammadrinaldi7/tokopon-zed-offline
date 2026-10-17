import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    Zap,
    Plus,
    Clock,
    Calendar,
    ShoppingBag,
    Search,
    Trash2,
    ToggleLeft,
    ToggleRight,
    Store,
    CheckCircle2,
    AlertCircle,
    X,
    Loader2,
    TrendingDown,
    Flame
} from 'lucide-react';
import axios from 'axios';

export default function FlashSaleIndex({
    flashSales = [],
    stats = {}
}) {
    const { businessUnits } = usePage().props;

    const [activeFilter, setActiveFilter] = useState('all'); // all, ongoing, upcoming, ended

    // Modal Event State
    const [isEventModalOpen, setIsEventModalOpen] = useState(false);
    const [eventFormData, setEventFormData] = useState({
        id: null,
        title: '',
        start_time: '',
        end_time: '',
        business_unit_id: '',
        is_active: true,
    });
    const [isSubmittingEvent, setIsSubmittingEvent] = useState(false);

    // Modal Item State
    const [isItemModalOpen, setIsItemModalOpen] = useState(false);
    const [targetFlashSaleId, setTargetFlashSaleId] = useState(null);
    const [productSearch, setProductSearch] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [isSearchingProduct, setIsSearchingProduct] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [itemFormData, setItemFormData] = useState({
        flash_sale_price: '',
        original_price: '',
        quota_stock: 10,
    });
    const [isSubmittingItem, setIsSubmittingItem] = useState(false);

    // Open Event Create Modal
    const handleOpenCreateEvent = () => {
        const now = new Date();
        const sixHoursLater = new Date(now.getTime() + 6 * 60 * 60 * 1000);

        setEventFormData({
            id: null,
            title: `Flash Sale Kilat ${now.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}`,
            start_time: now.toISOString().slice(0, 16),
            end_time: sixHoursLater.toISOString().slice(0, 16),
            business_unit_id: '',
            is_active: true,
        });
        setIsEventModalOpen(true);
    };

    // Submit Event Form
    const handleSubmitEvent = (e) => {
        e.preventDefault();
        setIsSubmittingEvent(true);

        router.post('/crm/flash-sale/event', eventFormData, {
            onSuccess: () => {
                setIsEventModalOpen(false);
                setIsSubmittingEvent(false);
            },
            onError: () => setIsSubmittingEvent(false),
        });
    };

    // Toggle Event Active
    const handleToggleEvent = async (fs) => {
        try {
            await axios.post(`/crm/flash-sale/event/${fs.id}/toggle`);
            router.reload({ preserveScroll: true });
        } catch (err) {
            alert('Gagal mengubah status sesi Flash Sale');
        }
    };

    // Delete Event
    const handleDeleteEvent = (fs) => {
        if (confirm(`Yakin ingin menghapus sesi Flash Sale '${fs.title}' dan semua produk di dalamnya?`)) {
            router.delete(`/crm/flash-sale/event/${fs.id}`, {
                preserveScroll: true,
            });
        }
    };

    // Open Item Add Modal
    const handleOpenAddItem = (flashSaleId) => {
        setTargetFlashSaleId(flashSaleId);
        setSelectedProduct(null);
        setProductSearch('');
        setSearchResults([]);
        setItemFormData({
            flash_sale_price: '',
            original_price: '',
            quota_stock: 10,
        });
        setIsItemModalOpen(true);
    };

    // Search Products with debounce
    useEffect(() => {
        const timer = setTimeout(async () => {
            if (productSearch.trim().length >= 2) {
                setIsSearchingProduct(true);
                try {
                    const res = await axios.get('/crm/flash-sale/search-products', {
                        params: { q: productSearch },
                    });
                    setSearchResults(res.data || []);
                } catch (e) {
                    console.error('Search product failed:', e);
                } finally {
                    setIsSearchingProduct(false);
                }
            } else {
                setSearchResults([]);
            }
        }, 250);

        return () => clearTimeout(timer);
    }, [productSearch]);

    const handleSelectProduct = (product) => {
        setSelectedProduct(product);
        const original = product.base_price;
        const discounted = Math.round(original * 0.9); // Diskon default 10%
        setItemFormData({
            original_price: String(original),
            flash_sale_price: String(discounted),
            quota_stock: 10,
        });
        setSearchResults([]);
        setProductSearch(product.name);
    };

    // Submit Item Form
    const handleSubmitItem = (e) => {
        e.preventDefault();
        if (!selectedProduct) {
            alert('Silakan pilih produk terlebih dahulu!');
            return;
        }

        setIsSubmittingItem(true);
        router.post(
            '/crm/flash-sale/item',
            {
                flash_sale_id: targetFlashSaleId,
                product_accurate_id: selectedProduct.id,
                flash_sale_price: itemFormData.flash_sale_price,
                original_price: itemFormData.original_price,
                quota_stock: itemFormData.quota_stock,
            },
            {
                onSuccess: () => {
                    setIsItemModalOpen(false);
                    setIsSubmittingItem(false);
                },
                onError: () => setIsSubmittingItem(false),
            }
        );
    };

    // Delete Item
    const handleDeleteItem = (itemId) => {
        if (confirm('Hapus produk ini dari sesi Flash Sale?')) {
            router.delete(`/crm/flash-sale/item/${itemId}`, {
                preserveScroll: true,
            });
        }
    };

    // Filtered flash sales
    const filteredSales = flashSales.filter((fs) => {
        if (activeFilter === 'all') return true;
        return fs.status === activeFilter;
    });

    return (
        <EcommerceLayout title="Flash Sale Kilat">
            <Head title="Manajemen Flash Sale Kilat" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header & Actions Bar */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-rose-500/10 text-rose-600">
                                <Zap className="w-6 h-6" />
                            </span>
                            Flash Sale Kilat
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Atur jadwal promo kilat dengan harga miring dan kuota stok terbatas di aplikasi mobile.
                        </p>
                    </div>

                    <button
                        onClick={handleOpenCreateEvent}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-bold text-sm shadow-md shadow-rose-500/20 hover:shadow-lg transition-all active:scale-95"
                    >
                        <Plus className="w-4 h-4" />
                        Buat Sesi Flash Sale
                    </button>
                </div>

                {/* 2. KPI Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                            <Flame className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sedang Live</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {stats?.ongoing_count ?? 0}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">sesi aktif</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <Clock className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Akan Datang</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {stats?.upcoming_count ?? 0}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">sesi</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <ShoppingBag className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Produk Promo</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {stats?.total_products ?? 0}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">unit</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-rose-950 to-slate-900 text-white p-4 rounded-2xl shadow-sm flex items-center justify-between">
                        <div>
                            <div className="text-xs font-semibold text-rose-200 uppercase tracking-wider flex items-center gap-1.5">
                                <span>⚡ Timer Countdown</span>
                            </div>
                            <p className="text-xs text-slate-300 mt-1 leading-relaxed">
                                Banner flash sale otomatis muncul di mobile app saat jam mulai tercapai.
                            </p>
                        </div>
                    </div>
                </div>

                {/* 3. Filter Status Tabs */}
                <div className="flex items-center gap-1 bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-2xs text-xs font-bold text-slate-600 w-fit">
                    {[
                        { key: 'all', label: `Semua Sesi (${flashSales.length})` },
                        { key: 'ongoing', label: `Sedang Berlangsung (${stats?.ongoing_count ?? 0})` },
                        { key: 'upcoming', label: `Akan Datang (${stats?.upcoming_count ?? 0})` },
                        { key: 'ended', label: 'Telah Berakhir' },
                    ].map((tab) => (
                        <button
                            key={tab.key}
                            onClick={() => setActiveFilter(tab.key)}
                            className={`px-3.5 py-2 rounded-xl transition-all ${
                                activeFilter === tab.key
                                    ? 'bg-rose-500 text-white shadow-2xs'
                                    : 'hover:text-slate-900 hover:bg-slate-50'
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* 4. Flash Sale Sessions List */}
                {filteredSales.length === 0 ? (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-12 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 mx-auto flex items-center justify-center mb-3">
                            <Zap className="w-8 h-8" />
                        </div>
                        <h3 className="text-base font-bold text-slate-800">Tidak ada sesi Flash Sale</h3>
                        <p className="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                            Buat sesi Flash Sale promo kilat baru untuk meningkatkan konversi belanja pembeli.
                        </p>
                        <button
                            onClick={handleOpenCreateEvent}
                            className="mt-4 px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold rounded-xl transition-all shadow-2xs"
                        >
                            Buat Sesi Sekarang
                        </button>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {filteredSales.map((fs) => (
                            <div
                                key={fs.id}
                                className="bg-white rounded-3xl border border-slate-200/80 shadow-2xs overflow-hidden"
                            >
                                {/* Session Header Bar */}
                                <div className="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={`w-3 h-3 rounded-full shrink-0 ${
                                                fs.status === 'ongoing'
                                                    ? 'bg-emerald-500 animate-pulse'
                                                    : fs.status === 'upcoming'
                                                    ? 'bg-blue-500'
                                                    : 'bg-slate-400'
                                            }`}
                                        />
                                        <div>
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <h3 className="text-base font-black text-slate-900 tracking-tight">
                                                    {fs.title}
                                                </h3>
                                                <span
                                                    className={`px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider ${
                                                        fs.status === 'ongoing'
                                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                            : fs.status === 'upcoming'
                                                            ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                                            : 'bg-slate-100 text-slate-600'
                                                    }`}
                                                >
                                                    {fs.status_label}
                                                </span>
                                                <span className="px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-slate-100 text-slate-600">
                                                    {fs.business_unit_name}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2 text-xs text-slate-400 mt-1">
                                                <Clock className="w-3.5 h-3.5" />
                                                <span>{fs.start_time} — {fs.end_time}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex items-center gap-2">
                                        <button
                                            onClick={() => handleToggleEvent(fs)}
                                            className="text-slate-400 hover:text-slate-600 transition-colors"
                                            title={fs.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'}
                                        >
                                            {fs.is_active ? (
                                                <ToggleRight className="w-7 h-7 text-emerald-500" />
                                            ) : (
                                                <ToggleLeft className="w-7 h-7 text-slate-300" />
                                            )}
                                        </button>

                                        <button
                                            onClick={() => handleOpenAddItem(fs.id)}
                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors"
                                        >
                                            <Plus className="w-3.5 h-3.5 text-rose-600" />
                                            Tambah Produk
                                        </button>

                                        <button
                                            onClick={() => handleDeleteEvent(fs)}
                                            className="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus sesi"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                {/* Session Items Grid */}
                                <div className="p-5">
                                    {fs.items.length === 0 ? (
                                        <div className="py-8 text-center text-slate-400">
                                            <ShoppingBag className="w-8 h-8 mx-auto text-slate-300 mb-2" />
                                            <p className="text-xs font-medium">Belum ada produk promo di sesi ini.</p>
                                            <button
                                                onClick={() => handleOpenAddItem(fs.id)}
                                                className="mt-2 text-xs font-bold text-rose-600 hover:underline"
                                            >
                                                + Tambah Produk Pertama
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                            {fs.items.map((item) => (
                                                <div
                                                    key={item.id}
                                                    className="relative bg-slate-50/70 hover:bg-white rounded-2xl border border-slate-200/80 p-3 flex flex-col justify-between transition-all hover:shadow-xs group"
                                                >
                                                    <div>
                                                        <div className="relative aspect-square w-full rounded-xl overflow-hidden bg-white mb-2.5 border border-slate-100">
                                                            <img
                                                                src={item.thumbnail}
                                                                alt={item.name}
                                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                                            />
                                                            {item.discount_percent > 0 && (
                                                                <span className="absolute top-2 left-2 px-2 py-0.5 rounded-lg bg-rose-600 text-white font-black text-[10.5px] shadow-2xs">
                                                                    -{item.discount_percent}%
                                                                </span>
                                                            )}
                                                            <button
                                                                onClick={() => handleDeleteItem(item.id)}
                                                                className="absolute top-2 right-2 w-7 h-7 rounded-lg bg-white/90 text-rose-600 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 shadow-2xs"
                                                                title="Hapus produk dari promo"
                                                            >
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </button>
                                                        </div>

                                                        <h4 className="font-extrabold text-xs text-slate-900 line-clamp-2 leading-snug">
                                                            {item.name}
                                                        </h4>
                                                        {item.item_no && (
                                                            <span className="text-[10px] text-slate-400 font-mono block mt-0.5">
                                                                {item.item_no}
                                                            </span>
                                                        )}
                                                    </div>

                                                    <div className="mt-3 pt-2.5 border-t border-slate-200/60 space-y-2">
                                                        <div>
                                                            <div className="text-sm font-black text-rose-600 tracking-tight">
                                                                {item.formatted_flash_sale_price}
                                                            </div>
                                                            {item.formatted_original_price && (
                                                                <div className="text-[11px] text-slate-400 line-through">
                                                                    {item.formatted_original_price}
                                                                </div>
                                                            )}
                                                        </div>

                                                        {/* Stock Progress */}
                                                        <div>
                                                            <div className="flex items-center justify-between text-[10.5px] text-slate-500 mb-1">
                                                                <span>Terjual: <strong>{item.sold_stock}</strong></span>
                                                                <span>Kuota: {item.quota_stock}</span>
                                                            </div>
                                                            <div className="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                                <div
                                                                    className="bg-rose-500 h-full rounded-full transition-all"
                                                                    style={{
                                                                        width: `${Math.min(
                                                                            100,
                                                                            (item.sold_stock / item.quota_stock) * 100
                                                                        )}%`,
                                                                    }}
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Form: Sesi Flash Sale */}
            {isEventModalOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsEventModalOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-md w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-lg font-black text-slate-900 tracking-tight">
                                    Buat Sesi Flash Sale Baru
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Tentukan jadwal mulai dan selesai promo kilat.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsEventModalOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitEvent} className="space-y-4 mt-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Judul Sesi *</label>
                                <input
                                    type="text"
                                    value={eventFormData.title}
                                    onChange={(e) => setEventFormData({ ...eventFormData, title: e.target.value })}
                                    required
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Waktu Mulai *</label>
                                    <input
                                        type="datetime-local"
                                        value={eventFormData.start_time}
                                        onChange={(e) => setEventFormData({ ...eventFormData, start_time: e.target.value })}
                                        required
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Waktu Berakhir *</label>
                                    <input
                                        type="datetime-local"
                                        value={eventFormData.end_time}
                                        onChange={(e) => setEventFormData({ ...eventFormData, end_time: e.target.value })}
                                        required
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Store / Unit Bisnis</label>
                                <select
                                    value={eventFormData.business_unit_id}
                                    onChange={(e) => setEventFormData({ ...eventFormData, business_unit_id: e.target.value })}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                                >
                                    <option value="">Semua Store (Syihab & GSK)</option>
                                    {businessUnits?.map((bu) => (
                                        <option key={bu.id} value={bu.id}>
                                            {bu.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="event-active-check"
                                    checked={eventFormData.is_active}
                                    onChange={(e) => setEventFormData({ ...eventFormData, is_active: e.target.checked })}
                                    className="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300"
                                />
                                <label htmlFor="event-active-check" className="text-xs font-bold text-slate-700">
                                    Aktifkan Sesi Ini Sekarang
                                </label>
                            </div>

                            <div className="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setIsEventModalOpen(false)}
                                    className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmittingEvent}
                                    className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white text-xs font-bold shadow-md shadow-rose-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSubmittingEvent && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Simpan Sesi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Form: Tambah Produk Promo */}
            {isItemModalOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsItemModalOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-lg font-black text-slate-900 tracking-tight">
                                    Tambah Produk Flash Sale
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Cari produk dan atur harga promo serta kuota stok.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsItemModalOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitItem} className="space-y-4 mt-4">
                            {/* Product Search Input */}
                            <div className="relative">
                                <label className="block text-xs font-bold text-slate-700 mb-1">Cari Produk *</label>
                                <div className="relative">
                                    <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                    <input
                                        type="text"
                                        value={productSearch}
                                        onChange={(e) => setProductSearch(e.target.value)}
                                        placeholder="Ketik nama produk atau item no..."
                                        className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                    />
                                    {isSearchingProduct && (
                                        <Loader2 className="w-4 h-4 text-rose-500 animate-spin absolute right-3.5 top-1/2 -translate-y-1/2" />
                                    )}
                                </div>

                                {/* Autocomplete Dropdown */}
                                {searchResults.length > 0 && (
                                    <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-2xl shadow-xl z-20 max-h-56 overflow-y-auto divide-y divide-slate-100">
                                        {searchResults.map((prod) => (
                                            <button
                                                key={prod.id}
                                                type="button"
                                                onClick={() => handleSelectProduct(prod)}
                                                className="w-full p-2.5 flex items-center gap-3 text-left hover:bg-rose-50/60 transition-colors"
                                            >
                                                <img
                                                    src={prod.thumbnail}
                                                    alt={prod.name}
                                                    className="w-10 h-10 rounded-lg object-cover bg-slate-100 shrink-0"
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <div className="font-bold text-xs text-slate-900 truncate">
                                                        {prod.name}
                                                    </div>
                                                    <div className="text-[11px] text-slate-500 font-semibold">
                                                        {prod.formatted_price}
                                                    </div>
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Selected Product Banner */}
                            {selectedProduct && (
                                <div className="p-3 bg-rose-50/80 rounded-2xl border border-rose-100 flex items-center gap-3">
                                    <img
                                        src={selectedProduct.thumbnail}
                                        alt={selectedProduct.name}
                                        className="w-12 h-12 rounded-xl object-cover bg-white shrink-0 border border-rose-200/60"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="font-black text-xs text-slate-900 truncate">
                                            {selectedProduct.name}
                                        </div>
                                        <div className="text-[11px] text-slate-500">
                                            Harga Normal: <strong>{selectedProduct.formatted_price}</strong>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Harga Flash Sale (Rp) *
                                    </label>
                                    <input
                                        type="number"
                                        value={itemFormData.flash_sale_price}
                                        onChange={(e) => setItemFormData({ ...itemFormData, flash_sale_price: e.target.value })}
                                        required
                                        min={1}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-black text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                        Harga Normal / Coret (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        value={itemFormData.original_price}
                                        onChange={(e) => setItemFormData({ ...itemFormData, original_price: e.target.value })}
                                        min={0}
                                        className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Kuota Stok Promo *
                                </label>
                                <input
                                    type="number"
                                    value={itemFormData.quota_stock}
                                    onChange={(e) => setItemFormData({ ...itemFormData, quota_stock: e.target.value })}
                                    required
                                    min={1}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setIsItemModalOpen(false)}
                                    className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmittingItem || !selectedProduct}
                                    className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white text-xs font-bold shadow-md shadow-rose-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSubmittingItem && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Simpan Produk
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </EcommerceLayout>
    );
}
