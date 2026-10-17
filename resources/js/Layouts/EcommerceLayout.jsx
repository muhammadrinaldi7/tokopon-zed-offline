import React, { useState, useEffect, useRef } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    MessageSquare,
    TrendingUp,
    Ticket,
    Zap,
    Image as ImageIcon,
    PackageCheck,
    Crown,
    CreditCard,
    Settings,
    ArrowLeft,
    Volume2,
    VolumeX,
    Bell,
    CheckCircle2,
    AlertCircle,
    Info,
    Store,
    Wifi,
    UserCheck
} from 'lucide-react';

export default function EcommerceLayout({ children, title = 'E-Commerce & CRM Suite' }) {
    const page = usePage();
    const { auth, businessUnits, flash } = page.props || {};
    const url = page.url || (typeof window !== 'undefined' ? window.location.pathname : '');
    const isChatPage = Boolean(url && url.startsWith('/crm/chat'));
    const [soundEnabled, setSoundEnabled] = useState(true);
    const [wsConnected, setWsConnected] = useState(true);
    const [toast, setToast] = useState(null);
    const audioRef = useRef(null);

    // Navigasi Terpadu 9 Sub-Modul
    const navItems = [
        {
            label: 'Live Chat CS',
            href: '/crm/chat',
            icon: MessageSquare,
            badge: null,
            color: 'indigo'
        },
        {
            label: 'Analitik Closing',
            href: '/crm/analytics',
            icon: TrendingUp,
            badge: null,
            color: 'emerald'
        },
        {
            label: 'Voucher Kupon',
            href: '/crm/vouchers',
            icon: Ticket,
            badge: null,
            color: 'amber'
        },
        {
            label: 'Flash Sale',
            href: '/crm/flash-sale',
            icon: Zap,
            badge: 'HOT',
            color: 'rose'
        },
        {
            label: 'Banner Promo',
            href: '/crm/banners',
            icon: ImageIcon,
            badge: null,
            color: 'blue'
        },
        {
            label: 'Kurasi Produk',
            href: '/crm/products',
            icon: PackageCheck,
            badge: null,
            color: 'purple'
        },
        {
            label: 'Member Loyalty',
            href: '/crm/loyalty',
            icon: Crown,
            badge: null,
            color: 'yellow'
        },
        {
            label: 'Rekening Bank',
            href: '/crm/payments',
            icon: CreditCard,
            badge: null,
            color: 'cyan'
        },
        {
            label: 'Pengaturan App',
            href: '/crm/settings',
            icon: Settings,
            badge: null,
            color: 'slate'
        },
    ];

    // Mainkan audio lonceng saat ada pesan baru
    const playChime = () => {
        if (!soundEnabled) return;
        try {
            const audio = document.getElementById('global-chat-chime');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {});
            }
        } catch (e) {
            console.warn('Audio chime playback restricted by browser policy:', e);
        }
    };

    // Dengarkan WebSocket Reverb secara global di Layout
    useEffect(() => {
        if (window.Echo) {
            // Channel private notifikasi CS untuk seluruh pesan masuk
            const channel = window.Echo.private('cs-chats');
            const handleMessage = (e) => {
                if (e.senderType === 'customer' || e.senderType === 'guest') {
                    playChime();
                    setToast({
                        type: 'info',
                        title: `Pesan Baru: ${e.user || 'Pelanggan'}`,
                        message: e.message || 'Mengirim pesan baru...',
                    });
                }
            };

            channel.listen('.message.sent', handleMessage);
            channel.listen('MessageSent', handleMessage);

            return () => {
                window.Echo.leave('cs-chats');
            };
        }
    }, [soundEnabled]);

    // Tangkap flash message dari Laravel session
    useEffect(() => {
        if (flash?.success) {
            setToast({ type: 'success', title: 'Berhasil', message: flash.success });
        } else if (flash?.error) {
            setToast({ type: 'error', title: 'Peringatan', message: flash.error });
        } else if (flash?.info) {
            setToast({ type: 'info', title: 'Informasi', message: flash.info });
        }
    }, [flash]);

    // Auto dismiss toast after 4 seconds
    useEffect(() => {
        if (toast) {
            const timer = setTimeout(() => setToast(null), 4000);
            return () => clearTimeout(timer);
        }
    }, [toast]);

    const activeBU = businessUnits?.find(
        (b) => b.id === auth?.user?.active_business_unit_id
    ) || businessUnits?.[0];

    return (
        <div className="flex flex-col h-screen bg-slate-100 overflow-hidden text-slate-800">
            {/* 1. Header Toolbar Persisten */}
            <header className="bg-white border-b border-slate-200/80 px-4 py-2.5 shadow-2xs z-20 shrink-0">
                <div className="flex items-center justify-between gap-3 flex-wrap">
                    {/* Brand & Context */}
                    <div className="flex items-center gap-3">
                        <a
                            href="/zoffline"
                            className="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-900 text-slate-600 hover:text-white flex items-center justify-center transition-all duration-200 border border-slate-200/80 shadow-2xs group"
                            title="Kembali ke Dashboard Utama POS Toko"
                        >
                            <ArrowLeft className="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
                        </a>

                        <div>
                            <div className="flex items-center gap-2">
                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold uppercase tracking-wider bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-2xs">
                                    <span>⚡</span>
                                    <span>Tokopon E-Commerce Suite</span>
                                </span>
                                <span className="text-[11px] text-slate-400 font-medium hidden sm:inline">
                                    • React Inertia CRM Hub
                                </span>
                            </div>
                            <h1 className="text-sm font-extrabold text-slate-900 tracking-tight mt-0.5">
                                {title}
                            </h1>
                        </div>
                    </div>

                    {/* Controls & Quick Badges */}
                    <div className="flex items-center gap-2">
                        {/* Status WebSocket Realtime */}
                        <div className="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-700 text-xs font-bold">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span className="text-[11px]">Reverb Realtime</span>
                        </div>

                        {/* Toggle Suara Chime */}
                        <button
                            type="button"
                            onClick={() => {
                                setSoundEnabled(!soundEnabled);
                                if (!soundEnabled) playChime();
                            }}
                            className={`px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center gap-1.5 transition-all shadow-2xs ${
                                soundEnabled
                                    ? 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                    : 'bg-rose-50 border-rose-200 text-rose-600'
                            }`}
                            title="Aktifkan atau bisukan suara lonceng pesan masuk"
                        >
                            {soundEnabled ? (
                                <>
                                    <Volume2 className="w-3.5 h-3.5 text-indigo-600" />
                                    <span className="text-[11px]">Suara: Aktif</span>
                                </>
                            ) : (
                                <>
                                    <VolumeX className="w-3.5 h-3.5 text-rose-500" />
                                    <span className="text-[11px]">Mute</span>
                                </>
                            )}
                        </button>

                        {/* Toko Aktif */}
                        {activeBU && (
                            <div className="hidden lg:flex items-center gap-1 px-2.5 py-1 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 text-[11px] font-extrabold">
                                <Store className="w-3 h-3" />
                                <span>{activeBU.name}</span>
                            </div>
                        )}

                        {/* User Profile Info */}
                        <div className="flex items-center gap-2 pl-2 border-l border-slate-200">
                            <div className="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-2xs">
                                {auth?.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'C'}
                            </div>
                            <div className="hidden sm:block text-left">
                                <span className="block text-xs font-bold text-slate-900 leading-none">
                                    {auth?.user?.name || 'Staf CS'}
                                </span>
                                <span className="text-[10px] text-slate-400 font-medium leading-none">
                                    Customer Service
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* 2. Sub-Navigation Tabs Bar (Instant Navigation without page reload) */}
                <nav className="mt-2.5 pt-2 border-t border-slate-100 flex items-center gap-1 overflow-x-auto scrollbar-none pb-0.5 text-xs font-bold">
                    {navItems.map((item) => {
                        const Icon = item.icon;
                        const isActive = Boolean(url && url.startsWith(item.href));

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                preserveScroll
                                className={`px-3 py-1.5 rounded-xl transition-all duration-150 flex items-center gap-1.5 shrink-0 ${
                                    isActive
                                        ? 'bg-slate-900 text-white shadow-xs font-extrabold'
                                        : 'bg-white hover:bg-slate-100/90 text-slate-600 hover:text-slate-900 border border-slate-200/50'
                                }`}
                            >
                                <Icon className={`w-3.5 h-3.5 ${isActive ? 'text-indigo-400' : 'text-slate-500'}`} />
                                <span className="text-[11.5px]">{item.label}</span>
                                {item.badge && (
                                    <span className="text-[9px] px-1.5 py-0.2 rounded-full font-black bg-rose-500 text-white animate-pulse">
                                        {item.badge}
                                    </span>
                                )}
                            </Link>
                        );
                    })}
                </nav>
            </header>

            {/* 3. Main Content View Area */}
            <main className={`flex-1 relative min-h-0 ${isChatPage ? 'overflow-hidden flex flex-col' : 'overflow-y-auto'}`}>
                {children}

                {/* Global Toast Alert */}
                {toast && (
                    <div className="fixed bottom-5 right-5 z-50 max-w-sm animate-in slide-in-from-bottom duration-200">
                        <div
                            className={`p-3.5 rounded-2xl shadow-xl border flex items-start gap-3 bg-white ${
                                toast.type === 'success'
                                    ? 'border-emerald-300'
                                    : toast.type === 'error'
                                    ? 'border-rose-300'
                                    : 'border-indigo-300'
                            }`}
                        >
                            <div className="mt-0.5">
                                {toast.type === 'success' ? (
                                    <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                ) : toast.type === 'error' ? (
                                    <AlertCircle className="w-5 h-5 text-rose-600" />
                                ) : (
                                    <Info className="w-5 h-5 text-indigo-600" />
                                )}
                            </div>
                            <div className="flex-1 min-w-0">
                                <h4 className="text-xs font-bold text-slate-900">{toast.title}</h4>
                                <p className="text-xs text-slate-600 mt-0.5 leading-relaxed truncate">{toast.message}</p>
                            </div>
                            <button
                                onClick={() => setToast(null)}
                                className="text-slate-400 hover:text-slate-600 text-xs font-bold ml-1"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                )}
            </main>
        </div>
    );
}
