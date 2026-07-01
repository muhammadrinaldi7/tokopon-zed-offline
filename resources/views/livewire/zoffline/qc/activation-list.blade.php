<div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Daftar Aktivasi Garansi</h1>
                <p class="text-sm text-gray-500 mt-1">Pantau status QC dan Aktivasi Garansi per IMEI untuk seluruh
                    transaksi cabang Anda.</p>
            </div>
            <div>
                <a href="{{ route('zoffline.warranty-activation') }}" wire:navigate
                    class="px-5 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-sm hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Aktivasi Baru
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Cari Transaksi /
                        SN</label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.500ms="search"
                            class="w-full pl-10 pr-4 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ketik No Invoice, Nama, atau IMEI...">
                        <div class="absolute left-3 top-3.5 text-gray-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Status
                        Aktivasi</label>
                    <select wire:model.live="statusFilter"
                        class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                        <option value="all">Semua Status</option>
                        <option value="inactive">Belum Aktif (Butuh QC)</option>
                        <option value="active">Sudah Aktif</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Rentang
                        Waktu</label>
                    <div class="flex items-center gap-2">
                        <input type="date" wire:model.live="dateStart"
                            class="w-full px-3 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                        <span class="text-gray-400">-</span>
                        <input type="date" wire:model.live="dateEnd"
                            class="w-full px-3 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead
                        class="bg-gray-50/50 border-b border-gray-100 text-xs uppercase font-bold text-gray-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Tgl Pembelian</th>
                            <th class="px-6 py-4">Pelanggan & Invoice</th>
                            <th class="px-6 py-4">Barang (SN/IMEI)</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($paginatedItems as $item)
                            <tr class="hover:bg-blue-50/30 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500 font-medium">
                                    {{ \Carbon\Carbon::parse($item['order_date'])->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $item['customer_name'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $item['order_number'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $item['product_name'] }}</div>
                                    <div
                                        class="text-xs font-mono text-gray-500 mt-1 bg-gray-100 inline-block px-2 py-0.5 rounded">
                                        {{ $item['serial_number'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if ($item['is_activated'])
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200/50">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200/50">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Belum Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if (!$item['is_activated'])
                                        <a href="{{ route('zoffline.qc.warranty-activation', ['sn' => $item['serial_number']]) }}"
                                            wire:navigate
                                            class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-200 text-gray-700 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 text-xs font-bold rounded-xl transition-all shadow-sm">
                                            Aktivasi Sekarang
                                        </a>
                                    @else
                                        <button disabled
                                            class="inline-flex items-center justify-center px-4 py-2 bg-gray-50 border border-transparent text-gray-400 text-xs font-bold rounded-xl cursor-not-allowed">
                                            Selesai
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-4">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900">Tidak ada data ditemukan</h3>
                                    <p class="text-sm text-gray-500 mt-1">Coba sesuaikan filter atau kata kunci
                                        pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($paginatedItems->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $paginatedItems->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
