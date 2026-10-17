import React, { useState } from 'react';
import {
    X,
    Ticket,
    Sparkles,
    Send,
    Plus,
    Check,
    AlertCircle,
    Loader2
} from 'lucide-react';
import axios from 'axios';

export default function QuickVoucherModal({
    isOpen,
    onClose,
    activeCoupons = [],
    onSendCouponText
}) {
    if (!isOpen) return null;

    const [activeTab, setActiveTab] = useState('list'); // 'list' | 'custom'
    const [discountAmount, setDiscountAmount] = useState('50000');
    const [minOrder, setMinOrder] = useState('500000');
    const [discountType, setDiscountType] = useState('FIXED');
    const [voucherTitle, setVoucherTitle] = useState('Kupon Spesial Closing CS');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMsg, setErrorMsg] = useState('');

    const handleShareExisting = (coupon) => {
        const text = `🎉 Halo kak! Khusus untuk kakak hari ini, kakak bisa menggunakan kupon promo *${coupon.code}* untuk mendapatkan diskon ${coupon.discount_type === 'PERCENTAGE' ? coupon.discount_amount + '%' : 'Rp ' + Number(coupon.discount_amount).toLocaleString('id-ID')} saat checkout di aplikasi Tokopon ya!`;
        onSendCouponText(text);
        onClose();
    };

    const handleGenerateCustom = async (e) => {
        e.preventDefault();
        setErrorMsg('');
        setIsSubmitting(true);

        try {
            const res = await axios.post('/crm/chat/quick-voucher', {
                discount_amount: parseFloat(discountAmount),
                min_order_amount: parseFloat(minOrder) || 0,
                discount_type: discountType,
                title: voucherTitle,
            });

            if (res.data?.success) {
                const text = `🎉 Halo kak! Khusus untuk pemesanan kakak sekarang, kami berikan kupon closing spesial: *${res.data.coupon.code}* senilai ${discountType === 'PERCENTAGE' ? discountAmount + '%' : 'Rp ' + Number(discountAmount).toLocaleString('id-ID')} (berlaku 2 hari). Yuk langsung kami bantu prosesnya!`;
                onSendCouponText(text);
                onClose();
            }
        } catch (err) {
            setErrorMsg(err.response?.data?.message || 'Gagal menerbitkan kupon.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4 animate-in fade-in duration-200">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs transition-opacity"
                onClick={onClose}
            />

            {/* Modal Dialog */}
            <div className="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl p-6 z-10 space-y-4 animate-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                            <Ticket className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="text-base font-extrabold text-slate-900">
                                Beri Kupon Diskon Closing
                            </h3>
                            <p className="text-xs text-slate-400">
                                Insentif voucher untuk mempercepat deal transaksi
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

                {/* Tab Switcher */}
                <div className="flex rounded-xl bg-slate-100 p-1 text-xs font-bold">
                    <button
                        type="button"
                        onClick={() => setActiveTab('list')}
                        className={`flex-1 py-1.5 rounded-lg transition-all ${
                            activeTab === 'list'
                                ? 'bg-white text-slate-900 shadow-2xs'
                                : 'text-slate-500 hover:text-slate-900'
                        }`}
                    >
                        Kupon Promo Aktif
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('custom')}
                        className={`flex-1 py-1.5 rounded-lg transition-all flex items-center justify-center gap-1 ${
                            activeTab === 'custom'
                                ? 'bg-white text-slate-900 shadow-2xs'
                                : 'text-slate-500 hover:text-slate-900'
                        }`}
                    >
                        <Sparkles className="w-3.5 h-3.5 text-amber-500" />
                        <span>Kupon Custom Closing</span>
                    </button>
                </div>

                {/* Tab Content 1: Kupon Aktif */}
                {activeTab === 'list' && (
                    <div className="space-y-2 max-h-72 overflow-y-auto pr-1">
                        {activeCoupons.length === 0 ? (
                            <p className="text-xs text-slate-400 italic text-center py-6">
                                Belum ada kupon promo aktif yang terdaftar.
                            </p>
                        ) : (
                            activeCoupons.map((coupon) => (
                                <div
                                    key={coupon.id}
                                    className="p-3 rounded-2xl border border-slate-200/80 bg-slate-50/60 hover:bg-amber-50/60 hover:border-amber-200 flex items-center justify-between transition-colors"
                                >
                                    <div>
                                        <span className="font-mono text-xs font-extrabold text-indigo-600 bg-indigo-50 border border-indigo-200/60 px-2 py-0.5 rounded-md">
                                            {coupon.code}
                                        </span>
                                        <h5 className="text-xs font-bold text-slate-900 mt-1">
                                            {coupon.title}
                                        </h5>
                                        <p className="text-[11px] text-slate-500">
                                            Diskon {coupon.discount_type === 'PERCENTAGE' ? coupon.discount_amount + '%' : 'Rp ' + Number(coupon.discount_amount).toLocaleString('id-ID')}
                                            {coupon.min_order_amount > 0 ? ` (Min. Rp ${Number(coupon.min_order_amount).toLocaleString('id-ID')})` : ''}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => handleShareExisting(coupon)}
                                        className="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-2xs flex items-center gap-1 transition-colors shrink-0"
                                    >
                                        <Send className="w-3 h-3" />
                                        <span>Kirim</span>
                                    </button>
                                </div>
                            ))
                        )}
                    </div>
                )}

                {/* Tab Content 2: Generate Kupon Custom CS */}
                {activeTab === 'custom' && (
                    <form onSubmit={handleGenerateCustom} className="space-y-3">
                        {errorMsg && (
                            <div className="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                                <AlertCircle className="w-4 h-4 shrink-0" />
                                <span>{errorMsg}</span>
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                    Tipe Diskon
                                </label>
                                <select
                                    value={discountType}
                                    onChange={(e) => setDiscountType(e.target.value)}
                                    className="w-full p-2 text-xs bg-slate-100 border border-slate-200 rounded-xl font-bold"
                                >
                                    <option value="FIXED">Potongan Rupiah (Rp)</option>
                                    <option value="PERCENTAGE">Persentase (%)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                    Besar Diskon
                                </label>
                                <input
                                    type="number"
                                    value={discountAmount}
                                    onChange={(e) => setDiscountAmount(e.target.value)}
                                    placeholder="50000"
                                    className="w-full p-2 text-xs bg-slate-100 border border-slate-200 rounded-xl font-bold font-mono"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                Minimal Belanja (Rp)
                            </label>
                            <input
                                type="number"
                                value={minOrder}
                                onChange={(e) => setMinOrder(e.target.value)}
                                placeholder="500000"
                                className="w-full p-2 text-xs bg-slate-100 border border-slate-200 rounded-xl font-medium font-mono"
                            />
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="w-full py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold text-xs shadow-xs flex items-center justify-center gap-1.5 transition-all disabled:opacity-50"
                            >
                                {isSubmitting ? (
                                    <>
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                        <span>Menerbitkan Kupon...</span>
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="w-4 h-4" />
                                        <span>Terbitkan & Kirim ke Chat</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}
