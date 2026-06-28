<div class="max-w-7xl mx-auto p-4 md:p-6 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pesanan & Tagihan (SO)</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola Sales Order dan Down Payment (Mini Accurate)</p>
        </div>
        <a href="{{ route('admin.sales-orders.create') }}" wire:navigate
            class="px-4 py-2 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl transition-colors flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Buat Sales Order
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex gap-4 mb-6">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="pl-10 p-2 w-full rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm"
                    placeholder="Cari No. SO atau Nama Pelanggan...">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="p-4 font-bold rounded-tl-xl">No. Pesanan (SO)</th>
                        <th class="p-4 font-bold">Tanggal</th>
                        <th class="p-4 font-bold">Pelanggan</th>
                        <th class="p-4 font-bold">Total Nilai</th>
                        <th class="p-4 font-bold text-center">Status</th>
                        <th class="p-4 font-bold text-right rounded-tr-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-[#1c69d4]">{{ $order->order_number }}</div>
                                @if ($order->accurate_so_number)
                                    <div class="text-[10px] text-gray-400 font-medium">Accurate:
                                        {{ $order->accurate_so_number }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ $order->order_date ? $order->order_date->format('d M Y') : $order->created_at->format('d M Y') }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-gray-800">{{ $order->user->name ?? 'Unknown' }}</div>
                                <div class="text-[10px] text-gray-400">{{ $order->user->email ?? '' }}</div>
                            </td>
                            <td class="p-4 font-bold text-gray-800">Rp
                                {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                            <td class="p-4 text-center">
                                @if ($order->order_status === 'pending')
                                    <span
                                        class="px-2.5 py-1 bg-amber-50 text-amber-600 text-[10px] font-bold uppercase rounded-md border border-amber-200">Pending</span>
                                @elseif($order->order_status === 'down_payment')
                                    <span
                                        class="px-2.5 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold uppercase rounded-md border border-blue-200">DP
                                        Dibayar</span>
                                @elseif($order->order_status === 'completed')
                                    <span
                                        class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase rounded-md border border-emerald-200">Selesai</span>
                                @else
                                    <span
                                        class="px-2.5 py-1 bg-gray-50 text-gray-600 text-[10px] font-bold uppercase rounded-md border border-gray-200">{{ $order->order_status }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('admin.sales-orders.show', $order) }}" wire:navigate
                                    class="inline-flex items-center justify-center p-2 bg-white border border-gray-200 rounded-lg text-gray-600 hover:text-[#1c69d4] hover:bg-blue-50 transition-colors shadow-sm"
                                    title="Lihat Peta Relasi & Detail">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm font-medium">Belum ada data Sales Order</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
