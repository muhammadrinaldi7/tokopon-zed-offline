<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('zoffline.inbound.index') }}"
                class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-700 font-semibold text-sm mb-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                Kembali ke Daftar PO
            </a>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight flex items-center gap-2">
                Pindai Inbound: <span class="text-blue-600">{{ $po->po_number }}</span>
            </h2>
            <p class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
                Vendor: <strong>{{ $po->vendor->vendor_name ?? '-' }}</strong>
            </p>
        </div>

        <div>
            @php
                $ordered = $po->items->sum('quantity_ordered');
                $received = $po->items->sum('quantity_received');
                $isComplete = $ordered > 0 && $received === $ordered;
                $isPartial = $received > 0 && $received < $ordered;
            @endphp

            <button wire:click="completeReceiveItem"
                class="px-6 py-3 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 {{ $isComplete || $isPartial ? 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-md' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed' }}"
                {{ $isComplete || $isPartial ? '' : 'disabled' }}>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Selesaikan Penerimaan (Sync)
            </button>
        </div>
    </div>

    <!-- Alerts -->
    @if ($errorMessage)
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl mb-6 flex items-start gap-3 shadow-sm animate-fade-in-up"
            role="alert">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h4 class="font-bold text-rose-800">Gagal Memindai</h4>
                <p class="text-sm mt-0.5">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    @if ($successMessage)
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl mb-6 flex items-start gap-3 shadow-sm animate-fade-in-up"
            role="alert">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h4 class="font-bold text-emerald-800">Berhasil</h4>
                <p class="text-sm mt-0.5">{{ $successMessage }}</p>
            </div>
        </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Scan Instructions & Progress -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-6">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                        </path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-neutral-800 mb-2">Panduan Pemindaian</h3>
                <ul class="space-y-3 text-sm text-neutral-600">
                    <li class="flex items-start gap-2">
                        <span
                            class="bg-blue-100 text-blue-700 w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">1</span>
                        <span>Klik tombol <strong class="text-blue-600">Scan IMEI</strong> pada produk yang ingin Anda
                            terima di daftar sebelah kanan.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span
                            class="bg-blue-100 text-blue-700 w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">2</span>
                        <span>Pindai barcode IMEI/SN menggunakan alat scanner pada popup yang muncul.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span
                            class="bg-blue-100 text-blue-700 w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">3</span>
                        <span>Isi form QC (Quality Control) untuk menentukan kelayakan kondisi fisik produk.</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-6">
                <h3 class="font-bold text-neutral-800 mb-4">Progress Keseluruhan</h3>
                @php
                    $totalPercent = $ordered > 0 ? min(100, round(($received / $ordered) * 100)) : 0;
                @endphp
                <div class="flex items-end justify-between mb-2">
                    <div class="text-3xl font-black text-blue-600">{{ $received }}</div>
                    <div class="text-sm font-bold text-neutral-500 mb-1">dari {{ $ordered }} Unit</div>
                </div>
                <div class="w-full bg-neutral-100 rounded-full h-3 mb-2 overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full transition-all duration-1000"
                        style="width: {{ $totalPercent }}%"></div>
                </div>
                <div class="text-right text-xs font-bold text-neutral-500">{{ $totalPercent }}% Selesai</div>
            </div>
        </div>

        <!-- Right Column: Item List -->
        <div class="lg:col-span-2 space-y-4">
            @foreach ($po->items as $item)
                @php
                    $isDone = $item->quantity_received >= $item->quantity_ordered;
                    $isActive = $activeItemNo === $item->item_no;
                @endphp
                <div
                    class="bg-white rounded-2xl shadow-sm border {{ $isActive ? 'border-blue-400 ring-4 ring-blue-50' : 'border-neutral-200' }} overflow-hidden transition-all">
                    <!-- Item Header -->
                    <div
                        class="p-5 {{ $isDone ? 'bg-emerald-50/50' : 'bg-white' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-neutral-100">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-bold text-neutral-800 text-lg">{{ $item->item_name }}</h4>
                                @if ($isDone)
                                    <span
                                        class="bg-emerald-100 text-emerald-700 text-xs px-2.5 py-1 rounded-md font-bold flex items-center gap-1 border border-emerald-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Lengkap
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 text-sm text-neutral-500">
                                <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-neutral-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                        </path>
                                    </svg> <span
                                        class="font-mono font-bold text-neutral-700">{{ $item->item_no }}</span></span>
                                <span class="text-neutral-300">&bull;</span>
                                <span class="font-semibold text-neutral-600">Rp
                                    {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-5 sm:justify-end">
                            <div class="text-right">
                                <div class="text-xs font-bold text-neutral-500 uppercase tracking-wider mb-0.5">
                                    Diterima</div>
                                <div class="text-2xl font-black {{ $isDone ? 'text-emerald-600' : 'text-blue-600' }}">
                                    {{ $item->quantity_received }}<span
                                        class="text-base text-neutral-400 font-bold">/{{ $item->quantity_ordered }}</span>
                                </div>
                            </div>
                            @if (!$isDone)
                                <button wire:click="setActiveItem('{{ $item->item_no }}')"
                                    class="px-5 py-2.5 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-600 hover:text-white transition-all text-sm font-bold shadow-sm border border-blue-200 hover:border-blue-600 flex items-center gap-2 group shrink-0">
                                    <svg class="w-5 h-5 text-blue-500 group-hover:text-white transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                        </path>
                                    </svg>
                                    Scan IMEI
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Scanned IMEIs List -->
                    @if ($item->inspections->count() > 0)
                        <div class="p-4 bg-neutral-50 border-t border-neutral-100">
                            <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider mb-3">Daftar
                                IMEI yang telah di-Scan:</div>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($item->inspections as $ins)
                                    <div
                                        class="group inline-flex items-center bg-white border {{ $ins->verdict === 'PASSED' ? 'border-emerald-200 shadow-sm' : 'border-rose-300 bg-rose-50 shadow-sm' }} rounded-lg overflow-hidden transition-all hover:shadow">
                                        <div
                                            class="px-2.5 py-1.5 flex items-center border-r {{ $ins->verdict === 'PASSED' ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-rose-200 bg-rose-100 text-rose-600' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="{{ $ins->verdict === 'PASSED' ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12' }}">
                                                </path>
                                            </svg>
                                        </div>
                                        <span
                                            class="px-3 py-1.5 text-sm font-mono font-bold {{ $ins->verdict === 'FAILED' ? 'text-rose-700' : 'text-neutral-700' }}">
                                            {{ $ins->imei }}
                                        </span>
                                        <button wire:click="deleteQc({{ $ins->id }})"
                                            class="px-3 py-1.5 bg-neutral-50 text-neutral-400 hover:bg-rose-500 hover:text-white transition-colors border-l border-neutral-100 group-hover:border-transparent"
                                            title="Hapus Scan">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-4 bg-neutral-50 flex items-center justify-center border-t border-neutral-100">
                            <span class="text-sm text-neutral-400 flex items-center gap-2"><svg class="w-5 h-5"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg> Belum ada IMEI yang dipindai untuk produk ini.</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal Scan IMEI Popup -->
    @if ($activeItemNo && !$scannedImei)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-neutral-900/60 backdrop-blur-sm p-4"
            x-data="{}" x-init="setTimeout(() => $refs.imeiInput.focus(), 100)">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden relative animate-fade-in-up">
                <!-- Modal Header Strip -->
                <div class="h-2 bg-blue-500 w-full absolute top-0 left-0"></div>

                <button wire:click="$set('activeItemNo', null)"
                    class="absolute top-6 right-6 w-8 h-8 flex items-center justify-center rounded-full bg-neutral-100 text-neutral-500 hover:bg-rose-100 hover:text-rose-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="p-8 pb-10 text-center">
                    <div
                        class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner ring-8 ring-white">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                            </path>
                        </svg>
                    </div>

                    <h3 class="font-black text-2xl text-neutral-800">Scan Barcode SN/IMEI</h3>
                    <p class="text-sm text-neutral-500 mt-2 mb-6">Arahkan scanner ke barcode serial number</p>

                    <div class="bg-blue-50/50 border border-blue-100 p-4 rounded-2xl text-left shadow-sm mb-6">
                        <div class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Target Produk
                        </div>
                        <div class="font-bold text-blue-900 text-lg leading-tight mb-1">
                            {{ $po->items->where('item_no', $activeItemNo)->first()->item_name ?? $activeItemNo }}
                        </div>
                        <div
                            class="inline-flex items-center gap-1.5 px-2 py-1 bg-white rounded-md border border-blue-100 text-xs font-mono font-bold text-blue-700">
                            <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                </path>
                            </svg>
                            {{ $activeItemNo }}
                        </div>
                    </div>

                    <div class="relative">
                        <input type="text" wire:model="barcodeInput" wire:keydown.enter="processScan"
                            x-ref="imeiInput"
                            class="w-full pl-12 pr-4 py-4 border-2 border-blue-200 rounded-2xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 text-xl font-mono shadow-sm transition-all text-center tracking-widest text-neutral-800"
                            placeholder="TAP & SCAN DI SINI">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-blue-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <button wire:click="$set('activeItemNo', null)"
                        class="mt-6 text-sm font-semibold text-neutral-400 hover:text-neutral-700 transition-colors inline-flex items-center gap-1">
                        Batalkan Scan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Detailed QC Form Modal Overlay -->
    @if ($scannedImei && $activeItemId)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-neutral-900/70 backdrop-blur-sm p-4 overflow-y-auto"
            x-data="{ qcStep: 0 }">
            <div
                class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden relative animate-fade-in-up">
                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-neutral-100 flex justify-between items-center bg-white z-10 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-black text-lg text-neutral-900 uppercase tracking-wide">Form QC Inbound
                            </h3>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                IMEI: <strong class="text-blue-600 font-mono">{{ $scannedImei }}</strong>
                            </p>
                        </div>
                    </div>
                    <button wire:click="$set('scannedImei', '')"
                        class="w-10 h-10 flex items-center justify-center rounded-full bg-neutral-100 text-neutral-500 hover:bg-rose-100 hover:text-rose-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (The QC Wizard Component) -->
                <div class="flex-1 overflow-y-auto bg-neutral-50/50 relative p-6 md:p-8">
                    @livewire(
                        'admin.qc.inspection-form',
                        [
                            'inspectableType' => \App\Models\PurchaseOrderItem::class,
                            'inspectableId' => $activeItemId,
                            'imei' => $scannedImei,
                            'label' => 'QC Inbound PO Grosir',
                            'hideVerdict' => false,
                            'hideHeader' => true,
                        ],
                        key('qc-form-' . $scannedImei)
                    )
                </div>
            </div>
        </div>
    @endif
</div>
