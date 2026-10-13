<div class="p-4 sm:p-6 min-h-screen bg-neutral-100">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Manajemen Banner Promo Carousel'])

    {{-- Controls & Content --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Table Toolbar --}}
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-extrabold text-gray-900">Daftar Banner Beranda Mobile</h3>
                <p class="text-xs text-gray-500 mt-0.5">Banner otomatis bergeser di bagian paling atas aplikasi smartphone pembeli.</p>
            </div>
            <button wire:click="openModal"
                class="px-4 py-2.5 bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Tambah Banner Baru</span>
            </button>
        </div>

        {{-- Table Banners --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Urutan</th>
                        <th class="px-5 py-3.5">Preview Banner</th>
                        <th class="px-5 py-3.5">Judul & Toko</th>
                        <th class="px-5 py-3.5">Aksi Klik (Target)</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($banners as $banner)
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-5 py-4 font-mono font-bold text-gray-400">
                                #{{ $banner->sort_order }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="w-36 h-18 rounded-xl overflow-hidden border border-gray-200 bg-gray-100 shadow-2xs">
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-900 text-sm">{{ $banner->title }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    Unit: <strong class="text-indigo-600">{{ $banner->businessUnit?->name ?? 'Semua Toko' }}</strong>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-medium text-gray-600">
                                @if($banner->target_type === 'none')
                                    <span class="text-gray-400">Hanya Gambar (Tanpa Link)</span>
                                @else
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 font-bold rounded text-[10px] uppercase">
                                        {{ $banner->target_type }}: {{ $banner->target_value }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <button wire:click="toggleActive({{ $banner->id }})"
                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $banner->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-400' }}">
                                    {{ $banner->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="edit({{ $banner->id }})" class="px-2.5 py-1 text-gray-600 hover:text-indigo-600 font-bold hover:bg-gray-100 rounded-lg transition-colors">
                                        Edit
                                    </button>
                                    <button wire:click="delete({{ $banner->id }})" onclick="return confirm('Hapus banner ini?') || event.stopImmediatePropagation()"
                                        class="px-2.5 py-1 text-rose-500 hover:text-rose-700 font-bold hover:bg-rose-50 rounded-lg transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">
                                Belum ada banner promo. Klik "Tambah Banner Baru" untuk memasang banner carousel.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL TAMBAH / EDIT BANNER --}}
    @if($showModal)
        <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg p-6 space-y-4" @click.away="$wire.set('showModal', false)">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <h3 class="text-base font-extrabold text-gray-900">{{ $bannerId ? 'Edit Banner' : 'Tambah Banner Promo' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form wire:submit.prevent="save" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Judul Banner</label>
                        <input type="text" wire:model="title" placeholder="Contoh: Promo Gajian Diskon 10%"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 outline-none">
                        @error('title') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Unggah Gambar Banner (Rasio 16:9 / Landscape)</label>
                        <input type="file" wire:model="imageFile" accept="image/*"
                            class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('imageFile') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror

                        @if ($imageFile)
                            <div class="mt-2 text-[11px] text-emerald-600 font-bold">Preview Gambar Baru:</div>
                            <img src="{{ $imageFile->temporaryUrl() }}" class="mt-1 h-24 rounded-xl object-cover border border-gray-200">
                        @elseif ($existingImageUrl)
                            <div class="mt-2 text-[11px] text-gray-400">Gambar Saat Ini:</div>
                            <img src="{{ $existingImageUrl }}" class="mt-1 h-24 rounded-xl object-cover border border-gray-200">
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Toko / Unit Bisnis</label>
                            <select wire:model="business_unit_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold">
                                <option value="">Semua Toko</option>
                                @foreach($businessUnits as $bu)
                                    <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Urutan Prioritas</label>
                            <input type="number" wire:model="sort_order" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tindakan Saat Diklik</label>
                            <select wire:model="target_type" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold">
                                <option value="none">Tidak Ada (Hanya Gambar)</option>
                                <option value="category">Buka Kategori</option>
                                <option value="brand">Buka Brand</option>
                                <option value="product">Buka Produk Tertentu (ID)</option>
                                <option value="url">Buka Link Web Luar</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nilai / Keyword Target</label>
                            <input type="text" wire:model="target_value" placeholder="Contoh: HandPhone / Apple / https://..."
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl text-xs font-bold shadow-xs">Simpan Banner</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
