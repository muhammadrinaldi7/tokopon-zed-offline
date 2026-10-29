<div>
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Perangkat Buyback & Harga Trade-In</h1>
            <p class="text-sm text-gray-500 mt-1">
                Kelola <strong>Harga Beli (Buy Price)</strong> dan <strong>Harga Jual Custom (Custom Sell Price)</strong> via Excel / CSV tanpa tertimpa sinkronisasi Accurate.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <button wire:click="exportExcel(false)" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all"
                title="Export Excel (.xlsx) seluruh SKU sesuai filter aktif">
                <svg wire:loading.remove wire:target="exportExcel(false)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <svg wire:loading wire:target="exportExcel(false)" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Export Excel (.xlsx)</span>
            </button>

            <button wire:click="exportExcel(true)" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all"
                title="Export Excel ringkas 1 baris per Model + Storage + Proyek (tanpa duplikasi warna)">
                <svg wire:loading.remove wire:target="exportExcel(true)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <svg wire:loading wire:target="exportExcel(true)" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Export Ringkas (Tanpa Warna)</span>
            </button>

            <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-3.5 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all"
                title="Export format CSV (; delimiter)">
                <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>CSV</span>
            </button>

            <button wire:click="$set('showImportModal', true)"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span>Import Excel / CSV</span>
            </button>
        </div>
    </div>

    <div
        class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden mb-6">
        <div
            class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col xl:flex-row gap-4 items-center justify-between">
            <div class="flex flex-col md:flex-row items-center gap-3 w-full">
                {{-- Search Bar --}}
                <div class="relative w-full md:w-64">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari SKU atau nama..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white transition-all shadow-sm">
                </div>

                {{-- Filters --}}
                <div class="flex items-center gap-2 w-full overflow-x-auto pb-1 md:pb-0">
                    <select wire:model.live="filterBrandName"
                        class="w-full md:w-auto border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white text-gray-700 shadow-sm min-w-[140px]">
                        <option value="">Semua Merek</option>
                        @foreach ($availableBrands as $brand)
                            <option value="{{ $brand }}">{{ $brand }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterOs"
                        class="w-full md:w-auto border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white text-gray-700 shadow-sm min-w-[120px]">
                        <option value="">Semua OS</option>
                        @foreach ($availableOs as $os)
                            <option value="{{ $os }}">{{ $os }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterCategoryName"
                        class="w-full md:w-auto border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white text-gray-700 shadow-sm min-w-[140px]">
                        <option value="">Semua Kategori</option>
                        @foreach ($availableCategories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterProyek"
                        class="w-full md:w-auto border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white text-gray-700 shadow-sm min-w-[140px]">
                        <option value="">Semua Proyek</option>
                        @foreach ($availableProyeks as $proyek)
                            <option value="{{ $proyek }}">{{ $proyek }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div wire:loading wire:target="search, filterBrandName, filterCategoryName, filterProyek, filterOs"
                class="text-xs text-gray-500 flex items-center gap-2 flex-shrink-0">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
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
                        <th class="p-4">Merek / OS</th>
                        <th class="p-4">Kategori / Proyek</th>
                        <th class="p-4">Harga Accurate (Ref)</th>
                        <th class="p-4">Harga Beli (Buy Price)</th>
                        <th class="p-4">Harga Jual Custom (2nd)</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($devices as $device)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="p-4">
                                <span class="font-bold text-gray-700 font-mono text-xs">{{ $device->item_no }}</span>
                                <span class="block text-[11px] text-gray-400">ID: {{ $device->id }}</span>
                            </td>
                            <td class="p-4 min-w-[220px]">
                                <p class="font-bold text-gray-900">{{ $device->name }}</p>
                            </td>
                            <td class="p-4">
                                <span class="text-gray-700 font-semibold block text-sm">{{ $device->brandName ?: '-' }}</span>
                                <span
                                    class="inline-flex mt-1 px-2 py-0.5 {{ strtoupper((string) $device->os) === 'IOS' ? 'bg-sky-50 text-sky-700' : (strtoupper((string) $device->os) === 'ANDROID' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600') }} rounded text-[11px] font-bold">
                                    {{ $device->os ?: '-' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-gray-600 font-semibold block text-xs">{{ $device->categoryName ?: '-' }}</span>
                                <span
                                    class="inline-flex mt-1 px-2.5 py-0.5 bg-violet-50 text-violet-700 rounded-lg text-[11px] font-bold">
                                    {{ $device->proyek ?: '-' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <p class="font-semibold text-gray-500 text-sm">
                                    Rp {{ number_format((float) ($device->base_price ?? 0), 0, ',', '.') }}
                                </p>
                                @if ((float) ($device->base_price ?? 0) > 0 && (float) ($device->base_price ?? 0) <= 100)
                                    <span class="inline-block text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded mt-0.5">
                                        Placeholder Accurate
                                    </span>
                                @endif
                            </td>
                            <td class="p-4">
                                <p class="font-black text-emerald-600">
                                    Rp {{ number_format((float) ($device->buy_price ?? 0), 0, ',', '.') }}
                                </p>
                            </td>
                            <td class="p-4">
                                @if (($device->custom_sell_price ?? 0) > 0)
                                    <p class="font-black text-[#1c69d4]">
                                        Rp {{ number_format((float) $device->custom_sell_price, 0, ',', '.') }}
                                    </p>
                                    <span class="inline-block text-[10px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded mt-0.5">
                                        Custom Excel
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum diatur (Fallback Accurate)</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <button wire:click="editDevice({{ $device->id }})"
                                    class="p-2 text-[#1c69d4] hover:bg-[#eff6ff] rounded-lg transition"
                                    title="Edit Harga Beli & Harga Jual Custom">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500">
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

        @if ($devices->hasPages())
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
                class="relative transform overflow-hidden rounded-2xl bg-white/95 backdrop-blur-2xl border border-white shadow-xl text-left w-full max-w-lg">

                {{-- Header --}}
                <div class="px-6 py-5 border-b border-gray-200/50 flex justify-between items-center bg-white/40">
                    <div>
                        <h2 class="text-[17px] font-bold tracking-tight text-gray-900">Ubah Harga Perangkat Trade-In</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Nilai ini aman dan tidak akan tertimpa sinkronisasi Accurate</p>
                    </div>
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

                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">SKU</span>
                                <p class="text-sm font-mono font-bold text-gray-700">{{ $editItemNo }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Harga Accurate (Ref)</span>
                                <p class="text-sm font-bold text-gray-600">Rp {{ number_format($editAccuratePrice, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-gray-200/60">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Nama Barang</span>
                            <p class="text-sm font-semibold text-gray-800">{{ $editName }}</p>
                        </div>
                    </div>

                    {{-- Buy Price --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">
                            Harga Beli / Terima HP Lama (<span class="text-emerald-600">Buy Price</span>)
                        </label>
                        <div class="relative" wire:key="device-edit-buy-price-{{ $editingDeviceId }}" x-data="{
                            rawVal: @entangle('editBuyPrice'),
                            get maskedVal() {
                                if (this.rawVal === null || this.rawVal === undefined || this.rawVal === '') return '';
                                let num = typeof this.rawVal === 'number' ? Math.round(this.rawVal) : Math.round(Number(this.rawVal) || 0);
                                if (isNaN(num)) return '';
                                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            },
                            set maskedVal(val) {
                                if (!val) {
                                    this.rawVal = 0;
                                    return;
                                }
                                let clean = val.toString().replace(/\D/g, '');
                                this.rawVal = clean !== '' ? parseInt(clean, 10) : 0;
                            }
                        }">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">Rp</span>
                            <input type="text" x-model="maskedVal"
                                @keydown="
                                    if ($event.ctrlKey || $event.metaKey || ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter'].includes($event.key)) return;
                                    if (!/[0-9]/.test($event.key)) $event.preventDefault();
                                "
                                class="w-full text-[15px] bg-white border border-gray-300 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 rounded-lg pl-11 pr-4 py-2.5 shadow-sm transition-all text-gray-900 font-bold font-mono"
                                placeholder="0"
                                required>
                        </div>
                        @error('editBuyPrice')
                            <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Custom Sell Price --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">
                            Harga Jual Unit Second (<span class="text-[#1c69d4]">Custom Sell Price</span>)
                        </label>
                        <div class="relative" wire:key="device-edit-sell-price-{{ $editingDeviceId }}" x-data="{
                            rawVal: @entangle('editCustomSellPrice'),
                            get maskedVal() {
                                if (this.rawVal === null || this.rawVal === undefined || this.rawVal === '') return '';
                                let num = typeof this.rawVal === 'number' ? Math.round(this.rawVal) : Math.round(Number(this.rawVal) || 0);
                                if (isNaN(num)) return '';
                                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            },
                            set maskedVal(val) {
                                if (!val) {
                                    this.rawVal = 0;
                                    return;
                                }
                                let clean = val.toString().replace(/\D/g, '');
                                this.rawVal = clean !== '' ? parseInt(clean, 10) : 0;
                            }
                        }">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">Rp</span>
                            <input type="text" x-model="maskedVal"
                                @keydown="
                                    if ($event.ctrlKey || $event.metaKey || ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter'].includes($event.key)) return;
                                    if (!/[0-9]/.test($event.key)) $event.preventDefault();
                                "
                                class="w-full text-[15px] bg-white border border-gray-300 focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg pl-11 pr-4 py-2.5 shadow-sm transition-all text-gray-900 font-bold font-mono"
                                placeholder="0">
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">
                            Isi <code>0</code> jika ingin mereset dan mengikuti fallback Harga Accurate (<code>base_price</code>).
                        </p>
                        @error('editCustomSellPrice')
                            <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Propagate to color group --}}
                    <label class="flex items-start gap-3 p-3.5 rounded-xl border border-indigo-100 bg-indigo-50/60 cursor-pointer">
                        <input type="checkbox" wire:model="applyToColorGroup"
                            class="mt-0.5 rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                        <span class="text-xs text-indigo-900 leading-relaxed">
                            <strong class="block font-bold">Samakan ke seluruh varian warna dalam grup ini</strong>
                            Otomatis menerapkan harga yang sama ke semua warna dengan <strong>Model, Storage & Proyek</strong> yang sama di unit bisnis aktif.
                        </span>
                    </label>

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

    {{-- Import Excel / CSV Modal --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                wire:click="closeImportModal"></div>

            {{-- Modal Content --}}
            <div
                class="relative bg-white rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Import Harga Excel / CSV</h3>
                        <p class="text-xs text-gray-500 mt-1">Mass update Buy Price & Custom Sell Price (Unit Second)</p>
                    </div>
                    <button wire:click="closeImportModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-2 bg-white rounded-full hover:bg-gray-100 border border-gray-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($importSummary)
                    {{-- Summary View --}}
                    <div class="p-6 space-y-5">
                        <div class="text-center py-1">
                            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2.5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900">Import Selesai Diproses</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Hasil verifikasi & pembaruan Buy Price / Custom Sell Price</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 border border-gray-200/80 rounded-xl p-3.5 text-center">
                                <p class="text-xs text-gray-500 font-medium">Total Baris File</p>
                                <p class="text-xl font-black text-gray-800 mt-1">{{ number_format($importSummary['total'], 0, ',', '.') }}</p>
                            </div>
                            <div class="bg-emerald-50 border border-emerald-200/80 rounded-xl p-3.5 text-center">
                                <p class="text-xs text-emerald-700 font-bold">Baris Diperbarui</p>
                                <p class="text-xl font-black text-emerald-700 mt-1">{{ number_format($importSummary['updated'], 0, ',', '.') }}</p>
                            </div>
                            <div class="bg-blue-50 border border-blue-200/80 rounded-xl p-3.5 text-center">
                                <p class="text-xs text-blue-700 font-medium">Harga Sama (Dilewati)</p>
                                <p class="text-xl font-black text-blue-700 mt-1">{{ number_format($importSummary['unchanged'], 0, ',', '.') }}</p>
                            </div>
                            <div class="bg-amber-50 border border-amber-200/80 rounded-xl p-3.5 text-center">
                                <p class="text-xs text-amber-700 font-medium">Tidak Ditemukan</p>
                                <p class="text-xl font-black text-amber-700 mt-1">{{ number_format($importSummary['skipped'], 0, ',', '.') }}</p>
                            </div>
                        </div>

                        @if (($importSummary['group_synced'] ?? 0) > 0)
                            <div class="p-3.5 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-800 font-medium text-center">
                                Ditambah <strong>{{ number_format($importSummary['group_synced'], 0, ',', '.') }} varian warna</strong> lainnya dalam grup Model + Storage + Proyek yang sama ikut disamakan secara otomatis.
                            </div>
                        @endif

                        <div class="p-3.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-600 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>Sistem hanya mengupdate produk yang harganya berubah. Produk yang harganya tetap sama otomatis dilewati tanpa menyentuh database, dan seluruh nilai <strong>Custom Sell Price</strong> maupun <strong>Buy Price</strong> aman dari sinkronisasi Accurate.</p>
                        </div>

                        <button type="button" wire:click="closeImportModal"
                            class="w-full bg-[#1c69d4] hover:bg-[#1553a8] text-white py-3 rounded-xl text-sm font-bold shadow-sm transition-all">
                            Tutup
                        </button>
                    </div>
                @else
                    {{-- Form View --}}
                    <form wire:submit.prevent="importCsv" class="p-6 space-y-5">
                        <div
                            class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl text-sm text-indigo-800 space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p><strong>Panduan & Template:</strong></p>
                                <div class="flex items-center gap-3">
                                    <button type="button" wire:click="downloadTemplate('xlsx')" class="text-xs font-bold text-emerald-700 hover:text-emerald-900 underline flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Template Excel (.xlsx)
                                    </button>
                                    <button type="button" wire:click="downloadTemplate('csv')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Template CSV
                                    </button>
                                </div>
                            </div>
                            <ol class="list-decimal pl-5 space-y-1 text-xs leading-relaxed">
                                <li>Pilih filter (misal Merek <code>APPLE</code> & Kategori <code>HP SECOND</code>), lalu klik <strong>Export Excel (.xlsx)</strong> atau <strong>Export Ringkas (Tanpa Warna)</strong>.</li>
                                <li>Ubah kolom <code class="bg-white px-1 py-0.5 rounded border border-indigo-200">Buy Price</code> (harga terima HP lama) dan/atau <code class="bg-white px-1 py-0.5 rounded border border-indigo-200">Custom Sell Price</code> (harga jual HP Second).</li>
                                <li><strong>Jangan ubah kolom ID / SKU.</strong> Jika Anda hanya mengimpor file hasil <strong>Export Ringkas (Tanpa Warna)</strong>, centang opsi penyamaan grup warna di bawah ini.</li>
                            </ol>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">File Excel (.xlsx / .xls) atau CSV (.csv)</label>
                            <input type="file" wire:model="csvFile" accept=".xlsx,.xls,.csv,.txt" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                            @error('csvFile')
                                <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                            <div wire:loading wire:target="csvFile"
                                class="text-xs text-indigo-600 mt-2 flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Mengunggah file...
                            </div>
                        </div>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-teal-200 bg-teal-50/60 cursor-pointer">
                            <input type="checkbox" wire:model="syncColorGroupOnImport"
                                class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-600">
                            <span class="text-xs text-teal-900 leading-relaxed">
                                <strong class="block font-bold">Samakan harga ke seluruh warna dalam grup Model + Storage + Proyek yang sama</strong>
                                Centang ini jika menggunakan file <strong>Export Ringkas (Tanpa Warna)</strong> atau jika Anda cukup mengubah 1 warna saja di Excel untuk mewakili semua warna lainnya.
                            </span>
                        </label>

                        {{-- Loading Banner --}}
                        <div wire:loading wire:target="importCsv"
                            class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs flex items-center gap-3">
                            <svg class="w-5 h-5 text-amber-600 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <div>
                                <p class="font-bold text-amber-900">Sedang memproses data Excel / CSV...</p>
                                <p class="text-amber-700 mt-0.5">Sistem membandingkan harga per 250 data. Hanya produk yang harganya berubah yang disimpan ke database. Mohon jangan tutup halaman ini.</p>
                            </div>
                        </div>

                        <div class="pt-4 flex gap-3 border-t border-gray-100">
                            <button type="button" wire:click="closeImportModal" wire:loading.attr="disabled" wire:target="importCsv"
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl text-[15px] font-bold transition-all disabled:opacity-50">
                                Batal
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="importCsv"
                                class="flex-[2] flex items-center justify-center gap-2 bg-indigo-600 text-white py-3 rounded-xl text-[15px] font-bold hover:bg-indigo-700 shadow-sm transition-all disabled:opacity-50">
                                <svg wire:loading wire:target="importCsv" class="w-5 h-5 animate-spin" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span wire:loading.remove wire:target="importCsv">Mulai Import Data</span>
                                <span wire:loading wire:target="importCsv">Sedang Memproses...</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
