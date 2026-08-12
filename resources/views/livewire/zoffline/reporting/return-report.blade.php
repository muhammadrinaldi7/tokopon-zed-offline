<div class="p-6">
    <div class="flex flex-col items-start gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Retur</h1>
            <p class="text-sm text-gray-500 mt-1">Gunakan laporan ini untuk memantau data klaim garansi dan barang retur.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 w-full">
            <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="status"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent rounded-lg cursor-pointer w-full">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_repair">Diproses</option>
                    <option value="waiting_refund">Menunggu Refund</option>
                    <option value="approved">Disetujui</option>
                    <option value="completed">Selesai</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>

            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm col-span-1 md:col-span-2">
                <input type="date" wire:model.live="startDate"
                    class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full">
                <span class="text-gray-400 text-sm font-bold">-</span>
                <input type="date" wire:model.live="endDate"
                    class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full">
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input wire:model.live.debounce.500ms="search" type="text"
                class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm transition-shadow"
                placeholder="Cari No Klaim, Serial Number, atau Nama Pelanggan...">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Klaim & No Resi</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Produk & SN</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kendala</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Resolusi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($claims as $claim)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ $claim->claim_number }}</div>
                                <div class="text-sm text-gray-500">{{ $claim->claimed_at ? $claim->claimed_at->format('d/m/Y H:i') : '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $claim->customer->name ?? '-' }}</div>
                                <div class="text-sm text-gray-500">{{ $claim->customer->profile->phone_number ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 line-clamp-2">
                                    {{ $claim->warranty->orderItem->product_name ?? ($claim->warranty->orderItem->variant->name ?? 'Produk Tidak Diketahui') }}
                                </div>
                                <div class="text-xs font-mono font-bold text-gray-500 mt-1">SN: {{ $claim->serial_number }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 line-clamp-2" title="{{ $claim->issue_description }}">{{ $claim->issue_description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badge = $claim->status_badge;
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $badge->bg }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge->dot }}"></span>
                                    {{ $badge->label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($claim->resolution)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($claim->resolution) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum ada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-gray-500 font-medium">Tidak ada data laporan retur di rentang
                                        waktu ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($claims->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $claims->links() }}
            </div>
        @endif
    </div>
</div>
