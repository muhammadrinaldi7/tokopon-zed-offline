<div class="max-w-7xl mx-auto p-2 md:p-6 min-h-screen">
    <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Riwayat Closing Kasir</h2>
            <p class="text-gray-500 text-sm">Informasi rekapitulasi transaksi POS berdasarkan kasir yang bertugas.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="date" wire:model.live="dateFilter"
                    class="pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm font-medium focus:ring-blue-500 focus:border-blue-500 shadow-sm text-gray-700 bg-white">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <a href="{{ route('zoffline.pos') }}" wire:navigate
                class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-sm font-bold flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke POS
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold">
                        <th class="p-4">Tanggal</th>
                        <th class="p-4">Kasir (Handled By)</th>
                        <th class="p-4 text-center">Total Transaksi</th>
                        <th class="p-4 text-right">Total Tunai</th>
                        <th class="p-4 text-right">Total Non-Tunai</th>
                        <th class="p-4 text-right bg-emerald-50/50 text-emerald-700">TOTAL INVOICE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($reports as $report)
                        <tr class="hover:bg-blue-50/50 transition">
                            <td class="p-4 font-bold text-gray-800">
                                {{ \Carbon\Carbon::parse($report->date)->translatedFormat('l, d F Y') }}
                            </td>
                            <td class="p-4 font-bold text-blue-700">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-black text-xs">
                                        {{ strtoupper(substr($report->handledBy->name ?? '?', 0, 2)) }}
                                    </div>
                                    {{ $report->handledBy->name ?? 'Kasir Tidak Diketahui' }}
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <span wire:click="showDetail('{{ $report->date }}', {{ $report->handled_by }})"
                                    class="inline-flex cursor-pointer items-center px-3 py-1 rounded-full text-xs font-black bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ $report->total_invoice }} TRX
                                </span>
                            </td>
                            <td class="p-4 text-right font-mono text-gray-600">
                                Rp {{ number_format($report->total_tunai, 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-right font-mono text-gray-600">
                                Rp {{ number_format($report->total_non_tunai, 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-emerald-600 bg-emerald-50/30">
                                Rp {{ number_format($report->grand_total, 0, ',', '.') }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="font-bold text-gray-700">Belum Ada Riwayat Transaksi POS</p>
                                    <p class="text-sm mt-1 text-gray-400">Pada tanggal tersebut belum ada transaksi yang
                                        tercatat.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Rincian Transaksi --}}
    @if ($showDetailModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
            wire:click.self="closeDetailModal">
            <div
                class="bg-white rounded-2xl shadow-xl w-full max-w-4xl flex flex-col max-h-[90vh] overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $detailModalTitle }}</h3>
                        <p class="text-xs text-gray-500 mt-1">Menampilkan seluruh invoice yang diproses oleh kasir ini.
                        </p>
                    </div>
                    <button wire:click="closeDetailModal"
                        class="text-gray-400 hover:text-gray-600 transition p-2 rounded-lg hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-0 overflow-y-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-white shadow-sm z-10">
                            <tr
                                class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold">
                                <th class="p-4">Waktu</th>
                                <th class="p-4">Invoice</th>
                                <th class="p-4">Pelanggan</th>
                                <th class="p-4 text-center">Metode Bayar</th>
                                <th class="p-4 text-right">Total Transaksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($selectedOrders as $order)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 text-gray-600 whitespace-nowrap">
                                        {{ $order->created_at->format('H:i') }}
                                    </td>
                                    <td class="p-4 font-mono font-bold text-blue-600 whitespace-nowrap">
                                        {{ $order->order_number }}
                                    </td>
                                    <td class="p-4 font-bold text-gray-800">
                                        {{ $order->customer_name ?? 'Walk-in Customer' }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            @foreach ($order->payments as $payment)
                                                <span
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700">
                                                    {{ $payment->paymentMethod->name ?? 'Kas / Tunai' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="p-4 text-right font-mono font-bold text-emerald-600 whitespace-nowrap">
                                        Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-500 italic">
                                        Data transaksi tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end shrink-0">
                    <button wire:click="closeDetailModal"
                        class="px-5 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 transition shadow-sm">
                        Tutup Rincian
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
