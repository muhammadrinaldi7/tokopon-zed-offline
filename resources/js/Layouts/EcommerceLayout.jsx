import React, { useState, useEffect, useRef, useMemo } from 'react';
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
    Users,
    Menu,
    X,
    PanelLeftClose,
    PanelLeftOpen,
    Search,
    ChevronsUpDown,
    ExternalLink,
    Radio,
    ShieldCheck,
    Sparkles
} from 'lucide-react';

export default function EcommerceLayout({ children, title = 'E-Commerce & CRM Suite' }) {
    const page = usePage();
    const { auth, businessUnits, flash } = page.props || {};
    const url = page.url || (typeof window !== 'undefined' ? window.location.pathname : '');
    const isChatPage = Boolean(url && url.startsWith('/crm/chat'));
    const [soundEnabled, setSoundEnabled] = useState(true);
    const [wsConnected, setWsConnected] = useState(true);
    const [toast, setToast] = useState(null);
    const [navSearch, setNavSearch] = useState('');

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
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('crm-nav-search');
                if (searchInput) {
                    if (isCollapsed) setIsCollapsed(false);
                    searchInput.focus();
                }
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isCollapsed]);

    // Tutup mobile drawer saat navigasi berubah
    useEffect(() => {
        setMobileOpen(false);
    }, [url]);

    // Navigasi Terstruktur 4 Kelompok (10 Sub-Modul CRM)
    const rawNavigationGroups = [
        {
            title: 'Layanan & Chat',
            items: [
                {
                    label: 'Live Chat CS',
                    href: '/crm/chat',
                    icon: MessageSquare,
                    badge: 'LIVE',
                    badgeType: 'live',
                    description: 'Pesan & Inbox Pelanggan Realtime',
                },
                {
                    label: 'Analitik Closing',
                    href: '/crm/analytics',
                    icon: TrendingUp,
                    badge: null,
                    description: 'Konversi CS & Laporan Penjualan',
                },
            ]
        },
        {
            title: 'Promosi & Marketing',
            items: [
                {
                    label: 'Flash Sale',
                    href: '/crm/flash-sale',
                    icon: Zap,
                    badge: 'HOT',
                    badgeType: 'promo',
                    description: 'Promo Kilat Berbatas Waktu',
                },
                {
                    label: 'Voucher Kupon',
                    href: '/crm/vouchers',
                    icon: Ticket,
                    badge: null,
                    description: 'Diskon & Kode Kupon Promo',
                },
                {
                    label: 'Banner Promo',
                    href: '/crm/banners',
                    icon: ImageIcon,
                    badge: null,
                    description: 'Slider & Banner Beranda Web',
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
                    description: 'Katalog Produk & Galeri Foto',
                },
                {
                    label: 'Member Loyalty',
                    href: '/crm/loyalty',
                    icon: Crown,
                    badge: null,
                    description: 'Tier Poin & Keanggotaan Member',
                },
                {
                    label: 'Referral & Komisi',
                    href: '/crm/referral',
                    icon: Users,
                    badge: null,
                    description: 'Program Afiliasi & Reward CS',
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
                    description: 'Metode Pembayaran Transfer & VA',
                },
                {
                    label: 'Pengaturan CRM',
                    href: '/crm/settings',
                    icon: Settings,
                    badge: null,
                    description: 'Konfigurasi Jam Operasional & Toko',
                },
            ]
        }
    ];

    // Filter navigasi berdasarkan input pencarian jika ada
    const navigationGroups = useMemo(() => {
        if (!navSearch.trim()) return rawNavigationGroups;
        const q = navSearch.toLowerCase().trim();
        return rawNavigationGroups
            .map((grp) => ({
                ...grp,
                items: grp.items.filter(
                    (item) =>
                        item.label.toLowerCase().includes(q) ||
                        (item.description && item.description.toLowerCase().includes(q))
                ),
            }))
            .filter((grp) => grp.items.length > 0);
    }, [navSearch]);

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
                requireInteraction: true,
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

        // 1. Putar suara lonceng
        playChime();

        // 2. Munculkan popup Windows native
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

    const isExpanded = !isCollapsed || mobileOpen;

    return (
        <div className="flex h-screen bg-[#F8FAFC] overflow-hidden text-slate-800 font-sans antialiased selection:bg-indigo-500 selection:text-white">
            {/* Audio chime tag cadangan */}
            <audio id="global-chat-chime" preload="auto" src="/sounds/notification.mp3" className="hidden" />

            {/* Mobile Backdrop Overlay */}
            {mobileOpen && (
                <div
                    onClick={() => setMobileOpen(false)}
                    className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-40 md:hidden animate-in fade-in duration-200"
                />
            )}

            {/* ═══════════════════════════════════════════════════════════════
                1. LEFT SIDEBAR (HubSpot / Zendesk / Crisp Enterprise Pro)
                Deep Obsidian Theme: bg-[#0B0F19] with Slate 800 borders
               ═══════════════════════════════════════════════════════════════ */}
            <aside
                className={`fixed md:static inset-y-0 left-0 z-50 bg-[#0B0F19] text-slate-300 border-r border-slate-800/80 flex flex-col shrink-0 transition-all duration-300 ease-in-out shadow-2xl md:shadow-none select-none ${
                    isCollapsed ? 'md:w-[68px]' : 'md:w-[260px]'
                } ${mobileOpen ? 'w-[270px] translate-x-0' : '-translate-x-full md:translate-x-0'}`}
            >
                {/* ── Brand & Workspace Header ── */}
                <div className="h-16 px-3.5 border-b border-slate-800/80 flex items-center justify-between shrink-0 bg-[#0B0F19]">
                    <div className="flex items-center gap-2.5 min-w-0">
                        {/* Custom Modern Vector Logo */}
                        <div className="relative w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 p-0.5 shadow-lg shadow-indigo-600/30 shrink-0 flex items-center justify-center">
                            <div className="w-full h-full bg-[#0B0F19]/20 backdrop-blur-xs rounded-[10px] flex items-center justify-center">
                                <svg
                                    viewBox="0 0 24 24"
                                    className="w-5 h-5 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M12 2L2 7l10 5 10-5-10-5z" />
                                    <path d="M2 17l10 5 10-5" />
                                    <path d="M2 12l10 5 10-5" />
                                </svg>
                            </div>
                            <span className="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-[#0B0F19]" />
                        </div>

                        {/* Brand Title (Visible when expanded) */}
                        {isExpanded && (
                            <div className="min-w-0 transition-opacity duration-200">
                                <div className="flex items-center gap-1.5">
                                    <span className="font-extrabold text-white text-[13.5px] tracking-tight leading-none">
                                        TOKOPON
                                    </span>
                                    <span className="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 leading-none">
                                        CRM
                                    </span>
                                </div>
                                <span className="text-[10px] text-slate-400 font-medium tracking-tight truncate block mt-0.5">
                                    Commerce Suite Pro
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Mobile close button */}
                    <button
                        type="button"
                        onClick={() => setMobileOpen(false)}
                        className="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                        title="Tutup Menu"
                    >
                        <X className="w-4 h-4" />
                    </button>

                    {/* Desktop toggle collapse button */}
                    <button
                        type="button"
                        onClick={toggleSidebar}
                        className="hidden md:flex p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/80 transition-all border border-transparent hover:border-slate-700/60"
                        title={isCollapsed ? 'Lebarkan Sidebar (Ctrl+B)' : 'Ciutkan Sidebar (Ctrl+B)'}
                    >
                        {isCollapsed ? (
                            <PanelLeftOpen className="w-4 h-4 text-indigo-400" />
                        ) : (
                            <PanelLeftClose className="w-4 h-4" />
                        )}
                    </button>
                </div>

                {/* ── Active Store / Business Unit Card (HubSpot / Zendesk style) ── */}
                <div className="pt-3 px-2.5 shrink-0">
                    {isExpanded ? (
                        <div className="p-2 rounded-xl bg-slate-900/90 border border-slate-800/90 hover:border-slate-700 transition-colors group flex items-center justify-between">
                            <div className="flex items-center gap-2.5 min-w-0">
                                <div className="w-7 h-7 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                                    <Store className="w-3.5 h-3.5" />
                                </div>
                                <div className="min-w-0">
                                    <div className="text-[11.5px] font-semibold text-slate-200 truncate leading-tight">
                                        {activeBU?.name || 'Toko Tokopon Pusat'}
                                    </div>
                                    <div className="text-[9.5px] text-slate-400 flex items-center gap-1.5 leading-tight mt-0.5">
                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                                        <span className="truncate">Online Store Active</span>
                                    </div>
                                </div>
                            </div>
                            <ChevronsUpDown className="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300 transition-colors shrink-0" />
                        </div>
                    ) : (
                        <div
                            className="relative group flex justify-center py-1 cursor-pointer"
                            title={`Toko: ${activeBU?.name || 'Toko Utama'}`}
                        >
                            <div className="w-8 h-8 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-indigo-400 group-hover:border-indigo-500/40 transition-colors">
                                <Store className="w-4 h-4" />
                            </div>
                            <span className="absolute bottom-1 right-2 w-2 h-2 rounded-full bg-emerald-400 border border-[#0B0F19]" />

                            {/* Tooltip on collapse */}
                            <div className="absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1.5 bg-slate-900 text-white text-[11px] font-semibold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 transition-all duration-150 whitespace-nowrap shadow-xl z-50 border border-slate-700">
                                {activeBU?.name || 'Toko Utama'} (Online)
                            </div>
                        </div>
                    )}
                </div>

                {/* ── Quick Search Filter (Super clean like Linear / Slack) ── */}
                {isExpanded && (
                    <div className="px-2.5 pt-2 pb-1 shrink-0">
                        <div className="relative">
                            <Search className="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2" />
                            <input
                                id="crm-nav-search"
                                type="text"
                                value={navSearch}
                                onChange={(e) => setNavSearch(e.target.value)}
                                placeholder="Cari modul... (Ctrl+K)"
                                className="w-full bg-slate-900/60 border border-slate-800/90 rounded-lg pl-8 pr-7 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/40 transition-all"
                            />
                            {navSearch && (
                                <button
                                    type="button"
                                    onClick={() => setNavSearch('')}
                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300"
                                >
                                    <X className="w-3.5 h-3.5" />
                                </button>
                            )}
                        </div>
                    </div>
                )}

                {/* ── Navigation Items List ── */}
                <nav className="flex-1 overflow-y-auto px-2.5 py-2 space-y-4 scrollbar-thin scrollbar-thumb-slate-800 scrollbar-track-transparent">
                    {navigationGroups.map((group, gIdx) => (
                        <div key={gIdx} className="space-y-1">
                            {isExpanded ? (
                                <div className="px-2 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between select-none">
                                    <span>{group.title}</span>
                                </div>
                            ) : (
                                <div className="h-px bg-slate-800/80 my-2 mx-1" />
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
                                            className={`relative flex items-center gap-2.5 px-2.5 py-2 rounded-lg transition-all duration-150 text-[12.5px] font-medium leading-none ${
                                                isActive
                                                    ? 'bg-gradient-to-r from-indigo-500/20 via-indigo-500/10 to-transparent text-white font-semibold border-l-2 border-indigo-400 shadow-xs'
                                                    : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60'
                                            } ${isCollapsed && !mobileOpen ? 'justify-center px-0 h-9' : 'h-9'}`}
                                        >
                                            <Icon
                                                className={`w-4 h-4 shrink-0 transition-colors ${
                                                    isActive
                                                        ? 'text-indigo-400'
                                                        : 'text-slate-400 group-hover:text-slate-200'
                                                }`}
                                            />

                                            {isExpanded && (
                                                <span className="truncate flex-1 tracking-tight">
                                                    {item.label}
                                                </span>
                                            )}

                                            {/* Micro Badge */}
                                            {isExpanded && item.badge && (
                                                <span
                                                    className={`text-[9px] font-extrabold px-1.5 py-0.5 rounded-md leading-none shrink-0 ${
                                                        item.badgeType === 'live'
                                                            ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1'
                                                            : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
                                                    }`}
                                                >
                                                    {item.badgeType === 'live' && (
                                                        <span className="w-1 h-1 rounded-full bg-emerald-400 animate-ping" />
                                                    )}
                                                    {item.badge}
                                                </span>
                                            )}
                                        </Link>

                                        {/* Floating Popover Tooltip when Sidebar is Collapsed */}
                                        {isCollapsed && !mobileOpen && (
                                            <div className="absolute left-full ml-3 top-1/2 -translate-y-1/2 px-3 py-2 bg-slate-900/95 backdrop-blur-md text-white text-xs font-semibold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 transition-all duration-150 whitespace-nowrap shadow-2xl z-50 border border-slate-700/80 flex items-center gap-2">
                                                <div>
                                                    <div className="text-white font-bold">{item.label}</div>
                                                    {item.description && (
                                                        <div className="text-[10px] text-slate-400 font-normal mt-0.5">
                                                            {item.description}
                                                        </div>
                                                    )}
                                                </div>
                                                {item.badge && (
                                                    <span className="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-indigo-500 text-white">
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

                    {navigationGroups.length === 0 && (
                        <div className="text-center py-6 px-3 text-slate-500 text-xs">
                            Tidak ada modul "{navSearch}"
                        </div>
                    )}
                </nav>

                {/* ── Bottom Section: Quick App Switcher & User Profile ── */}
                <div className="p-2.5 border-t border-slate-800/80 bg-[#080C14] space-y-2 shrink-0">
                    {/* Kembali ke POS Toko */}
                    <a
                        href="/zoffline"
                        className={`flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800/80 border border-slate-800 hover:border-slate-700 transition-all group ${
                            isCollapsed && !mobileOpen ? 'justify-center px-0 h-9' : 'h-9'
                        }`}
                        title="Beralih ke Terminal Kasir POS"
                    >
                        <ArrowLeft className="w-3.5 h-3.5 text-slate-400 group-hover:-translate-x-0.5 transition-transform shrink-0" />
                        {isExpanded && (
                            <span className="truncate flex-1 text-[11.5px]">Kembali ke POS</span>
                        )}
                        {isExpanded && (
                            <span className="text-[9.5px] px-1 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">
                                POS
                            </span>
                        )}
                    </a>

                    {/* User Profile Tile */}
                    <div className="pt-1 flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 via-indigo-700 to-purple-600 text-white font-bold text-xs flex items-center justify-center shadow-xs shrink-0 ring-1 ring-white/10">
                            {auth?.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'C'}
                        </div>
                        {isExpanded && (
                            <div className="min-w-0 flex-1">
                                <div className="text-xs font-semibold text-slate-200 truncate leading-none">
                                    {auth?.user?.name || 'Staf Admin CRM'}
                                </div>
                                <div className="text-[10px] text-slate-400 truncate leading-none mt-1">
                                    {auth?.user?.role || 'Customer Service Specialist'}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </aside>

            {/* ═══════════════════════════════════════════════════════════════
                2. MAIN CONTENT AREA (Header Toolbar + Children Workspace)
               ═══════════════════════════════════════════════════════════════ */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#F8FAFC]">
                {/* Modern Enterprise Header Toolbar */}
                <header className="h-16 bg-white border-b border-slate-200/80 px-4 md:px-6 flex items-center justify-between gap-3 shrink-0 z-20 shadow-xs">
                    {/* Left: Mobile trigger & Breadcrumb */}
                    <div className="flex items-center gap-3 min-w-0">
                        {/* Mobile Drawer Button */}
                        <button
                            type="button"
                            onClick={() => setMobileOpen(true)}
                            className="md:hidden p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors shrink-0"
                            title="Buka Menu"
                        >
                            <Menu className="w-5 h-5" />
                        </button>

                        <div className="min-w-0">
                            {/* Breadcrumbs */}
                            <div className="flex items-center gap-1.5 text-[11px] font-medium text-slate-400 truncate">
                                <span>Tokopon Suite</span>
                                <span>/</span>
                                <span className="text-slate-600 font-semibold">CRM Hub</span>
                                <span>/</span>
                                <span className="text-indigo-600 font-bold">{title}</span>
                            </div>
                            <h1 className="text-base font-extrabold text-slate-900 tracking-tight leading-tight truncate">
                                {title}
                            </h1>
                        </div>
                    </div>

                    {/* Right: Operational Controls & Status Badges */}
                    <div className="flex items-center gap-2 flex-wrap">
                        {/* Status WebSocket Realtime Reverb */}
                        <div className="hidden sm:flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-emerald-50/80 border border-emerald-200/60 text-emerald-700 text-xs font-semibold">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500" />
                            </span>
                            <span className="text-[11px] tracking-tight">Reverb WebSocket: Live</span>
                        </div>

                        {/* Tombol Status Izin Notifikasi Desktop Windows */}
                        <button
                            type="button"
                            onClick={requestDesktopNotificationPermission}
                            className={`px-2.5 py-1.5 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all shadow-2xs shrink-0 ${
                                desktopNotifStatus === 'granted'
                                    ? 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
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
                                    ? 'Notif Windows: Aktif'
                                    : desktopNotifStatus === 'denied'
                                    ? 'Notif Diblokir'
                                    : 'Izinkan Notifikasi'}
                            </span>
                        </button>

                        {/* Toggle Suara Chime Notifikasi */}
                        <button
                            type="button"
                            onClick={() => {
                                setSoundEnabled(!soundEnabled);
                                if (!soundEnabled) playChime();
                            }}
                            className={`px-2.5 py-1.5 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all shadow-2xs shrink-0 ${
                                soundEnabled
                                    ? 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                    : 'bg-rose-50 border-rose-200 text-rose-600'
                            }`}
                            title="Aktifkan atau bisukan nada dering pesan baru"
                        >
                            {soundEnabled ? (
                                <>
                                    <Volume2 className="w-3.5 h-3.5 text-indigo-600" />
                                    <span className="text-[11.5px] hidden md:inline">Suara: On</span>
                                </>
                            ) : (
                                <>
                                    <VolumeX className="w-3.5 h-3.5 text-rose-500" />
                                    <span className="text-[11.5px] hidden md:inline">Mute</span>
                                </>
                            )}
                        </button>
                    </div>
                </header>

                {/* Main Content Workspace */}
                <main className={`flex-1 relative min-h-0 ${isChatPage ? 'overflow-hidden flex flex-col' : 'overflow-y-auto'}`}>
                    {children}

                    {/* Global Floating Toast Alert */}
                    {toast && (
                        <div className="fixed bottom-5 right-5 z-50 max-w-sm animate-in slide-in-from-bottom duration-200">
                            <div
                                className={`p-3.5 rounded-xl shadow-xl border flex items-start gap-3 bg-white ${
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
