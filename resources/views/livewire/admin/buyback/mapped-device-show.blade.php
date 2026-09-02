<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('admin.buyback.mapped-devices') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Perangkat di Tier: {{ $tier->name }}</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1 pl-7">Kelola daftar SKU yang ter-mapping pada tier ini.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.buyback.create', ['tier_id' => $tier->id]) }}" wire:navigate
                class="flex items-center gap-2 bg-[#1c69d4] hover:bg-[#1553a8] text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Perangkat ke Tier Ini
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-400 font-bold uppercase tracking-wider">
                        <th class="p-4">SKU (No. Item)</th>
                        <th class="p-4">Nama Barang</th>
                        <th class="p-4">Status Mapping</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($devices as $device)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="p-4">
                                <span class="font-bold text-gray-700">{{ $device->productAccurate->item_no ?? '-' }}</span>
                            </td>
                            <td class="p-4 min-w-[200px]">
                                <p class="font-bold text-gray-900">{{ $device->productAccurate->name ?? '-' }}</p>
                            </td>
                            <td class="p-4">
                                @if($device->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-[11px] font-bold border border-emerald-100">
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 text-gray-600 rounded-lg text-[11px] font-bold border border-gray-200">
                                        <div class="w-1.5 h-1.5 rounded-full bg-gray-400"></div> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="confirm('Apakah Anda yakin ingin menghapus mapping ini?') || event.stopImmediatePropagation()"
                                        wire:click="deleteMapping({{ $device->id }})"
                                        class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition"
                                        title="Hapus Mapping">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <p class="font-medium text-gray-900">Belum ada perangkat yang di-mapping ke tier ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
