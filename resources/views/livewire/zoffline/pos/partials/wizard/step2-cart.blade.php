<div class="space-y-6">
    <div class="mb-6">
        <div class="flex gap-2 items-center">
            <div class="rounded-full w-8 h-8 bg-[#DFE7FF] flex items-center justify-center text-black">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-auto" viewBox="0 0 24 24">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="1.2">
                        <path d="M14.5 10.5a2.5 2.5 0 1 1-5 0a2.5 2.5 0 0 1 5 0" />
                        <path
                            d="M16 3.5c2.48 0 4.19.384 5.133.676c.543.169.867.683.867 1.251v9.755c0 1.115-1.228 1.954-2.324 1.748c-.94-.178-2.165-.32-3.676-.32c-4.75 0-5.89 1.805-12.855.27A1.47 1.47 0 0 1 2 15.437V5.421c0-.976.92-1.687 1.878-1.497C10.197 5.177 11.421 3.5 16 3.5" />
                        <path
                            d="M2 7.5c1.951 0 3.705-1.595 3.929-3.246M18.5 4c0 2.04 1.765 3.969 3.5 3.969m0 5.531c-1.9 0-3.74 1.31-3.898 3.098M6 16.996a4 4 0 0 0-4-4m17 6.737a18.5 18.5 0 0 0-3-.233c-4.294 0-5.638 1.66-11 .703" />
                    </g>
                </svg>
            </div>
            <p class="text-sm text-neutral-500">Transaksi Penjualan</p>
        </div>
        <h1 class="text-3xl font-semibold  text-neutral-800 mt-4">Scan SN / Barcode Produk</h1>
    </div>
    {{-- CUSTOMER INFO PILLS (FIGMA STYLE) --}}
    @if ($selectedCustomerId || $isNewCustomer)
        <div class="flex flex-col gap-2">

            <div>
                <h1 class="text-sm font-semibold text-neutral-600">Customer :</h1>
            </div>

            <div class="flex flex-wrap gap-3">
                <div
                    class="px-5 py-2.5 bg-[#BDCEFF] text-neutral-900 rounded-xl text-sm font-bold shadow-sm shadow-blue-500/20">
                    {{ $this->displayCustomerName }}
                </div>

                @if ($this->displayCustomerPhone)
                    <div class="px-5 py-2.5 bg-[#D4F1FF] text-neutral-900 rounded-xl text-sm font-bold shadow-sm">
                        {{ $this->displayCustomerPhone }}
                    </div>
                @endif

                @if ($this->displayCustomerEmail)
                    <div class="px-5 py-2.5 bg-[#FFCDA2] text-neutral-900 rounded-xl text-sm font-bold shadow-sm">
                        {{ $this->displayCustomerEmail }}
                    </div>
                @endif
            </div>

        </div>
    @endif

    {{-- SCANNER AREA (MODERN POS STYLE) --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8 text-center relative overflow-hidden">

        <div class="relative z-10 max-w-2xl mx-auto">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Scan SN / Barcode</h2>
            <p class="text-sm text-gray-500 mb-6">Arahkan scanner ke barcode atau ketik manual</p>

            {{-- Input Area --}}
            <div class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-4 flex text-neutral-300 items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-auto" viewBox="0 0 24 24">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path fill="currentColor"
                            d="M4 6h2v12H4zm3 0h1v12H7zm2 0h3v12H9zm4 0h1v12h-1zm3 0h2v12h-2zm3 0h1v12h-1zM2 4v4H0V4a2 2 0 0 1 2-2h4v2zm20-2a2 2 0 0 1 2 2v4h-2V4h-4V2zM2 16v4h4v2H2a2 2 0 0 1-2-2v-4zm20 4v-4h2v4a2 2 0 0 1-2 2h-4v-2z" />
                    </svg>
                </div>

                <input type="text" wire:model.defer="scanned_sn" wire:keydown.enter="processScan"
                    x-ref="barcodeScanner"
                    class="block w-full pl-11 pr-16 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl text-center text-xl font-mono font-bold tracking-widest text-gray-800 placeholder-gray-300 focus:bg-white focus:border-blue-500 focus:ring-0 transition-all shadow-inner"
                    placeholder="SN / BARCODE" autofocus>

                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <kbd
                        class="hidden sm:inline-flex items-center px-2.5 py-1 border border-gray-200 rounded-lg text-xs font-sans font-semibold text-gray-400 bg-white shadow-sm">
                        ↵ Enter
                    </kbd>
                </div>
            </div>
        </div>
    </div>

    {{-- CART LIST (MODERN CARD STYLE) --}}
    @if (count($cart) > 0)
        <div
            class="bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 overflow-hidden flex flex-col">

            {{-- Header --}}
            <div class="p-5 sm:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800 text-lg">Konfirmasi Produk Pilihan</h3>
                <span class="text-xs font-bold bg-blue-100 text-[#1c69d4] px-3 py-1.5 rounded-full">
                    {{ count($cart) }} Item
                </span>
            </div>

            {{-- Cart Items --}}
            <div class="divide-y divide-gray-100/80">
                @foreach ($cart as $index => $item)
                    <div class="p-5 sm:p-6 hover:bg-gray-50/30 transition-colors group relative">

                        <div class="flex flex-col lg:flex-row gap-6">

                            {{-- Kiri: Info Produk & Diskon --}}
                            <div class="flex-1 space-y-3">
                                @php
                                    $nameParts = explode(' - ', $item['name']);

                                    // Hapus prefix 'DS' jika ada di awal nama
                                    if (isset($nameParts[0]) && trim(strtoupper($nameParts[0])) === 'DS') {
                                        array_shift($nameParts);
                                    }

                                    // Hapus prefix 'HP' di awal (bisa 'HP ' atau 'HP' saja)
                                    if (isset($nameParts[0])) {
                                        if (trim(strtoupper($nameParts[0])) === 'HP') {
                                            array_shift($nameParts);
                                        } else {
                                            $nameParts[0] = preg_replace('/^HP\s+/i', '', trim($nameParts[0]));
                                        }
                                    }

                                    $parsedStorage = null;
                                    $parsedColor = null;

                                    if (count($nameParts) >= 3) {
                                        $parsedColor = trim(array_pop($nameParts));
                                        $parsedStorage = trim(array_pop($nameParts));
                                        $baseName = trim(implode(' - ', $nameParts));
                                    } elseif (count($nameParts) == 2) {
                                        $lastPart = trim($nameParts[1]);
                                        if (preg_match('/^(\d+(GB|TB)?)$/i', str_replace(' ', '', $lastPart))) {
                                            $parsedStorage = trim(array_pop($nameParts));
                                            $baseName = trim(implode(' - ', $nameParts));
                                        } else {
                                            $baseName = trim($item['name']);
                                        }
                                    } else {
                                        $baseName = trim($item['name']);
                                    }

                                    $displayRam = $item['ram'] !== '-' ? $item['ram'] : null;
                                    $displayStorage = $item['storage'] !== '-' ? $item['storage'] : $parsedStorage;
                                    $displayColor = $item['color'] !== '-' ? $item['color'] : $parsedColor;
                                @endphp
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-bold text-gray-900 text-lg leading-tight pr-8 lg:pr-0">
                                            {{ $baseName }}</h4>
                                        {{-- @if ($item['is_second'])
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 uppercase tracking-widest shrink-0">Second</span>
                                        @endif --}}
                                    </div>
                                    @if ($displayRam || $displayStorage || $displayColor)
                                        <div
                                            class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-500">
                                            @if ($displayRam || $displayStorage)
                                                <span>{{ $displayRam ? $displayRam . ' / ' : '' }}{{ $displayStorage ?? '' }}</span>
                                            @endif

                                            @if (($displayRam || $displayStorage) && $displayColor)
                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                            @endif

                                            @if ($displayColor)
                                                <span>{{ $displayColor }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- MANUAL DISCOUNT PRESETS --}}
                                @php
                                    $presets = $this->getActiveManualDiscountPresets();
                                    $itemBrandId = $item['brand_id'] ?? null;
                                    $validPresets = $presets->filter(function ($p) use ($itemBrandId) {
                                        return is_null($p->brand_id) || ($itemBrandId && $p->brand_id == $itemBrandId);
                                    });
                                @endphp

                                @if ($validPresets->count() > 0)
                                    <div class="pt-2">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">
                                            Internal Cashback</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($validPresets as $preset)
                                                <button
                                                    wire:click="toggleManualDiscount({{ $index }}, {{ $preset->amount }})"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors {{ isset($item['discount_amount']) && $item['discount_amount'] == $preset->amount ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                                                    {{ number_format($preset->amount, 0, ',', '.') }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Tengah: Area Input Serial Number (Diberi width lebih besar) --}}
                            <div class="w-full lg:w-5/12 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                @if ($item['has_sn'])
                                    @php
                                        $currentSns = $item['serial_numbers'] ?? [];
                                        $isSnFull = count($currentSns) >= $item['qty'];
                                    @endphp

                                    <div class="flex items-center gap-2 mb-3">
                                        <input type="text" wire:model.defer="new_sns.{{ $index }}"
                                            wire:keydown.enter="addSerialNumber({{ $index }})"
                                            @if ($isSnFull) disabled @endif
                                            class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                            placeholder="{{ $isSnFull ? '✓ SN Terpenuhi' : 'Scan SN ke-' . (count($currentSns) + 1) . ' (Enter)' }}">
                                    </div>

                                    @if (count($currentSns) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($currentSns as $snIndex => $snValue)
                                                <div
                                                    class="inline-flex items-stretch bg-blue-50 border border-blue-200 rounded-md overflow-hidden shadow-sm group/sn">
                                                    <span
                                                        class="px-2.5 py-1 text-xs font-mono text-[#1c69d4] font-medium flex items-center">
                                                        {{ $snValue }}
                                                    </span>
                                                    <button type="button"
                                                        wire:click="removeSerialNumber({{ $index }}, {{ $snIndex }})"
                                                        class="px-1.5 py-1 bg-white hover:bg-rose-50 text-gray-400 hover:text-rose-500 border-l border-blue-200 transition-colors flex items-center">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <div class="h-full flex items-center justify-center">
                                        <span class="text-sm text-gray-400 font-medium italic">Tidak memerlukan Serial
                                            Number</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Kanan: Qty & Harga --}}
                            <div class="flex flex-col gap-4 lg:w-48 shrink-0">
                                <div class="flex flex-row lg:flex-col items-center lg:items-end justify-between gap-4">
                                    {{-- Qty Control --}}
                                    <div class="flex items-center bg-white border border-gray-200 rounded-lg shadow-sm">
                                        <button wire:click="decrementCartItem({{ $index }})"
                                            class="w-9 h-9 flex items-center justify-center text-gray-500 hover:text-[#1c69d4] hover:bg-blue-50 rounded-l-lg transition font-black text-lg">-</button>
                                        <input type="number" wire:model.lazy="cart.{{ $index }}.qty"
                                            class="w-12 h-9 text-center bg-transparent border-x border-gray-100 text-sm font-bold p-0 focus:ring-0"
                                            min="1">
                                        <button wire:click="incrementCartItem({{ $index }})"
                                            class="w-9 h-9 flex items-center justify-center text-gray-500 hover:text-[#1c69d4] hover:bg-blue-50 rounded-r-lg transition font-black text-lg">+</button>
                                    </div>

                                    {{-- Total Harga Item --}}
                                    <div class="text-right">
                                        <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">
                                            Subtotal</p>
                                        <p class="font-black text-gray-900 text-lg">Rp
                                            {{ number_format($item['price'] * $item['qty'] - ($item['discount_amount'] ?? 0), 0, ',', '.') }}
                                        </p>
                                        @if (isset($item['discount_amount']) && $item['discount_amount'] > 0)
                                            <p class="text-[10px] font-bold text-emerald-500 mt-0.5">(Termasuk Diskon Rp
                                                {{ number_format($item['discount_amount'], 0, ',', '.') }})</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Tombol Hapus (Selalu Tampil) --}}
                                <div
                                    class="flex justify-end border-t lg:border-t-0 border-gray-100 pt-3 lg:pt-0 lg:mt-auto">
                                    <button wire:click="removeFromCart({{ $index }})"
                                        class="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-rose-500 bg-rose-50 hover:bg-rose-100 hover:text-rose-600 rounded-lg transition-colors"
                                        title="Hapus Item">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer / Total --}}
            <div
                class="p-6 bg-gradient-to-r from-gray-50 to-[#1c69d4]/5 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                <span class="font-bold text-gray-500 uppercase tracking-widest text-sm">Total Tagihan Sementara</span>
                <span class="font-black text-3xl text-[#1c69d4]">Rp
                    {{ number_format($this->subtotal - $this->itemDiscountTotal, 0, ',', '.') }}</span>
            </div>

        </div>
    @endif

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-6">
        <button wire:click="prevStep"
            class="px-8 py-3.5 bg-white hover:bg-gray-50 border-2 border-gray-100 text-gray-700 font-black rounded-xl shadow-sm transition-all flex items-center gap-2">
            Kembali
        </button>
        <button wire:click="nextStep"
            class="px-8 py-3.5 bg-[#668DFF] hover:bg-[#4f7df8] text-white font-black rounded-xl shadow-[0_8px_15px_-3px_rgba(28,105,212,0.3)] hover:shadow-[0_12px_20px_-3px_rgba(28,105,212,0.4)] hover:-translate-y-0.5 transition-all flex items-center gap-2">
            Lanjut
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </div>
</div>
