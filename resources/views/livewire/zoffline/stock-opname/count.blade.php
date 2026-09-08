<div class="p-4 sm:p-6 max-w-7xl mx-auto pb-28">
    {{-- Header Workstation --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('zoffline.stock-opname.index') }}" wire:navigate
                    class="p-2 bg-white border border-neutral-200 hover:bg-neutral-100 rounded-xl transition text-neutral-600 shadow-2xs"
                    title="Jeda & Kembali ke Riwayat">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight flex items-center gap-2">
                        Workstation Penghitungan Fisik
                        <span class="px-2.5 py-0.5 bg-blue-100 text-[#4E44DB] text-xs font-bold rounded-lg font-mono">
                            {{ $opname->opname_number }}
                        </span>
                    </h2>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Cabang: <span class="font-bold text-neutral-700">{{ $opname->branch->name }}</span> | 
                        Gudang: <span class="font-bold text-neutral-700">{{ $opname->warehouse->name }}</span> | 
                        Dimulai: <span class="text-neutral-600">{{ $opname->start_time->format('d M Y, H:i') }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="proceedToSummary"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md hover:shadow-lg transition font-semibold text-xs sm:text-sm flex items-center gap-2 cursor-pointer">
                <span>Selesai & Buat Berita Acara</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Progress & KPI Overview --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Total Stok Buku</span>
            <span class="text-xl font-bold text-neutral-800 font-mono">{{ number_format($opname->total_system_qty) }}</span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">Snapshot awal sesi</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Total Fisik Terhitung</span>
            <span class="text-xl font-bold text-blue-600 font-mono">{{ number_format($opname->total_physical_qty) }}</span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">Sudah discan / dihitung</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Selisih Kuantitas</span>
            <span class="text-xl font-bold font-mono {{ $opname->total_difference_qty == 0 ? 'text-green-600' : ($opname->total_difference_qty < 0 ? 'text-red-600' : 'text-amber-600') }}">
                {{ $opname->total_difference_qty > 0 ? '+' : '' }}{{ number_format($opname->total_difference_qty) }}
            </span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">Fisik vs Buku</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">IMEI Terverifikasi</span>
            <div class="flex items-center gap-2 mt-0.5">
                <span class="text-xl font-bold text-neutral-900 font-mono">{{ $serialStats['matched'] }}</span>
                <span class="text-xs text-neutral-400 font-mono">/ {{ $serialStats['total'] }}</span>
            </div>
            @if($serialStats['unexpected'] > 0)
                <span class="text-[10px] text-red-600 font-bold block mt-0.5">
                    +{{ $serialStats['unexpected'] }} Barang Nyasar
                </span>
            @else
                <span class="text-[11px] text-neutral-400 block mt-0.5">Semua SN valid</span>
            @endif
        </div>
    </div>

    {{-- Scanner Section (Active Barcode Area) --}}
    <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-neutral-900 rounded-2xl p-5 mb-5 shadow-lg text-white">
        <div class="max-w-2xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs text-blue-200 mb-2">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                Scanner Aktif & Siap Menerima Input
            </div>
            <h3 class="text-lg font-bold mb-3">Pindai Barcode / Input IMEI Handphone</h3>

            <form wire:submit.prevent="processScan" class="relative max-w-xl mx-auto">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <input type="text"
                        wire:model="barcodeScan"
                        autofocus
                        id="barcodeScannerInput"
                        placeholder="Arahkan Barcode Scanner atau Ketik IMEI..."
                        class="w-full pl-12 pr-28 py-3.5 bg-white/95 text-gray-900 placeholder-gray-400 rounded-xl text-sm font-mono tracking-wider focus:ring-4 focus:ring-blue-400 focus:bg-white outline-none shadow-inner font-semibold">
                    <button type="submit"
                        class="absolute right-2 px-4 py-2 bg-[#4E44DB] hover:bg-[#3d34b3] text-white rounded-lg text-xs font-bold transition">
                        Verifikasi
                    </button>
                </div>
            </form>

            {{-- Scan Alert Display --}}
            @if($scanAlert)
                <div class="mt-3 inline-block px-4 py-2 rounded-xl text-xs font-semibold {{ $scanAlert['type'] === 'success' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($scanAlert['type'] === 'warning' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30') }}">
                    {{ $scanAlert['message'] }}
                </div>
            @endif

            @if($lastScannedItem)
                <div class="mt-2 text-xs text-blue-200">
                    Terakhir discan: <span class="font-bold text-white">{{ $lastScannedItem['name'] }}</span> (<span class="font-mono">{{ $lastScannedItem['sn'] }}</span>) - 
                    <span class="font-bold {{ $lastScannedItem['status'] === 'MATCHED' ? 'text-emerald-400' : 'text-red-400' }}">{{ $lastScannedItem['status_label'] }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Workstation Table Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
        {{-- Navigation Tabs & Search --}}
        <div class="p-4 border-b border-neutral-100 bg-neutral-50/50 flex flex-col md:flex-row gap-3 justify-between items-center">
            {{-- Tabs --}}
            <div class="flex items-center gap-1.5 p-1 bg-neutral-200/60 rounded-xl text-xs font-semibold w-full md:w-auto overflow-x-auto">
                <button wire:click="$set('activeTab', 'ALL')"
                    class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'ALL' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}">
                    Semua Item ({{ $items->count() }})
                </button>
                <button wire:click="$set('activeTab', 'SERIALIZED')"
                    class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'SERIALIZED' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}">
                    Unit HP / IMEI
                </button>
                <button wire:click="$set('activeTab', 'NON_SERIALIZED')"
                    class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'NON_SERIALIZED' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}">
                    Aksesoris
                </button>
                <button wire:click="$set('activeTab', 'DISCREPANCY')"
                    class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'DISCREPANCY' ? 'bg-white text-red-600 shadow-xs font-bold' : 'text-neutral-600 hover:text-neutral-900' }}">
                    Selisih Saja
                </button>
            </div>

            {{-- Search Bar --}}
            <div class="relative w-full md:w-64">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Filter nama produk / SKU..."
                    class="w-full pl-8 pr-3 py-1.5 bg-white border border-neutral-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                <svg class="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        {{-- Table Items --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-neutral-50 text-[11px] font-bold text-neutral-500 uppercase tracking-wider border-b border-neutral-200">
                        <th class="px-5 py-3.5">Produk / SKU</th>
                        <th class="px-5 py-3.5">Tipe</th>
                        <th class="px-5 py-3.5 text-center">Buku (Snapshot)</th>
                        <th class="px-5 py-3.5 text-center">Fisik Riil</th>
                        <th class="px-5 py-3.5 text-center">Selisih Qty</th>
                        <th class="px-5 py-3.5 text-center">Rincian Fisik / Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 text-neutral-700">
                    @forelse($items as $item)
                        <tr class="hover:bg-neutral-50/80 transition-colors {{ $item->difference_qty != 0 ? 'bg-red-50/20' : '' }}">
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-gray-900 block text-xs sm:text-sm">{{ $item->product_name }}</span>
                                <span class="text-[11px] text-gray-400 font-mono">{{ $item->item_no }}</span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($item->is_serialized)
                                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold rounded">
                                        IMEI (HP)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-orange-50 text-orange-700 border border-orange-200 text-[10px] font-bold rounded">
                                        Aksesoris
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap font-mono font-bold text-neutral-600">
                                {{ number_format($item->system_qty) }}
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                @if($item->is_serialized)
                                    <span class="text-sm font-bold font-mono {{ $item->physical_qty === $item->system_qty ? 'text-green-600' : 'text-blue-600' }}">
                                        {{ number_format($item->physical_qty) }}
                                    </span>
                                @else
                                    {{-- Quick Counter untuk Aksesoris --}}
                                    <div class="inline-flex items-center gap-1.5 bg-neutral-100 p-1 rounded-xl border border-neutral-200">
                                        <button wire:click="decrementQty({{ $item->id }}, 1)"
                                            class="w-7 h-7 bg-white hover:bg-neutral-200 text-neutral-700 rounded-lg flex items-center justify-center font-bold shadow-2xs transition">
                                            -
                                        </button>
                                        <input type="number" min="0"
                                            value="{{ $item->physical_qty }}"
                                            wire:change="updatePhysicalQty({{ $item->id }}, $event.target.value)"
                                            class="w-14 text-center py-1 bg-white border border-neutral-200 rounded-lg font-mono font-bold text-xs outline-none">
                                        <button wire:click="incrementQty({{ $item->id }}, 1)"
                                            class="w-7 h-7 bg-white hover:bg-neutral-200 text-neutral-700 rounded-lg flex items-center justify-center font-bold shadow-2xs transition">
                                            +
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap font-mono font-bold">
                                @if($item->difference_qty == 0)
                                    <span class="text-green-600">0 (Cocok)</span>
                                @elseif($item->difference_qty < 0)
                                    <span class="text-red-600">{{ $item->difference_qty }}</span>
                                @else
                                    <span class="text-amber-600">+{{ $item->difference_qty }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                @if($item->is_serialized)
                                    <button wire:click="openSerialModal({{ $item->id }})"
                                        class="px-3 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-semibold rounded-lg transition flex items-center gap-1.5 mx-auto cursor-pointer">
                                        <span>Daftar IMEI</span>
                                        <span class="px-1.5 py-0.2 bg-white text-neutral-800 text-[10px] font-mono rounded font-bold border border-neutral-200">
                                            {{ $item->matched_count }}/{{ $item->system_qty }}
                                        </span>
                                    </button>
                                @else
                                    <span class="text-[11px] text-neutral-400">Input Kuantitas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-neutral-400">
                                Tidak ada item ditemukan sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Serial Numbers Detail Modal --}}
    @if($showSerialModal && $selectedItemForSerials)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" wire:click="closeSerialModal"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden border border-neutral-200 z-10">
                <div class="p-5 border-b border-neutral-100 flex items-center justify-between bg-neutral-50/50">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">{{ $selectedItemForSerials->product_name }}</h3>
                        <p class="text-xs text-neutral-500 font-mono">SKU: {{ $selectedItemForSerials->item_no }}</p>
                    </div>
                    <button wire:click="closeSerialModal" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5 max-h-96 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                                <th class="px-3 py-2">Nomor IMEI / Seri</th>
                                <th class="px-3 py-2 text-center">Status Verifikasi</th>
                                <th class="px-3 py-2">Waktu Scan</th>
                                <th class="px-3 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 text-neutral-700 font-mono">
                            @forelse($selectedItemForSerials->serials as $sn)
                                <tr>
                                    <td class="px-3 py-2.5 font-bold text-gray-900">{{ $sn->serial_number }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        @if($sn->status === 'MATCHED')
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold rounded">
                                                ✓ Cocok
                                            </span>
                                        @elseif($sn->status === 'MISSING')
                                            <span class="px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold rounded">
                                                Belum Discan (Missing)
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold rounded">
                                                ⚠ Barang Nyasar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-neutral-500 text-[11px]">
                                        {{ $sn->scanned_at ? $sn->scanned_at->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-neutral-500 text-[11px] font-sans">
                                        {{ $sn->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-neutral-400">Belum ada serial number tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-neutral-100 bg-neutral-50/50 flex justify-end">
                    <button wire:click="closeSerialModal" class="px-4 py-2 bg-neutral-200 hover:bg-neutral-300 text-neutral-700 rounded-xl text-xs font-bold transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Script Audio Feedback Synthesizer (Tanpa file audio eksternal) --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

            Livewire.on('play-scan-sound', (event) => {
                const type = event.type || 'success';
                const now = audioCtx.currentTime;

                if (type === 'success') {
                    // Beep nada tinggi ganda (ting-ting)
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, now); // A5
                    osc.frequency.setValueAtTime(1174.66, now + 0.08); // D6
                    gain.gain.setValueAtTime(0.15, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(now);
                    osc.stop(now + 0.2);
                } else if (type === 'warning') {
                    // Beep nada sedang datar
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(440, now);
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(now);
                    osc.stop(now + 0.3);
                } else if (type === 'error') {
                    // Buzzer nada rendah ganda (tet-tet)
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, now);
                    osc.frequency.setValueAtTime(160, now + 0.12);
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(now);
                    osc.stop(now + 0.35);
                }

                // Kembalikan fokus ke input scanner otomatis
                setTimeout(() => {
                    const input = document.getElementById('barcodeScannerInput');
                    if (input) input.focus();
                }, 100);
            });
        });
    </script>
</div>
