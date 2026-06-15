<div class="space-y-6">
    {{-- CUSTOMER INFO PILLS --}}
    @if ($selectedCustomerId || $isNewCustomer)
        @php 
            $cName = $selectedCustomerId ? (\App\Models\User::find($selectedCustomerId)->name ?? 'Pelanggan') : ($customerName ?: 'Pelanggan Baru');
            $cPhone = $selectedCustomerId ? (\App\Models\User::with('profile')->find($selectedCustomerId)->profile->phone_number ?? '') : $customerPhone;
            $cEmail = $selectedCustomerId ? (\App\Models\User::find($selectedCustomerId)->email ?? '') : $customerEmail;
        @endphp
        <div class="flex flex-wrap gap-3">
            <div class="px-5 py-2.5 bg-blue-500 text-white rounded-full text-sm font-bold shadow-sm shadow-blue-500/20">
                {{ $cName }}
            </div>
            @if($cPhone)
            <div class="px-5 py-2.5 bg-blue-100 text-blue-800 rounded-full text-sm font-bold shadow-sm">
                {{ $cPhone }}
            </div>
            @endif
            @if($cEmail)
            <div class="px-5 py-2.5 bg-orange-100 text-orange-800 rounded-full text-sm font-bold shadow-sm">
                {{ $cEmail }}
            </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- PROMO SECTION --}}
        <div class="bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 p-8 h-max">
            <h3 class="font-black text-gray-800 text-lg mb-2">Promo Code / Voucher Tersedia</h3>
            <p class="text-sm text-gray-500 mb-6">Pilih promo yang berlaku untuk keranjang Anda saat ini.</p>
            
            <div class="space-y-3">
                @forelse ($this->activePromos as $promo)
                    <label class="flex items-start gap-4 p-4 rounded-2xl border-2 {{ in_array($promo->id, $selectedPromos) ? 'border-[#1c69d4] bg-blue-50/50' : 'border-gray-100 hover:border-blue-200 hover:bg-gray-50' }} cursor-pointer transition-all">
                        <div class="mt-1">
                            <input type="checkbox" wire:model.live="selectedPromos" value="{{ $promo->id }}" class="w-5 h-5 text-[#1c69d4] border-gray-300 rounded focus:ring-[#1c69d4]">
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800 text-base">{{ $promo->name }}</h4>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $promo->description ?? 'Nikmati potongan harga spesial.' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            @if($promo->discount_type === 'fixed')
                                <span class="block font-black text-rose-500">-Rp {{ number_format($promo->discount_value, 0, ',', '.') }}</span>
                            @else
                                <span class="block font-black text-rose-500">-{{ $promo->discount_value }}%</span>
                            @endif
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Potongan</span>
                        </div>
                    </label>
                @empty
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 font-bold">Tidak ada promo aktif untuk barang di keranjang.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ADD-ON SECTION --}}
        <div class="bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 p-8 flex flex-col h-max">
            <h3 class="font-black text-gray-800 text-lg mb-2">Aksesoris / Tambahan Produk</h3>
            <p class="text-sm text-gray-500 mb-6">Tawarkan produk pelengkap ke pelanggan.</p>
            
            <div class="relative mb-6">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </span>
                <input wire:model.live.debounce.300ms="searchAddons" type="text" 
                    class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl pl-12 pr-4 py-4 text-sm font-medium focus:border-[#1c69d4] focus:bg-white focus:ring-2 focus:ring-[#1c69d4]/20 shadow-sm transition-all"
                    placeholder="Cari aksesoris atau produk lain...">
            </div>

            @if(strlen($searchAddons) >= 2)
                <div class="grid grid-cols-1 gap-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                    @forelse($this->addonsResults as $product)
                        <div wire:click="openVariantPicker({{ $product->id }}, {{ $product->is_second_catalog ? 'true' : 'false' }})" 
                            class="flex items-center gap-4 p-3 rounded-2xl border border-gray-100 hover:border-[#1c69d4] hover:bg-blue-50/50 cursor-pointer transition-all group">
                            
                            <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                                @if($product->media->isNotEmpty())
                                    <img src="{{ url('storage/' . $product->media->first()->file_path) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-sm text-gray-800 truncate group-hover:text-[#1c69d4]">{{ $product->name }}</h4>
                                <p class="text-sm font-black text-gray-600 mt-1">Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-[#1c69d4] flex items-center justify-center group-hover:bg-[#1c69d4] group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <p class="text-sm text-gray-500 font-bold">Produk tidak ditemukan.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-sm font-medium">Mulai ketik untuk mencari produk tambahan</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-6">
        <button wire:click="prevStep"
            class="px-8 py-3.5 bg-white hover:bg-gray-50 border-2 border-gray-100 text-gray-700 font-black rounded-xl shadow-sm transition-all flex items-center gap-2">
            Kembali
        </button>
        <button wire:click="nextStep"
            class="px-8 py-3.5 bg-[#1c69d4] hover:bg-blue-700 text-white font-black rounded-xl shadow-[0_8px_15px_-3px_rgba(28,105,212,0.3)] hover:shadow-[0_12px_20px_-3px_rgba(28,105,212,0.4)] hover:-translate-y-0.5 transition-all flex items-center gap-2">
            Lanjut
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </div>
</div>
