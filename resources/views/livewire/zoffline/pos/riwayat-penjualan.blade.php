<div class="p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-neutral-800">Riwayat Penjualan</h1>
                <p class="text-sm text-neutral-500 mt-1">Daftar transaksi kasir di cabang ini.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
            <div
                class="p-4 border-b border-neutral-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-neutral-50/50">
                <div class="relative max-w-md w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-neutral-200 rounded-xl leading-5 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out shadow-sm"
                        placeholder="Cari nomor struk atau pelanggan...">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Tanggal & Struk</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Pelanggan</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Total & Pembayaran</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Kasir/Sales</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @forelse($orders as $order)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm  font-bold text-neutral-900 ">{{ $order->order_number }}</div>
                                    <div class="text-xs text-neutral-500 mt-1">
                                        {{ $order->created_at->format('d M Y, H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900">
                                        {{ $order->user->name ?? 'Guest' }}
                                    </div>
                                    @if ($order->user && $order->user->identity)
                                        <div class="text-xs text-neutral-500 mt-1">{{ $order->user->identity }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-emerald-600">Rp
                                        {{ number_format($order->total_amount, 0, ',', '.') }}</div>
                                    <div class="text-xs text-neutral-500 mt-1">
                                        {{ $order->paymentMethod->name ?? 'Tunai' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900">
                                        {{ $order->handledBy->name ?? '-' }}
                                    </div>
                                    @if ($order->salesBy)
                                        <div
                                            class="text-[11px] text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded inline-block mt-1">
                                            Sales: {{ $order->salesBy->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($order->order_status === 'DELETED')
                                        <span
                                            class="px-2 py-0.5 bg-red-50 text-red-700 text-[10px] font-bold rounded border border-red-200 uppercase">
                                            🗑️ Dosa
                                        </span>
                                    @elseif (!empty($order->accurate_invoice_no) || !empty($order->accurate_receipt_no))
                                        <div class="inline-flex flex-col gap-1 items-start">
                                            <span
                                                class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded border border-emerald-200 uppercase">
                                                ✓ Tersinkron
                                            </span>
                                            @if ($order->accurate_invoice_no)
                                                <span class="text-[10px] text-neutral-400 font-mono"
                                                    title="Invoice No">Inv: {{ $order->accurate_invoice_no }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span
                                            class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold rounded border border-amber-200 uppercase">
                                            ⏳ Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    @if ($order->order_status != 'DELETED')
                                        <button wire:click="reprintOrder({{ $order->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg text-xs font-bold transition-all border border-emerald-100">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Struk
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div
                                        class="mx-auto w-16 h-16 bg-neutral-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-neutral-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-neutral-900">Belum ada riwayat penjualan</h3>
                                    <p class="mt-1 text-sm text-neutral-500">Transaksi baru akan muncul di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="p-4 border-t border-neutral-100 bg-neutral-50/50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('livewire.zoffline.pos.modal.riwayat-receipt')
</div>
