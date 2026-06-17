<div class="max-w-3xl mx-auto space-y-2">

    {{-- ─────────────── HEADER ─────────────── --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Tambah Perangkat Buyback</h1>
        <p class="text-sm text-gray-500 mt-1">
            Input harga beli HP dan tier akan ter-assign otomatis sesuai range harga yang sudah dikonfigurasi.
        </p>
    </div>

    {{-- ─────────────── CARD FORM ─────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Card Header --}}
        <div class="bg-white border-b border-gray-100 px-6 py-5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Formulir Pendaftaran HP</h2>
                <p class="text-sm text-gray-500">Lengkapi spesifikasi dari HP yang akan dibeli</p>
            </div>
        </div>

        <form wire:submit.prevent="save" class="p-6 space-y-5">

            {{-- SECTION 1: SINKRONISASI ACCURATE --}}
            <div class="bg-blue-50/50 rounded-xl border border-blue-100 p-5 space-y-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-6 bg-blue-600 rounded-full"></div>
                    <h3 class="font-bold text-blue-900">1. Master Data Accurate</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-blue-900 mb-1.5">Business Unit</label>
                        <select wire:model.live="target_business_unit_id"
                            class="w-full rounded-lg border-blue-200 bg-white py-2.5 text-blue-900 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
                            <option value="">-- Semua BU --</option>
                            @foreach (\App\Models\BusinessUnit::all() as $bu)
                                <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-blue-900 mb-1.5">Pilih SKU Produk (Ketik untuk
                            mencari)</label>
                        <div class="relative" x-data="{ open: false }">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="searchProduct" @focus="open = true"
                                @click.away="open = false" placeholder="Misal: iPhone 15 Bekas..."
                                class="w-full pl-10 rounded-lg border-blue-200 bg-white py-2.5 text-blue-900 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition-all">

                            @if (!empty($productsAccurateList))
                                <div x-show="open"
                                    class="absolute z-10 w-full bg-white border border-gray-200 rounded-xl shadow-xl max-h-60 overflow-y-auto mt-2 py-1">
                                    @foreach ($productsAccurateList as $prod)
                                        <div wire:click="selectProduct({{ $prod->id }})" @click="open = false"
                                            class="cursor-pointer px-4 py-3 hover:bg-blue-50 border-b border-gray-50 last:border-0 transition-colors">
                                            <div class="font-bold text-sm text-gray-900">{{ $prod->name }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5 flex gap-2">
                                                <span
                                                    class="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">{{ $prod->item_no }}</span>
                                                <span>{{ $prod->brandName ?? 'Tanpa Merek' }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('product_accurate_id')
                            <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            {{-- SECTION 2: SPESIFIKASI --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-6 bg-gray-400 rounded-full"></div>
                    <h3 class="font-bold text-gray-800">2. Spesifikasi Fisik</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Merek (Terisi Otomatis)</label>
                        <select wire:model="brand_id"
                            class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-600 pointer-events-none"
                            tabindex="-1">
                            <option value="">-- Merek tidak terdeteksi --</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Model HP (Terisi Otomatis)</label>
                        <input type="text" wire:model="model_name"
                            placeholder="Pilih produk dari Accurate di atas..."
                            class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-600 pointer-events-none"
                            tabindex="-1">
                        @error('model_name')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Kapasitas RAM
                            <span class="font-normal text-gray-400">(opsional)</span>
                        </label>
                        <input type="text" wire:model="ram" placeholder="cth: 8GB"
                            class="w-full rounded-lg border-gray-200 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm transition-all hover:border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Kapasitas Penyimpanan</label>
                        <input type="text" wire:model="storage" placeholder="cth: 256GB"
                            class="w-full rounded-lg border-gray-200 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm transition-all hover:border-gray-300">
                        @error('storage')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            {{-- SECTION 3: HARGA & STATUS --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-6 bg-emerald-500 rounded-full"></div>
                    <h3 class="font-bold text-gray-800">3. Harga Dasar & Tier</h3>
                </div>

                {{-- Harga Beli + Auto Tier Detection --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Harga Beli (Kondisi Sempurna)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm font-bold">Rp</span>
                            </div>
                            <input type="number" wire:model.live="base_price" placeholder="0"
                                class="w-full rounded-lg border-gray-200 py-2.5 pl-10 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            Tier akan ter-assign otomatis berdasarkan harga ini.
                        </p>
                        @error('base_price')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Tier Detection Preview --}}
                    <div class="pt-6">
                        @if ($detected_tier_id && $detectedTier)
                            <div class="flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-emerald-800">Tier Ditemukan!</p>
                                    <p class="text-sm text-emerald-700 font-semibold">{{ $detectedTier->name }}</p>
                                    <p class="text-xs text-emerald-600 mt-0.5">{{ $detectedTier->price_range_label }}
                                    </p>
                                </div>
                            </div>
                        @elseif (!empty($base_price) && is_numeric($base_price) && $base_price > 0)
                            <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-amber-400 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-amber-800">Tier Tidak Ditemukan</p>
                                    <p class="text-xs text-amber-700 mt-0.5">
                                        Tidak ada tier dengan range harga Rp
                                        {{ number_format($base_price, 0, ',', '.') }}.
                                        Tambah tier baru di halaman Buyback Tiers.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div
                                class="flex items-center gap-3 p-4 bg-gray-50 border border-gray-200 rounded-lg text-gray-400">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">Masukkan harga untuk deteksi tier otomatis.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Rules Preview dari Tier yang Ter-detect --}}
                @if ($detectedTier && !empty($detectedTier->rules))
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                        <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#1c69d4]" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                            Rules dari Tier "{{ $detectedTier->name }}"
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($detectedTier->rules as $category => $items)
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                                        {{ $category }}
                                    </p>
                                    <div class="space-y-1.5">
                                        @foreach ($items as $item)
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-gray-600">{{ $item['name'] }}</span>
                                                <span
                                                    class="font-bold {{ $item['type'] === 'fixed' ? 'text-rose-500' : 'text-amber-500' }}">
                                                    @if ($item['type'] === 'fixed')
                                                        -Rp {{ number_format($item['value'], 0, ',', '.') }}
                                                    @else
                                                        -{{ $item['value'] }}%
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Status Aktif --}}
                <div
                    class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100 mt-4 transition-all hover:bg-gray-100">
                    <div class="flex items-center h-5">
                        <input type="checkbox" wire:model="is_active" id="is_active"
                            class="w-5 h-5 text-[#1c69d4] bg-white border-gray-300 rounded focus:ring-[#1c69d4] cursor-pointer">
                    </div>
                    <div class="flex flex-col">
                        <label for="is_active" class="text-sm font-bold text-gray-800 cursor-pointer">
                            Perangkat Ini Aktif
                        </label>
                        <p class="text-xs text-gray-500">Jika dinonaktifkan, model HP ini tidak akan bisa dipilih oleh
                            staf saat Buyback.</p>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-6 mt-6 flex gap-4 border-t border-gray-100">
                    <a href="{{ route('admin.buyback.index') }}" wire:navigate
                        class="flex-1 text-center px-4 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Kembali
                    </a>
                    <button type="submit"
                        class="flex-[2] px-4 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-[#1c69d4] to-[#7C74F0] hover:from-[#1553a8] hover:to-[#5e58c2] transition-all shadow-md shadow-blue-500/20">
                        Simpan Perangkat ke Sistem
                    </button>
                </div>
        </form>
    </div>

    {{-- All Tiers Reference --}}
    @if ($allTiers->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-sm font-bold text-gray-700 mb-3">Referensi Tier yang Tersedia</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($allTiers as $tier)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ $tier->name }}</p>
                            <p class="text-xs text-gray-500">{{ $tier->price_range_label }}</p>
                        </div>
                        <span class="text-xs text-gray-400">{{ $tier->devices_count ?? 0 }} HP</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
