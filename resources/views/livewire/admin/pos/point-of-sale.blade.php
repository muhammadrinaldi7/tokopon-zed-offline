<div class="bg-gray-100" x-data="{ showSidebar: false }">
    <div class="flex h-[calc(100vh-72px)] overflow-hidden">

        {{-- ═══════════════════════════════════════════════════════════
             LEFT PANEL: Product Search & Grid
        ═══════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Top Bar --}}
            <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <a href="{{ route('/') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
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

                    <div class="flex bg-gray-100 p-1 rounded-lg">
                        <button type="button" wire:click="$set('productType', 'all')"
                            class="px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $productType === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Semua</button>
                        <button type="button" wire:click="$set('productType', 'new')"
                            class="px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $productType === 'new' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Baru</button>
                        <button type="button" wire:click="$set('productType', 'second')"
                            class="px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $productType === 'second' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Second</button>
                    </div>
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
                                            class="absolute top-2 right-2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Second</span>
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

        {{-- ═══════════════════════════════════════════════════════════
             RIGHT PANEL: Cart, Customer & Payment
        ═══════════════════════════════════════════════════════════ --}}
        <div class="w-[420px] bg-white border-l border-gray-200 flex flex-col shrink-0 overflow-hidden">
            {{-- Cart Header --}}
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                <h2 class="font-black text-gray-900 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                    Keranjang
                </h2>
                @if (!empty($cart))
                    <span
                        class="bg-[#1c69d4] text-white text-xs font-black px-2.5 py-1 rounded-full">{{ count($cart) }}</span>
                @endif
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto px-5 py-3 space-y-3">
                @forelse($cart as $index => $item)
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 relative group">
                        <button wire:click="removeFromCart({{ $index }})"
                            class="absolute top-2 right-2 text-gray-300 hover:text-rose-500 transition opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="flex justify-between items-start mb-2">
                            <div class="pr-6">
                                <h4 class="font-bold text-gray-800 text-sm">{{ $item['name'] }}</h4>
                                <p class="text-[10px] text-gray-400 uppercase font-bold">{{ $item['color'] }} -
                                    {{ $item['storage'] }}
                                    @if ($item['is_second'] ?? false)
                                        <span class="text-emerald-500">• Second</span>
                                    @endif
                                </p>
                            </div>
                            <p class="font-bold text-gray-800 text-sm whitespace-nowrap">Rp
                                {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-2">
                            <div class="flex items-center gap-1">
                                <button wire:click="decrementCartItem({{ $index }})"
                                    class="w-7 h-7 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition text-sm font-bold">−</button>
                                <span class="w-8 text-center font-bold text-sm">{{ $item['qty'] }}</span>
                                <button wire:click="incrementCartItem({{ $index }})"
                                    class="w-7 h-7 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition text-sm font-bold">+</button>
                            </div>
                            <p class="text-xs text-gray-400">@ Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                        </div>
                        {{-- SN Input --}}
                        <div class="mt-3">
                            <input type="text"
                                wire:change="updateSerialNumber({{ $index }}, $event.target.value)"
                                value="{{ $item['serial_number'] }}"
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-xs font-mono focus:border-[#1c69d4] focus:ring-0 transition-all placeholder-gray-300"
                                placeholder="SN / IMEI...">
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-gray-300">
                        <svg class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <p class="text-sm font-bold text-gray-400">Keranjang kosong</p>
                    </div>
                @endforelse
            </div>

            {{-- Bottom Section: Customer, Payment, Totals --}}
            <div class="border-t border-gray-200 bg-gray-50 shrink-0">

                {{-- Customer Section --}}
                <div class="px-5 py-3 border-b border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Customer</p>
                    @if ($selectedCustomerId)
                        @php $customer = \App\Models\User::with('profile')->find($selectedCustomerId); @endphp
                        <div
                            class="flex items-center justify-between bg-emerald-50 rounded-lg p-3 border border-emerald-100">
                            <div>
                                <p class="font-bold text-gray-800 text-sm">{{ $customer->name }}</p>
                                <p class="text-[10px] text-gray-500">
                                    {{ $customer->profile->phone_number ?? $customer->email }}</p>
                            </div>
                            <button wire:click="clearSelectedCustomer"
                                class="text-rose-400 hover:text-rose-600 text-xs font-bold">Ganti</button>
                        </div>
                    @elseif($isNewCustomer)
                        <div class="space-y-2">
                            <input type="text" wire:model="customerName"
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Nama Customer *">
                            <input type="text" wire:model="customerPhone"
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-0"
                                placeholder="No HP *">
                            <input type="email" wire:model="customerEmail"
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Email (opsional)">
                            <button wire:click="$set('isNewCustomer', false)"
                                class="text-xs text-gray-400 hover:text-gray-600 font-bold">← Cari customer
                                lama</button>
                        </div>
                    @else
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                                class="w-full bg-white border border-gray-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Cari nama / no HP...">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                            class="text-xs text-[#1c69d4] hover:underline font-bold mt-2 block">+ Customer
                            Baru</button>
                    @endif
                </div>

                {{-- Payment Method --}}
                <div class="px-5 py-3 border-b border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Pembayaran</p>
                    <select wire:model.live="payment_method_id"
                        class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold focus:border-[#1c69d4] focus:ring-0">
                        <option value="">-- Pilih Metode --</option>
                        @foreach ($this->paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}
                                {{ $pm->rates->count() > 0 ? '(' . $pm->rates->count() . ' tarif)' : ($pm->mdr_percentage > 0 ? '(MDR ' . $pm->mdr_percentage . '%)' : '') }}
                            </option>
                        @endforeach
                    </select>

                    @if ($this->paymentMethodRates->count() > 0)
                        <div class="mt-2.5">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Tipe Kartu
                                / Tenor Cicilan</p>
                            <select wire:model.live="payment_method_rate_id"
                                class="w-full bg-[#f4f7fc] border border-blue-100 text-blue-900 rounded-lg px-3 py-2 text-xs font-bold focus:border-[#1c69d4] focus:ring-0">
                                <option value="">-- Pilih Opsi / Tenor --</option>
                                @foreach ($this->paymentMethodRates as $rate)
                                    <option value="{{ $rate->id }}">{{ $rate->name }} (MDR
                                        {{ $rate->mdr_percentage }}%)</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                {{-- Discount --}}
                <div class="px-5 py-3 border-b border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Diskon (Rp)</p>
                    <input type="number" wire:model.live="discount_amount"
                        class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold focus:border-[#1c69d4] focus:ring-0"
                        placeholder="0" min="0">
                </div>

                {{-- Totals --}}
                <div class="px-5 py-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-bold text-gray-800">Rp
                            {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if ($this->discount_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-rose-500">Diskon</span>
                            <span class="font-bold text-rose-500">- Rp
                                {{ number_format($this->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if ($this->mdrAmount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-amber-600">Beban MDR ({{ $this->mdrPercentage }}%)</span>
                            <span class="font-bold text-amber-600">+ Rp
                                {{ number_format($this->mdrAmount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="border-t border-gray-200 pt-2 flex justify-between">
                        <span class="font-black text-gray-900 text-lg">Total</span>
                        <span class="font-black text-[#1c69d4] text-lg">Rp
                            {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Pay Button --}}
                <div class="px-5 pb-5">
                    <button wire:click="openCheckout" {{ empty($cart) ? 'disabled' : '' }}
                        class="w-full py-4 rounded-xl font-black text-white text-lg transition-all shadow-lg active:scale-[0.98]
                        {{ empty($cart) ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#1c69d4] hover:bg-blue-700 shadow-blue-500/30' }}">
                        <svg class="w-5 h-5 inline-block mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Bayar
                    </button>
                </div>
            </div>
        </div>
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
                    @if ($this->mdrAmount > 0)
                        <div class="flex justify-between text-sm"><span class="text-amber-600">MDR
                                ({{ $this->selectedPaymentMethodRate ? $this->selectedPaymentMethodRate->name : $this->selectedPaymentMethod->name ?? '' }}
                                - {{ $this->mdrPercentage }}%)</span><span class="font-bold text-amber-600">+ Rp
                                {{ number_format($this->mdrAmount, 0, ',', '.') }}</span></div>
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
                            Cetak
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
                    <p class="text-[10px] text-gray-500">No: {{ $completedOrder->order_number }}</p>
                    <p class="text-[10px] text-gray-500">Kasir: {{ $completedOrder->handledBy->name ?? '-' }}</p>
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
                            @endif
                        </div>
                    @endforeach
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="flex justify-between">
                        <span>Subtotal</span><span>{{ number_format($completedOrder->total_amount, 0, ',', '.') }}</span>
                    </div>
                    @if ($completedOrder->discount_amount > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Diskon</span><span>-{{ number_format($completedOrder->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if ($completedOrder->mdr_amount > 0)
                        <div class="flex justify-between text-amber-600">
                            <span>MDR
                                ({{ $completedOrder->paymentMethodRate ? $completedOrder->paymentMethodRate->name : $completedOrder->paymentMethod->name ?? '' }}
                                - {{ $completedOrder->mdr_percentage }}%)</span>
                            <span>+{{ number_format($completedOrder->mdr_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="border-t border-dashed border-gray-300 my-1"></div>
                    <div class="flex justify-between font-bold text-sm"><span>TOTAL</span><span>Rp
                            {{ number_format($completedOrder->grand_total, 0, ',', '.') }}</span></div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <p class="text-[10px] text-gray-500">Bayar:
                        {{ $completedOrder->paymentMethod->name ?? 'Cash' }}{{ $completedOrder->paymentMethodRate ? ' - ' . $completedOrder->paymentMethodRate->name : '' }}
                    </p>
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

    {{-- Print Styles --}}
    <style>
        @media print {
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
                padding: 5mm;
                font-size: 10px;
            }
        }
    </style>
</div>
