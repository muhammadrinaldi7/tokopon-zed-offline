<div class="p-6 max-w-4xl mx-auto">
    {{-- Breadcrumb & Back --}}
    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('zoffline.stock-opname.index') }}" wire:navigate
            class="inline-flex items-center gap-2 text-sm text-neutral-600 hover:text-neutral-900 transition font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Riwayat Opname
        </a>
    </div>

    {{-- Form Container --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
        {{-- Header Card --}}
        <div class="p-6 border-b border-neutral-100 bg-neutral-50/50">
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">Inisiasi Sesi Stock Opname Baru</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Sistem akan mengambil snapshot stok buku saat ini untuk dibandingkan dengan penghitungan fisik riil.
            </p>
        </div>

        <form wire:submit.prevent="startOpname" class="p-6 space-y-6">
            {{-- Informasi Lokasi Cabang & Gudang (Terkunci Sesuai Akun BM) --}}
            <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-4.5">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-blue-100 text-[#4E44DB] rounded-lg mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider">Lokasi Toko Terkunci</h4>
                        <p class="text-xs text-blue-700 mt-0.5">
                            Sesuai hak akses Branch Manager, sesi opname ini otomatis terisolasi untuk cabang Anda:
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                            <div class="bg-white p-3 rounded-lg border border-blue-100 shadow-2xs">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Cabang</span>
                                <span class="text-sm font-bold text-gray-900">{{ $branchName }}</span>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-blue-100 shadow-2xs">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Gudang Toko</span>
                                <span class="text-sm font-bold text-gray-900">{{ $warehouseName }}</span>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-blue-100 shadow-2xs">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Unit Bisnis</span>
                                <span class="text-sm font-bold text-gray-900">{{ $businessUnitName }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pilihan Cakupan Opname (Type) --}}
            <div>
                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2">
                    Pilih Cakupan Barang yang Di-Audit <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Opsi 1: Semua Barang --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'ALL' ? 'border-[#4E44DB] bg-blue-50/20' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="ALL" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-gray-900">Semua Produk</span>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'ALL' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'ALL')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Audit menyeluruh untuk seluruh unit Handphone (IMEI) dan Aksesoris toko.
                        </p>
                    </label>

                    {{-- Opsi 2: Khusus Unit HP (IMEI) --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-blue-50/20' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="SERIALIZED_ONLY" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-gray-900">Khusus Unit HP (IMEI)</span>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'SERIALIZED_ONLY' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'SERIALIZED_ONLY')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Paling umum untuk audit harian/mingguan unit HP bernilai tinggi melalui scanner IMEI.
                        </p>
                    </label>

                    {{-- Opsi 3: Khusus Aksesoris --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'NON_SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-blue-50/20' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="NON_SERIALIZED_ONLY" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-gray-900">Khusus Aksesoris</span>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'NON_SERIALIZED_ONLY' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'NON_SERIALIZED_ONLY')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Audit barang tanpa nomor seri (Case, Charger, Tempered Glass, dll) via input kuantitas fisik.
                        </p>
                    </label>
                </div>
            </div>

            {{-- Estimasi Snapshot Box --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-neutral-700 block">Estimasi Data Snapshot Stok Buku</span>
                    <span class="text-xs text-neutral-500">Jumlah data yang saat ini terdaftar aktif di gudang cabang Anda:</span>
                </div>
                <div class="flex items-center gap-6">
                    @if(in_array($type, ['ALL', 'SERIALIZED_ONLY', 'CATEGORY']))
                        <div class="text-center">
                            <span class="text-xs text-neutral-400 block font-medium">Unit IMEI Aktif</span>
                            <span class="text-base font-bold text-neutral-900 font-mono">{{ number_format($totalAvailableSns) }} Unit</span>
                        </div>
                    @endif
                    @if(in_array($type, ['ALL', 'NON_SERIALIZED_ONLY', 'CATEGORY']))
                        <div class="text-center border-l border-neutral-200 pl-6">
                            <span class="text-xs text-neutral-400 block font-medium">SKU Aksesoris</span>
                            <span class="text-base font-bold text-neutral-900 font-mono">{{ number_format($totalNonSerialSkus) }} SKU</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Catatan / Tujuan Opname --}}
            <div>
                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                    Catatan Sesi / Tujuan Opname (Opsional)
                </label>
                <textarea wire:model="notes" rows="3"
                    placeholder="Contoh: Stock Opname Rutin Mingguan Cabang, Pemeriksaan unit etalase dan brankas..."
                    class="w-full px-4 py-2.5 bg-white border border-neutral-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"></textarea>
                @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-3">
                <a href="{{ route('zoffline.stock-opname.index') }}" wire:navigate
                    class="px-5 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-xl text-sm font-semibold transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" wire:loading.attr="disabled"
                    class="px-6 py-2.5 bg-[#4E44DB] hover:bg-[#3d34b3] text-white rounded-xl shadow-md hover:shadow-lg transition font-semibold text-sm flex items-center gap-2 cursor-pointer">
                    <span wire:loading.remove>Mulai Penghitungan Fisik</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyiapkan Snapshot...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
