<div class="relative min-h-screen bg-neutral-50 p-4 sm:p-8 font-sans">
    <div class="max-w-6xl mx-auto">
        {{-- Header & Back Button --}}
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('admin.finance.warranty-return') }}"
                class="w-10 h-10 bg-white border border-neutral-200 rounded-xl flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1
                    class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600 tracking-tight">
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
                            @if ($isDowngrade)
                                <span
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-50 text-rose-700 text-sm font-bold rounded-xl border border-rose-100">
                                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    Menunggu Refund (Uang Keluar)
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 text-emerald-700 text-sm font-bold rounded-xl border border-emerald-100">
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
                            <p class="text-sm font-medium text-neutral-500">
                                {{ $claim->customer->profile->phone_number ?? '-' }}</p>
                        </div>
                        <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-100">
                            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Business Unit
                            </p>
                            <p class="font-black text-neutral-800 text-lg">
                                {{ $claim->warranty->policy->businessUnit->name ?? 'Default' }}</p>
                            <p class="text-sm font-medium text-neutral-500">{{ $claim->claimedBy->branch->name }}</p>
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
                            $productName = "{$variant->product->brand} {$variant->product->name} {$variant->ram}/{$variant->storage} - {$variant->color}";
                            if (isset($variant->name)) {
                                $productName = $variant->name ?? '-';
                            }
                        }

                        // Jika ada barang pengganti (upgrade/downgrade), gunakan nama produk pengganti
                        if ($claim->resolution_type === 'replacement_different' && $claim->replacement_product_name) {
                            $productName = $claim->replacement_product_name;
                        }
                    @endphp

                    <div class="flex items-center gap-4 p-5 bg-blue-50/50 border border-blue-100 rounded-2xl mb-6">
                        <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-black text-blue-900 text-lg">
                                {{ $productName }}
                                @if ($claim->resolution_type === 'replacement_different' && $claim->replacement_item_no)
                                    <span
                                        class="text-xs font-bold text-white bg-blue-500 px-2 py-1 rounded ml-2">{{ $claim->replacement_item_no }}</span>
                                @endif
                            </p>
                            <p class="text-sm font-medium text-blue-700 mt-0.5">Tipe Resolusi:
                                {{ $claim->resolution_type === 'replacement_different' ? 'Upgrade/Downgrade' : 'Sama' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl border border-neutral-100">
                            <p class="text-xs font-bold text-neutral-400 mb-1">IMEI LAMA (Rusak)</p>
                            <p class="font-mono font-bold text-neutral-700">
                                {{ $claim->warranty->original_serial_number }}</p>
                        </div>
                        <div class="p-4 rounded-xl border border-neutral-100 bg-neutral-50">
                            <p class="text-xs font-bold text-neutral-400 mb-1">IMEI BARU (Pengganti)</p>
                            <p class="font-mono font-bold text-neutral-900">
                                {{ $claim->warranty->serial_number ?? 'Belum ditentukan' }}</p>
                        </div>
                    </div>
                </div>
                {{-- Info Pelanggan & Rekening --}}
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-xl shadow-neutral-200/40 border border-neutral-100">
                    <div class="flex items-center justify-between border-b border-neutral-100 pb-4 mb-6">
                        <h2 class="text-xl font-black text-neutral-800">Informasi Pelanggan & Pembayaran</h2>
                        <button type="button" wire:click="openEditBank"
                            class="text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-4 py-2 rounded-xl border border-emerald-200/60 transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span>Koreksi Rekening</span>
                        </button>
                    </div>

                    @php
                        $userBank = $claim->customer?->bankAccounts?->first();
                        $displayBank = $userBank?->bank_name ?? null;
                        $displayAccNo = $userBank?->account_number ?? null;
                        $displayAccName = $userBank?->account_name ?? null;
                    @endphp

                    @if ($isEditingBank)
                        {{-- Form Koreksi Rekening --}}
                        <div
                            class="p-5 bg-emerald-50/50 rounded-2xl border border-emerald-100 mb-6 animate-in fade-in duration-200">
                            <h4
                                class="text-xs font-bold uppercase tracking-wider text-emerald-800 mb-4 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Koreksi Data Rekening Tujuan Transfer
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-neutral-700 mb-1.5">Nama Bank / e-Wallet
                                        <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="editBankName"
                                        placeholder="Contoh: BCA / BRI / Mandiri"
                                        class="w-full p-3 bg-white border border-neutral-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                                    @error('editBankName')
                                        <span
                                            class="text-xs text-rose-500 font-bold block mt-1.5">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-neutral-700 mb-1.5">Nomor Rekening <span
                                            class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="editBankAccountNumber"
                                        placeholder="Nomor rekening valid"
                                        class="w-full p-3 bg-white border border-neutral-200 rounded-xl text-sm font-mono font-bold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                                    @error('editBankAccountNumber')
                                        <span
                                            class="text-xs text-rose-500 font-bold block mt-1.5">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-neutral-700 mb-1.5">Atas Nama Rekening
                                        <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="editBankAccountName"
                                        placeholder="Nama pemilik sesuai buku tabungan"
                                        class="w-full p-3 bg-white border border-neutral-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                                    @error('editBankAccountName')
                                        <span
                                            class="text-xs text-rose-500 font-bold block mt-1.5">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" wire:click="$set('isEditingBank', false)"
                                    class="px-4 py-2.5 bg-white border border-neutral-200 text-neutral-600 rounded-xl text-sm font-bold hover:bg-neutral-50 hover:text-neutral-900 transition-colors">
                                    Batal
                                </button>
                                <button type="button" wire:click="saveBankInfo"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-sm shadow-emerald-200 transition-colors">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-1">Pelanggan</p>
                            <p class="font-black text-neutral-800 text-lg">{{ $claim->customer->name ?? 'Customer' }}
                            </p>
                            <p class="text-sm font-medium text-neutral-500">{{ $claim->customer->email ?? '-' }}</p>
                            <p class="text-xs font-mono font-bold text-neutral-500 mt-0.5">
                                {{ $claim->customer->profile->phone_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-1">Tujuan
                                Transfer</p>
                            @if ($displayBank && $displayAccNo)
                                <p class="font-black text-emerald-600 text-lg flex items-center gap-1.5">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    {{ $displayBank }}
                                </p>
                                <p class="font-mono font-bold text-neutral-800 text-base mt-0.5">{{ $displayAccNo }}
                                </p>
                                <p class="text-sm font-medium text-neutral-500 mt-1">A.N: <span
                                        class="font-bold text-neutral-700">{{ $displayAccName ?: '-' }}</span></p>
                            @else
                                <p class="text-sm font-medium text-neutral-500 italic mt-2">Belum diisi pelanggan /
                                    rekening belum ada.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Form Transaksi --}}
            <div class="space-y-6">



                {{-- Summary Nominal --}}
                <div
                    class="bg-white rounded-3xl p-6 shadow-xl shadow-neutral-200/40 border {{ $isDowngrade ? 'border-rose-100' : 'border-emerald-100' }}">
                    <p class="text-sm font-bold text-neutral-500 mb-2">Total Transaksi</p>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-sm font-black {{ $isDowngrade ? 'text-rose-500' : 'text-emerald-500' }}">Rp</span>
                        <h3 class="text-4xl font-black {{ $isDowngrade ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ number_format($refundAmount, 0, ',', '.') }}
                        </h3>
                    </div>
                    <p class="text-xs font-medium {{ $isDowngrade ? 'text-rose-400' : 'text-emerald-400' }} mt-2">
                        {{ $isDowngrade ? '*Nominal yang harus dikirim ke pelanggan' : '*Nominal yang harus diterima dari pelanggan' }}
                    </p>
                </div>

                {{-- Form Eksekusi --}}
                <div
                    class="bg-white rounded-3xl shadow-xl shadow-neutral-200/40 border border-neutral-100 overflow-hidden">
                    <div class="p-6 border-b border-neutral-100 bg-neutral-50/50">
                        <h3 class="font-black text-lg text-neutral-800">Form Eksekusi</h3>
                    </div>

                    <form wire:submit.prevent="processTransaction" class="p-6 space-y-6">

                        @if (session()->has('error'))
                            <div
                                class="p-4 bg-rose-50 text-rose-700 rounded-xl text-sm font-medium border border-rose-100">
                                {{ session('error') }}
                            </div>
                        @endif

                        @error('general')
                            <div
                                class="p-4 bg-rose-50 text-rose-700 rounded-xl text-sm font-medium border border-rose-100">
                                {{ $message }}
                            </div>
                        @enderror

                        <div>
                            <label class="block text-sm font-bold text-neutral-700 mb-2">
                                Bank Asal Toko <span class="text-rose-500">*</span>
                            </label>
                            <div x-data="{
                                open: false,
                                search: '',
                                value: @entangle('selectedBankNo'),
                                options: [
                                    { id: '10.02.103', label: '10.02.103 - Kas Retur' },
                                    @foreach ($banks as $account)
                                        { id: '{{ $account->account_no }}', label: '{{ $account->account_no }} - {{ addslashes($account->name) }}' }, @endforeach
                                ],
                                get filteredOptions() {
                                    if (this.search === '') return this.options;
                                    return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                get selectedLabel() {
                                    const selected = this.options.find(i => i.id == this.value);
                                    return selected ? selected.label : '-- Pilih Bank --';
                                }
                            }" class="relative w-full" @click.away="open = false">
                                <!-- Trigger -->
                                <button type="button"
                                    @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                    class="flex items-center justify-between w-full p-3.5 text-sm font-mono text-left bg-neutral-50 border rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                                    :class="open ? 'border-emerald-500 ring-2 ring-emerald-100' :
                                        'border-neutral-200 hover:border-neutral-300'">
                                    <span x-text="selectedLabel"
                                        :class="!value ? 'text-neutral-500' : 'text-neutral-900 font-bold'"
                                        class="truncate pr-4"></span>
                                    <svg class="w-4 h-4 text-neutral-400 transition-transform shrink-0"
                                        :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Dropdown -->
                                <div x-show="open" x-transition.opacity
                                    class="absolute z-50 w-full mt-1.5 bg-white border border-neutral-200 rounded-xl shadow-xl"
                                    style="display: none;">
                                    <div class="p-2 border-b border-neutral-100 bg-neutral-50/50 rounded-t-xl">
                                        <div class="relative">
                                            <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-neutral-400"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                            <input type="text" x-model="search"
                                                placeholder="Cari nama atau nomor akun..."
                                                class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-neutral-200 rounded-md focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                                @keydown.escape="open = false" x-ref="searchInput">
                                        </div>
                                    </div>
                                    <ul class="max-h-56 overflow-y-auto py-1">
                                        <li @click="value = ''; open = false; search = ''"
                                            class="px-4 py-2.5 text-sm text-neutral-500 cursor-pointer hover:bg-neutral-50 transition-colors">
                                            -- Pilih Bank --
                                        </li>
                                        <template x-for="option in filteredOptions" :key="option.id">
                                            <li @click="value = option.id; open = false; search = ''"
                                                class="px-4 py-2.5 text-sm font-mono cursor-pointer hover:bg-emerald-50 transition-colors border-l-2 border-transparent"
                                                :class="value == option.id ?
                                                    'bg-emerald-50 text-emerald-700 font-bold border-emerald-500' :
                                                    'text-neutral-700 hover:border-emerald-200'">
                                                <span x-text="option.label"></span>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0"
                                            class="px-4 py-3 text-sm text-neutral-400 text-center italic bg-neutral-50/50">
                                            Akun tidak ditemukan
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <p class="text-[11px] text-neutral-400 mt-2 font-medium">Daftar bank di atas telah
                                disesuaikan dengan Business Unit pesanan asli.</p>
                            @error('selectedBankNo')
                                <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Tampilkan upload file HANYA jika Refund (isDowngrade) --}}
                        @if ($isDowngrade)
                            <div>
                                <label class="block text-sm font-bold text-neutral-700 mb-2">
                                    Upload Bukti Transfer <span class="text-rose-500">*</span>
                                </label>
                                <div
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-neutral-200 border-dashed rounded-2xl hover:border-emerald-400 hover:bg-emerald-50/50 transition-colors group relative {{ $paymentReceipt ? 'bg-emerald-50 border-emerald-400' : 'bg-neutral-50' }}">
                                    <div class="space-y-2 text-center">
                                        @if ($paymentReceipt)
                                            <svg class="mx-auto h-10 w-10 text-emerald-500" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="text-sm text-emerald-700 font-bold">File Terpilih</div>
                                            <p class="text-xs text-emerald-500 font-medium truncate max-w-[200px]">
                                                {{ $paymentReceipt->getClientOriginalName() }}</p>
                                        @else
                                            <svg class="mx-auto h-10 w-10 text-neutral-300 group-hover:text-emerald-400 transition-colors"
                                                stroke="currentColor" fill="none" viewBox="0 0 48 48"
                                                aria-hidden="true">
                                                <path
                                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                    stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                            <div class="flex text-sm text-neutral-600 justify-center">
                                                <label for="file-upload"
                                                    class="relative cursor-pointer rounded-md font-bold text-emerald-600 hover:text-emerald-500 focus-within:outline-none">
                                                    <span>Upload file</span>
                                                    <input id="file-upload" wire:model="paymentReceipt"
                                                        type="file" class="sr-only" accept="image/*">
                                                </label>
                                            </div>
                                            <p class="text-xs text-neutral-400 font-medium">PNG, JPG, GIF up to 5MB
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Loading indicator --}}
                                    <div wire:loading wire:target="paymentReceipt"
                                        class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                                        <div class="flex items-center gap-2 text-emerald-600 font-bold">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            Mengunggah...
                                        </div>
                                    </div>
                                </div>
                                @error('paymentReceipt')
                                    <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        <div class="pt-4 border-t border-neutral-100">
                            <button type="submit" wire:loading.attr="disabled"
                                class="w-full flex items-center justify-center px-6 py-3.5 border border-transparent text-sm font-black rounded-xl text-white {{ $isDowngrade ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $isDowngrade ? 'focus:ring-rose-500' : 'focus:ring-emerald-500' }} disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="processTransaction">Konfirmasi & Simpan
                                    Transaksi</span>
                                <span wire:loading wire:target="processTransaction" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
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
