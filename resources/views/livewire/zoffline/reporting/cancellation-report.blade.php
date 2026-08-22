<div class="p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-neutral-800">Laporan Pembatalan Kasir</h1>
                <p class="text-sm text-neutral-500 mt-1">Pantau aktivitas pembatalan order (Sales Order / POS) dan
                    performa kasir.</p>
            </div>
            <div class="flex gap-4">
                <div class="flex items-center gap-2 bg-white border border-neutral-200 rounded-xl px-2 py-1 shadow-sm">
                    <input type="date" wire:model.live="dateFrom" class="border-none focus:ring-0 text-sm py-1"
                        title="Dari Tanggal">
                    <span class="text-neutral-400 text-xs font-medium">s/d</span>
                    <input type="date" wire:model.live="dateTo" class="border-none focus:ring-0 text-sm py-1"
                        title="Sampai Tanggal">
                </div>
                <button wire:click="exportExcel"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-sm rounded-xl border border-emerald-200 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Export Excel
                </button>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 flex flex-col justify-center items-start relative overflow-hidden">
                <div class="absolute right-0 top-0 p-4 opacity-5">
                    <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1">Total Order Batal</h3>
                <p class="text-3xl font-black text-neutral-900">{{ number_format($totalCancellations) }}</p>
            </div>
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 flex flex-col justify-center items-start relative overflow-hidden">
                <div class="absolute right-0 top-0 p-4 opacity-5 text-red-600">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1">Nilai Transaksi Batal</h3>
                <p class="text-3xl font-black text-red-600">Rp {{ number_format($totalValue, 0, ',', '.') }}</p>
            </div>
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 lg:col-span-2 relative overflow-hidden">
                <h3 class="text-sm font-bold text-neutral-500 mb-3">Top Kasir Terbanyak Membatalkan</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($topCashiers as $kasir)
                        <div
                            class="bg-red-50 text-red-700 px-3 py-1.5 rounded-lg text-sm font-bold border border-red-100 flex items-center">
                            {{ $kasir->requestedBy->name ?? 'Kasir Terhapus' }}
                            <span
                                class="ml-2 bg-red-200 text-red-800 px-2 py-0.5 rounded-full text-[10px]">{{ $kasir->total }}x</span>
                        </div>
                    @empty
                        <p class="text-sm text-neutral-400 italic">Tidak ada data pembatalan di rentang waktu ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
            <div class="p-4 border-b border-neutral-100 bg-neutral-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-lg font-bold text-neutral-800">Riwayat Pengajuan Pembatalan</h2>
                <div class="relative w-full sm:w-80">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kasir, no order, accurate, alasan..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors placeholder:text-neutral-400">
                    <div class="absolute left-3 top-2.5 text-neutral-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Kasir</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                No Order</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                No Accurate</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Nilai (Rp)</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Alasan</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Approved By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @forelse ($requests as $req)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 font-medium">
                                    {{ $req->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="font-bold text-neutral-900">{{ $req->requestedBy->name ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold text-neutral-900">
                                    @if ($req->approvable)
                                        {{ $req->approvable->order_number }}
                                        @if ($req->approvable->order_channel === 'SO')
                                            <span
                                                class="ml-1 px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] rounded border border-blue-200">SO</span>
                                        @else
                                            <span
                                                class="ml-1 px-1.5 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] rounded border border-emerald-200">POS</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-neutral-600">
                                    @if ($req->approvable)
                                        @if ($req->approvable->order_channel === 'SO')
                                            {{ $req->approvable->accurate_so_number ?? '-' }}
                                        @else
                                            {{ $req->approvable->accurate_invoice_no ?? '-' }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">
                                    {{ number_format($req->approvable?->grand_total ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-neutral-600 max-w-xs truncate"
                                    title="{{ $req->reason }}">
                                    {{ $req->reason ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($req->status === 'COMPLETED')
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded border border-emerald-200 text-[10px] font-bold bg-emerald-50 text-emerald-700 uppercase">
                                            ✓ Disetujui
                                        </span>
                                    @elseif($req->status === 'REJECTED')
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded border border-red-200 text-[10px] font-bold bg-red-50 text-red-700 uppercase">
                                            ✕ Ditolak
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded border border-amber-200 text-[10px] font-bold bg-amber-50 text-amber-700 uppercase">
                                            ⏳ Menunggu
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 font-medium">
                                    {{ $req->histories->last()?->actedBy->name ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div
                                        class="mx-auto w-16 h-16 bg-neutral-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-neutral-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-neutral-900">Belum ada data</h3>
                                    <p class="mt-1 text-sm text-neutral-500">Tidak ada pengajuan pembatalan di rentang
                                        waktu ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($requests->hasPages())
                <div class="p-4 border-t border-neutral-100 bg-neutral-50/50">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
