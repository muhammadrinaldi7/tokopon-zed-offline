<div class="px-6 py-8 w-full max-w-7xl mx-auto" x-data="{ alert: null }"
    @admin-alert.window="
        alert = $event.detail;
        setTimeout(() => alert = null, 4000);
    ">

    <!-- Alpine Notification Setup -->
    <div x-show="alert" x-transition.opacity.duration.300ms style="display: none;"
        class="mb-6 px-4 py-3 rounded-xl border flex items-center gap-3 text-sm font-medium shadow-sm transition-all"
        :class="alert?.type === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-800' :
            'bg-red-50 border-red-100 text-red-800'">
        <svg x-show="alert?.type === 'success'" class="w-5 h-5 text-emerald-500 shrink-0" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg x-show="alert?.type === 'error'" class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="alert?.message"></span>
    </div>

    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl border border-blue-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Audit & Duplikat Pengguna</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Analisa nomor telepon ganda, cek riwayat pesanan (orders), riwayat pembelian HP (sell phones), serta integrasi pelanggan Accurate.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div wire:loading class="flex items-center gap-2 text-xs font-medium text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100">
                <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Memuat data...</span>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Card 1: Total Duplikat -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Nomor Ganda</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1.5">{{ number_format($stats['totalDuplicatePhones'] ?? 0) }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Nomor HP memiliki &gt; 1 akun</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 border border-amber-100 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-50 flex items-center text-xs text-amber-700 font-medium">
                <span>{{ number_format($stats['totalDuplicateAccounts'] ?? 0) }} akun teridentifikasi</span>
            </div>
        </div>

        <!-- Card 2: Punya Riwayat Orders (Penjualan) -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Riwayat Pesanan (Orders)</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-1.5">{{ number_format($stats['phonesWithOrdersCount'] ?? 0) }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Grup nomor dengan pesanan toko</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-100 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-50 flex items-center text-xs text-emerald-700 font-medium">
                <span>Pelanggan membeli dari toko</span>
            </div>
        </div>

        <!-- Card 3: Punya Riwayat Pembelian HP (Sell Phone) -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Riwayat Pembelian HP</p>
                    <h3 class="text-2xl font-bold text-indigo-600 mt-1.5">{{ number_format($stats['phonesWithSellPhonesCount'] ?? 0) }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Grup nomor menjual HP ke toko</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-100 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-50 flex items-center text-xs text-indigo-700 font-medium">
                <span>Layanan Beli HP / Tukar Tambah</span>
            </div>
        </div>

        <!-- Card 4: Tanpa Transaksi Sama Sekali -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Tanpa Transaksi Sama Sekali</p>
                    <h3 class="text-2xl font-bold text-gray-600 mt-1.5">{{ number_format($stats['phonesWithoutTransactionsCount'] ?? 0) }}</h3>
                    <p class="text-xs text-gray-500 mt-1">0 Order & 0 Pembelian HP</p>
                </div>
                <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500 border border-gray-200 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-50 flex items-center text-xs text-gray-500 font-medium">
                <span>Akun pasif / tanpa riwayat</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Search Bar -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.400ms="search"
                    placeholder="Cari nomor HP (cth: 0813...), nama, atau email..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                @if (!empty($search))
                    <button wire:click="$set('search', '')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Filter Status Transaksi -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 whitespace-nowrap">Filter Transaksi:</span>
                    <select wire:model.live="orderFilter"
                        class="bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-medium text-gray-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                        <option value="all">Semua Nomor Duplikat</option>
                        <option value="has_transactions">Ada Transaksi (Pesanan / Pembelian HP)</option>
                        <option value="has_orders">Hanya Riwayat Pesanan (Orders)</option>
                        <option value="has_sell_phones">Hanya Riwayat Pembelian HP (Sell Phone)</option>
                        <option value="no_transactions">Tanpa Transaksi Sama Sekali (0 Order & 0 Beli HP)</option>
                        <option value="conflict_transactions">Konflik: Banyak Akun Punya Transaksi</option>
                    </select>
                </div>

                <!-- Per Page Selector -->
                <div class="flex items-center gap-2">
                    <select wire:model.live="perPage"
                        class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                        <option value="10">10 / hal</option>
                        <option value="15">15 / hal</option>
                        <option value="25">25 / hal</option>
                        <option value="50">50 / hal</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Duplicate Groups List -->
    <div class="space-y-5">
        @forelse ($paginatedGroups as $group)
            @php
                $phone = $group->phone_number;
                $userList = $usersByPhone->get($phone, collect());
                $hasOrdersInGroup = $userList->contains(fn($u) => $u->orders_count > 0);
                $hasSellPhonesInGroup = $userList->contains(fn($u) => $u->sell_phones_count > 0);
                $accountsWithTransactionsCount = $userList->filter(fn($u) => $u->orders_count > 0 || $u->sell_phones_count > 0)->count();
                $totalOrdersInGroup = $userList->sum('orders_count');
                $totalOrdersSpentInGroup = $userList->sum('orders_total_amount');
                $totalSellPhonesInGroup = $userList->sum('sell_phones_count');
                $totalSellPhonesSpentInGroup = $userList->sum('sell_phones_total_amount');
            @endphp

            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden hover:border-blue-300 transition-all">
                <!-- Group Header Bar -->
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="font-mono text-base font-bold text-gray-900 tracking-wide">{{ $phone }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ $group->accounts_count }} Akun Terdaftar
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Ditemukan {{ $group->accounts_count }} pengguna berbeda menggunakan nomor telepon ini
                            </p>
                        </div>
                    </div>

                    <!-- Summary Badges in Group Header -->
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($accountsWithTransactionsCount > 1)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Konflik: {{ $accountsWithTransactionsCount }} Akun Sama-Sama Punya Transaksi
                            </span>
                        @endif

                        @if ($hasOrdersInGroup)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                {{ $totalOrdersInGroup }} Pesanan (Rp {{ number_format($totalOrdersSpentInGroup, 0, ',', '.') }})
                            </span>
                        @endif

                        @if ($hasSellPhonesInGroup)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                {{ $totalSellPhonesInGroup }} Beli HP (Rp {{ number_format($totalSellPhonesSpentInGroup, 0, ',', '.') }})
                            </span>
                        @endif

                        @if (!$hasOrdersInGroup && !$hasSellPhonesInGroup)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                </svg>
                                Semua 0 Transaksi (Belum Pernah Belanja / Jual HP)
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Group Children Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/75 text-gray-500 uppercase text-[11px] font-bold tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-6">User / Profil</th>
                                <th class="py-3 px-6">Email Terdaftar</th>
                                <th class="py-3 px-6">Tanggal Dibuat</th>
                                <th class="py-3 px-6">Pelanggan Accurate</th>
                                <th class="py-3 px-6">Pesanan Toko (Orders)</th>
                                <th class="py-3 px-6">Pembelian HP (Sell Phone)</th>
                                <th class="py-3 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($userList as $index => $u)
                                @php
                                    $hasAnyTx = ($u->orders_count > 0 || $u->sell_phones_count > 0);
                                    $isPrimaryCandidate = ($index === 0 && $hasAnyTx);
                                    $isEmptyDuplicate = (!$hasAnyTx && $u->accurateCustomers->isEmpty());
                                @endphp
                                <tr class="hover:bg-blue-50/30 transition-colors {{ $isPrimaryCandidate ? 'bg-emerald-50/20' : '' }}">
                                    <!-- User Column -->
                                    <td class="py-3.5 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg {{ $hasAnyTx ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700' }} flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ strtoupper(substr($u->name ?? 'U', 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-gray-900">{{ $u->name }}</span>
                                                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-mono">ID: {{ $u->id }}</span>
                                                    @if ($isPrimaryCandidate)
                                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                            Utama / Aktif
                                                        </span>
                                                    @elseif ($isEmptyDuplicate)
                                                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                                            Duplikat Kosong
                                                        </span>
                                                    @endif
                                                </div>
                                                @if (!empty($u->profile->full_name) && $u->profile->full_name !== $u->name)
                                                    <p class="text-xs text-gray-400 mt-0.5">Nama Profil: {{ $u->profile->full_name }}</p>
                                                @endif
                                                @if (!empty($u->profile->domisili))
                                                    <p class="text-[11px] text-gray-400">Domisili: {{ $u->profile->domisili }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Email Column -->
                                    <td class="py-3.5 px-6 font-mono text-xs text-gray-700">
                                        {{ $u->email }}
                                    </td>

                                    <!-- Created At Column -->
                                    <td class="py-3.5 px-6 text-xs text-gray-600 whitespace-nowrap">
                                        <div>{{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}</div>
                                        <span class="text-[11px] text-gray-400">{{ $u->created_at ? $u->created_at->format('H:i') : '' }}</span>
                                    </td>

                                    <!-- Accurate Customer Column -->
                                    <td class="py-3.5 px-6 whitespace-nowrap">
                                        @if ($u->accurateCustomers->isNotEmpty())
                                            <div class="space-y-1">
                                                @foreach ($u->accurateCustomers as $ac)
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>{{ $ac->accurate_customer_no ?? 'Terkait' }}</span>
                                                        @if ($ac->businessUnit)
                                                            <span class="text-[10px] text-blue-500 font-normal">({{ $ac->businessUnit->name }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 font-medium italic">Belum Ada</span>
                                        @endif
                                    </td>

                                    <!-- Orders Status Column -->
                                    <td class="py-3.5 px-6 whitespace-nowrap">
                                        @if ($u->orders_count > 0)
                                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                </svg>
                                                <div>
                                                    <span class="font-bold text-xs">{{ $u->orders_count }} Pesanan</span>
                                                    <span class="text-[10px] text-emerald-700 block">Rp {{ number_format($u->orders_total_amount ?? 0, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-500">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                                </svg>
                                                0 Pesanan
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Sell Phone Status Column -->
                                    <td class="py-3.5 px-6 whitespace-nowrap">
                                        @if ($u->sell_phones_count > 0)
                                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-800">
                                                <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <div>
                                                    <span class="font-bold text-xs">{{ $u->sell_phones_count }} Unit HP</span>
                                                    <span class="text-[10px] text-indigo-700 block">Rp {{ number_format($u->sell_phones_total_amount ?? 0, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-500">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                                </svg>
                                                0 Beli HP
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions Column -->
                                    <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($u->orders_count > 0)
                                                <button wire:click="openOrdersModal({{ $u->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-blue-600/20 cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                    </svg>
                                                    Lihat Pesanan
                                                </button>
                                            @endif

                                            @if ($u->sell_phones_count > 0)
                                                <button wire:click="openSellPhonesModal({{ $u->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-indigo-600/20 cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    Lihat Pembelian HP
                                                </button>
                                            @endif

                                            @if ($u->orders_count == 0 && $u->sell_phones_count == 0)
                                                <span class="text-xs text-gray-400 italic">Tidak ada transaksi</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white p-12 rounded-2xl border border-gray-100 shadow-sm text-center">
                <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-800">Tidak Ada Data Duplikat Ditemukan</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                    @if (!empty($search) || $orderFilter !== 'all')
                        Tidak ada kelompok nomor telepon ganda yang sesuai dengan filter atau kata kunci pencarian Anda. Coba ubah filter pencarian.
                    @else
                        Selamat! Tidak ada nomor telepon ganda yang ditemukan pada database Anda.
                    @endif
                </p>
                @if (!empty($search) || $orderFilter !== 'all')
                    <button wire:click="$set('search', ''); $set('orderFilter', 'all')"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                        Reset Filter
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination Links -->
    <div class="mt-8">
        {{ $paginatedGroups->links() }}
    </div>

    <!-- Modal 1: Rincian Pesanan Penjualan (Orders Modal) -->
    @if ($isOrdersModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Background overlay / Backdrop -->
            <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-sm transition-opacity" wire:click="closeOrdersModal"></div>

            <!-- Modal Panel Box -->
            <div class="relative z-10 bg-white rounded-2xl text-left shadow-2xl overflow-hidden sm:max-w-3xl w-full max-h-[90vh] flex flex-col border border-gray-100 animate-in fade-in zoom-in duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Riwayat Pesanan Toko: {{ $selectedUserName }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            User ID: <span class="font-mono font-bold text-gray-700">{{ $selectedUserId }}</span> • Email: <span class="font-mono text-gray-700">{{ $selectedUserEmail }}</span> • Telp: <span class="font-mono text-gray-700">{{ $selectedUserPhone }}</span>
                        </p>
                    </div>
                    <button wire:click="closeOrdersModal"
                        class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body: Orders Table -->
                <div class="p-6 overflow-y-auto flex-1">
                    @if (count($selectedUserOrders) > 0)
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] font-bold tracking-wider border-b border-gray-200">
                                    <tr>
                                        <th class="py-3 px-4">No. Pesanan</th>
                                        <th class="py-3 px-4">Tanggal</th>
                                        <th class="py-3 px-4">Unit Usaha</th>
                                        <th class="py-3 px-4">Total Belanja</th>
                                        <th class="py-3 px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($selectedUserOrders as $order)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3 px-4 font-mono font-bold text-xs text-blue-600">
                                                {{ $order['order_number'] }}
                                            </td>
                                            <td class="py-3 px-4 text-xs text-gray-600 whitespace-nowrap">
                                                {{ $order['order_date'] }}
                                            </td>
                                            <td class="py-3 px-4 text-xs text-gray-700 font-medium">
                                                {{ $order['business_unit'] }}
                                            </td>
                                            <td class="py-3 px-4 text-xs font-bold text-gray-900 whitespace-nowrap">
                                                Rp {{ number_format($order['grand_total'], 0, ',', '.') }}
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold
                                                    {{ in_array(strtoupper($order['order_status']), ['COMPLETED', 'PAID', 'SELESAI']) ? 'bg-emerald-100 text-emerald-800' :
                                                       (in_array(strtoupper($order['order_status']), ['CANCELLED', 'BATAL']) ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                                    {{ strtoupper($order['order_status']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-400 text-sm">
                            Pengguna ini belum memiliki riwayat transaksi pesanan.
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end shrink-0">
                    <button wire:click="closeOrdersModal"
                        class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl transition-all cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal 2: Rincian Pembelian HP (Sell Phones Modal) -->
    @if ($isSellPhonesModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title-sellphones" role="dialog" aria-modal="true">
            <!-- Background overlay / Backdrop -->
            <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-sm transition-opacity" wire:click="closeSellPhonesModal"></div>

            <!-- Modal Panel Box -->
            <div class="relative z-10 bg-white rounded-2xl text-left shadow-2xl overflow-hidden sm:max-w-4xl w-full max-h-[90vh] flex flex-col border border-gray-100 animate-in fade-in zoom-in duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Riwayat Pembelian HP (Pelanggan Menjual HP): {{ $selectedUserName }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            User ID: <span class="font-mono font-bold text-gray-700">{{ $selectedUserId }}</span> • Email: <span class="font-mono text-gray-700">{{ $selectedUserEmail }}</span> • Telp: <span class="font-mono text-gray-700">{{ $selectedUserPhone }}</span>
                        </p>
                    </div>
                    <button wire:click="closeSellPhonesModal"
                        class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body: Sell Phones Table -->
                <div class="p-6 overflow-y-auto flex-1">
                    @if (count($selectedUserSellPhones) > 0)
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] font-bold tracking-wider border-b border-gray-200">
                                    <tr>
                                        <th class="py-3 px-4">No. Invoice / ID</th>
                                        <th class="py-3 px-4">Perangkat HP</th>
                                        <th class="py-3 px-4">Spesifikasi</th>
                                        <th class="py-3 px-4">Unit Usaha</th>
                                        <th class="py-3 px-4">Nilai Taksiran</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Tanggal Transaksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($selectedUserSellPhones as $item)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3 px-4 font-mono font-bold text-xs text-indigo-600">
                                                {{ $item['invoice_number'] }}
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="text-xs font-semibold text-gray-800 uppercase block">{{ $item['phone_brand'] }}</span>
                                                <span class="text-xs text-gray-600">{{ $item['phone_model'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-xs text-gray-600 whitespace-nowrap">
                                                <span>{{ $item['phone_specs'] }}</span>
                                                @if (!empty($item['imei']) && $item['imei'] !== '-')
                                                    <span class="block text-[11px] font-mono text-gray-400">IMEI: {{ $item['imei'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-xs text-gray-700 font-medium">
                                                {{ $item['business_unit'] }}
                                            </td>
                                            <td class="py-3 px-4 text-xs font-bold text-gray-900 whitespace-nowrap">
                                                Rp {{ number_format($item['appraised_value'], 0, ',', '.') }}
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                @php
                                                    $st = strtoupper($item['status']);
                                                    $badgeClass = 'bg-gray-100 text-gray-700';
                                                    if (in_array($st, ['COMPLETED', 'PAID', 'SELESAI', 'APPROVED'])) {
                                                        $badgeClass = 'bg-emerald-100 text-emerald-800';
                                                    } elseif (in_array($st, ['PAYING', 'PROCESSING', 'INSPECTING'])) {
                                                        $badgeClass = 'bg-blue-100 text-blue-800';
                                                    } elseif (in_array($st, ['REJECTED', 'CANCELLED', 'BATAL'])) {
                                                        $badgeClass = 'bg-red-100 text-red-800';
                                                    } elseif (in_array($st, ['SUBMITTED', 'PENDING'])) {
                                                        $badgeClass = 'bg-amber-100 text-amber-800';
                                                    }
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $badgeClass }}">
                                                    {{ $st }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-xs text-gray-600 whitespace-nowrap">
                                                {{ $item['created_at'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-400 text-sm">
                            Pengguna ini belum memiliki riwayat penjualan HP ke toko.
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end shrink-0">
                    <button wire:click="closeSellPhonesModal"
                        class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl transition-all cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
