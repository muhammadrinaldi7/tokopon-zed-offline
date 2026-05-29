<div class="bg-gray-100" x-data="{ showSidebar: false }">

    {{-- Bungkus layout utama dengan x-data dari Alpine.js --}}
    <div x-data="{ openCart: false }" class="relative flex h-screen overflow-hidden">

        {{-- LEFT PANEL: Product Search & Grid --}}
        <div class="flex-1 flex flex-col overflow-hidden w-full">
            {{-- Top Bar --}}
            <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-xl font-black text-gray-900 tracking-tight">ZPOS</h1>
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
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <img src="{{ asset('assets/png/searchphone.png') }}" alt="" class="w-48 h-auto mb-4">
                        <p class="font-bold text-base text-neutral-900">Cari produk atau masukkan SKU</p>
                        <p class="text-xs text-neutral-400">untuk memulai transaksi baru</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Overlay Background (Muncul saat cart dibuka di mobile) --}}
        <div x-show="openCart" x-transition.opacity x-cloak @click="openCart = false"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden">
        </div>

        {{-- ═══════════════════════════════════════════════════════════
         RIGHT PANEL: Cart, Customer & Payment (Drawer on Mobile)
    ═══════════════════════════════════════════════════════════ --}}
        <div :class="openCart ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
            class="fixed lg:static inset-y-0 right-0 z-50  w-[85%] md:w-[50%] lg:w-[35%] transform transition-transform duration-300 ease-in-out bg-white border-l border-gray-200 flex flex-col shrink-0 h-full shadow-2xl lg:shadow-none">

            {{-- Cart Header --}}
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                <h2 class="font-black text-gray-900 text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                    Keranjang
                    @if (!empty($cart))
                        <span
                            class="bg-[#1c69d4] text-white text-xs font-black px-2.5 py-0.5 rounded-full ml-1">{{ count($cart) }}</span>
                    @endif
                </h2>

                {{-- Tombol Close (Hanya tampil di mobile) --}}
                <button @click="openCart = false"
                    class="lg:hidden p-1 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-md transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto min-h-0 bg-gray-50">
                {{-- Cart Items --}}
                <div class="px-4 py-3 space-y-3 border-b border-gray-100  bg-gray-50/30">
                    @forelse($cart as $index => $item)
                        <div
                            class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm relative group transition-all duration-200 hover:shadow-md hover:border-blue-200">

                            {{-- Tombol Hapus (Muncul saat hover di Desktop) --}}
                            <button wire:click="removeFromCart({{ $index }})"
                                class="absolute top-2.5 right-2.5 text-gray-300 hover:text-rose-500 transition-all bg-white rounded-full p-1 hover:bg-rose-50 lg:opacity-0 group-hover:opacity-100 z-10">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="flex flex-col gap-3">
                                {{-- Bagian Header: Nama, Spesifikasi, & Harga Total --}}
                                <div class="flex justify-between items-start pr-8">
                                    <div class="space-y-1.5">
                                        <h4 class="font-bold text-gray-800 text-sm leading-tight">{{ $item['name'] }}
                                        </h4>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-bold uppercase tracking-wide">{{ $item['color'] }}</span>
                                            <span
                                                class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-bold uppercase tracking-wide">{{ $item['storage'] }}</span>
                                            @if ($item['is_second'] ?? false)
                                                <span
                                                    class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-600 border border-emerald-100 font-bold uppercase tracking-wide">Second</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-blue-600 text-sm whitespace-nowrap">Rp
                                            {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</p>
                                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">@ Rp
                                            {{ number_format($item['price'], 0, ',', '.') }}</p>
                                    </div>
                                </div>

                                {{-- Bagian Action: Quantity & Tombol Cek Stok --}}
                                <div class="flex items-center justify-between gap-3 pt-2 border-t border-gray-100">
                                    <div class="flex items-center gap-1">
                                        <button wire:click="decrementCartItem({{ $index }})"
                                            class="w-7 h-7 rounded-md bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition shadow-sm text-sm font-bold">−</button>
                                        <span
                                            class="w-7 text-center font-bold text-gray-800 text-xs">{{ $item['qty'] }}</span>
                                        <button wire:click="incrementCartItem({{ $index }})"
                                            class="w-7 h-7 rounded-md bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition shadow-sm text-sm font-bold">+</button>
                                    </div>

                                    {{-- TOMBOL CEK STOK (Baru ditambahkan) --}}
                                    <button wire:click="checkStock({{ $index }})"
                                        class="flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-100 transition-colors px-2.5 py-1.5 rounded-md text-[11px] font-bold shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        Cek Stok
                                    </button>
                                </div>

                                @php
                                    $snArray = array_filter($item['serial_numbers'] ?? [], function ($val) {
                                        return !empty(trim($val));
                                    });
                                    $quantity = $item['qty'] ?? 1;
                                    $isFull = count($snArray) >= $quantity;
                                    $nextIndex = count($snArray);
                                @endphp

                                {{-- Bagian Serial Number (SN) --}}
                                <div class="mt-1 space-y-2.5 bg-gray-50/50 p-2.5 rounded-lg border border-gray-100">

                                    {{-- BARIS 1: Badge SN yang sudah di-scan --}}
                                    @if (count($snArray) > 0)
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($snArray as $snIndex => $snValue)
                                                <span
                                                    class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 text-[11px] font-mono pl-2 pr-1 py-1 rounded-md shadow-sm select-none">
                                                    {{ $snValue }}
                                                    <button type="button"
                                                        wire:click="removeSerialNumber({{ $index }}, {{ $snIndex }})"
                                                        class="text-gray-400 hover:text-rose-500 font-bold w-4 h-4 flex items-center justify-center rounded hover:bg-rose-50 transition-colors focus:outline-none"
                                                        title="Hapus SN">
                                                        &times;
                                                    </button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- BARIS 2: Input & Tombol Scan --}}
                                    <div class="flex items-center gap-2">
                                        @if (!$isFull)
                                            <div class="relative flex-1">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <input type="text"
                                                    id="sn_input_{{ $index }}_{{ $nextIndex }}"
                                                    wire:change="updateSerialNumber({{ $index }}, {{ $nextIndex }}, $event.target.value)"
                                                    class="w-full bg-white border border-gray-300 rounded-md pl-7 pr-2.5 py-1.5 text-[11px] font-mono focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all placeholder-gray-400 shadow-sm"
                                                    placeholder="Scan / Ketik SN ke-{{ $nextIndex + 1 }}...">
                                            </div>

                                            <button type="button"
                                                onclick="startScanner({{ $index }}, {{ $nextIndex }})"
                                                class="shrink-0 bg-neutral-600 hover:bg-neutral-700 text-white rounded-md py-1.5 px-2 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-1"
                                                title="Scan Barcode Kamera">
                                                <svg fill="#ffffff" width="800px" height="800px" class="size-5"
                                                    viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M48.5,32A1.61,1.61,0,0,1,50,33.5v8.85Q50,47,45.5,47h-9a1.55,1.55,0,0,1,0-3.1h8.25c1.66,0,2.25-.61,2.25-2.32V33.5A1.61,1.61,0,0,1,48.5,32Zm-45,0A1.61,1.61,0,0,1,5,33.5H5v8.08c0,1.71.59,2.32,2.25,2.32H15.5a1.55,1.55,0,0,1,0,3.1h-9Q2,47,2,42.35H2V33.5A1.61,1.61,0,0,1,3.5,32ZM20.17,14c.73,0,1.33.45,1.33,1h0V37c0,.55-.6,1-1.33,1H16.83c-.73,0-1.33-.45-1.33-1h0V15c0-.55.6-1,1.33-1h3.34ZM11.5,14a1,1,0,0,1,1,1h0V37a1,1,0,0,1-1,1h-1a1,1,0,0,1-1-1h0V15a1,1,0,0,1,1-1h1Zm15,0a1,1,0,0,1,1,1h0V37a1,1,0,0,1-1,1h-1a1,1,0,0,1-1-1h0V15a1,1,0,0,1,1-1h1Zm15,0a1,1,0,0,1,1,1h0V37a1,1,0,0,1-1,1h-1a1,1,0,0,1-1-1h0V15a1,1,0,0,1,1-1h1Zm-6.33,0c.73,0,1.33.45,1.33,1h0V37c0,.55-.6,1-1.33,1H31.83c-.73,0-1.33-.45-1.33-1h0V15c0-.55.6-1,1.33-1h3.34ZM45.5,5Q50,5,50,9.65h0V18.5a1.5,1.5,0,0,1-3,0h0V10.42c0-1.71-.59-2.32-2.25-2.32H36.5a1.55,1.55,0,0,1,0-3.1h9Zm-30,0a1.55,1.55,0,0,1,0,3.1H7.25C5.59,8.1,5,8.71,5,10.42V18.5A1.61,1.61,0,0,1,3.5,20,1.61,1.61,0,0,1,2,18.5V9.65Q2,5,6.5,5Z" />
                                                </svg>
                                            </button>
                                        @else
                                            <div
                                                class="w-full bg-emerald-50 border border-emerald-200 rounded-md px-3 py-1.5 text-[11px] text-emerald-700 font-bold flex items-center justify-center gap-1.5 select-none shadow-sm">
                                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Semua SN sudah terpenuhi ({{ $quantity }}/{{ $quantity }})
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        {{-- Tampilan Kosong (Empty State) yang diperbarui --}}
                        <div
                            class="flex flex-col items-center justify-center py-10 bg-white border border-dashed border-gray-300 rounded-xl">
                            <div class="bg-gray-50 p-3 rounded-full mb-3">
                                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-500">Keranjang masih kosong</p>
                            <p class="text-xs text-gray-400 mt-1">Pilih produk dan tambahkan ke keranjang</p>
                        </div>
                    @endforelse
                </div>

                {{-- Form Section: Customer, Payments, Discount --}}
                <div class=" divide-y divide-gray-200/60 antialiased selection:bg-blue-500 selection:text-white">
                    {{-- Customer Section --}}
                    <div class="p-4 transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Customer</p>
                            @if (!$selectedCustomerId && !$isNewCustomer)
                                <button wire:click="$set('isNewCustomer', true)" wire:loading.attr="disabled"
                                    class="text-[11px] text-[#1c69d4] hover:text-blue-700 font-semibold transition flex items-center gap-0.5 disabled:opacity-50 disabled:cursor-not-allowed">

                                    {{-- Icon Plus (Akan hilang saat loading) --}}
                                    <svg wire:loading.remove wire:target="isNewCustomer" class="w-3 h-3"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>

                                    {{-- Icon Spinner (Akan muncul dan berputar saat loading) --}}
                                    <svg wire:loading wire:target="isNewCustomer"
                                        class="animate-spin w-3 h-3 text-[#1c69d4]" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>

                                    Customer Baru
                                </button>
                            @endif
                        </div>

                        @if ($selectedCustomerId)
                            {{-- Direkomendasikan mengganti query ini dengan $selectedCustomerData dari Component Livewire --}}
                            @php $customer = \App\Models\User::with('profile')->find($selectedCustomerId); @endphp
                            <div
                                class="flex items-center justify-between bg-white border border-emerald-100 shadow-sm rounded-xl p-3 transition-all hover:border-emerald-200">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs uppercase shadow-inner">
                                        {{ substr($customer->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-xs tracking-tight">
                                            {{ $customer->name }}</p>
                                        <p class="text-[11px] text-gray-500 font-medium">
                                            {{ $customer->profile->phone_number ?? $customer->email }}
                                        </p>
                                    </div>
                                </div>
                                <button wire:click="clearSelectedCustomer"
                                    class="text-gray-400 hover:text-rose-500 text-[11px] font-semibold px-2 py-1 hover:bg-rose-50 rounded-lg transition-all duration-200">Ganti</button>
                            </div>
                        @elseif($isNewCustomer)
                            <div class="bg-white border border-gray-200/80 shadow-sm rounded-xl p-3 space-y-2.5">
                                <div class="space-y-2">
                                    <input type="text" wire:model="customerName"
                                        class="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-medium focus:bg-white focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition-all placeholder:text-gray-400"
                                        placeholder="Nama Customer *">
                                    <input type="text" wire:model="customerPhone"
                                        class="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-medium focus:bg-white focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition-all placeholder:text-gray-400"
                                        placeholder="No HP *">
                                    <input type="email" wire:model="customerEmail"
                                        class="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-medium focus:bg-white focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition-all placeholder:text-gray-400"
                                        placeholder="Email (opsional)">
                                </div>
                                <button wire:click="$set('isNewCustomer', false)"
                                    class="text-[11px] text-gray-400 hover:text-gray-600 font-medium flex items-center gap-1 transition">
                                    ← Cari customer lama
                                </button>
                            </div>
                        @else
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                                    class="w-full bg-white border border-gray-200 shadow-sm rounded-lg pl-9 pr-3 py-2 text-xs focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition placeholder:text-gray-400"
                                    placeholder="Cari nama / no HP...">
                            </div>

                            @if (strlen($searchCustomer) >= 2)
                                <div
                                    class="bg-white border border-gray-200 rounded-xl shadow-xl max-h-40 overflow-y-auto divide-y divide-gray-50 mt-1.5 z-20 relative">
                                    @forelse($this->customerResults as $user)
                                        <button wire:click="selectCustomer({{ $user->id }})"
                                            class="w-full p-2.5 hover:bg-gray-50/80 text-left flex justify-between items-center transition group">
                                            <div>
                                                <p
                                                    class="font-semibold text-gray-800 text-xs group-hover:text-[#1c69d4] transition-colors">
                                                    {{ $user->name }}</p>
                                                <p class="text-[10px] text-gray-400 font-medium">
                                                    {{ $user->profile->phone_number ?? $user->email }}</p>
                                            </div>
                                            <span
                                                class="text-xs text-gray-400 group-hover:text-emerald-500 font-bold transition-all transform group-hover:translate-x-[-2px]">Pilih
                                                →</span>
                                        </button>
                                    @empty
                                        <p class="p-3 text-xs text-gray-400 text-center font-medium">Customer tidak
                                            ditemukan</p>
                                    @endforelse
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Sales Section --}}
                    <div class="p-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Tenaga Penjual
                            (Sales)</p>

                        {{-- Selected Sales Tags --}}
                        @if (count($selectedSales) > 0)
                            <div class="flex flex-wrap gap-1.5 mb-2.5">
                                @foreach ($selectedSales as $sales)
                                    <div
                                        class="flex items-center gap-1 bg-[#1c69d4]/5 text-[#1c69d4] border border-[#1c69d4]/10 rounded-lg pl-2.5 pr-1.5 py-1 shadow-sm transition hover:bg-[#1c69d4]/10">
                                        <span class="text-[11px] font-bold tracking-tight">{{ $sales['name'] }}</span>
                                        <button wire:click="removeSales({{ $sales['id'] }})"
                                            class="w-5 h-5 rounded-md flex items-center justify-center text-[#1c69d4]/60 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" wire:model.live.debounce.300ms="searchSales"
                                class="w-full bg-white border border-gray-200 shadow-sm rounded-lg pl-9 pr-3 py-2 text-xs focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition placeholder:text-gray-400"
                                placeholder="Cari nama / NIK (tambah sales)...">

                            @if (strlen($searchSales) >= 2)
                                <div
                                    class="absolute z-30 w-full bg-white border border-gray-200 rounded-xl shadow-xl max-h-40 overflow-y-auto divide-y divide-gray-50 mt-1.5">
                                    @forelse($this->salesResults as $sales)
                                        <button wire:click="selectSales({{ $sales->id }})"
                                            class="w-full p-2.5 hover:bg-gray-50 text-left flex justify-between items-center group transition">
                                            <div>
                                                <p
                                                    class="font-semibold text-gray-800 text-xs group-hover:text-[#1c69d4] transition-colors">
                                                    {{ $sales->name }}</p>
                                                <p class="text-[10px] text-gray-400 font-medium">NIK:
                                                    {{ $sales->employee_no ?? 'N/A' }}</p>
                                            </div>
                                            <span
                                                class="text-xs font-bold text-[#1c69d4] opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all">+
                                                Tambah</span>
                                        </button>
                                    @empty
                                        <p class="p-3 text-xs text-gray-400 text-center font-medium">Sales tidak
                                            ditemukan</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Payment Methods --}}
                    <div class="p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Metode Pembayaran
                            </p>
                            <button type="button" wire:click="addPaymentRow" wire:loading.attr="disabled"
                                class="text-[11px] font-bold text-[#1c69d4] hover:text-blue-800 flex items-center gap-0.5 transition-colors px-2 py-1 hover:bg-blue-50 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">

                                {{-- Icon Plus (Akan hilang saat loading) --}}
                                <svg wire:loading.remove wire:target="addPaymentRow" class="w-3.5 h-3.5"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>

                                {{-- Icon Spinner (Akan muncul dan berputar saat loading) --}}
                                <svg wire:loading wire:target="addPaymentRow"
                                    class="animate-spin w-3.5 h-3.5 text-[#1c69d4]" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>

                                Split Bayar
                            </button>
                        </div>

                        <div class="space-y-3">
                            @foreach ($payments as $index => $payment)
                                <div class="p-3 bg-white border border-gray-200/80 shadow-sm rounded-xl space-y-2.5 transition hover:shadow-md relative"
                                    wire:key="payment-row-{{ $index }}">
                                    <div class="flex justify-between items-center border-b border-gray-50 pb-1.5">
                                        <span
                                            class="text-[10px] font-extrabold text-gray-400 tracking-wider uppercase">Alokasi
                                            #{{ $index + 1 }}</span>
                                        @if (count($payments) > 1)
                                            <button type="button" wire:click="removePaymentRow({{ $index }})"
                                                class="text-rose-500 hover:text-rose-700 text-[11px] font-semibold flex items-center gap-0.5 transition-colors px-1.5 py-0.5 hover:bg-rose-50 rounded-md">
                                                Hapus
                                            </button>
                                        @endif
                                    </div>

                                    <select wire:model.live="payments.{{ $index }}.payment_method_id"
                                        class="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-2.5 py-2 text-xs font-semibold focus:bg-white focus:border-[#1c69d4] focus:ring-0 transition">
                                        <option value="">-- Pilih Metode --</option>
                                        @foreach ($this->paymentMethods as $pm)
                                            <option value="{{ $pm->id }}">{{ $pm->name }}
                                                {{ $pm->rates->count() > 0 ? '(' . $pm->rates->count() . ' tarif)' : ($pm->mdr_percentage > 0 ? '(MDR ' . $pm->mdr_percentage . '%)' : '') }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @php
                                        $pmId = $payment['payment_method_id'];
                                        $pmObj = $pmId ? \App\Models\PaymentMethod::find($pmId) : null;
                                        $rowRates = $pmObj
                                            ? $pmObj->rates()->where('is_active', true)->get()
                                            : collect();
                                    @endphp

                                    @if ($rowRates->count() > 0)
                                        <select wire:model.live="payments.{{ $index }}.payment_method_rate_id"
                                            class="w-full bg-blue-50/30 border border-blue-100 text-blue-900 rounded-lg px-2.5 py-2 text-xs font-bold focus:border-[#1c69d4] focus:ring-0 transition">
                                            <option value="">-- Pilih Opsi / Tenor --</option>
                                            @foreach ($rowRates as $rate)
                                                <option value="{{ $rate->id }}">{{ $rate->name }} (MDR
                                                    {{ $rate->mdr_percentage }}%)</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    <div class="flex gap-2">
                                        <div class="relative flex-1" x-data="{
                                            rawAmount: @entangle('payments.' . $index . '.amount').live,
                                            get maskedAmount() {
                                                if (!this.rawAmount) return '';
                                                return this.rawAmount.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                            },
                                            set maskedAmount(val) {
                                                this.rawAmount = val.replace(/\D/g, '');
                                            }
                                        }">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Rp</span>
                                            <input type="text" x-model="maskedAmount"
                                                class="w-full pl-8 pr-3 py-2 bg-gray-50/50 border border-gray-200 rounded-lg text-xs font-bold focus:bg-white focus:border-[#1c69d4] focus:ring-0 transition"
                                                placeholder="Jumlah Bayar">
                                        </div>
                                        @if (count($payments) > 1)
                                            <button type="button"
                                                wire:click="autofillRemaining({{ $index }})"
                                                class="px-3 py-2 text-xs font-bold bg-[#1c69d4] text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all shadow-sm shadow-blue-500/20 whitespace-nowrap">
                                                Sisa Tab
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Validation Status Banner --}}
                        @php
                            $targetTotal = max(0, $this->subtotal - (int) $this->totalDiscount);
                            $allocatedTotal = (int) $this->paymentsTotalBase;
                            $diff = $targetTotal - $allocatedTotal;
                        @endphp

                        <div class="transition-all duration-300">
                            @if ($diff === 0)
                                <div
                                    class="flex items-center gap-2 p-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Pembayaran Lunas & Sesuai
                                </div>
                            @elseif ($diff > 0)
                                <div
                                    class="flex items-center gap-2 p-2.5 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-bold justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-amber-600 animate-pulse" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Kurang Bayar: Rp {{ number_format($diff, 0, ',', '.') }}
                                </div>
                            @else
                                <div
                                    class="flex items-center gap-2 p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-bold justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Kembalian / Lebih: Rp {{ number_format(abs($diff), 0, ',', '.') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Discount Section --}}
                    <div class="p-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Diskon Manual (Rp)
                        </p>
                        <div class="relative" x-data="{
                            rawDiscount: @entangle('discount_amount').live,
                            get maskedDiscount() {
                                if (!this.rawDiscount) return '';
                                return this.rawDiscount.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            },
                            set maskedDiscount(val) {
                                this.rawDiscount = val.replace(/\D/g, '');
                            }
                        }">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Rp</span>
                            <input type="text" x-model="maskedDiscount"
                                class="w-full bg-white border border-gray-200 shadow-sm rounded-lg pl-8 pr-3 py-2 text-xs font-bold focus:border-[#1c69d4] focus:ring-0 transition placeholder:text-gray-300"
                                placeholder="0">
                        </div>
                    </div>

                    {{-- Promos Section --}}
                    @if (count($this->activePromos) > 0)
                        <div class="p-4">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">
                                Promo / Voucher Terpakai
                            </p>
                            <div class="space-y-2 max-h-36  pr-1">
                                @foreach ($this->activePromos as $promo)
                                    <label
                                        class="flex items-start gap-2.5 bg-white border border-gray-200/60 p-2.5 rounded-xl cursor-pointer shadow-sm hover:border-[#1c69d4]/40 transition group">

                                        <input type="checkbox" wire:model.live="selectedPromos"
                                            value="{{ $promo->id }}"
                                            class="mt-0.5 rounded text-[#1c69d4] focus:ring-[#1c69d4]/20 border-gray-300 w-3.5 h-3.5 transition">

                                        <div class="text-xs leading-tight w-full">
                                            {{-- Nama Promo --}}
                                            <div
                                                class="font-bold text-gray-700 group-hover:text-[#1c69d4] transition-colors line-clamp-1">
                                                {{ $promo->name }}
                                            </div>

                                            {{-- Info Diskon Utama --}}
                                            <div class="text-[10px] text-gray-400 font-semibold mt-1 tracking-wide">
                                                @if ($promo->code)
                                                    <span
                                                        class="bg-gray-100 text-gray-600 px-1 py-0.5 rounded mr-1 font-mono font-normal">
                                                        {{ $promo->code }}
                                                    </span>
                                                    &bull;
                                                @endif

                                                @if ($promo->discount_type === 'fixed')
                                                    Potongan Rp{{ number_format($promo->discount_value, 0, ',', '.') }}
                                                @else
                                                    Potongan {{ number_format($promo->discount_value, 0) }}%
                                                @endif
                                            </div>

                                            @if ($promo->is_bundle)
                                                <div
                                                    class="mt-3 bg-gradient-to-br from-emerald-50/80 to-white border border-dashed border-emerald-200 rounded-lg p-2.5 relative overflow-hidden">

                                                    {{-- Aksen Dekoratif --}}
                                                    <div
                                                        class="absolute -right-2 -top-2 w-8 h-8 bg-emerald-100 rounded-full opacity-50">
                                                    </div>

                                                    {{-- Header Info Bundle & Nominal (Sejajar Kiri-Kanan) --}}
                                                    <div
                                                        class="flex items-start justify-between gap-2 border-emerald-100 pb-2 relative z-10">
                                                        <div class="flex items-center gap-1.5">
                                                            <div
                                                                class="bg-emerald-100 text-emerald-600 p-1 rounded-md shrink-0">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="2"
                                                                    stroke="currentColor" class="w-3.5 h-3.5">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        d="m8.99 14.993 6-6m6 3.001c0 1.268-.63 2.39-1.593 3.069a3.746 3.746 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043 3.745 3.745 0 0 1-3.068 1.593c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.746 3.746 0 0 1-1.043-3.297 3.746 3.746 0 0 1-1.593-3.068c0-1.268.63-2.39 1.593-3.068a3.746 3.746 0 0 1 1.043-3.297 3.745 3.745 0 0 1 3.296-1.042 3.745 3.745 0 0 1 3.068-1.594c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.297 3.746 3.746 0 0 1 1.593 3.068ZM9.74 9.743h.008v.007H9.74v-.007Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                                </svg>
                                                            </div>
                                                            <span
                                                                class="text-[10px] font-black text-emerald-700 uppercase tracking-wider">
                                                                Diskon Bundle
                                                            </span>
                                                        </div>

                                                        {{-- Badge Nominal Diskon --}}
                                                        <div
                                                            class="bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm whitespace-nowrap">
                                                            @if ($promo->bundle_discount_type === 'fixed')
                                                                +
                                                                Rp{{ number_format($promo->bundle_discount_value, 0, ',', '.') }}
                                                            @else
                                                                +
                                                                {{ number_format($promo->bundle_discount_value, 0) }}%
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- Daftar Item Bundle --}}
                                                    <div class="relative z-10">
                                                        <p
                                                            class="text-[9px] text-emerald-500 font-bold mb-1.5 uppercase tracking-wide">
                                                            Berlaku untuk produk:</p>

                                                        @if ($promo->bundleSkus && count($promo->bundleSkus) > 0)
                                                            <ul class="space-y-1.5">
                                                                @foreach ($promo->bundleSkus as $bundleItem)
                                                                    <li
                                                                        class="flex items-start gap-1.5 text-[10px] text-gray-700 font-medium">
                                                                        {{-- Icon Check Kecil --}}
                                                                        <svg class="w-3 h-3 text-emerald-400 shrink-0 mt-0.5"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor" stroke-width="3">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                        <span class="line-clamp-1 leading-snug">
                                                                            {{ $bundleItem->variant->product->name ?? $bundleItem->sku }}
                                                                        </span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <div
                                                                class="flex items-center gap-1 text-[9px] text-emerald-400 italic">
                                                                <svg class="w-3 h-3" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor"
                                                                    stroke-width="2">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                Belum ada item di-set
                                                            </div>
                                                        @endif
                                                    </div>

                                                </div>
                                            @endif

                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Notes Section --}}
                    <div class="p-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Catatan Pesanan
                        </p>
                        <textarea wire:model.defer="notes" rows="2"
                            class="w-full bg-white border border-gray-200 shadow-sm rounded-lg px-3 py-2 text-xs focus:border-[#1c69d4] focus:ring-0 placeholder-gray-300 resize-none transition"
                            placeholder="Tambahkan catatan internal atau request cetakan jika ada..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Pinned Footer: Totals & Pay Button --}}
            <div class="border-t border-gray-200 bg-white shrink-0 p-4 space-y-3.5">
                <div class="space-y-1.5">
                    <div class="flex justify-between text-xs font-medium text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-bold text-gray-800">Rp
                            {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if ($this->totalPromoDiscount > 0)
                        <div class="flex justify-between text-xs font-medium text-emerald-600">
                            <span>Diskon Promo</span>
                            <span class="font-bold">-Rp
                                {{ number_format($this->totalPromoDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if ($this->discount_amount > 0)
                        <div class="flex justify-between text-xs font-medium text-rose-500">
                            <span>Diskon</span>
                            <span class="font-bold">- Rp
                                {{ number_format($this->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="border-t border-gray-150 pt-1.5 flex justify-between items-center">
                        <span class="font-black text-gray-900 text-base">Total Tagihan</span>
                        <span class="font-black text-[#1c69d4] text-lg">Rp
                            {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div>
                    <button wire:click="openCheckout" wire:loading.attr="disabled"
                        {{ empty($cart) ? 'disabled' : '' }}
                        class="w-full py-3.5 rounded-xl font-black text-white text-base transition-all shadow-md active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed
    {{ empty($cart) ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#1c69d4] hover:bg-blue-700 shadow-blue-500/20' }}">

                        {{-- Icon Dompet/Bayar (Akan hilang saat loading) --}}
                        <svg wire:loading.remove wire:target="openCheckout"
                            class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>

                        {{-- Icon Spinner Putih (Akan muncul dan berputar saat loading) --}}
                        <svg wire:loading wire:target="openCheckout"
                            class="animate-spin w-4 h-4 inline-block mr-1.5 -mt-0.5 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>

                        Bayar
                    </button>
                </div>
            </div>
        </div>

        {{-- Floating Action Button (FAB) khusus Mobile untuk membuka Cart --}}
        <button @click="openCart = true"
            class="lg:hidden fixed bottom-25 right-6 bg-[#1c69d4] text-white p-4 rounded-full shadow-xl hover:bg-blue-700 active:scale-95 transition-all z-30 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
            </svg>
            @if (!empty($cart))
                <span
                    class="absolute -top-2 -right-2 bg-rose-500 text-white text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full border-2 border-white">{{ count($cart) }}</span>
            @endif
        </button>
    </div>

    @include('livewire.zoffline.pos.modal.variant')
    @include('livewire.zoffline.pos.modal.checkout')
    @include('livewire.zoffline.pos.modal.riwayat-penjualan')
    @include('livewire.zoffline.pos.modal.receipt-struk')
    @include('livewire.zoffline.pos.modal.stok-gudang')

    <div id="scanner-modal"
        class="hidden fixed inset-0 z-50 bg-black/60  items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-4 rounded-lg w-11/12 max-w-md shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-700">Arahkan Kamera ke Barcode</h3>
                <button onclick="closeScanner()" class="text-red-500 hover:text-red-700 font-bold p-1">Tutup</button>
            </div>
            <div id="reader" class="w-full bg-black rounded overflow-hidden"></div>
        </div>
    </div>
    {{-- Print Styles --}}
    <style>
        @media print {
            @page {
                margin: 0;
            }

            body * {
                visibility: hidden;
            }

            #receipt-content,
            #receipt-content * {
                visibility: visible;
            }

            #receipt-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                padding: 4mm;
                font-size: 12px;
            }
        }
    </style>
    @script
        <script>
            $wire.on('print-rawbt', (event) => {
                const base64 = event.base64;
                const orderNumber = event.orderNumber;
                const isAndroid = /Android/i.test(navigator.userAgent);

                if (isAndroid) {
                    const rawbtUri = `rawbt:base64,${base64}`;
                    window.location.href = rawbtUri;
                } else {
                    const rawBytes = atob(base64);
                    const bytes = new Uint8Array(rawBytes.length);
                    for (let i = 0; i < rawBytes.length; i++) {
                        bytes[i] = rawBytes.charCodeAt(i);
                    }
                    const blob = new Blob([bytes], {
                        type: 'application/octet-stream'
                    });
                    const url = URL.createObjectURL(blob);

                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `nota-${orderNumber}.prn`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
            });
        </script>
    @endscript
</div>
