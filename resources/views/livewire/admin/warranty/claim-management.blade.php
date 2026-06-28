<div class="space-y-6 p-4 md:p-8">
    <!-- Header -->
    <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Manajemen Klaim Garansi</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola dan proses pengajuan klaim garansi dari pelanggan.</p>
        </div>
    </div>

    <!-- Filters & List -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative w-full md:w-96">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari No Klaim, SN, Pelanggan..."
                    class="w-full bg-gray-50 border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] transition-colors">
            </div>

            <select wire:model.live="statusFilter"
                class="bg-gray-50 border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] text-gray-700">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Persetujuan (Pending)</option>
                <option value="approved">Disetujui (Approved)</option>
                <option value="in_repair">Dalam Perbaikan (In Repair)</option>
                <option value="completed">Selesai (Completed)</option>
                <option value="rejected">Ditolak (Rejected)</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">No Klaim / Tgl
                        </th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Garansi & SN</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Pelanggan</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($claims as $claim)
                        <tr wire:key="claim-{{ $claim->id }}"
                            class="hover:bg-blue-50/30 transition-colors duration-200">
                            <td class="py-4 px-6">
                                <div class="font-bold text-blue-600">{{ $claim->claim_number }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $claim->claimed_at->format('d M Y H:i') }}
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $claim->warranty->policy->name ?? 'Unknown' }}
                                </div>
                                <div class="text-xs font-mono text-gray-500 mt-1">SN: {{ $claim->serial_number }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $claim->customer->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $claim->customer->phone_number ?? '-' }}
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if ($claim->status === 'pending')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                                    </span>
                                @elseif($claim->status === 'approved')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Disetujui
                                    </span>
                                @elseif($claim->status === 'in_repair')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Diproses
                                    </span>
                                @elseif($claim->status === 'completed')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                                    </span>
                                @elseif($claim->status === 'rejected')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button wire:click="openProcessModal({{ $claim->id }})"
                                    class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 text-xs font-bold transition-colors">
                                    Lihat & Proses
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-300"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-lg font-medium text-gray-500">Belum ada klaim garansi</p>
                                </div>
                            </td>
                        </tr>
                    @endempty
            </tbody>
        </table>
    </div>
    @if ($claims->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $claims->links() }}
        </div>
    @endif
</div>

