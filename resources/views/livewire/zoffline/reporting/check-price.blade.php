<div>
    <div class="px-4 mt-5 pt-12 pb-24 md:px-6 md:pt-16 max-w-7xl mx-auto space-y-6 md:space-y-8 animate-fade-in-up">

        {{-- Header Section --}}
        <div class="border-b border-gray-200 pb-6">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Cek Harga Dasar</h1>
                <p class="text-gray-500 font-medium mt-1">Cari harga beli (Buy Price) perangkat dari sistem Accurate.</p>
            </div>
        </div>

        {{-- Filters Section --}}
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-5 shadow-md border border-gray-100">
            <div class="flex flex-col gap-4 w-full">
                {{-- Dropdown Filters di atas --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full">
                    <select wire:model.live="filterBrandName"
                        class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-gray-700 font-semibold transition-all">
                        <option value="">Semua Merek</option>
                        @foreach ($availableBrands as $brand)
                            <option value="{{ $brand }}">{{ $brand }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterCategoryName"
                        class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-gray-700 font-semibold transition-all">
                        <option value="">Semua Kategori</option>
                        @foreach ($availableCategories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterProyek"
                        class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl px-4 py-3.5 text-sm focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-gray-700 font-semibold transition-all">
                        <option value="">Semua Proyek</option>
                        @foreach ($availableProyeks as $proyek)
                            <option value="{{ $proyek }}">{{ $proyek }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search Bar --}}
                <div class="relative w-full">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari SKU atau nama perangkat..."
                        class="w-full pl-12 pr-4 py-3.5 bg-gray-50/50 border border-gray-200 hover:border-gray-300 rounded-2xl text-gray-800 text-[15px] focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all font-medium placeholder-gray-400">
                </div>
            </div>

            <div wire:loading class="w-full mt-3">
                <div
                    class="flex items-center justify-center gap-2 text-teal-600 text-sm font-bold bg-teal-50 rounded-xl py-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Memuat data...
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-md border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50/80 border-b border-gray-200/60 text-xs text-gray-500 font-black uppercase tracking-widest">
                            <th class="p-5">SKU (No. Item)</th>
                            <th class="p-5">Nama Barang</th>
                            <th class="p-5">Merek</th>
                            <th class="p-5">Kategori</th>
                            <th class="p-5">Proyek</th>
                            <th class="p-5 text-right">Harga Dasar Beli</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/80">
                        @forelse($devices as $device)
                            <tr class="hover:bg-teal-50/40 transition-colors duration-200">
                                <td class="p-5">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600 font-mono">
                                        {{ $device->item_no }}
                                    </span>
                                </td>
                                <td class="p-5 min-w-[200px]">
                                    <p class="font-bold text-gray-900 text-[15px] leading-snug">{{ $device->name }}</p>
                                </td>
                                <td class="p-5">
                                    <span
                                        class="text-gray-600 font-semibold text-sm">{{ $device->brandName ?: '-' }}</span>
                                </td>
                                <td class="p-5">
                                    <span
                                        class="text-gray-600 font-semibold text-sm">{{ $device->categoryName ?: '-' }}</span>
                                </td>
                                <td class="p-5">
                                    <span
                                        class="inline-flex px-2.5 py-1 bg-violet-50 text-violet-700 border border-violet-100 rounded-lg text-xs font-bold">
                                        {{ $device->proyek ?: '-' }}
                                    </span>
                                </td>
                                <td class="p-5 text-right">
                                    <p class="font-black text-emerald-600 text-lg tabular-nums">
                                        Rp {{ number_format($device->buy_price ?? 0, 0, ',', '.') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div
                                            class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <p class="font-bold text-gray-900 text-lg">Tidak ada perangkat ditemukan</p>
                                        <p class="text-sm text-gray-500 mt-1">Coba sesuaikan pencarian atau filter Anda.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($devices->hasPages())
                <div class="p-5 border-t border-gray-100 bg-gray-50/50">
                    {{ $devices->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
