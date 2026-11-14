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
    AlertCircle,
    Loader2,
    ShieldAlert,
    Info,
    ArrowRight
} from 'lucide-react';
import axios from 'axios';

export default function BuybackSimulatorModal({
    isOpen,
    onClose,
    onSendEstimateText
}) {
    if (!isOpen) return null;

    const [searchQuery, setSearchQuery] = useState('');
    const [devices, setDevices] = useState([]);
    const [isSearching, setIsSearching] = useState(false);

    const [selectedDevice, setSelectedDevice] = useState(null);
    const [tierInfo, setTierInfo] = useState(null);
    const [categories, setCategories] = useState([]);
    const [isLoadingRules, setIsLoadingRules] = useState(false);

    // Selected condition rules map
    const [selectedRules, setSelectedRules] = useState({});
    const [copied, setCopied] = useState(false);

    // Auto search when user types
    useEffect(() => {
        if (!selectedDevice) {
            const timer = setTimeout(() => {
                fetchDevices(searchQuery);
            }, 250);
            return () => clearTimeout(timer);
        }
    }, [searchQuery, selectedDevice]);

    const fetchDevices = async (q) => {
        setIsSearching(true);
        try {
            const res = await axios.get('/crm/chat/buyback/search-devices', {
                params: { q }
            });
            setDevices(res.data || []);
        } catch (err) {
            console.error('Gagal mengambil daftar perangkat buyback:', err);
        } finally {
            setIsSearching(false);
        }
    };

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
                setCategories(res.data.categories || []);
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
        setCategories([]);
        setSelectedRules({});
        setSearchQuery('');
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

    // Calculate live price and deductions
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

        categories.forEach((cat) => {
            (cat.items || []).forEach((item) => {
                const isSelected = cat.is_multiple
                    ? !!selectedRules[item.key]
                    : selectedRules[cat.category] === item.key;

                if (isSelected && Number(item.value) > 0) {
                    const deductionAmount = item.type === 'fixed'
                        ? Number(item.value)
                        : (basePrice * (Number(item.value) / 100));

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
    }, [selectedDevice, categories, selectedRules]);

    // Build the chat message to send or copy
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
            <div className="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl z-10 flex flex-col max-h-[92vh] overflow-hidden animate-in zoom-in-95 duration-200 border border-slate-200">
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
                                    CS Quick Tool
                                </span>
                            </h3>
                            <p className="text-xs text-slate-400">
                                Cek perkiraan harga beli riil customer sesuai kondisi tanpa upload foto & QC
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

                {/* 2. Body Area */}
                <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-slate-50/50">
                    {/* View A: Belum Pilih Perangkat (Search Box) */}
                    {!selectedDevice ? (
                        <div className="space-y-4">
                            <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                                <label className="block text-xs font-bold text-slate-700">
                                    Cari Tipe HP Customer
                                </label>
                                <div className="relative">
                                    <input
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        placeholder="Ketik tipe HP, contoh: iPhone 11, S23, Redmi Note 12..."
                                        autoFocus
                                        className="w-full pl-9 pr-9 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all"
                                    />
                                    <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                                    {isSearching && (
                                        <Loader2 className="w-4 h-4 text-emerald-600 animate-spin absolute right-3 top-3" />
                                    )}
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    💡 Masukkan kata kunci seri HP atau varian memori (RAM/Storage).
                                </p>
                            </div>

                            {/* Daftar Hasil Pencarian */}
                            <div className="space-y-2">
                                <span className="text-[11px] font-black uppercase text-slate-400 tracking-wider px-1 block">
                                    {isSearching ? 'Mencari Perangkat...' : `Hasil Perangkat (${devices.length})`}
                                </span>

                                {devices.length === 0 && !isSearching ? (
                                    <div className="bg-white p-8 rounded-2xl border border-slate-200 text-center space-y-2">
                                        <Smartphone className="w-8 h-8 text-slate-300 mx-auto" />
                                        <p className="text-xs font-bold text-slate-600">Tidak ada perangkat ditemukan</p>
                                        <p className="text-[11px] text-slate-400">Coba gunakan kata kunci model yang lebih umum</p>
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 gap-2 max-h-80 overflow-y-auto pr-1">
                                        {devices.map((dev) => (
                                            <button
                                                key={dev.id}
                                                type="button"
                                                onClick={() => handleSelectDevice(dev)}
                                                className="w-full p-3 bg-white hover:bg-emerald-50/60 rounded-xl border border-slate-200/80 hover:border-emerald-300 text-left transition-all flex items-center justify-between group shadow-2xs cursor-pointer"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-emerald-100 text-slate-600 group-hover:text-emerald-700 flex items-center justify-center shrink-0 font-bold transition-colors">
                                                        <Smartphone className="w-4 h-4" />
                                                    </div>
                                                    <div>
                                                        <h4 className="text-xs font-extrabold text-slate-900 group-hover:text-emerald-950">
                                                            {dev.name}
                                                        </h4>
                                                        <div className="flex items-center gap-2 mt-0.5 text-[10.5px] text-slate-400">
                                                            <span className="font-bold text-slate-500">{dev.brand}</span>
                                                            {dev.category && <span>• {dev.category}</span>}
                                                            {dev.proyek && dev.proyek !== '-' && (
                                                                <span className="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-semibold">
                                                                    {dev.proyek}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="text-right shrink-0">
                                                    <span className="text-xs font-black text-emerald-700 block">
                                                        {dev.formatted_price}
                                                    </span>
                                                    <span className="text-[10px] text-slate-400 font-medium">
                                                        Harga Acuan Mulus
                                                    </span>
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    ) : (
                        /* View B: Sudah Pilih Perangkat (Form Checklist Minus & Kalkulasi) */
                        <div className="space-y-5">
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
                                        className="text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-xl transition-all self-start sm:self-auto shrink-0 flex items-center gap-1.5"
                                    >
                                        <RotateCcw className="w-3.5 h-3.5" />
                                        <span>Ganti Unit</span>
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
                                    <p className="text-xs font-bold text-slate-600">Memuat standar pengurangan kondisi...</p>
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
                                            className="text-[11px] font-bold text-rose-600 hover:underline"
                                        >
                                            Reset Semua Centang
                                        </button>
                                    </div>

                                    {categories.map((cat, catIdx) => (
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
                                                        ? (item.type === 'fixed'
                                                            ? `-Rp ${Number(item.value).toLocaleString('id-ID')}`
                                                            : `-${item.value}%`)
                                                        : 'Mulus (Rp 0)';

                                                    return (
                                                        <button
                                                            key={item.key}
                                                            type="button"
                                                            onClick={() => toggleRule(item.key, cat.category, cat.is_multiple)}
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
                                    <span>Harga Acuan Awal</span>
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
                                            <div key={i} className="flex items-center justify-between text-emerald-100 text-[11.5px]">
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
                            Estimasi: <strong className="text-emerald-700">Rp {Number(calculation.netPrice).toLocaleString('id-ID')}</strong>
                        </div>

                        <div className="flex items-center gap-2 w-full sm:w-auto">
                            {/* Tombol Salin Pesan */}
                            <button
                                type="button"
                                onClick={handleCopy}
                                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-all shadow-2xs"
                                title="Salin format pesan rapi ke clipboard"
                            >
                                {copied ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
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
