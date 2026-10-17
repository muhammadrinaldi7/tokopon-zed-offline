import React, { useState, useEffect } from 'react';
import {
    X,
    Search,
    ShoppingBag,
    Loader2
} from 'lucide-react';
import axios from 'axios';

export default function ProductPickerModal({
    isOpen,
    onClose,
    onSelectProduct
}) {
    if (!isOpen) return null;

    const [searchQuery, setSearchQuery] = useState('');
    const [products, setProducts] = useState([]);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            fetchProducts(searchQuery);
        }, 250);

        return () => clearTimeout(timer);
    }, [searchQuery]);

    const fetchProducts = async (q) => {
        setIsLoading(true);
        try {
            const res = await axios.get('/crm/chat/search-products', {
                params: { q }
            });
            setProducts(res.data || []);
        } catch (e) {
            console.error('Gagal mengambil daftar produk:', e);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4 animate-in fade-in duration-200">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs transition-opacity"
                onClick={onClose}
            />

            {/* Modal Dialog */}
            <div className="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl p-6 z-10 space-y-4 animate-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                            <ShoppingBag className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="text-base font-extrabold text-slate-900">
                                Rekomendasikan Produk
                            </h3>
                            <p className="text-xs text-slate-400">
                                Pilih produk untuk dikirimkan sebagai kartu rekomendasi ke chat
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Search Bar */}
                <div className="relative">
                    <input
                        type="text"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        placeholder="Cari nama produk atau kode SKU / Item No..."
                        className="w-full pl-9 pr-8 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none"
                    />
                    <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                    {isLoading && (
                        <Loader2 className="w-4 h-4 text-indigo-500 animate-spin absolute right-3 top-3" />
                    )}
                </div>

                {/* Results List */}
                <div className="max-h-72 overflow-y-auto space-y-2 pr-1 divide-y divide-slate-50">
                    {products.length === 0 && !isLoading ? (
                        <p className="text-xs text-slate-400 italic text-center py-6">
                            Tidak ada produk ditemukan.
                        </p>
                    ) : (
                        products.map((prod) => (
                            <div
                                key={prod.id}
                                onClick={() => {
                                    onSelectProduct(prod);
                                    onClose();
                                }}
                                className="pt-2 flex items-center justify-between p-2 rounded-xl hover:bg-indigo-50/70 cursor-pointer transition-all border border-transparent hover:border-indigo-100 group"
                            >
                                <div className="flex items-center gap-3 min-w-0">
                                    <img
                                        src={prod.thumbnail}
                                        alt={prod.name}
                                        className="w-12 h-12 rounded-xl object-cover border border-slate-100 shrink-0"
                                    />
                                    <div className="min-w-0">
                                        <h4 className="text-xs font-bold text-slate-900 truncate">
                                            {prod.name}
                                        </h4>
                                        <span className="text-[10px] text-slate-400 font-mono">
                                            {prod.item_no}
                                        </span>
                                        <p className="text-xs font-black text-indigo-600 mt-0.5">
                                            {prod.formatted_price}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    className="px-3 py-1.5 bg-indigo-600 group-hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shrink-0 shadow-2xs"
                                >
                                    Pilih
                                </button>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
