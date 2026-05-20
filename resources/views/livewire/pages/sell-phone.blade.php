<div class="max-w-7xl mx-auto p-2  md:p-6 min-h-screen" x-data="{ step: 1 }" x-cloak>
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
        <div class="w-full flex gap-4 items-center bg-violet-600 py-3 px-6 rounded-md shadow-sm">
            <img src="{{ asset('assets/png/sell.png') }}" class="w-5 md:w-10 h-auto" alt="">
            <h1 class="text-white text-xl md:text-4xl font-bold">Sell Phones</h1>
        </div>
    </div>

    {{-- Title Section --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 mt-10 text-left">
        <div class="space-y-2 mx-auto md:mx-0">
            <h1 class="text-3xl md:text-5xl font-black text-neutral-900 tracking-tight">
                Jual HP <span class="text-violet-600">Instan.</span>
            </h1>
            <p class="text-neutral-500 text-sm md:text-base  font-medium max-w-md">Ubah gadget lamamu menjadi uang tunai
                dengan proses yang
                cepat dan transparan.</p>
        </div>
    </div>

    {{-- Stepper Indicator (Visual Progress) - Violet Version --}}
    <div class="mb-14 w-full max-w-7xl mx-auto  mt-4">
        <div class="flex justify-between items-start relative">
            <!-- Progress Line Background -->
            {{-- Posisi top disetel 20px agar tepat memotong tengah lingkaran --}}
            <div
                class="absolute left-0 top-[20px] transform -translate-y-1/2 w-full h-1 bg-neutral-200 rounded-full z-0">
            </div>

            <!-- Progress Line Active -->
            <div class="absolute left-0 top-[20px] transform -translate-y-1/2 h-1 bg-violet-600 rounded-full z-0 transition-all duration-500 ease-in-out"
                :style="'width: ' + ((step - 1) * 50) + '%'"></div>

            <!-- Step 1 Dot -->
            <div class="relative z-10 flex flex-col items-center cursor-pointer group" @click="step = 1">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 1 ? 'bg-violet-600 text-white shadow-lg shadow-violet-200 ring-4 ring-violet-50' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    1
                </div>
                {{-- Teks sekarang fleksibel dan wrap otomatis --}}
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300 w-auto"
                    :class="step >= 1 ? 'text-violet-700' : 'text-neutral-400'">
                    Spesifikasi
                </span>
            </div>

            <!-- Step 2 Dot -->
            <div class="relative z-10 flex flex-col items-center cursor-pointer group" @click="if(step > 2) step = 2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 2 ? 'bg-violet-600 text-white shadow-lg shadow-violet-200 ring-4 ring-violet-50' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    2
                </div>
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300 w-auto"
                    :class="step >= 2 ? 'text-violet-700' : 'text-neutral-400'">
                    Kondisi
                </span>
            </div>

            <!-- Step 3 Dot -->
            <div class="relative z-10 flex flex-col items-center group">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 3 ? 'bg-violet-600 text-white shadow-lg shadow-violet-200 ring-4 ring-violet-50' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    3
                </div>
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300 w-sauto"
                    :class="step >= 3 ? 'text-violet-700' : 'text-neutral-400'">
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

            {{-- Brand Selection Cards (with Logo) --}}
            <div>
                <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider mb-4 block">Pilih Merk
                    Perangkat</h1>
                {{-- Menggunakan grid 3 kolom di mobile, dan 4/5 kolom di layar besar agar card logonya pas --}}
                {{-- 1. x-data dipindah ke pembungkus utama agar animasinya barengan --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4" x-data="{ show: false }"
                    x-init="setTimeout(() => show = true, 100)">

                    @foreach ($brands as $brand)
                        <label wire:key="brand-{{ $brand->id }}" class="relative cursor-pointer group">
                            <input type="radio" wire:model.live="selected_brand_id" value="{{ $brand->id }}"
                                class="peer hidden">
                            <div
                                class="bg-white h-auto overflow-hidden rounded-lg text-center transition-all peer-checked:bg-violet-100 hover:shadow-lg shadow-md flex items-center justify-center">

                                @php
                                    $baseName =
                                        strtolower($brand->name) === 'apple' ? 'iphone' : strtolower($brand->name);
                                    $imageName = $baseName . 'header';
                                @endphp

                                <img x-show="show" x-cloak
                                    x-transition:enter="transition transform ease-out duration-1000 delay-500"
                                    x-transition:enter-start="opacity-0 translate-y-full"
                                    x-transition:enter-end="opacity-100 "
                                    src="{{ asset('assets/brand/' . $imageName . '.png') }}" alt="{{ $brand->name }}"
                                    class="object-contain">

                            </div>
                        </label>
                    @endforeach
                </div>
                @error('selected_brand_id')
                    <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- Detail Inputs (Hanya muncul jika brand sudah dipilih) --}}
            <div x-show="$wire.selected_brand_id" x-cloak x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">Model /
                            Seri</label>

                        <!-- Input Combobox dengan Dropdown bergaya Select (Alpine.js) -->
                        <div x-data="{
                            open: false,
                            search: $wire.entangle('selected_model_name').live,
                            models: @js($available_models),
                            get filteredModels() {
                                if (!this.search) return this.models;
                                return this.models.filter(m => m.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            selectModel(model) {
                                this.search = model;
                                this.open = false;
                            }
                        }" wire:key="model-combo-{{ $selected_brand_id ?? 'new' }}"
                            @click.away="open = false" class="relative w-full">

                            <!-- Input Pencarian (Langsung di input field utama) -->
                            <div class="relative w-full">
                                <input type="text" x-model="search" @focus="open = true" @click="open = true"
                                    placeholder="Ketik atau pilih Model HP..." autocomplete="off"
                                    class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-violet-500 outline-none transition-all font-bold text-neutral-700 cursor-text pr-12">

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
                                    <button @click="selectModel(model)" type="button"
                                        class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-colors"
                                        :class="model === search ? 'bg-violet-100 text-violet-700' :
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

                    <div x-show="$wire.selected_model_name" x-cloak class="space-y-2 md:col-span-2">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">Kapasitas (RAM
                            / Storage)</label>
                        <select wire:model.live="buyback_device_id"
                            class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-violet-500 outline-none transition-all font-bold text-neutral-700 appearance-none cursor-pointer">
                            <option value="">Pilih Kapasitas</option>
                            @foreach ($available_storages as $device)
                                <option value="{{ $device->id }}">
                                    {{ $device->ram ? $device->ram . ' / ' : '' }}{{ $device->storage }}
                                </option>
                            @endforeach
                        </select>
                        @error('buyback_device_id')
                            <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Evaluasi Validasi Step 1 --}}
            @php
                $isStep1Valid = !empty($buyback_device_id);
            @endphp
            <div class="flex justify-end pt-4 pb-10">
                <button type="button" @click="step = 2" {{-- Tambahkan attribute disabled agar benar-benar tidak bisa diklik --}} {{ $isStep1Valid ? '' : 'disabled' }}
                    class="px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-2 shadow-lg active:scale-95
                    {{ $isStep1Valid ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-violet-900/20' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none' }}">
                    Lanjut Kondisi Fisik
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- STEP 2: Condition & Photos --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-8">

            <h3 class="text-lg md:text-xl uppercase font-black text-neutral-800 px-1">Kondisi & Kelengkapan</h3>

            <div class="space-y-8">
                <div class="space-y-8">

                    @if ($buyback_device && count($device_rules) > 0)
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-6">
                            <p class="text-sm font-bold text-blue-900 mb-2">Harga Dasar (Mulus 100%): Rp
                                {{ number_format($buyback_device->base_price, 0, ',', '.') }}</p>
                            <p class="text-xs text-blue-700">Silakan centang opsi di bawah ini jika terdapat minus pada
                                perangkat Anda. Harga akan dikalkulasi secara otomatis.</p>
                        </div>

                        @php
                            $groupedRules = collect($device_rules)->groupBy('category');
                        @endphp

                        @foreach ($groupedRules as $category => $rules)
                            <div class="space-y-3 mb-6">
                                <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">
                                    {{ $category }}
                                </h1>

                                <div class="flex flex-wrap gap-3">
                                    @foreach ($rules as $rule)
                                        <label class="cursor-pointer block ">
                                            {{-- LOGIKA PEMISAHAN: Jika kategori 'kelengkapan' pakai checkbox, jika tidak pakai radio --}}
                                            @if (str_contains(strtolower($category), 'kelengkapan'))
                                                <input type="checkbox"
                                                    wire:model.live="selected_rules.{{ $rule['key'] }}"
                                                    class="peer hidden">
                                            @else
                                                {{-- Untuk Radio, wire:model harus diarahkan ke property yang sama per kategori --}}
                                                {{-- Contoh: selected_rules.layar atau selected_rules.fisik --}}
                                                <input type="radio" name="{{ $category }}"
                                                    value="{{ $rule['key'] }}"
                                                    wire:model.live="selected_rules.{{ $category }}"
                                                    class="peer hidden">
                                            @endif

                                            <div
                                                class="py-2 px-4 bg-white shadow-sm border-2 border-transparent rounded-xl text-center text-sm font-bold text-neutral-600 transition-all peer-checked:border-violet-600 peer-checked:bg-violet-50 peer-checked:text-violet-700 hover:border-violet-200 flex items-center justify-center ">
                                                {{ $rule['name'] }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="p-6 bg-neutral-50 rounded-2xl text-center border border-neutral-200">
                            <p class="text-sm font-medium text-neutral-500">Silakan pilih perangkat pada langkah
                                sebelumnya untuk memuat formulir kondisi.</p>
                        </div>
                    @endif

                    {{-- Catatan Tambahan --}}
                    <div class="space-y-3">
                        <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">Catatan
                            Tambahan (Minus dll)</h1>
                        <textarea wire:model.live="old_phone_additional_note" rows="3"
                            placeholder="Jelaskan kondisi detail jika ada minus..."
                            class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-violet-500 outline-none transition-all font-medium text-neutral-700"></textarea>
                    </div>

                    {{-- Upload Foto --}}
                    {{-- Container Utama Upload Media --}}
                    <div class="space-y-3">
                        <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">
                            Upload Foto HP (Maks. 5MB/Foto)
                        </h1>

                        {{-- Grid 2 Pilihan: Kamera Langsung & Galeri --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            {{-- OPSI 1: AMBIL FOTO LANGSUNG (KAMERA) --}}
                            <div class="relative group">
                                {{-- 
                Tanpa 'multiple' karena kamera HP hanya bisa menjepret 1 foto per klik.
                Ditambah 'capture="environment"' agar memaksa HP langsung membuka kamera belakang.
                Logika array_merge di backend yang akan menyatukannya sampai 5 foto.
            --}}
                                <input type="file" wire:model.live="photos" accept="image/*"
                                    capture="environment"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                <div
                                    class="w-full p-6 border-2 border-dashed border-emerald-200 rounded-3xl bg-white shadow-sm hover:bg-emerald-50 transition-colors flex flex-col items-center justify-center text-center h-full min-h-[150px]">
                                    <div
                                        class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                        {{-- Icon Kamera --}}
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                            </path>
                                            <circle cx="12" cy="13" r="3" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2"></circle>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-emerald-900 text-sm">Ambil Foto Langsung</p>
                                    <p class="text-xs text-emerald-600/70 mt-1">Buka kamera (bisa jepret bergantian
                                        sampai 5x)</p>
                                </div>
                            </div>

                            {{-- OPSI 2: UPLOAD DARI GALERI --}}
                            <div class="relative group">
                                {{-- Menggunakan 'multiple' dan TANPA 'capture' agar bisa pilih banyak langsung dari galeri --}}
                                <input type="file" wire:model.live="photos" multiple accept="image/*"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                <div
                                    class="w-full p-6 border-2 border-dashed border-violet-200 rounded-3xl bg-white shadow-sm hover:bg-violet-50 transition-colors flex flex-col items-center justify-center text-center h-full min-h-[160px]">
                                    <div
                                        class="w-12 h-12 bg-violet-100 text-violet-600 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                        {{-- Icon Upload/Galeri --}}
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12">
                                            </path>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-violet-900 text-sm">Pilih dari Galeri</p>
                                    <p class="text-xs text-violet-600/70 mt-1">Pilih sekaligus banyak (Maksimal 5 foto)
                                    </p>
                                </div>
                            </div>

                        </div>

                        {{-- Error handling global untuk properti $photos --}}
                        @error('photos')
                            <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span>
                        @enderror

                        {{-- Indikator Loading upload file sementara --}}
                        <div wire:loading wire:target="photos"
                            class="text-xs font-bold text-violet-600 mt-2 flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-violet-600" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Sedang mengunggah...
                        </div>

                        {{-- Grid Preview Foto-Foto yang Sudah Masuk --}}
                        @if ($photos)
                            <div class="grid grid-cols-3 md:grid-cols-4 gap-3 mt-4">
                                @foreach ($photos as $photo)
                                    <div
                                        class="relative aspect-square rounded-2xl overflow-hidden border border-neutral-200 shadow-sm">
                                        <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Error handling untuk validasi per file di dalam array --}}
                        @error('photos.*')
                            <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Evaluasi Validasi Step 2 --}}
                @php
                    // 1. Validasi Foto (Minimal 1)
                    $photoValid = !empty($photos) && count($photos) >= 1;

                    // 2. Validasi Inputan Radio/Kondisi
                    $rulesValid = false;
                    if ($buyback_device && count($device_rules) > 0) {
                        // Ambil semua nama kategori unik yang BUKAN kelengkapan (karena non-kelengkapan menggunakan sistem Radio)
                        $requiredCategories = collect($device_rules)
                            ->filter(function ($rule) {
                                return !str_contains(strtolower($rule['category']), 'kelengkapan');
                            })
                            ->pluck('category')
                            ->unique();

                        // Hitung berapa banyak kategori wajib yang sudah dipilih oleh user di komponen Livewire
                        $filledCategoriesCount = collect($selected_rules)
                            ->filter(function ($value, $key) use ($requiredCategories) {
                                // Memastikan key yang diisi ada di daftar kategori wajib dan nilainya tidak kosong
                                return $requiredCategories->contains($key) && !empty($value);
                            })
                            ->count();

                        // Dianggap valid jika jumlah yang diisi sama dengan jumlah kategori yang diwajibkan
                        $rulesValid = $filledCategoriesCount === $requiredCategories->count();
                    }

                    // Gabungkan semua kondisi: Foto harus valid DAN semua radio kondisi harus sudah dipilih
                    $isStep2Valid = $photoValid && $rulesValid;
                @endphp

                <div class="flex justify-between items-center pt-4 pb-10">
                    <button type="button" @click="step = 1"
                        class="text-neutral-500 hover:text-neutral-800 font-bold px-6 py-4 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </button>
                    <button type="button" @click="step = 3" {{ $isStep2Valid ? '' : 'disabled' }}
                        class="px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-2 shadow-lg active:scale-95
                    {{ $isStep2Valid ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-violet-900/20' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none' }}">
                        Cek Ringkasan
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
        {{-- STEP 3: Summary & Submit --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-6">
            <div
                class="bg-white text-neutral-900 rounded-[2.5rem] p-8 md:p-10 shadow-sm border border-neutral-100 relative overflow-hidden">
                <h4
                    class="text-violet-500 font-bold uppercase tracking-widest text-xs mb-6 relative z-10 flex items-center gap-2">
                    Ringkasan Unit Anda
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                </h4>
                {{-- Form Input Khusus Role FL --}}
                @if (Auth::user() && Auth::user()->hasRole('fl'))
                    <div class="mb-8 p-6 bg-neutral-50 rounded-3xl border border-neutral-100 space-y-4">
                        <p class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-2">
                            Informasi Tambahan (FL)
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Input Nama --}}
                            <div class="flex flex-col gap-1">
                                <label for="name"
                                    class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Nama
                                    Lengkap</label>
                                <input type="text" id="name" wire:model="name" required
                                    class="w-full px-4 py-3 text-sm bg-white border @error('name') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
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
                                    class="w-full px-4 py-3 text-sm bg-white border @error('mobilePhone') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
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
                                    class="w-full px-4 py-3 text-sm bg-white border @error('email') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
                                    placeholder="Contoh: user@email.com">
                                @error('email')
                                    <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Input NIK --}}
                            <div class="flex flex-col gap-1">
                                <label for="nik"
                                    class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">NIK
                                    (KTP)</label>
                                <input type="text" id="nik" wire:model="nik" required maxlength="16"
                                    class="w-full px-4 py-3 text-sm bg-white border @error('nik') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
                                    placeholder="16 digit nomor NIK">
                                @error('nik')
                                    <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Input NPWP --}}
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label for="npwp"
                                    class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">NPWP
                                    (Opsional)</label>
                                <input type="text" id="npwp" wire:model="npwp"
                                    class="w-full px-4 py-3 text-sm bg-white border @error('npwp') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
                                    placeholder="Masukkan nomor NPWP">
                                @error('npwp')
                                    <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Upload Foto KTP --}}
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Upload
                                    Foto KTP</label>
                                <div class="relative flex items-center justify-center w-full">
                                    <label for="foto_ktp"
                                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer bg-white hover:bg-neutral-50 transition-colors @error('foto_ktp') border-red-400 @else border-neutral-200 @enderror">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">

                                            {{-- Indikator Loading upload file --}}
                                            <div wire:loading wire:target="foto_ktp"
                                                class="text-xs text-violet-600 font-bold mb-2 animate-pulse">
                                                Memproses foto KTP...
                                            </div>

                                            <div wire:loading.remove wire:target="foto_ktp"
                                                class="flex flex-col items-center justify-center">
                                                @if ($foto_ktp)
                                                    <p
                                                        class="mb-1 text-xs text-emerald-600 font-bold flex items-center gap-1">
                                                        ✓ KTP Berhasil Dimuat
                                                    </p>
                                                    <p class="text-[10px] text-neutral-500">
                                                        {{ $foto_ktp->getClientOriginalName() }}</p>
                                                @else
                                                    <svg class="w-8 h-8 mb-2 text-neutral-400" aria-hidden="true"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 20 16">
                                                        <path stroke="currentColor" stroke-linecap="round"
                                                            stroke-linejoin="round" stroke-width="2"
                                                            d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                                    </svg>
                                                    <p class="mb-1 text-xs text-neutral-500 font-medium">Klik untuk
                                                        upload foto KTP</p>
                                                    <p class="text-[10px] text-neutral-400 uppercase">PNG, JPG, JPEG
                                                        (Max. 2MB)</p>
                                                @endif
                                            </div>
                                        </div>
                                        {{-- DISESUAIKAN: wire:model diganti ke foto_ktp agar sinkron dengan Class --}}
                                        <input id="foto_ktp" wire:model="foto_ktp" type="file" accept="image/*"
                                            required class="hidden" />
                                    </label>
                                </div>
                                @error('foto_ktp')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-8 p-6 bg-neutral-50 rounded-3xl border border-neutral-100 space-y-4">
                        <p class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-2">
                            Informasi Account Transfer User (FL)
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Input Nama --}}
                            <div class="flex flex-col gap-1">
                                <label for="bank_name"
                                    class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Nama
                                    Bank</label>
                                <input type="text" id="bank_name" wire:model="bank_name" required
                                    class="w-full px-4 py-3 text-sm bg-white border @error('bank_name') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
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
                                <input type="number" id="account_number" wire:model="account_number" required
                                    class="w-full px-4 py-3 text-sm bg-white border @error('account_number') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
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
                                    class="w-full px-4 py-3 text-sm bg-white border @error('account_name') border-red-500 @else border-neutral-200 @enderror rounded-xl focus:outline-none focus:border-violet-500 transition-colors"
                                    placeholder="Contoh: user">
                                @error('account_name')
                                    <span class="text-red-500 text-xs mt-0.5">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif
                <div class="space-y-6 relative">
                    <div class="bg-neutral-50 rounded-3xl p-6">
                        <div class="flex flex-col gap-1 border-b border-neutral-200 pb-5 mb-5">
                            <span class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">Model
                                Perangkat</span>
                            <span
                                class="text-2xl font-bold text-neutral-800">{{ $buyback_device->model_name ?? '-' }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1">
                                <span
                                    class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">Brand</span>
                                <span
                                    class="font-bold text-violet-600">{{ $buyback_device->brand->name ?? '-' }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span
                                    class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">Kapasitas</span>
                                <span class="font-bold text-neutral-800">
                                    {{ $buyback_device ? ($buyback_device->ram ? $buyback_device->ram . ' / ' : '') . $buyback_device->storage : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-emerald-50 rounded-3xl p-5 border border-emerald-100 flex items-center justify-between gap-4">
                        <div class="text-xs">
                            <p class="font-black text-emerald-900">Estimasi Harga Jual Anda</p>
                            <p class="text-emerald-700 font-medium">Berdasarkan kondisi yang Anda cantumkan.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl md:text-3xl font-black text-emerald-600">Rp
                                {{ number_format($final_price, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse md:flex-row justify-between items-center gap-4 pt-2">
                <button type="button" @click="step = 2"
                    class="w-full md:w-auto text-neutral-500 hover:text-neutral-800 font-bold px-6 py-4 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Edit Data
                </button>
                <div class="w-full md:w-auto">
                    <button type="button" wire:click="submit" wire:loading.attr="disabled"
                        class="w-full md:w-auto bg-violet-600 hover:bg-violet-700 text-white px-10 py-4 rounded-2xl font-black text-lg transition-all active:scale-[0.97] shadow-xl shadow-violet-900/20 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed disabled:pointer-events-none">

                        {{-- Kondisi 1: Teks Normal (Akan hilang/tersembunyi saat loading) --}}
                        <span wire:loading.remove wire:target="submit">
                            Kirim Penawaran Sekarang
                        </span>

                        {{-- Kondisi 2: Konten Loading (Hanya muncul saat method submit berjalan) --}}
                        {{-- Ganti baris pembuka span menjadi wire:loading.flex --}}
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
