<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Perangkat Buyback</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data harga dasar HP untuk fitur Tukar Tambah & Jual HP.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.buyback.create') }}" wire:navigate
                class="flex items-center gap-2 bg-[#1c69d4] hover:bg-[#1553a8] text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Mapping Tier / Perangkat Baru
            </a>
            <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export (CSV)
            </button>
            <button wire:click="$set('showImportModal', true)"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import (CSV)
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col xl:flex-row gap-4 items-center justify-between">
            <div class="flex flex-col md:flex-row items-center gap-3 w-full">
                {{-- Search Bar --}}
                <div class="relative w-full md:w-64">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari SKU atau nama..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white transition-all shadow-sm">
                </div>

                {{-- Filters --}}
                <div class="flex items-center gap-2 w-full overflow-x-auto pb-1 md:pb-0">
                    <select wire:model.live="filterBrandName" class="w-full md:w-auto border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white text-gray-700 shadow-sm min-w-[140px]">
                        <option value="">Semua Merek</option>
                        @foreach($availableBrands as $brand)
                            <option value="{{ $brand }}">{{ $brand }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterCategoryName" class="w-full md:w-auto border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white text-gray-700 shadow-sm min-w-[140px]">
                        <option value="">Semua Kategori</option>
                        @foreach($availableCategories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterProyek" class="w-full md:w-auto border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white text-gray-700 shadow-sm min-w-[140px]">
                        <option value="">Semua Proyek</option>
                        @foreach($availableProyeks as $proyek)
                            <option value="{{ $proyek }}">{{ $proyek }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div wire:loading wire:target="search, filterBrandName, filterCategoryName, filterProyek" class="text-xs text-gray-500 flex items-center gap-2 flex-shrink-0">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Memuat data...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50 border-b border-gray-100 text-xs text-gray-400 font-bold uppercase tracking-wider">
                        <th class="p-4">SKU (No. Item)</th>
                        <th class="p-4">Nama Barang</th>
                        <th class="p-4">Merek</th>
                        <th class="p-4">Kategori</th>
                        <th class="p-4">Proyek</th>
                        <th class="p-4">Harga Beli (Buy Price)</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($devices as $device)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="p-4">
                                <span class="font-bold text-gray-700">{{ $device->item_no }}</span>
                            </td>
                            <td class="p-4 min-w-[200px]">
                                <p class="font-bold text-gray-900">{{ $device->name }}</p>
                            </td>
                            <td class="p-4">
                                <span class="text-gray-600 font-semibold">{{ $device->brandName ?: '-' }}</span>
                            </td>
                            <td class="p-4">
                                <span class="text-gray-600 font-semibold">{{ $device->categoryName ?: '-' }}</span>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg text-[11px] font-bold">
                                    {{ $device->proyek ?: '-' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <p class="font-black text-emerald-600">Rp
                                    {{ number_format($device->buy_price ?? 0, 0, ',', '.') }}</p>
                            </td>
                            <td class="p-4 text-right">
                                <button wire:click="editDevice({{ $device->id }})"
                                    class="p-2 text-[#1c69d4] hover:bg-[#eff6ff] rounded-lg transition"
                                    title="Edit Harga">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <p class="font-medium text-gray-900">Belum ada perangkat yang dikonfigurasi dari Accurate</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($devices->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50/30">
            {{ $devices->links() }}
        </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div wire:click="closeEditModal" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"></div>

            <div
                class="relative transform overflow-hidden rounded-2xl bg-white/90 backdrop-blur-2xl border border-white shadow-xl text-left w-full max-w-md">

                {{-- Header --}}
                <div class="px-6 py-5 border-b border-gray-200/50 flex justify-between items-center bg-white/40">
                    <h2 class="text-[17px] font-semibold tracking-tight text-gray-900">Ubah Harga Beli</h2>
                    <button wire:click="closeEditModal"
                        class="text-gray-400 hover:text-gray-600 bg-gray-100/50 hover:bg-gray-200/50 rounded-full p-1.5 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="updateDevice" class="p-6 space-y-5">
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">SKU</label>
                            <input type="text" wire:model="editItemNo"
                                class="w-full text-sm bg-gray-100 border-none rounded px-3 py-2 text-gray-600 font-mono"
                                readonly disabled>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Barang</label>
                            <input type="text" wire:model="editName"
                                class="w-full text-sm bg-gray-100 border-none rounded px-3 py-2 text-gray-700 font-semibold"
                                readonly disabled>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Harga Beli (Buy Price)</label>
                        <div class="relative">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">Rp</span>
                            <input type="number" wire:model="editBuyPrice" min="0" step="1000"
                                class="w-full text-[15px] bg-white border border-gray-300 focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg pl-10 pr-4 py-3 shadow-sm transition-all text-gray-900 font-bold"
                                required>
                        </div>
                        @error('editBuyPrice')
                            <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                        @if ($editBuyPrice > 0)
                            <p class="text-xs text-gray-500 mt-1.5 ml-1">= Rp
                                {{ number_format($editBuyPrice, 0, ',', '.') }}</p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="pt-2 flex gap-3">
                        <button type="button" wire:click="closeEditModal"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-lg text-[15px] font-semibold transition-all">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 bg-[#1c69d4] text-white py-3 rounded-lg text-[15px] font-semibold hover:bg-[#3f36b8] hover:shadow-sm hover:shadow-[#1c69d4]/30 active:scale-[0.98] transition-all">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Import CSV Modal --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                wire:click="$set('showImportModal', false)"></div>

            {{-- Modal Content --}}
            <div class="relative bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Import CSV</h3>
                        <p class="text-xs text-gray-500 mt-1">Mass update Buy Price</p>
                    </div>
                    <button wire:click="$set('showImportModal', false)"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-2 bg-white rounded-full hover:bg-gray-100 border border-gray-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="importCsv" class="p-6 space-y-5">
                    <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl text-sm text-indigo-800 space-y-2">
                        <p><strong>Cara Penggunaan:</strong></p>
                        <ol class="list-decimal pl-5 space-y-1 text-xs">
                            <li>Klik tombol <strong>Export (CSV)</strong> untuk mendownload template dan data saat ini.</li>
                            <li>Buka file CSV tersebut di Excel / Spreadsheet.</li>
                            <li>Edit kolom <code class="bg-white px-1 py-0.5 rounded border border-indigo-200">Buy Price</code> sesuai keinginan. <strong>Jangan ubah kolom ID.</strong></li>
                            <li>Simpan kembali dalam format CSV, lalu upload di sini.</li>
                        </ol>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">File CSV</label>
                        <input type="file" wire:model="csvFile" accept=".csv" required
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        @error('csvFile') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="csvFile" class="text-xs text-indigo-600 mt-2 flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Mengunggah file...
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-gray-100">
                        <button type="button" wire:click="$set('showImportModal', false)"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl text-[15px] font-bold transition-all">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="flex-[2] flex items-center justify-center gap-2 bg-indigo-600 text-white py-3 rounded-xl text-[15px] font-bold hover:bg-indigo-700 shadow-sm transition-all">
                            <svg wire:loading wire:target="importCsv" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="importCsv">Mulai Import Data</span>
                            <span wire:loading wire:target="importCsv">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
