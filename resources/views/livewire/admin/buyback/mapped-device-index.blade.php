<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Perangkat Mapped / Tier</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar produk Accurate yang sudah dipetakan ke Tier Buyback.</p>
        </div>
        <div class="flex items-center gap-3">
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
</div>
