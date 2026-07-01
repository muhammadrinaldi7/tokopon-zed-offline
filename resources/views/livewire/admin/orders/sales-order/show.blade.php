<div class="max-w-7xl mx-auto p-4 md:p-6 min-h-screen">
    <div class="mb-6 flex justify-between items-start">
        <div>
            <a href="{{ route('admin.sales-orders.index') }}" wire:navigate
                class="text-sm font-medium text-gray-500 hover:text-[#1c69d4] flex items-center gap-1 mb-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar SO
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Detail & Peta Relasi SO</h1>
            <p class="text-gray-500 text-sm mt-1">SO Number: {{ $order->order_number }}</p>
        </div>

        <div class="flex items-center gap-3">
            @if (
                $order->order_status !== 'COMPLETED' &&
                $order->order_status !== 'cancelled' &&
                $this->getRemainingBalance() > 0)
                <button type="button" wire:click="openDpModal"
                    class="px-5 py-2.5 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-600 transition-colors shadow-sm shadow-emerald-500/20 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Uang Muka (DP)
                </button>
            @endif

            @if (!$order->accurateDocs()->where('doc_type', 'SALES_INVOICE')->exists())
                <button type="button" wire:click="openInvoiceModal"
                    class="px-5 py-2.5 bg-[#1c69d4] text-white hover:bg-blue-700 font-bold rounded-xl text-sm transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    Terbitkan Faktur
                </button>
            @endif
        </div>
    </div>



    {{-- SO Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Daftar Barang</h3>
                <table class="w-full text-left border-collapse">
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

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Ringkasan Nilai SO</h3>
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

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Informasi Tambahan</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase">Pelanggan</span>
                        <span class="font-semibold text-gray-800">{{ $order->user->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase">Unit Usaha</span>
                        <span class="font-semibold text-gray-800">{{ $order->businessUnit->name ?? '-' }}</span>
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
        </div>
        {{-- Relationship Map (SAP B1 Style) --}}
        <div class="bg-white col-span-3 rounded-2xl shadow-sm border border-gray-100 p-8 mb-6 overflow-x-auto"
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
                                <div class="text-[10px] font-bold text-[#1c69d4] uppercase tracking-wider mb-1">Sales Order
                                </div>
                                <div class="text-xs font-bold text-gray-800 mb-1">
                                    {{ $soDoc ? $soDoc->doc_number : $order->accurate_so_number }}</div>
                                <div class="text-[10px] text-gray-500">
                                    {{ $soDoc ? $soDoc->created_at->format('d/m/Y H:i') : ($order->order_date ? $order->order_date->format('d/m/Y') : '-') }}
                                </div>
                            </div>
                        </div>

                        @if($doDoc)
                        <div id="node-do" style="z-index: 10;">
                            <div
                                class="w-48 bg-orange-50 border-2 border-orange-500 rounded-xl p-4 text-center shadow-sm cursor-move hover:shadow-md transition-shadow">
                                <div class="text-[10px] font-bold text-orange-600 uppercase tracking-wider mb-1">Delivery Order</div>
                                <div class="text-[10px] font-semibold text-orange-800 mb-1">{{ $doDoc->doc_number }}</div>
                                <div class="text-[10px] text-gray-500">{{ $doDoc->created_at->format('d/m/Y H:i') }}</div>
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
                        <p class="text-xs text-gray-500">Total Tagihan: Rp {{ number_format($this->getRemainingBalance(), 0, ',', '.') }}</p>
                    </div>
                    <button wire:click="$set('showDpModal', false)"
                        class="text-gray-400 hover:text-rose-500 font-bold text-2xl leading-none">&times;</button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal Pembayaran *</label>
                            <input type="date" wire:model="dp_date" class="w-full rounded-xl p-3 border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-white" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">No. Kontrak / Referensi</label>
                            <input type="text" wire:model="dp_contract_number" class="w-full rounded-xl p-3 border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-white" placeholder="Opsional">
                        </div>
                    </div>
                    
                    @include('livewire.zoffline.pos.partials.wizard.step4-payment', ['hideSplit' => true, 'allowEditAmount' => true, 'hideFooter' => true])
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

    {{-- Invoice / Fulfillment Modal --}}
    @if ($showInvoiceModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 sm:p-6"
            style="margin: 0 !important;">
            <div class="bg-gray-50 rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh]">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-gray-900 font-bold text-xl leading-tight">Terbitkan Faktur</h2>
                            <p class="text-xs font-medium text-gray-500">Input Serial Number dan Pelunasan Akhir (Sisa Tagihan: Rp {{ number_format($this->getRemainingBalance(), 0, ',', '.') }})</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showInvoiceModal', false)"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    <div
                        class="bg-blue-50/50 border border-blue-100 text-blue-800 text-sm p-4 rounded-xl flex gap-3 items-start">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            Faktur Penjualan (Sales Invoice) akan dibuat di Accurate untuk memotong stok. Pastikan Anda memasukkan <strong>Serial Number</strong> (dipisah koma) sesuai jumlah kuantitas jika barang tersebut merupakan perangkat ber-SN.
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach ($order->items as $item)
                            @php
                                $variant = $item->variant;
                                if ($variant && get_class($variant) === \App\Models\ProductAccurate::class) {
                                    $itemName = $variant->name;
                                    $sku = $variant->item_no ?? '';
                                    $subDesc = '';
                                } else {
                                    $isNew = $item->product_variant_type === \App\Models\ProductVariant::class;
                                    $itemName = $isNew
                                        ? $variant->product->name ?? 'Unknown'
                                        : $variant->secondProduct->name ?? 'Unknown';
                                    $sku = $variant->sku ?? '';
                                    $subDesc = trim(($variant->storage ?? '') . ' ' . ($variant->color ?? ''));
                                }
                            @endphp
                            <div class="p-4 border {{ empty($sku) ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white' }} rounded-xl flex flex-col gap-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold text-gray-800">{{ $itemName }}</div>
                                        <div class="text-xs text-gray-500">{{ $subDesc }} | Qty:
                                            {{ $item->qty }}</div>
                                        @if (empty($sku))
                                            <div class="text-xs font-bold text-red-500 mt-1">Error: Produk ini
                                                belum ada SKU (Item No) dari Accurate.</div>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-gray-800">Rp
                                            {{ number_format($item->price_at_checkout, 0, ',', '.') }}</div>
                                        @if ($item->discount_amount > 0)
                                            <div class="text-[10px] text-emerald-600 font-bold">Disc: Rp
                                                {{ number_format($item->discount_amount, 0, ',', '.') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label
                                        class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Input
                                        Serial Number (Pisah dengan koma jika > 1)</label>
                                    @if ($item->qty > 1)
                                        <div class="space-y-2">
                                            @for ($i = 0; $i < $item->qty; $i++)
                                                <input type="text"
                                                    wire:model.defer="invoice_sns.{{ $item->id }}.{{ $i }}"
                                                    class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors"
                                                    placeholder="SN untuk item ke-{{ $i + 1 }}">
                                            @endfor
                                        </div>
                                    @else
                                        <input type="text"
                                            wire:model.defer="invoice_sns.{{ $item->id }}.0"
                                            class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors"
                                            placeholder="Masukkan IMEI/SN">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($this->getRemainingBalance() > 0)
                        <div class="mt-8 border-t border-gray-200 pt-6">
                            <h4 class="font-black text-gray-800 mb-4 text-lg">Pelunasan Sisa Tagihan</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal Pembayaran *</label>
                                    <input type="date" wire:model="invoice_date" class="w-full rounded-xl p-3 border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">No. Kontrak / Referensi</label>
                                    <input type="text" wire:model="invoice_contract_number" class="w-full rounded-xl p-3 border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-white" placeholder="Opsional">
                                </div>
                            </div>
                            @include('livewire.zoffline.pos.partials.wizard.step4-payment', ['hideSplit' => false, 'hideFooter' => true])
                        </div>
                    @endif
                </div>

                <div class="p-5 bg-white border-t border-gray-100 flex justify-end shrink-0 gap-3">
                    <button type="button" wire:click="$set('showInvoiceModal', false)"
                        class="px-5 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">Batal</button>
                    <button type="button" wire:click="submitFaktur"
                        wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-[#1c69d4] text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-all flex items-center gap-2">
                        <span wire:loading.remove wire:target="submitFaktur">Terbitkan Faktur</span>
                        <span wire:loading wire:target="submitFaktur">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/leader-line-new@1.1.9/leader-line.min.js" data-navigate-track></script>
    <script data-navigate-track>
        function loadMapScripts(callback) {
            if (typeof LeaderLine !== 'undefined') {
                callback();
                return;
            }
            setTimeout(() => loadMapScripts(callback), 100);
        }

        document.addEventListener('livewire:initialized', () => {
            let lines = [];

            function updateLines() {
                lines.forEach(l => {
                    try {
                        l.position();
                    } catch (e) {}
                });
            }

            function makeDraggable(el) {
                if (!el) return;
                let isDragging = false;
                let initialX, initialY;
                let xOffset = 0,
                    yOffset = 0;

                // Keep track of any existing transforms
                const style = window.getComputedStyle(el);
                const matrix = new DOMMatrixReadOnly(style.transform === 'none' ? 'matrix(1, 0, 0, 1, 0, 0)' : style
                    .transform);
                xOffset = matrix.m41;
                yOffset = matrix.m42;

                el.style.cursor = 'move';
                el.style.userSelect = 'none';

                el.addEventListener('mousedown', function(e) {
                    if (e.target.tagName.toLowerCase() === 'button') return;
                    initialX = e.clientX - xOffset;
                    initialY = e.clientY - yOffset;
                    isDragging = true;
                    el.style.zIndex = '1000';

                    document.addEventListener('mousemove', drag);
                    document.addEventListener('mouseup', dragEnd);
                });

                function drag(e) {
                    if (!isDragging) return;
                    e.preventDefault();
                    xOffset = e.clientX - initialX;
                    yOffset = e.clientY - initialY;
                    el.style.transform = `translate(${xOffset}px, ${yOffset}px)`;
                    updateLines();
                }

                function dragEnd(e) {
                    isDragging = false;
                    el.style.zIndex = '10';
                    document.removeEventListener('mousemove', drag);
                    document.removeEventListener('mouseup', dragEnd);
                }
            }

            function initMap() {
                if (typeof LeaderLine === 'undefined') return;

                // clear old instances
                try {
                    lines.forEach(l => l.remove());
                } catch (e) {}
                lines = [];

                // Define colors
                const colorDPInv = '#10b981'; // emerald-500
                const colorDPRec = '#14b8a6'; // teal-500
                const colorSI = '#a855f7'; // purple-500
                const colorSR = '#3b82f6'; // blue-500
                const colorDO = '#f97316'; // orange-500

                const nodeSO = document.getElementById('node-so');
                if (nodeSO) makeDraggable(nodeSO);

                const nodeDO = document.getElementById('node-do');
                if (nodeDO) {
                    makeDraggable(nodeDO);
                    if (nodeSO) {
                        lines.push(new LeaderLine(nodeSO, nodeDO, {
                            color: colorDO,
                            size: 3,
                            path: 'fluid',
                            endPlug: 'arrow3',
                            dropShadow: true
                        }));
                    }
                }

                // Loop DPs
                document.querySelectorAll('.node-dp-inv').forEach(el => {
                    makeDraggable(el);
                    if (nodeSO) {
                        lines.push(new LeaderLine(nodeSO, el, {
                            color: colorDPInv,
                            size: 3,
                            path: 'fluid',
                            endPlug: 'arrow3',
                            dropShadow: true
                        }));
                    }
                });

                document.querySelectorAll('.node-dp-rec').forEach(el => {
                    makeDraggable(el);
                    const invId = el.getAttribute('data-parent');
                    const parent = document.getElementById(invId);
                    if (parent) {
                        lines.push(new LeaderLine(parent, el, {
                            color: colorDPRec,
                            size: 3,
                            path: 'fluid',
                            endPlug: 'arrow3',
                            dropShadow: true
                        }));
                    }
                });

                const nodeSI = document.getElementById('node-si');
                if (nodeSI) {
                    makeDraggable(nodeSI);
                    if (nodeSO) {
                        lines.push(new LeaderLine(nodeSO, nodeSI, {
                            color: colorSI,
                            size: 3,
                            path: 'fluid',
                            endPlug: 'arrow3',
                            dropShadow: true
                        }));
                    }
                }

                const nodeSR = document.getElementById('node-sr');
                if (nodeSR) {
                    makeDraggable(nodeSR);
                    if (nodeSI) {
                        lines.push(new LeaderLine(nodeSI, nodeSR, {
                            color: colorSR,
                            size: 3,
                            path: 'fluid',
                            endPlug: 'arrow3',
                            dropShadow: true
                        }));
                    }
                }

                // Ultimate Scroll Fix: Catch ALL scroll events on the page (even inside divs)
                window.addEventListener('scroll', updateLines, true);
                window.addEventListener('resize', updateLines);
            }

            loadMapScripts(() => {
                // Initialize after DOM is fully ready
                setTimeout(() => {
                    initMap();
                }, 300);
            });

            Livewire.hook('morph.updated', ({
                component
            }) => {
                loadMapScripts(() => {
                    setTimeout(() => {
                        initMap();
                    }, 300);
                });
            });
        });
    </script>
</div>
