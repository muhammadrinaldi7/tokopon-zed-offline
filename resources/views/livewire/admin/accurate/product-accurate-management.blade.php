<div>
    {{-- Header Section --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Produk Accurate</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola dan sinkronisasi data master produk dari Accurate Online.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="startSync" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 bg-[#1c69d4] hover:bg-[#1556b0] text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                <svg wire:loading wire:target="startSync" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"
                    fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>

                <span wire:loading.remove wire:target="startSync">
                    Sinkronisasi Accurate
                </span>
                <span wire:loading wire:target="startSync">
                    Memulai...
                </span>

            </button>
            @if ($isSyncing)
                <div
                    class="mt-4 p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-between animate-pulse">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-indigo-900">Menarik Data dari Accurate...</h4>
                            <p class="text-xs text-indigo-700 mt-0.5">Memproses Halaman ke-{{ $syncCurrentPage }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-black text-indigo-600">{{ $syncImportedCount }}</span>
                        <span class="text-xs text-indigo-500 font-medium block uppercase tracking-wider">Item
                            Masuk</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabs & Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div class="flex flex-col sm:flex-row items-center justify-between border-b border-gray-100">
            {{-- Tabs --}}
            <div class="flex w-full sm:w-auto">
                <button wire:click="$set('activeTab', 'syihab')"
                    class="flex-1 sm:flex-none px-6 py-4 text-sm font-semibold transition-colors border-b-2 {{ $activeTab === 'syihab' ? 'border-[#1c69d4] text-[#1c69d4]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Produk Baru (Syihab)
                </button>
                <button wire:click="$set('activeTab', 'second')"
                    class="flex-1 sm:flex-none px-6 py-4 text-sm font-semibold transition-colors border-b-2 {{ $activeTab === 'second' ? 'border-[#1c69d4] text-[#1c69d4]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Produk Bekas (Second)
                </button>
            </div>

            {{-- Search Bar --}}
            <div class="p-3 sm:py-0 sm:pr-4 w-full sm:w-72">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4] transition-shadow"
                        placeholder="Cari SKU atau Nama Barang...">
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Accurate ID /
                            SKU</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Barang
                        </th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">
                            Harga Dasar</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">
                            Harga Modal</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">
                            Stok</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">
                            Terakhir Sinkron</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900">{{ $product->item_no ?? '-' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 mb-1">ID: {{ $product->accurate_id }}</div>
                                <div class="flex items-center gap-2 mt-1">
                                    @if ($product->product_variants_count == 0)
                                        <button wire:click="generateVariantLocally({{ $product->id }})"
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                            Generate Variant
                                        </button>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800">
                                            Belum Dibuat
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800">
                                            Selesai
                                        </span>
                                    @endif
                                    
                                    <button wire:click="syncSerialNumber({{ $product->accurate_id }})"
                                        class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                        Sync Serial Number
                                    </button>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-medium text-gray-800">
                                {{ $product->name }}
                            </td>
                            <td class="py-4 px-6 text-right font-medium text-gray-900">
                                Rp {{ number_format($product->base_price, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-right font-medium text-gray-900">
                                Rp {{ number_format($product->base_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if ($product->stock > 0)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $product->stock }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Habis
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right text-xs text-gray-500">
                                {{ $product->updated_at->diffForHumans() }}
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ $product->updated_at->format('d/m/Y H:i') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <p class="text-gray-500 font-medium">Belum ada data dari Accurate</p>
                                <p class="text-sm text-gray-400 mt-1">Klik tombol Sinkronisasi untuk menarik data dari
                                    database {{ $activeTab === 'syihab' ? 'Baru' : 'Bekas' }}.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($products->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
