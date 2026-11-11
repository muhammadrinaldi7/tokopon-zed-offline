<div class="p-6 space-y-6 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                    Accurate Online ERP
                </span>
                <span class="text-xs text-gray-500 font-medium">Sales Invoices & Receipts</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight mt-1">Faktur Penjualan (Sales Invoice)</h1>
            <p class="text-xs text-gray-500 mt-0.5">Daftar seluruh dokumen faktur penjualan dan bukti penerimaan kas/bank yang terintegrasi dengan Accurate.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.accurate.document-control') }}" wire:navigate
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Pusat Kendali Dokumen
            </a>
            <a href="{{ route('admin.accurate.sales-sync-logs') }}" wire:navigate
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 shadow-sm transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Antrean & Log
            </a>
        </div>
    </div>

    <!-- 4 Metrik KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Faktur -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Faktur Accurate</p>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalInvoices) }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Faktur penjualan tersinkron</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 2. Total Nominal Faktur -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Transaksi Faktur</p>
                    <p class="text-2xl font-black text-emerald-600 mt-1">Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Total omzet dalam faktur</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 3. Sudah Dilunasi (Sales Receipt) -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Faktur Lunas (Receipt)</p>
                    <p class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($paidCount) }}</p>
                    <p class="text-[11px] text-indigo-500 font-medium mt-0.5">Memiliki nomor penerimaan</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 4. Belum Lunas / Piutang -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Belum Lunas / Piutang</p>
                    <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($unpaidCount) }}</p>
                    <p class="text-[11px] text-amber-500 font-medium mt-0.5">Belum terbit Sales Receipt</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $datePreset === $key ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Pencarian -->
            <div class="lg:col-span-2 relative">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari No Faktur, No Order, atau Pelanggan..."
                    class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Filter Status Faktur -->
            <div>
                <select wire:model.live="invoiceFilter"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="all">Semua Status Faktur</option>
                    <option value="has_invoice">✓ Punya Faktur Accurate</option>
                    <option value="no_invoice">⏳ Belum Punya Faktur</option>
                </select>
            </div>

            <!-- Filter Status Pelunasan -->
            <div>
                <select wire:model.live="paymentFilter"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="all">Semua Status Lunas</option>
                    <option value="paid">✓ Lunas (Punya Receipt)</option>
                    <option value="unpaid">⚠️ Belum Lunas / Piutang</option>
                </select>
            </div>

            <!-- Filter Cabang -->
            <div>
                <select wire:model.live="branchFilter"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Semua Cabang Toko</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Faktur Penjualan -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Tanggal & Saluran</th>
                        <th class="py-3 px-4">No. Faktur Accurate</th>
                        <th class="py-3 px-4">Nomor Pesanan</th>
                        <th class="py-3 px-4">Pelanggan</th>
                        <th class="py-3 px-4">Cabang & Kasir</th>
                        <th class="py-3 px-4 text-right">Nilai Faktur</th>
                        <th class="py-3 px-4">Penerimaan (Receipt)</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-gray-50/70 transition">
                            <!-- Tanggal & Saluran -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-900">
                                    {{ $inv->order_date ? \Carbon\Carbon::parse($inv->order_date)->format('d/m/Y') : ($inv->created_at ? $inv->created_at->format('d/m/Y H:i') : '-') }}
                                </div>
                                <div class="inline-flex items-center gap-1 mt-0.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $inv->order_channel === 'pos' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        {{ strtoupper($inv->order_channel ?? 'POS') }}
                                    </span>
                                </div>
                            </td>

                            <!-- No Faktur Accurate -->
                            <td class="py-3 px-4">
                                @if($inv->accurate_invoice_no)
                                    <div class="font-mono font-bold text-blue-700 text-sm">
                                        {{ $inv->accurate_invoice_no }}
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 mt-1">
                                        ✓ Tersinkron Accurate
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        ⏳ Menunggu Sinkron
                                    </span>
                                @endif
                            </td>

                            <!-- Nomor Pesanan -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900">#{{ $inv->order_number }}</div>
                                @if($inv->accurate_so_number)
                                    <div class="text-[10px] text-gray-400 font-mono">SO: {{ $inv->accurate_so_number }}</div>
                                @endif
                            </td>

                            <!-- Pelanggan -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-800">{{ $inv->user->name ?? 'Pelanggan Umum' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $inv->user->email ?? '-' }}</div>
                            </td>

                            <!-- Cabang & Kasir -->
                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-800">{{ $inv->branch->name ?? '-' }}</div>
                                <div class="text-[10px] text-gray-400">{{ $inv->handledBy->name ?? 'Kasir Umum' }}</div>
                            </td>

                            <!-- Nilai Faktur -->
                            <td class="py-3 px-4 text-right">
                                <div class="font-black text-gray-900">
                                    Rp {{ number_format($inv->grand_total, 0, ',', '.') }}
                                </div>
                            </td>

                            <!-- Penerimaan (Receipt) -->
                            <td class="py-3 px-4">
                                @if($inv->accurate_receipt_no)
                                    <div class="font-mono font-semibold text-emerald-700 text-xs">
                                        {{ $inv->accurate_receipt_no }}
                                    </div>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 mt-0.5">
                                        ✓ Lunas Kas/Bank
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Belum Lunas / Piutang
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="openDetail({{ $inv->id }})"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
                                        title="Lihat Detail Faktur">
                                        Detail
                                    </button>
                                    <a href="{{ route('admin.accurate.document-control') }}?search={{ $inv->order_number }}" wire:navigate
                                        class="px-2 py-1.5 rounded-lg text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition"
                                        title="Buka di Pusat Kendali Dokumen">
                                        Kendali
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-xs font-medium">Tidak ada dokumen faktur penjualan yang sesuai filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Detail Faktur Penjualan -->
    @if($showDetailModal && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden" @click.away="closeDetail">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Detail Dokumen Faktur Penjualan</h3>
                        <p class="text-xs text-gray-500 font-mono">Pesanan #{{ $selectedOrder->order_number }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto text-xs text-gray-700">
                    <!-- Integrasi Accurate Status Card -->
                    <div class="p-4 bg-blue-50/60 rounded-xl border border-blue-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-900">Identitas Dokumen Accurate</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-600 text-white">
                                {{ $selectedOrder->businessUnit->name ?? 'Default Unit' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                            <div>
                                <span class="text-gray-500 block">No. Faktur Penjualan (SI):</span>
                                <span class="font-mono font-bold text-blue-800 text-sm">
                                    {{ $selectedOrder->accurate_invoice_no ?: 'Belum Terdaftar' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">No. Penerimaan Penjualan (SR):</span>
                                <span class="font-mono font-bold text-emerald-800 text-sm">
                                    {{ $selectedOrder->accurate_receipt_no ?: 'Belum Ada Pelunasan' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Pesanan -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-100 text-xs">
                        <div>
                            <span class="text-gray-400 block text-[10px]">TANGGAL ORDER</span>
                            <span class="font-semibold text-gray-800">{{ $selectedOrder->order_date ? \Carbon\Carbon::parse($selectedOrder->order_date)->format('d M Y') : $selectedOrder->created_at->format('d M Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px]">CABANG</span>
                            <span class="font-semibold text-gray-800">{{ $selectedOrder->branch->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px]">KASIR / STAFF</span>
                            <span class="font-semibold text-gray-800">{{ $selectedOrder->handledBy->name ?? 'Kasir Umum' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px]">TOTAL TRANSAKSI</span>
                            <span class="font-bold text-emerald-600">Rp {{ number_format($selectedOrder->grand_total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Item Produk -->
                    <div>
                        <h4 class="font-bold text-gray-900 mb-2">Item Barang yang Difakturkan:</h4>
                        <div class="border border-gray-100 rounded-xl overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-[10px] text-gray-500 uppercase font-bold border-b border-gray-100">
                                    <tr>
                                        <th class="py-2 px-3">Produk / Varian</th>
                                        <th class="py-2 px-3 text-center">Qty</th>
                                        <th class="py-2 px-3 text-right">Harga</th>
                                        <th class="py-2 px-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($selectedOrder->items as $item)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="py-2 px-3">
                                                <div class="font-semibold text-gray-900">{{ $item->product_name }}</div>
                                                @if($item->serial_number)
                                                    <div class="text-[10px] font-mono text-gray-400">SN: {{ $item->serial_number }}</div>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-center font-bold">{{ $item->quantity }}</td>
                                            <td class="py-2 px-3 text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                            <td class="py-2 px-3 text-right font-bold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div>
                        <h4 class="font-bold text-gray-900 mb-2">Metode Pembayaran (Pelunasan):</h4>
                        <div class="space-y-1.5">
                            @forelse($selectedOrder->payments as $pmt)
                                <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ $pmt->paymentMethod->name ?? 'Kas / Tunai' }}
                                        </span>
                                        <span class="text-[11px] text-gray-500 font-mono">Bank: {{ $pmt->paymentMethod->accurate_bank_no ?? 'KAS-CASH' }}</span>
                                    </div>
                                    <span class="font-bold text-gray-900">Rp {{ number_format($pmt->amount, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="p-2.5 bg-amber-50 text-amber-700 text-xs rounded-lg">
                                    Belum ada catatan pembayaran.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('admin.accurate.document-control') }}?search={{ $selectedOrder->order_number }}" wire:navigate
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition">
                        Buka di Kendali Dokumen
                    </a>
                    <button wire:click="closeDetail" class="px-4 py-2 rounded-lg text-xs font-bold bg-gray-200 hover:bg-gray-300 text-gray-700 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

