<div class="p-6 max-w-7xl mx-auto">
    {{-- Header Section --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Stock Opname Cabang</h2>
                @if(!$isGlobal && $user->branch)
                    <span class="px-3 py-1 bg-blue-50 text-[#4E44DB] border border-blue-200 text-xs font-bold rounded-lg flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        {{ $user->branch->name }}
                    </span>
                @endif
                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg">
                    {{ $user->getActiveBusinessUnit()->name ?? 'Unit Bisnis' }}
                </span>
            </div>
            <p class="text-sm text-gray-500">Pencatatan fisik berkala dan audit kesesuaian stok toko vs buku sistem.</p>
        </div>

        <div>
            <button wire:click="navigateToCreate"
                class="px-5 py-2.5 bg-[#4E44DB] hover:bg-[#3d34b3] text-white rounded-xl shadow-md hover:shadow-lg transition-all font-semibold flex items-center gap-2 text-sm cursor-pointer">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Mulai Sesi Opname Baru
            </button>
        </div>
    </div>

    {{-- Main Container Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
        {{-- Filter Bar --}}
        <div class="p-4 sm:p-5 border-b border-neutral-100 bg-neutral-50/50 flex flex-col sm:flex-row gap-3 justify-between items-center">
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari No. Opname atau BM..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-neutral-200 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                </div>

                <select wire:model.live="filterStatus" class="px-3 py-2 bg-white border border-neutral-200 rounded-xl text-xs sm:text-sm text-neutral-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                    <option value="ALL">Semua Status</option>
                    <option value="COUNTING">Sedang Dihitung</option>
                    <option value="PENDING_APPROVAL">Menunggu Approval</option>
                    <option value="COMPLETED">Selesai / Disahkan</option>
                    <option value="REJECTED">Ditolak / Perlu Revisi</option>
                </select>

                @if($isGlobal && count($branches) > 0)
                    <select wire:model.live="filterBranchId" class="px-3 py-2 bg-white border border-neutral-200 rounded-xl text-xs sm:text-sm text-neutral-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="text-xs text-neutral-500 font-medium">
                Total Sesi: <span class="font-bold text-neutral-800">{{ $opnames->total() }}</span>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-neutral-50 text-[11px] font-bold text-neutral-500 uppercase tracking-wider border-b border-neutral-200">
                        <th class="px-5 py-3.5">No. Opname & Tanggal</th>
                        <th class="px-5 py-3.5">Cabang / Gudang</th>
                        <th class="px-5 py-3.5">Tipe Cakupan</th>
                        <th class="px-5 py-3.5">Pelaksana (BM)</th>
                        <th class="px-5 py-3.5 text-center">Buku vs Fisik</th>
                        <th class="px-5 py-3.5 text-center">Selisih Fisik</th>
                        <th class="px-5 py-3.5">Nilai HPP Selisih</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 text-neutral-700">
                    @forelse($opnames as $opname)
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900 block font-mono text-xs">{{ $opname->opname_number }}</span>
                                <span class="text-[11px] text-gray-500">{{ $opname->start_time->format('d M Y, H:i') }}</span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-medium text-gray-800 block text-xs">{{ $opname->branch->name ?? '-' }}</span>
                                <span class="text-[10px] text-gray-500">{{ $opname->warehouse->name ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($opname->type === 'ALL')
                                    <span class="px-2 py-0.5 bg-neutral-100 text-neutral-700 text-[10px] font-bold rounded">Semua Barang</span>
                                @elseif($opname->type === 'SERIALIZED_ONLY')
                                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded">Khusus HP (IMEI)</span>
                                @elseif($opname->type === 'NON_SERIALIZED_ONLY')
                                    <span class="px-2 py-0.5 bg-orange-50 text-orange-700 text-[10px] font-bold rounded">Aksesoris</span>
                                @else
                                    <span class="px-2 py-0.5 bg-teal-50 text-teal-700 text-[10px] font-bold rounded">{{ $opname->category_filter ?? 'Kategori' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-semibold text-gray-800 text-xs">{{ $opname->user->name ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <span class="text-xs text-gray-500 font-mono">Buku: {{ number_format($opname->total_system_qty) }}</span>
                                <span class="text-xs font-bold text-gray-900 font-mono block">Fisik: {{ number_format($opname->total_physical_qty) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if($opname->total_difference_qty == 0)
                                    <span class="px-2 py-1 bg-green-50 text-green-700 border border-green-200 text-xs font-bold rounded-lg inline-block">
                                        Cocok (0)
                                    </span>
                                @elseif($opname->total_difference_qty < 0)
                                    <span class="px-2 py-1 bg-red-50 text-red-700 border border-red-200 text-xs font-bold rounded-lg inline-block">
                                        Minus ({{ $opname->total_difference_qty }})
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold rounded-lg inline-block">
                                        Surplus (+{{ $opname->total_difference_qty }})
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs">
                                @if($opname->total_loss_value > 0)
                                    <div class="text-red-600 font-bold">
                                        - Rp {{ number_format($opname->total_loss_value, 0, ',', '.') }}
                                    </div>
                                @endif
                                @if($opname->total_surplus_value > 0)
                                    <div class="text-amber-600 font-bold">
                                        + Rp {{ number_format($opname->total_surplus_value, 0, ',', '.') }}
                                    </div>
                                @endif
                                @if($opname->total_loss_value == 0 && $opname->total_surplus_value == 0)
                                    <span class="text-gray-400">Rp 0</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if($opname->status === 'COUNTING')
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                        Sedang Dihitung
                                    </span>
                                @elseif($opname->status === 'PENDING_APPROVAL')
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                        Menunggu Approval
                                    </span>
                                @elseif($opname->status === 'COMPLETED' || $opname->status === 'APPROVED')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                        Selesai / Disahkan
                                    </span>
                                @elseif($opname->status === 'REJECTED')
                                    <span class="px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-700 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                        {{ $opname->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($opname->status === 'COUNTING')
                                        <a href="{{ route('zoffline.stock-opname.count', $opname->id) }}" wire:navigate
                                            class="px-3 py-1.5 bg-[#4E44DB] hover:bg-[#3d34b3] text-white text-xs font-semibold rounded-lg shadow-sm transition flex items-center gap-1">
                                            <span>Hitung Fisik</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        </a>
                                    @else
                                        <a href="{{ route('zoffline.stock-opname.summary', $opname->id) }}" wire:navigate
                                            class="p-1.5 text-neutral-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Lihat Berita Acara & Rincian">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Cetak PDF Berita Acara --}}
                                    <a href="{{ route('zoffline.stock-opname.pdf', $opname->id) }}" target="_blank"
                                        class="p-1.5 text-neutral-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Cetak Berita Acara PDF">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </a>

                                    {{-- Export Excel --}}
                                    <a href="{{ route('zoffline.stock-opname.export-excel', $opname->id) }}"
                                        class="p-1.5 text-neutral-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition" title="Export Excel">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-neutral-400">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <p class="text-sm font-medium text-neutral-600">Belum ada sesi Stock Opname</p>
                                <p class="text-xs text-neutral-400 mt-1">Klik tombol "+ Mulai Sesi Opname Baru" untuk memulai audit stok cabang Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opnames->hasPages())
            <div class="p-4 border-t border-neutral-100 bg-neutral-50/50">
                {{ $opnames->links() }}
            </div>
        @endif
    </div>
</div>
