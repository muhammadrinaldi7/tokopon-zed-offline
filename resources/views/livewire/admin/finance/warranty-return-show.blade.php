<div class="relative min-h-screen bg-neutral-50 p-4 sm:p-8 font-sans">
    <div class="max-w-6xl mx-auto">
        {{-- Header & Back Button --}}
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('finance.warranty-return') }}" class="w-10 h-10 bg-white border border-neutral-200 rounded-xl flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600 tracking-tight">
                    Proses Transaksi Finance
                </h1>
                <p class="text-sm text-neutral-500 mt-1 font-medium">Klaim Garansi #{{ $claim->claim_number }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Kolom Kiri: Informasi Klaim --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Card: Status & Info Dasar --}}
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-xl shadow-neutral-200/40 border border-neutral-100">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                        <div>
                            <h2 class="text-xl font-black text-neutral-800">Detail Klaim</h2>
                            <p class="text-sm text-neutral-500 mt-1">Informasi pelanggan dan perangkat</p>
                        </div>
                        <div>
                            @if($isDowngrade)
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-50 text-rose-700 text-sm font-bold rounded-xl border border-rose-100">
                                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    Menunggu Refund (Uang Keluar)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 text-emerald-700 text-sm font-bold rounded-xl border border-emerald-100">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Menunggu Top-up (Uang Masuk)
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-100">
                            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Pelanggan</p>
                            <p class="font-black text-neutral-800 text-lg">{{ $claim->customer->name ?? '-' }}</p>
                            <p class="text-sm font-medium text-neutral-500">{{ $claim->customer->phone ?? '-' }}</p>
                        </div>
                        <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-100">
                            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Business Unit</p>
                            <p class="font-black text-neutral-800 text-lg">{{ $claim->warranty->policy->businessUnit->name ?? 'Default' }}</p>
                            <p class="text-sm font-medium text-neutral-500">Cabang Penjualan Asli</p>
                        </div>
                    </div>
                </div>

                {{-- Card: Detail Perangkat --}}
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-xl shadow-neutral-200/40 border border-neutral-100">
                    <h2 class="text-xl font-black text-neutral-800 mb-6">Unit Pengganti</h2>
                    
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

                    <div class="flex items-center gap-4 p-5 bg-blue-50/50 border border-blue-100 rounded-2xl mb-6">
                        <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <p class="font-black text-blue-900 text-lg">{{ $productName }}</p>
                            <p class="text-sm font-medium text-blue-700 mt-0.5">Tipe Resolusi: {{ $claim->resolution_type === 'replacement_different' ? 'Upgrade/Downgrade' : 'Sama' }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl border border-neutral-100">
                            <p class="text-xs font-bold text-neutral-400 mb-1">IMEI LAMA (Rusak)</p>
                            <p class="font-mono font-bold text-neutral-700">{{ $claim->serial_number }}</p>
                        </div>
                        <div class="p-4 rounded-xl border border-neutral-100 bg-neutral-50">
                            <p class="text-xs font-bold text-neutral-400 mb-1">IMEI BARU (Pengganti)</p>
                            <p class="font-mono font-bold text-neutral-900">{{ $claim->replacement_serial_number ?? 'Belum ditentukan' }}</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Kolom Kanan: Form Transaksi --}}
            <div class="space-y-6">
                
                {{-- Summary Nominal --}}
                <div class="bg-white rounded-3xl p-6 shadow-xl shadow-neutral-200/40 border {{ $isDowngrade ? 'border-rose-100' : 'border-emerald-100' }}">
                    <p class="text-sm font-bold text-neutral-500 mb-2">Total Transaksi</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm font-black {{ $isDowngrade ? 'text-rose-500' : 'text-emerald-500' }}">Rp</span>
                        <h3 class="text-4xl font-black {{ $isDowngrade ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ number_format($refundAmount, 0, ',', '.') }}
                        </h3>
                    </div>
                    <p class="text-xs font-medium {{ $isDowngrade ? 'text-rose-400' : 'text-emerald-400' }} mt-2">
                        {{ $isDowngrade ? '*Nominal yang harus dikirim ke pelanggan' : '*Nominal yang harus diterima dari pelanggan' }}
                    </p>
                </div>

                {{-- Form Eksekusi --}}
                <div class="bg-white rounded-3xl shadow-xl shadow-neutral-200/40 border border-neutral-100 overflow-hidden">
                    <div class="p-6 border-b border-neutral-100 bg-neutral-50/50">
                        <h3 class="font-black text-lg text-neutral-800">Form Eksekusi</h3>
                    </div>
                    
                    <form wire:submit.prevent="processTransaction" class="p-6 space-y-6">
                        
                        @if (session()->has('error'))
                            <div class="p-4 bg-rose-50 text-rose-700 rounded-xl text-sm font-medium border border-rose-100">
                                {{ session('error') }}
                            </div>
                        @endif
                        
                        @error('general')
                            <div class="p-4 bg-rose-50 text-rose-700 rounded-xl text-sm font-medium border border-rose-100">
                                {{ $message }}
                            </div>
                        @enderror

                        <div>
                            <label class="block text-sm font-bold text-neutral-700 mb-2">
                                Bank Asal Toko <span class="text-rose-500">*</span>
                            </label>
                            <select wire:model="selectedBankNo" required class="w-full bg-neutral-50 border border-neutral-200 text-neutral-900 text-sm rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 block p-3.5 transition-colors font-medium">
                                <option value="">-- Pilih Bank --</option>
                                <option value="10.02.103">Kas Retur</option>
                                @foreach ($banks as $account)
                                    <option value="{{ $account->account_no }}">{{ $account->account_no }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-neutral-400 mt-2 font-medium">Daftar bank di atas telah disesuaikan dengan Business Unit pesanan asli.</p>
                            @error('selectedBankNo') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                        </div>

                        {{-- Tampilkan upload file HANYA jika Refund (isDowngrade) --}}
                        @if($isDowngrade)
                            <div>
                                <label class="block text-sm font-bold text-neutral-700 mb-2">
                                    Upload Bukti Transfer <span class="text-rose-500">*</span>
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-neutral-200 border-dashed rounded-2xl hover:border-emerald-400 hover:bg-emerald-50/50 transition-colors group relative {{ $paymentReceipt ? 'bg-emerald-50 border-emerald-400' : 'bg-neutral-50' }}">
                                    <div class="space-y-2 text-center">
                                        @if($paymentReceipt)
                                            <svg class="mx-auto h-10 w-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="text-sm text-emerald-700 font-bold">File Terpilih</div>
                                            <p class="text-xs text-emerald-500 font-medium truncate max-w-[200px]">{{ $paymentReceipt->getClientOriginalName() }}</p>
                                        @else
                                            <svg class="mx-auto h-10 w-10 text-neutral-300 group-hover:text-emerald-400 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div class="flex text-sm text-neutral-600 justify-center">
                                                <label for="file-upload" class="relative cursor-pointer rounded-md font-bold text-emerald-600 hover:text-emerald-500 focus-within:outline-none">
                                                    <span>Upload file</span>
                                                    <input id="file-upload" wire:model="paymentReceipt" type="file" class="sr-only" accept="image/*">
                                                </label>
                                            </div>
                                            <p class="text-xs text-neutral-400 font-medium">PNG, JPG, GIF up to 5MB</p>
                                        @endif
                                    </div>
                                    
                                    {{-- Loading indicator --}}
                                    <div wire:loading wire:target="paymentReceipt" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                                        <div class="flex items-center gap-2 text-emerald-600 font-bold">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Mengunggah...
                                        </div>
                                    </div>
                                </div>
                                @error('paymentReceipt') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="pt-4 border-t border-neutral-100">
                            <button type="submit" wire:loading.attr="disabled"
                                class="w-full flex items-center justify-center px-6 py-3.5 border border-transparent text-sm font-black rounded-xl text-white {{ $isDowngrade ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $isDowngrade ? 'focus:ring-rose-500' : 'focus:ring-emerald-500' }} disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="processTransaction">Konfirmasi & Simpan Transaksi</span>
                                <span wire:loading wire:target="processTransaction" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Memproses ke Accurate...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
