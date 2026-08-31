<div class="p-6">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Penerimaan Barang (Inbound PO)</h2>
            <p class="text-sm text-gray-500 mt-1">Pindai dan terima barang dari Purchase Order Accurate</p>
        </div>
        <button wire:click="syncPos" wire:loading.attr="disabled"
            class="px-5 py-2.5 bg-neutral-800 text-white rounded-xl shadow-sm hover:bg-neutral-900 transition-all font-semibold flex items-center gap-2">
            <span wire:loading.remove wire:target="syncPos" class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Tarik PO Terbaru
            </span>
            <span wire:loading wire:target="syncPos" class="flex items-center gap-2">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Menyinkronkan...
            </span>
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
        <div class="p-5 border-b border-neutral-100 bg-neutral-50/50">
            <div class="relative max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari No. PO atau Nama Vendor..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-neutral-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow outline-none">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-neutral-50 text-xs font-bold text-neutral-500 uppercase tracking-wider border-b border-neutral-200">
                        <th class="px-6 py-4">Tanggal PO</th>
                        <th class="px-6 py-4">Nomor PO</th>
                        <th class="px-6 py-4">Vendor</th>
                        <th class="px-6 py-4">Progress Kuantitas</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 text-sm text-neutral-700">
                    @forelse($pos as $po)
                        <tr class="hover:bg-blue-50/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-neutral-900">
                                    {{ \Carbon\Carbon::parse($po->po_date)->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-blue-600">{{ $po->po_number }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-neutral-800 line-clamp-1">
                                    {{ $po->vendor->vendor_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $ordered = $po->items->sum('quantity_ordered');
                                    $received = $po->items->sum('quantity_received');
                                    $percent = $ordered > 0 ? min(100, round(($received / $ordered) * 100)) : 0;
                                    $progressColor =
                                        $percent === 100
                                            ? 'bg-emerald-500'
                                            : ($percent > 0
                                                ? 'bg-blue-500'
                                                : 'bg-neutral-300');
                                @endphp
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 bg-neutral-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $progressColor }} rounded-full transition-all duration-500"
                                            style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-neutral-600 w-12 text-right">{{ $received }}/{{ $ordered }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($po->status === 'COMPLETED')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                                    </span>
                                @elseif($po->status === 'PARTIAL')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Parsial
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-neutral-100 text-neutral-600 border border-neutral-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Menunggu
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('zoffline.inbound.scan', $po->id) }}"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-neutral-200 text-neutral-700 rounded-lg text-sm font-bold hover:bg-neutral-50 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                                    Mulai Pindai
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                        </path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 text-neutral-400 mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-neutral-900 mb-1">Belum Ada Purchase Order</h3>
                                <p class="text-neutral-500 max-w-sm mx-auto">Silakan klik "Tarik PO Terbaru" untuk
                                    mengambil data Inbound terbaru dari sistem Accurate.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pos->hasPages())
            <div class="px-6 py-4 border-t border-neutral-100 bg-neutral-50/30">
                {{ $pos->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </div>
</div>
