<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Penjualan</h1>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi transaksi dan performa penjualan per vendor untuk seluruh cabang</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-3 w-full">
            {{-- Separator CSV --}}
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center justify-between">
                <span class="text-xs text-gray-500 mr-2 font-medium">Separator:</span>
                <select wire:model="csvSeparator"
                    class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 p-0 font-medium cursor-pointer w-full text-right truncate">
                    <option value=";">Semicolon (;)</option>
                    <option value=",">Comma (,)</option>
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
                <!-- Trigger Button -->
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

                <!-- Dropdown Search Panel -->
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 top-full mt-1.5 w-64 max-h-72 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50 flex flex-col"
                     style="display: none;">
                    
                    <!-- Search Input -->
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

                    <!-- Vendor List -->
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
                <!-- Trigger Button -->
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

                <!-- Dropdown Search Panel -->
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 top-full mt-1.5 w-64 max-h-72 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50 flex flex-col"
                     style="display: none;">
                    
                    <!-- Search Input -->
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

                    <!-- Project List -->
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

            {{-- Filter Rentang Tanggal (Default: Bulan Ini) --}}
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
                @if ($activeTab === 'transactions')
                    {{-- Tombol CSV Transaksi --}}
                    <button wire:click="exportCsvOpsi3" wire:loading.attr="disabled"
                        class="flex-1 flex items-center justify-center gap-1.5 bg-slate-700 hover:bg-slate-800 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                        <svg wire:loading.remove wire:target="exportCsvOpsi3" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading wire:target="exportCsvOpsi3" class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportCsvOpsi3">Export CSV</span>
                        <span wire:loading wire:target="exportCsvOpsi3">Memproses...</span>
                    </button>

                    {{-- Tombol Excel Transaksi --}}
                    <button wire:click="exportExcelOpsi3" wire:loading.attr="disabled"
                        class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                        <svg wire:loading.remove wire:target="exportExcelOpsi3" class="w-4 h-4 shrink-0 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading wire:target="exportExcelOpsi3" class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportExcelOpsi3">Export Excel</span>
                        <span wire:loading wire:target="exportExcelOpsi3">Memproses...</span>
                    </button>
                @else
                    {{-- Tombol CSV Vendor --}}
                    <button wire:click="exportVendorCsv" wire:loading.attr="disabled"
                        class="flex-1 flex items-center justify-center gap-1.5 bg-slate-700 hover:bg-slate-800 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                        <svg wire:loading.remove wire:target="exportVendorCsv" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading wire:target="exportVendorCsv" class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportVendorCsv">CSV Vendor</span>
                        <span wire:loading wire:target="exportVendorCsv">Memproses...</span>
                    </button>

                    {{-- Tombol Excel Vendor --}}
                    <button wire:click="exportVendorExcel" wire:loading.attr="disabled"
                        class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                        <svg wire:loading.remove wire:target="exportVendorExcel" class="w-4 h-4 shrink-0 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading wire:target="exportVendorExcel" class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportVendorExcel">Excel Vendor</span>
                        <span wire:loading wire:target="exportVendorExcel">Memproses...</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-6 border-b border-gray-200 pb-px">
        <button wire:click="$set('activeTab', 'transactions')" 
            class="px-4 py-2.5 text-sm font-bold border-b-2 transition-all duration-150 flex items-center gap-2 {{ $activeTab === 'transactions' ? 'border-[#1c69d4] text-[#1c69d4]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Semua Transaksi
        </button>
        <button wire:click="$set('activeTab', 'vendor')" 
            class="px-4 py-2.5 text-sm font-bold border-b-2 transition-all duration-150 flex items-center gap-2 {{ $activeTab === 'vendor' ? 'border-[#1c69d4] text-[#1c69d4]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Penjualan Per Vendor (Semua Cabang)
        </button>
    </div>

    {{-- Summary Cards --}}
    @if ($activeTab === 'transactions')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Transaksi</p>
                <h3 class="text-xl font-black text-gray-800">{{ number_format($summary['count']) }} <span class="text-sm font-medium text-gray-400">Nota</span></h3>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Gross</p>
                <h3 class="text-xl font-bold text-gray-700">Rp {{ number_format($summary['gross'], 0, ',', '.') }}</h3>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50 rounded-full opacity-50"></div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Net Sales</p>
                <h3 class="text-xl font-black text-[#1c69d4]">Rp {{ number_format($summary['net'], 0, ',', '.') }}</h3>
            </div>
        </div>
    @else
        @php
            $totalVendorCount = count($vendorSummary);
            $totalVendorQty = array_sum(array_column($vendorSummary, 'qty'));
            $totalVendorNet = array_sum(array_column($vendorSummary, 'net'));
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Vendor Aktif</p>
                <h3 class="text-xl font-black text-gray-800">{{ number_format($totalVendorCount) }} <span class="text-sm font-medium text-gray-400">Supplier/Vendor</span></h3>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Unit Terjual</p>
                <h3 class="text-xl font-bold text-gray-700">{{ number_format($totalVendorQty) }} <span class="text-sm font-medium text-gray-400">Unit / Pcs</span></h3>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-50 rounded-full opacity-50"></div>
                <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">Total Net Sales Vendor</p>
                <h3 class="text-xl font-black text-emerald-600">Rp {{ number_format($totalVendorNet, 0, ',', '.') }}</h3>
            </div>
        </div>
    @endif

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h3 class="font-bold text-gray-700 text-sm">
                {{ $activeTab === 'transactions' ? 'Daftar Transaksi' : 'Rekap Penjualan Per Vendor (Semua Cabang, Bulan Ini)' }}
            </h3>
            <div class="relative w-full sm:w-auto">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="{{ $activeTab === 'transactions' ? 'Cari No Order / Pelanggan / Sales...' : 'Cari Nama Vendor...' }}"
                    class="w-full sm:w-80 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        @if ($activeTab === 'transactions')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                            <th class="px-5 py-4 font-bold">Tanggal & Nota</th>
                            <th class="px-5 py-4 font-bold">Pelanggan & Sales</th>
                            <th class="px-5 py-4 font-bold">Cabang</th>
                            <th class="px-5 py-4 font-bold">Proyek</th>
                            <th class="px-5 py-4 font-bold">Pembayaran</th>
                            <th class="px-5 py-4 font-bold text-right">Gross</th>
                            <th class="px-5 py-4 font-bold text-right">Potongan</th>
                            <th class="px-5 py-4 font-bold text-right">Net Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($orders as $order)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3">
                                    <p class="text-xs font-semibold text-gray-800">{{ $order->created_at->format('d M Y H:i') }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5 font-mono">{{ $order->order_number }}</p>
                                    @if ($order->accurate_invoice_no)
                                        <span class="inline-block mt-1 px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">Accurate: {{ $order->accurate_invoice_no }}</span>
                                    @endif
                                    <div class="mt-2 text-[10px] text-gray-500 leading-tight">
                                        @foreach ($order->items as $item)
                                            @php
                                                $variant = $item->variant;
                                                $name = $variant?->name ?? ($variant?->product?->name ?? ($item->product_name ?? 'Unknown Product'));
                                            @endphp
                                            <div class="truncate w-40 sm:w-48" title="{{ $name }}">
                                                {{ $name }} ({{ $item->qty }}x)
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-xs font-bold text-gray-800">{{ $order->user ? $order->user->name : 'Walk-in' }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        {{ $order->salesBy ? $order->salesBy->name : '-' }}
                                    </p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md text-[11px] font-medium">
                                        {{ $order->shipping_address_snapshot['store'] ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @php
                                        $projects = collect($order->items)->map(function ($item) {
                                            return $item->variant?->proyek;
                                        })->filter()->unique()->implode(', ');
                                    @endphp
                                    <span class="text-[11px] font-medium text-gray-700">
                                        {{ empty($projects) ? '-' : $projects }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-xs font-medium text-gray-700">
                                    {{ $order->payments->first()->paymentMethod->name ?? '-' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <p class="text-xs font-bold text-gray-800">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if ($order->discount_amount > 0)
                                        <p class="text-[11px] text-red-500 font-medium">Diskon: Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</p>
                                    @endif
                                    @if ($order->mdr_amount > 0)
                                        <p class="text-[11px] text-orange-500 font-medium">MDR: Rp {{ number_format($order->mdr_amount, 0, ',', '.') }}</p>
                                    @endif
                                    @if ($order->discount_amount == 0 && $order->mdr_amount == 0)
                                        <p class="text-[11px] text-gray-400">-</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @php $net = $order->grand_total - $order->mdr_amount; @endphp
                                    <p class="text-sm font-black text-[#1c69d4]">Rp {{ number_format($net, 0, ',', '.') }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-gray-400 text-sm">
                                    Tidak ada transaksi yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-100 bg-white">
                {{ $orders->links() }}
            </div>
        @else
            {{-- Tabel Rekap Vendor --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                            <th class="px-5 py-4 font-bold w-12 text-center">No</th>
                            <th class="px-5 py-4 font-bold">Nama Vendor / Supplier</th>
                            <th class="px-5 py-4 font-bold">Distribusi Cabang</th>
                            <th class="px-5 py-4 font-bold text-center">Total Qty</th>
                            <th class="px-5 py-4 font-bold text-right">Gross Sales</th>
                            <th class="px-5 py-4 font-bold text-right">Total Diskon</th>
                            <th class="px-5 py-4 font-bold text-right">Net Sales</th>
                            <th class="px-5 py-4 font-bold text-center">Jumlah Nota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($vendorSummary as $index => $vnd)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3 text-center text-xs text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#1c69d4] flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($vnd['vendor_name'], 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800">{{ $vnd['vendor_name'] }}</p>
                                            <span class="text-[10px] text-gray-400 font-medium">{{ count($vnd['branches']) }} Cabang Aktif</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-1.5 max-w-sm">
                                        @foreach ($vnd['branches'] as $bName => $bData)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-700 rounded-md text-[10px] font-medium" title="Gross: Rp {{ number_format($bData['gross']) }}">
                                                <span class="font-semibold">{{ $bName }}:</span>
                                                <span class="text-blue-600 font-bold">{{ number_format($bData['qty']) }}x</span>
                                                <span class="text-gray-500 text-[9px]">(Rp {{ number_format($bData['net'] / 1000000, 1) }}M)</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-center text-xs font-semibold text-gray-700">{{ number_format($vnd['qty']) }}</td>
                                <td class="px-5 py-3 text-right text-xs text-gray-700">Rp {{ number_format($vnd['gross'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-right text-xs text-red-500">Rp {{ number_format($vnd['discount'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-right text-xs font-black text-[#1c69d4]">Rp {{ number_format($vnd['net'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-center text-xs font-semibold text-gray-700">{{ number_format($vnd['transaction_count']) }} Nota</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-gray-400 text-sm">
                                    Tidak ada data penjualan vendor yang ditemukan pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
