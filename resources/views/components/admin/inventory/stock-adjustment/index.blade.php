<div class="p-6 bg-gray-50 min-h-screen space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                    Modul Inventaris
                </span>
                <span class="text-xs text-gray-500 font-mono">Stock Adjustment & Accurate Sync</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Penyesuaian Stok Persediaan</h1>
            <p class="text-sm text-gray-500">Kelola mutasi penyesuaian stok fisik (pengurangan/penambahan) dengan sistem approval & sinkronisasi Accurate.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openCreateModal"
                class="bg-blue-600 hover:bg-blue-700 transition-all text-white px-4 py-2.5 rounded-xl font-medium shadow-sm hover:shadow flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Buat Penyesuaian Baru
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No. ADJ, SKU, Nama Barang, Alasan..."
                        class="w-full pl-9 pr-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            {{-- Filter Status --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status Approval</label>
                <select wire:model.live="filterStatus" class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-lg outline-none focus:border-blue-500">
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
                <select wire:model.live="filterType" class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-lg outline-none focus:border-blue-500">
                    <option value="ALL">Semua Tipe</option>
                    <option value="OUT">🔴 Pengurangan (Keluar)</option>
                    <option value="IN">🟢 Penambahan (Masuk)</option>
                </select>
            </div>

            {{-- Filter Gudang --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Gudang</label>
                <select wire:model.live="filterWarehouseId" class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-lg outline-none focus:border-blue-500">
                    <option value="ALL">Semua Gudang</option>
                    @foreach($warehouses as $wh)
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
                        <th class="px-6 py-4">Barang Disesuaikan (SKU)</th>
                        <th class="px-6 py-4">Tipe & Qty</th>
                        <th class="px-6 py-4">Tujuan Alokasi (Aset / HP)</th>
                        <th class="px-6 py-4">Gudang & Proyek</th>
                        <th class="px-6 py-4">Status & Accurate</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse ($adjustments as $adj)
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            {{-- Nomor & Tanggal --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900 font-mono text-xs">{{ $adj->adjustment_number }}</span>
                                <div class="text-[11px] text-gray-500 mt-0.5">
                                    {{ $adj->created_at->format('d M Y, H:i') }} WIB
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    Oleh: <strong class="text-gray-600">{{ $adj->requestedBy->name ?? 'Kasir' }}</strong>
                                </div>
                            </td>

                            {{-- Barang Disesuaikan --}}
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900 max-w-xs truncate" title="{{ $adj->product_name }}">{{ $adj->product_name }}</div>
                                <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                    SKU: <strong class="text-gray-700">{{ $adj->item_no }}</strong>
                                </div>
                                @if($adj->notes)
                                    <div class="text-[11px] text-gray-500 italic mt-1 max-w-xs truncate" title="{{ $adj->notes }}">
                                        "{{ $adj->notes }}"
                                    </div>
                                @endif
                            </td>

                            {{-- Tipe & Qty --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($adj->adjustment_type === 'OUT')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                        Keluar (-{{ $adj->quantity }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                        Masuk (+{{ $adj->quantity }})
                                    </span>
                                @endif
                                <div class="text-[10px] text-gray-500 mt-1 uppercase font-semibold">
                                    {{ str_replace('_', ' ', $adj->reason_category) }}
                                </div>
                            </td>

                            {{-- Tujuan Alokasi --}}
                            <td class="px-6 py-4">
                                @if($adj->target_item_no || $adj->target_product_name)
                                    <div class="bg-amber-50/80 border border-amber-200/60 p-2 rounded-lg max-w-xs">
                                        <div class="font-bold text-amber-900 text-[11px] truncate" title="{{ $adj->target_product_name }}">
                                            {{ $adj->target_product_name ?: '-' }}
                                        </div>
                                        <div class="text-[10px] text-amber-700 font-mono mt-0.5">
                                            SKU: {{ $adj->target_item_no }}
                                        </div>
                                        @if($adj->target_serial_number)
                                            <div class="text-[10px] text-amber-800 font-mono mt-0.5">
                                                SN: {{ $adj->target_serial_number }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs italic">- Tidak ada unit tujuan -</span>
                                @endif
                            </td>

                            {{-- Gudang & Proyek --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-800 font-medium">{{ $adj->warehouse->name ?? ($adj->warehouse_name ?? '-') }}</div>
                                <div class="mt-1">
                                    @if($adj->proyek)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $adj->proyek }} {{ $adj->project_no ? "({$adj->project_no})" : '' }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-[10px]">- Tanpa Proyek -</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Status & Accurate --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    @if($adj->status === 'PENDING')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Menunggu Approval
                                        </span>
                                    @elseif($adj->status === 'APPROVED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Approved
                                        </span>
                                    @elseif($adj->status === 'SYNCED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Synced Accurate
                                        </span>
                                    @elseif($adj->status === 'FAILED_SYNC')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800" title="{{ $adj->sync_error }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal Sync
                                        </span>
                                    @elseif($adj->status === 'REJECTED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-200 text-gray-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Ditolak
                                        </span>
                                    @endif
                                </div>

                                @if($adj->accurate_adjustment_no)
                                    <div class="text-[10px] text-gray-500 font-mono mt-1">
                                        Accurate: <strong class="text-gray-700">{{ $adj->accurate_adjustment_no }}</strong>
                                    </div>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($adj->status === 'FAILED_SYNC')
                                        <button wire:click="retrySync({{ $adj->id }})"
                                            wire:loading.attr="disabled"
                                            title="Coba sinkronkan ulang ke Accurate"
                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded text-[11px] font-bold transition">
                                            Retry Sync
                                        </button>
                                    @endif
                                    <button wire:click="viewDetail({{ $adj->id }})"
                                        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-xs transition">
                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    <span class="font-medium text-gray-500">Belum ada data penyesuaian stok.</span>
                                    <p class="text-xs text-gray-400">Klik tombol "Buat Penyesuaian Baru" untuk membuat permohonan baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($adjustments->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $adjustments->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL BUAT PENYESUAIAN BARU --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-gray-100 overflow-hidden my-8">
                {{-- Header Modal --}}
                <div class="bg-gradient-to-r from-blue-900 to-indigo-900 p-5 text-white flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold">Form Penyesuaian Stok Persediaan</h2>
                        <p class="text-xs text-blue-100/80">Pengajuan mutasi stok fisik dengan otorisasi approval & integrasi Accurate.</p>
                    </div>
                    <button wire:click="closeCreateModal" class="text-white/70 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    {{-- 1. Pilihan Gudang & Tipe --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Gudang --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Gudang Penyesuaian <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="warehouse_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-lg p-2.5 focus:bg-white focus:border-blue-500 outline-none">
                                <option value="">-- Pilih Gudang --</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}">
                                        {{ $wh->name }} {{ Auth::user()->warehouse_id == $wh->id ? '⭐ (Gudang Anda)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <span class="text-red-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        {{-- Tipe Penyesuaian --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Arah Mutasi Stok <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" wire:click="$set('adjustment_type', 'OUT')"
                                    class="py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 {{ $adjustment_type === 'OUT' ? 'bg-rose-100 text-rose-800 border-2 border-rose-500' : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200' }}">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                    Pengurangan (Keluar)
                                </button>
                                <button type="button" wire:click="$set('adjustment_type', 'IN')"
                                    class="py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 {{ $adjustment_type === 'IN' ? 'bg-emerald-100 text-emerald-800 border-2 border-emerald-500' : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200' }}">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    Penambahan (Masuk)
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Barang Utama yang Disesuaikan --}}
                    <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-blue-900">
                                Barang yang Disesuaikan Stoknya <span class="text-red-500">*</span>
                            </label>
                            @if($selectedItem)
                                <span class="text-[11px] text-blue-700 bg-blue-100 px-2 py-0.5 rounded font-mono">
                                    Stok Saat Ini: <strong>{{ $current_stock }} pcs</strong>
                                </span>
                            @endif
                        </div>

                        {{-- Input Search SKU --}}
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchItem"
                                placeholder="Ketik nama produk atau SKU Accurate (misal: antigores, s26)..."
                                class="w-full text-xs p-2.5 pr-8 bg-white border border-blue-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <svg class="w-4 h-4 text-blue-400 absolute right-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>

                            {{-- Autocomplete Dropdown --}}
                            @if(!empty($itemSearchResults))
                                <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-52 overflow-y-auto divide-y divide-gray-100">
                                    @foreach($itemSearchResults as $res)
                                        <button type="button" wire:click="selectItem({{ $res['id'] }})"
                                            class="w-full text-left p-2.5 hover:bg-blue-50 transition flex items-center justify-between text-xs">
                                            <div>
                                                <div class="font-bold text-gray-800">{{ $res['name'] }}</div>
                                                <div class="text-[11px] text-gray-500 font-mono">SKU: {{ $res['item_no'] }}</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[10px] font-bold">
                                                    Stok: {{ $res['stock'] ?? 0 }}
                                                </span>
                                                @if(!empty($res['proyek']))
                                                    <span class="block text-[10px] text-blue-600 font-semibold mt-0.5">{{ $res['proyek'] }}</span>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('item_no') <span class="text-red-500 text-[11px]">{{ $message }}</span> @enderror

                        {{-- Item Terpilih Info Card --}}
                        @if($selectedItem)
                            <div class="bg-white p-3 rounded-lg border border-blue-200 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-gray-900">{{ $product_name }}</div>
                                    <div class="text-gray-500 font-mono text-[11px]">SKU: {{ $item_no }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($proyek)
                                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-[10px] font-bold border border-indigo-200">
                                            Proyek: {{ $proyek }} {{ $project_no ? "({$project_no})" : '' }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-gray-400 italic">Tanpa Proyek</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Input Quantity --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Jumlah Disesuaikan (Qty) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" min="1" wire:model="quantity"
                                        class="w-full text-xs font-bold p-2 bg-white border border-gray-300 rounded-lg focus:border-blue-500 outline-none">
                                    <span class="absolute right-3 top-2 text-xs text-gray-400">Unit / Pcs</span>
                                </div>
                                @error('quantity') <span class="text-red-500 text-[11px]">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Kategori Alasan <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="reason_category" class="w-full text-xs bg-white border border-gray-300 rounded-lg p-2 focus:border-blue-500 outline-none">
                                    <option value="PEMELIHARAAN_INVENTARIS">Pemeliharaan Inventaris / Unit Display</option>
                                    <option value="BARANG_RUSAK_DEFECT">Barang Rusak / Defect / Cacat Pabrik</option>
                                    <option value="SAMPLE_PROMOSI">Sample / Display Promosi Toko</option>
                                    <option value="SELISIH_OPNAME">Selisih Hasil Stock Opname</option>
                                    <option value="KOREKSI_STOK">Koreksi Administrasi / Salah Input</option>
                                    <option value="LAINNYA">Lain-lain</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 3. SKU Tujuan Alokasi (OPSIONAL) --}}
                    <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-amber-950">
                                    Barang / Aset Tujuan Alokasi <span class="text-gray-500 font-normal">(Opsional)</span>
                                </label>
                                <p class="text-[11px] text-amber-800/80">
                                    Isi jika penyesuaian ini digunakan untuk merawat unit aset tertentu (misal: pasang antigores pada HP LDU/Display S26 Ultra).
                                </p>
                            </div>
                            @if($target_item_no)
                                <button type="button" wire:click="clearTargetItem" class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline">
                                    Batal / Hapus Target
                                </button>
                            @endif
                        </div>

                        {{-- Search Input Target SKU --}}
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchTargetItem"
                                placeholder="Ketik nama atau SKU barang tujuan (misal: S26 Ultra LDU)..."
                                class="w-full text-xs p-2.5 pr-8 bg-white border border-amber-200 rounded-lg focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
                            <svg class="w-4 h-4 text-amber-400 absolute right-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>

                            {{-- Autocomplete Dropdown Target --}}
                            @if(!empty($targetItemSearchResults))
                                <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-48 overflow-y-auto divide-y divide-gray-100">
                                    @foreach($targetItemSearchResults as $res)
                                        <button type="button" wire:click="selectTargetItem({{ $res['id'] }})"
                                            class="w-full text-left p-2.5 hover:bg-amber-50 transition flex items-center justify-between text-xs">
                                            <div>
                                                <div class="font-bold text-gray-800">{{ $res['name'] }}</div>
                                                <div class="text-[11px] text-gray-500 font-mono">SKU: {{ $res['item_no'] }}</div>
                                            </div>
                                            @if(!empty($res['proyek']))
                                                <span class="text-[10px] text-amber-800 bg-amber-100 px-2 py-0.5 rounded font-semibold">{{ $res['proyek'] }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Target Terpilih Card --}}
                        @if($target_item_no)
                            <div class="bg-white p-3 rounded-lg border border-amber-300 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-amber-950">{{ $target_product_name }}</div>
                                    <div class="text-amber-800 font-mono text-[11px]">SKU: {{ $target_item_no }}</div>
                                </div>
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-900 rounded text-[10px] font-bold">
                                    Unit Tujuan Terpilih
                                </span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-amber-900 mb-1">
                                    Nomor Seri / IMEI Unit Display <span class="text-gray-500 font-normal">(Opsional)</span>
                                </label>
                                <input type="text" wire:model="target_serial_number" placeholder="Misal IMEI unit display: 3582910..."
                                    class="w-full text-xs p-2 bg-white border border-amber-200 rounded-lg outline-none focus:border-amber-500 font-mono">
                            </div>
                        @endif
                    </div>

                    {{-- 4. Keterangan & Akun Accurate --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Catatan / Keterangan Penyesuaian
                            </label>
                            <textarea wire:model="notes" rows="2" placeholder="Jelaskan kebutuhan penyesuaian stok ini secara rinci..."
                                class="w-full text-xs p-2.5 bg-gray-50 border border-gray-200 rounded-lg outline-none focus:bg-white focus:border-blue-500"></textarea>
                            @error('notes') <span class="text-red-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-700 block">Akun Penyesuaian Accurate (COA)</span>
                                <span class="text-[11px] text-gray-500">Nomor akun beban/pemeliharaan di Accurate Online</span>
                            </div>
                            <input type="text" wire:model="accurate_account_no" placeholder="5101"
                                class="w-28 text-xs font-mono font-bold p-2 bg-white border border-gray-300 rounded-lg text-center outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>

                {{-- Footer Modal --}}
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <button type="button" wire:click="closeCreateModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="submitAdjustment"
                        wire:loading.attr="disabled"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2">
                        <svg wire:loading wire:target="submitAdjustment" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="submitAdjustment">Ajukan Penyesuaian</span>
                        <span wire:loading wire:target="submitAdjustment">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DETAIL PENYESUAIAN --}}
    @if($showDetailModal && $selectedAdjustment)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-gray-100 overflow-hidden my-8">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                            Detail Penyesuaian Stok
                        </span>
                        <h3 class="text-base font-bold text-gray-900 mt-1 font-mono">{{ $selectedAdjustment->adjustment_number }}</h3>
                    </div>
                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    {{-- Status Banner --}}
                    <div class="p-3 rounded-xl flex items-center justify-between {{ $selectedAdjustment->status === 'SYNCED' ? 'bg-emerald-50 text-emerald-900 border border-emerald-200' : ($selectedAdjustment->status === 'PENDING' ? 'bg-amber-50 text-amber-900 border border-amber-200' : 'bg-rose-50 text-rose-900 border border-rose-200') }}">
                        <div>
                            <span class="font-bold block">Status: {{ $selectedAdjustment->status }}</span>
                            <span class="text-[11px] opacity-80">
                                @if($selectedAdjustment->status === 'SYNCED')
                                    Berhasil disinkronkan ke Accurate Online.
                                @elseif($selectedAdjustment->status === 'PENDING')
                                    Menunggu verifikasi dan persetujuan pimpinan.
                                @elseif($selectedAdjustment->status === 'FAILED_SYNC')
                                    Disetujui pimpinan, namun terjadi kendala saat mengirim ke Accurate.
                                @else
                                    Ditolak oleh pimpinan.
                                @endif
                            </span>
                        </div>
                        @if($selectedAdjustment->accurate_adjustment_no)
                            <div class="text-right font-mono">
                                <span class="text-[10px] opacity-70 block">No. Accurate</span>
                                <strong class="text-xs">{{ $selectedAdjustment->accurate_adjustment_no }}</strong>
                            </div>
                        @endif
                    </div>

                    {{-- Informasi Barang Disesuaikan --}}
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 space-y-2">
                        <div class="font-bold text-gray-800 text-sm">{{ $selectedAdjustment->product_name }}</div>
                        <div class="grid grid-cols-2 gap-2 text-gray-600">
                            <div>SKU: <strong class="font-mono text-gray-800">{{ $selectedAdjustment->item_no }}</strong></div>
                            <div>Tipe: <strong class="{{ $selectedAdjustment->adjustment_type === 'OUT' ? 'text-rose-600' : 'text-emerald-600' }}">{{ $selectedAdjustment->adjustment_type === 'OUT' ? 'Keluar (-)' : 'Masuk (+)' }}</strong></div>
                            <div>Jumlah: <strong class="text-gray-800">{{ $selectedAdjustment->quantity }} unit</strong></div>
                            <div>Gudang: <strong class="text-gray-800">{{ $selectedAdjustment->warehouse->name ?? ($selectedAdjustment->warehouse_name ?? '-') }}</strong></div>
                            <div>Proyek: <strong class="text-blue-700">{{ $selectedAdjustment->proyek ?: '-' }}</strong></div>
                            <div>Alasan: <strong class="text-gray-800">{{ str_replace('_', ' ', $selectedAdjustment->reason_category) }}</strong></div>
                        </div>
                    </div>

                    {{-- Informasi Tujuan Alokasi --}}
                    @if($selectedAdjustment->target_item_no)
                        <div class="bg-amber-50 p-3.5 rounded-xl border border-amber-200 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Unit Tujuan Alokasi / Pemeliharaan:</span>
                            <div class="font-bold text-amber-950">{{ $selectedAdjustment->target_product_name }}</div>
                            <div class="text-[11px] text-amber-900 font-mono">
                                SKU Target: <strong>{{ $selectedAdjustment->target_item_no }}</strong>
                                @if($selectedAdjustment->target_serial_number)
                                    | SN/IMEI: <strong>{{ $selectedAdjustment->target_serial_number }}</strong>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Catatan --}}
                    @if($selectedAdjustment->notes)
                        <div>
                            <span class="text-gray-500 font-medium block mb-1">Catatan Pembuat:</span>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 italic text-gray-700">
                                "{{ $selectedAdjustment->notes }}"
                            </div>
                        </div>
                    @endif

                    {{-- Error Sync jika ada --}}
                    @if($selectedAdjustment->sync_error)
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-[11px]">
                            <strong class="block mb-0.5">Pesan Kendala Accurate:</strong>
                            {{ $selectedAdjustment->sync_error }}
                        </div>
                    @endif

                    {{-- Actor Info --}}
                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
                        <span>Dibuat oleh: <strong class="text-gray-700">{{ $selectedAdjustment->requestedBy->name ?? 'Kasir' }}</strong></span>
                        @if($selectedAdjustment->approvedBy)
                            <span>Disetujui oleh: <strong class="text-gray-700">{{ $selectedAdjustment->approvedBy->name }}</strong></span>
                        @endif
                    </div>
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button wire:click="closeDetailModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-bold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
