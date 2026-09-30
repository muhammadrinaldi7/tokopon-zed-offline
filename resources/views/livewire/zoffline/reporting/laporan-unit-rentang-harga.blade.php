<div class="p-6 bg-[#f7f7f7] min-h-screen">
    {{-- Breadcrumb & Top Bar --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <a href="{{ route('zoffline.reporting') }}" wire:navigate class="hover:text-[#1c69d4] transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Pusat Laporan
                </a>
                <span>/</span>
                <span class="text-gray-800 font-bold">Cek Unit Rentang Harga</span>
            </div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Cek Unit Rentang Harga</h1>
            <p class="text-sm text-gray-500 mt-0.5">Filter stok berdasarkan rentang harga jual SKU atau modal SN</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            {{-- Export Excel --}}
            <button wire:click="exportExcel" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
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
                class="flex items-center gap-2 bg-green-500 hover:bg-green-600 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
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

    {{-- Summary / Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: Total SKU / Varian --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total SKU / Produk</p>
                <h3 class="text-2xl font-black text-gray-800 mt-1">{{ number_format($summary['total_skus'], 0, ',', '.') }} <span class="text-sm font-semibold text-gray-500">SKU</span></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
            </div>
        </div>

        {{-- Card 2: Total Unit Fisik --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Unit Ready (SN)</p>
                <h3 class="text-2xl font-black text-blue-600 mt-1">{{ number_format($summary['total_units'], 0, ',', '.') }} <span class="text-sm font-semibold text-gray-500">Unit</span></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-[#1c69d4]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>

        {{-- Card 3: Total Nilai Jual --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Potensi Nilai Jual</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">Rp {{ number_format($summary['total_nilai_jual'], 0, ',', '.') }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        {{-- Card 4: Total Nilai Modal (HPP) --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Nilai HPP (Modal)</p>
                <h3 class="text-2xl font-black text-amber-600 mt-1">Rp {{ number_format($summary['total_nilai_hpp'], 0, ',', '.') }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Filter Card Section --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-5 mb-6 space-y-4">
        {{-- Section 1: Quick Filter Presets --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-[#1c69d4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Preset Rentang Harga:</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="setPreset('all')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ empty($selectedPreset) && empty($minPrice) && empty($maxPrice) ? 'bg-[#1c69d4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Semua
                </button>
                <button type="button" wire:click="setPreset('under_1m')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $selectedPreset === 'under_1m' ? 'bg-[#1c69d4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    &lt; Rp 1 Jt
                </button>
                <button type="button" wire:click="setPreset('1m_3m')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $selectedPreset === '1m_3m' ? 'bg-[#1c69d4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Rp 1 Jt - 3 Jt
                </button>
                <button type="button" wire:click="setPreset('3m_5m')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $selectedPreset === '3m_5m' ? 'bg-[#1c69d4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Rp 3 Jt - 5 Jt
                </button>
                <button type="button" wire:click="setPreset('5m_10m')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $selectedPreset === '5m_10m' ? 'bg-[#1c69d4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Rp 5 Jt - 10 Jt
                </button>
                <button type="button" wire:click="setPreset('above_10m')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $selectedPreset === 'above_10m' ? 'bg-[#1c69d4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    &gt; Rp 10 Jt
                </button>

                @if(!empty($minPrice) || !empty($maxPrice) || !empty($search) || !empty($warehouseId) || !empty($vendor_id) || !empty($subkategori) || !empty($brand) || !empty($kategori) || !empty($businessUnitId))
                    <button type="button" wire:click="resetFilters"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Reset Filter
                    </button>
                @endif
            </div>
        </div>

        {{-- Section 2: Custom Price Range & Type Filter --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            {{-- Acuan Harga: Harga Jual SKU vs Modal HPP --}}
            <div class="md:col-span-3">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Acuan Rentang Harga</label>
                <div class="relative">
                    <select wire:model.live="priceType" class="w-full pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-sm font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white appearance-none cursor-pointer">
                        <option value="harga_jual">Harga Jual</option>
                        <option value="hpp">Harga Modal</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            {{-- Harga Minimum --}}
            <div class="md:col-span-3">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Harga Minimum (Rp)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-400 pointer-events-none">Rp</span>
                    <input type="number" wire:model.live.debounce.400ms="minPrice" placeholder="0" min="0" step="10000"
                        class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white placeholder-gray-300">
                </div>
            </div>

            {{-- Harga Maksimum --}}
            <div class="md:col-span-3">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Harga Maksimum (Rp)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-400 pointer-events-none">Rp</span>
                    <input type="number" wire:model.live.debounce.400ms="maxPrice" placeholder="Maksimal..." min="0" step="10000"
                        class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white placeholder-gray-300">
                </div>
            </div>

            {{-- Info Range Indicator --}}
            <div class="md:col-span-3 flex flex-col justify-end pt-1 md:pt-4">
                <div class="bg-blue-50/70 border border-blue-100 rounded-xl px-3 py-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#1c69d4] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-[11px] font-medium text-[#1c69d4] truncate">
                        @if(!empty($minPrice) && !empty($maxPrice))
                            Rp {{ number_format($minPrice, 0, ',', '.') }} s/d Rp {{ number_format($maxPrice, 0, ',', '.') }}
                        @elseif(!empty($minPrice))
                            &ge; Rp {{ number_format($minPrice, 0, ',', '.') }}
                        @elseif(!empty($maxPrice))
                            &le; Rp {{ number_format($maxPrice, 0, ',', '.') }}
                        @else
                            Semua rentang harga
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 3: Dropdown Unit Bisnis, Gudang, Brand, Kategori, Subkategori, Vendor, and Search --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3 pt-3 border-t border-gray-100">
            {{-- Dropdown Unit Bisnis --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Unit Bisnis</label>
                <div class="relative">
                    <select wire:model.live="businessUnitId" class="w-full pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white appearance-none cursor-pointer">
                        <option value="">Semua BU</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            {{-- Dropdown Gudang --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Gudang</label>
                <div class="relative">
                    <select wire:model.live="warehouseId" class="w-full pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white appearance-none cursor-pointer">
                        <option value="">Semua Gudang</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            {{-- Dropdown Brand / Merek --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Merek / Brand</label>
                <div class="relative">
                    <select wire:model.live="brand" class="w-full pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white appearance-none cursor-pointer">
                        <option value="">Semua Brand</option>
                        @foreach($brands as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            {{-- Dropdown Kategori --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kategori</label>
                <div class="relative">
                    <select wire:model.live="kategori" class="w-full pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white appearance-none cursor-pointer">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoris as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            {{-- Dropdown Subkategori --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Subkategori</label>
                <div class="relative">
                    <select wire:model.live="subkategori" class="w-full pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white appearance-none cursor-pointer">
                        <option value="">Semua Subkategori</option>
                        @foreach($subkategoris as $sub)
                            <option value="{{ $sub }}">{{ $sub }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            {{-- Dropdown Vendor (Searchable) --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Vendor Pemasok</label>
                <div x-data="{
                    open: false,
                    search: '',
                    selectedId: @entangle('vendor_id').live,
                    selectedName: 'Semua Vendor',
                    vendors: [
                        { id: '', name: 'Semua Vendor' },
                        @foreach($vendors as $vendor)
                            { id: '{{ $vendor->id }}', name: '{{ addslashes($vendor->vendor_name) }}' },
                        @endforeach
                    ],
                    init() {
                        let found = this.vendors.find(v => String(v.id) === String(this.selectedId));
                        if (found) this.selectedName = found.name;

                        this.$watch('selectedId', (val) => {
                            let found = this.vendors.find(v => String(v.id) === String(val));
                            this.selectedName = found ? found.name : 'Semua Vendor';
                        });
                    },
                    get filteredVendors() {
                        if (!this.search) return this.vendors;
                        return this.vendors.filter(v => v.name.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    selectVendor(id, name) {
                        this.selectedId = id;
                        this.selectedName = name;
                        $wire.set('vendor_id', id);
                        this.open = false;
                        this.search = '';
                    }
                }" 
                @click.outside="open = false"
                class="relative w-full">
                    <!-- Dropdown Button -->
                    <button @click="open = !open" type="button" class="w-full pl-3 pr-8 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] bg-white text-left cursor-pointer flex items-center justify-between shadow-sm">
                        <span class="truncate" x-text="selectedName">Semua Vendor</span>
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-hidden flex flex-col" 
                         style="display: none;">
                        <!-- Search Input -->
                        <div class="p-2 border-b border-gray-100 bg-gray-50/50 relative">
                            <input x-model="search" 
                                   x-ref="vendorSearchInput"
                                   type="text" 
                                   placeholder="Cari Vendor..." 
                                   class="w-full pl-7 pr-2 py-1 border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white"
                                   x-init="$watch('open', value => { if (value) setTimeout(() => $refs.vendorSearchInput.focus(), 50) })">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <!-- Vendor Options List -->
                        <div class="overflow-y-auto max-h-48 divide-y divide-gray-50">
                            <template x-for="vendor in filteredVendors" :key="vendor.id">
                                <button @click="selectVendor(vendor.id, vendor.name)" type="button" class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 transition-colors flex items-center justify-between" :class="String(selectedId) === String(vendor.id) ? 'bg-[#1c69d4]/5 text-[#1c69d4] font-bold' : 'text-gray-700'">
                                    <span x-text="vendor.name" class="truncate"></span>
                                    <svg x-show="String(selectedId) === String(vendor.id)" class="w-3.5 h-3.5 text-[#1c69d4] shrink-0 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input Pencarian --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cari Cepat</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari SKU / SN / Produk..." 
                        class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table Section --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        {{-- Table Header Bar & Mode Toggle --}}
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
                {{-- View Mode Selector Segmented Pills --}}
                <div class="inline-flex bg-gray-200/70 p-1 rounded-xl">
                    <button type="button" wire:click="$set('viewMode', 'sku')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $viewMode === 'sku' ? 'bg-white text-[#1c69d4] shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        Rekap SKU (Produk)
                    </button>
                    <button type="button" wire:click="$set('viewMode', 'sn')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $viewMode === 'sn' ? 'bg-white text-[#1c69d4] shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Detail per Serial Number (SN)
                    </button>
                </div>

                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#1c69d4]/10 text-[#1c69d4]">
                    {{ $items->total() }} {{ $viewMode === 'sku' ? 'SKU' : 'Unit SN' }}
                </span>
            </div>

            <div wire:loading class="text-xs font-semibold text-[#1c69d4] flex items-center gap-1.5">
                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memuat data...
            </div>
        </div>
        
        <div class="overflow-x-auto">
            @if($viewMode === 'sku')
                {{-- TABLE MODE: REKAP SKU / PRODUK --}}
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                            <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('sku')">
                                SKU / Item No
                                @if($sortField === 'sku' || $sortField === 'item_no')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('name')">
                                Nama Produk & Subkategori
                                @if($sortField === 'name')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold">Brand & Kategori</th>
                            <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('harga_jual')">
                                Harga Jual (SKU)
                                @if($sortField === 'harga_jual' || $sortField === 'base_price')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold text-center">
                                Total Stok Ready
                            </th>
                            <th class="px-5 py-4 font-bold min-w-[320px]">
                                Unit Serial Number & Modal (HPP)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($items as $product)
                            @php
                                $sns = $product->productSerialNumbers ?? collect();
                                $snCount = $sns->count();
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors align-top" x-data="{ openSnList: false }">
                                {{-- SKU --}}
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-[#1c69d4] font-mono">
                                        {{ $product->item_no }}
                                    </span>
                                </td>

                                {{-- Nama Produk & Subkategori --}}
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold text-gray-900 leading-snug">{{ $product->name }}</p>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        @if(!empty($product->proyek))
                                            @php
                                                $upperSub = strtoupper($product->proyek);
                                            @endphp
                                            @if(str_contains($upperSub, 'RESMI') || str_contains($upperSub, 'IBOX') || str_contains($upperSub, 'TAM'))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ $product->proyek }}
                                                </span>
                                            @elseif(str_contains($upperSub, 'INTER') || str_contains($upperSub, 'GLOBAL'))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                    {{ $product->proyek }}
                                                </span>
                                            @elseif(str_contains($upperSub, 'BEACUKAI'))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    {{ $product->proyek }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700">
                                                    {{ $product->proyek }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                {{-- Brand & Kategori --}}
                                <td class="px-5 py-4">
                                    <p class="text-xs font-bold text-gray-800">{{ $product->brandName ?: '-' }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $product->categoryName ?: '-' }}</p>
                                </td>

                                {{-- Harga Jual (SKU) --}}
                                <td class="px-5 py-4 text-right">
                                    <p class="text-base font-black text-emerald-600">Rp {{ number_format($product->base_price ?? 0, 0, ',', '.') }}</p>
                                </td>

                                {{-- Total Stok Ready --}}
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black {{ $snCount > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $snCount }} Unit
                                    </span>
                                </td>

                                {{-- Daftar Unit SN & Modal (HPP) --}}
                                <td class="px-5 py-4">
                                    @if($snCount === 0)
                                        <span class="text-xs text-gray-400 italic">Tidak ada SN ready</span>
                                    @else
                                        <div class="space-y-1.5">
                                            {{-- Tampilkan 2 SN pertama --}}
                                            @foreach($sns->take(2) as $sn)
                                                <div class="bg-gray-50 border border-gray-200/80 rounded-xl px-2.5 py-1.5 flex items-center justify-between gap-2 text-xs">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-mono font-bold text-gray-800">{{ $sn->serial_number }}</span>
                                                        <span class="text-[10px] text-gray-500">({{ $sn->warehouse->name ?? 'Gudang -' }})</span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 px-1.5 py-0.5 rounded">
                                                            Modal: Rp {{ number_format($sn->hpp ?? 0, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- Tampilkan sisa SN jika ada lebih dari 2 --}}
                                            @if($snCount > 2)
                                                <div x-show="openSnList" x-collapse class="space-y-1.5 pt-1">
                                                    @foreach($sns->skip(2) as $sn)
                                                        <div class="bg-gray-50 border border-gray-200/80 rounded-xl px-2.5 py-1.5 flex items-center justify-between gap-2 text-xs">
                                                            <div class="flex items-center gap-2">
                                                                <span class="font-mono font-bold text-gray-800">{{ $sn->serial_number }}</span>
                                                                <span class="text-[10px] text-gray-500">({{ $sn->warehouse->name ?? 'Gudang -' }})</span>
                                                            </div>
                                                            <div class="text-right">
                                                                <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 px-1.5 py-0.5 rounded">
                                                                    Modal: Rp {{ number_format($sn->hpp ?? 0, 0, ',', '.') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <button @click="openSnList = !openSnList" type="button" 
                                                    class="text-[11px] font-bold text-[#1c69d4] hover:underline flex items-center gap-1 mt-1 cursor-pointer">
                                                    <span x-text="openSnList ? 'Sembunyikan' : '+ Lihat ' + {{ $snCount - 2 }} + ' SN lainnya'"></span>
                                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{'rotate-180': openSnList}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-400 mb-3">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-gray-700">Tidak ada produk SKU pada filter rentang harga ini.</p>
                                        <p class="text-xs text-gray-400 mt-1">Coba sesuaikan batas rentang harga atau reset filter.</p>
                                        <button wire:click="resetFilters" type="button" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-[#1c69d4] text-white hover:bg-[#1858b3] transition-colors shadow-sm">
                                            Reset Semua Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                {{-- TABLE MODE: DETAIL PER SERIAL NUMBER (SN) --}}
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                            <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('serial_number')">
                                Serial Number
                                @if($sortField === 'serial_number')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('item_no')">
                                Produk (SKU)
                                @if($sortField === 'item_no')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold">Brand</th>
                            <th class="px-5 py-4 font-bold">Kategori</th>
                            <th class="px-5 py-4 font-bold cursor-pointer hover:bg-gray-50" wire:click="sortBy('subkategori')">
                                Subkategori
                                @if($sortField === 'subkategori')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold">Lokasi Gudang</th>
                            <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('hpp')">
                                Modal (HPP SN)
                                @if($sortField === 'hpp')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold text-right cursor-pointer hover:bg-gray-50" wire:click="sortBy('harga_jual')">
                                Harga Jual (SKU)
                                @if($sortField === 'harga_jual' || $sortField === 'base_price')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold">Vendor</th>
                            <th class="px-5 py-4 font-bold text-center cursor-pointer hover:bg-gray-50" wire:click="sortBy('receipt_date')">
                                Tanggal Masuk
                                @if($sortField === 'receipt_date')
                                    <span class="ml-1 text-[#1c69d4]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-5 py-4 font-bold text-center">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($items as $item)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3">
                                    <p class="text-sm font-black text-gray-800">{{ $item->serial_number }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Dibuat: {{ $item->created_at ? $item->created_at->format('d M Y H:i') : '-' }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-xs font-bold text-gray-800 line-clamp-2" title="{{ $item->productAccurate->name ?? 'Unknown' }}">
                                        {{ $item->productAccurate->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-[11px] text-[#1c69d4] font-semibold mt-0.5">{{ $item->item_no }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-gray-100 text-gray-700">
                                        {{ $item->productAccurate->brandName ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-[11px] font-semibold text-gray-700">{{ $item->productAccurate->categoryName ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    @php
                                        $subVal = $item->productAccurate->proyek ?? ($item->proyek ?? null);
                                        $upperSub = strtoupper($subVal ?? '');
                                    @endphp
                                    @if(!empty($subVal))
                                        @if(str_contains($upperSub, 'RESMI') || str_contains($upperSub, 'IBOX') || str_contains($upperSub, 'TAM'))
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                {{ $subVal }}
                                            </span>
                                        @elseif(str_contains($upperSub, 'INTER') || str_contains($upperSub, 'GLOBAL'))
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                {{ $subVal }}
                                            </span>
                                        @elseif(str_contains($upperSub, 'BEACUKAI'))
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                {{ $subVal }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-gray-100 text-gray-700">
                                                {{ $subVal }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 text-[11px] font-bold">
                                        <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        {{ $item->warehouse->name ?? 'Belum Dialokasikan' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <p class="text-sm font-bold text-amber-700">Rp {{ number_format($item->hpp ?? 0, 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <p class="text-sm font-black text-emerald-600">Rp {{ number_format($item->productAccurate->base_price ?? ($item->base_price ?? 0), 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-xs font-semibold text-gray-600">{{ $item->vendor->vendor_name ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <p class="text-[11px] font-semibold text-gray-700">{{ $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('d M Y') : '-' }}</p>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($item->status == 'Available')
                                        <span class="inline-block px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-md text-[10px] font-bold uppercase">Available</span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 rounded-md text-[10px] font-bold uppercase">{{ $item->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-400 mb-3">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-gray-700">Tidak ada unit Serial Number pada filter ini.</p>
                                        <p class="text-xs text-gray-400 mt-1">Coba sesuaikan batas rentang harga, pilih Unit Bisnis, atau reset filter.</p>
                                        <button wire:click="resetFilters" type="button" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-[#1c69d4] text-white hover:bg-[#1858b3] transition-colors shadow-sm">
                                            Reset Semua Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
        
        <div class="p-4 border-t border-gray-100 bg-white">
            {{ $items->links() }}
        </div>
    </div>
</div>
