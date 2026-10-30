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
    UserCheck,
    Users,
    Share2,
    Menu,
    X,
    PanelLeftClose,
    PanelLeftOpen
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

    // State Sidebar Collapsible (Persisten via LocalStorage)
    const [isCollapsed, setIsCollapsed] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('tokopon_crm_sidebar_collapsed') === 'true';
        }
        return false;
    });
    const [mobileOpen, setMobileOpen] = useState(false);

    const toggleSidebar = () => {
        setIsCollapsed((prev) => {
            const next = !prev;
            if (typeof window !== 'undefined') {
                localStorage.setItem('tokopon_crm_sidebar_collapsed', String(next));
            }
            return next;
        });
    };

    // Keyboard shortcut Ctrl+B / Cmd+B untuk toggle sidebar
    useEffect(() => {
        const handleKeyDown = (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                e.preventDefault();
                toggleSidebar();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // Tutup mobile drawer saat navigasi berubah
    useEffect(() => {
        setMobileOpen(false);
    }, [url]);

    // Navigasi Terstruktur 4 Kelompok (10 Sub-Modul)
    const navigationGroups = [
        {
            title: 'Layanan & Chat',
            items: [
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
            ]
        },
        {
            title: 'Promosi & Penjualan',
            items: [
                {
                    label: 'Flash Sale',
                    href: '/crm/flash-sale',
                    icon: Zap,
                    badge: 'HOT',
                    color: 'rose'
                },
                {
                    label: 'Voucher Kupon',
                    href: '/crm/vouchers',
                    icon: Ticket,
                    badge: null,
                    color: 'amber'
                },
                {
                    label: 'Banner Promo',
                    href: '/crm/banners',
                    icon: ImageIcon,
                    badge: null,
                    color: 'blue'
                },
            ]
        },
        {
            title: 'Katalog & Pelanggan',
            items: [
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
                    label: 'Referral & Komisi',
                    href: '/crm/referral',
                    icon: Users,
                    badge: null,
                    color: 'emerald'
                },
            ]
        },
        {
            title: 'Sistem & Konfigurasi',
            items: [
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
            ]
        }
    ];

    // State Izin Desktop Notification Browser & Audio Context
    const [desktopNotifStatus, setDesktopNotifStatus] = useState('default');
    const sharedAudioCtxRef = useRef(null);
    const titleFlashingTimerRef = useRef(null);
    const seenMessageIdsRef = useRef(new Set());
    const lastPolledIdRef = useRef(0);
    const lastChimeTimeRef = useRef(0);

    const initAudioContext = () => {
        try {
            if (!sharedAudioCtxRef.current && typeof window !== 'undefined' && (window.AudioContext || window.webkitAudioContext)) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                sharedAudioCtxRef.current = new AudioCtx();
            }
            if (sharedAudioCtxRef.current && sharedAudioCtxRef.current.state === 'suspended') {
                sharedAudioCtxRef.current.resume();
            }
        } catch (e) {
            console.warn('AudioContext init error:', e);
        }
        return sharedAudioCtxRef.current;
    };

    // Sintesis lonceng cadangan via Web Audio API jika audio tag diblokir browser
    const playSynthChime = () => {
        try {
            const ctx = initAudioContext();
            if (!ctx) return;
            const now = ctx.currentTime;

            // Nada 1: 587.33 Hz (D5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, now);
            gain1.gain.setValueAtTime(0.25, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.3);

            // Nada 2: 880 Hz (A5)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880, now + 0.1);
            gain2.gain.setValueAtTime(0.3, now + 0.1);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.1);
            osc2.stop(now + 0.65);
        } catch (e) {
            console.warn('Fallback synthesizer warning:', e);
        }
    };

    // Mainkan audio lonceng saat ada pesan baru (dengan proteksi debounce 600ms)
    const playChime = () => {
        if (!soundEnabled) return;
        const now = Date.now();
        if (now - lastChimeTimeRef.current < 600) {
            return;
        }
        lastChimeTimeRef.current = now;

        try {
            const audio = document.getElementById('global-chat-chime');
            if (audio) {
                audio.currentTime = 0;
                audio.volume = 0.9;
                const p = audio.play();
                if (p !== undefined) {
                    p.catch(() => playSynthChime());
                    return;
                }
            }
            playSynthChime();
        } catch (e) {
            playSynthChime();
        }
    };

    // Tampilkan notifikasi popup Windows (seperti WhatsApp Web)
    const triggerDesktopNotification = (sender, message, conversationId) => {
        if (typeof window === 'undefined' || !('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        try {
            const notif = new Notification(`💬 ${sender || 'Pesan Baru'}`, {
                body: message || 'Membalas percakapan Anda...',
                icon: '/favicon.ico',
                tag: conversationId ? `tokopon-conv-${conversationId}` : 'tokopon-cs',
                renotify: true,
                requireInteraction: true, // Menjaga notifikasi tetap tampil di pojok layar Windows
            });

            notif.onclick = () => {
                window.focus();
                if (conversationId && typeof window !== 'undefined' && !window.location.pathname.startsWith('/crm/chat')) {
                    router.get('/crm/chat', { active: conversationId }, { preserveState: true, preserveScroll: true });
                }
                notif.close();
            };
        } catch (err) {
            console.warn('Desktop notification error:', err);
        }
    };

    // Minta izin notifikasi desktop Windows
    const requestDesktopNotificationPermission = () => {
        playChime();
        if (!('Notification' in window)) {
            alert('Browser Anda tidak mendukung Web Notifications API.');
            return;
        }

        if (Notification.permission === 'default') {
            Notification.requestPermission().then((permission) => {
                setDesktopNotifStatus(permission);
                if (permission === 'granted') {
                    triggerDesktopNotification(
                        'Tokopon Live Chat CS',
                        'Notifikasi desktop aktif! Anda akan mendapatkan popup di Windows setiap ada pesan baru dari pelanggan.',
                        null
                    );
                }
            });
        } else if (Notification.permission === 'granted') {
            triggerDesktopNotification(
                'Tokopon Live Chat (Tes)',
                'Bunyi dan notifikasi desktop aktif! Popup ini akan muncul walau Anda membuka YouTube / aplikasi lain.',
                null
            );
        } else {
            alert('Izin notifikasi diblokir di browser. Silakan klik ikon setelan/gembok di sebelah URL bar dan ubah izin Notifikasi ke "Izinkan / Allow".');
        }
    };

    // Kedipkan judul tab browser saat di background
    const originalTitle = `${title} - Tokopon CRM`;
    const startFlashingTitle = (sender) => {
        if (typeof document === 'undefined' || !document.hidden) return;
        let isAlert = true;
        if (titleFlashingTimerRef.current) clearInterval(titleFlashingTimerRef.current);
        titleFlashingTimerRef.current = setInterval(() => {
            document.title = isAlert ? `🔔 (1) Pesan Baru: ${sender || 'Pelanggan'}!` : originalTitle;
            isAlert = !isAlert;
        }, 1000);
    };

    const stopFlashingTitle = () => {
        if (titleFlashingTimerRef.current) {
            clearInterval(titleFlashingTimerRef.current);
            titleFlashingTimerRef.current = null;
        }
        document.title = originalTitle;
    };

    // Inisialisasi status notifikasi & auto-unlock audio saat user klik pertama kali
    useEffect(() => {
        if (typeof window !== 'undefined' && 'Notification' in window) {
            setDesktopNotifStatus(Notification.permission);
        } else {
            setDesktopNotifStatus('unsupported');
        }

        const unlockAllAudio = () => {
            const audio = document.getElementById('global-chat-chime');
            if (audio) {
                audio.muted = true;
                const p = audio.play();
                if (p !== undefined) {
                    p.then(() => {
                        audio.pause();
                        audio.currentTime = 0;
                        audio.muted = false;
                    }).catch(() => {});
                }
            }
            initAudioContext();
            document.removeEventListener('click', unlockAllAudio);
            document.removeEventListener('keydown', unlockAllAudio);
        };
        document.addEventListener('click', unlockAllAudio, { passive: true });
        document.addEventListener('keydown', unlockAllAudio, { passive: true });

        const handleFocus = () => stopFlashingTitle();
        const handleVisibilityChange = () => {
            if (!document.hidden) stopFlashingTitle();
        };

        window.addEventListener('focus', handleFocus);
        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            document.removeEventListener('click', unlockAllAudio);
            document.removeEventListener('keydown', unlockAllAudio);
            window.removeEventListener('focus', handleFocus);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            stopFlashingTitle();
        };
    }, [title]);

    // Eksekusi notifikasi gabungan (Sound + Desktop Popup Windows + In-App Toast + Title Flash)
    const handleIncomingCustomerMessage = (payload) => {
        if (!payload) return;

        const msgId = payload.id;
        const convId = payload.conversationId || 0;
        const senderName = typeof payload.user === 'object' && payload.user !== null
            ? (payload.user.name || 'Pelanggan')
            : (payload.user || payload.sender || 'Pelanggan');
        const messageText = (payload.message || payload.text || 'Mengirim pesan baru...').trim();

        // 1. Kunci deduplikasi ganda: ID numerik database DAN signature isi pesan
        const dedupKeys = [];
        if (msgId) {
            dedupKeys.push(`id-${msgId}`);
        }
        if (convId && messageText) {
            dedupKeys.push(`sig-${convId}-${messageText}`);
        }

        // Jika salah satu kunci sudah pernah dibunyikan/ditampilkan, abaikan duplikat
        const isDuplicate = dedupKeys.some((k) => seenMessageIdsRef.current.has(k));
        if (isDuplicate) {
            return;
        }

        // Tandai kunci-kunci pesan ini sudah terlihat
        dedupKeys.forEach((k) => seenMessageIdsRef.current.add(k));

        // Naikkan watermark ID agar background polling tidak memproses ulang pesan ini
        if (msgId && msgId > lastPolledIdRef.current) {
            lastPolledIdRef.current = msgId;
        }

        // 1. Putar suara lonceng (terlindung debounce 600ms)
        playChime();

        // 2. Munculkan popup Windows native (seperti WA Web)
        triggerDesktopNotification(senderName, messageText, convId);

        // 3. Kedipkan tab browser jika sedang di background
        startFlashingTitle(senderName);

        // 4. In-App toast
        setToast({
            type: 'info',
            title: `Pesan Baru: ${senderName}`,
            message: messageText,
        });

        // 5. Broadcast ke komponen internal (CsChat dsb)
        if (typeof window !== 'undefined') {
            window.dispatchEvent(
                new CustomEvent('tokopon:incoming-message', {
                    detail: {
                        ...payload,
                        id: msgId,
                        conversationId: convId,
                        sender: senderName,
                        user: senderName,
                        message: messageText,
                        text: messageText,
                    },
                })
            );
        }
    };

    // Dengarkan WebSocket Reverb secara global di Layout
    useEffect(() => {
        if (!window.Echo) return;

        const channel = window.Echo.private('cs-chats');
        const handleMessage = (e) => {
            if (e.senderType === 'customer' || e.senderType === 'guest') {
                handleIncomingCustomerMessage(e);
            }
        };

        // MessageSent disiarkan dengan alias 'message.sent'
        channel.listen('.message.sent', handleMessage);

        return () => {
            channel.stopListening('.message.sent', handleMessage);
        };
    }, [soundEnabled, desktopNotifStatus]);

    // Polling background timer pengaman (Setiap 3.5 detik untuk menjamin notif selalu masuk walau Reverb putus)
    useEffect(() => {
        let isPolling = false;
        const pollInterval = setInterval(() => {
            if (isPolling) return;
            isPolling = true;

            const activeConvId = (typeof window !== 'undefined' && window.__tokoponActiveConvId) || 0;

            window.axios.get('/crm/chat/poll-messages', {
                params: {
                    after_id: lastPolledIdRef.current,
                    active_conv_id: activeConvId,
                }
            })
            .then((res) => {
                if (res.data?.maxId) {
                    if (lastPolledIdRef.current === 0) {
                        lastPolledIdRef.current = res.data.maxId;
                    } else if (res.data.maxId > lastPolledIdRef.current) {
                        lastPolledIdRef.current = res.data.maxId;
                    }
                }

                if (res.data?.messages && Array.isArray(res.data.messages)) {
                    res.data.messages.forEach((msg) => {
                        handleIncomingCustomerMessage(msg);
                    });
                }
            })
            .catch(() => {})
            .finally(() => {
                isPolling = false;
            });
        }, 3500);

        return () => clearInterval(pollInterval);
    }, [soundEnabled, desktopNotifStatus]);

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
        <div className="flex h-screen bg-slate-100 overflow-hidden text-slate-800 font-sans antialiased">
            {/* Mobile Backdrop Overlay */}
            {mobileOpen && (
                <div
                    onClick={() => setMobileOpen(false)}
                    className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 md:hidden animate-in fade-in duration-200"
                />
            )}

            {/* ─── 1. Left Sidebar (Collapsible & Responsive) ─── */}
            <aside
                className={`fixed md:static inset-y-0 left-0 z-50 bg-white border-r border-slate-200/90 flex flex-col shrink-0 transition-all duration-300 ease-in-out shadow-xl md:shadow-none ${
                    isCollapsed ? 'md:w-[72px]' : 'md:w-64'
                } ${mobileOpen ? 'w-64 translate-x-0' : '-translate-x-full md:translate-x-0'}`}
            >
                {/* Brand & Toggle Header */}
                <div className="h-16 border-b border-slate-100 flex items-center justify-between px-3.5 shrink-0 bg-white">
                    <div className="flex items-center gap-3 overflow-hidden">
                        <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-purple-600 text-white flex items-center justify-center font-black shadow-sm shadow-indigo-500/20 shrink-0">
                            <span className="text-xl">⚡</span>
                        </div>
                        {(!isCollapsed || mobileOpen) && (
                            <div className="min-w-0 transition-opacity duration-200">
                                <span className="block font-black text-slate-900 text-sm tracking-tight truncate leading-tight">
                                    Tokopon CRM
                                </span>
                                <span className="block text-[10.5px] font-bold text-indigo-600 truncate uppercase tracking-wider">
                                    E-Commerce Hub
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Mobile close button */}
                    <button
                        type="button"
                        onClick={() => setMobileOpen(false)}
                        className="md:hidden p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        title="Tutup menu"
                    >
                        <X className="w-5 h-5" />
                    </button>

                    {/* Desktop toggle collapse button */}
                    <button
                        type="button"
                        onClick={toggleSidebar}
                        className="hidden md:flex p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 transition-all"
                        title={isCollapsed ? 'Lebarkan Menu (Ctrl+B)' : 'Ciutkan Menu (Ctrl+B)'}
                    >
                        {isCollapsed ? (
                            <PanelLeftOpen className="w-4 h-4" />
                        ) : (
                            <PanelLeftClose className="w-4 h-4" />
                        )}
                    </button>
                </div>

                {/* Grouped Navigation Links */}
                <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-5 scrollbar-thin scrollbar-thumb-slate-200">
                    {navigationGroups.map((group, gIdx) => (
                        <div key={gIdx} className="space-y-1">
                            {(!isCollapsed || mobileOpen) ? (
                                <div className="px-3 py-1 text-[10px] font-black uppercase tracking-wider text-slate-400 select-none">
                                    {group.title}
                                </div>
                            ) : (
                                <div className="h-px bg-slate-100 my-2 mx-2" />
                            )}

                            {group.items.map((item) => {
                                const Icon = item.icon;
                                const isActive = Boolean(url && url.startsWith(item.href));

                                return (
                                    <div key={item.href} className="relative group">
                                        <Link
                                            href={item.href}
                                            preserveScroll
                                            onClick={() => setMobileOpen(false)}
                                            className={`flex items-center gap-3 px-3 py-2.5 rounded-2xl transition-all duration-150 font-bold text-xs ${
                                                isActive
                                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25'
                                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                                            } ${isCollapsed && !mobileOpen ? 'justify-center px-0' : ''}`}
                                        >
                                            <Icon
                                                className={`w-5 h-5 shrink-0 transition-transform group-hover:scale-105 ${
                                                    isActive
                                                        ? 'text-white'
                                                        : 'text-slate-500 group-hover:text-indigo-600'
                                                }`}
                                            />

                                            {(!isCollapsed || mobileOpen) && (
                                                <span className="truncate flex-1 text-[13px] tracking-tight">
                                                    {item.label}
                                                </span>
                                            )}

                                            {(!isCollapsed || mobileOpen) && item.badge && (
                                                <span className="text-[9.5px] px-1.5 py-0.5 rounded-full font-black bg-rose-500 text-white shrink-0 shadow-2xs">
                                                    {item.badge}
                                                </span>
                                            )}
                                        </Link>

                                        {/* Floating Tooltip when Sidebar is Collapsed */}
                                        {isCollapsed && !mobileOpen && (
                                            <div className="absolute left-full ml-3 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-slate-900 text-white text-xs font-bold rounded-xl opacity-0 pointer-events-none group-hover:opacity-100 transition-all duration-150 whitespace-nowrap shadow-xl z-50 flex items-center gap-2 border border-slate-700">
                                                <span>{item.label}</span>
                                                {item.badge && (
                                                    <span className="text-[9px] px-1.5 py-0.5 rounded-full font-black bg-rose-500 text-white">
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ))}
                </nav>

                {/* Bottom Footer Section in Sidebar */}
                <div className="p-3 border-t border-slate-100 space-y-2 shrink-0 bg-slate-50/60">
                    {/* Kembali ke POS */}
                    <a
                        href="/zoffline"
                        className={`flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-white border border-slate-200/80 transition-all shadow-2xs group ${
                            isCollapsed && !mobileOpen ? 'justify-center px-0' : ''
                        }`}
                        title="Kembali ke Dashboard Utama POS Toko"
                    >
                        <ArrowLeft className="w-4 h-4 text-slate-500 group-hover:-translate-x-1 transition-transform shrink-0" />
                        {(!isCollapsed || mobileOpen) && (
                            <span className="truncate">Kembali ke POS</span>
                        )}
                    </a>

                    {/* Toko Aktif */}
                    {activeBU && (!isCollapsed || mobileOpen) && (
                        <div className="flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold">
                            <Store className="w-3.5 h-3.5 shrink-0" />
                            <span className="truncate text-[11.5px]">{activeBU.name}</span>
                        </div>
                    )}
                </div>
            </aside>

            {/* ─── 2. Main Right Area (Header + Main Content) ─── */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Modern Top Header Toolbar */}
                <header className="h-16 bg-white border-b border-slate-200/80 px-4 md:px-6 flex items-center justify-between gap-3 shrink-0 shadow-2xs z-20">
                    <div className="flex items-center gap-3 min-w-0">
                        {/* Mobile Drawer Trigger */}
                        <button
                            type="button"
                            onClick={() => setMobileOpen(true)}
                            className="md:hidden p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                            title="Buka menu navigasi"
                        >
                            <Menu className="w-5 h-5" />
                        </button>

                        <div>
                            <div className="flex items-center gap-2 text-[11px] font-semibold text-slate-400">
                                <span>Tokopon CRM</span>
                                <span>/</span>
                                <span className="text-indigo-600 font-bold">{title}</span>
                            </div>
                            <h1 className="text-base font-extrabold text-slate-900 tracking-tight leading-tight truncate">
                                {title}
                            </h1>
                        </div>
                    </div>

                    {/* Controls & Quick Badges */}
                    <div className="flex items-center gap-2 flex-wrap">
                        {/* Status WebSocket Realtime */}
                        <div className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-700 text-xs font-bold">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span className="text-[11px]">Reverb Realtime</span>
                        </div>

                        {/* Tombol Status Izin Notifikasi Desktop Windows */}
                        <button
                            type="button"
                            onClick={requestDesktopNotificationPermission}
                            className={`px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center gap-1.5 transition-all shadow-2xs shrink-0 ${
                                desktopNotifStatus === 'granted'
                                    ? 'bg-emerald-50 border-emerald-300 text-emerald-800 hover:bg-emerald-100'
                                    : desktopNotifStatus === 'denied'
                                    ? 'bg-rose-50 border-rose-300 text-rose-700'
                                    : 'bg-amber-500 hover:bg-amber-600 text-white animate-pulse'
                            }`}
                            title={
                                desktopNotifStatus === 'granted'
                                    ? 'Notifikasi Desktop aktif! Popup Windows akan muncul walau Anda membuka YouTube/aplikasi lain. Klik untuk tes notif.'
                                    : desktopNotifStatus === 'denied'
                                    ? 'Notifikasi diblokir browser. Buka setelan izin situs di URL bar untuk mengizinkan.'
                                    : 'Klik untuk mengizinkan notifikasi desktop agar pesan masuk selalu muncul di pojok layar Windows!'
                            }
                        >
                            <Bell className={`w-3.5 h-3.5 ${desktopNotifStatus === 'granted' ? 'text-emerald-600' : ''}`} />
                            <span className="text-[11.5px] hidden md:inline">
                                {desktopNotifStatus === 'granted'
                                    ? 'Notif Desktop: Aktif'
                                    : desktopNotifStatus === 'denied'
                                    ? 'Notif Diblokir'
                                    : 'Izinkan Notif'}
                            </span>
                        </button>

                        {/* Toggle Suara Chime */}
                        <button
                            type="button"
                            onClick={() => {
                                setSoundEnabled(!soundEnabled);
                                if (!soundEnabled) playChime();
                            }}
                            className={`px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center gap-1.5 transition-all shadow-2xs shrink-0 ${
                                soundEnabled
                                    ? 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                    : 'bg-rose-50 border-rose-200 text-rose-600'
                            }`}
                            title="Aktifkan atau bisukan suara lonceng pesan masuk"
                        >
                            {soundEnabled ? (
                                <>
                                    <Volume2 className="w-3.5 h-3.5 text-indigo-600" />
                                    <span className="text-[11.5px] hidden md:inline">Suara: Aktif</span>
                                </>
                            ) : (
                                <>
                                    <VolumeX className="w-3.5 h-3.5 text-rose-500" />
                                    <span className="text-[11.5px] hidden md:inline">Mute</span>
                                </>
                            )}
                        </button>

                        {/* User Profile Info */}
                        <div className="flex items-center gap-2.5 pl-2 border-l border-slate-200">
                            <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center font-black text-xs shadow-xs">
                                {auth?.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'C'}
                            </div>
                            <div className="hidden lg:block text-left">
                                <span className="block text-xs font-bold text-slate-900 leading-none">
                                    {auth?.user?.name || 'Staf CS'}
                                </span>
                                <span className="text-[10px] text-slate-400 font-medium leading-none mt-0.5 block">
                                    Customer Service
                                </span>
                            </div>
                        </div>
                    </div>
                </header>

                {/* Main Content Area */}
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
        </div>
    );
}
