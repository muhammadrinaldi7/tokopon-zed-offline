            {{-- Cart Header --}}
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                <h2 class="font-black text-gray-900 text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                    Keranjang
                    @if (!empty($cart))
                        <span
                            class="bg-[#1c69d4] text-white text-xs font-black px-2.5 py-0.5 rounded-full ml-1">{{ count($cart) }}</span>
                    @endif
                </h2>

                {{-- Tombol Close (Hanya tampil di mobile) --}}
                <button @click="openCart = false"
                    class="lg:hidden p-1 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-md transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Cart Items --}}
            <div
                class="max-h-[170px] overflow-y-auto px-4 py-2.5 space-y-2.5 border-b border-gray-100 shrink-0 bg-white">
                @forelse($cart as $index => $item)
                    <div class="bg-gray-50 rounded-lg p-2.5 border border-gray-100 relative group">
                        <button wire:click="removeFromCart({{ $index }})"
                            class="absolute top-2.5 right-2.5 text-gray-300 hover:text-rose-500 transition lg:opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="flex justify-between items-start mb-1.5">
                            <div class="pr-6">
                                <h4 class="font-bold text-gray-800 text-xs">{{ $item['name'] }}</h4>
                                <p class="text-[10px] text-gray-400 uppercase font-bold">{{ $item['color'] }} -
                                    {{ $item['storage'] }}
                                    @if ($item['is_second'] ?? false)
                                        <span class="text-emerald-500">• Second</span>
                                    @endif
                                </p>
                            </div>
                            <p class="font-bold text-gray-800 text-xs whitespace-nowrap">Rp
                                {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1">
                                <button wire:click="decrementCartItem({{ $index }})"
                                    class="w-6 h-6 rounded bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition text-xs font-bold">−</button>
                                <span class="w-6 text-center font-bold text-xs">{{ $item['qty'] }}</span>
                                <button wire:click="incrementCartItem({{ $index }})"
                                    class="w-6 h-6 rounded bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition text-xs font-bold">+</button>
                            </div>
                            <p class="text-[10px] text-gray-400">@ Rp {{ number_format($item['price'], 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- SN Input --}}
                        @php
                            $snArray = $item['serial_numbers'] ?? [$item['serial_number'] ?? ''];
                        @endphp
                        <div class="mt-2 space-y-2">
                            @foreach ($snArray as $snIndex => $snValue)
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <input type="text" id="sn_input_{{ $index }}_{{ $snIndex }}"
                                            wire:change="updateSerialNumber({{ $index }}, {{ $snIndex }}, $event.target.value)"
                                            value="{{ $snValue }}"
                                            class="w-full bg-white border border-gray-200 rounded px-2.5 py-1 text-[11px] font-mono focus:border-[#1c69d4] focus:ring-0 transition-all placeholder-gray-300"
                                            placeholder="SN / IMEI {{ count($snArray) > 1 ? 'ke-' . ($snIndex + 1) : '' }}...">

                                        <button type="button"
                                            onclick="startScanner({{ $index }}, {{ $snIndex }})"
                                            class="shrink-0 bg-[#1c69d4] hover:bg-blue-700 text-white border border-[#1c69d4] rounded px-2 py-1 transition-all focus:outline-none focus:ring-2 focus:ring-[#1c69d4] focus:ring-offset-1"
                                            title="Scan Barcode Kamera">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    @if(($item['is_second'] ?? false) && $snValue)
                                        <div class="flex justify-end">
                                            <a href="{{ route('qc.inspect', ['secondProductVariant' => $item['variant_id'], 'imei' => $snValue]) }}" target="_blank" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-1 rounded border border-emerald-100 flex items-center gap-1 transition shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Lakukan QC Serah Terima
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-6 text-gray-300">
                        <svg class="w-8 h-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <p class="text-xs font-bold text-gray-400">Keranjang kosong</p>
                    </div>
                @endforelse
            </div>
