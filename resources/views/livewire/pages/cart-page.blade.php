<div class="max-w-7xl mx-auto p-2  md:p-6 min-h-screen">
    <div class="flex gap-2">
        <a href="/"
            class="bg-neutral-500 hover:bg-neutral-600 transition-colors text-white px-3 flex justify-center items-center rounded-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="size-6 md:size-8 rotate-180">
                <path fill-rule="evenodd"
                    d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                    clip-rule="evenodd" />
            </svg>
        </a>
        <div
            class="w-full flex gap-4  items-center bg-linear-to-r from-[#0097FF] via-[#4E44DB] to-[#013559] py-3 px-6 rounded-md shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-white">
                <path
                    d="M2.25 2.25a.75.75 0 0 0 0 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 0 0-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 0 0 0-1.5H5.378A2.25 2.25 0 0 1 7.5 15h11.218a.75.75 0 0 0 .674-.421 60.358 60.358 0 0 0 2.96-7.228.75.75 0 0 0-.525-.965A60.864 60.864 0 0 0 5.68 4.509l-.232-.867A1.875 1.875 0 0 0 3.636 2.25H2.25ZM3.75 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM16.5 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" />
            </svg>

            <div class="flex justify-between items-center w-full">
                <h1 class="text-white text-xl md:text-4xl font-bold">Keranjang Belanja</h1>
                <p class="text-white text-lg font-bold">
                    @if ($totalItems > 0)
                        {{ $totalItems }}
                    @else
                        0
                    @endif
                </p>
            </div>
        </div>
    </div>


    <div class="mt-8 relative z-20">
        @if ($items->isEmpty())
            {{-- Empty Cart State --}}
            <div class="bg-white rounded-3xl p-8 text-center shadow-xl border border-gray-100">
                <div class="w-65 h-auto  flex items-center justify-center mx-auto mb-2">
                    <img src="{{ asset('assets/png/cart.png') }}" alt="">
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Keranjang Belanjamu Kosong</h3>
                <p class="text-gray-500 mb-4 max-w-sm mx-auto text-xs">Yuk mulai belanja dan temukan smartphone
                    impianmu!</p>
                <a href="{{ route('buy-mobile') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold text-white bg-[#4E44DB] rounded-2xl shadow-lg shadow-[#4E44DB]/30 hover:bg-[#3d35b8] hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Mulai Belanja
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Cart Items --}}
                <div class="lg:col-span-2 space-y-4">
                    {{-- Header --}}
                    <div
                        class="bg-white/80 backdrop-blur-xl rounded-2xl px-6 py-4 shadow-sm border border-white flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-600">{{ $items->count() }} Produk</span>
                        <button wire:click="clearCart" wire:confirm="Hapus semua item dari keranjang?"
                            class="text-xs font-semibold text-red-500 hover:text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">
                            Hapus Semua
                        </button>
                    </div>

                    {{-- Item Cards --}}
                    @foreach ($items as $item)
                        @php
                            $variant = $item->productVariant;
                            $product = $variant->product;
                            $imageUrl =
                                $product->getFirstMediaUrl('cover', 'thumb') ?:
                                $product->getFirstMediaUrl('gallery', 'thumb') ?:
                                $product->getFirstMediaUrl('cover') ?:
                                $product->getFirstMediaUrl('gallery');
                        @endphp
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow"
                            wire:key="cart-item-{{ $item->id }}">
                            <div class="flex gap-4 p-5">
                                {{-- Image --}}
                                <div class="w-24 h-24 md:w-28 md:h-28 rounded-xl bg-gray-50 overflow-hidden shrink-0">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                            class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-200" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 min-w-0 flex flex-col">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($product->brand)
                                                <span
                                                    class="text-[10px] font-bold text-[#4E44DB] uppercase tracking-wider">{{ $product->brand->name }}</span>
                                            @endif
                                            <h3
                                                class="font-bold text-gray-800 text-sm md:text-base leading-tight line-clamp-2">
                                                {{ $product->name }}
                                            </h3>
                                            <div class="flex flex-wrap gap-1.5 mt-1.5">
                                                @if ($variant->condition)
                                                    <span
                                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-gray-100 text-gray-600">{{ $variant->condition }}</span>
                                                @endif
                                                @if ($variant->color)
                                                    <span
                                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-blue-50 text-blue-600">{{ $variant->color }}</span>
                                                @endif
                                                @if ($variant->storage)
                                                    <span
                                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-purple-50 text-purple-600">{{ $variant->storage }}</span>
                                                @endif
                                                @if ($variant->ram)
                                                    <span
                                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600">{{ $variant->ram }}
                                                        RAM</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Remove Button --}}
                                        <button wire:click="removeItem({{ $item->id }})"
                                            class="text-gray-300 hover:text-red-500 hover:bg-red-50 p-1.5 rounded-lg transition shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Price + Qty --}}
                                    <div class="mt-auto pt-3 flex items-center justify-between">
                                        <p class="font-black text-gray-900 text-base md:text-lg">
                                            Rp {{ number_format($variant->price, 0, ',', '.') }}
                                        </p>

                                        {{-- Qty Controls --}}
                                        <div
                                            class="flex items-center gap-0 bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                                            <button wire:click="decrementQty({{ $item->id }})"
                                                class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-[#4E44DB] transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                @if ($item->qty <= 1) disabled @endif>
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" d="M5 12h14" />
                                                </svg>
                                            </button>
                                            <span
                                                class="w-10 text-center text-sm font-bold text-gray-800 select-none">{{ $item->qty }}</span>
                                            <button wire:click="incrementQty({{ $item->id }})"
                                                class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-[#4E44DB] transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                @if ($item->qty >= $variant->stock) disabled @endif>
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" d="M12 5v14m-7-7h14" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Stock warning --}}
                                    @if ($variant->stock <= 3 && $variant->stock > 0)
                                        <p
                                            class="text-[10px] font-semibold text-amber-500 mt-1.5 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Sisa {{ $variant->stock }} unit
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Order Summary Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-[88px]">
                        <h3 class="font-bold text-gray-800 text-lg mb-5">Ringkasan Belanja</h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-gray-500">
                                <span>Total Harga ({{ $totalItems }} item)</span>
                                <span class="font-semibold text-gray-700">Rp
                                    {{ number_format($totalPrice, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 mt-5 pt-5">
                            <div class="flex justify-between items-center mb-5">
                                <span class="font-bold text-gray-800">Total</span>
                                <span class="font-black text-xl text-[#4E44DB]">Rp
                                    {{ number_format($totalPrice, 0, ',', '.') }}</span>
                            </div>

                            <a href="{{ route('checkout') }}" wire:navigate
                                class="w-full py-3.5 bg-[#4E44DB] text-white font-bold rounded-2xl shadow-lg shadow-[#4E44DB]/25 hover:bg-[#3d35b8] hover:-translate-y-0.5 transition-all text-sm flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                                Checkout
                            </a>

                            <p class="text-[10px] text-gray-400 text-center mt-3">
                                Pembayaran aman & terpercaya
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
