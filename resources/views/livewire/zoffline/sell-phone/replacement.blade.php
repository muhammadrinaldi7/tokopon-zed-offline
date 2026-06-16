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

            <div x-data="{ qcStep: 0, maxQcStep: {{ $maxQcStep }} }" class="bg-white p-4 md:p-6 rounded-2xl shadow-sm border border-neutral-100 relative">
                
                {{-- Progress Bar QC --}}
                <div class="mb-6 relative">
                    <div class="h-2 bg-neutral-100 rounded-full overflow-hidden">
                        <div class="h-full bg-violet-500 transition-all duration-300" :style="'width: ' + ((qcStep / maxQcStep) * 100) + '%'"></div>
                    </div>
                    <div class="mt-2 text-xs font-bold text-neutral-500 text-right">Tahap <span x-text="qcStep"></span> dari <span x-text="maxQcStep"></span></div>
                </div>

                {{-- QC SUB-STEP 0: Foto Produk & IMEI --}}
                <div x-show="qcStep === 0" x-transition.opacity class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">IMEI Perangkat <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model.live.debounce.500ms="imei"
                            placeholder="Scan atau ketik IMEI..."
                            class="w-full p-4 bg-gray-50 shadow-sm border-2 border-transparent rounded-2xl focus:border-violet-500 outline-none transition-all font-bold text-neutral-700">
                        @error('imei') <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-4" x-data="{ activeSlot: null }">
                        <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">
                            Ambil Foto HP Live (Maks. 5MB/Foto)
                        </h1>
                        <p class="text-xs text-neutral-400 -mt-2 mb-4 ml-1">Semua foto harus diambil langsung dari kamera saat ini.</p>

                        @php
                            $slots = [
                                'depan' => 'Tampak Depan',
                                'belakang' => 'Tampak Belakang',
                                'kiri' => 'Samping Kiri',
                                'kanan' => 'Samping Kanan',
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

                                <div class="relative aspect-square rounded-3xl overflow-hidden transition-all duration-300 group
                                 {{ $photoFile ? 'border border-neutral-100 shadow-sm' : 'border-2 border-dashed bg-neutral-50/50 hover:bg-neutral-50/100 cursor-pointer' }}
                                {{ $hasError ? 'border-rose-300 bg-rose-50/20' : 'border-neutral-200 hover:border-neutral-300' }}">

                                    @if ($photoFile)
                                        <img src="{{ $photoFile->temporaryUrl() }}"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                        <div class="absolute inset-x-0 bottom-0 bg-black/40 backdrop-blur-xs py-2 px-3 text-center pointer-events-none z-10">
                                            <span class="text-[11px] font-bold text-white tracking-wide block truncate">{{ $label }}</span>
                                        </div>
                                        <button type="button" wire:click="$set('{{ $propertyName }}', null)"
                                            class="absolute top-2 right-2 bg-white/80 hover:bg-white text-neutral-800 p-2 rounded-xl backdrop-blur-md shadow-sm transition hover:scale-105 active:scale-95 z-10 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-16v1M4 7h16" /></svg>
                                        </button>
                                    @else
                                        {{-- CAMERA ONLY UPLOAD --}}
                                        <label class="absolute inset-0 flex flex-col items-center justify-center p-3 text-center select-none overflow-hidden cursor-pointer z-10">
                                            <input type="file" accept="image/*" capture="environment" class="hidden" @change="customCompressHandler($event, 'photo_{{ $key }}')">
                                            <div class="absolute inset-0 flex items-center justify-center z-0 group-hover:scale-110 transition-transform duration-300">
                                                <img src="{{ asset('assets/png/' . $key . '.png') }}" alt="{{ $label }}" class="w-50 h-auto object-contain drop-shadow-sm {{ $hasError ? 'opacity-20' : 'opacity-30' }}" onerror="this.onerror=null; this.src='{{ asset('assets/png/default.png') }}';">
                                            </div>
                                            <div class="relative z-10 flex flex-col items-center justify-center">
                                                <h4 class="font-bold text-xs tracking-tight {{ $hasError ? 'text-rose-700' : 'text-neutral-800' }}">{{ $label }}</h4>
                                                <div class="mt-1 bg-white/80 backdrop-blur-sm rounded-full p-1.5 shadow-sm">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><circle cx="12" cy="13" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></circle></svg>
                                                </div>
                                            </div>
                                        </label>
                                    @endif

                                    <div wire:loading.flex wire:target="{{ $propertyName }}"
                                        class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center gap-1 z-10">
                                        <span class="animate-spin w-5 h-5 border-2 border-violet-600 border-t-transparent rounded-full"></span>
                                    </div>
                                    @error('photo_' . $key)
                                        <div class="absolute inset-x-0 bottom-0 bg-rose-500 text-white p-2 text-center z-10 flex flex-col items-center justify-center h-12">
                                            <span class="text-[9px] font-bold uppercase">{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- QC SUB-STEPS (Categories) --}}
                @foreach($categories as $index => $categoryName)
                    <div x-show="qcStep === {{ $index + 1 }}" x-transition.opacity style="display: none;" class="space-y-6">
                        <h4 class="text-lg font-black text-violet-700 uppercase tracking-wider border-b border-neutral-100 pb-2">
                            Pengecekan {{ $categoryName }}
                        </h4>
                        
                        <div class="space-y-4">
                            @foreach($qc_results as $i => $item)
                                @if($item['category'] === $categoryName)
                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <span class="text-sm font-bold text-neutral-700">{{ $item['name'] }}</span>
                                            
                                            @if($item['type'] === 'boolean')
                                                <div class="flex items-center gap-2">
                                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                                        <input type="radio" wire:model.live="qc_results.{{ $i }}.value" value="1" class="peer hidden">
                                                        <div class="px-4 py-2 rounded-lg text-xs font-bold border transition-all
                                                            peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500
                                                            text-neutral-400 border-neutral-200 bg-white hover:bg-neutral-50 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                            Berfungsi Normal
                                                        </div>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                                        <input type="radio" wire:model.live="qc_results.{{ $i }}.value" value="0" class="peer hidden">
                                                        <div class="px-4 py-2 rounded-lg text-xs font-bold border transition-all
                                                            peer-checked:bg-rose-500 peer-checked:text-white peer-checked:border-rose-500
                                                            text-neutral-400 border-neutral-200 bg-white hover:bg-neutral-50 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            Bermasalah
                                                        </div>
                                                    </label>
                                                </div>
                                            @else
                                                <input type="text" wire:model.lazy="qc_results.{{ $i }}.value"
                                                    class="p-2 text-sm border border-gray-200 rounded-lg focus:ring-violet-500 focus:border-violet-500 bg-white"
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
                <div x-show="qcStep === maxQcStep" x-transition.opacity style="display: none;" class="space-y-6">
                    <h4 class="text-lg font-black text-violet-700 uppercase tracking-wider border-b border-neutral-100 pb-2">Kesimpulan Kelayakan</h4>
                    <p class="text-sm text-neutral-500">Berdasarkan inspeksi yang Anda lakukan, apakah perangkat ini layak untuk dibeli?</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="cursor-pointer group">
                            <input type="radio" wire:model.live="qc_verdict" value="pass" class="peer hidden">
                            <div class="h-full flex flex-col items-center justify-center p-6 border-2 border-neutral-200 rounded-2xl transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:bg-neutral-50">
                                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <h5 class="font-bold text-emerald-700 text-center">Layak Beli</h5>
                                <p class="text-xs text-center text-neutral-500 mt-1">Lanjut ke tahap penilaian harga</p>
                            </div>
                        </label>
                        <label class="cursor-pointer group">
                            <input type="radio" wire:model.live="qc_verdict" value="conditional" class="peer hidden">
                            <div class="h-full flex flex-col items-center justify-center p-6 border-2 border-neutral-200 rounded-2xl transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 hover:bg-neutral-50">
                                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <h5 class="font-bold text-amber-700 text-center">Perlu Perbaikan</h5>
                                <p class="text-xs text-center text-neutral-500 mt-1">Lanjut dengan catatan minus berat</p>
                            </div>
                        </label>
                        <label class="cursor-pointer group">
                            <input type="radio" wire:model.live="qc_verdict" value="fail" class="peer hidden">
                            <div class="h-full flex flex-col items-center justify-center p-6 border-2 border-neutral-200 rounded-2xl transition-all peer-checked:border-rose-500 peer-checked:bg-rose-50 hover:bg-neutral-50">
                                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </div>
                                <h5 class="font-bold text-rose-700 text-center">Tidak Layak</h5>
                                <p class="text-xs text-center text-neutral-500 mt-1">Batalkan & hentikan transaksi</p>
                            </div>
                        </label>
                    </div>

                    <div class="space-y-2 mt-6 border-t border-neutral-100 pt-6">
                        <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">Catatan Akhir QC (Opsional)</label>
                        <textarea wire:model.live.debounce.500ms="qc_notes" rows="3"
                            placeholder="Tulis ringkasan hasil inspeksi jika perlu..."
                            class="w-full p-4 bg-gray-50 shadow-sm border-2 border-transparent rounded-2xl focus:border-violet-500 outline-none transition-all text-sm font-medium text-neutral-700"></textarea>
                    </div>
                </div>

                {{-- Wizard Navigation Buttons --}}
                @php
                    // Validasi foto dan imei untuk sub-step 0
                    $isPhotoValid = !empty($photo_depan) && !empty($photo_belakang) && !empty($photo_kiri) && !empty($photo_kanan) && !empty($photo_kelengkapan);
                    $isStep0Valid = !empty($imei) && $isPhotoValid;
                @endphp
                <div class="flex flex-col md:flex-row justify-between items-center mt-8 pt-4 border-t border-neutral-100 gap-4">
                    <div class="w-full md:w-auto flex justify-start">
                        <button x-show="qcStep > 0" type="button" @click="qcStep--"
                            class="text-neutral-500 hover:text-neutral-800 font-bold px-4 py-3 transition-colors flex items-center gap-2 border md:border-none border-neutral-200 rounded-xl md:rounded-none w-full md:w-auto justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Mundur
                        </button>
                        <button x-show="qcStep === 0" type="button" @click="step = 1"
                            class="text-neutral-500 hover:text-neutral-800 font-bold px-4 py-3 transition-colors flex items-center gap-2 border md:border-none border-neutral-200 rounded-xl md:rounded-none w-full md:w-auto justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Kembali ke Specs
                        </button>
                    </div>

                    <div class="w-full md:w-auto flex justify-end">
                        <button x-show="qcStep === 0" type="button" @click="qcStep++" {{ $isStep0Valid ? '' : 'disabled' }}
                            class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full md:w-auto
                            {{ $isStep0Valid ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-md shadow-violet-200' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed' }}">
                            Mulai Ceklis
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>

                        <button x-show="qcStep > 0 && qcStep < maxQcStep" type="button" @click="qcStep++"
                            class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white shadow-md shadow-violet-200 w-full md:w-auto">
                            Lanjut
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>

                        <button x-show="qcStep === maxQcStep" type="button" 
                            @click="$wire.qc_verdict === 'fail' ? $wire.submit() : step = 3"
                            :disabled="!$wire.qc_verdict"
                            class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full md:w-auto"
                            :class="!$wire.qc_verdict ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : ($wire.qc_verdict === 'fail' ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-violet-600 hover:bg-violet-700 text-white')">
                            <span x-text="$wire.qc_verdict === 'fail' ? 'Batalkan Transaksi' : 'Lanjut Kondisi Harga'"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- STEP 3: Condition & Price (Original) --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            style="display: none;" class="space-y-8">

            <h3 class="text-lg md:text-xl uppercase font-black text-neutral-800 px-1">Inspeksi Minus / Penyesuaian Harga</h3>
            <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl">
                <p class="text-sm font-bold text-amber-900 mb-1">Potongan Harga Otomatis</p>
                <p class="text-xs text-amber-700">Tandai cacat fisik atau fungsi yang mengurangi nilai jual. Data dari Ceklis QC sebelumnya bisa Anda gunakan sebagai patokan.</p>
            </div>

            <div class="space-y-8 bg-white p-6 rounded-2xl shadow-sm border border-neutral-100">
                @if ($buyback_device && count($device_rules) > 0)
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-6">
                        <p class="text-sm font-bold text-blue-900 mb-2">Harga Dasar (Mulus 100%): Rp
                            {{ number_format($buyback_device->base_price, 0, ',', '.') }}</p>
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
                                        @if (str_contains(strtolower($category), 'kelengkapan'))
                                            <input type="checkbox"
                                                wire:model.live="selected_rules.{{ $rule['key'] }}"
                                                class="peer hidden">
                                        @else
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

                <div class="space-y-3 pt-6 border-t border-neutral-100">
                    <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">Catatan Tambahan Minus Harga</h1>
                    <textarea wire:model.live="old_phone_additional_note" rows="3"
                        placeholder="Jelaskan kondisi detail jika ada minus..."
                        class="w-full p-4 bg-gray-50 shadow-sm border-2 border-transparent rounded-2xl focus:border-violet-500 outline-none transition-all font-medium text-neutral-700"></textarea>
                </div>
            </div>

            @php
                $rulesValid = false;
                if ($buyback_device && count($device_rules) > 0) {
                    $requiredCategories = collect($device_rules)
                        ->filter(function ($rule) {
                            return !str_contains(strtolower($rule['category']), 'kelengkapan');
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
                {{ $rulesValid ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-violet-900/20' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed pointer-events-none' }}">
                    Cek Ringkasan
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
            </div>
        </div>
