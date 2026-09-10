<div class="p-6 bg-[#f7f7f7] min-h-screen">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('zoffline.reporting') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Penjualan Per Proyek</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1">Matriks perbandingan dan rekapitulasi penjualan harian per kategori proyek</p>
        </div>

        {{-- Mode Switch (Nominal Rp vs Qty Unit) --}}
        <div class="flex items-center bg-white p-1 rounded-xl border border-gray-200 shadow-sm self-start md:self-auto">
            <button type="button" wire:click="$set('valueMode', 'nominal')"
                class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 {{ $valueMode === 'nominal' ? 'bg-[#1c69d4] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Nominal (Rp)</span>
            </button>
            <button type="button" wire:click="$set('valueMode', 'qty')"
                class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 {{ $valueMode === 'qty' ? 'bg-[#1c69d4] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span>Unit (Qty)</span>
            </button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="flex flex-col items-start mb-6 gap-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3 w-full">
            
            {{-- Separator CSV --}}
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center justify-between">
                <span class="text-xs text-gray-500 mr-2 font-medium">Separator:</span>
                <select wire:model.live="csvSeparator"
                    class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 p-0 font-medium cursor-pointer w-full text-right truncate">
                    <option value=";">Semicolon (;)</option>
                    <option value=",">Comma (,)</option>
                </select>
            </div>

            {{-- Filter Unit Bisnis --}}
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="businessUnitFilter"
                    class="border-none text-sm font-bold text-gray-800 focus:ring-0 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="all">Semua Bisnis Unit</option>
                    @foreach ($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Cabang --}}
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="branchFilter"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="">Semua Cabang</option>
                    @foreach ($availableBranches as $branch)
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Proyek (Multi-Select Searchable Dropdown) --}}
            <div x-data="{
                open: false,
                search: '',
                selected: @entangle('proyekFilter').live,
                projects: {{ json_encode($availableProjects) }},
                get filteredProjects() {
                    if (!this.search) return this.projects;
                    return this.projects.filter(p => p.toLowerCase().includes(this.search.toLowerCase()));
                },
                get displayLabel() {
                    if (!this.selected || this.selected.length === 0) return 'Semua Proyek';
                    if (this.selected.length === 1) return this.selected[0];
                    return this.selected.length + ' Proyek Terpilih';
                },
                selectAll() {
                    this.selected = [...this.projects];
                },
                clearAll() {
                    this.selected = [];
                }
            }" @click.outside="open = false"
                class="relative bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <!-- Trigger Button -->
                <div @click="open = !open"
                    class="w-full flex items-center justify-between cursor-pointer select-none">
                    <span class="text-sm font-medium text-gray-700 truncate" x-text="displayLabel">Semua Proyek</span>
                    <div class="flex items-center gap-1 shrink-0 ml-1">
                        <button type="button" x-show="selected && selected.length > 0"
                            @click.stop="clearAll()" class="text-gray-400 hover:text-gray-600 p-0.5"
                            title="Reset filter proyek">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Dropdown Search Panel -->
                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute left-0 top-full mt-1.5 w-72 max-h-80 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50 flex flex-col"
                    style="display: none;">

                    <!-- Quick Actions -->
                    <div class="px-3 pb-2 flex items-center justify-between border-b border-gray-100 text-xs">
                        <button type="button" @click="selectAll()" class="text-[#1c69d4] hover:underline font-bold">
                            Pilih Semua
                        </button>
                        <button type="button" @click="clearAll()" class="text-gray-400 hover:text-gray-600">
                            Reset
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="px-2 py-2 border-b border-gray-100">
                        <div class="relative">
                            <input x-model="search" x-ref="projectSearchInput" @keydown.escape="open = false"
                                type="text" placeholder="Ketik cari nama proyek..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4]"
                                x-init="$watch('open', value => { if (value) setTimeout(() => $refs.projectSearchInput.focus(), 50) })">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Project List -->
                    <div class="overflow-y-auto flex-1 max-h-56 divide-y divide-gray-50 text-xs">
                        <template x-for="p in filteredProjects" :key="p">
                            <label class="px-3 py-2 cursor-pointer hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                <input type="checkbox" :value="p" x-model="selected"
                                    class="rounded text-[#1c69d4] focus:ring-[#1c69d4] w-3.5 h-3.5 border-gray-300">
                                <span x-text="p" class="truncate pr-2"
                                    :class="{
                                        'font-bold text-[#1c69d4]': selected && selected.includes(p),
                                        'text-gray-700': !(selected && selected.includes(p))
                                    }"></span>
                            </label>
                        </template>

                        <div x-show="filteredProjects.length === 0 && search" class="px-3 py-4 text-center text-gray-400 italic">
                            Proyek tidak ditemukan
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Rentang Tanggal --}}
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="dateRange"
                    class="border-none text-sm font-bold text-blue-600 focus:ring-0 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="last_month">Bulan Lalu</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Kustom</option>
                </select>
            </div>

            @if ($dateRange === 'custom')
                <div class="md:col-span-2 lg:col-span-2 flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm">
                    <input type="date" wire:model.live="startDate"
                        class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                    <span class="text-gray-400 text-sm font-bold">-</span>
                    <input type="date" wire:model.live="endDate"
                        class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                </div>
            @endif

            {{-- Tombol Export Excel & CSV --}}
            <div class="flex items-center gap-2 col-span-1 {{ $dateRange === 'custom' ? 'lg:col-span-6' : 'lg:col-span-2' }}">
                {{-- CSV Export --}}
                <button wire:click="exportCsv" wire:loading.attr="disabled"
                    class="flex-1 flex items-center justify-center gap-1.5 bg-slate-700 hover:bg-slate-800 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                    <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4 shrink-0 text-slate-300"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <svg wire:loading wire:target="exportCsv"
                        class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                    <span wire:loading wire:target="exportCsv">Memproses...</span>
                </button>

                {{-- Excel Export --}}
                <button wire:click="exportExcel" wire:loading.attr="disabled"
                    class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                    <svg wire:loading.remove wire:target="exportExcel"
                        class="w-4 h-4 shrink-0 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <svg wire:loading wire:target="exportExcel"
                        class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                    <span wire:loading wire:target="exportExcel">Memproses...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Proyek Aktif</p>
            <h3 class="text-xl font-black text-gray-800">{{ $matrixData['activeProjectsCount'] }} <span class="text-xs font-medium text-gray-400">/ {{ count($matrixData['columns']) }} Kategori</span></h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Unit Terjual</p>
            <h3 class="text-xl font-bold text-gray-700">{{ number_format($matrixData['grandTotal']['qty']) }} <span class="text-xs font-medium text-gray-400">Pcs / Unit</span></h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50 rounded-full opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Penjualan</p>
            <h3 class="text-xl font-black text-[#1c69d4]">Rp {{ number_format($matrixData['grandTotal']['nominal'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Rata-rata Penjualan / Hari</p>
            <h3 class="text-xl font-bold text-emerald-600">Rp {{ number_format($matrixData['dailyAverage'], 0, ',', '.') }}</h3>
        </div>
    </div>

    {{-- Pivot Matrix Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <span>Matriks Penjualan Harian</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $valueMode === 'nominal' ? 'bg-blue-50 text-[#1c69d4]' : 'bg-emerald-50 text-emerald-600' }}">
                        Mode: {{ $valueMode === 'nominal' ? 'Nominal (Rp)' : 'Unit Terjual (Qty)' }}
                    </span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">Klik angka pada sel untuk melihat rincian nota transaksi</p>
            </div>
            <div class="text-xs text-gray-500 font-medium">
                Periode: <span class="font-bold text-gray-700">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</span> s/d <span class="font-bold text-gray-700">{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="overflow-x-auto max-h-[70vh]">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 z-20 shadow-sm">
                    <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                        <th class="px-5 py-3.5 sticky left-0 z-30 bg-slate-900 min-w-36 border-r border-slate-700">
                            Row Labels (Tanggal)
                        </th>
                        @foreach ($matrixData['columns'] as $col)
                            <th class="px-4 py-3.5 text-right min-w-36 border-r border-slate-700 font-semibold tracking-normal">
                                {{ $col }}
                            </th>
                        @endforeach
                        <th class="px-5 py-3.5 text-right min-w-40 bg-slate-900 font-black border-l border-slate-700">
                            Grand Total
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse ($matrixData['dates'] as $d)
                        @php
                            $dateKey = $d['raw'];
                            $rowTotal = $matrixData['rowTotals'][$dateKey][$valueMode] ?? 0;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors group">
                            {{-- Tanggal Column --}}
                            <td class="px-5 py-3 font-medium text-gray-800 sticky left-0 z-10 bg-white group-hover:bg-blue-50/60 border-r border-gray-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold">{{ $d['display'] }}</span>
                                    <span class="text-[10px] text-gray-400 font-normal">({{ $d['day_name'] }})</span>
                                </div>
                            </td>

                            {{-- Dynamic Project Columns --}}
                            @foreach ($matrixData['columns'] as $col)
                                @php
                                    $cell = $matrixData['matrix'][$dateKey][$col] ?? ['nominal' => 0, 'qty' => 0, 'count' => 0];
                                    $val = $cell[$valueMode] ?? 0;
                                @endphp
                                <td class="px-4 py-3 text-right border-r border-gray-50 font-mono">
                                    @if ($val > 0)
                                        <button type="button" wire:click="openDetail('{{ $dateKey }}', '{{ $col }}')"
                                            class="inline-flex items-center gap-1 font-semibold text-gray-800 hover:text-[#1c69d4] hover:underline cursor-pointer group-hover:font-bold transition-all"
                                            title="Klik untuk lihat {{ $cell['count'] }} item transaksi">
                                            @if ($valueMode === 'nominal')
                                                {{ number_format($val, 0, ',', '.') }}
                                            @else
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md">{{ number_format($val) }}</span>
                                            @endif
                                        </button>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Row Grand Total Column --}}
                            <td class="px-5 py-3 text-right font-black font-mono border-l border-gray-100 bg-gray-50/50 group-hover:bg-blue-50/80 text-[#1c69d4]">
                                @if ($rowTotal > 0)
                                    @if ($valueMode === 'nominal')
                                        {{ number_format($rowTotal, 0, ',', '.') }}
                                    @else
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-md">{{ number_format($rowTotal) }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-300 font-normal">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($matrixData['columns']) + 2 }}" class="px-5 py-8 text-center text-gray-400">
                                Tidak ada data penjualan ditemukan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="sticky bottom-0 z-20 shadow-[0_-2px_10px_-2px_rgba(0,0,0,0.1)]">
                    <tr class="bg-slate-100 border-t-2 border-slate-300 text-xs font-black text-slate-800 font-mono">
                        {{-- Grand Total Label --}}
                        <td class="px-5 py-3.5 sticky left-0 z-30 bg-slate-200 border-r border-slate-300 font-sans uppercase tracking-wider text-[11px]">
                            Grand Total
                        </td>

                        {{-- Project Column Totals --}}
                        @foreach ($matrixData['columns'] as $col)
                            @php
                                $colTotal = $matrixData['columnTotals'][$col][$valueMode] ?? 0;
                            @endphp
                            <td class="px-4 py-3.5 text-right border-r border-slate-200">
                                @if ($colTotal > 0)
                                    @if ($valueMode === 'nominal')
                                        {{ number_format($colTotal, 0, ',', '.') }}
                                    @else
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md">{{ number_format($colTotal) }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 font-normal">-</span>
                                @endif
                            </td>
                        @endforeach

                        {{-- Absolute Grand Total --}}
                        @php
                            $absTotal = $matrixData['grandTotal'][$valueMode] ?? 0;
                        @endphp
                        <td class="px-5 py-3.5 text-right font-black border-l border-slate-300 bg-blue-600 text-white text-sm">
                            @if ($valueMode === 'nominal')
                                Rp {{ number_format($absTotal, 0, ',', '.') }}
                            @else
                                {{ number_format($absTotal) }} Unit
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Modal Drill-Down Transaksi --}}
    @if ($showDetailModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[85vh] flex flex-col overflow-hidden border border-gray-100 animate-in fade-in zoom-in duration-150">
                
                {{-- Modal Header --}}
                <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-500/20 text-blue-400 rounded-xl border border-blue-500/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold">Rincian Transaksi Proyek: <span class="text-blue-400">{{ $detailProject }}</span></h3>
                            <p class="text-xs text-slate-400">Tanggal: <span class="text-white font-semibold">{{ $detailDisplayDate }}</span> ({{ count($detailItems) }} Item Transaksi)</p>
                        </div>
                    </div>
                    <button wire:click="closeDetailModal" class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Search in Modal --}}
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between gap-4">
                    <div class="relative flex-1">
                        <input type="text" wire:model.live.debounce.250ms="detailSearch"
                            placeholder="Cari no nota, produk, SKU, pelanggan, sales..."
                            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4]">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <div class="text-xs font-bold text-gray-700 shrink-0">
                        Total Subtotal: <span class="text-[#1c69d4] text-sm">Rp {{ number_format($detailItems->sum('subtotal'), 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="overflow-y-auto flex-1 p-4">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-100 text-[10px] uppercase font-bold text-gray-500 tracking-wider">
                                <th class="px-3 py-2.5 rounded-l-lg">Nota & Jam</th>
                                <th class="px-3 py-2.5">Pelanggan & Sales</th>
                                <th class="px-3 py-2.5">Cabang</th>
                                <th class="px-3 py-2.5">Produk & SKU</th>
                                <th class="px-3 py-2.5">Serial Number</th>
                                <th class="px-3 py-2.5 text-center">Qty</th>
                                <th class="px-3 py-2.5 text-right">Harga</th>
                                <th class="px-3 py-2.5 text-right">Diskon</th>
                                <th class="px-3 py-2.5 text-right rounded-r-lg">Subtotal Bersih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($detailItems as $it)
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="px-3 py-2.5">
                                        <p class="font-bold text-gray-800 font-mono">{{ $it['order_number'] }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-gray-400">
                                            <span>{{ $it['created_at'] }}</span>
                                            @if ($it['invoice_no'] !== '-')
                                                <span class="text-blue-600 font-semibold font-mono">Inv: {{ $it['invoice_no'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <p class="font-semibold text-gray-800">{{ $it['customer_name'] }}</p>
                                        <p class="text-[10px] text-gray-400">Sales: {{ $it['sales_name'] }}</p>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[10px] font-medium">{{ $it['branch'] }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 max-w-xs">
                                        <p class="font-bold text-gray-800 truncate" title="{{ $it['product_name'] }}">{{ $it['product_name'] }}</p>
                                        <p class="text-[10px] text-gray-400 font-mono">{{ $it['sku'] }}</p>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span class="font-mono text-[11px] text-gray-600">{{ $it['serial_number'] }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-bold text-gray-800">
                                        {{ $it['qty'] }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-gray-600 font-mono">
                                        Rp {{ number_format($it['price'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono">
                                        @if ($it['discount'] > 0)
                                            <span class="text-red-500 font-medium">Rp {{ number_format($it['discount'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-bold text-[#1c69d4] font-mono">
                                        Rp {{ number_format($it['subtotal'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-400 text-xs">
                                        Tidak ada item transaksi yang cocok dengan kriteria pencarian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-xs text-gray-500">Menampilkan {{ count($detailItems) }} baris data</span>
                    <button type="button" wire:click="closeDetailModal"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
