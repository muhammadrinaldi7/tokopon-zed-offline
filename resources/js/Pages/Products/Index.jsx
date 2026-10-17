import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    PackageCheck,
    Search,
    Filter,
    Upload,
    Trash2,
    Image as ImageIcon,
    CheckCircle2,
    AlertCircle,
    X,
    Loader2,
    Store,
    Layers,
    Tag,
    Eye
} from 'lucide-react';

export default function ProductCurationIndex({
    products = { data: [] },
    categories = [],
    brands = [],
    filters = {},
    onlineWarehouseCount = 0
}) {
    const { businessUnits } = usePage().props;

    const [search, setSearch] = useState(filters?.search || '');
    const [selectedBu, setSelectedBu] = useState(filters?.bu || '');
    const [selectedCategory, setSelectedCategory] = useState(filters?.category || '');
    const [selectedBrand, setSelectedBrand] = useState(filters?.brand || '');
    const [onlyInStock, setOnlyInStock] = useState(filters?.in_stock ?? true);
    const [mediaFilter, setMediaFilter] = useState(filters?.media || 'all');

    // Modal Upload Foto State
    const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [coverFile, setCoverFile] = useState(null);
    const [coverPreview, setCoverPreview] = useState(null);
    const [galleryFiles, setGalleryFiles] = useState([]);
    const [isSubmitting, setIsSubmitting] = useState(false);

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
            '/crm/products',
            {
                search,
                bu: selectedBu,
                category: selectedCategory,
                brand: selectedBrand,
                in_stock: onlyInStock,
                media: mediaFilter,
                ...newFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleOpenUpload = (prod) => {
        setSelectedProduct(prod);
        setCoverFile(null);
        setCoverPreview(prod.has_custom_cover ? prod.thumbnail : null);
        setGalleryFiles([]);
        setIsUploadModalOpen(true);
    };

    const handleCoverChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setCoverFile(file);
            setCoverPreview(URL.createObjectURL(file));
        }
    };

    const handleSubmitPhotos = (e) => {
        e.preventDefault();
        if (!selectedProduct) return;

        setIsSubmitting(true);
        const data = new FormData();
        if (coverFile) {
            data.append('coverPhoto', coverFile);
        }
        for (let i = 0; i < galleryFiles.length; i++) {
            data.append('galleryPhotos[]', galleryFiles[i]);
        }

        router.post(`/crm/products/${selectedProduct.id}/photos`, data, {
            onSuccess: () => {
                setIsUploadModalOpen(false);
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false),
        });
    };

    const handleRemoveCover = () => {
        if (!selectedProduct) return;
        if (confirm(`Hapus foto sampul produk '${selectedProduct.name}'?`)) {
            router.delete(`/crm/products/${selectedProduct.id}/cover`, {
                preserveScroll: true,
                onSuccess: () => {
                    setCoverPreview(null);
                    setCoverFile(null);
                },
            });
        }
    };

    return (
        <EcommerceLayout title="Kurasi Produk Mobile">
            <Head title="Kurasi Produk & Foto Mobile" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header Toolbar */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-purple-500/10 text-purple-600">
                                <PackageCheck className="w-6 h-6" />
                            </span>
                            Kurasi Produk Mobile
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Atur foto sampul, galeri Cloudflare R2, dan ketersediaan stok online untuk aplikasi mobile.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <span className="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-600 shadow-2xs">
                            Gudang Online Terhubung: <strong>{onlineWarehouseCount}</strong>
                        </span>
                    </div>
                </div>

                {/* 2. Filter & Search Toolbar */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                    <div className="flex flex-wrap items-center gap-3">
                        {/* Search Input */}
                        <div className="relative flex-1 min-w-[240px]">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama produk, SKU item no..."
                                className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                            />
                        </div>

                        {/* Store / BU Filter */}
                        <select
                            value={selectedBu}
                            onChange={(e) => {
                                setSelectedBu(e.target.value);
                                applyFilters({ bu: e.target.value });
                            }}
                            className="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2"
                        >
                            <option value="">Semua Store (Syihab & GSK)</option>
                            {businessUnits?.map((bu) => (
                                <option key={bu.id} value={bu.id}>
                                    {bu.name}
                                </option>
                            ))}
                        </select>

                        {/* Category Filter */}
                        <select
                            value={selectedCategory}
                            onChange={(e) => {
                                setSelectedCategory(e.target.value);
                                applyFilters({ category: e.target.value });
                            }}
                            className="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2"
                        >
                            <option value="">Semua Kategori</option>
                            {categories.map((cat) => (
                                <option key={cat} value={cat}>
                                    {cat}
                                </option>
                            ))}
                        </select>

                        {/* Brand Filter */}
                        <select
                            value={selectedBrand}
                            onChange={(e) => {
                                setSelectedBrand(e.target.value);
                                applyFilters({ brand: e.target.value });
                            }}
                            className="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2"
                        >
                            <option value="">Semua Brand</option>
                            {brands.map((b) => (
                                <option key={b} value={b}>
                                    {b}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Secondary Filters (Stock & Media) */}
                    <div className="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100 text-xs">
                        <div className="flex items-center gap-4">
                            <label className="inline-flex items-center gap-2 cursor-pointer font-semibold text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={onlyInStock}
                                    onChange={(e) => {
                                        setOnlyInStock(e.target.checked);
                                        applyFilters({ in_stock: e.target.checked });
                                    }}
                                    className="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-slate-300"
                                />
                                <span>Hanya Tampilkan Stok Ready (Online &gt; 0)</span>
                            </label>
                        </div>

                        {/* Media Filter Tabs */}
                        <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-xl font-bold text-slate-600">
                            {[
                                { key: 'all', label: 'Semua Produk' },
                                { key: 'has_media', label: 'Sudah Ada Foto' },
                                { key: 'no_media', label: 'Belum Ada Foto' },
                            ].map((tab) => (
                                <button
                                    key={tab.key}
                                    onClick={() => {
                                        setMediaFilter(tab.key);
                                        applyFilters({ media: tab.key });
                                    }}
                                    className={`px-3 py-1 rounded-lg transition-all ${
                                        mediaFilter === tab.key
                                            ? 'bg-white text-purple-600 shadow-2xs'
                                            : 'hover:text-slate-900'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* 3. Products Grid */}
                {products?.data?.length === 0 ? (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-12 text-center">
                        <PackageCheck className="w-12 h-12 text-slate-300 mx-auto mb-2" />
                        <h3 className="text-base font-bold text-slate-800">Tidak ada produk ditemukan</h3>
                        <p className="text-xs text-slate-500 mt-1">
                            Coba ubah filter kategori, brand, atau matikan filter stok ready.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
                        {products.data.map((prod) => (
                            <div
                                key={prod.id}
                                className="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between group"
                            >
                                <div>
                                    {/* Thumbnail */}
                                    <div className="relative aspect-square w-full bg-slate-100 overflow-hidden">
                                        <img
                                            src={prod.thumbnail}
                                            alt={prod.name}
                                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                        />
                                        {/* Status Tag */}
                                        <div className="absolute top-2 left-2">
                                            {prod.has_custom_cover ? (
                                                <span className="px-1.5 py-0.5 rounded-md bg-emerald-600 text-white font-extrabold text-[9px] shadow-2xs">
                                                    Foto HD
                                                </span>
                                            ) : (
                                                <span className="px-1.5 py-0.5 rounded-md bg-slate-700/80 text-white font-medium text-[9px]">
                                                    Default
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="p-3">
                                        <span className="text-[10px] text-purple-600 font-bold block uppercase tracking-wider truncate">
                                            {prod.brand || prod.category || 'Gadget'}
                                        </span>
                                        <h4 className="font-extrabold text-xs text-slate-900 line-clamp-2 mt-0.5 leading-snug" title={prod.name}>
                                            {prod.name}
                                        </h4>
                                        <div className="text-xs font-black text-slate-900 mt-1">
                                            {prod.formatted_price}
                                        </div>
                                        <div className="flex items-center justify-between text-[10.5px] text-slate-400 mt-1">
                                            <span>Stok Online: <strong className="text-slate-700">{prod.stock}</strong></span>
                                            {prod.gallery?.length > 0 && (
                                                <span className="text-purple-600 font-bold">+{prod.gallery.length} foto</span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="p-2.5 pt-0">
                                    <button
                                        onClick={() => handleOpenUpload(prod)}
                                        className="w-full py-1.5 px-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors"
                                    >
                                        <Upload className="w-3.5 h-3.5" />
                                        <span>Kelola Foto</span>
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* 4. Pagination */}
                {products?.links && products.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1 mt-6">
                        {products.links.map((link, idx) => (
                            <button
                                key={idx}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
                                    link.active
                                        ? 'bg-purple-600 text-white shadow-2xs'
                                        : link.url
                                        ? 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                                        : 'text-slate-300 cursor-not-allowed'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Upload Foto Produk */}
            {isUploadModalOpen && selectedProduct && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsUploadModalOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-base font-black text-slate-900 tracking-tight">
                                    Kelola Foto Produk
                                </h3>
                                <p className="text-xs text-slate-500 truncate max-w-sm">
                                    {selectedProduct.name}
                                </p>
                            </div>
                            <button
                                onClick={() => setIsUploadModalOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitPhotos} className="space-y-4 mt-4">
                            {/* Sampul Utama */}
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Foto Sampul Utama (Cover)
                                </label>
                                <div className="flex items-center gap-3">
                                    <div className="w-24 h-24 rounded-2xl bg-slate-100 overflow-hidden border border-slate-200 shrink-0 relative group">
                                        <img
                                            src={coverPreview || selectedProduct.thumbnail}
                                            alt="Cover"
                                            className="w-full h-full object-cover"
                                        />
                                        {selectedProduct.has_custom_cover && (
                                            <button
                                                type="button"
                                                onClick={handleRemoveCover}
                                                className="absolute inset-0 bg-rose-600/80 text-white flex items-center justify-center text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity"
                                                title="Hapus sampul"
                                            >
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={handleCoverChange}
                                            className="text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer"
                                        />
                                        <p className="text-[10.5px] text-slate-400 mt-1">
                                            Format JPG/PNG/WEBP hingga 10MB. Otomatis diunggah ke Cloudflare R2.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Galeri Tambahan */}
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">
                                    Galeri Foto Tambahan (Bisa Pilih Banyak)
                                </label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    onChange={(e) => setGalleryFiles(Array.from(e.target.files))}
                                    className="text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                                />
                                {galleryFiles.length > 0 && (
                                    <p className="text-[11px] text-purple-600 font-bold mt-1">
                                        {galleryFiles.length} foto tambahan siap diunggah.
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setIsUploadModalOpen(false)}
                                    className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors"
                                >
                                    Tutup
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting || (!coverFile && galleryFiles.length === 0)}
                                    className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-purple-500/20 disabled:opacity-50 transition-all"
                                >
                                    {isSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Simpan Foto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </EcommerceLayout>
    );
}
