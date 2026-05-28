<div class="bg-gray-100" x-data="{ showSidebar: false }">

    {{-- Bungkus layout utama dengan x-data dari Alpine.js --}}
    <div x-data="{ openCart: false }" class="relative flex h-[calc(100vh-72px)] overflow-hidden">

        @include('livewire.admin.pos.partials.product-grid')

        {{-- Overlay Background (Muncul saat cart dibuka di mobile) --}}
        <div x-show="openCart" x-transition.opacity x-cloak @click="openCart = false"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden">
        </div>

        {{-- ═══════════════════════════════════════════════════════════
         RIGHT PANEL: Cart, Customer & Payment (Drawer on Mobile)
    ═══════════════════════════════════════════════════════════ --}}
        <div :class="openCart ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
            class="fixed lg:static inset-y-0 right-0 z-50 md:z-10 w-[85%] sm:w-[420px] transform transition-transform duration-300 ease-in-out bg-white border-l border-gray-200 flex flex-col shrink-0 h-full shadow-2xl lg:shadow-none">

            @include('livewire.admin.pos.partials.cart-panel')

            {{-- Form Section: Customer, Payments, Discount --}}
            <div class="flex-1 overflow-y-auto bg-gray-50 divide-y divide-gray-100 min-h-0">
                @include('livewire.admin.pos.partials.customer-section')

                @include('livewire.admin.pos.partials.payment-section')
            </div>

            {{-- Pinned Footer: Totals & Pay Button --}}
            <div class="border-t border-gray-200 bg-white shrink-0 p-4 space-y-3.5">
                <div class="space-y-1.5">
                    <div class="flex justify-between text-xs font-medium text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-bold text-gray-800">Rp
                            {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>
                    
                    @if ($this->totalPromoDiscount > 0)
                        <div class="flex justify-between text-xs font-medium text-emerald-600">
                            <span>Diskon Promo</span>
                            <span class="font-bold">-Rp {{ number_format($this->totalPromoDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if ($this->discount_amount > 0)
                        <div class="flex justify-between text-xs font-medium text-rose-500">
                            <span>Diskon Manual</span>
                            <span class="font-bold">- Rp
                                {{ number_format($this->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="border-t border-gray-150 pt-1.5 flex justify-between items-center">
                        <span class="font-black text-gray-900 text-base">Total Tagihan</span>
                        <span class="font-black text-[#1c69d4] text-lg">Rp
                            {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div>
                    <button wire:click="openCheckout" {{ empty($cart) || !$this->isPaymentsValid ? 'disabled' : '' }}
                        class="w-full py-3.5 rounded-xl font-black text-white text-base transition-all shadow-md active:scale-[0.98]
                    {{ empty($cart) || !$this->isPaymentsValid ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#1c69d4] hover:bg-blue-700 shadow-blue-500/20' }}">
                        <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Bayar
                    </button>
                </div>
            </div>
        </div>

        {{-- Floating Action Button (FAB) khusus Mobile untuk membuka Cart --}}
        <button @click="openCart = true"
            class="lg:hidden fixed bottom-25 right-6 bg-[#1c69d4] text-white p-4 rounded-full shadow-xl hover:bg-blue-700 active:scale-95 transition-all z-30 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
            </svg>
            @if (!empty($cart))
                <span
                    class="absolute -top-2 -right-2 bg-rose-500 text-white text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full border-2 border-white">{{ count($cart) }}</span>
            @endif
        </button>
    </div>

    @include('livewire.admin.pos.partials.modals')

    @script
        <script>
            $wire.on('print-rawbt', (event) => {
                const base64 = event.base64;
                const orderNumber = event.orderNumber;
                const isAndroid = /Android/i.test(navigator.userAgent);

                if (isAndroid) {
                    const rawbtUri = `rawbt:base64,${base64}`;
                    window.location.href = rawbtUri;
                } else {
                    const rawBytes = atob(base64);
                    const bytes = new Uint8Array(rawBytes.length);
                    for (let i = 0; i < rawBytes.length; i++) {
                        bytes[i] = rawBytes.charCodeAt(i);
                    }
                    const blob = new Blob([bytes], {
                        type: 'application/octet-stream'
                    });
                    const url = URL.createObjectURL(blob);

                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `nota-${orderNumber}.prn`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
            });
        </script>
    @endscript
</div>
