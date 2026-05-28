<div>
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Cari Device (IMEI)</h1>
            <p class="text-gray-500 text-sm mt-1">Lacak jejak riwayat fisik (QC) perangkat berdasarkan Serial Number/IMEI</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <form wire:submit="search" class="max-w-xl mx-auto flex flex-col items-center gap-6">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            
            <div class="w-full text-center">
                <label class="block font-bold text-gray-700 mb-3 text-lg">Masukkan IMEI / Serial Number</label>
                <div class="relative">
                    <input type="text" wire:model="imei" 
                        class="w-full text-center text-xl tracking-widest font-mono font-bold bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-4 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder-gray-300"
                        placeholder="Contoh: 351234567890123" autofocus>
                </div>
                @error('imei')
                    <p class="text-rose-500 text-sm mt-2 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" 
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-500/30 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Lacak Riwayat Device
            </button>
        </form>
    </div>
</div>
