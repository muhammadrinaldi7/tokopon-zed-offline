<div class="p-6">
    <div class="flex flex-col  items-start   gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Klaim Promo</h1>
            <p class="text-sm text-gray-500 mt-1">Gunakan laporan ini untuk menagih subsidi promo ke pihak Brand
                (Principal).</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 w-full">
            <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="businessUnitFilter"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent rounded-lg cursor-pointer w-full">
                    <option value="">Semua Unit Usaha</option>
                    @foreach (\App\Models\BusinessUnit::where('is_active', true)->get() as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="brandFilter"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent rounded-lg cursor-pointer w-full">
                    <option value="">Semua Brand</option>
                    @foreach ($availableBrands as $brand)
                        <option value="{{ $brand }}">{{ $brand }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="dateRange"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent rounded-lg cursor-pointer w-full">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="last_week">Minggu Lalu</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="last_month">Bulan Lalu</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Pilih Tanggal</option>
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
            <button wire:click="exportCsvClaim" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg wire:loading.remove wire:target="exportCsvClaim" class="w-4 h-4" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportCsvClaim" class="animate-spin w-4 h-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span wire:loading.remove wire:target="exportCsvClaim">Export CSV (Rincian Klaim)</span>
                <span wire:loading wire:target="exportCsvClaim">Memproses...</span>
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input wire:model.live.debounce.500ms="search" type="text"
                class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 sm:text-sm transition-shadow"
                placeholder="Cari No Order atau No Invoice Accurate...">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal
                            & Order</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cabang
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Produk
                            Terjual</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama
                            Promo</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ $order->order_number }}</div>
                                <div class="text-xs text-gray-500">{{ $order->created_at->format('d M Y H:i') }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">Inv: {{ $order->accurate_invoice_no ?? '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $order->shipping_address_snapshot['store'] ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    @foreach ($order->items as $item)
                                        @php
                                            $variant = $item->variant;
                                            $name =
                                                $variant?->name ??
                                                ($variant?->product?->name ?? ($item->product_name ?? 'Unknown'));
                                            $merk =
                                                $variant?->brandName ??
                                                ($variant?->accurateData?->brandName ??
                                                    ($variant?->product?->brand?->name ?? 'Unknown'));
                                        @endphp
                                        <div class="text-sm text-gray-900 flex items-center justify-between gap-4">
                                            <span>
                                                <span class="font-bold">[{{ $merk }}]</span> {{ $name }}
                                                <span class="text-gray-500 text-xs">x{{ $item->qty }}</span>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    @foreach ($order->items as $item)
                                        @foreach ($item->promos as $promo)
                                            <div class="text-sm font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded">
                                                @if(!empty($promo->pivot->serial_number))
                                                    <span class="font-bold text-xs text-purple-800">[SN: {{ $promo->pivot->serial_number }}]</span> 
                                                @endif
                                                {{ $promo->name }} 
                                                - Rp {{ number_format($promo->pivot->discount_amount, 0, ',', '.') }}
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-gray-500 font-medium">Tidak ada data klaim promo di rentang
                                        tanggal ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $orders->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </div>
</div>
