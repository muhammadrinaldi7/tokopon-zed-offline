<div class="p-4 md:p-6 lg:p-8 bg-[#f7f7f7] min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Sales Order (SO) & DP</h1>
                <p class="text-sm text-gray-500 mt-1">Pantau performa pemesanan uang muka (DP), total piutang berjalan, dan status outstanding per cabang.</p>
            </div>
            @if($search || $branchFilter || ($businessUnitFilter && $businessUnitFilter !== 'all') || !empty($proyekFilter) || $dateRange !== 'this_month')
                <div class="flex items-center">
                    <button wire:click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-600 border border-gray-200 text-xs font-semibold rounded-xl shadow-sm transition-all">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Filter Bar Grid -->
        <div class="flex flex-col items-start mb-6 gap-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 w-full">
                {{-- Separator CSV --}}
                <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center justify-between">
                    <span class="text-xs text-gray-500 mr-2 font-medium">Separator:</span>
                    <select wire:model="csvSeparator"
                        class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 p-0 font-medium cursor-pointer w-full text-right truncate">
                        <option value=";">Semicolon (;)</option>
                        <option value=",">Comma (,)</option>
                    </select>
                </div>

                {{-- Filter Unit Bisnis --}}
                <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                    <select wire:model.live="businessUnitFilter"
                        class="border-none text-sm font-bold text-gray-800 focus:ring-0 bg-transparent p-0 cursor-pointer w-full truncate">
                        <option value="all">Semua Bisnis Unit</option>
                        @foreach ($businessUnits as $bu)
                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Cabang --}}
                <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                    <select wire:model.live="branchFilter"
                        class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 cursor-pointer w-full truncate">
                        <option value="">Semua Cabang</option>
                        @foreach ($availableBranches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Proyek (Multi Select Searchable Dropdown) --}}
                <div x-data="{
                    open: false,
                    search: '',
                    selected: @entangle('proyekFilter').live,
                    projects: {{ json_encode($availableProjects) }},
                    get filteredProjects() {
                        if (!this.search) return this.projects;
                        return this.projects.filter(p => p.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get displayLabel() {
                        if (!this.selected || this.selected.length === 0) return 'Semua Proyek';
                        if (this.selected.length === 1) return this.selected[0];
                        return this.selected.length + ' Proyek Terpilih';
                    },
                    clearSelection() {
                        this.selected = [];
                    }
                }" @click.outside="open = false"
                    class="relative bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                    <!-- Trigger Button -->
                    <div @click="open = !open"
                        class="w-full flex items-center justify-between cursor-pointer select-none">
                        <span class="text-sm font-medium text-gray-700 truncate" x-text="displayLabel">Semua Proyek</span>
                        <div class="flex items-center gap-1 shrink-0 ml-1">
                            <button type="button" x-show="selected && selected.length > 0"
                                @click.stop="clearSelection()" class="text-gray-400 hover:text-gray-600 p-0.5"
                                title="Reset filter proyek">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <!-- Dropdown Search Panel -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute left-0 top-full mt-1.5 w-64 max-h-72 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50 flex flex-col"
                        style="display: none;">

                        <!-- Search Input -->
                        <div class="px-2 pb-2 border-b border-gray-100">
                            <div class="relative">
                                <input x-model="search" x-ref="projectSearchInput" @keydown.escape="open = false"
                                    type="text" placeholder="Ketik cari proyek..."
                                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4]"
                                    x-init="$watch('open', value => { if (value) setTimeout(() => $refs.projectSearchInput.focus(), 50) })">
                                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Project List -->
                        <div class="overflow-y-auto flex-1 max-h-56 divide-y divide-gray-50 text-xs">
                            <template x-for="p in filteredProjects" :key="p">
                                <label
                                    class="px-3 py-2 cursor-pointer hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                    <input type="checkbox" :value="p" x-model="selected"
                                        class="rounded text-[#1c69d4] focus:ring-[#1c69d4] w-3.5 h-3.5 border-gray-300">
                                    <span x-text="p" class="truncate pr-2"
                                        :class="{
                                            'font-bold text-[#1c69d4]': selected && selected.includes(p),
                                            'text-gray-700': !(selected && selected.includes(p))
                                        }"></span>
                                </label>
                            </template>

                            <div x-show="filteredProjects.length === 0 && search"
                                class="px-3 py-4 text-center text-gray-400 italic">
                                Proyek tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filter Rentang Tanggal --}}
                <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                    <select wire:model.live="dateRange"
                        class="border-none text-sm font-bold text-blue-600 focus:ring-0 bg-transparent p-0 cursor-pointer w-full truncate">
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="this_year">Tahun Ini</option>
                        <option value="custom">Kustom</option>
                    </select>
                </div>

                @if ($dateRange === 'custom')
                    <div
                        class="md:col-span-2 lg:col-span-2 flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm">
                        <input type="date" wire:model.live="startDate"
                            class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                        <span class="text-gray-400 text-sm font-bold">-</span>
                        <input type="date" wire:model.live="endDate"
                            class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                    </div>
                @endif

                {{-- Export Buttons (CSV & Excel) --}}
                <div class="flex items-center gap-2 col-span-1 {{ $dateRange === 'custom' ? 'lg:col-span-6' : 'lg:col-span-1' }}">
                    <button wire:click="exportCsv" wire:loading.attr="disabled"
                        title="Download format CSV"
                        class="flex-1 flex items-center justify-center gap-1.5 bg-slate-700 hover:bg-slate-800 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                        <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4 shrink-0 text-slate-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <svg wire:loading wire:target="exportCsv"
                            class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span wire:loading.remove wire:target="exportCsv">CSV</span>
                        <span wire:loading wire:target="exportCsv">...</span>
                    </button>

                    <button wire:click="exportExcel" wire:loading.attr="disabled"
                        title="Download format Excel (.xlsx)"
                        class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 disabled:cursor-wait text-white text-xs font-bold py-2 px-3 rounded-xl shadow-sm transition-colors h-full min-h-10.5">
                        <svg wire:loading.remove wire:target="exportExcel"
                            class="w-4 h-4 shrink-0 text-emerald-200" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <svg wire:loading wire:target="exportExcel"
                            class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span wire:loading.remove wire:target="exportExcel">Excel</span>
                        <span wire:loading wire:target="exportExcel">...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center items-start">
                <div class="absolute right-0 top-0 w-24 h-24 bg-amber-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1 z-10">SO Outstanding (Belum Lunas)</h3>
                <p class="text-3xl font-black text-neutral-900 z-10">{{ number_format($totalOutstanding) }} <span class="text-sm font-medium text-neutral-400">order</span></p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center items-start">
                <div class="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1 z-10">Total Piutang Berjalan</h3>
                <p class="text-3xl font-black text-red-600 z-10">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 relative overflow-hidden flex flex-col justify-center items-start">
                <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 opacity-50"></div>
                <h3 class="text-sm font-bold text-neutral-500 mb-1 z-10">Closing Rate</h3>
                <div class="flex items-baseline gap-2 z-10">
                    <p class="text-3xl font-black text-emerald-600">{{ $closingRate }}%</p>
                    <p class="text-xs text-neutral-400">dari {{ $totalSO }} SO</p>
                </div>
            </div>
        </div>

        <!-- Aging Report -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-neutral-100 mb-8">
            <h3 class="text-base font-bold text-neutral-900 mb-4">SO Aging (Umur Outstanding)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($agingData as $label => $count)
                    <div class="p-4 rounded-xl border {{ $loop->last ? 'bg-red-50 border-red-100' : 'bg-neutral-50 border-neutral-100' }}">
                        <p class="text-xs font-bold {{ $loop->last ? 'text-red-700' : 'text-neutral-500' }}">{{ $label }}</p>
                        <p class="text-2xl font-black {{ $loop->last ? 'text-red-700' : 'text-neutral-900' }}">{{ $count }}</p>
                    </div>
                @endforeach
            </div>
            @if($agingData['> 30 Hari'] > 0)
                <div class="mt-4 p-3 bg-red-50 border border-red-100 text-red-700 text-sm rounded-lg flex items-start gap-2">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p>Terdapat <strong>{{ $agingData['> 30 Hari'] }} SO</strong> yang menggantung lebih dari 30 hari. Segera hubungi pelanggan untuk konfirmasi pelunasan atau pembatalan.</p>
                </div>
            @endif
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
            <div class="p-4 border-b border-neutral-100 bg-neutral-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h2 class="text-lg font-bold text-neutral-800">Daftar SO Outstanding</h2>
                <div class="relative w-full sm:w-auto">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari No SO / Pelanggan / Sales / Item..."
                        class="w-full sm:w-80 pl-10 pr-4 py-2 border border-neutral-200 rounded-xl text-sm focus:border-blue-600 focus:ring-blue-600 bg-white">
                    <svg class="w-5 h-5 text-neutral-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">No SO</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Cabang</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Pelanggan</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Item (Barang)</th>
                            <th scope="col" class="px-5 py-3 text-center text-xs font-bold text-neutral-500 uppercase tracking-wider">Qty</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-bold text-neutral-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-bold text-emerald-600 uppercase tracking-wider">DP Masuk</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-bold text-red-600 uppercase tracking-wider">Sisa Tagihan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @forelse ($outstandingOrders as $order)
                            @php
                                $dpPaid = $order->payments()->where('status', 'PAID')->sum('amount');
                                $sisaTagihan = max(0, $order->grand_total - $dpPaid);
                                $isOld = $order->created_at->diffInDays(now()) > 30;
                            @endphp
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900">{{ $order->created_at->format('d M Y, H:i') }}</div>
                                    <div class="text-[10px] font-bold mt-1 {{ $isOld ? 'text-red-500 bg-red-50 inline-block px-1.5 py-0.5 rounded' : 'text-neutral-400' }}">
                                        {{ $order->created_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono font-bold text-blue-600">{{ $order->order_number }}</span>
                                    @if($order->accurate_invoice_no)
                                        <div class="text-[11px] text-gray-500 font-medium">Inv: {{ $order->accurate_invoice_no }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-neutral-700 bg-neutral-100 px-2.5 py-1 rounded-lg border border-neutral-200">
                                        {{ $order->branch->name ?? ($order->shipping_address_snapshot['store'] ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-neutral-900">{{ $order->user->name ?? '-' }}</div>
                                    <div class="text-xs text-neutral-500">{{ $order->user->profile->phone_number ?? $order->user->phone ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        @forelse($order->items as $item)
                                            <div class="text-xs font-semibold text-neutral-800">
                                                {{ $item->variant->name ?? $item->product_name ?? 'Item' }}
                                                @if(!empty($item->variant->proyek))
                                                    <span class="ml-1 text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">{{ $item->variant->proyek }}</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-xs text-neutral-400 italic">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-col gap-1.5 items-center">
                                        @forelse($order->items as $item)
                                            <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 text-xs font-bold bg-neutral-100 text-neutral-700 rounded-md border border-neutral-200">
                                                {{ $item->qty }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-neutral-400">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-right font-bold text-neutral-900">
                                    {{ number_format($order->grand_total, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-right font-bold text-emerald-600">
                                    {{ number_format($dpPaid, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-right font-black text-red-600">
                                    {{ number_format($sisaTagihan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-neutral-500">
                                    <div class="mx-auto w-16 h-16 bg-neutral-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-8 w-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-neutral-900">Tidak ada data Sales Order</h3>
                                    <p class="mt-1 text-sm text-neutral-500">Tidak ada Sales Order yang sesuai dengan kriteria filter saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($outstandingOrders->hasPages())
                <div class="p-4 border-t border-neutral-100 bg-neutral-50/50">
                    {{ $outstandingOrders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
