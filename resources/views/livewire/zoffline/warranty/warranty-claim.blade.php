<div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('zoffline') }}" wire:navigate
                class="text-sm font-bold text-gray-500 hover:text-blue-600 inline-flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Klaim Garansi</h1>
        </div>

        @if ($isSubmitted)
            <div class="bg-emerald-50 border-2 border-dashed border-emerald-200 rounded-2xl p-8 text-center mt-8">
                <div
                    class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900">Klaim Berhasil Diajukan</h3>
                <p class="text-gray-500 mt-2 max-w-sm mx-auto">
                    Klaim garansi telah masuk ke sistem dan menunggu persetujuan Manager.
                </p>
                <div class="mt-8 flex justify-center gap-4">
                    <button wire:click="resetForm"
                        class="px-6 py-3 bg-white border border-emerald-200 text-emerald-700 font-bold rounded-xl hover:bg-emerald-100 transition-colors shadow-sm">
                        Ajukan Klaim Lainnya
                    </button>
                </div>
            </div>
        @elseif (!$isInspecting)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                <div class="mb-8 border-b border-gray-100 pb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-2">Cari Perangkat</h2>
                    <p class="text-sm text-gray-500 mb-4">Masukkan Serial Number / IMEI perangkat yang ingin diklaim.
                    </p>

                    <form wire:submit="searchWarranties" class="flex gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" wire:model="searchQuery"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl pl-11 pr-4 py-3 text-sm font-mono focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Scan SN / IMEI...">
                        </div>
                        <button type="submit"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors shadow-sm whitespace-nowrap">
                            Cari Garansi
                        </button>
                    </form>
                    @error('searchQuery')
                        <span class="text-xs text-rose-500 mt-2 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                @if (count($foundWarranties) > 0)
                    <div class="mb-8">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-wider mb-4">Pilih Garansi yang
                            Ingin Diklaim</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($foundWarranties as $warranty)
                                @php
                                    $isExpired =
                                        $warranty->expires_at < \Carbon\Carbon::now() || $warranty->status !== 'active';
                                    $isDisabled = false; // We allow selection even if expired, to process Paid Service
                                @endphp
                                <label class="relative cursor-pointer group">
                                    <input type="radio" wire:model="selectedWarrantyId" value="{{ $warranty->id }}"
                                        wire:click="selectWarranty({{ $warranty->id }})" class="peer hidden"
                                        {{ $isDisabled ? 'disabled' : '' }}>
                                    <div
                                        class="h-full border-2 rounded-2xl p-5 transition-all
                                        {{ $isDisabled ? 'border-gray-200 bg-gray-50 opacity-60 cursor-not-allowed' : 'border-gray-200 bg-white hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50/30' }}">

                                        <div class="flex justify-between items-start mb-3">
                                            <div
                                                class="w-10 h-10 rounded-xl flex items-center justify-center {{ $warranty->policy->type === 'insurance' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                            </div>
                                            <div class="text-right">
                                                @if ($isExpired)
                                                    <span
                                                        class="inline-block px-2 py-1 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-md">Habis
                                                        Masa Garansi</span>
                                                @else
                                                    <span
                                                        class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md">Aktif</span>
                                                @endif
                                            </div>
                                        </div>

                                        <h4 class="font-bold text-gray-900 mb-1 line-clamp-1">
                                            {{ $warranty->policy->name ?? 'Garansi' }}
                                            <span class="text-xs text-gray-500">( {{ $warranty->policy->duration_days }}
                                                Hari)</span>
                                        </h4>
                                        <div class="space-y-1.5 mt-3 text-xs text-gray-500">
                                            <div class="flex justify-between">
                                                <span>Berakhir:</span>
                                                <span
                                                    class="font-bold {{ $isExpired ? 'text-rose-600' : 'text-gray-700' }}">{{ $warranty->expires_at->format('d M Y') }}</span>
                                            </div>
                                            {{-- <div class="flex justify-between">
                                                <span>Telah Diklaim:</span>
                                                <span
                                                    class="font-bold text-gray-700">{{ $warranty->claims_used }}x</span>
                                            </div> --}}
                                        </div>

                                        <!-- Custom Radio Button Indicator -->
                                        {{-- <div
                                            class="absolute top-5 right-5 w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-blue-500 flex items-center justify-center transition-colors {{ $isDisabled ? 'hidden' : '' }}">
                                            <div
                                                class="w-2.5 h-2.5 rounded-full bg-blue-500 opacity-0 peer-checked:opacity-100 transition-opacity">
                                            </div>
                                        </div> --}}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedWarrantyId')
                            <span class="text-xs text-rose-500 mt-2 block font-medium">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($selectedWarrantyId)
                        <div class="border-t border-gray-100 pt-8 animate-fade-in-up">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
                                <h3 class="text-lg font-bold text-gray-900">Informasi Garansi</h3>
                                <button type="button" wire:click="openQcHistory"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-bold rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    Cek Riwayat QC (Unboxing)
                                </button>
                            </div>

                            @php
                                $selectedWarranty = $foundWarranties->firstWhere('id', $selectedWarrantyId);
                                $isSelectedExpired = $selectedWarranty
                                    ? $selectedWarranty->expires_at < \Carbon\Carbon::now() ||
                                        $selectedWarranty->status !== 'active'
                                    : false;
                            @endphp

                            @if ($selectedWarranty)
                                <div
                                    class="mb-6 {{ $isSelectedExpired ? 'bg-rose-50 border-rose-100' : 'bg-blue-50 border-blue-100' }} rounded-lg p-4 border">
                                    @if ($isSelectedExpired)
                                        <p class="text-sm font-bold text-rose-800 mb-1">Peringatan: Garansi ini sudah
                                            Habis.</p>
                                        <p class="text-xs text-rose-600">Perbaikan akan diproses sebagai Servis
                                            Berbayar (Service Center).</p>
                                    @else
                                        <p class="text-sm font-bold text-blue-800 mb-1">Cakupan Garansi:
                                            {{ $selectedWarranty->type === 'full_cover' ? 'Full Cover (Termasuk Human Error)' : 'Ganti Unit (Hanya Cacat Pabrik)' }}
                                        </p>
                                        <p class="text-xs text-blue-600">Proses akan diawali dengan pengecekan QC
                                            Penerimaan untuk verifikasi kerusakan.</p>
                                    @endif
                                </div>
                            @endif

                            <div class="flex justify-end mt-4">
                                <button type="button" wire:click="startInspection"
                                    class="px-8 py-3 {{ $isSelectedExpired ? 'bg-rose-600 hover:bg-rose-700' : 'bg-[#1c69d4] hover:bg-[#3f36b8]' }} text-white font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 w-full md:w-auto">
                                    <svg wire:loading.remove wire:target="startInspection" class="w-5 h-5"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg wire:loading wire:target="startInspection" class="animate-spin h-5 w-5"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ $isSelectedExpired ? 'Lanjutkan QC dan Service' : 'Mulai QC Penerimaan Klaim' }}

                                </button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @elseif ($isInspecting)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="mb-6 pb-4 border-b border-gray-100 flex justify-between items-end">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Form QC Penerimaan Klaim</h2>
                        <p class="text-sm text-gray-500">SN: <span
                                class="font-mono font-bold text-gray-800">{{ $searchQuery }}</span></p>
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
                                Ambil Foto Fisik Terkini
                            </h1>
                            <p class="text-xs text-neutral-400 -mt-2 mb-4 ml-1">Ambil foto untuk membuktikan kondisi
                                saat barang diterima (sebelum diservis).</p>

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
                                Cek Kondisi & Fungsi {{ $categoryName }}
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
                                                                Bagus / Normal
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
                                                        placeholder="Isi catatan (cth: Layar gores)">
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- SUB-STEP N+1: FINAL CONCLUSION --}}
                    <div x-show="qcStep === {{ $maxQcStep }}" x-transition.opacity style="display: none;"
                        class="space-y-6">

                        <!-- Peringatan Garansi/Service -->
                        @php
                            $selectedWarranty = collect($foundWarranties)->firstWhere('id', $selectedWarrantyId);
                            $isSelectedExpired = $selectedWarranty
                                ? $selectedWarranty->expires_at < \Carbon\Carbon::now() ||
                                    $selectedWarranty->status !== 'active'
                                : false;

                            // Hitung jumlah cacat/rusak
                            $failedItems = collect($qc_results)->where('type', 'boolean')->where('value', '0');
                        @endphp

                        <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200 shadow-inner">
                            <h4 class="text-lg font-black text-gray-900 mb-4 border-b border-gray-200 pb-2">Rangkuman
                                Hasil QC</h4>

                            @if ($failedItems->count() > 0)
                                <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 mb-4">
                                    <p class="text-sm font-bold text-rose-800 mb-2 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Ditemukan {{ $failedItems->count() }} Kerusakan / Cacat Fisik:
                                    </p>
                                    <ul class="list-disc pl-5 text-sm text-rose-700 space-y-1">
                                        @foreach ($failedItems as $fail)
                                            <li>{{ $fail['name'] }} ({{ $fail['category'] }})</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 mb-4">
                                    <p class="text-sm font-bold text-emerald-800 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Tidak ditemukan kerusakan fungsional dalam QC ini.
                                    </p>
                                </div>
                            @endif

                            <form wire:submit="submitClaim">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 mt-6">
                                    <div class="form-control">
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Pelanggan <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" wire:model="customer_name"
                                            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                                            required>
                                        @error('customer_name')
                                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-control">
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Nomor Telepon</label>
                                        <input type="text" wire:model="customer_phone"
                                            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                                <div class="form-control mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Keluhan Pelanggan &
                                        Catatan QC <span class="text-red-500">*</span></label>
                                    <textarea wire:model="issue_description" rows="3"
                                        class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Deskripsi keluhan pelanggan..." required></textarea>
                                    @error('issue_description')
                                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex justify-end mt-6">
                                    <button type="submit"
                                        class="px-8 py-3 {{ $isSelectedExpired ? 'bg-rose-600 hover:bg-rose-700' : 'bg-[#1c69d4] hover:bg-[#3f36b8]' }} text-white font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 w-full md:w-auto">
                                        <svg wire:loading.remove wire:target="submitClaim" class="w-5 h-5"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg wire:loading wire:target="submitClaim" class="animate-spin h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ $isSelectedExpired ? 'Lanjutkan Isi Form Service' : 'Simpan QC & Ajukan Klaim' }}
                                    </button>
                                </div>
                            </form>
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
                    <div class="flex flex-col md:flex-row justify-between items-center mt-8 pt-4 border-t border-neutral-100 gap-4"
                        x-show="qcStep < {{ $maxQcStep }}">
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
                                Mulai Pengecekan Fungsional
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
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Form Service Center -->
    @if ($showServiceCenterForm)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            <div
                class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-rose-50">
                    <div>
                        <h3 class="font-bold text-xl text-rose-900">Form Pendaftaran Service Center</h3>
                        <p class="text-xs text-rose-600 mt-0.5">Perangkat Out of Warranty (Berbayar)</p>
                    </div>
                    <button wire:click="$set('showServiceCenterForm', false)"
                        class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <form wire:submit="submitServiceCenter">
                        @php
                            $svcWarranty = $foundWarranties ? $foundWarranties->firstWhere('id', $selectedWarrantyId) : null;
                        @endphp
                        
                        @if($svcWarranty)
                            <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-4 mb-5">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">Informasi Perangkat</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-[11px] text-gray-500 mb-0.5">Nama Produk</p>
                                        <p class="font-bold text-gray-800 text-sm line-clamp-1">{{ $svcWarranty->orderItem->product_name ?? ($svcWarranty->orderItem->variant->name ?? 'Unknown Product') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-gray-500 mb-0.5">IMEI / Serial Number</p>
                                        <p class="font-bold font-mono text-gray-900 text-sm">{{ $svcWarranty->serial_number }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="text-[11px] text-gray-500 mb-0.5">No Faktur / Transaksi Pelanggan</p>
                                        <p class="font-bold text-gray-800 text-sm">
                                            {{ $svcWarranty->orderItem->order->accurate_invoice_no ?? ($svcWarranty->orderItem->order->order_number ?? '-') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Cek Fisik Saat Diterima <span
                                        class="text-red-500">*</span></label>
                                <textarea wire:model="physical_condition" rows="2"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl p-3 text-sm focus:ring-rose-500 focus:border-rose-500"
                                    placeholder="Contoh: Layar retak pojok kanan, bazel mulus, mesin nyala..." required></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Kelengkapan yang
                                    Ditinggal</label>
                                <textarea wire:model="accessories" rows="2"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl p-3 text-sm focus:ring-rose-500 focus:border-rose-500"
                                    placeholder="Contoh: Hanya unit HP saja, tanpa dus dan charger."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Estimasi Biaya Awal
                                    (Opsional)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3 text-gray-500 text-sm font-bold">Rp</span>
                                    <input type="number" wire:model="estimated_cost"
                                        class="w-full bg-gray-50 border-gray-200 rounded-xl pl-10 p-3 text-sm focus:ring-rose-500 focus:border-rose-500"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" wire:click="$set('showServiceCenterForm', false)"
                                class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl">Batal</button>
                            <button type="submit"
                                class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-sm">Simpan
                                Tiket Servis</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal QC History -->
    @if ($showQcModal && $qcInspection)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                wire:click="$set('showQcModal', false)"></div>

            <!-- Modal Content -->
            <div
                class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-xl text-gray-900">Riwayat QC Unboxing</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Dilakukan pada:
                                {{ $qcInspection->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('showQcModal', false)"
                        class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    <!-- Inspector Info -->
                    <div
                        class="flex justify-between items-center bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50">
                        <div>
                            <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">Diinspeksi Oleh
                            </p>
                            <p class="font-bold text-indigo-900">{{ $qcInspection->inspector->name ?? 'Staff' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">Status Final QC
                            </p>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Lulus / Passed
                            </span>
                        </div>
                    </div>

                    <!-- Photos -->
                    @php
                        $mediaItems = $qcInspection->getMedia('qc_photos');
                    @endphp
                    @if ($mediaItems->count() > 0)
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-3">Foto Kondisi Awal (Unboxing)</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                                @foreach ($mediaItems as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank"
                                        class="aspect-square rounded-xl overflow-hidden border border-gray-100 shadow-sm bg-gray-50 group relative block">
                                        <img src="{{ $media->getUrl() }}" alt="QC Photo"
                                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                                        <div
                                            class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-md"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                            </svg>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Checklist -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 mb-3">Hasil Checklist QC Fungsional</h4>
                        @php
                            // Decode JSON from DB
                            $results = is_array($qcInspection->checklist_results)
                                ? $qcInspection->checklist_results
                                : json_decode($qcInspection->checklist_results, true);
                            $grouped = collect($results)->groupBy('category');
                        @endphp

                        <div class="space-y-4">
                            @foreach ($grouped as $category => $items)
                                <div class="bg-gray-50 rounded-xl border border-gray-100 overflow-hidden">
                                    <div class="bg-gray-100/50 px-4 py-2 border-b border-gray-100">
                                        <h5 class="text-xs font-bold text-gray-600 uppercase">{{ $category }}</h5>
                                    </div>
                                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach ($items as $item)
                                            <div
                                                class="flex items-center justify-between bg-white p-2.5 rounded-lg border border-gray-100 shadow-sm">
                                                <span
                                                    class="text-sm font-medium text-gray-700">{{ $item['name'] }}</span>
                                                @if ($item['type'] === 'boolean')
                                                    @if ($item['value'])
                                                        <svg class="w-5 h-5 text-emerald-500" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-rose-500" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    @endif
                                                @else
                                                    <span
                                                        class="text-sm font-bold text-gray-900">{{ $item['value'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Notes -->
                    @if ($qcInspection->inspector_notes)
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-2">Catatan Tambahan</h4>
                            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                                <p class="text-sm text-amber-800">{{ $qcInspection->inspector_notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
