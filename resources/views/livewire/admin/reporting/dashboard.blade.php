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

            <div class="flex flex-wrap items-center gap-2">
                <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                    <select wire:model.live="businessUnitFilter"
                        class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 pr-6 rounded-lg cursor-pointer">
                        <option value="">Semua Unit Usaha</option>
                        @foreach ($businessUnits as $bu)
                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                    <select wire:model.live="branchFilter"
                        class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 pr-6 rounded-lg cursor-pointer">
                        <option value="">Semua Cabang</option>
                        @foreach ($availableBranches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                    <select wire:model.live="dateRange"
                        class="border-none text-sm font-bold text-blue-600 focus:ring-0 bg-transparent p-0 pr-6 rounded-lg cursor-pointer">
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="last_7_days">7 Hari Terakhir</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="this_year">Tahun Ini</option>
                        <option value="custom">Kustom</option>
                    </select>
                </div>

                @if ($dateRange === 'custom')
                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                        <input type="date" wire:model.live="startDate"
                            class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700">
                        <span class="text-gray-400 text-sm font-bold">-</span>
                        <input type="date" wire:model.live="endDate"
                            class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700">
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- SECTION 1: TOP KPI CARDS (NUMBERS ONLY, BASED ON SELECTED DATE FILTER) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Net Sales --}}
        <div
            class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-5 shadow-lg text-white relative overflow-hidden flex flex-col justify-center">
            <p class="text-xs font-bold text-blue-200 uppercase tracking-wider mb-1">Keuntungan (Net Sales)</p>
            <h3 class="text-2xl font-black">Rp {{ number_format($totalNet, 0, ',', '.') }}</h3>
        </div>

        {{-- Gross Sales --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] flex flex-col justify-center">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Gross</p>
            <h3 class="text-xl font-bold text-gray-800">Rp {{ number_format($totalGross, 0, ',', '.') }}</h3>
        </div>

        {{-- Diskon --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] flex flex-col justify-center">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Diskon</p>
            <h3 class="text-xl font-bold text-red-500">- Rp {{ number_format($totalDiscount, 0, ',', '.') }}</h3>
        </div>

        {{-- Total QTY Sold --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] flex flex-col justify-center border-l-4 border-l-yellow-400">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Qty Terjual</p>
                <div class="text-yellow-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($totalQty, 0, ',', '.') }} <span
                    class="text-sm font-normal text-gray-500">Pcs</span></h3>
        </div>

        {{-- Total Transactions --}}
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] flex flex-col justify-center border-l-4 border-l-purple-500">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Transaksi</p>
                <div class="text-purple-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($totalTransactions, 0, ',', '.') }} <span
                    class="text-sm font-normal text-gray-500">Struk</span></h3>
        </div>
    </div>

    {{-- SECTION 2: TOP PERFORMERS (LISTS WITH REVENUE) --}}
    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                d="M10 2a1 1 0 01.932.638l2.164 5.05 5.534.804a1 1 0 01.554 1.706l-4.004 3.902.945 5.51a1 1 0 01-1.451 1.054L10 17.643l-4.947 2.602a1 1 0 01-1.451-1.054l.945-5.51-4.004-3.902a1 1 0 01.554-1.706l5.534-.804 2.164-5.05A1 1 0 0110 2z"
                clip-rule="evenodd"></path>
        </svg>
        Peringkat Tertinggi (Top Performers)
    </h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Top Products --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Top 5 Produk</h3>
            <div class="space-y-4">
                @forelse($topProducts as $index => $tp)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold">
                                {{ $index + 1 }}</div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 line-clamp-1" title="{{ $tp['name'] }}">
                                    {{ $tp['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $tp['total_qty'] }} Pcs Terjual</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600">Rp
                                {{ number_format($tp['total_revenue'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">Belum ada data.</p>
                @endforelse
            </div>
        </div>

        {{-- Top Sales --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Top 5 Kasir/Sales
            </h3>
            <div class="space-y-4">
                @forelse($topSales as $index => $ts)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold">
                                {{ $index + 1 }}</div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 line-clamp-1" title="{{ $ts['name'] }}">
                                    {{ $ts['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $ts['total_transactions'] }} Transaksi</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-blue-600">Rp
                                {{ number_format($ts['total_revenue'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">Belum ada data.</p>
                @endforelse
            </div>
        </div>

        {{-- Top Branches --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Top 5 Cabang</h3>
            <div class="space-y-4">
                @forelse($topBranches as $index => $tb)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-xs font-bold">
                                {{ $index + 1 }}</div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 line-clamp-1" title="{{ $tb['name'] }}">
                                    {{ $tb['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $tb['total_transactions'] }} Transaksi</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-purple-600">Rp
                                {{ number_format($tb['total_revenue'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">Belum ada data.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- SECTION 2.5: TRANSAKSI KASIR --}}
    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-t pt-8 mt-8">
        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        Laporan Transaksi Kasir
    </h2>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] overflow-hidden mb-8">
        <div class="overflow-x-auto overflow-y-auto" style="max-height: 400px;">
            <table class="w-full text-left border-collapse relative">
                <thead class="sticky top-0 z-10 shadow-sm">
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4 bg-gray-50">Nama Kasir</th>
                        <th class="px-6 py-4 bg-gray-50 text-center">Qty</th>
                        <th class="px-6 py-4 bg-gray-50 text-right">Amount (Struk)</th>
                        <th class="px-6 py-4 bg-gray-50 text-right">Cashback</th>
                        <th class="px-6 py-4 bg-gray-50 text-right">Promo</th>
                        <th class="px-6 py-4 bg-gray-50 text-right text-blue-600">Tunai</th>
                        <th class="px-6 py-4 bg-gray-50 text-right text-purple-600">Non-Tunai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($cashierData as $cData)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        {{ substr($cData['name'], 0, 1) }}
                                    </div>
                                    <span class="text-sm font-bold text-gray-800">{{ $cData['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center font-medium">{{ $cData['qty'] }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-800 text-right">Rp {{ number_format($cData['amount'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-red-500 text-right">Rp {{ number_format($cData['cashback'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-green-500 text-right">Rp {{ number_format($cData['promo'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-blue-600 text-right">Rp {{ number_format($cData['tunai'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-purple-600 text-right">Rp {{ number_format($cData['non_tunai'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                Belum ada data transaksi kasir pada rentang waktu ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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

            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
                <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Metode Bayar</h2>
                <div id="chart-payment-method" wire:ignore class="w-full flex justify-center items-center"></div>
            </div>
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
                                    return "Rp " + new Intl.NumberFormat('id-ID').format(val)
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
                        series: [{ data: newData.trend.series }],
                        xaxis: { categories: newData.trend.labels }
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
