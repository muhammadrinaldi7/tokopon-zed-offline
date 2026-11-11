<div class="p-6 bg-slate-50 min-h-screen">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-bold bg-blue-100 text-blue-700 rounded-lg">Kontrol Dokumen & Integrasi</span>
                <span class="text-xs text-gray-400">|</span>
                <span class="text-xs text-gray-500 font-medium">Accurate Online ERP</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Pusat Kendali Dokumen Accurate</h1>
            <p class="text-sm text-gray-500 mt-0.5">Inspeksi integritas, selaraskan tanggal transaksi, dan perbaiki tautan faktur/receipt tanpa mengubah database langsung.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.accurate.sales-sync-logs') }}" wire:navigate
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 text-xs font-bold rounded-xl shadow-sm transition-all">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Antrean & Auto-Retry</span>
            </a>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200/80 mb-6 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
            <!-- Periode Preset -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-medium">
                <span class="text-gray-400 font-semibold mr-1">Periode:</span>
                @foreach (['this_month' => 'Bulan Ini', 'today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'this_week' => 'Minggu Ini', 'last_month' => 'Bulan Lalu', 'all' => 'Semua'] as $key => $label)
                    <button type="button" wire:click="setDatePreset('{{ $key }}')"
                        class="px-3 py-1.5 rounded-lg transition-all {{ $datePreset === $key ? 'bg-blue-600 text-white font-bold shadow-sm' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if($search || $statusFilter || $branchFilter || ($businessUnitFilter && $businessUnitFilter !== 'all') || $datePreset !== 'this_month')
                <button type="button" wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 text-xs text-rose-600 hover:text-rose-700 font-semibold transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Reset Filter</span>
                </button>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Pencarian -->
            <div class="relative col-span-1 sm:col-span-2">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari No Order, No Faktur, No Receipt, atau Pelanggan..."
                    class="w-full pl-9 pr-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-blue-600 focus:border-blue-600">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Filter Status -->
            <div>
                <select wire:model.live="statusFilter"
                    class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:bg-white focus:ring-1 focus:ring-blue-600">
                    <option value="">Semua Status Sinkron</option>
                    <option value="SYNCED">Lengkap (Faktur & Receipt Ada)</option>
                    <option value="PARTIAL">Parsial (Faktur Ada, Belum Lunas)</option>
                    <option value="PENDING">Pending (Belum Terkirim)</option>
                    <option value="FAILED">Gagal Sinkron</option>
                </select>
            </div>

            <!-- Filter Cabang -->
            <div>
                <select wire:model.live="branchFilter"
                    class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:bg-white focus:ring-1 focus:ring-blue-600">
                    <option value="">Semua Cabang Toko</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Unit Bisnis -->
            <div>
                <select wire:model.live="businessUnitFilter"
                    class="w-full py-2 px-3 text-xs bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:bg-white focus:ring-1 focus:ring-blue-600">
                    <option value="all">Semua Unit Bisnis</option>
                    @foreach ($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($datePreset === 'custom')
            <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                <span class="text-xs text-gray-500 font-medium">Rentang Kustom:</span>
                <input type="date" wire:model.live="startDate" class="py-1 px-2.5 text-xs bg-gray-50 border border-gray-200 rounded-lg">
                <span class="text-gray-400 text-xs">s/d</span>
                <input type="date" wire:model.live="endDate" class="py-1 px-2.5 text-xs bg-gray-50 border border-gray-200 rounded-lg">
            </div>
        @endif
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h2 class="text-sm font-bold text-gray-800">Daftar Transaksi & Dokumen Terhubung</h2>
            <span class="text-xs text-gray-500 font-medium">Total: {{ number_format($orders->total()) }} transaksi</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Waktu & No Order</th>
                        <th class="px-5 py-3.5">Pelanggan & Cabang</th>
                        <th class="px-5 py-3.5">Faktur Penjualan (SI)</th>
                        <th class="px-5 py-3.5">Pelunasan Kas (SR)</th>
                        <th class="px-5 py-3.5 text-right">Total Transaksi</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-center">Aksi Kendali</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse ($orders as $order)
                        @php
                            $orderDateFormatted = $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : \Carbon\Carbon::parse($order->created_at)->format('d M Y');
                            $hasInvoice = !empty($order->accurate_invoice_no);
                            $hasReceipt = !empty($order->accurate_receipt_no);
                        @endphp
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <!-- Waktu & No Order -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-bold text-blue-600 font-mono text-xs">{{ $order->order_number }}</div>
                                <div class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ $orderDateFormatted }}</span>
                                </div>
                            </td>

                            <!-- Pelanggan & Cabang -->
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-gray-900 truncate max-w-[180px]">{{ $order->user->name ?? 'Pelanggan Umum' }}</div>
                                <div class="text-[11px] text-gray-500 mt-0.5 truncate max-w-[180px]">
                                    {{ $order->branch->name ?? ($order->shipping_address_snapshot['store'] ?? 'Toko') }}
                                    @if($order->businessUnit)
                                        <span class="text-[10px] font-bold text-gray-400">({{ $order->businessUnit->name }})</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Faktur Accurate -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($hasInvoice)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        {{ $order->accurate_invoice_no }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">Belum ada faktur</span>
                                @endif
                            </td>

                            <!-- Receipt Accurate -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($hasReceipt)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ $order->accurate_receipt_no }}
                                    </span>
                                @else
                                    <span class="text-amber-500 font-medium text-[11px] bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Belum lunas</span>
                                @endif
                            </td>

                            <!-- Total -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-gray-900">
                                Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                @if($hasInvoice && $hasReceipt)
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">SYNCED</span>
                                @elseif($hasInvoice)
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">PARTIAL</span>
                                @elseif($order->accurate_sync_status === 'FAILED')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-800">FAILED</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600">PENDING</span>
                                @endif
                            </td>

                            <!-- Aksi Kendali -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <div class="inline-flex items-center gap-1" x-data="{ openOptions: false }">
                                    <!-- 1. Tombol Inspeksi Diff Live -->
                                    <button type="button" wire:click="inspectDocument({{ $order->id }})"
                                        title="Inspeksi Integritas & Diff Live Accurate"
                                        class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- 2. Tombol Ubah Tanggal Transaksi -->
                                    <button type="button" wire:click="openEditDateModal({{ $order->id }})"
                                        title="Ubah Tanggal Transaksi (Otomatis Sync ke Accurate)"
                                        class="p-1.5 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </button>

                                    <!-- 3. Tombol Re-Link Dokumen -->
                                    <button type="button" wire:click="openRelinkModal({{ $order->id }})"
                                        title="Tautkan Ulang / Re-Link Nomor Faktur/Receipt"
                                        class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </button>

                                    <!-- 4. Dropdown Opsi Lainnya -->
                                    <div class="relative" @click.outside="openOptions = false">
                                        <button type="button" @click="openOptions = !openOptions"
                                            class="p-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                            </svg>
                                        </button>

                                        <div x-show="openOptions" x-cloak
                                            class="absolute right-0 top-full mt-1 w-52 bg-white rounded-xl shadow-xl border border-gray-200 py-1.5 z-30 text-left text-xs divide-y divide-gray-50">
                                            <div class="py-1">
                                                <button type="button" wire:click="openAuditModal({{ $order->id }})" @click="openOptions = false"
                                                    class="w-full px-3 py-1.5 text-left text-gray-700 hover:bg-blue-50 flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>Riwayat Perubahan Audit</span>
                                                </button>
                                                <button type="button" wire:click="openUnlinkModal({{ $order->id }}, 'SALES_INVOICE')" @click="openOptions = false"
                                                    class="w-full px-3 py-1.5 text-left text-gray-700 hover:bg-amber-50 flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                    <span>Putus Tautan Faktur</span>
                                                </button>
                                            </div>
                                            <div class="py-1">
                                                <button type="button" wire:click="openRollbackModal({{ $order->id }})" @click="openOptions = false"
                                                    class="w-full px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-semibold">
                                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    <span>Rollback & Sinkron Ulang</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm font-semibold text-gray-600">Tidak ada data transaksi yang sesuai filter.</p>
                                <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau ganti filter periode tanggal.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: INSPEKSI & DIFF DOKUMEN LIVE -->
    @if ($showDiffModal && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Inspeksi Dokumen Accurate: {{ $selectedOrder->order_number }}</h3>
                        <p class="text-xs text-gray-500">Perbandingan langsung antara database sistem lokal vs data Accurate Online API.</p>
                    </div>
                    <button type="button" wire:click="closeDiffModal" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <!-- Status Integritas Alert -->
                    @if (!empty($diffData['has_discrepancy']))
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <p class="font-bold">Terdeteksi Perbedaan Data Antara Sistem & Accurate!</p>
                                <p class="mt-0.5">Ada ketidaksamaan tanggal atau nominal faktur. Anda dapat menyelaraskannya dengan tombol kendali di bawah.</p>
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <div>
                                <p class="font-bold">Data Sinkron & Cocok Sempurna!</p>
                                <p class="mt-0.5">Seluruh atribut tanggal, nominal, dan nomor dokumen pada Accurate cocok dengan database sistem.</p>
                            </div>
                        </div>
                    @endif

                    <!-- Perbandingan Side-by-Side -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden text-xs">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-4 py-2.5">Atribut</th>
                                    <th class="px-4 py-2.5">Nilai Sistem Lokal</th>
                                    <th class="px-4 py-2.5">Nilai di Accurate Online</th>
                                    <th class="px-4 py-2.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="px-4 py-2.5 font-semibold text-gray-700">Tanggal Transaksi</td>
                                    <td class="px-4 py-2.5 font-mono">{{ $diffData['local']['order_date'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono">
                                        {{ $diffData['accurate_invoice']['transDate'] ?? ($diffData['accurate_invoice'] ? 'Tidak ada' : 'Faktur Tidak Ditemukan') }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if(isset($diffData['discrepancies']['date']))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">BEDA</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">COCOK</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2.5 font-semibold text-gray-700">Nomor Faktur (SI)</td>
                                    <td class="px-4 py-2.5 font-mono">{{ $diffData['local']['accurate_invoice_no'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono">{{ $diffData['accurate_invoice']['number'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if(isset($diffData['discrepancies']['invoice_number']))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">BEDA</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">COCOK</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2.5 font-semibold text-gray-700">Total Nominal</td>
                                    <td class="px-4 py-2.5 font-bold">Rp {{ number_format($diffData['local']['grand_total'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2.5 font-bold">
                                        Rp {{ number_format((float)($diffData['accurate_invoice']['totalAmount'] ?? 0), 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if(isset($diffData['discrepancies']['amount']))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">BEDA</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">COCOK</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2.5 font-semibold text-gray-700">Pelunasan Kas (Receipt)</td>
                                    <td class="px-4 py-2.5 font-mono">{{ $diffData['local']['accurate_receipt_no'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono">{{ $diffData['accurate_receipt']['number'] ?? ($diffData['accurate_receipt'] ? 'Ada' : 'Belum Ada') }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ !empty($diffData['accurate_receipt']) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ !empty($diffData['accurate_receipt']) ? 'TERCATAT' : '-' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100 bg-gray-50/50 flex justify-end gap-2">
                    <button type="button" wire:click="closeDiffModal"
                        class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 2: UBAH TANGGAL TRANSAKSI -->
    @if ($showEditDateModal && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-amber-50/60 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Ubah Tanggal Transaksi: {{ $selectedOrder->order_number }}</h3>
                        <p class="text-xs text-amber-800">Tanggal akan disinkronkan ke Accurate dan database sistem secara atomik.</p>
                    </div>
                    <button type="button" wire:click="$set('showEditDateModal', false)" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="executeDateChange" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Tanggal Transaksi Baru *</label>
                        <input type="date" wire:model="newTransactionDate"
                            class="w-full py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:bg-white focus:ring-1 focus:ring-blue-600">
                        @error('newTransactionDate') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Alasan Perubahan Tanggal (Wajib untuk Audit) *</label>
                        <textarea wire:model="changeReason" rows="3" placeholder="Contoh: Koreksi salah input tanggal kasir shift malam..."
                            class="w-full py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:ring-1 focus:ring-blue-600"></textarea>
                        @error('changeReason') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="p-3 bg-blue-50 text-blue-800 rounded-xl text-[11px] space-y-1">
                        <p class="font-bold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Alur Otomatis di Accurate:
                        </p>
                        <p>Jika faktur telah memiliki pelunasan (*Sales Receipt*), sistem akan menghapus pelunasan lama, menggeser tanggal faktur di Accurate, lalu menerbitkan ulang pelunasan di tanggal yang baru.</p>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showEditDateModal', false)"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-4 py-2 text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                            <span wire:loading.remove wire:target="executeDateChange">Simpan & Sinkronkan</span>
                            <span wire:loading wire:target="executeDateChange">Memproses ke Accurate...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 3: RE-LINK DOKUMEN -->
    @if ($showRelinkModal && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-emerald-50/60 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Tautkan Dokumen: {{ $selectedOrder->order_number }}</h3>
                        <p class="text-xs text-emerald-800">Menghubungkan nomor faktur atau receipt yang valid dari Accurate ke order ini.</p>
                    </div>
                    <button type="button" wire:click="$set('showRelinkModal', false)" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="executeRelink" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Jenis Dokumen *</label>
                        <select wire:model="relinkDocType"
                            class="w-full py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:bg-white focus:ring-1 focus:ring-blue-600">
                            <option value="SALES_INVOICE">Faktur Penjualan (Sales Invoice)</option>
                            <option value="SALES_RECEIPT">Pelunasan Penjualan (Sales Receipt)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Nomor Dokumen di Accurate *</label>
                        <input type="text" wire:model="newDocNumber" placeholder="Contoh: FP.2026.11.0001"
                            class="w-full py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-800 focus:bg-white focus:ring-1 focus:ring-blue-600">
                        <p class="text-[10px] text-gray-400 mt-1">Sistem akan memverifikasi keberadaan nomor dokumen ini ke Accurate API.</p>
                        @error('newDocNumber') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Alasan Re-Link (Wajib Audit) *</label>
                        <textarea wire:model="changeReason" rows="2" placeholder="Contoh: Faktur dibuat ulang oleh tim Finance di Accurate..."
                            class="w-full py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:ring-1 focus:ring-blue-600"></textarea>
                        @error('changeReason') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showRelinkModal', false)"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                            <span wire:loading.remove wire:target="executeRelink">Verifikasi & Tautkan</span>
                            <span wire:loading wire:target="executeRelink">Memverifikasi ke Accurate...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 4: ROLLBACK & RE-SYNC -->
    @if ($showRollbackModal && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-rose-50/70 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-rose-900">Rollback & Sinkron Ulang: {{ $selectedOrder->order_number }}</h3>
                        <p class="text-xs text-rose-700">Tindakan ini akan menghapus dokumen di Accurate dan memicu ulang pembuatan faktur.</p>
                    </div>
                    <button type="button" wire:click="$set('showRollbackModal', false)" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="executeRollbackAndResync" class="p-6 space-y-4 text-xs">
                    <div class="p-3 bg-rose-50 text-rose-800 rounded-xl text-[11px] space-y-1">
                        <p class="font-bold flex items-center gap-1 text-rose-700">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            Peringatan Tindakan Kritis:
                        </p>
                        <p>Dokumen faktur ({{ $selectedOrder->accurate_invoice_no ?? '-' }}) dan pelunasan di Accurate akan dihapus, lalu sistem akan menerbitkan faktur dan pelunasan baru secara bersih.</p>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Alasan Rollback & Sinkron Ulang *</label>
                        <textarea wire:model="rollbackReason" rows="3" placeholder="Contoh: Dokumen di Accurate menggantung atau item tidak sesuai..."
                            class="w-full py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:ring-1 focus:ring-rose-600"></textarea>
                        @error('rollbackReason') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showRollbackModal', false)"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                            <span wire:loading.remove wire:target="executeRollbackAndResync">Eksekusi Rollback & Sinkron Ulang</span>
                            <span wire:loading wire:target="executeRollbackAndResync">Sedang Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 5: RIWAYAT AUDIT LOG PERUBAHAN -->
    @if ($showAuditModal && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Riwayat Audit Perubahan: {{ $selectedOrder->order_number }}</h3>
                        <p class="text-xs text-gray-500">Jejak audit seluruh intervensi dokumen dan perubahan tanggal transaksi.</p>
                    </div>
                    <button type="button" wire:click="$set('showAuditModal', false)" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    @if ($auditLogs->isEmpty())
                        <div class="text-center py-8 text-gray-400 text-xs">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="font-semibold text-gray-600">Belum ada riwayat audit untuk order ini.</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Order ini belum pernah mengalami intervensi atau koreksi manual.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($auditLogs as $log)
                                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 text-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">
                                            {{ $log->action_type }} ({{ $log->document_type }})
                                        </span>
                                        <span class="text-[11px] text-gray-400 font-medium">
                                            {{ $log->created_at->format('d M Y, H:i') }}
                                        </span>
                                    </div>
                                    <p class="text-gray-800 font-medium"><strong>Alasan:</strong> {{ $log->reason }}</p>
                                    <div class="text-[11px] text-gray-500 flex items-center justify-between pt-1 border-t border-gray-200/60">
                                        <span>Dieksekusi oleh: <strong>{{ $log->performedBy->name ?? 'System/Admin' }}</strong></span>
                                        <span class="font-bold {{ $log->status === 'SUCCESS' ? 'text-emerald-600' : 'text-rose-600' }}">
                                            Status: {{ $log->status }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-4 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                    <button type="button" wire:click="$set('showAuditModal', false)"
                        class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>

