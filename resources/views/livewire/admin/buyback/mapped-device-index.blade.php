<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Perangkat Mapped / Tier</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar produk Accurate yang sudah dipetakan ke Tier Buyback.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="exportTemplate" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg wire:loading.remove wire:target="exportTemplate" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <svg wire:loading wire:target="exportTemplate" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Download Template
            </button>

            <button wire:click="exportExcel" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Export Excel
            </button>

            <button @click="$wire.set('showImportModal', true)"
                class="flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Excel
            </button>

            <a href="{{ route('admin.buyback.create') }}" wire:navigate
                class="flex items-center gap-2 bg-[#1c69d4] hover:bg-[#1553a8] text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Mapping
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col xl:flex-row gap-4 items-center justify-between">
            <div class="flex items-center gap-3 w-full">
                {{-- Search Bar --}}
                <div class="relative w-full md:w-64">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama tier..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white transition-all shadow-sm">
                </div>
            </div>
            
            <div wire:loading wire:target="search" class="text-xs text-gray-500 flex items-center gap-2 flex-shrink-0">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Memuat data...
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($tiers as $tier)
                    <div class="group relative bg-white rounded-2xl border border-gray-100 hover:border-blue-200 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden flex flex-col">
                        <!-- Decorative top gradient -->
                        <div class="h-2 w-full bg-gradient-to-r from-[#1c69d4] to-[#7C74F0] opacity-80 group-hover:opacity-100 transition-opacity"></div>
                        
                        <div class="p-5 flex-1 flex flex-col">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-blue-100 transition-all duration-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold border border-emerald-100">
                                    {{ $tier->devices_count }} Perangkat
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-[#1c69d4] transition-colors">{{ $tier->name }}</h3>
                            <p class="text-sm text-gray-500 mb-6 flex-1">Kategori tier untuk potongan harga buyback.</p>
                            
                            <a href="{{ route('admin.buyback.mapped-devices.show', $tier->id) }}" wire:navigate
                                class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-bold text-[#1c69d4] bg-[#eff6ff] group-hover:bg-[#1c69d4] group-hover:text-white rounded-xl transition-all duration-300">
                                Kelola Perangkat
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 flex flex-col items-center justify-center bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                        <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Belum Ada Tier</h3>
                        <p class="text-sm text-gray-500 max-w-sm text-center">Silakan buat tier buyback terlebih dahulu sebelum me-mapping perangkat.</p>
                    </div>
                @endforelse
            </div>
        </div>
        
        @if($tiers->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50/30">
            {{ $tiers->links() }}
        </div>
        @endif
    </div>

    <!-- Import Modal -->
    <div x-show="$wire.showImportModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="$wire.showImportModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75 backdrop-blur-sm"></div>
            </div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="$wire.showImportModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                Import Data Excel
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    Unggah file .xlsx hasil export untuk melakukan update massal mapping tier perangkat beserta harganya.
                                </p>
                                
                                <form wire:submit="importExcel">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel (.xlsx)</label>
                                        <input type="file" wire:model="excelFile" accept=".xlsx,.xls"
                                            class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-xl file:border-0
                                            file:text-sm file:font-bold
                                            file:bg-amber-50 file:text-amber-700
                                            hover:file:bg-amber-100 transition-colors">
                                        @error('excelFile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                        <button type="submit" wire:loading.attr="disabled"
                                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                            <svg wire:loading wire:target="importExcel" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span wire:loading.remove wire:target="importExcel">Import Data</span>
                                            <span wire:loading wire:target="importExcel">Memproses...</span>
                                        </button>
                                        <button type="button" @click="$wire.set('showImportModal', false)"
                                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors">
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
