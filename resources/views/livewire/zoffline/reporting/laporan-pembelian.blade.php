<div>
    <div class="px-4 pt-12 pb-24 md:px-6 md:pt-16 max-w-7xl mx-auto space-y-6 md:space-y-8 animate-fade-in-up">
        
        {{-- Header Section --}}
        <div class="border-b border-gray-200 pb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Laporan Pembelian</h1>
                <p class="text-gray-500 font-medium mt-1">Data pembelian perangkat dari pelanggan (Tukar Tambah & Jual HP).</p>
            </div>
            <button wire:click="exportXls" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-all shadow-sm disabled:opacity-70">
                <svg wire:loading.remove wire:target="exportXls" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <svg wire:loading wire:target="exportXls" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="exportXls">Export XLS</span>
                <span wire:loading wire:target="exportXls">Mengekspor...</span>
            </button>
        </div>

        {{-- Filters Section --}}
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-5 shadow-md border border-gray-100">
            <div class="flex flex-col gap-4 w-full">
                {{-- Dropdown Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 w-full">
                    <input type="date" wire:model.live="filterStartDate"
                        class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-gray-700 font-semibold transition-all"
                        placeholder="Tanggal Mulai">

                    <input type="date" wire:model.live="filterEndDate"
                        class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-gray-700 font-semibold transition-all"
                        placeholder="Tanggal Selesai">

                    <select wire:model.live="filterBranchId" class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-gray-700 font-semibold transition-all">
                        <option value="">Semua Cabang</option>
                        @foreach($availableBranches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterStatus" class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-gray-700 font-semibold transition-all">
                        <option value="">Semua Status</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="PENDING_APPROVAL">Pending Approval</option>
                        <option value="PAYING">Paying</option>
                        <option value="CANCELLED">Cancelled</option>
                        <option value="REJECTED">Rejected</option>
                    </select>
                </div>

                {{-- Search Bar --}}
                <div class="relative w-full">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No. Invoice atau Model HP..."
                        class="w-full pl-12 pr-4 py-3.5 bg-gray-50/50 border border-gray-200 hover:border-gray-300 rounded-2xl text-gray-800 text-[15px] focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-medium placeholder-gray-400">
                </div>
            </div>

            <div wire:loading class="w-full mt-3">
                <div class="flex items-center justify-center gap-2 text-emerald-600 text-sm font-bold bg-emerald-50 rounded-xl py-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Memuat data...
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-md border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200/60 text-xs text-gray-500 font-black uppercase tracking-widest">
                            <th class="p-5">Tanggal</th>
                            <th class="p-5">No. Invoice</th>
                            <th class="p-5">Merek & Model HP</th>
                            <th class="p-5">Handled By</th>
                            <th class="p-5">Customer</th>
                            <th class="p-5 text-center">Status</th>
                            <th class="p-5 text-right">Harga Beli</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/80">
                        @forelse($purchases as $item)
                            <tr class="hover:bg-emerald-50/40 transition-colors duration-200">
                                <td class="p-5 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900">{{ $item->created_at->format('d M Y') }}</span>
                                        <span class="text-xs text-gray-500 font-medium">{{ $item->created_at->format('H:i') }}</span>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600 font-mono">
                                        {{ $item->invoice_number ?: '-' }}
                                    </span>
                                </td>
                                <td class="p-5 min-w-[200px]">
                                    <p class="font-bold text-gray-900 text-[15px] leading-snug">{{ $item->phone_model }}</p>
                                    <p class="text-xs text-gray-500 font-medium mt-0.5">{{ $item->phone_brand }}</p>
                                </td>
                                <td class="p-5">
                                    <span class="text-sm font-semibold text-gray-700">{{ $item->handledBy->name ?? '-' }}</span>
                                    @if($item->branch)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $item->branch->name }}</p>
                                    @endif
                                </td>
                                <td class="p-5">
                                    <span class="text-sm font-semibold text-gray-700">{{ $item->user->name ?? 'Tamu' }}</span>
                                </td>
                                <td class="p-5 text-center">
                                    @if($item->status === 'COMPLETED')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">COMPLETED</span>
                                    @elseif($item->status === 'CANCELLED' || $item->status === 'REJECTED')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">{{ $item->status }}</span>
                                    @elseif($item->status === 'PENDING_APPROVAL')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">WAITING APPROVAL</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td class="p-5 text-right">
                                    <p class="font-black text-emerald-600 text-[15px] tabular-nums whitespace-nowrap">
                                        Rp {{ number_format($item->appraised_value ?? 0, 0, ',', '.') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="font-bold text-gray-900 text-lg">Tidak ada data pembelian</p>
                                        <p class="text-sm text-gray-500 mt-1">Belum ada transaksi pembelian HP yang cocok dengan filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($purchases->hasPages())
            <div class="p-5 border-t border-gray-100 bg-gray-50/50">
                {{ $purchases->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
