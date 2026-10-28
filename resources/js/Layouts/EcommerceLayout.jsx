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
    Share2
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

    // Navigasi Terpadu 10 Sub-Modul
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
            label: 'Referral & Komisi',
            href: '/crm/referral',
            icon: Users,
            badge: null,
            color: 'emerald'
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
                    <div className="flex items-center gap-2 flex-wrap">
                        {/* Status WebSocket Realtime */}
                        <div className="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-700 text-xs font-bold">
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
                            className={`px-2.5 sm:px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center gap-1.5 transition-all shadow-2xs shrink-0 ${
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
                            <span className="text-[11px] hidden sm:inline">
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
                            className={`px-2.5 sm:px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center gap-1.5 transition-all shadow-2xs shrink-0 ${
                                soundEnabled
                                    ? 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                    : 'bg-rose-50 border-rose-200 text-rose-600'
                            }`}
                            title="Aktifkan atau bisukan suara lonceng pesan masuk"
                        >
                            {soundEnabled ? (
                                <>
                                    <Volume2 className="w-3.5 h-3.5 text-indigo-600" />
                                    <span className="text-[11px] hidden sm:inline">Suara: Aktif</span>
                                </>
                            ) : (
                                <>
                                    <VolumeX className="w-3.5 h-3.5 text-rose-500" />
                                    <span className="text-[11px] hidden sm:inline">Mute</span>
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
