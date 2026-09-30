<div class="p-4 sm:p-6 max-w-5xl mx-auto space-y-6">
    {{-- Breadcrumb & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('zoffline.stock-opname.index') }}" wire:navigate
            class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-neutral-600 hover:text-neutral-900 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Riwayat Opname
        </a>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-neutral-100 text-neutral-700 border border-neutral-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Sesi Baru
            </span>
        </div>
    </div>

    {{-- Banner Jika Sudah Ada Sesi Aktif Berjalan di Cabang Ini --}}
    @if($activeOpname)
        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white rounded-2xl p-5 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="p-2.5 bg-white/20 rounded-xl mt-0.5 shrink-0">
                    <svg class="w-6 h-6 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <span class="inline-block px-2.5 py-0.5 bg-white/25 rounded-md text-[10px] font-extrabold uppercase tracking-wider mb-1">
                        Sesi Opname Sedang Berjalan
                    </span>
                    <h3 class="text-base sm:text-lg font-bold">Sesi #{{ $activeOpname->opname_number }} Sedang Aktif</h3>
                    <p class="text-xs text-amber-100 mt-0.5">
                        Dimulai oleh <strong class="text-white">{{ $activeOpname->user->name ?? 'BM Cabang' }}</strong> pada {{ $activeOpname->start_time->format('d M Y, H:i') }}.
                        Anda dapat langsung bergabung ke workstation untuk proses scan bersama tim BM.
                    </p>
                </div>
            </div>
            <a href="{{ route('zoffline.stock-opname.count', $activeOpname->id) }}" wire:navigate
                class="px-5 py-2.5 bg-white text-orange-700 hover:bg-orange-50 rounded-xl font-bold text-xs sm:text-sm shadow-sm transition shrink-0 flex items-center gap-2">
                <span>Gabung Scan Bersama</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    @endif

    {{-- Main Container Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200/90 overflow-hidden">
        {{-- Card Header --}}
        <div class="p-6 border-b border-neutral-100 bg-neutral-50/40">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-extrabold text-neutral-900 tracking-tight">Inisiasi Sesi Stock Opname</h1>
                    <p class="text-xs sm:text-sm text-neutral-500 mt-0.5">
                        Kombinasikan <strong class="text-neutral-700">Brand, Kategori, dan Proyek</strong> untuk audit terfokus, atau biarkan kosong untuk audit menyeluruh toko.
                    </p>
                </div>
                {{-- Store Lock Indicator --}}
                <div class="inline-flex items-center gap-2 px-3.5 py-2 bg-blue-50 border border-blue-200/80 rounded-xl self-start sm:self-auto">
                    <svg class="w-4 h-4 text-[#4E44DB]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <div class="text-[11px] leading-tight">
                        <span class="font-bold text-blue-900 block">{{ $branchName }}</span>
                        <span class="text-blue-600 block">{{ $warehouseName }} &bull; {{ $businessUnitName }}</span>
                    </div>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="startOpname" class="p-6 space-y-7">
            {{-- 1. TIGA KUNCI UTAMA (BRAND, KATEGORI, PROYEK) --}}
            <div class="space-y-3.5">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-xs font-bold text-neutral-800 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-[#4E44DB] text-white flex items-center justify-center text-[10px] font-bold">1</span>
                            Kunci Utama Audit (Brand &bull; Kategori &bull; Proyek)
                        </label>
                        <p class="text-xs text-neutral-400 mt-0.5">
                            Pilih kombinasi kunci di bawah ini. Anda dapat memilih salah satu, ketiganya, atau membiarkannya kosong.
                        </p>
                    </div>

                    @if($brandFilter || $categoryFilter || $projectFilter || $itemType !== 'ALL')
                        <button type="button" wire:click="resetFilters"
                            class="px-2.5 py-1 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset ke Semua
                        </button>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- KUNCI 1: BRAND / MEREK --}}
                    <div class="p-4 rounded-xl border-2 transition-all bg-white {{ $brandFilter ? 'border-purple-500 bg-purple-50/15 shadow-2xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <div class="flex items-center justify-between mb-2.5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-neutral-900 uppercase tracking-wide">Brand / Merek</span>
                            </div>

                            @if($brandFilter)
                                <button type="button" wire:click="setBrandFilter('')"
                                    class="text-[10px] font-bold text-purple-700 bg-purple-100 hover:bg-purple-200 px-1.5 py-0.5 rounded cursor-pointer flex items-center gap-1">
                                    <span>{{ $brandFilter }}</span>
                                    <span>&times;</span>
                                </button>
                            @else
                                <span class="text-[10px] font-medium text-neutral-400">Semua Brand</span>
                            @endif
                        </div>

                        {{-- Quick Brand Pills --}}
                        <div class="mb-2.5 flex items-center gap-1 flex-wrap">
                            @foreach(['APPLE', 'SAMSUNG', 'XIAOMI', 'OPPO', 'VIVO', 'INFINIX', 'REALME'] as $b)
                                @if(in_array($b, $brandList))
                                    <button type="button" wire:click="setBrandFilter('{{ $brandFilter === $b ? '' : $b }}')"
                                        class="px-2 py-0.5 text-[11px] rounded font-semibold border transition cursor-pointer {{ $brandFilter === $b ? 'bg-purple-600 text-white border-purple-600' : 'bg-neutral-50 text-neutral-600 border-neutral-200 hover:bg-neutral-100' }}">
                                        {{ $b }}
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        {{-- Search & Dropdown --}}
                        <div class="space-y-1.5">
                            <input type="text" wire:model.live.debounce.150ms="searchBrand"
                                placeholder="Cari brand... ({{ count($brandList) }} merek)"
                                class="w-full px-3 py-1.5 bg-neutral-50/70 border border-neutral-200 rounded-lg text-xs focus:bg-white focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none transition">

                            <select wire:model.live="brandFilter"
                                class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs font-semibold text-neutral-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none transition">
                                <option value="">-- Semua Brand --</option>
                                @foreach($this->filteredBrandList as $b)
                                    <option value="{{ $b }}">{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- KUNCI 2: KATEGORI PRODUK --}}
                    <div class="p-4 rounded-xl border-2 transition-all bg-white {{ $categoryFilter ? 'border-cyan-500 bg-cyan-50/15 shadow-2xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <div class="flex items-center justify-between mb-2.5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-neutral-900 uppercase tracking-wide">Kategori Produk</span>
                            </div>

                            @if($categoryFilter)
                                <button type="button" wire:click="setCategoryFilter('')"
                                    class="text-[10px] font-bold text-cyan-700 bg-cyan-100 hover:bg-cyan-200 px-1.5 py-0.5 rounded cursor-pointer flex items-center gap-1">
                                    <span>{{ $categoryFilter }}</span>
                                    <span>&times;</span>
                                </button>
                            @else
                                <span class="text-[10px] font-medium text-neutral-400">Semua Kategori</span>
                            @endif
                        </div>

                        {{-- Quick Category Pills --}}
                        <div class="mb-2.5 flex items-center gap-1 flex-wrap">
                            @foreach(['HP SECOND', 'HP NEW', 'HANDPHONE', 'ACCESSORIES', 'CHARGER', 'CASE'] as $c)
                                @if(in_array($c, $categoryList))
                                    <button type="button" wire:click="setCategoryFilter('{{ $categoryFilter === $c ? '' : $c }}')"
                                        class="px-2 py-0.5 text-[11px] rounded font-semibold border transition cursor-pointer {{ $categoryFilter === $c ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-neutral-50 text-neutral-600 border-neutral-200 hover:bg-neutral-100' }}">
                                        {{ $c }}
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        {{-- Search & Dropdown --}}
                        <div class="space-y-1.5">
                            <input type="text" wire:model.live.debounce.150ms="searchCategory"
                                placeholder="Cari kategori... ({{ count($categoryList) }} jenis)"
                                class="w-full px-3 py-1.5 bg-neutral-50/70 border border-neutral-200 rounded-lg text-xs focus:bg-white focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">

                            <select wire:model.live="categoryFilter"
                                class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs font-semibold text-neutral-800 focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                                <option value="">-- Semua Kategori --</option>
                                @foreach($this->filteredCategoryList as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- KUNCI 3: PROYEK --}}
                    <div class="p-4 rounded-xl border-2 transition-all bg-white {{ $projectFilter ? 'border-rose-500 bg-rose-50/15 shadow-2xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <div class="flex items-center justify-between mb-2.5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-neutral-900 uppercase tracking-wide">Divisi Proyek</span>
                            </div>

                            @if($projectFilter)
                                <button type="button" wire:click="setProjectFilter('')"
                                    class="text-[10px] font-bold text-rose-700 bg-rose-100 hover:bg-rose-200 px-1.5 py-0.5 rounded cursor-pointer flex items-center gap-1">
                                    <span>{{ $projectFilter }}</span>
                                    <span>&times;</span>
                                </button>
                            @else
                                <span class="text-[10px] font-medium text-neutral-400">Semua Proyek</span>
                            @endif
                        </div>

                        {{-- Quick Project Pills --}}
                        <div class="mb-2.5 flex items-center gap-1 flex-wrap">
                            @foreach($projectList as $p)
                                <button type="button" wire:click="setProjectFilter('{{ $projectFilter === $p ? '' : $p }}')"
                                    class="px-2 py-0.5 text-[11px] rounded font-semibold border transition cursor-pointer {{ $projectFilter === $p ? 'bg-rose-600 text-white border-rose-600' : 'bg-neutral-50 text-neutral-600 border-neutral-200 hover:bg-neutral-100' }}">
                                    {{ $p }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Select Dropdown --}}
                        <div class="space-y-1.5">
                            <div class="h-[29px] hidden sm:block"></div> {{-- spacer aligning with search inputs --}}
                            <select wire:model.live="projectFilter"
                                class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs font-semibold text-neutral-800 focus:ring-1 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                                <option value="">-- Semua Proyek --</option>
                                @foreach($projectList as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. JENIS BARANG YANG DIHITUNG (TIPE FISIK) --}}
            <div class="space-y-2.5">
                <label class="text-xs font-bold text-neutral-800 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#4E44DB] text-white flex items-center justify-center text-[10px] font-bold">2</span>
                    Jenis Barang yang Dihitung Fisik
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Opsi 1: Semua Barang --}}
                    <div wire:click="setItemType('ALL')"
                        class="p-3.5 rounded-xl border-2 cursor-pointer transition select-none flex items-center gap-3 {{ $itemType === 'ALL' ? 'border-[#4E44DB] bg-indigo-50/20 ring-1 ring-[#4E44DB]/20 shadow-2xs' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $itemType === 'ALL' ? 'bg-[#4E44DB] text-white' : 'bg-neutral-100 text-neutral-500' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-bold text-neutral-900 block">Semua Barang</span>
                            <span class="text-[11px] text-neutral-400 block">HP (IMEI) + Aksesoris</span>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $itemType === 'ALL' ? 'border-[#4E44DB] bg-[#4E44DB]' : 'border-neutral-300' }}">
                            @if($itemType === 'ALL')
                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                            @endif
                        </div>
                    </div>

                    {{-- Opsi 2: Khusus Unit HP (IMEI) --}}
                    <div wire:click="setItemType('SERIALIZED_ONLY')"
                        class="p-3.5 rounded-xl border-2 cursor-pointer transition select-none flex items-center gap-3 {{ $itemType === 'SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-indigo-50/20 ring-1 ring-[#4E44DB]/20 shadow-2xs' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $itemType === 'SERIALIZED_ONLY' ? 'bg-[#4E44DB] text-white' : 'bg-neutral-100 text-neutral-500' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-bold text-neutral-900 block">Khusus Unit HP (IMEI)</span>
                            <span class="text-[11px] text-neutral-400 block">Fokus Scan Barcode IMEI</span>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $itemType === 'SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-[#4E44DB]' : 'border-neutral-300' }}">
                            @if($itemType === 'SERIALIZED_ONLY')
                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                            @endif
                        </div>
                    </div>

                    {{-- Opsi 3: Khusus Aksesoris --}}
                    <div wire:click="setItemType('NON_SERIALIZED_ONLY')"
                        class="p-3.5 rounded-xl border-2 cursor-pointer transition select-none flex items-center gap-3 {{ $itemType === 'NON_SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-indigo-50/20 ring-1 ring-[#4E44DB]/20 shadow-2xs' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $itemType === 'NON_SERIALIZED_ONLY' ? 'bg-[#4E44DB] text-white' : 'bg-neutral-100 text-neutral-500' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-bold text-neutral-900 block">Khusus Aksesoris</span>
                            <span class="text-[11px] text-neutral-400 block">Input Kuantitas Fisik Rak</span>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $itemType === 'NON_SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-[#4E44DB]' : 'border-neutral-300' }}">
                            @if($itemType === 'NON_SERIALIZED_ONLY')
                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. LIVE ESTIMASI REAL-TIME & PREVIEW ITEM --}}
            <div class="space-y-2.5">
                <label class="text-xs font-bold text-neutral-800 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#4E44DB] text-white flex items-center justify-center text-[10px] font-bold">3</span>
                    Estimasi Data Stok Buku Toko
                </label>

                <div class="bg-neutral-50/70 border border-neutral-200/90 rounded-2xl p-5 space-y-4">
                    {{-- Dynamic Active Combination Banner --}}
                    <div class="flex items-center gap-2 flex-wrap text-xs bg-white p-2.5 rounded-xl border border-neutral-200 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Target Audit:</span>
                        
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 font-bold rounded text-[11px]">
                            {{ $itemType === 'SERIALIZED_ONLY' ? 'Khusus Unit HP (IMEI)' : ($itemType === 'NON_SERIALIZED_ONLY' ? 'Khusus Aksesoris' : 'Semua Barang') }}
                        </span>

                        @if($brandFilter)
                            <span class="px-2 py-0.5 bg-purple-100 text-purple-800 font-bold rounded text-[11px] flex items-center gap-1">
                                <span>Brand: {{ $brandFilter }}</span>
                            </span>
                        @endif

                        @if($categoryFilter)
                            <span class="px-2 py-0.5 bg-cyan-100 text-cyan-800 font-bold rounded text-[11px] flex items-center gap-1">
                                <span>Kategori: {{ $categoryFilter }}</span>
                            </span>
                        @endif

                        @if($projectFilter)
                            <span class="px-2 py-0.5 bg-rose-100 text-rose-800 font-bold rounded text-[11px] flex items-center gap-1">
                                <span>Proyek: {{ $projectFilter }}</span>
                            </span>
                        @endif

                        @if(!$brandFilter && !$categoryFilter && !$projectFilter)
                            <span class="text-neutral-500 font-medium text-[11px] italic">
                                Seluruh Katalog Toko (Full Audit)
                            </span>
                        @endif
                    </div>

                    {{-- 3 Metric Counters --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        {{-- Box 1: Unit IMEI --}}
                        <div class="bg-white p-4 rounded-xl border border-neutral-200/80 shadow-2xs flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 block">Unit HP (IMEI)</span>
                                <span class="text-lg font-black text-neutral-900 font-mono">
                                    {{ number_format($totalAvailableSns) }} <span class="text-xs font-normal text-neutral-500">Unit</span>
                                </span>
                            </div>
                        </div>

                        {{-- Box 2: SKU Aksesoris --}}
                        <div class="bg-white p-4 rounded-xl border border-neutral-200/80 shadow-2xs flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 block">SKU Aksesoris</span>
                                <span class="text-lg font-black text-neutral-900 font-mono">
                                    {{ number_format($totalNonSerialSkus) }} <span class="text-xs font-normal text-neutral-500">SKU</span>
                                </span>
                            </div>
                        </div>

                        {{-- Box 3: Estimasi Nilai Modal HPP --}}
                        <div class="bg-white p-4 rounded-xl border border-neutral-200/80 shadow-2xs flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#4E44DB] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 block">Estimasi Nilai Stok Buku</span>
                                <span class="text-lg font-black text-neutral-900 font-mono">
                                    Rp {{ number_format($totalEstimatedHpp, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Sample Items Preview & Safety Guard --}}
                    <div class="pt-3 border-t border-neutral-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Contoh Item:</span>
                            @forelse($sampleItems as $sample)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white border border-neutral-200 text-neutral-700 text-[11px] font-medium shadow-2xs">
                                    <span class="text-[9px] font-bold uppercase {{ $sample['type'] === 'IMEI' ? 'text-purple-600' : 'text-emerald-600' }}">[{{ $sample['type'] }}]</span>
                                    <span class="truncate max-w-[200px]">{{ $sample['name'] }}</span>
                                </span>
                            @empty
                                <span class="text-neutral-400 text-xs italic">Belum ada item terpilih</span>
                            @endforelse
                        </div>

                        {{-- Guarding indicator --}}
                        <div>
                            @if($totalAvailableSns > 0 || $totalNonSerialSkus > 0)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Stok Siap Diaudit
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    0 Barang Ditemukan di Cabang Ini
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. CATATAN SESI (OPSIONAL) --}}
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-neutral-800 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#4E44DB] text-white flex items-center justify-center text-[10px] font-bold">4</span>
                    Catatan Sesi / Tujuan Opname (Opsional)
                </label>
                <textarea wire:model="notes" rows="2"
                    placeholder="Contoh: Audit Mingguan Khusus Brand Apple - Kategori HP Second - Proyek Resmi, Pemeriksaan fisik display etalase dan brankas..."
                    class="w-full px-4 py-2.5 bg-white border border-neutral-200 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none transition placeholder:text-neutral-400"></textarea>
                @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- ACTION FOOTER BUTTONS --}}
            <div class="pt-5 border-t border-neutral-100 flex items-center justify-between">
                <a href="{{ route('zoffline.stock-opname.index') }}" wire:navigate
                    class="px-5 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-xl text-xs sm:text-sm font-semibold transition cursor-pointer">
                    Batal
                </a>

                <button type="submit"
                    wire:loading.attr="disabled"
                    @if($totalAvailableSns === 0 && $totalNonSerialSkus === 0) disabled @endif
                    class="px-6 py-3 bg-[#4E44DB] hover:bg-[#3d34b3] disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl shadow-md hover:shadow-lg transition-all font-bold text-xs sm:text-sm flex items-center gap-2 cursor-pointer">
                    <span wire:loading.remove>
                        Mulai Penghitungan Fisik
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyiapkan Snapshot Data...
                    </span>
                    <svg wire:loading.remove class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>
