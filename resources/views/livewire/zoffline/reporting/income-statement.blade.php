<div class="p-6 bg-[#f7f7f7] min-h-screen">
    {{-- Header & Filters --}}
    <div class="flex flex-col items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Laba Rugi</h1>
            <p class="text-sm text-gray-500 mt-1">Ringkasan laba kotor disinkronkan dengan DPP Pajak Penjualan & Modal Stok Aktif</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 w-full">
            
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari POS, SI, Pelanggan, SN..."
                    class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full placeholder-gray-400">
            </div>

            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="businessUnitFilter"
                    class="border-none text-sm font-bold text-gray-800 focus:ring-0 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="">Semua Bisnis Unit</option>
                    @foreach ($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="branchFilter"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="">Semua Cabang</option>
                    @foreach ($availableBranches as $branch)
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="dateRange"
                    class="border-none text-sm font-bold text-blue-600 focus:ring-0 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="today">Hari Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="custom">Kustom</option>
                </select>
            </div>

            @if ($dateRange === 'custom')
                <div class="md:col-span-1 flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm">
                    <input type="date" wire:model.live="startDate"
                        class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                    <span class="text-gray-400 text-sm font-bold">-</span>
                    <input type="date" wire:model.live="endDate"
                        class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                </div>
            @endif
        </div>
    </div>

    {{-- Board Utama Statistik Laba Rugi --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pendapatan Bersih (DPP)</p>
                    <p class="text-xs text-gray-400 mb-2">Revenue Bersih PPN - Beban MDR</p>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl font-black text-gray-800 mt-2">Rp {{ number_format($report['net_revenue'], 0, ',', '.') }}</h3>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total HPP (Modal)</p>
                    <p class="text-xs text-gray-400 mb-2">Modal Asli Riil SN Terjual</p>
                </div>
                <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-700 mt-2">Rp {{ number_format($report['cogs'], 0, ',', '.') }}</h3>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Laba Kotor</p>
                    <p class="text-xs text-gray-400 mb-2">Pendapatan Bersih - Total HPP</p>
                </div>
            </div>
            <h3 class="text-2xl font-black mt-2 relative z-10 {{ $report['gross_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
            </h3>
            
            <div class="absolute -right-4 -bottom-4 w-24 h-24 {{ $report['gross_profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-full opacity-60"></div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Margin Profit</p>
                    <p class="text-xs text-gray-400 mb-2">Persentase Laba Kotor</p>
                </div>
                <div class="p-2 {{ $report['margin_percentage'] >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }} rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $report['margin_percentage'] >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-black mt-2 {{ $report['margin_percentage'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ number_format($report['margin_percentage'], 2) }}%
            </h3>
        </div>
    </div>

    {{-- Detail Tabel Breakdown Laba Rugi Per Baris Item Penjualan --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center shrink-0">
            <h2 class="text-md font-bold text-gray-800">Detail Laba Rugi Per Item Transaksi</h2>
            <span class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-semibold">
                {{ count($report['items_breakdown']) }} Item Terjual
            </span>
        </div>
        
        <div class="overflow-x-auto overflow-y-auto max-h-[600px] relative">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-600 uppercase text-[10px] font-bold tracking-wider border-b border-gray-200">
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">Tanggal & Kode Order</th>
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">SKU & Deskripsi Barang</th>
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">Vendor Riil SN</th>
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 text-center shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">Qty</th>
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 text-right shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">Penjualan Bersih (DPP)</th>
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 text-right shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">HPP SN Asli</th>
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 text-right shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">Laba Kotor</th>
                        <th class="sticky top-0 z-10 bg-gray-100 px-6 py-3 text-center shadow-[inset_0_-1px_0_rgba(229,231,235,1)]">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($report['items_breakdown'] as $item)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-400 block mb-0.5">{{ \Carbon\Carbon::parse($item['tanggal_transaksi'])->format('d M Y H:i') }}</span>
                                <span class="font-semibold text-gray-800 block">{{ $item['order_number'] }}</span>
                                <span class="text-xs text-blue-600 font-mono">{{ $item['accurate_invoice_no'] ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900 block truncate max-w-xs">{{ $item['nama_produk'] }}</span>
                                <span class="text-xs text-gray-400 font-mono">SKU: {{ $item['sku'] }}</span>
                                @if($item['serial_number'])
                                    <span class="text-[11px] text-gray-500 block truncate max-w-xs" title="{{ $item['serial_number'] }}">SN: {{ $item['serial_number'] }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-700 font-medium">
                                <div class="whitespace-normal break-words max-w-[180px] leading-relaxed text-xs" title="{{ $item['vendor'] }}">
                                    {{ $item['vendor'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center font-medium text-gray-700">
                                {{ $item['qty'] }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-800">
                                Rp {{ number_format($item['revenue_item'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium {{ !$item['has_sn_record'] || $item['cogs_item'] <= 0 ? 'text-gray-400 italic' : 'text-gray-600' }}">
                                {{ !$item['has_sn_record'] || $item['cogs_item'] <= 0 ? '-' : 'Rp ' . number_format($item['cogs_item'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold {{ $item['laba_kotor_item'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                Rp {{ number_format($item['laba_kotor_item'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $item['margin_persen'] >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    {{ number_format($item['margin_persen'], 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                                Tidak ada transaksi sukses yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 shrink-0">
            {{ $report['items_breakdown']->links() }}
        </div>
    </div>
</div>