<div class="p-6 bg-[#f7f7f7] min-h-screen">
    {{-- Header & Filters --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Penjualan</h1>
            <p class="text-sm text-gray-500 mt-1">Ringkasan performa penjualan dan operasional</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <button wire:click="exportCsv" class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export CSV
            </button>

            <div class="flex items-center gap-3 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
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

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50 rounded-full opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Net Sales</p>
            <h3 class="text-xl font-black text-gray-800">Rp {{ number_format($totalNet, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Gross Sales</p>
            <h3 class="text-lg font-bold text-gray-700">Rp {{ number_format($totalGross, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Diskon</p>
            <h3 class="text-lg font-bold text-red-500">- Rp {{ number_format($totalDiscount, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Biaya MDR</p>
            <h3 class="text-lg font-bold text-orange-500">- Rp {{ number_format($totalMdr, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Transaksi</p>
            <h3 class="text-lg font-bold text-gray-800">{{ number_format($totalTransactions) }} <span class="text-xs font-medium text-gray-400">Order</span></h3>
        </div>
    </div>

    {{-- Charts Area --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        {{-- Trend Chart (Span 2 cols) --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-5" wire:ignore>
            <h3 class="text-sm font-bold text-gray-800 mb-4">Tren Penjualan (Net)</h3>
            <div id="trendChart" class="w-full h-[300px]"></div>
        </div>

        {{-- Payment Methods (Span 1 col) --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-5" wire:ignore>
            <h3 class="text-sm font-bold text-gray-800 mb-4">Metode Pembayaran</h3>
            <div id="paymentChart" class="w-full h-[300px]"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Branch Performance --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-5" wire:ignore>
            <h3 class="text-sm font-bold text-gray-800 mb-4">Kinerja Cabang</h3>
            <div id="branchChart" class="w-full h-[250px]"></div>
        </div>

        {{-- Sales Performance --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-5" wire:ignore>
            <h3 class="text-sm font-bold text-gray-800 mb-4">Kinerja Sales</h3>
            <div id="salesChart" class="w-full h-[250px]"></div>
        </div>

        {{-- Top Products List (Simple Bars) --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-5">
            <h3 class="text-sm font-bold text-gray-800 mb-4">10 Produk Terlaris</h3>
            <div class="space-y-4 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($topProducts as $idx => $prod)
                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <p class="text-xs font-semibold text-gray-700 truncate pr-2" title="{{ $prod['name'] }}">{{ $idx + 1 }}. {{ $prod['name'] }}</p>
                            <p class="text-xs font-bold text-gray-900">{{ $prod['total_qty'] }}x</p>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            @php 
                                $maxQty = count($topProducts) > 0 ? $topProducts[0]['total_qty'] : 1;
                                $width = ($prod['total_qty'] / $maxQty) * 100;
                            @endphp
                            <div class="bg-[#1c69d4] h-1.5 rounded-full" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 text-sm py-8">Belum ada data produk</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- CDN for ApexCharts --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        document.addEventListener('livewire:initialized', () => {
            let trendChart, paymentChart, branchChart, salesChart;

            const formatRp = (value) => {
                return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            };

            const initCharts = () => {
                // Get data from Livewire component
                const trendData = @this.trendData;
                const paymentData = @this.paymentMethodData;
                const branchData = @this.branchData;
                const salesData = @this.salesData;

                // 1. Trend Chart
                if(document.querySelector("#trendChart")) {
                    const trendOptions = {
                        series: [{
                            name: 'Penjualan',
                            data: trendData.series
                        }],
                        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                        colors: ['#1c69d4'],
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.0, stops: [0, 90, 100] } },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 3 },
                        xaxis: { categories: trendData.labels, tooltip: { enabled: false } },
                        yaxis: { labels: { formatter: (value) => { return formatRp(value); } } },
                        tooltip: { y: { formatter: function (val) { return formatRp(val); } } }
                    };
                    trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
                    trendChart.render();
                }

                // 2. Payment Donut Chart
                if(document.querySelector("#paymentChart") && paymentData.length > 0) {
                    const paymentOptions = {
                        series: paymentData.map(item => item.total),
                        labels: paymentData.map(item => item.name),
                        chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
                        colors: ['#1c69d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'],
                        plotOptions: { pie: { donut: { size: '65%' } } },
                        dataLabels: { enabled: false },
                        legend: { position: 'bottom' },
                        tooltip: { y: { formatter: function (val) { return formatRp(val); } } }
                    };
                    paymentChart = new ApexCharts(document.querySelector("#paymentChart"), paymentOptions);
                    paymentChart.render();
                } else if (document.querySelector("#paymentChart")) {
                    document.querySelector("#paymentChart").innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Data kosong</div>';
                }

                // 3. Branch Bar Chart
                if(document.querySelector("#branchChart") && branchData.length > 0) {
                    const branchOptions = {
                        series: [{ name: 'Penjualan', data: branchData.map(item => item.total) }],
                        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                        plotOptions: { bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' } } },
                        colors: ['#10b981'],
                        dataLabels: { enabled: false },
                        xaxis: { categories: branchData.map(item => item.store), labels: { formatter: function (val) { return formatRp(val); } } },
                        tooltip: { y: { formatter: function (val) { return formatRp(val); } } }
                    };
                    branchChart = new ApexCharts(document.querySelector("#branchChart"), branchOptions);
                    branchChart.render();
                } else if (document.querySelector("#branchChart")) {
                    document.querySelector("#branchChart").innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Data kosong</div>';
                }

                // 4. Sales Bar Chart
                if(document.querySelector("#salesChart") && salesData.length > 0) {
                    const salesOptions = {
                        series: [{ name: 'Penjualan', data: salesData.map(item => item.total) }],
                        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                        colors: ['#f59e0b'],
                        dataLabels: { enabled: false },
                        xaxis: { categories: salesData.map(item => item.name), labels: { formatter: function (val) { return formatRp(val); } } },
                        tooltip: { y: { formatter: function (val) { return formatRp(val); } } }
                    };
                    salesChart = new ApexCharts(document.querySelector("#salesChart"), salesOptions);
                    salesChart.render();
                } else if (document.querySelector("#salesChart")) {
                    document.querySelector("#salesChart").innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Data kosong</div>';
                }
            };

            initCharts();

            // Re-render charts when Livewire component updates
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    if(trendChart) trendChart.destroy();
                    if(paymentChart) paymentChart.destroy();
                    if(branchChart) branchChart.destroy();
                    if(salesChart) salesChart.destroy();
                    
                    document.querySelector("#paymentChart").innerHTML = '';
                    document.querySelector("#branchChart").innerHTML = '';
                    document.querySelector("#salesChart").innerHTML = '';

                    setTimeout(() => {
                        initCharts();
                    }, 50);
                })
            });
        });
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</div>
