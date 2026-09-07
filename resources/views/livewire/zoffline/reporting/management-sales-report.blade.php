<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col items-start mb-6 gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-2">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Penjualan Management</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                        Management & Profitability
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Rekapitulasi penjualan bersih, estimasi HPP riil/rata-rata, dan analisa margin keuntungan per item transaksi</p>
            </div>
            <div>
                <a href="{{ route('reporting.sales') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition shadow-sm">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Buka Laporan Operasional
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3 w-full">
            {{-- Separator CSV --}}
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center justify-between">
                <span class="text-xs text-gray-500 mr-2 font-medium">Sep:</span>
                <select wire:model="csvSeparator"
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

            {{-- Filter Vendor (Searchable Dropdown) --}}
            <div x-data="{
                open: false,
                search: '',
                selected: @entangle('vendorFilter').live,
                vendors: {{ json_encode($availableVendors) }},
                get filteredVendors() {
                    if (!this.search) return this.vendors;
                    return this.vendors.filter(v => v.toLowerCase().includes(this.search.toLowerCase()));
                },
                get displayLabel() {
                    if (!this.selected) return 'Semua Vendor';
                    if (this.selected === 'unknown') return 'Tanpa Vendor / Unknown';
                    return this.selected;
                },
                selectVendor(val) {
                    this.selected = val;
                    this.open = false;
                    this.search = '';
                }
            }" 
            @click.outside="open = false" 
            class="relative bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <div @click="open = !open" class="w-full flex items-center justify-between cursor-pointer select-none">
                    <span class="text-sm font-medium text-gray-700 truncate" x-text="displayLabel">Semua Vendor</span>
                    <div class="flex items-center gap-1 shrink-0 ml-1">
                        <button type="button" x-show="selected" @click.stop="selectVendor('')" class="text-gray-400 hover:text-gray-600 p-0.5" title="Reset filter vendor">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 top-full mt-1.5 w-64 max-h-72 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50 flex flex-col"
                     style="display: none;">
                    
                    <div class="px-2 pb-2 border-b border-gray-100">
                        <div class="relative">
                            <input x-model="search" 
                                   x-ref="vendorSearchInput"
                                   @keydown.escape="open = false"
                                   type="text" 
                                   placeholder="Ketik cari vendor..." 
                                   class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4]"
                                   x-init="$watch('open', value => { if (value) setTimeout(() => $refs.vendorSearchInput.focus(), 50) })">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1 max-h-56 divide-y divide-gray-50 text-xs">
                        <div @click="selectVendor('')" 
                             class="px-3 py-2 cursor-pointer hover:bg-blue-50 hover:text-[#1c69d4] flex items-center justify-between transition-colors"
                             :class="{'font-bold text-[#1c69d4] bg-blue-50/60': !selected}">
                            <span>Semua Vendor</span>
                            <span x-show="!selected" class="text-[#1c69d4] font-bold">✓</span>
                        </div>

                        <div @click="selectVendor('unknown')" 
                             class="px-3 py-2 cursor-pointer hover:bg-blue-50 hover:text-[#1c69d4] flex items-center justify-between transition-colors"
                             :class="{'font-bold text-[#1c69d4] bg-blue-50/60': selected === 'unknown'}">
                            <span>Tanpa Vendor / Unknown</span>
                            <span x-show="selected === 'unknown'" class="text-[#1c69d4] font-bold">✓</span>
                        </div>

                        <template x-for="v in filteredVendors" :key="v">
                            <div @click="selectVendor(v)" 
                                 class="px-3 py-2 cursor-pointer hover:bg-blue-50 hover:text-[#1c69d4] flex items-center justify-between transition-colors"
                                 :class="{'font-bold text-[#1c69d4] bg-blue-50/60': selected === v}">
                                <span x-text="v" class="truncate pr-2"></span>
                                <span x-show="selected === v" class="text-[#1c69d4] font-bold">✓</span>
                            </div>
                        </template>

                        <div x-show="filteredVendors.length === 0 && search" class="px-3 py-4 text-center text-gray-400 italic">
                            Vendor tidak ditemukan
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Proyek (Multi Select) --}}
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
                clearSelection() {
                    this.selected = [];
                }
            }" 
            @click.outside="open = false" 
            class="relative bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <div @click="open = !open" class="w-full flex items-center justify-between cursor-pointer select-none">
                    <span class="text-sm font-medium text-gray-700 truncate" x-text="displayLabel">Semua Proyek</span>
                    <div class="flex items-center gap-1 shrink-0 ml-1">
                        <button type="button" x-show="selected && selected.length > 0" @click.stop="clearSelection()" class="text-gray-400 hover:text-gray-600 p-0.5" title="Reset filter proyek">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 top-full mt-1.5 w-64 max-h-72 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50 flex flex-col"
                     style="display: none;">
                    
                    <div class="px-2 pb-2 border-b border-gray-100">
                        <div class="relative">
                            <input x-model="search" 
                                   x-ref="projectSearchInput"
                                   @keydown.escape="open = false"
                                   type="text" 
                                   placeholder="Ketik cari proyek..." 
                                   class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4]"
                                   x-init="$watch('open', value => { if (value) setTimeout(() => $refs.projectSearchInput.focus(), 50) })">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1 max-h-56 divide-y divide-gray-50 text-xs">
                        <template x-for="p in filteredProjects" :key="p">
                            <label class="px-3 py-2 cursor-pointer hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                <input type="checkbox" :value="p" x-model="selected" class="rounded text-[#1c69d4] focus:ring-[#1c69d4] w-3.5 h-3.5 border-gray-300">
                                <span x-text="p" class="truncate pr-2" :class="{'font-bold text-[#1c69d4]': selected && selected.includes(p), 'text-gray-700': !(selected && selected.includes(p))}"></span>
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

            {{-- Export Buttons (CSV & Excel) --}}
            <div class="flex items-center gap-2 col-span-1 {{ $dateRange === 'custom' ? 'lg:col-span-6' : 'lg:col-span-2' }}">
                {{-- Tombol CSV --}}
                <button wire:click="exportCsv" wire:loading.attr="disabled"
                    class="flex-1 flex items-center justify-center gap-1.5 bg-slate-700 hover:bg-slate-800 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                    <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                    <span wire:loading wire:target="exportCsv">Memproses...</span>
                </button>

                {{-- Tombol Excel --}}
                <button wire:click="exportExcel" wire:loading.attr="disabled"
                    class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                    <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4 shrink-0 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                    <span wire:loading wire:target="exportExcel">Memproses...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 5 Summary KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Transaksi</p>
            <h3 class="text-lg font-black text-gray-800">
                {{ number_format($summary['orders_count']) }} 
                <span class="text-xs font-normal text-gray-400">Nota</span>
            </h3>
            <p class="text-[11px] text-gray-400 mt-1 font-medium">{{ number_format($summary['items_count']) }} Item Terjual</p>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-[11px] font-bold text-blue-500 uppercase tracking-wider mb-1">Penjualan Bersih</p>
            <h3 class="text-lg font-black text-[#1c69d4]">
                Rp {{ number_format($summary['net_sales'], 0, ',', '.') }}
            </h3>
            <p class="text-[11px] text-gray-400 mt-1 font-medium">Omset Riil Transaksi</p>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-[11px] font-bold text-amber-500 uppercase tracking-wider mb-1">Total HPP (Modal)</p>
            <h3 class="text-lg font-black text-gray-800">
                Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}
            </h3>
            <p class="text-[11px] text-gray-400 mt-1 font-medium">SN Riil & Rata-rata Non-SN</p>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Total Margin (Laba)</p>
            <h3 class="text-lg font-black {{ $summary['total_margin'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                Rp {{ number_format($summary['total_margin'], 0, ',', '.') }}
            </h3>
            <p class="text-[11px] text-gray-400 mt-1 font-medium">Laba Kotor Transaksi</p>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="absolute -right-3 -top-3 w-14 h-14 bg-purple-50 rounded-full opacity-50"></div>
            <p class="text-[11px] font-bold text-purple-600 uppercase tracking-wider mb-1">Rata-rata Margin %</p>
            <h3 class="text-xl font-black {{ $summary['margin_pct'] >= 15 ? 'text-emerald-600' : ($summary['margin_pct'] >= 5 ? 'text-amber-600' : 'text-rose-600') }}">
                {{ number_format($summary['margin_pct'], 2) }}%
            </h3>
            <p class="text-[11px] text-gray-400 mt-1 font-medium">Margin / Penjualan Bersih</p>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h3 class="font-bold text-gray-800 text-sm">Rincian Transaksi Penjualan Management</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Menampilkan seluruh baris item pesanan dengan analisa HPP dan Margin</p>
            </div>
            <div class="relative w-full sm:w-auto">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari Order / Invoice / Sales / Produk / SN..."
                    class="w-full sm:w-80 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3.5 font-bold">Tanggal & Nota</th>
                        <th class="px-4 py-3.5 font-bold">Produk & SKU</th>
                        <th class="px-4 py-3.5 font-bold">Pelanggan & Sales</th>
                        <th class="px-4 py-3.5 font-bold">Cabang / Proyek</th>
                        <th class="px-4 py-3.5 font-bold text-center">Qty</th>
                        <th class="px-4 py-3.5 font-bold text-right">Penjualan Bersih</th>
                        <th class="px-4 py-3.5 font-bold text-right">HPP</th>
                        <th class="px-4 py-3.5 font-bold text-right">Margin (Rp)</th>
                        <th class="px-4 py-3.5 font-bold text-center">Margin (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-xs">
                    @forelse($items as $item)
                        @php
                            $order = $item->order;
                            $variant = $item->variant;
                            $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                            $sku = $variant?->item_no ?? $variant?->sku ?? $variant?->accurateData?->item_no ?? '-';
                            $proyek = $variant?->proyek ?? '-';
                            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

                            // Kalkulasi Penjualan Bersih
                            $itemPromosTotal = $item->promos->sum('pivot.discount_amount');
                            $actualSubtotal = $item->subtotal - ($item->discount_amount ?? 0) - $itemPromosTotal;
                            $penjualanBersih = round($actualSubtotal);

                            // Kalkulasi HPP (SN vs Non-SN HPP rata-rata)
                            $snList = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));
                            $itemHpp = 0;
                            $hasSn = !empty($snList);

                            if ($hasSn) {
                                foreach ($snList as $sn) {
                                    $snModel = $snDataMap->get($sn);
                                    $snHpp = (float)($snModel?->hpp ?? 0);
                                    if ($snHpp <= 0) {
                                        $snHpp = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                                    }
                                    $itemHpp += $snHpp;
                                }
                            } else {
                                $baseCost = (float)($variant?->base_cost ?? $variant?->accurateData?->base_cost ?? 0);
                                $itemHpp = $baseCost * (float)$item->qty;
                            }

                            $itemHpp = round($itemHpp);
                            $margin = $penjualanBersih - $itemHpp;
                            $marginPct = $penjualanBersih > 0 ? round(($margin / $penjualanBersih) * 100, 2) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            {{-- Tanggal & Nota --}}
                            <td class="px-4 py-3 align-top">
                                <p class="font-semibold text-gray-800">{{ $order->order_date ? $order->order_date->format('d M Y') : $order->created_at->format('d M Y') }}</p>
                                <p class="text-[11px] text-gray-500 font-mono mt-0.5">{{ $order->order_number }}</p>
                                @if ($order->accurate_invoice_no)
                                    <span class="inline-block mt-1 px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[10px] font-bold">
                                        Inv: {{ $order->accurate_invoice_no }}
                                    </span>
                                @endif
                                @if ($order->accurate_so_number)
                                    <span class="inline-block mt-1 px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px]">
                                        SO: {{ $order->accurate_so_number }}
                                    </span>
                                @endif
                            </td>

                            {{-- Produk & SKU & SN --}}
                            <td class="px-4 py-3 align-top max-w-xs">
                                <p class="font-bold text-gray-800 leading-snug line-clamp-2" title="{{ $name }}">{{ $name }}</p>
                                <p class="text-[11px] text-gray-400 font-mono mt-0.5">SKU: {{ $sku }}</p>
                                @if (!empty($snList))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach ($snList as $sn)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-700 font-medium" title="SN: {{ $sn }}">
                                                SN: {{ $sn }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="inline-block mt-1 text-[10px] text-gray-400 italic">Non-SN</span>
                                @endif
                            </td>

                            {{-- Pelanggan & Sales --}}
                            <td class="px-4 py-3 align-top">
                                <p class="font-bold text-gray-800">{{ $order->user ? $order->user->name : 'Walk-in' }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{ $order->salesBy ? $order->salesBy->name : '-' }}
                                </p>
                            </td>

                            {{-- Cabang & Proyek & Unit Bisnis --}}
                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[11px] font-medium">
                                        {{ $branch }}
                                    </span>
                                    @if ($order->businessUnit)
                                        <span class="inline-block px-1.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-100 rounded text-[10px] font-semibold">
                                            {{ $order->businessUnit->name }}
                                        </span>
                                    @endif
                                    @if ($proyek && $proyek !== '-')
                                        <span class="text-[10px] text-blue-600 font-medium">
                                            Proyek: {{ $proyek }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Qty --}}
                            <td class="px-4 py-3 text-center align-top font-bold text-gray-700">
                                {{ $item->qty }}
                            </td>

                            {{-- Penjualan Bersih --}}
                            <td class="px-4 py-3 text-right align-top font-bold text-[#1c69d4]">
                                Rp {{ number_format($penjualanBersih, 0, ',', '.') }}
                                @if ($item->discount_amount > 0 || $itemPromosTotal > 0)
                                    <p class="text-[10px] text-gray-400 font-normal">
                                        Disc: Rp {{ number_format(($item->discount_amount ?? 0) + $itemPromosTotal, 0, ',', '.') }}
                                    </p>
                                @endif
                            </td>

                            {{-- HPP --}}
                            <td class="px-4 py-3 text-right align-top">
                                <p class="font-bold text-gray-700">Rp {{ number_format($itemHpp, 0, ',', '.') }}</p>
                                <span class="inline-block text-[9px] font-semibold px-1 py-0.2 rounded {{ $hasSn ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $hasSn ? 'HPP SN' : 'HPP Avg' }}
                                </span>
                            </td>

                            {{-- Margin (Rp) --}}
                            <td class="px-4 py-3 text-right align-top">
                                <p class="font-bold {{ $margin >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    Rp {{ number_format($margin, 0, ',', '.') }}
                                </p>
                            </td>

                            {{-- Margin (%) --}}
                            <td class="px-4 py-3 text-center align-top">
                                @if ($marginPct >= 15)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ number_format($marginPct, 1) }}%
                                    </span>
                                @elseif ($marginPct >= 5)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ number_format($marginPct, 1) }}%
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ number_format($marginPct, 1) }}%
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-gray-400 text-sm">
                                Tidak ada data penjualan yang ditemukan pada periode dan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 bg-white">
            {{ $items->links() }}
        </div>
    </div>
</div>
