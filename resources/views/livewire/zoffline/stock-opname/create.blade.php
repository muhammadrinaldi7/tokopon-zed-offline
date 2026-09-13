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

    {{-- Banner Jika Sudah Ada Sesi Aktif Berjalan di Cabang Ini --}}
    @if($activeOpname)
        <div class="mb-6 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="p-2.5 bg-white/20 rounded-xl mt-0.5">
                    <svg class="w-6 h-6 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <span class="inline-block px-2.5 py-0.5 bg-white/25 rounded-md text-[10px] font-extrabold uppercase tracking-wider mb-1">
                        Sesi Opname Sedang Berjalan
                    </span>
                    <h3 class="text-base sm:text-lg font-bold">Sesi #{{ $activeOpname->opname_number }} Sedang Aktif</h3>
                    <p class="text-xs text-amber-100 mt-0.5">
                        Dimulai oleh <strong class="text-white">{{ $activeOpname->user->name ?? 'BM Cabang' }}</strong> pada {{ $activeOpname->start_time->format('d M Y, H:i') }}.
                        Anda dapat langsung bergabung ke workstation untuk proses scan bersama tim BM.
                    </p>
                </div>
            </div>
            <a href="{{ route('zoffline.stock-opname.count', $activeOpname->id) }}" wire:navigate
                class="px-5 py-2.5 bg-white text-orange-700 hover:bg-orange-50 rounded-xl font-bold text-xs sm:text-sm shadow-md transition shrink-0 flex items-center gap-2">
                <span>Gabung Scan Bersama</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    @endif

    {{-- Form Container --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
        {{-- Header Card --}}
        <div class="p-6 border-b border-neutral-100 bg-neutral-50/50">
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">Inisiasi Sesi Stock Opname Baru</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Sistem akan mengambil snapshot stok buku toko saat ini sesuai cakupan yang Anda pilih untuk dibandingkan dengan penghitungan fisik riil.
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
                            Sesuai otorisasi Branch Manager, sesi opname ini otomatis terisolasi untuk cabang dan unit bisnis Anda:
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
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider">
                        Pilih Cakupan Barang yang Di-Audit <span class="text-red-500">*</span>
                    </label>
                    <span class="text-[11px] text-neutral-400 font-medium">
                        Unit Bisnis: <strong class="text-neutral-700">{{ $businessUnitName }}</strong>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Opsi 1: Semua Barang --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'ALL' ? 'border-[#4E44DB] bg-blue-50/20 shadow-xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="ALL" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
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
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-blue-50/20 shadow-xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="SERIALIZED_ONLY" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-bold text-gray-900">Khusus Unit HP (IMEI)</span>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'SERIALIZED_ONLY' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'SERIALIZED_ONLY')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Audit harian/mingguan seluruh unit HP bernilai tinggi melalui scanner IMEI.
                        </p>
                    </label>

                    {{-- Opsi 3: Khusus Aksesoris --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'NON_SERIALIZED_ONLY' ? 'border-[#4E44DB] bg-blue-50/20 shadow-xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="NON_SERIALIZED_ONLY" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-bold text-gray-900">Khusus Aksesoris</span>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'NON_SERIALIZED_ONLY' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'NON_SERIALIZED_ONLY')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Audit barang tanpa nomor seri (Case, Charger, Tempered Glass, dll) via input fisik.
                        </p>
                    </label>

                    {{-- Opsi 4: Per Brand --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'BRAND' ? 'border-[#4E44DB] bg-blue-50/20 shadow-xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="BRAND" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-bold text-gray-900">Per Brand / Merek</span>
                                <span class="px-1.5 py-0.2 bg-purple-100 text-purple-700 font-bold text-[10px] rounded">Filter</span>
                            </div>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'BRAND' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'BRAND')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Audit fokus untuk satu merek tertentu (misal: Khusus Apple, Samsung, atau Oppo).
                        </p>
                    </label>

                    {{-- Opsi 5: Per Proyek --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'PROYEK' ? 'border-[#4E44DB] bg-blue-50/20 shadow-xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="PROYEK" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-bold text-gray-900">Per Proyek</span>
                                <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-700 font-bold text-[10px] rounded">Filter</span>
                            </div>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'PROYEK' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'PROYEK')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Audit berdasarkan divisi proyek (misal: SJU, SAB, RESMI, INTER, atau BEACUKAI).
                        </p>
                    </label>

                    {{-- Opsi 6: Per Kategori --}}
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition-all {{ $type === 'CATEGORY' ? 'border-[#4E44DB] bg-blue-50/20 shadow-xs' : 'border-neutral-200 hover:border-neutral-300' }}">
                        <input type="radio" wire:model.live="type" value="CATEGORY" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-bold text-gray-900">Per Kategori</span>
                                <span class="px-1.5 py-0.2 bg-blue-100 text-blue-700 font-bold text-[10px] rounded">Filter</span>
                            </div>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $type === 'CATEGORY' ? 'border-[#4E44DB]' : 'border-gray-300' }}">
                                @if($type === 'CATEGORY')
                                    <div class="w-2 h-2 rounded-full bg-[#4E44DB]"></div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">
                            Audit kelompok kategori barang (misal: HP Second, HP New, Case, atau Charger).
                        </p>
                    </label>
                </div>
            </div>

            {{-- Dynamic Filter Dropdowns --}}
            @if($type === 'BRAND')
                <div class="p-4 bg-purple-50/60 border border-purple-200 rounded-xl">
                    <label class="block text-xs font-bold text-purple-900 uppercase tracking-wider mb-1.5">
                        Pilih Brand / Merek Produk Yang Di-Audit <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="brandFilter"
                        class="w-full px-4 py-2.5 bg-white border border-purple-300 rounded-xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition">
                        <option value="">-- Pilih Brand Produk ({{ count($brandList) }} Merek Tersedia) --</option>
                        @foreach($brandList as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                    @error('brandFilter') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    <span class="text-[11px] text-purple-700 block mt-1">
                        Sistem hanya akan memasukkan barang dengan brand ini ke lembar audit stock opname.
                    </span>
                </div>
            @endif

            @if($type === 'PROYEK')
                <div class="p-4 bg-emerald-50/60 border border-emerald-200 rounded-xl">
                    <label class="block text-xs font-bold text-emerald-900 uppercase tracking-wider mb-1.5">
                        Pilih Proyek Produk Yang Di-Audit <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="projectFilter"
                        class="w-full px-4 py-2.5 bg-white border border-emerald-300 rounded-xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition">
                        <option value="">-- Pilih Proyek ({{ count($projectList) }} Proyek Tersedia) --</option>
                        @foreach($projectList as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                    @error('projectFilter') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    <span class="text-[11px] text-emerald-700 block mt-1">
                        Hanya barang dengan proyek ini pada unit bisnis {{ $businessUnitName }} yang akan diaudit.
                    </span>
                </div>
            @endif

            @if($type === 'CATEGORY')
                <div class="p-4 bg-blue-50/60 border border-blue-200 rounded-xl">
                    <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider mb-1.5">
                        Pilih Kategori Produk Yang Di-Audit <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="categoryFilter"
                        class="w-full px-4 py-2.5 bg-white border border-blue-300 rounded-xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                        <option value="">-- Pilih Kategori Produk ({{ count($categoryList) }} Kategori Tersedia) --</option>
                        @foreach($categoryList as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('categoryFilter') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    <span class="text-[11px] text-blue-700 block mt-1">
                        Sistem hanya akan memuat produk yang tergolong dalam kategori ini pada gudang cabang Anda.
                    </span>
                </div>
            @endif

            {{-- Estimasi Snapshot Box --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-neutral-700 block">Estimasi Data Snapshot Stok Buku</span>
                    <span class="text-xs text-neutral-500">
                        Perkiraan data yang akan diaudit sesuai cakupan terpilih saat ini:
                    </span>
                </div>
                <div class="flex items-center gap-6">
                    @if(in_array($type, ['ALL', 'SERIALIZED_ONLY', 'BRAND', 'PROYEK', 'CATEGORY']))
                        <div class="text-center">
                            <span class="text-xs text-neutral-400 block font-medium">Unit IMEI Aktif</span>
                            <span class="text-base font-bold text-neutral-900 font-mono">{{ number_format($totalAvailableSns) }} Unit</span>
                        </div>
                    @endif
                    @if(in_array($type, ['ALL', 'NON_SERIALIZED_ONLY', 'BRAND', 'PROYEK', 'CATEGORY']))
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
                    placeholder="Contoh: Stock Opname Rutin Mingguan Brand Apple, Pemeriksaan unit etalase dan brankas..."
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
