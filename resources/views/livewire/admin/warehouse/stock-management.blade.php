<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Stok Gudang</h1>
            <p class="text-sm text-gray-500 mt-1">Lihat dan sinkronisasikan stok varian produk di setiap cabang/gudang
                langsung dari Accurate Online.</p>
        </div>

        <button wire:click="syncAllStocks" wire:loading.attr="disabled"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-[#1c69d4] hover:bg-[#1552a8] active:bg-[#0f3d82] rounded-lg transition-colors shadow-sm disabled:opacity-50">
            <svg wire:loading.remove class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg wire:loading class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span>Sync Semua Stok Halaman Ini</span>
        </button>
    </div>

    <!-- Tabs and Filters -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 pb-1">
        <div class="flex gap-2">
            <button wire:click="$set('activeTab', 'new')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-all {{ $activeTab === 'new' ? 'border-[#1c69d4] text-[#1c69d4] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Produk Baru
            </button>
            <button wire:click="$set('activeTab', 'second')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-all {{ $activeTab === 'second' ? 'border-[#1c69d4] text-[#1c69d4] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Produk Second (Bekas)
            </button>
        </div>

        <div class="w-full md:w-80">
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari SKU atau nama produk..."
                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1c69d4]/30 focus:border-[#1c69d4]">
                <span class="absolute left-3.5 top-2.5 text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">SKU / Item No</th>
                        <th class="px-6 py-4">Nama Produk / Varian</th>
                        @foreach ($warehouses as $warehouse)
                            <th class="px-6 py-4 text-center">{{ $warehouse->name }}
                                <button wire:click="syncProductPerWh('{{ $warehouse->name }}')"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#1c69d4] hover:bg-[#1c69d4]/10 rounded-lg transition-all active:scale-95 disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="syncProductPerWh('{{ $warehouse->name }}')"
                                        class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg wire:loading wire:target="syncProductPerWh('{{ $warehouse->name }}')"
                                        class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </button>
                            </th>
                        @endforeach
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    @forelse($variantsList as $variant)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-mono font-medium text-xs text-gray-600">
                                {{ $variant->sku ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">
                                    {{ $activeTab === 'second' ? $variant->secondProduct->name ?? '-' : $variant->product->name ?? '-' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    RAM: {{ $variant->ram ?? '-' }} | Storage: {{ $variant->storage ?? '-' }} | Color:
                                    {{ $variant->color ?? '-' }}
                                    @if ($activeTab === 'second')
                                        | Kondisi: <span
                                            class="px-1.5 py-0.5 text-[10px] font-semibold bg-amber-50 text-amber-700 rounded">{{ $variant->condition_desc ?? 'Bekas' }}</span>
                                    @endif
                                </div>
                            </td>
                            @foreach ($warehouses as $warehouse)
                                @php
                                    $wsRecord = $variant->warehouseStocks->firstWhere('warehouse_id', $warehouse->id);
                                    $qty = $wsRecord ? $wsRecord->stock : 0;
                                @endphp
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center justify-center font-bold px-2.5 py-1 text-xs rounded-full {{ $qty > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-500' }}">
                                        {{ $qty }}
                                    </span>
                                </td>
                            @endforeach
                            <td class="px-6 py-4 text-right">
                                <button
                                    wire:click="syncVariantStock({{ $variant->id }}, {{ $activeTab === 'second' ? 'true' : 'false' }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#1c69d4] hover:bg-[#1c69d4]/10 rounded-lg transition-all active:scale-95 disabled:opacity-50">
                                    <svg wire:loading.remove
                                        wire:target="syncVariantStock({{ $variant->id }}, {{ $activeTab === 'second' ? 'true' : 'false' }})"
                                        class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg wire:loading
                                        wire:target="syncVariantStock({{ $variant->id }}, {{ $activeTab === 'second' ? 'true' : 'false' }})"
                                        class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span>Sync</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($warehouses) + 3 }}" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <div class="font-medium text-gray-600">Tidak ada varian ditemukan</div>
                                <div class="text-xs text-gray-400 mt-1">Coba cari dengan kata kunci lain atau pilih tab
                                    produk yang berbeda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($variantsList->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $variantsList->links() }}
            </div>
        @endif
    </div>
</div>
