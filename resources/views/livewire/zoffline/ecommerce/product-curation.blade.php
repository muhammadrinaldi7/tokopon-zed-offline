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
                        Unggah dan kelola foto produk untuk katalog mobile app. Foto otomatis tersimpan di Cloudflare R2 CDN.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.products') }}" target="_blank"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1">
                        <span>📦 Katalog Baru (Syihab)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="{{ route('admin.second-products') }}" target="_blank"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1">
                        <span>🔄 Katalog Second (GSK)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <span class="px-3 py-1.5 bg-sky-50 text-sky-800 rounded-xl text-xs font-bold border border-sky-200">
                        {{ $onlineWarehouseCount }} Gudang Online Aktif
                    </span>
                </div>
            </div>

            {{-- Filter Rows Utama: Search, Brand, Kategori, Toko, Status Foto --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 pt-1">
                {{-- Search Input --}}
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama produk / SKU..."
                        class="w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 outline-none transition">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    @if(!empty($search))
                        <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>

                {{-- Filter Brand (Pilihan Brand) --}}
                <select wire:model.live="selectedBrand" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:bg-white focus:border-indigo-500 outline-none transition">
                    <option value="">Semua Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand }}">{{ $brand }}</option>
                    @endforeach
                </select>

                {{-- Filter Kategori --}}
                <select wire:model.live="selectedCategory" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:bg-white focus:border-indigo-500 outline-none transition">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>

                {{-- Filter Toko Mobile --}}
                <select wire:model.live="selectedBusinessUnitId" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:bg-white focus:border-indigo-500 outline-none transition">
                    <option value="">Semua Toko Mobile</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>

                {{-- Filter Status Foto R2 --}}
                <select wire:model.live="mediaFilter" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:bg-white focus:border-indigo-500 outline-none transition">
                    <option value="all">Semua Status Foto</option>
                    <option value="has_media">✅ Sudah Ada Foto (R2)</option>
                    <option value="no_media">⚠️ Belum Ada Foto</option>
                </select>
            </div>

            {{-- Sub-Toolbar: Toggle Stok 0 & Summary Info --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Toggle Hanya Stok Ready (> 0) --}}
                    <button type="button" wire:click="$toggle('onlyInStock')"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold transition-all border {{ $onlyInStock ? 'bg-emerald-50 text-emerald-800 border-emerald-300 shadow-xs' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                        <span class="w-2 h-2 rounded-full {{ $onlyInStock ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' }}"></span>
                        <span>Hanya Stok Ready (> 0)</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold {{ $onlyInStock ? 'bg-emerald-200/70 text-emerald-900' : 'bg-gray-200 text-gray-600' }}">
                            {{ $onlyInStock ? 'Aktif' : 'Semua Stok' }}
                        </span>
                    </button>

                    @if(!empty($search) || $selectedBusinessUnitId || !empty($selectedCategory) || !empty($selectedBrand) || $mediaFilter !== 'all' || !$onlyInStock)
                        <button type="button" wire:click="resetFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset Filter</span>
                        </button>
                    @endif
                </div>

                <div class="text-xs font-semibold text-gray-500">
                    Menampilkan <span class="font-bold text-gray-900">{{ $products->total() }}</span> produk katalog
                </div>
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
                                @if($prod->brandName)
                                    <span class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 bg-black/70 backdrop-blur-xs text-white rounded text-[9px] font-extrabold uppercase">
                                        {{ $prod->brandName }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <span class="text-[10px] font-bold text-indigo-600 uppercase truncate">{{ $prod->categoryName ?? 'Gadget' }}</span>
                                @if($prod->item_no)
                                    <span class="text-[9px] font-mono text-gray-400 truncate max-w-[60px]" title="{{ $prod->item_no }}">{{ $prod->item_no }}</span>
                                @endif
                            </div>
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
                    <div class="col-span-full py-16 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-400 mx-auto flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-800">Tidak ada produk yang cocok</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                            @if($onlyInStock)
                                Tidak ada produk dengan stok > 0 untuk kombinasi filter ini. Anda dapat menonaktifkan tombol "Hanya Stok Ready" untuk melihat produk dengan stok 0.
                            @else
                                Coba sesuaikan kata kunci pencarian, kategori, atau brand yang dipilih.
                            @endif
                        </p>
                        <button type="button" wire:click="resetFilters" class="mt-3 px-4 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition">
                            Reset Semua Filter
                        </button>
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
                localPreview: null,
                compressInfo: '',

                compressFile(file, maxDimension = 1200, quality = 0.85) {
                    return new Promise((resolve) => {
                        if (!file.type.match(/image.*/)) {
                            resolve(file);
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const img = new Image();
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                let width = img.width;
                                let height = img.height;

                                if (width > maxDimension || height > maxDimension) {
                                    if (width > height) {
                                        height = Math.round((height * maxDimension) / width);
                                        width = maxDimension;
                                    } else {
                                        width = Math.round((width * maxDimension) / height);
                                        height = maxDimension;
                                    }
                                }

                                canvas.width = width;
                                canvas.height = height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, width, height);

                                canvas.toBlob((blob) => {
                                    if (!blob) {
                                        resolve(file);
                                        return;
                                    }
                                    const cleanName = file.name.replace(/\.[^/.]+$/, '') + '.webp';
                                    const compressed = new File([blob], cleanName, { type: 'image/webp' });
                                    resolve(compressed);
                                }, 'image/webp', quality);
                            };
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                },

                async handleCoverSelect(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.isUploading = true;
                    this.progress = 10;
                    this.localPreview = URL.createObjectURL(file);
                    const origMb = (file.size / (1024 * 1024)).toFixed(1);

                    // Kompresi instan di browser
                    const compressed = await this.compressFile(file);
                    const compKb = Math.round(compressed.size / 1024);
                    this.compressInfo = origMb > 0.3 ? (origMb + ' MB ➔ ' + compKb + ' KB') : (compKb + ' KB');
                    this.progress = 25;

                    // Upload via API resmi Livewire
                    $wire.upload('coverPhoto', compressed,
                        () => {
                            this.isUploading = false;
                            this.progress = 100;
                        },
                        () => {
                            this.isUploading = false;
                            alert('Gagal mengunggah foto. Silakan coba kembali.');
                        },
                        (evt) => {
                            this.progress = 25 + Math.round(evt.detail.progress * 0.75);
                        }
                    );
                },

                async handleGallerySelect(e) {
                    const files = e.target.files;
                    if (!files || files.length === 0) return;

                    this.isUploading = true;
                    this.progress = 10;

                    const compressedList = [];
                    for (let i = 0; i < files.length; i++) {
                        const comp = await this.compressFile(files[i]);
                        compressedList.push(comp);
                    }
                    this.progress = 30;

                    $wire.uploadMultiple('galleryPhotos', compressedList,
                        () => {
                            this.isUploading = false;
                            this.progress = 100;
                        },
                        () => {
                            this.isUploading = false;
                            alert('Gagal mengunggah galeri foto.');
                        },
                        (evt) => {
                            this.progress = 30 + Math.round(evt.detail.progress * 0.7);
                        }
                    );
                }
            }">
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
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-gray-800">
                                Pilih Foto Sampul Baru <span class="text-rose-500">*</span>
                            </label>
                            <span x-show="compressInfo" class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold rounded-lg" style="display: none;">
                                ⚡ Terkompresi: <span x-text="compressInfo"></span>
                            </span>
                        </div>

                        <input type="file" @change="handleCoverSelect($event)" accept="image/png,image/jpeg,image/webp" :disabled="isUploading"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-200 rounded-2xl p-1 bg-gray-50 disabled:opacity-50">
                        <p class="text-[10px] text-gray-400 mt-1">Otomatis dioptimalkan ke WebP super ringan & tajam untuk katalog mobile.</p>
                        @error('coverPhoto') <div class="mt-1.5 p-2 bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-rose-200">{{ $message }}</div> @enderror

                        {{-- Realtime Progress Bar saat Mengunggah dari Browser --}}
                        <div x-show="isUploading" class="mt-3 p-3 bg-indigo-50 border border-indigo-200 rounded-2xl" style="display: none;">
                            <div class="flex items-center justify-between text-xs font-bold text-indigo-800 mb-1.5">
                                <span class="flex items-center gap-1.5">
                                    <svg class="animate-spin w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Mengunggah foto instan ke server...</span>
                                </span>
                                <span x-text="progress + '%'" class="font-mono font-extrabold text-indigo-700"></span>
                            </div>
                            <div class="w-full bg-indigo-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-150 ease-out" :style="'width: ' + progress + '%'"></div>
                            </div>
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
                        <input type="file" @change="handleGallerySelect($event)" multiple accept="image/png,image/jpeg,image/webp" :disabled="isUploading"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 border border-gray-200 rounded-2xl p-1 bg-gray-50 disabled:opacity-50">
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
