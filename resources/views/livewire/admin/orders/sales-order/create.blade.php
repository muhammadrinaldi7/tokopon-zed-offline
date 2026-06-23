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
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Informasi Pelanggan & SO</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="relative">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex justify-between">
                        <span>Pelanggan *</span>
                        <div wire:loading wire:target="searchCustomer" class="text-[#1c69d4]">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="searchCustomer" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50/50 hover:bg-gray-50 transition-colors" placeholder="Ketik nama / email pelanggan..." autocomplete="off">
                    
                    @if(!empty($customerSearchResults))
                        <div class="absolute z-10 mt-1 w-full bg-white rounded-xl shadow-lg border border-gray-100 max-h-60 overflow-y-auto">
                            @foreach($customerSearchResults as $res)
                                <div wire:click="selectCustomer({{ $res['id'] }}, '{{ addslashes($res['name']) }}')" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                    <div class="font-bold text-gray-800 text-sm">{{ $res['name'] }}</div>
                                    <div class="text-xs text-gray-400">{{ $res['email'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    <input type="hidden" wire:model="user_id" required>
                    @error('user_id') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>


                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal SO *</label>
                    <input type="date" wire:model="order_date" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50/50 hover:bg-gray-50 transition-colors" required>
                    @error('order_date') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-x-auto">
            <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Daftar Barang</h3>
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="p-3 font-bold rounded-tl-lg w-[45%]">Pilih Barang</th>
                        <th class="p-3 font-bold w-[10%]">Kuantitas</th>
                        <th class="p-3 font-bold w-[15%]">Harga Satuan</th>
                        <th class="p-3 font-bold w-[15%]">Diskon (Rp)</th>
                        <th class="p-3 font-bold w-[15%]">Total Harga</th>
                        <th class="p-3 font-bold text-center rounded-tr-lg">Hapus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $index => $item)
                        <tr class="group hover:bg-blue-50/30 transition-colors" wire:key="item-{{ $index }}">

                            <td class="p-2 relative">
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.searchProduct" class="w-full px-3 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-white pr-8" placeholder="Ketik nama produk / SKU..." autocomplete="off">
                                    <div wire:loading wire:target="items.{{ $index }}.searchProduct" class="absolute right-3 top-3 text-[#1c69d4]">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                
                                @if(!empty($item['searchResults']))
                                    <div class="absolute z-10 mt-1 left-2 right-2 w-64 md:w-full bg-white rounded-xl shadow-xl border border-gray-100 max-h-60 overflow-y-auto">
                                        @foreach($item['searchResults'] as $res)
                                            <div wire:click="selectProduct({{ $index }}, {{ $res['id'] }}, '{{ addslashes($res['name']) }}', {{ $res['price'] }})" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                                <div class="font-bold text-gray-800 text-sm whitespace-normal">{{ $res['name'] }}</div>
                                                <div class="text-xs text-[#1c69d4] font-medium mt-1">Rp {{ number_format($res['price'], 0, ',', '.') }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <input type="hidden" wire:model="items.{{ $index }}.variant_id" required>
                            </td>
                            <td class="p-2">
                                <input type="number" min="1" wire:model.live.debounce.500ms="items.{{ $index }}.qty" class="w-full px-3 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-center bg-white font-medium">
                            </td>
                            <td class="p-2">
                                <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_price" class="w-full px-3 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-right bg-white font-medium">
                            </td>
                            <td class="p-2">
                                <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.discount" class="w-full px-3 py-2.5 rounded-xl border-gray-200 text-sm text-red-500 focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-right bg-white font-medium placeholder-red-300" placeholder="0">
                            </td>
                            <td class="p-2 text-right">
                                <div class="font-bold text-gray-800 bg-gray-50 px-4 py-2.5 rounded-xl border border-gray-200 shadow-inner flex items-center justify-end h-full">
                                    Rp {{ number_format($item['total'], 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="p-2 text-center">
                                @if(count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Hapus Baris">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="mt-4">
                <button type="button" wire:click="addItem" class="px-4 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-bold rounded-lg text-sm transition-colors flex items-center gap-2 border border-emerald-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Baris
                </button>
                @error('items') <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Footer Totals & Notes --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Catatan Pesanan</label>
                <textarea wire:model="notes" rows="5" class="w-full flex-1 px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50/50 hover:bg-gray-50 transition-colors resize-none" placeholder="Tuliskan catatan khusus untuk pesanan ini..."></textarea>
            </div>
            
            <div class="bg-gray-800 rounded-2xl shadow-sm border border-gray-700 p-6 text-white">
                <h3 class="font-bold text-gray-300 mb-4 border-b border-gray-600 pb-3 uppercase tracking-wider text-xs">Ringkasan Pesanan</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-gray-400">
                        <span>Sub Total</span>
                        <span class="font-medium text-white">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-gray-400">
                        <span>Total Diskon</span>
                        <span class="font-medium text-red-400">- Rp {{ number_format($discount_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-600 flex justify-between items-center">
                        <span class="font-bold text-lg">Total Akhir (Grand Total)</span>
                        <span class="font-black text-2xl text-emerald-400">Rp {{ number_format($grand_total, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="w-full sm:w-auto px-6 py-3 bg-[#1c69d4] hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2">
                        <div wire:loading.remove wire:target="save">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                        </div>
                        <div wire:loading wire:target="save">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <span wire:loading.remove wire:target="save">Simpan Sales Order</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