<!-- Modal Form Replacement Accurate -->
@if ($showReplacementForm && $selectedClaimId)
    @php
        $selectedClaim = $selectedClaimObj;
    @endphp
    @if ($selectedClaim)
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            <div
                class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                <div
                    class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-amber-50">
                    <div>
                        <h3 class="font-bold text-xl text-amber-900">Form Retur & Ganti Unit (Accurate)</h3>
                        <p class="text-xs text-amber-700 mt-0.5">Sistem akan memotong stok secara otomatis</p>
                    </div>
                    <button wire:click="closeReplacementForm"
                        class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <!-- Info Barang (Read Only) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6 shadow-sm">
                        <h4
                            class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 border-b border-gray-200 pb-2">
                            Data Retur (Otomatis)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">Nama Produk</p>
                                <p class="font-bold text-gray-800 text-sm line-clamp-1">
                                    {{ $selectedClaim->warranty->orderItem->product_name ?? ($selectedClaim->warranty->orderItem->variant->name ?? 'Unknown Product') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">Pelanggan</p>
                                <p class="font-bold text-gray-800 text-sm">
                                    {{ $selectedClaim->customer->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">No Faktur Awal</p>
                                <p class="font-bold text-gray-800 text-sm">
                                    {{ $selectedClaim->warranty->orderItem->order->accurate_invoice_no ?? ($selectedClaim->warranty->orderItem->order->order_number ?? '-') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">IMEI / SN (Barang Rusak)</p>
                                <p class="font-bold font-mono text-gray-900 text-sm">
                                    {{ $selectedClaim->serial_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Input -->
                    <div class="space-y-4">
                        <!-- Tipe Ganti Unit -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Jenis Penggantian <span class="text-amber-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="replacement_type" value="same" class="peer sr-only">
                                    <div class="p-3 rounded-xl border-2 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800 border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center gap-2">
                                        <div class="w-4 h-4 rounded-full border-2 border-current flex items-center justify-center shrink-0">
                                            <div class="w-2 h-2 rounded-full bg-current opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                        </div>
                                        <span class="text-sm font-bold">Ganti Unit Sama (1:1)</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="replacement_type" value="different" class="peer sr-only">
                                    <div class="p-3 rounded-xl border-2 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800 border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center gap-2">
                                        <div class="w-4 h-4 rounded-full border-2 border-current flex items-center justify-center shrink-0">
                                            <div class="w-2 h-2 rounded-full bg-current opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                        </div>
                                        <span class="text-sm font-bold">Unit Beda (Upgrade/Downgrade)</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Pilih Produk Pengganti (Jika Beda) -->
                        @if($replacement_type === 'different')
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                            @if(!$replacement_item_no)
                            <div class="relative">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Cari Produk Pengganti <span class="text-amber-500">*</span></label>
                                <div class="absolute inset-y-0 left-0 pl-3 pt-6 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" wire:model.live.debounce.300ms="search_product_query"
                                    placeholder="Ketik nama produk pengganti..."
                                    class="w-full bg-white border-gray-300 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                
                                @if(count($product_results) > 0)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                    @foreach($product_results as $prod)
                                    <button type="button" wire:click="selectReplacementProduct('{{ $prod['item_no'] }}', '{{ $prod['name'] }}', {{ $prod['base_price'] ?? 0 }})"
                                        class="w-full text-left px-4 py-3 hover:bg-amber-50 transition-colors border-b border-gray-100 last:border-0 flex justify-between items-center">
                                        <div>
                                            <div class="font-bold text-gray-800 text-sm line-clamp-1">{{ $prod['name'] }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">SKU: {{ $prod['item_no'] }}</div>
                                        </div>
                                        <div class="text-amber-600 font-bold text-sm">
                                            Rp {{ number_format($prod['base_price'] ?? 0, 0, ',', '.') }}
                                        </div>
                                    </button>
                                    @endforeach
                                </div>
                                @elseif(strlen($search_product_query) > 2)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 text-center text-sm text-gray-500">
                                    Produk tidak ditemukan.
                                </div>
                                @endif
                            </div>
                            @error('replacement_item_no')
                                <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                    Anda wajib memilih produk pengganti.
                                </div>
                            @enderror
                            @else
                            <!-- Produk Terpilih -->
                            <div class="bg-white border-2 border-amber-200 rounded-xl p-4 shadow-sm relative">
                                <button type="button" wire:click="cancelReplacementProduct" class="absolute top-2 right-2 text-gray-400 hover:text-rose-500 p-1 bg-gray-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                                <p class="text-[11px] text-gray-500 uppercase font-bold tracking-wider mb-1">Produk Pengganti Terpilih</p>
                                <p class="font-bold text-gray-900 text-sm pr-6">{{ $replacement_product_name }}</p>
                                
                                @php
                                    $oldPrice = (float)($original_price ?? 0);
                                    $newPrice = (float)($replacement_price ?? 0);
                                    $diff = $newPrice - $oldPrice;
                                @endphp
                                
                                <div class="mt-3 flex gap-4 text-sm bg-amber-50 p-3 rounded-lg border border-amber-100 items-end">
                                    <div class="flex-1">
                                        <p class="text-[10px] uppercase font-bold text-amber-700 mb-1">Harga Lama (Nilai Retur Faktur)</p>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                                <span class="text-sm text-amber-700 font-bold">Rp</span>
                                            </div>
                                            <input type="number" wire:model.live.debounce.300ms="original_price" class="w-full bg-white border border-amber-200 rounded-lg pl-8 pr-2 py-2 text-sm font-bold text-amber-900 focus:ring-amber-500 focus:border-amber-500 shadow-sm" title="Ubah jika nilai faktur Accurate berbeda (misal karena diskon/MDR)">
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] uppercase font-bold text-amber-700 mb-1">Harga Baru</p>
                                        <div class="bg-white border border-amber-100 rounded-lg px-3 py-2">
                                            <p class="font-bold text-amber-900 text-sm">Rp {{ number_format($newPrice, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                    <div class="border-l-2 border-amber-200 pl-4 flex-1">
                                        <p class="text-[10px] uppercase font-bold text-amber-700 mb-1">Selisih</p>
                                        @if($diff > 0)
                                            <p class="font-black text-rose-600 text-sm">+ Rp {{ number_format($diff, 0, ',', '.') }} <span class="text-[10px] font-normal block leading-tight">Pelanggan Kurang Bayar</span></p>
                                        @elseif($diff < 0)
                                            <p class="font-black text-emerald-600 text-sm">- Rp {{ number_format(abs($diff), 0, ',', '.') }} <span class="text-[10px] font-normal block leading-tight">Refund ke Pelanggan</span></p>
                                        @else
                                            <p class="font-black text-amber-900 text-sm">Rp 0</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pilih Bank untuk Refund/Pembayaran -->
                            <div class="mt-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Pilih Bank / Kas Pembayaran <span class="text-amber-500">*</span>
                                </label>
                                <select wire:model="bank_no" class="w-full bg-white border-gray-300 rounded-xl p-3 text-sm focus:ring-amber-500 focus:border-amber-500 shadow-sm">
                                    <option value="">-- Pilih Akun Bank/Kas Accurate --</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->account_no }}">{{ $bank->name }} ({{ $bank->account_no }})</option>
                                    @endforeach
                                </select>
                                @error('bank_no')
                                    <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                        Anda wajib memilih bank untuk proses pelunasan/refund.
                                    </div>
                                @enderror
                            </div>
                            @endif
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">IMEI / Serial Number Baru
                                <span class="text-amber-500">*</span></label>
                            <input type="text" wire:model="replacement_imei"
                                class="w-full bg-white border border-gray-300 rounded-xl p-3 text-sm focus:ring-amber-500 focus:border-amber-500 font-mono shadow-sm"
                                placeholder="Scan atau ketik IMEI unit pengganti...">
                            @error('replacement_imei')
                                <div
                                    class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="bg-blue-50 border border-blue-100 p-3 rounded-lg flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-xs text-blue-800 leading-relaxed">
                                <p class="mb-1">Pastikan IMEI baru telah disiapkan. Sistem akan menembak secara *realtime*:</p>
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li><b>Sales Return</b> (menarik IMEI lama)</li>
                                    <li><b>Sales Invoice</b> (mengeluarkan IMEI baru)</li>
                                    <li><b>Sales Receipt</b> (menyelesaikan piutang/refund otomatis)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="closeReplacementForm"
                            class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                        <button type="button" wire:click="approveReplacement"
                            class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2"
                            wire:loading.attr="disabled" wire:target="approveReplacement">
                            <span wire:loading.remove wire:target="approveReplacement">Eksekusi Retur
                                Accurate</span>
                            <span wire:loading wire:target="approveReplacement">Memproses API...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

<!-- Modal Form Service (Perbaikan Biasa) -->
@if ($showServiceForm && $selectedClaimId)
    @php
        $selectedClaim = $selectedClaimObj;
    @endphp
    @if ($selectedClaim)
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 min-h-screen backdrop-blur-sm transition-opacity"></div>
            <div
                class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                <div
                    class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-blue-50">
                    <div>
                        <h3 class="font-bold text-xl text-blue-900">Form Persetujuan Servis (Perbaikan)</h3>
                        <p class="text-xs text-blue-700 mt-0.5">Kirim perangkat ini ke Service Center internal /
                            mitra</p>
                    </div>
                    <button wire:click="closeServiceForm"
                        class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <!-- Info Barang (Read Only) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6 shadow-sm">
                        <h4
                            class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 border-b border-gray-200 pb-2">
                            Informasi Perangkat</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">Nama Produk</p>
                                <p class="font-bold text-gray-800 text-sm line-clamp-1">
                                    {{ $selectedClaim->warranty->orderItem->product_name ?? ($selectedClaim->warranty->orderItem->variant->name ?? 'Unknown Product') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">Pelanggan</p>
                                <p class="font-bold text-gray-800 text-sm">
                                    {{ $selectedClaim->customer->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">IMEI / SN (Barang Rusak)</p>
                                <p class="font-bold font-mono text-gray-900 text-sm">
                                    {{ $selectedClaim->serial_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Input -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Persetujuan Servis
                                (Opsional)</label>
                            <textarea wire:model="resolution_notes" rows="3"
                                class="w-full bg-white border border-gray-300 rounded-xl p-3 text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                placeholder="Tulis instruksi khusus untuk teknisi atau catatan ke pelanggan..."></textarea>
                            @error('resolution_notes')
                                <div
                                    class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="closeServiceForm"
                            class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                        <button type="button" wire:click="approveService"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2"
                            wire:loading.attr="disabled" wire:target="approveService">
                            <span wire:loading.remove wire:target="approveService">Setujui & Proses Servis</span>
                            <span wire:loading wire:target="approveService">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

<!-- Modal Form Tolak Klaim -->
@if ($showRejectForm && $selectedClaimId)
    @php
        $selectedClaim = $selectedClaimObj;
    @endphp
    @if ($selectedClaim)
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            <div
                class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                <div
                    class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-rose-50">
                    <div>
                        <h3 class="font-bold text-xl text-rose-900">Form Penolakan Klaim Garansi</h3>
                        <p class="text-xs text-rose-700 mt-0.5">Berikan alasan mengapa klaim ini ditolak (misal:
                            Human Error)</p>
                    </div>
                    <button wire:click="closeRejectForm"
                        class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <!-- Info Barang (Read Only) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6 shadow-sm">
                        <h4
                            class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 border-b border-gray-200 pb-2">
                            Informasi Perangkat</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">Nama Produk</p>
                                <p class="font-bold text-gray-800 text-sm line-clamp-1">
                                    {{ $selectedClaim->warranty->orderItem->product_name ?? ($selectedClaim->warranty->orderItem->variant->name ?? 'Unknown Product') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">Pelanggan</p>
                                <p class="font-bold text-gray-800 text-sm">
                                    {{ $selectedClaim->customer->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 mb-0.5">IMEI / SN (Barang Rusak)</p>
                                <p class="font-bold font-mono text-gray-900 text-sm">
                                    {{ $selectedClaim->serial_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Input -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Penolakan <span
                                    class="text-rose-500">*</span></label>
                            <textarea wire:model="resolution_notes" rows="3" required
                                class="w-full bg-white border border-gray-300 rounded-xl p-3 text-sm focus:ring-rose-500 focus:border-rose-500 shadow-sm"
                                placeholder="Tulis alasan jelas kenapa klaim garansi ditolak (misal: Layar retak karena jatuh)..."></textarea>
                            @error('resolution_notes')
                                <div
                                    class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="closeRejectForm"
                            class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                        <button type="button" wire:click="rejectClaim"
                            class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2"
                            wire:loading.attr="disabled" wire:target="rejectClaim">
                            <span wire:loading.remove wire:target="rejectClaim">Konfirmasi Penolakan</span>
                            <span wire:loading wire:target="rejectClaim">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

<!-- Process Modal -->
@if ($showModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeModal">
        </div>

        <div
            class="relative bg-gray-50 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
            <div class="bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                <h3 class="font-bold text-xl text-gray-800">Detail & Proses Klaim Garansi</h3>
                <button wire:click="closeModal"
                    class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @php
                $selectedClaim = $selectedClaimObj;
            @endphp

            @if ($selectedClaim)
                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    @if (!$viewingQcDetails)
                        <!-- Top Section: Info & Perbandingan QC -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                            <!-- Col 1: Claim Info -->
                            <div
                                class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex flex-col h-full">
                                <h4
                                    class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">
                                    Info Tiket</h4>
                                <div class="space-y-4 flex-1">
                                    <div>
                                        <p class="text-gray-500 text-xs mb-1">No Klaim</p>
                                        <p class="font-bold text-blue-600">{{ $selectedClaim->claim_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs mb-1">SN / IMEI Perangkat</p>
                                        <p class="font-mono font-bold text-gray-900">
                                            {{ $selectedClaim->serial_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs mb-1">Keluhan Pelanggan</p>
                                        <div
                                            class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-100 text-sm font-medium">
                                            {{ $selectedClaim->issue_description }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Col 2 & 3: QC Comparison -->
                            <div
                                class="lg:col-span-2 bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex flex-col h-full">
                                <h4
                                    class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">
                                    Perbandingan Kondisi (QC)</h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- QC Awal -->
                                    <div
                                        class="bg-gray-50 p-4 justify-between flex flex-col rounded-xl border border-gray-200">
                                        <h5
                                            class="font-bold text-gray-800 mb-3 text-center bg-gray-200 py-1 rounded-lg">
                                            QC Saat Beli (Unboxing)</h5>
                                        @if ($originalInspection)
                                            @php
                                                $frontImg = $originalInspection->getMedia('qc_photos')->first();
                                            @endphp
                                            <div
                                                class="aspect-square bg-gray-200 rounded-lg overflow-hidden mb-3 relative">
                                                @if ($frontImg)
                                                    <img src="{{ $frontImg->getUrl() }}"
                                                        class="w-full h-full object-cover">
                                                    <div
                                                        class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[10px] text-center py-1">
                                                        Tampak Depan</div>
                                                @else
                                                    <div
                                                        class="flex items-center justify-center h-full text-gray-400 text-sm">
                                                        No Photo</div>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                @php
                                                    $failsOrig = collect($originalInspection->checklist_results ?? [])
                                                        ->where('type', 'boolean')
                                                        ->where('value', '0');
                                                @endphp
                                                <p class="font-bold">Kerusakan Awal: <span
                                                        class="{{ $failsOrig->count() > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $failsOrig->count() }}
                                                        item</span></p>
                                            </div>
                                            <button wire:click="viewQcDetails('original')"
                                                class="mt-3 w-full py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-lg transition-colors">
                                                Lihat Detail Lengkap
                                            </button>
                                        @else
                                            <div class="text-center py-8 text-gray-400 text-sm">Tidak ada data QC
                                                Unboxing</div>
                                        @endif
                                    </div>

                                    <!-- QC Masuk -->
                                    <div
                                        class="bg-blue-50 p-4 justify-between flex flex-col rounded-xl border border-blue-100">
                                        <h5
                                            class="font-bold text-blue-900 mb-3 text-center bg-blue-200 py-1 rounded-lg">
                                            QC Saat Klaim Masuk</h5>
                                        @if ($claimInspection)
                                            @php
                                                $frontImgClaim = $claimInspection->getMedia('qc_photos')->first();
                                            @endphp
                                            <div
                                                class="aspect-square bg-blue-100 rounded-lg overflow-hidden mb-3 relative">
                                                @if ($frontImgClaim)
                                                    <img src="{{ $frontImgClaim->getUrl() }}"
                                                        class="w-full h-full object-cover">
                                                    <div
                                                        class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[10px] text-center py-1">
                                                        Tampak Depan</div>
                                                @else
                                                    <div
                                                        class="flex items-center justify-center h-full text-gray-400 text-sm">
                                                        No Photo</div>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                @php
                                                    $failsClaim = collect($claimInspection->checklist_results ?? [])
                                                        ->where('type', 'boolean')
                                                        ->where('value', '0');
                                                @endphp
                                                <p class="font-bold">Kerusakan Saat Ini: <span
                                                        class="{{ $failsClaim->count() > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $failsClaim->count() }}
                                                        item</span></p>
                                                @if ($failsClaim->count() > 0)
                                                    <ul class="list-disc pl-4 mt-1 text-rose-600">
                                                        @foreach ($failsClaim->take(3) as $fc)
                                                            <li>{{ $fc['name'] }}</li>
                                                        @endforeach
                                                        @if ($failsClaim->count() > 3)
                                                            <li>...dll</li>
                                                        @endif
                                                    </ul>
                                                @endif
                                            </div>
                                            <button wire:click="viewQcDetails('claim')"
                                                class="mt-3 w-full py-1.5 bg-blue-200 hover:bg-blue-300 text-blue-800 font-bold text-xs rounded-lg transition-colors">
                                                Lihat Detail Lengkap
                                            </button>
                                        @else
                                            <div class="text-center py-8 text-blue-400 text-sm">Tidak ada data QC
                                                Klaim</div>
                                        @endif
                                    </div>
                                </div>

                                @if ($originalInspection && $claimInspection)
                                    <div
                                        class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-lg text-sm text-amber-800 flex items-start gap-2">
                                        <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                            </path>
                                        </svg>
                                        <div>
                                            <span class="font-bold">Keputusan Admin:</span> Pastikan kerusakan saat
                                            ini sesuai dengan cakupan garansi dan tidak ada kerusakan *human error*
                                            yang baru terjadi (seperti retak parah yang tidak ada di foto unboxing).
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Bottom Section: Tindakan Admin -->
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h4
                                class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">
                                Tindakan Keputusan & Integrasi Accurate</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Catatan Resolusi sekarang dipindah ke Modal masing-masing (Servis / Tolak) -->
                            </div>

                            <div class="flex flex-wrap gap-2 mt-6 pt-4 border-t border-gray-100">
                                @if ($selectedClaim->status === 'pending')
                                    <button wire:click="openServiceForm"
                                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                        Setujui Klaim Biasa (Servis)
                                    </button>
                                    <button wire:click="openReplacementForm"
                                        class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                        Setujui & Ganti Unit (Accurate)
                                    </button>
                                    <button wire:click="openRejectForm"
                                        class="px-6 py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold rounded-lg text-sm transition-colors">
                                        Tolak Klaim (Reject)
                                    </button>
                                @endif

                                @if ($selectedClaim->status === 'approved')
                                    <button wire:click="updateStatus('in_repair')"
                                        class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                        Proses Perbaikan (In Repair)
                                    </button>
                                @endif

                                @if (in_array($selectedClaim->status, ['approved', 'in_repair']))
                                    <button wire:click="updateStatus('completed')"
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                        Selesai (Completed)
                                    </button>
                                @endif

                                @if ($selectedClaim->status === 'waiting_refund')
                                    <button wire:click="openRefundForm"
                                        class="px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                        Proses Refund Tunai
                                    </button>
                                @endif
                            </div>
                        </div>
                    @else
                        @php
                            $qcData = $viewingQcDetails === 'original' ? $originalInspection : $claimInspection;
                            $qcTitle =
                                $viewingQcDetails === 'original' ? 'QC Saat Beli (Unboxing)' : 'QC Saat Klaim Masuk';
                        @endphp

                        <div class="flex items-center gap-3 mb-4">
                            <button wire:click="closeQcDetails"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-lg transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Kembali
                            </button>
                            <h4 class="text-lg font-black text-gray-800">Detail {{ $qcTitle }}</h4>
                        </div>

                        @if ($qcData)
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="p-6 border-b border-gray-100">
                                    <h5 class="font-bold text-gray-900 mb-4">Bukti Foto</h5>
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                        @foreach ($qcData->getMedia('qc_photos') as $media)
                                            <div
                                                class="aspect-square bg-gray-100 rounded-xl overflow-hidden shadow-inner group relative">
                                                <img src="{{ $media->getUrl() }}" alt="QC Photo"
                                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                                <div
                                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <a href="{{ $media->getUrl() }}" target="_blank"
                                                        class="px-3 py-1.5 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-lg border border-white/30 hover:bg-white/40">Perbesar</a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="p-6 bg-gray-50">
                                    <h5 class="font-bold text-gray-900 mb-4">Hasil Pengecekan Fungsional</h5>
                                    @php
                                        $groupedResults = collect($qcData->checklist_results ?? [])->groupBy(
                                            'category',
                                        );
                                    @endphp
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        @foreach ($groupedResults as $category => $items)
                                            <div
                                                class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                                <div class="bg-gray-100 px-4 py-2 border-b border-gray-200">
                                                    <h6 class="text-xs font-bold text-gray-600 uppercase">
                                                        {{ $category }}</h6>
                                                </div>
                                                <div class="p-4 grid gap-3">
                                                    @foreach ($items as $item)
                                                        <div class="flex items-center justify-between">
                                                            <span
                                                                class="text-sm font-medium text-gray-700">{{ $item['name'] }}</span>
                                                            @if ($item['type'] === 'boolean')
                                                                @if ($item['value'])
                                                                    <span
                                                                        class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">Bagus</span>
                                                                @else
                                                                    <span
                                                                        class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 px-2 py-1 rounded-md border border-rose-200">Cacat</span>
                                                                @endif
                                                            @else
                                                                <span
                                                                    class="text-sm font-bold text-gray-900">{{ $item['value'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($qcData->inspector_notes)
                                        <div class="mt-6 p-4 bg-amber-50 border border-amber-100 rounded-xl">
                                            <h6 class="text-xs font-bold text-amber-800 mb-1">Catatan Inspektur
                                            </h6>
                                            <p class="text-sm text-amber-900">{{ $qcData->inspector_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif

    <!-- Modal Proses Refund -->
    @if ($showRefundForm && $selectedClaimId)
        @php
            $selectedClaim = $selectedClaimObj;
        @endphp
        @if ($selectedClaim)
            <div class="fixed inset-0 z-[120] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="relative bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-yellow-50">
                        <div>
                            <h3 class="font-bold text-xl text-yellow-900">Proses Refund Tunai</h3>
                            <p class="text-xs text-yellow-700 mt-0.5">Cairkan sisa saldo ke Kas/Bank</p>
                        </div>
                        <button wire:click="closeRefundForm"
                            class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto flex-1">
                        <div class="bg-gray-50 rounded-xl p-4 mb-5 text-center border border-gray-200 shadow-inner">
                            <p class="text-sm text-gray-500 mb-1">Nominal Refund (Kelebihan Bayar)</p>
                            <p class="text-3xl font-black text-rose-600">Rp {{ number_format($selectedClaim->refund_amount ?? 0, 0, ',', '.') }}</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2" for="bank_no">
                                    Sumber Kas/Bank (Uang Keluar) <span class="text-rose-500">*</span>
                                </label>
                                <select id="bank_no" wire:model="bank_no"
                                    class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 block p-3 transition-colors">
                                    <option value="">-- Pilih Akun Bank / Kas (Sesuai Accurate) --</option>
                                    @foreach (\App\Models\AccurateGlAccount::where('account_type', 'CASH_BANK')->get() as $account)
                                        <option value="{{ $account->account_no }}">{{ $account->account_no }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_no')
                                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                        <button wire:click="closeRefundForm" type="button"
                            class="px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 hover:text-gray-900 rounded-xl transition-colors shadow-sm">
                            Batal
                        </button>
                        <button wire:click="processRefundCash({{ $selectedClaimId }})" type="button"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-yellow-600 hover:bg-yellow-700 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            Cairkan Uang Sekarang
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
