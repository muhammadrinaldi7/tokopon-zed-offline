<div class="space-y-6">
    {{-- RANGKUMAN TAGIHAN & PROMO --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Rincian Tagihan & Promo
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Diskon & Catatan --}}
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-600 uppercase">Promo Code / Voucher</label>
                        <select multiple wire:model.live="selectedPromos"
                            class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 min-h-[100px]">
                            @foreach ($this->availablePromos as $promo)
                                <option value="{{ $promo->id }}">
                                    {{ $promo->name }} (Diskon Rp {{ number_format($promo->discount_amount, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-gray-400 font-medium">Tahan tombol CTRL/CMD untuk memilih lebih dari 1 promo.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-600 uppercase">Diskon Tambahan (Rp)</label>
                            <input type="number" wire:model.lazy="discount_amount"
                                class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20"
                                placeholder="0" min="0">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-600 uppercase">Catatan</label>
                            <input type="text" wire:model.lazy="notes"
                                class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20"
                                placeholder="Cttn tambahan...">
                        </div>
                    </div>
                </div>

                {{-- Total --}}
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 flex flex-col justify-center space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium text-sm">Subtotal</span>
                        <span class="font-bold text-gray-800">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium text-sm">Total Diskon</span>
                        <span class="font-bold text-rose-500">- Rp {{ number_format($this->totalDiscount, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-gray-200/60 my-1"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 font-bold text-lg">Grand Total</span>
                        <span class="font-black text-3xl text-[#1c69d4] tracking-tight">Rp {{ number_format(max(0, $this->subtotal - $this->totalDiscount), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- METODE PEMBAYARAN --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 text-center">
            <h2 class="text-xl font-black text-gray-800">Pilih Mode Pembayaran</h2>
            <p class="text-sm text-gray-500 mt-1">Bagaimana pelanggan akan membayar tagihan ini?</p>
        </div>

        <div class="p-6">
            {{-- PILIHAN MODE --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <button wire:click="setPaymentMode('tunai')"
                    class="relative p-5 rounded-2xl border-2 transition-all flex flex-col items-center justify-center gap-3 group {{ $paymentMode === 'tunai' ? 'border-[#1c69d4] bg-blue-50/50 shadow-md' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50' }}">
                    <div class="{{ $paymentMode === 'tunai' ? 'bg-[#1c69d4] text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-500' }} w-14 h-14 rounded-full flex items-center justify-center transition-colors">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="font-bold text-lg {{ $paymentMode === 'tunai' ? 'text-[#1c69d4]' : 'text-gray-700' }}">TUNAI</span>
                    
                    @if($paymentMode === 'tunai')
                        <div class="absolute -top-3 -right-3 w-8 h-8 bg-[#1c69d4] text-white rounded-full flex items-center justify-center shadow-sm border-2 border-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    @endif
                </button>

                <button wire:click="setPaymentMode('non-tunai')"
                    class="relative p-5 rounded-2xl border-2 transition-all flex flex-col items-center justify-center gap-3 group {{ $paymentMode === 'non-tunai' ? 'border-[#1c69d4] bg-blue-50/50 shadow-md' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50' }}">
                    <div class="{{ $paymentMode === 'non-tunai' ? 'bg-[#1c69d4] text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-500' }} w-14 h-14 rounded-full flex items-center justify-center transition-colors">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <span class="font-bold text-lg {{ $paymentMode === 'non-tunai' ? 'text-[#1c69d4]' : 'text-gray-700' }}">NON-TUNAI</span>
                    
                    @if($paymentMode === 'non-tunai')
                        <div class="absolute -top-3 -right-3 w-8 h-8 bg-[#1c69d4] text-white rounded-full flex items-center justify-center shadow-sm border-2 border-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    @endif
                </button>

                <button wire:click="setPaymentMode('split')"
                    class="relative p-5 rounded-2xl border-2 transition-all flex flex-col items-center justify-center gap-3 group {{ $paymentMode === 'split' ? 'border-[#1c69d4] bg-blue-50/50 shadow-md' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50' }}">
                    <div class="{{ $paymentMode === 'split' ? 'bg-[#1c69d4] text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-500' }} w-14 h-14 rounded-full flex items-center justify-center transition-colors">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <span class="font-bold text-lg {{ $paymentMode === 'split' ? 'text-[#1c69d4]' : 'text-gray-700' }}">SPLIT PAYMENT</span>
                    
                    @if($paymentMode === 'split')
                        <div class="absolute -top-3 -right-3 w-8 h-8 bg-[#1c69d4] text-white rounded-full flex items-center justify-center shadow-sm border-2 border-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    @endif
                </button>
            </div>

            {{-- FORM PEMBAYARAN --}}
            @if($paymentMode)
                <div class="bg-gray-50/50 border border-gray-200 rounded-2xl p-4 md:p-6 shadow-inner space-y-4">
                    
                    @foreach($payments as $index => $payment)
                        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative">
                            @if($paymentMode === 'split')
                                <div class="flex justify-between items-center mb-3 border-b border-gray-100 pb-2">
                                    <h4 class="font-bold text-gray-700">Pembayaran {{ $index + 1 }}</h4>
                                    @if(count($payments) > 1)
                                        <button wire:click="removePaymentRow({{ $index }})" class="text-rose-500 hover:text-rose-700 text-sm font-bold flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                
                                {{-- Jika SPLIT, user harus pilih kategori dulu --}}
                                @if($paymentMode === 'split')
                                    <div class="md:col-span-3 space-y-1.5">
                                        <label class="text-xs font-bold text-gray-500 uppercase">Kategori</label>
                                        <select wire:model.live="payments.{{ $index }}.category"
                                            class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20">
                                            <option value="">-- Pilih --</option>
                                            <option value="TUNAI">TUNAI</option>
                                            <option value="NON-TUNAI">NON-TUNAI</option>
                                        </select>
                                    </div>
                                @endif

                                {{-- Metode Pembayaran (Akun Tunai atau EDC/QRIS) --}}
                                @if($payment['category'] === 'TUNAI')
                                    <div class="md:col-span-{{ $paymentMode === 'split' ? '5' : '6' }} space-y-1.5">
                                        <label class="text-xs font-bold text-gray-500 uppercase">Akun Kas Tunai</label>
                                        <select wire:model.live="payments.{{ $index }}.payment_method_id"
                                            class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20">
                                            <option value="">-- Pilih Akun Tunai --</option>
                                            @foreach ($this->cashPaymentMethods as $method)
                                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif($payment['category'] === 'NON-TUNAI')
                                    <div class="md:col-span-{{ $paymentMode === 'split' ? '3' : '4' }} space-y-1.5">
                                        <label class="text-xs font-bold text-gray-500 uppercase">Tipe (EDC/QRIS/Transfer)</label>
                                        <select wire:model.live="payments.{{ $index }}.payment_method_id"
                                            class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20">
                                            <option value="">-- Pilih Tipe --</option>
                                            @foreach ($this->nonCashPaymentMethods as $method)
                                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    {{-- Rate / MDR khusus Non-Tunai --}}
                                    @php
                                        $currentMethod = collect($this->nonCashPaymentMethods)->firstWhere('id', $payment['payment_method_id']);
                                    @endphp
                                    
                                    @if ($currentMethod && count($currentMethod->rates ?? []) > 0)
                                        <div class="md:col-span-{{ $paymentMode === 'split' ? '3' : '4' }} space-y-1.5">
                                            <label class="text-xs font-bold text-gray-500 uppercase">EDC / Bank (MDR)</label>
                                            <select wire:model.live="payments.{{ $index }}.payment_method_rate_id"
                                                class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20">
                                                <option value="">-- Pilih EDC --</option>
                                                @foreach ($currentMethod->rates->where('is_active', true) as $rate)
                                                    <option value="{{ $rate->id }}">
                                                        {{ $rate->name }} (MDR: {{ (float) $rate->mdr_percentage }}%)
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                @endif

                                {{-- Nominal / Amount --}}
                                <div class="md:col-span-{{ $paymentMode === 'split' ? '3' : '6' }} space-y-1.5">
                                    <label class="text-xs font-bold text-gray-500 uppercase">Nominal</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold">Rp</span>
                                        <input type="number" wire:model.lazy="payments.{{ $index }}.amount"
                                            class="w-full bg-white border border-gray-300 rounded-lg pl-10 pr-3 py-2 font-bold focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 text-gray-800"
                                            min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($paymentMode === 'split')
                        <div class="flex justify-between items-center pt-2">
                            <button wire:click="addPaymentRow"
                                class="px-4 py-2 bg-white border border-gray-300 hover:border-blue-500 hover:text-blue-600 text-gray-600 font-bold rounded-lg shadow-sm transition-all flex items-center gap-1.5 text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Tambah Pembayaran
                            </button>
                            
                            @php
                                $totalPaid = $this->paymentsTotalBase;
                                $target = max(0, $this->subtotal - $this->totalDiscount);
                                $kurang = $target - $totalPaid;
                            @endphp
                            <div class="text-right">
                                <p class="text-xs font-bold text-gray-500 uppercase">Sisa Tagihan</p>
                                <p class="text-lg font-black {{ $kurang > 0 ? 'text-rose-500' : ($kurang < 0 ? 'text-amber-500' : 'text-emerald-500') }}">
                                    Rp {{ number_format(abs($kurang), 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-4">
        <button wire:click="prevStep"
            class="px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </button>
        <button wire:click="processCheckout"
            @if(!$this->isPaymentsValid) disabled @endif
            class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
            Proses Transaksi
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </button>
    </div>
</div>
