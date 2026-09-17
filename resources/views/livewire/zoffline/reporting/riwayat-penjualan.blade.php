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
                                        <div
                                            class="text-[11px] text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded inline-block mt-1">
                                            Sales: {{ $order->salesBy->name }}</div>
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
                                        <div class="flex flex-col gap-2 items-center">
                                            <button wire:click="reprintOrder({{ $order->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg text-xs font-bold transition-all border border-emerald-100">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Struk
                                            </button>

                                            @if (!$pendingCancel && $order->order_status !== 'CANCELLED')
                                                <button wire:click="requestCancellation({{ $order->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-[10px] font-bold transition-all border border-red-100 uppercase mt-1">
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
</div>
