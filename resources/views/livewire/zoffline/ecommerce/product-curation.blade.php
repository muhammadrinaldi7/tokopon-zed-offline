<div class="p-4 sm:p-6 min-h-screen bg-neutral-100">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Kurasi & Pemantauan Produk Mobile'])

    {{-- Content Area --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Toolbar --}}
        <div class="p-5 border-b border-gray-100 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Produk Aktif di Aplikasi Mobile</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Produk yang tampil secara otomatis disaring dari gudang berstatus <em>Online Store</em> dan Unit Bisnis aktif.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-sky-50 text-sky-800 rounded-xl text-xs font-bold border border-sky-200">
                        {{ $onlineWarehouseCount }} Gudang Online Aktif
                    </span>
                </div>
            </div>

            {{-- Filter Rows --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama produk atau SKU..."
                    class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 outline-none">

                <select wire:model.live="selectedBusinessUnitId" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700">
                    <option value="">Semua Toko Mobile</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="selectedCategory" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Products Grid --}}
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                @forelse($products as $prod)
                    @php
                        $hasMedia = false;
                        $imgUrl = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
                        if ($prod->product && $prod->product->hasMedia('cover')) {
                            $imgUrl = $prod->product->getFirstMediaUrl('cover');
                            $hasMedia = true;
                        } elseif ($prod->productVariants && $prod->productVariants->isNotEmpty()) {
                            $first = $prod->productVariants->first();
                            if ($first && $first->hasMedia('variant_image')) {
                                $imgUrl = $first->getFirstMediaUrl('variant_image');
                                $hasMedia = true;
                            }
                        }
                        $onlineStock = $prod->warehouseStocks->sum('stock');
                    @endphp
                    <div class="bg-gray-50/70 border border-gray-200 rounded-2xl p-3 flex flex-col justify-between hover:bg-white hover:shadow-sm transition-all group">
                        <div>
                            {{-- Image Container --}}
                            <div class="relative w-full aspect-square bg-gray-100 rounded-xl overflow-hidden mb-2.5 border border-gray-200">
                                <img src="{{ $imgUrl }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @if($hasMedia)
                                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 bg-sky-600 text-white rounded text-[9px] font-black uppercase shadow-xs">
                                        R2 CDN
                                    </span>
                                @else
                                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 bg-amber-500 text-white rounded text-[9px] font-bold shadow-xs">
                                        Placeholder
                                    </span>
                                @endif
                            </div>

                            <span class="text-[10px] font-bold text-indigo-600 uppercase">{{ $prod->brandName ?? 'Gadget' }}</span>
                            <h4 class="text-xs font-bold text-gray-900 line-clamp-2 leading-snug mt-0.5">{{ $prod->name }}</h4>
                        </div>

                        <div class="pt-2 mt-2 border-t border-gray-200/60">
                            <div class="text-xs font-black text-gray-900">
                                Rp {{ number_format($prod->base_price, 0, ',', '.') }}
                            </div>
                            <div class="flex items-center justify-between text-[10px] mt-1 text-gray-500">
                                <span>Stok: <strong class="{{ $onlineStock > 0 ? 'text-emerald-700' : 'text-rose-600' }} font-bold">{{ $onlineStock }}</strong></span>
                                <span class="truncate max-w-[65px] font-mono font-medium">{{ $prod->businessUnit?->code }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-gray-400 text-xs">
                        Tidak ada produk yang cocok dengan pencarian dan filter Anda.
                    </div>
                @endforelse
            </div>

            {{-- Pagination Links --}}
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
