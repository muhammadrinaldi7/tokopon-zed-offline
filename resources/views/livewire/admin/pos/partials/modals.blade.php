    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Variant Picker
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showVariantModal && $variantModalProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-5 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-gray-900">{{ $variantModalProduct->name }}</h3>
                        <p class="text-xs text-gray-400">Pilih varian yang akan dijual</p>
                    </div>
                    <button wire:click="$set('showVariantModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-5 space-y-2 max-h-80 overflow-y-auto">
                    @foreach ($variantModalVariants as $variant)
                        <button wire:click="addVariantToCart({{ $variant['id'] }})"
                            class="w-full p-4 rounded-xl border border-gray-100 hover:border-[#1c69d4]/50 hover:bg-blue-50/30 transition-all text-left flex justify-between items-center {{ $variant['stock'] <= 0 ? 'opacity-40 cursor-not-allowed' : '' }}"
                            {{ $variant['stock'] <= 0 ? 'disabled' : '' }}>
                            <div>
                                <p class="font-bold text-gray-800">{{ $variant['label'] }}</p>
                                @if ($variant['condition'])
                                    <p class="text-[10px] text-emerald-500 font-bold uppercase">
                                        {{ $variant['condition'] }}</p>
                                @endif
                                <p class="text-xs text-gray-400 font-mono mt-0.5">SKU: {{ $variant['sku'] ?: '-' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-[#1c69d4]">Rp
                                    {{ number_format($variant['price'], 0, ',', '.') }}</p>
                                <p
                                    class="text-[10px] text-gray-400 font-bold {{ $variant['stock'] <= 0 ? 'text-rose-500' : '' }}">
                                    Stok: {{ $variant['stock'] }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Checkout Confirmation
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showCheckoutModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-5 bg-gray-50 border-b border-gray-100">
                    <h3 class="font-black text-gray-900 text-xl">Konfirmasi Pembayaran</h3>
                    <p class="text-sm text-gray-500 mt-1">Pastikan semua data sudah benar sebelum memproses.</p>
                </div>
                <div class="p-5 space-y-3 max-h-60 overflow-y-auto">
                    @foreach ($cart as $item)
                        <div class="flex justify-between text-sm border-b border-gray-50 pb-2">
                            <div>
                                <p class="font-bold text-gray-800">{{ $item['name'] }} <span
                                        class="text-gray-400">({{ $item['color'] }}/{{ $item['storage'] }})</span>
                                </p>
                                <p class="text-[10px] text-gray-400 font-mono">SN: {{ $item['serial_number'] }}</p>
                            </div>
                            <p class="font-bold text-gray-700 whitespace-nowrap">{{ $item['qty'] }}x Rp
                                {{ number_format($item['price'], 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="p-5 bg-gray-50 border-t border-gray-100 space-y-1">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span
                            class="font-bold">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                    @if ($this->discount_amount > 0)
                        <div class="flex justify-between text-sm"><span class="text-rose-500">Diskon</span><span
                                class="font-bold text-rose-500">- Rp
                                {{ number_format($this->discount_amount, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between pt-2 border-t border-gray-200"><span
                            class="font-black text-lg">TOTAL</span><span class="font-black text-[#1c69d4] text-lg">Rp
                            {{ number_format($this->grandTotal, 0, ',', '.') }}</span></div>
                </div>
                <div class="p-5 flex gap-3">
                    <button wire:click="$set('showCheckoutModal', false)"
                        class="flex-1 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Batal</button>
                    <button wire:click="processPayment" wire:loading.attr="disabled" wire:target="processPayment"
                        class="flex-1 py-3 rounded-xl font-bold text-white bg-[#1c69d4] hover:bg-blue-700 transition shadow-md shadow-blue-500/20">
                        <span wire:loading.remove wire:target="processPayment">Proses Bayar</span>
                        <span wire:loading wire:target="processPayment">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: History Sales (Riwayat Penjualan)
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showHistoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
                {{-- Header --}}
                <div class="p-5 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-gray-900 text-lg">20 Transaksi POS Terakhir</h3>
                        <p class="text-xs text-gray-400">Daftar penjualan yang berhasil diproses lewat kasir</p>
                    </div>
                    <button wire:click="$set('showHistoryModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Table/Content --}}
                <div class="p-5 max-h-[450px] overflow-y-auto">
                    @if (count($historyOrders) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr
                                        class="border-b border-gray-200 text-gray-400 uppercase font-black tracking-wider bg-gray-50/50">
                                        <th class="p-3">Waktu / No. Order</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3">Metode</th>
                                        <th class="p-3 text-right">Total Akhir</th>
                                        <th class="p-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                    @foreach ($historyOrders as $order)
                                        <tr class="hover:bg-gray-50/80 transition-colors">
                                            <td class="p-3">
                                                <p class="font-bold text-gray-900">{{ $order->order_number }}</p>
                                                <p class="text-[10px] text-gray-400 font-mono">
                                                    {{ $order->created_at->format('d M Y H:i') }}</p>
                                            </td>
                                            <td class="p-3 text-gray-600">
                                                {{ $order->user->name ?? 'Umum/Cash' }}
                                            </td>
                                            <td class="p-3">
                                                <span
                                                    class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-md uppercase">
                                                    {{ $order->paymentMethod->name ?? 'Cash' }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-right font-bold text-gray-900">
                                                Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                                            </td>
                                            <td class="p-3 text-center">
                                                <button wire:click="reprintOrder({{ $order->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-md text-[11px] font-bold transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                    </svg>
                                                    Struk
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-300">
                            <svg class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-sm font-bold text-gray-400">Belum ada riwayat transaksi hari ini</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button wire:click="$set('showHistoryModal', false)"
                        class="px-4 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-xl text-xs font-bold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Receipt (Struk)
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showReceiptModal && $completedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-black text-gray-900">Struk Transaksis</h3>
                    <div class="flex items-center gap-2">
                        <button
                            onclick="document.getElementById('receipt-content').classList.remove('hidden'); window.print();"
                            class="text-[#1c69d4] hover:text-blue-700 font-bold text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>

                        </button>
                        <button wire:click="getEscposBase64" wire:loading.attr="disabled"
                            class="text-teal-600 hover:text-teal-700 font-bold text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span wire:loading.remove wire:target="getEscposBase64"></span>
                            <span wire:loading wire:target="getEscposBase64">Memproses...</span>
                        </button>
                        {{-- ─── TOMBOL WHATSAPP MEKARI QONTAK ─── --}}
                        @if (Auth::user()->hasRole('admin') || !$completedOrder->is_wa_sent)
                            {{-- Aktif jika Admin ATAU jika WA belum pernah dikirim --}}
                            <button wire:click="sendReceiptToQontak" wire:loading.attr="disabled"
                                class="text-emerald-600 hover:text-emerald-700 font-bold text-xs flex items-center gap-1 transition">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397 0 11.983 0c3.192.001 6.192 1.242 8.447 3.498c2.256 2.255 3.497 5.255 3.497 8.447c-.004 6.585-5.342 11.93-11.93 11.93c-2.002-.001-3.973-.503-5.729-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451c5.436 0 9.86-4.42 9.864-9.858c.002-2.634-1.023-5.11-2.887-6.974c-1.864-1.864-4.341-2.887-6.973-2.889c-5.44 0-9.865 4.42-9.869 9.859c-.001 1.706.469 3.372 1.36 4.866l-.993 3.626l3.71-.973zm11.233-6.17c-.3-.149-1.774-.875-2.046-.974c-.272-.1-.471-.149-.669.149c-.198.299-.768.974-.941 1.173c-.173.199-.347.224-.647.075c-.3-.15-1.266-.466-2.41-1.487c-.89-.794-1.49-1.774-1.664-2.073c-.173-.3-.018-.462.13-.61c.134-.133.298-.348.446-.521c.15-.173.199-.298.298-.497c.099-.198.05-.372-.025-.521c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.501-.669-.51l-.57-.01c-.199 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487c.709.306 1.263.489 1.694.626c.712.226 1.36.194 1.872.118c.571-.085 1.774-.726 2.022-1.392c.247-.667.247-1.241.173-1.392c-.074-.15-.272-.249-.571-.398z" />
                                </svg>
                                WA (Sent)
                            </button>
                        @endif

                        {{-- ─── TOMBOL EMAIL POS_SALES ─── --}}
                        @if (Auth::user()->hasRole('admin') || !$completedOrder->is_email_sent)
                            {{-- Aktif jika Admin ATAU jika Email belum pernah dikirim --}}
                            <button wire:click="sendReceiptToEmail" wire:loading.attr="disabled"
                                class="text-blue-600 hover:text-blue-700 font-bold text-xs flex items-center gap-1 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span wire:loading.remove wire:target="sendReceiptToEmail">Email</span>
                                <span wire:loading wire:target="sendReceiptToEmail">Sending...</span>
                            </button>
                        @else
                            {{-- Terkunci untuk Kasir/FL jika is_email_sent bernilai true --}}
                            <button disabled
                                class="text-gray-300 cursor-not-allowed font-bold text-xs flex items-center gap-1"
                                title="Sudah dikirim oleh kasir">
                                <svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Email (Sent)
                            </button>
                        @endif

                        {{-- Tombol Tutup --}}
                        <button wire:click="closeReceipt" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Receipt Preview --}}
                <div id="receipt-content" class="p-5 font-mono text-xs leading-relaxed">
                    <div class="text-center mb-3">
                        <p class="font-bold text-sm">TOKOPUN</p>
                        <p class="text-[10px] text-gray-500">
                            {{ $completedOrder->shipping_address_snapshot['store'] ?? 'Toko' }}</p>
                        <p class="text-[10px] text-gray-400">{{ $completedOrder->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <p class="text-[10px] text-gray-500">Tanggal:
                        {{ $completedOrder->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-[10px] text-gray-500">No: {{ $completedOrder->order_number }}</p>
                    <p class="text-[10px] text-gray-500">Sales: {{ $completedOrder->salesBy->first()->name ?? '-' }}
                    </p>
                    <p class="text-[10px] text-gray-500">Customer: {{ $completedOrder->user->name ?? '-' }}</p>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    @foreach ($completedOrder->items as $item)
                        @php
                            $v = $item->variant;
                            $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
                        @endphp
                        <div class="mb-1">
                            <p class="font-bold">{{ $itemName }}</p>
                            <div class="flex justify-between">
                                <span>{{ $item->qty }}x
                                    {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if ($item->serial_number)
                                <p class="text-[9px] text-gray-400">SN: {{ $item->serial_number }}</p>
                                @if ($item->product_variant_type === 'App\Models\SecondProductVariant')
                                    @php
                                        $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                                    @endphp
                                    @foreach ($sns as $sn)
                                        @if ($sn)
                                            <div
                                                class="mt-2 mb-1 flex flex-col items-center justify-center p-2 border border-dashed border-gray-300 rounded bg-gray-50">
                                                <p class="text-[8px] text-gray-500 font-bold mb-1 text-center">
                                                    Sertifikat QC Perangkat<br>(SN: {{ $sn }})</p>
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode(route('public.device-qc', ['imei' => $sn])) }}"
                                                    class="w-16 h-16 grayscale mix-blend-multiply">
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                        </div>
                    @endforeach
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="flex justify-between">
                        <span>Subtotal</span><span>{{ number_format($completedOrder->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-dashed border-gray-300 my-1"></div>
                    <div class="flex justify-between font-bold text-sm"><span>TOTAL</span><span>Rp
                            {{ number_format($completedOrder->grand_total, 0, ',', '.') }}</span></div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="space-y-0.5 mb-2">
                        @foreach ($completedOrder->payments as $payment)
                            <div class="flex justify-between text-[10px] text-gray-500">
                                <span>Bayar
                                    ({{ $payment->paymentMethod->name ?? 'Cash' }}{{ $payment->paymentMethodRate ? ' - ' . $payment->paymentMethodRate->name : '' }})
                                    :</span>
                                <span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($completedOrder->accurate_invoice_no)
                        <p class="text-[10px] text-gray-400">Inv: {{ $completedOrder->accurate_invoice_no }}</p>
                    @endif
                    <div class="text-center mt-4">
                        <p class="text-[10px] text-gray-400">Terima kasih telah berbelanja!</p>
                        <p class="text-[10px] text-gray-300">www.tokopun.com</p>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100">
                    <button wire:click="newTransaction"
                        class="w-full py-3 rounded-xl font-bold text-white bg-emerald-500 hover:bg-emerald-600 transition shadow-md">
                        Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    @endif
    <div id="scanner-modal"
        class="hidden fixed inset-0 z-50 bg-black/60  items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-4 rounded-lg w-11/12 max-w-md shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-700">Arahkan Kamera ke Barcode</h3>
                <button onclick="closeScanner()" class="text-red-500 hover:text-red-700 font-bold p-1">Tutup</button>
            </div>
            <div id="reader" class="w-full bg-black rounded overflow-hidden"></div>
        </div>
    </div>
    {{-- Print Styles --}}
    <style>
        @media print {
            @page {
                margin: 0;
            }

            body * {
                visibility: hidden;
            }

            #receipt-content,
            #receipt-content * {
                visibility: visible;
            }

            #receipt-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                padding: 4mm;
                font-size: 12px;
            }
        }
    </style>
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
