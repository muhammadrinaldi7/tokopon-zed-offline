<section id="buymobile" class="max-w-7xl mx-auto p-2 md:p-6">
    <div class="flex gap-2 ">
        <div wire:click="goBack()" class="bg-neutral-500 text-white px-3 flex justify-center items-center rounded-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="size-6 md:size-8 rotate-180">
                <path fill-rule="evenodd"
                    d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="w-full flex gap-4 items-center bg-blue-500 py-3 px-6 rounded-md">
            <img src="{{ asset('assets/png/buymobile.png') }}" class="w-5 md:w-10 h-auto" alt="">
            <h1 class="text-white text-xl md:text-4xl font-bold">Buy Mobile Phone</h1>
        </div>
    </div>
    <div class="flex gap-2 mt-4">
        {{-- Tombol Reset / All Brands --}}
        <div wire:click="setBrand(null)"
            class="cursor-pointer px-3 flex justify-center items-center rounded-md border transition-all 
            {{ is_null($selectedBrand) ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-200' : 'bg-neutral-100 text-neutral-400 border-neutral-200' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 md:size-8">
                <path
                    d="M6 12a.75.75 0 0 1-.75-.75v-7.5a.75.75 0 1 1 1.5 0v7.5A.75.75 0 0 1 6 12ZM18 12a.75.75 0 0 1-.75-.75v-7.5a.75.75 0 0 1 1.5 0v7.5A.75.75 0 0 1 18 12ZM6.75 20.25v-1.5a.75.75 0 0 0-1.5 0v1.5a.75.75 0 0 0 1.5 0ZM18.75 18.75v1.5a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 1.5 0ZM12.75 5.25v-1.5a.75.75 0 0 0-1.5 0v1.5a.75.75 0 0 0 1.5 0ZM12 21a.75.75 0 0 1-.75-.75v-7.5a.75.75 0 0 1 1.5 0v7.5A.75.75 0 0 1 12 21ZM3.75 15a2.25 2.25 0 1 0 4.5 0 2.25 2.25 0 0 0-4.5 0ZM12 11.25a2.25 2.25 0 1 1 0-4.5 2.25 2.25 0 0 1 0 4.5ZM15.75 15a2.25 2.25 0 1 0 4.5 0 2.25 2.25 0 0 0-4.5 0Z" />
            </svg>
        </div>

        {{-- List Brands --}}
        <div class="flex overflow-x-auto gap-2 md:gap-4 no-scrollbar">
            @foreach ($brands as $brand)
                @php
                    // Logika gambar: kalau Apple pakai 'iphone', sisanya pakai nama brand (huruf kecil)
                    $imageName = strtolower($brand->name) === 'apple' ? 'iphone' : strtolower($brand->name);
                @endphp

                <div wire:click="setBrand('{{ $brand->name }}')"
                    class="flex-none cursor-pointer bg-white rounded-md border w-25 md:w-35 flex items-center justify-center transition-all hover:border-blue-400 
                    {{ $selectedBrand === $brand->name ? 'border-blue-600 ring-2 ring-blue-100 shadow-md' : 'border-neutral-200' }}">

                    {{-- Panggil gambar secara dinamis --}}
                    <img src="{{ asset('assets/brand/' . $imageName . '.png') }}" class=""
                        alt="{{ $brand->name }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="flex flex-col mt-25 gap-1 md:gap-2 justify-center items-center mb-6 ">
        <h1 class=" font-bold text-lg md:text-3xl lg:text-5xl text-neutral-400">
            All <span
                class="bg-linear-to-r from-indigo-600 via-emerald-600 to-orange-500 bg-clip-text text-transparent">Products</span>
        </h1>
        <h1 class="font-semibold text-xs md:text-lg">All products are available</h1>
    </div>
    @foreach ($groupedProducts as $brandName => $items)
        <div class="mb-20">
            <div class="flex items-center gap-4 mb-6">
                <h2 class="text-base md:text-2xl font-black uppercase tracking-[0.2em] text-neutral-800">
                    {{ $brandName == 'Apple' ? 'iPhone' : $brandName }}
                </h2>
                <div class="h-px grow bg-neutral-200"></div>
            </div>
            <div class="flex gap-2 md:gap-6 overflow-x-auto no-scrollbar snap-x snap-mandatory">
                @foreach ($items as $product)
                    @php
                        $imageUrl =
                            $product->getFirstMediaUrl('cover', 'thumb') ?:
                            $product->getFirstMediaUrl('gallery', 'thumb') ?:
                            $product->getFirstMediaUrl('cover') ?:
                            $product->getFirstMediaUrl('gallery');
                    @endphp
                    <div class="shrink-0 w-40 md:w-50 lg:w-65 snap-start">
                        <div
                            class="bg-neutral-200 rounded-2xl py-5 flex items-center justify-center p-2 aspect-square relative">
                            @if ($product->is_second_catalog ?? false)
                                <span
                                    class="absolute top-2 left-2 z-10 bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-md shadow-sm">SECOND</span>
                            @endif
                            <img src="{{ $imageUrl ?: 'https://placehold.co/400x400?text=No+Image' }}"
                                class="w-full h-full object-contain" alt="">
                        </div>
                        <div class="text-center py-2">
                            <h1 class="font-semibold text-xs md:text-sm lg:text-lg line-clamp-1">{{ $product->name }}
                            </h1>
                            <h1 class="font-bold text-xs md:text-sm lg:text-lg mt-1">Rp
                                {{ number_format($product->starting_price ?? 0, 0, ',', '.') }}</h1>
                        </div>
                        <a href="{{ route('products.show', $product) }}" wire:navigate>
                            <button
                                class="text-center text-sm md:text-base py-1 md:py-2 font-bold border rounded-full w-full hover:bg-black hover:text-white transition-all">Buy
                                Now</button>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</section>
