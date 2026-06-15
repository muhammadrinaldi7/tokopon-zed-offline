<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.second-products') }}"
                class="text-sm font-medium text-gray-400 hover:text-[#1c69d4] transition">← Kembali ke Daftar Produk Second</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-2">Kelola Varian Second: {{ $product->name }}</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 xl:gap-8">
        {{-- List Area --}}
        <div class="lg:col-span-3 space-y-4">
            @forelse($variants as $variant)
                <div class="bg-white rounded-lg p-5 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div class="flex gap-4 items-center">
                        <div
                            class="w-16 h-16 shrink-0 bg-gray-50 rounded-lg border border-gray-100 overflow-hidden flex items-center justify-center">
                            @if ($url = $variant->getFirstMediaUrl('variant_image', 'thumb'))
                                <img src="{{ $url }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-bold text-gray-800 text-lg">
                                    {{ $variant->ram ? $variant->ram . ' - ' : '' }}{{ $variant->storage ? $variant->storage . ' - ' : '' }}{{ $variant->color ?? 'Standar' }}
                                </h3>
                                <span
                                    class="text-[10px] font-bold tracking-widest px-2 py-0.5 rounded bg-gray-100 text-gray-500 uppercase">{{ $variant->condition_desc }}</span>
                                @if(!isset($variant->has_sn) || $variant->has_sn)
                                    <span class="text-[10px] font-bold tracking-widest px-2 py-0.5 rounded bg-indigo-50 text-indigo-500 uppercase">WAJIB SN</span>
                                @else
                                    <span class="text-[10px] font-bold tracking-widest px-2 py-0.5 rounded bg-gray-50 text-gray-400 uppercase">TANPA SN</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">SKU: <span
                                    class="font-mono text-gray-700">{{ $variant->sku ?? '-' }}</span></p>

                            {{-- Accurate Connection Status --}}
                            <div class="mt-3 flex items-center gap-2">
                                @if ($variant->product_accurate_id)
                                    <div
                                        class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Terkoneksi Accurate:
                                        {{ $variant->accurateData->accurate_id ?? $variant->product_accurate_id }}
                                    </div>
                                @else
                                    <span
                                        class="text-xs font-semibold text-rose-500 bg-rose-50 bg-opacity-50 px-2 py-1 rounded-md">Data
                                        Manual (Tidak Sync)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <div class="text-xl font-bold text-[#1c69d4]">Rp
                            {{ number_format($variant->price, 0, ',', '.') }}</div>
                        <div class="text-sm text-gray-500 font-medium my-1">Stok: {{ $variant->stock }}</div>
                        <div class="flex gap-2 mt-2">
                            <button wire:click="editVariant({{ $variant->id }})"
                                class="text-sm border border-gray-200 text-gray-600 hover:bg-gray-50 rounded px-2.5 py-1 transition">Edit</button>
                            <button wire:click="confirmDelete({{ $variant->id }})"
                                class="text-sm border border-rose-200 text-rose-600 hover:bg-rose-50 rounded px-2.5 py-1 transition">Hapus</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg p-10 shadow-sm border border-gray-100 text-center text-gray-400">
                    <p>Produk ini belum memiliki variasi.<br>Silakan tambah varian di panel sebelah kanan.</p>
                </div>
            @endforelse
        </div>

        {{-- Form Area --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100 sticky top-24">
                <h2 class="font-bold text-gray-800 text-lg mb-5 border-b border-gray-100 pb-3">
                    {{ $isEditing ? 'Ubah Varian' : 'Tambah Varian Baru' }}
                </h2>

                <form wire:submit.prevent="saveVariant" class="space-y-4">
                    {{-- ACCURATE AUTOCOMPLETE --}}
                    <div
                        class="col-span-1 border border-[#1c69d4] border-opacity-30 bg-[#eff2ff] p-4 rounded-lg relative">
                        <label class="block text-xs font-bold text-[#1c69d4] tracking-wide mb-1 uppercase">🔗 Hubungkan
                            ke Accurate</label>
                        <p class="text-[11px] text-[#1c69d4] opacity-70 mb-2 leading-tight">Cari nama produk dari
                            Accurate
                            untuk mengambil Harga & Stok secara otomatis.</p>

                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchAccurate"
                                placeholder="Ketik nama / kode accurate..."
                                class="w-full text-[15px] rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] disabled:opacity-50 disabled:bg-gray-100 transition-all font-medium"
                                {{ $selectedAccurateId ? 'disabled' : '' }}>
                            @if ($selectedAccurateId)
                                <button type="button" wire:click="clearAccurate"
                                    class="absolute right-2 top-2 text-rose-500 text-xs font-bold bg-white px-2 py-0.5 rounded shadow-sm border border-rose-100">Batal</button>
                            @endif
                        </div>

                        {{-- Dropdown Results --}}
                        @if (!empty($searchResults) && !$selectedAccurateId)

                            <div
                                class="absolute z-10 w-full bg-white mt-1 border border-gray-200 rounded-lg shadow-sm max-h-48 overflow-y-auto">
                                @forelse($searchResults as $res)
                                    @php
                                        $kode = $res->item_no ?? '';
                                        $harga = $res->base_price;
                                        $meta = $res->name;

                                    @endphp
                                    <div wire:click="selectAccurate('{{ $res->id }}', {{ $harga }}, {{ $res->stock }}, '{{ $kode }}')"
                                        class="p-3 hover:bg-[#eff2ff] cursor-pointer border-b border-gray-50 last:border-b-0">
                                        <div class="font-bold text-sm text-gray-800 line-clamp-1">
                                            {{ $meta ?? $res->accurate_id }}</div>
                                        <div class="flex justify-between items-center mt-1">
                                            <span
                                                class="text-xs text-gray-500 font-mono">{{ $kode ?: $res->accurate_id }}</span>
                                            <span class="text-xs font-bold text-[#1c69d4]">Stok:
                                                {{ $res->stock }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-3 text-sm text-gray-500 text-center">Tidak ditemukan.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    {{-- Manual Input --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">RAM <span
                                    class="text-gray-400 font-normal">(ops)</span></label>
                            <input type="text" wire:model="ram" placeholder="Cth: 8GB"
                                class="w-full text-[14px] rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">Storage <span
                                    class="text-gray-400 font-normal">(ops)</span></label>
                            <input type="text" wire:model="storage" placeholder="Cth: 256GB"
                                class="w-full text-[14px] rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">Warna <span
                                    class="text-gray-400 font-normal">(ops)</span></label>
                            <input type="text" wire:model="color" placeholder="Cth: Black"
                                class="w-full text-[14px] rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">Kondisi</label>
                            <select wire:model="condition" id="condition" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block p-3 transition-colors">
                                <option value="Resmi">Resmi</option>
                                <option value="Inter">Inter</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">SKU Internal <span
                                    class="text-gray-400 font-normal">(opsional)</span></label>
                            <input type="text" wire:model="sku"
                                class="w-full text-[14px] rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all">
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Wajib Serial Number (SN)</label>
                            <p class="text-xs text-gray-500 mt-0.5">Jika diaktifkan, produk ini wajib diisi SN saat checkout di POS.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="has_sn" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#1c69d4]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1c69d4]"></div>
                        </label>
                    </div>

                    {{-- Image Upload --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">Gambar Varian <span
                                class="text-gray-400 font-normal">(opsional)</span></label>
                        <div
                            class="flex items-center gap-4 border border-gray-300 rounded-lg px-4 py-3 bg-white shadow-sm focus-within:ring-4 focus-within:ring-[#1c69d4]/10 focus-within:border-[#1c69d4] transition-all">
                            @if ($variantImage)
                                <img src="{{ $variantImage->temporaryUrl() }}"
                                    class="w-12 h-12 rounded object-cover border border-gray-200">
                            @elseif ($currentVariantImageUrl)
                                <img src="{{ $currentVariantImageUrl }}"
                                    class="w-12 h-12 rounded object-cover border border-gray-200">
                            @else
                                <div
                                    class="w-12 h-12 rounded bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                            @endif
                            <input type="file" wire:model="variantImage"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:font-semibold file:text-[#1c69d4] file:bg-[#eff2ff] hover:file:bg-[#e0e7ff] transition">
                        </div>
                    </div>

                    @if ($product->is_second)
                        <div class="mt-4 p-4 border border-amber-200 bg-amber-50 rounded-lg">
                            <label class="block text-xs font-bold text-amber-800 mb-2">Harga Manual (Produk
                                Bekas)</label>
                            <div class="relative">
                                <span
                                    class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-[15px]">Rp</span>
                                <input type="number" wire:model="manualPrice"
                                    class="w-full text-[15px] pl-10 pr-4 py-2.5 rounded-lg border-gray-300 bg-white shadow-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all font-medium text-gray-800"
                                    min="0">
                            </div>
                            @error('manualPrice')
                                <span class="text-xs text-rose-500 mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    @if ($selectedAccurateId)
                        <div
                            class="p-4 bg-gray-50 rounded-lg border border-gray-100 flex justify-between items-center shadow-inner mt-2">
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Harga Sync:</p>
                                <p
                                    class="text-lg font-bold {{ $product->is_second ? 'text-gray-400 line-through' : 'text-[#1c69d4]' }}">
                                    Rp
                                    {{ number_format($simulatedPrice, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 font-medium">Stok Sync:</p>
                                <p class="text-lg font-bold text-emerald-600">{{ $simulatedStock }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="pt-5 flex gap-3">
                        @if ($isEditing)
                            <button type="button" wire:click="resetForm"
                                class="flex-1 bg-white border border-gray-200 text-gray-600 py-3 rounded-lg text-[15px] font-bold hover:bg-gray-50 transition active:scale-[0.98]">Batal</button>
                        @endif
                        <button type="submit"
                            class="flex-1 bg-[#1c69d4] text-white py-3 rounded-lg text-[15px] font-bold hover:bg-opacity-90 shadow-sm shadow-[#1c69d4]/30 transition active:scale-[0.98]">
                            {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Varian' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
