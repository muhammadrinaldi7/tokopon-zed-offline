import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Search,
    X,
    Loader2,
    User,
    Clock,
    CheckCircle,
    Store,
    Phone,
    Award,
    Filter
} from 'lucide-react';

export default function ChatSidebar({
    conversations,
    activeId,
    businessUnits,
    filters,
    onSelectChat
}) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [isSearching, setIsSearching] = useState(false);
    const activeTab = filters?.tab || 'all';
    const activeBu = filters?.bu || '';

    // Debounce search update ke server
    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchTerm !== (filters?.search || '')) {
                setIsSearching(true);
                router.get(
                    '/crm/chat',
                    {
                        ...filters,
                        search: searchTerm,
                        active: activeId,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onFinish: () => setIsSearching(false),
                    }
                );
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchTerm]);

    // Ubah tab filter status
    const handleTabChange = (tabKey) => {
        router.get(
            '/crm/chat',
            { ...filters, tab: tabKey, active: activeId },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Ubah filter unit bisnis (Toko)
    const handleBuChange = (buId) => {
        router.get(
            '/crm/chat',
            { ...filters, bu: buId, active: activeId },
            { preserveState: true, preserveScroll: true }
        );
    };

    const clearSearch = () => {
        setSearchTerm('');
        router.get(
            '/crm/chat',
            { ...filters, search: '', active: activeId },
            { preserveState: true, preserveScroll: true }
        );
    };

    const tabs = [
        { key: 'all', label: 'Semua' },
        { key: 'my_chats', label: '👤 Chat Saya' },
        { key: 'unassigned', label: '⏳ Belum Diambil' },
        { key: 'open', label: 'Open' },
        { key: 'deal', label: 'Deal 🎉' },
        { key: 'follow_up', label: 'Follow Up' },
        { key: 'lost', label: 'Batal' },
    ];

    return (
        <aside className="w-full md:w-88 border-r border-slate-200/80 bg-slate-50/70 flex flex-col shrink-0 h-full overflow-hidden">
            {/* Header: Pencarian & Filter Toko */}
            <div className="p-3.5 border-b border-slate-200 bg-white space-y-2.5 shrink-0">
                {/* Search Bar Fleksibel */}
                <div className="relative">
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Cari nama, no HP (08/62), produk..."
                        className="w-full pl-9 pr-8 py-2 bg-slate-100 border border-slate-200/80 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none"
                    />
                    <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />

                    {isSearching ? (
                        <Loader2 className="w-3.5 h-3.5 text-indigo-500 animate-spin absolute right-3 top-2.5" />
                    ) : (
                        searchTerm && (
                            <button
                                type="button"
                                onClick={clearSearch}
                                className="absolute right-2.5 top-2 text-slate-400 hover:text-rose-500 transition-colors p-0.5"
                                title="Hapus pencarian"
                            >
                                <X className="w-3.5 h-3.5" />
                            </button>
                        )
                    )}
                </div>

                {/* Filter Unit Bisnis (Toko Baru vs Second GSK) */}
                <div className="flex items-center gap-1.5">
                    <div className="flex items-center gap-1 text-[11px] font-bold text-slate-500 shrink-0">
                        <Store className="w-3.5 h-3.5 text-indigo-600" />
                        <span>Toko:</span>
                    </div>
                    <select
                        value={activeBu}
                        onChange={(e) => handleBuChange(e.target.value)}
                        className="flex-1 text-[11px] font-bold bg-slate-100 hover:bg-slate-200/80 border border-slate-200 rounded-lg px-2 py-1 outline-none transition-colors cursor-pointer text-slate-700"
                    >
                        <option value="">Semua Toko Unit Bisnis</option>
                        {businessUnits?.map((bu) => (
                            <option key={bu.id} value={bu.id}>
                                {bu.name} {bu.is_second ? '(Second Hand)' : '(Baru / Retail)'}
                            </option>
                        ))}
                    </select>
                </div>

                {/* Status Filter Tabs Pills */}
                <div className="flex items-center gap-1 overflow-x-auto scrollbar-none pb-0.5 text-[11px] font-bold">
                    {tabs.map((tab) => {
                        const isActive = activeTab === tab.key;
                        return (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() => handleTabChange(tab.key)}
                                className={`px-2.5 py-1 rounded-lg transition-all shrink-0 ${
                                    isActive
                                        ? 'bg-slate-900 text-white shadow-2xs'
                                        : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
                                }`}
                            >
                                {tab.label}
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* List Percakapan Pelanggan */}
            <div className="flex-1 overflow-y-auto divide-y divide-slate-100/90">
                {conversations.length === 0 ? (
                    <div className="p-8 text-center text-slate-400 text-xs">
                        <p className="font-semibold">Tidak ada obrolan ditemukan.</p>
                        <p className="text-[11px] mt-1 text-slate-400">
                            Coba ubah kata kunci atau filter status.
                        </p>
                    </div>
                ) : (
                    conversations.map((conv) => {
                        const isSelected = parseInt(activeId) === parseInt(conv.id);
                        const unread = conv.unreadCount || 0;

                        return (
                            <div
                                key={conv.id}
                                onClick={() => onSelectChat(conv.id)}
                                className={`p-3.5 cursor-pointer transition-all duration-150 relative border-l-4 ${
                                    isSelected
                                        ? 'bg-white shadow-xs border-indigo-600'
                                        : 'hover:bg-white/80 border-transparent'
                                }`}
                            >
                                <div className="flex items-start gap-2.5">
                                    {/* User Avatar Initial */}
                                    <div
                                        className={`w-9 h-9 rounded-xl flex items-center justify-center font-black text-xs shrink-0 shadow-2xs ${
                                            conv.isGuest
                                                ? 'bg-amber-100 text-amber-800'
                                                : 'bg-indigo-100 text-indigo-700'
                                        }`}
                                    >
                                        {conv.userInitial}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between gap-1">
                                            <div className="flex items-center gap-1 min-w-0">
                                                <span className="text-xs font-bold text-slate-800 truncate">
                                                    {conv.userName}
                                                </span>
                                                {conv.isGuest && (
                                                    <span className="text-[9px] bg-amber-50 text-amber-700 border border-amber-200/80 px-1 rounded font-semibold shrink-0">
                                                        Guest
                                                    </span>
                                                )}
                                            </div>
                                            <span className="text-[10px] text-slate-400 font-mono shrink-0">
                                                {conv.lastTime}
                                            </span>
                                        </div>

                                        {/* Phone & Loyalty Badges */}
                                        {conv.guestPhone && (
                                            <div className="flex items-center gap-1 mt-0.5 flex-wrap">
                                                <span className="text-[10px] font-mono text-slate-600 bg-slate-100 px-1.5 py-0.2 rounded font-medium">
                                                    📱 {conv.guestPhone}
                                                </span>
                                                {conv.loyaltyTier && (
                                                    <span className="text-[9px] font-extrabold px-1 rounded bg-amber-50 text-amber-800 border border-amber-200">
                                                        ⭐ {conv.loyaltyTier} ({conv.loyaltyPoints}p)
                                                    </span>
                                                )}
                                            </div>
                                        )}

                                        {/* Last message preview */}
                                        <p className="text-xs text-slate-500 truncate mt-1">
                                            {conv.lastMessage}
                                        </p>

                                        {/* Bottom Status Row */}
                                        <div className="flex items-center justify-between mt-1.5 pt-0.5">
                                            <div className="flex items-center gap-1 flex-wrap">
                                                {/* Closing Status */}
                                                {conv.closingStatus === 'deal' ? (
                                                    <span className="px-1.5 py-0.2 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                        Deal {conv.closingAmount > 0 ? `Rp${conv.closingAmount.toLocaleString('id-ID')}` : ''}
                                                    </span>
                                                ) : conv.closingStatus === 'follow_up' ? (
                                                    <span className="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                        Follow Up
                                                    </span>
                                                ) : conv.closingStatus === 'lost' ? (
                                                    <span className="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                        Batal
                                                    </span>
                                                ) : (
                                                    <span className="px-1.5 py-0.2 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                                        Aktif
                                                    </span>
                                                )}

                                                {/* CS Assignment */}
                                                {conv.assignedToName ? (
                                                    <span
                                                        className={`px-1.5 py-0.2 rounded text-[9px] font-bold border ${
                                                            conv.isAssignedToMe
                                                                ? 'bg-indigo-100 text-indigo-700 border-indigo-200'
                                                                : 'bg-slate-100 text-slate-600 border-slate-200'
                                                        }`}
                                                    >
                                                        👤 {conv.isAssignedToMe ? 'Saya' : conv.assignedToName}
                                                    </span>
                                                ) : (
                                                    <span className="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                        ⏳ Bebas
                                                    </span>
                                                )}

                                                {conv.businessUnitName && (
                                                    <span className="text-[9px] font-mono text-slate-400 uppercase">
                                                        {conv.businessUnitName.substring(0, 10)}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Unread Message Pill */}
                                            {unread > 0 && (
                                                <span className="bg-indigo-600 text-white text-[10px] font-extrabold px-1.5 py-0.5 rounded-full flex items-center justify-center shrink-0 min-w-4.5 text-center">
                                                    {unread}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })
                )}
            </div>
        </aside>
    );
}
