<div class="p-4 md:p-6 lg:p-8 bg-[#f7f7f7] min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <div class="mb-4">
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Aktivasi Garansi</h1>
                <p class="text-sm text-gray-500 mt-1">Pantau data aktivasi dan performa Promotor / Inspektur</p>
            </div>

            <div class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-3">
                <div class="flex items-center gap-3 bg-white p-2 rounded-xl border border-gray-200 shadow-sm flex-wrap">
                    <select wire:model.live="dateRange"
                        class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent py-1.5 pl-3 pr-8 rounded-lg cursor-pointer hover:bg-gray-50">
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="this_year">Tahun Ini</option>
                        <option value="custom">Kustom</option>
                    </select>

                    @if ($dateRange === 'custom')
                        <div class="hidden sm:block h-6 w-px bg-gray-200"></div>
                        <div class="flex items-center gap-2 px-2 border-gray-100">
                            <input type="date" wire:model.live="startDate"
                                class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5 w-full sm:w-auto">
                            <span class="text-gray-400 text-sm">-</span>
                            <input type="date" wire:model.live="endDate"
                                class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5 w-full sm:w-auto">
                        </div>
                    @endif
                </div>

                <div class="flex gap-2 flex-wrap mt-2 sm:mt-0">
                    <div
                        class="flex items-center gap-2 bg-white px-2 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                        <label class="text-xs text-gray-500 font-medium">Separator</label>
                        <select wire:model.live="csvSeparator"
                            class="border-none text-xs font-bold focus:ring-0 text-gray-700 bg-transparent py-2 pl-1 pr-3 cursor-pointer hover:bg-gray-50 rounded-lg">
                            <option value=";">Titik Koma (;)</option>
                            <option value=",">Koma (,)</option>
                        </select>
                    </div>
                    <div
                        class="flex items-center gap-2 bg-white px-2 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                        <label class="text-xs text-gray-500 font-medium">Status Aktivasi</label>
                        <select wire:model.live="activationStatus"
                            class="border-none text-xs font-bold focus:ring-0 text-gray-700 bg-transparent py-2 pl-1 pr-3 cursor-pointer hover:bg-gray-50 rounded-lg">
                            <option value="all">Semua</option>
                            <option value="activated">Sudah Diaktivasi</option>
                            <option value="unactivated">Belum Diaktivasi</option>
                        </select>
                    </div>

                    <div
                        class="flex items-center gap-2 bg-white px-2 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                        <label class="text-xs text-gray-500 font-medium">Cabang</label>
                        <select wire:model.live="branchId"
                            class="border-none text-xs font-bold focus:ring-0 text-gray-700 bg-transparent py-2 pl-1 pr-3 cursor-pointer hover:bg-gray-50 rounded-lg">
                            <option value="">Semua Cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button wire:click="exportCsv" wire:loading.attr="disabled"
                        class="flex items-center gap-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 disabled:opacity-75 disabled:cursor-wait text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Export CSV
                    </button>
                    <button type="button"
                        x-data="{ isDownloading: false }"
                        @click="
                            isDownloading = true;
                            const url = new URL('{{ route('reporting.warranty.pdf') }}');
                            url.searchParams.set('startDate', $wire.startDate);
                            url.searchParams.set('endDate', $wire.endDate);
                            url.searchParams.set('search', $wire.search);
                            url.searchParams.set('branchId', $wire.branchId || '');
                            url.searchParams.set('activationStatus', $wire.activationStatus);
                            
                            window.location.href = url.toString();
                            
                            setTimeout(() => isDownloading = false, 8000);
                            window.addEventListener('focus', () => isDownloading = false);
                        "
                        :disabled="isDownloading"
                        class="flex items-center gap-2 bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 disabled:opacity-75 disabled:cursor-wait text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                        <span x-show="!isDownloading" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Export PDF
                        </span>
                        
                        <span x-show="isDownloading" style="display: none;" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden">
                <div class="absolute right-0 top-0 w-24 h-24 bg-teal-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1 z-10">Total Garansi Ditemukan</h3>
                <p class="text-3xl font-black text-neutral-900 z-10">{{ number_format($totalActivations) }} <span
                        class="text-sm font-medium text-neutral-400">unit</span></p>
            </div>

            <div
                class="bg-white p-5 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center">
                <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-3 z-10">Top Promotor (Sales)</h3>
                <div class="space-y-2 z-10">
                    @forelse($topPromotors as $name => $count)
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-bold text-gray-700 truncate w-32"
                                title="{{ $name }}">{{ $name }}</span>
                            <span class="font-black text-blue-600 bg-blue-50 px-2 rounded">{{ $count }}</span>
                        </div>
                        @if ($loop->iteration == 3)
                            @break
                        @endif
                    @empty
                        <div class="text-xs text-gray-400">Belum ada data promotor</div>
                    @endforelse
                </div>
            </div>

            <div
                class="bg-white p-5 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center">
                <div class="absolute right-0 top-0 w-24 h-24 bg-purple-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-3 z-10">Top Inspektur (Aktivator)</h3>
                <div class="space-y-2 z-10">
                    @forelse($topInspectors as $name => $count)
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-bold text-gray-700 truncate w-32"
                                title="{{ $name }}">{{ $name }}</span>
                            <span
                                class="font-black text-purple-600 bg-purple-50 px-2 rounded">{{ $count }}</span>
                        </div>
                        @if ($loop->iteration == 3)
                            @break
                        @endif
                    @empty
                        <div class="text-xs text-gray-400">Belum ada data inspektur</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
            <div class="p-4 border-b border-neutral-100 bg-gray-50/50 flex justify-between items-center">
                <h2 class="text-sm font-bold text-gray-700">Detail Aktivasi Garansi</h2>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari SN / Pelanggan / Promotor..."
                        class="w-64 sm:w-80 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50 text-[11px] uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-4 font-bold text-left">Tgl Transaksi</th>
                            <th class="px-6 py-4 font-bold text-left">Tgl Aktivasi</th>
                            <th class="px-6 py-4 font-bold text-left">No. Order / SN</th>
                            <th class="px-6 py-4 font-bold text-left">Cabang</th>
                            <th class="px-6 py-4 font-bold text-left">Kategori / Nama Barang</th>
                            <th class="px-6 py-4 font-bold text-left">Pelanggan</th>
                            <th class="px-6 py-4 font-bold text-left">Tipe Garansi</th>
                            <th class="px-6 py-4 font-bold text-left">Aktivator / Promotor</th>
                            <th class="px-6 py-4 font-bold text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @forelse ($warranties as $w)
                            @php
                                $warranty = $w->warranty;
                                $isActivated = $warranty && $warranty->activated_at;
                                $orderDate = $w->orderItem->order->order_date ?? $w->orderItem->order->created_at;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-800">
                                        {{ $orderDate ? $orderDate->format('d M Y') : '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-800">
                                        {{ $isActivated ? $warranty->activated_at->format('d M Y') : '-' }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $isActivated ? $warranty->activated_at->format('H:i') : 'Belum Aktivasi' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-mono font-medium text-gray-500 block mb-1">
                                        {{ $w->orderItem->order->order_number ?? '-' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-[#1c69d4]/10 text-[#1c69d4]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        {{ $w->serial_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-800">
                                        {{ $w->orderItem->order->branch->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-500 mb-1">
                                        {{ $w->orderItem->variant->categoryName ?? '-' }}</div>
                                    <div class="text-sm font-bold text-gray-800 truncate w-48" title="{{ $w->orderItem->variant->name ?? '-' }}">
                                        {{ $w->orderItem->variant->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-800">{{ $w->orderItem->order->user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $w->orderItem->order->user->phone ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $warranty->policy->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 mb-1" title="Inspektur (Aktivator)">
                                        <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                            </path>
                                        </svg>
                                        <span
                                            class="text-sm font-bold text-gray-800">{{ $warranty->deviceInspection->inspector->name ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5" title="Promotor (Sales)">
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                        <span
                                            class="text-xs font-medium text-gray-500">{{ $w->orderItem->order->salesBy->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($warranty && $warranty->status === 'ACTIVE')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-green-100 text-green-800">
                                            AKTIF
                                        </span>
                                    @elseif (!$warranty)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-800">
                                            BELUM AKTIVASI
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-yellow-100 text-yellow-800">
                                            {{ $warranty->status }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                    <div
                                        class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-gray-900">Belum ada data</h3>
                                    <p class="mt-1 text-sm text-gray-500">Tidak ada aktivasi garansi pada periode ini.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100 bg-white">
                {{ $warranties->links() }}
            </div>
        </div>
    </div>
</div>
