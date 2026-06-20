<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Katalog</h1>
        <p class="text-gray-500 mt-1">Konfigurasikan visibilitas dan aturan penampilan produk pada halaman customer.</p>
    </div>

    {{-- Main Settings Box --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 p-6 max-w-3xl">
        <form wire:submit.prevent="save">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-50 pb-2">Ambang Batas Stok</h2>
            
            <div class="mb-6 space-y-4">
                <div class="bg-[#eff2ff] p-4 rounded-lg border border-[#1c69d4]/20">
                    <p class="text-sm text-[#1c69d4] font-medium leading-relaxed">
                        Pengaturan ini memastikan bahwa hanya produk yang sudah tersinkron dengan Accurate (<code>has_active_accurate = true</code>) dan memiliki stok di atas atau sama dengan nilai di bawah ini yang akan ditampilkan ke publik. Hal ini menjaga agar katalog online sesuai dengan ketersediaan barang di toko fisik.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Batas Minimum Stok Tampil</label>
                    <input type="number" wire:model="minimumStockThreshold" min="0" class="w-full text-[15px] rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all" placeholder="Contoh: 5">
                    <p class="text-xs text-gray-400 mt-1.5">Produk dengan stok di bawah nilai ini tidak akan muncul di website.</p>
                    @error('minimumStockThreshold') <span class="text-rose-500 text-xs font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-5 border-t border-gray-50">
                <button type="submit" wire:loading.attr="disabled" class="bg-[#1c69d4] text-white px-8 py-3 rounded-lg font-bold hover:bg-[#3f36b8] active:scale-95 transition-all shadow-sm shadow-[#1c69d4]/25 disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>

