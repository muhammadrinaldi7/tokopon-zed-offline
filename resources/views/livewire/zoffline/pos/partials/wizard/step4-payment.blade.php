<div class="space-y-6">
    {{-- METODE PEMBAYARAN WIZARD (Dipindah ke atas) --}}
    <div class="flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800">
                    @if ($paymentWizardStep === 1)
                        Pilih Mode Pembayaran
                    @elseif($paymentWizardStep === 2)
                        Pilih Metode Pembayaran
                    @elseif($paymentWizardStep === 3)
                        Detail Pembayaran
                    @elseif($paymentWizardStep === 'split_dashboard')
                        Split Payment Dashboard
                    @endif
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Lengkapi proses pembayaran</p>
            </div>

            @if ($paymentWizardStep !== 1)
                <button wire:click="prevPaymentWizardStep"
                    class="px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </button>
            @endif
        </div>

        <div class="flex-1 min-h-70">
            @if ($paymentWizardStep === 1)
                {{-- STEP 1: PILIHAN MODE --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button wire:click="setPaymentMode('tunai')"
                        class="w-full min-h-62.5 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out border-2 {{ $paymentMode === 'tunai' ? 'border-[#1c69d4] shadow-md ring-4 ring-[#1c69d4]/10' : 'border-transparent' }}">

                        <div
                            class="bg-[#FFC4C4] text-neutral-800 rounded-full w-20 h-20 flex items-center justify-center transition-all duration-300">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>

                        <div class="text-left mt-6">
                            <h1
                                class="text-2xl font-black {{ $paymentMode === 'tunai' ? 'text-[#1c69d4]' : 'text-neutral-800' }}">
                                TUNAI</h1>
                            <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Pembayaran langsung di tempat
                            </p>
                        </div>
                    </button>

                    <button wire:click="setPaymentMode('non-tunai')"
                        class="w-full min-h-62.5 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out border-2 {{ $paymentMode === 'non-tunai' ? 'border-[#1c69d4] shadow-md ring-4 ring-[#1c69d4]/10' : 'border-transparent' }}">

                        <div
                            class="bg-[#DFE7FF] text-neutral-800 rounded-full w-20 h-20 flex items-center justify-center transition-all duration-300">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>

                        <div class="text-left mt-6">
                            <h1
                                class="text-2xl font-black {{ $paymentMode === 'non-tunai' ? 'text-[#1c69d4]' : 'text-neutral-800' }}">
                                NON-TUNAI</h1>
                            <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Transfer bank atau pembayaran digital.
                            </p>
                        </div>
                    </button>

                    <button wire:click="setPaymentMode('split')"
                        class="w-full min-h-62.5 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out border-2 {{ $paymentMode === 'split' ? 'border-[#1c69d4] shadow-md ring-4 ring-[#1c69d4]/10' : 'border-transparent' }}">

                        <div
                            class="bg-slate-200 text-neutral-800 rounded-full w-20 h-20 flex items-center justify-center transition-all duration-300">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>

                        <div class="text-left mt-6">
                            <h1
                                class="text-2xl font-black {{ $paymentMode === 'split' ? 'text-[#1c69d4]' : 'text-neutral-800' }}">
                                SPLIT</h1>
                            <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Bayar dengan lebih dari satu metode
                            </p>
                        </div>
                    </button>
                </div>
            @elseif($paymentWizardStep === 2)
                {{-- STEP 2: PILIHAN METODE (KAS/BANK) --}}
                @php
                    $cat = $payments[$activePaymentIndex]['category'] ?? '';
                    $methods = $cat === 'TUNAI' ? $this->cashPaymentMethods : $this->nonCashPaymentMethods;
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach ($methods as $method)
                        @php
                            $imageName = strtolower(str_replace(' ', '', $method->name)) . '.png';
                            $imagePath = public_path('assets/png/paymentmethod/' . $imageName);
                            $hasImage = file_exists($imagePath);
                        @endphp
                        <button wire:click="selectPaymentMethod({{ $method->id }})"
                            class="p-6 bg-white shadow-sm border-2 border-gray-100 rounded-2xl hover:border-[#1c69d4] hover:shadow-md transition-all flex flex-col items-center justify-center gap-4 group">

                            @if ($hasImage)
                                <div class="h-16 w-full flex items-center justify-center">
                                    <img src="{{ asset('assets/png/paymentmethod/' . $imageName) }}"
                                        alt="{{ $method->name }}"
                                        class="max-h-full max-w-full object-contain filter group-hover:brightness-110 transition-all">
                                </div>
                            @else
                                <div
                                    class="w-16 h-16 bg-gray-50 border border-gray-100 rounded-full flex items-center justify-center text-gray-400 group-hover:text-[#1c69d4] group-hover:bg-blue-50 group-hover:border-blue-200 transition-all">
                                    @if ($cat === 'TUNAI')
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    @else
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="text-center">
                                    <span
                                        class="font-bold text-sm text-gray-700 group-hover:text-[#1c69d4] transition-colors">{{ $method->name }}</span>
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            @elseif($paymentWizardStep === 3)
                {{-- STEP 3: MDR & NOMINAL --}}
                <div class="max-w-7xl mx-auto space-y-6">
                    @php
                        $payment = $payments[$activePaymentIndex] ?? [];
                        $cat = $payment['category'] ?? '';
                        $pmId = $payment['payment_method_id'] ?? '';
                        $methodObj =
                            $cat === 'TUNAI'
                                ? collect($this->cashPaymentMethods)->firstWhere('id', $pmId)
                                : collect($this->nonCashPaymentMethods)->firstWhere('id', $pmId);

                        $hasRate = $cat === 'NON-TUNAI' && $methodObj && count($methodObj->rates ?? []) > 0;

                        $imageName = $methodObj ? strtolower(str_replace(' ', '', $methodObj->name)) . '.png' : '';
                        $imagePath = $imageName ? public_path('assets/png/paymentmethod/' . $imageName) : '';
                        $hasImage = $imagePath ? file_exists($imagePath) : false;
                    @endphp

                    <div class="bg-blue-50 border border-blue-100 p-5 rounded-2xl flex items-center gap-4">
                        @if ($hasImage)
                            <div
                                class="w-16 h-16 bg-white rounded-xl flex items-center justify-center shadow-sm p-2 border border-blue-200">
                                <img src="{{ asset('assets/png/paymentmethod/' . $imageName) }}"
                                    alt="{{ $methodObj->name }}" class="max-h-full max-w-full object-contain">
                            </div>
                        @else
                            <div
                                class="w-14 h-14 bg-[#1c69d4] text-white rounded-xl flex items-center justify-center shadow-md">
                                @if ($cat === 'TUNAI')
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                @else
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                @endif
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-bold text-blue-500/70">{{ $cat }}</p>
                            <h3 class="text-2xl font-black text-gray-800">{{ $methodObj->name ?? 'Unknown Method' }}
                            </h3>
                        </div>
                    </div>

                    @if ($hasRate)
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-gray-700 uppercase tracking-wide">Pilih Tipe EDC / MDR
                                Rate</label>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($methodObj->rates->where('is_active', true) as $rate)
                                    <label
                                        class="relative flex cursor-pointer rounded-2xl border-2 {{ ($payment['payment_method_rate_id'] ?? '') == $rate->id ? 'border-[#1c69d4] bg-blue-50/50 shadow-sm' : 'border-gray-200 hover:border-blue-300' }} p-4 transition-all group">
                                        <input type="radio"
                                            wire:model.live="payments.{{ $activePaymentIndex }}.payment_method_rate_id"
                                            value="{{ $rate->id }}" class="sr-only">
                                        <div class="flex-1">
                                            <span
                                                class="block text-lg font-black {{ ($payment['payment_method_rate_id'] ?? '') == $rate->id ? 'text-[#1c69d4]' : 'text-gray-700' }}">{{ $rate->name }}</span>
                                            <span class="block text-sm font-bold text-emerald-500 mt-1">MDR:
                                                {{ (float) $rate->mdr_percentage }}%</span>
                                        </div>
                                        @if (($payment['payment_method_rate_id'] ?? '') == $rate->id)
                                            <div class="text-[#1c69d4] flex items-center justify-center">
                                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <div class="flex justify-between items-end">
                            <label class="text-sm font-bold text-gray-700 uppercase tracking-wide">Nominal
                                Pembayaran</label>
                            @if ($paymentMode === 'split')
                                <button wire:click="autofillRemaining({{ $activePaymentIndex }})"
                                    class="text-xs font-bold text-blue-600 hover:text-blue-700 hover:underline">Bayar
                                    Lunas (Sisa)</button>
                            @endif
                        </div>
                        <div class="relative">
                            <span
                                class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 font-black text-2xl">Rp</span>
                            <input type="number" wire:model.lazy="payments.{{ $activePaymentIndex }}.amount"
                                class="w-full bg-white border-2 border-gray-300 rounded-2xl pl-16 pr-5 py-5 text-4xl font-black text-gray-800 focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/20 transition-all text-right"
                                min="0">
                        </div>
                    </div>

                    @if ($paymentMode === 'split')
                        <button wire:click="savePaymentLine"
                            class="w-full py-4 mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-2xl shadow-lg shadow-emerald-600/30 transition-all text-xl flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambahkan ke Split Payment
                        </button>
                    @endif
                </div>
            @elseif($paymentWizardStep === 'split_dashboard')
                {{-- SPLIT PAYMENT DASHBOARD --}}
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-4">
                        @forelse($payments as $index => $payment)
                            @php
                                $pmObj = \App\Models\PaymentMethod::find($payment['payment_method_id']);
                                $rateObj = \App\Models\PaymentMethodRate::find(
                                    $payment['payment_method_rate_id'] ?? null,
                                );
                                $imageName = $pmObj ? strtolower(str_replace(' ', '', $pmObj->name)) . '.png' : '';
                                $imagePath = $imageName ? public_path('assets/png/paymentmethod/' . $imageName) : '';
                                $hasImage = $imagePath ? file_exists($imagePath) : false;
                            @endphp
                            <div
                                class="bg-white border-2 border-gray-100 rounded-2xl p-5 shadow-sm flex items-center justify-between group hover:border-blue-200 transition-colors">
                                <div class="flex items-center gap-5">
                                    @if ($hasImage)
                                        <div
                                            class="w-16 h-16 bg-white rounded-xl flex items-center justify-center shadow-sm p-2 border border-gray-100">
                                            <img src="{{ asset('assets/png/paymentmethod/' . $imageName) }}"
                                                alt="{{ $pmObj->name ?? 'Logo' }}"
                                                class="max-h-full max-w-full object-contain">
                                        </div>
                                    @else
                                        <div
                                            class="w-14 h-14 rounded-2xl flex items-center justify-center {{ $payment['category'] === 'TUNAI' ? 'bg-emerald-100 text-emerald-600' : 'bg-[#1c69d4]/10 text-[#1c69d4]' }}">
                                            @if ($payment['category'] === 'TUNAI')
                                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            @else
                                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                </svg>
                                            @endif
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                            {{ $payment['category'] }}</p>
                                        <h4 class="text-xl font-black text-gray-800">
                                            {{ $pmObj->name ?? 'Unknown Method' }}</h4>
                                        @if ($rateObj)
                                            <p class="text-sm font-bold text-blue-500 mt-0.5">MDR:
                                                {{ $rateObj->name }} ({{ (float) $rateObj->mdr_percentage }}%)</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="text-right">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Nominal
                                        </p>
                                        <p class="text-2xl font-black text-gray-800">Rp
                                            {{ number_format($payment['amount'], 0, ',', '.') }}</p>
                                    </div>
                                    <button wire:click="removePaymentRow({{ $index }})"
                                        class="p-3 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div
                                class="text-center py-12 bg-gray-50/50 rounded-2xl border border-dashed border-gray-300">
                                <div
                                    class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 font-bold text-lg">Belum ada pembayaran</p>
                                <p class="text-gray-400 text-sm mt-1">Silakan tambah metode pembayaran di bawah</p>
                            </div>
                        @endforelse
                    </div>

                    @php
                        $totalPaid = collect($payments)->sum('amount');
                        $target = max(0, $this->subtotal() - (int) $this->totalDiscount());
                        $kurang = $target - $totalPaid;
                    @endphp

                    <div
                        class="bg-gray-50 border border-gray-200 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Total Sisa Tagihan</p>
                            <p
                                class="text-4xl font-black {{ $kurang > 0 ? 'text-rose-500' : ($kurang < 0 ? 'text-amber-500' : 'text-emerald-500') }} mt-1">
                                Rp {{ number_format(abs($kurang), 0, ',', '.') }}
                            </p>
                        </div>

                        @if ($kurang > 0)
                            <div class="flex gap-3 w-full md:w-auto">
                                <button wire:click="addSplitPayment('TUNAI')"
                                    class="flex-1 md:flex-none px-6 py-4 bg-white border-2 border-emerald-500 text-emerald-600 hover:bg-emerald-50 hover:shadow-md font-black rounded-xl transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Tunai
                                </button>
                                <button wire:click="addSplitPayment('NON-TUNAI')"
                                    class="flex-1 md:flex-none px-6 py-4 bg-white border-2 border-[#1c69d4] text-[#1c69d4] hover:bg-blue-50 hover:shadow-md font-black rounded-xl transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Non-Tunai
                                </button>
                            </div>
                        @else
                            <div
                                class="px-6 py-3 bg-emerald-100 text-emerald-700 font-bold rounded-xl flex items-center gap-2">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Tagihan Terpenuhi
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- RANGKUMAN TAGIHAN & PROMO (Dipindah ke bawah) --}}
    <div class="flex flex-col gap-6">
        {{-- Diskon & Catatan --}}
        <div class="space-y-1.5">
            <label class="text-xs font-bold text-gray-600 uppercase">Catatan</label>
            <input type="text" wire:model.lazy="notes"
                class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20"
                placeholder="Catatan tambahan...">
        </div>

        {{-- Total --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 flex flex-col justify-center space-y-3 shadow-sm">
            <div class="flex justify-between items-center">
                <span class="text-gray-500 font-medium text-sm">Subtotal</span>
                <span class="font-bold text-gray-800">Rp
                    {{ number_format($this->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-500 font-medium text-sm">Total Diskon</span>
                <span class="font-bold text-rose-500">- Rp
                    {{ number_format($this->totalDiscount, 0, ',', '.') }}</span>
            </div>
            <div class="border-t border-gray-200/60 my-2"></div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600 font-bold text-lg">Grand Total</span>
                <span class="font-black text-3xl md:text-4xl text-[#1c69d4] tracking-tight">Rp
                    {{ number_format(max(0, $this->subtotal - $this->totalDiscount), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-6 border-t border-gray-200 mt-2">
        <button wire:click="prevStep"
            class="px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </button>
        <button wire:click="processPayment" @if (!$this->isPaymentsValid) disabled @endif
            class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
            Proses Transaksi
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </button>
    </div>
