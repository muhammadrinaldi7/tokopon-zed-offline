<div class="p-4 sm:p-6 min-h-screen bg-neutral-100">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Kurasi & Foto Produk Mobile'])

    {{-- Content Area --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Toolbar --}}
        <div class="p-5 border-b border-gray-100 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                        <span>Produk Katalog & Foto Mobile</span>
                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-black rounded-lg border border-emerald-200">
                            Storage: Cloudflare R2
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Anda dapat mengunggah atau mengganti foto produk langsung dari sini. Foto otomatis tersimpan di Cloudflare R2 CDN dan langsung tampil di mobile app.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.products') }}" target="_blank"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1">
                        <span>📱 Katalog Baru (Syihab)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="{{ route('admin.second-products') }}" target="_blank"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1">
                        <span>🔄 Katalog Second (GSK)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <span class="px-3 py-1.5 bg-sky-50 text-sky-800 rounded-xl text-xs font-bold border border-sky-200">
                        {{ $onlineWarehouseCount }} Gudang Online
                    </span>
                </div>
            </div>

            {{-- Filter Rows --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama produk atau SKU..."
                    class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 outline-none">

                <select wire:model.live="selectedBusinessUnitId" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700">
                    <option value="">Semua Toko Mobile</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="selectedCategory" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Products Grid --}}
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                @forelse($products as $prod)
                    @php
                        $hasMedia = false;
                        $imgUrl = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
                        if ($prod->hasMedia('cover')) {
                            $imgUrl = $prod->getFirstMediaUrl('cover');
                            $hasMedia = true;
                        } elseif ($prod->product && $prod->product->hasMedia('cover')) {
                            $imgUrl = $prod->product->getFirstMediaUrl('cover');
                            $hasMedia = true;
                        } elseif ($prod->productVariants && $prod->productVariants->isNotEmpty()) {
                            $first = $prod->productVariants->first();
                            if ($first && $first->hasMedia('variant_image')) {
                                $imgUrl = $first->getFirstMediaUrl('variant_image');
                                $hasMedia = true;
                            }
                        } elseif ($prod->secondProductVariants && $prod->secondProductVariants->isNotEmpty()) {
                            $firstSec = $prod->secondProductVariants->first();
                            if ($firstSec && $firstSec->hasMedia('variant_image')) {
                                $imgUrl = $firstSec->getFirstMediaUrl('variant_image');
                                $hasMedia = true;
                            }
                        }
                        $onlineStock = $prod->warehouseStocks->sum('stock');
                    @endphp
                    <div class="bg-gray-50/70 border border-gray-200 rounded-2xl p-3 flex flex-col justify-between hover:bg-white hover:shadow-md transition-all group">
                        <div>
                            {{-- Image Container --}}
                            <div class="relative w-full aspect-square bg-gray-100 rounded-xl overflow-hidden mb-2.5 border border-gray-200 group-hover:border-indigo-300 transition-colors">
                                <img src="{{ $imgUrl }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @if($hasMedia)
                                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 bg-sky-600 text-white rounded text-[9px] font-black uppercase shadow-xs">
                                        R2 CDN
                                    </span>
                                @else
                                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 bg-amber-500 text-white rounded text-[9px] font-bold shadow-xs">
                                        Placeholder
                                    </span>
                                @endif
                            </div>

                            <span class="text-[10px] font-bold text-indigo-600 uppercase">{{ $prod->brandName ?? 'Gadget' }}</span>
                            <h4 class="text-xs font-bold text-gray-900 line-clamp-2 leading-snug mt-0.5" title="{{ $prod->name }}">{{ $prod->name }}</h4>
                        </div>

                        <div class="pt-2 mt-2 border-t border-gray-200/60">
                            <div class="text-xs font-black text-gray-900">
                                Rp {{ number_format($prod->base_price, 0, ',', '.') }}
                            </div>
                            <div class="flex items-center justify-between text-[10px] mt-1 text-gray-500">
                                <span>Stok: <strong class="{{ $onlineStock > 0 ? 'text-emerald-700' : 'text-rose-600' }} font-bold">{{ $onlineStock }}</strong></span>
                                <span class="truncate max-w-[65px] font-mono font-medium">{{ $prod->businessUnit?->code }}</span>
                            </div>

                            {{-- Tombol Aksi Upload / Edit Foto Cepat --}}
                            <button type="button" wire:click="openUploadModal({{ $prod->id }})" wire:loading.attr="disabled"
                                class="mt-2.5 w-full py-1.5 px-2 bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 rounded-xl text-[11px] font-extrabold flex items-center justify-center gap-1 transition-all duration-200 shadow-xs disabled:opacity-50">
                                <span wire:loading.remove wire:target="openUploadModal({{ $prod->id }})" class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ $hasMedia ? 'Ganti Foto' : 'Upload Foto' }}</span>
                                </span>
                                <span wire:loading wire:target="openUploadModal({{ $prod->id }})" class="flex items-center gap-1.5 text-indigo-600">
                                    <svg class="animate-spin w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Membuka...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center text-gray-400 text-xs">
                        Tidak ada produk yang cocok dengan pencarian dan filter Anda.
                    </div>
                @endforelse
            </div>

            {{-- Pagination Links --}}
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL UPLOAD FOTO KE CLOUDFLARE R2 --}}
    @if($showUploadModal && $selectedProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity animate-in fade-in"
            x-data="{
                isUploading: false,
                progress: 0,
                localPreview: null
            }"
            x-on:livewire-upload-start="isUploading = true; progress = 0"
            x-on:livewire-upload-finish="isUploading = false"
            x-on:livewire-upload-error="isUploading = false"
            x-on:livewire-upload-progress="progress = $event.detail.progress">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-gray-100 overflow-hidden" @click.outside="if(!isUploading) $wire.closeUploadModal()">
                {{-- Header Modal --}}
                <div class="px-6 py-4 bg-gradient-to-r from-gray-900 to-indigo-950 text-white flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-extrabold flex items-center gap-2">
                            <span>Upload Foto Produk ke R2</span>
                            <span class="px-2 py-0.5 bg-sky-500/30 text-sky-200 border border-sky-400/30 text-[10px] rounded-lg">Cloudflare CDN</span>
                        </h4>
                        <p class="text-[11px] text-gray-300 truncate max-w-sm mt-0.5">{{ $selectedProduct->name }}</p>
                    </div>
                    <button type="button" wire:click="closeUploadModal" :disabled="isUploading" class="p-1.5 text-gray-400 hover:text-white rounded-xl hover:bg-white/10 transition disabled:opacity-40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                    {{-- Info Singkat Produk --}}
                    <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between text-xs">
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase">SKU / Item No</div>
                            <div class="font-mono font-bold text-gray-800">{{ $selectedProduct->item_no ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase">Toko / Unit Bisnis</div>
                            <div class="font-bold text-indigo-700">{{ $selectedProduct->businessUnit?->name ?? 'Semua' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase">Harga Jual</div>
                            <div class="font-extrabold text-emerald-600">Rp {{ number_format($selectedProduct->base_price, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    {{-- Foto Saat Ini (Jika Ada) --}}
                    @if($selectedProduct->hasMedia('cover'))
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Foto Sampul Saat Ini di R2</label>
                            <div class="relative w-36 h-36 bg-gray-100 rounded-2xl overflow-hidden border border-gray-200 group">
                                <img src="{{ $selectedProduct->getFirstMediaUrl('cover') }}" class="w-full h-full object-cover">
                                <button type="button" wire:click="removeCoverPhoto({{ $selectedProduct->id }})"
                                    class="absolute top-2 right-2 p-1.5 bg-rose-600 text-white rounded-xl hover:bg-rose-700 shadow-md transition"
                                    title="Hapus foto ini">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Form Upload Foto Sampul Baru --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-800 mb-1.5">
                            Pilih Foto Sampul Baru <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" wire:model="coverPhoto" accept="image/png,image/jpeg,image/webp"
                            @change="if ($event.target.files[0]) { localPreview = URL.createObjectURL($event.target.files[0]); }"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-200 rounded-2xl p-1 bg-gray-50">
                        <p class="text-[10px] text-gray-400 mt-1">Format: JPG, PNG, WEBP (Maksimal 10MB). Resolusi persegi (1:1) disarankan untuk katalog mobile.</p>
                        @error('coverPhoto') <div class="mt-1.5 p-2 bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-rose-200">{{ $message }}</div> @enderror

                        {{-- Realtime Progress Bar saat Mengunggah dari Browser --}}
                        <div x-show="isUploading" class="mt-3 p-3 bg-indigo-50 border border-indigo-200 rounded-2xl" style="display: none;">
                            <div class="flex items-center justify-between text-xs font-bold text-indigo-800 mb-1.5">
                                <span class="flex items-center gap-1.5">
                                    <svg class="animate-spin w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Sedang mengunggah file ke server...</span>
                                </span>
                                <span x-text="progress + '%'" class="font-mono font-extrabold text-indigo-700"></span>
                            </div>
                            <div class="w-full bg-indigo-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-150 ease-out" :style="'width: ' + progress + '%'"></div>
                            </div>
                            <p class="text-[10px] text-indigo-600 mt-1">Mohon tunggu hingga 100% sebelum menekan tombol simpan.</p>
                        </div>

                        {{-- Instant Preview Foto Baru --}}
                        <div class="mt-3" x-show="localPreview && !isUploading" style="display: none;">
                            <span class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Foto Siap Disimpan:
                            </span>
                            <div class="mt-1 w-32 h-32 rounded-2xl overflow-hidden border-2 border-emerald-500 shadow-xs bg-gray-100">
                                <img :src="localPreview" class="w-full h-full object-cover">
                            </div>
                        </div>
                    </div>

                    {{-- Form Upload Galeri Tambahan (Opsional) --}}
                    <div class="pt-2 border-t border-gray-100">
                        <label class="block text-xs font-bold text-gray-800 mb-1.5">
                            Galeri Foto Tambahan <span class="text-gray-400 font-normal">(Opsional, bisa multiple)</span>
                        </label>
                        <input type="file" wire:model="galleryPhotos" multiple accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 border border-gray-200 rounded-2xl p-1 bg-gray-50">
                        <p class="text-[10px] text-gray-400 mt-1">Foto tampak samping, belakang, kelengkapan aksesoris, atau dusbox.</p>
                        @error('galleryPhotos.*') <div class="mt-1.5 p-2 bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-rose-200">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Footer Modal --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button type="button" wire:click="closeUploadModal" :disabled="isUploading"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-900 rounded-xl hover:bg-gray-100 transition disabled:opacity-40">
                        Batal
                    </button>
                    <button type="button" wire:click="saveProductPhotos"
                        wire:loading.attr="disabled"
                        wire:target="coverPhoto, galleryPhotos, saveProductPhotos"
                        :disabled="isUploading"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black shadow-md hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                        
                        {{-- State 1: Uploading ke browser (isUploading) --}}
                        <template x-if="isUploading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span x-text="'Mengunggah (' + progress + '%) ...'"></span>
                            </span>
                        </template>

                        {{-- State 2: Menyimpan dari PHP ke Cloudflare R2 --}}
                        <span wire:loading wire:target="saveProductPhotos" class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>Menyimpan ke Cloudflare R2...</span>
                        </span>

                        {{-- State 3: Ready to submit --}}
                        <template x-if="!isUploading">
                            <span wire:loading.remove wire:target="saveProductPhotos" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Simpan ke Cloudflare R2</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
