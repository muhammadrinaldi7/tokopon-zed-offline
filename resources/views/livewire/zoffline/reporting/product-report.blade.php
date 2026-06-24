<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Kinerja Produk</h1>
            <p class="text-sm text-gray-500 mt-1">Analisa penjualan SKU dan pergerakan stok</p>
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

            <div class="flex flex-wrap items-center gap-2">
                <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                    <select wire:model.live="businessUnitFilter"
                        class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 pr-6 rounded-lg cursor-pointer">
                        <option value="">Semua Unit Usaha</option>
                        @foreach(\App\Models\BusinessUnit::where('is_active', true)->get() as $bu)
                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                    <select wire:model.live="dateRange"
                        class="border-none text-sm font-bold text-blue-600 focus:ring-0 bg-transparent p-0 pr-6 rounded-lg cursor-pointer">
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="this_year">Tahun Ini</option>
                        <option value="custom">Kustom</option>
                    </select>
                </div>

                @if ($dateRange === 'custom')
                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                        <input type="date" wire:model.live="startDate"
                            class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700">
                        <span class="text-gray-400 text-sm font-bold">-</span>
                        <input type="date" wire:model.live="endDate"
                            class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700">
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div
        class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div
            class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h3 class="font-bold text-gray-700 text-sm">Daftar Produk Terjual</h3>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-auto">
                    <select wire:model.live="branchFilter"
                        class="border-gray-200 pl-10 pr-4 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5">
                        <option value="">Semua Cabang</option>
                        <option value="Banjarbaru">Banjarbaru</option>
                        <option value="Martapura">Martapura</option>
                        <option value="Premium">Premium</option>
                        <option value="Sultan Adam">Sultan Adam</option>
                        <option value="Veteran">Veteran</option>

                    </select>
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                <div class="relative w-full sm:w-auto">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari Nama / SKU Produk..."
                        class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-4 font-bold w-12 text-center">#</th>
                        <th class="px-5 py-4 font-bold">Produk (SKU & Nama)</th>
                        <th class="px-5 py-4 font-bold">Cabang</th>
                        <th class="px-5 py-4 font-bold text-center">Qty Terjual</th>
                        <th class="px-5 py-4 font-bold text-right">Gross Revenue</th>
                        <th class="px-5 py-4 font-bold text-right">Potongan</th>
                        <th class="px-5 py-4 font-bold text-right">Net Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($products as $idx => $product)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3 text-center text-xs font-bold text-gray-400">
                                {{ $products->firstItem() + $idx }}
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-sm font-bold text-gray-800">{{ $product->name }}</p>
                                <p
                                    class="text-[11px] text-gray-500 mt-0.5 font-mono bg-gray-100 px-1.5 py-0.5 rounded inline-block">
                                    {{ $product->sku }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md text-[11px] font-medium">
                                    {{ $product->branch }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-3 py-1 bg-blue-50 text-[#1c69d4] rounded-full text-xs font-black">
                                    {{ $product->total_qty }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <p class="text-xs font-bold text-gray-700">Rp
                                    {{ number_format($product->gross_revenue, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($product->total_discount > 0)
                                    <p class="text-[11px] text-red-500 font-medium">- Rp
                                        {{ number_format($product->total_discount, 0, ',', '.') }}</p>
                                @else
                                    <p class="text-[11px] text-gray-400">-</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <p class="text-sm font-black text-gray-900">Rp
                                    {{ number_format($product->net_revenue, 0, ',', '.') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-400 text-sm">
                                Tidak ada data produk yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 bg-white">
            {{ $products->links() }}
        </div>
    </div>
</div>
