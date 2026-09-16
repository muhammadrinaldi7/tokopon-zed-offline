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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 w-full">
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

                    <select wire:model.live="filterSalesId" class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-gray-700 font-semibold transition-all">
                        <option value="">Semua Sales</option>
                        @foreach($availableSales as $sales)
                            <option value="{{ $sales->id }}">{{ $sales->name }} ({{ $sales->employee_no ?? '-' }})</option>
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No. Invoice, Model HP, IMEI, Sales, atau Frontliner..."
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
                            <th class="p-5">Tenaga Penjual (Sales)</th>
                            <th class="p-5">Customer</th>
                            <th class="p-5 text-center">Status</th>
                            <th class="p-5 text-right">Harga Beli</th>
                            <th class="p-5 text-center">Deskripsi</th>
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
                                    @if($item->salesBy)
                                        <span class="text-sm font-semibold text-indigo-600 block">{{ $item->salesBy->name }}</span>
                                        <span class="text-xs text-gray-500 font-mono">{{ $item->salesBy->employee_no ?? '-' }}</span>
                                    @else
                                        <span class="text-xs text-gray-400 italic">-</span>
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
                                <td class="p-5 text-center whitespace-nowrap">
                                    <button type="button" wire:click="showDescription({{ $item->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 hover:text-emerald-800 text-xs font-bold rounded-xl border border-emerald-200/80 transition-all shadow-2xs hover:shadow-xs active:scale-95 group"
                                        title="Lihat Deskripsi Pembelian">
                                        <svg class="w-4 h-4 text-emerald-600 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span>Lihat Desc</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-12 text-center">
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

    {{-- Modal Detail Deskripsi Pembelian --}}
    @if ($showDescModal && $selectedPurchase)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-200"
             x-data
             @keydown.escape.window="$wire.closeDescModal()">
            <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden border border-gray-100 animate-in zoom-in-95 duration-200"
                 @click.outside="$wire.closeDescModal()">
                {{-- Header Modal --}}
                <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 text-white flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center shrink-0 border border-white/20">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-100">Deskripsi & Detail Pembelian</span>
                                <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-mono font-bold bg-white/20 text-white backdrop-blur-xs">
                                    {{ $selectedPurchase->invoice_number ?: ('SPL-' . $selectedPurchase->id) }}
                                </span>
                            </div>
                            <h2 class="text-xl font-black tracking-tight mt-0.5 text-white">
                                {{ $selectedPurchase->phone_brand }} {{ $selectedPurchase->phone_model }}
                            </h2>
                        </div>
                    </div>
                    <button type="button" wire:click="closeDescModal"
                        class="text-white/70 hover:text-white p-2 rounded-full hover:bg-white/10 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                    
                    {{-- 1. Kartu Utama: Deskripsi Kondisi & Catatan Minus --}}
                    <div class="bg-emerald-50/50 border border-emerald-200/80 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-emerald-900 font-black text-sm">
                                <span class="p-1.5 bg-emerald-600 text-white rounded-lg">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </span>
                                <span>Deskripsi Kondisi / Catatan Fisik (Minus)</span>
                            </div>
                            @if($selectedPurchase->minus_desc)
                                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full">
                                    Catatan Tersedia
                                </span>
                            @else
                                <span class="text-[11px] font-bold text-emerald-800 bg-emerald-200 px-2.5 py-0.5 rounded-full">
                                    Mulus / Tanpa Minus
                                </span>
                            @endif
                        </div>

                        @if($selectedPurchase->minus_desc)
                            {{-- Parsing potongan minus atau tampilkan format rapi --}}
                            @php
                                $minusParts = array_filter(array_map('trim', explode('|', $selectedPurchase->minus_desc)));
                            @endphp
                            @if(count($minusParts) > 1)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                    @foreach($minusParts as $part)
                                        <div class="flex items-start gap-2 bg-white p-3 rounded-xl border border-emerald-100 shadow-2xs">
                                            <span class="text-emerald-500 font-bold mt-0.5">•</span>
                                            <span class="text-xs text-gray-800 font-medium leading-relaxed">{{ $part }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-white p-4 rounded-xl border border-emerald-100 shadow-2xs">
                                    <p class="text-xs text-gray-800 leading-relaxed whitespace-pre-line font-medium">
                                        {{ $selectedPurchase->minus_desc }}
                                    </p>
                                </div>
                            @endif
                        @else
                            <div class="bg-white p-4 rounded-xl border border-emerald-100 shadow-2xs text-center">
                                <p class="text-xs text-emerald-800 font-medium">
                                    Tidak ada catatan minus fisik. Perangkat dibeli dalam kondisi normal/mulus.
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- 2. Metadata Grid Transaksi & Identitas --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50/80 p-4 rounded-2xl border border-gray-200/60 text-xs">
                        <div>
                            <span class="text-gray-400 block font-semibold text-[11px] uppercase">IMEI / SN</span>
                            <span class="font-mono font-bold text-gray-900 block mt-0.5">{{ $selectedPurchase->imei ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-semibold text-[11px] uppercase">Kapasitas</span>
                            <span class="font-semibold text-gray-800 block mt-0.5">
                                {{ $selectedPurchase->phone_ram ? $selectedPurchase->phone_ram . ' / ' : '' }}{{ $selectedPurchase->phone_storage ?: '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-semibold text-[11px] uppercase">Status</span>
                            <div class="mt-0.5">
                                @if($selectedPurchase->status === 'COMPLETED')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">COMPLETED</span>
                                @elseif($selectedPurchase->status === 'CANCELLED' || $selectedPurchase->status === 'REJECTED')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">{{ $selectedPurchase->status }}</span>
                                @elseif($selectedPurchase->status === 'PENDING_APPROVAL')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">WAITING APPROVAL</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">{{ $selectedPurchase->status }}</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-semibold text-[11px] uppercase">Tanggal & Jam</span>
                            <span class="font-semibold text-gray-800 block mt-0.5">{{ $selectedPurchase->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    </div>

                    {{-- 3. Detail Customer & Petugas --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        {{-- Frontliner / Handled By --}}
                        <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-semibold block text-[11px] uppercase">Handled By (Kasir/FL)</span>
                            <p class="font-bold text-gray-900 mt-1">{{ optional($selectedPurchase->handledBy)->name ?? '-' }}</p>
                            <p class="text-gray-500 text-[11px] mt-0.5">{{ optional($selectedPurchase->branch)->name ?? 'Semua Cabang' }}</p>
                        </div>

                        {{-- Tenaga Penjual / Sales --}}
                        <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-semibold block text-[11px] uppercase">Tenaga Penjual (Sales)</span>
                            @if($selectedPurchase->salesBy)
                                <p class="font-bold text-indigo-600 mt-1">{{ $selectedPurchase->salesBy->name }}</p>
                                <p class="text-gray-500 font-mono text-[11px] mt-0.5">{{ $selectedPurchase->salesBy->employee_no ?? '-' }}</p>
                            @else
                                <p class="text-gray-400 italic mt-1">-</p>
                            @endif
                        </div>

                        {{-- Customer & Rekening --}}
                        <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-semibold block text-[11px] uppercase">Customer & Rekening</span>
                            <p class="font-bold text-gray-900 mt-1">{{ optional($selectedPurchase->user)->name ?? 'Tamu' }}</p>
                            @php
                                $bName = $selectedPurchase->bank_name ?: ($selectedPurchase->user?->bankAccounts?->first()?->bank_name);
                                $bNo = $selectedPurchase->bank_account_number ?: ($selectedPurchase->user?->bankAccounts?->first()?->account_number);
                            @endphp
                            @if($bName && $bNo)
                                <p class="text-gray-600 font-mono text-[11px] mt-0.5">{{ $bName }} - {{ $bNo }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- 4. Finansial & Penyesuaian Harga (Nego) --}}
                    <div class="bg-gradient-to-br from-gray-50 to-emerald-50/30 p-4 rounded-2xl border border-gray-200/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Nilai Beli / Kesepakatan Akhir</span>
                            <p class="text-2xl font-black text-emerald-600 tabular-nums mt-0.5">
                                Rp {{ number_format($selectedPurchase->appraised_value ?? 0, 0, ',', '.') }}
                            </p>
                            @if($selectedPurchase->is_price_adjusted && $selectedPurchase->original_appraised_value)
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    Harga Sistem Awal: <span class="line-through">Rp {{ number_format($selectedPurchase->original_appraised_value, 0, ',', '.') }}</span>
                                </p>
                            @endif
                        </div>

                        @if($selectedPurchase->price_adjustment_reason || $selectedPurchase->reject_reason)
                            <div class="bg-white p-3 rounded-xl border border-amber-200 text-xs max-w-sm">
                                @if($selectedPurchase->price_adjustment_reason)
                                    <span class="text-amber-800 font-bold block mb-0.5">Alasan Penyesuaian Harga:</span>
                                    <span class="text-gray-700">{{ $selectedPurchase->price_adjustment_reason }}</span>
                                @endif
                                @if($selectedPurchase->reject_reason)
                                    <span class="text-rose-800 font-bold block mb-0.5 mt-1">Alasan Penolakan:</span>
                                    <span class="text-gray-700">{{ $selectedPurchase->reject_reason }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- 5. Hasil QC Fisik (Device Inspection) jika ada --}}
                    @php
                        $inspections = $selectedPurchase->inspections()->latest()->get();
                    @endphp
                    @if($inspections->isNotEmpty())
                        <div class="border border-gray-200 rounded-2xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Hasil Pemeriksaan QC Kelayakan
                                </h4>
                                @foreach($inspections->take(1) as $insp)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $insp->verdict === 'pass' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        QC VERDICT: {{ strtoupper($insp->verdict) }}
                                    </span>
                                @endforeach
                            </div>

                            @foreach($inspections->take(1) as $insp)
                                @if($insp->checklist_results && is_array($insp->checklist_results))
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[11px]">
                                        @foreach($insp->checklist_results as $chk)
                                            <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 border border-gray-100">
                                                <span class="font-medium text-gray-700 truncate mr-2">{{ $chk['name'] ?? '-' }}</span>
                                                @if(($chk['type'] ?? '') === 'boolean')
                                                    @if($chk['value'] === true || $chk['value'] === '1' || $chk['value'] === 1)
                                                        <span class="text-emerald-600 font-bold shrink-0">✓ Normal</span>
                                                    @else
                                                        <span class="text-rose-600 font-bold shrink-0">✗ Minus</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-600 font-semibold shrink-0">{{ $chk['value'] ?? '-' }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if($insp->inspector_notes)
                                    <p class="text-xs text-gray-600 bg-gray-50 p-2.5 rounded-xl border border-gray-100 italic">
                                        <span class="font-bold text-gray-700 not-italic">Catatan Inspektor:</span> {{ $insp->inspector_notes }}
                                    </p>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- 6. Dokumentasi Foto Fisik jika ada --}}
                    @php
                        $photos = $selectedPurchase->getMedia('photos');
                    @endphp
                    @if($photos && $photos->count() > 0)
                        <div class="border border-gray-200 rounded-2xl p-4 space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Foto Fisik Perangkat ({{ $photos->count() }} Foto)
                            </h4>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                                @foreach($photos as $photo)
                                    <a href="{{ $photo->getUrl() }}" target="_blank"
                                       class="group relative aspect-square rounded-xl overflow-hidden border border-gray-200 hover:border-emerald-500 shadow-2xs transition-all block">
                                        <img src="{{ $photo->getUrl() }}" alt="Foto Fisik" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                        <span class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold">
                                            Buka Zoom
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Footer Modal --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('sell-phone.show', $selectedPurchase) }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3.5 py-2 rounded-xl transition-all">
                        <span>Buka Halaman Detail Transaksi</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>

                    <button type="button" wire:click="closeDescModal"
                        class="px-5 py-2 rounded-xl bg-gray-900 hover:bg-gray-800 text-white text-xs font-bold shadow-sm transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
