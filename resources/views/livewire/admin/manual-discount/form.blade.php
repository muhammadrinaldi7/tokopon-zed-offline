<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.manual-discount.index') }}" wire:navigate
            class="p-2 hover:bg-gray-100 rounded-xl transition-colors text-gray-500">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $presetId ? 'Edit Preset Diskon' : 'Tambah Preset Diskon' }}</h2>
            <p class="text-sm text-gray-500 mt-1">Konfigurasi nominal diskon internal cashback.</p>
        </div>
    </div>

    <form wire:submit="save" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        
        <!-- Nominal -->
        <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700">Nominal Diskon (Rp) <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-4 top-2.5 text-gray-500 font-medium">Rp</span>
                <input type="number" wire:model="amount" placeholder="Contoh: 25000"
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:border-[#4E44DB] focus:ring-2 focus:ring-[#4E44DB]/20 transition-all outline-none">
            </div>
            @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            <p class="text-xs text-gray-500">Masukkan angka saja tanpa titik (contoh: 25000 untuk dua puluh lima ribu).</p>
        </div>

        <!-- Brand -->
        <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700">Berlaku Untuk Brand (Opsional)</label>
            <select wire:model="brand_id"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:border-[#4E44DB] focus:ring-2 focus:ring-[#4E44DB]/20 transition-all outline-none">
                <option value="">-- Berlaku Untuk Semua Brand --</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </select>
            @error('brand_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            <p class="text-xs text-gray-500">Jika dikosongkan, tombol diskon ini akan muncul di seluruh produk apa pun.</p>
        </div>

        <!-- Status Active -->
        <div class="flex items-center gap-3 pt-2">
            <button type="button" wire:click="$toggle('is_active')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#4E44DB] focus:ring-offset-2 {{ $is_active ? 'bg-[#4E44DB]' : 'bg-gray-200' }}" role="switch" aria-checked="false">
                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
            </button>
            <label class="text-sm font-semibold text-gray-700 cursor-pointer" wire:click="$toggle('is_active')">Aktifkan Preset Ini</label>
        </div>

        <!-- Submit -->
        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit"
                class="bg-[#4E44DB] hover:bg-[#3d35b3] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Simpan
            </button>
        </div>
    </form>
</div>
