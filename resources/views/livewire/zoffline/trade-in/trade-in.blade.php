<section class="max-w-7xl mx-auto p-2 md:p-6" x-data="{ step: 1 }" x-cloak>
    {{-- Header Navigation --}}
    <div class="flex gap-2">
        <a href="/"
            class="bg-neutral-500 text-white px-3 flex justify-center items-center rounded-md hover:bg-neutral-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="size-6 md:size-8 rotate-180">
                <path fill-rule="evenodd"
                    d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                    clip-rule="evenodd" />
            </svg>
        </a>
        <div class="w-full flex gap-4 items-center bg-emerald-500 py-3 px-6 rounded-md shadow-sm">
            <img src="{{ asset('assets/png/trade.png') }}" class="w-5 md:w-10 h-auto" alt="Trade In">
            <h1 class="text-white text-xl md:text-4xl font-bold">Trade-in</h1>
        </div>
    </div>
    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-xl font-bold text-sm">
            {{ session('error') }}
        </div>
    @endif
    {{-- Title Section --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 mt-10 gap-4">
        <div>
            <h1 class="text-4xl md:text-6xl font-black tracking-tighter text-neutral-800">
                Trade-In <span class="text-emerald-500">Program</span>
            </h1>
            <p class="text-neutral-500 text-sm md:text-lg mt-2 font-medium">Tukarkan HP lama kamu dengan penawaran harga
                terbaik.
            </p>
        </div>
        <div
            class="bg-emerald-50 text-emerald-700 px-4 py-2 rounded-2xl text-sm font-bold flex items-center h-fit border border-emerald-200 shadow-sm">
            <span class="relative flex h-3 w-3 mr-2">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            Proses Cepat 15 Menit
        </div>
    </div>

    {{-- Stepper Indicator (Visual Progress) --}}
    <div class="mb-14 w-full max-w-7xl mx-auto mt-4">
        <div class="flex justify-between items-start relative"> {{-- Ganti items-center jadi items-start --}}
            <!-- Progress Line Background -->
            {{-- Top kita ubah ke 20px (setengah dari tinggi lingkaran w-10/40px) agar garis pas di tengah lingkaran --}}
            <div
                class="absolute left-0 top-[20px] transform -translate-y-1/2 w-full h-1 bg-neutral-200 rounded-full z-0">
            </div>

            <!-- Progress Line Active -->
            <div class="absolute left-0 top-[20px] transform -translate-y-1/2 h-1 bg-emerald-500 rounded-full z-0 transition-all duration-500 ease-in-out"
                :style="'width: ' + ((step - 1) * 50) + '%'"></div>

            <!-- Step 1 Dot -->
            <div class="relative z-10 flex flex-col items-center cursor-pointer group" @click="step = 1">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 1 ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 ring-4 ring-emerald-50' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    1
                </div>
                {{-- Hilangkan class absolute, gunakan mt-3 --}}
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300"
                    :class="step >= 1 ? 'text-emerald-700' : 'text-neutral-400'">
                    HP Lama
                </span>
            </div>

            <!-- Step 2 Dot -->
            <div class="relative z-10 flex flex-col items-center cursor-pointer group" @click="if(step > 2) step = 2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 2 ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 ring-4 ring-emerald-50' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    2
                </div>
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300"
                    :class="step >= 2 ? 'text-emerald-700' : 'text-neutral-400'">
                    Kondisi
                </span>
            </div>

            <!-- Step 3 Dot -->
            <div class="relative z-10 flex flex-col items-center group">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shrink-0"
                    :class="step >= 3 ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 ring-4 ring-emerald-50' :
                        'bg-white text-neutral-400 border-2 border-neutral-200'">
                    3
                </div>
                <span
                    class="mt-3 text-[10px] md:text-xs font-bold text-center leading-tight transition-colors duration-300"
                    :class="step >= 3 ? 'text-emerald-700' : 'text-neutral-400'">
                    HP Incaran
                </span>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="submit" class="max-w-7xl mx-auto">

        {{-- ==========================================
             STEP 1: Informasi HP Lama
             ========================================== --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            class="space-y-8 mt-4">

            {{-- Brand Selection Cards (with Logo) --}}
            <div>
                <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider mb-4 block">1. Pilih Merk
                    HP Lama</h1>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4" x-data="{ show: false }"
                    x-init="setTimeout(() => show = true, 100)">

                    @foreach ($brands as $brand)
                        <label class="relative cursor-pointer group">
                            <input type="radio" wire:model.live="selected_brand_id" value="{{ $brand->id }}"
                                class="peer hidden">
                            <div
                                class="bg-white h-auto overflow-hidden rounded-2xl text-center transition-all peer-checked:bg-emerald-100 hover:shadow-lg shadow-sm flex items-center justify-center">

                                @php
                                    $baseName =
                                        strtolower($brand->name) === 'apple' ? 'iphone' : strtolower($brand->name);
                                    $imageName = $baseName . 'header';
                                @endphp

                                <img x-show="show" x-cloak
                                    x-transition:enter="transition transform ease-out duration-1000 delay-500"
                                    x-transition:enter-start="opacity-0 translate-y-full"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    src="{{ asset('assets/brand/' . $imageName . '.png') }}" alt="{{ $brand->name }}"
                                    class="object-contain ">

                            </div>
                        </label>
                    @endforeach
                </div>
                @error('selected_brand_id')
                    <span class="text-rose-500 text-xs font-bold block mt-2 ml-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- Detail Inputs (Hanya muncul jika brand sudah dipilih) --}}
            <div x-show="$wire.selected_brand_id" x-cloak x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Model --}}
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">2. Model /
                            Seri</label>
                        <select wire:model.live="selected_model_name"
                            class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-emerald-500 outline-none transition-all font-bold text-neutral-700 appearance-none cursor-pointer">
                            <option value="">Pilih Model HP</option>
                            @foreach ($available_models as $model)
                                <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                        </select>
                        @error('selected_model_name')
                            <span class="text-rose-500 text-xs font-bold ml-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Storage/RAM --}}
                    <div x-show="$wire.selected_model_name" x-cloak class="space-y-2 md:col-span-2">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">Kapasitas (RAM
                            / Storage)</label>
                        <select wire:model.live="buyback_device_id"
                            class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-emerald-500 outline-none transition-all font-bold text-neutral-700 appearance-none cursor-pointer">
                            <option value="">Pilih Kapasitas</option>
                            @foreach ($available_storages as $device)
                                <option value="{{ $device->id }}">
                                    {{ $device->ram ? $device->ram . ' / ' : '' }}{{ $device->storage }}
                                </option>
                            @endforeach
                        </select>
                        @error('buyback_device_id')
                            <span class="text-rose-500 text-xs font-bold ml-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Evaluasi Validasi Step 1 --}}
            @php
                $isStep1Valid = !empty($buyback_device_id);
            @endphp

            <div class="flex justify-end pt-6 pb-10">
                <button type="button" @click="step = 2" {{ $isStep1Valid ? '' : 'disabled' }}
                    class="px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-2 shadow-lg active:scale-95
                    {{ $isStep1Valid ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-900/20' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none' }}">
                    Lanjut Kondisi Fisik
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ==========================================
             STEP 2: Kondisi & Kelengkapan
             ========================================== --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-8 mt-4">

            {{-- Kondisi Fisik & Aturan Pengurangan --}}
            <div>
                <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider mb-4 block">
                    3. Kondisi Fisik
                </h1>

                @if ($buyback_device && count($device_rules) > 0)
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl mb-6">
                        <p class="text-sm font-bold text-emerald-900 mb-2">Harga Dasar (Mulus 100%): Rp
                            {{ number_format($buyback_device->base_price, 0, ',', '.') }}</p>
                        <p class="text-xs text-emerald-700">Silakan centang opsi di bawah ini jika terdapat minus pada
                            perangkat Anda. Harga akan dikalkulasi secara otomatis.</p>
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
                        <p class="text-sm font-medium text-neutral-500">Silakan pilih perangkat pada langkah sebelumnya
                            untuk memuat formulir kondisi.</p>
                    </div>
                @endif
            </div>

            {{-- Catatan --}}
            <div>
                <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider mb-4 block">
                    5. Catatan (Minus dll)
                </h1>
                <textarea wire:model.live="old_phone_additional_note" rows="3"
                    placeholder="Tuliskan jika ada minus atau kendala spesifik..."
                    class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-emerald-500 outline-none transition-all font-medium text-neutral-700"></textarea>
            </div>

            {{-- Upload Foto --}}
            {{-- Upload Foto --}}
            {{-- Container Utama Upload Media --}}
            <div class="space-y-4" x-data="{ activeSlot: null }">
                <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">
                    Upload Foto HP Sesuai Posisi (Maks. 5MB/Foto)
                </h1>

                {{-- Array Helper PHP untuk mapping slot --}}
                @php
                    $slots = [
                        'depan' => 'Tampak Depan',
                        'belakang' => 'Tampak Belakang',
                        'kiri' => 'Samping Kiri',
                        'kanan' => 'Samping Kanan',
                        'kelengkapan' => 'Kelengkapan / Box / Charger',
                    ];
                @endphp

                {{-- Grid Layout Utama untuk Slot (Keterangan di Tengah Kotak) --}}
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach ($slots as $key => $label)
                        @php
                            $propertyName = 'photo_' . $key;
                            $photoFile = $this->{$propertyName};
                            $hasError = $errors->has($propertyName);
                        @endphp

                        {{-- Main Interactive Container --}}
                        <div class="relative aspect-square rounded-3xl overflow-hidden transition-all duration-300 group
                                 {{ $photoFile ? 'border border-neutral-100 shadow-sm' : 'border-2 border-dashed bg-neutral-50/50 hover:bg-neutral-50/100 cursor-pointer' }}
                                {{ $hasError ? 'border-rose-300 bg-rose-50/20' : 'border-neutral-200 hover:border-neutral-300' }}"
                            @if (!$photoFile) @click="activeSlot === '{{ $key }}' ? activeSlot = null : activeSlot = '{{ $key }}'" @endif>

                            @if ($photoFile)
                                {{-- State 1: Image Preview --}}
                                <img src="{{ $photoFile->temporaryUrl() }}"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

                                {{-- Text Overlay di Bagian Bawah Gambar Terunggah --}}
                                <div
                                    class="absolute inset-x-0 bottom-0 bg-black/40 backdrop-blur-xs py-2 px-3 text-center pointer-events-none z-10">
                                    <span class="text-[11px] font-bold text-white tracking-wide block truncate">
                                        {{ $label }}
                                    </span>
                                </div>

                                {{-- Floating Delete Button --}}
                                <button type="button" wire:click="$set('{{ $propertyName }}', null)"
                                    class="absolute top-2 right-2 bg-white/80 hover:bg-white text-neutral-800 p-2 rounded-xl backdrop-blur-md shadow-sm transition hover:scale-105 active:scale-95 z-10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-16v1M4 7h16" />
                                    </svg>
                                </button>
                            @else
                                {{-- State 2: Placeholder Empty (Gambar jadi Background, Teks di Tengah) --}}
                                <div
                                    class="absolute inset-0 flex items-center justify-center p-3 text-center select-none overflow-hidden">

                                    {{-- Layer 1: Kontainer Gambar PNG sebagai "Background" (z-0) --}}
                                    <div
                                        class="absolute inset-0 flex items-center justify-center z-0 group-hover:scale-110 transition-transform duration-300">
                                        {{-- Memanggil gambar sesuai nama $key --}}
                                        {{-- Catatan: Opacity diturunkan (misal: opacity-30) agar teks di atasnya tetap terbaca jelas --}}
                                        <img src="{{ asset('assets/png/' . $key . '.png') }}"
                                            alt="Ilustrasi {{ $label }}"
                                            class="w-50 h-auto object-contain drop-shadow-sm {{ $hasError ? 'opacity-20' : 'opacity-30' }}"
                                            onerror="this.onerror=null; this.src='{{ asset('assets/png/default.png') }}';">
                                    </div>

                                    {{-- Layer 2: Kontainer Teks (z-10 agar berada di atas gambar) --}}
                                    <div class="relative z-10 flex flex-col items-center justify-center">
                                        <h4
                                            class="font-bold text-xs tracking-tight {{ $hasError ? 'text-rose-700' : 'text-neutral-800' }}">
                                            {{ $label }}
                                        </h4>
                                        <p class="text-[10px] text-neutral-500 mt-0.5">Ketuk untuk upload</p>
                                    </div>

                                </div>
                            @endif

                            {{-- Indikator Loading Layer --}}
                            <div wire:loading.flex wire:target="{{ $propertyName }}"
                                class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center gap-1 z-10">
                                <span
                                    class="animate-spin inline-block w-5 h-5 border-2 border-violet-600 border-t-transparent rounded-full"></span>
                                <span
                                    class="text-[9px] font-black text-violet-600 uppercase tracking-widest">Uploading</span>
                            </div>

                            @error('photo_' . $key)
                                <div
                                    class="absolute inset-x-0 bottom-0 bg-rose-500 text-white p-2 text-center z-10 flex flex-col items-center justify-center h-12 transition-all duration-300">
                                    <span class="text-[9px] font-bold uppercase tracking-wider leading-none">Gagal
                                        Upload</span>
                                    <span
                                        class="text-[10px] font-medium block truncate w-full px-1 mt-0.5 leading-tight">{{ $message }}</span>
                                </div>
                            @enderror

                            {{-- POP-UP PILIHAN (Kamera vs Galeri) yang sudah dilengkapi Auto Compress --}}
                            <div x-show="activeSlot === '{{ $key }}'"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
                                x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="transform opacity-0 scale-95 translate-y-2"
                                @click.away="activeSlot = null"
                                class="absolute inset-x-2 bottom-2 bg-white border border-neutral-200 rounded-2xl shadow-xl z-20 p-1.5 space-y-1">

                                {{-- Opsi 1: Kamera --}}
                                <label
                                    class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-emerald-50 text-emerald-900 cursor-pointer transition-colors">
                                    {{-- Menggunakan event JavaScript customCompressHandler --}}
                                    <input type="file" accept="image/*" capture="environment" class="hidden"
                                        @change="customCompressHandler($event, 'photo_{{ $key }}'); activeSlot = null">
                                    <div
                                        class="w-7 h-7 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                            </path>
                                            <circle cx="12" cy="13" r="3" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2"></circle>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold">Kamera</span>
                                </label>

                                {{-- Opsi 2: Galeri --}}
                                <label
                                    class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-violet-50 text-violet-900 cursor-pointer transition-colors">
                                    {{-- Menggunakan event JavaScript customCompressHandler --}}
                                    <input type="file" accept="image/*" class="hidden"
                                        @change="customCompressHandler($event, 'photo_{{ $key }}'); activeSlot = null">
                                    <div
                                        class="w-7 h-7 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12">
                                            </path>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold">Galeri</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Evaluasi Step 2 --}}
            @php
                // 1. Validasi Foto (Semua slot wajib diisi)
                $photoValid =
                    !empty($photo_depan) &&
                    !empty($photo_belakang) &&
                    !empty($photo_kiri) &&
                    !empty($photo_kanan) &&
                    !empty($photo_kelengkapan);

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
                // $isStep2Valid = $photoValid && $rulesValid;
                $isStep2Valid = $rulesValid;
            @endphp

            <div class="flex justify-between items-center pt-6 pb-10">
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
                    {{ $isStep2Valid ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-900/20' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none' }}">
                    Pilih Target
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </div>


        {{-- ==========================================
     STEP 3: Pilih HP Incaran & Submit
     ========================================== --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-8 mt-4">

            {{-- Pemilihan Target --}}
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">7. Pilih HP
                        Incaranmu</h1>
                    <div class="flex bg-neutral-100 p-1 rounded-lg">
                        <button type="button" wire:click="$set('targetType', 'second')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $targetType === 'second' ? 'bg-white text-emerald-600 shadow-sm' : 'text-neutral-500 hover:text-neutral-700' }}">Second</button>
                        <button type="button" wire:click="$set('targetType', 'new')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $targetType === 'new' ? 'bg-white text-emerald-600 shadow-sm' : 'text-neutral-500 hover:text-neutral-700' }}">Baru</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Select Brand Incaran --}}
                    <div class="space-y-2">
                        <label class="text-xs font-black text-neutral-400 uppercase ml-1 tracking-widest">Brand</label>
                        <select wire:model.live="selectedTargetBrand"
                            class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-emerald-500 outline-none transition-all appearance-none font-bold text-neutral-700 cursor-pointer">
                            <option value="">Pilih Brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Select Model Incaran --}}
                    <div class="space-y-2">
                        <label class="text-xs font-black text-neutral-400 uppercase ml-1 tracking-widest">Model
                            HP</label>
                        <select wire:model.live="selectedProductId" @disabled(!$selectedTargetBrand)
                            class="w-full p-4 bg-white shadow-sm border-2 border-transparent rounded-2xl focus:border-emerald-500 outline-none transition-all appearance-none font-bold text-neutral-700 disabled:opacity-50 cursor-pointer">
                            <option value="">Pilih Seri / Model</option>
                            @foreach ($products as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @error('selectedProductId')
                    <span class="text-rose-500 text-xs font-bold block ml-1">{{ $message }}</span>
                @enderror

                @if (!empty($availableTargetVariants))
                    <div class="space-y-4 mt-6">
                        <label class="text-xs font-black text-neutral-400 uppercase ml-1 tracking-widest block">Pilih
                            Varian (Warna & Storage)</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($availableTargetVariants as $variant)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="selectedTargetVariantId"
                                        value="{{ $variant['id'] }}" class="hidden peer">
                                    <div
                                        class="p-4 rounded-xl border-2 transition-all {{ $selectedTargetVariantId == $variant['id'] ? 'border-emerald-500 bg-emerald-50' : 'border-neutral-100 bg-white hover:border-emerald-200' }}">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="font-bold text-neutral-800">{{ $variant['label'] }}</span>
                                            <span
                                                class="text-[10px] font-black uppercase bg-neutral-100 text-neutral-500 px-2 py-0.5 rounded">Stok:
                                                {{ $variant['stock'] }}</span>
                                        </div>
                                        <p class="text-emerald-600 font-bold text-sm">Rp
                                            {{ number_format($variant['price'], 0, ',', '.') }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedTargetVariantId')
                            <span class="text-rose-500 text-xs font-bold block ml-1">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>

            @auth
                @if (auth()->user()->hasRole('fl'))
                    {{-- Data Customer (Khusus FL) --}}
                    <div class="space-y-6 mt-10">
                        <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">Data Customer
                        </h1>
                        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 p-6 space-y-6">

                            {{-- Tabs --}}
                            <div class="flex p-1 bg-neutral-100 rounded-xl w-fit">
                                <button type="button" wire:click="$set('isNewCustomer', true)"
                                    class="px-6 py-2 rounded-lg text-sm font-bold transition-all {{ $isNewCustomer ? 'bg-white text-emerald-600 shadow-sm' : 'text-neutral-500 hover:text-neutral-700' }}">
                                    Pelanggan Baru
                                </button>
                                <button type="button" wire:click="$set('isNewCustomer', false)"
                                    class="px-6 py-2 rounded-lg text-sm font-bold transition-all {{ !$isNewCustomer ? 'bg-white text-emerald-600 shadow-sm' : 'text-neutral-500 hover:text-neutral-700' }}">
                                    Cari Pelanggan Lama
                                </button>
                            </div>

                            @if (!$isNewCustomer)
                                {{-- Mode: Cari Pelanggan Lama --}}
                                <div class="space-y-4">
                                    @if ($selectedCustomerId)
                                        @php $selectedUser = \App\Models\User::find($selectedCustomerId); @endphp
                                        <div
                                            class="p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-between">
                                            <div>
                                                <p
                                                    class="text-xs font-black text-emerald-600 uppercase tracking-wider mb-1">
                                                    Pelanggan Terpilih</p>
                                                <h3 class="font-bold text-neutral-800">{{ $selectedUser->name }}</h3>
                                                <p class="text-sm text-neutral-500">{{ $selectedUser->email }} •
                                                    {{ $selectedUser->profile->phone_number ?? '-' }}</p>
                                            </div>
                                            <button type="button" wire:click="clearSelectedCustomer"
                                                class="text-rose-500 hover:bg-rose-50 p-2 rounded-lg transition-colors font-bold text-sm">
                                                Batal
                                            </button>
                                        </div>
                                        <input type="hidden" wire:model="selectedCustomerId"
                                            value="{{ $selectedCustomerId }}">
                                    @else
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-neutral-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                                                class="w-full pl-10 p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                                placeholder="Cari nama, email, atau no HP...">
                                        </div>

                                        @if (strlen($searchCustomer) >= 2)
                                            <div
                                                class="bg-white border rounded-xl shadow-lg max-h-60 overflow-y-auto divide-y mt-2">
                                                @forelse($this->customerResults as $user)
                                                    <div wire:click="selectCustomer({{ $user->id }})"
                                                        class="p-3 hover:bg-neutral-50 cursor-pointer transition-colors flex justify-between items-center group">
                                                        <div>
                                                            <h4 class="font-bold text-neutral-800">{{ $user->name }}
                                                            </h4>
                                                            <p class="text-xs text-neutral-500">{{ $user->email }} •
                                                                {{ $user->profile->phone_number ?? '-' }}</p>
                                                        </div>
                                                        <span
                                                            class="text-emerald-500 font-bold text-sm opacity-0 group-hover:opacity-100 transition-opacity">Pilih</span>
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
                                        <span class="text-rose-500 text-xs font-bold">{{ $message }}</span>
                                    @enderror
                                </div>
                            @else
                                {{-- Mode: Registrasi Pelanggan Baru --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">Nama Lengkap</label>
                                        <input type="text" wire:model="name"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="Nama Sesuai KTP">
                                        @error('name')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">Nomor HP</label>
                                        <input type="text" wire:model="mobilePhone"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="08xxxxxxxx">
                                        @error('mobilePhone')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">Email</label>
                                        <input type="email" wire:model="email"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="email@contoh.com">
                                        @error('email')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">NIK</label>
                                        <input type="number" wire:model="nik"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="16 Digit NIK">
                                        @error('nik')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">NPWP
                                            (Opsional)</label>
                                        <input type="text" wire:model="npwp"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="Nomor NPWP">
                                        @error('npwp')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">Foto KTP</label>
                                        <input type="file" wire:model="foto_ktp"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            accept="image/*">
                                        @error('foto_ktp')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">Nama Bank</label>
                                        <input type="text" wire:model="bank_name"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="BCA / Mandiri / BNI">
                                        @error('bank_name')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">Nomor Rekening</label>
                                        <input type="number" wire:model="account_number"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="1234567890">
                                        @error('account_number')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-neutral-500 mb-1 block">Nama Pemilik
                                            Rekening</label>
                                        <input type="text" wire:model="account_name"
                                            class="w-full p-3 bg-neutral-50 border rounded-xl focus:border-emerald-500 focus:ring-0 outline-none transition-all"
                                            placeholder="Nama Sesuai Rekening">
                                        @error('account_name')
                                            <span class="text-rose-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endauth

            {{-- Rincian Akhir (Summary) --}}
            <div class="space-y-6 mt-10">
                <div class="flex items-center justify-between ml-1">
                    <h1 class="text-xs font-black text-neutral-500 uppercase tracking-widest">8. Ringkasan Pengajuan
                    </h1>
                    <span
                        class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-0.5 rounded">Final
                        Check</span>
                </div>

                <div class="relative group">
                    {{-- Main Card - Putih Polos --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-11 items-center">

                            {{-- Sisi Kiri: HP Lama --}}
                            <div class="lg:col-span-5 p-8 md:p-10">
                                <div class="flex items-center gap-5">
                                    {{-- Thumbnail Foto Unit dari Pengguna --}}
                                    @if (!empty($photos))
                                        <div
                                            class="w-20 h-20 bg-neutral-50 rounded-2xl p-1.5 border border-neutral-100 shrink-0 flex items-center justify-center overflow-hidden">
                                            {{-- Mengambil foto pertama yang diupload --}}
                                            <img src="{{ $photos[0]->temporaryUrl() }}"
                                                class="w-full h-full object-cover rounded-xl">
                                        </div>
                                    @else
                                        {{-- Fallback jika foto belum terisi/error --}}
                                        <div
                                            class="w-20 h-20 bg-neutral-50 rounded-2xl border border-neutral-100 shrink-0 flex items-center justify-center text-neutral-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 00-2 2z" />
                                            </svg>
                                        </div>
                                    @endif

                                    <div class="space-y-1 flex-1">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[10px] font-black text-neutral-400 uppercase tracking-widest mb-0.5">Unit
                                                Lama Anda</span>
                                            <span
                                                class="text-xs font-black text-emerald-600 uppercase italic tracking-tighter">{{ $buyback_device->brand->name ?? '-' }}</span>
                                        </div>

                                        <h2 class="text-xl md:text-2xl font-bold text-neutral-800 leading-tight">
                                            {{ $buyback_device->model_name ?? '-' }}
                                        </h2>

                                        <p class="text-neutral-400 font-medium text-[10px] uppercase tracking-wide">
                                            {{ $buyback_device ? ($buyback_device->ram ? $buyback_device->ram . ' / ' : '') . $buyback_device->storage : '-' }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="mt-6 bg-emerald-50 rounded-xl p-4 border border-emerald-100 flex items-center justify-between gap-4">
                                    <div class="text-xs">
                                        <p class="font-black text-emerald-900">Estimasi Harga Jual Anda</p>
                                        <p class="text-emerald-700 font-medium">Berdasarkan kondisi yang dicentang.</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xl md:text-2xl font-black text-emerald-600">Rp
                                            {{ number_format($final_price, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tengah: Divider Sederhana --}}
                            <div class="lg:col-span-1 flex lg:flex-col items-center justify-center py-4 lg:py-0">
                                <div class="h-px w-full lg:w-px lg:h-20 bg-neutral-100"></div>
                                <div
                                    class="w-10 h-10 bg-white border border-neutral-100 rounded-full shadow-sm flex items-center justify-center z-10 -mx-5 lg:mx-0 lg:-my-5 text-emerald-500">
                                    <svg class="w-5 h-5 rotate-90 lg:rotate-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                                <div class="h-px w-full lg:w-px lg:h-20 bg-neutral-100"></div>
                            </div>

                            {{-- Sisi Kanan: HP Incaran --}}
                            <div class="lg:col-span-5 p-8 md:p-10">
                                @php
                                    if ($targetType === 'new') {
                                        $target = $selectedProductId
                                            ? \App\Models\Product::with('brand')->find($selectedProductId)
                                            : null;
                                        $targetVariant = $selectedTargetVariantId
                                            ? \App\Models\ProductVariant::find($selectedTargetVariantId)
                                            : null;
                                    } else {
                                        $target = $selectedProductId
                                            ? \App\Models\SecondProduct::with('brand')->find($selectedProductId)
                                            : null;
                                        $targetVariant = $selectedTargetVariantId
                                            ? \App\Models\SecondProductVariant::find($selectedTargetVariantId)
                                            : null;
                                    }
                                @endphp

                                <div class="flex items-center gap-5">
                                    @if ($target && $targetVariant)
                                        {{-- Thumbnail Produk --}}
                                        <div
                                            class="w-20 h-20 bg-neutral-50 rounded-2xl p-2 border border-neutral-100 shrink-0 flex items-center justify-center">
                                            <img src="{{ $target->getFirstMediaUrl('thumbnail_image') ?: $target->getFirstMediaUrl('cover') }}"
                                                class="max-w-full max-h-full object-contain">
                                        </div>
                                        <div class="space-y-1">
                                            <span
                                                class="text-[10px] font-black text-emerald-600 up   case italic tracking-tighter">{{ $target->brand->name ?? '' }}</span>
                                            <h2 class="text-xl md:text-2xl font-bold text-neutral-800 leading-tight">
                                                {{ $target->name }}</h2>
                                            <p class="text-xs font-bold text-neutral-500 uppercase">
                                                {{ $targetVariant->color }} - {{ $targetVariant->storage }}</p>
                                            <p class="text-emerald-500 font-bold text-sm">Rp
                                                {{ number_format($targetVariant->price, 0, ',', '.') }}</p>
                                        </div>
                                    @else
                                        <div
                                            class="w-20 h-20 bg-neutral-50 rounded-2xl border-2 border-dashed border-neutral-100 shrink-0 flex items-center justify-center text-neutral-200">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </div>
                                        <div class="space-y-1 text-neutral-300 italic">
                                            <span
                                                class="text-[10px] font-black uppercase tracking-widest">Incaran</span>
                                            <h2 class="text-xl font-medium">Belum memilih...</h2>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Footer Info - Putih Polos dengan border atas tipis --}}
                        <div class="bg-neutral-50/50 p-6 flex items-start gap-4 border-t border-neutral-100">
                            <svg class="w-5 h-5 text-neutral-400 shrink-0 mt-0.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-left">
                                <p class="text-[11px] md:text-xs text-neutral-500 leading-relaxed font-medium">
                                    Taksiran harga di atas adalah <span
                                        class="text-neutral-800 font-bold underline decoration-emerald-200 underline-offset-4">estimasi
                                        awal</span>. Finalisasi harga akan dilakukan setelah tim admin melakukan
                                    inspeksi fisik terhadap unit lama yang kami terima.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tombol Navigasi --}}
            @php $isStep3Valid = $selectedTargetBrand && $selectedProductId; @endphp
            <div class="flex justify-between items-center pt-6 pb-12">
                <button type="button" @click="step = 2"
                    class="text-neutral-500 hover:text-neutral-800 font-bold px-6 py-4 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali
                </button>
                <button type="button" wire:click="submit" wire:loading.attr="disabled"
                    {{ $isStep3Valid ? '' : 'disabled' }}
                    class="px-10 py-4 rounded-2xl font-black transition-all flex items-center gap-3 shadow-lg active:scale-95 group
            {{ $isStep3Valid ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-900/20' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none' }}">
                    <span wire:loading.remove>Kirim Pengajuan</span>
                    <span wire:loading>Sedang Mengirim...</span>
                    <svg wire:loading.remove class="w-5 h-5 group-hover:translate-x-1 transition-transform"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>
            </div>
        </div>
    </form>
</section>
