import React, { useState } from 'react';
import {
    X,
    User,
    Phone,
    Mail,
    MapPin,
    Crown,
    ShoppingBag,
    FileText,
    Copy,
    Check,
    MessageCircle,
    Calendar,
    ChevronRight,
    ExternalLink
} from 'lucide-react';

export default function Customer360Drawer({
    isOpen,
    onClose,
    customerCrm,
    activeConv
}) {
    if (!isOpen) return null;

    const [copiedPhone, setCopiedPhone] = useState(false);
    const [internalNotes, setInternalNotes] = useState(activeConv?.closingNotes || '');
    const [isSavingNotes, setIsSavingNotes] = useState(false);
    const [noteSaved, setNoteSaved] = useState(false);

    const handleCopyPhone = () => {
        if (customerCrm?.phone) {
            navigator.clipboard.writeText(customerCrm.phone);
            setCopiedPhone(true);
            setTimeout(() => setCopiedPhone(false), 2000);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-hidden flex justify-end animate-in fade-in duration-200">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-slate-900/40 backdrop-blur-2xs transition-opacity"
                onClick={onClose}
            />

            {/* Slide-over panel */}
            <div className="relative w-full max-w-md bg-white shadow-2xl h-full flex flex-col z-10 animate-in slide-in-from-right duration-200">
                {/* Header */}
                <div className="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                            360°
                        </div>
                        <div>
                            <h3 className="text-sm font-extrabold text-slate-900">
                                Profil Pelanggan & CRM
                            </h3>
                            <p className="text-[11px] text-slate-500">
                                Informasi terpadu transaksi & loyalitas
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 rounded-full bg-white hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center border border-slate-200 transition-colors"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Content body */}
                <div className="flex-1 overflow-y-auto p-4 space-y-4">
                    {/* 1. Profil Identitas Pelanggan */}
                    <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-3">
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                                Informasi Kontak
                            </span>
                            <span
                                className={`text-[10px] font-extrabold px-2 py-0.5 rounded-full border ${
                                    customerCrm?.hasAccount
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-amber-50 text-amber-700 border-amber-200'
                                }`}
                            >
                                {customerCrm?.hasAccount ? 'Member Terdaftar' : 'Tamu (Guest)'}
                            </span>
                        </div>

                        <div>
                            <h4 className="text-base font-extrabold text-slate-900">
                                {customerCrm?.fullName || 'Pelanggan'}
                            </h4>
                            {customerCrm?.email && (
                                <div className="flex items-center gap-1.5 text-xs text-slate-500 mt-0.5">
                                    <Mail className="w-3.5 h-3.5 text-slate-400" />
                                    <span>{customerCrm.email}</span>
                                </div>
                            )}
                        </div>

                        {/* Phone with 1-click copy & WhatsApp */}
                        {customerCrm?.phone ? (
                            <div className="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200/80">
                                <div className="flex items-center gap-2">
                                    <Phone className="w-4 h-4 text-indigo-600" />
                                    <span className="text-xs font-mono font-bold text-slate-800">
                                        {customerCrm.phone}
                                    </span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <button
                                        type="button"
                                        onClick={handleCopyPhone}
                                        className="px-2 py-1 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-indigo-600 text-[11px] font-bold flex items-center gap-1 transition-colors"
                                        title="Salin nomor HP"
                                    >
                                        {copiedPhone ? (
                                            <>
                                                <Check className="w-3 h-3 text-emerald-600" />
                                                <span className="text-emerald-700">Tersalin</span>
                                            </>
                                        ) : (
                                            <>
                                                <Copy className="w-3 h-3" />
                                                <span>Salin</span>
                                            </>
                                        )}
                                    </button>
                                    {activeConv?.waUrl && (
                                        <a
                                            href={activeConv.waUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold flex items-center gap-1 transition-colors"
                                        >
                                            <ExternalLink className="w-3 h-3" />
                                            <span>WA</span>
                                        </a>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <p className="text-xs text-slate-400 italic">
                                Nomor HP belum ditautkan pada akun ini.
                            </p>
                        )}

                        {/* Alamat / Domisili */}
                        <div className="pt-2 border-t border-slate-100 flex items-start gap-2 text-xs text-slate-600">
                            <MapPin className="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                            <div>
                                <span className="font-bold text-slate-700 block text-[11px] uppercase tracking-wider">
                                    Alamat Pengiriman / Domisili:
                                </span>
                                <span className="mt-0.5 block leading-relaxed">
                                    {customerCrm?.address || 'Belum diisi'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* 2. Kartu Status Loyalitas & Poin */}
                    <div className="p-4 rounded-2xl bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-transparent border border-amber-300/80 shadow-2xs space-y-3">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1.5">
                                <Crown className="w-4 h-4 text-amber-600" />
                                <span className="text-xs font-black uppercase tracking-wider text-amber-900">
                                    Tier Keanggotaan
                                </span>
                            </div>
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500 text-white shadow-2xs">
                                {customerCrm?.loyalty?.tier || 'BRONZE'}
                            </span>
                        </div>

                        <div className="grid grid-cols-2 gap-2 pt-1">
                            <div className="p-2.5 rounded-xl bg-white/80 border border-amber-200">
                                <span className="text-[10px] font-bold text-slate-500 block uppercase">
                                    Saldo Poin Aktif
                                </span>
                                <span className="text-base font-black text-amber-700 mt-0.5 block">
                                    {(customerCrm?.loyalty?.pointsBalance || 0).toLocaleString('id-ID')} Poin
                                </span>
                            </div>
                            <div className="p-2.5 rounded-xl bg-white/80 border border-amber-200">
                                <span className="text-[10px] font-bold text-slate-500 block uppercase">
                                    Total Belanja (Spend)
                                </span>
                                <span className="text-sm font-black text-slate-900 mt-0.5 block">
                                    {customerCrm?.loyalty?.formattedTotalSpend || 'Rp 0'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* 3. Riwayat 3 Pesanan Terakhir */}
                    <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-2.5">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1.5">
                                <ShoppingBag className="w-4 h-4 text-indigo-600" />
                                <span className="text-xs font-extrabold uppercase tracking-wider text-slate-900">
                                    Pesanan Terakhir
                                </span>
                            </div>
                            <span className="text-[11px] text-slate-400 font-bold">
                                Total {customerCrm?.totalOrdersCount || 0} Order
                            </span>
                        </div>

                        {customerCrm?.recentOrders && customerCrm.recentOrders.length > 0 ? (
                            <div className="space-y-2 pt-1">
                                {customerCrm.recentOrders.map((order) => (
                                    <div
                                        key={order.id}
                                        className="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100/80 transition-colors"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-mono font-bold text-slate-800">
                                                {order.orderNumber}
                                            </span>
                                            <span className="text-[10px] font-mono text-slate-400">
                                                {order.date}
                                            </span>
                                        </div>
                                        <p className="text-xs text-slate-600 font-medium truncate mt-1">
                                            {order.firstItemName}
                                            {order.itemCount > 1 ? ` (+${order.itemCount - 1} item)` : ''}
                                        </p>
                                        <div className="flex items-center justify-between mt-1.5 pt-1 border-t border-slate-200/60">
                                            <span className="text-xs font-black text-indigo-600">
                                                {order.grandTotal}
                                            </span>
                                            <span className="text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                {order.status}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-xs text-slate-400 italic py-2">
                                Belum ada riwayat pesanan tercatat.
                            </p>
                        )}
                    </div>

                    {/* 4. Catatan Khusus CRM Internal */}
                    <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-2">
                        <div className="flex items-center gap-1.5">
                            <FileText className="w-4 h-4 text-slate-600" />
                            <span className="text-xs font-extrabold uppercase tracking-wider text-slate-900">
                                Catatan CRM Internal
                            </span>
                        </div>
                        <p className="text-[11px] text-slate-400 leading-normal">
                            Catatan ini hanya terlihat oleh staf CS & Admin, tidak dapat dibaca oleh pelanggan.
                        </p>
                        <textarea
                            rows={3}
                            value={internalNotes}
                            onChange={(e) => setInternalNotes(e.target.value)}
                            placeholder="Contoh: Customer langganan toko Veteran, minta bonus temper glass..."
                            className="w-full p-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all"
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
