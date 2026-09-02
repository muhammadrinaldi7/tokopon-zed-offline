<div>
    {{-- ─────────────── HEADER ─────────────── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kategori Tier Buyback</h1>
            <p class="text-sm text-gray-500 mt-1">
                Kelola kategori tier potongan buyback berdasarkan model HP.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative w-full md:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama tier..."
                    class="w-full bg-white border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] transition-all">
            </div>
            <button wire:click="create"
                class="bg-gradient-to-r from-[#1c69d4] to-[#7C74F0] text-white px-5 py-2.5 rounded-lg font-bold hover:shadow-sm hover:shadow-[#1c69d4]/40 hover:-translate-y-0.5 transition-all flex items-center gap-2 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Tier Baru
            </button>
        </div>
    </div>

    {{-- ─────────────── TIER CARDS ─────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($tiers as $tier)
            <div
                class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-sm hover:shadow-indigo-900/5 transition-all duration-300 overflow-hidden flex flex-col group">

                {{-- Card Header --}}
                <div class="bg-gray-50/50 p-6 border-b border-gray-100 relative overflow-hidden">
                    {{-- Decorative blur --}}
                    <div
                        class="absolute -top-10 -right-10 w-32 h-32 bg-[#1c69d4]/5 rounded-full blur-2xl group-hover:bg-[#1c69d4]/10 transition-colors">
                    </div>

                    <div class="flex items-start justify-between relative z-10">
                        <div>
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-gray-200 text-gray-500 rounded-lg text-[10px] font-black uppercase tracking-widest mb-3 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#1c69d4]"></span> Tier
                            </span>
                            <h2 class="text-2xl font-black text-gray-900">{{ $tier->name }}</h2>
                        </div>
                        <div
                            class="bg-white border border-gray-100 shadow-sm text-gray-700 text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#1c69d4]" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            {{ $tier->devices_count }} HP
                        </div>
                    </div>
                </div>

                {{-- Rules Preview --}}
                <div class="p-6 flex-1 space-y-5 bg-white">
                    @if (!empty($tier->rules))
                        @foreach ($tier->rules as $category => $items)
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-[1px] bg-gray-200"></div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        {{ $category }}
                                    </p>
                                    <div class="flex-1 h-[1px] bg-gray-100"></div>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                    @foreach ($items as $item)
                                        <div
                                            class="flex items-center justify-between p-3 bg-gray-50/50 hover:bg-gray-50 border border-gray-100 rounded-lg transition-colors">
                                            <span class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                                <div
                                                    class="w-1.5 h-1.5 rounded-full {{ $item['type'] === 'fixed' ? 'bg-amber-400' : 'bg-indigo-400' }}">
                                                </div>
                                                {{ $item['name'] }}
                                            </span>
                                            <span
                                                class="font-black px-2.5 py-1 rounded-lg text-xs {{ $item['type'] === 'fixed' ? 'bg-amber-50 text-amber-700' : 'bg-[#eff6ff] text-indigo-700' }}">
                                                @if ($item['type'] === 'fixed')
                                                    - Rp {{ number_format($item['value'], 0, ',', '.') }}
                                                @else
                                                    - {{ $item['value'] }}%
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div
                            class="flex flex-col items-center justify-center py-8 text-gray-400 h-full border-2 border-dashed border-gray-100 rounded-lg">
                            <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-500">Belum Ada Aturan Minus</p>
                            <p class="text-xs text-center mt-1 w-2/3">Tambahkan aturan potongan harga pada tier ini.</p>
                        </div>
                    @endif
                </div>

                {{-- Card Footer Actions --}}
                <div class="border-t border-gray-100 px-6 py-4 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-1">
                        <button wire:click="delete({{ $tier->id }})"
                            wire:confirm="Hapus tier '{{ $tier->name }}'? HP yang terhubung akan kehilangan tier-nya."
                            class="p-2 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                            title="Hapus Tier">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        <button wire:click="duplicate({{ $tier->id }})"
                            class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                            title="Duplikasi Tier">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                        </button>
                    </div>
                    <button wire:click="edit({{ $tier->id }})"
                        class="flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-gray-900 hover:bg-[#1c69d4] shadow-sm shadow-gray-900/10 hover:shadow-[#1c69d4]/30 rounded-lg transition-all">
                        Edit Aturan
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div
                class="col-span-1 md:col-span-2 xl:col-span-3 py-24 text-center border-2 border-dashed border-gray-200 rounded-3xl bg-gray-50">
                <div class="w-20 h-20 bg-white shadow-sm rounded-lg flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900">Belum Ada Tier Dibuat</h3>
                <p class="text-gray-500 mt-2 max-w-md mx-auto">Mulai dengan membuat tier untuk mengelompokkan HP dan
                    aturan potongannya.</p>
                <button wire:click="create"
                    class="mt-6 bg-[#1c69d4] text-white px-6 py-3 rounded-lg font-bold hover:shadow-sm hover:shadow-[#1c69d4]/40 transition inline-flex items-center gap-2">
                    Buat Tier Pertama
                </button>
            </div>
        @endforelse
    </div>

    {{-- ─────────────── MODAL FORM (INTERACTIVE RULES EDITOR) ─────────────── --}}
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 backdrop-blur-sm pt-10 pb-6 px-4 overflow-y-auto"
            wire:click.self="closeModal">
            <div
                class="bg-gray-50 rounded-[2rem] shadow-sm w-full max-w-3xl mx-auto overflow-hidden ring-1 ring-white/10">

                {{-- Modal Header --}}
                <div class="bg-white px-8 py-6 flex items-center justify-between border-b border-gray-100">
                    <div>
                        <span
                            class="inline-block px-3 py-1 bg-[#eff6ff] text-#1c69d4 rounded-lg text-xs font-black uppercase tracking-wider mb-2">Tier
                            Editor</span>
                        <h2 class="text-2xl font-black text-gray-900">
                            {{ $isEditMode ? 'Edit Aturan: ' . $name : 'Buat Tier Baru' }}
                        </h2>
                    </div>
                    <button wire:click="closeModal"
                        class="w-10 h-10 bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full flex items-center justify-center transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="store" class="p-8 space-y-8">

                    {{-- Info Dasar Tier --}}
                    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <h3
                            class="text-sm font-black text-gray-900 uppercase tracking-wide mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-#1c69d4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Informasi Aturan
                        </h3>
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Nama
                                    Aturan</label>
                                <input type="text" wire:model="name" placeholder="cth: IPHONE 17 PRO"
                                    class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all font-semibold text-gray-900">
                                @error('name')
                                    <span class="text-xs font-bold text-rose-500 mt-1.5 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Rules Editor Interaktif --}}
                    <div>
                        <div class="flex items-center justify-between mb-4 px-1">
                            <div>
                                <h3
                                    class="text-sm font-black text-gray-900 uppercase tracking-wide flex items-center gap-2">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    Kondisi Minus & Potongan
                                </h3>
                                <p class="text-xs font-medium text-gray-500 mt-1">Buat kategori (misal: Layar, Fisik)
                                    dan tentukan besaran potongannya.</p>
                            </div>
                            <button type="button" wire:click="addCategory"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-[#1c69d4] bg-white border border-[#1c69d4]/20 hover:bg-[#1c69d4]/5 hover:border-[#1c69d4]/40 rounded-lg transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Kategori
                            </button>
                        </div>

                        <div class="space-y-5">
                            @foreach ($ruleCategories as $catIndex => $catData)
                                <div
                                    class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden group">

                                    {{-- Category Header --}}
                                    <div
                                        class="flex items-center gap-3 px-5 py-4 bg-gray-50/80 border-b border-gray-100">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center font-black text-gray-400">
                                            {{ $catIndex + 1 }}
                                        </div>
                                        <input type="text" @if ($catIndex < 3) readonly @endif
                                            wire:model="ruleCategories.{{ $catIndex }}.category"
                                            placeholder="Nama Kategori (cth: Layar / Fisik / Kelengkapan)"
                                            class="flex-1 bg-transparent border-none py-1.5 px-2 text-base font-black text-gray-800 placeholder-gray-400 focus:ring-0">
                                        <button type="button" wire:click="removeCategory({{ $catIndex }})"
                                            class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition"
                                            title="Hapus Kategori">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Items --}}
                                    <div class="p-5 space-y-3 bg-white">
                                        @foreach ($catData['items'] as $itemIndex => $item)
                                            <div
                                                class="flex items-start md:items-center gap-3 flex-col md:flex-row group/item">
                                                {{-- Nama kondisi --}}
                                                <div class="flex-1 w-full relative">
                                                    <input type="text"
                                                        wire:model="ruleCategories.{{ $catIndex }}.items.{{ $itemIndex }}.name"
                                                        placeholder="Nama Kondisi Minus (cth: Retak Rambut)"
                                                        class="w-full bg-gray-50 border-transparent rounded-lg py-2.5 px-4 text-sm font-semibold focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all">
                                                </div>

                                                {{-- Tipe & Nilai potongan --}}
                                                <div class="flex items-center gap-2 w-full md:w-auto">
                                                    <select
                                                        wire:model="ruleCategories.{{ $catIndex }}.items.{{ $itemIndex }}.type"
                                                        class="bg-gray-50 border-transparent rounded-lg py-2.5 px-3 text-sm font-semibold focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all w-32 shrink-0">
                                                        <option value="fixed">Nominal (Rp)</option>
                                                        <option value="percentage">Persen (%)</option>
                                                    </select>

                                                    <div class="relative w-36 shrink-0">
                                                        <input type="number"
                                                            wire:model="ruleCategories.{{ $catIndex }}.items.{{ $itemIndex }}.value"
                                                            placeholder="0"
                                                            class="w-full bg-amber-50/50 border-transparent text-amber-900 rounded-lg py-2.5 px-4 font-bold focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-all text-sm">
                                                    </div>

                                                    <button type="button"
                                                        wire:click="removeItem({{ $catIndex }}, {{ $itemIndex }})"
                                                        class="w-10 h-10 flex items-center justify-center text-gray-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition md:opacity-0 group-hover/item:opacity-100"
                                                        title="Hapus Kondisi">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach

                                        <button type="button" wire:click="addItem({{ $catIndex }})"
                                            class="w-full mt-2 py-3 border-2 border-dashed border-gray-200 hover:border-indigo-300 hover:bg-[#eff6ff]/50 rounded-lg flex items-center justify-center gap-2 text-sm font-bold text-gray-500 hover:text-#1c69d4 transition-colors">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Tambah Kondisi Minus
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            @if (count($ruleCategories) === 0)
                                <div
                                    class="text-center py-8 bg-white rounded-lg border-2 border-dashed border-gray-200">
                                    <p class="text-sm font-bold text-gray-400 mb-4">Mulai dengan menambahkan kategori
                                        baru.</p>
                                    <button type="button" wire:click="addCategory"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-gray-900 hover:bg-[#1c69d4] rounded-lg transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Buat Kategori Pertama
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-6 border-t border-gray-200 flex gap-4">
                        <button type="button" wire:click="closeModal"
                            class="px-8 py-3 rounded-lg font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition w-full md:w-auto text-center">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 px-8 py-3 rounded-lg font-bold text-white bg-gradient-to-r from-[#1c69d4] to-[#7C74F0] hover:shadow-sm hover:shadow-[#1c69d4]/40 hover:-translate-y-0.5 transition-all text-center">
                            Simpan Perubahan Tier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
