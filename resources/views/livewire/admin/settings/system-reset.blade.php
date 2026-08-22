<div class="p-6 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">System Reset Data</h1>
            <p class="text-sm text-gray-500">Pembersihan data selektif untuk keperluan migrasi Accurate.</p>
        </div>
        <button wire:click="exportPendingSO" class="bg-indigo-600 hover:bg-indigo-700 transition-colors text-white px-5 py-2.5 rounded-lg font-medium shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download SO Belum Lunas
        </button>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl mb-6 border border-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 text-red-800 p-4 rounded-xl mb-6 border border-red-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl mb-8 shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold text-amber-800">Peringatan: Tindakan Ini Bersifat Permanen</h3>
                <div class="mt-2 text-sm text-amber-700">
                    <p>Mereset sistem akan <strong>menghapus permanen</strong> data pada tabel yang dipilih. Selalu pastikan Anda telah mengunduh backup (misalnya data SO yang menggantung) sebelum melanjutkan.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-800">Pre-flight Summary & Checklist</h3>
            <div class="flex gap-2">
                <button wire:click="selectAll" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium px-3 py-1 rounded-lg hover:bg-indigo-50 transition-colors">Pilih Semua</button>
                <button wire:click="deselectAll" class="text-sm text-gray-600 hover:text-gray-800 font-medium px-3 py-1 rounded-lg hover:bg-gray-100 transition-colors">Batal Pilih</button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
            @foreach($preflightData as $key => $data)
            <div class="p-6 hover:bg-gray-50/50 transition-colors cursor-pointer" wire:click="$set('selectedCategories.{{ $key }}', ! $wire.selectedCategories['{{ $key }}'])">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 mt-1">
                        <div class="relative flex items-center justify-center">
                            <input type="checkbox" wire:model="selectedCategories.{{ $key }}" class="peer h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer" onclick="event.stopPropagation();">
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-gray-900 mb-2">{{ $data['label'] }}</h4>
                        <div class="space-y-1">
                            @foreach($data['tables'] as $table => $count)
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500 font-mono">{{ $table }}</span>
                                    <span class="font-bold {{ $count > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ number_format($count) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="bg-gray-50/80 px-6 py-5 border-t border-gray-100 flex justify-end">
            <button wire:click="openConfirmModal" class="bg-red-600 hover:bg-red-700 transition-colors text-white px-8 py-3 rounded-xl font-bold shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Eksekusi Reset Terpilih
            </button>
        </div>
    </div>

    {{-- Confirm Modal --}}
    @if($showConfirmModal)
    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col">
            <div class="bg-red-50 px-6 py-5 border-b border-red-100 flex items-center gap-3">
                <div class="bg-red-100 p-2 rounded-full text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-red-800">Konfirmasi Eksekusi Reset</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700 text-sm mb-4">Anda akan menghapus data pada kategori berikut secara permanen:</p>
                <ul class="list-disc list-inside text-sm text-gray-600 mb-6 bg-gray-50 p-4 rounded-lg space-y-1">
                    @foreach($selectedCategories as $key => $isSelected)
                        @if($isSelected && isset($preflightData[$key]))
                            <li><strong>{{ $preflightData[$key]['label'] }}</strong></li>
                        @endif
                    @endforeach
                </ul>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Untuk melanjutkan, ketik: <strong class="text-gray-900 select-none">RESET SISTEM</strong></label>
                    <input type="text" wire:model="confirmText" placeholder="Ketik kata kunci di sini..." 
                        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all font-mono text-center text-lg font-bold">
                    @error('confirmText') <span class="text-red-500 text-xs mt-1 block text-center">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button wire:click="$set('showConfirmModal', false)" class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                <button wire:click="executeReset" class="px-6 py-2.5 text-sm font-bold text-white bg-red-600 border border-transparent rounded-xl hover:bg-red-700 transition-colors shadow-sm flex items-center gap-2">
                    <span wire:loading.remove wire:target="executeReset">Saya Yakin, Hapus Data</span>
                    <span wire:loading wire:target="executeReset">Mengeksekusi...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
