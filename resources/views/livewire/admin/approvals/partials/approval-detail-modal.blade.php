{{-- ═══════════════════════════════════════════════════════════
      MODAL: Detail Pengajuan & Struk Transaksi (View Only)
 ═══════════════════════════════════════════════════════════ --}}
@if ($showDetailModal && $detailRequest)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
        <div class="relative w-full {{ $detailOrder ? 'max-w-4xl' : 'max-w-xl' }} bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden my-8" @click.outside="$wire.closeDetail()">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Detail Persetujuan</h3>
                        <p class="text-xs text-gray-500">ID Pengajuan #{{ $detailRequest->id }} &bull; {{ $detailRequest->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($detailRequest->status === 'PENDING')
                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold rounded-lg uppercase">Pending</span>
                    @elseif($detailRequest->status === 'APPROVED')
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg uppercase">Approved</span>
                    @elseif($detailRequest->status === 'COMPLETED')
                        <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold rounded-lg uppercase">Selesai</span>
                    @else
                        <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold rounded-lg uppercase">Ditolak</span>
                    @endif

                    <button wire:click="closeDetail" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto max-h-[calc(85vh-130px)]">
                @if($detailOrder)
                    {{-- 2-Column Grid jika ada Order (Struk + Detail Pengajuan) --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        
                        {{-- Left Column: Struk Transaksi (Thermal Style) --}}
                        <div class="lg:col-span-6 bg-gray-50/80 rounded-2xl border border-dashed border-gray-200 p-5 font-mono text-xs text-gray-800 shadow-inner">
                            <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-3">
                                <span class="font-sans font-bold text-gray-700 text-xs flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Struk Pesanan
                                </span>
                                <span class="font-sans text-[10px] px-2 py-0.5 rounded bg-gray-200 font-semibold text-gray-700">Read-Only</span>
                            </div>

                            <div class="text-center mb-3">
                                <p class="font-bold text-sm text-gray-900">{{ optional($detailOrder->businessUnit)->store_title ?? 'Z-POS STORE' }}</p>
                                <p class="text-[10px] text-gray-500">{{ $detailOrder->shipping_address_snapshot['store'] ?? 'Toko' }}</p>
                                <p class="text-[10px] text-gray-400">{{ $detailOrder->created_at->format('d/m/Y H:i') }}</p>
                            </div>

                            <div class="border-t border-dashed border-gray-300 my-2"></div>

                            <div class="space-y-0.5 text-[11px]">
                                <div class="flex justify-between"><span class="text-gray-500">No. Order:</span><span class="font-bold text-gray-900">{{ $detailOrder->order_number }}</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Kasir:</span><span>{{ $detailOrder->handledBy->name ?? '-' }}</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Sales:</span><span>{{ $detailOrder->salesBy->name ?? '-' }}</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Customer:</span><span class="font-semibold">{{ $detailOrder->user->name ?? '-' }}</span></div>
                                @if(optional($detailOrder->user)->profile?->phone_number)
                                <div class="flex justify-between"><span class="text-gray-500">Telp:</span><span>{{ $detailOrder->user->profile->phone_number }}</span></div>
                                @endif
                            </div>

                            <div class="border-t border-dashed border-gray-300 my-2.5"></div>

                            {{-- Items List --}}
                            <div class="space-y-2 mb-3">
                                @foreach ($detailOrder->items as $item)
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
                                    <div class="text-[11px] leading-tight">
                                        <p class="font-bold text-gray-900">
                                            {{ $itemName }}
                                            @if ($ram != null){{ $ram }}/@endif{{ $storage }} {{ $color }}
                                        </p>
                                        <div class="flex justify-between text-gray-600 mt-0.5">
                                            <span>{{ $item->qty }}x Rp {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                            <span class="font-medium text-gray-800">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                        @if ($item->serial_number)
                                            <p class="text-[9px] text-gray-400 font-mono mt-0.5">SN: {{ $item->serial_number }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-dashed border-gray-300 my-2"></div>

                            {{-- Summary --}}
                            <div class="space-y-1 text-[11px]">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span>Rp {{ number_format($detailOrder->total_amount, 0, ',', '.') }}</span>
                                </div>
                                @if ($detailOrder->discount_amount > 0)
                                    <div class="flex justify-between text-rose-600 font-medium">
                                        <span>Diskon</span>
                                        <span>-Rp {{ number_format($detailOrder->discount_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="border-t border-dashed border-gray-300 my-1.5"></div>
                                <div class="flex justify-between font-black text-xs text-gray-900">
                                    <span>TOTAL</span>
                                    <span>Rp {{ number_format($detailOrder->grand_total, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="border-t border-dashed border-gray-300 my-2"></div>

                            {{-- Payments --}}
                            <div class="space-y-0.5 text-[10px] text-gray-600">
                                @forelse ($detailOrder->payments as $payment)
                                    <div class="flex justify-between">
                                        <span>Bayar ({{ $payment->paymentMethod->name ?? 'Cash' }}{{ optional($payment->paymentMethod)->bank_name ? ' - ' . $payment->paymentMethod->bank_name : '' }}{{ $payment->paymentMethodRate ? ' - ' . $payment->paymentMethodRate->name : '' }}):</span>
                                        <span class="font-semibold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <div class="text-gray-400 italic">Belum ada rincian pembayaran</div>
                                @endforelse
                            </div>

                            @if ($detailOrder->accurate_invoice_no)
                                <div class="mt-2 text-[10px] text-gray-500 font-mono">
                                    Accurate Invoice: {{ $detailOrder->accurate_invoice_no }}
                                </div>
                            @endif

                            @if ($detailOrder->notes)
                                <div class="mt-2 p-2 bg-white rounded border border-gray-200 text-[10px] text-gray-600">
                                    <span class="font-bold text-gray-700">Catatan Order:</span> {{ $detailOrder->notes }}
                                </div>
                            @endif
                        </div>

                        {{-- Right Column: Detail Approval & Alasan --}}
                        <div class="lg:col-span-6 space-y-4">
                            
                            {{-- Info Pemohon Card --}}
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Informasi Pengajuan</h4>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-400 block text-[10px]">Tipe Pengajuan</span>
                                        <span class="font-bold text-blue-600 uppercase">{{ str_replace('_', ' ', $detailRequest->request_type) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px]">Level Persetujuan</span>
                                        <span class="font-bold text-gray-800">Level {{ $detailRequest->current_level }} dari {{ $detailRequest->required_level }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px]">Pemohon</span>
                                        <span class="font-semibold text-gray-800">{{ $detailRequest->requestedBy->name ?? '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px]">Cabang</span>
                                        <span class="font-semibold text-gray-800">{{ $detailRequest->requestedBy->branch->name ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Alasan Pengajuan Card (Highlight) --}}
                            <div class="bg-amber-50/60 border border-amber-200 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Alasan Pengajuan</h4>
                                </div>
                                <div class="bg-white/80 p-3 rounded-lg border border-amber-100 text-xs text-gray-800 leading-relaxed whitespace-pre-line font-medium">
                                    {{ $detailRequest->reason ?: 'Tidak ada alasan yang dicantumkan.' }}
                                </div>
                            </div>

                            {{-- Riwayat Persetujuan Timeline --}}
                            <div class="bg-white border border-gray-200 rounded-xl p-4">
                                <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Riwayat Persetujuan
                                </h4>

                                @if($detailRequest->histories && $detailRequest->histories->count() > 0)
                                    <div class="space-y-3 relative before:absolute before:inset-0 before:left-3.5 before:w-0.5 before:bg-gray-200">
                                        @foreach($detailRequest->histories as $history)
                                            <div class="relative flex items-start gap-3 pl-1">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 {{ $history->action === 'APPROVED' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                                                    @if($history->action === 'APPROVED')
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    @else
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    @endif
                                                </div>
                                                <div class="flex-1 bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-xs">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-bold {{ $history->action === 'APPROVED' ? 'text-emerald-700' : 'text-rose-700' }}">
                                                            Level {{ $history->level }} &bull; {{ $history->action }}
                                                        </span>
                                                        <span class="text-[10px] text-gray-400">{{ $history->created_at->format('d/m/y H:i') }}</span>
                                                    </div>
                                                    <p class="text-gray-700 mt-1 text-[11px] font-medium">
                                                        Oleh: <span class="font-semibold">{{ $history->actedBy->name ?? 'Sistem' }}</span>
                                                    </p>
                                                    @if($history->notes)
                                                        <p class="text-gray-500 text-[10px] mt-0.5 italic">"{{ $history->notes }}"</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-gray-400 italic text-center py-2">Belum ada riwayat tindakan pada pengajuan ini.</p>
                                @endif
                            </div>

                        </div>
                    </div>
                @else
                    {{-- Layout untuk Non-Order Request (Custom Cashback, Warranty Extension, dll) --}}
                    <div class="space-y-4">
                        
                        {{-- Info Pengajuan Grid --}}
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Informasi Pengajuan</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <span class="text-gray-400 block text-[10px]">Tipe Pengajuan</span>
                                    <span class="font-bold text-blue-600 uppercase">{{ str_replace('_', ' ', $detailRequest->request_type) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px]">Level Persetujuan</span>
                                    <span class="font-bold text-gray-800">Level {{ $detailRequest->current_level }} dari {{ $detailRequest->required_level }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px]">Pemohon</span>
                                    <span class="font-semibold text-gray-800">{{ $detailRequest->requestedBy->name ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px]">Cabang</span>
                                    <span class="font-semibold text-gray-800">{{ $detailRequest->requestedBy->branch->name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Payload Data (Jika ada) --}}
                        @if($detailRequest->payload)
                            <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
                                <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Detail Data Tambahan
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-white p-3 rounded-lg border border-blue-100">
                                    @if($detailRequest->request_type === 'CUSTOM_CASHBACK')
                                        <div>
                                            <span class="text-gray-400 block text-[10px]">Nama Item / Produk</span>
                                            <span class="font-bold text-gray-800">{{ $detailRequest->payload['product_name'] ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[10px]">Nominal Cashback</span>
                                            <span class="font-black text-emerald-600 text-sm">Rp {{ number_format($detailRequest->payload['amount'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        @foreach($detailRequest->payload as $key => $val)
                                            <div>
                                                <span class="text-gray-400 block text-[10px] uppercase">{{ str_replace('_', ' ', $key) }}</span>
                                                <span class="font-semibold text-gray-800">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Alasan Pengajuan Card --}}
                        <div class="bg-amber-50/60 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Alasan Pengajuan</h4>
                            </div>
                            <div class="bg-white/80 p-3 rounded-lg border border-amber-100 text-xs text-gray-800 leading-relaxed whitespace-pre-line font-medium">
                                {{ $detailRequest->reason ?: 'Tidak ada alasan yang dicantumkan.' }}
                            </div>
                        </div>

                        {{-- Riwayat Persetujuan Timeline --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Riwayat Persetujuan
                            </h4>

                            @if($detailRequest->histories && $detailRequest->histories->count() > 0)
                                <div class="space-y-3 relative before:absolute before:inset-0 before:left-3.5 before:w-0.5 before:bg-gray-200">
                                    @foreach($detailRequest->histories as $history)
                                        <div class="relative flex items-start gap-3 pl-1">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 {{ $history->action === 'APPROVED' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                                                @if($history->action === 'APPROVED')
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-xs">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-bold {{ $history->action === 'APPROVED' ? 'text-emerald-700' : 'text-rose-700' }}">
                                                        Level {{ $history->level }} &bull; {{ $history->action }}
                                                    </span>
                                                    <span class="text-[10px] text-gray-400">{{ $history->created_at->format('d/m/y H:i') }}</span>
                                                </div>
                                                <p class="text-gray-700 mt-1 text-[11px] font-medium">
                                                    Oleh: <span class="font-semibold">{{ $history->actedBy->name ?? 'Sistem' }}</span>
                                                </p>
                                                @if($history->notes)
                                                    <p class="text-gray-500 text-[10px] mt-0.5 italic">"{{ $history->notes }}"</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 italic text-center py-2">Belum ada riwayat tindakan pada pengajuan ini.</p>
                            @endif
                        </div>

                    </div>
                @endif
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button wire:click="closeDetail" class="px-5 py-2 rounded-xl font-bold text-xs text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 transition shadow-sm">
                    Tutup
                </button>
            </div>

        </div>
    </div>
@endif
