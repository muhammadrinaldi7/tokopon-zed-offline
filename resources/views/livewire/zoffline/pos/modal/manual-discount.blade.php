@if ($showManualDiscountModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" wire:click="closeManualDiscountModal">
        </div>

        <!-- Modal Content -->
        <div
            class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Pilih Cashback Internal</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Silahkan pilih nominal cashback yang tersedia</p>
                </div>
                <button wire:click="closeManualDiscountModal"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto">
                @php
                    $presets = $this->getActiveManualDiscountPresets();
                    $itemBrandId = $manualDiscountCartIndex !== null ? ($cart[$manualDiscountCartIndex]['brand_id'] ?? null) : null;
                    $validPresets = $presets->filter(function ($p) use ($itemBrandId) {
                        return is_null($p->brand_id) || ($itemBrandId && $p->brand_id == $itemBrandId);
                    });
                @endphp

                @if ($validPresets->count() > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($validPresets as $preset)
                            @php
                                $isActive = isset($cart[$manualDiscountCartIndex]['discount_amount']) && $cart[$manualDiscountCartIndex]['discount_amount'] == $preset->amount;
                            @endphp
                            <button wire:click="toggleManualDiscount({{ $manualDiscountCartIndex }}, {{ $preset->amount }})"
                                class="flex flex-col items-center justify-center p-4 rounded-xl border-2 transition-all {{ $isActive ? 'border-indigo-600 bg-indigo-50/50' : 'border-gray-100 hover:border-indigo-200 hover:bg-gray-50' }}">
                                <span class="text-xs font-semibold text-gray-500 mb-1">Rp</span>
                                <span class="text-lg font-bold {{ $isActive ? 'text-indigo-700' : 'text-gray-900' }}">
                                    {{ number_format($preset->amount, 0, ',', '.') }}
                                </span>
                                @if($isActive)
                                    <div class="mt-2 w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Tidak ada cashback tersedia untuk produk ini.</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end">
                <button wire:click="closeManualDiscountModal"
                    class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold text-sm hover:bg-gray-50 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endif
