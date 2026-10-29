<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Katalog & API Trade-In</h1>
        <p class="text-gray-500 mt-1">Konfigurasikan visibilitas stok katalog serta aturan dinamis list produk & sumber harga pada API Tukar Tambah.</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-6 max-w-4xl">
        {{-- Main Settings Box: Stock Threshold --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Ambang Batas Stok Katalog</h2>

            <div class="space-y-4">
                <div class="bg-[#eff2ff] p-4 rounded-lg border border-[#1c69d4]/20">
                    <p class="text-sm text-[#1c69d4] font-medium leading-relaxed">
                        Pengaturan ini memastikan bahwa hanya produk yang sudah tersinkron dengan Accurate (<code>has_active_accurate = true</code>) dan memiliki stok di atas atau sama dengan nilai di bawah ini yang akan ditampilkan ke publik.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Batas Minimum Stok Tampil</label>
                    <input type="number" wire:model="minimumStockThreshold" min="0" class="w-full text-[15px] rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all" placeholder="Contoh: 5">
                    <p class="text-xs text-gray-400 mt-1.5">Produk dengan stok di bawah nilai ini tidak akan muncul di website.</p>
                    @error('minimumStockThreshold') <span class="text-rose-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Dynamic Trade-In API Settings Box --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Pengaturan Dinamis API Trade-In & Harga Unit Second</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Mengatur penyederhanaan varian warna dan sumber harga jual unit bisnis Second (BU 2).</p>
                </div>
                <a href="{{ \Illuminate\Support\Facades\Route::has('admin.buyback.index') ? route('admin.buyback.index') : url('/admin/buyback/devices') }}"
                    wire:navigate
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1c69d4] bg-[#eff6ff] hover:bg-blue-100 px-3.5 py-2 rounded-lg transition">
                    <span>Kelola Excel Harga Trade-In</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>

            {{-- Toggle Grouping --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-[#1c69d4]/40 bg-gray-50/50 cursor-pointer transition">
                    <input type="checkbox" wire:model="tradeinGroupOldDevices"
                        class="mt-1 rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                    <div>
                        <span class="block text-sm font-bold text-gray-800">Ringkas HP Lama Tanpa Warna</span>
                        <span class="block text-xs text-gray-500 mt-1 leading-relaxed">
                            Menggabungkan varian warna pada API <code>/old-devices</code> menjadi <strong>Model + RAM/Storage + Proyek</strong>.
                        </span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-[#1c69d4]/40 bg-gray-50/50 cursor-pointer transition">
                    <input type="checkbox" wire:model="tradeinGroupTargetDevices"
                        class="mt-1 rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                    <div>
                        <span class="block text-sm font-bold text-gray-800">Ringkas HP Tujuan Tanpa Warna</span>
                        <span class="block text-xs text-gray-500 mt-1 leading-relaxed">
                            Menggabungkan varian warna pada API <code>/target-devices</code> (Baru & Second) menjadi <strong>Model + RAM/Storage + Proyek</strong>.
                        </span>
                    </div>
                </label>
            </div>

            {{-- Sumber Harga Jual BU Second --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Sumber Harga Jual Unit Bisnis Second (BU 2)</label>
                <select wire:model="tradeinSecondPriceSource"
                    class="w-full text-sm rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all bg-white">
                    <option value="custom_fallback">Utamakan Custom Sell Price (Excel), Fallback ke Harga Accurate [Rekomendasi]</option>
                    <option value="custom_only">Hanya Custom Sell Price (Excel) — Sembunyikan produk yang belum diatur di Excel</option>
                    <option value="accurate_only">Selalu Gunakan Harga Real Accurate (base_price)</option>
                </select>
                <p class="text-xs text-gray-400 mt-1.5">
                    Kolom <code>custom_sell_price</code> dikelola via Import Excel/CSV dan kebal terhadap sinkronisasi Accurate. Pilih <code>Hanya Custom Sell Price</code> jika ingin menyembunyikan produk berharga placeholder Accurate (misal <code>Rp 1</code>) yang belum diisi di Excel.
                </p>
                @error('tradeinSecondPriceSource') <span class="text-rose-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Strategi Harga Grup Warna --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Strategi Harga Beli Grup HP Lama</label>
                    <select wire:model="tradeinOldPriceStrategy"
                        class="w-full text-sm rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] bg-white">
                        <option value="min">Ambil Harga Terendah (Min) antar warna</option>
                        <option value="max">Ambil Harga Tertinggi (Max) antar warna</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Strategi Harga Jual Grup HP Tujuan</label>
                    <select wire:model="tradeinTargetPriceStrategy"
                        class="w-full text-sm rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] bg-white">
                        <option value="min">Ambil Harga Terendah (Min — Mulai Dari) antar warna</option>
                        <option value="max">Ambil Harga Tertinggi (Max) antar warna</option>
                    </select>
                </div>
            </div>

            {{-- Filter Aksesoris Nyasar --}}
            <label class="flex items-start gap-3 p-4 rounded-xl border border-amber-200/80 bg-amber-50/40 cursor-pointer">
                <input type="checkbox" wire:model="tradeinExcludeAccessories"
                    class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-600">
                <div>
                    <span class="block text-sm font-bold text-gray-800">Otomatis Sembunyikan Aksesoris Nyasar di Kategori HP</span>
                    <span class="block text-xs text-gray-500 mt-0.5 leading-relaxed">
                        Menyembunyikan item dengan proyek <code>ACCESSORIES</code>, bertanda <code>is_addon</code>, atau diawali <code>CASE -</code> / <code>TEMPERED</code> / <code>CHARGER</code> agar tidak muncul di daftar perangkat HP.
                    </span>
                </div>
            </label>
        </div>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="bg-[#1c69d4] text-white px-8 py-3 rounded-lg font-bold hover:bg-[#3f36b8] active:scale-95 transition-all shadow-sm shadow-[#1c69d4]/25 disabled:opacity-50 flex items-center gap-2">
                <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
