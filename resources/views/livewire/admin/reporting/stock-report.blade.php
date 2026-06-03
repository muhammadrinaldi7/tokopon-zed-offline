<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Stok</h1>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi stok barang beserta nilainya</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <button wire:click="exportCsv" class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Item Fisik</p>
            <h3 class="text-xl font-black text-gray-800">{{ number_format($summary['total_items']) }} <span class="text-sm font-medium text-gray-400">Pcs</span></h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Nilai Modal</p>
            <h3 class="text-xl font-bold text-red-500">Rp {{ number_format($summary['total_cost'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50 rounded-full opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Potensi Penjualan</p>
            <h3 class="text-xl font-black text-[#1c69d4]">Rp {{ number_format($summary['total_potential_revenue'], 0, ',', '.') }}</h3>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h3 class="font-bold text-gray-700 text-sm">Daftar Stok Produk</h3>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <input type="date" wire:model.live="filterDate" class="border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-2 px-3 bg-white" title="Filter Tanggal Tarik Stok">
                </div>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari SKU / Nama Produk..." 
                        class="w-64 sm:w-80 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
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
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('sku')">
                            SKU & Kategori
                            @if($sortField === 'sku')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('name')">
                            Nama Produk
                            @if($sortField === 'name')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('stock')">
                            Stok & Gudang
                            @if($sortField === 'stock')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold text-center cursor-pointer hover:bg-gray-50" wire:click="sortBy('sync_date')">
                            Tanggal Tarik Stok
                            @if($sortField === 'sync_date')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('base_cost')">
                            Harga Beli
                            @if($sortField === 'base_cost')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('base_price')">
                            Harga Jual
                            @if($sortField === 'base_price')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold text-center cursor-pointer hover:bg-gray-50" wire:click="sortBy('age_days')">
                            Umur
                            @if($sortField === 'age_days')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($stocks as $item)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3">
                                <p class="text-xs font-semibold text-gray-800">{{ $item['sku'] }}</p>
                                <span class="inline-block mt-1 px-1.5 py-0.5 {{ $item['category'] == 'Baru' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }} rounded text-[10px] font-bold">
                                    {{ $item['category'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-xs font-bold text-gray-800">{{ $item['name'] }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5">{{ $item['color'] }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-sm font-black {{ $item['stock'] > 0 ? 'text-green-600' : 'text-red-500' }}">{{ $item['stock'] }} Pcs</p>
                                <p class="text-[11px] font-bold text-gray-500 mt-0.5 truncate">
                                    <span class="inline-block w-2 h-2 rounded-full bg-[#1c69d4] mr-1"></span>
                                    {{ $item['warehouse_name'] }}
                                </p>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <p class="text-xs font-medium text-gray-700">{{ $item['sync_datetime'] }}</p>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <p class="text-xs font-bold text-gray-700">Rp {{ number_format($item['base_cost'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <p class="text-sm font-black text-[#1c69d4]">Rp {{ number_format($item['base_price'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <p class="text-xs font-medium {{ $item['age_days'] > 90 ? 'text-red-500' : 'text-gray-700' }}">
                                    {{ $item['age_days'] }} Hari
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-400 text-sm">
                                Tidak ada data stok yang ditemukan.
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
