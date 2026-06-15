<div class="space-y-6">
    {{-- CUSTOMER INFO PILLS (FIGMA STYLE) --}}
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

    {{-- SCANNER AREA (LARGE VISUAL) --}}
    <div class="bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 p-8 text-center relative overflow-hidden group">
        {{-- Decorative bracket icons --}}
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-5">
            <svg class="w-64 h-64 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.5">
                <path stroke-linecap="square" stroke-linejoin="miter" d="M4 6v12M20 6v12M4 6h4M20 6h-4M4 18h4M20 18h-4" />
            </svg>
        </div>

        <div class="relative z-10 max-w-xl mx-auto">
            <div class="w-20 h-20 mx-auto bg-blue-50 text-[#1c69d4] rounded-2xl flex items-center justify-center mb-6 shadow-sm group-hover:scale-110 transition-transform duration-300">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            
            <div class="flex items-center gap-2 mb-2">
                <input type="text" wire:model.defer="scanned_sn" wire:keydown.enter="processScan"
                    x-ref="barcodeScanner"
                    class="flex-1 bg-gray-50 border-0 border-b-2 border-gray-200 focus:border-[#1c69d4] focus:ring-0 px-2 py-3 text-center text-xl font-mono tracking-widest text-gray-800 transition-colors"
                    placeholder="SCAN SN / BARCODE" autofocus>
            </div>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Arahkan scanner ke barcode atau ketik manual lalu tekan Enter</p>
            
            {{-- Manual Search Area (Optional/Fallback) --}}
            <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-center gap-3">
                <span class="text-sm font-bold text-gray-500">Atau</span>
                <button onclick="document.getElementById('manual-search').focus()" class="text-[#1c69d4] font-bold text-sm hover:underline flex items-center gap-1">
                    Cari Produk Manual 
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </button>
                <input id="manual-search" type="text" wire:model.live.debounce.300ms="search" class="sr-only"> {{-- Hidden search bound to 'search' --}}
            </div>

            {{-- Manual Search Results Dropdown --}}
            @if (strlen($search) >= 2)
                <div class="absolute top-full left-0 w-full mt-2 bg-white border border-gray-100 rounded-2xl shadow-xl max-h-60 overflow-y-auto z-50 text-left">
                    @forelse($this->searchResults as $product)
                        <button wire:click="openVariantPicker({{ $product->id }}, {{ $product->is_second_catalog ? 'true' : 'false' }})"
                            class="w-full p-3 hover:bg-blue-50/50 text-left flex flex-col transition border-b border-gray-50 last:border-0">
                            <span class="font-bold text-gray-800">{{ $product->name }}</span>
                            <span class="text-xs text-[#1c69d4] font-bold">Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</span>
                        </button>
                    @empty
                        <p class="p-4 text-sm text-gray-500 font-medium text-center">Produk tidak ditemukan</p>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    {{-- CART LIST (CLEAN TABLE) --}}
    @if (count($cart) > 0)
        <div class="bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                <h3 class="font-black text-gray-800 text-lg">Konfirmasi Produk Pilihan</h3>
                <span class="text-xs font-black bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full">{{ count($cart) }} Item</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-xs text-gray-400 font-bold uppercase tracking-wider border-b border-gray-100">
                            <th class="px-6 py-4">Nama Produk / Tipe</th>
                            <th class="px-6 py-4">Spesifikasi</th>
                            <th class="px-6 py-4">Serial Number</th>
                            <th class="px-6 py-4 text-center">Qty</th>
                            <th class="px-6 py-4 text-right">Harga Satuan</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($cart as $index => $item)
                            <tr class="hover:bg-gray-50/30 transition-colors group">
                                <td class="px-6 py-5 align-top">
                                    <p class="font-bold text-gray-800 text-base leading-tight">{{ $item['name'] }}</p>
                                    @if ($item['is_second'])
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 uppercase tracking-widest">Second</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <p class="text-sm font-medium text-gray-600">{{ $item['storage'] }} / {{ $item['ram'] }}</p>
                                    <p class="text-sm font-medium text-gray-600">{{ $item['color'] }}</p>
                                    
                                    {{-- MANUAL DISCOUNT PRESETS --}}
                                    @php
                                        $presets = $this->getActiveManualDiscountPresets();
                                        $itemBrandId = $item['brand_id'] ?? null;
                                        $validPresets = $presets->filter(function($p) use ($itemBrandId) {
                                            return is_null($p->brand_id) || ($itemBrandId && $p->brand_id == $itemBrandId);
                                        });
                                    @endphp
                                    @if($validPresets->count() > 0)
                                    <div class="mt-4 pt-3 border-t border-gray-100/50">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Internal Cashback</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($validPresets as $preset)
                                                <button wire:click="toggleManualDiscount({{ $index }}, {{ $preset->amount }})"
                                                    class="px-2.5 py-1 rounded-md text-[10px] font-black border transition-colors {{ (isset($item['discount_amount']) && $item['discount_amount'] == $preset->amount) ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}">
                                                    {{ number_format($preset->amount, 0, ',', '.') }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-5 align-top min-w-[200px]">
                                    @if ($item['has_sn'])
                                        <div class="space-y-2">
                                            @for ($i = 0; $i < $item['qty']; $i++)
                                                <div class="flex items-center gap-2">
                                                    <input type="text"
                                                        wire:model.lazy="cart.{{ $index }}.serial_numbers.{{ $i }}"
                                                        class="flex-1 bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-mono focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition shadow-sm"
                                                        placeholder="Scan / Ketik SN">
                                                    
                                                    @if (($item['is_second'] ?? false) && !empty($item['serial_numbers'][$i] ?? ''))
                                                        <button type="button" wire:click="openQcSerahTerima('{{ $item['serial_numbers'][$i] }}')"
                                                            class="shrink-0 text-[10px] font-bold text-emerald-600 hover:bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-md transition-all">
                                                            QC
                                                        </button>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 font-medium italic">Tidak memerlukan SN</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="flex justify-center">
                                        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-lg shadow-sm w-max">
                                            <button wire:click="decrementCartItem({{ $index }})" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-[#1c69d4] hover:bg-blue-50 rounded-l-lg transition font-black">-</button>
                                            <input type="number" wire:model.lazy="cart.{{ $index }}.qty" class="w-10 h-8 text-center bg-transparent border-none text-sm font-bold p-0 focus:ring-0" min="1">
                                            <button wire:click="incrementCartItem({{ $index }})" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-[#1c69d4] hover:bg-blue-50 rounded-r-lg transition font-black">+</button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-top text-right">
                                    <p class="font-black text-gray-800 text-base">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                    @if(isset($item['discount_amount']) && $item['discount_amount'] > 0)
                                        <p class="text-xs font-bold text-rose-500 mt-1">- Rp {{ number_format($item['discount_amount'], 0, ',', '.') }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-5 align-top text-right">
                                    <button wire:click="removeFromCart({{ $index }})"
                                        class="p-2 text-gray-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="p-6 bg-[#1c69d4]/5 border-t border-[#1c69d4]/10 flex justify-between items-center">
                <span class="font-bold text-gray-500 uppercase tracking-widest text-xs">Total Tagihan Sementara</span>
                <span class="font-black text-2xl text-[#1c69d4]">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
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
            class="px-8 py-3.5 bg-[#1c69d4] hover:bg-blue-700 text-white font-black rounded-xl shadow-[0_8px_15px_-3px_rgba(28,105,212,0.3)] hover:shadow-[0_12px_20px_-3px_rgba(28,105,212,0.4)] hover:-translate-y-0.5 transition-all flex items-center gap-2">
            Lanjut
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </div>
</div>

{{-- MODAL KONFIRMASI SCAN --}}
@if($showScannedItemModal && $scannedItemConfirm)
<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="bg-[#1c69d4] p-8 text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-white/10 to-transparent"></div>
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-md">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-2xl font-black text-white relative z-10">Produk Ditemukan!</h3>
        </div>

        <div class="p-8 space-y-5">
            <div class="text-center">
                <p class="text-xl font-bold text-gray-800 leading-tight mb-2">{{ $scannedItemConfirm['name'] }}</p>
                <div class="flex items-center justify-center gap-2 mb-4">
                    <span class="px-3 py-1 bg-gray-100 rounded-full text-xs font-bold text-gray-600">{{ $scannedItemConfirm['storage'] }} / {{ $scannedItemConfirm['ram'] }}</span>
                    <span class="px-3 py-1 bg-gray-100 rounded-full text-xs font-bold text-gray-600">{{ $scannedItemConfirm['color'] }}</span>
                </div>
            </div>

            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-5 text-center">
                <p class="text-xs text-blue-600 font-bold uppercase tracking-widest mb-1">Harga Jual</p>
                <p class="text-2xl font-black text-[#1c69d4]">Rp {{ number_format($scannedItemConfirm['price'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="p-8 pt-0 flex gap-3">
            <button wire:click="cancelScannedItem"
                class="flex-1 px-4 py-4 bg-white border-2 border-gray-100 hover:bg-gray-50 text-gray-700 font-black rounded-xl transition-all">
                Batal
            </button>
            <button wire:click="confirmScannedItem"
                class="flex-1 px-4 py-4 bg-[#1c69d4] hover:bg-blue-700 text-white font-black rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2">
                Tambahkan
            </button>
        </div>
    </div>
</div>
@endif
