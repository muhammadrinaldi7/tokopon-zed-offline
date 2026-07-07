<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="text-lg font-black text-gray-800">Ubah Barang (Swap Item)</h3>
                        <p class="text-sm text-gray-500">Pilih barang yang ingin diganti (swap) dengan varian/warna lain.</p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-red-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    @if ($swappingItemId)
                        <div class="mb-4 flex justify-between items-center bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <div>
                                <span class="text-sm font-bold text-blue-800">Mode Swap Aktif</span>
                                <p class="text-xs text-blue-600">Silakan cari produk pengganti di bawah ini.</p>
                            </div>
                            <button wire:click="cancelSwap" class="text-sm text-red-500 font-bold hover:underline">Batal Swap</button>
                        </div>
                        
                        <div class="mb-4">
                            <input type="text" wire:model.live.debounce.300ms="searchQuery" 
                                placeholder="Cari nama produk / SKU pengganti..."
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1c69d4] focus:border-transparent outline-none transition">
                        </div>

                        @if(count($searchResults) > 0)
                            <div class="space-y-3">
                                @foreach ($searchResults as $res)
                                    <div class="p-4 border rounded-xl flex justify-between items-center hover:bg-gray-50 transition cursor-pointer"
                                         wire:click="executeSwap({{ $res['id'] }}, '{{ addslashes($res['type']) }}', {{ $res['price'] }})">
                                        <div>
                                            <h4 class="font-bold text-gray-800">{{ $res['name'] }}</h4>
                                            <p class="text-xs text-gray-400 font-mono">SKU: {{ $res['sku'] ?? '-' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-black text-[#1c69d4]">Rp {{ number_format($res['price'], 0, ',', '.') }}</p>
                                            <p class="text-xs text-emerald-600 font-bold mt-1">Stok: {{ $res['stock'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(strlen($searchQuery) >= 3)
                            <div class="p-8 text-center text-gray-500">
                                Tidak menemukan varian dengan stok tersedia.
                            </div>
                        @endif
                    @elseif ($order)
                        <div class="space-y-4">
                            @foreach ($order->items as $item)
                                <div class="p-4 border rounded-lg flex justify-between items-center bg-white shadow-sm hover:shadow-md transition">
                                    <div>
                                        <h4 class="font-bold text-gray-800">{{ $item->product_name }}</h4>
                                        <p class="text-sm text-gray-600">Varian: {{ $item->variant?->name ?? $item->variant?->label ?? '-' }}</p>
                                        <p class="text-xs text-gray-400 font-mono">SN/IMEI: {{ $item->serial_number ?? '-' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-[#1c69d4]">Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                                        <button wire:click="startSwap({{ $item->id }})" class="mt-2 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold rounded text-xs transition">
                                            Ganti Varian
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-gray-50 border-t flex justify-end">
                    <button wire:click="closeModal" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
