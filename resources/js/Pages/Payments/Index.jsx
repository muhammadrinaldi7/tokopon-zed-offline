import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import EcommerceLayout from '@/Layouts/EcommerceLayout';
import {
    CreditCard,
    Plus,
    Copy,
    Check,
    Store,
    Smartphone,
    ToggleLeft,
    ToggleRight,
    Edit2,
    Trash2,
    CheckCircle2,
    AlertCircle,
    X,
    Loader2,
    QrCode
} from 'lucide-react';
import axios from 'axios';

export default function PaymentsIndex({
    paymentMethods = [],
    stats = {},
    popularBanks = {},
    filters = {}
}) {
    const { businessUnits } = usePage().props;

    const [search, setSearch] = useState(filters?.search || '');
    const [copiedAccount, setCopiedAccount] = useState(null);

    // Modal Form State (Create / Edit)
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingMethod, setEditingMethod] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        bank_name: 'BCA',
        account_number: '',
        account_owner: 'PT TOKOPON GADGET INDONESIA',
        business_unit_id: businessUnits?.[0]?.id || '',
        accurate_bank_no: '11.10.001',
        is_active: true,
        is_visible_mobile: true,
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleCopy = (accNo) => {
        navigator.clipboard.writeText(accNo);
        setCopiedAccount(accNo);
        setTimeout(() => setCopiedAccount(null), 2000);
    };

    const handleOpenCreate = () => {
        setEditingMethod(null);
        setFormData({
            name: 'Transfer Bank BCA',
            bank_name: 'BCA',
            account_number: '',
            account_owner: 'PT TOKOPON GADGET INDONESIA',
            business_unit_id: businessUnits?.[0]?.id || '',
            accurate_bank_no: '11.10.001',
            is_active: true,
            is_visible_mobile: true,
        });
        setIsFormOpen(true);
    };

    const handleOpenEdit = (pm) => {
        setEditingMethod(pm);
        setFormData({
            name: pm.name,
            bank_name: pm.bank_name || 'BCA',
            account_number: pm.account_number,
            account_owner: pm.account_owner,
            business_unit_id: pm.business_unit_id,
            accurate_bank_no: pm.accurate_bank_no || '11.10.001',
            is_active: pm.is_active,
            is_visible_mobile: pm.is_visible_mobile,
        });
        setIsFormOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsSubmitting(true);

        if (editingMethod) {
            router.put(`/crm/payments/${editingMethod.id}`, formData, {
                onSuccess: () => {
                    setIsFormOpen(false);
                    setIsSubmitting(false);
                },
                onError: () => setIsSubmitting(false),
            });
        } else {
            router.post('/crm/payments', formData, {
                onSuccess: () => {
                    setIsFormOpen(false);
                    setIsSubmitting(false);
                },
                onError: () => setIsSubmitting(false),
            });
        }
    };

    const handleToggleActive = async (pm) => {
        try {
            await axios.post(`/crm/payments/${pm.id}/toggle-active`);
            router.reload({ preserveScroll: true });
        } catch (e) {
            alert('Gagal mengubah status aktif');
        }
    };

    const handleToggleMobile = async (pm) => {
        try {
            await axios.post(`/crm/payments/${pm.id}/toggle-mobile`);
            router.reload({ preserveScroll: true });
        } catch (e) {
            alert('Gagal mengubah visibilitas mobile');
        }
    };

    const handleDelete = (pm) => {
        if (confirm(`Yakin ingin menghapus rekening ${pm.name} (${pm.account_number})?`)) {
            router.delete(`/crm/payments/${pm.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <EcommerceLayout title="Rekening Bank Toko">
            <Head title="Manajemen Rekening Pembayaran Bank" />

            <div className="p-5 md:p-6 max-w-7xl mx-auto space-y-6">
                {/* 1. Header Toolbar */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-cyan-500/10 text-cyan-600">
                                <CreditCard className="w-6 h-6" />
                            </span>
                            Rekening Pembayaran Bank
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Kelola nomor rekening bank resmi toko dan QRIS untuk verifikasi transfer checkout pembeli.
                        </p>
                    </div>

                    <button
                        onClick={handleOpenCreate}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-bold text-sm shadow-md shadow-cyan-500/20 hover:shadow-lg transition-all active:scale-95"
                    >
                        <Plus className="w-4 h-4" />
                        Tambah Rekening Baru
                    </button>
                </div>

                {/* 2. KPI Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center font-bold">
                            <CreditCard className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Rekening</div>
                            <div className="text-2xl font-black text-slate-900 mt-0.5">
                                {stats?.total_methods ?? 0}
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <Smartphone className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tampil di App Mobile</div>
                            <div className="text-2xl font-black text-emerald-600 mt-0.5">
                                {stats?.mobile_active ?? 0}
                                <span className="text-xs font-medium text-slate-400 ml-1.5">aktif</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <CheckCircle2 className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Operasional</div>
                            <div className="text-sm font-black text-slate-800 mt-0.5">
                                Terhubung dengan Akun Accurate
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3. Payment Methods Grid */}
                {paymentMethods.length === 0 ? (
                    <div className="bg-white rounded-3xl border border-slate-200/80 p-12 text-center">
                        <CreditCard className="w-12 h-12 text-slate-300 mx-auto mb-2" />
                        <h3 className="text-base font-bold text-slate-800">Belum ada rekening pembayaran</h3>
                        <p className="text-xs text-slate-500 mt-1">
                            Tambahkan rekening bank resmi agar pembeli dapat melakukan pembayaran transfer.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {paymentMethods.map((pm) => {
                            const isQris = pm.bank_name?.toUpperCase() === 'QRIS';

                            return (
                                <div
                                    key={pm.id}
                                    className={`bg-white rounded-2xl border transition-all duration-200 p-5 shadow-2xs hover:shadow-md flex flex-col justify-between ${
                                        pm.is_active ? 'border-slate-200/90' : 'border-slate-200 opacity-60 bg-slate-50/50'
                                    }`}
                                >
                                    <div>
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="flex items-center gap-3">
                                                <div
                                                    className={`w-11 h-11 rounded-xl flex items-center justify-center font-black text-sm shrink-0 shadow-2xs ${
                                                        isQris
                                                            ? 'bg-rose-50 text-rose-600 border border-rose-200'
                                                            : 'bg-blue-50 text-blue-700 border border-blue-200 font-mono'
                                                    }`}
                                                >
                                                    {isQris ? <QrCode className="w-6 h-6" /> : pm.bank_name.slice(0, 4)}
                                                </div>
                                                <div>
                                                    <h3 className="font-extrabold text-sm text-slate-900 tracking-tight">
                                                        {pm.name}
                                                    </h3>
                                                    <span className="text-[11px] text-slate-400 font-semibold">
                                                        {pm.business_unit_name}
                                                    </span>
                                                </div>
                                            </div>

                                            <button
                                                onClick={() => handleToggleActive(pm)}
                                                className="text-slate-400 hover:text-slate-600 transition-colors"
                                                title={pm.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                            >
                                                {pm.is_active ? (
                                                    <ToggleRight className="w-7 h-7 text-emerald-500" />
                                                ) : (
                                                    <ToggleLeft className="w-7 h-7 text-slate-300" />
                                                )}
                                            </button>
                                        </div>

                                        {/* Account Number Box */}
                                        <div className="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                            <div className="flex items-center justify-between">
                                                <span className="text-[10.5px] uppercase font-bold text-slate-400">
                                                    Nomor Rekening:
                                                </span>
                                                <button
                                                    onClick={() => handleCopy(pm.account_number)}
                                                    className="inline-flex items-center gap-1 text-[11px] font-bold text-cyan-700 hover:text-cyan-800"
                                                >
                                                    {copiedAccount === pm.account_number ? (
                                                        <>
                                                            <Check className="w-3 h-3 text-emerald-600" />
                                                            <span className="text-emerald-600">Tersalin</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Copy className="w-3 h-3" />
                                                            <span>Salin</span>
                                                        </>
                                                    )}
                                                </button>
                                            </div>
                                            <div className="font-mono font-black text-slate-900 text-base tracking-wider">
                                                {pm.account_number}
                                            </div>
                                            <div className="text-[11px] text-slate-600 font-semibold truncate pt-0.5">
                                                a/n {pm.account_owner}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Footer Controls */}
                                    <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <button
                                            onClick={() => handleToggleMobile(pm)}
                                            className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors ${
                                                pm.is_visible_mobile
                                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60'
                                                    : 'bg-slate-100 text-slate-400'
                                            }`}
                                        >
                                            <Smartphone className="w-3 h-3" />
                                            <span>{pm.is_visible_mobile ? 'Muncul di Mobile' : 'Disembunyikan'}</span>
                                        </button>

                                        <div className="flex items-center gap-1">
                                            <button
                                                onClick={() => handleOpenEdit(pm)}
                                                className="p-1.5 rounded-lg text-slate-500 hover:text-cyan-600 hover:bg-cyan-50 transition-colors"
                                                title="Edit rekening"
                                            >
                                                <Edit2 className="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(pm)}
                                                className="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                title="Hapus rekening"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Modal Form: Tambah / Edit Rekening */}
            {isFormOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-2xs" onClick={() => setIsFormOpen(false)} />

                    <div className="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-md w-full p-6 z-10 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-lg font-black text-slate-900 tracking-tight">
                                    {editingMethod ? 'Edit Rekening Pembayaran' : 'Tambah Rekening Bank Baru'}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Rekening ini akan tampil pada instruksi pembayaran checkout pembeli.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsFormOpen(false)}
                                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4 mt-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Nama Bank / Metode *</label>
                                <select
                                    value={formData.bank_name}
                                    onChange={(e) => {
                                        const b = e.target.value;
                                        setFormData({
                                            ...formData,
                                            bank_name: b,
                                            name: b === 'QRIS' ? 'QRIS Toko' : `Transfer Bank ${b}`,
                                        });
                                    }}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500/20"
                                >
                                    {Object.entries(popularBanks).map(([code, label]) => (
                                        <option key={code} value={code}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Label Tampilan *</label>
                                <input
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    required
                                    placeholder="Contoh: Transfer Bank BCA"
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Nomor Rekening / Merchant ID *</label>
                                <input
                                    type="text"
                                    value={formData.account_number}
                                    onChange={(e) => setFormData({ ...formData, account_number: e.target.value })}
                                    required
                                    placeholder="Contoh: 123-456-7890"
                                    className="w-full px-3.5 py-2 font-mono font-bold bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Atas Nama Pemilik Rekening *</label>
                                <input
                                    type="text"
                                    value={formData.account_owner}
                                    onChange={(e) => setFormData({ ...formData, account_owner: e.target.value })}
                                    required
                                    placeholder="Contoh: PT TOKOPON GADGET INDONESIA"
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm uppercase font-semibold"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Store / Unit Bisnis *</label>
                                    <select
                                        value={formData.business_unit_id}
                                        onChange={(e) => setFormData({ ...formData, business_unit_id: e.target.value })}
                                        required
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold"
                                    >
                                        {businessUnits?.map((bu) => (
                                            <option key={bu.id} value={bu.id}>
                                                {bu.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">No. Akun Accurate *</label>
                                    <input
                                        type="text"
                                        value={formData.accurate_bank_no}
                                        onChange={(e) => setFormData({ ...formData, accurate_bank_no: e.target.value })}
                                        required
                                        placeholder="11.10.001"
                                        className="w-full px-3 py-2 font-mono bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2 pt-2">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={formData.is_active}
                                        onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                        className="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300"
                                    />
                                    <span className="text-xs font-bold text-slate-700">Aktifkan Rekening Ini</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={formData.is_visible_mobile}
                                        onChange={(e) => setFormData({ ...formData, is_visible_mobile: e.target.checked })}
                                        className="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300"
                                    />
                                    <span className="text-xs font-bold text-slate-700">Tampilkan pada Checkout Aplikasi Mobile</span>
                                </label>
                            </div>

                            <div className="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setIsFormOpen(false)}
                                    className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 text-white text-xs font-bold shadow-md shadow-cyan-500/20 disabled:opacity-50"
                                >
                                    {isSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Simpan Rekening
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </EcommerceLayout>
    );
}
