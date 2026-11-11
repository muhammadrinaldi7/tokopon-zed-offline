<div class="p-6 space-y-6 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    Accurate Online ERP
                </span>
                <span class="text-xs text-gray-500 font-medium">Purchase Invoices & Buyback</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight mt-1">Faktur Pembelian (Purchase Invoice)</h1>
            <p class="text-xs text-gray-500 mt-0.5">Daftar faktur pembelian Accurate dari pengadaan barang, pembelian HP bekas (SellPhone / Buyback), dan trade-in.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.accurate.sales-invoices') }}" wire:navigate
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Faktur Penjualan
            </a>
            <a href="{{ route('admin.accurate.document-control') }}" wire:navigate
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 shadow-sm transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Pusat Kendali
            </a>
        </div>
    </div>

    <!-- 4 Metrik KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Faktur Pembelian -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Faktur Pembelian</p>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalInvoices) }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Faktur beli tersinkron</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 2. Total Nominal Pembelian -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Pengeluaran Pembelian</p>
                    <p class="text-2xl font-black text-rose-600 mt-1">Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Total biaya beli barang</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 3. Rata-rata Nilai Unit -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Rata-rata Harga Unit</p>
                    <p class="text-2xl font-black text-indigo-600 mt-1">Rp {{ number_format($averageValue, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Per perangkat HP / barang</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 4. Menunggu Faktur -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Menunggu Faktur</p>
                    <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($totalPending) }}</p>
                    <p class="text-[11px] text-amber-500 font-medium mt-0.5">Belum ada no faktur Accurate</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Kontrol Pencarian -->
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4">
        <!-- Preset Periode -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold text-gray-500 mr-1">Periode:</span>
                @foreach([
                    'all' => 'Semua',
                    'today' => 'Hari Ini',
                    'yesterday' => 'Kemarin',
                    'this_week' => 'Minggu Ini',
                    'this_month' => 'Bulan Ini',
                ] as $key => $label)
                    <button wire:click="setDatePreset('{{ $key }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $datePreset === $key ? 'bg-amber-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if($startDate || $endDate)
                <span class="text-xs font-medium text-gray-500">
                    {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : 'Awal' }} — {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d M Y') : 'Sekarang' }}
                </span>
            @endif
        </div>

        <!-- Filter Form Baris 2 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Pencarian -->
            <div class="lg:col-span-2 relative">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari No Faktur, IMEI, Merk HP, atau Pelanggan..."
                    class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Filter Status Faktur -->
            <div>
                <select wire:model.live="invoiceFilter"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition">
                    <option value="all">Semua Status Faktur</option>
                    <option value="has_invoice">✓ Punya Faktur Accurate</option>
                    <option value="no_invoice">⏳ Belum Ada Faktur</option>
                </select>
            </div>

            <!-- Filter Cabang -->
            <div>
                <select wire:model.live="branchFilter"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition">
                    <option value="">Semua Cabang Toko</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Faktur Pembelian -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Tanggal Pembelian</th>
                        <th class="py-3 px-4">No. Faktur Beli Accurate</th>
                        <th class="py-3 px-4">Perangkat & IMEI</th>
                        <th class="py-3 px-4">Penjual / Vendor</th>
                        <th class="py-3 px-4">Cabang</th>
                        <th class="py-3 px-4 text-right">Nilai Pembelian</th>
                        <th class="py-3 px-4">Kas/Bank Accurate</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                    @forelse($purchases as $p)
                        <tr class="hover:bg-gray-50/70 transition">
                            <!-- Tanggal -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-900">
                                    {{ $p->created_at ? $p->created_at->format('d/m/Y') : '-' }}
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Buyback HP
                                </span>
                            </td>

                            <!-- No. Faktur Beli Accurate -->
                            <td class="py-3 px-4">
                                @if($p->invoice_number)
                                    <div class="font-mono font-bold text-amber-800 text-sm">
                                        {{ $p->invoice_number }}
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 mt-1">
                                        ✓ Purchase Invoice
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        ⏳ Menunggu Faktur
                                    </span>
                                @endif
                            </td>

                            <!-- Perangkat & IMEI -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900">{{ $p->phone_brand }} {{ $p->phone_model }}</div>
                                <div class="text-[11px] text-gray-500">{{ $p->phone_ram ? $p->phone_ram . '/' . $p->phone_storage : '' }}</div>
                                @if($p->imei)
                                    <div class="text-[10px] font-mono text-gray-400 mt-0.5">IMEI: {{ $p->imei }}</div>
                                @endif
                            </td>

                            <!-- Penjual / Vendor -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-800">{{ $p->user->name ?? ($p->bank_account_name ?: 'Customer Umum') }}</div>
                                <div class="text-[10px] text-gray-400">{{ $p->bank_name ? $p->bank_name . ' - ' . $p->bank_account_number : '-' }}</div>
                            </td>

                            <!-- Cabang -->
                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-800">{{ $p->branch->name ?? '-' }}</div>
                            </td>

                            <!-- Nilai Pembelian -->
                            <td class="py-3 px-4 text-right">
                                <div class="font-black text-rose-600 text-sm">
                                    Rp {{ number_format($p->appraised_value, 0, ',', '.') }}
                                </div>
                            </td>

                            <!-- Kas/Bank Accurate -->
                            <td class="py-3 px-4">
                                <div class="font-mono text-xs text-gray-800">{{ $p->store_bank_no ?: 'KAS-CASH' }}</div>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-center">
                                <button wire:click="openDetail({{ $p->id }})"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-xs font-medium">Tidak ada dokumen faktur pembelian yang sesuai filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Detail Faktur Pembelian -->
    @if($showDetailModal && $selectedSellPhone)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-gray-100 overflow-hidden" @click.away="closeDetail">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Detail Faktur Pembelian (Buyback HP)</h3>
                        <p class="text-xs text-gray-500 font-mono">SPL #{{ $selectedSellPhone->id }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs text-gray-700">
                    <!-- Accurate Card -->
                    <div class="p-3.5 bg-amber-50/70 rounded-xl border border-amber-200 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-amber-900">Dokumen Accurate Purchase</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white">
                                {{ $selectedSellPhone->businessUnit->name ?? 'Default' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-500 block">No. Faktur Pembelian (PI):</span>
                                <span class="font-mono font-bold text-amber-800 text-sm">
                                    {{ $selectedSellPhone->invoice_number ?: 'Belum Terbit' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Akun Kas/Bank Accurate:</span>
                                <span class="font-mono font-bold text-gray-800">
                                    {{ $selectedSellPhone->store_bank_no ?: 'KAS-CASH' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Perangkat -->
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 space-y-2">
                        <h4 class="font-bold text-gray-900">Informasi Perangkat:</h4>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div><span class="text-gray-400">Merk & Tipe:</span> <span class="font-semibold text-gray-900">{{ $selectedSellPhone->phone_brand }} {{ $selectedSellPhone->phone_model }}</span></div>
                            <div><span class="text-gray-400">Kapasitas:</span> <span class="font-semibold text-gray-900">{{ $selectedSellPhone->phone_ram }}/{{ $selectedSellPhone->phone_storage }}</span></div>
                            <div><span class="text-gray-400">IMEI:</span> <span class="font-mono font-bold text-gray-900">{{ $selectedSellPhone->imei ?: '-' }}</span></div>
                            <div><span class="text-gray-400">Harga Beli:</span> <span class="font-black text-rose-600">Rp {{ number_format($selectedSellPhone->appraised_value, 0, ',', '.') }}</span></div>
                        </div>
                        @if($selectedSellPhone->minus_desc)
                            <div class="pt-1 text-[11px] text-gray-500 border-t border-gray-200">
                                <span class="font-semibold text-gray-700">Catatan Minus:</span> {{ $selectedSellPhone->minus_desc }}
                            </div>
                        @endif
                    </div>

                    <!-- Rekening Tujuan Transfer -->
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 space-y-1">
                        <h4 class="font-bold text-gray-900">Rekening Tujuan Penjual:</h4>
                        <div class="text-xs text-gray-800">
                            {{ $selectedSellPhone->bank_name }} - {{ $selectedSellPhone->bank_account_number }} a/n {{ $selectedSellPhone->bank_account_name }}
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                    <button wire:click="closeDetail" class="px-4 py-2 rounded-lg text-xs font-bold bg-gray-200 hover:bg-gray-300 text-gray-700 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

