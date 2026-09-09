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
            <div class="flex flex-wrap items-center gap-y-1 gap-x-3 text-sm text-gray-500 mt-1">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                    Vendor: <strong class="text-gray-800">{{ $po->vendor->vendor_name ?? '-' }}</strong>
                </span>
                <span class="text-gray-300">|</span>
                <span class="flex items-center gap-1.5">
                    Cabang: <strong class="text-gray-800">{{ Auth::user()->branch->name ?? 'Pusat' }}</strong>
                </span>
                <span class="text-gray-300">|</span>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-gray-600">Gudang Masuk:</label>
                    <select wire:model.live="selectedWarehouseId"
                        class="text-xs font-bold text-blue-700 bg-blue-50/80 border border-blue-200 rounded-lg px-2.5 py-1 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                        @foreach ($availableWarehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @php
                $ordered = $po->items->sum('quantity_ordered');
                $received = $po->items->sum('quantity_received');
                $isComplete = $ordered > 0 && $received === $ordered;
                $isPartial = $received > 0 && $received < $ordered;
            @endphp

            {{-- PERMISSION CHECK: Tambahkan @can('inbound.migrate-scan') atau check permission di sini jika diperlukan --}}
            @can('salin-imei-po')
                <button wire:click="openMigrateModal"
                    class="px-4 py-3 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 text-sm hover:shadow-md">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                        </path>
                    </svg>
                    <span>Salin Scan dari PO Lain</span>
                </button>
            @endcan

            <button wire:click="openConfirmModal"
                class="px-6 py-3 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 {{ $isComplete || $isPartial ? 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-md' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed' }}"
                {{ $isComplete || $isPartial ? '' : 'disabled' }}>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Selesaikan Penerimaan (Sync)</span>
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
                    $proyek = $item->proyek ?? '-';
                    $upperProyek = strtoupper($proyek);
                    $isResmi =
                        str_contains($upperProyek, 'RESMI') ||
                        str_contains($upperProyek, 'IBOX') ||
                        str_contains($upperProyek, 'TAM');
                    $isInter = str_contains($upperProyek, 'INTER') || str_contains($upperProyek, 'GLOBAL');
                @endphp
                <div
                    class="bg-white rounded-2xl shadow-sm border {{ $isActive ? 'border-blue-400 ring-4 ring-blue-50' : 'border-neutral-200' }} overflow-hidden transition-all">
                    <!-- Item Header -->
                    <div
                        class="p-5 {{ $isDone ? 'bg-emerald-50/50' : 'bg-white' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-neutral-100">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h4 class="font-bold text-neutral-800 text-lg">{{ $item->item_name }}</h4>

                                @if ($proyek !== '-')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-extrabold uppercase tracking-wide border {{ $isResmi ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ($isInter ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-blue-100 text-blue-800 border-blue-200') }}">
                                        📁 Proyek: {{ $proyek }}
                                    </span>
                                @endif

                                @if ($isDone)
                                    <span
                                        class="bg-emerald-100 text-emerald-700 text-xs px-2.5 py-1 rounded-md font-bold flex items-center gap-1 border border-emerald-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
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
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-3">
                                <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">
                                    Daftar IMEI yang telah di-Scan ({{ $item->inspections->count() }}):
                                </div>
                                <span class="text-[11px] text-neutral-400">Klik ✏️ untuk koreksi IMEI typo sebelum
                                    sync</span>
                            </div>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($item->inspections as $ins)
                                    @if ($editingInspectionId === $ins->id)
                                        <!-- Inline Edit Form -->
                                        <div
                                            class="inline-flex flex-col bg-white border-2 border-blue-400 rounded-xl p-2 shadow-md animate-fade-in">
                                            <div class="flex items-center gap-1.5">
                                                <input type="text" wire:model="editingImei"
                                                    wire:keydown.enter="saveEditImei"
                                                    wire:keydown.escape="cancelEditImei"
                                                    class="font-mono text-xs font-bold px-2.5 py-1.5 border border-blue-300 rounded-lg bg-blue-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase tracking-wider text-neutral-800"
                                                    placeholder="Ketik IMEI baru...">
                                                <button type="button" wire:click="saveEditImei"
                                                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    Simpan
                                                </button>
                                                <button type="button" wire:click="cancelEditImei"
                                                    class="px-2 py-1.5 bg-neutral-200 hover:bg-neutral-300 text-neutral-700 rounded-lg text-xs font-semibold transition-colors">
                                                    Batal
                                                </button>
                                            </div>
                                            @if ($editErrorMessage)
                                                <div
                                                    class="text-[10px] font-bold text-rose-600 mt-1 max-w-xs leading-tight">
                                                    {{ $editErrorMessage }}</div>
                                            @endif
                                        </div>
                                    @else
                                        @php
                                            $valRes = \App\Livewire\Zoffline\Inbound\Scan::validateImeiOrSn($ins->imei);
                                            $isLuhnValid = $valRes['valid'] && $valRes['type'] === 'imei_valid';
                                        @endphp
                                        <div
                                            class="group inline-flex items-center bg-white border {{ $ins->verdict === 'PASSED' ? 'border-emerald-200 shadow-sm' : 'border-rose-300 bg-rose-50 shadow-sm' }} rounded-lg overflow-hidden transition-all hover:shadow">
                                            <div
                                                class="px-2.5 py-1.5 flex items-center border-r {{ $ins->verdict === 'PASSED' ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-rose-200 bg-rose-100 text-rose-600' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3"
                                                        d="{{ $ins->verdict === 'PASSED' ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12' }}">
                                                    </path>
                                                </svg>
                                            </div>
                                            <span
                                                class="px-3 py-1.5 text-sm font-mono font-bold {{ $ins->verdict === 'FAILED' ? 'text-rose-700' : 'text-neutral-700' }}">
                                                {{ $ins->imei }}
                                            </span>

                                            @if ($isLuhnValid)
                                                <span
                                                    class="px-1.5 py-0.5 mr-1 text-[9px] font-bold bg-emerald-100 text-emerald-700 rounded"
                                                    title="Luhn Checksum Terverifikasi (15 Digit OK)">15✓</span>
                                            @endif

                                            @if (!$ins->is_pushed)
                                                <button type="button"
                                                    wire:click="startEditImei({{ $ins->id }})"
                                                    class="px-2 py-1.5 bg-neutral-50 text-neutral-400 hover:bg-blue-500 hover:text-white transition-colors border-l border-neutral-100 group-hover:border-transparent"
                                                    title="Koreksi IMEI">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="deleteQc({{ $ins->id }})"
                                                    class="px-2 py-1.5 bg-neutral-50 text-neutral-400 hover:bg-rose-500 hover:text-white transition-colors border-l border-neutral-100 group-hover:border-transparent"
                                                    title="Hapus Scan">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
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
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-neutral-900/60 backdrop-blur-sm p-4 overflow-y-auto"
            x-data="{
                cameraActive: false,
                rawInput: @entangle('barcodeInput'),
                html5Scanner: null,
            
                init() {
                    this.$nextTick(() => {
                        if (this.$refs.imeiInput) this.$refs.imeiInput.focus();
                    });
                },
            
                checkLuhn(val) {
                    let clean = (val || '').replace(/\D/g, '');
                    if (clean.length !== 15) return null;
                    let sum = 0;
                    let parity = 15 % 2;
                    for (let i = 0; i < 15; i++) {
                        let d = parseInt(clean[i]);
                        if (i % 2 === parity) {
                            d *= 2;
                            if (d > 9) d -= 9;
                        }
                        sum += d;
                    }
                    return sum % 10 === 0;
                },
            
                startCamera() {
                    this.cameraActive = true;
                    this.$nextTick(() => {
                        if (typeof Html5Qrcode !== 'undefined') {
                            try {
                                if (this.html5Scanner) {
                                    this.html5Scanner.stop().catch(() => {}).then(() => {
                                        this.launchCamera();
                                    });
                                } else {
                                    this.launchCamera();
                                }
                            } catch (e) {
                                console.error('Camera error', e);
                            }
                        } else {
                            alert('Scanner library belum siap, silakan gunakan input manual.');
                        }
                    });
                },
            
                launchCamera() {
                    this.html5Scanner = new Html5Qrcode('inbound-camera-reader');
                    this.html5Scanner.start({ facingMode: 'environment' }, { fps: 15, qrbox: { width: 280, height: 160 } },
                        (decodedText) => {
                            this.stopCamera();
                            this.rawInput = decodedText.trim();
                            $wire.set('barcodeInput', decodedText.trim());
                            $wire.processScan();
                        },
                        (err) => {}
                    ).catch(err => {
                        console.error('Camera start error', err);
                        alert('Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan di browser.');
                        this.cameraActive = false;
                    });
                },
            
                stopCamera() {
                    if (this.html5Scanner) {
                        try {
                            this.html5Scanner.stop().then(() => {
                                this.html5Scanner.clear();
                            }).catch(() => {});
                        } catch (e) {}
                    }
                    this.cameraActive = false;
                }
            }">
            <div
                class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden relative animate-fade-in-up border border-neutral-100">
                <!-- Modal Header Strip -->
                <div class="h-2 bg-gradient-to-r from-blue-500 to-indigo-600 w-full absolute top-0 left-0"></div>

                <button wire:click="$set('activeItemNo', null)" @click="stopCamera()"
                    class="absolute top-6 right-6 w-8 h-8 flex items-center justify-center rounded-full bg-neutral-100 text-neutral-500 hover:bg-rose-100 hover:text-rose-600 transition-colors z-20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="p-6 md:p-8 text-center">
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold border border-blue-200/60">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            Inbound QC Scanner
                        </span>
                    </div>

                    <h3 class="font-black text-2xl text-neutral-800">Scan Barcode SN/IMEI</h3>
                    <p class="text-xs text-neutral-500 mt-1 mb-4">Gunakan kamera HP atau ketik manual dengan verifikasi
                        otomatis</p>

                    <!-- Target Product Card -->
                    @php
                        $activeItem = $po->items->where('item_no', $activeItemNo)->first();
                        $activeProyek = $activeItem->proyek ?? '-';
                        $upperActiveProyek = strtoupper($activeProyek);
                        $isActiveResmi =
                            str_contains($upperActiveProyek, 'RESMI') ||
                            str_contains($upperActiveProyek, 'IBOX') ||
                            str_contains($upperActiveProyek, 'TAM');
                        $isActiveInter =
                            str_contains($upperActiveProyek, 'INTER') || str_contains($upperActiveProyek, 'GLOBAL');
                    @endphp
                    <div class="bg-slate-50 border border-slate-200/80 p-4 rounded-2xl text-left shadow-sm mb-4">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Target Produk
                            </div>
                            @if ($activeProyek !== '-')
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wide border {{ $isActiveResmi ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ($isActiveInter ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-blue-100 text-blue-800 border-blue-200') }}">
                                    📁 Proyek: {{ $activeProyek }}
                                </span>
                            @endif
                        </div>
                        <div class="font-bold text-neutral-900 text-base leading-snug mb-1">
                            {{ $activeItem->item_name ?? $activeItemNo }}
                        </div>
                        <div
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-white rounded-md border border-neutral-200 text-xs font-mono font-bold text-neutral-700">
                            SKU: {{ $activeItemNo }}
                        </div>
                    </div>

                    <!-- Camera Viewfinder if Active -->
                    <div x-show="cameraActive"
                        class="mb-4 relative rounded-2xl overflow-hidden bg-black border-2 border-blue-500 shadow-inner"
                        style="display: none;">
                        <div id="inbound-camera-reader" class="w-full" style="min-height: 240px;"></div>
                        <div class="p-3 bg-neutral-900 text-white flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-emerald-400 font-semibold animate-pulse">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Kamera Aktif... Arahkan ke
                                Barcode Dus/HP
                            </span>
                            <button type="button" @click="stopCamera()"
                                class="px-3 py-1 bg-neutral-700 hover:bg-neutral-600 rounded-lg text-xs font-bold text-white transition-colors">
                                Tutup Kamera
                            </button>
                        </div>
                    </div>

                    <!-- Mode Switch Buttons -->
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <button type="button" @click="if(!cameraActive) startCamera(); else stopCamera();"
                            class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all flex items-center justify-center gap-2"
                            :class="cameraActive ? 'bg-blue-600 text-white border-blue-600 shadow-sm' :
                                'bg-blue-50/80 hover:bg-blue-100 text-blue-700 border-blue-200'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span x-text="cameraActive ? 'Kamera Sedang Aktif' : '📷 Scan Kamera HP'"></span>
                        </button>

                        <button type="button" @click="stopCamera(); $refs.imeiInput.focus();"
                            class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all flex items-center justify-center gap-2"
                            :class="!cameraActive ? 'bg-slate-800 text-white border-slate-800 shadow-sm' :
                                'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            <span>⌨️ Input Manual / Scanner</span>
                        </button>
                    </div>

                    <!-- Input Box with Realtime Luhn Feedback -->
                    <div class="relative mb-2 text-left">
                        <input type="text" wire:model="barcodeInput" wire:keydown.enter="processScan"
                            x-ref="imeiInput" x-model="rawInput"
                            class="w-full pl-11 pr-4 py-3.5 border-2 rounded-2xl focus:outline-none text-lg font-mono shadow-sm transition-all text-center tracking-widest font-bold uppercase text-neutral-800"
                            :class="{
                                'border-emerald-500 focus:ring-4 focus:ring-emerald-100 bg-emerald-50/20': (rawInput ||
                                    '').replace(/\D/g, '').length === 15 && checkLuhn(rawInput) === true,
                                'border-rose-400 focus:ring-4 focus:ring-rose-100 bg-rose-50/20': (rawInput || '')
                                    .replace(/\D/g, '').length === 15 && checkLuhn(rawInput) === false,
                                'border-blue-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100': (rawInput ||
                                    '').replace(/\D/g, '').length !== 15
                            }"
                            placeholder="TAP / KETIK IMEI DI SINI">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <!-- Realtime Validation Helper Banner -->
                    <div class="space-y-2 mb-5">
                        <div class="flex items-center justify-between text-xs px-1 min-h-[24px]">
                            <div class="flex items-center gap-1.5">
                                <template
                                    x-if="(rawInput || '').replace(/\D/g, '').length === 15 && checkLuhn(rawInput) === true">
                                    <span
                                        class="text-emerald-700 font-bold flex items-center gap-1 bg-emerald-100 px-2.5 py-0.5 rounded-md">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Luhn Checksum Sesuai (15 Digit OK)
                                    </span>
                                </template>
                                <template
                                    x-if="(rawInput || '').replace(/\D/g, '').length === 15 && checkLuhn(rawInput) === false">
                                    <span
                                        class="text-rose-700 font-bold flex items-center gap-1 bg-rose-100 px-2.5 py-0.5 rounded-md animate-pulse">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Salah Ketik Angka (Luhn Invalid)
                                    </span>
                                </template>
                                <template
                                    x-if="(rawInput || '').length > 0 && (rawInput || '').replace(/\D/g, '').length !== 15">
                                    <span
                                        class="text-slate-500 font-medium bg-slate-100 px-2 py-0.5 rounded-md font-mono text-[11px]">
                                        Panjang: <strong x-text="(rawInput || '').length"></strong> Karakter
                                    </span>
                                </template>
                            </div>

                            <!-- 4 Digit Highlight -->
                            <template x-if="(rawInput || '').length >= 4">
                                <div
                                    class="text-[11px] font-mono text-slate-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                    Akhiran: <strong class="text-blue-700 font-black text-xs"
                                        x-text="(rawInput || '').slice(-4)"></strong>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Submit & Cancel Buttons -->
                    <div class="flex items-center justify-between gap-3 pt-2 border-t border-neutral-100">
                        <button type="button" wire:click="$set('activeItemNo', null)" @click="stopCamera()"
                            class="px-4 py-2.5 text-xs font-bold text-neutral-500 hover:text-neutral-800 transition-colors">
                            Batal
                        </button>

                        <button type="button" wire:click="processScan"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-2">
                            <span>Lanjut ke QC Fisik</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </div>
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

    <!-- Modal Konfirmasi Selesaikan Penerimaan (Sync Accurate) -->
    @if ($showConfirmModal)
        @php
            $ordered = $po->items->sum('quantity_ordered');
            $received = $po->items->sum('quantity_received');
            $pushed = $po->items->sum('quantity_pushed');
            $toPush = $received - $pushed;
            $isComplete = $ordered > 0 && $received === $ordered;
            $selectedWh = \App\Models\Warehouse::find($selectedWarehouseId) ?? Auth::user()->warehouse;
        @endphp
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto animate-fade-in"
            x-data="{ showDetailSns: false }">
            <div
                class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-150">

                <!-- Header -->
                <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-500/20 text-emerald-400 rounded-xl border border-emerald-500/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold">Konfirmasi Penerimaan Barang (Sync Accurate)</h3>
                            <p class="text-xs text-slate-400">PO: <span
                                    class="text-white font-mono font-bold">{{ $po->po_number }}</span> &bull; Vendor:
                                <span class="text-slate-200">{{ $po->vendor->vendor_name ?? '-' }}</span></p>
                        </div>
                    </div>
                    <button wire:click="closeConfirmModal"
                        class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 overflow-y-auto space-y-4">

                    <!-- Warehouse Selection Card -->
                    <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-blue-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Gudang Tujuan Penerimaan
                            </span>
                            <span class="text-xs font-semibold text-slate-500">Cabang: <strong
                                    class="text-slate-700">{{ Auth::user()->branch->name ?? 'Pusat' }}</strong></span>
                        </div>

                        <div class="relative">
                            <select wire:model.live="selectedWarehouseId"
                                class="w-full text-sm font-bold text-blue-900 bg-white border border-blue-300 rounded-xl px-3.5 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer">
                                @foreach ($availableWarehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-[11px] text-blue-700/80 mt-1.5 flex items-center gap-1">
                            <span>ℹ️ Stok barang yang diterima akan otomatis masuk ke gudang
                                <strong>{{ $selectedWh->name ?? '-' }}</strong> di Accurate.</span>
                        </p>
                    </div>

                    <!-- Status Banner -->
                    @if ($isComplete)
                        <div
                            class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                                <span class="font-bold text-emerald-900">Status PO: Penerimaan Lengkap (100%
                                    COMPLETED)</span>
                            </div>
                            <span
                                class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-lg">{{ $received }}
                                / {{ $ordered }} Unit</span>
                        </div>
                    @else
                        <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    <span class="font-bold text-amber-900">Status PO: Penerimaan Sebagian
                                        (PARTIAL)</span>
                                </div>
                                <span
                                    class="font-mono font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-lg">{{ $received }}
                                    / {{ $ordered }} Unit</span>
                            </div>
                            <p class="text-[11px] text-amber-700">
                                Sisa <strong>{{ $ordered - $received }} unit</strong> belum di-scan dan dapat
                                disinkronkan kembali pada sesi inbound berikutnya.
                            </p>
                        </div>
                    @endif

                    <!-- Items Summary & Pre-Sync Audit Table -->
                    <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-sm">
                        <div
                            class="px-4 py-2.5 bg-slate-100 border-b border-gray-200 flex items-center justify-between text-xs font-bold text-slate-700">
                            <span>Rincian Barang yang akan Dikirim ke Accurate:</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="showDetailSns = !showDetailSns"
                                    class="text-[11px] text-blue-600 hover:text-blue-800 font-semibold underline">
                                    <span
                                        x-text="showDetailSns ? 'Sembunyikan Audit IMEI' : '🔍 Lihat Audit Detail IMEI'"></span>
                                </button>
                                <span
                                    class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">{{ $toPush }}
                                    Unit Siap Sync</span>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-56 overflow-y-auto text-xs">
                            @foreach ($po->items as $item)
                                @php
                                    $itemToPush = $item->quantity_received - $item->quantity_pushed;
                                    $unpushedInspections = $item->inspections->where('is_pushed', false);
                                    $confirmProyek = $item->proyek ?? '-';
                                    $upperConfirmProyek = strtoupper($confirmProyek);
                                    $isConfirmResmi =
                                        str_contains($upperConfirmProyek, 'RESMI') ||
                                        str_contains($upperConfirmProyek, 'IBOX') ||
                                        str_contains($upperConfirmProyek, 'TAM');
                                    $isConfirmInter =
                                        str_contains($upperConfirmProyek, 'INTER') ||
                                        str_contains($upperConfirmProyek, 'GLOBAL');
                                @endphp
                                @if ($itemToPush > 0)
                                    <div class="p-4 hover:bg-slate-50 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="font-bold text-slate-800">{{ $item->item_name }}</span>
                                                    @if ($confirmProyek !== '-')
                                                        <span
                                                            class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wide border {{ $isConfirmResmi ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ($isConfirmInter ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-blue-100 text-blue-800 border-blue-200') }}">
                                                            {{ $confirmProyek }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-slate-500 font-mono">SKU:
                                                    {{ $item->item_no }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-emerald-600 text-sm">+{{ $itemToPush }}
                                                    Unit</div>
                                                @if ($unpushedInspections->count() > 0)
                                                    <span
                                                        class="text-[10px] text-blue-600 font-medium">({{ $unpushedInspections->count() }}
                                                        SN/IMEI)</span>
                                                @else
                                                    <span class="text-[10px] text-slate-400 font-medium">Non-SN</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Expandable Serial Numbers List with Verification Badges -->
                                        @if ($unpushedInspections->count() > 0)
                                            <div x-show="showDetailSns"
                                                class="mt-2.5 pt-2 border-t border-slate-100 space-y-1"
                                                style="display: none;">
                                                <div class="text-[10px] font-bold text-slate-400 uppercase">Daftar IMEI
                                                    untuk {{ $item->item_name }}:</div>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach ($unpushedInspections as $ins)
                                                        @php
                                                            $val = \App\Livewire\Zoffline\Inbound\Scan::validateImeiOrSn(
                                                                $ins->imei,
                                                            );
                                                        @endphp
                                                        <span
                                                            class="inline-flex items-center gap-1 font-mono text-[11px] px-2 py-0.5 rounded border {{ $val['valid'] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                                                            <span>{{ $ins->imei }}</span>
                                                            @if ($val['valid'] && $val['type'] === 'imei_valid')
                                                                <span class="text-[9px] font-bold text-emerald-600">✓
                                                                    15</span>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Safety Notice -->
                    <div
                        class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-start gap-2 text-[11px] text-slate-600">
                        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Sistem akan membuat Surat Jalan (Receive Item) baru di Accurate Online dan meng-update
                            saldo kuantitas terkirim pada Purchase Order ini.</span>
                    </div>

                </div>

                <!-- Footer Actions -->
                <div class="p-4 bg-slate-50 border-t border-gray-200 flex items-center justify-between gap-3">
                    <button type="button" wire:click="closeConfirmModal"
                        class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-100 transition-colors">
                        Batal / Cek Lagi
                    </button>

                    <button type="button" wire:click="completeReceiveItem" wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="completeReceiveItem" class="w-4 h-4" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="completeReceiveItem" class="animate-spin w-4 h-4 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>Ya, Selesaikan & Kirim ke Accurate</span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- Modal Migrasi / Salin Scan dari PO Lain -->
    @if ($showMigrateModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div
                class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden animate-fade-in-up border border-neutral-100 my-8">
                <!-- Header Strip -->
                <div class="h-2 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 w-full"></div>

                <!-- Modal Header -->
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight">Salin / Pindahkan Scan IMEI
                                dari PO Lain</h3>
                            <p class="text-xs text-slate-500">Pindahkan data hasil scan IMEI dari PO lama ke PO ini
                                tanpa perlu scan ulang fisik.</p>
                        </div>
                    </div>
                    <button wire:click="closeMigrateModal"
                        class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Error Alert -->
                    @if ($migrateErrorMessage)
                        <div
                            class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl flex items-start gap-3 text-xs">
                            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <div class="font-bold text-rose-800">Perhatian</div>
                                <div>{{ $migrateErrorMessage }}</div>
                            </div>
                        </div>
                    @endif

                    <!-- Step 1: Select Source PO -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            1. Pilih Purchase Order Asal (Sumber Data Scan)
                        </label>
                        <select wire:model.live="sourcePoId"
                            class="w-full text-sm font-semibold text-slate-800 bg-slate-50 border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
                            <option value="">-- Pilih Nomor PO yang Memiliki Data Scan --</option>
                            @foreach ($availableSourcePos as $src)
                                @php
                                    $srcInspectionCount = $src->items->sum(fn($it) => $it->inspections->count());
                                @endphp
                                <option value="{{ $src->id }}">
                                    {{ $src->po_number }} - {{ $src->vendor->vendor_name ?? 'Vendor' }}
                                    ({{ $srcInspectionCount }} IMEI discan)
                                    [{{ \Carbon\Carbon::parse($src->po_date)->format('d/m/Y') }}]
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($sourcePoObj)
                        <!-- Options & Migration Settings -->
                        <div class="p-4 bg-amber-50/70 border border-amber-200/80 rounded-2xl space-y-3">
                            <div class="text-xs font-bold text-amber-900 uppercase tracking-wider">2. Opsi Pemindahan
                                Data</div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <label
                                    class="flex items-start gap-2.5 p-3 bg-white border border-amber-200 rounded-xl cursor-pointer hover:bg-amber-50/50 transition-colors">
                                    <input type="radio" wire:model.live="migrateOption" value="move"
                                        class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                    <div>
                                        <div class="font-bold text-slate-800">Pindahkan Data (Move)</div>
                                        <div class="text-[11px] text-slate-500 leading-tight mt-0.5">IMEI dialihkan ke
                                            PO baru ini (Direkomendasikan jika PO lama salah SKU).</div>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-2.5 p-3 bg-white border border-amber-200 rounded-xl cursor-pointer hover:bg-amber-50/50 transition-colors">
                                    <input type="radio" wire:model.live="migrateOption" value="copy"
                                        class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                    <div>
                                        <div class="font-bold text-slate-800">Salin Data (Duplicate)</div>
                                        <div class="text-[11px] text-slate-500 leading-tight mt-0.5">Buat salinan baru
                                            IMEI ke PO ini dan biarkan data di PO lama tetap ada.</div>
                                    </div>
                                </label>
                            </div>

                            <label
                                class="flex items-center gap-2 pt-1 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" wire:model="resetPushedStatus"
                                    class="rounded text-amber-600 focus:ring-amber-500">
                                <span>Reset status push Accurate (<code
                                        class="text-amber-800 font-mono text-[10px]">is_pushed = 0</code>) agar siap
                                    disinkronkan ke Accurate</span>
                            </label>
                        </div>

                        <!-- Step 3: Mapping Items Table -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    3. Pemetaan SKU / Produk (Target PO Ini ← Sumber PO Lama)
                                </label>
                                <span class="text-[11px] text-slate-500">Sistem otomatis mencocokkan nama item yang
                                    serupa</span>
                            </div>

                            <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-100 border-b border-slate-200 text-slate-700 font-bold">
                                        <tr>
                                            <th class="p-3 w-1/2">Item di PO Ini (Target)</th>
                                            <th class="p-3 w-1/2">Ambil Scan IMEI dari Item PO Lama (Sumber)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($po->items as $targetItem)
                                            @php
                                                $targetProyek = $targetItem->proyek ?? '-';
                                                $upperTargetProyek = strtoupper($targetProyek);
                                                $isTargetResmi =
                                                    str_contains($upperTargetProyek, 'RESMI') ||
                                                    str_contains($upperTargetProyek, 'IBOX') ||
                                                    str_contains($upperTargetProyek, 'TAM');
                                                $isTargetInter =
                                                    str_contains($upperTargetProyek, 'INTER') ||
                                                    str_contains($upperTargetProyek, 'GLOBAL');
                                            @endphp
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="p-3 align-top">
                                                    <div class="font-bold text-slate-900 leading-snug">
                                                        {{ $targetItem->item_name }}</div>
                                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                                        <span
                                                            class="font-mono text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                                            SKU: {{ $targetItem->item_no }}
                                                        </span>
                                                        @if ($targetProyek !== '-')
                                                            <span
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wide border {{ $isTargetResmi ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ($isTargetInter ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-blue-100 text-blue-800 border-blue-200') }}">
                                                                Proyek: {{ $targetProyek }}
                                                            </span>
                                                        @endif
                                                        <span class="text-[10px] text-slate-500 font-medium">
                                                            (Target: {{ $targetItem->quantity_ordered }} Unit, Saat
                                                            ini: {{ $targetItem->quantity_received }} Unit)
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="p-3 align-top">
                                                    <select wire:model="itemMappings.{{ $targetItem->id }}"
                                                        class="w-full text-xs font-medium text-slate-800 bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
                                                        <option value="">-- Jangan Salin / Lewati Item Ini --
                                                        </option>
                                                        @foreach ($sourcePoObj->items as $sItem)
                                                            @php
                                                                $sProyek = $sItem->proyek ?? '-';
                                                                $sCount = $sItem->inspections->count();
                                                            @endphp
                                                            <option value="{{ $sItem->id }}">
                                                                [{{ $sProyek !== '-' ? $sProyek : 'NO PROYEK' }}]
                                                                {{ $sItem->item_name }} (SKU: {{ $sItem->item_no }}) -
                                                                {{ $sCount }} IMEI
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
                    <button type="button" wire:click="closeMigrateModal"
                        class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-100 transition-colors">
                        Batal
                    </button>

                    @if ($sourcePoObj)
                        <button type="button" wire:click="executeMigration" wire:loading.attr="disabled"
                            class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2 disabled:opacity-50">
                            <svg wire:loading.remove wire:target="executeMigration" class="w-4 h-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                                </path>
                            </svg>
                            <svg wire:loading wire:target="executeMigration" class="animate-spin w-4 h-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span>Proses Pindahkan IMEI</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
