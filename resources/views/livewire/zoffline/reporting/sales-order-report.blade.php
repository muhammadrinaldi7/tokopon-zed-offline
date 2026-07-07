<div class="p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-neutral-800">Laporan Sales Order (SO) & DP</h1>
                <p class="text-sm text-neutral-500 mt-1">Pantau performa pemesanan dengan uang muka, total piutang, dan status outstanding.</p>
            </div>
            <div class="flex gap-4">
                <div class="flex items-center gap-2 bg-white border border-neutral-200 rounded-xl px-2 py-1 shadow-sm">
                    <input type="date" wire:model.live="dateFrom" class="border-none focus:ring-0 text-sm py-1" title="Dari Tanggal">
                    <span class="text-neutral-400 text-xs font-medium">s/d</span>
                    <input type="date" wire:model.live="dateTo" class="border-none focus:ring-0 text-sm py-1" title="Sampai Tanggal">
                </div>
                <button wire:click="exportExcel" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-sm rounded-xl border border-emerald-200 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Excel
                </button>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center items-start">
                <div class="absolute right-0 top-0 w-24 h-24 bg-amber-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1 z-10">SO Outstanding (Belum Lunas)</h3>
                <p class="text-3xl font-black text-neutral-900 z-10">{{ number_format($totalOutstanding) }} <span class="text-sm font-medium text-neutral-400">order</span></p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center items-start">
                <div class="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1 z-10">Total Piutang Berjalan</h3>
                <p class="text-3xl font-black text-red-600 z-10">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center items-start">
                <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1 z-10">Closing Rate</h3>
                <div class="flex items-baseline gap-2 z-10">
                    <p class="text-3xl font-black text-emerald-600">{{ $closingRate }}%</p>
                    <p class="text-xs text-neutral-400">dari {{ $totalSO }} SO</p>
                </div>
            </div>
        </div>

        <!-- Aging Report -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 mb-8">
            <h3 class="text-base font-bold text-neutral-900 mb-4">SO Aging (Umur Outstanding)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($agingData as $label => $count)
                    <div class="p-4 rounded-xl border {{ $loop->last ? 'bg-red-50 border-red-100' : 'bg-neutral-50 border-neutral-100' }}">
                        <p class="text-xs font-bold {{ $loop->last ? 'text-red-700' : 'text-neutral-500' }}">{{ $label }}</p>
                        <p class="text-2xl font-black {{ $loop->last ? 'text-red-700' : 'text-neutral-900' }}">{{ $count }}</p>
                    </div>
                @endforeach
            </div>
            @if($agingData['> 30 Hari'] > 0)
                <div class="mt-4 p-3 bg-red-50 border border-red-100 text-red-700 text-sm rounded-lg flex items-start gap-2">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p>Terdapat <strong>{{ $agingData['> 30 Hari'] }} SO</strong> yang menggantung lebih dari 30 hari. Segera hubungi pelanggan untuk konfirmasi pelunasan atau pembatalan.</p>
                </div>
            @endif
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
            <div class="p-4 border-b border-neutral-100 bg-neutral-50/50 flex justify-between items-center">
                <h2 class="text-lg font-bold text-neutral-800">Daftar SO Outstanding</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">No SO</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Pelanggan</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-neutral-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-emerald-600 uppercase tracking-wider">DP Masuk</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-red-600 uppercase tracking-wider">Sisa Tagihan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @forelse ($outstandingOrders as $order)
                            @php
                                $dpPaid = $order->payments()->where('status', 'SUCCESS')->sum('amount');
                                $sisaTagihan = max(0, $order->grand_total - $dpPaid);
                                $isOld = $order->created_at->diffInDays(now()) > 30;
                            @endphp
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900">{{ $order->created_at->format('d M Y, H:i') }}</div>
                                    <div class="text-[10px] font-bold mt-1 {{ $isOld ? 'text-red-500 bg-red-50 inline-block px-1.5 py-0.5 rounded' : 'text-neutral-400' }}">
                                        {{ $order->created_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono font-bold text-blue-600">{{ $order->order_number }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-neutral-900">{{ $order->user->name ?? '-' }}</div>
                                    <div class="text-xs text-neutral-500">{{ $order->user->phone ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-neutral-900">
                                    {{ number_format($order->grand_total, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-emerald-600">
                                    {{ number_format($dpPaid, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-red-600">
                                    {{ number_format($sisaTagihan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                    <div class="mx-auto w-16 h-16 bg-neutral-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-neutral-900">Semua Lunas!</h3>
                                    <p class="mt-1 text-sm text-neutral-500">Tidak ada Sales Order yang menggantung saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($outstandingOrders->hasPages())
                <div class="p-4 border-t border-neutral-100 bg-neutral-50/50">
                    {{ $outstandingOrders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
