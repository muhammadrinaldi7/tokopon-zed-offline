<div class="p-8">
    <div class="bg-linear-to-r from-[#4E44DB] via-[#6355F6] to-[#766bf2] rounded-4xl p-8 text-white mb-8 shadow-xl shadow-[#4E44DB]/15 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-white/5 rounded-full blur-2xl"></div>
        <div class="absolute -left-10 -top-10 w-44 h-44 bg-white/5 rounded-full blur-2xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black tracking-tight mb-2">Template Quality Control (QC)</h1>
                <p class="text-indigo-100 text-sm font-medium">Atur formulir pengecekan perangkat berdasarkan Merek (Brand) agar dinamis dan fleksibel.</p>
            </div>
            <button wire:click="create" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold px-6 py-3 rounded-2xl transition-all shadow-sm flex items-center gap-2 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Template Baru
            </button>
        </div>
    </div>

    @if(!$showForm)
    <!-- LIST VIEW -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden p-6 mb-8">
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-center mb-6">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama template..." class="w-full sm:w-72 bg-gray-50 border border-gray-200 rounded-2xl px-5 py-3 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($templates as $tmpl)
            <div class="border {{ $tmpl->is_default ? 'border-indigo-200 bg-indigo-50/30' : 'border-gray-100 bg-white' }} rounded-2xl p-5 hover:shadow-lg hover:-translate-y-1 transition-all group relative">
                @if($tmpl->is_default)
                    <div class="absolute -top-3 -right-3 bg-gradient-to-r from-amber-400 to-amber-500 text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-sm flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        DEFAULT
                    </div>
                @endif
                
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">{{ $tmpl->name }}</h3>
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            Brand: <span class="font-semibold text-indigo-600">{{ $tmpl->brand->name ?? 'Semua Merek' }}</span>
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer" title="Aktif/Nonaktif">
                        <input type="checkbox" class="sr-only peer" wire:click="toggleActive({{ $tmpl->id }})" {{ $tmpl->is_active ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>
                
                @php
                    $itemCount = is_array($tmpl->items) ? count($tmpl->items) : (is_array(json_decode($tmpl->items, true)) ? count(json_decode($tmpl->items, true)) : 0);
                @endphp
                <div class="bg-gray-50 rounded-xl p-3 mb-4 flex items-center gap-3">
                    <div class="bg-white w-10 h-10 rounded-lg shadow-sm flex items-center justify-center text-indigo-600 font-bold">
                        {{ $itemCount }}
                    </div>
                    <div class="text-xs text-gray-500">
                        Item Pengecekan<br>
                        terdaftar pada form ini
                    </div>
                </div>

                <div class="flex gap-2 border-t border-gray-100 pt-4">
                    <button wire:click="edit({{ $tmpl->id }})" class="flex-1 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:text-indigo-600 font-semibold py-2 px-3 rounded-xl text-xs transition-colors text-center">
                        Edit Form
                    </button>
                    @if(!$tmpl->is_default)
                        <button wire:click="setAsDefault({{ $tmpl->id }})" wire:confirm="Jadikan template ini sebagai default fallback jika brand tidak ditemukan?" class="flex-1 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-semibold py-2 px-3 rounded-xl text-xs transition-colors text-center">
                            Set Default
                        </button>
                    @endif
                    <button wire:click="delete({{ $tmpl->id }})" wire:confirm="Hapus template ini secara permanen?" class="w-10 flex items-center justify-center bg-white border border-gray-200 text-red-500 hover:bg-red-50 hover:border-red-100 font-semibold rounded-xl transition-colors" title="Hapus">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="col-span-full py-16 text-center bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <h3 class="text-gray-800 font-bold mb-1">Belum Ada Template</h3>
                <p class="text-sm text-gray-500 mb-4">Silakan buat template QC pertama Anda.</p>
                <button wire:click="create" class="text-indigo-600 font-bold text-sm hover:underline">Buat Template Baru</button>
            </div>
            @endforelse
        </div>
        
        <div class="mt-6">
            {{ $templates->links() }}
        </div>
    </div>
    @else
    <!-- FORM VIEW -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8">
        <div class="p-8 border-b border-gray-50 bg-gray-50/30 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $templateId ? 'Edit Template QC' : 'Buat Template QC Baru' }}</h2>
                <p class="text-xs text-gray-500 mt-1">Definisikan kategori dan nama item yang akan dicek saat inspeksi.</p>
            </div>
            <button wire:click="closeForm" class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 pb-8 border-b border-gray-100">
                <div class="md:col-span-1 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Nama Template <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" placeholder="Cth: iPhone QC Standar">
                        @error('name') <span class="text-xs text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Terapkan Pada Brand</label>
                        <select wire:model="brand_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                            <option value="">-- Bebas (Umum) --</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">Kosongkan jika template ini untuk semua brand.</p>
                        @error('brand_id') <span class="text-xs text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-3 cursor-pointer p-3 bg-amber-50/50 border border-amber-100 rounded-xl hover:bg-amber-50 transition-colors">
                            <input wire:model="is_default" type="checkbox" class="w-5 h-5 text-amber-500 bg-white border-gray-300 rounded focus:ring-amber-500 focus:ring-2">
                            <div>
                                <span class="block text-sm font-bold text-gray-800">Jadikan Default</span>
                                <span class="block text-[10px] text-gray-500">Digunakan jika brand tidak cocok dengan template lain</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 mb-4 uppercase tracking-wide">Daftar Item Pengecekan</label>
                    
                    <div class="space-y-3 mb-6 max-h-96 overflow-y-auto pr-2 rounded-xl">
                        @foreach($items as $index => $item)
                        <div class="flex gap-3 items-center bg-gray-50 border border-gray-200 p-3 rounded-xl hover:border-indigo-300 transition-colors">
                            <div class="w-8 h-8 shrink-0 bg-white rounded-lg flex items-center justify-center font-black text-gray-400 text-xs border border-gray-100">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 grid grid-cols-3 gap-3">
                                <div class="col-span-1">
                                    <span class="block text-[10px] text-gray-400 font-bold mb-1">KATEGORI</span>
                                    <input wire:model="items.{{ $index }}.category" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:border-indigo-500 focus:ring-0">
                                </div>
                                <div class="col-span-1">
                                    <span class="block text-[10px] text-gray-400 font-bold mb-1">NAMA PENGECEKAN</span>
                                    <input wire:model="items.{{ $index }}.name" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-800 focus:border-indigo-500 focus:ring-0">
                                </div>
                                <div class="col-span-1">
                                    <span class="block text-[10px] text-gray-400 font-bold mb-1">TIPE INPUT</span>
                                    <select wire:model="items.{{ $index }}.type" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:border-indigo-500 focus:ring-0">
                                        <option value="boolean">Pass/Fail (Boolean)</option>
                                        <option value="text">Teks / Angka</option>
                                    </select>
                                </div>
                            </div>
                            <button wire:click="removeItem({{ $index }})" class="shrink-0 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Item">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                        @endforeach

                        @if(empty($items))
                            <div class="text-center py-8 bg-red-50 border border-dashed border-red-200 rounded-xl">
                                <p class="text-red-500 text-sm font-bold">Belum ada item pengecekan.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Add New Item Form -->
                    <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4">
                        <h4 class="text-xs font-bold text-indigo-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Tambah Item Baru
                        </h4>
                        
                        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                            <div class="w-full sm:w-1/3">
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">Kategori</label>
                                <input wire:model="newItemCategory" type="text" list="categoryList" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:ring-0" placeholder="Pilih / ketik kategori...">
                                <datalist id="categoryList">
                                    @foreach($availableCategories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                                @error('newItemCategory') <span class="text-[10px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="w-full sm:w-1/3">
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">Nama Pengecekan</label>
                                <input wire:model="newItemName" wire:keydown.enter="addItem" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:ring-0" placeholder="Cth: Layar Retak">
                                @error('newItemName') <span class="text-[10px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="w-full sm:w-1/4">
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">Tipe Input</label>
                                <select wire:model="newItemType" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:ring-0">
                                    <option value="boolean">Pass/Fail</option>
                                    <option value="text">Teks Bebas</option>
                                </select>
                            </div>

                            <button wire:click="addItem" type="button" class="w-full sm:w-auto bg-[#4E44DB] hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition-colors shadow-md shadow-indigo-500/20">
                                Tambah
                            </button>
                        </div>
                        @error('items') <span class="text-xs text-red-500 font-bold mt-3 block text-center bg-red-100 py-2 rounded-lg">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button wire:click="closeForm" class="px-6 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button wire:click="save" class="px-8 py-2.5 bg-[#4E44DB] hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Simpan Template
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
