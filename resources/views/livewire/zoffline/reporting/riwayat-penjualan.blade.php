<div class="p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-800">Riwayat Penjualan</h1>
                <p class="text-sm text-neutral-500 mt-1">
                    Daftar transaksi kasir
                    @if ($activeBranchName)
                        di cabang <span class="font-bold text-neutral-700">{{ $activeBranchName }}</span>
                    @else
                        di <span class="font-bold text-neutral-700">Semua Cabang</span>
                    @endif
                    @if ($canViewAllBu)
                        <span class="text-neutral-400">•</span>
                        <span class="font-semibold text-neutral-600">
                            {{ $activeBuId ? ($businessUnits->firstWhere('id', $activeBuId)?->name ?? 'Unit Bisnis') : 'Semua Unit Bisnis (BU)' }}
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('zoffline.pos') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm shadow-indigo-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Transaksi Baru
                </a>
            </div>
        </div>

        <!-- Quick Status Filter Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Semua -->
            <button wire:click="setStatusFilter('')" type="button"
                class="flex flex-col p-4 rounded-2xl border transition-all text-left {{ empty($filterStatus) ? 'bg-white border-indigo-500 ring-2 ring-indigo-500/20 shadow-md' : 'bg-white/70 border-neutral-200 hover:border-neutral-300 hover:bg-white shadow-sm' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Semua</span>
                    <span class="p-1.5 rounded-lg bg-neutral-100 text-neutral-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl lg:text-2xl font-black text-neutral-900">{{ number_format($statusCounts['ALL'] ?? 0) }}</span>
                    <span class="text-xs text-neutral-400">transaksi</span>
                </div>
            </button>

            <!-- Selesai (Tersinkron) -->
            <button wire:click="setStatusFilter('COMPLETED')" type="button"
                class="flex flex-col p-4 rounded-2xl border transition-all text-left {{ $filterStatus === 'COMPLETED' ? 'bg-emerald-50/70 border-emerald-500 ring-2 ring-emerald-500/20 shadow-md' : 'bg-white/70 border-neutral-200 hover:border-emerald-300 hover:bg-white shadow-sm' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Selesai</span>
                    <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl lg:text-2xl font-black text-emerald-800">{{ number_format($statusCounts['COMPLETED'] ?? 0) }}</span>
                    <span class="text-xs text-emerald-600/70">tersinkron</span>
                </div>
            </button>

            <!-- Piutang -->
            <button wire:click="setStatusFilter('PIUTANG')" type="button"
                class="flex flex-col p-4 rounded-2xl border transition-all text-left {{ $filterStatus === 'PIUTANG' ? 'bg-violet-50/70 border-violet-500 ring-2 ring-violet-500/20 shadow-md' : 'bg-white/70 border-neutral-200 hover:border-violet-300 hover:bg-white shadow-sm' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Piutang</span>
                    <span class="p-1.5 rounded-lg bg-violet-100 text-violet-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl lg:text-2xl font-black text-violet-800">{{ number_format($statusCounts['PIUTANG'] ?? 0) }}</span>
                    <span class="text-xs text-violet-600/70">piutang</span>
                </div>
            </button>

            <!-- Pending -->
            <button wire:click="setStatusFilter('PENDING')" type="button"
                class="flex flex-col p-4 rounded-2xl border transition-all text-left {{ $filterStatus === 'PENDING' ? 'bg-amber-50/70 border-amber-500 ring-2 ring-amber-500/20 shadow-md' : 'bg-white/70 border-neutral-200 hover:border-amber-300 hover:bg-white shadow-sm' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Pending</span>
                    <span class="p-1.5 rounded-lg bg-amber-100 text-amber-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl lg:text-2xl font-black text-amber-800">{{ number_format($statusCounts['PENDING'] ?? 0) }}</span>
                    <span class="text-xs text-amber-600/70">pending</span>
                </div>
            </button>

            <!-- Batal -->
            <button wire:click="setStatusFilter('CANCELLED')" type="button"
                class="flex flex-col p-4 rounded-2xl border transition-all text-left col-span-2 sm:col-span-1 {{ $filterStatus === 'CANCELLED' ? 'bg-red-50/70 border-red-500 ring-2 ring-red-500/20 shadow-md' : 'bg-white/70 border-neutral-200 hover:border-red-300 hover:bg-white shadow-sm' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-red-700">Batal</span>
                    <span class="p-1.5 rounded-lg bg-red-100 text-red-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl lg:text-2xl font-black text-red-800">{{ number_format($statusCounts['CANCELLED'] ?? 0) }}</span>
                    <span class="text-xs text-red-600/70">dibatalkan</span>
                </div>
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
            <div class="p-4 md:p-5 border-b border-neutral-100 bg-neutral-50/60 space-y-4">
                <!-- Baris 1: Search Bar Luas, Nyaman, & Jelas -->
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-neutral-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full pl-12 pr-12 py-3 text-base text-neutral-900 font-medium bg-white border border-neutral-200 rounded-xl placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm transition duration-150 ease-in-out"
                        placeholder="Ketik untuk mencari nomor struk, nomor faktur, SN, atau nama pelanggan...">
                    @if ($search)
                        <button wire:click="$set('search', '')" type="button"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-neutral-400 hover:text-neutral-600 transition"
                            title="Hapus Pencarian">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>

                <!-- Baris 2: Controls & Dropdowns Filter -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-0.5">
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if ($canViewAllBu)
                            <select wire:model.live="filterBuId"
                                class="bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm font-medium text-neutral-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm min-w-[130px]"
                                title="Filter Unit Bisnis">
                                <option value="">Semua BU</option>
                                @foreach ($businessUnits as $bu)
                                    <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if ($canViewAllBranches)
                            <select wire:model.live="filterBranchId"
                                class="bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm font-medium text-neutral-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm min-w-[140px]"
                                title="Filter Cabang">
                                <option value="">Semua Cabang</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $branch->name }} {{ empty($filterBuId) && $branch->businessUnit ? '(' . $branch->businessUnit->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <select wire:model.live="filterKasir"
                            class="bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm font-medium text-neutral-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm min-w-[140px]"
                            title="Filter Kasir">
                            <option value="">Semua Kasir</option>
                            @foreach ($cashiers as $cashier)
                                <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                            @endforeach
                        </select>

                        <div
                            class="flex items-center gap-2 bg-white border border-neutral-200 rounded-xl px-2.5 py-1 shadow-sm">
                            <input type="date" wire:model.live="filterStartDate"
                                class="border-none focus:ring-0 text-sm py-1 text-neutral-700 font-medium" title="Tanggal Awal">
                            <span class="text-neutral-400 text-xs font-semibold">s/d</span>
                            <input type="date" wire:model.live="filterEndDate"
                                class="border-none focus:ring-0 text-sm py-1 text-neutral-700 font-medium" title="Tanggal Akhir">
                        </div>

                        <select wire:model.live="filterStatus"
                            class="bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm font-medium text-neutral-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm min-w-[130px]"
                            title="Filter Status">
                            <option value="">Semua Status</option>
                            <option value="COMPLETED">✓ Selesai</option>
                            <option value="PIUTANG">⚠️ Piutang</option>
                            <option value="PENDING">⏳ Pending</option>
                            <option value="CANCELLED">🚫 Batal</option>
                        </select>
                    </div>

                    @if ($search || $filterStartDate || $filterEndDate || $filterStatus || $filterPaymentMethod || $filterKasir || ($canViewAllBu && $filterBuId) || ($canViewAllBranches && $filterBranchId))
                        <button wire:click="clearFilters"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors border border-red-200/70 shadow-sm"
                            title="Reset Filter">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Reset Filter</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Tanggal & Struk</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Pelanggan</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Total & Pembayaran</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Kasir/Sales</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @forelse($orders as $order)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <div class="text-sm font-bold text-neutral-900">{{ $order->order_number }}</div>
                                        @if ($canViewAllBu && $order->businessUnit)
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $order->business_unit_id == 1 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}"
                                                title="Unit Bisnis: {{ $order->businessUnit->name }}">
                                                {{ $order->businessUnit->code ?? $order->businessUnit->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-neutral-500 mt-1 flex items-center gap-1">
                                        <span>{{ $order->created_at->format('d M Y, H:i') }}</span>
                                        @if ($order->branch)
                                            <span class="text-neutral-400">• {{ $order->branch->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900">
                                        {{ $order->user->name ?? 'Guest' }}
                                    </div>
                                    @if ($order->user && $order->user->identity)
                                        <div class="text-xs text-neutral-500 mt-1">{{ $order->user->identity }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-emerald-600">Rp
                                        {{ number_format($order->grand_total, 0, ',', '.') }}</div>
                                    <div class="text-xs text-neutral-500 mt-1">
                                        {{ $order->payments->first()->paymentMethod->name ?? 'Tunai' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900">
                                        {{ $order->handledBy->name ?? '-' }}
                                    </div>
                                    @if ($order->salesBy)
                                        <div class="flex flex-col items-start gap-0.5 mt-1">
                                            <div
                                                class="text-[11px] text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded inline-flex items-center gap-1">
                                                <span>Sales: {{ $order->salesBy->name }}</span>
                                                @if (auth()->user()->can('edit-order-salesman') || auth()->user()->hasRole(['admin', 'superadmin']))
                                                    <button wire:click="openEditSalesModal({{ $order->id }})" class="hover:text-indigo-900 ml-0.5" title="Ubah Sales">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                            @if (!empty($order->sales_logs_count) && $order->sales_logs_count > 0)
                                                <button wire:click="openEditSalesModal({{ $order->id }})"
                                                    class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200/80 px-1.5 py-0.5 rounded transition"
                                                    title="Pernah diubah {{ $order->sales_logs_count }} kali. Klik untuk lihat riwayat.">
                                                    <svg class="w-2.5 h-2.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{{ $order->sales_logs_count }}x diubah</span>
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if (auth()->user()->can('edit-order-salesman') || auth()->user()->hasRole(['admin', 'superadmin']))
                                            <div class="mt-1">
                                                <button wire:click="openEditSalesModal({{ $order->id }})" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold underline decoration-dashed flex items-center gap-0.5" title="Tentukan Sales">
                                                    + Set Sales
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php $pendingCancel = $order->approvalRequests->where('status', 'PENDING')->first(); @endphp
                                    @if ($pendingCancel)
                                        <span
                                            class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold rounded border border-amber-200 uppercase">
                                            ⏳ Batal Pending
                                        </span>
                                    @elseif ($order->order_status === 'CANCELLED')
                                        <span
                                            class="px-2 py-0.5 bg-red-50 text-red-700 text-[10px] font-bold rounded border border-red-200 uppercase">
                                            🚫 Dibatalkan
                                        </span>
                                    @elseif ($order->order_status === 'DELETED')
                                        <span
                                            class="px-2 py-0.5 bg-red-50 text-red-700 text-[10px] font-bold rounded border border-red-200 uppercase">
                                            🗑️ Dosa
                                        </span>
                                    @elseif ($order->order_status === 'PIUTANG')
                                        <span
                                            class="px-2 py-0.5 bg-violet-50 text-violet-700 text-[10px] font-bold rounded border border-violet-200 uppercase">
                                            ⚠️ Piutang
                                        </span>
                                    @elseif (!empty($order->accurate_invoice_no) || !empty($order->accurate_receipt_no))
                                        <div class="inline-flex flex-col gap-1 items-start">
                                            <span
                                                class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded border border-emerald-200 uppercase">
                                                ✓ Tersinkron
                                            </span>
                                            @if ($order->accurate_invoice_no)
                                                <span class="text-[10px] text-neutral-400 font-mono"
                                                    title="Invoice No">Inv: {{ $order->accurate_invoice_no }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span
                                            class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold rounded border border-amber-200 uppercase">
                                            ⏳ Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    @if ($order->order_status != 'DELETED')
                                        <div class="flex flex-col gap-1.5 items-center">
                                            <button wire:click="reprintOrder({{ $order->id }})"
                                                class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg text-xs font-bold transition-all border border-emerald-100">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Struk
                                            </button>

                                            @if (auth()->user()->can('edit-order-salesman') || auth()->user()->hasRole(['admin', 'superadmin']))
                                                <button wire:click="openEditSalesModal({{ $order->id }})"
                                                    class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-[10px] font-bold transition-all border border-indigo-200 uppercase"
                                                    title="Ubah Tenaga Penjual">
                                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    Ubah Sales
                                                </button>
                                            @endif

                                            @if (!$pendingCancel && $order->order_status !== 'CANCELLED')
                                                <button wire:click="requestCancellation({{ $order->id }})"
                                                    class="w-full inline-flex items-center justify-center gap-1 px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-[10px] font-bold transition-all border border-red-100 uppercase">
                                                    Batalkan
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div
                                        class="mx-auto w-16 h-16 bg-neutral-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-neutral-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-neutral-900">
                                        {{ $search || $filterStatus || $filterKasir || $filterStartDate || $filterEndDate ? 'Tidak ada transaksi yang cocok' : 'Belum ada riwayat penjualan' }}
                                    </h3>
                                    <p class="mt-1 text-sm text-neutral-500">
                                        {{ $search || $filterStatus || $filterKasir || $filterStartDate || $filterEndDate ? 'Coba ubah atau reset filter pencarian Anda.' : 'Transaksi baru akan muncul di sini.' }}
                                    </p>
                                    @if ($search || $filterStatus || $filterKasir || $filterStartDate || $filterEndDate)
                                        <button wire:click="clearFilters" class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-semibold rounded-lg transition">
                                            Reset Semua Filter
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="p-4 border-t border-neutral-100 bg-neutral-50/50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('livewire.zoffline.pos.modal.riwayat-receipt')

    {{-- MODAL AJUKAN PEMBATALAN --}}
    @if ($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
                @click.outside="$wire.closeCancelModal()">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-center text-neutral-900 mb-2">Ajukan Pembatalan Transaksi</h3>
                    <p class="text-sm text-center text-neutral-500 mb-6">Penghapusan faktur di Accurate memerlukan
                        persetujuan. Silakan isi alasan pembatalan.</p>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase mb-2">Alasan
                            Pembatalan</label>
                        <textarea wire:model="cancelReason" rows="3"
                            class="w-full px-4 py-3 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all resize-none text-sm"
                            placeholder="Contoh: Salah input nominal, customer retur, dsb..."></textarea>
                        @error('cancelReason')
                            <span class="text-xs text-red-500 font-medium mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="p-4 bg-neutral-50 border-t border-neutral-100 flex gap-3">
                    <button wire:click="closeCancelModal"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-neutral-600 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 transition-all">
                        Tutup
                    </button>
                    <button wire:click="submitCancellation"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-all shadow-sm shadow-red-600/20">
                        Kirim Pengajuan
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL UBAH TENAGA PENJUAL --}}
    @if ($showEditSalesModal && $this->orderToEditSales)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-neutral-100"
                @click.outside="$wire.closeEditSalesModal()">
                {{-- Header --}}
                <div class="p-5 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-indigo-100/60 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-neutral-900">Ubah Tenaga Penjual (Sales)</h3>
                            <p class="text-xs text-neutral-500 font-medium">Order: <span class="font-mono font-bold text-indigo-700">{{ $this->orderToEditSales->order_number }}</span></p>
                        </div>
                    </div>
                    <button wire:click="closeEditSalesModal" class="text-neutral-400 hover:text-neutral-600 transition p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body Content --}}
                <div class="p-6 space-y-4">
                    {{-- Info Transaksi Singkat --}}
                    <div class="bg-neutral-50 rounded-xl p-3.5 border border-neutral-200/70 text-xs grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-neutral-400 block font-medium">Cabang / BU:</span>
                            <span class="font-bold text-neutral-800">{{ $this->orderToEditSales->branch->name ?? 'Semua Cabang' }} ({{ $this->orderToEditSales->businessUnit->code ?? ($this->orderToEditSales->businessUnit->name ?? '-') }})</span>
                        </div>
                        <div>
                            <span class="text-neutral-400 block font-medium">Kasir:</span>
                            <span class="font-bold text-neutral-800">{{ $this->orderToEditSales->handledBy->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-neutral-400 block font-medium">Sales Saat Ini:</span>
                            <span class="font-bold text-indigo-700">{{ $this->orderToEditSales->salesBy->name ?? 'Belum ada sales' }}</span>
                            @if($this->orderToEditSales->salesBy?->employee_no)
                                <span class="text-neutral-400 font-mono text-[10px]">({{ $this->orderToEditSales->salesBy->employee_no }})</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-neutral-400 block font-medium">Faktur Accurate:</span>
                            @if ($this->orderToEditSales->accurate_invoice_no)
                                <span class="inline-flex items-center gap-1 font-bold text-emerald-600">
                                    <span>✓ {{ $this->orderToEditSales->accurate_invoice_no }}</span>
                                </span>
                            @else
                                <span class="text-neutral-400">Belum ada faktur</span>
                            @endif
                        </div>
                    </div>

                    {{-- Form Pilih Sales Baru --}}
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2">
                            Pilih Tenaga Penjual Baru <span class="text-red-500">*</span>
                        </label>

                        {{-- Search Input --}}
                        <div class="relative mb-2">
                            <input type="text" wire:model.live.debounce.250ms="searchNewSales"
                                class="w-full pl-9 pr-4 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs font-medium text-neutral-800 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition"
                                placeholder="Cari nama atau NIK sales...">
                            <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        {{-- Sales List Box --}}
                        <div class="max-h-48 overflow-y-auto border border-neutral-200 rounded-xl divide-y divide-neutral-100 bg-white shadow-inner">
                            @forelse ($this->availableSalesList as $sales)
                                <button type="button" wire:click="selectNewSales({{ $sales->id }})"
                                    class="w-full text-left px-3.5 py-2.5 flex items-center justify-between text-xs transition {{ $selectedNewSalesId == $sales->id ? 'bg-indigo-50/90 text-indigo-900 font-bold border-l-4 border-indigo-600' : 'hover:bg-neutral-50 text-neutral-700 font-medium' }}">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ $sales->name }}</span>
                                            @if ($this->orderToEditSales->sales_id == $sales->id)
                                                <span class="text-[9px] bg-neutral-200 text-neutral-700 px-1.5 py-0.5 rounded font-normal">(Saat Ini)</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-neutral-400 font-mono mt-0.5">
                                            {{ $sales->employee_no ?? 'No NIK' }} &bull; {{ $sales->branch->name ?? 'Semua Cabang' }}
                                        </div>
                                    </div>
                                    @if ($selectedNewSalesId == $sales->id)
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>
                            @empty
                                <div class="p-4 text-center text-xs text-neutral-400">
                                    Tidak ada tenaga penjual yang cocok.
                                </div>
                            @endforelse
                        </div>
                        @error('selectedNewSalesId')
                            <span class="text-xs text-red-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Catatan Alasan Perubahan (Opsional) --}}
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                            Catatan Perubahan (Opsional)
                        </label>
                        <input type="text" wire:model="editSalesNotes"
                            class="w-full px-3.5 py-2 border border-neutral-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition"
                            placeholder="Contoh: Koreksi input salah sales oleh kasir">
                    </div>

                    @if ($this->orderToEditSales->accurate_invoice_no)
                        <div class="p-3 bg-blue-50/70 border border-blue-200/80 rounded-xl flex items-start gap-2.5 text-xs text-blue-900">
                            <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-[11px] leading-relaxed">
                                <span class="font-bold">Info Sinkronisasi Accurate:</span> Pesanan ini memiliki Faktur Penjualan Accurate (<span class="font-mono font-bold">{{ $this->orderToEditSales->accurate_invoice_no }}</span>). Sistem akan otomatis memperbarui tenaga penjual pada faktur Accurate tersebut.
                            </div>
                        </div>
                    @endif

                    {{-- Riwayat Perubahan Sales (Audit Trail) --}}
                    @if ($this->orderToEditSales->salesLogs && $this->orderToEditSales->salesLogs->count() > 0)
                        <div class="border-t border-neutral-200/80 pt-4 mt-2">
                            <h4 class="text-xs font-bold text-neutral-800 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Riwayat Perubahan Sales ({{ $this->orderToEditSales->salesLogs->count() }})
                            </h4>
                            <div class="space-y-2 max-h-44 overflow-y-auto pr-1">
                                @foreach ($this->orderToEditSales->salesLogs as $log)
                                    <div class="p-2.5 bg-neutral-50 rounded-xl border border-neutral-200/70 text-xs">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span class="text-neutral-500 font-mono text-[10px]">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $log->accurate_sync_status === 'SUCCESS' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($log->accurate_sync_status === 'FAILED' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-neutral-100 text-neutral-600') }}">
                                                {{ $log->accurate_sync_status === 'SUCCESS' ? '✓ Accurate' : ($log->accurate_sync_status === 'FAILED' ? '✕ Gagal Sync' : 'Lokal') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 font-medium text-neutral-800 flex-wrap">
                                            <span class="text-neutral-500 line-through">{{ $log->previousSales->name ?? 'Tanpa Sales' }}</span>
                                            <span class="text-neutral-400 font-bold">➔</span>
                                            <span class="font-bold text-indigo-700">{{ $log->newSales->name ?? '-' }}</span>
                                            <span class="text-neutral-400 text-[10px] ml-auto">oleh <strong class="text-neutral-700">{{ $log->changedBy->name ?? 'Sistem' }}</strong></span>
                                        </div>
                                        @if ($log->notes)
                                            <div class="mt-1 text-[11px] text-neutral-600 italic bg-white p-1.5 rounded border border-neutral-100">
                                                "{{ $log->notes }}"
                                            </div>
                                        @endif
                                        @if ($log->accurate_sync_status === 'FAILED' && $log->accurate_sync_message)
                                            <div class="mt-1 text-[10px] text-red-600 font-mono">
                                                Error: {{ $log->accurate_sync_message }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer Actions --}}
                <div class="p-4 bg-neutral-50 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <button wire:click="closeEditSalesModal" type="button"
                        class="px-4 py-2 text-xs font-bold text-neutral-600 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-100 transition">
                        Batal
                    </button>
                    <button wire:click="updateSalesperson" wire:loading.attr="disabled" type="button"
                        class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading wire:target="updateSalesperson" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="updateSalesperson">Simpan Perubahan</span>
                        <span wire:loading wire:target="updateSalesperson">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
