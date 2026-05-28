<div class="bg-gray-100" x-data="{ showSidebar: false }">

    {{-- Bungkus layout utama dengan x-data dari Alpine.js --}}
    <div x-data="{ openCart: false }" class="relative flex h-[calc(100vh-72px)] overflow-hidden">

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

        {{-- Overlay Background (Muncul saat cart dibuka di mobile) --}}
        <div x-show="openCart" x-transition.opacity x-cloak @click="openCart = false"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden">
        </div>

        {{-- ═══════════════════════════════════════════════════════════
         RIGHT PANEL: Cart, Customer & Payment (Drawer on Mobile)
    ═══════════════════════════════════════════════════════════ --}}
        <div :class="openCart ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
            class="fixed lg:static inset-y-0 right-0 z-50 md:z-10 w-[85%] sm:w-[420px] transform transition-transform duration-300 ease-in-out bg-white border-l border-gray-200 flex flex-col shrink-0 h-full shadow-2xl lg:shadow-none">

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

            {{-- Cart Items --}}
            <div
                class="max-h-[170px] overflow-y-auto px-4 py-2.5 space-y-2.5 border-b border-gray-100 shrink-0 bg-white">
                @forelse($cart as $index => $item)
                    <div class="bg-gray-50 rounded-lg p-2.5 border border-gray-100 relative group">
                        <button wire:click="removeFromCart({{ $index }})"
                            class="absolute top-2.5 right-2.5 text-gray-300 hover:text-rose-500 transition lg:opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="flex justify-between items-start mb-1.5">
                            <div class="pr-6">
                                <h4 class="font-bold text-gray-800 text-xs">{{ $item['name'] }}</h4>
                                <p class="text-[10px] text-gray-400 uppercase font-bold">{{ $item['color'] }} -
                                    {{ $item['storage'] }}
                                    @if ($item['is_second'] ?? false)
                                        <span class="text-emerald-500">• Second</span>
                                    @endif
                                </p>
                            </div>
                            <p class="font-bold text-gray-800 text-xs whitespace-nowrap">Rp
                                {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1">
                                <button wire:click="decrementCartItem({{ $index }})"
                                    class="w-6 h-6 rounded bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition text-xs font-bold">−</button>
                                <span class="w-6 text-center font-bold text-xs">{{ $item['qty'] }}</span>
                                <button wire:click="incrementCartItem({{ $index }})"
                                    class="w-6 h-6 rounded bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition text-xs font-bold">+</button>
                            </div>
                            <p class="text-[10px] text-gray-400">@ Rp {{ number_format($item['price'], 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- SN Input --}}
                        @php
                            $snArray = $item['serial_numbers'] ?? [$item['serial_number'] ?? ''];
                        @endphp
                        <div class="mt-2 space-y-2">
                            @foreach ($snArray as $snIndex => $snValue)
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <input type="text" id="sn_input_{{ $index }}_{{ $snIndex }}"
                                            wire:change="updateSerialNumber({{ $index }}, {{ $snIndex }}, $event.target.value)"
                                            value="{{ $snValue }}"
                                            class="w-full bg-white border border-gray-200 rounded px-2.5 py-1 text-[11px] font-mono focus:border-[#1c69d4] focus:ring-0 transition-all placeholder-gray-300"
                                            placeholder="SN / IMEI {{ count($snArray) > 1 ? 'ke-' . ($snIndex + 1) : '' }}...">

                                        <button type="button"
                                            onclick="startScanner({{ $index }}, {{ $snIndex }})"
                                            class="shrink-0 bg-[#1c69d4] hover:bg-blue-700 text-white border border-[#1c69d4] rounded px-2 py-1 transition-all focus:outline-none focus:ring-2 focus:ring-[#1c69d4] focus:ring-offset-1"
                                            title="Scan Barcode Kamera">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    @if(($item['is_second'] ?? false) && $snValue)
                                        <div class="flex justify-end">
                                            <a href="{{ route('qc.inspect', ['secondProductVariant' => $item['variant_id'], 'imei' => $snValue]) }}" target="_blank" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-1 rounded border border-emerald-100 flex items-center gap-1 transition shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Lakukan QC Serah Terima
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-6 text-gray-300">
                        <svg class="w-8 h-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <p class="text-xs font-bold text-gray-400">Keranjang kosong</p>
                    </div>
                @endforelse
            </div>

            {{-- Form Section: Customer, Payments, Discount --}}
            <div class="flex-1 overflow-y-auto bg-gray-50 divide-y divide-gray-100 min-h-0">
                {{-- Customer Section --}}
                <div class="px-4 py-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Customer</p>
                    @if ($selectedCustomerId)
                        @php $customer = \App\Models\User::with('profile')->find($selectedCustomerId); @endphp
                        <div
                            class="flex items-center justify-between bg-emerald-50 rounded-lg p-2.5 border border-emerald-100">
                            <div>
                                <p class="font-bold text-gray-800 text-xs">{{ $customer->name }}</p>
                                <p class="text-[10px] text-gray-500">
                                    {{ $customer->profile->phone_number ?? $customer->email }}</p>
                            </div>
                            <button wire:click="clearSelectedCustomer"
                                class="text-rose-400 hover:text-rose-600 text-[11px] font-bold">Ganti</button>
                        </div>
                    @elseif($isNewCustomer)
                        <div class="space-y-1.5">
                            <input type="text" wire:model="customerName"
                                class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Nama Customer *">
                            <input type="text" wire:model="customerPhone"
                                class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="No HP *">
                            <input type="email" wire:model="customerEmail"
                                class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Email (opsional)">
                            <button wire:click="$set('isNewCustomer', false)"
                                class="text-[10px] text-gray-400 hover:text-gray-600 font-bold">← Cari customer
                                lama</button>
                        </div>
                    @else
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                                class="w-full bg-white border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Cari nama / no HP...">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        @if (strlen($searchCustomer) >= 2)
                            <div class="bg-white border rounded-lg shadow-lg max-h-32 overflow-y-auto divide-y mt-1">
                                @forelse($this->customerResults as $user)
                                    <button wire:click="selectCustomer({{ $user->id }})"
                                        class="w-full p-2 hover:bg-gray-50 text-left flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">{{ $user->name }}</p>
                                            <p class="text-[10px] text-gray-400">
                                                {{ $user->profile->phone_number ?? $user->email }}</p>
                                        </div>
                                        <span class="text-emerald-500 text-[10px] font-bold">Pilih</span>
                                    </button>
                                @empty
                                    <p class="p-2 text-xs text-gray-400 text-center">Tidak ditemukan</p>
                                @endforelse
                            </div>
                        @endif
                        <button wire:click="$set('isNewCustomer', true)"
                            class="text-[10px] text-[#1c69d4] hover:underline font-bold mt-1.5 block">+ Customer
                            Baru</button>
                    @endif
                </div>
                {{-- Sales Section --}}
                <div class="px-4 py-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Tenaga Penjual
                        (Sales)</p>

                    {{-- Selected Sales Tags --}}
                    @if (count($selectedSales) > 0)
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach ($selectedSales as $sales)
                                <div
                                    class="flex items-center gap-1.5 bg-[#1c69d4]/10 text-[#1c69d4] border border-[#1c69d4]/20 rounded-md px-2 py-1">
                                    <span class="text-[11px] font-bold">{{ $sales['name'] }}</span>
                                    <button wire:click="removeSales({{ $sales['id'] }})"
                                        class="text-[#1c69d4]/70 hover:text-rose-500 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="relative">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchSales"
                                class="w-full bg-white border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Cari nama / NIK (tambah sales)...">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        @if (strlen($searchSales) >= 2)
                            <div
                                class="absolute z-10 w-full bg-white border rounded-lg shadow-lg max-h-40 overflow-y-auto divide-y mt-1">
                                @forelse($this->salesResults as $sales)
                                    <button wire:click="selectSales({{ $sales->id }})"
                                        class="w-full p-2 hover:bg-gray-50 text-left flex justify-between items-center group transition">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">{{ $sales->name }}</p>
                                            <p class="text-[9px] text-gray-400">{{ $sales->employee_no ?? 'N/A' }}</p>
                                        </div>
                                        <span
                                            class="text-[#1c69d4] text-[10px] font-bold opacity-0 group-hover:opacity-100 transition">Pilih</span>
                                    </button>
                                @empty
                                    <p class="p-2 text-xs text-gray-400 text-center">Tidak ditemukan</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Payment Methods --}}
                <div class="px-4 py-3 space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Metode Pembayaran</p>
                        <button type="button" wire:click="addPaymentRow"
                            class="text-[11px] font-bold text-[#1c69d4] hover:text-blue-800 flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Split Pembayaran
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($payments as $index => $payment)
                            <div class="p-2.5 bg-white border border-gray-200 rounded-xl space-y-2 relative"
                                wire:key="payment-row-{{ $index }}">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-extrabold text-gray-500">Alokasi
                                        #{{ $index + 1 }}</span>
                                    @if (count($payments) > 1)
                                        <button type="button" wire:click="removePaymentRow({{ $index }})"
                                            class="text-rose-500 hover:text-rose-700 text-[10px] font-bold flex items-center gap-0.5 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </button>
                                    @endif
                                </div>

                                <select wire:model.live="payments.{{ $index }}.payment_method_id"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-bold focus:border-[#1c69d4] focus:ring-0">
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
                                    $rowRates = $pmObj ? $pmObj->rates()->where('is_active', true)->get() : collect();
                                @endphp

                                @if ($rowRates->count() > 0)
                                    <select wire:model.live="payments.{{ $index }}.payment_method_rate_id"
                                        class="w-full bg-blue-50/50 border border-blue-100 text-blue-900 rounded-lg px-2 py-1.5 text-xs font-bold focus:border-[#1c69d4] focus:ring-0">
                                        <option value="">-- Pilih Opsi / Tenor --</option>
                                        @foreach ($rowRates as $rate)
                                            <option value="{{ $rate->id }}">{{ $rate->name }} (MDR
                                                {{ $rate->mdr_percentage }}%)</option>
                                        @endforeach
                                    </select>
                                @endif

                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <span
                                            class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Rp</span>
                                        <input type="number" wire:model.live="payments.{{ $index }}.amount"
                                            class="w-full pl-7 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold focus:border-[#1c69d4] focus:ring-0"
                                            placeholder="Jumlah Bayar" min="0">
                                    </div>
                                    @if (count($payments) > 1)
                                        <button type="button" wire:click="autofillRemaining({{ $index }})"
                                            class="px-2 py-1.5 text-xs font-bold bg-[#1c69d4] text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all whitespace-nowrap">
                                            Gunakan Sisa
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Validation Status Banner --}}
                    @php
                        $targetTotal = max(0, $this->subtotal - $this->discount_amount);
                        $allocatedTotal = (int) $this->paymentsTotalBase;
                        $diff = $targetTotal - $allocatedTotal;
                    @endphp

                    @if ($diff === 0)
                        <div
                            class="flex items-center gap-2 p-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-xs font-bold justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Jumlah Pembayaran Sesuai
                        </div>
                    @elseif ($diff > 0)
                        <div
                            class="flex items-center gap-2 p-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-xs font-bold justify-center">
                            <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Kurang Bayar: Rp {{ number_format($diff, 0, ',', '.') }}
                        </div>
                    @else
                        <div
                            class="flex items-center gap-2 p-2 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs font-bold justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Kelebihan Bayar: Rp {{ number_format(abs($diff), 0, ',', '.') }}
                        </div>
                    @endif
                </div>

                {{-- Discount --}}
                <div class="px-4 py-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Diskon (Rp)</p>
                    <input type="number" wire:model.live.debounce.300ms="discount_amount"
                        class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-bold focus:border-[#1c69d4] focus:ring-0"
                        placeholder="0" min="0">
                </div>

                {{-- Catatan --}}
                <div class="px-4 pb-4">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Catatan Pesanan</p>
                    <textarea wire:model.defer="notes" rows="2"
                        class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0 placeholder-gray-300 resize-none"
                        placeholder="Opsional..."></textarea>
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
                    <button wire:click="openCheckout" {{ empty($cart) ? 'disabled' : '' }}
                        class="w-full py-3.5 rounded-xl font-black text-white text-base transition-all shadow-md active:scale-[0.98]
                    {{ empty($cart) ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#1c69d4] hover:bg-blue-700 shadow-blue-500/20' }}">
                        <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
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

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Variant Picker
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showVariantModal && $variantModalProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-5 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-gray-900">{{ $variantModalProduct->name }}</h3>
                        <p class="text-xs text-gray-400">Pilih varian yang akan dijual</p>
                    </div>
                    <button wire:click="$set('showVariantModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-5 space-y-2 max-h-80 overflow-y-auto">
                    @foreach ($variantModalVariants as $variant)
                        <button wire:click="addVariantToCart({{ $variant['id'] }})"
                            class="w-full p-4 rounded-xl border border-gray-100 hover:border-[#1c69d4]/50 hover:bg-blue-50/30 transition-all text-left flex justify-between items-center {{ $variant['stock'] <= 0 ? 'opacity-40 cursor-not-allowed' : '' }}"
                            {{ $variant['stock'] <= 0 ? 'disabled' : '' }}>
                            <div>
                                <p class="font-bold text-gray-800">{{ $variant['label'] }}</p>
                                @if ($variant['condition'])
                                    <p class="text-[10px] text-emerald-500 font-bold uppercase">
                                        {{ $variant['condition'] }}</p>
                                @endif
                                <p class="text-xs text-gray-400 font-mono mt-0.5">SKU: {{ $variant['sku'] ?: '-' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-[#1c69d4]">Rp
                                    {{ number_format($variant['price'], 0, ',', '.') }}</p>
                                <p
                                    class="text-[10px] text-gray-400 font-bold {{ $variant['stock'] <= 0 ? 'text-rose-500' : '' }}">
                                    Stok: {{ $variant['stock'] }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Checkout Confirmation
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showCheckoutModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-5 bg-gray-50 border-b border-gray-100">
                    <h3 class="font-black text-gray-900 text-xl">Konfirmasi Pembayaran</h3>
                    <p class="text-sm text-gray-500 mt-1">Pastikan semua data sudah benar sebelum memproses.</p>
                </div>
                <div class="p-5 space-y-3 max-h-60 overflow-y-auto">
                    @foreach ($cart as $item)
                        <div class="flex justify-between text-sm border-b border-gray-50 pb-2">
                            <div>
                                <p class="font-bold text-gray-800">{{ $item['name'] }} <span
                                        class="text-gray-400">({{ $item['color'] }}/{{ $item['storage'] }})</span>
                                </p>
                                <p class="text-[10px] text-gray-400 font-mono">SN: {{ $item['serial_number'] }}</p>
                            </div>
                            <p class="font-bold text-gray-700 whitespace-nowrap">{{ $item['qty'] }}x Rp
                                {{ number_format($item['price'], 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="p-5 bg-gray-50 border-t border-gray-100 space-y-1">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span
                            class="font-bold">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                    @if ($this->discount_amount > 0)
                        <div class="flex justify-between text-sm"><span class="text-rose-500">Diskon</span><span
                                class="font-bold text-rose-500">- Rp
                                {{ number_format($this->discount_amount, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between pt-2 border-t border-gray-200"><span
                            class="font-black text-lg">TOTAL</span><span class="font-black text-[#1c69d4] text-lg">Rp
                            {{ number_format($this->grandTotal, 0, ',', '.') }}</span></div>
                </div>
                <div class="p-5 flex gap-3">
                    <button wire:click="$set('showCheckoutModal', false)"
                        class="flex-1 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Batal</button>
                    <button wire:click="processPayment" wire:loading.attr="disabled" wire:target="processPayment"
                        class="flex-1 py-3 rounded-xl font-bold text-white bg-[#1c69d4] hover:bg-blue-700 transition shadow-md shadow-blue-500/20">
                        <span wire:loading.remove wire:target="processPayment">Proses Bayar</span>
                        <span wire:loading wire:target="processPayment">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: History Sales (Riwayat Penjualan)
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showHistoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
                {{-- Header --}}
                <div class="p-5 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-gray-900 text-lg">20 Transaksi POS Terakhir</h3>
                        <p class="text-xs text-gray-400">Daftar penjualan yang berhasil diproses lewat kasir</p>
                    </div>
                    <button wire:click="$set('showHistoryModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Table/Content --}}
                <div class="p-5 max-h-[450px] overflow-y-auto">
                    @if (count($historyOrders) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr
                                        class="border-b border-gray-200 text-gray-400 uppercase font-black tracking-wider bg-gray-50/50">
                                        <th class="p-3">Waktu / No. Order</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3">Metode</th>
                                        <th class="p-3 text-right">Total Akhir</th>
                                        <th class="p-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                    @foreach ($historyOrders as $order)
                                        <tr class="hover:bg-gray-50/80 transition-colors">
                                            <td class="p-3">
                                                <p class="font-bold text-gray-900">{{ $order->order_number }}</p>
                                                <p class="text-[10px] text-gray-400 font-mono">
                                                    {{ $order->created_at->format('d M Y H:i') }}</p>
                                            </td>
                                            <td class="p-3 text-gray-600">
                                                {{ $order->user->name ?? 'Umum/Cash' }}
                                            </td>
                                            <td class="p-3">
                                                <span
                                                    class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-md uppercase">
                                                    {{ $order->paymentMethod->name ?? 'Cash' }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-right font-bold text-gray-900">
                                                Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                                            </td>
                                            <td class="p-3 text-center">
                                                <button wire:click="reprintOrder({{ $order->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-md text-[11px] font-bold transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                    </svg>
                                                    Struk
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-300">
                            <svg class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-sm font-bold text-gray-400">Belum ada riwayat transaksi hari ini</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button wire:click="$set('showHistoryModal', false)"
                        class="px-4 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-xl text-xs font-bold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Receipt (Struk)
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showReceiptModal && $completedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-black text-gray-900">Struk Transaksi</h3>
                    <div class="flex items-center gap-2">
                        <button
                            onclick="document.getElementById('receipt-content').classList.remove('hidden'); window.print();"
                            class="text-[#1c69d4] hover:text-blue-700 font-bold text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>

                        </button>
                        {{-- <button wire:click="printEscpos" wire:loading.attr="disabled"
                            class="text-orange-600 hover:text-orange-700 font-bold text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span wire:loading.remove wire:target="printEscpos">Cetak (ESC/POS)</span>
                            <span wire:loading wire:target="printEscpos">Printing...</span>
                        </button> --}}
                        <button wire:click="getEscposBase64" wire:loading.attr="disabled"
                            class="text-teal-600 hover:text-teal-700 font-bold text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span wire:loading.remove wire:target="getEscposBase64"></span>
                            <span wire:loading wire:target="getEscposBase64">Memproses...</span>
                        </button>
                        {{-- ─── TOMBOL WHATSAPP MEKARI QONTAK ─── --}}
                        @if (Auth::user()->hasRole('admin') || !$completedOrder->is_wa_sent)
                            {{-- Aktif jika Admin ATAU jika WA belum pernah dikirim --}}
                            <button wire:click="sendReceiptToQontak" wire:loading.attr="disabled"
                                class="text-emerald-600 hover:text-emerald-700 font-bold text-xs flex items-center gap-1 transition">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397 0 11.983 0c3.192.001 6.192 1.242 8.447 3.498c2.256 2.255 3.497 5.255 3.497 8.447c-.004 6.585-5.342 11.93-11.93 11.93c-2.002-.001-3.973-.503-5.729-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451c5.436 0 9.86-4.42 9.864-9.858c.002-2.634-1.023-5.11-2.887-6.974c-1.864-1.864-4.341-2.887-6.973-2.889c-5.44 0-9.865 4.42-9.869 9.859c-.001 1.706.469 3.372 1.36 4.866l-.993 3.626l3.71-.973zm11.233-6.17c-.3-.149-1.774-.875-2.046-.974c-.272-.1-.471-.149-.669.149c-.198.299-.768.974-.941 1.173c-.173.199-.347.224-.647.075c-.3-.15-1.266-.466-2.41-1.487c-.89-.794-1.49-1.774-1.664-2.073c-.173-.3-.018-.462.13-.61c.134-.133.298-.348.446-.521c.15-.173.199-.298.298-.497c.099-.198.05-.372-.025-.521c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.501-.669-.51l-.57-.01c-.199 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487c.709.306 1.263.489 1.694.626c.712.226 1.36.194 1.872.118c.571-.085 1.774-.726 2.022-1.392c.247-.667.247-1.241.173-1.392c-.074-.15-.272-.249-.571-.398z" />
                                </svg>
                                <span wire:loading.remove wire:target="sendReceiptToQontak">WhatsApp</span>
                                <span wire:loading wire:target="sendReceiptToQontak">Sending...</span>
                            </button>
                        @else
                            {{-- Terkunci untuk Kasir/FL jika is_wa_sent bernilai true --}}
                            <button disabled
                                class="text-gray-300 cursor-not-allowed font-bold text-xs flex items-center gap-1"
                                title="Sudah dikirim oleh kasir">
                                <svg class="w-4 h-4 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397 0 11.983 0c3.192.001 6.192 1.242 8.447 3.498c2.256 2.255 3.497 5.255 3.497 8.447c-.004 6.585-5.342 11.93-11.93 11.93c-2.002-.001-3.973-.503-5.729-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451c5.436 0 9.86-4.42 9.864-9.858c.002-2.634-1.023-5.11-2.887-6.974c-1.864-1.864-4.341-2.887-6.973-2.889c-5.44 0-9.865 4.42-9.869 9.859c-.001 1.706.469 3.372 1.36 4.866l-.993 3.626l3.71-.973zm11.233-6.17c-.3-.149-1.774-.875-2.046-.974c-.272-.1-.471-.149-.669.149c-.198.299-.768.974-.941 1.173c-.173.199-.347.224-.647.075c-.3-.15-1.266-.466-2.41-1.487c-.89-.794-1.49-1.774-1.664-2.073c-.173-.3-.018-.462.13-.61c.134-.133.298-.348.446-.521c.15-.173.199-.298.298-.497c.099-.198.05-.372-.025-.521c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.501-.669-.51l-.57-.01c-.199 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487c.709.306 1.263.489 1.694.626c.712.226 1.36.194 1.872.118c.571-.085 1.774-.726 2.022-1.392c.247-.667.247-1.241.173-1.392c-.074-.15-.272-.249-.571-.398z" />
                                </svg>
                                WA (Sent)
                            </button>
                        @endif

                        {{-- ─── TOMBOL EMAIL POS_SALES ─── --}}
                        @if (Auth::user()->hasRole('admin') || !$completedOrder->is_email_sent)
                            {{-- Aktif jika Admin ATAU jika Email belum pernah dikirim --}}
                            <button wire:click="sendReceiptToEmail" wire:loading.attr="disabled"
                                class="text-blue-600 hover:text-blue-700 font-bold text-xs flex items-center gap-1 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span wire:loading.remove wire:target="sendReceiptToEmail">Email</span>
                                <span wire:loading wire:target="sendReceiptToEmail">Sending...</span>
                            </button>
                        @else
                            {{-- Terkunci untuk Kasir/FL jika is_email_sent bernilai true --}}
                            <button disabled
                                class="text-gray-300 cursor-not-allowed font-bold text-xs flex items-center gap-1"
                                title="Sudah dikirim oleh kasir">
                                <svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Email (Sent)
                            </button>
                        @endif

                        {{-- Tombol Tutup --}}
                        <button wire:click="closeReceipt" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Receipt Preview --}}
                <div id="receipt-content" class="p-5 font-mono text-xs leading-relaxed">
                    <div class="text-center mb-3">
                        <p class="font-bold text-sm">TOKOPUN</p>
                        <p class="text-[10px] text-gray-500">
                            {{ $completedOrder->shipping_address_snapshot['store'] ?? 'Toko' }}</p>
                        <p class="text-[10px] text-gray-400">{{ $completedOrder->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <p class="text-[10px] text-gray-500">Tanggal:
                        {{ $completedOrder->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-[10px] text-gray-500">No: {{ $completedOrder->order_number }}</p>
                    <p class="text-[10px] text-gray-500">Sales: {{ $completedOrder->salesBy->first()->name ?? '-' }}
                    </p>
                    <p class="text-[10px] text-gray-500">Customer: {{ $completedOrder->user->name ?? '-' }}</p>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    @foreach ($completedOrder->items as $item)
                        @php
                            $v = $item->variant;
                            $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
                        @endphp
                        <div class="mb-1">
                            <p class="font-bold">{{ $itemName }}</p>
                            <div class="flex justify-between">
                                <span>{{ $item->qty }}x
                                    {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if ($item->serial_number)
                                <p class="text-[9px] text-gray-400">SN: {{ $item->serial_number }}</p>
                                @if($item->product_variant_type === 'App\Models\SecondProductVariant')
                                    @php
                                        $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                                    @endphp
                                    @foreach($sns as $sn)
                                        @if($sn)
                                        <div class="mt-2 mb-1 flex flex-col items-center justify-center p-2 border border-dashed border-gray-300 rounded bg-gray-50">
                                            <p class="text-[8px] text-gray-500 font-bold mb-1 text-center">Sertifikat QC Perangkat<br>(SN: {{ $sn }})</p>
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode(route('public.device-qc', ['imei' => $sn])) }}" class="w-16 h-16 grayscale mix-blend-multiply">
                                        </div>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                        </div>
                    @endforeach
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="flex justify-between">
                        <span>Subtotal</span><span>{{ number_format($completedOrder->total_amount, 0, ',', '.') }}</span>
                    </div>
                    {{-- @if ($completedOrder->discount_amount > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Diskon</span><span>-{{ number_format($completedOrder->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif --}}
                    <div class="border-t border-dashed border-gray-300 my-1"></div>
                    <div class="flex justify-between font-bold text-sm"><span>TOTAL</span><span>Rp
                            {{ number_format($completedOrder->grand_total, 0, ',', '.') }}</span></div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="space-y-0.5 mb-2">
                        @foreach ($completedOrder->payments as $payment)
                            <div class="flex justify-between text-[10px] text-gray-500">
                                <span>Bayar
                                    ({{ $payment->paymentMethod->name ?? 'Cash' }}{{ $payment->paymentMethodRate ? ' - ' . $payment->paymentMethodRate->name : '' }})
                                    :</span>
                                <span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($completedOrder->accurate_invoice_no)
                        <p class="text-[10px] text-gray-400">Inv: {{ $completedOrder->accurate_invoice_no }}</p>
                    @endif
                    <div class="text-center mt-4">
                        <p class="text-[10px] text-gray-400">Terima kasih telah berbelanja!</p>
                        <p class="text-[10px] text-gray-300">www.tokopun.com</p>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100">
                    <button wire:click="newTransaction"
                        class="w-full py-3 rounded-xl font-bold text-white bg-emerald-500 hover:bg-emerald-600 transition shadow-md">
                        Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    @endif
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
