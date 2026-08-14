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
        </div>
    </div>



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
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
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
                                    </td>
                                    <td class="p-3 text-sm text-center">{{ $item->qty }}</td>
                                    <td class="p-3 text-sm text-right">Rp
                                        {{ number_format($item->price_at_checkout, 0, ',', '.') }}
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
                    {{-- Node 1: Sales Order --}}
                    @php
                        $soDoc = $order->accurateDocs->where('doc_type', 'SALES_ORDER')->first();
                        $doDoc = $order->accurateDocs->where('doc_type', 'DELIVERY_ORDER')->first();
                    @endphp
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-16">
                        <div id="node-so" style="z-index: 10;">
                            <div
                                class="w-48 bg-[#eff6ff] border-2 border-[#1c69d4] rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-all">
                                <div class="text-[10px] font-bold text-[#1c69d4] uppercase tracking-wider mb-1">Sales
                                    Order
                                </div>
                                <div class="text-xs font-bold text-gray-800 mb-1">
                                    {{ $soDoc ? $soDoc->doc_number : $order->accurate_so_number }}</div>
                                <div class="text-[10px] text-gray-500">
                                    {{ $soDoc ? $soDoc->created_at->format('d/m/Y H:i') : ($order->order_date ? $order->order_date->format('d/m/Y') : '-') }}
                                </div>
                            </div>
                        </div>

                        @if ($doDoc)
                            <div id="node-do" style="z-index: 10;">
                                <div
                                    class="w-48 bg-orange-50 border-2 border-orange-500 rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-shadow">
                                    <div class="text-[10px] font-bold text-orange-600 uppercase tracking-wider mb-1">
                                        Delivery Order</div>
                                    <div class="text-[10px] font-semibold text-orange-800 mb-1">
                                        {{ $doDoc->doc_number }}</div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ $doDoc->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
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

                    {{-- Line --}}
                    <div class="w-16 h-0.5 bg-gray-300 relative">
                        <div
                            class="absolute right-0 -top-1.5 w-3 h-3 border-t-2 border-r-2 border-gray-300 transform rotate-45">
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

    <livewire:components.swap-item-modal />
    <livewire:components.cancel-order-modal />
</div>
