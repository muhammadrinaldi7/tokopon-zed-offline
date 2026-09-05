<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center p-2 bg-[#1c69d4]/10 text-[#1c69d4] rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </span>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Sinkronisasi Serial Number & IMEI</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1 pl-10">
                Pantau dan lengkapi data <b>Vendor</b> dan <b>HPP</b> pada Serial Number / IMEI yang terlewat akibat kegagalan webhook.
            </p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <button wire:click="openDocModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-semibold rounded-xl shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Tarik Dokumen RI
            </button>

            <button wire:click="startSyncFilteredHpp"
                wire:loading.attr="disabled"
                wire:confirm="Yakin ingin menyinkronkan HPP (Nearest Cost) untuk seluruh SKU pada filter saat ini?"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#1c69d4] hover:bg-[#1556b0] active:scale-95 text-white text-sm font-semibold rounded-xl shadow-sm transition-all disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Sinkron HPP Terfilter
            </button>
        </div>
    </div>

    {{-- 4 KPI Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Available --}}
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">SN Siap Jual (Available)</p>
                <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($stats['total_available']) }}</h3>
                <p class="text-xs text-emerald-600 font-medium mt-1 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    Stok aktif di sistem
                </p>
            </div>
            <div class="p-3 bg-blue-50 text-[#1c69d4] rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>

        {{-- Card 2: Tanpa Keduanya (Kritis) --}}
        <div wire:click="$set('filterTab', 'missing_both')" 
             class="bg-white p-5 rounded-2xl border-2 {{ $filterTab === 'missing_both' ? 'border-rose-500 bg-rose-50/10' : 'border-gray-200' }} shadow-sm hover:border-rose-300 cursor-pointer transition-all flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-rose-500 uppercase tracking-wider flex items-center gap-1">
                    <span>⚠️</span> Tanpa Vendor & HPP
                </p>
                <h3 class="text-2xl font-black text-rose-600 mt-1">{{ number_format($stats['missing_both']) }}</h3>
                <p class="text-xs text-rose-500 font-medium mt-1">Perlu tindakan segera</p>
            </div>
            <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>

        {{-- Card 3: Tanpa Vendor Saja --}}
        <div wire:click="$set('filterTab', 'missing_vendor')"
             class="bg-white p-5 rounded-2xl border-2 {{ $filterTab === 'missing_vendor' ? 'border-amber-500 bg-amber-50/10' : 'border-gray-200' }} shadow-sm hover:border-amber-300 cursor-pointer transition-all flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Tanpa Vendor</p>
                <h3 class="text-2xl font-black text-amber-600 mt-1">{{ number_format($stats['missing_vendor']) }}</h3>
                <p class="text-xs text-amber-600 font-medium mt-1">Vendor belum terpetakan</p>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
        </div>

        {{-- Card 4: Tanpa HPP --}}
        <div wire:click="$set('filterTab', 'missing_hpp')"
             class="bg-white p-5 rounded-2xl border-2 {{ $filterTab === 'missing_hpp' ? 'border-purple-500 bg-purple-50/10' : 'border-gray-200' }} shadow-sm hover:border-purple-300 cursor-pointer transition-all flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-purple-600 uppercase tracking-wider">Tanpa HPP (Biaya Beli)</p>
                <h3 class="text-2xl font-black text-purple-600 mt-1">{{ number_format($stats['missing_hpp']) }}</h3>
                <p class="text-xs text-purple-600 font-medium mt-1">HPP Rp 0 atau belum ada</p>
            </div>
            <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Main Container: Filter Tabs, Toolbar, and Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Navigation Tabs --}}
        <div class="border-b border-gray-200 px-6 pt-4 bg-gray-50/50 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button wire:click="$set('filterTab', 'missing_both')"
                    class="px-4 py-3 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 {{ $filterTab === 'missing_both' ? 'border-rose-600 text-rose-600 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                    <span>⚠️ Tanpa Vendor & HPP</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterTab === 'missing_both' ? 'bg-rose-100 text-rose-700' : 'bg-gray-200 text-gray-600' }}">{{ $stats['missing_both'] }}</span>
                </button>

                <button wire:click="$set('filterTab', 'missing_vendor')"
                    class="px-4 py-3 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 {{ $filterTab === 'missing_vendor' ? 'border-amber-600 text-amber-600 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                    <span>Tanpa Vendor Saja</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterTab === 'missing_vendor' ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600' }}">{{ $stats['missing_vendor'] }}</span>
                </button>

                <button wire:click="$set('filterTab', 'missing_hpp')"
                    class="px-4 py-3 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 {{ $filterTab === 'missing_hpp' ? 'border-purple-600 text-purple-600 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                    <span>Tanpa HPP Saja</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterTab === 'missing_hpp' ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-600' }}">{{ $stats['missing_hpp'] }}</span>
                </button>

                <button wire:click="$set('filterTab', 'all_missing')"
                    class="px-4 py-3 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 {{ $filterTab === 'all_missing' ? 'border-orange-600 text-orange-600 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                    <span>Semua Bermasalah</span>
                </button>

                <button wire:click="$set('filterTab', 'all')"
                    class="px-4 py-3 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 {{ $filterTab === 'all' ? 'border-[#1c69d4] text-[#1c69d4] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                    <span>Semua Serial Number</span>
                </button>
            </div>
        </div>

        {{-- Toolbar Filters Row --}}
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
            {{-- Search Bar --}}
            <div class="w-full md:w-80 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari IMEI, SKU, Nama Produk..."
                    class="w-full pl-10 pr-4 py-2 text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1c69d4]/20 focus:border-[#1c69d4] transition-all">
            </div>

            {{-- Dropdown Filters --}}
            <div class="flex items-center gap-2.5 flex-wrap w-full md:w-auto">
                {{-- Filter BU --}}
                <select wire:model.live="businessUnitId"
                    class="border-gray-200 text-xs font-semibold focus:ring-[#1c69d4] focus:border-[#1c69d4] text-gray-700 bg-gray-50 py-2 pl-3 pr-8 rounded-xl cursor-pointer">
                    <option value="">Semua Unit Usaha</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>

                {{-- Filter Gudang --}}
                <select wire:model.live="warehouseId"
                    class="border-gray-200 text-xs font-semibold focus:ring-[#1c69d4] focus:border-[#1c69d4] text-gray-700 bg-gray-50 py-2 pl-3 pr-8 rounded-xl cursor-pointer">
                    <option value="">Semua Gudang</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </select>

                {{-- Filter Status SN --}}
                <select wire:model.live="snStatus"
                    class="border-gray-200 text-xs font-semibold focus:ring-[#1c69d4] focus:border-[#1c69d4] text-gray-700 bg-gray-50 py-2 pl-3 pr-8 rounded-xl cursor-pointer">
                    <option value="Available">Hanya Available</option>
                    <option value="Unavailable">Hanya Unavailable</option>
                    <option value="all">Semua Status</option>
                </select>

                {{-- Per Page --}}
                <select wire:model.live="perPage"
                    class="border-gray-200 text-xs font-semibold focus:ring-[#1c69d4] focus:border-[#1c69d4] text-gray-700 bg-gray-50 py-2 pl-3 pr-8 rounded-xl cursor-pointer">
                    <option value="15">15 / hal</option>
                    <option value="25">25 / hal</option>
                    <option value="50">50 / hal</option>
                    <option value="100">100 / hal</option>
                </select>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-50 text-gray-400 font-bold uppercase text-[10px] tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Serial Number / IMEI</th>
                        <th class="py-3 px-4">Nama Produk & SKU</th>
                        <th class="py-3 px-4">Unit Usaha & Gudang</th>
                        <th class="py-3 px-4">Vendor</th>
                        <th class="py-3 px-4">HPP (Harga Beli)</th>
                        <th class="py-3 px-4">Tgl Terima</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($serialNumbers as $index => $sn)
                        <tr class="hover:bg-blue-50/30 transition-colors group">
                            <td class="py-3 px-4 text-center font-medium text-gray-400">
                                {{ $serialNumbers->firstItem() + $index }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-gray-900 text-sm tracking-tight">{{ $sn->serial_number }}</span>
                                    @if($sn->status === 'Available')
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded-md text-[10px] border border-emerald-200">Available</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 font-bold rounded-md text-[10px]">{{ $sn->status }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-bold text-gray-800 line-clamp-1" title="{{ $sn->product_name ?? '-' }}">{{ $sn->product_name ?? '-' }}</p>
                                <span class="font-mono text-gray-400 text-[11px]">SKU: {{ $sn->item_no }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-gray-700">{{ $sn->businessUnit?->name ?? '-' }}</span>
                                    <span class="text-gray-400 text-[11px]">{{ $sn->warehouse?->name ?? 'Gudang Belum Dialokasikan' }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if($sn->vendor)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-800 font-semibold rounded-lg text-xs border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $sn->vendor->vendor_name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-rose-50 text-rose-700 font-bold rounded-lg text-[10px] border border-rose-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Belum Ada Vendor
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if(!empty($sn->hpp) && (float)$sn->hpp > 0)
                                    <span class="font-mono font-bold text-gray-900 text-xs">Rp {{ number_format($sn->hpp, 0, ',', '.') }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-50 text-purple-700 font-bold rounded-lg text-[10px] border border-purple-200">
                                        Rp 0 / Belum Ada
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono text-gray-500">
                                {{ $sn->receipt_date ? \Carbon\Carbon::parse($sn->receipt_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button wire:click="syncSingleSn({{ $sn->id }})"
                                        wire:loading.attr="disabled"
                                        title="Sinkronkan HPP & Vendor IMEI ini dari Accurate"
                                        class="p-1.5 bg-blue-50 hover:bg-[#1c69d4] text-[#1c69d4] hover:text-white rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </button>

                                    <button wire:click="openEditModal({{ $sn->id }})"
                                        title="Edit Manual Vendor / HPP"
                                        class="p-1.5 bg-gray-50 hover:bg-gray-200 text-gray-600 rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm font-semibold text-gray-600">Tidak ada data Serial Number ditemukan</p>
                                <p class="text-xs text-gray-400 mt-1">Coba sesuaikan tab filter atau kata kunci pencarian Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if($serialNumbers->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $serialNumbers->links() }}
            </div>
        @endif
    </div>

    {{-- Collapsible Section: Opsi Sinkronisasi Massal (Legacy & Logs) --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden" x-data="{ open: @entangle('showLegacySection') }">
        <button type="button" @click="open = !open"
            class="w-full p-5 flex items-center justify-between text-left hover:bg-gray-50 transition-colors border-b border-transparent"
            :class="open ? 'border-gray-200' : ''">
            <div class="flex items-center gap-3">
                <span class="p-2 bg-neutral-100 text-neutral-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </span>
                <div>
                    <h4 class="text-sm font-bold text-gray-800">Opsi Sinkronisasi Massal & Terminal Log (Lanjutan)</h4>
                    <p class="text-xs text-gray-400 mt-0.5">Tarik saldo SN seluruh katalog, pemindaian massal arsip Receive Item, atau pantau log proses.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($isSyncing || $isSyncingVendor || $isSyncingHpp)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                        Proses Berjalan...
                    </span>
                @endif
                <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </button>

        <div x-show="open" x-transition class="p-6 space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <button wire:click="startSync"
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-neutral-800 hover:bg-neutral-900 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                    @if($isSyncing)
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sinkron Saldo SN...
                    @else
                        Mulai Sinkronisasi Saldo SN (Per SKU)
                    @endif
                </button>

                <button wire:click="startSyncVendor"
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                    @if($isSyncingVendor)
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sinkron Seluruh Receive Item...
                    @else
                        Pindai Seluruh Arsip Receive Item
                    @endif
                </button>

                <button wire:click="startSyncHpp"
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                    @if($isSyncingHpp)
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Get HPP Semua Item...
                    @else
                        Get HPP Semua Item (Nearest Cost)
                    @endif
                </button>
            </div>

            {{-- Progress Bar --}}
            @if($totalItems > 0)
                <div>
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-bold text-gray-700">Progres Proses:</span>
                        <span class="text-xs font-bold text-[#1c69d4]">{{ $processedItems }} / {{ $totalItems }}</span>
                    </div>
                    @php
                        $percentage = $totalItems > 0 ? round(($processedItems / $totalItems) * 100) : 0;
                    @endphp
                    <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-[#1c69d4] h-2.5 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                    </div>
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp)
                        <p class="text-xs text-gray-500 mt-2 font-mono flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                            </span>
                            {{ $currentItem }}
                        </p>
                    @endif
                </div>
            @endif

            {{-- Terminal Logs --}}
            <div>
                <h4 class="text-xs font-bold text-gray-700 mb-2">Terminal Log Sinkronisasi:</h4>
                <div class="bg-gray-950 rounded-xl p-4 h-56 overflow-y-auto font-mono text-xs shadow-inner">
                    @forelse($logs as $log)
                        <div class="mb-1 text-emerald-400 border-b border-gray-900/60 pb-1">
                            {{ $log }}
                        </div>
                    @empty
                        <div class="text-gray-500 text-center italic mt-20">Belum ada aktivitas log terbaru</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 1: Tarik Dokumen Penerimaan Barang (Receive Item) --}}
    @if($showDocModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity" wire:click="closeDocModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 p-6 space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-900">Tarik Dokumen Penerimaan Barang</h3>
                                <p class="text-xs text-gray-500">Sinkronkan Vendor & HPP langsung dari dokumen Accurate</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeDocModal" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Pilihan Unit Usaha --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Unit Usaha (Database Source):</label>
                        <select wire:model="docBusinessUnitId"
                            class="w-full text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl p-2.5 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                            @foreach($businessUnits as $bu)
                                <option value="{{ $bu->id }}">{{ $bu->name }} ({{ $bu->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Opsi A: Berdasarkan Nomor Dokumen Spesifik --}}
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
                        <h4 class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Opsi 1: Masukkan Nomor Dokumen RI
                        </h4>
                        <p class="text-[11px] text-gray-500">
                            Masukkan Nomor Penerimaan Barang Accurate (misal: <code class="bg-white px-1 py-0.5 rounded border border-gray-200">RI.2026.09.0012</code>) atau ID Dokumen:
                        </p>
                        <div class="flex gap-2">
                            <input type="text" wire:model="docInput"
                                placeholder="Contoh: RI.2026.09.0001 / 14256"
                                class="flex-1 text-xs font-mono bg-white border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                            <button type="button" wire:click="syncSpecificDoc"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-sm disabled:opacity-50">
                                Proses Dokumen
                            </button>
                        </div>
                    </div>

                    {{-- Opsi B: Tarik N Dokumen Terbaru --}}
                    <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 space-y-3">
                        <h4 class="text-xs font-bold text-[#1c69d4] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#1c69d4]"></span>
                            Opsi 2: Tarik Dokumen Terbaru Sekaligus
                        </h4>
                        <p class="text-[11px] text-gray-500">
                            Sistem akan mengambil dokumen Receive Item terbaru yang baru dibuat di Accurate untuk menyapu webhook yang terlewat:
                        </p>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-600">Ambil:</span>
                                <select wire:model="recentDocsLimit" class="text-xs font-bold bg-white border border-gray-200 rounded-lg py-1 px-2">
                                    <option value="15">15 Dokumen Terakhir</option>
                                    <option value="25">25 Dokumen Terakhir</option>
                                    <option value="50">50 Dokumen Terakhir</option>
                                </select>
                            </div>
                            <button type="button" wire:click="syncRecentDocs"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-[#1c69d4] hover:bg-[#1556b0] active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-sm disabled:opacity-50">
                                Tarik Dokumen Terbaru
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL 2: Edit Manual Vendor & HPP --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity" wire:click="closeEditModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-base font-black text-gray-900">Edit Vendor & HPP Serial Number</h3>
                            <p class="text-xs font-mono text-[#1c69d4] mt-0.5">{{ $editingSnNumber }}</p>
                        </div>
                        <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs">
                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Produk:</span>
                        <span class="font-bold text-gray-800">{{ $editingProductName }}</span>
                    </div>

                    {{-- Form Fields --}}
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Vendor:</label>
                            <select wire:model="editVendorId"
                                class="w-full text-xs font-medium bg-white border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-[#1c69d4]">
                                <option value="">-- Pilih Vendor --</option>
                                @foreach($vendors as $vnd)
                                    <option value="{{ $vnd->id }}">{{ $vnd->vendor_name }} ({{ $vnd->vendor_no }})</option>
                                @endforeach
                            </select>
                            @error('editVendorId') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">HPP (Harga Pokok Pembelian):</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-xs font-bold">Rp</span>
                                <input type="number" wire:model="editHpp"
                                    class="w-full pl-9 pr-4 py-2 text-xs font-mono font-bold bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1c69d4]">
                            </div>
                            @error('editHpp') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Terima (Receipt Date):</label>
                            <input type="date" wire:model="editReceiptDate"
                                class="w-full py-2 px-3 text-xs bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1c69d4]">
                            @error('editReceiptDate') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeEditModal"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-xs transition-colors">
                            Batal
                        </button>
                        <button type="button" wire:click="saveManualEdit"
                            class="px-5 py-2 bg-[#1c69d4] hover:bg-[#1556b0] active:scale-95 text-white font-bold rounded-xl text-xs transition-all shadow-sm">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
