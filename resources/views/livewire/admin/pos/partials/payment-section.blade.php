                {{-- Payment Methods --}}
                <div class="px-4 py-3 space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Metode Pembayaran</p>
                        <button type="button" wire:click="addPaymentRow"
                            class="text-[11px] font-bold text-[#1c69d4] hover:text-blue-800 flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Split Pembayaran
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($payments as $index => $payment)
                            <div class="p-2.5 bg-white border border-gray-200 rounded-xl space-y-2 relative"
                                wire:key="payment-row-{{ $index }}">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-extrabold text-gray-500">Alokasi
                                        #{{ $index + 1 }}</span>
                                    @if (count($payments) > 1)
                                        <button type="button" wire:click="removePaymentRow({{ $index }})"
                                            class="text-rose-500 hover:text-rose-700 text-[10px] font-bold flex items-center gap-0.5 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </button>
                                    @endif
                                </div>

                                <select wire:model.live="payments.{{ $index }}.payment_method_id"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-bold focus:border-[#1c69d4] focus:ring-0">
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach ($this->paymentMethods as $pm)
                                        <option value="{{ $pm->id }}">{{ $pm->name }}
                                            {{ $pm->rates->count() > 0 ? '(' . $pm->rates->count() . ' tarif)' : ($pm->mdr_percentage > 0 ? '(MDR ' . $pm->mdr_percentage . '%)' : '') }}
                                        </option>
                                    @endforeach
                                </select>

                                @php
                                    $pmId = $payment['payment_method_id'];
                                    $pmObj = $pmId ? \App\Models\PaymentMethod::find($pmId) : null;
                                    $rowRates = $pmObj ? $pmObj->rates()->where('is_active', true)->get() : collect();
                                @endphp

                                @if ($rowRates->count() > 0)
                                    <select wire:model.live="payments.{{ $index }}.payment_method_rate_id"
                                        class="w-full bg-blue-50/50 border border-blue-100 text-blue-900 rounded-lg px-2 py-1.5 text-xs font-bold focus:border-[#1c69d4] focus:ring-0">
                                        <option value="">-- Pilih Opsi / Tenor --</option>
                                        @foreach ($rowRates as $rate)
                                            <option value="{{ $rate->id }}">{{ $rate->name }} (MDR
                                                {{ $rate->mdr_percentage }}%)</option>
                                        @endforeach
                                    </select>
                                @endif

                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <span
                                            class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Rp</span>
                                        <input type="number" wire:model.live="payments.{{ $index }}.amount"
                                            class="w-full pl-7 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold focus:border-[#1c69d4] focus:ring-0"
                                            placeholder="Jumlah Bayar" min="0">
                                    </div>
                                    @if (count($payments) > 1)
                                        <button type="button" wire:click="autofillRemaining({{ $index }})"
                                            class="px-2 py-1.5 text-xs font-bold bg-[#1c69d4] text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all whitespace-nowrap">
                                            Gunakan Sisa
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Validation Status Banner --}}
                    @php
                        $targetTotal = max(0, $this->subtotal - $this->totalDiscount);
                        $allocatedTotal = (int) $this->paymentsTotalBase;
                        $diff = $targetTotal - $allocatedTotal;
                    @endphp

                    @if ((int)$diff === 0)
                        <div
                            class="flex items-center gap-2 p-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-xs font-bold justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Jumlah Pembayaran Sesuai
                        </div>
                    @elseif ($diff > 0)
                        <div
                            class="flex items-center gap-2 p-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-xs font-bold justify-center">
                            <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Kurang Bayar: Rp {{ number_format($diff, 0, ',', '.') }}
                        </div>
                    @else
                        <div
                            class="flex items-center gap-2 p-2 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs font-bold justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Kelebihan Bayar: Rp {{ number_format(abs($diff), 0, ',', '.') }}
                        </div>
                    @endif
                </div>

                {{-- Discount --}}
                <div class="px-4 py-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Diskon Manual (Rp)
                    </p>
                    <input type="number" wire:model.live.debounce.300ms="discount_amount"
                        class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-bold focus:border-[#1c69d4] focus:ring-0"
                        placeholder="0" min="0">
                </div>

                {{-- Promos --}}
                @if (count($this->activePromos) > 0)
                    <div class="px-4 pb-3">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Gunakan
                            Promo/Voucher</p>
                        <div
                            class="space-y-2 bg-gray-50 border border-gray-100 p-2.5 rounded-lg max-h-32 overflow-y-auto">
                            @foreach ($this->activePromos as $promo)
                                <label class="flex items-start gap-2 cursor-pointer group">
                                    <input type="checkbox" wire:model.live="selectedPromos" value="{{ $promo->id }}"
                                        class="mt-0.5 rounded text-[#1c69d4] focus:ring-[#1c69d4] border-gray-300">
                                    <div class="text-xs">
                                        <div
                                            class="font-bold text-gray-700 group-hover:text-[#1c69d4] transition-colors">
                                            {{ $promo->name }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">
                                            @if ($promo->code)
                                                {{ $promo->code }} &bull;
                                            @endif
                                            @if ($promo->discount_type === 'fixed')
                                                Potongan Rp {{ number_format($promo->discount_value, 0, ',', '.') }}
                                            @else
                                                Potongan {{ number_format($promo->discount_value, 0) }}%
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Catatan --}}
                <div class="px-4 pb-4">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Catatan Pesanan</p>
                    <textarea wire:model.defer="notes" rows="2"
                        class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0 placeholder-gray-300 resize-none"
                        placeholder="Opsional..."></textarea>
                </div>
