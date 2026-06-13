<div class="space-y-4">
    {{-- SCANNER AREA --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Scan Barcode / SN Produk</h2>
            <div class="flex gap-3">
                <input type="text" wire:model.defer="scanned_sn" wire:keydown.enter="processScan"
                    x-ref="barcodeScanner"
                    class="flex-1 bg-white border border-gray-300 rounded-xl px-4 py-3 text-lg font-mono focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition"
                    placeholder="Scan SN / IMEI / Barcode di sini..." autofocus>
                <button wire:click="processScan"
                    class="px-6 py-3 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-sm transition">
                    Scan
                </button>
            </div>

        </div>
    </div>

    {{-- CART LIST --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Daftar Belanjaan
            </h3>
            <span class="text-xs font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded-md">{{ count($cart) }} Item</span>
        </div>

        <div class="p-0 overflow-y-auto max-h-[50vh]">
            @if (count($cart) === 0)
                <div class="p-12 text-center flex flex-col items-center justify-center text-gray-400">
                    <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <p class="font-bold text-lg text-gray-500">Keranjang masih kosong</p>
                    <p class="text-sm">Scan barcode untuk mulai menambahkan barang.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($cart as $index => $item)
                        <div class="p-4 hover:bg-gray-50 transition relative group">
                            <div class="flex gap-4">
                                {{-- Item Details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="font-bold text-gray-800 text-sm md:text-base leading-tight">
                                            {{ $item['name'] }}
                                            @if ($item['is_second'])
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">Second</span>
                                            @endif
                                        </h4>
                                        <div class="text-right ml-4">
                                            <p class="font-bold text-gray-800">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                    
                                    <p class="text-xs text-gray-500 mb-2">
                                        {{ $item['ram'] }} / {{ $item['storage'] }} / {{ $item['color'] }}
                                    </p>

                                    {{-- Qty Controls --}}
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center bg-white border border-gray-200 rounded-lg shadow-sm">
                                            <button wire:click="decrementCartItem({{ $index }})"
                                                class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-[#1c69d4] hover:bg-blue-50 rounded-l-lg transition">
                                                -
                                            </button>
                                            <input type="number" wire:model.lazy="cart.{{ $index }}.qty"
                                                class="w-12 h-8 text-center border-x border-y-0 border-gray-200 text-sm font-bold p-0 focus:ring-0 focus:border-gray-200"
                                                min="1">
                                            <button wire:click="incrementCartItem({{ $index }})"
                                                class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-[#1c69d4] hover:bg-blue-50 rounded-r-lg transition">
                                                +
                                            </button>
                                        </div>

                                        <p class="font-bold text-[#1c69d4]">
                                            Rp {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}
                                        </p>
                                    </div>

                                    {{-- SN Input Loop --}}
                                    @if ($item['has_sn'])
                                        <div class="mt-3 space-y-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                            <p class="text-xs font-bold text-gray-600 mb-1">Serial Numbers (Wajib):</p>
                                            @for ($i = 0; $i < $item['qty']; $i++)
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-gray-400 w-4">{{ $i + 1 }}.</span>
                                                    <input type="text"
                                                        wire:model.lazy="cart.{{ $index }}.serial_numbers.{{ $i }}"
                                                        class="flex-1 bg-white border border-gray-200 rounded-md px-3 py-1.5 text-xs font-mono focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition"
                                                        placeholder="Scan / Ketik SN">
                                                </div>
                                            @endfor
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Delete Button --}}
                            <button wire:click="removeFromCart({{ $index }})"
                                class="absolute top-4 right-4 p-1.5 text-gray-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition opacity-0 group-hover:opacity-100">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        {{-- Total Summary --}}
        <div class="p-4 bg-blue-50/50 border-t border-blue-100">
            <div class="flex justify-between items-center text-sm mb-1">
                <span class="text-gray-600 font-medium">Subtotal Barang:</span>
                <span class="font-bold text-gray-800">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between items-center text-lg mt-2 pt-2 border-t border-blue-200/50">
                <span class="font-bold text-gray-800">Total Tagihan Sementara:</span>
                <span class="font-black text-[#1c69d4]">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-2">
        <button wire:click="prevStep"
            class="px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </button>
        <button wire:click="nextStep"
            class="px-8 py-3 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
            Lanjut ke Promo & Add-ons
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </div>
</div>

{{-- MODAL KONFIRMASI SCAN --}}
@if($showScannedItemModal && $scannedItemConfirm)
<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="bg-[#1c69d4] p-6 text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-white/10 to-transparent"></div>
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 backdrop-blur-md">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white relative z-10">Konfirmasi Barang</h3>
            <p class="text-blue-100 text-sm mt-1 relative z-10">Pastikan barang & harga sesuai sebelum masuk keranjang</p>
        </div>

        <div class="p-6 space-y-4">
            <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Nama Produk</p>
                <p class="text-lg font-bold text-gray-800 leading-tight">{{ $scannedItemConfirm['name'] }}</p>
                
                <div class="flex flex-wrap gap-2 mt-3">
                    <span class="px-2 py-1 bg-white border border-gray-200 rounded text-xs font-bold text-gray-600">{{ $scannedItemConfirm['storage'] }} / {{ $scannedItemConfirm['ram'] }}</span>
                    <span class="px-2 py-1 bg-white border border-gray-200 rounded text-xs font-bold text-gray-600">{{ $scannedItemConfirm['color'] }}</span>
                    @if($scannedItemConfirm['isSecond'])
                        <span class="px-2 py-1 bg-amber-100 text-amber-800 border border-amber-200 rounded text-xs font-bold">Second</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
                    <p class="text-xs text-blue-600 font-bold uppercase tracking-wider mb-1">Harga Jual</p>
                    <p class="text-lg font-black text-[#1c69d4]">Rp {{ number_format($scannedItemConfirm['price'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">SN / IMEI</p>
                    <p class="text-sm font-mono font-bold text-gray-800 break-all">{{ $scannedItemConfirm['sn'] }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 pt-0 flex gap-3">
            <button wire:click="cancelScannedItem"
                class="flex-1 px-4 py-3 bg-white border-2 border-gray-200 hover:bg-gray-50 hover:border-gray-300 text-gray-700 font-bold rounded-xl transition-all">
                Batal
            </button>
            <button wire:click="confirmScannedItem"
                class="flex-1 px-4 py-3 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Masuk Keranjang
            </button>
        </div>
    </div>
</div>
@endif
