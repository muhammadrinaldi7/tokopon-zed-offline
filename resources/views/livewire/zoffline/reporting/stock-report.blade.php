<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Stok</h1>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi stok barang beserta nilainya</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            {{-- Export Excel --}}
            <button wire:click="exportExcel" wire:loading.attr="disabled"
                @if(!$hasAppliedFilter) disabled title="Terapkan filter terlebih dahulu untuk mengekspor data" @endif
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                <span wire:loading wire:target="exportExcel">Memproses...</span>
            </button>

            {{-- Separator CSV & Export CSV --}}
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <span class="text-xs text-gray-500 mr-2 font-medium">Separator:</span>
                <select wire:model="csvSeparator"
                    class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 p-0 font-medium cursor-pointer w-full text-right truncate">
                    <option value=";">Semicolon (;)</option>
                    <option value=",">Comma (,)</option>
                </select>
            </div>
            <button wire:click="exportCsv" wire:loading.attr="disabled"
                @if(!$hasAppliedFilter) disabled title="Terapkan filter terlebih dahulu untuk mengekspor data" @endif
                class="flex items-center gap-2 bg-green-500 hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Memproses...</span>
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Item Fisik</p>
            @if(!$hasAppliedFilter)
                <h3 class="text-xl font-black text-gray-300">- <span class="text-xs font-normal text-gray-400">Pilih filter</span></h3>
            @else
                <h3 class="text-xl font-black text-gray-800">{{ number_format($summary['total_items']) }} <span class="text-sm font-medium text-gray-400">Pcs</span></h3>
            @endif
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Nilai Modal</p>
            @if(!$hasAppliedFilter)
                <h3 class="text-xl font-bold text-gray-300">-</h3>
            @else
                <h3 class="text-xl font-bold text-red-500">Rp {{ number_format($summary['total_cost'], 0, ',', '.') }}</h3>
            @endif
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50 rounded-full opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Potensi Penjualan</p>
            @if(!$hasAppliedFilter)
                <h3 class="text-xl font-black text-gray-300">-</h3>
            @else
                <h3 class="text-xl font-black text-[#1c69d4]">Rp {{ number_format($summary['total_potential_revenue'], 0, ',', '.') }}</h3>
            @endif
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden relative">
        <div wire:loading wire:target="applyFilter,resetFilter,sortBy,gotoPage,nextPage,previousPage" class="absolute top-0 left-0 right-0 h-1 bg-[#1c69d4] animate-pulse z-20"></div>
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <h3 class="font-bold text-gray-700 text-sm whitespace-nowrap">Filter & Pencarian Stok</h3>
            <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                @if($isAdmin)
                    <div class="relative">
                        <select wire:model.live="filterBU" class="border-gray-200 rounded-xl text-xs sm:text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-2 px-3 bg-white" title="Filter Business Unit">
                            <option value="">Semua BU</option>
                            @foreach($businessUnits as $bu)
                                <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                {{-- Dropdown Multi-Select Merek --}}
                <div wire:key="multi-select-brand-bu-{{ $filterBU }}" x-data="{
                    open: false,
                    search: '',
                    selected: @entangle('filterBrand'),
                    allOptions: {{ json_encode($availableBrands->values()) }},
                    get filtered() {
                        if (!this.search.trim()) return this.allOptions;
                        return this.allOptions.filter(i => i.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get label() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === 0) return 'Semua Merek';
                        if (sel.length === 1) return sel[0];
                        return sel.length + ' Merek';
                    },
                    toggleAll() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === this.allOptions.length) {
                            this.selected = [];
                        } else {
                            this.selected = [...this.allOptions];
                        }
                    }
                }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="open = !open" 
                        class="border rounded-xl text-xs sm:text-sm py-2 pl-3 pr-7 bg-white hover:bg-gray-50/80 focus:outline-none flex items-center justify-between gap-1.5 shadow-sm transition-all duration-150 min-w-[130px] max-w-[180px]"
                        :class="(Array.isArray(selected) && selected.length > 0) ? 'border-[#1c69d4] text-[#1c69d4] font-semibold bg-blue-50/20' : 'border-gray-200 text-gray-700'"
                        title="Filter Merek (Multi-Select)">
                        <span class="truncate" x-text="label">Semua Merek</span>
                        <template x-if="Array.isArray(selected) && selected.length > 1">
                            <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-[#1c69d4] rounded-full shrink-0" x-text="selected.length"></span>
                        </template>
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute right-2 transition-transform duration-200 pointer-events-none" :class="{'rotate-180 text-[#1c69d4]': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 mt-1.5 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden flex flex-col py-1"
                        style="display: none;">
                        <template x-if="allOptions.length > 4">
                            <div class="px-2 py-1.5 border-b border-gray-100 bg-gray-50/70">
                                <input type="text" x-model="search" placeholder="Cari merek..." class="w-full px-2 py-1 text-xs border border-gray-200 rounded-lg focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] bg-white">
                            </div>
                        </template>
                        <div class="flex items-center justify-between px-3 py-1.5 border-b border-gray-100 text-[11px] text-gray-500 bg-gray-50/40">
                            <button type="button" @click="toggleAll()" class="hover:text-[#1c69d4] font-medium transition-colors" x-text="Array.isArray(selected) && selected.length === allOptions.length ? 'Batal Semua' : 'Pilih Semua'"></button>
                            <template x-if="Array.isArray(selected) && selected.length > 0">
                                <button type="button" @click="selected = []" class="text-red-500 hover:text-red-700 font-medium transition-colors">Reset</button>
                            </template>
                        </div>
                        <div class="overflow-y-auto max-h-48 divide-y divide-gray-50 py-0.5">
                            <template x-for="item in filtered" :key="item">
                                <label class="flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-blue-50/60 cursor-pointer transition-colors select-none">
                                    <input type="checkbox" :value="item" x-model="selected" class="rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                                    <span class="truncate text-gray-700 font-medium" :class="{'text-[#1c69d4] font-bold': Array.isArray(selected) && selected.includes(item)}" x-text="item"></span>
                                </label>
                            </template>
                            <template x-if="filtered.length === 0">
                                <div class="px-3 py-3 text-center text-xs text-gray-400">Tidak ada pilihan</div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Dropdown Multi-Select Proyek --}}
                <div wire:key="multi-select-proyek-bu-{{ $filterBU }}" x-data="{
                    open: false,
                    search: '',
                    selected: @entangle('filterProyek'),
                    allOptions: {{ json_encode($availableProyeks->values()) }},
                    get filtered() {
                        if (!this.search.trim()) return this.allOptions;
                        return this.allOptions.filter(i => i.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get label() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === 0) return 'Semua Proyek';
                        if (sel.length === 1) return sel[0];
                        return sel.length + ' Proyek';
                    },
                    toggleAll() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === this.allOptions.length) {
                            this.selected = [];
                        } else {
                            this.selected = [...this.allOptions];
                        }
                    }
                }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="open = !open" 
                        class="border rounded-xl text-xs sm:text-sm py-2 pl-3 pr-7 bg-white hover:bg-gray-50/80 focus:outline-none flex items-center justify-between gap-1.5 shadow-sm transition-all duration-150 min-w-[130px] max-w-[180px]"
                        :class="(Array.isArray(selected) && selected.length > 0) ? 'border-[#1c69d4] text-[#1c69d4] font-semibold bg-blue-50/20' : 'border-gray-200 text-gray-700'"
                        title="Filter Proyek (Multi-Select)">
                        <span class="truncate" x-text="label">Semua Proyek</span>
                        <template x-if="Array.isArray(selected) && selected.length > 1">
                            <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-[#1c69d4] rounded-full shrink-0" x-text="selected.length"></span>
                        </template>
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute right-2 transition-transform duration-200 pointer-events-none" :class="{'rotate-180 text-[#1c69d4]': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 mt-1.5 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden flex flex-col py-1"
                        style="display: none;">
                        <template x-if="allOptions.length > 4">
                            <div class="px-2 py-1.5 border-b border-gray-100 bg-gray-50/70">
                                <input type="text" x-model="search" placeholder="Cari proyek..." class="w-full px-2 py-1 text-xs border border-gray-200 rounded-lg focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] bg-white">
                            </div>
                        </template>
                        <div class="flex items-center justify-between px-3 py-1.5 border-b border-gray-100 text-[11px] text-gray-500 bg-gray-50/40">
                            <button type="button" @click="toggleAll()" class="hover:text-[#1c69d4] font-medium transition-colors" x-text="Array.isArray(selected) && selected.length === allOptions.length ? 'Batal Semua' : 'Pilih Semua'"></button>
                            <template x-if="Array.isArray(selected) && selected.length > 0">
                                <button type="button" @click="selected = []" class="text-red-500 hover:text-red-700 font-medium transition-colors">Reset</button>
                            </template>
                        </div>
                        <div class="overflow-y-auto max-h-48 divide-y divide-gray-50 py-0.5">
                            <template x-for="item in filtered" :key="item">
                                <label class="flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-blue-50/60 cursor-pointer transition-colors select-none">
                                    <input type="checkbox" :value="item" x-model="selected" class="rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                                    <span class="truncate text-gray-700 font-medium" :class="{'text-[#1c69d4] font-bold': Array.isArray(selected) && selected.includes(item)}" x-text="item"></span>
                                </label>
                            </template>
                            <template x-if="filtered.length === 0">
                                <div class="px-3 py-3 text-center text-xs text-gray-400">Tidak ada pilihan</div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Dropdown Multi-Select Kategori --}}
                <div wire:key="multi-select-category-bu-{{ $filterBU }}" x-data="{
                    open: false,
                    search: '',
                    selected: @entangle('filterCategory'),
                    allOptions: {{ json_encode($availableCategories->values()) }},
                    get filtered() {
                        if (!this.search.trim()) return this.allOptions;
                        return this.allOptions.filter(i => i.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get label() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === 0) return 'Semua Kategori';
                        if (sel.length === 1) return sel[0];
                        return sel.length + ' Kategori';
                    },
                    toggleAll() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === this.allOptions.length) {
                            this.selected = [];
                        } else {
                            this.selected = [...this.allOptions];
                        }
                    }
                }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="open = !open" 
                        class="border rounded-xl text-xs sm:text-sm py-2 pl-3 pr-7 bg-white hover:bg-gray-50/80 focus:outline-none flex items-center justify-between gap-1.5 shadow-sm transition-all duration-150 min-w-[130px] max-w-[180px]"
                        :class="(Array.isArray(selected) && selected.length > 0) ? 'border-[#1c69d4] text-[#1c69d4] font-semibold bg-blue-50/20' : 'border-gray-200 text-gray-700'"
                        title="Filter Kategori (Multi-Select)">
                        <span class="truncate" x-text="label">Semua Kategori</span>
                        <template x-if="Array.isArray(selected) && selected.length > 1">
                            <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-[#1c69d4] rounded-full shrink-0" x-text="selected.length"></span>
                        </template>
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute right-2 transition-transform duration-200 pointer-events-none" :class="{'rotate-180 text-[#1c69d4]': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 mt-1.5 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden flex flex-col py-1"
                        style="display: none;">
                        <template x-if="allOptions.length > 4">
                            <div class="px-2 py-1.5 border-b border-gray-100 bg-gray-50/70">
                                <input type="text" x-model="search" placeholder="Cari kategori..." class="w-full px-2 py-1 text-xs border border-gray-200 rounded-lg focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] bg-white">
                            </div>
                        </template>
                        <div class="flex items-center justify-between px-3 py-1.5 border-b border-gray-100 text-[11px] text-gray-500 bg-gray-50/40">
                            <button type="button" @click="toggleAll()" class="hover:text-[#1c69d4] font-medium transition-colors" x-text="Array.isArray(selected) && selected.length === allOptions.length ? 'Batal Semua' : 'Pilih Semua'"></button>
                            <template x-if="Array.isArray(selected) && selected.length > 0">
                                <button type="button" @click="selected = []" class="text-red-500 hover:text-red-700 font-medium transition-colors">Reset</button>
                            </template>
                        </div>
                        <div class="overflow-y-auto max-h-48 divide-y divide-gray-50 py-0.5">
                            <template x-for="item in filtered" :key="item">
                                <label class="flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-blue-50/60 cursor-pointer transition-colors select-none">
                                    <input type="checkbox" :value="item" x-model="selected" class="rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                                    <span class="truncate text-gray-700 font-medium" :class="{'text-[#1c69d4] font-bold': Array.isArray(selected) && selected.includes(item)}" x-text="item"></span>
                                </label>
                            </template>
                            <template x-if="filtered.length === 0">
                                <div class="px-3 py-3 text-center text-xs text-gray-400">Tidak ada pilihan</div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Dropdown Multi-Select Gudang --}}
                <div wire:key="multi-select-warehouse-bu-{{ $filterBU }}" x-data="{
                    open: false,
                    search: '',
                    selected: @entangle('filterWarehouse'),
                    allOptions: {{ json_encode($availableWarehouses->values()) }},
                    get filtered() {
                        if (!this.search.trim()) return this.allOptions;
                        return this.allOptions.filter(i => i.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get label() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === 0) return 'Semua Gudang';
                        if (sel.length === 1) return sel[0];
                        return sel.length + ' Gudang';
                    },
                    toggleAll() {
                        let sel = Array.isArray(this.selected) ? this.selected : [];
                        if (sel.length === this.allOptions.length) {
                            this.selected = [];
                        } else {
                            this.selected = [...this.allOptions];
                        }
                    }
                }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="open = !open" 
                        class="border rounded-xl text-xs sm:text-sm py-2 pl-3 pr-7 bg-white hover:bg-gray-50/80 focus:outline-none flex items-center justify-between gap-1.5 shadow-sm transition-all duration-150 min-w-[130px] max-w-[180px]"
                        :class="(Array.isArray(selected) && selected.length > 0) ? 'border-[#1c69d4] text-[#1c69d4] font-semibold bg-blue-50/20' : 'border-gray-200 text-gray-700'"
                        title="Filter Gudang (Multi-Select)">
                        <span class="truncate" x-text="label">Semua Gudang</span>
                        <template x-if="Array.isArray(selected) && selected.length > 1">
                            <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-[#1c69d4] rounded-full shrink-0" x-text="selected.length"></span>
                        </template>
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute right-2 transition-transform duration-200 pointer-events-none" :class="{'rotate-180 text-[#1c69d4]': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 mt-1.5 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden flex flex-col py-1"
                        style="display: none;">
                        <template x-if="allOptions.length > 4">
                            <div class="px-2 py-1.5 border-b border-gray-100 bg-gray-50/70">
                                <input type="text" x-model="search" placeholder="Cari gudang..." class="w-full px-2 py-1 text-xs border border-gray-200 rounded-lg focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] bg-white">
                            </div>
                        </template>
                        <div class="flex items-center justify-between px-3 py-1.5 border-b border-gray-100 text-[11px] text-gray-500 bg-gray-50/40">
                            <button type="button" @click="toggleAll()" class="hover:text-[#1c69d4] font-medium transition-colors" x-text="Array.isArray(selected) && selected.length === allOptions.length ? 'Batal Semua' : 'Pilih Semua'"></button>
                            <template x-if="Array.isArray(selected) && selected.length > 0">
                                <button type="button" @click="selected = []" class="text-red-500 hover:text-red-700 font-medium transition-colors">Reset</button>
                            </template>
                        </div>
                        <div class="overflow-y-auto max-h-48 divide-y divide-gray-50 py-0.5">
                            <template x-for="item in filtered" :key="item">
                                <label class="flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-blue-50/60 cursor-pointer transition-colors select-none">
                                    <input type="checkbox" :value="item" x-model="selected" class="rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                                    <span class="truncate text-gray-700 font-medium" :class="{'text-[#1c69d4] font-bold': Array.isArray(selected) && selected.includes(item)}" x-text="item"></span>
                                </label>
                            </template>
                            <template x-if="filtered.length === 0">
                                <div class="px-3 py-3 text-center text-xs text-gray-400">Tidak ada pilihan</div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="relative flex-1 sm:flex-initial">
                    <input type="text" wire:model="search" wire:keydown.enter="applyFilter" placeholder="Cari SKU / Nama... (Enter)" 
                        class="w-full sm:w-56 pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs sm:text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                {{-- Action Buttons: Terapkan Filter & Reset --}}
                <div class="flex items-center gap-2">
                    <button wire:click="applyFilter" wire:loading.attr="disabled"
                        class="flex items-center gap-1.5 bg-[#1c69d4] hover:bg-blue-700 disabled:opacity-75 text-white text-xs sm:text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-all active:scale-95">
                        <svg wire:loading.remove wire:target="applyFilter" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <svg wire:loading wire:target="applyFilter" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="applyFilter">Terapkan</span>
                        <span wire:loading wire:target="applyFilter">Memuat...</span>
                    </button>

                    @if($hasAppliedFilter || $filterWarehouse || $filterCategory || $filterBrand || $filterProyek || $search)
                        <button wire:click="resetFilter" wire:loading.attr="disabled" title="Reset semua filter"
                            class="flex items-center gap-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs sm:text-sm font-medium py-2 px-3 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="hidden sm:inline">Reset</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto" wire:loading.class="opacity-60 pointer-events-none transition-opacity duration-150" wire:target="applyFilter,resetFilter,sortBy,gotoPage,nextPage,previousPage">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('sku')">
                            SKU & Kategori
                            @if($sortField === 'sku')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('name')">
                            Nama Produk
                            @if($sortField === 'name')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('brand')">
                            Merek
                            @if($sortField === 'brand')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('stock')">
                            Stok & Gudang
                            @if($sortField === 'stock')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>

                        <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('base_cost')">
                            Harga Beli
                            @if($sortField === 'base_cost')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('base_price')">
                            Harga Jual
                            @if($sortField === 'base_price')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-bold text-center cursor-pointer hover:bg-gray-50" wire:click="sortBy('age_days')">
                            Umur
                            @if($sortField === 'age_days')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    {{-- Skeleton Loading saat memuat data filter --}}
                    <tr wire:loading wire:target="applyFilter,sortBy,gotoPage,nextPage,previousPage" class="animate-pulse">
                        <td colspan="7" class="p-0">
                            <div class="space-y-3 p-4">
                                @for($i = 0; $i < 6; $i++)
                                    <div class="flex items-center justify-between gap-4 py-2 border-b border-gray-50">
                                        <div class="h-4 bg-gray-200 rounded w-28"></div>
                                        <div class="h-4 bg-gray-200 rounded w-64"></div>
                                        <div class="h-4 bg-gray-100 rounded w-16"></div>
                                        <div class="h-4 bg-gray-200 rounded w-24"></div>
                                        <div class="h-4 bg-gray-200 rounded w-20"></div>
                                        <div class="h-4 bg-gray-200 rounded w-20"></div>
                                        <div class="h-4 bg-gray-100 rounded w-12"></div>
                                    </div>
                                @endfor
                            </div>
                        </td>
                    </tr>

                    @if(!$hasAppliedFilter)
                        <tr wire:loading.remove wire:target="applyFilter">
                            <td colspan="7" class="py-16 text-center">
                                <div class="max-w-md mx-auto text-center px-4">
                                    <div class="w-16 h-16 bg-blue-50 text-[#1c69d4] rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-base font-bold text-gray-800 mb-1">Pilih Filter untuk Menampilkan Stok</h4>
                                    <p class="text-xs text-gray-500 mb-5 leading-relaxed">
                                        Silakan tentukan Gudang, Kategori, Merek, atau ketik kata kunci pencarian, lalu klik tombol <span class="font-bold text-[#1c69d4]">"Terapkan"</span> untuk melihat data stok.
                                    </p>
                                    <button wire:click="applyFilter" wire:loading.attr="disabled" class="inline-flex items-center gap-2 bg-[#1c69d4] hover:bg-blue-700 text-white text-xs font-bold py-2.5 px-5 rounded-xl shadow-sm transition-all active:scale-95">
                                        <span>Tampilkan Stok Sekarang</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @else
                        @forelse($stocks as $item)
                            <tr wire:loading.remove wire:target="applyFilter" wire:key="stock-row-{{ $item['id'] }}" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3">
                                    <p class="text-xs font-semibold text-gray-800">{{ $item['sku'] }}</p>
                                    <span class="inline-block mt-1 px-1.5 py-0.5 {{ $item['category'] == 'Baru' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }} rounded text-[10px] font-bold">
                                        {{ $item['category'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-xs font-bold text-gray-800">{{ $item['name'] }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                        <span class="font-semibold text-gray-600">Vendor:</span> {{ $item['vendor_name'] ?? '-' }}
                                    </p>
                                    @if(isset($item['sn']) && $item['sn'] !== '-')
                                        <p class="text-[11px] text-gray-500 mt-1 max-w-xs truncate" title="{{ $item['sn'] }}">
                                            <span class="font-semibold text-gray-600">SN:</span> {{ $item['sn'] }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 rounded-md text-xs font-semibold">
                                        {{ $item['brand'] && $item['brand'] !== '-' ? $item['brand'] : '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-sm font-black {{ $item['stock'] > 0 ? 'text-green-600' : 'text-red-500' }}">{{ $item['stock'] }} Pcs</p>
                                    <p class="text-[11px] font-bold text-gray-500 mt-0.5 truncate">
                                        <span class="inline-block w-2 h-2 rounded-full bg-[#1c69d4] mr-1"></span>
                                        {{ $item['warehouse_name'] }}
                                    </p>
                                </td>

                                <td class="px-5 py-3 text-right">
                                    <p class="text-xs font-bold text-gray-700">Rp {{ number_format($item['base_cost'], 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <p class="text-sm font-black text-[#1c69d4]">Rp {{ number_format($item['base_price'], 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <p class="text-xs font-medium {{ $item['age_days'] > 90 ? 'text-red-500' : 'text-gray-700' }}">
                                        {{ $item['age_days'] }} Hari
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr wire:loading.remove wire:target="applyFilter">
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400 text-sm">
                                    <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    Tidak ada data stok yang sesuai dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-gray-100 bg-white">
            @if($hasAppliedFilter)
                {{ $stocks->links() }}
            @endif
        </div>
    </div>

    {{-- Modal Loading Export --}}
    <div wire:loading wire:target="exportExcel,exportCsv" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-sm w-full shadow-2xl border border-gray-100 text-center animate-in fade-in zoom-in-95 duration-150">
            <div class="w-16 h-16 bg-blue-50 text-[#1c69d4] rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="animate-spin w-8 h-8 text-[#1c69d4]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-base font-black text-gray-800 mb-1">Sedang Menyiapkan Laporan</h3>
            <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                Sistem sedang memproses data stok. File akan otomatis terunduh begitu proses selesai.
            </p>
            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-[#1c69d4] h-full w-2/3 animate-pulse rounded-full"></div>
            </div>
            <p class="text-[10px] text-gray-400 mt-3 font-medium">Tips: Format CSV mengunduh jauh lebih cepat untuk puluhan ribu barang.</p>
        </div>
    </div>
</div>
