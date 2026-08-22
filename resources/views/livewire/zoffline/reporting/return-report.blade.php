<div class="p-6">
    <div class="flex flex-col items-start gap-4 mb-6">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Laporan Retur</h1>
                <p class="text-sm text-gray-500 mt-1">Gunakan laporan ini untuk memantau data klaim garansi dan barang retur.</p>
            </div>
            <div>
                <button wire:click="exportExcel"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="exportExcel">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                    <span wire:loading wire:target="exportExcel">Mengekspor...</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 w-full">
            <div class="bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="status"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent rounded-lg cursor-pointer w-full">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_repair">Diproses</option>
                    <option value="waiting_refund">Menunggu Refund</option>
                    <option value="approved">Disetujui</option>
                    <option value="completed">Selesai</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>

            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm col-span-1 md:col-span-2">
                <input type="date" wire:model.live="startDate"
                    class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full">
                <span class="text-gray-400 text-sm font-bold">-</span>
                <input type="date" wire:model.live="endDate"
                    class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full">
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input wire:model.live.debounce.500ms="search" type="text"
                class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm transition-shadow"
                placeholder="Cari No Klaim, Serial Number, atau Nama Pelanggan...">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Klaim & No Resi</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan & Sales</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Produk, Qty & Harga</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kendala</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Resolusi</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($claims as $claim)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ $claim->claim_number }}</div>
                                <div class="text-sm text-gray-500">{{ $claim->claimed_at ? $claim->claimed_at->format('d/m/Y H:i') : '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $claim->customer->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $claim->customer->profile->phone_number ?? '-' }}</div>
                                <div class="text-xs text-indigo-600 font-medium mt-1">Sales: {{ $claim->warranty->orderItem->order->salesBy->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 line-clamp-2">
                                    {{ $claim->warranty->orderItem->product_name ?? ($claim->warranty->orderItem->variant->name ?? 'Produk Tidak Diketahui') }}
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs font-mono font-bold text-gray-500">SN: {{ $claim->serial_number }}</span>
                                    <span class="text-xs text-gray-400">&bull;</span>
                                    <span class="text-xs font-medium text-gray-600">Qty: {{ $claim->warranty->orderItem->qty ?? 1 }}</span>
                                </div>
                                <div class="text-xs font-bold text-emerald-600 mt-1">Rp {{ number_format($claim->warranty->orderItem->price_at_checkout ?? 0, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 line-clamp-2" title="{{ $claim->issue_description }}">{{ $claim->issue_description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badge = $claim->status_badge;
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $badge->bg }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge->dot }}"></span>
                                    {{ $badge->label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($claim->resolution)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($claim->resolution) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button wire:click="showDetail({{ $claim->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-fuchsia-600 hover:text-fuchsia-700 bg-fuchsia-50 hover:bg-fuchsia-100 rounded-lg transition-colors">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-gray-500 font-medium">Tidak ada data laporan retur di rentang
                                        waktu ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($claims->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $claims->links() }}
            </div>
        @endif
    </div>

    <!-- Slide-over Panel Detail -->
    @if($showDetailPanel && $this->selectedClaim)
        <div class="fixed inset-0 overflow-hidden z-50">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetail"></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-md transform transition-all duration-300 ease-in-out">
                        <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                            <div class="bg-gray-50 px-4 py-6 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-bold text-gray-900" id="slide-over-title">
                                        Detail Klaim: {{ $this->selectedClaim->claim_number }}
                                    </h2>
                                    <div class="ml-3 flex h-7 items-center">
                                        <button type="button" wire:click="closeDetail" class="rounded-md bg-gray-50 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-500">
                                            <span class="sr-only">Close panel</span>
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="relative flex-1 px-4 py-6 sm:px-6">
                                <div class="space-y-6">
                                    <!-- Informasi Klaim -->
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-500 mb-3 border-b pb-2">INFORMASI KLAIM</h3>
                                        <dl class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Tgl Klaim</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->claimed_at ? $this->selectedClaim->claimed_at->format('d/m/Y H:i') : '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Tgl Selesai</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->resolved_at ? $this->selectedClaim->resolved_at->format('d/m/Y H:i') : '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <dt class="text-gray-500">Status</dt>
                                                <dd>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $this->selectedClaim->status_badge->bg }}">
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $this->selectedClaim->status_badge->dot }}"></span>
                                                        {{ $this->selectedClaim->status_badge->label }}
                                                    </span>
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Resolusi</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->resolution ? ucfirst($this->selectedClaim->resolution) : '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Dibuat Oleh</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->claimedBy->name ?? '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Disetujui Oleh</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->approvedBy->name ?? '-' }}</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <!-- Informasi Transaksi -->
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-500 mb-3 border-b pb-2">INFORMASI TRANSAKSI</h3>
                                        <dl class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">No. Pesanan</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->warranty->orderItem->order->order_number ?? '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">No. Invoice Accurate</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->warranty->orderItem->order->accurate_invoice_no ?? '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Nama Sales</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->warranty->orderItem->order->salesBy->name ?? '-' }}</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <!-- Informasi Pelanggan -->
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-500 mb-3 border-b pb-2">INFORMASI PELANGGAN</h3>
                                        <dl class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Nama</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->customer->name ?? '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">No. HP</dt>
                                                <dd class="font-medium text-gray-900">{{ $this->selectedClaim->customer->profile->phone_number ?? '-' }}</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <!-- Informasi Produk -->
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-500 mb-3 border-b pb-2">INFORMASI PRODUK</h3>
                                        <dl class="space-y-2 text-sm">
                                            <div>
                                                <dt class="text-gray-500">Produk</dt>
                                                <dd class="font-medium text-gray-900 mt-1">
                                                    {{ $this->selectedClaim->warranty->orderItem->product_name ?? ($this->selectedClaim->warranty->orderItem->variant->name ?? 'Produk Tidak Diketahui') }}
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Serial Number</dt>
                                                <dd class="font-mono font-bold text-gray-900">{{ $this->selectedClaim->serial_number }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Harga Satuan & Qty</dt>
                                                <dd class="font-medium text-gray-900">Rp {{ number_format($this->selectedClaim->warranty->orderItem->price_at_checkout ?? 0, 0, ',', '.') }} ({{ $this->selectedClaim->warranty->orderItem->qty ?? 1 }}x)</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <!-- Detail Masalah -->
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-500 mb-3 border-b pb-2">DETAIL MASALAH & RESOLUSI</h3>
                                        <dl class="space-y-4 text-sm">
                                            <div>
                                                <dt class="text-gray-500 font-bold">Kendala / Keluhan</dt>
                                                <dd class="mt-1 text-gray-900 bg-gray-50 p-3 rounded-lg">{{ $this->selectedClaim->issue_description ?: '-' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500 font-bold">Diagnosis Teknisi</dt>
                                                <dd class="mt-1 text-gray-900 bg-gray-50 p-3 rounded-lg">{{ $this->selectedClaim->diagnosis ?: '-' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500 font-bold">Catatan Resolusi</dt>
                                                <dd class="mt-1 text-gray-900 bg-gray-50 p-3 rounded-lg">{{ $this->selectedClaim->resolution_notes ?: '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500 font-bold">Nominal Refund</dt>
                                                <dd class="font-medium text-red-600">
                                                    {{ $this->selectedClaim->refund_amount ? 'Rp ' . number_format($this->selectedClaim->refund_amount, 0, ',', '.') : '-' }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                    
                                    @can('manage-orders')
                                    <div class="pt-4 border-t">
                                        <a href="{{ route('admin.warranty.claims', ['search' => $this->selectedClaim->claim_number]) }}" class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-fuchsia-600 hover:bg-fuchsia-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fuchsia-500">
                                            Buka di Manajemen Klaim
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
