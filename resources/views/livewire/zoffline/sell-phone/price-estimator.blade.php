<div x-data="{
    copied: false,
    copyText(text) {
        if (!navigator.clipboard) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        } else {
            navigator.clipboard.writeText(text);
        }
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
    }
}" class="min-h-screen bg-neutral-100 py-6 px-4 md:px-8">

    {{-- Container Utama --}}
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Top Navigation & Header Bar --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80">
            <div class="flex items-center gap-4">
                <a href="{{ route('zoffline') }}" wire:navigate
                    class="w-12 h-12 rounded-2xl bg-neutral-100 hover:bg-neutral-200 text-neutral-700 flex items-center justify-center transition-all duration-200 shadow-xs hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">
                            Simulasi Buyback HP
                        </span>
                        @if ($selected_tier_name)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800">
                                {{ $selected_tier_name }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black text-neutral-900 tracking-tight mt-1">
                        Estimasi Harga Beli HP
                    </h1>
                    <p class="text-xs md:text-sm text-neutral-500 font-medium">
                        Hitung taksiran harga beli HP customer secara instan sesuai kondisi unit riil tanpa perlu form QC.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-end md:self-center">
                @if ($selected_model_name || $selected_brand_id)
                    <button type="button" wire:click="resetAll"
                        class="px-4 py-2.5 rounded-xl border border-neutral-300 hover:bg-neutral-100 text-neutral-700 text-xs md:text-sm font-bold flex items-center gap-2 transition-all cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Ganti HP Baru
                    </button>
                @endif
                @can('sell-phone')
                    <a href="{{ route('zoffline.sell-phone') }}" wire:navigate
                        class="px-4 py-2.5 rounded-xl bg-[#4E44DB] hover:bg-[#3f36b8] text-white text-xs md:text-sm font-bold flex items-center gap-2 shadow-sm transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Transaksi Pembelian
                    </a>
                @endcan
            </div>
        </div>

        {{-- Quick Search Bar --}}
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-neutral-200/80">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="quick_search"
                    placeholder="Pencarian Cepat: Ketik tipe / model HP (contoh: iPhone 13 128, Galaxy S23, dsb)..."
                    class="block w-full pl-11 pr-10 py-3.5 bg-neutral-50/80 border border-neutral-200 rounded-2xl text-sm font-medium text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#4E44DB] focus:bg-white transition-all">
                @if (!empty($quick_search))
                    <button type="button" wire:click="$set('quick_search', '')"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-neutral-400 hover:text-neutral-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Quick Search Results Dropdown --}}
            @if (!empty($quick_search) && count($quickSearchResults) > 0)
                <div class="mt-3 bg-neutral-50 rounded-2xl p-2 border border-neutral-200 divide-y divide-neutral-200/70 max-h-72 overflow-y-auto">
                    @foreach ($quickSearchResults as $item)
                        <button type="button" wire:click="selectProduct({{ $item['id'] }})"
                            class="w-full px-4 py-3 text-left hover:bg-white rounded-xl flex items-center justify-between transition-colors group cursor-pointer">
                            <div>
                                <span class="font-bold text-sm text-neutral-800 group-hover:text-[#4E44DB]">{{ $item['name'] }}</span>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-neutral-500">
                                    <span class="font-semibold text-neutral-600">{{ $item['brand'] }}</span>
                                    <span>•</span>
                                    <span>{{ $item['category'] }}</span>
                                    <span>•</span>
                                    <span>{{ $item['proyek'] }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-lg">
                                    {{ $item['formatted_price'] }}
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @elseif (!empty($quick_search))
                <div class="mt-3 p-4 text-center text-xs text-neutral-500 bg-neutral-50 rounded-2xl border border-neutral-200">
                    Tidak ditemukan perangkat dengan kata kunci "<span class="font-bold">{{ $quick_search }}</span>". Coba gunakan filter brand di bawah.
                </div>
            @endif
        </div>

        {{-- JIKA BELUM ADA MODEL YANG DIPILIH: TAMPILKAN FILTER BERJENJANG SEPERTI SELLPHONE --}}
        @if (!$selected_product)

            {{-- STEP 1: PILIH BRAND --}}
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-[#A28153] uppercase tracking-wider">Langkah 1</span>
                        <h2 class="text-lg font-bold text-neutral-800">Pilih Merek / Brand Perangkat</h2>
                    </div>
                    @if ($selected_brand_id)
                        <span class="text-xs font-bold text-neutral-500">
                            Terpilih: <strong class="text-neutral-900">{{ $selected_brand_id }}</strong>
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    @foreach ($brands as $brand)
                        @php
                            $isBrandActive = $selected_brand_id === $brand['id'];
                        @endphp
                        <button type="button" wire:click="$set('selected_brand_id', '{{ $brand['id'] }}')"
                            class="p-4 rounded-2xl border-2 flex flex-col items-center justify-center text-center transition-all duration-200 cursor-pointer select-none
                            {{ $isBrandActive
                                ? 'border-[#D3AD7B] bg-[#D3AD7B]/15 text-[#A28153] font-black shadow-sm scale-102 ring-2 ring-[#D3AD7B]/20'
                                : 'border-neutral-200/80 bg-neutral-50/50 hover:bg-white hover:border-[#D3AD7B]/50 hover:shadow-xs text-neutral-700 font-bold' }}">
                            <span class="text-sm md:text-base leading-tight">{{ $brand['name'] }}</span>
                            <span class="text-[10px] text-neutral-400 mt-1 font-medium">{{ $brand['item_count'] }} tipe</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- STEP 2 & 3: KATEGORI & PROYEK / SERI (JIKA BRAND SUDAH DIPILIH) --}}
            @if ($selected_brand_id)
                <div class="grid grid-cols-1 {{ count($available_proyek) > 0 ? 'md:grid-cols-2' : '' }} gap-6 animate-fade-in">

                    {{-- PILIH KATEGORI --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-[#A28153] uppercase tracking-wider">Langkah 2</span>
                                <h2 class="text-lg font-bold text-neutral-800">Pilih Kategori Produk</h2>
                            </div>
                            @if ($selected_categoryName)
                                <button type="button" wire:click="$set('selected_categoryName', null)"
                                    class="text-xs font-bold text-neutral-400 hover:text-neutral-700 underline cursor-pointer">
                                    Semua Kategori
                                </button>
                            @endif
                        </div>

                        @if (count($available_categories) > 0)
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($available_categories as $cat)
                                    @php
                                        $isCatActive = $selected_categoryName === $cat;
                                    @endphp
                                    <button type="button" wire:click="$set('selected_categoryName', '{{ $cat }}')"
                                        class="px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold border-2 transition-all cursor-pointer
                                        {{ $isCatActive
                                            ? 'border-[#D3AD7B] bg-[#D3AD7B]/15 text-[#A28153] ring-2 ring-[#D3AD7B]/20'
                                            : 'border-neutral-200/80 bg-neutral-50/50 text-neutral-700 hover:bg-white hover:border-[#D3AD7B]/50' }}">
                                        {{ $cat }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 text-center text-xs text-neutral-400 bg-neutral-50 rounded-2xl">
                                Kategori standar dipilih otomatis.
                            </div>
                        @endif
                    </div>

                    {{-- PILIH PROYEK / STATUS (JIKA TERSEDIA) --}}
                    @if (count($available_proyek) > 0)
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-[#A28153] uppercase tracking-wider">Langkah 3</span>
                                    <h2 class="text-lg font-bold text-neutral-800">Pilih Proyek / Kondisi</h2>
                                </div>
                                @if ($selected_proyek)
                                    <button type="button" wire:click="$set('selected_proyek', null)"
                                        class="text-xs font-bold text-neutral-400 hover:text-neutral-700 underline cursor-pointer">
                                        Semua Proyek
                                    </button>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($available_proyek as $proyek)
                                    @php
                                        $isProyekActive = $selected_proyek === $proyek;
                                    @endphp
                                    <button type="button" wire:click="$set('selected_proyek', '{{ $proyek }}')"
                                        class="px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold border-2 transition-all cursor-pointer
                                        {{ $isProyekActive
                                            ? 'border-[#D3AD7B] bg-[#D3AD7B]/15 text-[#A28153] ring-2 ring-[#D3AD7B]/20'
                                            : 'border-neutral-200/80 bg-neutral-50/50 text-neutral-700 hover:bg-white hover:border-[#D3AD7B]/50' }}">
                                        {{ $proyek }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- STEP 4: PILIH MODEL HP (Muncul jika kategori sudah dipilih atau tidak ada kategori terpisah) --}}
                @if ($selected_categoryName || count($available_categories) === 0)
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80 space-y-4 animate-fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-[#A28153] uppercase tracking-wider">
                                    {{ count($available_proyek) > 0 ? 'Langkah 4' : 'Langkah 3' }}
                                </span>
                                <h2 class="text-lg font-bold text-neutral-800">
                                    Pilih Tipe / Model HP ({{ count($available_models) }} model tersedia)
                                </h2>
                            </div>
                            @if ($selected_proyek)
                                <span class="text-xs font-semibold text-neutral-400">
                                    Proyek: <strong class="text-neutral-700">{{ $selected_proyek }}</strong>
                                </span>
                            @endif
                        </div>

                        @if (count($available_models) > 0)
                            <!-- Alpine.js Searchable Combobox Dropdown (Ultra Cepat, 0ms latency) -->
                            <div x-data="{
                                open: false,
                                search: '',
                                models: @js($available_models),
                                get filteredModels() {
                                    let searchStr = (this.search || '').toString().toLowerCase().trim();
                                    if (!searchStr) return this.models;
                                    return this.models.filter(m => (m.name || '').toString().toLowerCase().includes(searchStr) || (m.item_no || '').toString().toLowerCase().includes(searchStr));
                                },
                                selectModel(model) {
                                    this.search = model.name;
                                    this.open = false;
                                    $wire.selectProduct(model.id);
                                }
                            }" wire:key="model-combo-{{ $selected_brand_id }}-{{ $selected_categoryName }}-{{ $selected_proyek }}"
                                @click.away="open = false" class="relative w-full">

                                <div class="relative w-full">
                                    <input type="text" x-model="search" @focus="open = true" @click="open = true"
                                        placeholder="Ketik nama tipe model HP untuk mencari (contoh: 13 128GB, Pro Max, dsb)..."
                                        autocomplete="off"
                                        class="w-full p-4 bg-neutral-50/70 hover:bg-white shadow-xs border-2 border-neutral-200 focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 rounded-2xl outline-none transition-all font-bold text-neutral-800 cursor-text pr-12 text-sm md:text-base">

                                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-neutral-400">
                                        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Dropdown List Hasil Pencarian Model -->
                                <div x-show="open" x-transition.opacity.duration.150ms x-cloak
                                    class="absolute z-50 w-full mt-2 bg-white border border-neutral-200 rounded-2xl shadow-xl max-h-72 overflow-y-auto p-2 space-y-1">
                                    <template x-for="model in filteredModels.slice(0, 50)" :key="model.id">
                                        <button @mousedown="selectModel(model)" @click="selectModel(model)"
                                            type="button"
                                            class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-colors flex items-center justify-between hover:bg-neutral-100 group cursor-pointer text-neutral-700">
                                            <div>
                                                <span x-text="model.name" class="block leading-snug"></span>
                                                <span x-show="model.item_no" x-text="model.item_no" class="text-[11px] text-neutral-400 font-mono"></span>
                                            </div>
                                            <span x-show="model.formatted_price" x-text="model.formatted_price"
                                                class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-lg shrink-0 ml-2"></span>
                                        </button>
                                    </template>

                                    <div x-show="filteredModels.length === 0" class="px-4 py-4 text-xs text-neutral-400 text-center font-medium">
                                        Tidak ada model yang cocok dengan pencarian "<span x-text="search" class="font-bold"></span>".
                                    </div>
                                    <div x-show="filteredModels.length > 50" class="px-4 py-2 text-[11px] text-neutral-400 text-center border-t border-neutral-100">
                                        Menampilkan 50 model pertama. Ketik lebih spesifik untuk mempersempit.
                                    </div>
                                </div>
                            </div>

                            {{-- Jika model sedikit (<= 12 items), tampilkan juga tombol kartu cepat di bawahnya --}}
                            @if (count($available_models) <= 12)
                                <div class="pt-2">
                                    <span class="text-xs font-bold text-neutral-500 mb-2 block">Atau pilih langsung dari daftar:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                        @foreach ($available_models as $m)
                                            <button type="button" wire:click="selectProduct({{ $m['id'] }})"
                                                class="p-3.5 rounded-2xl border border-neutral-200/80 bg-neutral-50/40 hover:bg-white hover:border-[#D3AD7B] hover:shadow-xs transition-all text-left flex flex-col justify-between group cursor-pointer">
                                                <span class="font-bold text-xs text-neutral-800 group-hover:text-[#A28153] leading-snug line-clamp-2">
                                                    {{ $m['name'] }}
                                                </span>
                                                <div class="mt-2 pt-1.5 border-t border-neutral-200/50 flex items-center justify-between text-[11px]">
                                                    <span class="text-neutral-400 font-mono">{{ $m['item_no'] }}</span>
                                                    <span class="font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">
                                                        {{ $m['formatted_price'] }}
                                                    </span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="p-6 text-center text-xs text-neutral-400 bg-neutral-50 rounded-2xl border border-neutral-200">
                                {{ count($available_proyek) > 0 ? 'Pilih proyek/kondisi pada Langkah 3 untuk memuat model HP.' : 'Tidak ada model pada kategori ini.' }}
                            </div>
                        @endif
                    </div>
                @endif
            @endif

        @else
            {{-- JIKA MODEL SUDAH DIPILIH: TAMPILKAN CHECKLIST SIMULASI HARGA REAL-TIME --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- KOLOM KIRI (2 SPAN): KARTU PERANGKAT & CHECKLIST KONDISI --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Info Perangkat Terpilih --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80 relative overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-[#A28153] border border-[#D3AD7B]/40 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#A28153] uppercase tracking-wider">
                                            {{ $selected_product['brand'] }}
                                        </span>
                                        <span class="text-neutral-300">•</span>
                                        <span class="text-xs text-neutral-500 font-medium">
                                            {{ $selected_product['proyek'] }}
                                        </span>
                                    </div>
                                    <h2 class="text-xl md:text-2xl font-black text-neutral-900 mt-0.5 leading-tight">
                                        {{ $selected_product['name'] }}
                                    </h2>
                                    <span class="text-xs text-neutral-400 font-mono mt-1 block">
                                        SKU: {{ $selected_product['item_no'] }}
                                    </span>
                                </div>
                            </div>

                            <button type="button" wire:click="resetAll"
                                class="self-start sm:self-center px-3.5 py-2 rounded-xl text-xs font-bold text-neutral-600 hover:text-neutral-900 bg-neutral-100 hover:bg-neutral-200 transition-colors flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Ganti Tipe HP
                            </button>
                        </div>

                        {{-- Reference Price Banner --}}
                        <div class="mt-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <span class="text-[11px] font-black uppercase tracking-wider text-amber-800">
                                    Harga Acuan Grade A (Mulus Prima)
                                </span>
                                <p class="text-xs text-amber-900/80 mt-0.5">
                                    Patokan awal dari sistem Accurate sebelum dikurangi minus kualifikasi fisik unit.
                                </p>
                            </div>
                            <span class="text-xl md:text-2xl font-black text-amber-900 shrink-0">
                                Rp {{ number_format($base_price, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- CHECKLIST KONDISI UNIT (GROUPED BY CATEGORY) --}}
                    {{-- PENTING: Hanya label kondisi saja yang ditampilkan, TIDAK ADA harga minus di tombol --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80 space-y-6">
                        <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                            <div>
                                <h3 class="text-lg font-bold text-neutral-900">Kondisi & Kelengkapan HP</h3>
                                <p class="text-xs text-neutral-500 mt-0.5">
                                    Pilih kondisi riil HP customer. Estimasi harga akan berkurang secara otomatis.
                                </p>
                            </div>
                            <button type="button" wire:click="resetConditionRules"
                                class="text-xs font-bold text-neutral-500 hover:text-neutral-800 underline cursor-pointer">
                                Reset Checklist
                            </button>
                        </div>

                        <div class="space-y-6">
                            @foreach ($grouped_rules as $catName => $catData)
                                @php
                                    $isMultiple = $catData['is_multiple'];
                                    $items = $catData['items'];
                                @endphp
                                <div class="space-y-3" wire:key="group-{{ \Illuminate\Support\Str::slug($catName) }}">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-black uppercase tracking-wider text-neutral-600">
                                            {{ $catName }}
                                        </h4>
                                        <span class="text-[10px] text-neutral-400 font-semibold">
                                            ({{ $isMultiple ? 'Bisa pilih lebih dari 1' : 'Pilih salah satu' }})
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-2.5">
                                        @foreach ($items as $rule)
                                            @php
                                                $isSelected = $isMultiple
                                                    ? !empty($selected_rules[$rule['key']])
                                                    : ($selected_rules[$catName] ?? '') === $rule['key'];
                                            @endphp
                                            <button type="button" wire:key="btn-rule-{{ $rule['key'] }}"
                                                wire:click="toggleRule('{{ $rule['key'] }}', '{{ $catName }}', {{ $isMultiple ? 'true' : 'false' }})"
                                                class="py-2.5 px-4 rounded-xl flex items-center justify-center transition-all cursor-pointer select-none text-xs md:text-sm font-bold border-2
                                                {{ $isSelected
                                                    ? 'border-[#D3AD7B] bg-[#D3AD7B]/15 text-[#A28153] shadow-xs ring-2 ring-[#D3AD7B]/20'
                                                    : 'border-neutral-200/80 bg-neutral-50/50 text-neutral-700 hover:bg-white hover:border-[#D3AD7B]/50' }}">

                                                @if ($isSelected)
                                                    <svg class="w-4 h-4 mr-1.5 text-[#A28153] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                @endif

                                                {{-- HANYA LABEL SAJA SESUAI PERMINTAAN USER --}}
                                                <span>{{ $rule['name'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>

                                    {{-- Info deskripsi bantuan untuk kondisi yang dipilih --}}
                                    @php
                                        $activeDescriptions = [];
                                        foreach ($items as $r) {
                                            $active = $isMultiple
                                                ? !empty($selected_rules[$r['key']])
                                                : ($selected_rules[$catName] ?? '') === $r['key'];

                                            if ($active && !empty($r['description'])) {
                                                $activeDescriptions[] = $r['description'];
                                            }
                                        }
                                    @endphp

                                    @if (count($activeDescriptions) > 0)
                                        <div class="p-3 bg-neutral-50 rounded-xl text-xs text-neutral-500 border border-neutral-100 flex items-start gap-2">
                                            <svg class="w-4 h-4 text-neutral-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="space-y-1">
                                                @foreach ($activeDescriptions as $desc)
                                                    <p class="leading-relaxed">{{ $desc }}</p>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN (1 SPAN): STICKY ESTIMASI HARGA & AKSI --}}
                <div class="space-y-6">
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200/80 sticky top-6 space-y-6">
                        <div>
                            <span class="text-xs font-bold text-neutral-400 uppercase tracking-wider block">Hasil Perhitungan</span>
                            <h3 class="text-lg font-black text-neutral-900 mt-0.5">Estimasi Harga Beli</h3>
                        </div>

                        {{-- KARTU HARGA AKHIR --}}
                        <div class="p-5 rounded-2xl bg-linear-to-br from-emerald-500 to-teal-700 text-white shadow-lg shadow-emerald-500/20">
                            <span class="text-xs font-semibold text-emerald-100 uppercase tracking-wider block">
                                Estimasi Realistis (Net)
                            </span>
                            <div class="text-3xl md:text-4xl font-black mt-2 tracking-tight">
                                Rp {{ number_format($calculated_price, 0, ',', '.') }}
                            </div>
                            <p class="text-[11px] text-emerald-100 mt-2 leading-relaxed">
                                Taksiran harga beli terdekat sesuai kondisi unit yang dicentang.
                            </p>
                        </div>

                        {{-- RINGKASAN KONDISI TERPILIH --}}
                        <div class="space-y-2 border-t border-neutral-100 pt-4">
                            <div class="flex items-center justify-between text-xs font-bold text-neutral-600">
                                <span>Kondisi Unit Terpilih:</span>
                                <span>{{ count($selectedConditionLabels) }} minus/catatan</span>
                            </div>

                            @if (count($selectedConditionLabels) > 0)
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    @foreach ($selectedConditionLabels as $label)
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                            {{ $label }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-3 text-center text-xs text-emerald-800 bg-emerald-50 rounded-xl font-medium border border-emerald-100">
                                    ✨ Kondisi Mulus Prima 99% (Grade A)
                                </div>
                            @endif
                        </div>

                        {{-- TOMBOL SALIN TEKS ESTIMASI UNTUK CUSTOMER --}}
                        <div class="space-y-3 pt-2">
                            <button type="button" @click="copyText(@js($estimateText))"
                                class="w-full py-3.5 px-4 rounded-2xl bg-[#4E44DB] hover:bg-[#3f36b8] text-white font-bold text-sm flex items-center justify-center gap-2 shadow-sm transition-all duration-200 cursor-pointer">
                                <template x-if="!copied">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                        </svg>
                                        <span>Salin Teks Estimasi</span>
                                    </div>
                                </template>
                                <template x-if="copied">
                                    <div class="flex items-center gap-2 text-emerald-200">
                                        <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Tersalin ke Clipboard!</span>
                                    </div>
                                </template>
                            </button>

                            <button type="button" wire:click="resetConditionRules"
                                class="w-full py-2.5 px-4 rounded-xl border border-neutral-200 hover:bg-neutral-50 text-neutral-600 font-semibold text-xs flex items-center justify-center gap-2 transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Reset Checklist Kondisi
                            </button>
                        </div>

                        {{-- Disclaimer Box --}}
                        <div class="p-3.5 bg-neutral-50 rounded-2xl border border-neutral-200/70 text-[11px] text-neutral-500 leading-relaxed">
                            💡 <strong>Tips CS & Kasir:</strong> Gunakan estimasi ini untuk memberikan taksiran awal yang transparan kepada customer via WhatsApp atau secara langsung sebelum proses QC fisik.
                        </div>
                    </div>
                </div>

            </div>
        @endif

    </div>
</div>
