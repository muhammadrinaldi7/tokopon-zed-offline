import React, { useEffect, useState } from 'react';
import {
    X,
    CheckCircle2,
    Clock,
    XCircle,
    DollarSign,
    Loader2,
    AlertTriangle
} from 'lucide-react';
import axios from 'axios';

export default function ClosingModal({
    isOpen,
    onClose,
    activeConv,
    onClosingSuccess
}) {
    const [status, setStatus] = useState('deal');
    const [amount, setAmount] = useState('');
    const [notes, setNotes] = useState('');
    const [isSaving, setIsSaving] = useState(false);
    const [errorMsg, setErrorMsg] = useState('');
    const [duplicateWarning, setDuplicateWarning] = useState('');

    // Selalu reset form ke kondisi bersih setiap kali modal dibuka untuk transaksi baru
    useEffect(() => {
        if (isOpen) {
            setStatus('deal');
            setAmount('');
            setNotes('');
            setErrorMsg('');
            setDuplicateWarning('');
        }
    }, [isOpen, activeConv?.id, activeConv?.leadId]);

    if (!isOpen || !activeConv) return null;

    const submitClosing = async (forceDuplicate = false) => {
        setIsSaving(true);
        setErrorMsg('');
        if (!forceDuplicate) {
            setDuplicateWarning('');
        }

        try {
            const numericAmount = amount ? parseFloat(String(amount).replace(/[^0-9]/g, '')) : 0;
            const res = await axios.post(`/crm/chat/${activeConv.id}/closing`, {
                status,
                amount: numericAmount,
                notes,
                force_duplicate: forceDuplicate,
            });

            if (res.data?.success) {
                onClosingSuccess(status, numericAmount, res.data);
                onClose();
            }
        } catch (err) {
            const code = err.response?.data?.code;
            const msg = err.response?.data?.message || 'Gagal menyimpan status closing.';
            if (code === 'DUPLICATE_CLOSING') {
                setDuplicateWarning(msg);
            } else {
                setErrorMsg(msg);
            }
        } finally {
            setIsSaving(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        await submitClosing(false);
    };

    return (
        <div className="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4 animate-in fade-in duration-200">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs transition-opacity"
                onClick={onClose}
            />

            {/* Modal Dialog */}
            <div className="relative w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 z-10 space-y-4 animate-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 className="text-base font-extrabold text-slate-900">
                            Tag Status Closing Chat
                        </h3>
                        <p className="text-xs text-slate-400">
                            Pencatatan konversi penjualan untuk evaluasi performa CS
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {activeConv.wonCount > 0 && (
                        <div className="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-900 leading-relaxed">
                            <strong>Multi-Closing Aktif:</strong> Customer ini sudah memiliki{' '}
                            <strong>{activeConv.wonCount} transaksi WON</strong> ({activeConv.formattedTotalWonAmount || ''}).
                            Menyimpan Deal baru akan tercatat sebagai <strong>transaksi terpisah</strong> atas nama Anda tanpa menimpa closing sebelumnya.
                        </div>
                    )}

                    {errorMsg && (
                        <div className="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 flex items-start gap-2">
                            <AlertTriangle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                            <span>{errorMsg}</span>
                        </div>
                    )}

                    {duplicateWarning && (
                        <div className="p-3 rounded-2xl bg-amber-50 border border-amber-300 text-xs text-amber-900 space-y-2.5">
                            <div className="flex items-start gap-2 font-semibold">
                                <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                                <span>{duplicateWarning}</span>
                            </div>
                            <div className="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setDuplicateWarning('')}
                                    className="px-3 py-1.5 rounded-xl bg-white border border-amber-300 text-amber-900 text-[11px] font-bold"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    disabled={isSaving}
                                    onClick={() => submitClosing(true)}
                                    className="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold"
                                >
                                    Ya, Ini Transaksi Berbeda — Tetap Simpan
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Status Radio Chips */}
                    <div className="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            onClick={() => setStatus('deal')}
                            className={`p-3 rounded-2xl border text-xs font-bold flex flex-col items-center gap-1.5 transition-all ${
                                status === 'deal'
                                    ? 'bg-emerald-50 border-emerald-400 text-emerald-800 shadow-2xs'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                            }`}
                        >
                            <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                            <span>Deal 🎉</span>
                        </button>

                        <button
                            type="button"
                            onClick={() => setStatus('follow_up')}
                            className={`p-3 rounded-2xl border text-xs font-bold flex flex-col items-center gap-1.5 transition-all ${
                                status === 'follow_up'
                                    ? 'bg-blue-50 border-blue-400 text-blue-800 shadow-2xs'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                            }`}
                        >
                            <Clock className="w-5 h-5 text-blue-600" />
                            <span>Follow Up</span>
                        </button>

                        <button
                            type="button"
                            onClick={() => setStatus('lost')}
                            className={`p-3 rounded-2xl border text-xs font-bold flex flex-col items-center gap-1.5 transition-all ${
                                status === 'lost'
                                    ? 'bg-rose-50 border-rose-400 text-rose-800 shadow-2xs'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                            }`}
                        >
                            <XCircle className="w-5 h-5 text-rose-600" />
                            <span>Batal (Lost)</span>
                        </button>
                    </div>

                    {/* Nominal Deal (Hanya muncul jika status Deal) */}
                    {status === 'deal' && (
                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                Nominal Omset Closing (Rp)
                            </label>
                            <input
                                type="number"
                                value={amount}
                                onChange={(e) => setAmount(e.target.value)}
                                placeholder="Contoh: 14500000"
                                className="w-full p-2.5 text-xs bg-slate-100 border border-slate-200 rounded-xl font-bold font-mono"
                            />
                        </div>
                    )}

                    {/* Catatan Closing */}
                    <div>
                        <label className="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                            Catatan Closing
                        </label>
                        <textarea
                            rows={3}
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            placeholder="Catatan hasil negosiasi atau alasan batal..."
                            className="w-full p-2.5 text-xs bg-slate-100 border border-slate-200 rounded-xl"
                        />
                    </div>

                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={isSaving}
                            className="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-xs flex items-center justify-center gap-1.5 transition-all disabled:opacity-50"
                        >
                            {isSaving ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                    <span>Menyimpan...</span>
                                </>
                            ) : (
                                <span>Simpan Status Closing</span>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
