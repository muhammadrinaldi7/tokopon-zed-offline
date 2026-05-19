<div class="bg-white min-h-screen pb-20" x-data="{ justAdded: @entangle('added') }"
    x-effect="if(justAdded) { setTimeout(() => { justAdded = false; $wire.set('added', false) }, 2500) }">

    {{-- Breadcrumb --}}
    <div class="max-w-7xl mx-auto px-6 pt-6">
        <nav class="flex items-center gap-2 text-sm text-gray-400">
            <a href="/" wire:navigate class="hover:text-[#4E44DB] transition">Home</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('products.index') }}" wire:navigate class="hover:text-[#4E44DB] transition">Produk</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-700 font-medium truncate max-w-xs">{{ $product->name }}</span>
        </nav>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-6 mt-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 xl:gap-16">

            {{-- LEFT: Image Gallery --}}
            <div class="space-y-4">
                {{-- Main Image --}}
                <div
                    class="relative aspect-square bg-gray-50 rounded-3xl overflow-hidden border border-gray-100 flex items-center justify-center">
                    @if ($images->isNotEmpty())
                        <img src="{{ $images[$activeImageIndex]->getUrl() }}" alt="{{ $product->name }}"
                            class="w-full h-full object-contain p-4 transition-opacity duration-300"
                            wire:key="main-img-{{ $activeImageIndex }}">
                    @else
                        <div class="text-center">
                            <svg class="w-24 h-24 text-gray-200 mx-auto" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <p class="text-gray-300 text-sm mt-2">Belum ada gambar</p>
                        </div>
                    @endif

                    {{-- Brand badge --}}
                    @if ($product->brand)
                        <div class="absolute top-4 left-4">
                            <span
                                class="bg-white/90 backdrop-blur-sm text-gray-800 text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm uppercase tracking-wider">
                                {{ $product->brand->name }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Thumbnail Strip --}}
                @if ($images->count() > 1)
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach ($images as $index => $media)
                            <button wire:click="setActiveImage({{ $index }})"
                                class="shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 transition-all {{ $activeImageIndex === $index ? 'border-[#4E44DB] ring-2 ring-[#4E44DB]/20' : 'border-gray-100 hover:border-gray-300' }}">
                                <img src="{{ $media->getUrl() }}" alt="" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- RIGHT: Product Info --}}
            <div class="space-y-6">
                {{-- Category & Name --}}
                <div>
                    @if ($product->category)
                        <span class="text-xs font-bold text-[#4E44DB] uppercase tracking-widest">
                            {{ $product->category->name }}
                        </span>
                    @endif
                    <div class="flex items-center gap-3 mt-1">
                        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
                            {{ $product->name }}
                        </h1>
                        @if ($product->is_second)
                            <span
                                class="bg-amber-500 text-white text-xs font-bold px-3 py-1 rounded-lg shadow-sm shrink-0">SECOND</span>
                        @endif
                    </div>
                    @if ($product->reviews->count() > 0)
                        <div class="flex items-center gap-2 mt-3">
                            <div class="flex items-center text-amber-400">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                    </path>
                                </svg>
                                <span
                                    class="ml-1 text-sm font-bold text-gray-800">{{ number_format($product->average_rating, 1) }}</span>
                            </div>
                            <span class="text-gray-300">•</span>
                            <span class="text-sm text-gray-500 font-medium pb-px">{{ $product->reviews->count() }}
                                Ulasan</span>
                        </div>
                    @endif
                </div>

                {{-- Price --}}
                <div class="bg-linear-to-r from-[#4E44DB]/5 to-transparent p-5 rounded-2xl">
                    @if ($selectedVariant)
                        <p class="text-sm text-gray-500 font-medium">Harga</p>
                        <p class="text-3xl font-black text-[#4E44DB]">
                            Rp {{ number_format($selectedVariant->price, 0, ',', '.') }}
                        </p>
                        <div class="flex items-center gap-3 mt-2">
                            <span
                                class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $selectedVariant->stock > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                {{ $selectedVariant->stock > 0 ? 'Stok: ' . $selectedVariant->stock : 'Habis' }}
                            </span>
                            @if ($selectedVariant->condition)
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600">
                                    {{ $selectedVariant->condition }}
                                </span>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500 font-medium">Mulai dari</p>
                        <p class="text-3xl font-black text-gray-900">
                            Rp {{ number_format($product->starting_price ?? 0, 0, ',', '.') }}
                        </p>
                    @endif
                </div>

                {{-- Variant Selector --}}
                @if ($product->variants->count() > 0)
                    <div>
                        <h3 class="text-sm font-bold text-gray-700 mb-3">Pilih Varian</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->variants as $variant)
                                <button wire:click="selectVariant({{ $variant->id }})" @class([
                                    'px-4 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all',
                                    'border-[#4E44DB] bg-[#4E44DB]/5 text-[#4E44DB] ring-2 ring-[#4E44DB]/20' =>
                                        $selectedVariantId === $variant->id,
                                    'border-gray-200 text-gray-700 hover:border-gray-400' =>
                                        $selectedVariantId !== $variant->id && $variant->stock > 0,
                                    'border-gray-100 text-gray-300 cursor-not-allowed line-through' =>
                                        $variant->stock <= 0,
                                ])
                                    @if ($variant->stock <= 0) disabled @endif>
                                    <span class="flex items-center justify-center gap-1.5">
                                        @if ($url = $variant->getFirstMediaUrl('variant_image', 'thumb'))
                                            <img src="{{ $url }}"
                                                class="w-5 h-5 rounded-sm object-cover border border-gray-200"
                                                alt="Varian {{ $variant->color }}">
                                        @endif
                                        <span>
                                            {{ $variant->ram ? $variant->ram . ' / ' : '' }}{{ $variant->storage ?? '' }}
                                            {{ $variant->color ? '- ' . $variant->color : '' }}
                                        </span>
                                    </span>
                                    <span class="block text-[10px] font-medium mt-0.5 opacity-70">
                                        Rp {{ number_format($variant->price, 0, ',', '.') }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Quantity & Add to Cart --}}
                @if ($selectedVariant && $selectedVariant->stock > 0)
                    <div class="flex items-center gap-4 pt-2">
                        {{-- Qty Selector --}}
                        <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden">
                            <button wire:click="decrementQty"
                                class="px-3.5 py-2.5 text-gray-500 hover:bg-gray-50 transition font-bold text-lg">−</button>
                            <span
                                class="px-5 py-2.5 font-bold text-gray-800 border-x-2 border-gray-200 min-w-[50px] text-center">{{ $quantity }}</span>
                            <button wire:click="incrementQty"
                                class="px-3.5 py-2.5 text-gray-500 hover:bg-gray-50 transition font-bold text-lg">+</button>
                        </div>

                        {{-- Add to Cart Button --}}
                        <button wire:click="addToCart"
                            class="flex-1 bg-[#4E44DB] text-white py-3.5 rounded-xl font-bold text-base hover:bg-[#3f36b8] active:scale-[0.98] transition-all shadow-lg shadow-[#4E44DB]/25 flex items-center justify-center gap-2"
                            wire:loading.attr="disabled">
                            @if ($added)
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Ditambahkan!
                            @else
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                                <span wire:loading.remove wire:target="addToCart">Tambah ke Keranjang</span>
                                <span wire:loading wire:target="addToCart">Menambahkan...</span>
                            @endif
                        </button>
                    </div>

                    {{-- Trade In Button --}}
                    <div class="mt-4">
                        <a href="{{ route('trade-in', ['product' => $product->slug]) }}" wire:navigate
                            class="w-full bg-emerald-500 text-white py-3.5 rounded-xl font-bold text-base hover:bg-emerald-600 active:scale-[0.98] transition-all shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Tukar Tambah dengan Produk Ini
                        </a>
                    </div>
                @elseif(!$selectedVariant)
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
                        <p class="text-amber-700 font-semibold text-sm">Pilih varian terlebih dahulu</p>
                    </div>
                @else
                    <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-center">
                        <p class="text-rose-700 font-semibold text-sm">Stok varian ini habis</p>
                    </div>
                @endif

                {{-- Description --}}
                @if ($product->description)
                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">Deskripsi Produk</h3>
                        <div class="text-gray-600 text-sm leading-relaxed prose prose-sm max-w-none">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- Specifications --}}
                @if ($product->specifications && count($product->specifications) > 0)
                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">Spesifikasi</h3>
                        <div class="bg-gray-50 rounded-2xl overflow-hidden">
                            <table class="w-full text-sm">
                                <tbody>
                                    @foreach ($product->specifications as $key => $value)
                                        <tr class="border-b border-gray-100 last:border-b-0">
                                            <td class="px-5 py-3 text-gray-500 font-medium w-1/3 bg-gray-50/80">
                                                {{ $key }}
                                            </td>
                                            <td class="px-5 py-3 text-gray-800 font-semibold">
                                                {{ $value }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Reviews Section --}}
    <div class="max-w-7xl mx-auto px-6 mt-16">
        <div class="border-t border-gray-100 pt-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Ulasan Pelanggan</h2>

            @if ($product->reviews->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($product->reviews as $review)
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                            <div class="flex items-center gap-3 mb-4">
                                <div
                                    class="w-10 h-10 rounded-full bg-[#4E44DB] text-white flex items-center justify-center font-bold text-sm shadow-md shadow-[#4E44DB]/20">
                                    {{ strtoupper(substr($review->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm">
                                        {{ $review->user->name ?? 'Pengguna' }}</h4>
                                    <p class="text-xs text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center text-amber-400 mb-3">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'fill-current' : 'text-gray-200 fill-current' }}"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                        </path>
                                    </svg>
                                @endfor
                            </div>
                            @if ($review->comment)
                                <p class="text-gray-600 text-sm leading-relaxed">
                                    "{{ $review->comment }}"
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 rounded-2xl p-10 text-center border border-gray-100">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <p class="text-gray-400 font-medium">Belum ada ulasan untuk produk ini.</p>
                </div>
            @endif
        </div>
    </div>
</div>
