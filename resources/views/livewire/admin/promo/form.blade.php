<div>
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $promo ? 'Edit Promo' : 'Tambah Promo Baru' }}</h1>
            <p class="text-gray-500 text-sm mt-1">Lengkapi form di bawah ini untuk mengatur promo atau voucher diskon.</p>
        </div>
        <a href="{{ route('admin.promos.index') }}" wire:navigate
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </a>
    </div>

    <form wire:submit="save" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-8">
        {{-- Section 1: Informasi Dasar --}}
        <div>
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Informasi Promo
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Promo <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Misal: Cashback 10% Samsung">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Kode Voucher</label>
                    <input type="text" wire:model="code" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase font-mono" placeholder="Opsional (misal: S24CASHBACK)">
                    @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <hr class="border-gray-100">

        {{-- Section 2: Kategori & Akuntansi --}}
        <div>
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Kategori & Akuntansi
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Kategori Promo <span class="text-red-500">*</span></label>
                    <select wire:model.live="category" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="internal">Tanggungan Internal Toko</option>
                        <option value="brand">Sponsor Brand (Klaim)</option>
                    </select>
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if($category === 'brand')
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Brand Sponsor <span class="text-red-500">*</span></label>
                        <select wire:model="brand_id" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Pilih Brand --</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif
                
                <div class="{{ $category !== 'brand' ? 'md:col-span-1' : 'md:col-span-2' }}">
                    <label class="block text-sm font-bold text-gray-700 mb-2">GL Account Accurate (Opsional)</label>
                    <input type="text" wire:model="accurate_account_no" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono" placeholder="Misal: 6100.01">
                    <p class="text-xs text-gray-500 mt-1">Kode akun perkiraan diskon di Accurate Online.</p>
                    @error('accurate_account_no') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <hr class="border-gray-100">

        {{-- Section 3: Nominal Diskon --}}
        <div>
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Aturan Diskon
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Diskon <span class="text-red-500">*</span></label>
                    <select wire:model.live="discount_type" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="fixed">Nominal Tetap (Rp)</option>
                        <option value="percentage">Persentase (%)</option>
                    </select>
                    @error('discount_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Nilai Diskon <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        @if($discount_type === 'fixed')
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">Rp</span>
                            <input type="number" wire:model="discount_value" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" min="0" placeholder="0">
                        @else
                            <input type="number" wire:model="discount_value" class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" min="0" max="100" placeholder="0">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">%</span>
                        @endif
                    </div>
                    @error('discount_value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if($discount_type === 'percentage')
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Maksimal Potongan (Opsional)</label>
                        <div class="relative max-w-md">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">Rp</span>
                            <input type="number" wire:model="max_discount" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" min="0" placeholder="Biarkan kosong jika tanpa batas">
                        </div>
                        @error('max_discount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        </div>

        <hr class="border-gray-100">

        {{-- Section 4: Periode Berlaku --}}
        <div>
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Periode Promo
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Mulai Berlaku</label>
                    <input type="date" wire:model="start_date" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Berakhir Pada</label>
                    <input type="date" wire:model="end_date" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center h-[42px]">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-3 text-sm font-bold text-gray-700">Promo Aktif</span>
                    </label>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Biarkan tanggal kosong jika promo berlaku selamanya.</p>
        </div>

        <div class="pt-6 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-8 rounded-xl shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Simpan Promo
            </button>
        </div>
    </form>
</div>
