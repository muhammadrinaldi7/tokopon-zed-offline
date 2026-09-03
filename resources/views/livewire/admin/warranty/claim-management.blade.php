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
                <option value="waiting_refund">Menunggu Refund Kasir</option>
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
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 {{ $claim->status_badge->bg }} rounded-full text-xs font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $claim->status_badge->dot }}"></span>
                                    {{ $claim->status_badge->label }}
                                </span>
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
        <div wire:key="modal-replacement-form" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
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


                    @if($hasPendingApproval)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
                            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-amber-50">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-amber-900 mb-2">Menunggu Persetujuan</h4>
                            <p class="text-sm text-amber-700">Pengajuan ganti unit sedang dalam antrean <b>Approval</b>. Eksekusi pemotongan stok dan faktur Accurate akan otomatis berjalan setelah Admin menyetujui pengajuan ini.</p>
                        </div>
                    @else
                        <!-- Form Input -->
                        <div class="space-y-4">
                        <!-- Tipe Ganti Unit -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Jenis Penggantian <span
                                    class="text-amber-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="replacement_type" value="same"
                                        class="peer sr-only">
                                    <div
                                        class="p-3 rounded-xl border-2 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800 border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center gap-2">
                                        <div
                                            class="w-4 h-4 rounded-full border-2 border-current flex items-center justify-center shrink-0">
                                            <div
                                                class="w-2 h-2 rounded-full bg-current opacity-0 peer-checked:opacity-100 transition-opacity">
                                            </div>
                                        </div>
                                        <span class="text-sm font-bold">Ganti Unit Sama (1:1)</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="replacement_type" value="different"
                                        class="peer sr-only">
                                    <div
                                        class="p-3 rounded-xl border-2 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800 border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center gap-2">
                                        <div
                                            class="w-4 h-4 rounded-full border-2 border-current flex items-center justify-center shrink-0">
                                            <div
                                                class="w-2 h-2 rounded-full bg-current opacity-0 peer-checked:opacity-100 transition-opacity">
                                            </div>
                                        </div>
                                        <span class="text-sm font-bold">Unit Beda (Upgrade/Downgrade)</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Pilih Produk Pengganti (Jika Beda) -->
                        @if ($replacement_type === 'different')
                            <div class="relative z-50 bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                                @if (!$replacement_item_no)
                                    <div class="relative">
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Cari Produk
                                            Pengganti <span class="text-amber-500">*</span></label>
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 pt-6 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <input type="text"
                                            wire:model.live.debounce.300ms="search_product_query"
                                            placeholder="Ketik nama produk pengganti..."
                                            class="w-full bg-white border-gray-300 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-amber-500 focus:border-amber-500 transition-colors">

                                        @if (count($product_results) > 0)
                                            <div
                                                class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                                @foreach ($product_results as $prod)
                                                    <button type="button"
                                                        wire:click="selectReplacementProduct('{{ $prod['item_no'] }}', '{{ $prod['name'] }}', {{ $prod['base_price'] ?? 0 }})"
                                                        class="w-full text-left px-4 py-3 hover:bg-amber-50 transition-colors border-b border-gray-100 last:border-0 flex justify-between items-center">
                                                        <div>
                                                            <div
                                                                class="font-bold text-gray-800 text-sm line-clamp-1">
                                                                {{ $prod['name'] }}</div>
                                                            <div class="text-xs text-gray-500 mt-0.5">SKU:
                                                                {{ $prod['item_no'] }}</div>
                                                        </div>
                                                        <div class="text-amber-600 font-bold text-sm">
                                                            Rp
                                                            {{ number_format($prod['base_price'] ?? 0, 0, ',', '.') }}
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @elseif(strlen($search_product_query) > 2)
                                            <div
                                                class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 text-center text-sm text-gray-500">
                                                Produk tidak ditemukan.
                                            </div>
                                        @endif
                                    </div>
                                    @error('replacement_item_no')
                                        <div
                                            class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                            Anda wajib memilih produk pengganti.
                                        </div>
                                    @enderror
                                @else
                                    <!-- Produk Terpilih -->
                                    <div
                                        class="bg-white border-2 border-amber-200 rounded-xl p-4 shadow-sm relative">
                                        <button type="button" wire:click="cancelReplacementProduct"
                                            class="absolute top-2 right-2 text-gray-400 hover:text-rose-500 p-1 bg-gray-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <p
                                            class="text-[11px] text-gray-500 uppercase font-bold tracking-wider mb-1">
                                            Produk Pengganti Terpilih</p>
                                        <p class="font-bold text-gray-900 text-sm pr-6">
                                            {{ $replacement_product_name }}</p>

                                        @php
                                            $oldPrice = (float) ($original_price ?? 0);
                                            $newPrice = (float) ($replacement_price ?? 0);
                                            $diff = $newPrice - $oldPrice;
                                        @endphp

                                        <div
                                            class="mt-3 flex gap-4 text-sm bg-amber-50 p-3 rounded-lg border border-amber-100 items-end">
                                            <div class="flex-1">
                                                <p class="text-[10px] uppercase font-bold text-amber-700 mb-1">
                                                    Harga Lama (Nilai Retur Faktur)</p>
                                                <div class="relative">
                                                    <div
                                                        class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                                        <span class="text-sm text-amber-700 font-bold">Rp</span>
                                                    </div>
                                                    <input type="number"
                                                        wire:model.live.debounce.300ms="original_price"
                                                        {{ !auth()->user()->can('edit_warranty_return_price') ? 'readonly' : '' }}
                                                        class="w-full {{ !auth()->user()->can('edit_warranty_return_price') ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' }} border border-amber-200 rounded-lg pl-8 pr-2 py-2 text-sm font-bold text-amber-900 focus:ring-amber-500 focus:border-amber-500 shadow-sm"
                                                        title="{{ !auth()->user()->can('edit_warranty_return_price') ? 'Anda tidak memiliki akses untuk mengubah harga retur' : 'Ubah jika nilai faktur Accurate berbeda (misal karena diskon/MDR)' }}">
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-1">
                                                    <p class="text-[10px] uppercase font-bold text-amber-700">
                                                        Harga Baru</p>
                                                    @if ($is_editing_replacement_price)
                                                        <button type="button" wire:click.prevent="toggleEditReplacementPrice" class="text-emerald-600 hover:text-emerald-800 transition p-0.5 rounded focus:ring-2 focus:ring-emerald-500" title="Simpan Harga">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </button>
                                                    @else
                                                        <button type="button" wire:click.prevent="toggleEditReplacementPrice" class="text-amber-600 hover:text-amber-800 transition p-0.5 rounded focus:ring-2 focus:ring-amber-500" title="Edit Harga">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                                @if ($is_editing_replacement_price)
                                                    <div class="relative"
                                                        x-data="{
                                                            price: @entangle('replacement_price').live,
                                                            formattedPrice: '',
                                                            formatNumber(value) {
                                                                if (!value) return '0';
                                                                let val = value.toString().replace(/\D/g, '');
                                                                return new Intl.NumberFormat('id-ID').format(val);
                                                            },
                                                            updatePrice(value) {
                                                                let numericValue = value.replace(/\D/g, '');
                                                                this.price = numericValue;
                                                                this.formattedPrice = this.formatNumber(numericValue);
                                                            },
                                                            init() {
                                                                this.formattedPrice = this.formatNumber(this.price);
                                                                $watch('price', value => {
                                                                    if (document.activeElement !== this.$refs.priceInput) {
                                                                        this.formattedPrice = this.formatNumber(value);
                                                                    }
                                                                });
                                                            }
                                                        }">
                                                        <div
                                                            class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                                            <span class="text-sm text-amber-700 font-bold">Rp</span>
                                                        </div>
                                                        <input type="text"
                                                            x-ref="priceInput"
                                                            :value="formattedPrice"
                                                            @input="updatePrice($event.target.value)"
                                                            class="w-full bg-white border border-amber-200 rounded-lg pl-8 pr-2 py-2 text-sm font-bold text-amber-900 focus:ring-amber-500 focus:border-amber-500 shadow-sm"
                                                            title="Edit harga jual kustom untuk barang pengganti ini">
                                                    </div>
                                                @else
                                                    <div class="bg-white border border-amber-100 rounded-lg px-3 py-2">
                                                        <p class="font-bold text-amber-900 text-sm">Rp
                                                            {{ number_format($newPrice, 0, ',', '.') }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="border-l-2 border-amber-200 pl-4 flex-1">
                                                <p class="text-[10px] uppercase font-bold text-amber-700 mb-1">
                                                    Selisih</p>
                                                @if ($diff > 0)
                                                    <p class="font-black text-rose-600 text-sm">+ Rp
                                                        {{ number_format($diff, 0, ',', '.') }} <span
                                                            class="text-[10px] font-normal block leading-tight">Pelanggan
                                                            Kurang Bayar</span></p>
                                                @elseif($diff < 0)
                                                    <p class="font-black text-emerald-600 text-sm">- Rp
                                                        {{ number_format(abs($diff), 0, ',', '.') }} <span
                                                            class="text-[10px] font-normal block leading-tight">Refund
                                                            ke Pelanggan</span></p>
                                                @else
                                                    <p class="font-black text-amber-900 text-sm">Rp 0</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>


                                @endif
                            </div>
                        @endif

                        <div class="relative z-40">
                            <label class="block text-sm font-bold text-gray-700 mb-1">IMEI / Serial Number Baru
                                <span class="text-amber-500">*</span></label>

                            @if ($replacement_imei)
                                <div
                                    class="flex items-center justify-between p-3 bg-amber-50 border border-amber-200 rounded-xl">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span
                                            class="font-mono font-bold text-amber-900">{{ $replacement_imei }}</span>
                                    </div>
                                    <button type="button" wire:click="$set('replacement_imei', '')"
                                        class="text-amber-600 hover:text-amber-800 text-sm font-bold underline">Ubah</button>
                                </div>
                            @else
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 pt-6 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" wire:model.live.debounce.300ms="search_imei_query"
                                    class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-4 p-3 text-sm focus:ring-amber-500 focus:border-amber-500 font-mono shadow-sm"
                                    placeholder="Ketik atau scan SN/IMEI pengganti...">

                                @if (count($imei_results) > 0)
                                    <div
                                        class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                        @foreach ($imei_results as $res)
                                            <button type="button"
                                                wire:click="selectImei('{{ $res['serial_number'] }}')"
                                                class="w-full text-left px-4 py-3 hover:bg-amber-50 transition-colors border-b border-gray-100 last:border-0">
                                                <div class="font-bold text-gray-800 font-mono text-sm">
                                                    {{ $res['serial_number'] }}</div>
                                                <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">
                                                    {{ $res['product_name'] }} (SKU: {{ $res['item_no'] }})</div>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif(strlen($search_imei_query) > 2)
                                    <div
                                        class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 text-center text-sm text-gray-500">
                                        IMEI/SN tidak ditemukan di sistem. Anda bisa tetap menggunakan ini jika
                                        yakin:
                                        <button type="button"
                                            wire:click="selectImei('{{ $search_imei_query }}')"
                                            class="mt-2 block w-full px-4 py-2 bg-amber-100 text-amber-700 rounded-lg font-bold hover:bg-amber-200 transition-colors">
                                            Gunakan "{{ $search_imei_query }}"
                                        </button>
                                    </div>
                                @endif
                            @endif

                            @error('replacement_imei')
                                <div
                                    class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Pilih Sales (Tenaga Penjual) -->
                        <div class="mt-4 mb-4 relative z-30">
                            <label class="block text-sm font-bold text-gray-700 mb-1">
                                Pilih Sales Pengganti <span class="text-amber-500">*</span>
                            </label>
                            
                            @if($selected_sales_id)
                                <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-xl mb-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-blue-900">{{ $search_sales_query }}</div>
                                            <div class="text-xs text-blue-700">Sales Terpilih</div>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="selectSales(null)" class="text-rose-500 hover:bg-rose-100 p-1.5 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 pt-6 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text"
                                        wire:model.live.debounce.300ms="search_sales_query"
                                        placeholder="Ketik nama sales..."
                                        class="w-full bg-white border-gray-300 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-amber-500 focus:border-amber-500 transition-colors">

                                    @if(strlen($search_sales_query) >= 2)
                                        <div class="absolute z-[60] w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                            @forelse($this->salesResults as $sales)
                                                <button type="button"
                                                    wire:click="selectSales({{ $sales->id }})"
                                                    class="w-full text-left px-4 py-3 hover:bg-amber-50 transition-colors border-b border-gray-100 last:border-0">
                                                    <div class="font-bold text-gray-800 text-sm">{{ $sales->name }}</div>
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        No Karyawan: {{ $sales->employee_no ?? '-' }}
                                                    </div>
                                                </button>
                                            @empty
                                                <div class="px-4 py-3 text-center text-sm text-gray-500">
                                                    Sales tidak ditemukan.
                                                </div>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                                <p class="mt-1.5 text-xs text-gray-500">
                                    Salesperson akan dicatat pada faktur pengganti (Sales Invoice Baru) untuk KPI Sales.
                                </p>
                            @endif

                            @error('selected_sales_id')
                                <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Catatan Tambahan -->
                        <div class="mt-4 mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">
                                Catatan Tambahan (Opsional)
                            </label>
                            <textarea wire:model="manual_note" rows="2"
                                class="w-full bg-white border border-gray-300 rounded-xl p-3 text-sm focus:ring-amber-500 focus:border-amber-500 shadow-sm"
                                placeholder="Ketik catatan tambahan retur di sini..."></textarea>
                        </div>

                        <!-- Pilih Bank untuk Refund/Pembayaran/Offsetting -->
                        <div class="mt-4 mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">
                                Pilih Bank / Kas Pembayaran <span class="text-amber-500">*</span>
                            </label>
                            <input type="text" value="10.02.103" disabled
                                class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-sm text-gray-500 shadow-sm cursor-not-allowed">
                            <p class="mt-1.5 text-xs text-gray-500">
                                Akun ini digunakan oleh Accurate untuk membuat Sales Receipt pelunasan faktur atau
                                pencatatan piutang.
                            </p>
                            @error('bank_no')
                                <div
                                    class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-600 text-xs font-medium">
                                    Anda wajib memilih bank untuk syarat dokumen Accurate.
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
                                <p class="mb-1">Pastikan IMEI baru telah disiapkan. Sistem akan menembak secara
                                    *realtime*:</p>
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li><b>Sales Return</b> (menarik IMEI lama)</li>
                                    <li><b>Sales Invoice</b> (mengeluarkan IMEI baru)</li>
                                    <li><b>Sales Receipt</b> (menyelesaikan piutang/refund otomatis)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                        </div>
                    @endif

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="closeReplacementForm"
                            class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Tutup</button>
                        
                        @if(!$hasPendingApproval)
                        <button type="button" wire:click="approveReplacement"
                            class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2"
                            wire:loading.attr="disabled" wire:target="approveReplacement">
                            <span wire:loading.remove wire:target="approveReplacement">Ajukan Ganti Unit</span>
                            <span wire:loading wire:target="approveReplacement">Sedang Menyimpan...</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

<!-- Modal Service Center -->
<div>
    @if ($showServiceForm && $selectedClaimId)
        @php
            $selectedClaim = $selectedClaimObj;
        @endphp
        @if ($selectedClaim)
            <div wire:key="modal-service-form" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/60 min-h-screen backdrop-blur-sm transition-opacity">
                </div>
                <div
                    class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-blue-50">
                        <div>
                            <h3 class="font-bold text-xl text-blue-900">Form Persetujuan Servis (Perbaikan)</h3>
                            <p class="text-xs text-blue-700 mt-0.5">Kirim perangkat ini ke Service Center internal
                                /
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
                                <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Persetujuan
                                    Servis
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
                                <span wire:loading.remove wire:target="approveService">Setujui & Proses
                                    Servis</span>
                                <span wire:loading wire:target="approveService">Memproses...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<!-- Modal Form Tolak Klaim -->
@if ($showRejectForm && $selectedClaimId)
    @php
        $selectedClaim = $selectedClaimObj;
    @endphp
    @if ($selectedClaim)
        <div wire:key="modal-reject-form" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
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
    <div wire:key="modal-process" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
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
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div
                                class="bg-gray-50 border-b border-gray-100 px-5 py-3 flex justify-between items-center">
                                <h4 class="text-sm font-black text-gray-700 uppercase tracking-wider">
                                    Control Panel: Resolusi Klaim
                                </h4>
                                <div class="text-xs text-gray-500 font-medium">
                                    ID: <span
                                        class="font-mono text-gray-800">{{ $selectedClaim->claim_number }}</span>
                                </div>
                            </div>

                            <div class="p-5">
                                <!-- Timestamps & Actors Info -->
                                <div
                                    class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 pb-6 border-b border-gray-100">
                                    <div>
                                        <span
                                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Diajukan
                                            Oleh</span>
                                        <span
                                            class="text-sm font-semibold text-gray-800">{{ $selectedClaim->claimedBy->name ?? 'Kasir / Sistem' }}</span>
                                        <span
                                            class="block text-xs text-gray-500">{{ $selectedClaim->claimed_at ? $selectedClaim->claimed_at->format('d M Y, H:i') : '-' }}</span>
                                    </div>
                                    @if ($selectedClaim->approvedBy)
                                        <div>
                                            <span
                                                class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Di-Approve
                                                Oleh</span>
                                            <span
                                                class="text-sm font-semibold text-blue-700">{{ $selectedClaim->approvedBy->name }}</span>
                                            <span
                                                class="block text-xs text-gray-500">{{ $selectedClaim->updated_at->format('d M Y, H:i') }}</span>
                                        </div>
                                    @endif
                                    @if ($selectedClaim->resolved_at)
                                        <div>
                                            <span
                                                class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Waktu
                                                Selesai</span>
                                            <span
                                                class="text-sm font-semibold text-emerald-700">{{ $selectedClaim->resolved_at->format('d M Y, H:i') }}</span>
                                        </div>
                                    @endif
                                </div>

                                <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Tindakan
                                    Sistem & Accurate</h5>
                                <div class="flex flex-wrap gap-3">
                                    @if ($selectedClaim->status === 'pending' && !$hasPendingApproval)
                                        <button wire:click="openReplacementForm"
                                            class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm transition-all shadow-sm shadow-amber-500/20 flex flex-col items-start gap-1">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                                Ganti Unit (Accurate API)
                                            </span>
                                            <span class="text-[10px] font-normal text-amber-100">Otomatis buat
                                                Sales Return & Invoice</span>
                                        </button>
                                        <button wire:click="openServiceForm"
                                            class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm transition-all shadow-sm shadow-blue-600/20 flex flex-col items-start gap-1">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Klaim Biasa (Servis)
                                            </span>
                                            <span class="text-[10px] font-normal text-blue-200">Perbaikan fisik
                                                tanpa ganti unit</span>
                                        </button>
                                        <button wire:click="openRejectForm"
                                            class="px-5 py-3 bg-rose-500 hover:bg-rose-600 text-white font-bold rounded-xl text-sm transition-all shadow-sm shadow-rose-500/20 flex flex-col items-start gap-1">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Tolak Klaim
                                            </span>
                                            <span class="text-[10px] font-normal text-rose-100">Batal / Tidak Berlaku</span>
                                        </button>
                                    @elseif ($selectedClaim->status === 'pending' && $hasPendingApproval)
                                        <div class="px-5 py-3 bg-amber-50 border border-amber-200 text-amber-700 font-bold rounded-xl text-sm w-full flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span>Sedang Menunggu Persetujuan Atasan (Approval Pending)</span>
                                            </div>
                                            <span class="text-xs bg-amber-200 text-amber-800 px-2 py-1 rounded-md">Mohon tunggu</span>
                                        </div>
                                    @endif

                                    @if ($selectedClaim->status === 'approved')
                                        <button wire:click="updateStatus('in_repair')"
                                            class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-sm transition-colors shadow-sm shadow-purple-500/20 flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                            Mulai Proses Perbaikan (In Repair)
                                        </button>
                                    @endif

                                    @if (in_array($selectedClaim->status, ['approved', 'in_repair']))
                                        <button wire:click="updateStatus('completed')"
                                            class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition-colors shadow-sm shadow-emerald-500/20 flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Tandai Selesai (Completed)
                                        </button>
                                    @endif

                                    @if (in_array($selectedClaim->status, ['waiting_payment', 'waiting_refund']))
                                        <div class="px-4 py-3 bg-blue-50 border border-blue-200 text-blue-700 font-bold rounded-xl text-sm flex items-center gap-2 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Menunggu Proses Keuangan (Silakan cek di Finance Dashboard)
                                        </div>
                                    @endif
                                </div>

                                    @if (in_array($selectedClaim->status, ['completed', 'rejected']))
                                        <div
                                            class="w-full px-6 py-5 bg-gray-50/80 border border-gray-200 rounded-2xl shadow-inner relative overflow-hidden">
                                            <div
                                                class="absolute top-0 left-0 w-1 h-full {{ $selectedClaim->status === 'completed' ? 'bg-emerald-500' : 'bg-rose-500' }}">
                                            </div>

                                            @if ($selectedClaim->status === 'completed')
                                                <p
                                                    class="text-base font-black text-emerald-800 flex items-center gap-2">
                                                    <svg class="w-6 h-6 text-emerald-500" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Klaim Garansi telah diselesaikan
                                                    @if ($selectedClaim->resolution === 'replaced')
                                                        <span
                                                            class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded text-xs ml-1">(Ganti
                                                            Unit)</span>
                                                    @elseif($selectedClaim->resolution === 'repaired')
                                                        <span
                                                            class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs ml-1">(Service/Perbaikan)</span>
                                                    @endif
                                                </p>
                                            @else
                                                <p
                                                    class="text-base font-black text-rose-800 flex items-center gap-2">
                                                    <svg class="w-6 h-6 text-rose-500" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Klaim Garansi ini telah ditolak.
                                                </p>
                                            @endif

                                            @if ($selectedClaim->resolution_notes)
                                                <div
                                                    class="mt-3 bg-white p-3 rounded-lg border border-gray-100 text-sm text-gray-600 italic">
                                                    <span
                                                        class="font-bold text-gray-400 not-italic mr-2">"</span>{{ $selectedClaim->resolution_notes }}<span
                                                        class="font-bold text-gray-400 not-italic ml-2">"</span>
                                                </div>
                                            @endif

                                            @if ($selectedClaim->resolution === 'replaced')
                                                @php
                                                    $replacementOrder = \App\Models\Order::with('accurateDocs')
                                                        ->where('order_number', 'WR-' . $selectedClaim->claim_number)
                                                        ->first();
                                                @endphp
                                                @if ($replacementOrder && $replacementOrder->accurateDocs->count() > 0)
                                                    <div class="mt-5 border-t border-gray-200 pt-4">
                                                        <p
                                                            class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                                                            Dokumen Accurate (Auto-Generated):</p>
                                                        <div class="flex flex-wrap gap-3">
                                                            @foreach ($replacementOrder->accurateDocs as $doc)
                                                                <div
                                                                    class="bg-white border-b-2 border-emerald-400 px-4 py-2.5 rounded-t-lg shadow-sm flex flex-col gap-1 min-w-[140px]">
                                                                    <span
                                                                        class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ str_replace('_', ' ', $doc->doc_type) }}</span>
                                                                    <span
                                                                        class="font-mono text-gray-800 font-bold text-sm">{{ $doc->doc_number }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Audit Trail / Riwayat Status -->
                            <div class="mt-8 px-4 border-t border-gray-100 pt-6">
                                <h5
                                    class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Audit Trail (Riwayat Status)
                                </h5>
                                @php
                                    $histories = $selectedClaim
                                        ->claimsHistory()
                                        ->with('user')
                                        ->orderBy('created_at', 'desc')
                                        ->get();
                                @endphp
                                @if ($histories->count() > 0)
                                    <div class="space-y-0 relative">
                                        @foreach ($histories as $history)
                                            <div class="flex gap-4 relative pb-5">
                                                <!-- Timeline Line -->
                                                @if (!$loop->last)
                                                    <div
                                                        class="absolute top-2 bottom-0 left-[5px] w-[2px] bg-gray-100 z-0">
                                                    </div>
                                                @endif

                                                <!-- Dot -->
                                                <div
                                                    class="relative z-10 w-3 h-3 rounded-full bg-[#1c69d4] mt-1 shrink-0 ring-4 ring-white">
                                                </div>

                                                <!-- Content -->
                                                <div class="flex-1 -mt-0.5">
                                                    <div class="flex items-center gap-2 mb-0.5">
                                                        <span
                                                            class="text-sm font-bold text-gray-800">{{ ucwords(str_replace('_', ' ', $history->status)) }}</span>
                                                        <span class="text-xs text-gray-400">&bull;</span>
                                                        <span
                                                            class="text-[11px] font-medium text-gray-500">{{ $history->created_at->format('d M Y, H:i') }}</span>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mb-1.5">
                                                        Oleh: <span
                                                            class="font-bold text-gray-700">{{ $history->user->name ?? 'Sistem' }}</span>
                                                    </div>
                                                    @if ($history->notes)
                                                        <div
                                                            class="text-[11px] text-gray-600 bg-gray-50 px-3 py-2 rounded-lg border border-gray-100 mt-1 inline-block">
                                                            {{ $history->notes }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p
                                        class="text-sm text-gray-400 italic bg-gray-50 p-4 rounded-xl text-center border border-gray-100 border-dashed">
                                        Belum ada riwayat perubahan status.</p>
                                @endif
                            </div>
                        </div>
                </div>
            @else
                @php
                    $qcData = $viewingQcDetails === 'original' ? $originalInspection : $claimInspection;
                    $qcTitle = $viewingQcDetails === 'original' ? 'QC Saat Beli (Unboxing)' : 'QC Saat Klaim Masuk';
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
                                $groupedResults = collect($qcData->checklist_results ?? [])->groupBy('category');
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($groupedResults as $category => $items)
                                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
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



<!-- Modal Konfirmasi Retur Accurate -->
<div>
@if ($showReplacementConfirmModal && $selectedClaimId)
    @php
        $selectedClaim =
            $selectedClaimObj ?? \App\Models\WarrantyClaim::with('warranty.orderItem.variant')->find($selectedClaimId);
        $oldProductName = $selectedClaim->warranty->orderItem->product_name ?? '-';
        $oldImei = $selectedClaim->warranty->serial_number ?? '-';

        $newProductName = $replacement_type === 'different' ? $replacement_product_name : $oldProductName;
        $newPrice = $replacement_type === 'different' ? $replacement_price : $original_price;
        $diff = $newPrice - $original_price;
    @endphp
    <div wire:key="modal-confirm-replacement" class="fixed inset-0 flex items-center justify-center p-4"
        style="z-index: 150;">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            wire:click="cancelReplacementConfirm"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200"
            style="animation: scaleUp 0.2s ease-out">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-amber-50">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-amber-900">Konfirmasi Eksekusi Accurate</h3>
                </div>
                <button type="button" wire:click="cancelReplacementConfirm"
                    class="text-gray-400 hover:text-gray-500 hover:bg-white p-2 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5">
                <p class="text-sm text-gray-600 mb-4">Anda akan memproses retur garansi ini ke Accurate. Berikut
                    adalah ringkasan aksi yang akan dicatat:</p>

                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-4 relative overflow-hidden">
                    <div class="absolute left-6 top-10 bottom-10 w-0.5 bg-gray-200 z-0"></div>

                    <!-- Produk Lama -->
                    <div class="relative z-10 flex gap-4 mb-5">
                        <div
                            class="w-8 h-8 rounded-full bg-gray-200 border-4 border-gray-50 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-0.5">Ditarik
                                (Sales Return)</div>
                            <div class="text-gray-900 font-medium text-sm">{{ $oldProductName }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">IMEI: <span
                                    class="font-mono font-medium text-gray-700">{{ $oldImei }}</span></div>
                        </div>
                    </div>

                    <!-- Produk Baru -->
                    <div class="relative z-10 flex gap-4">
                        <div
                            class="w-8 h-8 rounded-full bg-amber-100 border-4 border-gray-50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-0.5">Pengganti
                                (Sales Invoice Baru)</div>
                            <div class="text-gray-900 font-medium text-sm">{{ $newProductName }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">IMEI: <span
                                    class="font-mono font-medium text-amber-700">{{ $replacement_imei }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($diff > 0)
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3.5 flex gap-3">
                        <div class="bg-blue-100 text-blue-600 p-2 rounded-lg shrink-0 h-fit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-blue-900">Upgrade Unit (+ Rp
                                {{ number_format($diff, 0, ',', '.') }})</div>
                            <div class="text-xs text-blue-700 leading-relaxed mt-0.5">Pelanggan memilih unit yang
                                lebih mahal. Accurate akan secara otomatis mencatat selisih ini sebagai piutang
                                (Sales Invoice baru lebih besar nilainya), lalu dibayarkan melalui Sales Receipt ke
                                bank/kas yang Anda pilih.</div>
                        </div>
                    </div>
                @elseif($diff < 0)
                    <div class="bg-rose-50 border border-rose-100 rounded-xl p-3.5 flex gap-3">
                        <div class="bg-rose-100 text-rose-600 p-2 rounded-lg shrink-0 h-fit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-rose-900">Downgrade Unit (- Rp
                                {{ number_format(abs($diff), 0, ',', '.') }})</div>
                            <div class="text-xs text-rose-700 leading-relaxed mt-0.5">Pelanggan memilih unit yang
                                lebih murah. Klaim ini akan dialihkan ke status "Menunggu Refund" agar kasir bisa
                                mencairkan uang tunai tersebut.</div>
                        </div>
                    </div>
                @else
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3.5 flex gap-3">
                        <div class="bg-emerald-100 text-emerald-600 p-2 rounded-lg shrink-0 h-fit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-emerald-900">Ganti Unit Sama (1:1)</div>
                            <div class="text-xs text-emerald-700 leading-relaxed mt-0.5">Penukaran unit tanpa ada
                                biaya tambahan (Selisih Rp 0). Sales Invoice dan Sales Receipt baru akan otomatis
                                memotong sisa overpayment dari Retur tanpa melibatkan uang baru.</div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                <button type="button" wire:click="cancelReplacementConfirm"
                    class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-200 hover:text-gray-900 rounded-xl transition-colors">
                    Kembali
                </button>
                <button type="button" wire:click="approveReplacement"
                    class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl shadow-sm transition-all flex items-center gap-2"
                    wire:loading.attr="disabled" wire:target="approveReplacement">
                    <span wire:loading.remove wire:target="approveReplacement">Yakin, Eksekusi Sekarang</span>
                    <span wire:loading wire:target="approveReplacement">Sedang Menembak API...</span>
                </button>
            </div>
        </div>
    </div>
@endif

</div>
