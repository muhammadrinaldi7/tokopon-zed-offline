<div>
    <div class="mb-6">
        <a href="{{ route('admin.sales-orders.index') }}" wire:navigate class="text-sm font-medium text-gray-500 hover:text-[#1c69d4] flex items-center gap-1 mb-2 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar SO
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Buat Sales Order Baru</h1>
        <p class="text-gray-500 text-sm mt-1">Input pesanan pelanggan untuk pembuatan SO di Accurate</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Header Info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-lg">Informasi Pesanan</h3>
                    <p class="text-xs text-gray-500">Pilih pelanggan dan tentukan tanggal pembuatan SO</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 flex justify-between">
                        <span>Pelanggan *</span>
                        <div wire:loading wire:target="searchCustomer" class="text-[#1c69d4]">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="searchCustomer" class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium placeholder-gray-400" placeholder="Ketik nama / email pelanggan..." autocomplete="off">
                    
                    @if(!empty($customerSearchResults))
                        <div class="absolute z-20 mt-2 w-full bg-white rounded-xl shadow-2xl border border-gray-100 max-h-60 overflow-y-auto">
                            @foreach($customerSearchResults as $res)
                                <div wire:click="selectCustomer({{ $res['id'] }}, '{{ addslashes($res['name']) }}')" class="px-5 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                    <div class="font-bold text-gray-900 text-sm">{{ $res['name'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $res['email'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    <input type="hidden" wire:model="user_id" required>
                    @error('user_id') <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Tanggal SO *</label>
                    <input type="date" wire:model="order_date" class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium" required>
                    @error('order_date') <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                <h3 class="font-bold text-gray-800 text-lg">Daftar Produk</h3>
                <button type="button" wire:click="addItem" class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold rounded-xl text-sm transition-colors flex items-center gap-2 border border-blue-100 shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Baris
                </button>
            </div>
            @error('items') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror

            @foreach($items as $index => $item)
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative group transition-all hover:border-blue-200" wire:key="item-{{ $index }}">
                @if(count($items) > 1)
                <button type="button" wire:click="removeItem({{ $index }})" class="absolute -top-3 -right-3 p-2 bg-white border border-gray-100 shadow-md text-red-500 hover:text-white hover:bg-red-500 rounded-full transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 z-10" title="Hapus Baris">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    <!-- Produk Search -->
                    <div class="lg:col-span-5 relative">
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Pilih Produk</label>
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.searchProduct" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors placeholder-gray-400 font-medium" placeholder="Ketik nama produk / SKU..." autocomplete="off">
                            <div wire:loading wire:target="items.{{ $index }}.searchProduct" class="absolute right-3 top-3 text-[#1c69d4]">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        @if(!empty($item['searchResults']))
                            <div class="absolute z-30 mt-2 left-0 right-0 bg-white rounded-xl shadow-2xl border border-gray-100 max-h-60 overflow-y-auto">
                                @foreach($item['searchResults'] as $res)
                                    <div wire:click="selectProduct({{ $index }}, {{ $res['id'] }}, '{{ addslashes($res['name']) }}', {{ $res['price'] }})" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                        <div class="font-bold text-gray-800 text-sm leading-tight">{{ $res['name'] }}</div>
                                        <div class="text-xs text-[#1c69d4] font-bold mt-1">Rp {{ number_format($res['price'], 0, ',', '.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <input type="hidden" wire:model="items.{{ $index }}.variant_id" required>
                    </div>

                    <div class="grid grid-cols-3 lg:col-span-5 gap-3">
                        <!-- Kuantitas -->
                        <div class="col-span-1">
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Qty</label>
                            <input type="number" min="1" wire:model.live.debounce.500ms="items.{{ $index }}.qty" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-center bg-gray-50 focus:bg-white transition-colors font-bold">
                        </div>

                        <!-- Harga -->
                        <div class="col-span-1">
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 text-right">Harga</label>
                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_price" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-right bg-gray-50 focus:bg-white transition-colors font-bold">
                        </div>

                        <!-- Diskon -->
                        <div class="col-span-1">
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 text-right">Diskon</label>
                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.discount" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm text-red-500 focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-right bg-red-50 focus:bg-white transition-colors font-bold placeholder-red-300" placeholder="0">
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="lg:col-span-2 flex flex-col justify-end h-full">
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 lg:text-right hidden lg:block">Total</label>
                        <div class="font-black text-[#1c69d4] text-right mt-1 lg:mt-0 text-lg border-t border-gray-100 lg:border-0 pt-3 lg:pt-0">
                            <span class="text-xs font-normal text-gray-400 lg:hidden mr-2 uppercase">Total:</span>Rp {{ number_format($item['total'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Footer Totals & Notes --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 pt-4">
            <div class="xl:col-span-7 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Catatan Pesanan</h3>
                        <p class="text-xs text-gray-500">Informasi tambahan untuk Sales Order</p>
                    </div>
                </div>
                <textarea wire:model="notes" rows="5" class="w-full flex-1 px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors resize-none font-medium placeholder-gray-400" placeholder="Tuliskan catatan khusus untuk pesanan ini..."></textarea>
            </div>
            
            <div class="xl:col-span-5 bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-xl border border-gray-700 p-6 md:p-8 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                </div>
                
                <h3 class="font-bold text-gray-300 mb-6 border-b border-gray-600/50 pb-4 uppercase tracking-wider text-[11px]">Ringkasan Pembayaran</h3>
                <div class="space-y-4 relative z-10">
                    <div class="flex justify-between items-center text-gray-400">
                        <span class="text-sm">Sub Total</span>
                        <span class="font-bold text-white text-lg">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-gray-400">
                        <span class="text-sm">Total Diskon</span>
                        <span class="font-bold text-red-400 text-lg">- Rp {{ number_format($discount_amount, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="pt-6 mt-4 border-t border-gray-600/50">
                        <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Total Akhir (Grand Total)</div>
                        <div class="font-black text-4xl text-emerald-400 tracking-tight">Rp {{ number_format($grand_total, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="mt-8 relative z-10">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="w-full px-6 py-4 bg-[#1c69d4] hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl transition-all shadow-lg hover:shadow-blue-500/30 flex items-center justify-center gap-3">
                        <div wire:loading.remove wire:target="save">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                        </div>
                        <div wire:loading wire:target="save">
                            <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <span wire:loading.remove wire:target="save">Buat Sales Order</span>
                        <span wire:loading wire:target="save">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
