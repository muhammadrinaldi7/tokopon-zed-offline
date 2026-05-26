<div>
    {{-- ─────────────── HEADER ─────────────── --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Template QC</h1>
            <p class="text-sm text-gray-500 mt-1">
                Kelola template checklist untuk proses Quality Control (QC).
            </p>
        </div>
        <button wire:click="create"
            class="bg-gradient-to-r from-[#1c69d4] to-[#7C74F0] text-white px-5 py-2.5 rounded-lg font-bold hover:shadow-sm hover:shadow-[#1c69d4]/40 hover:-translate-y-0.5 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Buat Template Baru
        </button>
    </div>

    {{-- ─────────────── TEMPLATE CARDS ─────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($templates as $template)
            <div
                class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-sm hover:shadow-indigo-900/5 transition-all duration-300 overflow-hidden flex flex-col group {{ !$template->is_active ? 'opacity-75 grayscale-[0.2]' : '' }}">

                {{-- Card Header --}}
                <div class="bg-gray-50/50 p-6 border-b border-gray-100 relative overflow-hidden">
                    {{-- Decorative blur --}}
                    <div
                        class="absolute -top-10 -right-10 w-32 h-32 bg-[#1c69d4]/5 rounded-full blur-2xl group-hover:bg-[#1c69d4]/10 transition-colors">
                    </div>

                    <div class="flex items-start justify-between relative z-10">
                        <div>
                            @if ($template->is_default)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-lg text-[10px] font-black uppercase tracking-widest mb-3 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> DEFAULT
                                </span>
                            @elseif ($template->brand_id)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 border border-indigo-200 text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-widest mb-3 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span> {{ strtoupper($template->brand->name) }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-gray-200 text-gray-500 rounded-lg text-[10px] font-black uppercase tracking-widest mb-3 shadow-sm">
                                    UMUM
                                </span>
                            @endif

                            <h2 class="text-2xl font-black text-gray-900 leading-tight mb-1">{{ $template->name }}</h2>
                            <p class="text-xs text-gray-500 font-medium">
                                {{ count($template->items ?? []) }} Item Pengecekan
                            </p>
                        </div>
                        <div class="flex flex-col gap-2">
                             @if (!$template->is_active)
                                <span class="bg-gray-100 text-gray-500 text-xs font-bold px-2.5 py-1 rounded-lg">Nonaktif</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Items Preview --}}
                <div class="p-6 flex-1 space-y-4 bg-white">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-[1px] bg-gray-200"></div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                Item Checklist
                            </p>
                            <div class="flex-1 h-[1px] bg-gray-100"></div>
                        </div>

                        <div class="grid grid-cols-1 gap-2">
                            @foreach (collect($template->items ?? [])->take(5) as $item)
                                <div class="flex items-center justify-between p-2.5 bg-gray-50/50 hover:bg-gray-50 border border-gray-100 rounded-lg transition-colors">
                                    <span class="text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full {{ $item['type'] === 'boolean' ? 'bg-[#1c69d4]' : 'bg-amber-400' }}"></div>
                                        {{ $item['name'] }}
                                    </span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $item['type'] === 'boolean' ? 'bg-[#eff6ff] text-[#1c69d4]' : 'bg-amber-50 text-amber-700' }} uppercase">
                                        {{ $item['type'] === 'boolean' ? 'Pass/Fail' : 'Teks' }}
                                    </span>
                                </div>
                            @endforeach

                            @if (count($template->items ?? []) > 5)
                                <div class="text-center py-2 text-xs font-bold text-gray-400">
                                    + {{ count($template->items) - 5 }} item lainnya...
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Footer Actions --}}
                <div class="border-t border-gray-100 px-6 py-4 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-1">
                        <button wire:click="delete({{ $template->id }})"
                            wire:confirm="Hapus template '{{ $template->name }}'? History QC yang sudah memakai template ini akan tetap tersimpan."
                            class="p-2 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                            title="Hapus Template">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        <button wire:click="duplicate({{ $template->id }})"
                            class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                            title="Duplikasi Template">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                        </button>
                    </div>
                    
                    <button wire:click="edit({{ $template->id }})"
                        class="flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-gray-900 hover:bg-[#1c69d4] shadow-sm shadow-gray-900/10 hover:shadow-[#1c69d4]/30 rounded-lg transition-all">
                        Edit Checklist
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div
                class="col-span-1 md:col-span-2 xl:col-span-3 py-24 text-center border-2 border-dashed border-gray-200 rounded-3xl bg-gray-50">
                <div class="w-20 h-20 bg-white shadow-sm rounded-lg flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900">Belum Ada Template QC</h3>
                <p class="text-gray-500 mt-2 max-w-md mx-auto">Mulai dengan membuat template checklist pengecekan fisik unit.</p>
                <button wire:click="create"
                    class="mt-6 bg-[#1c69d4] text-white px-6 py-3 rounded-lg font-bold hover:shadow-sm hover:shadow-[#1c69d4]/40 transition inline-flex items-center gap-2">
                    Buat Template Default (22 Item)
                </button>
            </div>
        @endforelse
    </div>

    {{-- ─────────────── MODAL FORM ─────────────── --}}
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 backdrop-blur-sm pt-10 pb-6 px-4 overflow-y-auto"
            wire:click.self="closeModal">
            <div class="bg-gray-50 rounded-[2rem] shadow-sm w-full max-w-3xl mx-auto overflow-hidden ring-1 ring-white/10">

                {{-- Modal Header --}}
                <div class="bg-white px-8 py-6 flex items-center justify-between border-b border-gray-100">
                    <div>
                        <span class="inline-block px-3 py-1 bg-[#eff6ff] text-[#1c69d4] rounded-lg text-xs font-black uppercase tracking-wider mb-2">
                            Checklist Editor
                        </span>
                        <h2 class="text-2xl font-black text-gray-900">
                            {{ $isEditMode ? 'Edit Template: ' . $name : 'Buat Template Baru' }}
                        </h2>
                    </div>
                    <button wire:click="closeModal"
                        class="w-10 h-10 bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full flex items-center justify-center transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="store" class="p-8 space-y-8">
                    {{-- Info Dasar --}}
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Nama Template *</label>
                            <input type="text" wire:model="name" placeholder="cth: Template Default" required
                                class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all font-semibold text-gray-900">
                            @error('name') <span class="text-xs font-bold text-rose-500 mt-1.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Khusus Brand (Opsional)</label>
                                <select wire:model="brand_id"
                                    class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all font-semibold text-gray-900">
                                    <option value="">-- Berlaku untuk Semua Brand --</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex flex-col gap-3 justify-end pb-1">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" wire:model="is_default" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#1c69d4]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1c69d4]"></div>
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block group-hover:text-[#1c69d4] transition-colors">Jadikan Default</span>
                                        <span class="text-xs text-gray-500 font-medium">Gunakan jika tidak ada template khusus brand.</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block group-hover:text-emerald-600 transition-colors">Template Aktif</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Checklist Items --}}
                    <div>
                        <div class="flex items-center justify-between mb-4 px-1">
                            <div>
                                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wide flex items-center gap-2">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    Item Pengecekan ({{ count($items) }})
                                </h3>
                                <p class="text-xs font-medium text-gray-500 mt-1">Daftar hal yang perlu di-cek saat QC (urutan bisa disesuaikan).</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2">
                            @foreach ($items as $index => $item)
                                <div class="flex items-center gap-3 bg-gray-50/50 p-2 rounded-lg border border-gray-100 group">
                                    <div class="flex flex-col items-center justify-center w-8 text-gray-400 cursor-move hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                                        <span class="text-[10px] font-black leading-none mt-0.5">{{ $index + 1 }}</span>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <input type="text" wire:model="items.{{ $index }}.name" placeholder="Nama Pengecekan (cth: LCD, Baterai, Face ID)" required
                                            class="w-full bg-white border border-gray-200 rounded-lg py-2 px-3 text-sm font-semibold focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] transition-all">
                                    </div>

                                    <div class="w-40 shrink-0">
                                        <select wire:model="items.{{ $index }}.type"
                                            class="w-full bg-white border border-gray-200 rounded-lg py-2 px-3 text-sm font-semibold focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4] transition-all">
                                            <option value="boolean">Pilihan: OK / NOT OK</option>
                                            <option value="text">Input Teks Bebas</option>
                                        </select>
                                    </div>

                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition opacity-0 group-hover:opacity-100" title="Hapus Item">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            @endforeach

                            <button type="button" wire:click="addItem"
                                class="w-full mt-2 py-3 border-2 border-dashed border-gray-200 hover:border-[#1c69d4]/40 hover:bg-[#eff6ff]/50 rounded-lg flex items-center justify-center gap-2 text-sm font-bold text-gray-500 hover:text-[#1c69d4] transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Item Baru
                            </button>
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
                            Simpan Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
