import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import ChatSidebar from './Components/ChatSidebar';
import ChatStream from './Components/ChatStream';
import Customer360Drawer from './Components/Customer360Drawer';
import QuickVoucherModal from './Components/QuickVoucherModal';
import ClosingModal from './Components/ClosingModal';
import ProductPickerModal from './Components/ProductPickerModal';
import axios from 'axios';

export default function CsChatIndex({
    conversations = [],
    activeConversation = null,
    messages = [],
    customerCrm = null,
    activeCoupons = [],
    filters = {},
    quickReplies = []
}) {
    const { auth, businessUnits } = usePage().props;

    const [convList, setConvList] = useState(conversations);
    const [activeConv, setActiveConv] = useState(activeConversation);
    const [activeMessages, setActiveMessages] = useState(messages);
    const [activeCrm, setActiveCrm] = useState(customerCrm);

    // Selected product attachment for chat
    const [selectedProduct, setSelectedProduct] = useState(null);

    // Modals & Drawers state
    const [isCrmDrawerOpen, setIsCrmDrawerOpen] = useState(false);
    const [isVoucherModalOpen, setIsVoucherModalOpen] = useState(false);
    const [isClosingModalOpen, setIsClosingModalOpen] = useState(false);
    const [isProductPickerOpen, setIsProductPickerOpen] = useState(false);

    // Sync state when props change (e.g. from server page reload or router.get)
    useEffect(() => {
        setConvList(conversations);
    }, [conversations]);

    useEffect(() => {
        setActiveConv(activeConversation);
    }, [activeConversation]);

    useEffect(() => {
        setActiveMessages(messages);
    }, [messages]);

    useEffect(() => {
        setActiveCrm(customerCrm);
    }, [customerCrm]);

    // Handle select conversation dari sidebar
    const handleSelectChat = (convId) => {
        if (activeConv?.id === convId) return;

        // Reset selected product
        setSelectedProduct(null);

        // Optimistically set active conversation di UI
        const found = convList.find((c) => c.id === convId);
        if (found) {
            setActiveConv({ ...found, unreadCount: 0 });
            // Zero out unread count di list
            setConvList((prev) =>
                prev.map((c) => (c.id === convId ? { ...c, unreadCount: 0 } : c))
            );
        }

        router.get(
            '/crm/chat',
            {
                ...filters,
                active: convId,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Simpan active conversation ID secara global agar polling helper tahu percakapan aktif
    useEffect(() => {
        if (typeof window !== 'undefined') {
            window.__tokoponActiveConvId = activeConv?.id ? parseInt(activeConv.id) : 0;
        }
        return () => {
            if (typeof window !== 'undefined') {
                window.__tokoponActiveConvId = 0;
            }
        };
    }, [activeConv?.id]);

    // WebSocket real-time subscription untuk percakapan aktif
    useEffect(() => {
        if (!activeConv?.id || !window.Echo) return;

        const channel = window.Echo.private(`conversation.${activeConv.id}`);

        const handleNewMessage = (e) => {
            if (activeConv?.id && parseInt(e.conversationId) === parseInt(activeConv.id)) {
                setActiveMessages((prev) => {
                    const msgText = e.message || '';
                    const msgTime = e.time || '';
                    const exists = prev.some(
                        (m) => (e.id && m.id === e.id) || (m.time === msgTime && (m.text === msgText || m.message === msgText))
                    );
                    if (exists) return prev;

                    const userName = typeof e.user === 'object' && e.user !== null
                        ? (e.user.name || 'Pelanggan')
                        : (e.user || 'Pelanggan');

                    return [
                        ...prev,
                        {
                            id: e.id || ('rt-' + Date.now() + Math.random()),
                            sender_type: e.senderType,
                            message: msgText,
                            text: msgText,
                            time: msgTime,
                            created_at: msgTime,
                            read_at: e.senderType === 'customer' || e.senderType === 'guest' ? new Date().toISOString() : null,
                            user: userName,
                            isCs: e.senderType === 'cs',
                            attachments: e.attachments || [],
                            product: e.product || null,
                        },
                    ];
                });
            }
        };

        const handleReadReceipt = (e) => {
            if (activeConv?.id && parseInt(e.conversationId) === parseInt(activeConv.id)) {
                setActiveMessages((prev) =>
                    prev.map((m) =>
                        m.sender_type === 'cs' ? { ...m, read_at: e.readAt || new Date().toISOString() } : m
                    )
                );
            }
        };

        channel.listen('.message.sent', handleNewMessage);
        channel.listen('MessageSent', handleNewMessage);
        channel.listen('.message.read', handleReadReceipt);
        channel.listen('MessageRead', handleReadReceipt);

        return () => {
            channel.stopListening('.message.sent', handleNewMessage);
            channel.stopListening('MessageSent', handleNewMessage);
            channel.stopListening('.message.read', handleReadReceipt);
            channel.stopListening('MessageRead', handleReadReceipt);
            window.Echo.leave(`conversation.${activeConv.id}`);
        };
    }, [activeConv?.id]);

    // Sinkronisasi pesan masuk pelanggan dari event terpadu Layout (WebSocket & Polling Safeguard)
    useEffect(() => {
        const handleIncomingFromLayout = (event) => {
            const e = event.detail;
            if (!e) return;
            const convId = parseInt(e.conversationId);
            const isActive = activeConv && parseInt(activeConv.id) === convId;

            // Jika pesan ditujukan untuk obrolan yang sedang dibuka CS, append langsung
            if (isActive) {
                setActiveMessages((prev) => {
                    const msgText = e.message || e.text || '';
                    const msgTime = e.time || '';
                    const exists = prev.some(
                        (m) => (e.id && m.id === e.id) || (m.time === msgTime && (m.text === msgText || m.message === msgText))
                    );
                    if (exists) return prev;

                    return [
                        ...prev,
                        {
                            id: e.id || ('rt-' + Date.now() + Math.random()),
                            sender_type: e.senderType,
                            message: msgText,
                            text: msgText,
                            time: msgTime,
                            created_at: msgTime,
                            read_at: e.senderType === 'customer' || e.senderType === 'guest' ? new Date().toISOString() : null,
                            user: e.user || 'Pelanggan',
                            isCs: e.senderType === 'cs',
                            attachments: e.attachments || [],
                            product: e.product || null,
                        },
                    ];
                });
            }

            // Update status & posisi percakapan di daftar sidebar
            setConvList((prev) => {
                const targetIndex = prev.findIndex((c) => c.id === convId);

                if (targetIndex !== -1) {
                    const target = { ...prev[targetIndex] };
                    target.lastMessage = e.message || e.text;
                    target.lastTime = e.time;
                    target.lastMessageTime = e.time;
                    if (!isActive && (e.senderType === 'customer' || e.senderType === 'guest')) {
                        target.unreadCount = (target.unreadCount || 0) + 1;
                    }

                    // Geser ke paling atas antrean
                    const filtered = prev.filter((c) => c.id !== convId);
                    return [target, ...filtered];
                } else {
                    const userName = typeof e.user === 'object' && e.user !== null
                        ? (e.user.name || 'Pelanggan')
                        : (e.user || 'Pelanggan');

                    const newConvItem = {
                        id: convId,
                        userName: userName,
                        userInitial: (userName || 'P').charAt(0).toUpperCase(),
                        userPhone: '',
                        status: 'open',
                        closingStatus: 'none',
                        assignedToName: null,
                        assignedToId: null,
                        isAssignedToMe: false,
                        lastMessage: e.message || e.text,
                        lastTime: e.time,
                        lastMessageTime: e.time,
                        unreadCount: isActive ? 0 : 1,
                        productName: null,
                        productImage: null,
                        businessUnitName: null,
                    };
                    return [newConvItem, ...prev];
                }
            });
        };

        window.addEventListener('tokopon:incoming-message', handleIncomingFromLayout);
        return () => {
            window.removeEventListener('tokopon:incoming-message', handleIncomingFromLayout);
        };
    }, [activeConv?.id]);

    // Claim / Release handler
    const handleClaimToggle = async (action) => {
        if (!activeConv?.id) return;

        try {
            const res = await axios.post(`/crm/chat/${activeConv.id}/${action}`);
            if (res.data?.success) {
                const isClaim = action === 'claim';
                const currentUserName = auth?.user?.name || 'Saya';
                const currentUserId = auth?.user?.id || null;

                setActiveConv((prev) => ({
                    ...prev,
                    assignedToName: isClaim ? currentUserName : null,
                    assignedToId: isClaim ? currentUserId : null,
                    isAssignedToMe: isClaim,
                }));

                setConvList((prev) =>
                    prev.map((c) =>
                        c.id === activeConv.id
                            ? {
                                  ...c,
                                  assignedToName: isClaim ? currentUserName : null,
                                  assignedToId: isClaim ? currentUserId : null,
                                  isAssignedToMe: isClaim,
                              }
                            : c
                    )
                );
            }
        } catch (e) {
            console.error('Gagal mengubah status penugasan CS:', e);
            alert('Gagal mengubah status penugasan: ' + (e.response?.data?.message || e.message));
        }
    };

    // Closing modal success callback
    const handleClosingSuccess = (newStatus, newAmount) => {
        setActiveConv((prev) => ({
            ...prev,
            closingStatus: newStatus,
            closingAmount: newAmount,
        }));

        setConvList((prev) =>
            prev.map((c) =>
                c.id === activeConv?.id
                    ? { ...c, closingStatus: newStatus, closingAmount: newAmount }
                    : c
            )
        );
    };

    // Callback saat pesan baru berhasil terkirim via ChatStream
    const handleNewMessageSent = (sentMsg) => {
        // Tambahkan ke messages
        setActiveMessages((prev) => [...prev, sentMsg]);

        // Update preview di sidebar list
        setConvList((prev) => {
            const targetIndex = prev.findIndex((c) => c.id === activeConv?.id);
            if (targetIndex !== -1) {
                const target = { ...prev[targetIndex] };
                target.lastMessage = sentMsg.message;
                target.lastMessageTime = sentMsg.created_at;
                const filtered = prev.filter((c) => c.id !== activeConv?.id);
                return [target, ...filtered];
            }
            return prev;
        });

        // Clear product attachment
        setSelectedProduct(null);
    };

    return (
        <EcommerceLayout title="Live Chat CS & CRM Suite">
            <Head title="Live Chat CS & CRM" />

            <div className="flex h-full w-full overflow-hidden relative">
                {/* 1. Sidebar Antrean Chat (Kiri) */}
                <div className="w-80 md:w-96 shrink-0 h-full border-r border-slate-200/80 bg-white flex flex-col">
                    <ChatSidebar
                        conversations={convList}
                        activeId={activeConv?.id}
                        businessUnits={businessUnits}
                        filters={filters}
                        onSelectChat={handleSelectChat}
                    />
                </div>

                {/* 2. Workspace Percakapan (Tengah) */}
                <div className="flex-1 h-full min-w-0 bg-slate-50 flex flex-col relative overflow-hidden">
                    <ChatStream
                        activeConv={activeConv}
                        messages={activeMessages}
                        quickReplies={quickReplies}
                        onOpenCrmDrawer={() => setIsCrmDrawerOpen(true)}
                        onOpenClosingModal={() => setIsClosingModalOpen(true)}
                        onOpenVoucherModal={() => setIsVoucherModalOpen(true)}
                        onOpenProductPicker={() => setIsProductPickerOpen(true)}
                        onClaimToggle={handleClaimToggle}
                        selectedProduct={selectedProduct}
                        onRemoveProduct={() => setSelectedProduct(null)}
                        onNewMessageSent={handleNewMessageSent}
                    />
                </div>

                {/* 3. Customer 360 CRM Drawer (Kanan - Slide over) */}
                <Customer360Drawer
                    isOpen={isCrmDrawerOpen}
                    onClose={() => setIsCrmDrawerOpen(false)}
                    customerCrm={activeCrm}
                    activeConv={activeConv}
                />

                {/* 4. Quick Voucher Modal */}
                <QuickVoucherModal
                    isOpen={isVoucherModalOpen}
                    onClose={() => setIsVoucherModalOpen(false)}
                    activeCoupons={activeCoupons}
                    onSendCouponText={(text) => {
                        // Kirim kupon via chat stream
                        axios
                            .post(`/crm/chat/${activeConv?.id}/send`, {
                                message: text,
                            })
                            .then((res) => {
                                if (res.data?.success && res.data.message) {
                                    handleNewMessageSent(res.data.message);
                                }
                            })
                            .catch((err) => {
                                console.error('Gagal mengirim kupon:', err);
                            });
                    }}
                />

                {/* 5. Closing Status Modal */}
                <ClosingModal
                    isOpen={isClosingModalOpen}
                    onClose={() => setIsClosingModalOpen(false)}
                    activeConv={activeConv}
                    onClosingSuccess={handleClosingSuccess}
                />

                {/* 6. Product Catalog Picker Modal */}
                <ProductPickerModal
                    isOpen={isProductPickerOpen}
                    onClose={() => setIsProductPickerOpen(false)}
                    onSelectProduct={(product) => {
                        setSelectedProduct(product);
                        setIsProductPickerOpen(false);
                    }}
                />
            </div>
        </EcommerceLayout>
    );
}
