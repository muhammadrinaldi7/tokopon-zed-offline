<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="text-lg font-black text-gray-800">Ubah Barang (Swap Item)</h3>
                        <p class="text-sm text-gray-500">Pilih barang yang ingin diganti (swap) dengan varian/warna lain.
                        </p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-red-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    @if ($selectedCandidate)
                        <!-- STEP 2: KONFIRMASI -->
                        <div class="flex flex-col items-center justify-center text-center py-6 px-4">
                            <div
                                class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <h4 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Penukaran Barang</h4>
                            <p class="text-gray-500 mb-6 max-w-md">Anda akan menukar barang lama dengan barang baru.
                                Pastikan barang pengganti sudah benar sebelum melanjutkan.</p>

                            <div
                                class="w-full max-w-lg bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm text-left mb-8">
                                <div class="bg-gray-50 p-4 border-b border-gray-200 flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-700">Barang Pengganti (Baru)</span>
                                </div>
                                <div class="p-5">
                                    <h5 class="font-black text-gray-800 text-lg">{{ $selectedCandidate['name'] }}</h5>
                                    <div class="flex justify-between items-center mt-3">
                                        <div class="text-sm text-gray-500">SKU: <span
                                                class="font-mono font-bold text-gray-700">{{ $selectedCandidate['sku'] ?: '-' }}</span>
                                        </div>
                                        <div class="text-lg font-black text-[#1c69d4]">Rp
                                            {{ number_format($selectedCandidate['price'], 0, ',', '.') }}</div>
                                    </div>
                                    <div
                                        class="mt-2 text-xs font-bold text-emerald-600 bg-emerald-50 inline-block px-2 py-1 rounded-md border border-emerald-100">
                                        Sisa Stok: {{ $selectedCandidate['stock'] }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-4">
                                <button wire:click="clearCandidate" wire:loading.attr="disabled"
                                    class="px-6 py-3 bg-white border-2 border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition focus:ring-2 focus:ring-gray-200">
                                    Pilih Barang Lain
                                </button>
                                <button wire:click="executeSwap" wire:loading.attr="disabled"
                                    class="px-6 py-3 bg-[#1c69d4] text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition focus:ring-2 focus:ring-blue-300 flex items-center">
                                    <span wire:loading.remove wire:target="executeSwap">Ya, Tukar Sekarang</span>
                                    <span wire:loading wire:target="executeSwap">Memproses...</span>
                                </button>
                            </div>
                        </div>
                    @elseif ($swappingItemId)
                        <!-- STEP 1: CARI BARANG -->
                        <div
                            class="mb-5 flex justify-between items-start bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-xl border border-blue-100 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-blue-800 block">Pilih Barang Pengganti</span>
                                    <p class="text-xs text-blue-600/80 mt-0.5">Ketik minimal 3 huruf untuk mencari nama
                                        barang atau SKU.</p>
                                </div>
                            </div>
                            <button wire:click="cancelSwap"
                                class="text-xs text-red-500 font-bold hover:bg-red-50 px-3 py-1.5 rounded-lg border border-transparent hover:border-red-100 transition">Batal
                                Swap</button>
                        </div>

                        <div class="mb-4">
                            <input type="text" wire:model.live.debounce.300ms="searchQuery"
                                placeholder="Cari nama produk / SKU pengganti..."
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1c69d4] focus:border-transparent outline-none transition">
                        </div>

                        @if (count($searchResults) > 0)
                            <div class="space-y-3">
                                @foreach ($searchResults as $res)
                                    <div class="p-4 border border-gray-100 rounded-xl flex justify-between items-center bg-white hover:bg-blue-50 hover:border-blue-200 transition-all cursor-pointer group shadow-sm hover:shadow-md"
                                        wire:click="selectCandidate({{ $res['id'] }}, '{{ addslashes($res['type']) }}', {{ $res['price'] }}, '{{ addslashes($res['name']) }}', '{{ addslashes($res['sku'] ?? '') }}', {{ $res['stock'] }})">
                                        <div>
                                            <h4 class="font-bold text-gray-800">{{ $res['name'] }}</h4>
                                            <p class="text-xs text-gray-400 font-mono">SKU: {{ $res['sku'] ?? '-' }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-black text-[#1c69d4]">Rp
                                                {{ number_format($res['price'], 0, ',', '.') }}</p>
                                            <p class="text-xs text-emerald-600 font-bold mt-1">Stok:
                                                {{ $res['stock'] }}</p>
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
                        <div class="mb-4 text-sm text-gray-500">
                            Pilih barang dari pesanan ini yang ingin di tukar.
                        </div>
                        <div class="space-y-3">

                            @foreach ($order->items as $item)
                                <div
                                    class="p-5 border border-gray-100 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white hover:bg-gray-50 hover:border-gray-200 transition-all shadow-sm hover:shadow-md group">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 shrink-0 border border-blue-100">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-base leading-tight mb-1">
                                                {{ $item->variant?->name ?? ($item->variant?->label ?? '-') }}</h4>
                                            {{-- <div class="text-sm text-gray-600 mb-0.5">
                                                <span class="font-medium text-gray-500">Varian:</span>
                                               
                                            </div> --}}
                                            @if ($item->serial_number)
                                                <div
                                                    class="text-xs text-gray-400 bg-gray-100 inline-block px-2 py-0.5 rounded border border-gray-200 font-mono">
                                                    SN/IMEI: {{ $item->serial_number }}
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-400 font-mono">
                                                    SN/IMEI: -
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div
                                        class="mt-4 sm:mt-0 sm:text-right flex flex-row sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto gap-3">
                                        <div class="font-black text-gray-800 text-lg">
                                            Rp {{ number_format($item->price_at_checkout, 0, ',', '.') }}
                                        </div>
                                        <button wire:click="startSwap({{ $item->id }})"
                                            class="px-4 py-2 bg-white border-2 border-blue-100 text-blue-600 hover:bg-blue-50 hover:border-blue-200 font-bold rounded-xl text-sm transition-all focus:ring-2 focus:ring-blue-100 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                            Ganti Varian
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if (!$selectedCandidate)
                    <div class="p-4 bg-gray-50 border-t flex justify-end">
                        <button wire:click="closeModal"
                            class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition focus:ring-2 focus:ring-gray-200">
                            Tutup
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
