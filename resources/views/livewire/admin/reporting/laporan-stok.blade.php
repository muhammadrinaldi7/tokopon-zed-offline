<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Stok (Serial Number)</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar stok barang secara detail per unit Serial Number</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-green-500 hover:bg-green-600 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Memproses...</span>
            </button>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h3 class="font-bold text-gray-700 text-sm">Daftar Serial Number</h3>
            
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                {{-- Dropdown Gudang --}}
                <div class="relative">
                    <select wire:model.live="warehouseId" class="w-full sm:w-48 pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white appearance-none cursor-pointer">
                        <option value="">Semua Gudang</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                {{-- Input Pencarian --}}
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari SN / SKU / Nama Produk..." 
                        class="w-full sm:w-80 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('serial_number')">
                            Serial Number
                            @if($sortField === 'serial_number')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('item_no')">
                            Produk (SKU)
                            @if($sortField === 'item_no')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold">Lokasi Gudang</th>
                        <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('hpp')">
                            Harga Pokok (HPP)
                            @if($sortField === 'hpp')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold">Vendor</th>
                        <th class="px-5 py-4 font-bold text-center cursor-pointer hover:bg-gray-50" wire:click="sortBy('receipt_date')">
                            Tanggal Masuk
                            @if($sortField === 'receipt_date')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold text-center cursor-pointer hover:bg-gray-50" wire:click="sortBy('status')">
                            Status
                            @if($sortField === 'status')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($stocks as $item)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3">
                                <p class="text-sm font-black text-gray-800">{{ $item->serial_number }}</p>
                                <p class="text-[10px] text-gray-400 mt-1">Dibuat: {{ $item->created_at ? $item->created_at->format('d M Y H:i') : '-' }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-xs font-bold text-gray-800 line-clamp-2" title="{{ $item->productAccurate->name ?? 'Unknown' }}">
                                    {{ $item->productAccurate->name ?? 'Unknown' }}
                                </p>
                                <p class="text-[11px] text-[#1c69d4] font-semibold mt-0.5">{{ $item->item_no }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 text-[11px] font-bold">
                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    {{ $item->warehouse->name ?? 'Belum Dialokasikan' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <p class="text-sm font-bold text-gray-700">Rp {{ number_format($item->hpp ?? 0, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-xs font-semibold text-gray-600">{{ $item->vendor->vendor_name ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <p class="text-[11px] font-semibold text-gray-700">{{ $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('d M Y') : '-' }}</p>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($item->status == 'Available')
                                    <span class="inline-block px-2 py-1 bg-green-50 text-green-600 rounded text-[10px] font-bold uppercase">Available</span>
                                @else
                                    <span class="inline-block px-2 py-1 bg-red-50 text-red-600 rounded text-[10px] font-bold uppercase">{{ $item->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-400 text-sm">
                                Tidak ada data Serial Number yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-gray-100 bg-white">
            {{ $stocks->links() }}
        </div>
    </div>
</div>
