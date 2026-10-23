{{-- ═══════════════════════════════════════════════════════════
      MODAL: Receipt (Struk) - Read Only untuk Laporan Management
 ═══════════════════════════════════════════════════════════ --}}
@if ($showReceiptModal && $completedOrder)
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto"
         @keydown.window.escape="$wire.closeReceipt()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative my-auto animate-in fade-in duration-150 border border-gray-100"
             @click.outside="$wire.closeReceipt()">
            
            {{-- Header Modal --}}
            <div class="p-4 bg-gray-50/90 border-b border-gray-200/70 flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#1c69d4] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-900 text-sm">Struk Transaksi POS</h3>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Read-Only
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ $completedOrder->order_number }}</p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-1.5">
                    {{-- Tombol Cetak Thermal ESC/POS (RawBT / QZ) --}}
                    <button type="button" wire:click="getEscposBase64" wire:loading.attr="disabled"
                        class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition disabled:opacity-50"
                        title="Cetak Cepat ke Printer Thermal (ESC/POS)">
                        <svg wire:loading.remove wire:target="getEscposBase64" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <svg wire:loading wire:target="getEscposBase64" class="animate-spin w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>

                    {{-- Tombol Cetak Browser / PDF --}}
                    <button type="button" onclick="window.print()"
                        class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                        title="Cetak Browser (PDF / A4)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                    </button>

                    {{-- Tombol Tutup --}}
                    <button type="button" wire:click="closeReceipt" 
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition"
                        title="Tutup (Esc)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Receipt Paper Preview --}}
            <div id="receipt-readonly-print-area" class="p-6 font-mono text-xs leading-relaxed overflow-y-auto max-h-[70vh] bg-white text-gray-800 select-text">
                <div class="text-center mb-3">
                    <p class="font-bold text-sm tracking-tight text-gray-900">{{ optional($completedOrder->businessUnit)->store_title ?? 'Z-POS STORE' }}</p>
                    <p class="text-[11px] text-gray-500 font-medium">
                        {{ $completedOrder->shipping_address_snapshot['store'] ?? 'Toko' }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        {{ $completedOrder->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="border-t border-dashed border-gray-300 my-2.5"></div>

                <div class="space-y-0.5 text-[11px] text-gray-600">
                    <div class="flex justify-between">
                        <span>No. Transaksi</span>
                        <span class="font-bold font-mono text-gray-900">{{ $completedOrder->order_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir</span>
                        <span>{{ $completedOrder->handledBy->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Sales</span>
                        <span>{{ $completedOrder->salesBy->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Customer</span>
                        <span class="font-medium text-gray-900">{{ $completedOrder->user->name ?? 'Walk-in' }}</span>
                    </div>
                    @if (optional($completedOrder->user?->profile)->phone_number)
                        <div class="flex justify-between">
                            <span>No. Telp</span>
                            <span>{{ $completedOrder->user->profile->phone_number }}</span>
                        </div>
                    @endif
                </div>

                <div class="border-t border-dashed border-gray-300 my-2.5"></div>

                {{-- Daftar Item Transaksi --}}
                <div class="space-y-2 mb-2">
                    @foreach ($completedOrder->items as $item)
                        @php
                            $v = $item->variant;
                            if ($v instanceof \App\Models\ProductAccurate) {
                                $itemName = $v->name ?? '-';
                                $ram = '';
                                $storage = '';
                                $color = '';
                            } else {
                                $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
                                $ram = $v ? $v->ram ?? '' : '';
                                $storage = $v ? $v->storage ?? '' : '';
                                $color = $v ? $v->color ?? '' : '';
                            }
                            $itemName = preg_replace('/^(?:DS\s*-\s*HP\s*|DS\s*-\s*|HP\s*-\s*|HP\s*)/i', '', trim($itemName));
                        @endphp
                        <div>
                            <p class="font-bold text-gray-900 leading-snug">
                                {{ $itemName }}
                                @if ($ram != null)
                                    {{ $ram }}/
                                @endif{{ $storage }}
                                {{ $color }}
                            </p>
                            <div class="flex justify-between text-gray-600 mt-0.5">
                                <span>{{ $item->qty }}x {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                <span class="font-semibold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if ($item->serial_number)
                                <p class="text-[10px] text-gray-500 font-mono mt-0.5">
                                    SN: {{ $item->serial_number }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-dashed border-gray-300 my-2.5"></div>

                {{-- Rekap Subtotal, Diskon & Total --}}
                <div class="space-y-1 text-[11px]">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($completedOrder->total_amount, 0, ',', '.') }}</span>
                    </div>

                    @if ($completedOrder->discount_amount > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Diskon</span>
                            <span>-Rp {{ number_format($completedOrder->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if ($completedOrder->coupon_discount_amount > 0)
                        <div class="flex justify-between text-purple-600">
                            <span>Voucher ({{ $completedOrder->coupon_code }})</span>
                            <span>-Rp {{ number_format($completedOrder->coupon_discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if ($completedOrder->loyalty_discount_amount > 0)
                        <div class="flex justify-between text-amber-600">
                            <span>Diskon Poin</span>
                            <span>-Rp {{ number_format($completedOrder->loyalty_discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="border-t border-dashed border-gray-300 my-1.5"></div>

                    <div class="flex justify-between font-bold text-sm text-gray-900 pt-0.5">
                        <span>TOTAL</span>
                        <span>Rp {{ number_format($completedOrder->grand_total, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="border-t border-dashed border-gray-300 my-2.5"></div>

                {{-- Detail Pembayaran --}}
                <div class="space-y-1 mb-2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Rincian Pembayaran:</p>
                    @forelse ($completedOrder->payments as $payment)
                        <div class="flex justify-between text-[11px] text-gray-600">
                            <span>
                                {{ $payment->paymentMethod->name ?? 'Cash' }}
                                @if (optional($payment->paymentMethod)->bank_name)
                                    - {{ $payment->paymentMethod->bank_name }}
                                @endif
                                @if ($payment->paymentMethodRate)
                                    ({{ $payment->paymentMethodRate->name }})
                                @endif
                            </span>
                            <span class="font-semibold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-[10px] text-gray-400 italic">Data pembayaran tidak ditemukan.</p>
                    @endforelse
                </div>

                {{-- Dokumen Accurate & Catatan --}}
                @if ($completedOrder->accurate_invoice_no || $completedOrder->accurate_so_number || $completedOrder->notes)
                    <div class="border-t border-dashed border-gray-300 my-2.5"></div>
                    <div class="space-y-0.5 text-[10px] text-gray-500">
                        @if ($completedOrder->accurate_invoice_no)
                            <p>No. Invoice Accurate: <span class="font-mono font-medium text-gray-700">{{ $completedOrder->accurate_invoice_no }}</span></p>
                        @endif
                        @if ($completedOrder->accurate_so_number)
                            <p>No. SO Accurate: <span class="font-mono font-medium text-gray-700">{{ $completedOrder->accurate_so_number }}</span></p>
                        @endif
                        @if ($completedOrder->notes)
                            <p class="mt-1 text-gray-600 italic">"Catatan: {{ $completedOrder->notes }}"</p>
                        @endif
                    </div>
                @endif

                {{-- Footer Struk --}}
                <div class="text-center mt-5 pt-2 border-t border-dashed border-gray-200">
                    <p class="text-[10px] text-gray-500">Terima kasih telah berbelanja!</p>
                    <p class="text-[10px] text-gray-400">Call Center: 0811-5600-6464</p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-3 bg-gray-50 border-t border-gray-200/70 flex items-center justify-end">
                <button type="button" wire:click="closeReceipt"
                    class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-gray-700 bg-white hover:bg-gray-100 border border-gray-200 shadow-xs transition">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>

    {{-- Styling khusus untuk browser print --}}
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #receipt-readonly-print-area, #receipt-readonly-print-area * {
                visibility: visible !important;
            }
            #receipt-readonly-print-area {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 80mm !important;
                margin: 0 auto !important;
                padding: 10px !important;
                background: white !important;
                color: black !important;
                box-shadow: none !important;
            }
        }
    </style>
@endif
