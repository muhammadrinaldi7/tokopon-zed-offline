<div class="space-y-6 p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Laporan Laba Rugi Penjualan</h2>
            <p class="text-sm text-gray-500 mt-1">Laporan Laba Bersih (Net Profit) dari setiap penjualan yang sudah
                selesai (COMPLETED).</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium flex items-center gap-2 text-sm shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-4 items-end">
        <div class="w-full md:w-1/4">
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Pencarian</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="No Invoice / Nama..."
                class="w-full rounded-lg border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>

        <div class="w-full md:w-1/4">
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Periode</label>
            <select wire:model.live="dateRange"
                class="w-full rounded-lg border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="today">Hari Ini</option>
                <option value="yesterday">Kemarin</option>
                <option value="this_week">Minggu Ini</option>
                <option value="this_month">Bulan Ini</option>
                <option value="this_year">Tahun Ini</option>
                <option value="custom">Kustom Tanggal</option>
            </select>
        </div>

        @if ($dateRange === 'custom')
            <div class="w-full md:w-1/4">
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Dari
                    Tanggal</label>
                <input type="date" wire:model.live="startDate"
                    class="w-full rounded-lg border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div class="w-full md:w-1/4">
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Sampai
                    Tanggal</label>
                <input type="date" wire:model.live="endDate"
                    class="w-full rounded-lg border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
        @endif

        <div class="w-full md:w-1/4">
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Cabang</label>
            <select wire:model.live="branchFilter"
                class="w-full rounded-lg border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="">Semua Cabang</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->name }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Total Penjualan Bersih (Net Sales)</h3>
            <p class="text-2xl font-black text-gray-900">Rp
                {{ number_format($summary['total_net_sales'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Total HPP (Modal)</h3>
            <p class="text-2xl font-black text-rose-600">Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Total Biaya MDR</h3>
            <p class="text-2xl font-black text-orange-600">Rp {{ number_format($summary['total_mdr'], 0, ',', '.') }}
            </p>
        </div>
        <div
            class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl border border-blue-200 p-5 shadow-sm text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10">
                <svg class="w-24 h-24 -mt-4 -mr-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
                        clip-rule="evenodd"></path>
                </svg>
            </div>
            <h3 class="text-sm font-medium text-blue-100 mb-1 relative z-10">Total Laba Bersih</h3>
            <p class="text-2xl font-black text-white relative z-10 flex items-end gap-2">
                Rp {{ number_format($summary['total_net_profit'], 0, ',', '.') }}
                <span
                    class="text-xs font-bold text-blue-200 mb-1">({{ number_format($summary['global_profit_margin'], 2) }}%)</span>
            </p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50/50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold">
                        <th class="px-6 py-4">Tanggal / Cabang</th>
                        <th class="px-6 py-4">Faktur / Pelanggan</th>
                        <th class="px-6 py-4 text-right">Net Sales</th>
                        <th class="px-6 py-4 text-right">HPP</th>
                        <th class="px-6 py-4 text-right">MDR</th>
                        <th class="px-6 py-4 text-right">Net Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">
                                    {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '-' }}
                                </div>
                                <div class="text-gray-500 text-xs">
                                    {{ $order->shipping_address_snapshot['store'] ?? 'Cabang Utama' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-blue-600">
                                    {{ $order->accurate_invoice_no ?? $order->order_number }}</div>
                                <div class="text-gray-500 text-xs truncate max-w-[200px]">
                                    {{ $order->user->name ?? 'Pelanggan Umum' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900">
                                Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-rose-600">
                                - Rp {{ number_format($order->calculated_hpp, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-orange-500">
                                - Rp {{ number_format($order->calculated_mdr, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div
                                    class="font-black {{ $order->calculated_net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    Rp {{ number_format($order->calculated_net_profit, 0, ',', '.') }}
                                </div>
                                <div
                                    class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $order->calculated_profit_margin >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }} inline-block mt-1">
                                    {{ number_format($order->calculated_profit_margin, 2) }}% Margin
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Tidak ada data penjualan untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
