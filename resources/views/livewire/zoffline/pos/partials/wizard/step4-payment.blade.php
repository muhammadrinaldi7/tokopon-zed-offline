<div class="space-y-6">
    {{-- METODE PEMBAYARAN WIZARD (Dipindah ke atas) --}}
    <div class="flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800">
                    @if ($paymentWizardStep === 1)
                        Pilih Mode Pembayaran
                    @elseif($paymentWizardStep == 1.5)
                        Pilih Grup Bank
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
                    Reset
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
            @elseif($paymentWizardStep == 1.5)
                {{-- STEP 1.5: PILIH GRUP BANK (HANYA NON-TUNAI) --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @forelse ($this->nonCashBankGroups as $group)
                        @php
                            $imageName = strtolower(str_replace(' ', '', $group)) . '.png';
                            $imagePath = public_path('assets/png/paymentmethod/' . $imageName);
                            $hasImage = file_exists($imagePath);
                        @endphp
                        <button wire:click="selectBankGroup('{{ addslashes($group) }}')"
                            class="relative p-5 bg-white shadow-sm border-2 border-gray-100 rounded-2xl hover:border-[#1c69d4] hover:shadow-md transition-all text-left flex flex-col justify-between gap-6 group min-h-[140px]">

                            {{-- Kanan Atas: Logo --}}
                            <div class="flex justify-end w-full">
                                @if ($hasImage)
                                    <img src="{{ asset('assets/png/paymentmethod/' . $imageName) }}"
                                        alt="{{ $group }}"
                                        class="h-8 md:h-10 object-contain filter group-hover:brightness-110 transition-all">
                                @else
                                    <div
                                        class="w-10 h-10 bg-gray-50 border border-gray-100 rounded-full flex items-center justify-center text-gray-400 group-hover:text-[#1c69d4] group-hover:bg-blue-50 group-hover:border-blue-200 transition-all font-black text-sm">
                                        {{ strtoupper(substr($group, 0, 2)) }}
                                    </div>
                                @endif
                            </div>

                            {{-- Kiri Bawah: Teks --}}
                            <div>
                                {{-- Ubah w-10 h-auto p-2 menjadi w-10 h-10 flex items-center justify-center --}}
                                <div
                                    class="bg-neutral-200 text-neutral-800 rounded-full w-10 h-10 flex items-center justify-center shrink-0">

                                    {{-- Ubah w-8 h-auto menjadi w-5 h-5 atau w-6 h-6 agar pas di dalam lingkaran --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24">
                                        <path d="M0 0h24v24H0z" fill="none" />
                                        <path fill="currentColor" fill-rule="evenodd"
                                            d="M9.944 3.25h4.112c1.838 0 3.294 0 4.433.153c1.172.158 2.121.49 2.87 1.238c.748.749 1.08 1.698 1.238 2.87c.09.673.127 1.456.142 2.363a.8.8 0 0 1 .004.23q.009.848.007 1.84v.112c0 1.838 0 3.294-.153 4.433c-.158 1.172-.49 2.121-1.238 2.87c-.749.748-1.698 1.08-2.87 1.238c-1.14.153-2.595.153-4.433.153H9.944c-1.838 0-3.294 0-4.433-.153c-1.172-.158-2.121-.49-2.87-1.238c-.748-.749-1.08-1.698-1.238-2.87c-.153-1.14-.153-2.595-.153-4.433v-.112q-.002-.992.007-1.84a.8.8 0 0 1 .003-.23c.016-.907.053-1.69.143-2.363c.158-1.172.49-2.121 1.238-2.87c.749-.748 1.698-1.08 2.87-1.238c1.14-.153 2.595-.153 4.433-.153m-7.192 7.5q-.002.582-.002 1.25c0 1.907.002 3.262.14 4.29c.135 1.005.389 1.585.812 2.008s1.003.677 2.009.812c1.028.138 2.382.14 4.289.14h4c1.907 0 3.262-.002 4.29-.14c1.005-.135 1.585-.389 2.008-.812s.677-1.003.812-2.009c.138-1.028.14-2.382.14-4.289q0-.668-.002-1.25zm18.472-1.5H2.776c.02-.587.054-1.094.114-1.54c.135-1.005.389-1.585.812-2.008s1.003-.677 2.009-.812c1.028-.138 2.382-.14 4.289-.14h4c1.907 0 3.262.002 4.29.14c1.005.135 1.585.389 2.008.812s.677 1.003.812 2.009c.06.445.094.952.114 1.539M5.25 16a.75.75 0 0 1 .75-.75h4a.75.75 0 0 1 0 1.5H6a.75.75 0 0 1-.75-.75m6.5 0a.75.75 0 0 1 .75-.75H14a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <p
                                    class="text-[10px] text-neutral-800 font-medium mt-1.5 uppercase tracking-wide leading-tight">
                                    Transfer bank atau pembayaran digital
                                </p>
                            </div>
                        </button>
                    @empty
                        <div
                            class="col-span-full p-8 text-center bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                            <p class="text-gray-500 font-bold">Belum ada grup bank metode Non-Tunai yang aktif.</p>
                        </div>
                    @endforelse
                </div>
            @elseif($paymentWizardStep === 2)
                {{-- STEP 2: PILIHAN METODE (KAS/BANK) --}}
                @php
                    $paymentInfo = $payments[$activePaymentIndex] ?? [];
                    $cat = $paymentInfo['category'] ?? '';
                    $selectedBank = $paymentInfo['bank_name'] ?? '';

                    if ($cat === 'TUNAI') {
                        $methods = $this->cashPaymentMethods;
                    } else {
                        // Filter Non-Tunai by selected bank group
                        $methods = collect($this->nonCashPaymentMethods)->filter(function ($m) use ($selectedBank) {
                            return $m->bank_name === $selectedBank;
                        });
                    }
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
                <div class="max-w-7xl mx-auto space-y-6" wire:key="payment-step3-{{ $activePaymentIndex }}"
                    x-data="{
                        rawAmount: @entangle('payments.' . $activePaymentIndex . '.amount'),
                        rateId: @entangle('payments.' . $activePaymentIndex . '.payment_method_rate_id'),
                        hasRate: {{ $hasRate ? 'true' : 'false' }},
                        isSplit: {{ $paymentMode === 'split' ? 'true' : 'false' }},
                        formattedAmount: '',
                    
                        init() {
                            this.formattedAmount = this.formatNumber(this.rawAmount);
                    
                            $watch('rawAmount', value => {
                                if (document.activeElement !== this.$refs.amountInput) {
                                    this.formattedAmount = this.formatNumber(value);
                                }
                            });
                        },
                    
                        formatNumber(val) {
                            if (!val) return '';
                            let num = parseInt(String(val).replace(/\D/g, ''), 10);
                            return isNaN(num) ? '' : num.toLocaleString('id-ID');
                        },
                    
                        updateAmount(e) {
                            if (!this.isSplit) return;
                    
                            let val = e.target.value;
                            let num = parseInt(val.replace(/\D/g, ''), 10);
                            if (isNaN(num)) num = 0;
                    
                            this.formattedAmount = this.formatNumber(num);
                            this.rawAmount = num;
                        },
                    
                        saveLine() {
                            if (!this.canSave) return;
                            $wire.set('payments.' + {{ $activePaymentIndex }} + '.amount', this.rawAmount).then(() => {
                                $wire.savePaymentLine();
                            });
                        },
                    
                        get canSave() {
                            let amountValid = this.rawAmount > 0;
                            let rateValid = !this.hasRate || (this.rateId !== '' && this.rateId !== null);
                            return amountValid && rateValid;
                        }
                    }">

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
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <p class="text-sm font-bold text-blue-500/70 uppercase">{{ $cat }}</p>
                                @if (!empty($payment['bank_name']))
                                    <span
                                        class="px-2 py-0.5 bg-[#1c69d4]/10 text-[#1c69d4] rounded-md text-[10px] font-black uppercase tracking-widest">{{ $payment['bank_name'] }}</span>
                                @endif
                                @if ($hasRate)
                                    <template x-for="rate in @js($methodObj->rates->where('is_active', true)->values())" :key="rate.id">
                                        <span x-show="rateId == rate.id" style="display: none;"
                                            class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-[10px] font-black uppercase tracking-widest">
                                            Tarif: <span x-text="rate.name"></span> (<span
                                                x-text="parseFloat(rate.mdr_percentage)"></span>%)
                                        </span>
                                    </template>
                                @endif
                            </div>
                            <h3 class="text-2xl font-black text-gray-800">{{ $methodObj->name ?? 'Unknown Method' }}
                            </h3>
                        </div>
                    </div>

                    @if ($hasRate)
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-gray-700 uppercase tracking-wide">Pilih Tarif</label>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($methodObj->rates->where('is_active', true) as $rate)
                                    <label
                                        class="relative flex cursor-pointer rounded-2xl border-2 {{ ($payment['payment_method_rate_id'] ?? '') == $rate->id ? 'border-[#1c69d4] bg-white shadow-md ring-1 ring-[#1c69d4]/50' : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-gray-50/50' }} p-4 transition-all group">
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

                    @if (strtolower($methodObj->bank_name ?? '') === 'finance')
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-gray-700 uppercase tracking-wide">Nomor Kontrak (Opsional)</label>
                            <input type="text" wire:model.live.debounce.500ms="payments.{{ $activePaymentIndex }}.no_kontrak"
                                class="w-full bg-white border-2 border-gray-300 rounded-2xl px-5 py-4 text-xl font-bold text-gray-800 focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/20 transition-all"
                                placeholder="Masukkan Nomor Kontrak">
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
                            <input type="text" x-ref="amountInput" x-model="formattedAmount"
                                @input="updateAmount($event)" x-bind:readonly="!isSplit"
                                :class="!isSplit ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'text-gray-800'"
                                class="w-full bg-white border-2 border-gray-300 rounded-2xl pl-16 pr-5 py-5 text-4xl font-black focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/20 transition-all text-right"
                                placeholder="0">
                        </div>
                    </div>

                    @if ($paymentMode === 'split')
                        <button @click="saveLine" x-bind:disabled="!canSave"
                            class="w-full py-4 mt-4 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-400 disabled:hover:bg-gray-400 disabled:cursor-not-allowed text-white font-black rounded-2xl shadow-lg shadow-emerald-600/30 transition-all text-xl flex items-center justify-center gap-2">
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
                                            class="w-14 h-14 rounded-2xl flex items-center justify-center {{ ($payment['category'] ?? '') === 'TUNAI' ? 'bg-emerald-100 text-emerald-600' : 'bg-[#1c69d4]/10 text-[#1c69d4]' }}">
                                            @if (($payment['category'] ?? '') === 'TUNAI')
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
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                                {{ $payment['category'] ?? '' }}</p>
                                            @if (!empty($payment['bank_name']))
                                                <span
                                                    class="px-2 py-0.5 bg-[#1c69d4]/10 text-[#1c69d4] rounded-md text-[10px] font-black uppercase tracking-widest">{{ $payment['bank_name'] }}</span>
                                            @endif
                                            @if ($rateObj)
                                                <span
                                                    class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-[10px] font-black uppercase tracking-widest">
                                                    Tarif: {{ $rateObj->name }}
                                                    ({{ (float) $rateObj->mdr_percentage }}%)
                                                </span>
                                            @endif
                                        </div>
                                        <h4 class="text-xl font-black text-gray-800">
                                            {{ $pmObj->name ?? 'Unknown Method' }}</h4>
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


    </div>

    {{-- Footer Actions --}}
    <div class="flex flex-col sm:flex-row justify-between gap-3 pt-4 sm:pt-6 border-t border-gray-200 mt-2">
        <button wire:click="prevStep"
            class="order-last sm:order-first w-full sm:w-auto px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </button>
        <div x-data="{ showConfirmModal: false, showPiutangModal: false }" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft"
                class="w-full sm:w-auto px-6 py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-amber-300 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 min-w-[170px]">
                <span wire:loading.remove wire:target="saveDraft" class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    Simpan Draft
                </span>
                <span wire:loading.inline-flex wire:target="saveDraft" class="items-center gap-2 hidden">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Menyimpan...
                </span>
            </button>
            <button type="button" @click="showPiutangModal = true"
                class="w-full sm:w-auto px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 min-w-[170px]">
                Piutang
            </button>
            <button type="button" @click="showConfirmModal = true" @if (!$this->isPaymentsValid) disabled @endif
                class="w-full sm:w-auto px-8 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                Proses Transaksi
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>

            {{-- MODAL KONFIRMASI PIUTANG --}}
            <template x-teleport="body">
                <div x-show="showConfirmModal" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;"
                    aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div
                        class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 transition-opacity bg-gray-900/50 backdrop-blur-sm"
                            aria-hidden="true" @click="showConfirmModal = false"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                            aria-hidden="true">&#8203;</span>

                        <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="inline-block w-full max-w-2xl overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle border border-gray-100 relative">

                            {{-- Header --}}
                            <div
                                class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-white flex justify-between items-start sm:items-center sticky top-0 z-10">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg sm:text-xl font-black text-gray-800" id="modal-title">Konfirmasi
                                            Pesanan</h3>
                                        <span
                                            class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] sm:text-xs font-bold border border-blue-100">{{ $this->displayCustomerName }}</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Pastikan pesanan dan pembayaran sudah sesuai
                                    </p>
                                </div>
                                <button type="button" @click="showConfirmModal = false"
                                    class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 focus:outline-none p-2 rounded-xl transition-all shrink-0">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Body --}}
                            <div
                                class="px-4 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6 max-h-[65vh] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-200">

                                {{-- Daftar Item --}}
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Daftar
                                        Item</h4>
                                    <div class="space-y-3 bg-gray-50/50 p-3 sm:p-4 rounded-xl border border-gray-100">
                                        @forelse($this->cart as $item)
                                            <div
                                                class="flex flex-col sm:flex-row justify-between items-start gap-2 sm:gap-4 pb-3 border-b border-gray-200/60 last:border-0 last:pb-0">
                                                <div class="flex-1 w-full">
                                                    @php
                                                        $nameParts = explode(' - ', $item['name']);

                                                        // Hapus prefix 'DS' jika ada di awal nama
                                                        if (
                                                            isset($nameParts[0]) &&
                                                            trim(strtoupper($nameParts[0])) === 'DS'
                                                        ) {
                                                            array_shift($nameParts);
                                                        }

                                                        // Hapus prefix 'HP' di awal (bisa 'HP ' atau 'HP' saja)
                                                        if (isset($nameParts[0])) {
                                                            if (trim(strtoupper($nameParts[0])) === 'HP') {
                                                                array_shift($nameParts);
                                                            } else {
                                                                $nameParts[0] = preg_replace(
                                                                    '/^HP\s+/i',
                                                                    '',
                                                                    trim($nameParts[0]),
                                                                );
                                                            }
                                                        }

                                                        $parsedStorage = null;
                                                        $parsedColor = null;

                                                        if (count($nameParts) >= 3) {
                                                            // Ambil 2 elemen paling belakang sebagai Color dan Storage
                                                            $parsedColor = trim(array_pop($nameParts));
                                                            $parsedStorage = trim(array_pop($nameParts));
                                                            // Sisanya digabung kembali sebagai Base Name
                                                            $baseName = trim(implode(' - ', $nameParts));
                                                        } elseif (count($nameParts) == 2) {
                                                            // Jika cuma 2 elemen, cek apakah elemen terakhir berupa angka/kapasitas
                                                            $lastPart = trim($nameParts[1]);
                                                            if (
                                                                preg_match(
                                                                    '/^(\d+(GB|TB)?)$/i',
                                                                    str_replace(' ', '', $lastPart),
                                                                )
                                                            ) {
                                                                $parsedStorage = trim(array_pop($nameParts));
                                                                $baseName = trim(implode(' - ', $nameParts));
                                                            } else {
                                                                $baseName = trim($item['name']);
                                                            }
                                                        } else {
                                                            $baseName = trim($item['name']);
                                                        }

                                                        $displayRam = $item['ram'] !== '-' ? $item['ram'] : null;
                                                        $displayStorage =
                                                            $item['storage'] !== '-'
                                                                ? $item['storage']
                                                                : $parsedStorage;
                                                        $displayColor =
                                                            $item['color'] !== '-' ? $item['color'] : $parsedColor;
                                                    @endphp
                                                    <h4 class="font-bold text-gray-800 text-sm leading-tight">
                                                        {{ $baseName }}</h4>
                                                    <div class="text-xs text-gray-500 mt-1.5 flex flex-wrap gap-1">
                                                        @if ($displayRam || $displayStorage)
                                                            <span
                                                                class="bg-white border border-gray-200 px-2 py-0.5 rounded-md shadow-sm">{{ $displayRam ? $displayRam . ' / ' : '' }}{{ $displayStorage ?? '' }}</span>
                                                        @endif
                                                        @if ($displayColor)
                                                            <span
                                                                class="bg-white border border-gray-200 px-2 py-0.5 rounded-md shadow-sm">{{ $displayColor }}</span>
                                                        @endif
                                                        {{-- @if (!empty($item['condition']))
                                                            <span
                                                                class="bg-emerald-50 text-emerald-600 border border-emerald-100 px-2 py-0.5 rounded-md font-bold shadow-sm">{{ $item['condition'] }}</span>
                                                        @endif --}}
                                                    </div>
                                                    @if (count($item['serial_numbers']) > 0)
                                                        <div class="mt-2 flex flex-wrap gap-1">
                                                            @foreach ($item['serial_numbers'] as $sn)
                                                                <span
                                                                    class="text-[10px] font-mono bg-blue-50/50 border border-blue-100 text-blue-600 px-1.5 py-0.5 rounded">{{ $sn }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="w-full sm:w-auto mt-2 sm:mt-0 flex sm:block justify-between items-center sm:text-right">
                                                    <div class="text-left sm:text-right">
                                                        <div class="text-xs font-bold text-gray-400 sm:mt-0.5">
                                                            {{ $item['qty'] }} x</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="font-black text-gray-800 text-sm">Rp
                                                            {{ number_format($item['price'], 0, ',', '.') }}</div>
                                                        @if (isset($item['discount_amount']) && $item['discount_amount'] > 0)
                                                            <div class="text-xs text-rose-500 font-bold mt-0.5 sm:mt-1">- Rp
                                                                {{ number_format($item['discount_amount'], 0, ',', '.') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-2 text-gray-400 text-sm font-medium">Keranjang
                                                kosong</div>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Informasi Pembayaran --}}
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Metode
                                        Pembayaran</h4>
                                    <div class="bg-white border border-gray-100 rounded-xl p-3 sm:p-4 shadow-sm space-y-4">
                                        @if ($paymentMode === 'split')
                                            @foreach ($payments as $payment)
                                                @php
                                                    $pmObj = \App\Models\PaymentMethod::find(
                                                        $payment['payment_method_id'] ?? null,
                                                    );
                                                    $rateObj = \App\Models\PaymentMethodRate::find(
                                                        $payment['payment_method_rate_id'] ?? null,
                                                    );
                                                @endphp
                                                <div
                                                    class="flex flex-col sm:flex-row sm:justify-between sm:items-start text-sm border-b border-gray-50 pb-2 last:border-0 last:pb-0 gap-1.5 sm:gap-2">
                                                    <div class="flex flex-col gap-1.5">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="w-2 h-2 rounded-full shrink-0 {{ $payment['category'] === 'TUNAI' ? 'bg-emerald-500' : 'bg-[#1c69d4]' }}"></span>
                                                            <span
                                                                class="text-gray-700 font-bold">{{ $pmObj->name ?? $payment['category'] }}</span>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-1.5 pl-4">
                                                            @if (!empty($payment['bank_name']))
                                                                <span
                                                                    class="px-1.5 py-0.5 bg-[#1c69d4]/10 text-[#1c69d4] rounded text-[9px] font-black uppercase tracking-widest">{{ $payment['bank_name'] }}</span>
                                                            @endif
                                                            @if ($rateObj)
                                                                <span
                                                                    class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[9px] font-black uppercase tracking-widest">Tarif:
                                                                    {{ $rateObj->name }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <span class="font-black text-gray-800 pt-0.5 pl-4 sm:pl-0">Rp
                                                        {{ number_format($payment['amount'], 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            @php
                                                $payment = $payments[0] ?? [];
                                                $pmObj = \App\Models\PaymentMethod::find(
                                                    $payment['payment_method_id'] ?? null,
                                                );
                                                $rateObj = \App\Models\PaymentMethodRate::find(
                                                    $payment['payment_method_rate_id'] ?? null,
                                                );
                                                $methodName = $pmObj
                                                    ? $pmObj->name
                                                    : $payment['category'] ?? 'Belum dipilih';
                                            @endphp
                                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start text-sm gap-1.5 sm:gap-2">
                                                <div class="flex flex-col gap-1.5">
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="w-2 h-2 rounded-full shrink-0 {{ ($payment['category'] ?? '') === 'TUNAI' ? 'bg-emerald-500' : 'bg-[#1c69d4]' }}"></span>
                                                        <span
                                                            class="text-gray-700 font-bold">{{ $methodName }}</span>
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-1.5 pl-4">
                                                        @if (!empty($payment['bank_name']))
                                                            <span
                                                                class="px-1.5 py-0.5 bg-[#1c69d4]/10 text-[#1c69d4] rounded text-[9px] font-black uppercase tracking-widest">{{ $payment['bank_name'] }}</span>
                                                        @endif
                                                        @if ($rateObj)
                                                            <span
                                                                class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[9px] font-black uppercase tracking-widest">Tarif:
                                                                {{ $rateObj->name }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <span class="font-black text-gray-800 pt-0.5 pl-4 sm:pl-0">Rp
                                                    {{ number_format($payment['amount'] ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Rincian Tagihan --}}
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Rincian
                                        Tagihan</h4>
                                    <div class="bg-[#1c69d4]/5 rounded-xl p-3 sm:p-4 border border-[#1c69d4]/10 space-y-2">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 font-medium">Subtotal</span>
                                            <span class="font-bold text-gray-800">Rp
                                                {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                        @if ($this->itemDiscountTotal > 0)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Diskon Item</span>
                                                <span class="font-medium text-rose-400">- Rp
                                                    {{ number_format($this->itemDiscountTotal, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if ($this->promoDiscountTotal > 0)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Promo</span>
                                                <span class="font-medium text-rose-400">- Rp
                                                    {{ number_format($this->promoDiscountTotal, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 font-medium">Total Diskon</span>
                                            <span class="font-bold text-rose-500">- Rp
                                                {{ number_format($this->totalDiscount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="border-t border-[#1c69d4]/20 my-2 pt-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-800 font-black">Grand Total</span>
                                                <span class="font-black text-xl sm:text-2xl text-[#1c69d4]">Rp
                                                    {{ number_format(max(0, $this->subtotal - $this->totalDiscount), 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div
                                class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-white flex flex-col-reverse sm:flex-row justify-end gap-3 rounded-b-2xl sticky bottom-0 z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                                <button type="button" @click="showConfirmModal = false"
                                    class="w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-white border border-gray-200 text-gray-600 font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all flex justify-center">
                                    Batal
                                </button>
                                <button type="button" wire:click="processPayment" @click="showConfirmModal = false"
                                    class="w-full sm:w-auto px-6 py-3 sm:py-2.5 bg-[#1c69d4] hover:bg-blue-700 text-white font-black rounded-xl shadow-md shadow-blue-500/30 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Konfirmasi & Bayar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- MODAL KONFIRMASI PIUTANG --}}
            <template x-teleport="body">
                <div x-show="showPiutangModal" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;"
                    aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div
                        class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="showPiutangModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 transition-opacity bg-gray-900/50 backdrop-blur-sm"
                            aria-hidden="true" @click="showPiutangModal = false"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                            aria-hidden="true">&#8203;</span>

                        <div x-show="showPiutangModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="inline-block w-full max-w-xl overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle border border-gray-100 relative">

                            {{-- Header --}}
                            <div
                                class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-white flex justify-between items-start sm:items-center sticky top-0 z-10">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg sm:text-xl font-black text-gray-800" id="modal-title">Konfirmasi Piutang</h3>
                                        <span
                                            class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] sm:text-xs font-bold border border-blue-100">{{ $this->displayCustomerName }}</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Transaksi ini akan dicatat sebagai piutang (belum lunas)
                                    </p>
                                </div>
                                <button type="button" @click="showPiutangModal = false"
                                    class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 focus:outline-none p-2 rounded-xl transition-all shrink-0">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Body --}}
                            <div
                                class="px-4 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6 max-h-[65vh] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-200">
                                
                                <div class="bg-violet-50 border border-violet-100 text-violet-700 p-4 rounded-xl flex gap-3 text-sm">
                                    <svg class="w-6 h-6 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="font-bold mb-1">Perhatian!</p>
                                        <p>Order ini akan langsung diproses dan faktur akan dibuat di Accurate, namun <b>tanpa pelunasan</b> (Piutang). Pastikan data customer sudah benar.</p>
                                    </div>
                                </div>

                                {{-- Rincian Tagihan --}}
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Rincian
                                        Tagihan</h4>
                                    <div class="bg-violet-600/5 rounded-xl p-3 sm:p-4 border border-violet-600/10 space-y-2">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 font-medium">Subtotal</span>
                                            <span class="font-bold text-gray-800">Rp
                                                {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                        @if ($this->itemDiscountTotal > 0)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Diskon Item</span>
                                                <span class="font-medium text-rose-400">- Rp
                                                    {{ number_format($this->itemDiscountTotal, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if ($this->promoDiscountTotal > 0)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Promo</span>
                                                <span class="font-medium text-rose-400">- Rp
                                                    {{ number_format($this->promoDiscountTotal, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 font-medium">Total Diskon</span>
                                            <span class="font-bold text-rose-500">- Rp
                                                {{ number_format($this->totalDiscount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="border-t border-violet-600/20 my-2 pt-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-800 font-black">Total Piutang</span>
                                                <span class="font-black text-xl sm:text-2xl text-violet-600">Rp
                                                    {{ number_format(max(0, $this->subtotal - $this->totalDiscount), 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div
                                class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-white flex flex-col-reverse sm:flex-row justify-end gap-3 rounded-b-2xl sticky bottom-0 z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                                <button type="button" @click="showPiutangModal = false"
                                    class="w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-white border border-gray-200 text-gray-600 font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all flex justify-center">
                                    Batal
                                </button>
                                <button type="button" wire:click="processPiutang" @click="showPiutangModal = false"
                                    class="w-full sm:w-auto px-6 py-3 sm:py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-black rounded-xl shadow-md shadow-violet-500/30 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Konfirmasi Piutang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
