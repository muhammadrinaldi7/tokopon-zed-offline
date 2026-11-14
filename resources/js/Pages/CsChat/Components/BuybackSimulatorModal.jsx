import React, { useState, useEffect, useMemo } from 'react';
import {
    X,
    Calculator,
    Search,
    Smartphone,
    Check,
    Send,
    Copy,
    RotateCcw,
    Loader2,
    Layers,
    Filter,
    ChevronRight,
    Tag,
    SlidersHorizontal
} from 'lucide-react';
import axios from 'axios';

export default function BuybackSimulatorModal({
    isOpen,
    onClose,
    onSendEstimateText
}) {
    if (!isOpen) return null;

    // Selection mode: 'filter' (Bertahap seperti SellPhone) atau 'search' (Cari Cepat)
    const [selectionMode, setSelectionMode] = useState('filter');

    // 1. Filter Options State (SellPhone style)
    const [brands, setBrands] = useState([]);
    const [selectedBrand, setSelectedBrand] = useState('');
    const [availableCategories, setAvailableCategories] = useState([]);
    const [selectedCategory, setSelectedCategory] = useState('');
    const [availableProyeks, setAvailableProyeks] = useState([]);
    const [selectedProyek, setSelectedProyek] = useState('');
    const [availableModels, setAvailableModels] = useState([]);
    const [modelSearchQuery, setModelSearchQuery] = useState('');
    const [isLoadingFilters, setIsLoadingFilters] = useState(false);

    // 2. Quick Search State
    const [quickSearchQuery, setQuickSearchQuery] = useState('');
    const [quickDevices, setQuickDevices] = useState([]);
    const [isSearchingQuick, setIsSearchingQuick] = useState(false);

    // 3. Selected Device & Condition Rules State
    const [selectedDevice, setSelectedDevice] = useState(null);
    const [tierInfo, setTierInfo] = useState(null);
    const [conditionCategories, setConditionCategories] = useState([]);
    const [isLoadingRules, setIsLoadingRules] = useState(false);
    const [selectedRules, setSelectedRules] = useState({});
    const [copied, setCopied] = useState(false);

    // Load initial brands when modal opens
    useEffect(() => {
        if (isOpen && brands.length === 0) {
            fetchInitialBrands();
        }
    }, [isOpen]);

    const fetchInitialBrands = async () => {
        setIsLoadingFilters(true);
        try {
            const res = await axios.get('/crm/chat/buyback/filter-options');
            setBrands(res.data?.brands || []);
        } catch (err) {
            console.error('Gagal mengambil daftar brand:', err);
        } finally {
            setIsLoadingFilters(false);
        }
    };

    // When selected brand changes, load categories & models for that brand
    const handleSelectBrand = async (brandId) => {
        if (selectedBrand === brandId) return;

        setSelectedBrand(brandId);
        setSelectedCategory('');
        setSelectedProyek('');
        setAvailableCategories([]);
        setAvailableProyeks([]);
        setAvailableModels([]);
        setModelSearchQuery('');
        setIsLoadingFilters(true);

        try {
            const res = await axios.get('/crm/chat/buyback/filter-options', {
                params: { brand: brandId }
            });
            setAvailableCategories(res.data?.categories || []);
            setAvailableModels(res.data?.models || []);
        } catch (err) {
            console.error('Gagal memuat kategori brand:', err);
        } finally {
            setIsLoadingFilters(false);
        }
    };

    // When category changes, load proyeks & models
    const handleSelectCategory = async (catName) => {
        if (selectedCategory === catName) {
            // Deselect category
            setSelectedCategory('');
            setSelectedProyek('');
            setAvailableProyeks([]);
            handleSelectBrand(selectedBrand);
            return;
        }

        setSelectedCategory(catName);
        setSelectedProyek('');
        setAvailableProyeks([]);
        setIsLoadingFilters(true);

        try {
            const res = await axios.get('/crm/chat/buyback/filter-options', {
                params: { brand: selectedBrand, category: catName }
            });
            setAvailableProyeks(res.data?.proyeks || []);
            setAvailableModels(res.data?.models || []);
        } catch (err) {
            console.error('Gagal memuat proyek/seri:', err);
        } finally {
            setIsLoadingFilters(false);
        }
    };

    // When proyek changes, filter models
    const handleSelectProyek = async (proyekName) => {
        const nextProyek = selectedProyek === proyekName ? '' : proyekName;
        setSelectedProyek(nextProyek);
        setIsLoadingFilters(true);

        try {
            const res = await axios.get('/crm/chat/buyback/filter-options', {
                params: {
                    brand: selectedBrand,
                    category: selectedCategory,
                    proyek: nextProyek
                }
            });
            setAvailableModels(res.data?.models || []);
        } catch (err) {
            console.error('Gagal memuat model perangkat:', err);
        } finally {
            setIsLoadingFilters(false);
        }
    };

    // Auto search for Quick Search tab
    useEffect(() => {
        if (selectionMode === 'search' && !selectedDevice) {
            const timer = setTimeout(() => {
                fetchQuickDevices(quickSearchQuery);
            }, 250);
            return () => clearTimeout(timer);
        }
    }, [quickSearchQuery, selectionMode, selectedDevice]);

    const fetchQuickDevices = async (q) => {
        setIsSearchingQuick(true);
        try {
            const res = await axios.get('/crm/chat/buyback/search-devices', {
                params: { q }
            });
            setQuickDevices(res.data || []);
        } catch (err) {
            console.error('Gagal mencari perangkat:', err);
        } finally {
            setIsSearchingQuick(false);
        }
    };

    // Select Device handler (loads rules from backend)
    const handleSelectDevice = async (device) => {
        setIsLoadingRules(true);
        setSelectedDevice(device);
        setSelectedRules({});
        setCopied(false);

        try {
            const res = await axios.get(`/crm/chat/buyback/device-rules/${device.id}`);
            if (res.data?.success) {
                setSelectedDevice(res.data.device);
                setTierInfo(res.data.tier);
                setConditionCategories(res.data.categories || []);
            }
        } catch (err) {
            console.error('Gagal memuat aturan tier perangkat:', err);
        } finally {
            setIsLoadingRules(false);
        }
    };

    const handleResetDevice = () => {
        setSelectedDevice(null);
        setTierInfo(null);
        setConditionCategories([]);
        setSelectedRules({});
        setCopied(false);
    };

    const toggleRule = (ruleKey, category, isMultiple) => {
        setSelectedRules((prev) => {
            const next = { ...prev };
            if (isMultiple) {
                if (next[ruleKey]) {
                    delete next[ruleKey];
                } else {
                    next[ruleKey] = true;
                }
            } else {
                if (next[category] === ruleKey) {
                    delete next[category];
                } else {
                    next[category] = ruleKey;
                }
            }
            return next;
        });
    };

    // Filtered models by local search query inside the selected brand/cat
    const filteredAvailableModels = useMemo(() => {
        if (!modelSearchQuery) return availableModels;
        const q = modelSearchQuery.toLowerCase();
        return availableModels.filter((m) =>
            (m.name || '').toLowerCase().includes(q)
        );
    }, [availableModels, modelSearchQuery]);

    // Live price calculation
    const calculation = useMemo(() => {
        if (!selectedDevice) {
            return {
                basePrice: 0,
                totalDeductions: 0,
                netPrice: 0,
                deductionsList: [],
            };
        }

        const basePrice = Number(selectedDevice.base_price || 0);
        let totalDeductions = 0;
        const deductionsList = [];

        conditionCategories.forEach((cat) => {
            (cat.items || []).forEach((item) => {
                const isSelected = cat.is_multiple
                    ? !!selectedRules[item.key]
                    : selectedRules[cat.category] === item.key;

                if (isSelected && Number(item.value) > 0) {
                    const deductionAmount =
                        item.type === 'fixed'
                            ? Number(item.value)
                            : basePrice * (Number(item.value) / 100);

                    totalDeductions += deductionAmount;
                    deductionsList.push({
                        category: cat.category,
                        name: item.name,
                        amount: Math.round(deductionAmount),
                        type: item.type,
                        rawValue: item.value,
                    });
                }
            });
        });

        const netPrice = Math.max(0, Math.round(basePrice - totalDeductions));

        return {
            basePrice,
            totalDeductions: Math.round(totalDeductions),
            netPrice,
            deductionsList,
        };
    }, [selectedDevice, conditionCategories, selectedRules]);

    // Format chat message
    const generatedEstimateMessage = useMemo(() => {
        if (!selectedDevice) return '';

        const devName = selectedDevice.name || 'Perangkat HP';
        const formattedBase = `Rp ${Number(calculation.basePrice).toLocaleString('id-ID')}`;
        const formattedNet = `Rp ${Number(calculation.netPrice).toLocaleString('id-ID')}`;

        let deductionsText = '';
        if (calculation.deductionsList.length > 0) {
            deductionsText = calculation.deductionsList
                .map((d) => ` • ${d.name}: -Rp ${d.amount.toLocaleString('id-ID')}`)
                .join('\n');
        } else {
            deductionsText = ' • Kondisi Mulus Tanpa Pengurangan Minus (0)';
        }

        return `Halo Kak! 👋\nBerikut estimasi taksiran harga beli untuk unit Anda:\n\n📱 *Unit*: ${devName}\n💰 *Harga Acuan Kondisi Mulus*: ${formattedBase}\n\n🔍 *Penyesuaian Kondisi Unit*:\n${deductionsText}\n--------------------------------------------\n💵 *Estimasi Harga Beli*: *${formattedNet}*\n\n📌 *Catatan*:\nEstimasi ini adalah perhitungan terdekat berdasarkan informasi kondisi yang disampaikan. Harga final akan dipastikan oleh tim teknisi kami setelah pengecekan fisik langsung di outlet Tokopon ya Kak. Yuk langsung mampir ke toko kami hari ini! 😊`;
    }, [selectedDevice, calculation]);

    const handleCopy = () => {
        if (!generatedEstimateMessage) return;
        navigator.clipboard.writeText(generatedEstimateMessage);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
    };

    const handleSendToChat = () => {
        if (onSendEstimateText && generatedEstimateMessage) {
            onSendEstimateText(generatedEstimateMessage);
            onClose();
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-3 sm:p-4 animate-in fade-in duration-200">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs transition-opacity"
                onClick={onClose}
            />

            {/* Modal Dialog */}
            <div className="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl z-10 flex flex-col max-h-[92vh] overflow-hidden animate-in zoom-in-95 duration-200 border border-slate-200">
                {/* 1. Header Modal */}
                <div className="flex items-center justify-between p-4 sm:p-5 border-b border-slate-100 bg-white shrink-0">
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-md shadow-emerald-500/20">
                            <Calculator className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                <span>Simulasi Taksiran Buyback HP</span>
                                <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    SellPhone Filter
                                </span>
                            </h3>
                            <p className="text-xs text-slate-400">
                                Pilih brand, kategori, & model HP customer untuk kalkulasi harga beli riil
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* 2. Body Area */}
                <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-slate-50/50">
                    {/* View A: Belum Pilih Perangkat (Tahapan Filter / Quick Search) */}
                    {!selectedDevice ? (
                        <div className="space-y-4">
                            {/* Mode Switcher Tab */}
                            <div className="flex items-center gap-2 p-1 bg-slate-100 rounded-xl border border-slate-200/80 max-w-md">
                                <button
                                    type="button"
                                    onClick={() => setSelectionMode('filter')}
                                    className={`flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                                        selectionMode === 'filter'
                                            ? 'bg-white text-emerald-700 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    <Layers className="w-3.5 h-3.5" />
                                    <span>Filter Bertahap (SellPhone)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setSelectionMode('search')}
                                    className={`flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                                        selectionMode === 'search'
                                            ? 'bg-white text-emerald-700 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    <Search className="w-3.5 h-3.5" />
                                    <span>Pencarian Cepat</span>
                                </button>
                            </div>

                            {/* TAB 1: Filter Bertahap Persis SellPhone (Brand -> Kategori -> Proyek -> Model) */}
                            {selectionMode === 'filter' && (
                                <div className="space-y-4">
                                    {/* 1.1 Pilih Brand */}
                                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <span className="w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] font-black flex items-center justify-center">
                                                    1
                                                </span>
                                                <label className="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                                    Pilih Brand HP
                                                </label>
                                            </div>
                                            {selectedBrand && (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedBrand('');
                                                        setSelectedCategory('');
                                                        setSelectedProyek('');
                                                        setAvailableCategories([]);
                                                        setAvailableProyeks([]);
                                                        setAvailableModels([]);
                                                    }}
                                                    className="text-[11px] font-bold text-rose-600 hover:underline cursor-pointer"
                                                >
                                                    Ganti Brand
                                                </button>
                                            )}
                                        </div>

                                        {isLoadingFilters && brands.length === 0 ? (
                                            <div className="py-6 text-center">
                                                <Loader2 className="w-5 h-5 text-emerald-600 animate-spin mx-auto" />
                                                <span className="text-xs text-slate-400 mt-2 block font-medium">
                                                    Memuat pilihan brand...
                                                </span>
                                            </div>
                                        ) : (
                                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
                                                {brands.map((brand) => {
                                                    const isChecked = selectedBrand === brand.id;
                                                    return (
                                                        <button
                                                            key={brand.id}
                                                            type="button"
                                                            onClick={() => handleSelectBrand(brand.id)}
                                                            className={`relative p-3 rounded-xl border-2 text-left transition-all group overflow-hidden cursor-pointer flex flex-col justify-between min-h-[75px] ${
                                                                isChecked
                                                                    ? 'border-emerald-600 bg-emerald-50/70 shadow-xs scale-[1.02]'
                                                                    : 'border-slate-100 bg-slate-50/60 hover:border-emerald-300 hover:bg-white'
                                                            }`}
                                                        >
                                                            {/* Watermark Monogram */}
                                                            <span className="absolute -right-1 -bottom-2 text-4xl font-black text-slate-900/[0.04] group-hover:text-emerald-900/[0.08] select-none pointer-events-none uppercase">
                                                                {brand.name.slice(0, 1)}
                                                            </span>

                                                            <div className="flex items-center justify-between z-10">
                                                                <span className="text-[10px] font-black uppercase text-slate-400 group-hover:text-emerald-700">
                                                                    {brand.item_count} Unit
                                                                </span>
                                                                {isChecked && (
                                                                    <div className="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                                                                        <Check className="w-2.5 h-2.5 stroke-[3]" />
                                                                    </div>
                                                                )}
                                                            </div>

                                                            <div className="z-10 mt-2">
                                                                <h4
                                                                    className={`text-xs font-black uppercase tracking-wider truncate ${
                                                                        isChecked
                                                                            ? 'text-emerald-900 font-black'
                                                                            : 'text-slate-800 group-hover:text-emerald-700'
                                                                    }`}
                                                                    title={brand.name}
                                                                >
                                                                    {brand.name}
                                                                </h4>
                                                            </div>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>

                                    {/* 1.2 Pilih Kategori & Proyek (Muncul setelah Brand dipilih) */}
                                    {selectedBrand && (
                                        <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4 animate-in fade-in duration-200">
                                            {/* Sub-step 2: Kategori */}
                                            <div className="space-y-2">
                                                <div className="flex items-center gap-2">
                                                    <span className="w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] font-black flex items-center justify-center">
                                                        2
                                                    </span>
                                                    <label className="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                                        Kategori ({selectedBrand})
                                                    </label>
                                                </div>

                                                <div className="flex flex-wrap gap-2 pt-1">
                                                    {availableCategories.map((cat) => {
                                                        const isSelected = selectedCategory === cat;
                                                        return (
                                                            <button
                                                                key={cat}
                                                                type="button"
                                                                onClick={() => handleSelectCategory(cat)}
                                                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all border cursor-pointer flex items-center gap-1.5 ${
                                                                    isSelected
                                                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                                                        : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 hover:border-slate-300'
                                                                }`}
                                                            >
                                                                {isSelected && <Check className="w-3 h-3" />}
                                                                <span>{cat}</span>
                                                            </button>
                                                        );
                                                    })}
                                                    {availableCategories.length === 0 && !isLoadingFilters && (
                                                        <span className="text-xs text-slate-400 italic">
                                                            Semua kategori langsung tersedia di daftar model.
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Sub-step 3: Status Perangkat / Proyek (Jika ada proyek) */}
                                            {availableProyeks.length > 0 && (
                                                <div className="space-y-2 pt-3 border-t border-slate-100">
                                                    <div className="flex items-center gap-2">
                                                        <span className="w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] font-black flex items-center justify-center">
                                                            3
                                                        </span>
                                                        <label className="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                                            Status Perangkat / Seri
                                                        </label>
                                                    </div>

                                                    <div className="flex flex-wrap gap-2 pt-1">
                                                        {availableProyeks.map((proyek) => {
                                                            const isSelected = selectedProyek === proyek;
                                                            return (
                                                                <button
                                                                    key={proyek}
                                                                    type="button"
                                                                    onClick={() => handleSelectProyek(proyek)}
                                                                    className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all border cursor-pointer flex items-center gap-1.5 ${
                                                                        isSelected
                                                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                                                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 hover:border-slate-300'
                                                                    }`}
                                                                >
                                                                    {isSelected && <Check className="w-3 h-3" />}
                                                                    <span>{proyek}</span>
                                                                </button>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* 1.3 Daftar Model HP (Muncul setelah Brand dipilih) */}
                                    {selectedBrand && (
                                        <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3 animate-in fade-in duration-200">
                                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <div className="flex items-center gap-2">
                                                    <span className="w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] font-black flex items-center justify-center">
                                                        {availableProyeks.length > 0 ? '4' : '3'}
                                                    </span>
                                                    <label className="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                                        Pilih Model HP ({filteredAvailableModels.length} Tipe)
                                                    </label>
                                                </div>

                                                {/* Local Search input within selected filters */}
                                                <div className="relative w-full sm:w-64">
                                                    <input
                                                        type="text"
                                                        value={modelSearchQuery}
                                                        onChange={(e) => setModelSearchQuery(e.target.value)}
                                                        placeholder="Cari tipe/memori (misal: 128GB)..."
                                                        className="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all"
                                                    />
                                                    <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" />
                                                </div>
                                            </div>

                                            {isLoadingFilters ? (
                                                <div className="py-8 text-center">
                                                    <Loader2 className="w-6 h-6 text-emerald-600 animate-spin mx-auto" />
                                                    <p className="text-xs text-slate-400 mt-2">Memuat daftar model HP...</p>
                                                </div>
                                            ) : filteredAvailableModels.length === 0 ? (
                                                <div className="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                                    <Smartphone className="w-8 h-8 text-slate-300 mx-auto" />
                                                    <p className="text-xs font-bold text-slate-600 mt-2">
                                                        Tidak ada model yang cocok
                                                    </p>
                                                    <p className="text-[11px] text-slate-400">
                                                        Coba sesuaikan pilihan filter kategori atau kata kunci pencarian.
                                                    </p>
                                                </div>
                                            ) : (
                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto pr-1">
                                                    {filteredAvailableModels.map((dev) => (
                                                        <button
                                                            key={dev.id}
                                                            type="button"
                                                            onClick={() => handleSelectDevice(dev)}
                                                            className="w-full p-3 bg-white hover:bg-emerald-50/70 rounded-xl border border-slate-200 hover:border-emerald-400 text-left transition-all flex items-center justify-between group shadow-2xs cursor-pointer"
                                                        >
                                                            <div className="min-w-0 pr-2">
                                                                <h4 className="text-xs font-extrabold text-slate-900 group-hover:text-emerald-950 truncate">
                                                                    {dev.name}
                                                                </h4>
                                                                <div className="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-400">
                                                                    <span className="font-bold text-slate-500">
                                                                        {dev.brand}
                                                                    </span>
                                                                    {dev.category && <span>• {dev.category}</span>}
                                                                    {dev.proyek && dev.proyek !== '-' && (
                                                                        <span className="px-1 py-0.2 rounded bg-slate-100 text-slate-600 font-semibold">
                                                                            {dev.proyek}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>

                                                            <div className="text-right shrink-0">
                                                                <span className="text-xs font-black text-emerald-700 block">
                                                                    {dev.formatted_price}
                                                                </span>
                                                                <span className="text-[9.5px] text-slate-400 font-medium">
                                                                    Grade A
                                                                </span>
                                                            </div>
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* TAB 2: Pencarian Cepat Langsung (Free Text Search) */}
                            {selectionMode === 'search' && (
                                <div className="space-y-4">
                                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                                        <label className="block text-xs font-bold text-slate-700">
                                            Cari Cepat Tipe HP Customer
                                        </label>
                                        <div className="relative">
                                            <input
                                                type="text"
                                                value={quickSearchQuery}
                                                onChange={(e) => setQuickSearchQuery(e.target.value)}
                                                placeholder="Ketik tipe HP, contoh: iPhone 11, S23, Infinix GT..."
                                                autoFocus
                                                className="w-full pl-9 pr-9 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all"
                                            />
                                            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                                            {isSearchingQuick && (
                                                <Loader2 className="w-4 h-4 text-emerald-600 animate-spin absolute right-3 top-3" />
                                            )}
                                        </div>
                                        <p className="text-[11px] text-slate-400">
                                            💡 Masukkan kata kunci model HP atau kapasitas penyimpanan.
                                        </p>
                                    </div>

                                    {/* Hasil Quick Search */}
                                    <div className="space-y-2">
                                        <span className="text-[11px] font-black uppercase text-slate-400 tracking-wider px-1 block">
                                            {isSearchingQuick
                                                ? 'Mencari Perangkat...'
                                                : `Hasil Pencarian (${quickDevices.length})`}
                                        </span>

                                        {quickDevices.length === 0 && !isSearchingQuick ? (
                                            <div className="bg-white p-8 rounded-2xl border border-slate-200 text-center space-y-2">
                                                <Smartphone className="w-8 h-8 text-slate-300 mx-auto" />
                                                <p className="text-xs font-bold text-slate-600">
                                                    Tidak ada perangkat ditemukan
                                                </p>
                                                <p className="text-[11px] text-slate-400">
                                                    Coba gunakan kata kunci model yang lebih umum atau gunakan Tab Filter Bertahap
                                                </p>
                                            </div>
                                        ) : (
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto pr-1">
                                                {quickDevices.map((dev) => (
                                                    <button
                                                        key={dev.id}
                                                        type="button"
                                                        onClick={() => handleSelectDevice(dev)}
                                                        className="w-full p-3 bg-white hover:bg-emerald-50/70 rounded-xl border border-slate-200/80 hover:border-emerald-300 text-left transition-all flex items-center justify-between group shadow-2xs cursor-pointer"
                                                    >
                                                        <div className="min-w-0 pr-2">
                                                            <h4 className="text-xs font-extrabold text-slate-900 group-hover:text-emerald-950 truncate">
                                                                {dev.name}
                                                            </h4>
                                                            <div className="flex items-center gap-1.5 mt-0.5 text-[10.5px] text-slate-400">
                                                                <span className="font-bold text-slate-500">{dev.brand}</span>
                                                                {dev.category && <span>• {dev.category}</span>}
                                                            </div>
                                                        </div>

                                                        <div className="text-right shrink-0">
                                                            <span className="text-xs font-black text-emerald-700 block">
                                                                {dev.formatted_price}
                                                            </span>
                                                            <span className="text-[9.5px] text-slate-400 font-medium">
                                                                Grade A
                                                            </span>
                                                        </div>
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : (
                        /* View B: Sudah Pilih Perangkat (Form Checklist Minus & Kalkulasi Real-time) */
                        <div className="space-y-5 animate-in fade-in duration-200">
                            {/* Card Info Perangkat Terpilih */}
                            <div className="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div className="flex items-start gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 font-bold">
                                            <Smartphone className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <h4 className="text-sm font-black text-slate-900">
                                                    {selectedDevice.name}
                                                </h4>
                                                {tierInfo?.name && (
                                                    <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                                        {tierInfo.name}
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                Brand: <strong>{selectedDevice.brand}</strong> | Kategori: {selectedDevice.category}
                                            </p>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={handleResetDevice}
                                        className="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-xl transition-all self-start sm:self-auto shrink-0 flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <RotateCcw className="w-3.5 h-3.5" />
                                        <span>Ganti Model HP</span>
                                    </button>
                                </div>

                                <div className="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span className="text-xs font-bold text-slate-500">
                                        Harga Acuan Unit Sempurna (100% Grade A):
                                    </span>
                                    <span className="text-sm font-black text-slate-900">
                                        Rp {Number(selectedDevice.base_price).toLocaleString('id-ID')}
                                    </span>
                                </div>
                            </div>

                            {/* Checklist Minus Kondisi (Categorized) */}
                            {isLoadingRules ? (
                                <div className="bg-white p-8 rounded-2xl border border-slate-200 text-center space-y-2">
                                    <Loader2 className="w-6 h-6 text-emerald-600 animate-spin mx-auto" />
                                    <p className="text-xs font-bold text-slate-600">
                                        Memuat standar kualifikasi tier & minus kondisi...
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-black uppercase text-slate-700 tracking-wider">
                                            Centang Minus Kondisi Unit Customer
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => setSelectedRules({})}
                                            className="text-[11px] font-bold text-rose-600 hover:underline cursor-pointer"
                                        >
                                            Reset Semua Centang
                                        </button>
                                    </div>

                                    {conditionCategories.map((cat, catIdx) => (
                                        <div
                                            key={catIdx}
                                            className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-2.5"
                                        >
                                            <div className="flex items-center justify-between">
                                                <h5 className="text-xs font-extrabold text-slate-900 flex items-center gap-2">
                                                    <span>{cat.category}</span>
                                                    <span className="text-[10px] font-semibold text-slate-400">
                                                        {cat.is_multiple ? '(Bisa pilih banyak)' : '(Pilih salah satu)'}
                                                    </span>
                                                </h5>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                {(cat.items || []).map((item) => {
                                                    const isSelected = cat.is_multiple
                                                        ? !!selectedRules[item.key]
                                                        : selectedRules[cat.category] === item.key;

                                                    const isDeduction = Number(item.value) > 0;
                                                    const deductionLabel = isDeduction
                                                        ? item.type === 'fixed'
                                                            ? `-Rp ${Number(item.value).toLocaleString('id-ID')}`
                                                            : `-${item.value}%`
                                                        : 'Mulus (Rp 0)';

                                                    return (
                                                        <button
                                                            key={item.key}
                                                            type="button"
                                                            onClick={() =>
                                                                toggleRule(item.key, cat.category, cat.is_multiple)
                                                            }
                                                            className={`px-3 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer select-none border ${
                                                                isSelected
                                                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                                                    : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 hover:border-slate-300'
                                                            }`}
                                                            title={item.description || item.name}
                                                        >
                                                            {isSelected && <Check className="w-3.5 h-3.5 text-white" />}
                                                            <span>{item.name}</span>
                                                            <span
                                                                className={`text-[10px] px-1.5 py-0.2 rounded-md ${
                                                                    isSelected
                                                                        ? 'bg-emerald-700/80 text-emerald-100'
                                                                        : isDeduction
                                                                          ? 'bg-rose-50 text-rose-600 font-extrabold border border-rose-200'
                                                                          : 'bg-emerald-50 text-emerald-700 font-extrabold border border-emerald-200'
                                                                }`}
                                                            >
                                                                {deductionLabel}
                                                            </span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Summary Box Real-time */}
                            <div className="bg-linear-to-br from-emerald-500 to-teal-700 text-white p-5 rounded-2xl shadow-lg shadow-emerald-500/20 space-y-3">
                                <div className="flex items-center justify-between text-xs opacity-90 pb-2 border-b border-white/20">
                                    <span>Harga Acuan Awal (100% Mulus)</span>
                                    <span className="font-bold">
                                        Rp {Number(calculation.basePrice).toLocaleString('id-ID')}
                                    </span>
                                </div>

                                {calculation.deductionsList.length > 0 && (
                                    <div className="space-y-1.5 py-1 text-xs">
                                        <span className="font-bold text-emerald-100 block">
                                            Rincian Potongan Minus ({calculation.deductionsList.length}):
                                        </span>
                                        {calculation.deductionsList.map((d, i) => (
                                            <div
                                                key={i}
                                                className="flex items-center justify-between text-emerald-100 text-[11.5px]"
                                            >
                                                <span>• {d.name}</span>
                                                <span className="font-bold text-white">
                                                    -Rp {d.amount.toLocaleString('id-ID')}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <div className="pt-2 border-t border-white/20 flex items-center justify-between">
                                    <div>
                                        <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-100 block">
                                            Estimasi Harga Beli Riil
                                        </span>
                                        <span className="text-[10.5px] opacity-80">
                                            {calculation.deductionsList.length} minus kondisi dipilih
                                        </span>
                                    </div>
                                    <div className="text-right">
                                        <span className="text-2xl font-black text-white tracking-tight block">
                                            Rp {Number(calculation.netPrice).toLocaleString('id-ID')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* 3. Footer Actions */}
                {selectedDevice && (
                    <div className="p-4 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                        <div className="text-xs text-slate-500">
                            Estimasi:{' '}
                            <strong className="text-emerald-700">
                                Rp {Number(calculation.netPrice).toLocaleString('id-ID')}
                            </strong>
                        </div>

                        <div className="flex items-center gap-2 w-full sm:w-auto">
                            {/* Tombol Salin Pesan */}
                            <button
                                type="button"
                                onClick={handleCopy}
                                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-all shadow-2xs cursor-pointer"
                                title="Salin format pesan rapi ke clipboard"
                            >
                                {copied ? (
                                    <Check className="w-4 h-4 text-emerald-600" />
                                ) : (
                                    <Copy className="w-4 h-4" />
                                )}
                                <span>{copied ? 'Tersalin!' : 'Salin Teks'}</span>
                            </button>

                            {/* Tombol Kirim Langsung ke Chat */}
                            {onSendEstimateText && (
                                <button
                                    type="button"
                                    onClick={handleSendToChat}
                                    className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition-all cursor-pointer"
                                    title="Kirim pesan estimasi harga langsung ke obrolan customer ini"
                                >
                                    <Send className="w-4 h-4" />
                                    <span>Kirim ke Chat</span>
                                </button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
