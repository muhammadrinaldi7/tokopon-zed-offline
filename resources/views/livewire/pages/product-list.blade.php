<div class="bg-gray-50 min-h-screen pb-20">
    {{-- Hero Banner Section --}}
    <div class="bg-[#4E44DB] text-white pt-16 pb-24 relative overflow-hidden">
        {{-- Abstract background shapes --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-10">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white blur-3xl"></div>
            <div class="absolute bottom-0 left-10 w-72 h-72 rounded-full bg-blue-300 blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 relative z-10 flex flex-col md:flex-row items-center justify-between">
            <div class="w-full md:w-1/2 space-y-6">
                <span class="bg-white/20 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-md border border-white/20 tracking-wider">
                    PILIHAN TERBAIK
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">
                    Katalog <span class="text-blue-300">Smartphone</span><br>Terlengkap & Bergaransi
                </h1>
                <p class="text-blue-100 text-lg leading-relaxed max-w-md">
                    Temukan device impianmu hari ini dengan harga super kompetitif. Jaminan 100% original.
                </p>
                <div class="relative max-w-md mt-4">
                    <input wire:model.live.debounce.500ms="search" type="text" placeholder="Cari iPhone, Samsung, Xiaomi..." class="w-full pl-5 pr-12 py-4 rounded-2xl bg-white/10 text-white placeholder-blue-200 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white/20 backdrop-blur-md transition-all shadow-lg text-sm">
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="w-full md:w-1/2 mt-12 md:mt-0 flex justify-end">
                <div class="relative w-72 h-72 lg:w-96 lg:h-96">
                    <div class="absolute inset-0 bg-linear-to-tr from-blue-400 to-[#1e1494] rounded-full blur-2xl opacity-60 animate-pulse hidden md:block"></div>
                    {{-- Dummy Placeholder Image for Hero --}}
                    <div class="relative z-10 w-full h-full bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl flex items-center justify-center -rotate-3 hover:rotate-0 transition-transform duration-500">
                        <span class="text-white/50 font-bold text-xl uppercase tracking-widest text-center px-4">
                            Your Gadget<br>Here
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 -mt-8 relative z-20">
        {{-- Kategori Terpopuler --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white">
            <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#4E44DB]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                Kategori Terpopuler
            </h2>
            
            <div class="flex flex-wrap gap-4">
                <button wire:click="$set('selectedCategory', null)" 
                    class="flex flex-col items-center gap-2 min-w-[80px] group transition-transform hover:-translate-y-1">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-sm border-2 transition-all 
                        {{ $selectedCategory === null ? 'bg-[#4E44DB] border-[#4E44DB] text-white' : 'bg-gray-50 border-gray-100 text-gray-400 group-hover:border-[#4E44DB]/30 group-hover:text-[#4E44DB]' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <span class="text-xs font-semibold {{ $selectedCategory === null ? 'text-[#4E44DB]' : 'text-gray-600' }}">Semua</span>
                </button>

                @foreach($categories as $category)
                    <button wire:click="selectCategory({{ $category->id }})" 
                        class="flex flex-col items-center gap-2 min-w-[80px] group transition-transform hover:-translate-y-1">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-sm border-2 transition-all 
                            {{ $selectedCategory == $category->id ? 'bg-[#4E44DB] border-[#4E44DB] text-white' : 'bg-gray-50 border-gray-100 text-gray-400 group-hover:border-[#4E44DB]/30 group-hover:text-[#4E44DB]' }}">
                            @if($category->icon)
                                <i class="{{ $category->icon }} text-xl"></i>
                            @else
                                <span class="font-bold text-lg">{{ substr($category->name, 0, 1) }}</span>
                            @endif
                        </div>
                        <span class="text-xs font-semibold {{ $selectedCategory == $category->id ? 'text-[#4E44DB]' : 'text-gray-600' }}">
                            {{ $category->name }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Main Content: Produk Terbaru --}}
    <div class="max-w-7xl mx-auto px-6 mt-16">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-800">
                @if($selectedCategory)
                    {{ $categories->find($selectedCategory)->name ?? 'Kategori' }}
                @elseif($search)
                    Pencarian: "{{ $search }}"
                @else
                    Produk Terbaru
                @endif
            </h2>
            <div class="flex items-center gap-2 text-sm text-gray-500 font-medium bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ $products->total() }} Produk Ditemukan
            </div>
        </div>

        @if($products->isEmpty())
            <div class="bg-white rounded-3xl p-16 text-center shadow-xs border border-gray-100">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Produk Tidak Ditemukan</h3>
                <p class="text-gray-500">Coba gunakan kata kunci lain atau pilih kategori yang berbeda.</p>
                <button wire:click="$set('search', '')" class="mt-6 text-[#4E44DB] font-semibold hover:underline border border-[#4E44DB]/20 bg-[#4E44DB]/5 px-4 py-2 rounded-xl">
                    Hapus Pencarian
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <a href="{{ route('products.show', $product) }}" wire:navigate
                        class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group flex flex-col h-full">
                        {{-- Product Image --}}
                        <div class="relative w-full aspect-4/3 bg-gray-50 flex items-center justify-center overflow-hidden">
                            <div class="absolute inset-0 bg-linear-to-b from-transparent to-black/5 z-10"></div>
                            @php
                                $imageUrl = $product->getFirstMediaUrl('cover', 'thumb')
                                    ?: $product->getFirstMediaUrl('gallery', 'thumb')
                                    ?: $product->getFirstMediaUrl('cover')
                                    ?: $product->getFirstMediaUrl('gallery');
                            @endphp
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <svg class="w-16 h-16 text-gray-200 group-hover:scale-110 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            @endif
                            
                            {{-- Badges --}}
                            <div class="absolute top-4 left-4 z-20 flex flex-col gap-2">
                                @if($product->brand)
                                    <span class="bg-white/90 backdrop-blur-sm text-gray-800 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider shadow-sm">
                                        {{ $product->brand->name }}
                                    </span>
                                @endif
                            </div>
                            @if($product->has_active_accurate)
                                <div class="absolute top-4 right-4 z-20">
                                    <span class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        ERZAP
                                    </span>
                                </div>
                            @endif
                            @if($product->is_second)
                                <div class="absolute top-4 left-4 z-20">
                                    <span class="bg-amber-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm">
                                        SECOND
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Product Info --}}
                        <div class="p-5 flex flex-col flex-1">
                            <span class="text-xs font-semibold text-[#4E44DB] mb-1 truncate">
                                {{ $product->category?->name ?? 'Kategori Umum' }}
                            </span>
                            <h3 class="font-bold text-gray-800 text-lg leading-tight mb-2 line-clamp-2 group-hover:text-[#4E44DB] transition-colors">
                                {{ $product->name }}
                            </h3>
                            
                            <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-0.5">Mulai Dari</p>
                                    <p class="font-black text-gray-900 text-lg">
                                        Rp {{ number_format($product->starting_price ?? 0, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 flex items-center justify-center group-hover:bg-[#4E44DB] group-hover:text-white transition-colors shadow-sm">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($products->hasPages())
                <div class="mt-12 flex justify-center">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
