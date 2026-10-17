import React, { useState, useEffect, useRef } from 'react';
import {
    Send,
    ShoppingBag,
    Ticket,
    Paperclip,
    ExternalLink,
    Copy,
    Check,
    UserCheck,
    Award,
    CheckCircle2,
    Clock,
    XCircle,
    Info,
    Store,
    Phone,
    Image as ImageIcon,
    FileText,
    MessageCircle,
    X,
    Loader2
} from 'lucide-react';
import axios from 'axios';

export default function ChatStream({
    activeConv,
    messages = [],
    quickReplies = [],
    onOpenCrmDrawer,
    onOpenClosingModal,
    onOpenVoucherModal,
    onOpenProductPicker,
    onClaimToggle,
    selectedProduct,
    onRemoveProduct,
    onNewMessageSent
}) {
    const [inputText, setInputText] = useState('');
    const [attachment, setAttachment] = useState(null);
    const [isSending, setIsSending] = useState(false);
    const [copiedPhone, setCopiedPhone] = useState(false);
    const messagesEndRef = useRef(null);

    // Auto scroll ke pesan terbawah
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleCopyPhone = () => {
        if (activeConv?.userPhone) {
            navigator.clipboard.writeText(activeConv.userPhone);
            setCopiedPhone(true);
            setTimeout(() => setCopiedPhone(false), 2000);
        }
    };

    const handleSend = async (e) => {
        e.preventDefault();
        const hasText = inputText.trim() !== '';
        const hasProduct = !!selectedProduct;
        const hasAttachment = !!attachment;

        if ((!hasText && !hasProduct && !hasAttachment) || isSending || !activeConv) {
            return;
        }

        const currentText = inputText.trim();
        const currentProd = selectedProduct;
        const currentAtt = attachment;

        // Reset input segera (Optimistic UI)
        setInputText('');
        setAttachment(null);
        if (onRemoveProduct) onRemoveProduct();
        setIsSending(true);

        const formData = new FormData();
        formData.append('message', currentText);
        if (currentProd) {
            formData.append('product_accurate_id', currentProd.id);
        }
        if (currentAtt) {
            formData.append('attachment', currentAtt);
        }

        try {
            const res = await axios.post(`/crm/chat/${activeConv.id}/send`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data?.success && onNewMessageSent) {
                onNewMessageSent(res.data.message);
            }
        } catch (err) {
            console.error('Gagal mengirim pesan:', err);
        } finally {
            setIsSending(false);
        }
    };

    if (!activeConv) {
        return (
            <div className="flex-1 flex flex-col items-center justify-center text-slate-400 p-8 text-center bg-white h-full">
                <div className="w-16 h-16 rounded-3xl bg-indigo-50 text-indigo-500 flex items-center justify-center mb-3">
                    <MessageCircle className="w-8 h-8" />
                </div>
                <h4 className="text-sm font-extrabold text-slate-700">
                    Pilih Percakapan di Sidebar
                </h4>
                <p className="text-xs text-slate-400 mt-1 max-w-sm leading-relaxed">
                    Klik salah satu chat di sebelah kiri untuk membaca riwayat, melayani pertanyaan, dan melihat ringkasan CRM pelanggan.
                </p>
            </div>
        );
    }

    return (
        <div className="flex-1 flex flex-col bg-white h-full overflow-hidden">
            {/* 1. Chat Header */}
            <div className="px-5 py-3 border-b border-slate-200/80 flex items-center justify-between bg-white shadow-2xs z-10 shrink-0">
                <div className="flex items-center gap-3">
                    {/* Initial Avatar */}
                    <div
                        className={`w-10 h-10 rounded-2xl flex items-center justify-center font-black text-sm shadow-2xs shrink-0 ${
                            activeConv.isGuest
                                ? 'bg-amber-100 text-amber-800'
                                : 'bg-indigo-100 text-indigo-700'
                        }`}
                    >
                        {activeConv.userName.charAt(0).toUpperCase()}
                    </div>

                    <div>
                        <div className="flex items-center gap-2 flex-wrap">
                            <h3 className="font-extrabold text-slate-900 text-sm">
                                {activeConv.userName}
                            </h3>

                            {/* Phone Badge */}
                            {activeConv.userPhone && (
                                <div className="flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200/80">
                                    <span className="text-xs font-mono font-bold text-slate-700">
                                        📱 {activeConv.userPhone}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={handleCopyPhone}
                                        className="text-slate-400 hover:text-indigo-600 transition-colors ml-0.5"
                                        title="Salin nomor HP"
                                    >
                                        {copiedPhone ? (
                                            <Check className="w-3 h-3 text-emerald-600" />
                                        ) : (
                                            <Copy className="w-3 h-3" />
                                        )}
                                    </button>
                                </div>
                            )}

                            {/* Closing Status */}
                            {activeConv.closingStatus === 'deal' && (
                                <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    DEAL 🎉
                                </span>
                            )}
                        </div>

                        {/* Sub-header info */}
                        <div className="flex items-center gap-2 text-xs text-slate-400 mt-0.5 flex-wrap">
                            <span>
                                Toko: <strong className="text-slate-600">{activeConv.businessUnitName}</strong>
                            </span>
                            <span>•</span>
                            {activeConv.assignedToName ? (
                                <span className="text-indigo-600 font-bold">
                                    CS: {activeConv.isAssignedToMe ? 'Anda Sendiri' : activeConv.assignedToName}
                                </span>
                            ) : (
                                <span className="text-amber-600 font-bold bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">
                                    Belum Diambil CS
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Header Action Buttons */}
                <div className="flex items-center gap-2">
                    {/* CS Claim / Release toggle */}
                    <button
                        type="button"
                        onClick={onClaimToggle}
                        className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5 ${
                            !activeConv.assignedToId
                                ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                : activeConv.isAssignedToMe
                                ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200'
                                : 'bg-amber-500 hover:bg-amber-600 text-white'
                        }`}
                    >
                        <UserCheck className="w-3.5 h-3.5" />
                        <span>
                            {!activeConv.assignedToId
                                ? '🙋 Ambil Chat'
                                : activeConv.isAssignedToMe
                                ? 'Lepas Handle'
                                : 'Ambil Alih'}
                        </span>
                    </button>

                    {/* WhatsApp button */}
                    {activeConv.waUrl && (
                        <a
                            href={activeConv.waUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs flex items-center gap-1.5 transition-all"
                            title="Lanjut obrolan ke WhatsApp"
                        >
                            <ExternalLink className="w-3.5 h-3.5" />
                            <span>WhatsApp</span>
                        </a>
                    )}

                    {/* Detail CRM 360 */}
                    <button
                        type="button"
                        onClick={onOpenCrmDrawer}
                        className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 border border-slate-200 text-xs font-bold shadow-2xs flex items-center gap-1.5 transition-all"
                    >
                        <Info className="w-3.5 h-3.5 text-indigo-600" />
                        <span>Detail CRM</span>
                    </button>

                    {/* Tag Closing Button */}
                    <button
                        type="button"
                        onClick={onOpenClosingModal}
                        className="px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-black shadow-2xs flex items-center gap-1.5 transition-all"
                    >
                        <CheckCircle2 className="w-3.5 h-3.5" />
                        <span>Closing Deal</span>
                    </button>
                </div>
            </div>

            {/* 2. Messages Stream */}
            <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-slate-50/60">
                {messages.length === 0 ? (
                    <div className="h-full flex flex-col items-center justify-center text-slate-400 py-12">
                        <span className="text-3xl mb-2">💬</span>
                        <p className="text-xs font-semibold">Belum ada riwayat pesan.</p>
                        <p className="text-[11px] text-slate-400 mt-0.5">
                            Ketik balasan untuk memulai percakapan.
                        </p>
                    </div>
                ) : (
                    messages.map((msg) => (
                        <div
                            key={msg.id}
                            className={`flex flex-col ${
                                msg.isCs ? 'items-end' : 'items-start'
                            }`}
                        >
                            <div className="flex items-center gap-1.5 mb-1 text-[11px] text-slate-400">
                                <span className={`font-bold ${msg.isCs ? 'text-indigo-600' : 'text-slate-700'}`}>
                                    {msg.user}
                                </span>
                                <span>•</span>
                                <span>{msg.time}</span>
                            </div>

                            {/* Rekomendasi Produk Card */}
                            {msg.product && (
                                <div className="mb-2 p-2.5 bg-white border border-slate-200 rounded-2xl shadow-2xs max-w-sm flex items-center gap-3">
                                    <img
                                        src={msg.product.thumbnail}
                                        alt={msg.product.name}
                                        className="w-14 h-14 rounded-xl object-cover border border-slate-100 shrink-0"
                                    />
                                    <div className="flex-1 min-w-0">
                                        <span className="text-[10px] font-extrabold text-indigo-600 uppercase">
                                            {msg.product.brand}
                                        </span>
                                        <h5 className="text-xs font-bold text-slate-900 truncate">
                                            {msg.product.name}
                                        </h5>
                                        <p className="text-xs font-black text-emerald-600 mt-0.5">
                                            {msg.product.price}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Lampiran Gambar/File */}
                            {msg.attachments && msg.attachments.length > 0 && (
                                <div className="mb-2 space-y-1.5 max-w-sm">
                                    {msg.attachments.map((att) => (
                                        att.isImage ? (
                                            <a
                                                key={att.id}
                                                href={att.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="block overflow-hidden rounded-2xl border border-slate-200 shadow-2xs group"
                                            >
                                                <img
                                                    src={att.url}
                                                    alt={att.fileName}
                                                    className="max-h-56 w-auto object-cover rounded-2xl group-hover:scale-102 transition-transform"
                                                />
                                            </a>
                                        ) : (
                                            <a
                                                key={att.id}
                                                href={att.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="flex items-center gap-2 p-2.5 bg-white border border-slate-200 rounded-xl text-xs text-indigo-600 hover:underline shadow-2xs"
                                            >
                                                <FileText className="w-4 h-4 text-indigo-500" />
                                                <span className="truncate font-semibold">{att.fileName}</span>
                                            </a>
                                        )
                                    ))}
                                </div>
                            )}

                            {/* Message Text Bubble */}
                            {msg.text && (
                                <div
                                    className={`px-4 py-2.5 rounded-2xl max-w-md text-xs leading-relaxed shadow-2xs ${
                                        msg.isCs
                                            ? 'bg-indigo-600 text-white rounded-tr-xs'
                                            : 'bg-white text-slate-800 border border-slate-200/80 rounded-tl-xs'
                                    }`}
                                >
                                    {msg.text}
                                </div>
                            )}
                        </div>
                    ))
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* 3. Quick Replies Bar */}
            <div className="px-4 py-1.5 border-t border-slate-200/60 bg-slate-50 flex items-center gap-1.5 overflow-x-auto scrollbar-none shrink-0 text-xs">
                <span className="text-[10px] font-extrabold uppercase text-slate-400 shrink-0">
                    Balas Cepat:
                </span>
                {quickReplies.map((qr, idx) => (
                    <button
                        key={idx}
                        type="button"
                        onClick={() => setInputText((prev) => (prev ? prev + ' ' + qr.text : qr.text))}
                        className="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-600 transition-all font-medium shrink-0 text-[11px] shadow-2xs"
                    >
                        {qr.label}
                    </button>
                ))}
            </div>

            {/* 4. Pre-send Attached Product / Image Preview Chips */}
            {(selectedProduct || attachment) && (
                <div className="px-4 py-2 bg-indigo-50/50 border-t border-indigo-100 flex items-center gap-3 shrink-0">
                    {selectedProduct && (
                        <div className="flex items-center gap-2 bg-white px-2.5 py-1.5 rounded-xl border border-indigo-200 shadow-2xs">
                            <img
                                src={selectedProduct.thumbnail}
                                alt={selectedProduct.name}
                                className="w-7 h-7 rounded-lg object-cover"
                            />
                            <div className="text-[11px] min-w-0">
                                <span className="font-bold text-slate-800 truncate block max-w-xs">
                                    {selectedProduct.name}
                                </span>
                                <span className="font-black text-indigo-600">
                                    {selectedProduct.formatted_price}
                                </span>
                            </div>
                            <button
                                type="button"
                                onClick={onRemoveProduct}
                                className="text-slate-400 hover:text-rose-500 font-bold ml-1 text-xs"
                            >
                                <X className="w-3.5 h-3.5" />
                            </button>
                        </div>
                    )}

                    {attachment && (
                        <div className="flex items-center gap-2 bg-white px-2.5 py-1.5 rounded-xl border border-indigo-200 shadow-2xs">
                            <ImageIcon className="w-4 h-4 text-indigo-600" />
                            <span className="text-[11px] font-medium text-slate-700 max-w-xs truncate">
                                {attachment.name}
                            </span>
                            <button
                                type="button"
                                onClick={() => setAttachment(null)}
                                className="text-slate-400 hover:text-rose-500 font-bold ml-1 text-xs"
                            >
                                <X className="w-3.5 h-3.5" />
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* 5. Chat Input Bar */}
            <div className="p-3.5 bg-white border-t border-slate-200/80 shrink-0">
                <form onSubmit={handleSend} className="flex items-center gap-2">
                    {/* Button Rekomendasikan Produk */}
                    <button
                        type="button"
                        onClick={onOpenProductPicker}
                        className="p-2.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-slate-200 shadow-2xs"
                        title="Rekomendasikan Produk Shopee-style"
                    >
                        <ShoppingBag className="w-4 h-4" />
                    </button>

                    {/* Button Kupon Closing */}
                    <button
                        type="button"
                        onClick={onOpenVoucherModal}
                        className="p-2.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-all border border-slate-200 shadow-2xs"
                        title="Beri Kupon Diskon Closing"
                    >
                        <Ticket className="w-4 h-4 text-amber-500" />
                    </button>

                    {/* Button Upload Foto */}
                    <label
                        htmlFor="chat-file-input"
                        className="p-2.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-slate-200 shadow-2xs cursor-pointer"
                        title="Unggah Foto / Dokumen"
                    >
                        <Paperclip className="w-4 h-4" />
                    </label>
                    <input
                        type="file"
                        id="chat-file-input"
                        className="hidden"
                        accept="image/*,application/pdf"
                        onChange={(e) => {
                            if (e.target.files?.[0]) setAttachment(e.target.files[0]);
                        }}
                    />

                    {/* Text Input */}
                    <input
                        type="text"
                        value={inputText}
                        onChange={(e) => setInputText(e.target.value)}
                        placeholder="Ketik balasan Anda ke pelanggan..."
                        className="flex-1 px-4 py-2.5 bg-slate-100 border border-slate-200/80 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none"
                    />

                    {/* Send Button */}
                    <button
                        type="submit"
                        disabled={isSending}
                        className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-xs flex items-center gap-1.5 disabled:opacity-50"
                    >
                        {isSending ? (
                            <Loader2 className="w-3.5 h-3.5 animate-spin" />
                        ) : (
                            <>
                                <span>Kirim</span>
                                <Send className="w-3.5 h-3.5" />
                            </>
                        )}
                    </button>
                </form>
            </div>
        </div>
    );
}
