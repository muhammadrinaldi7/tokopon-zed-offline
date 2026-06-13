<div class="p-6 bg-[#f7f7f7] min-h-screen" x-data="dashboardAnalytics()">
    {{-- Scripts for ApexCharts --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- Top Header & Filters --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Dashboard Analitik</h1>
            <p class="text-sm text-gray-500 mt-1">Menampilkan data berdasarkan rentang tanggal yang dipilih.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <button wire:click="exportCsv"
                class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export CSV
            </button>

            <div class="flex items-center gap-3 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="branchFilter"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent py-1.5 pl-3 pr-8 rounded-lg cursor-pointer hover:bg-gray-50">
                    <option value="">Semua Cabang</option>
                    @foreach ($availableBranches as $branch)
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    @endforeach
                </select>

                <div class="h-6 w-px bg-gray-200"></div>

                <select wire:model.live="dateRange"
                    class="border-none text-sm font-bold text-blue-600 focus:ring-0 bg-blue-50 py-1.5 pl-3 pr-8 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors">
                    <option value="today">Hari Ini (Default)</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="last_7_days">7 Hari Terakhir</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Kustom</option>
                </select>

                @if ($dateRange === 'custom')
                    <div class="flex items-center gap-2 px-2 border-l border-gray-100">
                        <input type="date" wire:model.live="startDate"
                            class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5">
                        <span class="text-gray-400 text-sm">-</span>
                        <input type="date" wire:model.live="endDate"
                            class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5">
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- SECTION 1: TOP KPI CARDS (NUMBERS ONLY, BASED ON SELECTED DATE FILTER) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">

        {{-- Net Sales (Primary Card - Dark/Elegant Theme) --}}
        <div
            class="bg-slate-900 rounded-2xl p-5 shadow-lg relative overflow-hidden flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-5 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500">
            </div>
            <div class="flex items-center justify-between mb-3 relative z-10">
                <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Net Sales</p>
                <div class="p-2 bg-slate-800 rounded-lg text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-white relative z-10">Rp {{ number_format($totalNet, 0, ',', '.') }}</h3>
        </div>

        {{-- Gross Sales --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Gross</p>
                <div class="p-2 bg-emerald-50 rounded-lg text-emerald-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Rp {{ number_format($totalGross, 0, ',', '.') }}</h3>
        </div>

        {{-- Diskon --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Diskon</p>
                <div class="p-2 bg-rose-50 rounded-lg text-rose-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                        </path>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-rose-500">- Rp {{ number_format($totalDiscount, 0, ',', '.') }}</h3>
        </div>

        {{-- Total QTY Sold --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty Terjual</p>
                <div class="p-2 bg-amber-50 rounded-lg text-amber-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-gray-800">{{ number_format($totalQty, 0, ',', '.') }} <span
                    class="text-sm font-medium text-gray-400">Pcs</span></h3>
        </div>

        {{-- Total Transactions --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Transaksi</p>
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-gray-800">{{ number_format($totalTransactions, 0, ',', '.') }} <span
                    class="text-sm font-medium text-gray-400">Struk</span></h3>
        </div>

    </div>

    {{-- SECTION 2: TOP PERFORMERS (LISTS WITH REVENUE & RANKED BADGES) --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 2a1 1 0 01.932.638l2.164 5.05 5.534.804a1 1 0 01.554 1.706l-4.004 3.902.945 5.51a1 1 0 01-1.451 1.054L10 17.643l-4.947 2.602a1 1 0 01-1.451-1.054l.945-5.51-4.004-3.902a1 1 0 01.554-1.706l5.534-.804 2.164-5.05A1 1 0 0110 2z"
                        clip-rule="evenodd"></path>
                </svg>
                Peringkat Tertinggi
            </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Top Products --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-5 border-b border-gray-50 pb-3">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Top 5 Produk</h3>
                </div>
                <div class="flex flex-col gap-1">
                    @forelse($topProducts as $index => $tp)
                        <div class="flex items-center justify-between py-2 group cursor-default">
                            <div class="flex items-center gap-4 overflow-hidden">
                                <span
                                    class="text-sm font-bold w-4 text-center {{ $index === 0 ? 'text-amber-500' : ($index === 1 ? 'text-slate-400' : ($index === 2 ? 'text-orange-400' : 'text-slate-300')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div class="overflow-hidden">
                                    <p class="text-sm font-semibold text-slate-800 truncate transition-colors duration-200
                                    {{ $index === 0 ? 'group-hover:text-amber-600' : ($index === 1 ? 'group-hover:text-slate-600' : ($index === 2 ? 'group-hover:text-orange-600' : 'group-hover:text-blue-600')) }}"
                                        title="{{ $tp['name'] }}">
                                        {{ $tp['name'] }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">Rp
                                        {{ number_format($tp['total_revenue'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="text-right pl-3 flex-shrink-0">
                                {{-- RANK-BASED HIGHLIGHTED PCS BADGE --}}
                                <span
                                    class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-bold border shadow-sm transition-all duration-300
                                {{ $index === 0
                                    ? 'bg-amber-50 text-amber-700 border-amber-200 group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500'
                                    : ($index === 1
                                        ? 'bg-slate-100 text-slate-700 border-slate-200 group-hover:bg-slate-500 group-hover:text-white group-hover:border-slate-500'
                                        : ($index === 2
                                            ? 'bg-orange-50 text-orange-700 border-orange-200 group-hover:bg-orange-500 group-hover:text-white group-hover:border-orange-500'
                                            : 'bg-gray-50 text-gray-500 border-gray-100 group-hover:bg-gray-500 group-hover:text-white group-hover:border-gray-500')) }}">
                                    {{ $tp['total_qty'] }} <span
                                        class="text-[10px] font-medium ml-1 uppercase opacity-80 tracking-wider">Pcs</span>
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <p class="text-sm text-slate-400">Belum ada data produk.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Top Sales --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-5 border-b border-gray-50 pb-3">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Top 5 Kasir/Sales</h3>
                </div>
                <div class="flex flex-col gap-1">
                    @forelse($topSales as $index => $ts)
                        <div class="flex items-center justify-between py-2 group cursor-default">
                            <div class="flex items-center gap-4 overflow-hidden">
                                <span
                                    class="text-sm font-bold w-4 text-center {{ $index === 0 ? 'text-amber-500' : ($index === 1 ? 'text-slate-400' : ($index === 2 ? 'text-orange-400' : 'text-slate-300')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div class="overflow-hidden">
                                    <p class="text-sm font-semibold text-slate-800 truncate transition-colors duration-200
                                    {{ $index === 0 ? 'group-hover:text-amber-600' : ($index === 1 ? 'group-hover:text-slate-600' : ($index === 2 ? 'group-hover:text-orange-600' : 'group-hover:text-blue-600')) }}"
                                        title="{{ $ts['name'] }}">
                                        {{ $ts['name'] }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">Rp
                                        {{ number_format($ts['total_revenue'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="text-right pl-3 flex-shrink-0">
                                {{-- RANK-BASED HIGHLIGHTED PCS BADGE --}}
                                <span
                                    class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-bold border shadow-sm transition-all duration-300
                                {{ $index === 0
                                    ? 'bg-amber-50 text-amber-700 border-amber-200 group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500'
                                    : ($index === 1
                                        ? 'bg-slate-100 text-slate-700 border-slate-200 group-hover:bg-slate-500 group-hover:text-white group-hover:border-slate-500'
                                        : ($index === 2
                                            ? 'bg-orange-50 text-orange-700 border-orange-200 group-hover:bg-orange-500 group-hover:text-white group-hover:border-orange-500'
                                            : 'bg-gray-50 text-gray-500 border-gray-100 group-hover:bg-gray-500 group-hover:text-white group-hover:border-gray-500')) }}">
                                    {{ $ts['total_qty'] }} <span
                                        class="text-[10px] font-medium ml-1 uppercase opacity-80 tracking-wider">Pcs</span>
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <p class="text-sm text-slate-400">Belum ada data kasir.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Top Brands --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-5 border-b border-gray-50 pb-3">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Top 5 Brand</h3>
                </div>
                <div class="flex flex-col gap-1">
                    @forelse($topBrands as $index => $tb)
                        <div class="flex items-center justify-between py-2 group cursor-default">
                            <div class="flex items-center gap-4 overflow-hidden">
                                <span
                                    class="text-sm font-bold w-4 text-center {{ $index === 0 ? 'text-amber-500' : ($index === 1 ? 'text-slate-400' : ($index === 2 ? 'text-orange-400' : 'text-slate-300')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div class="overflow-hidden">
                                    <p class="text-sm font-semibold text-slate-800 truncate transition-colors duration-200
                                    {{ $index === 0 ? 'group-hover:text-amber-600' : ($index === 1 ? 'group-hover:text-slate-600' : ($index === 2 ? 'group-hover:text-orange-600' : 'group-hover:text-blue-600')) }}"
                                        title="{{ $tb['name'] }}">
                                        {{ $tb['name'] }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">Rp
                                        {{ number_format($tb['total_revenue'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="text-right pl-3 flex-shrink-0">
                                {{-- RANK-BASED HIGHLIGHTED PCS BADGE --}}
                                <span
                                    class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-bold border shadow-sm transition-all duration-300
                                {{ $index === 0
                                    ? 'bg-amber-50 text-amber-700 border-amber-200 group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500'
                                    : ($index === 1
                                        ? 'bg-slate-100 text-slate-700 border-slate-200 group-hover:bg-slate-500 group-hover:text-white group-hover:border-slate-500'
                                        : ($index === 2
                                            ? 'bg-orange-50 text-orange-700 border-orange-200 group-hover:bg-orange-500 group-hover:text-white group-hover:border-orange-500'
                                            : 'bg-gray-50 text-gray-500 border-gray-100 group-hover:bg-gray-500 group-hover:text-white group-hover:border-gray-500')) }}">
                                    {{ $tb['total_qty'] }} <span
                                        class="text-[10px] font-medium ml-1 uppercase opacity-80 tracking-wider">Pcs</span>
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <p class="text-sm text-slate-400">Belum ada data brand.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    {{-- SECTION 3: ANALYTICS CHARTS --}}
    <div class="grid grid-cols-1 gap-6 mb-8">
        {{-- Trend Chart --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Tren Omzet Penjualan</h2>
            <div id="chart-trend" wire:ignore class="w-full h-80"></div>
        </div>

        {{-- Donuts (Brand & Payment side by side if possible, or stacked) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
                <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Proporsi Brand</h2>
                <div id="chart-brand-proportion" wire:ignore class="w-full flex justify-center items-center"></div>
            </div>

            {{-- <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
                <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Metode Bayar</h2>
                <div id="chart-payment-method" wire:ignore class="w-full flex justify-center items-center"></div>
            </div> --}}
        </div>
    </div>

    {{-- SECTION 4: MONTH-TO-DATE (MTD) ANALYTICS --}}
    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-t pt-8">
        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        Analisis Month-To-Date (MTD)
    </h2>
    <p class="text-sm text-gray-500 mb-4">Membandingkan capaian mutlak dari tanggal 1 hingga hari ini di bulan
        berjalan, versus tanggal 1 hingga hari yang sama di bulan lalu.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pb-8">
        {{-- MTD Net Sales --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">MTD Omzet (Net)</p>
                <h3 class="text-xl font-bold text-gray-800">Rp
                    {{ number_format($mtdData['net_sales']['current'], 0, ',', '.') }}</h3>
            </div>
            <div class="text-right">
                @if ($mtdData['net_sales']['growth'] >= 0)
                    <span
                        class="inline-flex items-center text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $mtdData['net_sales']['growth'] }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        {{ $mtdData['net_sales']['growth'] }}%
                    </span>
                @endif
            </div>
        </div>

        {{-- MTD Transactions --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">MTD Transaksi</p>
                <h3 class="text-xl font-bold text-gray-800">
                    {{ number_format($mtdData['transactions']['current'], 0, ',', '.') }}</h3>
            </div>
            <div class="text-right">
                @if ($mtdData['transactions']['growth'] >= 0)
                    <span
                        class="inline-flex items-center text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $mtdData['transactions']['growth'] }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        {{ $mtdData['transactions']['growth'] }}%
                    </span>
                @endif
            </div>
        </div>

        {{-- MTD QTY --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">MTD Qty Terjual</p>
                <h3 class="text-xl font-bold text-gray-800">
                    {{ number_format($mtdData['qty']['current'], 0, ',', '.') }}</h3>
            </div>
            <div class="text-right">
                @if ($mtdData['qty']['growth'] >= 0)
                    <span
                        class="inline-flex items-center text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $mtdData['qty']['growth'] }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        {{ $mtdData['qty']['growth'] }}%
                    </span>
                @endif
            </div>
        </div>

        {{-- MTD Discount --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">MTD Total Diskon</p>
                <h3 class="text-xl font-bold text-red-500">Rp
                    {{ number_format($mtdData['discount']['current'], 0, ',', '.') }}</h3>
            </div>
            <div class="text-right">
                @if ($mtdData['discount']['growth'] >= 0)
                    {{-- Pertumbuhan diskon (biaya) mungkin dianggap negatif secara finansial, tapi kita tampilkan growth riil saja --}}
                    <span
                        class="inline-flex items-center text-xs font-bold text-gray-600 bg-gray-100 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $mtdData['discount']['growth'] }}%
                    </span>
                @else
                    <span
                        class="inline-flex items-center text-xs font-bold text-gray-600 bg-gray-100 px-2 py-1 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        {{ $mtdData['discount']['growth'] }}%
                    </span>
                @endif
            </div>
        </div>
    </div>


    {{-- Alpine Component Logic for ApexCharts --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dashboardAnalytics', () => ({
                charts: {
                    trend: null,
                    brandProportion: null,
                    paymentMethod: null
                },

                initData: {
                    trend: @json($trendData),
                    brandProportion: @json($brandProportionData),
                    paymentMethod: @json($paymentMethodData)
                },

                init() {
                    this.initAllCharts();

                    Livewire.on('update-charts', (data) => {
                        let payload = data[0];
                        if (payload) {
                            this.updateCharts(payload);
                        }
                    });
                },

                initAllCharts() {
                    // Trend Chart
                    let trendOptions = {
                        series: [{
                            name: 'Net Sales',
                            data: this.initData.trend.series
                        }],
                        chart: {
                            type: 'area',
                            height: 320,
                            fontFamily: 'inherit',
                            toolbar: {
                                show: false
                            },
                            zoom: {
                                enabled: false
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        colors: ['#1c69d4'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.4,
                                opacityTo: 0.05,
                                stops: [0, 90, 100]
                            }
                        },
                        xaxis: {
                            categories: this.initData.trend.labels,
                            tooltip: {
                                enabled: false
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: (value) => "Rp " + new Intl.NumberFormat('id-ID').format(
                                    value)
                            }
                        }
                    };
                    this.charts.trend = new ApexCharts(document.querySelector("#chart-trend"),
                        trendOptions);
                    this.charts.trend.render();

                    // Brand Proportion Donut Chart
                    let brandPropOptions = {
                        series: this.initData.brandProportion.series,
                        labels: this.initData.brandProportion.labels,
                        chart: {
                            type: 'donut',
                            height: 320,
                            fontFamily: 'inherit'
                        },
                        colors: ['#1c69d4', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#6b7280'],
                        dataLabels: {
                            enabled: false
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return new Intl.NumberFormat('id-ID').format(val) + " Pcs"
                                }
                            }
                        }
                    };
                    this.charts.brandProportion = new ApexCharts(document.querySelector(
                        "#chart-brand-proportion"), brandPropOptions);
                    this.charts.brandProportion.render();

                    // Payment Method Donut Chart
                    let payOptions = {
                        series: this.initData.paymentMethod.series,
                        labels: this.initData.paymentMethod.labels,
                        chart: {
                            type: 'donut',
                            height: 320,
                            fontFamily: 'inherit'
                        },
                        colors: ['#3b82f6', '#f43f5e', '#14b8a6', '#6366f1', '#eab308', '#94a3b8'],
                        dataLabels: {
                            enabled: false
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return "Rp " + new Intl.NumberFormat('id-ID').format(val)
                                }
                            }
                        }
                    };
                    this.charts.paymentMethod = new ApexCharts(document.querySelector(
                        "#chart-payment-method"), payOptions);
                    this.charts.paymentMethod.render();
                },

                updateCharts(newData) {
                    this.charts.trend.updateOptions({
                        series: [{
                            data: newData.trend.series
                        }],
                        xaxis: {
                            categories: newData.trend.labels
                        }
                    });

                    this.charts.brandProportion.updateOptions({
                        series: newData.brandProportion.series,
                        labels: newData.brandProportion.labels
                    });

                    this.charts.paymentMethod.updateOptions({
                        series: newData.paymentMethod.series,
                        labels: newData.paymentMethod.labels
                    });
                }
            }));
        });
    </script>
</div>
