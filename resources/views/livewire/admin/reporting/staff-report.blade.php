<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Analisa Kinerja Sales</h1>
            <p class="text-sm text-gray-500 mt-1">Evaluasi performa penjualan per karyawan/kasir</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <button wire:click="exportCsv" class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export CSV
            </button>

            <div class="flex items-center gap-3 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="businessUnitFilter"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent py-1.5 pl-3 pr-8 rounded-lg cursor-pointer hover:bg-gray-50">
                    <option value="">Semua Unit Usaha</option>
                    @foreach(\App\Models\BusinessUnit::where('is_active', true)->get() as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>

                <div class="h-6 w-px bg-gray-200"></div>

                <select wire:model.live="dateRange" class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent py-1.5 pl-3 pr-8 rounded-lg cursor-pointer hover:bg-gray-50">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Kustom</option>
                </select>

                @if($dateRange === 'custom')
                    <div class="flex items-center gap-2 px-2 border-l border-gray-100">
                        <input type="date" wire:model.live="startDate" class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5">
                        <span class="text-gray-400 text-sm">-</span>
                        <input type="date" wire:model.live="endDate" class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5">
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Data Table / Leaderboard --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-700 text-sm">Leaderboard Penjualan</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-4 font-bold w-16 text-center">Peringkat</th>
                        <th class="px-5 py-4 font-bold">Nama Karyawan / Sales</th>
                        <th class="px-5 py-4 font-bold text-center">Jumlah Transaksi</th>
                        <th class="px-5 py-4 font-bold text-right">Rata-Rata Transaksi</th>
                        <th class="px-5 py-4 font-bold text-right">Total Gross</th>
                        <th class="px-5 py-4 font-bold text-right">Total Net Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($staffPerformance as $idx => $staff)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-4 text-center">
                                @if($idx == 0)
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 font-bold text-sm">🏆</span>
                                @elseif($idx == 1)
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 font-bold text-sm">🥈</span>
                                @elseif($idx == 2)
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-600 font-bold text-sm">🥉</span>
                                @else
                                    <span class="text-sm font-bold text-gray-400">#{{ $idx + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                        {{ substr($staff['name'], 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ $staff['name'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-bold">
                                    {{ $staff['transactions'] }} Nota
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-xs font-medium text-gray-500">Rp {{ number_format($staff['avg_transaction'], 0, ',', '.') }} / trx</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm font-bold text-gray-700">Rp {{ number_format($staff['gross_revenue'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm font-black text-[#1c69d4]">Rp {{ number_format($staff['net_revenue'], 0, ',', '.') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <p class="text-gray-500 font-medium">Belum ada data penjualan pada rentang tanggal ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
