<div class="relative min-h-screen p-4 sm:p-8">
    {{-- Header & Actions --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.sell-phones.index') }}" wire:navigate
                    class="text-gray-400 hover:text-gray-700 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-extrabold text-gray-900">Kendala & Kesalahan Pembelian HP</h1>
            </div>
            <p class="text-gray-500 text-sm mt-1 ml-7">Pantau, tindak lanjuti, dan rekap seluruh catatan kendala transaksi penjualan HP bekas (#SPL).</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.sell-phones.index') }}" wire:navigate
                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Kelola Penjualan HP
            </a>

            <button wire:click="exportExcel" wire:loading.attr="disabled"
                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition text-xs font-bold flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span>Export Excel (.xlsx)</span>
            </button>
        </div>
    </div>

    {{-- 4 Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Issues --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Total Kendala</p>
                <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">{{ number_format($totalIssues, 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Open Issues --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 relative">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                @if ($openIssues > 0)
                    <span class="absolute top-2 right-2 w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Perlu Diselesaikan (OPEN)</p>
                <h3 class="text-xl font-extrabold text-rose-600 mt-0.5">{{ number_format($openIssues, 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Resolved Issues --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Terselesaikan (RESOLVED)</p>
                <h3 class="text-xl font-extrabold text-emerald-600 mt-0.5">{{ number_format($resolvedIssues, 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Top Issue Category --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs text-gray-500 font-semibold truncate">Kategori Terbanyak</p>
                <h3 class="text-sm font-extrabold text-gray-900 mt-0.5 truncate" title="{{ $topCategory }}">
                    {{ $topCategory }}
                </h3>
                <p class="text-[11px] text-purple-600 font-bold mt-0.5">{{ $topCategoryCount }} kendala</p>
            </div>
        </div>
    </div>

    {{-- Main Card: Filters & Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- Filters Section --}}
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                {{-- Search Bar --}}
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari #SPL, nama, hp, norek, komentar..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#1c69d4] focus:border-transparent outline-none transition placeholder-gray-400">
                </div>

                {{-- Filter Status --}}
                <div>
                    <select wire:model.live="statusFilter"
                        class="w-full py-2.5 px-3 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#1c69d4] focus:border-transparent outline-none transition font-medium">
                        <option value="">Semua Status Kendala</option>
                        <option value="OPEN">🔴 Belum Selesai (OPEN)</option>
                        <option value="RESOLVED">🟢 Terselesaikan (RESOLVED)</option>
                    </select>
                </div>

                {{-- Filter Kategori --}}
                <div>
                    <select wire:model.live="categoryFilter"
                        class="w-full py-2.5 px-3 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#1c69d4] focus:border-transparent outline-none transition font-medium">
                        <option value="">Semua Kategori</option>
                        @foreach ($categoryLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Reset Filter Button --}}
                <div class="flex items-center gap-2">
                    <button wire:click="resetFilter"
                        class="w-full py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            {{-- Date Range Filter --}}
            <div class="mt-3 flex flex-wrap items-center gap-3 pt-3 border-t border-gray-200/60 text-xs">
                <span class="text-gray-500 font-semibold">Rentang Tanggal Lapor:</span>
                <div class="flex items-center gap-2">
                    <input type="date" wire:model.live="dateFrom"
                        class="py-1.5 px-3 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:ring-2 focus:ring-[#1c69d4]">
                    <span class="text-gray-400">s/d</span>
                    <input type="date" wire:model.live="dateTo"
                        class="py-1.5 px-3 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:ring-2 focus:ring-[#1c69d4]">
                </div>
                @if ($dateFrom || $dateTo || $search || $statusFilter || $categoryFilter)
                    <span class="text-[11px] text-blue-600 font-medium">Filter aktif</span>
                @endif
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <th class="px-5 py-3.5">No. Transaksi / Waktu</th>
                        <th class="px-5 py-3.5">Pelanggan & HP</th>
                        <th class="px-5 py-3.5">Info Rekening Transfer</th>
                        <th class="px-5 py-3.5">Taksiran & Status SPL</th>
                        <th class="px-5 py-3.5">Kategori & Rincian Kendala</th>
                        <th class="px-5 py-3.5 text-center">Status Kendala</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse ($issues as $issue)
                        @php
                            $isResolved = $issue->status === 'RESOLVED';
                            $sellPhone = $issue->sellPhone;
                            $userBank = $sellPhone?->user?->bankAccounts?->first();
                            $bankName = $sellPhone?->bank_name ?: ($userBank?->bank_name ?? '-');
                            $bankAccNo = $sellPhone?->bank_account_number ?: ($userBank?->account_number ?? '-');
                            $bankAccName = $sellPhone?->bank_account_name ?: ($userBank?->account_name ?? '-');

                            $badgeStyles = [
                                'SALAH_NOREK' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'SALAH_NOMINAL' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'SALAH_QC' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'SALAH_IMEI' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                'GAGAL_TRANSFER' => 'bg-red-50 text-red-700 border-red-200',
                                'LAINNYA' => 'bg-gray-50 text-gray-700 border-gray-200',
                            ];
                        @endphp
                        <tr class="hover:bg-blue-50/30 transition-colors {{ $isResolved ? 'bg-gray-50/40 opacity-75' : '' }}">
                            {{-- Transaksi & Waktu --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($sellPhone)
                                    <a href="{{ route('admin.sell-phones.show', $sellPhone) }}" wire:navigate
                                        class="font-black text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1.5">
                                        #SPL-{{ $sellPhone->id }}
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="font-bold text-gray-400">SPL Terhapus</span>
                                @endif
                                <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $issue->created_at->format('d/m/Y H:i') }}
                                </div>
                                <div class="text-[10px] text-gray-400">({{ $issue->created_at->diffForHumans() }})</div>
                            </td>

                            {{-- Pelanggan & HP --}}
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-900">
                                    {{ $sellPhone && $sellPhone->user ? $sellPhone->user->name : 'Customer Terhapus' }}
                                </div>
                                <div class="text-[11px] text-gray-500 font-mono">
                                    {{ $sellPhone && $sellPhone->user && $sellPhone->user->profile ? ($sellPhone->user->profile->phone_number ?? '-') : '-' }}
                                </div>
                                @if ($sellPhone)
                                    <div class="mt-1 font-semibold text-indigo-600 text-[11px]">
                                        {{ $sellPhone->phone_brand }} {{ $sellPhone->phone_model }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ $sellPhone->businessUnit?->name ?? 'Toko' }} • FL: {{ $sellPhone->handledBy?->name ?? '-' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Info Rekening Transfer --}}
                            <td class="px-5 py-4">
                                <div class="font-bold text-emerald-700 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    {{ $bankName }}
                                </div>
                                <div class="font-mono text-gray-900 font-bold mt-0.5">
                                    {{ $bankAccNo }}
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    A.N: <span class="font-medium text-gray-700">{{ $bankAccName }}</span>
                                </div>
                            </td>

                            {{-- Taksiran & Status SPL --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-extrabold text-gray-900">
                                    Rp {{ number_format($sellPhone?->appraised_value ?? 0, 0, ',', '.') }}
                                </div>
                                @if ($sellPhone)
                                    <div class="mt-1">
                                        @php
                                            $splStatusColors = [
                                                'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'OFFERED' => 'bg-sky-50 text-sky-700 border-sky-200',
                                                'WAITING_FOR_DEVICE' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                'INSPECTING' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                'PENDING_APPROVAL' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                                'PAYING' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'CANCELLED' => 'bg-gray-50 text-gray-600 border-gray-200',
                                            ];
                                        @endphp
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded border {{ $splStatusColors[$sellPhone->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                            SPL: {{ str_replace('_', ' ', $sellPhone->status) }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Kategori & Rincian Kendala --}}
                            <td class="px-5 py-4 max-w-xs">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-md border {{ $badgeStyles[$issue->category] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                        {{ $categoryLabels[$issue->category] ?? $issue->category }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">oleh <strong class="text-gray-600">{{ $issue->user?->name ?? 'User' }}</strong></span>
                                </div>
                                <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed font-medium bg-gray-50/70 p-2 rounded-lg border border-gray-100">
                                    {{ $issue->comment }}
                                </p>
                                @if ($isResolved && ($issue->resolution_notes || $issue->resolvedBy))
                                    <div class="mt-1.5 text-[10px] text-emerald-800 bg-emerald-50/80 p-1.5 rounded border border-emerald-200">
                                        <span class="font-bold">Solusi:</span> {{ $issue->resolution_notes ?: 'Ditandai selesai.' }}
                                        @if ($issue->resolvedBy)
                                            <span class="text-emerald-600 block text-[9px] mt-0.5">Oleh: {{ $issue->resolvedBy->name }} ({{ $issue->resolved_at?->format('d/m/Y H:i') }})</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Status Kendala Toggle --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if ($isResolved)
                                    <button wire:click="toggleStatus({{ $issue->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200 transition"
                                        title="Klik untuk buka kembali kendala">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        SELESAI
                                    </button>
                                @else
                                    <button wire:click="toggleStatus({{ $issue->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200 hover:bg-rose-200 transition"
                                        title="Klik untuk tandai sudah selesai">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                        BELUM SELESAI
                                    </button>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($sellPhone)
                                        <a href="{{ route('admin.sell-phones.show', $sellPhone) }}" wire:navigate
                                            class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition"
                                            title="Buka Detail Transaksi">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    @endif

                                    <button wire:click="deleteIssue({{ $issue->id }})"
                                        wire:confirm="Yakin ingin menghapus catatan kendala ini?"
                                        class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                                        title="Hapus Catatan Kendala">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="font-bold text-gray-600 text-sm">Tidak ada catatan kendala</p>
                                    <p class="text-xs text-gray-400 mt-1">Data kendala transaksi penjualan HP akan muncul di sini sesuai filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($issues->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $issues->links() }}
            </div>
        @endif
    </div>
</div>
