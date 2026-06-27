<div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <button type="button" wire:click="goBack"
                class="text-sm font-bold text-gray-500 hover:text-blue-600 inline-flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </button>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Aktivasi Garansi</h1>
        </div>

        @if (!$foundItem)
            {{-- State 1: Search SN --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-10 text-center">
                <div
                    class="w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Scan Serial Number</h2>
                <p class="text-sm text-gray-500 mb-8">Masukkan SN/IMEI barang yang baru dibeli pelanggan untuk melakukan
                    pengecekan Unboxing QC.</p>

                <form wire:submit="searchItem" class="max-w-md mx-auto">
                    <div class="relative">
                        <input type="text" wire:model="searchQuery"
                            class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 text-lg font-mono text-center focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Scan atau ketik SN..." autofocus>
                        @error('searchQuery')
                            <span class="text-xs text-rose-500 mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($errorMessage)
                        <div
                            class="mt-4 p-3 bg-rose-50 text-rose-700 text-sm font-medium rounded-lg border border-rose-100">
                            {{ $errorMessage }}
                        </div>
                    @endif

                    <button type="submit"
                        class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-colors shadow-sm shadow-blue-500/30 flex items-center justify-center gap-2">
                        <svg wire:loading wire:target="searchItem" class="animate-spin h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Cari Transaksi
                    </button>
                </form>
            </div>
        @elseif (!$isInspecting)
            {{-- State 2: Found Item Confirm --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start gap-4 mb-6">
                    <div
                        class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Transaksi Ditemukan</h2>
                        <p class="text-sm text-gray-500">Pastikan data pelanggan dan barang di bawah ini sesuai sebelum
                            melakukan QC Unboxing.</p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Nomor Invoice</p>
                            <p class="font-bold text-gray-900">{{ $foundItem->order->order_number }}</p>
                            <span
                                class="text-xs text-gray-500">{{ $foundItem->order->accurate_invoice_no ?? '-' }}</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tanggal Pembelian</p>
                            <p class="font-medium text-gray-900">
                                {{ $foundItem->order->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <div class="col-span-2 border-t border-gray-200 pt-3">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pelanggan</p>
                            <p class="font-bold text-blue-600">{{ $foundItem->order->user->name ?? 'Tamu' }}</p>
                            <p class="text-sm text-gray-500">{{ $foundItem->order->user->profile->phone_number ?? '-' }}
                            </p>
                        </div>
                        <div class="col-span-2 border-t border-gray-200 pt-3">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Produk (SN: <span
                                    class="font-mono text-gray-900">{{ $foundItem->serial_number }}</span>)</p>
                            <p class="font-bold text-gray-900 text-lg">{{ $foundItem->product_name }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" wire:click="goBack"
                        class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition">Batal</button>
                    <button type="button" wire:click="startInspection"
                        class="flex-1 px-4 py-3 bg-emerald-500 text-white rounded-xl font-bold hover:bg-emerald-600 transition shadow-sm shadow-emerald-500/30">Mulai
                        QC Unboxing</button>
                </div>
            </div>
        @elseif ($isInspecting)
            {{-- State 3: Execution (Wizard Mode) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="mb-6 pb-4 border-b border-gray-100 flex justify-between items-end">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Form Aktivasi Garansi (QC Unboxing)</h2>
                        <p class="text-sm text-gray-500">SN: <span
                                class="font-mono font-bold text-gray-800">{{ $foundItem->serial_number }}</span></p>
                    </div>
                </div>

                @php
                    $groupedQc = collect($qc_results)->groupBy('category');
                    $categories = $groupedQc->keys();
                    $maxQcStep = $categories->count() + 1; // 0 = Camera, 1..N = Checklist, N+1 = Submit
                @endphp

                <div x-data="{ qcStep: 0 }" class="relative">
                    {{-- Progress Bar QC --}}
                    <div class="mb-6 relative">
                        <div class="h-2 bg-neutral-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 transition-all duration-300"
                                :style="'width: ' + ((qcStep / {{ $maxQcStep }}) * 100) + '%'"></div>
                        </div>
                        <div class="mt-2 text-xs font-bold text-neutral-500 text-right">Tahap <span
                                x-text="qcStep"></span> dari <span>{{ $maxQcStep }}</span></div>
                    </div>

                    {{-- QC SUB-STEP 0: Foto Produk & IMEI --}}
                    <div x-show="qcStep === 0" x-transition.opacity class="space-y-6">
                        <div class="space-y-4">
                            <h1 class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider block">
                                Ambil Foto Fisik & Kelengkapan
                            </h1>
                            <p class="text-xs text-neutral-400 -mt-2 mb-4 ml-1">Semua foto harus diambil langsung dari
                                kamera saat ini.</p>

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

                                    <div
                                        class="relative aspect-square rounded-3xl overflow-hidden transition-all duration-300 group
                                     {{ $photoFile ? 'border border-neutral-100 shadow-sm' : 'border-2 border-dashed bg-neutral-50/50 hover:bg-neutral-50/100 cursor-pointer' }}
                                    {{ $hasError ? 'border-rose-300 bg-rose-50/20' : 'border-neutral-200 hover:border-neutral-300' }}">

                                        @if ($photoFile)
                                            <img src="{{ $photoFile->temporaryUrl() }}"
                                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                            <div
                                                class="absolute inset-x-0 bottom-0 bg-black/40 backdrop-blur-xs py-2 px-3 text-center pointer-events-none z-10">
                                                <span
                                                    class="text-[11px] font-bold text-white tracking-wide block truncate">{{ $label }}</span>
                                            </div>
                                            <button type="button" wire:click="$set('{{ $propertyName }}', null)"
                                                class="absolute top-2 right-2 bg-white/80 hover:bg-white text-neutral-800 p-2 rounded-xl backdrop-blur-md shadow-sm transition hover:scale-105 active:scale-95 z-10 flex items-center justify-center">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-16v1M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            {{-- CAMERA ONLY UPLOAD --}}
                                            <label
                                                class="absolute inset-0 flex flex-col items-center justify-center p-3 text-center select-none overflow-hidden cursor-pointer z-10">
                                                <input type="file" accept="image/*" capture="environment"
                                                    wire:model="{{ $propertyName }}" class="hidden">
                                                <div
                                                    class="absolute inset-0 flex items-center justify-center z-0 group-hover:scale-110 transition-transform duration-300">
                                                    <img src="{{ asset('assets/png/' . $key . '.png') }}"
                                                        alt="{{ $label }}"
                                                        class="w-16 h-auto object-contain drop-shadow-sm {{ $hasError ? 'opacity-20' : 'opacity-30' }}"
                                                        onerror="this.onerror=null; this.src='{{ asset('assets/png/default.png') }}';">
                                                </div>
                                                <div class="relative z-10 flex flex-col items-center justify-center">
                                                    <h4
                                                        class="font-bold text-xs tracking-tight {{ $hasError ? 'text-rose-700' : 'text-neutral-800' }}">
                                                        {{ $label }}</h4>
                                                    <div
                                                        class="mt-1 bg-white/80 backdrop-blur-sm rounded-full p-1.5 shadow-sm">
                                                        <svg class="w-4 h-4 text-blue-600" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                                            </path>
                                                            <circle cx="12" cy="13" r="3"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"></circle>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </label>
                                        @endif

                                        <div wire:loading.flex wire:target="{{ $propertyName }}"
                                            class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center gap-1 z-10">
                                            <span
                                                class="animate-spin w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full"></span>
                                        </div>
                                    </div>
                                    @error('photo_' . $key)
                                        <span
                                            class="text-[9px] font-bold text-rose-500 uppercase mt-1 text-center block">{{ $message }}</span>
                                    @enderror
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- QC SUB-STEPS (Categories) --}}
                    @foreach ($categories as $index => $categoryName)
                        <div x-show="qcStep === {{ $index + 1 }}" x-transition.opacity style="display: none;"
                            class="space-y-6">
                            <h4
                                class="text-lg font-black text-blue-700 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                Pengecekan {{ $categoryName }}
                            </h4>

                            <div class="space-y-4">
                                @foreach ($qc_results as $i => $item)
                                    @if ($item['category'] === $categoryName)
                                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                            <div
                                                class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                                <span
                                                    class="text-sm font-bold text-neutral-700">{{ $item['name'] }}</span>

                                                @if ($item['type'] === 'boolean')
                                                    <div class="flex items-center gap-2">
                                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                                            <input type="radio"
                                                                wire:model.live="qc_results.{{ $i }}.value"
                                                                value="1" class="peer hidden">
                                                            <div
                                                                class="px-4 py-2 rounded-lg text-xs font-bold border transition-all
                                                                peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500
                                                                text-neutral-400 border-neutral-200 bg-white hover:bg-neutral-50 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="3"
                                                                        d="M5 13l4 4L19 7"></path>
                                                                </svg>
                                                                Sesuai / Berfungsi
                                                            </div>
                                                        </label>
                                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                                            <input type="radio"
                                                                wire:model.live="qc_results.{{ $i }}.value"
                                                                value="0" class="peer hidden">
                                                            <div
                                                                class="px-4 py-2 rounded-lg text-xs font-bold border transition-all
                                                                peer-checked:bg-rose-500 peer-checked:text-white peer-checked:border-rose-500
                                                                text-neutral-400 border-neutral-200 bg-white hover:bg-neutral-50 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="3"
                                                                        d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                                Cacat / Rusak
                                                            </div>
                                                        </label>
                                                    </div>
                                                @else
                                                    <input type="text"
                                                        wire:model.lazy="qc_results.{{ $i }}.value"
                                                        class="p-2 text-sm border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                        placeholder="Isi persentase (cth: 100%)">
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- SUB-STEP N+1: SUBMIT --}}
                    <div x-show="qcStep === {{ $maxQcStep }}" x-transition.opacity style="display: none;"
                        class="space-y-6">
                        <h4
                            class="text-lg font-black text-blue-700 uppercase tracking-wider border-b border-neutral-100 pb-2">
                            Kesimpulan & Konfirmasi</h4>
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <p class="text-sm font-bold text-blue-900">Periksa Kembali Catatan Anda</p>
                            <p class="text-xs text-blue-700 mt-1">Pastikan foto dan checklist QC telah diisi dengan
                                akurat sesuai kondisi perangkat saat diserahterimakan kepada pelanggan.</p>
                        </div>

                        <div class="space-y-2 mt-4">
                            <label class="text-xs font-black text-neutral-500 uppercase ml-1 tracking-wider">Catatan
                                Tambahan (Opsional)</label>
                            <textarea wire:model.live.debounce.500ms="qc_notes" rows="3"
                                placeholder="Cth: Casing ada sedikit goresan halus dari pabrik..."
                                class="w-full p-4 bg-gray-50 shadow-sm border-2 border-transparent rounded-2xl focus:border-blue-500 outline-none transition-all text-sm font-medium text-neutral-700"></textarea>
                        </div>
                    </div>

                    {{-- Wizard Navigation Buttons --}}
                    @php
                        $isPhotoValid =
                            !empty($photo_depan) &&
                            !empty($photo_belakang) &&
                            !empty($photo_kiri) &&
                            !empty($photo_kanan) &&
                            !empty($photo_kelengkapan);
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
                        </div>

                        <div class="w-full md:w-auto flex justify-end">
                            <button x-show="qcStep === 0" type="button" @click="qcStep++"
                                {{ $isPhotoValid ? '' : 'disabled' }}
                                class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full md:w-auto
                                {{ $isPhotoValid ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-200' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed' }}">
                                Mulai Ceklis Fungsional
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </button>

                            <button x-show="qcStep > 0 && qcStep < {{ $maxQcStep }}" type="button"
                                @click="qcStep++"
                                class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-200 w-full md:w-auto">
                                Lanjut
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </button>

                            <button x-show="qcStep === {{ $maxQcStep }}" type="button" wire:click="submit"
                                class="px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full md:w-auto bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-200">
                                Simpan Aktivasi Garansi
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    <!-- Success Modal -->
    @if ($isSaved)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            <div class="relative bg-white rounded-3xl w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                <div class="p-6 md:p-8 overflow-y-auto">
                    <div class="mb-6 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4 shadow-inner">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight">Aktivasi Garansi Berhasil</h3>
                        <p class="text-gray-500 mt-2 max-w-md mx-auto text-sm">
                            Inspeksi QC Unboxing selesai dan garansi telah diaktifkan untuk perangkat ini.
                        </p>
                    </div>

                    @if (count($generatedWarranties) > 0)
                        <div class="space-y-6">
                            @foreach ($generatedWarranties as $warranty)
                                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm shadow-blue-900/5 border border-gray-200 relative overflow-hidden">
                                    <!-- Background Decoration -->
                                    <div class="absolute -right-16 -top-16 w-64 h-64 bg-gradient-to-br {{ $warranty->policy->type === 'addon_warranty' ? 'from-purple-500/10 to-pink-500/10' : 'from-blue-500/10 to-cyan-500/10' }} rounded-full blur-3xl pointer-events-none"></div>

                                    <div class="relative z-10 flex flex-col md:flex-row gap-6 md:items-center justify-between border-b border-gray-100 pb-6 mb-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm {{ $warranty->policy->type === 'addon_warranty' ? 'bg-purple-50 text-purple-600' : 'bg-blue-50 text-blue-600' }}">
                                                @if ($warranty->policy->type === 'addon_warranty')
                                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                    </svg>
                                                @else
                                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <div>
                                                <h4 class="text-lg font-black text-gray-900">{{ $warranty->policy->name }}</h4>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $warranty->policy->type === 'addon_warranty' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                                        {{ $warranty->policy->type === 'addon_warranty' ? 'Asuransi Tambahan' : 'Garansi Utama Toko' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-left md:text-right">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Status Garansi</p>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-xs font-bold shadow-sm">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif s/d {{ $warranty->expires_at->format('d M Y') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Detail Perangkat</p>
                                            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 space-y-3">
                                                <div class="flex justify-between items-center border-b border-gray-200/60 pb-3">
                                                    <span class="text-sm font-medium text-gray-500">Model</span>
                                                    <span class="text-sm font-bold text-gray-900">{{ $foundItem->product_name }}</span>
                                                </div>
                                                <div class="flex justify-between items-center border-b border-gray-200/60 pb-3">
                                                    <span class="text-sm font-medium text-gray-500">Serial Number</span>
                                                    <span class="text-sm font-mono font-bold text-gray-900">{{ $warranty->serial_number }}</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-medium text-gray-500">Pelanggan</span>
                                                    <span class="text-sm font-bold text-blue-600">{{ $foundItem->order->user->name ?? 'Tamu' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Cakupan Kerusakan (Coverage)</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @foreach ($warranty->policy->coverage as $cov)
                                                    <div class="flex items-start gap-2.5 p-2 rounded-lg {{ $cov['covered'] ? 'bg-emerald-50/50' : 'bg-rose-50/50' }}">
                                                        @if ($cov['covered'])
                                                            <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @else
                                                            <svg class="w-4 h-4 text-rose-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        @endif
                                                        <span class="text-xs font-semibold {{ $cov['covered'] ? 'text-gray-800' : 'text-gray-500 line-through' }}">
                                                            {{ $cov['name'] }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
                            <svg class="w-12 h-12 text-amber-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="font-bold text-amber-800">Tidak ada Garansi Aktif</p>
                            <p class="text-sm text-amber-700 mt-1">Sistem tidak menemukan kebijakan garansi default untuk produk ini maupun pembelian asuransi tambahan.</p>
                        </div>
                    @endif

                    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                        <button type="button" class="px-8 py-3 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-colors shadow-sm flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Kartu Garansi
                        </button>
                        <a href="{{ route('zoffline') }}" class="px-8 py-3 bg-[#1c69d4] hover:bg-[#3f36b8] text-white font-bold rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2">
                            Selesai & Kembali ke Home
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
