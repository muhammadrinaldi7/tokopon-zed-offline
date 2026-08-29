<div>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.inbound.index') }}" class="text-blue-600 hover:underline text-sm"><i
                    class="fas fa-arrow-left"></i> Kembali ke List</a>
            <h2 class="text-2xl font-bold text-gray-800 mt-2">QC Inbound: {{ $po->po_number }}</h2>
            <p class="text-sm text-gray-500">Vendor: {{ $po->vendor->vendor_name ?? '-' }}</p>
        </div>

        <div>
            @php
                $ordered = $po->items->sum('quantity_ordered');
                $received = $po->items->sum('quantity_received');
                $isComplete = $ordered > 0 && $received === $ordered;
                $isPartial = $received > 0 && $received < $ordered;
            @endphp

            <button wire:click="completeReceiveItem"
                class="px-5 py-2 font-bold rounded shadow transition {{ $isComplete || $isPartial ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
                {{ $isComplete || $isPartial ? '' : 'disabled' }}>
                <i class="fas fa-check-circle mr-2"></i> Selesaikan Receive Item
            </button>
        </div>
    </div>

    @if ($errorMessage)
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ $errorMessage }}</p>
        </div>
    @endif

    @if ($successMessage)
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ $successMessage }}</p>
        </div>
    @endif

    {{-- <div class="mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 p-6 lg:p-8">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-barcode text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-800">Scan Produk (SKU)</h3>
                    <p class="text-xs text-gray-500">Scan Barcode SKU pada kardus/produk atau klik tombol "Pilih" pada daftar di bawah.</p>
                </div>
            </div>
            <div class="relative max-w-2xl">
                <input type="text" wire:model="barcodeInput" wire:keydown.enter="processScan" autofocus
                    class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-lg transition-all"
                    placeholder="Scan SKU / Barcode Produk...">
                <i class="fas fa-search absolute left-4 top-4 text-gray-400 text-lg"></i>
            </div>
        </div>
    </div> --}}

    <!-- Daftar Item -->
    <div class="space-y-4">
        @foreach ($po->items as $item)
            @php
                $isDone = $item->quantity_received >= $item->quantity_ordered;
                $isActive = $activeItemNo === $item->item_no;
            @endphp
            <div
                class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden {{ $isActive ? 'ring-2 ring-blue-500' : '' }}">
                <div class="p-5 {{ $isDone ? 'bg-green-50' : 'bg-white' }} flex justify-between items-center border-b">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-gray-800 text-lg">{{ $item->item_name }}</h4>
                            @if ($isDone)
                                <span
                                    class="bg-green-200 text-green-800 text-xs px-2.5 py-1 rounded-full font-semibold"><i
                                        class="fas fa-check mr-1"></i> Selesai</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 mt-1">SKU: <span
                                class="font-mono font-bold text-gray-700">{{ $item->item_no }}</span> &bull; Rp
                            {{ number_format($item->unit_price, 0, ',', '.') }}</div>
                    </div>
                    <div class="text-right flex items-center gap-6">
                        <div class="text-3xl font-black {{ $isDone ? 'text-green-600' : 'text-blue-600' }}">
                            {{ $item->quantity_received }} <span class="text-base text-gray-400 font-normal">/
                                {{ $item->quantity_ordered }}</span>
                        </div>
                        @if (!$isDone)
                            <button wire:click="setActiveItem('{{ $item->item_no }}')"
                                class="px-5 py-2.5 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-600 hover:text-white transition-all text-sm font-bold shadow-sm border border-blue-100 hover:border-blue-600 flex items-center gap-2">
                                <i class="fas fa-qrcode"></i> Scan IMEI
                            </button>
                        @endif
                    </div>
                </div>

                @if ($item->inspections->count() > 0)
                    <div class="p-4 bg-gray-50">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Daftar IMEI
                                di-QC:</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($item->inspections as $ins)
                                <div
                                    class="inline-flex items-center bg-white border {{ $ins->verdict === 'PASSED' ? 'border-green-200' : 'border-red-300 bg-red-50' }} rounded-lg px-3 py-1.5 shadow-sm transition-all hover:shadow">
                                    <i
                                        class="fas {{ $ins->verdict === 'PASSED' ? 'fa-check text-green-500' : 'fa-times text-red-500' }} text-sm mr-2"></i>
                                    <span
                                        class="text-sm font-mono font-semibold {{ $ins->verdict === 'FAILED' ? 'text-red-700' : 'text-gray-700' }}">{{ $ins->imei }}</span>
                                    <button wire:click="deleteQc({{ $ins->id }})"
                                        class="ml-3 text-gray-300 hover:text-red-600 transition-colors"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-gray-50 flex justify-between items-center">
                        <span class="text-sm text-gray-400 italic"><i class="fas fa-info-circle mr-1"></i> Belum ada
                            IMEI yang di-scan untuk produk ini.</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Modal Scan IMEI -->
    @if ($activeItemNo && !$scannedImei)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4"
            x-data="{}" x-init="setTimeout(() => $refs.imeiInput.focus(), 100)">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 relative animate-[fadeIn_0.2s_ease-out]">
                <button wire:click="$set('activeItemNo', null)"
                    class="absolute top-5 right-5 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-100 hover:text-red-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>

                <div class="text-center mb-6">
                    <div
                        class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                        <i class="fas fa-qrcode text-4xl"></i>
                    </div>
                    <h3 class="font-black text-2xl text-gray-800">Scan IMEI / SN</h3>
                    <p class="text-sm text-gray-500 mt-2 mb-4">Silakan scan IMEI untuk produk berikut:</p>

                    <div
                        class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 p-4 rounded-xl text-left shadow-sm">
                        <div class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Produk Terpilih
                        </div>
                        <div class="font-bold text-blue-900 text-lg leading-tight">
                            {{ $po->items->where('item_no', $activeItemNo)->first()->item_name ?? $activeItemNo }}
                        </div>
                        <div class="text-xs font-mono text-blue-600 mt-1.5 flex items-center gap-1"><i
                                class="fas fa-barcode"></i> SKU: {{ $activeItemNo }}</div>
                    </div>
                </div>

                <div class="relative mt-2">
                    <input type="text" wire:model="barcodeInput" wire:keydown.enter="processScan" x-ref="imeiInput"
                        class="w-full pl-12 pr-4 py-4 border-2 border-blue-200 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100 text-xl font-mono shadow-inner transition-all"
                        placeholder="Scan Barcode IMEI...">
                    <i class="fas fa-keyboard absolute left-4 top-4 text-blue-300 text-xl"></i>
                </div>

                <div class="mt-5 text-center">
                    <button wire:click="$set('activeItemNo', null)"
                        class="text-sm font-semibold text-gray-400 hover:text-gray-700 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Ganti Produk (Batal)
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Detailed QC Modal / Wizard Overlay -->
    @if ($scannedImei && $activeItemId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 backdrop-blur-sm p-4 overflow-y-auto"
            x-data="{ qcStep: 0 }">
            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white z-10">
                    <div>
                        <h3 class="font-black text-lg text-gray-900 uppercase tracking-wide flex items-center gap-2">
                            <i class="fas fa-clipboard-check text-blue-600"></i> Inspeksi Fisik
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Item:
                            <strong>{{ $po->items->where('item_no', $activeItemNo)->first()->item_name ?? $activeItemNo }}</strong>
                            | IMEI: <strong>{{ $scannedImei }}</strong></p>
                    </div>
                    <button wire:click="$set('scannedImei', '')"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-100 hover:text-red-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body (The QC Wizard Component) -->
                <div class="flex-1 overflow-y-auto bg-gray-50/50 relative p-6 md:p-8">
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
