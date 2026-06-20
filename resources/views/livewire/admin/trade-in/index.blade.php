<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Tukar Tambah</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola pengajuan taksiran harga dan verifikasi unit fisik.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden">
        {{-- Filters --}}
        <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari pelanggan atau tipe HP..." class="w-full pl-10 pr-4 py-2 text-sm border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4]">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <select wire:model.live="status" class="text-sm border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] w-full sm:w-48">
                <option value="">Semua Status</option>
                <option value="PENDING">Menunggu Taksiran</option>
                <option value="OFFERED">Penawaran Dikirim</option>
                <option value="WAITING_FOR_DEVICE">Menunggu Unit Dikirim</option>
                <option value="INSPECTING">Inspeksi Fisik</option>
                <option value="PAYING">Menunggu Pembayaran</option>
                <option value="COMPLETED">Selesai</option>
                <option value="CANCELLED">Dibatalkan</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 bg-gray-50 uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">ID & Tanggal</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">HP Lama (Ditukar)</th>
                        <th class="px-6 py-4">Status & Taksiran</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tradeIns as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900">#TRD-{{ $item->id }}</span>
                                <div class="text-xs text-gray-400 mt-1">{{ $item->created_at->format('d M Y, H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $item->user->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-[#1c69d4]">{{ $item->old_phone_brand }} {{ $item->old_phone_model }}</div>
                                <div class="text-xs text-gray-500 mt-1">Incaran: {{ $item->targetProduct->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'PENDING' => 'bg-amber-100 text-amber-800',
                                        'OFFERED' => 'bg-blue-100 text-blue-800',
                                        'WAITING_FOR_DEVICE' => 'bg-purple-100 text-purple-800',
                                        'INSPECTING' => 'bg-indigo-100 text-indigo-800',
                                        'PAYING' => 'bg-teal-100 text-teal-800',
                                        'COMPLETED' => 'bg-emerald-100 text-emerald-800',
                                        'CANCELLED' => 'bg-rose-100 text-rose-800',
                                    ];
                                @endphp
                                <span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-md tracking-wider {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ str_replace('_', ' ', $item->status) }}
                                </span>
                                @if($item->appraised_value)
                                    <div class="text-xs font-bold text-emerald-600 mt-2">Rp {{ number_format($item->appraised_value, 0, ',', '.') }}</div>
                                @else
                                    <div class="text-xs text-gray-400 mt-2">Belum ditaksir</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.trade-ins.show', $item) }}" wire:navigate class="text-[#1c69d4] hover:text-[#3f36b8] font-semibold text-sm">
                                    {{ $item->status === 'PENDING' ? 'Taksir Sekarang' : 'Detail' }} →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    <p class="text-sm font-medium text-gray-400">Tidak ada pengajuan tukar tambah ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($tradeIns->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $tradeIns->links() }}
            </div>
        @endif
    </div>
</div>

