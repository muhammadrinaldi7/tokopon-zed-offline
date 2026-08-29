<div class="relative min-h-screen bg-neutral-50 p-4 sm:p-8 font-sans">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600 tracking-tight">
                    Finance Dashboard: Garansi & Retur
                </h1>
                <p class="text-sm text-neutral-500 mt-2 font-medium">Monitoring Top-up dan Pencairan Refund atas Klaim Garansi Ganti Unit.</p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            {{-- Tab 1: Menunggu Refund (Uang Keluar) --}}
            <div wire:click="setTab('waiting_refund')" class="cursor-pointer bg-white rounded-3xl p-6 shadow-sm border {{ $activeTab === 'waiting_refund' ? 'border-rose-400 ring-4 ring-rose-50' : 'border-neutral-100 hover:border-neutral-300' }} transition-all relative overflow-hidden group">
                @if($activeTab === 'waiting_refund' && $summary['waiting_refund'] > 0)
                    <div class="absolute inset-0 bg-rose-50/50 animate-pulse"></div>
                @endif
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] uppercase tracking-widest font-bold {{ $activeTab === 'waiting_refund' ? 'text-rose-600' : 'text-neutral-500' }}">Perlu Di-refund</p>
                        <h3 class="text-3xl font-black {{ $activeTab === 'waiting_refund' ? 'text-rose-700' : 'text-neutral-800' }} mt-1">{{ $summary['waiting_refund'] }} <span class="text-sm font-bold opacity-50">Klaim</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl {{ $activeTab === 'waiting_refund' ? 'bg-rose-100 text-rose-600' : 'bg-neutral-100 text-neutral-400' }} flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Menunggu Pembayaran Top-up (Uang Masuk) --}}
            <div wire:click="setTab('waiting_payment')" class="cursor-pointer bg-white rounded-3xl p-6 shadow-sm border {{ $activeTab === 'waiting_payment' ? 'border-emerald-400 ring-4 ring-emerald-50' : 'border-neutral-100 hover:border-neutral-300' }} transition-all relative overflow-hidden group">
                @if($activeTab === 'waiting_payment' && $summary['waiting_payment'] > 0)
                    <div class="absolute inset-0 bg-emerald-50/50 animate-pulse"></div>
                @endif
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] uppercase tracking-widest font-bold {{ $activeTab === 'waiting_payment' ? 'text-emerald-600' : 'text-neutral-500' }}">Tunggu Top-up</p>
                        <h3 class="text-3xl font-black {{ $activeTab === 'waiting_payment' ? 'text-emerald-700' : 'text-neutral-800' }} mt-1">{{ $summary['waiting_payment'] }} <span class="text-sm font-bold opacity-50">Klaim</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl {{ $activeTab === 'waiting_payment' ? 'bg-emerald-100 text-emerald-600' : 'bg-neutral-100 text-neutral-400' }} flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Selesai --}}
            <div wire:click="setTab('resolved')" class="cursor-pointer bg-white rounded-3xl p-6 shadow-sm border {{ $activeTab === 'resolved' ? 'border-indigo-400 ring-4 ring-indigo-50' : 'border-neutral-100 hover:border-neutral-300' }} transition-all relative overflow-hidden group">
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] uppercase tracking-widest font-bold {{ $activeTab === 'resolved' ? 'text-indigo-600' : 'text-neutral-500' }}">Selesai</p>
                        <h3 class="text-3xl font-black {{ $activeTab === 'resolved' ? 'text-indigo-700' : 'text-neutral-800' }} mt-1">{{ $summary['resolved'] }} <span class="text-sm font-bold opacity-50">Klaim</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl {{ $activeTab === 'resolved' ? 'bg-indigo-100 text-indigo-600' : 'bg-neutral-100 text-neutral-400' }} flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content Table --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-neutral-200/40 border border-neutral-100 overflow-hidden">
            <div class="p-6 border-b border-neutral-100 bg-neutral-50/50 flex justify-between items-center">
                <h2 class="font-black text-lg text-neutral-800">
                    @if($activeTab === 'waiting_refund') Antrean Refund Pelanggan
                    @elseif($activeTab === 'waiting_payment') Menunggu Pembayaran Top-up
                    @else Riwayat Transaksi Keuangan
                    @endif
                </h2>
            </div>
            
            @if ($claims->isEmpty())
                <div class="p-16 text-center">
                    <div class="w-20 h-20 bg-neutral-50 rounded-full flex items-center justify-center mx-auto mb-6 border-8 border-white shadow-sm">
                        <svg class="w-8 h-8 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    </div>
                    <h3 class="text-xl font-black text-neutral-800 mb-2">Tidak ada data</h3>
                    <p class="text-neutral-500 font-medium text-sm">Semua antrean keuangan di kategori ini sudah beres.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white">
                                <th class="px-6 py-4 text-xs font-black text-neutral-400 uppercase tracking-widest border-b border-neutral-100">Klaim / Pelanggan</th>
                                <th class="px-6 py-4 text-xs font-black text-neutral-400 uppercase tracking-widest border-b border-neutral-100">Unit Pengganti</th>
                                <th class="px-6 py-4 text-xs font-black text-neutral-400 uppercase tracking-widest border-b border-neutral-100 text-right">Nominal Selisih</th>
                                <th class="px-6 py-4 text-xs font-black text-neutral-400 uppercase tracking-widest border-b border-neutral-100 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($claims as $claim)
                                <tr class="hover:bg-neutral-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-neutral-800">{{ $claim->claim_number }}</div>
                                        <div class="text-sm font-medium text-neutral-500 mt-0.5">{{ $claim->customer->name ?? 'Pelanggan' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $variant = $claim->warranty->orderItem->variant ?? null;
                                            $productName = '-';
                                            if ($variant) {
                                                if (isset($variant->secondProduct)) {
                                                    $productName = ($variant->secondProduct->name ?? '') . ' ' . ($variant->storage ?? '');
                                                } elseif (isset($variant->product)) {
                                                    $productName = ($variant->product->name ?? '') . ' ' . ($variant->variant_name ?? '');
                                                } else {
                                                    $productName = $variant->name ?? '-';
                                                }
                                            }
                                        @endphp
                                        <div class="font-bold text-neutral-700 text-sm">{{ trim($productName) }}</div>
                                        <div class="text-[11px] font-black text-neutral-400 uppercase tracking-wider mt-0.5">{{ $claim->resolution_type === 'replacement_different' ? 'Upgrade/Downgrade' : 'Sama' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="font-black text-lg {{ $activeTab === 'waiting_refund' ? 'text-rose-600' : 'text-emerald-600' }}">
                                            Rp {{ number_format($claim->refund_amount ?? 0, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($activeTab === 'waiting_refund')
                                            <button wire:click="openResolveModal({{ $claim->id }})" class="px-4 py-2 bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white font-bold text-xs rounded-xl transition shadow-sm">
                                                Konfirmasi Refund
                                            </button>
                                        @elseif($activeTab === 'waiting_payment')
                                            <button wire:click="openResolveModal({{ $claim->id }})" class="px-4 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white font-bold text-xs rounded-xl transition shadow-sm">
                                                Konfirmasi Transfer
                                            </button>
                                        @else
                                            <span class="px-3 py-1 bg-neutral-100 text-neutral-500 font-bold text-[10px] uppercase rounded-lg">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Konfirmasi Keuangan -->
    @if ($showResolveModal)
        <div wire:key="modal-resolve" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeResolveModal"></div>
            <div class="relative bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 {{ $activeTab === 'waiting_refund' ? 'bg-rose-50' : 'bg-emerald-50' }}">
                    <div>
                        <h3 class="font-bold text-xl {{ $activeTab === 'waiting_refund' ? 'text-rose-900' : 'text-emerald-900' }}">
                            {{ $activeTab === 'waiting_refund' ? 'Konfirmasi Refund' : 'Konfirmasi Pelunasan' }}
                        </h3>
                        <p class="text-xs {{ $activeTab === 'waiting_refund' ? 'text-rose-700' : 'text-emerald-700' }} mt-0.5">Pilih akun Kas/Bank Accurate</p>
                    </div>
                    <button wire:click="closeResolveModal"
                        class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                Akun Kas/Bank <span class="text-rose-500">*</span>
                            </label>
                            <select wire:model="selectedBankNo" required
                                class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 {{ $activeTab === 'waiting_refund' ? 'focus:ring-rose-500 focus:border-rose-500' : 'focus:ring-emerald-500 focus:border-emerald-500' }} block p-3 transition-colors">
                                <option value="">-- Pilih Akun --</option>
                                <option value="10.02.103">Kas Retur</option>
                                @foreach ($banks as $account)
                                    <option value="{{ $account->account_no }}">{{ $account->account_no }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedBankNo')
                                <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button wire:click="closeResolveModal" type="button"
                        class="px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 hover:text-gray-900 rounded-xl transition-colors shadow-sm">
                        Batal
                    </button>
                    <button wire:click="confirmResolve" type="button"
                        class="px-5 py-2.5 text-sm font-bold text-white {{ $activeTab === 'waiting_refund' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} rounded-xl transition-all shadow-md flex items-center gap-2">
                        Simpan & Selesai
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
