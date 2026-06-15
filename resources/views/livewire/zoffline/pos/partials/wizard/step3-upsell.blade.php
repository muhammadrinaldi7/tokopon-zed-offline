<div class="{{ $currentStep == 3 ? 'block' : 'hidden' }} space-y-6">
    {{-- PILIH PROMO --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <p class="text-sm text-gray-500 mb-6">Kasir dapat menawarkan barang aksesoris/tambahan di sini sebelum memilih promo.</p>
            
            {{-- SEARCH ADD-ONS --}}
            <div class="mb-8">
                <label class="text-xs font-bold text-gray-600 uppercase mb-2 block">Cari Aksesoris / Add-ons</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <input wire:model.live.debounce.300ms="searchAddons" type="text" 
                        class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 shadow-sm"
                        placeholder="Ketik nama atau SKU aksesoris...">
                </div>

                {{-- HASIL PENCARIAN ADD-ONS --}}
                @if(strlen($searchAddons) >= 2)
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                        @forelse($this->addonsResults as $product)
                            <div wire:click="openVariantPicker({{ $product->id }}, {{ $product->is_second_catalog ? 'true' : 'false' }})" 
                                class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-[#1c69d4] hover:bg-blue-50 cursor-pointer transition-all group bg-white shadow-sm">
                                
                                <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                                    @if($product->media->isNotEmpty())
                                        <img src="{{ url('storage/' . $product->media->first()->file_path) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-gray-800 truncate group-hover:text-[#1c69d4]">{{ $product->name }}</h4>
                                    <p class="text-xs font-medium text-emerald-600">Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</p>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-[#1c69d4] group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-4 text-center">
                                <p class="text-sm text-gray-500 font-medium">Aksesoris tidak ditemukan.</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
            
            <div class="border-t border-gray-100 my-6"></div>
            
            <div class="space-y-1.5 max-w-2xl">
                <label class="text-xs font-bold text-gray-600 uppercase">Promo Code / Voucher Tersedia</label>
                <select multiple wire:model.live="selectedPromos"
                    class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 min-h-[150px]">
                    @foreach ($this->activePromos as $promo)
                        <option value="{{ $promo->id }}" class="p-2 border-b border-gray-50 hover:bg-blue-50">
                            ⭐ {{ $promo->name }} 
                            @if($promo->discount_type === 'fixed')
                                (Diskon Rp {{ number_format($promo->discount_value, 0, ',', '.') }})
                            @else
                                (Diskon {{ $promo->discount_value }}%)
                            @endif
                        </option>
                    @endforeach
                </select>
                @if(count($this->activePromos) === 0)
                    <p class="text-sm text-amber-600 font-bold mt-2">Tidak ada promo yang valid untuk barang di keranjang saat ini.</p>
                @endif
                <p class="text-[11px] text-gray-400 font-medium mt-1">Tahan tombol CTRL/CMD untuk memilih lebih dari 1 promo.</p>
            </div>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-4 border-t border-gray-200 mt-6">
        <button wire:click="prevStep"
            class="px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </button>
        <button wire:click="nextStep"
            class="px-8 py-3 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-md shadow-[#1c69d4]/20 transition-all flex items-center gap-2">
            Lanjut ke Pembayaran
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </div>
</div>
