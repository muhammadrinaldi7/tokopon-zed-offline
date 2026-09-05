<div class="p-6">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Persetujuan Transaksi</h2>
            <p class="text-gray-600 text-sm mt-1">Kelola pengajuan pembatalan, buyback, perpanjangan garansi, dll.</p>
        </div>
        @if($isGlobal && count($businessUnits) > 1)
        <div class="flex items-center gap-2 bg-gray-100 p-1.5 rounded-xl text-xs font-bold">
            <span class="text-gray-500 px-2">Unit:</span>
            <button wire:click="$set('filterBusinessUnitId', null)"
                class="px-3 py-1.5 rounded-lg transition-all {{ is_null($filterBusinessUnitId) ? 'bg-white text-blue-600 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                Semua Unit
            </button>
            @foreach($businessUnits as $bu)
                <button wire:click="$set('filterBusinessUnitId', {{ $bu->id }})"
                    class="px-3 py-1.5 rounded-lg transition-all {{ $filterBusinessUnitId == $bu->id ? 'bg-white text-blue-600 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                    {{ $bu->name }}
                </button>
            @endforeach
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama pemohon..." class="px-4 py-2 border border-gray-200 rounded-lg text-sm w-64 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                <select wire:model.live="filterStatus" class="px-4 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="ALL">Semua Status</option>
                    <option value="PENDING">Menunggu Persetujuan</option>
                    <option value="APPROVED">Disetujui</option>
                    <option value="REJECTED">Ditolak</option>
                    <option value="COMPLETED">Selesai</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Tgl Pengajuan</th>
                        <th class="px-6 py-3 font-semibold">Unit Bisnis</th>
                        <th class="px-6 py-3 font-semibold">Pemohon</th>
                        <th class="px-6 py-3 font-semibold">Cabang</th>
                        <th class="px-6 py-3 font-semibold">Tipe & Dokumen</th>
                        <th class="px-6 py-3 font-semibold">Alasan</th>
                        <th class="px-6 py-3 font-semibold">Status & Level</th>
                        <th class="px-6 py-3 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($requests as $req)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                            {{ $req->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($req->business_unit_id == 1)
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-black rounded-lg uppercase">Syihab</span>
                            @elseif($req->business_unit_id == 2)
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black rounded-lg uppercase">GSK Second</span>
                            @elseif($req->business_unit_id == 3)
                                <span class="px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-black rounded-lg uppercase">GSK Distri</span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold rounded-lg uppercase">{{ $req->businessUnit->name ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-gray-900">{{ $req->requestedBy->name ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-900">{{ $req->branch?->name ?? ($req->requestedBy->branch->name ?? '-') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-2 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-bold rounded uppercase mb-1">
                                {{ str_replace('_', ' ', $req->request_type) }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1 font-mono">
                                @if($req->request_type === 'CUSTOM_CASHBACK')
                                    Item: {{ $req->payload['product_name'] ?? '-' }}<br>
                                    Nominal: Rp {{ number_format($req->payload['amount'] ?? 0, 0, ',', '.') }}
                                @elseif($req->approvable_type === 'App\Models\Order' || $req->approvable instanceof \App\Models\Order)
                                    Order: {{ $req->approvable->order_number ?? '-' }}
                                @elseif($req->approvable_type === 'App\Models\SellPhone' || $req->approvable instanceof \App\Models\SellPhone)
                                    HP: {{ $req->approvable->phone_brand ?? '' }} {{ $req->approvable->phone_model ?? '' }}
                                    @if($req->approvable?->appraised_value)
                                        <br><span class="text-gray-600 font-bold">Rp {{ number_format($req->approvable->appraised_value, 0, ',', '.') }}</span>
                                    @endif
                                @else
                                    ID: {{ $req->approvable_id }}
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs max-w-xs truncate" title="{{ $req->reason }}">{{ $req->reason }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($req->status === 'PENDING')
                                <span class="px-2 py-1 bg-amber-50 text-amber-700 text-[10px] font-bold rounded uppercase">Pending</span>
                            @elseif($req->status === 'APPROVED')
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded uppercase">Approved</span>
                            @elseif($req->status === 'COMPLETED')
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold rounded uppercase">Selesai</span>
                            @else
                                <span class="px-2 py-1 bg-red-50 text-red-700 text-[10px] font-bold rounded uppercase">Ditolak</span>
                            @endif
                            <div class="text-[10px] text-gray-500 mt-1">Level: {{ $req->current_level }} / {{ $req->required_level }}</div>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Tombol Lihat Detail (Selalu muncul untuk semua status) --}}
                                <button wire:click="viewDetail({{ $req->id }})" class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition cursor-pointer" title="Lihat Detail & Struk">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                @if($req->status === 'PENDING')
                                    <button wire:click="confirmApprove({{ $req->id }})" class="p-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-lg transition cursor-pointer" title="Setujui">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                    <button wire:click="confirmReject({{ $req->id }})" class="p-1.5 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition cursor-pointer" title="Tolak">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 font-medium">Terkunci</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <p class="text-gray-500 font-medium text-sm">Belum ada pengajuan persetujuan untuk filter ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

    <!-- Final Level Warning Modal -->
    @if($confirmingApprovalId)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm p-4">
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100">
            <div class="p-6 text-center">
                @if($confirmingRequestType === 'WARRANTY_EXTENSION')
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4 border-4 border-blue-50">
                    <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                
                <h3 class="mb-2 text-xl font-bold text-gray-900">Perpanjang Garansi</h3>
                <p class="text-sm text-gray-500 mb-4 font-medium leading-relaxed">
                    Silakan tentukan berapa lama garansi akan diperpanjang (dihitung sejak hari ini atau dari batas kadaluarsa).
                </p>
                <div class="mb-6 text-left">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Durasi (Hari)</label>
                    <input type="number" wire:model="extensionDays" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center font-bold text-lg" min="1" max="365">
                </div>
                @elseif($confirmingRequestType === 'CUSTOM_CASHBACK')
                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4 border-4 border-emerald-50">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                
                <h3 class="mb-2 text-xl font-bold text-gray-900">Konfirmasi Cashback Kustom</h3>
                <p class="text-sm text-gray-500 mb-6 font-medium leading-relaxed">
                    Anda akan menyetujui permintaan cashback kustom ini. Diskon akan langsung diterapkan di perangkat Kasir secara otomatis.
                </p>
                @elseif($confirmingRequestType === 'WARRANTY_REPLACEMENT')
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4 border-4 border-blue-50">
                    <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                
                <h3 class="mb-2 text-xl font-bold text-gray-900">Konfirmasi Eksekusi Ganti Unit</h3>
                <p class="text-sm text-gray-500 mb-6 font-medium leading-relaxed">
                    Menyetujui pengajuan ini akan otomatis mengeksekusi retur dan penggantian unit di Accurate (membuat <b>Sales Return</b>, <b>Sales Invoice</b>, dan <b>Sales Receipt</b>).
                    <br><br>Apakah Anda yakin ingin melanjutkan?
                </p>
                @elseif($confirmingRequestType === 'SELL_PHONE_APPROVAL')
                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4 border-4 border-amber-50">
                    <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                
                <h3 class="mb-2 text-xl font-bold text-gray-900">Persetujuan & Edit Harga</h3>
                <p class="text-sm text-gray-500 mb-4 font-medium leading-relaxed">
                    Anda dapat menyetujui dengan harga asli, atau menyesuaikan harga terlebih dahulu sebelum menyetujui.
                </p>

                <div class="mb-5 text-left bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        Harga Disetujui (Rp)
                    </label>
                    <div class="relative mb-3" wire:key="approval-adjusted-price" x-data="{
                        rawVal: @entangle('adjustedPrice'),
                        get maskedVal() {
                            if (!this.rawVal && this.rawVal !== 0) return '';
                            return this.rawVal.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        },
                        set maskedVal(val) {
                            this.rawVal = val ? parseInt(val.replace(/\D/g, '')) || 0 : 0;
                        }
                    }">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">Rp</span>
                        <input type="text" x-model="maskedVal"
                            @keydown="if (!/[0-9]|Backspace|Delete|Tab|Arrow/.test($event.key)) $event.preventDefault()"
                            class="w-full pl-11 pr-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition font-mono"
                            placeholder="0">
                    </div>
                    
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        Alasan Perubahan Harga <span class="text-xs font-normal text-gray-500">(Wajib jika harga diubah)</span>
                    </label>
                    <textarea 
                        wire:model="priceAdjustmentReason" 
                        rows="2" 
                        placeholder="Contoh: Kondisi fisik lebih buruk dari deskripsi..."
                        class="w-full px-3.5 py-2.5 bg-white border @error('priceAdjustmentReason') border-rose-300 ring-1 ring-rose-300 @else border-gray-300 @enderror rounded-xl text-xs focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition resize-none"></textarea>
                    @error('priceAdjustmentReason')
                        <p class="text-rose-600 text-[11px] mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                @else
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4 border-4 border-red-50">
                    <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                
                <h3 class="mb-2 text-xl font-bold text-gray-900">Peringatan Tahap Akhir</h3>
                <p class="text-sm text-gray-500 mb-6 font-medium leading-relaxed">
                    Anda berada di tingkat persetujuan terakhir. Jika Anda menyetujui ini, dokumen <b>Sales Receipt</b> dan <b>Sales Invoice</b> terkait akan otomatis dihapus secara permanen di Accurate.
                    <br><br>Apakah Anda yakin ingin melanjutkan?
                </p>
                @endif
                
                <div class="flex justify-center gap-3">
                    <button wire:click="cancelApprove" type="button" class="px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-blue-700 focus:ring-4 focus:ring-gray-100 transition-colors cursor-pointer">
                        Batal
                    </button>
                    @if($confirmingRequestType === 'WARRANTY_EXTENSION')
                    <button wire:click="executeApprove" type="button" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors shadow-md shadow-blue-500/20 cursor-pointer">
                        Setujui Perpanjangan
                    </button>
                    @elseif($confirmingRequestType === 'CUSTOM_CASHBACK')
                    <button wire:click="executeApprove" type="button" class="px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 transition-colors shadow-md shadow-emerald-500/20 cursor-pointer">
                        Setujui Cashback
                    </button>
                    @elseif($confirmingRequestType === 'WARRANTY_REPLACEMENT')
                    <button wire:click="executeApprove" type="button" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors shadow-md shadow-blue-500/20 cursor-pointer">
                        Setujui Ganti Unit
                    </button>
                    @elseif($confirmingRequestType === 'SELL_PHONE_APPROVAL')
                    <button wire:click="executeApprove" type="button" class="px-5 py-2.5 text-sm font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 focus:ring-4 focus:ring-amber-300 transition-colors shadow-md shadow-amber-500/20 cursor-pointer">
                        Simpan & Setujui
                    </button>
                    @else
                    <button wire:click="executeApprove" type="button" class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 focus:ring-4 focus:ring-red-300 transition-colors shadow-md shadow-red-500/20 cursor-pointer">
                        Ya, Setujui & Hapus
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Rejection Modal -->
    @if($rejectingApprovalId)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden" @click.outside="$wire.cancelReject()">
            <div class="p-6">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                
                <h3 class="text-center text-lg font-bold text-gray-900 mb-1">Tolak Pengajuan</h3>
                <p class="text-center text-xs text-gray-500 mb-4">
                    Silakan tuliskan alasan penolakan. Alasan ini akan tercatat dalam riwayat persetujuan.
                </p>

                <div class="mb-5 text-left">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        Alasan Penolakan <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        wire:model="rejectionReason" 
                        rows="3" 
                        placeholder="Contoh: Dokumen tidak lengkap / Stok sudah terlanjur dialokasikan..."
                        class="w-full px-3.5 py-2.5 bg-gray-50 border @error('rejectionReason') border-rose-300 ring-1 ring-rose-300 @else border-gray-200 @enderror rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition resize-none"></textarea>
                    @error('rejectionReason')
                        <p class="text-rose-600 text-[11px] mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2.5">
                    <button wire:click="cancelReject" type="button" class="px-4 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition shadow-sm cursor-pointer">
                        Batal
                    </button>
                    <button wire:click="executeReject" type="button" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 focus:ring-4 focus:ring-rose-200 transition shadow-sm shadow-rose-500/20 cursor-pointer">
                        Tolak Pengajuan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Detail & Struk Modal --}}
    @include('livewire.admin.approvals.partials.approval-detail-modal')
</div>
