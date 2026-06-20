<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Kelola Pesanan</h1>
            <p class="text-gray-500 text-sm mt-1">Pantau dan kelola seluruh transaksi pelanggan.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.orders.import-draft') }}" wire:navigate
                class="px-4 py-2 bg-[#1c69d4] text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import via Draft
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 flex flex-col md:flex-row gap-4 mb-6">
        <div class="flex-1 relative">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="Cari No. Pesanan atau Nama Pembeli..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
        </div>
        <div class="w-full md:w-64 shrink-0">
            <select wire:model.live="statusFilter"
                class="w-full px-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
                <option value="">Semua Status</option>
                <option value="PENDING">Menunggu Bayar</option>
                <option value="PROCESSING">Diproses</option>
                <option value="SHIPPED">Dikirim</option>
                <option value="COMPLETED">Selesai</option>
                <option value="CANCELLED">Dibatalkan</option>
            </select>
        </div>
        <div class="w-full md:w-64 shrink-0">
            <select wire:model.live="warehouseFilter"
                class="w-full px-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
                <option value="">Semua Warehouse</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">No. Pesanan</th>
                        <th class="px-6 py-4 font-bold">Pembeli & Waktu</th>
                        <th class="px-6 py-4 font-bold">Items & Total</th>
                        <th class="px-6 py-4 font-bold">Status</th>
                        <th class="px-6 py-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 align-top">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-900text-sm">{{ $order->order_number }}</span>
                                <div class="text-[10px] text-gray-400 font-mono mt-1 select-all"
                                    title="Klik untuk menyalin (segera hadir)">
                                    ID: {{ $order->id }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800 text-sm">{{ $order->user->name ?? 'User Terhapus' }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">{{ $order->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-800">{{ $order->items->count() }} Item</p>
                                <p class="text-sm font-black text-[#1c69d4] mt-1">Rp
                                    {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'PENDING' => 'bg-amber-50 text-amber-600 border-amber-100',
                                        'PROCESSING' => 'bg-blue-50 text-blue-600 border-blue-100',
                                        'SHIPPED' => 'bg-purple-50 text-purple-600 border-purple-100',
                                        'COMPLETED' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'CANCELLED' => 'bg-rose-50 text-rose-600 border-rose-100',
                                    ];
                                @endphp
                                <span
                                    class="text-xs font-bold px-3 py-1 rounded-lg border {{ $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $order->order_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Quick Actions for Order Progress --}}
                                    @if ($order->order_status === 'PENDING')
                                        <button wire:click="updateOrderStatus({{ $order->id }}, 'PROCESSING')"
                                            wire:confirm="Proses pesanan ini?"
                                            class="text-xs font-bold bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition">
                                            Proses
                                        </button>
                                        <button wire:click="updateOrderStatus({{ $order->id }}, 'CANCELLED')"
                                            wire:confirm="Batalkan pesanan ini?"
                                            class="text-xs font-bold bg-rose-50 text-rose-600 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition">
                                            Batal
                                        </button>
                                    @elseif ($order->order_status === 'PROCESSING')
                                        <button wire:click="updateOrderStatus({{ $order->id }}, 'SHIPPED')"
                                            wire:confirm="Tandai pesanan telah dikirim?"
                                            class="text-xs font-bold bg-purple-50 text-purple-600 hover:bg-purple-100 px-3 py-1.5 rounded-lg transition">
                                            Kirim
                                        </button>
                                    @endif

                                    {{-- ─── TOMBOL RE-SEND KHUSUS ADMIN ─── --}}
                                    @if (Auth::user()->hasRole('admin'))
                                        <button wire:click="resendEmail({{ $order->id }})"
                                            class="p-1 text-blue-500 hover:bg-blue-50 rounded-lg transition"
                                            title="Kirim Ulang Email (Admin)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </button>

                                        <button wire:click="resendWhatsApp({{ $order->id }})"
                                            class="p-1 text-emerald-500 hover:bg-emerald-50 rounded-lg transition"
                                            title="Kirim Ulang WA Qontak (Admin)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Order Detail Button (Struk) --}}
                                    <button wire:click="viewReceipt({{ $order->id }})"
                                        class="p-1.5 text-gray-400 hover:text-[#1c69d4] hover:bg-blue-50 rounded-lg transition"
                                        title="Lihat Struk">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-gray-500 font-medium">Belum ada pesanan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL: Receipt (Struk) Khusus View Admin --}}
    @if ($showReceiptModal && $selectedOrderForReceipt)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-black text-gray-900">Struk Transaksi</h3>
                    <div class="flex items-center gap-6">
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
                <div id="receipt-content" class="p-5 font-mono text-xs leading-relaxed overflow-y-auto max-h-[70vh]">
                    <div class="text-center mb-3">
                        <p class="font-bold text-sm">SYIHAB STORE</p>
                        <p class="text-[10px] text-gray-500">
                            {{ $selectedOrderForReceipt->shipping_address_snapshot['store'] ?? 'Toko' }}</p>
                        <p class="text-[10px] text-gray-400">
                            {{ $selectedOrderForReceipt->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <p class="text-[10px] text-gray-500">Tanggal:
                        {{ $selectedOrderForReceipt->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-[10px] text-gray-500">No: {{ $selectedOrderForReceipt->order_number }}</p>
                    <p class="text-[10px] text-gray-500">Kasir: {{ $selectedOrderForReceipt->handledBy->name ?? '-' }}
                    </p>
                    <p class="text-[10px] text-gray-500">Sales: {{ $selectedOrderForReceipt->salesBy->name ?? '-' }}
                    </p>
                    <p class="text-[10px] text-gray-500">Customer: {{ $selectedOrderForReceipt->user->name ?? '-' }}
                    </p>
                    <p class="text-[10px] text-gray-500">Customer No:
                        {{ $selectedOrderForReceipt->user->profile->phone_number ?? '-' }}
                    </p>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>

                    @foreach ($selectedOrderForReceipt->items as $item)
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
                            // Bersihkan awalan nama
                            $itemName = preg_replace('/^(?:DS\s*-\s*HP\s*|DS\s*-\s*|HP\s*-\s*|HP\s*)/i', '', trim($itemName));
                        @endphp
                        <div class="mb-1">
                            <p class="font-bold">{{ $itemName }}
                                @if ($ram != null)
                                    {{ $ram }}/
                                @endif{{ $storage }}
                                {{ $color }}
                            </p>
                            <div class="flex justify-between">
                                <span>{{ $item->qty }}x
                                    {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if ($item->serial_number)
                                <p class="text-[9px] text-gray-400">SN: {{ $item->serial_number }}</p>
                            @endif
                        </div>
                    @endforeach
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="flex justify-between">
                        <span>Subtotal</span><span>{{ number_format($selectedOrderForReceipt->total_amount, 0, ',', '.') }}</span>
                    </div>
                    @if ($selectedOrderForReceipt->discount_amount > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Diskon</span><span>-{{ number_format($selectedOrderForReceipt->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="border-t border-dashed border-gray-300 my-1"></div>
                    <div class="flex justify-between font-bold text-sm"><span>TOTAL</span><span>Rp
                            {{ number_format($selectedOrderForReceipt->grand_total, 0, ',', '.') }}</span></div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="space-y-0.5 mb-2">
                        @foreach ($selectedOrderForReceipt->payments as $payment)
                            <div class="flex justify-between text-[10px] text-gray-500">
                                <span>Bayar
                                    ({{ $payment->paymentMethod->name ?? 'Cash' }}{{ $payment->paymentMethodRate ? ' - ' . $payment->paymentMethodRate->name : '' }})
                                    :</span>
                                <span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($selectedOrderForReceipt->accurate_invoice_no)
                        <p class="text-[10px] text-gray-400">Inv: {{ $selectedOrderForReceipt->accurate_invoice_no }}
                        </p>
                    @endif
                    <div class="text-start mt-2">
                        <p class="text-[10px] text-gray-400">Catatan : {{ $selectedOrderForReceipt->notes ?? '' }}</p>
                    </div>
                    <div class="text-center mt-4">
                        <p class="text-[10px] text-gray-400">Terima kasih telah berbelanja!</p>
                        <p class="text-[10px] text-gray-300">Call Center : 0811-5600-6464</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

