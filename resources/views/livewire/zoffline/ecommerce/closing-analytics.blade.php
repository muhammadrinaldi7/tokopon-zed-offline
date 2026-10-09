<div class="p-4 sm:p-6 min-h-screen bg-neutral-100">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Laporan & Analitik Closing CS'])

    {{-- Filter Periode --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-sm font-extrabold text-gray-700">Performa Konversi Percakapan Pembeli</h3>
        <div class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-gray-200 text-xs font-bold shadow-2xs">
            <button wire:click="setPeriod('all')"
                class="px-3 py-1.5 rounded-lg transition-all {{ $period === 'all' ? 'bg-neutral-800 text-white' : 'text-gray-500 hover:text-gray-900' }}">Semua Waktu</button>
            <button wire:click="setPeriod('this_month')"
                class="px-3 py-1.5 rounded-lg transition-all {{ $period === 'this_month' ? 'bg-neutral-800 text-white' : 'text-gray-500 hover:text-gray-900' }}">Bulan Ini</button>
            <button wire:click="setPeriod('this_week')"
                class="px-3 py-1.5 rounded-lg transition-all {{ $period === 'this_week' ? 'bg-neutral-800 text-white' : 'text-gray-500 hover:text-gray-900' }}">Minggu Ini</button>
            <button wire:click="setPeriod('today')"
                class="px-3 py-1.5 rounded-lg transition-all {{ $period === 'today' ? 'bg-neutral-800 text-white' : 'text-gray-500 hover:text-gray-900' }}">Hari Ini</button>
        </div>
    </div>

    {{-- KPI Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: Total Omset Closing --}}
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-5 text-white shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-100 uppercase tracking-wider">Omset Closing Chat</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-sm">💰</span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black tracking-tight">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                <p class="text-[11px] text-emerald-100 mt-1">{{ $totalDeals }} transaksi berhasil closing</p>
            </div>
        </div>

        {{-- Card 2: Closing Conversion Rate --}}
        <div class="bg-white rounded-3xl p-5 border border-gray-200 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tingkat Konversi</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">🎯</span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-gray-900 tracking-tight">{{ $conversionRate }}%</div>
                <p class="text-[11px] text-gray-400 mt-1">Rasio chat masuk menjadi penjualan</p>
            </div>
        </div>

        {{-- Card 3: Total Chat Masuk --}}
        <div class="bg-white rounded-3xl p-5 border border-gray-200 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Chat Masuk</span>
                <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm">💬</span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-gray-900 tracking-tight">{{ $totalChats }}</div>
                <p class="text-[11px] text-gray-400 mt-1">Konsultasi pembeli via mobile & web</p>
            </div>
        </div>

        {{-- Card 4: Prospek Berjalan --}}
        <div class="bg-white rounded-3xl p-5 border border-gray-200 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status Prospek</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">⏳</span>
            </div>
            <div class="mt-4">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="text-lg font-black text-blue-600">{{ $totalFollowUps }}</div>
                        <span class="text-[10px] text-gray-400">Follow Up</span>
                    </div>
                    <div class="h-6 w-px bg-gray-200"></div>
                    <div>
                        <div class="text-lg font-black text-rose-500">{{ $totalLost }}</div>
                        <span class="text-[10px] text-gray-400">Batal / Lost</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CS Leaderboard & Recent Deals Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Leaderboard CS --}}
        <div class="bg-white rounded-3xl p-5 border border-gray-200 shadow-sm">
            <h4 class="text-sm font-extrabold text-gray-900 mb-1 flex items-center gap-2">
                <span>🏆</span>
                <span>Peringkat Penjualan Tim CS</span>
            </h4>
            <p class="text-xs text-gray-400 mb-4">Urutan staf dengan omset closing terbesar dari obrolan pelanggan.</p>

            <div class="divide-y divide-gray-100">
                @forelse($csLeaderboard as $index => $cs)
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full font-black text-xs flex items-center justify-center {{ $index === 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <div class="font-bold text-gray-900 text-xs">{{ $cs['agent_name'] }}</div>
                                <div class="text-[11px] text-gray-400">
                                    {{ $cs['deal_count'] }} Deal dari {{ $cs['total_handled'] }} Chat ({{ $cs['rate'] }}%)
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-emerald-600 text-xs">Rp {{ number_format($cs['total_omset'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400 text-xs">
                        Belum ada data closing dari tim CS pada periode ini.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Deals Stream --}}
        <div class="bg-white rounded-3xl p-5 border border-gray-200 shadow-sm">
            <h4 class="text-sm font-extrabold text-gray-900 mb-1 flex items-center gap-2">
                <span>🎉</span>
                <span>Riwayat Closing Terbaru</span>
            </h4>
            <p class="text-xs text-gray-400 mb-4">Daftar transaksi yang berhasil ditutup dari konsultasi chat.</p>

            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @forelse($recentDeals as $deal)
                    <div class="py-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-gray-900 text-xs truncate">
                                    {{ $deal->user?->name ?? ($deal->guest_name ?: 'Tamu') }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-mono">
                                    • CS: {{ $deal->closedBy?->name ?? 'Staff' }}
                                </span>
                            </div>
                            @if($deal->productAccurate)
                                <div class="text-[11px] font-medium text-indigo-600 truncate mt-0.5">
                                    📱 {{ $deal->productAccurate->name }}
                                </div>
                            @endif
                            @if($deal->closing_notes)
                                <p class="text-[11px] text-gray-500 italic mt-0.5">"{{ $deal->closing_notes }}"</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-black text-emerald-600 text-xs">
                                Rp {{ number_format($deal->closing_amount, 0, ',', '.') }}
                            </div>
                            <span class="text-[10px] text-gray-400 font-mono">
                                {{ $deal->closed_at?->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400 text-xs">
                        Belum ada transaksi closing terbaru.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
