<div class="p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-500 mb-1">
                    <a href="{{ route('zoffline.reporting') }}" wire:navigate class="hover:text-blue-600 transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Pusat Laporan
                    </a>
                    <span>/</span>
                    <span class="text-neutral-700 font-medium">Laporan Pembatalan</span>
                </div>
                <h1 class="text-2xl font-bold text-neutral-800">Laporan Pembatalan Kasir</h1>
                <p class="text-sm text-neutral-500 mt-1">Pantau aktivitas pembatalan order (POS & Sales Order), performa kasir, dan status persetujuan.</p>
            </div>

            <!-- Export Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="exportExcel"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold text-sm rounded-xl transition-all shadow-sm shadow-emerald-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="exportExcel" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 4h7v5h5v11H6V4zm2 8.5l2.25 3.5L8 19.5h2l1.25-2.25 1.25 2.25h2L12.25 16 14.5 12.5h-2L11.25 14.75 10 12.5H8z"/>
                        </svg>
                        Export Excel
                    </span>
                    <span wire:loading wire:target="exportExcel" class="flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mengunduh...
                    </span>
                </button>

                <button wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl transition-all shadow-sm shadow-blue-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="exportCsv" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export CSV
                    </span>
                    <span wire:loading wire:target="exportCsv" class="flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mengunduh...
                    </span>
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-neutral-100 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Date Range -->
                <div class="flex items-center gap-2 bg-neutral-50 border border-neutral-200 rounded-xl px-3 py-1.5 shadow-sm text-sm">
                    <span class="text-neutral-500 text-xs font-semibold uppercase">Periode:</span>
                    <input type="date" wire:model.live="dateFrom" class="bg-transparent border-none focus:ring-0 text-sm py-0.5 text-neutral-800 font-medium cursor-pointer" title="Dari Tanggal">
                    <span class="text-neutral-400 text-xs font-medium">s/d</span>
                    <input type="date" wire:model.live="dateTo" class="bg-transparent border-none focus:ring-0 text-sm py-0.5 text-neutral-800 font-medium cursor-pointer" title="Sampai Tanggal">
                </div>

                <!-- Status Filter -->
                <select wire:model.live="statusFilter" class="bg-neutral-50 border border-neutral-200 rounded-xl px-3 py-2 text-sm text-neutral-700 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="APPROVED">Disetujui (Approved)</option>
                    <option value="PENDING">Menunggu (Pending)</option>
                    <option value="REJECTED">Ditolak (Rejected)</option>
                </select>

                <!-- Channel Filter -->
                <select wire:model.live="channelFilter" class="bg-neutral-50 border border-neutral-200 rounded-xl px-3 py-2 text-sm text-neutral-700 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Semua Channel</option>
                    <option value="POS">POS (Kasir Toko)</option>
                    <option value="SO">Sales Order (SO)</option>
                </select>

                @if($search || $statusFilter || $channelFilter)
                    <button wire:click="resetFilters" class="text-xs text-red-600 hover:text-red-700 font-bold px-2 py-1 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset Filter
                    </button>
                @endif
            </div>

            <!-- Search Bar -->
            <div class="relative w-full lg:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari order, kasir, accurate, customer..."
                    class="w-full pl-9 pr-4 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all placeholder:text-neutral-400">
                <div class="absolute left-3 top-2.5 text-neutral-400 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Order Batal -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Total Pengajuan</span>
                    <p class="text-3xl font-extrabold text-neutral-900 mt-2">{{ number_format($totalCancellations) }}</p>
                </div>
                <div class="flex items-center gap-2 mt-4 pt-3 border-t border-neutral-100 text-xs text-neutral-500">
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ $totalApproved }} disetujui
                    </span>
                    <span>•</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-amber-600">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>{{ $totalPending }} pending
                    </span>
                </div>
            </div>

            <!-- Nilai Transaksi Batal -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-red-500">Nilai Transaksi Batal</span>
                    <p class="text-2xl lg:text-3xl font-black text-red-600 mt-2">Rp {{ number_format($totalValue, 0, ',', '.') }}</p>
                </div>
                <div class="flex items-center gap-2 mt-4 pt-3 border-t border-neutral-100 text-xs text-neutral-500">
                    <span class="text-neutral-400">Total nilai order yang diajukan batal</span>
                </div>
            </div>

            <!-- Status Breakdown -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-neutral-100 flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Ringkasan Status</span>
                <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2">
                        <span class="text-[11px] font-bold text-emerald-700 block">Disetujui</span>
                        <span class="text-lg font-black text-emerald-800">{{ $totalApproved }}</span>
                    </div>
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-2">
                        <span class="text-[11px] font-bold text-amber-700 block">Pending</span>
                        <span class="text-lg font-black text-amber-800">{{ $totalPending }}</span>
                    </div>
                    <div class="bg-red-50 border border-red-100 rounded-xl p-2">
                        <span class="text-[11px] font-bold text-red-700 block">Ditolak</span>
                        <span class="text-lg font-black text-red-800">{{ $totalRejected }}</span>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-neutral-100 text-xs text-neutral-400 text-center">
                    Berdasarkan periode terpilih
                </div>
            </div>

            <!-- Top Kasir -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-neutral-100 flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">Top Kasir Pengaju</span>
                <div class="space-y-1.5">
                    @forelse($topCashiers as $kasir)
                        <div class="flex items-center justify-between text-xs bg-neutral-50 px-2.5 py-1.5 rounded-lg">
                            <span class="font-semibold text-neutral-800 truncate max-w-[130px]">{{ $kasir->requestedBy->name ?? 'Kasir Terhapus' }}</span>
                            <span class="bg-red-100 text-red-700 font-bold px-2 py-0.5 rounded-full text-[10px]">{{ $kasir->total }}x</span>
                        </div>
                    @empty
                        <p class="text-xs text-neutral-400 italic py-2">Tidak ada data pembatalan di rentang waktu ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
            <div class="p-4 border-b border-neutral-100 bg-neutral-50/50 flex items-center justify-between">
                <h2 class="text-base font-bold text-neutral-800">Riwayat Pengajuan Pembatalan</h2>
                <span class="text-xs font-medium text-neutral-500">Menampilkan {{ $requests->total() }} data</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Kasir & Cabang</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">No Order</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">No Accurate</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Pelanggan</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Nilai (Rp)</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Alasan</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @forelse ($requests as $req)
                            <tr class="hover:bg-neutral-50/80 transition-colors">
                                <!-- Tanggal -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-neutral-900 font-medium">
                                    {{ $req->created_at->format('d M Y') }}
                                    <span class="block text-[11px] text-neutral-400 font-normal">{{ $req->created_at->format('H:i:s') }}</span>
                                </td>

                                <!-- Kasir & Cabang -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs">
                                    <span class="font-bold text-neutral-900 block">{{ $req->requestedBy->name ?? '-' }}</span>
                                    <span class="text-[11px] text-neutral-400 font-normal">
                                        {{ $req->approvable?->branch?->name ?? ($req->requestedBy?->branch?->name ?? ($req->approvable?->shipping_address_snapshot['store'] ?? '-')) }}
                                    </span>
                                </td>

                                <!-- No Order -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs font-mono font-bold text-neutral-900">
                                    @if ($req->approvable)
                                        {{ $req->approvable->order_number }}
                                        @if ($req->approvable->order_channel === 'SO')
                                            <span class="ml-1 px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] rounded font-semibold border border-blue-200">SO</span>
                                        @else
                                            <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] rounded font-semibold border border-emerald-200">POS</span>
                                        @endif
                                    @else
                                        <span class="text-neutral-400">-</span>
                                    @endif
                                </td>

                                <!-- No Accurate -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs font-mono text-neutral-600">
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

                                <!-- Pelanggan -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs">
                                    <span class="font-semibold text-neutral-800 block">{{ $req->approvable?->user?->name ?? 'Pelanggan Umum' }}</span>
                                    <span class="text-[11px] text-neutral-400">{{ $req->approvable?->user?->profile?->phone_number ?? '-' }}</span>
                                </td>

                                <!-- Nilai Transaksi -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs font-bold text-red-600">
                                    Rp {{ number_format($req->approvable?->grand_total ?? 0, 0, ',', '.') }}
                                </td>

                                <!-- Alasan -->
                                <td class="px-5 py-4 text-xs text-neutral-600 max-w-xs" title="{{ $req->reason }}">
                                    <p class="line-clamp-2">{{ $req->reason ?? '-' }}</p>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs">
                                    @if (in_array($req->status, ['APPROVED', 'COMPLETED']))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Disetujui
                                        </span>
                                    @elseif($req->status === 'REJECTED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Ditolak
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            Menunggu
                                        </span>
                                    @endif
                                </td>

                                <!-- Diproses Oleh -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-neutral-600">
                                    @if ($req->histories->isNotEmpty())
                                        @php $lastHist = $req->histories->last(); @endphp
                                        <span class="font-semibold text-neutral-800 block">{{ $lastHist->actedBy->name ?? '-' }}</span>
                                        <span class="text-[11px] text-neutral-400">{{ $lastHist->created_at ? $lastHist->created_at->format('d M Y, H:i') : '-' }}</span>
                                        @if($lastHist->notes)
                                            <span class="block text-[10px] text-neutral-500 italic mt-0.5 truncate max-w-[150px]" title="{{ $lastHist->notes }}">"{{ $lastHist->notes }}"</span>
                                        @endif
                                    @else
                                        <span class="text-neutral-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div class="mx-auto w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-neutral-900">Tidak ada data pembatalan</h3>
                                    <p class="mt-1 text-xs text-neutral-500">Tidak ditemukan pengajuan pembatalan yang sesuai dengan filter yang dipilih.</p>
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
