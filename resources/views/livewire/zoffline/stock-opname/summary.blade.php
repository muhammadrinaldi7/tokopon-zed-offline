<div class="p-4 sm:p-6 max-w-7xl mx-auto pb-24">
    {{-- Breadcrumb & Back --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('zoffline.stock-opname.index') }}" wire:navigate
                class="p-2 bg-white border border-neutral-200 hover:bg-neutral-100 rounded-xl transition text-neutral-600 shadow-2xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                        Berita Acara & Rangkuman Audit Stok
                    </h2>
                    <span class="font-mono text-xs px-2.5 py-0.5 bg-neutral-100 text-neutral-700 font-bold rounded-lg">
                        {{ $opname->opname_number }}
                    </span>
                </div>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Cabang: <span class="font-bold text-neutral-700">{{ $opname->branch->name }}</span> | 
                    Gudang: <span class="font-bold text-neutral-700">{{ $opname->warehouse->name }}</span> | 
                    Pelaksana: <span class="font-bold text-neutral-700">{{ $opname->user->name }}</span>
                </p>
            </div>
        </div>

        {{-- Quick Export & Print Actions --}}
        <div class="flex items-center gap-2">
            @if($opname->status === 'COUNTING')
                <button wire:click="reopenForCounting"
                    class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                    <span>Lanjutkan Hitung</span>
                </button>
            @endif

            @if($opname->status === 'REJECTED')
                <button wire:click="reopenForCounting"
                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Buka Sesi & Hitung Ulang</span>
                </button>
            @endif

            <a href="{{ route('zoffline.stock-opname.pdf', $opname->id) }}" target="_blank"
                class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Cetak Berita Acara PDF</span>
            </a>

            <a href="{{ route('zoffline.stock-opname.export-excel', $opname->id) }}"
                class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    <div class="mb-5">
        @if($opname->status === 'COMPLETED' || $opname->status === 'APPROVED')
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4.5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-emerald-900">Laporan Stock Opname Disahkan & Selesai</h4>
                    <p class="text-xs text-emerald-700 mt-0.5">
                        Hasil audit fisik telah diverifikasi oleh manajemen. Dokumen Berita Acara resmi dapat diunduh kapan saja sebagai arsip audit internal.
                    </p>
                </div>
            </div>
        @elseif($opname->status === 'PENDING_APPROVAL')
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4.5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-900">Menunggu Persetujuan Manajemen</h4>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Pengajuan Berita Acara sedang ditinjau oleh Manajer Operasional / Direktur pada menu Persetujuan Transaksi.
                        @if($latestApproval)
                            (Level: {{ $latestApproval->current_level }}/{{ $latestApproval->required_level }})
                        @endif
                    </p>
                </div>
            </div>
        @elseif($opname->status === 'REJECTED')
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4.5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-red-900">Laporan Stock Opname Ditolak</h4>
                    <p class="text-xs text-red-700 mt-0.5">
                        Manajemen meminta penghitungan ulang atau perbaikan keterangan Berita Acara. Klik "Buka Sesi & Hitung Ulang" untuk merevisi.
                    </p>
                </div>
            </div>
        @else
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4.5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-[#4E44DB] flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-blue-900">Sesi Masih Dalam Tahap Penghitungan</h4>
                    <p class="text-xs text-blue-700 mt-0.5">
                        Periksa rincian selisih di bawah, isi keterangan Berita Acara penjelasan, lalu klik tombol "Kirim Laporan / Ajukan Pengesahan".
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Total Stok Buku</span>
            <span class="text-xl font-bold text-neutral-900 font-mono">{{ number_format($opname->total_system_qty) }}</span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">Unit di sistem</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Total Fisik Riil</span>
            <span class="text-xl font-bold text-blue-600 font-mono">{{ number_format($opname->total_physical_qty) }}</span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">Dihitung fisik</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Selisih Kuantitas</span>
            <span class="text-xl font-bold font-mono {{ $opname->total_difference_qty == 0 ? 'text-green-600' : ($opname->total_difference_qty < 0 ? 'text-red-600' : 'text-amber-600') }}">
                {{ $opname->total_difference_qty > 0 ? '+' : '' }}{{ number_format($opname->total_difference_qty) }}
            </span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">
                {{ $opname->total_difference_qty == 0 ? 'Cocok Sempurna' : ($opname->total_difference_qty < 0 ? 'Selisih Kurang' : 'Selisih Lebih') }}
            </span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Estimasi Kerugian (Loss)</span>
            <span class="text-lg font-bold text-red-600 font-mono block truncate" title="Rp {{ number_format($opname->total_loss_value, 0, ',', '.') }}">
                Rp {{ number_format($opname->total_loss_value, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">HPP unit missing/hilang</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-2xs">
            <span class="text-[10px] text-neutral-400 uppercase font-bold block">Estimasi Surplus</span>
            <span class="text-lg font-bold text-amber-600 font-mono block truncate" title="Rp {{ number_format($opname->total_surplus_value, 0, ',', '.') }}">
                Rp {{ number_format($opname->total_surplus_value, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-neutral-400 block mt-0.5">HPP barang nyasar/lebih</span>
        </div>
    </div>

    {{-- Detail Selisih Section --}}
    <div class="space-y-6 mb-6">
        @if($discrepancyItems->isEmpty() && $missingSerials->isEmpty() && $unexpectedSerials->isEmpty())
            <div class="bg-green-50/70 border border-green-200 rounded-2xl p-8 text-center">
                <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-green-900">Stok Cabang 100% Cocok!</h3>
                <p class="text-xs text-green-700 max-w-md mx-auto mt-1">
                    Seluruh kuantitas fisik dan nomor seri/IMEI di gudang cabang ini sesuai secara sempurna dengan pencatatan buku sistem. Tidak ada barang hilang maupun nyasar.
                </p>
            </div>
        @else
            {{-- Tabel 1: Produk yang Memiliki Selisih --}}
            @if($discrepancyItems->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
                    <div class="p-4.5 border-b border-neutral-100 bg-neutral-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm">Daftar Produk yang Mengalami Selisih ({{ $discrepancyItems->count() }} SKU)</h3>
                            <p class="text-xs text-neutral-500">Perbandingan kuantitas buku vs hasil fisik riil</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm border-collapse">
                            <thead>
                                <tr class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                                    <th class="px-5 py-3">Nama Produk / SKU</th>
                                    <th class="px-5 py-3">Tipe</th>
                                    <th class="px-5 py-3 text-center">Buku</th>
                                    <th class="px-5 py-3 text-center">Fisik</th>
                                    <th class="px-5 py-3 text-center">Selisih</th>
                                    <th class="px-5 py-3">Nilai HPP / Unit</th>
                                    <th class="px-5 py-3">Total Nilai Selisih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100 text-neutral-700">
                                @foreach($discrepancyItems as $dItem)
                                    <tr class="hover:bg-neutral-50">
                                        <td class="px-5 py-3.5">
                                            <span class="font-bold text-gray-900 block text-xs">{{ $dItem->product_name }}</span>
                                            <span class="text-[10px] text-gray-400 font-mono">{{ $dItem->item_no }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            @if($dItem->is_serialized)
                                                <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold rounded">IMEI</span>
                                            @else
                                                <span class="px-2 py-0.5 bg-orange-50 text-orange-700 border border-orange-200 text-[10px] font-bold rounded">Aksesoris</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-center font-mono text-neutral-600">{{ $dItem->system_qty }}</td>
                                        <td class="px-5 py-3.5 text-center font-mono font-bold text-neutral-900">{{ $dItem->physical_qty }}</td>
                                        <td class="px-5 py-3.5 text-center font-mono font-bold">
                                            @if($dItem->difference_qty < 0)
                                                <span class="text-red-600">{{ $dItem->difference_qty }}</span>
                                            @else
                                                <span class="text-amber-600">+{{ $dItem->difference_qty }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-xs font-mono text-neutral-600">
                                            Rp {{ number_format($dItem->unit_cost, 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3.5 text-xs font-mono font-bold whitespace-nowrap">
                                            @if($dItem->difference_value < 0)
                                                <span class="text-red-600">- Rp {{ number_format(abs($dItem->difference_value), 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-amber-600">+ Rp {{ number_format($dItem->difference_value, 0, ',', '.') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Tabel 2: IMEI yang Hilang (Missing Serials) --}}
            @if($missingSerials->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-red-200 overflow-hidden">
                    <div class="p-4.5 border-b border-red-100 bg-red-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-red-900 text-sm flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Daftar IMEI Tidak Ditemukan / Hilang ({{ $missingSerials->count() }} Unit)</span>
                            </h3>
                            <p class="text-xs text-red-700">Tercatat di sistem cabang, namun fisik tidak ditemukan saat pemindaian</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-64">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                                    <th class="px-5 py-2.5">Nomor IMEI / Seri</th>
                                    <th class="px-5 py-2.5">Produk</th>
                                    <th class="px-5 py-2.5">SKU</th>
                                    <th class="px-5 py-2.5">HPP Unit</th>
                                    <th class="px-5 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100 text-neutral-700 font-mono">
                                @foreach($missingSerials as $mSn)
                                    <tr class="hover:bg-red-50/30">
                                        <td class="px-5 py-2.5 font-bold text-red-700">{{ $mSn->serial_number }}</td>
                                        <td class="px-5 py-2.5 font-sans text-gray-800">{{ $mSn->stockOpnameItem->product_name ?? '-' }}</td>
                                        <td class="px-5 py-2.5 text-neutral-500">{{ $mSn->item_no }}</td>
                                        <td class="px-5 py-2.5 text-red-600 font-bold">Rp {{ number_format($mSn->hpp, 0, ',', '.') }}</td>
                                        <td class="px-5 py-2.5 font-sans">
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded">
                                                Hilang / Missing
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Tabel 3: IMEI Nyasar (Unexpected Serials) --}}
            @if($unexpectedSerials->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-amber-200 overflow-hidden">
                    <div class="p-4.5 border-b border-amber-100 bg-amber-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-amber-900 text-sm flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Daftar IMEI Nyasar / Tidak Terdaftar di Cabang Ini ({{ $unexpectedSerials->count() }} Unit)</span>
                            </h3>
                            <p class="text-xs text-amber-700">Fisik ditemukan dan discan di etalase, tetapi di sistem belum terdaftar di cabang ini</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-64">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                                    <th class="px-5 py-2.5">Nomor IMEI / Seri</th>
                                    <th class="px-5 py-2.5">Produk</th>
                                    <th class="px-5 py-2.5">SKU</th>
                                    <th class="px-5 py-2.5">HPP Estimasi</th>
                                    <th class="px-5 py-2.5">Catatan Investigasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100 text-neutral-700 font-mono">
                                @foreach($unexpectedSerials as $uSn)
                                    <tr class="hover:bg-amber-50/30">
                                        <td class="px-5 py-2.5 font-bold text-amber-800">{{ $uSn->serial_number }}</td>
                                        <td class="px-5 py-2.5 font-sans text-gray-800">{{ $uSn->stockOpnameItem->product_name ?? '-' }}</td>
                                        <td class="px-5 py-2.5 text-neutral-500">{{ $uSn->item_no }}</td>
                                        <td class="px-5 py-2.5 text-amber-700 font-bold">Rp {{ number_format($uSn->hpp, 0, ',', '.') }}</td>
                                        <td class="px-5 py-2.5 font-sans text-neutral-600 text-[11px]">{{ $uSn->notes ?? 'Fisik ditemukan saat audit' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Form Berita Acara & Pengajuan --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
        <div class="p-5 border-b border-neutral-100 bg-neutral-50/50">
            <h3 class="font-bold text-gray-800 text-sm">Catatan Berita Acara Stock Opname</h3>
            <p class="text-xs text-neutral-500">Wajib diisi oleh Branch Manager (BM / BM GSK) sebagai keterangan resmi alasan selisih fisik.</p>
        </div>

        <div class="p-6">
            @if($opname->status === 'COUNTING')
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2">
                            Keterangan / Alasan Selisih Fisik
                            @if($opname->total_difference_qty != 0)
                                <span class="text-red-500">* (Wajib diisi jika terdapat selisih)</span>
                            @endif
                        </label>
                        <textarea wire:model="notes" rows="4"
                            placeholder="Tuliskan penjelasan mengenai barang hilang, barang rusak, atau barang nyasar. Contoh: 1 unit LCD pecah di etalase, 1 unit IMEI ... tertukar dengan cabang Veteran..."
                            class="w-full px-4 py-3 bg-white border border-neutral-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"></textarea>
                        @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex items-center justify-between">
                        <button wire:click="reopenForCounting"
                            class="px-4 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-xl text-xs sm:text-sm font-semibold transition cursor-pointer">
                            ← Kembali ke Scanner
                        </button>

                        <button wire:click="submitOpname" wire:loading.attr="disabled"
                            class="px-6 py-2.5 bg-[#4E44DB] hover:bg-[#3d34b3] text-white rounded-xl shadow-md hover:shadow-lg transition font-semibold text-xs sm:text-sm flex items-center gap-2 cursor-pointer">
                            <span wire:loading.remove>
                                {{ $opname->total_difference_qty == 0 ? 'Selesaikan Opname (100% Cocok)' : 'Kirim Laporan & Ajukan Pengesahan' }}
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            @else
                <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200">
                    <span class="text-[10px] text-neutral-400 font-bold uppercase block mb-1">Isi Berita Acara yang Diajukan:</span>
                    <p class="text-xs sm:text-sm text-neutral-800 leading-relaxed whitespace-pre-line font-serif">
                        {{ $opname->notes ?: 'Tidak ada catatan berita acara.' }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
