<div>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.inbound.index') }}" class="text-blue-600 hover:underline text-sm"><i class="fas fa-arrow-left"></i> Kembali ke List</a>
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
                class="px-5 py-2 font-bold rounded shadow transition {{ ($isComplete || $isPartial) ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
                {{ ($isComplete || $isPartial) ? '' : 'disabled' }}>
                <i class="fas fa-check-circle mr-2"></i> Selesaikan Receive Item
            </button>
        </div>
    </div>

    @if($errorMessage)
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ $errorMessage }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Panel Kiri: Scanner -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-barcode text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-lg">Rapid Scanner</h3>
                    <p class="text-xs text-gray-500 mt-1">Scan SKU (Item No) lalu scan IMEI secara bergantian.</p>
                </div>

                @if($activeItemNo)
                    <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4 text-center">
                        <span class="text-xs text-blue-600 font-bold uppercase tracking-wide">Aktif untuk Scan IMEI</span>
                        <div class="font-bold text-blue-900 mt-1">{{ $po->items->where('item_no', $activeItemNo)->first()->item_name ?? $activeItemNo }}</div>
                        <button wire:click="$set('activeItemNo', null)" class="text-xs text-red-500 mt-2 hover:underline">Batalkan Pilihan Item</button>
                    </div>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-4 text-center">
                        <span class="text-sm text-gray-600">Menunggu Scan SKU...</span>
                    </div>
                @endif

                <div class="relative">
                    <input type="text" wire:model="barcodeInput" wire:keydown.enter="processScan" autofocus
                        class="w-full pl-10 pr-4 py-3 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-lg shadow-inner"
                        placeholder="{{ $activeItemNo ? 'Scan IMEI/SN...' : 'Scan SKU / Barcode Produk...' }}">
                    <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Panel Kanan: Daftar Item -->
        <div class="md:col-span-2 space-y-4">
            @foreach($po->items as $item)
                @php
                    $isDone = $item->quantity_received >= $item->quantity_ordered;
                    $isActive = $activeItemNo === $item->item_no;
                @endphp
                <div class="bg-white rounded-lg shadow overflow-hidden {{ $isActive ? 'ring-2 ring-blue-500' : '' }}">
                    <div class="p-4 {{ $isDone ? 'bg-green-50' : 'bg-white' }} flex justify-between items-center border-b">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-gray-800">{{ $item->item_name }}</h4>
                                @if($isDone)
                                    <span class="bg-green-200 text-green-800 text-xs px-2 py-0.5 rounded-full"><i class="fas fa-check"></i> Selesai</span>
                                @endif
                                @if($isActive)
                                    <span class="bg-blue-200 text-blue-800 text-xs px-2 py-0.5 rounded-full animate-pulse">Scanning...</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">SKU: <span class="font-mono font-bold">{{ $item->item_no }}</span> &bull; Rp {{ number_format($item->unit_price, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold {{ $isDone ? 'text-green-600' : 'text-blue-600' }}">
                                {{ $item->quantity_received }} <span class="text-sm text-gray-500 font-normal">/ {{ $item->quantity_ordered }}</span>
                            </div>
                        </div>
                    </div>
                    
                    @if($item->inspections->count() > 0)
                        <div class="p-3 bg-gray-50">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-gray-600 uppercase">Daftar IMEI di-QC:</span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($item->inspections as $ins)
                                    <div class="inline-flex items-center bg-white border {{ $ins->verdict === 'PASSED' ? 'border-green-200' : 'border-red-300 bg-red-50' }} rounded px-2 py-1 shadow-sm">
                                        <i class="fas {{ $ins->verdict === 'PASSED' ? 'fa-check text-green-500' : 'fa-times text-red-500' }} text-xs mr-2"></i>
                                        <span class="text-sm font-mono {{ $ins->verdict === 'FAILED' ? 'text-red-700' : '' }}">{{ $ins->imei }}</span>
                                        <button wire:click="deleteQc({{ $ins->id }})" class="ml-2 text-gray-400 hover:text-red-600"><i class="fas fa-trash text-xs"></i></button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-gray-50 flex justify-between items-center">
                            <span class="text-xs text-gray-500 italic">Belum ada IMEI di-scan.</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Detailed QC Modal / Wizard Overlay -->
    @if($scannedImei && $activeItemId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 backdrop-blur-sm p-4 overflow-y-auto" x-data="{ qcStep: 0 }">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white z-10">
                    <div>
                        <h3 class="font-black text-lg text-gray-900 uppercase tracking-wide flex items-center gap-2">
                            <i class="fas fa-clipboard-check text-blue-600"></i> Inspeksi Fisik
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Item: <strong>{{ $po->items->where('item_no', $activeItemNo)->first()->item_name ?? $activeItemNo }}</strong> | IMEI: <strong>{{ $scannedImei }}</strong></p>
                    </div>
                    <button wire:click="$set('scannedImei', '')" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-100 hover:text-red-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body (The QC Wizard Component) -->
                <div class="flex-1 overflow-y-auto bg-gray-50/50 relative p-6 md:p-8">
                    @livewire('admin.qc.inspection-form', [
                        'inspectableType' => \App\Models\PurchaseOrderItem::class,
                        'inspectableId' => $activeItemId,
                        'imei' => $scannedImei,
                        'label' => 'QC Inbound PO Grosir',
                        'hideVerdict' => false,
                        'hideHeader' => true
                    ], key('qc-form-' . $scannedImei))
                </div>
            </div>
        </div>
    @endif
</div>
