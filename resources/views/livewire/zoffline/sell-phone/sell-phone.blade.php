<div class="max-w-7xl mx-auto p-2 md:p-6 min-h-screen" x-data="{ step: 1 }" x-cloak>
    {{-- Header Navigation --}}
    <div class="flex gap-2">
        <a href="/"
            class="bg-neutral-500 hover:bg-neutral-600 transition-colors text-white px-3 flex justify-center items-center rounded-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="size-6 md:size-8 rotate-180">
                <path fill-rule="evenodd"
                    d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                    clip-rule="evenodd" />
            </svg>
        </a>
        <div class="w-full flex items-center bg-linear-to-r from-[#D3AD7B] to-[#A28153] py-3 px-6 rounded-md shadow-sm">
            <h1 class="text-white text-xl md:text-4xl font-bold">Transaksi Pembelian</h1>
        </div>
    </div>

    {{-- Title Section --}}
    {{-- <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 mt-10 text-left">
        <div class="space-y-2 mx-auto md:mx-0">
            <h1 class="text-3xl md:text-5xl font-black text-neutral-900 tracking-tight">
                Jual HP <span class="text-violet-600">Instan.</span>
            </h1>
            <p class="text-neutral-500 text-sm md:text-base  font-medium max-w-md">Ubah gadget lamamu menjadi uang tunai
                dengan proses yang
                cepat dan transparan.</p>
        </div>
    </div> --}}

    {{-- Stepper Indicator (Visual Progress) - Amber Version --}}
    <div class="mb-14 w-full max-w-7xl mx-auto  mt-4">
        <div class="flex justify-between items-start relative">
            <!-- Progress Line Background -->
            {{-- Posisi top disetel 20px agar tepat memotong tengah lingkaran --}}
            <div
                class="absolute left-0 top-[20px] transform -translate-y-1/2 w-full h-1 bg-neutral-200 rounded-full z-0">
            </div>

            <!-- Progress Line Active -->
            <div class="absolute left-0 top-[20px] transform -translate-y-1/2 h-1 bg-linear-to-r from-[#D3AD7B] to-[#A28153] rounded-full z-0 transition-all duration-500 ease-in-out"
                :style="'width: ' + ((step - 1) * 33.33) + '%'"></div>

            <!-- Step 1 Dot -->
            <div class="relative z-10 flex flex-col items-center cursor-pointer group" @click="step = 1">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 1 ?
                        'bg-linear-to-br from-[#D3AD7B] to-[#A28153] text-white shadow-lg shadow-[#A28153]/40 ring-4 ring-[#D3AD7B]/30' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    1
                </div>
                {{-- Teks sekarang fleksibel dan wrap otomatis --}}
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300 w-auto"
                    :class="step >= 1 ? 'text-[#A28153]' : 'text-neutral-400'">
                    Spesifikasi
                </span>
            </div>

            <!-- Step 2 Dot -->
            <div class="relative z-10 flex flex-col items-center cursor-pointer group" @click="if(step > 2) step = 2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 2 ?
                        'bg-linear-to-br from-[#D3AD7B] to-[#A28153] text-white shadow-lg shadow-[#A28153]/40 ring-4 ring-[#D3AD7B]/30' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    2
                </div>
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300 w-auto"
                    :class="step >= 2 ? 'text-[#A28153]' : 'text-neutral-400'">
                    QC Fisik
                </span>
            </div>

            <!-- Step 3 Dot -->
            <div class="relative z-10 flex flex-col items-center cursor-pointer group" @click="if(step > 3) step = 3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 3 ?
                        'bg-linear-to-br from-[#D3AD7B] to-[#A28153] text-white shadow-lg shadow-[#A28153]/40 ring-4 ring-[#D3AD7B]/30' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    3
                </div>
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300 w-sauto"
                    :class="step >= 3 ? 'text-[#A28153]' : 'text-neutral-400'">
                    Kondisi
                </span>
            </div>

            <!-- Step 4 Dot -->
            <div class="relative z-10 flex flex-col items-center group">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 4 ?
                        'bg-linear-to-br from-[#D3AD7B] to-[#A28153] text-white shadow-lg shadow-[#A28153]/40 ring-4 ring-[#D3AD7B]/30' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    4
                </div>
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300 w-sauto"
                    :class="step >= 4 ? 'text-[#A28153]' : 'text-neutral-400'">
                    Ringkasan
                </span>
            </div>
        </div>
    </div>

    {{-- Form Steps Container --}}
    <div class="mt-16 max-w-7xl mx-auto">

        {{-- STEP 1: Device Info --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            class="space-y-8">

            {{-- Brand Selection Cards (Classy Typographic Design - No Logo - Large Premium Size) --}}
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-2.5 h-6 bg-linear-to-b from-[#D3AD7B] to-[#A28153] rounded-full inline-block"></span>
                        <h2 class="text-sm md:text-base font-black text-neutral-800 uppercase tracking-widest">
                            Pilih Merk Perangkat
                        </h2>
                    </div>
                    @if (!empty($brands) && count($brands) > 0)
                        <span
                            class="text-xs font-bold text-neutral-500 bg-neutral-100 px-3 py-1.5 rounded-full border border-neutral-200/60">
                            {{ count($brands) }} Pilihan Brand
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    @foreach ($brands ?? [] as $brand)
                        <label wire:key="brand-{{ $brand->id }}" class="relative cursor-pointer group select-none">
                            <input type="radio" wire:model.live="selected_brand_id" value="{{ $brand->id }}"
                                class="peer hidden">
                            <div
                                class="relative overflow-hidden rounded-3xl md:rounded-[2rem] p-6 md:p-8 transition-all duration-300 bg-white border-2 border-neutral-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:border-[#D3AD7B] hover:shadow-[0_12px_32px_-6px_rgba(211,173,123,0.18)] hover:-translate-y-1 peer-checked:border-[#A28153] peer-checked:bg-linear-to-br peer-checked:from-[#D3AD7B] peer-checked:to-[#A28153] peer-checked:shadow-xl peer-checked:shadow-[#A28153]/40 peer-checked:scale-[1.02] peer-checked:[&_.brand-name]:text-white peer-checked:[&_.brand-tag]:text-white/80 peer-checked:[&_.check-badge]:border-white peer-checked:[&_.check-badge]:bg-white peer-checked:[&_.check-icon]:opacity-100 peer-checked:[&_.watermark]:text-white/10 flex flex-col justify-between min-h-[140px] md:min-h-[165px]">

                                {{-- Monogram Watermark --}}
                                <span
                                    class="watermark absolute -right-2 -bottom-4 text-8xl md:text-9xl font-black tracking-tighter text-neutral-900/[0.04] select-none pointer-events-none group-hover:scale-110 group-hover:text-neutral-900/[0.06] transition-all duration-300 uppercase">
                                    {{ substr($brand->name, 0, 1) }}
                                </span>

                                {{-- Top Row: Tag & Check Indicator --}}
                                <div class="flex items-center justify-between z-10">
                                    <span
                                        class="brand-tag text-[10px] md:text-xs font-black uppercase tracking-widest text-neutral-400 group-hover:text-[#A28153] transition-colors">
                                        BRAND PERANGKAT
                                    </span>
                                    <div
                                        class="check-badge w-6 h-6 md:w-7 md:h-7 rounded-full border-2 border-neutral-200 group-hover:border-[#D3AD7B] flex items-center justify-center transition-all duration-200">
                                        <svg class="check-icon w-3.5 h-3.5 md:w-4 md:h-4 text-[#A28153] opacity-0 transition-opacity"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>

                                {{-- Brand Name --}}
                                <div class="z-10 mt-4">
                                    <h3 class="brand-name text-xl md:text-2xl lg:text-3xl font-black tracking-wider uppercase text-neutral-800 group-hover:text-[#A28153] transition-colors truncate"
                                        title="{{ $brand->name }}">
                                        {{ $brand->name }}
                                    </h3>
                                </div>

                            </div>
                        </label>
                    @endforeach
                </div>
                @error('selected_brand_id')
                    <span class="text-xs text-rose-500 font-bold block mt-3">{{ $message }}</span>
                @enderror
            </div>

            {{-- Detail Inputs (Hanya muncul jika brand sudah dipilih) --}}
            <div x-show="$wire.selected_brand_id" x-cloak x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 md:col-span-1">
                        <div x-data="{
                            open: false,
                            search: '',
                            selected: @entangle('selected_categoryName').live,
                            get filteredOptions() {
                                let opts = $wire.available_categories || [];
                                if (this.search === '') return opts;
                                return opts.filter(i => i && i.toLowerCase().includes(this.search.toLowerCase()));
                            }
                        }" @click.away="open = false" class="relative">
                            <label
                                class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block mb-2">Kategori</label>

                            <div @click="open = !open"
                                class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 outline-none transition-all font-bold cursor-pointer flex justify-between items-center"
                                :class="selected ? 'text-neutral-700' : 'text-neutral-400'">
                                <span x-text="selected ? selected : 'Pilih Kategori'"></span>
                                <svg class="w-5 h-5 transition-transform"
                                    :class="open ? 'rotate-180 text-[#D3AD7B]' : 'text-neutral-400'" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>

                            <div x-show="open" x-transition x-cloak
                                class="absolute z-50 w-full mt-2   bg-white border border-neutral-100 rounded-2xl shadow-xl shadow-neutral-200/50 flex flex-col overflow-hidden">
                                <div class="p-3 border-b border-neutral-100 bg-neutral-50/50">
                                    <input type="text" x-model="search" placeholder="Cari Kategori..."
                                        class="w-full bg-white border border-neutral-200 rounded-xl py-2.5 px-4 text-sm focus:border-[#D3AD7B] focus:ring-2 focus:ring-[#D3AD7B]/20 transition-all outline-none font-medium">
                                </div>
                                <div class="p-2">
                                    <ul class="max-h-52 overflow-y-auto space-y-1">
                                        <li @click="selected = ''; open = false; search = ''"
                                            class="px-4 py-3 text-sm rounded-xl cursor-pointer hover:bg-neutral-100 font-medium text-neutral-500 transition-colors">
                                            Pilih Kategori
                                        </li>
                                        <template x-for="item in filteredOptions" :key="item">
                                            <li @click="typeof item !== 'undefined' ? (selected = item, open = false, search = '') : null"
                                                class="px-4 py-3 text-sm rounded-xl cursor-pointer hover:bg-[#D3AD7B]/10 transition-colors font-bold text-neutral-700 flex items-center justify-between"
                                                :class="typeof item !== 'undefined' && selected === item ?
                                                    'bg-[#D3AD7B]/10 text-[#A28153]' : ''">
                                                <span x-text="typeof item !== 'undefined' ? item : ''"></span>
                                                <svg x-show="typeof item !== 'undefined' && selected === item"
                                                    class="w-4 h-4 text-[#D3AD7B]" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0"
                                            class="px-4 py-4 text-sm text-center text-neutral-400 font-medium">
                                            Tidak ditemukan
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @error('selected_categoryName')
                            <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div x-show="$wire.selected_categoryName" x-cloak class="space-y-2 md:col-span-1">
                        <div x-data="{
                            open: false,
                            search: '',
                            selected: @entangle('selected_proyek').live,
                            get filteredOptions() {
                                let opts = $wire.available_proyek || [];
                                if (this.search === '') return opts;
                                return opts.filter(i => i && i.toLowerCase().includes(this.search.toLowerCase()));
                            }
                        }" @click.away="open = false" class="relative">
                            <label
                                class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block mb-2">Status
                                Perangkat</label>

                            <div @click="open = !open"
                                class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 outline-none transition-all font-bold cursor-pointer flex justify-between items-center"
                                :class="selected ? 'text-neutral-700' : 'text-neutral-400'">
                                <span x-text="selected ? selected : 'Pilih Status Perangkat'"></span>
                                <svg class="w-5 h-5 transition-transform"
                                    :class="open ? 'rotate-180 text-[#D3AD7B]' : 'text-neutral-400'" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>

                            <div x-show="open" x-transition x-cloak
                                class="absolute z-50 w-full mt-2 bg-white border border-neutral-100 rounded-2xl shadow-xl shadow-neutral-200/50 flex flex-col overflow-hidden">
                                <div class="p-3 border-b border-neutral-100 bg-neutral-50/50">
                                    <input type="text" x-model="search" placeholder="Cari Status..."
                                        class="w-full bg-white border border-neutral-200 rounded-xl py-2.5 px-4 text-sm focus:border-[#D3AD7B] focus:ring-2 focus:ring-[#D3AD7B]/20 transition-all outline-none font-medium">
                                </div>
                                <div class="p-2">
                                    <ul class="max-h-52 overflow-y-auto space-y-1">
                                        <li @click="selected = ''; open = false; search = ''"
                                            class="px-4 py-3 text-sm rounded-xl cursor-pointer hover:bg-neutral-100 font-medium text-neutral-500 transition-colors">
                                            Pilih Status Perangkat
                                        </li>
                                        <template x-for="item in filteredOptions" :key="item">
                                            <li @click="typeof item !== 'undefined' ? (selected = item, open = false, search = '') : null"
                                                class="px-4 py-3 text-sm rounded-xl cursor-pointer hover:bg-[#D3AD7B]/10 transition-colors font-bold text-neutral-700 flex items-center justify-between"
                                                :class="typeof item !== 'undefined' && selected === item ?
                                                    'bg-[#D3AD7B]/10 text-[#A28153]' : ''">
                                                <span x-text="typeof item !== 'undefined' ? item : ''"></span>
                                                <svg x-show="typeof item !== 'undefined' && selected === item"
                                                    class="w-4 h-4 text-[#D3AD7B]" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0"
                                            class="px-4 py-4 text-sm text-center text-neutral-400 font-medium">
                                            Tidak ditemukan
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @error('selected_proyek')
                            <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div x-show="$wire.selected_proyek" x-cloak class="space-y-2 md:col-span-2">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">Model /
                            Seri</label>

                        <!-- Input Combobox dengan Dropdown bergaya Select (Alpine.js) -->
                        <div x-data="{
                            open: false,
                            search: @entangle('selected_model_name').live,
                            models: @entangle('available_models'),
                            get filteredModels() {
                                let searchStr = (this.search || '').toString().toLowerCase();
                                if (!searchStr) return this.models;
                                return this.models.filter(m => (m || '').toString().toLowerCase().includes(searchStr));
                            },
                            selectModel(model) {
                                this.search = model;
                                this.open = false;
                            }
                        }" wire:key="model-combo" @click.away="open = false"
                            class="relative w-full">

                            <!-- Input Pencarian (Langsung di input field utama) -->
                            <div class="relative w-full">
                                <input type="text" x-model="search" @focus="open = true" @click="open = true"
                                    placeholder="Ketik atau pilih Model HP..." autocomplete="off"
                                    class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 outline-none transition-all font-bold text-neutral-700 cursor-text pr-12">

                                <!-- Ikon Panah (Agar terlihat persis seperti select dropdown) -->
                                <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-neutral-400 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Dropdown List yang muncul ke bawah -->
                            <div x-show="open" x-transition.opacity.duration.200ms x-cloak
                                class="absolute z-50 w-full mt-2 bg-white border border-neutral-100 rounded-2xl shadow-xl max-h-60 overflow-y-auto p-2 space-y-1">

                                <template x-for="model in filteredModels" :key="model">
                                    <button @mousedown="selectModel(model)" @click="selectModel(model)"
                                        type="button"
                                        class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-colors"
                                        :class="model === search ? 'bg-[#D3AD7B]/20 text-[#A28153]' :
                                            'text-neutral-600 hover:bg-neutral-100'">
                                        <span x-text="model"></span>
                                    </button>
                                </template>

                                <!-- Jika tidak ada hasil -->
                                <div x-show="filteredModels.length === 0"
                                    class="px-4 py-3 text-sm text-neutral-500 text-center font-medium">
                                    Model tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="flex justify-end pt-4 pb-10">
                <button type="button" @click="step = 2" :disabled="!$wire.selected_model_name"
                    class="px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-2 shadow-lg active:scale-95"
                    :class="$wire.selected_model_name ?
                        'bg-linear-to-r from-[#D3AD7B] to-[#A28153] hover:from-[#C39D6B] hover:to-[#927143] text-white shadow-[#A28153]/30' :
                        'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none'">
                    Lanjut QC Kelayakan
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- STEP 2: QC Kelayakan Fisik --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-8">

            <h3 class="text-lg md:text-xl uppercase font-black text-neutral-800 px-1">Inspeksi Kelayakan</h3>

            @php
                // Mengelompokkan item QC berdasarkan kategori
                $groupedQc = collect($qc_results)->groupBy('category');
                $categories = $groupedQc->keys();
                // Sub-step: 0 = Foto & IMEI, 1..N = Kategori QC, N+1 = Verdict
                $maxQcStep = $categories->count() + 1;
            @endphp

            <div x-data="{ qcStep: 0 }"
                class="bg-white p-4 md:p-6 rounded-2xl shadow-sm border border-neutral-100 relative">

                {{-- Progress Bar QC --}}
                <div class="mb-6 relative">
                    <div class="h-2 bg-neutral-100 rounded-full overflow-hidden">
                        <div class="h-full bg-linear-to-r from-[#D3AD7B] to-[#A28153] transition-all duration-300"
                            :style="'width: ' + ((qcStep / {{ $maxQcStep }}) * 100) + '%'"></div>
                    </div>
                    <div class="mt-2 text-xs font-bold text-neutral-500 text-right">Tahap <span
                            x-text="qcStep"></span>
                        dari <span>{{ $maxQcStep }}</span></div>
                </div>

                {{-- QC SUB-STEP 0: Foto Produk & IMEI --}}
                <div x-show="qcStep === 0" x-transition.opacity class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">IMEI Perangkat
                            <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model.live.debounce.500ms="imei"
                            placeholder="Scan atau ketik IMEI..."
                            class="w-full p-4 bg-gray-50 shadow-sm border-2 border-transparent rounded-2xl focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 outline-none transition-all font-bold text-neutral-700">
                        @error('imei')
                            <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-4" x-data="{ activeSlot: null }">
                        <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">
                            Ambil Foto HP Live (Maks. 5MB/Foto)
                        </h1>
                        <p class="text-xs text-neutral-400 -mt-2 mb-4 ml-1">Semua foto harus diambil langsung dari
                            kamera saat ini.</p>

                        @php
                            $slots = [
                                'depan' => 'Tampak Depan',
                                'belakang' => 'Tampak Belakang',
                                'kiri' => 'Samping Kiri',
                                'kanan' => 'Samping Kanan',
                                'atas' => 'Tampak Atas',
                                'bawah' => 'Tampak Bawah',
                                'box' => 'Box Belakang',
                                'kelengkapan' => 'Kelengkapan / Box',
                            ];
                        @endphp

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            @foreach ($slots as $key => $label)
                                @php
                                    $propertyName = 'photo_' . $key;
                                    $photoFile = $this->{$propertyName};
                                    $hasError = $errors->has($propertyName);
                                @endphp

                                <div x-data="{ localPreview: null }"
                                    class="relative aspect-square rounded-3xl overflow-hidden transition-all duration-300 group
                                 {{ $photoFile ? 'border border-neutral-100 shadow-sm' : 'border-2 border-dashed bg-neutral-50/50 hover:bg-neutral-50/100 cursor-pointer' }}
                                {{ $hasError ? 'border-rose-300 bg-rose-50/20' : 'border-neutral-200 hover:border-neutral-300' }}">

                                    {{-- PREVIEW MURNI DARI BROWSER (ALPINE JS) --}}
                                    <template x-if="localPreview">
                                        <div class="absolute inset-0 z-20">
                                            <img :src="localPreview"
                                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

                                            {{-- Label Bawah --}}
                                            <div
                                                class="absolute inset-x-0 bottom-0 bg-black/40 backdrop-blur-xs py-2 px-3 text-center pointer-events-none z-30">
                                                <span
                                                    class="text-[11px] font-bold text-white tracking-wide block truncate">{{ $label }}</span>
                                            </div>

                                            {{-- Tombol Hapus --}}
                                            <button type="button"
                                                @click="localPreview = null; $wire.set('{{ $propertyName }}', null)"
                                                class="absolute top-2 right-2 bg-white/80 hover:bg-white text-neutral-800 p-2 rounded-xl backdrop-blur-md shadow-sm transition hover:scale-105 active:scale-95 z-40 flex items-center justify-center">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-16v1M4 7h16" />
                                                </svg>
                                            </button>

                                            {{-- Loading Overlay (Saat proses upload ke Livewire berlangsung) --}}
                                            <div wire:loading.flex wire:target="{{ $propertyName }}"
                                                class="absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center z-30">
                                                <svg class="animate-spin w-8 h-8 text-[#A28153]" fill="none"
                                                    viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- TOMBOL UPLOAD KAMERA --}}
                                    <label x-show="!localPreview"
                                        class="absolute inset-0 flex flex-col items-center justify-center p-3 text-center select-none overflow-hidden cursor-pointer z-10">
                                        <input type="file" accept="image/*" capture="environment" class="hidden"
                                            @change="if($event.target.files.length > 0) { localPreview = URL.createObjectURL($event.target.files[0]); } customCompressHandler($event, '{{ $propertyName }}')">
                                        <div
                                            class="absolute inset-0 flex items-center justify-center z-0 group-hover:scale-110 transition-transform duration-300">
                                            <img src="{{ asset('assets/png/' . $key . '.png') }}"
                                                alt="{{ $label }}"
                                                class="w-50 h-auto object-contain drop-shadow-sm {{ $hasError ? 'opacity-20' : 'opacity-30' }}"
                                                onerror="this.onerror=null; this.src='{{ asset('assets/png/default.png') }}';">
                                        </div>
                                        <div class="absolute bottom-3 inset-x-0">
                                            <span
                                                class="block text-[11px] font-bold tracking-wide {{ $hasError ? 'text-rose-500' : 'text-neutral-500 group-hover:text-neutral-700' }}">
                                                {{ $label }}
                                            </span>
                                        </div>
                                    </label>


                                    @error('photo_' . $key)
                                        <div
                                            class="absolute inset-x-0 bottom-0 bg-rose-500 text-white p-2 text-center z-10 flex flex-col items-center justify-center h-12">
                                            <span class="text-[9px] font-bold uppercase">{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- QC SUB-STEPS (Categories) --}}
                @foreach ($categories as $index => $categoryName)
                    <div x-show="qcStep === {{ $index + 1 }}" x-transition.opacity style="display: none;"
                        class="space-y-6">
                        <h4
                            class="text-lg font-black text-[#A28153] uppercase tracking-wider border-b border-[#D3AD7B]/30 pb-2">
                            Pengecekan {{ $categoryName }}
                        </h4>

                        <div class="space-y-4">
                            @foreach ($qc_results as $i => $item)
                                @if ($item['category'] === $categoryName)
                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <span
                                                class="text-sm font-bold text-neutral-700">{{ $item['name'] }}</span>

                                            @if ($item['type'] === 'boolean')
                                                <div class="flex items-center gap-2">
                                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                                        <input type="radio"
                                                            wire:model="qc_results.{{ $i }}.value"
                                                            value="1" class="peer hidden">
                                                        <div
                                                            class="px-4 py-2 rounded-lg text-xs font-bold border transition-all
                                                            peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500
                                                            text-neutral-400 border-neutral-200 bg-white hover:bg-neutral-50 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            Berfungsi Normal
                                                        </div>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                                        <input type="radio"
                                                            wire:model="qc_results.{{ $i }}.value"
                                                            value="0" class="peer hidden">
                                                        <div
                                                            class="px-4 py-2 rounded-lg text-xs font-bold border transition-all
                                                            peer-checked:bg-rose-500 peer-checked:text-white peer-checked:border-rose-500
                                                            text-neutral-400 border-neutral-200 bg-white hover:bg-neutral-50 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                            Bermasalah
                                                        </div>
                                                    </label>
                                                </div>
                                            @else
                                                <input type="text"
                                                    wire:model.lazy="qc_results.{{ $i }}.value"
                                                    class="p-2 text-sm border border-gray-200 rounded-lg focus:ring-[#D3AD7B] focus:border-[#D3AD7B] bg-white"
                                                    placeholder="Isi data...">
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- QC SUB-STEP N+1: VERDICT --}}
                <div x-show="qcStep === {{ $maxQcStep }}" x-transition.opacity style="display: none;"
                    class="space-y-6">
                    <h4
                        class="text-lg font-black text-[#A28153] uppercase tracking-wider border-b border-[#D3AD7B]/30 pb-2">
                        Kesimpulan Sistem (Auto-Verdict)</h4>
                    <p class="text-sm text-neutral-500">Berdasarkan data inspeksi yang Anda masukkan, sistem menentukan
                        bahwa perangkat ini:</p>

                    <div class="mt-4">
                        {{-- Tampilan Dinamis Berdasarkan qc_verdict --}}
                        <div x-show="$wire.qc_verdict === 'pass'"
                            class="p-6 border-2 border-emerald-500 bg-emerald-50 rounded-2xl flex flex-col items-center justify-center text-center">
                            <div
                                class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h5 class="text-xl font-black text-emerald-700 mb-1">LAYAK BELI (PASS)</h5>
                            <p class="text-sm text-emerald-600 font-medium">Seluruh komponen perangkat berfungsi 100%
                                normal.</p>
                        </div>

                        <div x-show="$wire.qc_verdict === 'conditional'"
                            class="p-6 border-2 border-[#D3AD7B] bg-[#D3AD7B]/10 rounded-2xl flex flex-col items-center justify-center text-center">
                            <div
                                class="w-16 h-16 bg-[#D3AD7B]/20 text-[#A28153] rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <h5 class="text-xl font-black text-[#A28153] mb-1">BERSYARAT (NEEDS SERVICE)</h5>
                            <p class="text-sm text-[#A28153]/80 font-medium whitespace-pre-line"
                                x-text="$wire.qc_notes">
                            </p>
                        </div>

                        <div x-show="$wire.qc_verdict === 'fail'"
                            class="p-6 border-2 border-rose-500 bg-rose-50 rounded-2xl flex flex-col items-center justify-center text-center">
                            <div
                                class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h5 class="text-xl font-black text-rose-700 mb-1">TIDAK LAYAK (FAIL)</h5>
                            <p class="text-sm text-rose-700/80 font-medium whitespace-pre-line"
                                x-text="$wire.qc_notes">
                            </p>
                        </div>

                        {{-- Loading State saat hitung verdict --}}
                        <div x-show="!$wire.qc_verdict"
                            class="p-10 border-2 border-dashed border-neutral-200 bg-neutral-50 rounded-2xl flex flex-col items-center justify-center text-center">
                            <span
                                class="animate-spin w-8 h-8 border-4 border-[#A28153] border-t-transparent rounded-full mb-4"></span>
                            <p class="text-sm text-neutral-500 font-bold">Sistem sedang menganalisa data inspeksi...
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2 mt-6 border-t border-neutral-100 pt-6">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">Tambahan
                            Catatan Manual (Opsional)</label>
                        <textarea wire:model.live.debounce.1000ms="old_phone_additional_note" rows="3"
                            placeholder="Ketik catatan tambahan di luar analisa sistem jika ada..."
                            class="w-full p-4 bg-gray-50 shadow-sm border-2 border-transparent rounded-2xl focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 outline-none transition-all text-sm font-medium text-neutral-700"></textarea>
                    </div>
                </div>

                {{-- Wizard Navigation Buttons --}}
                @php
                    // Validasi foto dan imei untuk sub-step 0
                    $isPhotoValid =
                        !empty($photo_depan) &&
                        !empty($photo_belakang) &&
                        !empty($photo_kiri) &&
                        !empty($photo_kanan) &&
                        !empty($photo_kelengkapan);
                    $isStep0Valid = !empty($imei) && $isPhotoValid;
                @endphp
                <div
                    class="flex flex-col md:flex-row justify-between items-center mt-8 pt-4 border-t border-neutral-100 gap-4">
                    <div class="w-full md:w-auto flex justify-start">
                        <button x-show="qcStep > 0" type="button" @click="qcStep--"
                            class="text-neutral-500 hover:text-neutral-800 font-bold px-4 py-3 transition-colors flex items-center gap-2 border md:border-none border-neutral-200 rounded-xl md:rounded-none w-full md:w-auto justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Mundur
                        </button>
                        <button x-show="qcStep === 0" type="button" @click="step = 1"
                            class="text-neutral-500 hover:text-neutral-800 font-bold px-4 py-3 transition-colors flex items-center gap-2 border md:border-none border-neutral-200 rounded-xl md:rounded-none w-full md:w-auto justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Kembali ke Specs
                        </button>
                    </div>

                    <div class="w-full md:w-auto flex justify-end">
                        <button x-show="qcStep === 0" type="button" @click="qcStep++"
                            {{ $isStep0Valid ? '' : 'disabled' }}
                            class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full md:w-auto
                            {{ $isStep0Valid ? 'bg-linear-to-r from-[#D3AD7B] to-[#A28153] hover:from-[#C39D6B] hover:to-[#927143] text-white shadow-md shadow-[#A28153]/30' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed' }}">
                            Mulai Ceklis
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>

                        <button x-show="qcStep > 0 && qcStep < {{ $maxQcStep }}" type="button"
                            x-data="{ isCalculating: false }"
                            @click="if (qcStep === {{ $maxQcStep - 1 }}) { isCalculating = true; $wire.calculateAutoVerdict().then(() => { qcStep++; isCalculating = false; }) } else { qcStep++ }"
                            :disabled="isCalculating"
                            class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full md:w-auto"
                            :class="isCalculating ? 'bg-[#D3AD7B]/80 text-white cursor-not-allowed' :
                                'bg-linear-to-r from-[#D3AD7B] to-[#A28153] hover:from-[#C39D6B] hover:to-[#927143] text-white shadow-md shadow-[#A28153]/30'">

                            <span x-show="!isCalculating" class="flex items-center gap-2">
                                Lanjut
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </span>

                            <span x-show="isCalculating" class="flex items-center gap-2" style="display: none;">
                                <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Memproses Kesimpulan...
                            </span>
                        </button>

                        <button x-show="qcStep === {{ $maxQcStep }}" type="button"
                            @click="$wire.qc_verdict === 'fail' ? $wire.submit() : step = 3"
                            :disabled="!$wire.qc_verdict"
                            class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full md:w-auto"
                            :class="!$wire.qc_verdict ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : ($wire
                                .qc_verdict === 'fail' ? 'bg-rose-600 hover:bg-rose-700 text-white' :
                                'bg-linear-to-r from-[#D3AD7B] to-[#A28153] hover:from-[#C39D6B] hover:to-[#927143] text-white shadow-md shadow-[#A28153]/30'
                            )">
                            <span
                                x-text="$wire.qc_verdict === 'fail' ? 'Batalkan Transaksi' : 'Lanjut Kondisi Harga'"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- STEP 3: Condition & Price (Original) --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-8">

            <h3 class="text-lg md:text-xl uppercase font-black text-neutral-800 px-1">Inspeksi Minus / Penyesuaian
                Harga</h3>
            <div class="bg-[#D3AD7B]/10 border border-[#D3AD7B]/30 p-4 rounded-xl">
                <p class="text-sm font-bold text-[#A28153] mb-1">Potongan Harga Otomatis</p>
                <p class="text-xs text-[#A28153]/90">Tandai cacat fisik atau fungsi yang mengurangi nilai jual. Data
                    dari
                    Ceklis QC sebelumnya bisa Anda gunakan sebagai patokan.</p>
            </div>

            <div class="space-y-8 bg-white p-6 rounded-2xl shadow-sm border border-neutral-100">
                @if ($base_price > 0 && count($device_rules) > 0)
                    <div class="p-4 bg-[#D3AD7B]/10 border border-[#D3AD7B]/30 rounded-xl mb-6">
                        <p class="text-sm font-bold text-[#A28153] mb-2">Harga Dasar (Mulus 100%): Rp
                            {{ number_format($base_price, 0, ',', '.') }}</p>
                    </div>

                    @php
                        $groupedRules = collect($device_rules)->groupBy('category');
                    @endphp

                    @foreach ($groupedRules as $category => $rules)
                        <div class="space-y-3 mb-8">
                            <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">
                                {{ $category }}
                            </h1>

                            <div class="flex flex-wrap gap-3">
                                @foreach ($rules as $rule)
                                    <label class="cursor-pointer block group">
                                        @if ($rule['is_multiple'])
                                            <input type="checkbox"
                                                wire:model.live="selected_rules.{{ $rule['key'] }}" class="hidden">
                                        @else
                                            <input type="radio" name="{{ $category }}"
                                                value="{{ $rule['key'] }}"
                                                wire:model.live="selected_rules.{{ $category }}" class="hidden">
                                        @endif

                                        <div
                                            class="py-2.5 px-4 bg-white shadow-sm border-2 border-transparent rounded-xl flex flex-col items-center justify-center transition-all group-has-[:checked]:border-[#D3AD7B] group-has-[:checked]:bg-[#D3AD7B]/10 hover:border-[#D3AD7B]/50">

                                            <span
                                                class="text-sm font-bold text-neutral-600 group-has-[:checked]:text-[#A28153] transition-colors">
                                                {{ $rule['name'] }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            @php
                                $selectedDesc = null;
                                $selectedTitle = null;
                                if (str_contains(strtolower($category), 'kelengkapan')) {
                                    $descs = [];
                                    foreach ($rules as $rule) {
                                        if (!empty($selected_rules[$rule['key']]) && !empty($rule['description'])) {
                                            $descs[] = "<strong>{$rule['name']}</strong>: {$rule['description']}";
                                        }
                                    }
                                    if (count($descs) > 0) {
                                        $selectedDesc = implode('<br>', $descs);
                                        $selectedTitle = 'Catatan ' . $category;
                                    }
                                } else {
                                    if (isset($selected_rules[$category])) {
                                        $selectedKey = $selected_rules[$category];
                                        $selectedRule = collect($rules)->firstWhere('key', $selectedKey);
                                        if ($selectedRule && !empty($selectedRule['description'])) {
                                            $selectedDesc = $selectedRule['description'];
                                            $selectedTitle = 'Info ' . $selectedRule['name'];
                                        }
                                    }
                                }
                            @endphp

                            @if ($selectedDesc)
                                <div
                                    class="mt-3 p-3 rounded-xl flex items-start gap-3 transition-all duration-300 ease-out">
                                    <div class="mt-0.5 text-[#D3AD7B]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-[#A28153] mb-0.5">{{ $selectedTitle }}</p>
                                        <p class="text-xs text-[#A28153]/80 leading-relaxed">{!! $selectedDesc !!}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="p-6 bg-neutral-50 rounded-2xl text-center border border-neutral-200">
                        <p class="text-sm font-medium text-neutral-500">Silakan pilih perangkat pada langkah
                            sebelumnya untuk memuat formulir kondisi.</p>
                    </div>
                @endif

                <div class="space-y-3 pt-6 border-t border-neutral-100">
                    <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">Catatan
                        Tambahan Minus Harga</h1>
                    <textarea wire:model.lazy="old_phone_additional_note" rows="3"
                        placeholder="Jelaskan kondisi detail jika ada minus..."
                        class="w-full p-4 bg-gray-50 shadow-sm border-2 border-transparent rounded-2xl focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 outline-none transition-all font-medium text-neutral-700"></textarea>
                </div>
            </div>

            @php
                $rulesValid = false;
                if ($base_price > 0 && count($device_rules) > 0) {
                    $requiredCategories = collect($device_rules)
                        ->filter(function ($rule) {
                            return !$rule['is_multiple'];
                        })
                        ->pluck('category')
                        ->unique();

                    $filledCategoriesCount = collect($selected_rules)
                        ->filter(function ($value, $key) use ($requiredCategories) {
                            return $requiredCategories->contains($key) && !empty($value);
                        })
                        ->count();

                    $rulesValid = $filledCategoriesCount === $requiredCategories->count();
                }
            @endphp

            <div class="flex justify-between items-center pt-4 pb-10">
                <button type="button" @click="step = 2"
                    class="text-neutral-500 hover:text-neutral-800 font-bold px-6 py-4 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke QC
                </button>
                <button type="button" @click="step = 4" {{ $rulesValid ? '' : 'disabled' }}
                    class="px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-2 shadow-lg active:scale-95
                {{ $rulesValid ? 'bg-linear-to-r from-[#D3AD7B] to-[#A28153] hover:from-[#C39D6B] hover:to-[#927143] text-white shadow-[#A28153]/30' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none' }}">
                    Cek Ringkasan
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- STEP 4: Summary & Submit --}}
        <div x-show="step === 4" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-6">
            <div
                class="bg-white text-neutral-900 rounded-[2.5rem] p-8 md:p-10 shadow-sm border border-neutral-100 relative overflow-hidden">
                <h4
                    class="text-[#A28153] font-bold uppercase tracking-widest text-xs mb-6 relative z-10 flex items-center gap-2">
                    Ringkasan Unit Anda
                    <span class="w-2 h-2 bg-[#D3AD7B] rounded-full animate-pulse"></span>
                </h4>
                {{-- Form Input Khusus Role FL --}}
                @if (Auth::user())
                    <div class="mb-8 space-y-6">
                        {{-- Tabs --}}
                        <div class="flex p-1 bg-neutral-100 rounded-2xl w-fit">
                            <button type="button" wire:click="$set('isNewCustomer', true)"
                                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $isNewCustomer ? 'bg-white text-[#A28153] shadow-sm border border-[#D3AD7B]/30' : 'text-neutral-500 hover:text-neutral-700' }}">
                                Pelanggan Baru
                            </button>
                            <button type="button" wire:click="$set('isNewCustomer', false)"
                                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ !$isNewCustomer ? 'bg-white text-[#A28153] shadow-sm border border-[#D3AD7B]/30' : 'text-neutral-500 hover:text-neutral-700' }}">
                                Cari Pelanggan Lama
                            </button>
                        </div>

                        @if (!$isNewCustomer)
                            {{-- Mode: Cari Pelanggan Lama --}}
                            <div class="p-6 bg-neutral-50 rounded-3xl border border-neutral-100 space-y-4">
                                <p class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-2">
                                    Pencarian Pelanggan
                                </p>

                                @if ($selectedCustomerId)
                                    @php
                                        $selectedUser = \App\Models\User::find($selectedCustomerId);
                                        $firstBank = $selectedUser ? $selectedUser->bankAccounts()->first() : null;
                                    @endphp
                                    <div
                                        class="p-4 bg-[#D3AD7B]/10 border border-[#D3AD7B]/30 rounded-2xl flex flex-col gap-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p
                                                    class="text-[10px] font-black text-[#A28153] uppercase tracking-widest mb-1">
                                                    Pelanggan Terpilih</p>
                                                <h3 class="font-bold text-neutral-800">{{ $selectedUser->name }}</h3>
                                                <p class="text-xs text-neutral-500">{{ $selectedUser->email }} •
                                                    {{ $selectedUser->profile->phone_number ?? '-' }}</p>
                                            </div>
                                            <button type="button" wire:click="clearSelectedCustomer"
                                                class="text-rose-500 hover:bg-rose-50 px-3 py-1.5 rounded-lg transition-colors font-bold text-sm">
                                                Batal
                                            </button>
                                        </div>

                                        @if ($firstBank && !$needsBankInfo)
                                            <div class="pt-3 border-t border-[#D3AD7B]/30">
                                                <p
                                                    class="text-[10px] font-black text-[#A28153] uppercase tracking-widest mb-2">
                                                    Data Rekening Tersimpan</p>
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-10 h-10 bg-white rounded-xl flex items-center justify-center font-bold text-[#A28153] text-xs border border-[#D3AD7B]/30 shadow-sm uppercase">
                                                        {{ substr($firstBank->bank_name, 0, 3) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-neutral-800">
                                                            {{ $firstBank->account_number }}</p>
                                                        <p class="text-xs text-neutral-600 font-medium">a.n
                                                            {{ $firstBank->account_name }} <span
                                                                class="text-neutral-400">•</span>
                                                            {{ $firstBank->bank_name }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <input type="hidden" wire:model="selectedCustomerId"
                                        value="{{ $selectedCustomerId }}">
                                @else
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-neutral-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                                            class="w-full pl-11 pr-4 py-3 text-sm bg-white border @error('selectedCustomerId') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Cari nama, email, atau no HP...">
                                    </div>

                                    @if (strlen($searchCustomer) >= 2)
                                        <div
                                            class="bg-white border border-[#D3AD7B]/20 rounded-2xl shadow-lg max-h-60 overflow-y-auto divide-y mt-2 overflow-hidden">
                                            @forelse($this->customerResults as $user)
                                                <div wire:click="selectCustomer({{ $user->id }})"
                                                    class="p-4 hover:bg-[#D3AD7B]/10 cursor-pointer transition-colors flex justify-between items-center group">
                                                    <div>
                                                        <h4 class="font-bold text-neutral-800">{{ $user->name }}
                                                        </h4>
                                                        <p class="text-xs text-neutral-500">{{ $user->email }} •
                                                            {{ $user->profile->phone_number ?? '-' }}</p>
                                                    </div>
                                                    <span
                                                        class="text-[#A28153] font-bold text-sm opacity-0 group-hover:opacity-100 transition-opacity">Pilih</span>
                                                </div>
                                            @empty
                                                <div class="p-4 text-center text-neutral-500 text-sm">
                                                    Pelanggan tidak ditemukan.
                                                </div>
                                            @endforelse
                                        </div>
                                    @endif
                                @endif
                                @error('selectedCustomerId')
                                    <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        @else
                            {{-- Mode: Registrasi Pelanggan Baru --}}
                            <div class="mb-8 p-6 bg-neutral-50 rounded-3xl border border-neutral-100 space-y-4">
                                <p class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-2">
                                    Informasi Tambahan
                                </p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Input Nama --}}
                                    <div class="flex flex-col gap-1">
                                        <label for="name"
                                            class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Nama
                                            Lengkap</label>
                                        <input type="text" id="name" wire:model="name" required
                                            class="w-full px-4 py-3 text-sm bg-white border @error('name') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Masukkan nama lengkap">
                                        @error('name')
                                            <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Input Mobile Phone --}}
                                    <div class="flex flex-col gap-1">
                                        <label for="mobilePhone"
                                            class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Nomor
                                            HP</label>
                                        <input type="tel" id="mobilePhone" wire:model="mobilePhone" required
                                            class="w-full px-4 py-3 text-sm bg-white border @error('mobilePhone') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Contoh: 08123456789">
                                        @error('mobilePhone')
                                            <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Input Email --}}
                                    <div class="flex flex-col gap-1">
                                        <label for="email"
                                            class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Email</label>
                                        <input type="email" id="email" wire:model="email" required
                                            class="w-full px-4 py-3 text-sm bg-white border @error('email') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Contoh: user@email.com">
                                        @error('email')
                                            <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Input Domisili --}}
                                    <div class="flex flex-col gap-1 md:col-span-2">
                                        <label for="domisili"
                                            class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Domisili
                                            (Alamat Singkat)</label>
                                        <input type="text" id="domisili" wire:model="domisili" required
                                            class="w-full px-4 py-3 text-sm bg-white border @error('domisili') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Contoh: Jakarta Selatan">
                                        @error('domisili')
                                            <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($isNewCustomer || $needsBankInfo)
                            @if (!$isNewCustomer && $needsBankInfo)
                                <div
                                    class="p-4 bg-[#D3AD7B]/10 border border-[#D3AD7B]/30 text-[#A28153] rounded-2xl mb-4 text-xs font-bold flex items-start gap-3">
                                    <svg class="w-5 h-5 shrink-0 text-[#A28153]" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        Pelanggan ini belum memiliki data Rekening Bank. Anda wajib melengkapinya untuk
                                        melanjutkan proses pembayaran.
                                    </div>
                                </div>
                            @endif

                            <div class="mb-8 p-6 bg-neutral-50 rounded-3xl border border-neutral-100 space-y-4">
                                <p class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-2">
                                    Informasi Account Transfer User
                                </p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Input Nama --}}
                                    <div class="flex flex-col gap-1">
                                        <label for="bank_name"
                                            class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Nama
                                            Bank</label>
                                        <input type="text" id="bank_name" wire:model="bank_name" required
                                            class="w-full px-4 py-3 text-sm bg-white border @error('bank_name') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Masukkan Nama Bank">
                                        @error('bank_name')
                                            <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Input Mobile Phone --}}
                                    <div class="flex flex-col gap-1">
                                        <label for="account_number"
                                            class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Nomor
                                            Rekening</label>
                                        <input type="number" id="account_number" wire:model="account_number"
                                            required
                                            class="w-full px-4 py-3 text-sm bg-white border @error('account_number') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Contoh: 3121321312312">
                                        @error('account_number')
                                            <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Input Email --}}
                                    <div class="flex flex-col gap-1">
                                        <label for="account_name"
                                            class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Nama
                                            Pemilik Rekening</label>
                                        <input type="text" id="account_name" wire:model="account_name" required
                                            class="w-full px-4 py-3 text-sm bg-white border @error('account_name') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-[#D3AD7B] focus:ring-4 focus:ring-[#D3AD7B]/20 transition-colors"
                                            placeholder="Contoh: user">
                                        @error('account_name')
                                            <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
                <div class="space-y-6 relative">
                    <div class="bg-neutral-50 rounded-3xl p-6">
                        <div class="flex flex-col gap-1 border-b border-neutral-200 pb-5 mb-5">
                            <span class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">Model
                                Perangkat</span>
                            <span class="text-2xl font-bold text-neutral-800">{{ $selected_model_name ?? '-' }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1">
                                <span
                                    class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">Brand</span>
                                <span class="font-bold text-[#A28153]">{{ $selected_brand_id ?? '-' }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span
                                    class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">Kategori
                                    /
                                    Proyek</span>
                                <span class="font-bold text-neutral-800">
                                    {{ $selected_categoryName ?? '-' }} / {{ $selected_proyek ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-[#D3AD7B]/10 rounded-3xl p-5 border border-[#D3AD7B]/30 flex items-center justify-between gap-4">
                        <div class="text-xs">
                            <p class="font-black text-[#A28153]">Estimasi Harga Jual Anda</p>
                            <p class="text-[#A28153]/80 font-medium">Berdasarkan kondisi yang Anda cantumkan.</p>

                        </div>
                        <div class="text-right">
                            {{-- TAMPILAN NORMAL --}}
                            <div class="flex items-center justify-end gap-3">
                                <p class="text-2xl md:text-3xl font-black text-[#A28153]">
                                    Rp <span
                                        x-text="new Intl.NumberFormat('id-ID').format($wire.final_price || 0)"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse md:flex-row justify-between items-center gap-4 pt-2">
                <button type="button" @click="step = 3"
                    class="w-full md:w-auto text-neutral-500 hover:text-neutral-800 font-bold px-6 py-4 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Edit Data
                </button>
                <div class="w-full md:w-auto">
                    <button type="button" wire:click="submit" wire:loading.attr="disabled"
                        class="w-full md:w-auto bg-linear-to-r from-[#D3AD7B] to-[#A28153] hover:from-[#C39D6B] hover:to-[#927143] text-white px-10 py-4 rounded-2xl font-black text-lg transition-all active:scale-[0.97] shadow-xl shadow-[#A28153]/30 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed disabled:pointer-events-none">

                        {{-- Kondisi 1: Teks Normal (Akan hilang/tersembunyi saat loading) --}}
                        <span wire:loading.remove wire:target="submit">
                            Kirim Penawaran Sekarang
                        </span>

                        {{-- Kondisi 2: Konten Loading (Hanya muncul saat method submit berjalan) --}}
                        <span wire:loading.flex wire:target="submit" class="items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Mengirim...
                        </span>

                    </button>
                    <p class="text-center text-[10px] text-neutral-400 mt-3 italic font-medium">
                        Dengan mengirim, Anda setuju dengan proses pengecekan teknis.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
