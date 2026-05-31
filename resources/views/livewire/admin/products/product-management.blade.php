<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Produk</h1>
        <div class="flex gap-2">
            <button wire:click="$set('showImportModal', true)"
                class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-emerald-100 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import CSV
            </button>
            <button wire:click="create"
                class="bg-[#1c69d4] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-opacity-90 transition">
                Tambah Produk
            </button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[200px] relative">
            <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari nama produk..."
                class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-200 text-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
        <select wire:model.live="filterCategory"
            class="rounded-lg border-gray-200 text-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all py-2.5 px-4 min-w-[150px]">
            <option value="">Semua Kategori</option>
            @foreach ($categoriesList as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterBrand"
            class="rounded-lg border-gray-200 text-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all py-2.5 px-4 min-w-[150px]">
            <option value="">Semua Brand</option>
            @foreach ($brandsList as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4">Status Accurate</th>
                        {{-- <th class="px-6 py-4">Total Stok</th>
                    <th class="px-6 py-4">Harga Termurah</th> --}}
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden shrink-0 border border-gray-100">
                                        @if ($product->hasMedia('cover'))
                                            <img src="{{ $product->getFirstMediaUrl('cover', 'thumb') }}"
                                                class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <button wire:click="viewDetail({{ $product->id }})"
                                            class="font-bold text-[#1c69d4] hover:text-[#3f36b8] hover:underline text-left transition-colors truncate max-w-[200px] md:max-w-[300px]"
                                            title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($product->has_active_accurate)
                                    <span
                                        class="bg-emerald-100 text-emerald-700 font-bold px-2.5 py-1 rounded-full text-xs">Aktif
                                        ✓</span>
                                @else
                                    <span
                                        class="bg-gray-100 text-gray-600 font-bold px-2.5 py-1 rounded-full text-xs">Belum
                                        Link</span>
                                @endif
                            </td>
                            {{-- <td class="px-6 py-4">{{ $product->total_stock }} Unit</td>
                        <td class="px-6 py-4">Rp. {{ number_format($product->starting_price ?? 0, 0, ',', '.') }}</td> --}}
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.products.variants', $product->slug) }}" wire:navigate
                                    class="text-[#1c69d4] font-semibold text-xs border border-[#1c69d4] px-3 py-1.5 rounded-lg hover:bg-[#eff2ff] mr-2 transition inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                    </svg>
                                    Varian
                                </a>
                                <button wire:click="edit({{ $product->id }})"
                                    class="text-gray-500 hover:text-gray-800 transition mr-2">
                                    Edit
                                </button>
                                <button wire:click="confirmDelete({{ $product->id }})"
                                    class="text-rose-500 hover:text-rose-700 transition">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada produk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Create/Edit --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0 transition-opacity"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Backdrop with blur --}}
            <div wire:click="$set('showModal', false)"
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity"></div>

            {{-- Modal Panel --}}
            <div
                class="relative transform overflow-hidden rounded-4xl bg-white/80 backdrop-blur-2xl border border-white shadow-sm shadow-[#1c69d4]/15 text-left transition-all sm:my-8 sm:w-full sm:max-w-md">

                {{-- Header --}}
                <div
                    class="px-6 py-5 border-b border-gray-200/50 flex justify-between items-center backdrop-blur-md bg-white/40">
                    <h2 class="text-[17px] font-semibold tracking-tight text-gray-900">
                        {{ $isEditing ? 'Edit Produk Utama' : 'Tambah Produk Baru' }}</h2>
                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-gray-600 bg-gray-100/50 hover:bg-gray-200/50 rounded-full p-1.5 transition-colors focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form Body --}}
                <form wire:submit.prevent="store" class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                    {{-- Photo Section --}}


                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Nama Produk Utama</label>
                        <input type="text" wire:model="name" placeholder="Contoh: iPhone 15 Pro Max"
                            class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800 placeholder-gray-400"
                            required>
                        @error('name')
                            <span class="text-xs text-rose-500 font-medium ml-1 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Kategori Produk <span
                                    class="text-rose-500">*</span></label>
                            <select wire:model="categoryId"
                                class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800"
                                required>
                                <option value="">Pilih Kategori...</option>
                                @foreach ($categoriesList as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('categoryId')
                                <span class="text-xs text-rose-500 font-medium ml-1 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Merek (opsional)</label>
                            <select wire:model="brandId"
                                class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800">
                                <option value="">Tanpa Merek / Lainnya...</option>
                                @foreach ($brandsList as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('brandId')
                                <span class="text-xs text-rose-500 font-medium ml-1 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Deskripsi Singkat</label>
                        <textarea wire:model="description" rows="3"
                            class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800 placeholder-gray-400 resize-none"
                            placeholder="(Opsional) Masukkan deskripsi produk..."></textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 ml-1">Cover Produk</label>
                            <div class="flex items-center gap-4">
                                @if ($coverImage)
                                    <div
                                        class="w-20 h-20 rounded-lg overflow-hidden border-2 border-[#1c69d4] shadow-sm shrink-0">
                                        <img src="{{ $coverImage->temporaryUrl() }}"
                                            class="w-full h-full object-cover">
                                    </div>
                                @elseif($currentCoverUrl)
                                    <div
                                        class="w-20 h-20 rounded-lg overflow-hidden border border-gray-200 shadow-sm shrink-0">
                                        <img src="{{ $currentCoverUrl }}" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div
                                        class="w-20 h-20 rounded-lg bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center shrink-0">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif

                                <div class="flex-1">
                                    <label
                                        class="relative cursor-pointer bg-white rounded-lg border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-colors inline-block overflow-hidden">
                                        <span>{{ $coverImage ? 'Ganti Cover' : ($currentCoverUrl ? 'Ubah Cover' : 'Upload Cover') }}</span>
                                        <input type="file" wire:model="coverImage" class="sr-only"
                                            accept="image/*">
                                    </label>
                                    <p class="text-[10px] text-gray-400 mt-1">Format: JPG, PNG, WEBP (Maks. 2MB)</p>
                                    <div wire:loading wire:target="coverImage"
                                        class="text-[10px] text-[#1c69d4] font-bold mt-1">Mengupload...</div>
                                </div>
                            </div>
                            @error('coverImage')
                                <span class="text-xs text-rose-500 font-medium ml-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 ml-1">Galeri Foto (Bisa
                                banyak)</label>
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($currentGallery as $media)
                                    <div class="relative group w-16 h-16">
                                        <img src="{{ $media['url'] }}"
                                            class="w-full h-full object-cover rounded-lg border border-gray-100">
                                        <button type="button" wire:click="removeGalleryImage({{ $media['id'] }})"
                                            wire:confirm="Hapus gambar ini dari galeri?"
                                            class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach

                                @if ($galleryImages)
                                    @foreach ($galleryImages as $index => $img)
                                        <div
                                            class="w-16 h-16 rounded-lg border-2 border-emerald-400 overflow-hidden relative">
                                            <img src="{{ $img->temporaryUrl() }}" class="w-full h-full object-cover">
                                            <div class="absolute inset-0 bg-emerald-400/20"></div>
                                        </div>
                                    @endforeach
                                @endif

                                <label
                                    class="w-16 h-16 rounded-lg border-2 border-dashed border-gray-200 hover:border-[#1c69d4] hover:bg-[#eff2ff]/50 flex items-center justify-center cursor-pointer transition-all">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    <input type="file" wire:model="galleryImages" class="sr-only" multiple
                                        accept="image/*">
                                </label>
                            </div>
                            @error('galleryImages.*')
                                <span class="text-xs text-rose-500 font-medium ml-1 block">{{ $message }}</span>
                            @enderror
                            <div wire:loading wire:target="galleryImages"
                                class="text-[10px] text-[#1c69d4] font-bold">Mengupload Galeri...</div>
                        </div>
                    </div>
                    {{-- Dynamic Specifications --}}
                    <div class="pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2 ml-1">
                            <label class="block text-sm font-medium text-gray-700">Spesifikasi Master</label>
                            <button type="button" wire:click="addSpecification"
                                class="text-xs font-bold text-[#1c69d4] hover:text-[#3f36b8] bg-[#eff2ff] px-2 py-1 rounded-lg transition-colors">
                                + Tambah Atribut
                            </button>
                        </div>

                        <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                            @forelse($specifications as $index => $spec)
                                <div class="flex gap-2 items-center">
                                    <input type="text" wire:model="specifications.{{ $index }}.key"
                                        placeholder="Ex: Kamera"
                                        class="w-1/3 text-[13px] bg-white/60 border border-gray-200/70 focus:border-[#1c69d4] rounded-lg px-3 py-2 shadow-sm transition-all"
                                        required>
                                    <input type="text" wire:model="specifications.{{ $index }}.value"
                                        placeholder="Ex: 48 MP Mumpuni"
                                        class="flex-1 text-[13px] bg-white/60 border border-gray-200/70 focus:border-[#1c69d4] rounded-lg px-3 py-2 shadow-sm transition-all"
                                        required>
                                    <button type="button" wire:click="removeSpecification({{ $index }})"
                                        class="text-rose-400 hover:text-rose-600 p-1.5 rounded-lg hover:bg-rose-50 transition-colors focus:outline-none">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <div
                                    class="text-[13px] text-gray-400 italic text-center py-2 bg-gray-50/50 rounded-lg border border-dashed border-gray-200">
                                    Belum ada spesifikasi tambahan.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-4 flex gap-3">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="flex-1 bg-gray-100/50 hover:bg-gray-200/70 text-gray-700 py-3 rounded-lg text-[15px] font-semibold transition-all">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 bg-[#1c69d4] text-white py-3 rounded-lg text-[15px] font-semibold hover:bg-[#3f36b8] hover:shadow-sm hover:shadow-[#1c69d4]/30 active:scale-[0.98] transition-all">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Detail --}}
    @if ($showDetailModal && $detailProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0 transition-opacity"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div wire:click="$set('showDetailModal', false)"
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity"></div>

            <div
                class="relative transform overflow-hidden rounded-4xl bg-white/80 backdrop-blur-2xl border border-white shadow-sm shadow-[#1c69d4]/15 text-left transition-all sm:my-8 w-full max-w-lg">
                {{-- Header --}}
                <div
                    class="px-6 py-5 border-b border-gray-200/50 flex justify-between items-center backdrop-blur-md bg-white/40">
                    <h2 class="text-[17px] font-semibold tracking-tight text-gray-900">Detail Produk</h2>
                    <button wire:click="$set('showDetailModal', false)"
                        class="text-gray-400 hover:text-gray-600 bg-gray-100/50 hover:bg-gray-200/50 rounded-full p-1.5 transition-colors focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <div>
                        <div class="flex flex-col sm:flex-row gap-5 mb-5 mt-2">
                            @if ($detailProduct->hasMedia('cover'))
                                <div
                                    class="w-32 h-32 rounded-3xl overflow-hidden border border-gray-200 shadow-sm shrink-0">
                                    <img src="{{ $detailProduct->getFirstMediaUrl('cover') }}"
                                        class="w-full h-full object-cover">
                                </div>
                            @endif
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1.5">
                                    <h3 class="text-xl font-bold text-[#1c69d4]">{{ $detailProduct->name }}</h3>
                                    @if ($detailProduct->has_active_accurate)
                                        <span
                                            class="bg-emerald-100 text-emerald-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase tracking-wider shrink-0">Terkoneksi
                                            Accurate</span>
                                    @endif
                                </div>
                                <div class="flex gap-2 mb-3">
                                    <span
                                        class="bg-[#eff2ff] text-[#1c69d4] px-2.5 py-1 rounded-lg text-[11px] font-bold tracking-wide uppercase">{{ $detailProduct->category?->name ?? 'Tanpa Kategori' }}</span>
                                    @if ($detailProduct->brand)
                                        <span
                                            class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-lg text-[11px] font-bold tracking-wide uppercase">{{ $detailProduct->brand->name }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 leading-relaxed">
                                    {{ $detailProduct->description ?: 'Tidak ada deskripsi.' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Gallery Slider/List in Detail --}}
                    @if ($detailProduct->hasMedia('gallery'))
                        <div class="pt-2">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 ml-1">Galeri
                                Produk</h4>
                            <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
                                @foreach ($detailProduct->getMedia('gallery') as $image)
                                    <a href="{{ $image->getUrl() }}" target="_blank"
                                        class="w-24 h-24 rounded-lg overflow-hidden border border-gray-100 shadow-sm shrink-0 hover:scale-105 transition-transform">
                                        <img src="{{ $image->getUrl('thumb') }}" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div class="bg-[#eff2ff]/50 p-4 rounded-lg border border-[#1c69d4]/10">
                            <p class="text-xs text-[#1c69d4] font-bold tracking-wide uppercase mb-1">Total Stok</p>
                            <p class="text-2xl font-black text-gray-800">{{ $detailProduct->total_stock }} <span
                                    class="text-sm font-medium text-gray-500">Unit</span></p>
                        </div>
                        <div class="bg-emerald-50/50 p-4 rounded-lg border border-emerald-100">
                            <p class="text-xs text-emerald-600 font-bold tracking-wide uppercase mb-1">Harga Mulai</p>
                            <p class="text-2xl font-black text-gray-800"><span
                                    class="text-sm font-medium text-gray-500 mr-1">Rp</span>{{ number_format($detailProduct->starting_price ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    @if ($detailProduct->specifications && count($detailProduct->specifications) > 0)
                        <div class="pt-2">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 ml-1">Spesifikasi
                                Master</h4>
                            <div class="space-y-2">
                                @foreach ($detailProduct->specifications as $key => $value)
                                    <div
                                        class="flex justify-between items-center py-2.5 px-4 bg-white/60 rounded-lg border border-gray-100 shadow-sm">
                                        <span class="text-[14px] font-bold text-[#1c69d4]">{{ $key }}</span>
                                        <span
                                            class="text-[14px] font-medium text-gray-700 text-right">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="pt-2">
                            <div
                                class="text-[13px] text-gray-400 italic text-center py-4 bg-gray-50/50 rounded-lg border border-dashed border-gray-200">
                                Produk ini belum memiliki spesifikasi tersimpan.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Import CSV --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-0 transition-opacity"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div wire:click="$set('showImportModal', false)"
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity"></div>

            <div
                class="relative transform overflow-hidden rounded-4xl bg-white/80 backdrop-blur-2xl border border-white shadow-sm shadow-[#1c69d4]/15 text-left transition-all sm:my-8 w-full max-w-lg">
                <div
                    class="px-6 py-5 border-b border-gray-200/50 flex justify-between items-center backdrop-blur-md bg-white/40">
                    <h2 class="text-[17px] font-semibold tracking-tight text-gray-900">Import Produk & Varian (CSV)
                    </h2>
                    <button wire:click="$set('showImportModal', false)"
                        class="text-gray-400 hover:text-gray-600 bg-gray-100/50 hover:bg-gray-200/50 rounded-full p-1.5 transition-colors focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="importCsv" class="p-6 space-y-5">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <h3 class="text-sm font-bold text-blue-800 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Panduan Import
                        </h3>
                        <p class="text-xs text-blue-700 leading-relaxed mb-3">
                            Pastikan format kolom sesuai dengan kerangka standar (template). Baris dengan nama produk
                            yang sama otomatis dikelompokkan ke satu induk. Kode Accurate yang tidak ditemukan akan
                            diabaikan (dibiarkan kosong).
                        </p>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button type="button" wire:click="downloadTemplateCsv" wire:loading.attr="disabled"
                                class="bg-white border border-blue-200 text-blue-700 text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm hover:bg-blue-100 transition inline-flex items-center justify-center gap-1.5 w-full sm:w-auto">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Template CSV
                            </button>
                            <button type="button" wire:click="exportAccurateDataCsv" wire:loading.attr="disabled"
                                class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm hover:bg-emerald-100 transition inline-flex items-center justify-center gap-1.5 w-full sm:w-auto">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Ekspor Master Accurate
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Upload File CSV</label>
                        <input type="file" wire:model="importFile" accept=".csv"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer bg-gray-50 border border-gray-200 rounded-xl p-1"
                            required>
                        @error('importFile')
                            <span class="text-xs text-rose-500 font-medium ml-1 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" wire:click="$set('showImportModal', false)"
                            class="flex-1 bg-gray-100/50 hover:bg-gray-200/70 text-gray-700 py-3 rounded-lg text-[15px] font-semibold transition-all">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="flex-1 bg-[#1c69d4] text-white py-3 rounded-lg text-[15px] font-semibold hover:bg-[#3f36b8] hover:shadow-sm hover:shadow-[#1c69d4]/30 active:scale-[0.98] transition-all disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="importCsv">Mulai Import</span>
                            <span wire:loading wire:target="importCsv">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
