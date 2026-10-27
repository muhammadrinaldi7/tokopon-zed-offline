<div class="max-w-7xl mx-auto p-4 md:p-6 min-h-screen">
    <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-start gap-4">
        <div>
            <a href="{{ route('admin.sales-orders.index') }}" wire:navigate
                class="text-sm font-semibold text-gray-500 hover:text-[#1c69d4] flex items-center gap-2 mb-3 transition-colors w-fit bg-white px-3 py-1.5 rounded-lg border border-gray-100 shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar SO
            </a>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Detail & Peta Relasi SO</h1>
            <p class="text-gray-500 text-sm mt-1 font-medium flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                SO Number: <span class="text-gray-700 font-bold">{{ $order->order_number }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if ($order->order_status !== 'COMPLETED' && $order->order_status !== 'cancelled' && $this->getRemainingBalance() > 0)
                <button type="button" wire:click="openDpModal"
                    class="px-5 py-2.5 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-600 transition-all active:scale-95 shadow-sm shadow-emerald-500/20 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Uang Muka (DP)
                </button>
            @endif

            {{-- @if (!$order->accurateDocs()->where('doc_type', 'SALES_INVOICE')->exists())
                
            @endif --}}

            @if ($order->approvalRequests()->where('status', 'PENDING')->where('request_type', 'ORDER_CANCELLATION')->exists())
                <span
                    class="px-4 py-2.5 bg-yellow-50 text-yellow-700 font-bold rounded-xl text-sm border border-yellow-200 flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Menunggu Approval Batal
                </span>
            @elseif (
                $order->order_status !== 'CANCELLED' &&
                    $order->order_status !== 'cancelled' &&
                    $order->order_status !== 'COMPLETED')
                <button type="button" @click="$dispatch('openSwapModal', { orderId: {{ $order->id }} })"
                    class="px-5 py-2.5 bg-white text-indigo-600 border border-indigo-200 hover:bg-indigo-50 hover:border-indigo-300 font-bold rounded-xl text-sm transition-all active:scale-95 shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    Ubah Barang (Swap)
                </button>

                    <button type="button" @click="$dispatch('openCancelModal', { orderId: {{ $order->id }} })"
                    class="px-5 py-2.5 bg-white text-red-600 border border-red-200 hover:bg-red-50 hover:border-red-300 font-bold rounded-xl text-sm transition-all active:scale-95 shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Batalkan SO
                </button>
            @endif

            @if (($order->order_status === 'CANCELLED' || $order->order_status === 'cancelled') && $this->availableDeposits->count() > 0)
                <button type="button" wire:click="openRefundDpModal"
                    class="px-5 py-2.5 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-all active:scale-95 shadow-sm shadow-rose-600/20 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                    </svg>
                    Pencairan Refund DP
                </button>
            @endif
        </div>
    </div>

    {{-- Banner Alert SO Dibatalkan yang memiliki DP --}}
    @if (($order->order_status === 'CANCELLED' || $order->order_status === 'cancelled') && $this->availableDeposits->count() > 0)
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div>
                    <h4 class="font-bold text-amber-900 text-sm">SO Dibatalkan Masih Memiliki Saldo Uang Muka (DP)</h4>
                    <p class="text-xs text-amber-700 mt-0.5">Total saldo DP: <strong>Rp {{ number_format($this->availableDeposits->sum('balance'), 0, ',', '.') }}</strong> saat ini tersimpan sebagai Saldo Deposit Pelanggan (Store Credit). Pelanggan dapat menggunakannya di POS atau Anda dapat memproses pengembalian dana via Transfer/Kas Toko.</p>
                </div>
            </div>
            <button type="button" wire:click="openRefundDpModal" class="px-4 py-2 bg-rose-600 text-white font-bold text-xs rounded-xl hover:bg-rose-700 shrink-0 shadow-sm flex items-center gap-1.5 transition">
                Proses Refund Dana
            </button>
        </div>
    @endif

    {{-- SO Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white lg:col-span-3 rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Informasi Tambahan
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-6">
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Pelanggan</span>
                    <span class="font-semibold text-gray-800">{{ $order->user->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">No. Telpon</span>
                    <span class="font-semibold text-gray-800">{{ $order->user->profile->phone_number ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Cabang</span>
                    <span class="font-semibold text-gray-800">{{ $order->branch->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Sales / Pramuniaga</span>
                    <span class="font-semibold text-gray-800">{{ $order->salesBy->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Alokasi Unit</span>
                    @if (count($this->lockedSerialNumbers) > 0)
                        <span class="inline-flex items-center gap-1 font-bold text-xs text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            {{ count($this->lockedSerialNumbers) }} Unit Terkunci
                        </span>
                    @else
                        <span class="text-xs font-semibold text-gray-500 block mt-0.5">
                            Indent (Non-Keep)
                        </span>
                    @endif
                </div>
                @if ($order->accurate_so_number)
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase">Accurate SO No.</span>
                        <span class="font-bold text-[#1c69d4]">{{ $order->accurate_so_number }}</span>
                    </div>
                @endif
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Catatan</span>
                    <span class="text-gray-600">{{ $order->notes ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-hidden">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Daftar Barang
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[500px]">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                <th class="p-3 font-bold rounded-tl-lg">Produk</th>
                                <th class="p-3 font-bold text-center">Qty</th>
                                <th class="p-3 font-bold text-right">Harga</th>
                                <th class="p-3 font-bold text-right">Diskon</th>
                                <th class="p-3 font-bold text-right rounded-tr-lg">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="p-3 text-sm font-semibold text-gray-800">
                                        @if ($item->variant && get_class($item->variant) === \App\Models\ProductAccurate::class)
                                            {{ $item->variant->name }}
                                            <div class="text-xs font-normal text-gray-500">
                                                {{ $item->variant->item_no ?? '' }}
                                            </div>
                                        @elseif($item->variant)
                                            {{ $item->variant->product->name ?? ($item->variant->secondProduct->name ?? 'Unknown') }}
                                            <div class="text-xs font-normal text-gray-500">
                                                {{ $item->variant->storage ?? '' }}
                                                {{ $item->variant->color ?? '' }}</div>
                                        @else
                                            Unknown Product
                                        @endif

                                        @if (!empty(trim((string)$item->serial_number)))
                                            @php
                                                $itemSns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                                            @endphp
                                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                @foreach ($itemSns as $sn)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-mono font-bold bg-amber-50 text-amber-800 border border-amber-300 px-2 py-0.5 rounded-md shadow-2xs">
                                                        <svg class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                        </svg>
                                                        IMEI: {{ $sn }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-sm text-center">{{ $item->qty }}</td>
                                    <td class="p-3 text-sm text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <span>Rp {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                            @if ($order->order_status !== 'COMPLETED' && $order->order_status !== 'CANCELLED' && $order->order_status !== 'cancelled' && !$order->accurateDocs->where('doc_type', 'SALES_INVOICE')->where('status', 'SUCCESS')->first())
                                                <button type="button" wire:click="openEditPriceModal({{ $item->id }})"
                                                    class="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors"
                                                    title="Ubah Harga Item SO">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>

                                        @php
                                            $masterPrice = null;
                                            if ($item->variant && get_class($item->variant) === \App\Models\ProductAccurate::class) {
                                                $masterPrice = (float) ($item->variant->base_price ?? 0);
                                            }
                                            $isPriceDiff = $masterPrice && $masterPrice > 0 && (int)$masterPrice !== (int)$item->price_at_checkout;
                                        @endphp

                                        @if ($isPriceDiff && $order->order_status !== 'COMPLETED' && $order->order_status !== 'CANCELLED' && $order->order_status !== 'cancelled' && !$order->accurateDocs->where('doc_type', 'SALES_INVOICE')->where('status', 'SUCCESS')->first())
                                            <div class="mt-1 flex justify-end">
                                                <button type="button" wire:click="openEditPriceModal({{ $item->id }})"
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 px-2 py-0.5 rounded-md border border-amber-200 transition"
                                                    title="Harga master Accurate berbeda dengan harga SO">
                                                    <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Master: Rp {{ number_format($masterPrice, 0, ',', '.') }}
                                                    <span class="text-blue-600 underline ml-0.5">Sesuaikan</span>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-sm text-right text-red-500">
                                        {{ $item->discount_amount > 0 ? '-Rp ' . number_format($item->discount_amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="p-3 text-sm text-right font-bold text-gray-800">Rp
                                        {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ringkasan Nilai SO
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-semibold text-gray-800">Rp
                            {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Total Diskon</span>
                        <span class="font-semibold text-red-500">- Rp
                            {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                        <span class="font-bold text-gray-800">Grand Total</span>
                        <span class="font-black text-lg text-[#1c69d4]">Rp
                            {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                    </div>

                    <div class="mt-6 bg-rose-50/50 rounded-2xl p-6 border-2 border-rose-100 shadow-inner">
                        @php $paid = $order->payments->sum('amount'); @endphp
                        <div class="flex flex-col justify-between text-emerald-600 mb-3 ">
                            <span class="font-bold text-sm uppercase tracking-wider">Telah Dibayar (DP)</span>
                            <span class="font-black text-xl">Rp {{ number_format($paid, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex flex-col justify-between border-t-2 border-rose-200/60 pt-4 mt-2">
                            <span class="font-bold text-rose-800 text-sm uppercase tracking-wider mb-1">Sisa Tagihan
                                {{-- <span class="text-[10px] font-normal text-rose-600 normal-case">(Yang harus
                                    dilunasi)</span> --}}
                            </span>
                            <span class="font-black text-2xl text-rose-600 tracking-tighter">Rp
                                {{ number_format($this->getRemainingBalance(), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>


        </div>
        {{-- Relationship Map (SAP B1 Style) --}}
        <div class="bg-white lg:col-span-3 rounded-2xl shadow-sm border border-gray-100 p-8 mb-6 overflow-x-auto"
            id="scrollable-map-wrapper">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 19V6l12-3v13M9 19c-1.657 0-3-1.343-3-3S7.343 13 9 13s3 1.343 3 3-1.343 3-3 3zm12-3c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3zM3 13v-3c0-2.21 1.79-4 4-4h16" />
                </svg>
                Relationship Map (Peta Dokumen)
            </h3>

            <div class="flex flex-wrap items-center justify-start min-w-[800px] gap-16 py-8 relative"
                id="relation-map-container" style="min-height: 400px;">

                @php
                    $hasAccurateDocs = $order->accurateDocs->count() > 0;
                @endphp

                @if ($hasAccurateDocs)
                    {{-- Node 1: Sales Order & Unit Terkunci (Keep IMEI) --}}
                    @php
                        $soDoc = $order->accurateDocs->where('doc_type', 'SALES_ORDER')->first();
                        $doDoc = $order->accurateDocs->where('doc_type', 'DELIVERY_ORDER')->first();
                        $lockedSns = $this->lockedSerialNumbers;
                    @endphp
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        {{-- SO Box --}}
                        <div id="node-so" style="z-index: 10;">
                            <div
                                class="w-48 bg-[#eff6ff] border-2 border-[#1c69d4] rounded-xl p-4 text-center shadow-sm hover:shadow-md transition-all">
                                <div class="text-[10px] font-bold text-[#1c69d4] uppercase tracking-wider mb-1">Sales Order</div>
                                <div class="text-xs font-bold text-gray-800 mb-1">
                                    {{ $soDoc ? $soDoc->doc_number : $order->accurate_so_number }}</div>
                                <div class="text-[10px] text-gray-500">
                                    {{ $soDoc ? $soDoc->created_at->format('d/m/Y H:i') : ($order->order_date ? $order->order_date->format('d/m/Y') : '-') }}
                                </div>
                            </div>
                        </div>

                        {{-- Legacy DO Node (Jika order pernah dibuatkan DO di Accurate) --}}
                        @if ($doDoc)
                            <div class="w-8 h-0.5 bg-gray-300 relative shrink-0 hidden md:block">
                                <div class="absolute right-0 -top-1 w-2.5 h-2.5 border-t-2 border-r-2 border-gray-300 transform rotate-45"></div>
                            </div>
                            <div id="node-do" style="z-index: 10;">
                                <div
                                    class="w-48 bg-orange-50 border-2 border-orange-500 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition-shadow">
                                    <div class="text-[10px] font-bold text-orange-600 uppercase tracking-wider mb-1">
                                        Delivery Order (Legacy)</div>
                                    <div class="text-[10px] font-semibold text-orange-800 mb-1">
                                        {{ $doDoc->doc_number }}</div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ $doDoc->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        @endif

                        {{-- Node Keep IMEI (Unit Terkunci di Tokopon Zed) --}}
                        @if (count($lockedSns) > 0)
                            <div class="w-8 h-0.5 bg-amber-400 relative shrink-0 hidden md:block">
                                <div class="absolute right-0 -top-1 w-2.5 h-2.5 border-t-2 border-r-2 border-amber-400 transform rotate-45"></div>
                            </div>
                            <div id="node-locked-imei" style="z-index: 10;">
                                <div
                                    class="w-60 bg-gradient-to-b from-amber-50 to-amber-50/40 border-2 border-amber-400 rounded-xl p-3.5 text-left shadow-sm hover:shadow-md transition-all">
                                    <div class="flex items-center justify-between gap-1 mb-2">
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-amber-800 bg-amber-100/90 px-2 py-0.5 rounded-md">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Keep IMEI
                                        </span>
                                        <span class="text-[10px] font-bold text-amber-700 bg-white px-2 py-0.5 rounded-full border border-amber-200 shadow-2xs">
                                            {{ count($lockedSns) }} Unit
                                        </span>
                                    </div>

                                    <div class="space-y-1.5 max-h-44 overflow-y-auto pr-1">
                                        @foreach ($lockedSns as $locked)
                                            <div class="bg-white p-2 rounded-lg border border-amber-200 shadow-2xs">
                                                <div class="text-xs font-bold text-gray-800 leading-tight truncate" title="{{ $locked['item_name'] }}">
                                                    {{ $locked['item_name'] }}
                                                </div>
                                                <div class="flex items-center justify-between gap-1 mt-1">
                                                    <span class="text-[11px] font-mono font-bold text-amber-900 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 select-all">
                                                        {{ $locked['sn'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-2.5 pt-2 border-t border-amber-200 flex items-center justify-between text-[11px]">
                                        <span class="text-emerald-700 font-bold flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Kunci Tokopon
                                        </span>
                                        @if ($order->order_status !== 'COMPLETED' && $order->order_status !== 'CANCELLED' && $order->order_status !== 'cancelled')
                                            <button type="button" @click="$dispatch('openSwapModal', { orderId: {{ $order->id }} })"
                                                class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-0.5"
                                                title="Tukar atau ganti unit IMEI">
                                                Swap &rarr;
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif (!$doDoc && $order->order_status !== 'COMPLETED' && $order->order_status !== 'CANCELLED' && $order->order_status !== 'cancelled')
                            <div class="w-8 h-0.5 bg-gray-300 relative shrink-0 hidden md:block">
                                <div class="absolute right-0 -top-1 w-2.5 h-2.5 border-t-2 border-r-2 border-gray-300 transform rotate-45"></div>
                            </div>
                            <div id="node-indent-info" style="z-index: 10;">
                                <div class="w-48 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-4 text-center">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Alokasi Unit</div>
                                    <div class="text-xs font-semibold text-gray-600 mb-0.5">Indent / Non-Keep</div>
                                    <div class="text-[10px] text-gray-400">Unit belum dikunci</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Line penghubung ke DP / Pelunasan --}}
                    <div class="w-10 h-0.5 bg-gray-300 relative shrink-0 hidden md:block">
                        <div class="absolute right-0 -top-1 w-2.5 h-2.5 border-t-2 border-r-2 border-gray-300 transform rotate-45"></div>
                    </div>

                    {{-- DPs --}}
                    @php
                        $dpInvoices = $order->accurateDocs->where('doc_type', 'DP_INVOICE')->values();
                        $dpReceipts = $order->accurateDocs->where('doc_type', 'DP_RECEIPT')->values();
                    @endphp
                    @if ($dpInvoices->count() > 0 || $dpReceipts->count() > 0)
                        <div class="flex flex-col gap-8">
                            @foreach ($dpInvoices as $idx => $dpInv)
                                @php $dpRec = $dpReceipts[$idx] ?? null; @endphp
                                <div class="flex items-center gap-16">
                                    {{-- Node: DP Invoice --}}
                                    <div id="node-dp-inv-{{ $idx }}"
                                        class="node-dp-inv w-48 bg-emerald-50 border-2 border-emerald-500 rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-shadow"
                                        style="z-index: 10;">
                                        <div
                                            class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">
                                            Faktur Uang Muka</div>
                                        <div class="text-[10px] font-semibold text-emerald-800 mb-1">
                                            {{ $dpInv->doc_number }}
                                        </div>
                                        <div class="text-xs font-bold text-gray-800 mb-1">Rp
                                            {{ number_format($dpInv->amount, 0, ',', '.') }}</div>
                                        <div class="text-[10px] text-gray-500">
                                            {{ $dpInv->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>

                                    @if ($dpRec)
                                        {{-- Node: DP Receipt --}}
                                        <div id="node-dp-rec-{{ $idx }}"
                                            data-parent="node-dp-inv-{{ $idx }}"
                                            class="node-dp-rec w-48 bg-teal-50 border-2 border-teal-500 rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-shadow"
                                            style="z-index: 10;">
                                            <div
                                                class="text-[10px] font-bold text-teal-600 uppercase tracking-wider mb-1">
                                                Penerimaan UM</div>
                                            <div class="text-[10px] font-semibold text-teal-800 mb-1">
                                                {{ $dpRec->doc_number }}
                                            </div>
                                            <div class="text-xs font-bold text-gray-800 mb-1">Rp
                                                {{ number_format($dpRec->amount, 0, ',', '.') }}</div>
                                            <div class="text-[10px] text-gray-500">
                                                {{ $dpRec->created_at->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($loop->first)
                                        @php $siDoc = $order->accurateDocs->where('doc_type', 'SALES_INVOICE')->first(); @endphp

                                        {{-- Node 3: Sales Invoice --}}
                                        <div id="node-si" style="z-index: 10;">
                                            @if ($siDoc)
                                                <div
                                                    class="w-48 bg-purple-50 border-2 border-purple-500 rounded-xl p-4 text-center shadow-sm cursor-pointer hover:shadow-md transition-all">
                                                    <div
                                                        class="text-[10px] font-bold text-purple-600 uppercase tracking-wider mb-1">
                                                        Sales Invoice</div>
                                                    <div class="text-[10px] font-semibold text-purple-800 mb-1">
                                                        {{ $siDoc->doc_number }}</div>
                                                    <div class="text-xs font-bold text-gray-800 mb-1">Rp
                                                        {{ number_format($siDoc->amount, 0, ',', '.') }}</div>
                                                    <div class="text-[10px] text-gray-500">
                                                        {{ $siDoc->created_at->format('d/m/Y H:i') }}</div>
                                                </div>
                                            @else
                                                <div
                                                    class="w-48 bg-gray-50 border-2 border-gray-300 border-dashed rounded-xl p-4 text-center">
                                                    <div
                                                        class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                                        Sales Invoice</div>
                                                    <div class="text-xs font-bold text-gray-400 mb-1">Belum Terbit
                                                    </div>
                                                    <div class="text-[10px] text-gray-400">Menunggu Pelunasan</div>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($siDoc)
                                            @php $srDoc = $order->accurateDocs->where('doc_type', 'SALES_RECEIPT')->first(); @endphp
                                            @if ($srDoc)
                                                {{-- Node 4: Sales Receipt Lunas --}}
                                                <div id="node-sr" style="z-index: 10;">
                                                    <div
                                                        class="w-48 bg-blue-50 border-2 border-blue-500 rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-all">
                                                        <div
                                                            class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">
                                                            Sales Receipt (Lunas)</div>
                                                        <div class="text-[10px] font-semibold text-blue-800 mb-1">
                                                            {{ $srDoc->doc_number }}</div>
                                                        <div class="text-xs font-bold text-gray-800 mb-1">Rp
                                                            {{ number_format($srDoc->amount, 0, ',', '.') }}</div>
                                                        <div class="text-[10px] text-gray-500">
                                                            {{ $srDoc->created_at->format('d/m/Y H:i') }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- No DP yet --}}
                        @php $siDoc = $order->accurateDocs->where('doc_type', 'SALES_INVOICE')->first(); @endphp
                        @if ($siDoc)
                            <div id="node-si"
                                class="w-48 bg-purple-50 border-2 border-purple-500 rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-shadow"
                                style="z-index: 10;">
                                <div class="text-[10px] font-bold text-purple-600 uppercase tracking-wider mb-1">Sales
                                    Invoice</div>
                                <div class="text-[10px] font-semibold text-purple-800 mb-1">{{ $siDoc->doc_number }}
                                </div>
                                <div class="text-xs font-bold text-gray-800 mb-1">Rp
                                    {{ number_format($siDoc->amount, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-gray-500">{{ $siDoc->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>

                            @php $srDoc = $order->accurateDocs->where('doc_type', 'SALES_RECEIPT')->first(); @endphp
                            @if ($srDoc)
                                {{-- Node 4: Sales Receipt Lunas --}}
                                <div class="relative group" id="node-sr" style="z-index: 10;">
                                    <div
                                        class="w-48 bg-blue-50 border-2 border-blue-500 rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-shadow">
                                        <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">
                                            Sales
                                            Receipt (Lunas)</div>
                                        <div class="text-[10px] font-semibold text-blue-800 mb-1">
                                            {{ $srDoc->doc_number }}
                                        </div>
                                        <div class="text-xs font-bold text-gray-800 mb-1">Rp
                                            {{ number_format($srDoc->amount, 0, ',', '.') }}</div>
                                        <div class="text-[10px] text-gray-500">
                                            {{ $srDoc->created_at->format('d/m/Y H:i') }}</div>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="relative group">
                                <div
                                    class="w-48 bg-gray-50 border-2 border-gray-300 border-dashed rounded-xl p-4 text-center">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Uang
                                        Muka
                                        (DP)</div>
                                    <div class="text-xs font-bold text-gray-400 mb-1">Belum Ada Pembayaran</div>
                                </div>
                            </div>
                        @endif
                    @endif
                @else
                    {{-- Fallback for Old Orders without accurateDocs --}}
                    @php $lockedSns = $this->lockedSerialNumbers; @endphp
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="relative group">
                            <div
                                class="w-48 bg-[#eff6ff] border-2 border-[#1c69d4] rounded-xl p-4 text-center shadow-sm cursor-pointer hover:shadow-md transition-all">
                                <div class="text-[10px] font-bold text-[#1c69d4] uppercase tracking-wider mb-1">Sales Order
                                </div>
                                <div class="text-xs font-bold text-gray-800 mb-1">{{ $order->order_number }}</div>
                                <div class="text-[10px] text-gray-500">
                                    {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</div>
                            </div>
                        </div>

                        @if (count($lockedSns) > 0)
                            <div class="w-8 h-0.5 bg-amber-400 relative shrink-0 hidden md:block">
                                <div class="absolute right-0 -top-1 w-2.5 h-2.5 border-t-2 border-r-2 border-amber-400 transform rotate-45"></div>
                            </div>
                            <div id="node-locked-imei-fb" style="z-index: 10;">
                                <div
                                    class="w-60 bg-gradient-to-b from-amber-50 to-amber-50/40 border-2 border-amber-400 rounded-xl p-3.5 text-left shadow-sm hover:shadow-md transition-all">
                                    <div class="flex items-center justify-between gap-1 mb-2">
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-amber-800 bg-amber-100/90 px-2 py-0.5 rounded-md">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Keep IMEI
                                        </span>
                                        <span class="text-[10px] font-bold text-amber-700 bg-white px-2 py-0.5 rounded-full border border-amber-200 shadow-2xs">
                                            {{ count($lockedSns) }} Unit
                                        </span>
                                    </div>

                                    <div class="space-y-1.5 max-h-44 overflow-y-auto pr-1">
                                        @foreach ($lockedSns as $locked)
                                            <div class="bg-white p-2.5 rounded-lg border border-amber-200 shadow-2xs">
                                                <div class="text-xs font-bold text-gray-800 leading-tight truncate" title="{{ $locked['item_name'] }}">
                                                    {{ $locked['item_name'] }}
                                                </div>
                                                <div class="flex items-center justify-between gap-1 mt-1.5">
                                                    <span class="text-[11px] font-mono font-bold text-amber-900 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 select-all">
                                                        {{ $locked['sn'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-3 pt-2.5 border-t border-amber-200 flex items-center justify-between text-[11px]">
                                        <span class="text-emerald-700 font-bold flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Kunci Tokopon
                                        </span>
                                        @if ($order->order_status !== 'COMPLETED' && $order->order_status !== 'CANCELLED' && $order->order_status !== 'cancelled')
                                            <button type="button" @click="$dispatch('openSwapModal', { orderId: {{ $order->id }} })"
                                                class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-0.5"
                                                title="Tukar atau ganti unit IMEI">
                                                Swap &rarr;
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Line --}}
                    <div class="w-10 h-0.5 bg-gray-300 relative shrink-0 hidden md:block">
                        <div
                            class="absolute right-0 -top-1 w-2.5 h-2.5 border-t-2 border-r-2 border-gray-300 transform rotate-45">
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        @if ($order->payments->count() > 0)
                            @foreach ($order->payments as $payment)
                                <div class="flex items-center">
                                    <div class="relative group">
                                        <div
                                            class="w-48 bg-emerald-50 border-2 border-emerald-500 rounded-xl p-4 text-center shadow-sm cursor-pointer hover:shadow-md transition-all">
                                            <div
                                                class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">
                                                Pembayaran DP</div>
                                            <div class="text-xs font-bold text-gray-800 mb-1">Rp
                                                {{ number_format($payment->amount, 0, ',', '.') }}</div>
                                            <div class="text-[10px] text-gray-500">
                                                {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>
                                    </div>

                                    @if ($loop->first)
                                        {{-- Line --}}
                                        <div class="w-16 h-0.5 bg-gray-300 relative">
                                            <div
                                                class="absolute right-0 -top-1.5 w-3 h-3 border-t-2 border-r-2 border-gray-300 transform rotate-45">
                                            </div>
                                        </div>

                                        {{-- Node 3: Sales Invoice (Mock) --}}
                                        <div class="relative group">
                                            <div
                                                class="w-48 bg-gray-50 border-2 border-gray-300 border-dashed rounded-xl p-4 text-center">
                                                <div
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                                    Sales Invoice</div>
                                                <div class="text-xs font-bold text-gray-400 mb-1">Belum Terbit</div>
                                                <div class="text-[10px] text-gray-400">Menunggu Pelunasan</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="relative group">
                                <div
                                    class="w-48 bg-gray-50 border-2 border-gray-300 border-dashed rounded-xl p-4 text-center">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Uang
                                        Muka (DP)</div>
                                    <div class="text-xs font-bold text-gray-400 mb-1">Belum Ada Pembayaran</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>



    {{-- DP Modal --}}
    @if ($showDpModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-gray-50 rounded-2xl shadow-xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-5 border-b border-gray-100 bg-white flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Terima Uang Muka (DP)</h3>
                        <p class="text-xs text-gray-500">Total Tagihan: Rp
                            {{ number_format($this->getRemainingBalance(), 0, ',', '.') }}</p>
                    </div>
                    <button wire:click="$set('showDpModal', false)"
                        class="text-gray-400 hover:text-rose-500 font-bold text-2xl leading-none">&times;</button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal
                                Pembayaran *</label>
                            <input type="date" wire:model="dp_date"
                                class="w-full rounded-xl p-3 border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-white"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">No.
                                Kontrak / Referensi</label>
                            <input type="text" wire:model="dp_contract_number"
                                class="w-full rounded-xl p-3 border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-white"
                                placeholder="Opsional">
                        </div>
                    </div>

                    @include('livewire.zoffline.pos.partials.wizard.step4-payment', [
                        'hideSplit' => true,
                        'allowEditAmount' => true,
                        'hideFooter' => true,
                    ])
                </div>
                <div class="p-5 bg-white border-t border-gray-100 shrink-0 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showDpModal', false)"
                        class="px-5 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">Batal</button>
                    <button type="button" wire:click="saveDp"
                        class="px-6 py-2.5 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-600 transition-colors shadow-sm flex items-center gap-2">
                        Simpan Pembayaran DP
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Item Price Modal --}}
    @if ($showEditPriceModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 bg-blue-50 text-[#1c69d4] rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg">Ubah Harga Item SO</h3>
                            <p class="text-xs text-gray-500">Sesuaikan harga transaksi dan sinkronkan ke Accurate</p>
                        </div>
                    </div>
                    <button wire:click="closeEditPriceModal" class="text-gray-400 hover:text-rose-500 font-bold text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Info Item --}}
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Produk</p>
                        <h4 class="font-bold text-gray-800 text-base leading-tight">{{ $editItemName }}</h4>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                            <span>SKU: <b class="font-mono text-gray-700">{{ $editItemSku }}</b></span>
                            <span>Qty: <b class="text-gray-700">{{ $editItemQty }} unit</b></span>
                        </div>
                    </div>

                    {{-- Harga Master Terkini (Jika ada) --}}
                    @if ($editItemMasterPrice && $editItemMasterPrice > 0)
                        <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider block">Harga Master Accurate Terkini</span>
                                <span class="text-base font-black text-[#1c69d4]">Rp {{ number_format($editItemMasterPrice, 0, ',', '.') }}</span>
                            </div>
                            <button type="button" wire:click="useMasterPrice"
                                class="px-3 py-1.5 bg-[#1c69d4] hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                                Gunakan Harga Master
                            </button>
                        </div>
                    @endif

                    {{-- Input Harga Baru --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                            Harga Satuan Baru (Rp) *
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm font-bold text-gray-400">Rp</span>
                            <input type="number" wire:model.live="editItemNewPrice"
                                class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-lg font-bold text-gray-800 focus:ring-2 focus:ring-[#1c69d4] focus:border-transparent outline-none transition"
                                placeholder="0" min="1" step="1000">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Harga saat ini di SO: Rp {{ number_format($editItemCurrentPrice, 0, ',', '.') }}</p>
                    </div>

                    {{-- Ringkasan Dampak --}}
                    @php
                        $cleanInputPrice = (float) preg_replace('/[^0-9]/', '', (string)$editItemNewPrice);
                        $impactDiff = ($cleanInputPrice - (float)$editItemCurrentPrice) * max(1, (int)$editItemQty);
                        $projGrandTotal = max(0, (float)$order->grand_total + $impactDiff);
                        $paidTotal = (float)$order->payments()->where('status', 'PAID')->sum('amount');
                        $projRemaining = max(0, $projGrandTotal - $paidTotal);
                    @endphp
                    <div class="p-4 bg-neutral-50 rounded-xl border border-neutral-200 text-xs space-y-2">
                        <div class="flex justify-between text-neutral-600">
                            <span>Total Nilai SO Baru:</span>
                            <span class="font-bold text-neutral-800">Rp {{ number_format($projGrandTotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-neutral-600">
                            <span>DP yang Sudah Dibayar:</span>
                            <span class="font-bold text-emerald-600">Rp {{ number_format($paidTotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="border-t border-neutral-200 pt-2 flex justify-between font-bold">
                            <span class="text-neutral-700">Sisa Tagihan Baru:</span>
                            <span class="text-base text-rose-600">Rp {{ number_format($projRemaining, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" wire:click="closeEditPriceModal"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="updateItemPrice" wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl transition flex items-center gap-2 shadow-sm shadow-blue-500/20">
                        <span wire:loading.remove wire:target="updateItemPrice">Simpan & Sinkron Accurate</span>
                        <span wire:loading wire:target="updateItemPrice">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Refund DP --}}
    @if ($showRefundDpModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl border border-gray-100 animate-in fade-in zoom-in-95 duration-150">
                <div class="p-6 bg-gradient-to-r from-rose-600 via-rose-700 to-rose-800 text-white flex justify-between items-center shadow-md">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-[11px] font-bold tracking-wide uppercase mb-1 backdrop-blur-xs">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                            </svg>
                            Pengembalian Dana Kas / Bank
                        </div>
                        <h3 class="text-lg font-black tracking-tight">Pencairan Refund Uang Muka (DP)</h3>
                        <p class="text-xs text-rose-100 mt-0.5">SO Dibatalkan: <span class="font-mono font-bold text-white">{{ $order->order_number }}</span></p>
                    </div>
                    <button type="button" wire:click="closeRefundDpModal" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    {{-- Info Saldo DP --}}
                    <div class="p-4 bg-gradient-to-br from-rose-50 to-pink-50/50 rounded-2xl border border-rose-100 text-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider block">Saldo DP Tersedia</span>
                            <span class="text-2xl font-black text-rose-800 tracking-tight">Rp {{ number_format($refundAmount, 0, ',', '.') }}</span>
                            <div class="text-xs text-rose-600/80 mt-0.5">
                                Pelanggan: <strong class="text-rose-900 font-bold">{{ $order->user->name ?? 'Pelanggan Umum' }}</strong>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-100/80 border border-rose-200/60 flex items-center justify-center text-rose-600 shadow-inner">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Form Nominal --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Nominal Refund Dicairkan (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] text-gray-400">Maks. Rp {{ number_format($refundAmount, 0, ',', '.') }}</span>
                        </div>
                        <div class="relative">
                            <span class="absolute left-4 top-2.5 text-sm font-bold text-gray-400">Rp</span>
                            <input type="number" wire:model="refundAmount" class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-base font-black text-gray-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition" min="1" max="{{ $refundAmount }}">
                        </div>
                        @error('refundAmount') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                    </div>

                    {{-- AKUN KAS / BANK PENGELUARAN TOKO (Interactive Cards with Bank Identity) --}}
                    <div x-data="{ search: '' }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Akun Kas / Bank Pengeluaran Toko <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] font-semibold text-gray-400">Pilih sumber dana refund</span>
                        </div>

                        {{-- Search Input Filter --}}
                        <div class="relative mb-2.5">
                            <input type="text"
                                   x-model="search"
                                   placeholder="Cari nama bank, no rekening, atau kas..."
                                   class="w-full pl-9 pr-8 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none transition">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <button type="button" x-show="search" @click="search = ''" class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>

                        {{-- List Akun Kas / Bank (Sumber data: accurate_gl_accounts) --}}
                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            @php
                                $hasKasCashInGl = $accurateGlAccounts->contains(function ($item) {
                                    return $item->account_no === 'KAS-CASH' || str_contains(strtolower($item->name), 'kasir') || str_contains(strtolower($item->name), 'laci');
                                });
                            @endphp

                            {{-- Option 1: Kas Tunai Kasir Standalone (jika belum ada akun laci kasir spesifik di GL) --}}
                            @if (!$hasKasCashInGl)
                                @php
                                    $isCashSelected = ($refundBankNo === 'KAS-CASH');
                                @endphp
                                <div wire:click="selectRefundBank('KAS-CASH')"
                                     x-show="!search || 'kas tunai cash kasir laci kascash outlet'.includes(search.toLowerCase())"
                                     class="group cursor-pointer p-3 rounded-2xl border-2 transition-all flex items-center justify-between gap-3 {{ $isCashSelected ? 'border-rose-500 bg-rose-50/70 shadow-sm ring-1 ring-rose-500/30' : 'border-gray-200 hover:border-rose-300 hover:bg-gray-50/80 bg-white' }}">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                                            <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 tracking-wider">
                                                    KAS TUNAI
                                                </span>
                                                <span class="font-bold text-sm text-gray-900">Kas Tunai Kasir</span>
                                                <span class="text-[10px] font-mono px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200">
                                                    KAS-CASH
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5 truncate">Pengeluaran uang tunai langsung dari laci kasir outlet</p>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        @if ($isCashSelected)
                                            <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center shadow-sm">
                                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 group-hover:border-rose-400 transition-colors"></div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Option 2+: Akun GL Kas & Bank dari accurate_gl_accounts --}}
                            @forelse ($accurateGlAccounts as $gl)
                                @php
                                    $accNo = $gl->account_no;
                                    $accName = $gl->name;
                                    $isSelected = ($refundBankNo === $accNo);

                                    $nameLower = strtolower($accName . ' ' . $accNo);
                                    $isCash = str_contains($nameLower, 'kas') || str_contains($nameLower, 'cash') || $accNo === 'KAS-CASH';

                                    // Deteksi nama Bank dari nama GL account
                                    $detectedBank = null;
                                    $bankKeywords = ['bca', 'mandiri', 'bri', 'bni', 'bsi', 'qris', 'cimb', 'permata', 'danamon', 'panin'];
                                    foreach ($bankKeywords as $kw) {
                                        if (str_contains($nameLower, $kw)) {
                                            $detectedBank = strtoupper($kw);
                                            break;
                                        }
                                    }

                                    $logoFile = $detectedBank ? strtolower($detectedBank) . '.png' : null;
                                    $hasLogo = $logoFile && file_exists(public_path('assets/png/paymentmethod/' . $logoFile));

                                    $searchString = strtolower($accNo . ' ' . $accName . ' ' . ($detectedBank ?? '') . ' ' . ($isCash ? 'kas tunai cash' : 'bank'));
                                @endphp
                                <div wire:click="selectRefundBank('{{ addslashes($accNo) }}')"
                                     x-show="!search || '{{ addslashes($searchString) }}'.includes(search.toLowerCase())"
                                     class="group cursor-pointer p-3 rounded-2xl border-2 transition-all flex items-center justify-between gap-3 {{ $isSelected ? 'border-rose-500 bg-rose-50/70 shadow-sm ring-1 ring-rose-500/30' : 'border-gray-200 hover:border-rose-300 hover:bg-gray-50/80 bg-white' }}">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-12 h-12 rounded-xl {{ $isCash ? 'bg-emerald-50 border border-emerald-200 text-emerald-600' : 'bg-white border border-gray-200' }} flex items-center justify-center shrink-0 p-1.5 shadow-xs group-hover:scale-105 transition-transform">
                                            @if ($isCash)
                                                <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            @elseif ($hasLogo)
                                                <img src="{{ asset('assets/png/paymentmethod/' . $logoFile) }}"
                                                     alt="{{ $detectedBank ?? $accName }}"
                                                     class="max-h-7 max-w-full object-contain">
                                            @elseif ($detectedBank)
                                                <span class="font-black text-xs text-blue-700 tracking-wider">
                                                    {{ $detectedBank }}
                                                </span>
                                            @else
                                                <svg class="w-6 h-6 text-gray-400 group-hover:text-rose-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                @if ($isCash)
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 tracking-wider">
                                                        KAS TUNAI
                                                    </span>
                                                @elseif ($detectedBank)
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-blue-50 text-blue-700 border border-blue-200/60 tracking-wider">
                                                        {{ $detectedBank }}
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-gray-100 text-gray-700 tracking-wider">
                                                        KAS / BANK
                                                    </span>
                                                @endif

                                                <span class="font-bold text-sm text-gray-900 truncate">{{ $accName }}</span>

                                                <span class="text-[10px] font-mono px-1.5 py-0.5 bg-gray-100 text-gray-700 rounded border border-gray-200 font-semibold" title="Accurate Account No">
                                                    COA: {{ $accNo }}
                                                </span>

                                                @if ($gl->database_source)
                                                    <span class="text-[9px] uppercase px-1.5 py-0.5 bg-gray-50 text-gray-400 rounded border border-gray-200 font-mono">
                                                        {{ $gl->database_source }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-2 flex-wrap">
                                                <span class="text-gray-400 text-[11px]">Akun GL Accurate Kas & Bank • Nomor COA: <strong class="font-mono text-gray-700 font-semibold">{{ $accNo }}</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        @if ($isSelected)
                                            <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center shadow-sm">
                                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 group-hover:border-rose-400 transition-colors"></div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-xs text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                    Belum ada data akun kas/bank di accurate_gl_accounts. Silakan sinkronkan akun GL dari menu Pengaturan Metode Pembayaran.
                                </div>
                            @endforelse
                        </div>

                        {{-- Active Account Info Banner --}}
                        <div class="mt-2.5">
                            @if ($refundBankNo)
                                @php
                                    $selectedAcc = $accurateGlAccounts->firstWhere('account_no', $refundBankNo);
                                    $selectedName = $selectedAcc ? $selectedAcc->name : ($refundBankNo === 'KAS-CASH' ? 'Kas Tunai Kasir Outlet' : $refundBankNo);
                                @endphp
                                <div class="px-3 py-2 bg-emerald-50 border border-emerald-200/80 rounded-xl flex items-center justify-between text-xs text-emerald-800 shadow-xs">
                                    <div class="flex items-center gap-2 truncate">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="truncate">Akun Terpilih: <strong class="text-gray-900 font-bold font-mono">{{ $refundBankNo }}</strong> ({{ $selectedName }})</span>
                                    </div>
                                    <button type="button" wire:click="selectRefundBank('')" class="text-rose-600 hover:text-rose-800 text-[11px] font-bold hover:underline shrink-0 ml-2">
                                        Batal Pilih
                                    </button>
                                </div>
                            @else
                                <div class="px-3 py-2 bg-rose-50/60 border border-rose-200/60 rounded-xl flex items-center gap-2 text-xs text-rose-600">
                                    <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>Klik salah satu akun kas/bank toko di atas untuk memilih sumber dana refund.</span>
                                </div>
                            @endif
                        </div>
                        @error('refundBankNo') <span class="text-xs text-rose-500 mt-1.5 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Data Rekening Tujuan Customer --}}
                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-3">
                        <div class="text-xs font-bold text-gray-700 flex items-center gap-1.5 uppercase tracking-wider">
                            <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Rekening Tujuan Pengembalian (Customer)
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Bank Customer</label>
                                <input type="text" wire:model="refundCustomerBankName" placeholder="Contoh: BCA / Mandiri / Tunai" class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">No. Rekening / No. HP</label>
                                <input type="text" wire:model="refundCustomerAccountNo" placeholder="Nomor rekening customer" class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none transition">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nama Pemilik Rekening</label>
                                <input type="text" wire:model="refundCustomerAccountName" placeholder="Nama lengkap pemilik rekening" class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none transition">
                            </div>
                        </div>
                    </div>

                    {{-- Bukti Transfer --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Unggah Bukti Transfer / Kwitansi <span class="text-rose-500">*</span></label>
                        <input type="file" wire:model="refundReceipt" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 transition cursor-pointer">
                        @error('refundReceipt') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                        @if ($refundReceipt)
                            <div class="mt-2 text-xs text-emerald-600 font-semibold flex items-center gap-1.5 p-2 bg-emerald-50 rounded-xl border border-emerald-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                File terpilih: <span class="font-bold">{{ $refundReceipt->getClientOriginalName() }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                        <textarea wire:model="refundNotes" rows="2" placeholder="Catatan atau keterangan refund..." class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none transition"></textarea>
                    </div>
                </div>

                <div class="p-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" wire:click="closeRefundDpModal" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition text-sm">
                        Batal
                    </button>
                    <button type="button" wire:click="processRefundDp" wire:loading.attr="disabled" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition flex items-center gap-2 shadow-sm shadow-rose-600/20 text-sm">
                        <span wire:loading.remove wire:target="processRefundDp">Konfirmasi & Cairkan Refund</span>
                        <span wire:loading wire:target="processRefundDp" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <livewire:components.swap-item-modal />
    <livewire:components.cancel-order-modal />
</div>
