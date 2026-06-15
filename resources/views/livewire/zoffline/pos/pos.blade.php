<div class="bg-gray-100" x-data="{ showSidebar: false }">

    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-gray-50">
        {{-- Clean Header --}}
        <div class="bg-white px-8 py-5 shrink-0 z-10 flex justify-between items-center border-b border-gray-200">
            <div>
                <h2 class="text-2xl font-black text-gray-800">
                    @if ($currentStep == 1)
                        Siapa Pelanggan Anda Hari Ini?
                    @elseif($currentStep == 2)
                        SCAN BARCODE BARANG
                    @elseif($currentStep == 3)
                        Tambahkan Proteksi & Paket Pendukung
                    @elseif($currentStep == 4)
                        Penyelesaian Pembayaran
                    @endif
                </h2>
                @if ($currentStep == 1)
                    <p class="text-gray-500 text-sm mt-1">Cari data pelanggan yang sudah terdaftar atau tambahkan pelanggan baru untuk memulai transaksi.</p>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800">{{ Auth::user()->name ?? 'Kasir' }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->businessUnit->name ?? '-' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg uppercase">
                    {{ substr(Auth::user()->name ?? 'K', 0, 1) }}
                </div>
            </div>
        </div>

        {{-- Main Content Area --}}
        <div class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 relative">
            <div class="max-w-4xl mx-auto min-h-full flex flex-col">
                @if ($currentStep == 1)
                    @include('livewire.zoffline.pos.partials.wizard.step1-customer')
                @elseif($currentStep == 2)
                    @include('livewire.zoffline.pos.partials.wizard.step2-cart')
                @elseif($currentStep == 3)
                    @include('livewire.zoffline.pos.partials.wizard.step3-upsell')
                @elseif($currentStep == 4)
                    @include('livewire.zoffline.pos.partials.wizard.step4-payment')
                @endif
            </div>
        </div>
    </div>

    @include('livewire.zoffline.pos.modal.variant')
    @include('livewire.zoffline.pos.modal.checkout')
    @include('livewire.zoffline.pos.modal.riwayat-penjualan')
    @if ($showHistoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800">Riwayat Transaksi POS (Hari Ini)</h3>
                    <button wire:click="$set('showHistoryModal', false)"
                        class="text-gray-400 hover:text-rose-500 font-bold">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto">
                    @include('livewire.zoffline.pos.partials.history-modal')
                </div>
            </div>
        </div>
    @endif

    <!-- Modal QC Serah Terima -->
    @if ($showQcModal && $targetSnId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Inspeksi QC Serah Terima</h3>
                    <button wire:click="$set('showQcModal', false)"
                        class="text-gray-400 hover:text-rose-500 font-bold">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1">
                    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-100 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-emerald-900 font-medium">QC Fisik Depan Pelanggan (IMEI: <span
                                    class="font-mono font-bold">{{ $targetImei }}</span>)</span>
                        </div>
                        <p class="text-xs text-emerald-700 mt-1">Pastikan kondisi fisik sesuai di hadapan pelanggan
                            sebelum diserahterimakan.</p>
                    </div>

                    {{-- We use key() to force component re-render when targetSnId changes --}}
                    @livewire(
                        'admin.qc.inspection-form',
                        [
                            'inspectableType' => \App\Models\ProductSerialNumber::class,
                            'inspectableId' => $targetSnId,
                            'label' => 'QC Serah Terima',
                        ],
                        key('qc-form-' . $targetSnId)
                    )
                </div>
                <div class="p-4 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="$set('showQcModal', false)"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition">Batal</button>
                </div>
            </div>
        </div>
    @endif

    @include('livewire.zoffline.pos.modal.draft-penjualan')
    @include('livewire.zoffline.pos.modal.receipt-struk')
    @include('livewire.zoffline.pos.modal.stok-gudang')

    {{-- <div id="scanner-modal"
        class="hidden fixed inset-0 z-50 bg-black/60  items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-4 rounded-lg w-11/12 max-w-md shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-700">Arahkan Kamera ke Barcode</h3>
                <button onclick="closeScanner()" class="text-red-500 hover:text-red-700 font-bold p-1">Tutup</button>
            </div>
            <div id="reader" class="w-full bg-black rounded overflow-hidden"></div>
        </div>
    </div> --}}
    <div id="scanner-modal" class="hidden fixed inset-0 z-50 bg-black/60 items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-4 rounded-lg w-11/12 max-w-md shadow-xl">

            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-700">Arahkan Kamera ke Barcode</h3>
                <button onclick="closeScanner()" class="text-red-500 hover:text-red-700 font-bold p-1">Tutup</button>
            </div>
            {{-- Kamera Element --}}
            <div id="reader" class="w-full h-full rounded-md overflow-hidden"></div>
        </div>
    </div>
    <!-- Customer QC Modal (Sertifikat QC) -->
    @if ($showCustomerQcModal && $customerQcData)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden relative">

                {{-- Aksen Header Cantik --}}
                <div
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white shrink-0 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                    <div class="relative z-10 flex items-start justify-between">
                        <div>
                            <h3 class="text-xl font-black flex items-center gap-2">
                                <svg class="w-6 h-6 text-blue-200" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Sertifikat QC Perangkat
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">Lulus Inspeksi Kualitas Standar</p>
                        </div>
                        <button wire:click="$set('showCustomerQcModal', false)"
                            class="text-white hover:text-rose-200 bg-white/10 hover:bg-rose-500 rounded-full w-8 h-8 flex items-center justify-center transition focus:outline-none">
                            &times;
                        </button>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50">

                    {{-- Device ID / IMEI --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-5 flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">IMEI / Serial Number
                            </p>
                            <p class="text-lg font-mono font-black text-gray-800">{{ $customerQcData->imei }}</p>
                        </div>
                        <div class="text-right">
                            <div
                                class="inline-flex flex-col items-center justify-center px-3 py-1.5 bg-emerald-50 border border-emerald-100 rounded-lg">
                                <span class="text-[10px] text-emerald-600 font-bold uppercase">Status QC</span>
                                <span class="text-sm font-black text-emerald-500">PASS</span>
                            </div>
                        </div>
                    </div>

                    {{-- QC Details Grid --}}
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Diinspeksi Pada</p>
                            <p class="text-sm font-medium text-gray-800 mt-0.5">
                                {{ $customerQcData->inspected_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Inspektor</p>
                            <p class="text-sm font-medium text-gray-800 mt-0.5">
                                {{ $customerQcData->inspector->name ?? 'Tim QC' }}</p>
                        </div>
                    </div>

                    {{-- Checklist Results Highlights --}}
                    @if ($customerQcData->checklist_results && is_array($customerQcData->checklist_results))
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 px-1">Ringkasan
                            Pengecekan Fisik & Fungsi</h4>
                        <div
                            class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden divide-y divide-gray-100 mb-6">
                            @foreach (array_slice($customerQcData->checklist_results, 0, 8) as $item)
                                <div class="px-4 py-2.5 flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">{{ $item['name'] }}</span>
                                    @if (($item['type'] ?? 'boolean') === 'boolean')
                                        @if ($item['value'])
                                            <span
                                                class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-600">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                                OK
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-600">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                MINUS
                                            </span>
                                        @endif
                                    @else
                                        <span
                                            class="text-sm font-bold text-gray-800">{{ $item['value'] ?? '-' }}</span>
                                    @endif
                                </div>
                            @endforeach
                            @if (count($customerQcData->checklist_results) > 8)
                                <div class="px-4 py-2 bg-gray-50 text-center">
                                    <p class="text-xs text-gray-500 italic">+
                                        {{ count($customerQcData->checklist_results) - 8 }} item lainnya telah dicek
                                        dengan status OK</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Photos --}}
                    @if ($customerQcData->hasMedia('qc_photos'))
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 px-1">Dokumentasi Unit
                        </h4>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($customerQcData->getMedia('qc_photos')->take(3) as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank"
                                    class="block aspect-square rounded-lg overflow-hidden border border-gray-200 shadow-sm hover:border-blue-400 transition">
                                    <img src="{{ $media->getUrl() }}" alt="QC Photo"
                                        class="w-full h-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    @endif

                </div>

                <div class="p-4 border-t border-gray-100 bg-white flex justify-end shrink-0">
                    <button wire:click="$set('showCustomerQcModal', false)"
                        class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

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
</div>
