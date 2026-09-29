<div class="max-w-7xl mx-auto p-3 md:p-6 min-h-screen space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200">
                    Operasional Cabang
                </span>
                <span class="text-xs text-gray-500 font-mono">Store Expense & Maintenance</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 mt-1">Pemakaian Inventaris Toko</h1>
            <p class="text-sm text-gray-500">Pencatatan & pengajuan pengeluaran barang untuk pemeliharaan unit display
                dan kebutuhan operasional toko.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openCreateModal"
                class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 transition-all text-white px-5 py-2.5 rounded-xl font-bold shadow-sm hover:shadow-md flex items-center gap-2 text-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Ajukan Pemakaian Inventaris
            </button>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari Penyesuaian / SKU / Barang</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari No. ADJ, SKU, Nama Barang, Alasan..."
                        class="w-full pl-9 pr-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            {{-- Filter Status --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status Approval</label>
                <select wire:model.live="filterStatus"
                    class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-lg outline-none focus:border-blue-500 cursor-pointer">
                    <option value="ALL">Semua Status</option>
                    <option value="PENDING">⏳ Menunggu Approval</option>
                    <option value="APPROVED">✅ Disetujui (Siap Sync)</option>
                    <option value="SYNCED">🎉 Berhasil Sync Accurate</option>
                    <option value="FAILED_SYNC">⚠️ Gagal Sync Accurate</option>
                    <option value="REJECTED">❌ Ditolak</option>
                </select>
            </div>

            {{-- Filter Tipe --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe Mutasi</label>
                <select wire:model.live="filterType"
                    class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-lg outline-none focus:border-blue-500 cursor-pointer">
                    <option value="ALL">Semua Tipe</option>
                    <option value="OUT"> Pengurangan (Keluar)</option>
                    <option value="IN">🟢 Penambahan (Masuk)</option>
                </select>
            </div>

            {{-- Filter Gudang --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Gudang</label>
                <select wire:model.live="filterWarehouseId"
                    class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-lg outline-none focus:border-blue-500 cursor-pointer">
                    <option value="ALL">Semua Gudang</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat Penyesuaian --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/70 text-gray-500 text-[11px] uppercase font-bold tracking-wider text-left">
                    <tr>
                        <th class="px-6 py-4">Nomor & Tanggal</th>
                        <th class="px-6 py-4">Rincian Barang (Detail)</th>
                        <th class="px-6 py-4">Total Item & Qty</th>
                        <th class="px-6 py-4">Tujuan Alokasi Display</th>
                        <th class="px-6 py-4">Gudang & Cabang</th>
                        <th class="px-6 py-4">Status & Accurate</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse ($adjustments as $adj)
                        @php
                            $itemCount = $adj->total_items ?: ($adj->items->count() ?: 1);
                            $totalQty = $adj->total_quantity ?: ($adj->items->sum('quantity') ?: $adj->quantity);
                            $firstItem = $adj->items->first();
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            {{-- Nomor & Tanggal --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="font-bold text-gray-900 font-mono text-xs">{{ $adj->adjustment_number }}</span>
                                <div class="text-[11px] text-gray-500 mt-0.5">
                                    {{ $adj->created_at->format('d M Y, H:i') }} WIB
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    Oleh: <strong
                                        class="text-gray-600">{{ $adj->requestedBy->name ?? 'Staff' }}</strong>
                                </div>
                            </td>

                            {{-- Barang Disesuaikan --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900 max-w-xs truncate"
                                        title="{{ $firstItem?->product_name ?? $adj->product_name }}">
                                        {{ $firstItem?->product_name ?? $adj->product_name }}
                                    </span>
                                    @if ($itemCount > 1)
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 whitespace-nowrap">
                                            +{{ $itemCount - 1 }} item lain
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                    SKU: <strong
                                        class="text-gray-700">{{ $firstItem?->item_no ?? $adj->item_no }}</strong>
                                    @if (!empty($firstItem?->proyek) || !empty($adj->proyek))
                                        <span
                                            class="ml-1 text-indigo-600 font-sans font-semibold">[{{ $firstItem?->proyek ?? $adj->proyek }}]</span>
                                    @endif
                                </div>
                                @if ($adj->notes)
                                    <div class="text-[11px] text-gray-500 italic mt-1 max-w-xs truncate"
                                        title="{{ $adj->notes }}">
                                        "{{ $adj->notes }}"
                                    </div>
                                @endif
                            </td>

                            {{-- Tipe & Total Qty --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold {{ $adj->adjustment_type === 'OUT' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                        {{ $adj->adjustment_type === 'OUT' ? ' Keluar' : '🟢 Masuk' }}
                                    </span>
                                    <span class="font-extrabold text-gray-900 text-xs font-mono">
                                        {{ $totalQty }} pcs
                                    </span>
                                </div>
                                <div class="text-[10px] text-gray-500 mt-1">
                                    <span class="font-bold text-gray-700">{{ $itemCount }}</span> Jenis Barang
                                </div>
                                <div class="text-[9px] text-gray-400 uppercase font-semibold mt-0.5">
                                    {{ str_replace('_', ' ', $adj->reason_category) }}
                                </div>
                            </td>

                            {{-- Tujuan Alokasi --}}
                            <td class="px-6 py-4">
                                @php
                                    $targets = $firstItem?->target_items_list ?? [];
                                    $targetName = $firstItem?->target_product_name ?? $adj->target_product_name;
                                    $targetSku = $firstItem?->target_item_no ?? $adj->target_item_no;
                                    $targetSn = $firstItem?->target_serial_number ?? $adj->target_serial_number;
                                @endphp
                                @if (!empty($targets))
                                    <div class="space-y-1 max-w-xs">
                                        @foreach (array_slice($targets, 0, 2) as $tgt)
                                            <div
                                                class="bg-amber-50/80 border border-amber-200/60 p-1.5 rounded-lg text-[11px]">
                                                <div class="font-bold text-amber-900 truncate"
                                                    title="{{ $tgt['product_name'] ?? $tgt['item_no'] }}">
                                                    <span
                                                        class="font-black text-[10px] bg-amber-200/90 text-amber-900 px-1 py-0.2 rounded font-mono mr-1">
                                                        {{ $tgt['quantity'] ?? 1 }}x
                                                    </span>
                                                    {{ $tgt['product_name'] ?? $tgt['item_no'] }}
                                                </div>
                                                <div class="text-[10px] text-amber-700 font-mono">
                                                    {{ $tgt['item_no'] }}
                                                    @if (!empty($tgt['serial_number']))
                                                        | SN: {{ $tgt['serial_number'] }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @if (count($targets) > 2)
                                            <span class="text-[10px] text-amber-800 font-bold block pl-1">
                                                {{ count($targets) - 2 }} unit lainnya</span>
                                        @endif
                                    </div>
                                @elseif($targetSku || $targetName)
                                    <div class="bg-amber-50/80 border border-amber-200/60 p-2 rounded-lg max-w-xs">
                                        <div class="font-bold text-amber-900 text-[11px] truncate"
                                            title="{{ $targetName }}">
                                            {{ $targetName ?: '-' }}
                                        </div>
                                        <div class="text-[10px] text-amber-700 font-mono mt-0.5">
                                            SKU: {{ $targetSku }}
                                        </div>
                                        @if ($targetSn)
                                            <div class="text-[10px] text-amber-800 font-mono mt-0.5">
                                                SN: {{ $targetSn }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs italic">- Tanpa Alokasi -</span>
                                @endif
                            </td>

                            {{-- Gudang & Cabang --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-800 font-medium">
                                    {{ $adj->warehouse->name ?? ($adj->warehouse_name ?? '-') }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $adj->branch->name ?? 'Semua Cabang' }}
                                </div>
                            </td>

                            {{-- Status & Accurate --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    @if ($adj->status === 'PENDING')
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Menunggu Approval
                                        </span>
                                    @elseif($adj->status === 'APPROVED')
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Approved (Siap
                                            Sync)
                                        </span>
                                    @elseif($adj->status === 'SYNCED')
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Synced
                                            Accurate
                                        </span>
                                    @elseif($adj->status === 'FAILED_SYNC')
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800"
                                            title="{{ $adj->sync_error }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal Sync
                                        </span>
                                    @elseif($adj->status === 'REJECTED')
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">
                                            Ditolak
                                        </span>
                                    @endif
                                </div>
                                @if ($adj->accurate_adjustment_no)
                                    <div class="text-[10px] text-gray-500 font-mono mt-1">
                                        No. ACC: <span
                                            class="font-semibold text-gray-800">{{ $adj->accurate_adjustment_no }}</span>
                                    </div>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($adj->status === 'FAILED_SYNC' || ($adj->status === 'APPROVED' && !$adj->accurate_adjustment_no))
                                        <button wire:click="retrySync({{ $adj->id }})"
                                            wire:loading.attr="disabled"
                                            class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold text-xs transition shadow-xs cursor-pointer flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                </path>
                                            </svg>
                                            Sync Ulang
                                        </button>
                                    @endif
                                    <button wire:click="viewDetail({{ $adj->id }})"
                                        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-xs transition cursor-pointer">
                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                    <span class="font-medium text-gray-500">Belum ada data penyesuaian stok.</span>
                                    <p class="text-xs text-gray-400">Klik tombol "Buat Penyesuaian Baru" untuk membuat
                                        permohonan baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($adjustments->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $adjustments->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL BUAT PENYESUAIAN BARU (HEADER + MULTI-ITEM) --}}
    @if ($showCreateModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div
                class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl border border-gray-100 overflow-hidden my-8 flex flex-col max-h-[90vh]">
                {{-- Header Modal --}}
                <div
                    class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 p-6 text-white flex items-center justify-between shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-white/20 text-white">
                                Dokumen Penyesuaian Baru
                            </span>
                            <span class="text-xs text-blue-200 font-mono">Multi-Item Entry</span>
                        </div>
                        <h2 class="text-xl font-bold mt-1">Form Pengajuan Penyesuaian Stok Persediaan</h2>
                        <p class="text-xs text-blue-100/80">Masukkan beberapa barang sekaligus dalam 1 pengajuan untuk
                            approval pimpinan & sinkronisasi Accurate.</p>
                    </div>
                    <button wire:click="closeCreateModal"
                        class="text-white/70 hover:text-white transition p-2 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Body Modal (Scrollable) --}}
                <div class="p-6 space-y-6 overflow-y-auto flex-1">
                    {{-- 1. INFORMASI HEADER DOKUMEN --}}
                    <div class="bg-gray-50/70 p-5 rounded-2xl border border-gray-200/80 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-xs font-black uppercase tracking-wider text-gray-700 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                1. Informasi Umum Dokumen
                            </h3>
                            <span class="text-[11px] text-gray-400">Aturan & Gudang Penyesuaian</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Gudang --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Gudang Penyesuaian <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="warehouse_id"
                                    class="w-full text-xs bg-white border border-gray-300 rounded-xl p-2.5 focus:border-blue-500 outline-none cursor-pointer font-medium">
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}">
                                            {{ $wh->name }}
                                            {{ Auth::user()->warehouse_id == $wh->id ? '⭐ (Gudang Anda)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')
                                    <span class="text-red-500 text-[11px] font-bold">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Kategori Alasan --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Kategori Alasan <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="reason_category"
                                    class="w-full text-xs bg-white border border-gray-300 rounded-xl p-2.5 focus:border-blue-500 outline-none cursor-pointer font-medium">
                                    <option value="PEMELIHARAAN_INVENTARIS">Pemeliharaan Inventaris / Unit Display
                                    </option>
                                    <option value="BARANG_RUSAK_DEFECT">Barang Rusak / Defect / Cacat Pabrik</option>
                                    <option value="SAMPLE_PROMOSI">Sample / Display Promosi Toko</option>
                                    <option value="SELISIH_OPNAME">Selisih Hasil Stock Opname</option>
                                    <option value="KOREKSI_STOK">Koreksi Administrasi / Salah Input</option>
                                    <option value="LAINNYA">Lain-lain</option>
                                </select>
                            </div>

                            {{-- Tipe Transaksi Tetap Pengeluaran --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Tipe Transaksi
                                </label>
                                <div
                                    class="py-2.5 px-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    Pengeluaran / Pemakaian Toko
                                </div>
                            </div>
                        </div>

                        {{-- Catatan Dokumen & Akun COA --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Keterangan / Alasan Pengajuan
                                </label>
                                <input type="text" wire:model="notes"
                                    placeholder="Misal: Pemeliharaan berkala penggantian antigores display toko cabang..."
                                    class="w-full text-xs p-2.5 bg-white border border-gray-300 rounded-xl outline-none focus:border-blue-500 font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Akun COA Accurate
                                </label>
                                <input type="text" wire:model="accurate_account_no" placeholder="5101"
                                    class="w-full text-xs font-mono font-bold p-2.5 bg-white border border-gray-300 rounded-xl text-center outline-none focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    {{-- 2. INPUT TAMBAH BARANG KE DAFTAR --}}
                    <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-200/80 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-xs font-black uppercase tracking-wider text-blue-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                                2. Tambah Barang ke Daftar Penyesuaian
                            </h3>
                            @if ($selectedItem)
                                <span
                                    class="text-xs text-blue-800 bg-blue-100 px-3 py-1 rounded-lg font-mono font-bold">
                                    Stok Gudang: <strong>{{ $temp_current_stock }} pcs</strong>
                                </span>
                            @endif
                        </div>

                        {{-- Baris 1: Pencarian Barang Utama & Qty --}}
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                            {{-- Cari Barang Utama --}}
                            <div class="md:col-span-6 relative">
                                <label class="block text-xs font-bold text-blue-900 mb-1">
                                    Barang yang Disesuaikan (SKU) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="searchItem"
                                        placeholder="Ketik SKU atau nama barang (misal: antigores, s26, case)..."
                                        class="w-full text-xs p-2.5 pr-8 bg-white border border-blue-300 rounded-xl focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none font-medium">
                                    <svg class="w-4 h-4 text-blue-400 absolute right-3 top-3" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>

                                {{-- Autocomplete Dropdown --}}
                                @if (!empty($itemSearchResults))
                                    <div
                                        class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-52 overflow-y-auto divide-y divide-gray-100">
                                        @foreach ($itemSearchResults as $res)
                                            <button type="button" wire:click="selectItem({{ $res['id'] }})"
                                                class="w-full text-left p-2.5 hover:bg-blue-50 transition flex items-center justify-between text-xs cursor-pointer">
                                                <div>
                                                    <div class="font-bold text-gray-800">{{ $res['name'] }}</div>
                                                    <div class="text-[11px] text-gray-500 font-mono">SKU:
                                                        {{ $res['item_no'] }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <span
                                                        class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[10px] font-bold">
                                                        Stok: {{ $res['stock'] ?? 0 }}
                                                    </span>
                                                    @if (!empty($res['proyek']))
                                                        <span
                                                            class="block text-[10px] text-blue-600 font-semibold mt-0.5">{{ $res['proyek'] }}</span>
                                                    @endif
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Arah Mutasi Baris Tetap Pengeluaran --}}
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-blue-900 mb-1">
                                    Jenis Mutasi
                                </label>
                                <div
                                    class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-bold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                    Pemakaian (Keluar)
                                </div>
                            </div>

                            {{-- Jumlah Qty --}}
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-blue-900 mb-1">
                                    Jumlah (Qty) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" min="1" wire:model="temp_quantity"
                                        class="w-full text-xs font-black p-2.5 bg-white border border-blue-300 rounded-xl focus:border-blue-500 outline-none">
                                    <span
                                        class="absolute right-3 top-2.5 text-[11px] text-gray-400 font-bold">pcs</span>
                                </div>
                            </div>
                        </div>

                        {{-- Info Barang Terpilih --}}
                        @if ($selectedItem)
                            <div
                                class="bg-white/90 p-3 rounded-xl border border-blue-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div>
                                    <span class="font-bold text-blue-950">{{ $temp_product_name }}</span>
                                    <span class="text-gray-500 font-mono text-[11px] ml-2">SKU:
                                        {{ $temp_item_no }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($temp_proyek)
                                        <span
                                            class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md text-[10px] font-bold border border-indigo-200">
                                            Proyek: {{ $temp_proyek }}
                                            {{ $temp_project_no ? "({$temp_project_no})" : '' }}
                                        </span>
                                    @endif
                                    @if ($temp_has_sn)
                                        <span
                                            class="px-2 py-0.5 bg-amber-50 text-amber-800 rounded-md text-[10px] font-bold border border-amber-200">
                                            Wajib SN / IMEI
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Baris 2: SKU Tujuan Alokasi (Bisa Multiple sampai Qty Barang) --}}
                        @php
                            $totalAllocated = array_sum(array_column($temp_target_items, 'quantity'));
                            $itemQty = (int) $temp_quantity;
                            $remainingQty = max(0, $itemQty - $totalAllocated);
                        @endphp
                        <div class="bg-amber-50/70 p-4 rounded-xl border border-amber-200 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <label class="block text-xs font-bold text-amber-950">
                                        Tujuan Alokasi / Unit Display <span
                                            class="text-gray-500 font-normal text-[11px]">(Opsional - berdasarkan
                                            kuantiti item)</span>
                                    </label>
                                    <span class="text-[10px] text-amber-800">
                                        Dapat dialokasikan hingga <strong>{{ $itemQty }}</strong> unit display
                                        (misal: penyesuaian {{ $itemQty }} antigores untuk {{ $itemQty }} HP
                                        display berbeda atau kurang).
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold font-mono border {{ $totalAllocated > $itemQty ? 'bg-rose-100 text-rose-800 border-rose-300' : ($totalAllocated == $itemQty && $itemQty > 0 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-900 border-amber-300') }}">
                                        <span>Teralokasi:</span>
                                        <strong>{{ $totalAllocated }} / {{ $itemQty }}</strong> Unit
                                    </span>
                                </div>
                            </div>

                            {{-- Form Tambah Unit Target --}}
                            <div
                                class="grid grid-cols-1 md:grid-cols-12 gap-2.5 items-end bg-white/80 p-3 rounded-xl border border-amber-200">
                                {{-- Cari Barang Target --}}
                                <div class="md:col-span-5 relative">
                                    <label
                                        class="block text-[10px] font-bold text-amber-900 uppercase tracking-wider mb-1">
                                        Pilih Unit Display (SKU / Nama HP)
                                    </label>
                                    <input type="text" wire:model.live.debounce.300ms="searchTargetItem"
                                        placeholder="Cari unit (misal: S26 Ultra LDU, Fold 6)..."
                                        class="w-full text-xs p-2 bg-white border border-amber-300 rounded-lg outline-none font-medium focus:border-amber-500">

                                    {{-- Autocomplete Dropdown Target --}}
                                    @if (!empty($targetItemSearchResults))
                                        <div
                                            class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-44 overflow-y-auto divide-y divide-gray-100">
                                            @foreach ($targetItemSearchResults as $res)
                                                <button type="button"
                                                    wire:click="selectTargetItem({{ $res['id'] }})"
                                                    class="w-full text-left p-2 hover:bg-amber-50 transition flex items-center justify-between text-xs cursor-pointer">
                                                    <div>
                                                        <div class="font-bold text-gray-800">{{ $res['name'] }}</div>
                                                        <div class="text-[10px] text-gray-500 font-mono">SKU:
                                                            {{ $res['item_no'] }}</div>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Qty Alokasi untuk unit ini --}}
                                <div class="md:col-span-2">
                                    <label
                                        class="block text-[10px] font-bold text-amber-900 uppercase tracking-wider mb-1">
                                        Qty Alokasi
                                    </label>
                                    <div class="relative">
                                        <input type="number" min="1" max="{{ max(1, $remainingQty) }}"
                                            wire:model="temp_target_quantity"
                                            class="w-full text-xs font-black p-2 bg-white border border-amber-300 rounded-lg outline-none text-center focus:border-amber-500">
                                        <span
                                            class="absolute right-2 top-2 text-[10px] text-gray-400 font-bold">unit</span>
                                    </div>
                                </div>

                                {{-- SN / IMEI Display --}}
                                <div class="md:col-span-3">
                                    <label
                                        class="block text-[10px] font-bold text-amber-900 uppercase tracking-wider mb-1">
                                        SN / IMEI <span class="text-gray-400 font-normal">(Opsional)</span>
                                    </label>
                                    <input type="text" wire:model="temp_target_serial_number"
                                        placeholder="Nomor Seri / IMEI..."
                                        class="w-full text-xs p-2 bg-white border border-amber-300 rounded-lg outline-none font-mono focus:border-amber-500">
                                </div>

                                {{-- Tombol Tambah ke List Alokasi --}}
                                <div class="md:col-span-2 flex items-center">
                                    <button type="button" wire:click="addTempTargetItem"
                                        @if (empty($temp_target_item_no) || $remainingQty <= 0) disabled @endif
                                        class="w-full py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer {{ empty($temp_target_item_no) || $remainingQty <= 0 ? 'bg-amber-200 text-amber-600 cursor-not-allowed' : 'bg-amber-600 hover:bg-amber-700 text-white shadow-xs' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Alokasi
                                    </button>
                                </div>
                            </div>

                            {{-- Selected Target Hint if not yet clicked + Alokasi --}}
                            @if ($temp_target_item_no && $remainingQty > 0)
                                <div
                                    class="flex items-center justify-between text-[11px] text-amber-900 bg-amber-100/90 px-3 py-1.5 rounded-lg font-medium border border-amber-300">
                                    <span>
                                        🎯 Dipilih: <strong>{{ $temp_target_product_name }}</strong> (SKU:
                                        {{ $temp_target_item_no }}) - klik <strong>"+ Alokasi"</strong> untuk
                                        menambahkan.
                                    </span>
                                    <button type="button" wire:click="clearTempTargetInput"
                                        class="text-rose-600 hover:text-rose-800 font-bold ml-2">Batal</button>
                                </div>
                            @endif

                            {{-- List of Added Target Units for this item --}}
                            @if (!empty($temp_target_items))
                                <div class="space-y-1.5 pt-1">
                                    <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider block">
                                        Daftar Unit Display yang Dialokasikan ({{ count($temp_target_items) }}
                                        unit/alokasi):
                                    </span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach ($temp_target_items as $tIdx => $ti)
                                            <div
                                                class="bg-white p-2.5 rounded-xl border border-amber-300 shadow-xs flex items-start justify-between gap-2">
                                                <div class="overflow-hidden">
                                                    <div class="flex items-center gap-1.5">
                                                        <span
                                                            class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 font-black text-[10px] font-mono shrink-0">
                                                            {{ $ti['quantity'] }}x
                                                        </span>
                                                        <span class="font-bold text-gray-900 text-xs truncate"
                                                            title="{{ $ti['product_name'] }}">
                                                            {{ $ti['product_name'] }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                                        SKU: {{ $ti['item_no'] }}
                                                        @if (!empty($ti['serial_number']))
                                                            <span
                                                                class="block text-amber-800 font-semibold truncate">SN:
                                                                {{ $ti['serial_number'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <button type="button"
                                                    wire:click="removeTempTargetItem({{ $tIdx }})"
                                                    class="text-rose-400 hover:text-rose-600 p-1 hover:bg-rose-50 rounded-md transition cursor-pointer"
                                                    title="Hapus unit alokasi ini">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Tombol Tambah ke Daftar --}}
                        <div class="flex justify-end pt-1">
                            <button type="button" wire:click="addItemToList"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Tambahkan Barang ke Daftar Pemakaian
                            </button>
                        </div>
                    </div>

                    {{-- 3. TABEL DAFTAR BARANG YANG AKAN DISESUAIKAN (KERANJANG MULTI-ITEM) --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-xs font-black uppercase tracking-wider text-gray-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                3. Daftar Barang yang Dikeluarkan untuk Pemakaian
                            </h3>
                            <span class="text-xs font-bold text-gray-600">
                                Total: <strong class="text-blue-700 font-black">{{ count($items) }}</strong> jenis
                                barang
                                (<strong
                                    class="text-emerald-700 font-black">{{ array_sum(array_column($items, 'quantity')) }}</strong>
                                unit/pcs)
                            </span>
                        </div>

                        @if (empty($items))
                            <div
                                class="p-8 bg-gray-50/80 rounded-2xl border-2 border-dashed border-gray-200 text-center text-gray-400">
                                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <p class="text-xs font-bold text-gray-500">Belum ada barang di daftar pemakaian.</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">Silakan pilih barang dan klik tombol "+
                                    Tambahkan Barang ke Daftar Pemakaian" di atas.</p>
                            </div>
                        @else
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead
                                        class="bg-gray-50/80 text-gray-500 font-bold uppercase text-[10px] tracking-wider text-left">
                                        <tr>
                                            <th class="px-4 py-3 text-center w-10">No</th>
                                            <th class="px-4 py-3">Barang Disesuaikan (SKU)</th>
                                            <th class="px-4 py-3 text-center">Arah Mutasi</th>
                                            <th class="px-4 py-3 text-center w-28">Kuantitas</th>
                                            <th class="px-4 py-3">Tujuan Alokasi (Aset / Display)</th>
                                            <th class="px-4 py-3 text-right w-16">Hapus</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($items as $idx => $it)
                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                <td class="px-4 py-3 text-center font-bold text-gray-400">
                                                    {{ $idx + 1 }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-gray-900">{{ $it['product_name'] }}
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 font-mono">
                                                        SKU: <span
                                                            class="text-gray-700 font-semibold">{{ $it['item_no'] }}</span>
                                                        @if (!empty($it['proyek']))
                                                            <span
                                                                class="ml-1 text-indigo-600 font-sans font-semibold">[{{ $it['proyek'] }}]</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                                    <span
                                                        class="inline-block px-2.5 py-1 rounded-full text-[10px] font-black {{ $it['adjustment_type'] === 'OUT' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                                        {{ $it['adjustment_type'] === 'OUT' ? ' Keluar' : '🟢 Masuk' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                                    <div
                                                        class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-lg p-1">
                                                        <button type="button"
                                                            wire:click="updateItemQuantity({{ $idx }}, {{ max(1, $it['quantity'] - 1) }})"
                                                            class="w-6 h-6 rounded bg-white hover:bg-gray-100 text-gray-700 font-black shadow-xs flex items-center justify-center cursor-pointer text-xs">
                                                            -
                                                        </button>
                                                        <span
                                                            class="w-8 text-center font-black text-xs text-gray-800 font-mono">
                                                            {{ $it['quantity'] }}
                                                        </span>
                                                        <button type="button"
                                                            wire:click="updateItemQuantity({{ $idx }}, {{ $it['quantity'] + 1 }})"
                                                            class="w-6 h-6 rounded bg-white hover:bg-gray-100 text-gray-700 font-black shadow-xs flex items-center justify-center cursor-pointer text-xs">
                                                            +
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if (!empty($it['target_items']) && count($it['target_items']) > 0)
                                                        <div class="space-y-1.5 max-w-xs">
                                                            @foreach ($it['target_items'] as $tgt)
                                                                <div
                                                                    class="bg-amber-50/90 p-1.5 rounded-lg border border-amber-200 text-[11px]">
                                                                    <div
                                                                        class="font-bold text-amber-950 truncate flex items-center gap-1">
                                                                        <span
                                                                            class="px-1.5 py-0.2 bg-amber-200 text-amber-900 rounded font-black text-[10px] font-mono shrink-0">
                                                                            {{ $tgt['quantity'] ?? 1 }}x
                                                                        </span>
                                                                        <span class="truncate"
                                                                            title="{{ $tgt['product_name'] ?? $tgt['item_no'] }}">
                                                                            {{ $tgt['product_name'] ?? $tgt['item_no'] }}
                                                                        </span>
                                                                    </div>
                                                                    <div
                                                                        class="text-[10px] text-amber-800 font-mono pl-6">
                                                                        SKU: {{ $tgt['item_no'] }}
                                                                        @if (!empty($tgt['serial_number']))
                                                                            | SN: {{ $tgt['serial_number'] }}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @elseif(!empty($it['target_item_no']))
                                                        <div
                                                            class="bg-amber-50/80 p-2 rounded-lg border border-amber-200 max-w-xs">
                                                            <div class="font-bold text-amber-900 text-[11px] truncate">
                                                                {{ $it['target_product_name'] ?? '-' }}
                                                            </div>
                                                            <div class="text-[10px] text-amber-800 font-mono">
                                                                SKU: {{ $it['target_item_no'] }}
                                                                @if (!empty($it['target_serial_number']))
                                                                    | SN: {{ $it['target_serial_number'] }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-gray-400 italic text-[11px]">- Tanpa Alokasi
                                                            -</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button"
                                                        wire:click="removeItemFromList({{ $idx }})"
                                                        class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                                        title="Hapus baris ini">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer Modal --}}
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between shrink-0">
                    <button type="button" wire:click="closeCreateModal"
                        class="px-4 py-2.5 text-xs font-bold text-gray-600 hover:text-gray-800 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" wire:click="submitAdjustment" wire:loading.attr="disabled"
                        @if (empty($items)) disabled @endif
                        class="px-6 py-2.5 rounded-xl text-xs font-bold transition shadow-md flex items-center gap-2 cursor-pointer {{ empty($items) ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-500/20' }}">
                        <svg wire:loading wire:target="submitAdjustment" class="animate-spin w-4 h-4 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span wire:loading.remove wire:target="submitAdjustment">
                            Ajukan Pemakaian ({{ count($items) }} Barang)
                        </span>
                        <span wire:loading wire:target="submitAdjustment">Menyimpan & Mengajukan Approval...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DETAIL PEMAKAIAN INVENTARIS (HEADER & DAFTAR ITEM) --}}
    @if ($showDetailModal && $selectedAdjustment)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div
                class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl border border-gray-100 overflow-hidden my-8 max-h-[90vh] flex flex-col">
                {{-- Header Detail --}}
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/80 shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[10px] font-black uppercase tracking-wider text-purple-700 bg-purple-100 px-2.5 py-0.5 rounded-full border border-purple-200">
                                Berkas Pemakaian Inventaris
                            </span>
                            <span
                                class="font-mono text-xs font-bold text-gray-800">{{ $selectedAdjustment->adjustment_number }}</span>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mt-1">
                            {{ $selectedAdjustment->total_items ?: $selectedAdjustment->items->count() }} Jenis Barang
                            ({{ $selectedAdjustment->total_quantity ?: $selectedAdjustment->items->sum('quantity') }}
                            unit)
                        </h3>
                    </div>
                    <button wire:click="closeDetailModal"
                        class="text-gray-400 hover:text-gray-600 p-2 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Body Detail --}}
                <div class="p-6 space-y-5 text-xs overflow-y-auto flex-1">
                    {{-- Status Banner --}}
                    <div
                        class="p-3.5 rounded-2xl flex items-center justify-between {{ $selectedAdjustment->status === 'SYNCED' ? 'bg-emerald-50 text-emerald-900 border border-emerald-200' : ($selectedAdjustment->status === 'PENDING' ? 'bg-amber-50 text-amber-900 border border-amber-200' : ($selectedAdjustment->status === 'APPROVED' ? 'bg-blue-50 text-blue-900 border border-blue-200' : 'bg-rose-50 text-rose-900 border border-rose-200')) }}">
                        <div>
                            <span class="font-bold text-sm block">Status: {{ $selectedAdjustment->status }}</span>
                            <span class="text-[11px] opacity-80">
                                @if ($selectedAdjustment->status === 'SYNCED')
                                    Berhasil disinkronkan ke Accurate Online.
                                @elseif($selectedAdjustment->status === 'PENDING')
                                    Menunggu verifikasi dan persetujuan pimpinan.
                                @elseif($selectedAdjustment->status === 'APPROVED')
                                    Telah disetujui pimpinan dan siap dikirim ke Accurate.
                                @elseif($selectedAdjustment->status === 'FAILED_SYNC')
                                    Disetujui pimpinan, namun terjadi kendala saat mengirim ke Accurate:
                                    {{ $selectedAdjustment->sync_error }}
                                @else
                                    Ditolak oleh pimpinan.
                                @endif
                            </span>
                        </div>
                        @if ($selectedAdjustment->accurate_adjustment_no)
                            <div class="text-right font-mono bg-white/70 px-3 py-1.5 rounded-xl border border-current">
                                <span class="text-[10px] opacity-70 block font-sans">No. Transaksi Accurate</span>
                                <strong class="text-xs">{{ $selectedAdjustment->accurate_adjustment_no }}</strong>
                            </div>
                        @endif
                    </div>

                    {{-- Informasi Header --}}
                    <div
                        class="grid grid-cols-2 md:grid-cols-4 gap-3 bg-gray-50/80 p-4 rounded-2xl border border-gray-100">
                        <div>
                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Gudang</span>
                            <span
                                class="font-bold text-gray-800">{{ $selectedAdjustment->warehouse->name ?? ($selectedAdjustment->warehouse_name ?? '-') }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Kategori Alasan</span>
                            <span
                                class="font-bold text-gray-800">{{ str_replace('_', ' ', $selectedAdjustment->reason_category) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Diajukan Oleh</span>
                            <span
                                class="font-bold text-gray-800">{{ $selectedAdjustment->requestedBy->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Waktu Pengajuan</span>
                            <span
                                class="font-bold text-gray-800">{{ $selectedAdjustment->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($selectedAdjustment->notes)
                            <div class="col-span-2 md:col-span-4 mt-1 pt-2 border-t border-gray-200">
                                <span class="text-[10px] text-gray-400 block font-bold uppercase">Catatan
                                    Dokumen</span>
                                <span class="text-gray-700 italic">"{{ $selectedAdjustment->notes }}"</span>
                            </div>
                        @endif
                    </div>

                    {{-- Tabel Detail Items --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-800 mb-2 uppercase tracking-wider">
                            Rincian Barang Disesuaikan ({{ $selectedAdjustment->items->count() }} Item)
                        </h4>
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead
                                    class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-wider text-left">
                                    <tr>
                                        <th class="px-4 py-2.5 text-center w-8">No</th>
                                        <th class="px-4 py-2.5">Barang & SKU</th>
                                        <th class="px-4 py-2.5 text-center">Arah</th>
                                        <th class="px-4 py-2.5 text-center">Qty</th>
                                        <th class="px-4 py-2.5">Tujuan Alokasi (Display / Aset)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($selectedAdjustment->items as $idx => $item)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="px-4 py-2.5 text-center font-bold text-gray-400">
                                                {{ $idx + 1 }}</td>
                                            <td class="px-4 py-2.5">
                                                <div class="font-bold text-gray-900">{{ $item->product_name }}</div>
                                                <div class="text-[11px] text-gray-500 font-mono">
                                                    SKU: {{ $item->item_no }}
                                                    @if ($item->proyek)
                                                        <span
                                                            class="text-indigo-600 font-sans font-semibold ml-1">[{{ $item->proyek }}]</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                                <span
                                                    class="inline-block px-2 py-0.5 rounded text-[10px] font-black {{ $item->adjustment_type === 'OUT' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                    {{ $item->adjustment_type === 'OUT' ? ' Keluar' : '🟢 Masuk' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-center font-black font-mono text-gray-900">
                                                {{ $item->quantity }} pcs
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @php
                                                    $itemTargets = $item->target_items_list;
                                                @endphp
                                                @if (!empty($itemTargets))
                                                    <div class="space-y-1 max-w-xs">
                                                        @foreach ($itemTargets as $itgt)
                                                            <div
                                                                class="bg-amber-50 p-1.5 rounded-lg border border-amber-200 text-[11px]">
                                                                <div
                                                                    class="font-bold text-amber-950 flex items-center gap-1">
                                                                    <span
                                                                        class="px-1 py-0.2 bg-amber-200 text-amber-900 rounded font-black text-[9px] font-mono shrink-0">
                                                                        {{ $itgt['quantity'] ?? 1 }}x
                                                                    </span>
                                                                    <span
                                                                        class="truncate">{{ $itgt['product_name'] ?? $itgt['item_no'] }}</span>
                                                                </div>
                                                                <div class="text-[10px] text-amber-800 font-mono pl-5">
                                                                    SKU: {{ $itgt['item_no'] }}
                                                                    @if (!empty($itgt['serial_number']))
                                                                        | SN: {{ $itgt['serial_number'] }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif($item->target_item_no)
                                                    <div class="bg-amber-50 p-1.5 rounded-lg border border-amber-200">
                                                        <div class="font-bold text-amber-950 text-[11px]">
                                                            {{ $item->target_product_name }}</div>
                                                        <div class="text-[10px] text-amber-800 font-mono">SKU:
                                                            {{ $item->target_item_no }}</div>
                                                    </div>
                                                @else
                                                    <span class="text-gray-400 italic text-[11px]">- Tanpa Alokasi
                                                        -</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        {{-- Fallback jika data lama belum masuk tabel items --}}
                                        <tr>
                                            <td class="px-4 py-2.5 text-center font-bold text-gray-400">1</td>
                                            <td class="px-4 py-2.5">
                                                <div class="font-bold text-gray-900">
                                                    {{ $selectedAdjustment->product_name }}</div>
                                                <div class="text-[11px] text-gray-500 font-mono">SKU:
                                                    {{ $selectedAdjustment->item_no }}</div>
                                            </td>
                                            <td class="px-4 py-2.5 text-center">
                                                <span
                                                    class="inline-block px-2 py-0.5 rounded text-[10px] font-black {{ $selectedAdjustment->adjustment_type === 'OUT' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                    {{ $selectedAdjustment->adjustment_type === 'OUT' ? ' Keluar' : '🟢 Masuk' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-center font-black font-mono">
                                                {{ $selectedAdjustment->quantity }} pcs
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @if ($selectedAdjustment->target_item_no)
                                                    <div class="bg-amber-50 p-1.5 rounded-lg border border-amber-200">
                                                        <div class="font-bold text-amber-950 text-[11px]">
                                                            {{ $selectedAdjustment->target_product_name }}</div>
                                                        <div class="text-[10px] text-amber-800 font-mono">SKU:
                                                            {{ $selectedAdjustment->target_item_no }}</div>
                                                    </div>
                                                @else
                                                    <span class="text-gray-400 italic text-[11px]">- Tanpa Alokasi
                                                        -</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Riwayat Approval (Jika Ada) --}}
                    @if ($selectedAdjustment->approvalRequest && $selectedAdjustment->approvalRequest->histories->isNotEmpty())
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2">
                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Riwayat Tindakan
                                Approval</span>
                            <div class="space-y-1.5">
                                @foreach ($selectedAdjustment->approvalRequest->histories as $h)
                                    <div class="flex items-center justify-between text-[11px]">
                                        <div>
                                            <strong class="text-gray-800">{{ $h->actedBy->name ?? 'User' }}</strong>
                                            <span class="text-gray-400">({{ $h->role_snapshot }})</span>:
                                            <span
                                                class="font-bold {{ $h->action === 'APPROVED' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $h->action }}</span>
                                            @if ($h->notes)
                                                <span class="italic text-gray-500">- "{{ $h->notes }}"</span>
                                            @endif
                                        </div>
                                        <span
                                            class="text-gray-400 font-mono">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer Detail --}}
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end shrink-0">
                    <button wire:click="closeDetailModal"
                        class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold text-xs transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
