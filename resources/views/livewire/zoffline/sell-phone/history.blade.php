<div class="max-w-7xl mx-auto p-3 md:p-6 min-h-screen space-y-6">
    {{-- Header Navigation & Title --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('zoffline') }}" wire:navigate
                class="bg-neutral-600 text-white p-3 rounded-xl hover:bg-neutral-700 transition shadow-sm flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                    class="size-6 rotate-180">
                    <path fill-rule="evenodd"
                        d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                        clip-rule="evenodd" />
                </svg>
            </a>
            <div class="flex-1 bg-linear-to-r from-[#0097FF] via-[#4E44DB] to-[#013559] py-3.5 px-6 rounded-2xl shadow-sm text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/10 backdrop-blur-xs rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-7">
                            <path d="M2.25 2.25a.75.75 0 0 0 0 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 0 0-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 0 0 0-1.5H5.378A2.25 2.25 0 0 1 7.5 15h11.218a.75.75 0 0 0 .674-.421 60.358 60.358 0 0 0 2.96-7.228.75.75 0 0 0-.525-.965A60.864 60.864 0 0 0 5.68 4.509l-.232-.867A1.875 1.875 0 0 0 3.636 2.25H2.25ZM3.75 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM16.5 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-black tracking-tight leading-none">Riwayat Jual HP</h1>
                        <p class="text-xs text-blue-100 mt-1 font-medium">Monitoring transaksi pengajuan jual HP, pencairan dana, dan cetak tanda terima.</p>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('zoffline.sell-phone') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 px-5 py-3.5 bg-[#4E44DB] hover:bg-[#3f36b8] text-white font-bold text-sm rounded-2xl shadow-sm hover:shadow-md transition shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Jual HP Baru
        </a>
    </div>

    {{-- Summary Statistics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        {{-- Card 1: Total Transaksi --}}
        <div wire:click="setPaymentFilter('')"
            class="bg-white rounded-2xl p-4 md:p-5 border border-gray-100 shadow-xs hover:shadow-md hover:border-gray-200 transition cursor-pointer {{ $filterPayment === '' ? 'ring-2 ring-[#4E44DB]' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Transaksi</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl md:text-3xl font-black text-gray-900 mt-2">{{ number_format($summary['total_count'] ?? 0) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-0.5">Semua data transaksi</p>
        </div>

        {{-- Card 2: Sudah Bayar (Lunas) --}}
        <div wire:click="setPaymentFilter('PAID')"
            class="bg-white rounded-2xl p-4 md:p-5 border border-emerald-100 shadow-xs hover:shadow-md hover:border-emerald-200 transition cursor-pointer {{ $filterPayment === 'PAID' ? 'ring-2 ring-emerald-500' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Sudah Bayar / Lunas</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl md:text-3xl font-black text-emerald-600 mt-2">{{ number_format($summary['completed_count'] ?? 0) }}</p>
            <p class="text-[11px] text-emerald-700 font-bold mt-0.5 truncate">Rp {{ number_format($summary['completed_total'] ?? 0, 0, ',', '.') }}</p>
        </div>

        {{-- Card 3: Menunggu Pembayaran --}}
        <div wire:click="setPaymentFilter('PAYING')"
            class="bg-white rounded-2xl p-4 md:p-5 border border-amber-100 shadow-xs hover:shadow-md hover:border-amber-200 transition cursor-pointer {{ $filterPayment === 'PAYING' ? 'ring-2 ring-amber-500' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Menunggu Bayar</span>
                <span class="p-2 bg-amber-50 text-amber-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl md:text-3xl font-black text-amber-600 mt-2">{{ number_format($summary['paying_count'] ?? 0) }}</p>
            <p class="text-[11px] text-amber-700 font-bold mt-0.5 truncate">Rp {{ number_format($summary['paying_total'] ?? 0, 0, ',', '.') }}</p>
        </div>

        {{-- Card 4: Dalam Proses --}}
        <div wire:click="setPaymentFilter('IN_PROGRESS')"
            class="bg-white rounded-2xl p-4 md:p-5 border border-purple-100 shadow-xs hover:shadow-md hover:border-purple-200 transition cursor-pointer {{ $filterPayment === 'IN_PROGRESS' ? 'ring-2 ring-purple-500' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-purple-700 uppercase tracking-wider">Dalam Proses</span>
                <span class="p-2 bg-purple-50 text-purple-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl md:text-3xl font-black text-purple-600 mt-2">{{ number_format($summary['in_progress_count'] ?? 0) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-0.5">Inspeksi & Penawaran</p>
        </div>
    </div>

    {{-- Filter & Search Section --}}
    <div class="bg-white rounded-3xl p-5 md:p-6 shadow-xs border border-gray-100 space-y-4">
        {{-- Search & Payment Filter Controls --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-4 items-center">
            {{-- Search Bar --}}
            <div class="md:col-span-5 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari Customer, Kasir, No. Transaksi, Model HP, IMEI..."
                    class="w-full pl-10 pr-10 py-3 bg-gray-50/80 border border-gray-200 rounded-2xl text-sm font-medium text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#4E44DB] focus:border-transparent transition-all placeholder-gray-400">
                @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Filter Status Bayar --}}
            <div class="md:col-span-3">
                <select wire:model.live="filterPayment"
                    class="w-full py-3 px-3.5 bg-gray-50/80 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#4E44DB] focus:border-transparent transition-all">
                    <option value="">Semua Status Pembayaran</option>
                    <option value="PAID">Sudah Bayar / Lunas (COMPLETED)</option>
                    <option value="PAYING">Menunggu Pembayaran (PAYING)</option>
                    <option value="IN_PROGRESS">Dalam Proses (Inspeksi/Taksiran)</option>
                    <option value="CANCELLED">Dibatalkan / Ditolak</option>
                    <option value="PENDING">Menunggu Taksiran</option>
                    <option value="OFFERED">Penawaran Tersedia</option>
                    <option value="INSPECTING">Inspeksi Fisik</option>
                </select>
            </div>

            {{-- Filter Cabang (Jika Admin) --}}
            @if($isAdmin)
                <div class="md:col-span-2">
                    <select wire:model.live="filterBranchId"
                        class="w-full py-3 px-3.5 bg-gray-50/80 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#4E44DB] focus:border-transparent transition-all">
                        <option value="">Semua Cabang</option>
                        @foreach($availableBranches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Tombol Reset Filter --}}
            <div class="{{ $isAdmin ? 'md:col-span-2' : 'md:col-span-4' }} flex justify-end">
                @if($search || $filterPayment || $filterStartDate || $filterEndDate || $filterBranchId)
                    <button wire:click="clearFilters"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-bold transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset Filter
                    </button>
                @endif
            </div>
        </div>

        {{-- Date Range Filters --}}
        <div class="pt-3 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-center">
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Mulai</label>
                <input type="date" wire:model.live="filterStartDate"
                    class="w-full bg-gray-50/80 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#4E44DB]">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Selesai</label>
                <input type="date" wire:model.live="filterEndDate"
                    class="w-full bg-gray-50/80 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#4E44DB]">
            </div>
            <div class="sm:col-span-2 flex items-end">
                <span class="text-xs text-gray-500">
                    Menampilkan <strong>{{ $sells->total() }}</strong> transaksi ditemukan.
                </span>
            </div>
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading class="w-full pt-2">
            <div class="flex items-center justify-center gap-2 text-[#4E44DB] text-xs font-bold bg-blue-50/60 rounded-xl py-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memuat data transaksi...
            </div>
        </div>
    </div>

    {{-- Transaction List Section --}}
    <div class="space-y-4">
        @php
            $statusColors = [
                'PENDING' => 'bg-amber-100 text-amber-800 border-amber-200',
                'OFFERED' => 'bg-blue-100 text-blue-800 border-blue-200',
                'WAITING_FOR_DEVICE' => 'bg-purple-100 text-purple-800 border-purple-200',
                'INSPECTING' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'PAYING' => 'bg-teal-100 text-teal-800 border-teal-200',
                'COMPLETED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'CANCELLED' => 'bg-rose-100 text-rose-800 border-rose-200',
                'REJECTED' => 'bg-rose-100 text-rose-800 border-rose-200',
            ];
            $statusLabels = [
                'PENDING' => 'Menunggu Taksiran',
                'OFFERED' => 'Penawaran Tersedia',
                'WAITING_FOR_DEVICE' => 'Menunggu Unit HP',
                'INSPECTING' => 'Sedang Diinspeksi',
                'PAYING' => 'Menunggu Pembayaran',
                'COMPLETED' => 'Lunas / Selesai',
                'CANCELLED' => 'Dibatalkan',
                'REJECTED' => 'Ditolak',
            ];
        @endphp

        @forelse($sells as $item)
            <div class="bg-white rounded-3xl p-5 md:p-6 shadow-xs border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all duration-200">
                {{-- Top Card Header: Transaction Number, Date, and Status Badge --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-100">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-900 text-white rounded-xl text-xs font-black tracking-wide">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            SPL-{{ $item->id }}
                        </div>

                        @if($item->invoice_number)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-xl text-[11px] font-bold font-mono">
                                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Inv: {{ $item->invoice_number }}
                            </span>
                        @endif

                        <span class="text-xs text-gray-400 font-medium ml-1">
                            {{ $item->created_at->format('d M Y, H:i') }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($item->payment_receipt_path)
                            <button type="button" wire:click="previewProof('{{ asset('storage/' . $item->payment_receipt_path) }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold hover:bg-emerald-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Ada Bukti Bayar
                            </button>
                        @endif

                        <span class="px-3 py-1 text-xs font-bold rounded-xl border {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                            {{ $statusLabels[$item->status] ?? $item->status }}
                        </span>
                    </div>
                </div>

                {{-- Middle Content: Device Details, Customer Info, Cashier Info, and Price --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 py-4 items-center">
                    {{-- Device Info (Col 5) --}}
                    <div class="md:col-span-5 flex items-start gap-4">
                        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center p-2 border border-gray-100 shrink-0 overflow-hidden">
                            @if($item->getFirstMediaUrl('photos', 'thumb'))
                                <img src="{{ $item->getFirstMediaUrl('photos', 'thumb') }}"
                                    class="object-contain max-h-full max-w-full" alt="{{ $item->phone_model }}">
                            @else
                                <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $item->phone_brand }}</span>
                            <h3 class="font-extrabold text-gray-900 text-base leading-snug">{{ $item->phone_model }}</h3>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 font-medium">
                                @if($item->phone_ram || $item->phone_storage)
                                    <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-md font-semibold text-[11px]">
                                        {{ $item->phone_ram ?? '-' }} / {{ $item->phone_storage ?? '-' }}
                                    </span>
                                @endif
                                @if($item->imei)
                                    <span class="text-[11px] text-gray-400 font-mono">IMEI: {{ $item->imei }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Customer & Kasir Info (Col 4) --}}
                    <div class="md:col-span-4 space-y-2 text-xs border-t md:border-t-0 md:border-l border-gray-100 pt-3 md:pt-0 md:pl-5">
                        {{-- Customer --}}
                        <div class="flex items-center gap-2">
                            <span class="text-gray-400 font-medium w-16 shrink-0">Customer:</span>
                            <div class="font-bold text-gray-900 truncate">
                                {{ $item->user->profile->full_name ?? $item->user->name ?? 'Customer Umum' }}
                                @if(optional(optional($item->user)->profile)->phone_number)
                                    <span class="text-gray-400 font-normal text-[11px]">({{ $item->user->profile->phone_number }})</span>
                                @endif
                            </div>
                        </div>

                        {{-- Kasir / FL --}}
                        <div class="flex items-center gap-2">
                            <span class="text-gray-400 font-medium w-16 shrink-0">Kasir/FL:</span>
                            <span class="font-semibold text-gray-700 truncate">
                                {{ optional($item->handledBy)->name ?? '-' }}
                                @if($item->branch)
                                    <span class="text-gray-400 font-normal text-[11px]">({{ $item->branch->name }})</span>
                                @endif
                            </span>
                        </div>

                        {{-- Sales / Tenaga Penjual --}}
                        @if($item->salesBy)
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 font-medium w-16 shrink-0">Sales:</span>
                                <span class="font-semibold text-indigo-600 truncate">
                                    {{ $item->salesBy->name }}
                                    @if($item->salesBy->employee_no)
                                        <span class="text-gray-400 font-normal font-mono text-[10px]">({{ $item->salesBy->employee_no }})</span>
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- Bank Info --}}
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="text-gray-400 font-medium w-16 shrink-0">Rek. Tujuan:</span>
                            <span class="text-gray-600 truncate font-mono">
                                @php
                                    $bankName = $item->bank_name ?: ($item->user->bankAccounts->first()->bank_name ?? null);
                                    $bankAccNo = $item->bank_account_number ?: ($item->user->bankAccounts->first()->account_number ?? null);
                                @endphp
                                @if($bankName && $bankAccNo)
                                    {{ $bankName }} - {{ $bankAccNo }}
                                @else
                                    <span class="italic text-gray-400">Belum diinput</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Appraised Value (Price) (Col 3) --}}
                    <div class="md:col-span-3 flex flex-col md:items-end justify-center border-t md:border-t-0 md:border-l border-gray-100 pt-3 md:pt-0 md:pl-5">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nilai Taksiran / Beli</span>
                        @if($item->appraised_value)
                            <p class="text-lg md:text-xl font-black text-emerald-600 tabular-nums mt-0.5">
                                Rp {{ number_format($item->appraised_value, 0, ',', '.') }}
                            </p>
                            @if($item->is_price_adjusted)
                                <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md mt-1 inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Nominal Disesuaikan
                                </span>
                            @endif
                        @else
                            <p class="text-xs text-gray-400 italic mt-0.5">Belum ada taksiran</p>
                        @endif
                    </div>
                </div>

                {{-- Bottom Actions Bar --}}
                <div class="pt-4 border-t border-gray-100 flex flex-wrap items-center justify-end gap-2">
                    {{-- Tombol Cetak Struk Jaminan --}}
                    @if(in_array($item->status, ['PAYING', 'COMPLETED']))
                        <button type="button" wire:click="showReceipt({{ $item->id }})"
                            class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Struk Jaminan
                        </button>
                        <button type="button" wire:click="printReceipt({{ $item->id }})" wire:loading.attr="disabled"
                            class="px-3 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Cetak Cepat ke Printer Thermal (ESC/POS)">
                            <svg wire:loading.remove wire:target="printReceipt({{ $item->id }})" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <svg wire:loading wire:target="printReceipt({{ $item->id }})" class="animate-spin w-4 h-4 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Print Thermal
                        </button>
                    @endif

                    {{-- Tombol Lihat Detail --}}
                    <a href="{{ route('sell-phone.show', $item) }}" wire:navigate
                        class="px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs">
                        Lihat Detail
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl p-12 shadow-xs border border-gray-100 text-center flex flex-col items-center justify-center">
                <div class="w-20 h-20 bg-gray-50 rounded-3xl flex items-center justify-center mb-4 border border-gray-100">
                    <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="font-extrabold text-gray-900 text-lg">Tidak ada transaksi ditemukan</h3>
                <p class="text-gray-500 mt-1 text-xs md:text-sm max-w-sm">
                    @if($search || $filterPayment || $filterStartDate || $filterEndDate)
                        Tidak ada transaksi yang cocok dengan kata kunci atau filter yang Anda pilih. Silakan sesuaikan pencarian.
                    @else
                        Belum ada transaksi jual HP di cabang ini.
                    @endif
                </p>
                @if($search || $filterPayment || $filterStartDate || $filterEndDate)
                    <button wire:click="clearFilters"
                        class="mt-4 px-5 py-2.5 bg-gray-900 text-white rounded-xl font-bold text-xs hover:bg-gray-800 transition">
                        Bersihkan Filter
                    </button>
                @else
                    <a href="{{ route('zoffline.sell-phone') }}" wire:navigate
                        class="mt-4 inline-flex items-center gap-2 bg-[#4E44DB] text-white px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-[#3f36b8] transition">
                        Mulai Jual HP
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination Controls --}}
    @if($sells->hasPages())
        <div class="pt-4">
            {{ $sells->links() }}
        </div>
    @endif

    {{-- Thermal Receipt Modal --}}
    @if ($showReceiptModal && $selectedSell)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden relative border border-gray-100 animate-scale-up">
                <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-gray-900 text-sm">Struk Tanda Terima</h3>
                        <p class="text-[10px] text-gray-400">SPL-{{ $selectedSell->id }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- WhatsApp Button --}}
                        <button wire:click="sendReceiptToQontak" wire:loading.attr="disabled"
                            class="group relative @if(Auth::user()->hasRole('admin') || !$selectedSell->is_wa_sent) text-emerald-600 hover:text-emerald-700 @else text-gray-300 cursor-not-allowed @endif font-bold text-sm flex items-center gap-1 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Kirim Struk WhatsApp">
                            <svg wire:loading.remove wire:target="sendReceiptToQontak" class="w-5 h-5 @if(!Auth::user()->hasRole('admin') && $selectedSell->is_wa_sent) opacity-40 @endif" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397 0 11.983 0c3.192.001 6.192 1.242 8.447 3.498c2.256 2.255 3.497 5.255 3.497 8.447c-.004 6.585-5.342 11.93-11.93 11.93c-2.002-.001-3.973-.503-5.729-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451c5.436 0 9.86-4.42 9.864-9.858c.002-2.634-1.023-5.11-2.887-6.974c-1.864-1.864-4.341-2.887-6.973-2.889c-5.44 0-9.865 4.42-9.869 9.859c-.001 1.706.469 3.372 1.36 4.866l-.993 3.626l3.71-.973zm11.233-6.17c-.3-.149-1.774-.875-2.046-.974c-.272-.1-.471-.149-.669.149c-.198.299-.768.974-.941 1.173c-.173.199-.347.224-.647.075c-.3-.15-1.266-.466-2.41-1.487c-.89-.794-1.49-1.774-1.664-2.073c-.173-.3-.018-.462.13-.61c.134-.133.298-.348.446-.521c.15-.173.199-.298.298-.497c.099-.198.05-.372-.025-.521c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.501-.669-.51l-.57-.01c-.199 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487c.709.306 1.263.489 1.694.626c.712.226 1.36.194 1.872.118c.571-.085 1.774-.726 2.022-1.392c.247-.667.247-1.241.173-1.392c-.074-.15-.272-.249-.571-.398z" />
                            </svg>
                            <svg wire:loading wire:target="sendReceiptToQontak" class="animate-spin w-5 h-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>

                        {{-- Email Button --}}
                        <button wire:click="sendReceiptToEmail" wire:loading.attr="disabled"
                            class="group relative @if(Auth::user()->hasRole('admin') || !$selectedSell->is_email_sent) text-blue-600 hover:text-blue-700 @else text-gray-300 cursor-not-allowed @endif font-bold text-sm flex items-center gap-1 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Kirim Struk Email">
                            <svg wire:loading.remove wire:target="sendReceiptToEmail" class="w-5 h-5 @if(!Auth::user()->hasRole('admin') && $selectedSell->is_email_sent) opacity-40 @endif" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <svg wire:loading wire:target="sendReceiptToEmail" class="animate-spin w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>

                        {{-- Thermal Print Button --}}
                        <button wire:click="printReceipt" wire:loading.attr="disabled"
                            class="text-blue-600 hover:text-blue-700 font-bold transition disabled:opacity-50 disabled:cursor-not-allowed" title="Print Thermal Printer (ESC/POS)">
                            <svg wire:loading.remove wire:target="printReceipt" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <svg wire:loading wire:target="printReceipt" class="animate-spin w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>

                        {{-- Browser Print Button --}}
                        <button onclick="window.print()"
                            class="text-gray-400 hover:text-gray-600 font-bold transition" title="Print Browser / PDF">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </button>

                        {{-- Close Button --}}
                        <button wire:click="closeReceipt" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Thermal Content Preview --}}
                <div id="receipt-content" class="p-5 font-mono text-xs leading-relaxed overflow-y-auto max-h-[70vh]">
                    <div class="text-center mb-3">
                        <p class="font-bold text-sm">{{ optional($selectedSell->businessUnit)->store_title ?? 'Z-POS STORE' }}</p>
                        <p class="text-[10px] text-gray-500">{{ optional($selectedSell->businessUnit)->address ?? 'Toko' }}</p>
                        <p class="text-[10px] text-gray-400">{{ $selectedSell->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="border-t border-dashed border-gray-300 my-2"></div>

                    <p class="text-[10px] text-gray-500">No Transaksi : SPL-{{ $selectedSell->id }}</p>
                    @if($selectedSell->invoice_number)
                        <p class="text-[10px] text-gray-500">No Invoice   : {{ $selectedSell->invoice_number }}</p>
                    @endif
                    <p class="text-[10px] text-gray-500">Frontliner   : {{ optional($selectedSell->handledBy)->name ?? '-' }}</p>
                    <p class="text-[10px] text-gray-500">Pelanggan    : {{ optional($selectedSell->user)->name ?? '-' }}</p>
                    <p class="text-[10px] text-gray-500">No. HP       : {{ optional(optional($selectedSell->user)->profile)->phone_number ?? '-' }}</p>

                    <div class="border-t border-dashed border-gray-300 my-2"></div>

                    <div class="text-center font-bold mb-2">DATA PERANGKAT</div>

                    <p class="text-[10px] text-gray-500">Merek/Model: {{ $selectedSell->phone_brand }} {{ $selectedSell->phone_model }}</p>
                    <p class="text-[10px] text-gray-500">Kapasitas  : {{ $selectedSell->phone_ram ?? '-' }} / {{ $selectedSell->phone_storage ?? '-' }}</p>
                    <p class="text-[10px] text-gray-500">IMEI/SN    : {{ $selectedSell->imei ?? '-' }}</p>

                    <div class="border-t border-dashed border-gray-300 my-2"></div>

                    <div class="flex justify-between font-bold text-xs">
                        <span>NILAI KESEPAKATAN</span>
                        <span>Rp {{ number_format($selectedSell->appraised_value, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-[10px] mt-1">
                        <span>STATUS TRANSAKSI</span>
                        <span class="uppercase font-bold text-emerald-600">{{ str_replace('_', ' ', $selectedSell->status) }}</span>
                    </div>

                    <div class="border-t border-dashed border-gray-300 my-3"></div>

                    <div class="text-center text-[9px] space-y-1 text-gray-500">
                        <p class="font-bold text-[10px] text-gray-700 mb-1">** JAMINAN PENYERAHAN UNIT **</p>
                        <p>Struk ini adalah bukti sah penyerahan perangkat ke toko.</p>
                        <p>Pembayaran akan ditransfer ke rekening:</p>
                        @php
                            $userBank = $selectedSell->bank_name 
                                ? (object)['bank_name' => $selectedSell->bank_name, 'account_number' => $selectedSell->bank_account_number, 'account_name' => $selectedSell->bank_account_name]
                                : ($selectedSell->user && $selectedSell->user->bankAccounts->first() ? $selectedSell->user->bankAccounts->first() : null);
                        @endphp
                        @if ($userBank)
                            <p class="font-bold text-gray-700 mt-1">{{ $userBank->bank_name }} - {{ $userBank->account_number }}</p>
                            <p class="font-bold text-gray-700">A/N: {{ $userBank->account_name }}</p>
                        @else
                            <p class="font-bold text-gray-700 mt-1">Rekening Belum Diinput</p>
                        @endif
                        <p class="mt-2">Simpan struk ini sampai dana berhasil masuk.</p>
                        <p class="mt-1">Terima kasih telah menjual HP Anda di {{ optional($selectedSell->businessUnit)->store_title ?? 'Z-POS STORE' }}.</p>
                    </div>

                    <div class="border-t border-dashed border-gray-300 my-3"></div>
                    <div class="text-center text-[10px] text-gray-400">*** TANDA TERIMA ***</div>
                </div>

                <style>
                    @media print {
                        body * {
                            visibility: hidden;
                        }
                        #receipt-content, #receipt-content * {
                            visibility: visible;
                        }
                        #receipt-content {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 80mm;
                            padding: 0;
                            margin: 0;
                            color: black;
                        }
                    }
                </style>
            </div>
        </div>
    @endif

    {{-- Payment Proof Modal --}}
    @if ($showProofModal && $proofImageUrl)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden relative border border-gray-100">
                <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-sm">Bukti Pembayaran / Transfer</h3>
                    <button wire:click="closeProofModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-4 flex flex-col items-center">
                    <img src="{{ $proofImageUrl }}" alt="Bukti Transfer" class="max-h-[70vh] rounded-2xl object-contain shadow-sm border border-gray-100">
                    <a href="{{ $proofImageUrl }}" target="_blank" download
                        class="mt-3 px-4 py-2 bg-gray-900 text-white rounded-xl text-xs font-bold hover:bg-gray-800 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Gambar
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
