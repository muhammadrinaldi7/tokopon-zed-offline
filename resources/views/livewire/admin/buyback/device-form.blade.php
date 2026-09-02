<div class="space-y-6">

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
                    {{-- <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-blue-900 mb-1.5">Business Unit</label>
                        <select wire:model.live="target_business_unit_id"
                            class="w-full rounded-lg border-blue-200 bg-white py-2.5 text-blue-900 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
                            <option value="">-- Semua BU --</option>
                            @foreach (\App\Models\BusinessUnit::all() as $bu)
                                <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                            @endforeach
                        </select>
                    </div> --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-blue-900 mb-1.5">Pilih SKU Produk (Ketik untuk
                            mencari)</label>
                        <div class="relative">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" wire:model.live.debounce.300ms="searchProduct"
                                    placeholder="Ketik nama atau No. Item (min. 2 karakter)..."
                                    class="w-full pl-10 pr-16 py-3 border border-gray-200 rounded-xl text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white transition-all shadow-sm">

                                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                    <!-- Loading Spinner -->
                                    <svg wire:loading wire:target="searchProduct"
                                        class="animate-spin h-5 w-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>

                                    @if (strlen($searchProduct) > 0)
                                        <button type="button" wire:click="$set('searchProduct', '')"
                                            class="text-gray-400 hover:text-gray-600 transition-colors bg-gray-100 hover:bg-gray-200 rounded-full p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- List Hasil Pencarian -->
                            @if (!empty($productsAccurateList))
                                <div
                                    class="absolute z-10 mt-2 w-full bg-white/95 backdrop-blur-xl border border-gray-100 rounded-2xl shadow-xl max-h-64 overflow-y-auto transform origin-top transition-all">
                                    <ul class="p-2 space-y-1">
                                        @foreach ($productsAccurateList as $prod)
                                            <li>
                                                <button type="button" wire:click="selectProduct('{{ $prod->id }}')"
                                                    class="w-full text-left px-4 py-3 rounded-xl hover:bg-[#eff6ff] group transition-all flex flex-col border border-transparent hover:border-blue-100">
                                                    <span
                                                        class="font-bold text-gray-900 group-hover:text-[#1c69d4] transition-colors">{{ $prod->name }}</span>
                                                    <span class="text-xs text-gray-500 font-mono mt-1">Item No:
                                                        {{ $prod->item_no }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif(strlen($searchProduct) >= 2)
                                <div
                                    class="absolute z-10 mt-2 w-full bg-white border border-gray-100 rounded-2xl shadow-lg p-6 text-center">
                                    <div
                                        class="mx-auto w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700">Produk tidak ditemukan</p>
                                    <p class="text-xs text-gray-500 mt-1">Coba gunakan kata kunci lain.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Selected Products Badges -->
                        @if (count($selectedProducts) > 0)
                            <div class="mt-4 p-5 bg-gray-50/80 rounded-2xl border border-gray-100">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                                    Perangkat Terpilih ({{ count($selectedProducts) }})
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($selectedProducts as $id => $name)
                                        <div
                                            class="group inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-blue-300 hover:shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                                            <div class="w-2 h-2 rounded-full bg-[#1c69d4] group-hover:animate-pulse">
                                            </div>
                                            <span
                                                class="text-sm font-bold text-gray-700 group-hover:text-[#1c69d4] transition-colors">{{ $name }}</span>
                                            <button type="button" wire:click="removeProduct('{{ $id }}')"
                                                class="ml-1 text-gray-300 hover:text-rose-500 hover:bg-rose-50 transition-all p-1 rounded-lg">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @error('product_accurate_id')
                            <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            {{-- SECTION 2: TIER & STATUS --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-6 bg-emerald-500 rounded-full"></div>
                    <h3 class="font-bold text-gray-800">2. Pilih Tier & Status</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Tier Buyback</label>
                        <select wire:model="selected_tier_id"
                            class="w-full rounded-lg border-gray-200 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm">
                            <option value="">-- Pilih Tier --</option>
                            @foreach ($allTiers as $tier)
                                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">
                            Pilih tier yang akan menentukan potongan minus untuk HP ini.
                        </p>
                        @error('selected_tier_id')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Status Aktif</label>
                        <label class="relative inline-flex items-center cursor-pointer mt-1">
                            <input type="checkbox" wire:model="is_active" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#1c69d4]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1c69d4]">
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-600">Terima Buyback</span>
                        </label>
                    </div>
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
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 p-5">
            <p class="text-sm font-bold text-gray-700 mb-3">Referensi Tier yang Tersedia</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($allTiers as $tier)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ $tier->name }}</p>
                            <p class="text-xs text-gray-500">Kategori Tier Buyback</p>
                        </div>
                        <span class="text-xs text-gray-400">{{ $tier->devices_count ?? 0 }} HP</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
