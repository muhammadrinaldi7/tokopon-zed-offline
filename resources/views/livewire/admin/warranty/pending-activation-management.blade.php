<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-[#1c69d4]">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Aktivasi Garansi Tertunda (Gantung)</h2>
                    <p class="text-gray-500 text-sm mt-0.5">Generate kartu garansi satuan per IMEI untuk perangkat yang sudah melalui proses inspeksi aktivasi QC.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.warranty.policies') }}" wire:navigate
                class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Kelola Kebijakan Garansi
            </a>
            <a href="{{ route('admin.warranty.claims') }}" wire:navigate
                class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Modul Klaim Garansi
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Inspeksi Aktivasi</p>
                <h3 class="text-2xl font-black text-gray-800 mt-1">{{ number_format($totalCount) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">Perangkat melalui proses QC</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#1c69d4] flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between {{ $pendingCount > 0 ? 'ring-2 ring-amber-400 bg-amber-50/20' : '' }}">
            <div>
                <div class="flex items-center gap-2">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Garansi Gantung (Perlu Dibuat)</p>
                    @if($pendingCount > 0)
                        <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white animate-pulse">Perlu Tindakan</span>
                    @endif
                </div>
                <h3 class="text-2xl font-black text-amber-600 mt-1">{{ number_format($pendingCount) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">Sudah QC tapi kartu garansi belum ada</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Garansi Sudah Aktif</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($activeCount) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">Memiliki kartu garansi resmi toko</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Filter Tabs & Search -->
        <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Tabs -->
            <div class="flex items-center gap-1.5 p-1 bg-gray-100/80 rounded-xl w-fit">
                <button type="button" wire:click="$set('statusFilter', 'pending')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $statusFilter === 'pending' ? 'bg-white text-amber-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                    <span>Menunggu Generate (Gantung)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600' }} font-black">
                        {{ $pendingCount }}
                    </span>
                </button>
                <button type="button" wire:click="$set('statusFilter', 'active')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $statusFilter === 'active' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                    <span>Sudah Aktif</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }} font-black">
                        {{ $activeCount }}
                    </span>
                </button>
                <button type="button" wire:click="$set('statusFilter', 'all')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $statusFilter === 'all' ? 'bg-white text-[#1c69d4] shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                    <span>Semua Riwayat</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'all' ? 'bg-blue-100 text-[#1c69d4]' : 'bg-gray-200 text-gray-600' }} font-black">
                        {{ $totalCount }}
                    </span>
                </button>
            </div>

            <!-- Business Unit Selector & Search Input -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                <div class="w-full sm:w-48">
                    <select wire:model.live="selectedBuId"
                        class="w-full rounded-xl border-gray-200 text-xs font-bold text-gray-700 focus:ring-[#1c69d4] focus:border-[#1c69d4]">
                        <option value="">Semua Business Unit</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative w-full sm:w-72">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari IMEI, Invoice, Pelanggan, Produk..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-xs font-medium">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-100 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-5 py-3.5">Waktu QC & Petugas</th>
                        <th class="px-5 py-3.5">Transaksi / Invoice</th>
                        <th class="px-5 py-3.5">Pelanggan</th>
                        <th class="px-5 py-3.5">Perangkat & IMEI</th>
                        <th class="px-5 py-3.5 text-center">Hasil QC</th>
                        <th class="px-5 py-3.5">Status & Rekomendasi Garansi</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($inspections as $ins)
                        @php
                            $orderItem = $ins->inspectable;
                            $order = $orderItem?->order;
                            $hasWarranty = !empty($ins->active_warranty);
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors {{ !$hasWarranty ? 'bg-amber-50/20' : '' }}">
                            <!-- Waktu QC & Petugas -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-bold text-gray-800">
                                    {{ $ins->inspected_at ? \Carbon\Carbon::parse($ins->inspected_at)->translatedFormat('d M Y, H:i') : '-' }}
                                </div>
                                <div class="text-gray-500 text-[11px] flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ $ins->inspector?->name ?? 'Kasir / QC' }}
                                </div>
                            </td>

                            <!-- Transaksi / Invoice -->
                            <td class="px-5 py-4">
                                <div class="font-bold text-[#1c69d4]">
                                    {{ $order?->order_number ?? 'Order #' . ($orderItem?->order_id ?? '-') }}
                                </div>
                                <div class="text-[11px] text-gray-500 mt-0.5">
                                    BU: <span class="font-bold text-gray-700">{{ $order?->businessUnit?->name ?? 'BU ' . ($order?->business_unit_id ?? '-') }}</span>
                                </div>
                            </td>

                            <!-- Pelanggan -->
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-800">
                                    {{ $order?->user?->name ?? $order?->customer_name ?? 'Pelanggan Toko' }}
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    {{ $order?->user?->email ?? '-' }}
                                </div>
                            </td>

                            <!-- Perangkat & IMEI -->
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-900 max-w-xs truncate" title="{{ $orderItem?->product_name }}">
                                    {{ $orderItem?->product_name ?? 'Produk tidak dikenal' }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="font-mono bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-[11px] font-bold tracking-wider border border-gray-200">
                                        {{ $ins->imei }}
                                    </span>
                                    <button type="button" 
                                        onclick="navigator.clipboard.writeText('{{ $ins->imei }}'); alert('IMEI disalin: {{ $ins->imei }}')" 
                                        class="text-gray-400 hover:text-gray-600 p-0.5 rounded transition-colors" title="Salin IMEI">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>

                            <!-- Hasil QC -->
                            <td class="px-5 py-4 text-center">
                                @if($ins->verdict === 'pass')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        PASS
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        {{ strtoupper($ins->verdict ?? 'QC') }}
                                    </span>
                                @endif
                                <button type="button" wire:click="openQcModal({{ $ins->id }})"
                                    class="block mx-auto text-[10px] text-gray-500 hover:text-[#1c69d4] underline mt-1 font-semibold">
                                    {{ $ins->passed_count ?? 0 }}/{{ $ins->total_items ?? 0 }} Checklist
                                </button>
                            </td>

                            <!-- Status & Rekomendasi Garansi -->
                            <td class="px-5 py-4">
                                @if($hasWarranty)
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800">
                                            GARANSI AKTIF
                                        </span>
                                        <span class="font-bold text-gray-800 text-[11px]">
                                            {{ $ins->active_warranty->duration_days }} Hari
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-1">
                                        {{ $ins->active_warranty->policy?->name ?? 'Kebijakan Toko' }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        Exp: {{ $ins->active_warranty->expires_at ? \Carbon\Carbon::parse($ins->active_warranty->expires_at)->format('d/m/Y') : '-' }}
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-800 animate-pulse">
                                            BELUM ADA GARANSI
                                        </span>
                                    </div>
                                    @if($ins->recommended_policy)
                                        <div class="mt-1 flex items-center gap-1 text-[11px] font-bold text-[#1c69d4]">
                                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            {{ $ins->recommended_policy->name }} ({{ $ins->recommended_policy->duration_days }} Hari)
                                        </div>
                                    @else
                                        <div class="mt-1 text-[11px] text-gray-400 italic">
                                            Pilih kebijakan manual
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                @if(!$hasWarranty)
                                    <button type="button" wire:click="openGenerateModal({{ $ins->id }})"
                                        class="px-3.5 py-2 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm transition-all flex items-center gap-1.5 ml-auto">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        Generate Garansi
                                    </button>
                                @else
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" wire:click="openWarrantyModal({{ $ins->id }})"
                                            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-xl text-[11px] transition-colors flex items-center gap-1 border border-emerald-200">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Kartu Garansi
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="font-bold text-gray-600 text-sm">Tidak Ada Data Ditemukan</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $statusFilter === 'pending' ? 'Semua perangkat yang telah diinspeksi QC sudah memiliki kartu garansi aktif.' : 'Tidak ada riwayat inspeksi sesuai filter yang dipilih.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($inspections->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $inspections->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL GENERATE GARANSI (PER IMEI) -->
    @if($showGenerateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in duration-200">
                <!-- Header Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-[#1c69d4] p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black leading-tight">Terbitkan Kartu Garansi</h3>
                                <p class="text-blue-100 text-xs mt-0.5">Aktivasi garansi manual untuk 1 nomor IMEI ini</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('showGenerateModal', false)"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body Modal -->
                <div class="p-6 space-y-5">
                    <!-- Info Perangkat Card -->
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2.5">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Perangkat & Tipe</span>
                                <h4 class="text-sm font-black text-gray-800">{{ $targetOrderItem?->product_name }}</h4>
                            </div>
                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-[#1c69d4]">
                                {{ $targetOrder?->businessUnit?->name ?? 'Cabang' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-200/60 text-xs">
                            <div>
                                <span class="text-gray-400 text-[10px] block">Nomor IMEI / SN</span>
                                <span class="font-mono font-bold text-gray-800 bg-white px-2 py-0.5 rounded border border-gray-200 inline-block mt-0.5">
                                    {{ $targetInspection?->imei }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] block">No. Invoice Order</span>
                                <span class="font-bold text-gray-800 inline-block mt-0.5">
                                    {{ $targetOrder?->order_number }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] block">Nama Pelanggan</span>
                                <span class="font-bold text-gray-800 inline-block mt-0.5">
                                    {{ $targetOrder?->user?->name ?? $targetOrder?->customer_name ?? 'Pelanggan Toko' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] block">Waktu QC Dilakukan</span>
                                <span class="font-bold text-gray-800 inline-block mt-0.5">
                                    {{ $targetInspection?->inspected_at ? \Carbon\Carbon::parse($targetInspection->inspected_at)->translatedFormat('d M Y, H:i') : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Rekomendasi Otomatis Box -->
                    @if($suggestedPolicy)
                        <div class="p-3.5 bg-blue-50 rounded-2xl border border-blue-100 flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="text-xs">
                                <p class="font-bold text-blue-900">Rekomendasi Kebijakan Toko:</p>
                                <p class="text-blue-700 font-semibold mt-0.5">
                                    {{ $suggestedPolicy->name }} &bull; Durasi: <span class="font-bold underline">{{ $suggestedPolicy->duration_days }} Hari</span> ({{ $suggestedPolicy->coverage_type == 'full_cover' ? 'Full Cover' : 'Ganti Unit' }})
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- Pilihan Kebijakan Garansi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">
                            Pilih Kebijakan Garansi yang Diterapkan: <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model.live="selectedPolicyId"
                            class="w-full rounded-xl border-gray-200 text-xs font-bold text-gray-800 focus:ring-[#1c69d4] focus:border-[#1c69d4] p-2.5">
                            <option value="">-- Pilih Kebijakan Garansi --</option>
                            @foreach($availablePolicies as $pol)
                                <option value="{{ $pol->id }}">
                                    {{ $pol->name }} ({{ $pol->duration_days }} Hari - {{ $pol->coverage_type == 'full_cover' ? 'Full Cover' : 'Ganti Unit' }})
                                </option>
                            @endforeach
                        </select>
                        @error('selectedPolicyId')
                            <p class="text-rose-500 text-[11px] mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Preview Masa Berlaku -->
                    @php
                        $chosenPolicy = collect($availablePolicies)->firstWhere('id', $selectedPolicyId);
                    @endphp
                    @if($chosenPolicy)
                        <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-emerald-700 font-bold block">Masa Berlaku Garansi</span>
                                <span class="text-emerald-900 font-semibold text-[11px]">
                                    Mulai: {{ \Carbon\Carbon::now()->format('d/m/Y') }} s/d {{ \Carbon\Carbon::now()->addDays($chosenPolicy->duration_days)->format('d/m/Y') }}
                                </span>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-600 text-white font-black text-xs rounded-xl shadow-xs">
                                {{ $chosenPolicy->duration_days }} HARI
                            </span>
                        </div>
                    @endif

                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/80 text-[11px] text-amber-800">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>
                                Menekan tombol di bawah hanya akan mengaktifkan garansi <strong>khusus untuk IMEI ini saja</strong>. Transaksi atau IMEI lainnya tidak akan terpengaruh.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="p-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                    <button type="button" wire:click="$set('showGenerateModal', false)"
                        class="px-4 py-2.5 text-gray-600 hover:bg-gray-200 font-bold text-xs rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="button" wire:click="confirmGenerate" wire:loading.attr="disabled"
                        class="px-5 py-2.5 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="confirmGenerate">
                            Konfirmasi & Terbitkan Garansi
                        </span>
                        <span wire:loading wire:target="confirmGenerate" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DETAIL QC INSPEKSI -->
    @if($showQcModal && $viewingInspection)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl overflow-hidden border border-gray-100">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-gray-800">Detail Hasil Inspeksi QC</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            IMEI: <span class="font-mono font-bold text-gray-700">{{ $viewingInspection->imei }}</span> &bull; 
                            Petugas: {{ $viewingInspection->inspector?->name ?? 'Kasir' }}
                        </p>
                    </div>
                    <button type="button" wire:click="$set('showQcModal', false)"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 max-h-96 overflow-y-auto space-y-4">
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl">
                        <span class="text-xs font-bold text-gray-600">Verdict Akhir</span>
                        @if($viewingInspection->verdict === 'pass')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">
                                LULUS (PASS)
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800">
                                GAGAL (FAIL)
                            </span>
                        @endif
                    </div>

                    @php
                        $results = is_array($viewingInspection->checklist_results) 
                            ? $viewingInspection->checklist_results 
                            : (json_decode($viewingInspection->checklist_results, true) ?? []);
                    @endphp

                    <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                        @forelse($results as $item)
                            <div class="p-3 flex items-center justify-between text-xs hover:bg-gray-50">
                                <span class="font-semibold text-gray-700">{{ $item['name'] ?? 'Item' }}</span>
                                <div>
                                    @if(isset($item['type']) && $item['type'] === 'text')
                                        <span class="font-bold text-gray-800">{{ $item['value'] ?? '-' }}</span>
                                    @elseif(isset($item['value']) && ($item['value'] === true || $item['value'] === 1 || $item['value'] === '1'))
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">OK</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">FAIL / NO</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-gray-400">Tidak ada detail checklist.</div>
                        @endforelse
                    </div>

                    @if(!empty($viewingInspection->inspector_notes))
                        <div class="p-3 bg-amber-50 rounded-xl text-xs text-amber-800">
                            <span class="font-bold block">Catatan Inspector:</span>
                            <p class="mt-0.5">{{ $viewingInspection->inspector_notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="$set('showQcModal', false)"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DETAIL KARTU GARANSI AKTIF -->
    @if($showWarrantyModal && $viewingWarranty)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black leading-tight">Kartu Garansi Resmi</h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-white/20 text-white mt-1 inline-block">
                                    STATUS: AKTIF
                                </span>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('showWarrantyModal', false)"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <div class="space-y-2">
                        <div class="flex justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Kebijakan Garansi</span>
                            <span class="font-bold text-gray-800">{{ $viewingWarranty->policy?->name ?? 'Default Toko' }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Nomor Serial / IMEI</span>
                            <span class="font-mono font-bold text-gray-800">{{ $viewingWarranty->serial_number }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Tipe Perlindungan</span>
                            <span class="font-bold text-gray-800">{{ $viewingWarranty->type == 'full_cover' ? 'Full Cover' : 'Ganti Unit' }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Durasi</span>
                            <span class="font-bold text-emerald-600">{{ $viewingWarranty->duration_days }} Hari</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Tanggal Diaktifkan</span>
                            <span class="font-bold text-gray-800">
                                {{ $viewingWarranty->activated_at ? \Carbon\Carbon::parse($viewingWarranty->activated_at)->translatedFormat('d F Y, H:i') : '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Tanggal Berakhir</span>
                            <span class="font-bold text-rose-600">
                                {{ $viewingWarranty->expires_at ? \Carbon\Carbon::parse($viewingWarranty->expires_at)->translatedFormat('d F Y') : '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-gray-500">Klaim Terpakai</span>
                            <span class="font-bold text-gray-800">{{ $viewingWarranty->claims_used ?? 0 }} Kali</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="$set('showWarrantyModal', false)"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
