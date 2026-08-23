<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Reset Transaksi ke Draft</h1>
            <p class="text-gray-500 text-sm mt-1">Kembalikan transaksi ke status Draft, ganti kasir penanggung jawab, & lacak riwayat dokumen.</p>
        </div>

        {{-- Tab Switcher --}}
        <div class="flex bg-gray-100 p-1 rounded-xl w-fit border border-gray-200">
            <button wire:click="setTab('orders')"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'orders' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Transaksi Aktif
            </button>
            <button wire:click="setTab('history')"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'history' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Riwayat Dokumen (History)
            </button>
        </div>
    </div>

    {{-- Info Banner (Only for Active Orders tab) --}}
    @if ($activeTab === 'orders')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="text-sm font-bold text-amber-800">Perhatian & Informasi</p>
                <p class="text-xs text-amber-700 mt-0.5">Aksi reset akan menghapus Faktur & Penerimaan Penjualan di Accurate, menghapus pembayaran, dan mengembalikan status ke Draft. Anda juga dapat <strong>mengubah kasir penanggung jawab</strong> secara langsung tanpa harus me-reset order.</p>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-3 mb-6 flex-wrap">
        <div class="flex-1 min-w-[240px] relative">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="{{ $activeTab === 'orders' ? 'Cari No. Transaksi, Nama Customer, atau SN...' : 'Cari No. Transaksi, No. Invoice/Receipt, Alasan...' }}"
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-amber-500/20 focus:border-amber-500">
        </div>
        <div class="w-full md:w-44 shrink-0">
            <select wire:model.live="filterBranch"
                class="w-full px-3 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-amber-500/20 focus:border-amber-500">
                <option value="">Semua Cabang</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-44 shrink-0">
            <select wire:model.live="filterCashier"
                class="w-full px-3 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-amber-500/20 focus:border-amber-500">
                <option value="">Semua Kasir</option>
                @foreach ($cashiers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-36 shrink-0">
            <input type="date" wire:model.live="filterStartDate"
                class="w-full px-3 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-amber-500/20 focus:border-amber-500"
                title="Tanggal Mulai">
        </div>
        <div class="w-full md:w-36 shrink-0">
            <input type="date" wire:model.live="filterEndDate"
                class="w-full px-3 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-amber-500/20 focus:border-amber-500"
                title="Tanggal Akhir">
        </div>
        @if ($search || $filterBranch || $filterCashier || $filterStartDate || $filterEndDate)
            <button wire:click="clearFilters"
                class="px-4 py-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium whitespace-nowrap">
                Reset Filter
            </button>
        @endif
    </div>

    {{-- TAB 1: TRANSAKSI AKTIF --}}
    @if ($activeTab === 'orders')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4 font-bold">No. Transaksi</th>
                            <th class="px-6 py-4 font-bold">Customer & Kasir</th>
                            <th class="px-6 py-4 font-bold">Cabang</th>
                            <th class="px-6 py-4 font-bold">Total & Payment</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 font-bold">Accurate</th>
                            <th class="px-6 py-4 font-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 align-top">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 text-sm">{{ $order->order_number }}</span>
                                    <div class="text-[10px] text-gray-400 font-mono mt-1">
                                        {{ $order->order_channel }} • {{ $order->created_at->format('d M Y, H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-800 text-sm">{{ $order->user->name ?? 'User Terhapus' }}</p>
                                    
                                    {{-- Cashier Badge with Quick Edit Button --}}
                                    <div class="mt-1.5 flex items-center gap-1.5">
                                        <button wire:click="openChangeCashierModal({{ $order->id }})"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 px-2 py-0.5 rounded-md transition group"
                                            title="Klik untuk mengubah kasir penanggung jawab">
                                            <svg class="w-3 h-3 text-blue-500 group-hover:text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Kasir: {{ $order->handledBy->name ?? 'Belum Ditentukan' }}</span>
                                            <svg class="w-2.5 h-2.5 text-blue-400 group-hover:text-blue-600 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-gray-700">{{ $order->branch->name ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-emerald-600">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $order->payments->first()?->paymentMethod->name ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusColors = [
                                            'COMPLETED' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                            'PENDING' => 'bg-amber-50 text-amber-600 border-amber-100',
                                            'PROCESSING' => 'bg-blue-50 text-blue-600 border-blue-100',
                                            'CANCELLED' => 'bg-rose-50 text-rose-600 border-rose-100',
                                            'PIUTANG' => 'bg-violet-50 text-violet-600 border-violet-100',
                                            'DELETED' => 'bg-red-50 text-red-600 border-red-100',
                                        ];
                                        $statusLabels = [
                                            'COMPLETED' => 'Selesai',
                                            'PENDING' => 'Pending',
                                            'PROCESSING' => 'Diproses',
                                            'CANCELLED' => 'Dibatalkan',
                                            'PIUTANG' => 'Piutang',
                                            'DELETED' => 'Dihapus',
                                        ];
                                    @endphp
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg border {{ $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                        {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if (!empty($order->accurate_invoice_no) || !empty($order->accurate_receipt_no))
                                        <div class="inline-flex flex-col gap-1 items-start">
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded border border-emerald-200 uppercase">
                                                ✓ Tersinkron
                                            </span>
                                            @if ($order->accurate_invoice_no)
                                                <span class="text-[10px] text-gray-500 font-mono" title="Invoice No">Inv: {{ $order->accurate_invoice_no }}</span>
                                            @endif
                                            @if ($order->accurate_receipt_no)
                                                <span class="text-[10px] text-gray-400 font-mono" title="Receipt No">Rec: {{ $order->accurate_receipt_no }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-[10px] text-gray-400 font-medium">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        {{-- Ganti Kasir Quick Button --}}
                                        <button wire:click="openChangeCashierModal({{ $order->id }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition border border-gray-200"
                                            title="Ganti Kasir">
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Ganti Kasir
                                        </button>

                                        {{-- Retry Accurate Push Button --}}
                                        @if ($order->order_status === 'COMPLETED' && empty($order->accurate_invoice_no) && empty($order->accurate_receipt_no))
                                            <button wire:click="retryAccuratePush({{ $order->id }})" wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-bold transition border border-blue-200 shadow-sm disabled:opacity-50"
                                                title="Coba sinkronkan ulang ke Accurate tanpa mereset pesanan">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Ulangi Sinkronisasi
                                            </button>
                                        @endif

                                        {{-- Ke Draft Button --}}
                                        @if (!in_array($order->order_status, ['DRAFT', 'DRAFT_LOADED', 'CANCELLED', 'DELETED']))
                                            <button wire:click="requestDirectCancellation({{ $order->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-lg text-xs font-bold transition border border-amber-200 shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                Ke Draft
                                            </button>
                                        @elseif ($order->order_status === 'CANCELLED')
                                            <span class="text-[10px] text-rose-500 font-bold">Sudah Batal</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-gray-900">Tidak ada transaksi ditemukan</h3>
                                    <p class="mt-1 text-sm text-gray-500">Coba ubah filter pencarian anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 2: RIWAYAT DOKUMEN (HISTORY LOGS) --}}
    @if ($activeTab === 'history')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4 font-bold">Waktu Reset</th>
                            <th class="px-6 py-4 font-bold">No. Transaksi</th>
                            <th class="px-6 py-4 font-bold">Customer & Kasir</th>
                            <th class="px-6 py-4 font-bold">Dokumen Sebelumnya (Accurate)</th>
                            <th class="px-6 py-4 font-bold">Total Nilai</th>
                            <th class="px-6 py-4 font-bold">Direset Oleh & Alasan</th>
                            <th class="px-6 py-4 font-bold text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 align-top">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-bold text-gray-900">{{ $log->created_at->format('d M Y') }}</span>
                                    <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                        {{ $log->created_at->format('H:i:s') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 text-sm">{{ $log->order->order_number ?? 'Order #' . $log->order_id }}</span>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200">
                                            Status: {{ $log->previous_status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-800 text-sm">{{ $log->order->user->name ?? 'User Terhapus' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $log->order->branch->name ?? '-' }}</p>
                                    @if ($log->previousHandledBy)
                                        <div class="text-[11px] text-gray-600 mt-1">
                                            Kasir Awal: <span class="font-medium text-gray-800">{{ $log->previousHandledBy->name }}</span>
                                        </div>
                                    @endif
                                    @if ($log->newHandledBy && $log->new_handled_by !== $log->previous_handled_by)
                                        <div class="text-[11px] text-blue-600 mt-0.5">
                                            Dialihkan ke: <span class="font-bold">{{ $log->newHandledBy->name }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        @if ($log->previous_accurate_invoice_no)
                                            <div class="flex items-center gap-1.5 text-xs font-mono text-gray-800">
                                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded">SI</span>
                                                <span>{{ $log->previous_accurate_invoice_no }}</span>
                                            </div>
                                        @endif
                                        @if ($log->previous_accurate_receipt_no)
                                            <div class="flex items-center gap-1.5 text-xs font-mono text-gray-800">
                                                <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded">CR</span>
                                                <span>{{ $log->previous_accurate_receipt_no }}</span>
                                            </div>
                                        @endif
                                        @if ($log->previous_accurate_so_number)
                                            <div class="flex items-center gap-1.5 text-xs font-mono text-gray-800">
                                                <span class="px-1.5 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded">SO</span>
                                                <span>{{ $log->previous_accurate_so_number }}</span>
                                            </div>
                                        @endif
                                        @if (!$log->previous_accurate_invoice_no && !$log->previous_accurate_receipt_no && !$log->previous_accurate_so_number)
                                            <span class="text-xs text-gray-400 italic">Tidak ada dokumen Accurate</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-emerald-600">
                                        Rp {{ number_format($log->previous_grand_total, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-xs font-bold text-gray-900">{{ $log->resetBy->name ?? 'Admin' }}</p>
                                    <p class="text-xs text-gray-600 mt-1 bg-amber-50/70 p-2 rounded-lg border border-amber-100/60 leading-relaxed line-clamp-2" title="{{ $log->reason }}">
                                        "{{ $log->reason }}"
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="viewLogDetail({{ $log->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-all border border-gray-200">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Snapshot
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-gray-900">Belum ada riwayat reset</h3>
                                    <p class="mt-1 text-sm text-gray-500">Transaksi yang di-reset ke draft akan tercatat di sini secara otomatis.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- MODAL GANTI KASIR PENANGGUNG JAWAB (STANDALONE) --}}
    @if ($showChangeCashierModal && $selectedOrderForCashier)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
                @click.outside="$wire.closeChangeCashierModal()">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-center text-neutral-900 mb-1">Ganti Kasir Penanggung Jawab</h3>
                    <p class="text-xs text-center text-neutral-500 mb-5">Ubah staf/kasir yang bertanggung jawab atas transaksi ini.</p>

                    {{-- Order Summary --}}
                    <div class="bg-gray-50 rounded-xl p-3.5 mb-4 text-xs space-y-1.5 border border-gray-100">
                        <div class="flex justify-between">
                            <span class="text-gray-500">No. Transaksi</span>
                            <span class="font-bold text-gray-900">{{ $selectedOrderForCashier->order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Customer</span>
                            <span class="font-bold text-gray-800">{{ $selectedOrderForCashier->user->name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Cabang</span>
                            <span class="font-semibold text-gray-800">{{ $selectedOrderForCashier->branch->name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Kasir Saat Ini</span>
                            <span class="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200/60">
                                {{ $selectedOrderForCashier->handledBy->name ?? 'Belum Ditentukan' }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase mb-2">Pilih Kasir Baru</label>
                        <select wire:model="newHandledById"
                            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all text-sm bg-white">
                            <option value="">-- Pilih Kasir / Staf --</option>
                            @foreach ($cashiers as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name }} ({{ $c->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('newHandledById')
                            <span class="text-xs text-red-500 font-medium mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="p-4 bg-neutral-50 border-t border-neutral-100 flex gap-3">
                    <button wire:click="closeChangeCashierModal"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-neutral-600 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 transition-all">
                        Batal
                    </button>
                    <button wire:click="updateCashier" wire:loading.attr="disabled"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-sm shadow-blue-600/20 disabled:opacity-50">
                        <span wire:loading.remove wire:target="updateCashier">Simpan Perubahan</span>
                        <span wire:loading wire:target="updateCashier">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL RESET KE DRAFT --}}
    @if ($showDirectCancelModal && $selectedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden"
                @click.outside="$wire.closeDirectCancelModal()">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-amber-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-center text-neutral-900 mb-2">Kembalikan ke Draft</h3>
                    <p class="text-sm text-center text-neutral-500 mb-5">Transaksi akan dikembalikan ke status Draft.
                    </p>

                    {{-- Order Detail Summary --}}
                    <div class="bg-gray-50 rounded-xl p-4 mb-4 text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-500">No. Transaksi</span>
                            <span class="font-bold text-gray-900">{{ $selectedOrder->order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Customer</span>
                            <span class="font-bold text-gray-900">{{ $selectedOrder->user->name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total</span>
                            <span class="font-bold text-emerald-600">Rp {{ number_format($selectedOrder->grand_total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Kasir Saat Ini</span>
                            <span class="font-bold text-gray-800">{{ $selectedOrder->handledBy->name ?? 'Belum Ditentukan' }}</span>
                        </div>
                        @if ($selectedOrder->accurate_invoice_no)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Invoice Accurate</span>
                                <span class="font-mono text-xs text-gray-700">{{ $selectedOrder->accurate_invoice_no }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Impact Warning --}}
                    <div class="bg-red-50 border border-red-100 rounded-xl p-3 mb-4">
                        <p class="text-xs font-bold text-red-700 mb-1">Yang akan dihapus & dicatat ke History:</p>
                        <ul class="text-xs text-red-600 space-y-0.5 list-disc list-inside">
                            <li>Faktur Penjualan & Penerimaan Penjualan di Accurate</li>
                            <li>Data pembayaran ({{ $selectedOrder->payments->count() }} record)</li>
                            <li>Nomor Invoice & Receipt Accurate</li>
                        </ul>
                    </div>

                    {{-- Optional Reassign Cashier on Reset --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-neutral-700 uppercase mb-1.5">Alihkan Kasir Penanggung Jawab (Opsional)</label>
                        <select wire:model="reassignCashierId"
                            class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all text-xs bg-white">
                            <option value="">-- Tetap Kasir Saat Ini ({{ $selectedOrder->handledBy->name ?? 'Belum Ditentukan' }}) --</option>
                            @foreach ($cashiers as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name }} ({{ $c->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase mb-2">Alasan Reset ke Draft</label>
                        <textarea wire:model="directCancelReason" rows="2.5"
                            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all resize-none text-sm"
                            placeholder="Contoh: Salah input nominal, ganti kasir shift, customer ganti pesanan..."></textarea>
                        @error('directCancelReason')
                            <span class="text-xs text-red-500 font-medium mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="p-4 bg-neutral-50 border-t border-neutral-100 flex gap-3">
                    <button wire:click="closeDirectCancelModal"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-neutral-600 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 transition-all">
                        Batal
                    </button>
                    <button wire:click="directCancellation" wire:loading.attr="disabled"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 transition-all shadow-sm shadow-amber-600/20 disabled:opacity-50">
                        <span wire:loading.remove wire:target="directCancellation">Kembalikan ke Draft</span>
                        <span wire:loading wire:target="directCancellation">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DETAIL SNAPSHOT HISTORY --}}
    @if ($showHistoryDetailModal && $selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden"
                @click.outside="$wire.closeHistoryDetailModal()">
                
                {{-- Modal Header --}}
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Snapshot Dokumen Sebelumnya</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Catatan data transaksi sebelum di-reset ke status Draft</p>
                    </div>
                    <button wire:click="closeHistoryDetailModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 overflow-y-auto space-y-6 flex-1 text-sm">
                    {{-- General Info Card --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl">
                        <div>
                            <span class="text-xs text-gray-500 block">No. Transaksi</span>
                            <span class="font-bold text-gray-900">{{ $selectedLog->order->order_number ?? 'Order #' . $selectedLog->order_id }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Customer</span>
                            <span class="font-bold text-gray-900">{{ $selectedLog->order->user->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Status Sebelum Reset</span>
                            <span class="font-bold text-amber-600">{{ $selectedLog->previous_status }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Total Nilai</span>
                            <span class="font-bold text-emerald-600">Rp {{ number_format($selectedLog->previous_grand_total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Cashier and Admin Info --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3 bg-blue-50/70 border border-blue-100 rounded-xl text-xs space-y-1">
                            <span class="font-bold text-blue-800 uppercase block">Informasi Kasir</span>
                            <div>Kasir Awal: <strong>{{ $selectedLog->previousHandledBy->name ?? 'Belum Ditentukan' }}</strong></div>
                            @if ($selectedLog->newHandledBy && $selectedLog->new_handled_by !== $selectedLog->previous_handled_by)
                                <div class="text-blue-700">Dialihkan ke: <strong>{{ $selectedLog->newHandledBy->name }}</strong></div>
                            @endif
                        </div>
                        <div class="p-3 bg-amber-50/70 border border-amber-100 rounded-xl text-xs space-y-1">
                            <span class="font-bold text-amber-800 uppercase block">Admin Reset</span>
                            <div>Direset Oleh: <strong>{{ $selectedLog->resetBy->name ?? 'Admin' }}</strong></div>
                            <div class="text-gray-500">{{ $selectedLog->created_at->format('d M Y H:i:s') }}</div>
                        </div>
                    </div>

                    {{-- Admin Reset Reason --}}
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200/60">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider block mb-1">Alasan Reset</span>
                        <p class="text-sm text-gray-800 font-medium italic">"{{ $selectedLog->reason }}"</p>
                    </div>

                    {{-- Dokumen Accurate Section --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Dokumen Accurate yang Pernah Dibuat
                        </h4>
                        @if (!empty($selectedLog->previous_accurate_docs_snapshot) && count($selectedLog->previous_accurate_docs_snapshot) > 0)
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase">
                                        <tr>
                                            <th class="px-4 py-2.5 font-bold">Tipe Dokumen</th>
                                            <th class="px-4 py-2.5 font-bold">No. Dokumen</th>
                                            <th class="px-4 py-2.5 font-bold">Accurate ID</th>
                                            <th class="px-4 py-2.5 font-bold">Nominal</th>
                                            <th class="px-4 py-2.5 font-bold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($selectedLog->previous_accurate_docs_snapshot as $doc)
                                            <tr>
                                                <td class="px-4 py-2.5 font-bold text-gray-800">{{ $doc['doc_type'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5 font-mono text-gray-700">{{ $doc['doc_number'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5 font-mono text-gray-500">{{ $doc['accurate_id'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5 font-bold text-emerald-600">Rp {{ number_format($doc['amount'] ?? 0, 0, ',', '.') }}</td>
                                                <td class="px-4 py-2.5">
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded {{ ($doc['status'] ?? '') === 'SUCCESS' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                                        {{ $doc['status'] ?? '-' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-3 bg-gray-50 rounded-xl text-xs text-gray-500 text-center">
                                Tidak ada log dokumen Accurate individual tersimpan. 
                                @if ($selectedLog->previous_accurate_invoice_no || $selectedLog->previous_accurate_receipt_no)
                                    (No. Invoice: {{ $selectedLog->previous_accurate_invoice_no ?? '-' }}, No. Receipt: {{ $selectedLog->previous_accurate_receipt_no ?? '-' }})
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Data Pembayaran Sebelumnya Section --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Data Pembayaran yang Pernah Dihapus
                        </h4>
                        @if (!empty($selectedLog->previous_payments_snapshot) && count($selectedLog->previous_payments_snapshot) > 0)
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase">
                                        <tr>
                                            <th class="px-4 py-2.5 font-bold">Metode Pembayaran</th>
                                            <th class="px-4 py-2.5 font-bold">Nominal</th>
                                            <th class="px-4 py-2.5 font-bold">No. Kontrak / Ref</th>
                                            <th class="px-4 py-2.5 font-bold">Waktu Bayar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($selectedLog->previous_payments_snapshot as $pm)
                                            <tr>
                                                <td class="px-4 py-2.5 font-bold text-gray-800">{{ $pm['payment_method_name'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5 font-bold text-emerald-600">Rp {{ number_format($pm['amount'] ?? 0, 0, ',', '.') }}</td>
                                                <td class="px-4 py-2.5 font-mono text-gray-600">{{ $pm['no_kontrak'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5 text-gray-500">{{ $pm['paid_at'] ?? $pm['created_at'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-3 bg-gray-50 rounded-xl text-xs text-gray-500 text-center">
                                Tidak ada data pembayaran yang tersimpan dalam snapshot.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button wire:click="closeHistoryDetailModal"
                        class="px-5 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
