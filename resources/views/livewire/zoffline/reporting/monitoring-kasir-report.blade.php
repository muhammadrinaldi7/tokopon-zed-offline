<div>
    <div class="p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <!-- Breadcrumbs -->
                    <nav class="flex text-sm text-gray-500 mb-2">
                        <a href="{{ route('zoffline.reporting') }}" wire:navigate class="hover:text-blue-600">Pusat Laporan</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900 font-medium">Monitoring Kasir</span>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900">Laporan Monitoring Kasir</h1>
                    <p class="text-gray-500 mt-1">Riwayat setoran dan settlement dari transaksi kasir offline.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button wire:click="exportXls" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:text-green-600 transition-colors shadow-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        <span class="font-medium">Export XLS</span>
                    </button>
                </div>
            </div>

            <!-- Filters section -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-wrap gap-4 items-end">
                <div class="w-full md:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Pilih Tanggal</label>
                    <select wire:model.live="dateRange" class="w-full md:w-48 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="this_year">Tahun Ini</option>
                        <option value="custom">Kustom</option>
                    </select>
                </div>

                @if($dateRange === 'custom')
                <div class="w-full md:w-auto flex items-center gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Mulai</label>
                        <input type="date" wire:model.live="startDate" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block p-2.5 w-36">
                    </div>
                    <span class="text-gray-400 mt-5">-</span>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sampai</label>
                        <input type="date" wire:model.live="endDate" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block p-2.5 w-36">
                    </div>
                </div>
                @endif

                <div class="w-full md:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Business Unit</label>
                    <select wire:model.live="businessUnitFilter" class="w-full md:w-48 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">Semua BU</option>
                        @foreach($availableBusinessUnits as $bu)
                            <option value="{{ $bu }}">{{ $bu }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cabang</label>
                    <select wire:model.live="branchFilter" class="w-full md:w-48 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">Semua Cabang</option>
                        @foreach($availableBranches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-auto flex-1">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cari Kasir / Penerima / Order</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.500ms="search" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="Cari nama atau order...">
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-gray-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Struk</p>
                        <h3 class="text-3xl font-bold text-gray-800">{{ number_format($summary['total_settlements']) }}</h3>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Tunai Sistem</p>
                        <h3 class="text-3xl font-bold text-gray-800">Rp {{ number_format($summary['total_tunai'], 0, ',', '.') }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Fisik Disetor</p>
                        <h3 class="text-3xl font-bold text-gray-800">Rp {{ number_format($summary['total_settle'], 0, ',', '.') }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 {{ $summary['total_selisih'] < 0 ? 'bg-red-50' : ($summary['total_selisih'] > 0 ? 'bg-orange-50' : 'bg-gray-50') }} rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Selisih</p>
                        <h3 class="text-3xl font-bold {{ $summary['total_selisih'] < 0 ? 'text-red-600' : ($summary['total_selisih'] > 0 ? 'text-orange-500' : 'text-gray-800') }}">
                            Rp {{ number_format($summary['total_selisih'], 0, ',', '.') }}
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Tanggal Settle</th>
                                <th class="px-6 py-4">Cabang</th>
                                <th class="px-6 py-4">Kasir (FL)</th>
                                <th class="px-6 py-4">Diterima Oleh</th>
                                <th class="px-6 py-4 text-center">Total Struk</th>
                                <th class="px-6 py-4 text-right">Total Tunai Sistem</th>
                                <th class="px-6 py-4 text-right">Total Setoran Fisik</th>
                                <th class="px-6 py-4 text-right">Total Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($settlements as $s)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ \Carbon\Carbon::parse($s->date)->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 font-medium">
                                        {{ $s->branch_name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-800">
                                        {{ $s->handledBy ? $s->handledBy->name : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $s->monitoringBy ? $s->monitoringBy->name : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-gray-700">
                                        {{ $s->total_struk }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-800">
                                        Rp {{ number_format($s->total_tunai, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-800">
                                        Rp {{ number_format($s->total_settle, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold {{ $s->total_selisih < 0 ? 'text-red-500' : ($s->total_selisih > 0 ? 'text-orange-500' : 'text-gray-500') }}">
                                        Rp {{ number_format($s->total_selisih, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p>Tidak ada data settlement yang ditemukan</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($settlements->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $settlements->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>
            
        </div>
    </div>
</div>
