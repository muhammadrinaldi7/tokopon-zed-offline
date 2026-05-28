        {{-- LEFT PANEL: Product Search & Grid --}}
        <div class="flex-1 flex flex-col overflow-hidden w-full">
            {{-- Top Bar --}}
            <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-xl font-black text-gray-900 tracking-tight">Point of Sale</h1>
                        <p class="text-xs text-gray-400">Kasir: <span
                                class="font-bold text-gray-600">{{ Auth::user()->name }}</span> •
                            {{ now()->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="openHistory"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg hover:bg-gray-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat Transaksi
                    </button>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="px-6 py-4 bg-white border-b border-gray-100 shrink-0">
                <div class="relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-[#1c69d4] focus:ring-0 text-sm font-medium transition-all"
                        placeholder="Cari produk atau SKU..." autofocus>
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="flex-1 overflow-y-auto p-6">
                @if (strlen($search) >= 2)
                    @php $results = $this->searchResults; @endphp
                    @if ($results->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                            @foreach ($results as $product)
                                <button
                                    wire:click="openVariantPicker({{ $product->id }}, {{ $product->is_second_catalog ? 'true' : 'false' }})"
                                    class="bg-white rounded-xl border border-gray-100 hover:border-[#1c69d4]/50 hover:shadow-md transition-all p-4 text-left group relative">
                                    @if ($product->is_second_catalog)
                                        <span
                                            class="absolute top-2 right-2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase z-10">Second</span>
                                    @endif
                                    <div
                                        class="aspect-square rounded-lg bg-gray-50 mb-3 overflow-hidden flex items-center justify-center">
                                        @if ($product->getFirstMediaUrl('cover'))
                                            <img src="{{ $product->getFirstMediaUrl('cover') }}"
                                                class="w-full h-full object-contain" alt="{{ $product->name }}">
                                        @else
                                            <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                        {{ $product->brand->name ?? '' }}</p>
                                    <h3
                                        class="font-bold text-gray-800 text-sm truncate mt-0.5 group-hover:text-[#1c69d4] transition-colors">
                                        {{ $product->name }}</h3>
                                    <p class="text-[#1c69d4] font-bold text-sm mt-1">Rp
                                        {{ number_format($product->starting_price ?? ($product->variants->min('price') ?? 0), 0, ',', '.') }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 mt-1">{{ $product->variants->count() }} varian
                                    </p>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <svg class="w-16 h-16 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="font-bold">Produk tidak ditemukan</p>
                            <p class="text-sm">Coba kata kunci lain atau periksa SKU</p>
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-20 text-gray-300">
                        <svg class="w-20 h-20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <p class="font-bold text-gray-400 text-lg">Ketik nama atau SKU produk</p>
                        <p class="text-sm text-gray-400">untuk memulai penjualan</p>
                    </div>
                @endif
            </div>
        </div>
