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
        <h1 class="text-3xl font-semibold  text-neutral-800 mt-2">Tambahkan Promo & Paket Pendukung</h1>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ADD-ON SECTION --}}
        <div class="bg-white rounded-3xl lg:col-span-2 shadow-sm border border-gray-100 p-6 flex flex-col h-120">
            <h3 class="font-black text-gray-800 text-lg mb-2 shrink-0">Add Ons</h3>
            <p class="text-xs text-gray-500 mb-6 shrink-0">Tawarkan produk pelengkap ke pelanggan.</p>

            <div class="relative mb-6 shrink-0">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
                <input wire:model.live.debounce.300ms="searchAddons" type="text" wire:key="input-search-addons"
                    class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl pl-12 pr-4 py-4 text-sm font-medium focus:border-[#1c69d4] focus:bg-white focus:ring-2 focus:ring-[#1c69d4]/20 shadow-sm transition-all"
                    placeholder="Cari aksesoris atau produk lain...">
            </div>

            <div class="flex-1 overflow-y-auto pr-2 no-scrollbar">
                <div class="grid grid-cols-2 md:grid-cols-3  gap-3">
                    @forelse($this->addonsResults as $product)
                        @php
                            $isInCart = collect($this->cart)->contains(function ($item) use ($product) {
                                return $item['variant_id'] == $product->id &&
                                    $item['variant_type'] == \App\Models\ProductAccurate::class;
                            });
                        @endphp
                        <div wire:click="selectAddon({{ $product->id }})"
                            class="relative flex items-center gap-4 p-3 rounded-xl border shadow-sm cursor-pointer transition-all group {{ $isInCart ? 'border-[#1c69d4] bg-blue-50/50 ring-1 ring-[#1c69d4]/50' : 'border-gray-100 hover:border-[#1c69d4] hover:bg-blue-50/50' }}">

                            @if ($isInCart)
                                <div class="absolute top-2 right-2 bg-[#1c69d4] text-white rounded-full p-1 shadow-md">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            @endif

                            <div class="w-7 h-7 rounded-xl overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                                <div class="w-full h-full flex items-center justify-center text-neutral-800">
                                    @php $productNameLower = strtolower($product->name); @endphp
                                    @if (str_contains($productNameLower, 'care'))
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    @elseif(str_contains($productNameLower, 'adapter'))
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    @elseif(str_contains($productNameLower, 'case'))
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-xs text-gray-800 truncate group-hover:text-[#1c69d4]">
                                    {{ $product->name }}</h4>
                                <p class="text-[10px] font-semibold text-blue-600 mt-1">Rp
                                    {{ number_format($product->base_price ?? 0, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center flex-1 flex flex-col justify-center items-center">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="text-sm text-gray-500 font-bold">Produk Add-Ons tidak ditemukan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        {{-- PROMO SECTION --}}
        <div
            class="bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 p-6 flex flex-col h-120">
            <h3 class="font-black text-gray-800 text-lg mb-2 shrink-0">Promo Tersedia</h3>
            <p class="text-xs text-gray-500 mb-6 shrink-0">Pilih promo yang berlaku untuk keranjang Anda saat ini.</p>

            <div class="space-y-3 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                @forelse ($this->activePromos as $promo)
                    <label
                        class="flex items-start gap-4 p-3 rounded-xl  {{ in_array($promo->id, $selectedPromos) ? 'border-[#1c69d4] border-2 bg-blue-50/50' : 'border-gray-100 border hover:border-blue-200 hover:bg-gray-50' }} cursor-pointer shadow-sm transition-all">
                        <div class="mt-1">
                            <input type="checkbox" wire:model.live="selectedPromos" value="{{ $promo->id }}"
                                class="w-5 h-5 text-[#1c69d4] border-gray-300 rounded focus:ring-[#1c69d4]">
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800  text-xs">{{ $promo->name }}</h4>
                            <p class="text-[10px] text-gray-500 mt-1 line-clamp-2">
                                {{ $promo->description ?? 'Nikmati potongan harga spesial.' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            @if ($promo->discount_type === 'fixed')
                                <span class="block text-sm font-semibold text-rose-500">-Rp
                                    {{ number_format($promo->discount_value, 0, ',', '.') }}</span>
                            @else
                                <span
                                    class="block text-sm font-semibold text-rose-500">-{{ $promo->discount_value }}%</span>
                            @endif
                            <span class="text-[10px] text-xs font-semibold text-gray-400 uppercase">Potongan</span>
                        </div>
                    </label>
                @empty
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 font-bold">Tidak ada promo aktif untuk barang di keranjang.</p>
                    </div>
                @endforelse
            </div>
        </div>


    </div>

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-6">
        <button wire:click="prevStep"
            class="px-8 py-3.5 bg-white hover:bg-gray-50 border-2 border-gray-100 text-gray-700 font-black rounded-xl shadow-sm transition-all flex items-center gap-2">
            Kembali
        </button>
        <button wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep"
            @if($this->hasZeroPriceItem) disabled @endif
            class="px-8 py-3.5 text-white font-black rounded-xl shadow-[0_8px_15px_-3px_rgba(28,105,212,0.3)] hover:shadow-[0_12px_20px_-3px_rgba(28,105,212,0.4)] hover:-translate-y-0.5 transition-all flex items-center gap-2 disabled:opacity-75 disabled:cursor-wait {{ $this->hasZeroPriceItem ? 'bg-gray-400 hover:bg-gray-400 cursor-not-allowed shadow-none hover:shadow-none hover:-translate-y-0' : 'bg-[#668DFF] hover:bg-[#4f7df8]' }}">
            <span wire:loading.remove wire:target="nextStep">Lanjut</span>
            <svg wire:loading.remove wire:target="nextStep" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            <span wire:loading.inline-flex wire:target="nextStep" class="items-center gap-2">
                <svg class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Memproses...
            </span>
        </button>
    </div>

    {{-- Modal Scan SN Khusus Addon --}}
    @if ($addonScanModalOpen)
        <div
            class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm transition-all">
            <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full mx-4"
                @click.away="$wire.closeAddonModal()">
                <div class="text-center">
                    <div
                        class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-[#1c69d4]">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-gray-800 mb-2">Scan Serial Number</h3>
                    <p class="text-sm text-gray-500 mb-6">Produk ini mewajibkan pencatatan Serial Number. Silakan scan
                        atau ketik SN pada kolom di bawah ini.</p>
                </div>
                <div class="mb-6 relative">
                    <input type="text" wire:model="addonSnInput" wire:keydown.enter="submitAddonSn"
                        x-data="{}" x-init="setTimeout(() => $el.focus(), 100)"
                        @focus-addon-sn-input.window="setTimeout(() => $el.focus(), 100)"
                        class="w-full bg-gray-50 border-2 border-gray-200 rounded-2xl px-4 py-4 text-center font-black tracking-wider focus:border-[#1c69d4] focus:bg-white focus:ring-0 transition-all uppercase"
                        placeholder="SCAN ATAU KETIK SN DI SINI" autocomplete="off">
                </div>
                <div class="flex gap-3">
                    <button wire:click="closeAddonModal"
                        class="flex-1 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-all">Batal</button>
                    <button wire:click="submitAddonSn"
                        class="flex-1 py-3 bg-[#1c69d4] text-white font-bold rounded-xl hover:bg-blue-700 transition-all">Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>
